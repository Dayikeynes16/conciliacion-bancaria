<?php

namespace App\Http\Controllers;

use App\Models\Conciliacion;
use App\Models\Factura;
use App\Models\Movimiento;
use App\Services\Reconciliation\MatcherService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $teamId = auth()->user()->current_team_id;

        // Middleware sets 'month' and 'year' in request from session/input
        $month = $request->input('month');
        $year = $request->input('year');

        // Fetch unreconciled items
        $invoices = Factura::where('team_id', $teamId)
            ->whereMonth('fecha_emision', $month)
            ->whereYear('fecha_emision', $year)
            ->doesntHave('conciliaciones')
            ->orderBy('fecha_emision', 'desc')
            ->get();

        $movements = Movimiento::where('team_id', $teamId)
            ->whereMonth('fecha', $month)
            ->whereYear('fecha', $year)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                    ->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones')
            ->orderBy('fecha', 'desc')
            ->get();

        return Inertia::render('Reconciliation/Workbench', [
            'invoices' => $invoices,
            'movements' => $movements,
        ]);
    }

    public function auto(Request $request, MatcherService $matcher)
    {
        $teamId = auth()->user()->current_team_id;

        // Fetch Tolerance Settings
        $tolerancia = \App\Models\Tolerancia::firstOrCreate(
            ['team_id' => $teamId],
            ['monto' => 0.00, 'dias' => 0, 'user_id' => auth()->id()]
        );
        $toleranceAmount = (float) ($tolerancia->monto ?? 0.00);
        $toleranceDays = (int) ($tolerancia->dias ?? 0);

        $month = $request->input('month');
        $year = $request->input('year');

        // Find matches using configured tolerance
        // Note: toleranceDays is reused as 'strict month' toggle in a way, or we just ignore it.
        // We pass month/year to filter potential candidates? 
        // Identifying candidates happens inside findMatches usually. 
        // We should update findMatches signature to accept Month/Year context.
        $matches = $matcher->findMatches($teamId, $toleranceAmount, $month, $year);

        // Instead of applying them, return suggestions for user to confirm
        return Inertia::render('Reconciliation/Matches', [
            'matches' => $matches,
            'tolerance' => [
                'amount' => $toleranceAmount,
                'days' => $toleranceDays,
            ],
        ]);
    }

    public function batch(Request $request, MatcherService $matcher)
    {
        $request->validate([
            'matches' => 'required|array',
            'matches.*.invoice_id' => 'required|exists:facturas,id',
            'matches.*.movement_id' => 'required|exists:movimientos,id',
        ]);

        $count = 0;
        foreach ($request->matches as $match) {
            $matcher->reconcile(
                [$match['invoice_id']],
                [$match['movement_id']],
                'automatico' // Use correct enum value
            );
            $count++;
        }

        return redirect()->route('reconciliation.index')->with('success', "Se han conciliado {$count} registros exitosamente.");
    }

    public function store(Request $request, MatcherService $matcher)
    {
        $request->validate([
            'invoice_ids' => 'required|array',
            'movement_ids' => 'required|array',
        ]);

        $matcher->reconcile($request->invoice_ids, $request->movement_ids, 'manual');

        return back()->with('success', 'Conciliación manual registrada exitosamente.');
    }

    public function history(Request $request)
    {
        $teamId = auth()->user()->current_team_id;
        $search = $request->input('search');

        // Middleware sets 'month' and 'year'
        // But history also needs to filter by these?
        // Yes, "all views... only show data for that period".
        // Currently history doesn't filter by month/year in previous turn?
        // Let's add it.

        $month = $request->input('month');
        $year = $request->input('year');

        // Fetch Invoices that have at least one conciliation
        // grouped by invoice essentially
        $reconciledInvoices = Factura::where('team_id', $teamId)
            ->has('conciliaciones')
            // Filter History by Invoice Date? Or Conciliation Date?
            // "Show me facturas and payments of selected..."
            // Usually history shows when it was reconciled, OR the date of the items.
            // Let's stick to Invoice Date for consistency with Workbench.
            ->whereMonth('fecha_emision', $month)
            ->whereYear('fecha_emision', $year)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%")
                        ->orWhere('uuid', 'like', "%{$search}%")
                        ->orWhere('monto', 'like', "%{$search}%");
                });
            })
            ->with(['conciliaciones.movimiento', 'conciliaciones.user'])
            ->latest('updated_at') // Sort by most recently updated/reconciled
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Reconciliation/History', [
            'reconciledGroups' => $reconciledInvoices,
            'filters' => [
                'search' => $search,
                'month' => $month,
                'year' => $year,
            ],
        ]);
    }

    public function status(Request $request)
    {
        $teamId = auth()->user()->current_team_id;
        $search = $request->input('search');
        $month = $request->input('month');
        $year = $request->input('year');

        // Helper closures for search
        $invoiceSearch = function ($query) use ($search) {
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%")
                        ->orWhere('uuid', 'like', "%{$search}%")
                        ->orWhere('monto', 'like', "%{$search}%");
                });
            }
        };

        $movementSearch = function ($query) use ($search) {
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('descripcion', 'like', "%{$search}%")
                        ->orWhere('referencia', 'like', "%{$search}%")
                        ->orWhere('monto', 'like', "%{$search}%");
                });
            }
        };

        // Conciliated Items
        $conciliatedInvoices = Factura::where('team_id', $teamId)
            ->has('conciliaciones')
            ->whereMonth('fecha_emision', $month)
            ->whereYear('fecha_emision', $year)
            ->where($invoiceSearch)
            ->with(['conciliaciones.user'])
            ->orderBy('fecha_emision', 'desc')
            ->limit(50)
            ->get();

        $conciliatedMovements = Movimiento::where('team_id', $teamId)
            ->has('conciliaciones')
            ->whereMonth('fecha', $month)
            ->whereYear('fecha', $year)
            ->where($movementSearch)
            ->with(['conciliaciones.user'])
            ->orderBy('fecha', 'desc')
            ->limit(50)
            ->get();

        // Pending Items
        $pendingInvoices = Factura::where('team_id', $teamId)
            ->doesntHave('conciliaciones')
            ->whereMonth('fecha_emision', $month)
            ->whereYear('fecha_emision', $year)
            ->where($invoiceSearch)
            ->orderBy('fecha_emision', 'desc')
            ->get();

        $pendingMovements = Movimiento::where('team_id', $teamId)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                    ->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones')
            ->whereMonth('fecha', $month)
            ->whereYear('fecha', $year)
            ->where($movementSearch)
            ->orderBy('fecha', 'desc')
            ->get();

        return Inertia::render('Reconciliation/Status', [
            'conciliatedInvoices' => $conciliatedInvoices,
            'conciliatedMovements' => $conciliatedMovements,
            'pendingInvoices' => $pendingInvoices,
            'pendingMovements' => $pendingMovements,
            'totalPendingInvoices' => $pendingInvoices->sum('monto'),
            'totalPendingMovements' => $pendingMovements->sum('monto'),
            'totalConciliatedInvoices' => $conciliatedInvoices->sum('monto'),
            'totalConciliatedMovements' => $conciliatedMovements->sum('monto'),
            'filters' => [
                'search' => $search,
                'month' => $month,
                'year' => $year,
            ],
        ]);
    }

    public function destroy($id)
    {
        $conciliacion = Conciliacion::findOrFail($id);

        // Check ownership via Factura
        if ($conciliacion->factura->team_id !== auth()->user()->current_team_id) {
            abort(403);
        }

        $conciliacion->delete();

        return back()->with('success', 'Conciliación eliminada exitosamente.');
    }
}
