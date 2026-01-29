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

        // Fetch Tolerance Settings
        $tolerancia = \App\Models\Tolerancia::firstOrCreate(
            ['team_id' => $teamId],
            ['monto' => 0.00, 'user_id' => auth()->id()]
        );
        $toleranceAmount = (float) ($tolerancia->monto ?? 0.00);

        return Inertia::render('Reconciliation/Workbench', [
            'invoices' => $invoices,
            'movements' => $movements,
            'tolerance' => $toleranceAmount,
        ]);
    }

    public function auto(Request $request, MatcherService $matcher)
    {
        $teamId = auth()->user()->current_team_id;

        // Fetch Tolerance Settings
        $tolerancia = \App\Models\Tolerancia::firstOrCreate(
            ['team_id' => $teamId],
            ['monto' => 0.00, 'user_id' => auth()->id()]
        );
        $toleranceAmount = (float) ($tolerancia->monto ?? 0.00);

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

        $month = $request->input('month');
        $year = $request->input('year');

        // 1. paginate distinct group_ids
        $query = Conciliacion::query()
            ->join('facturas', 'conciliacions.factura_id', '=', 'facturas.id')
            ->join('movimientos', 'conciliacions.movimiento_id', '=', 'movimientos.id')
            ->where('facturas.team_id', $teamId) // Ensure team ownership
            ->whereMonth('conciliacions.created_at', $month)
            ->whereYear('conciliacions.created_at', $year)
            ->distinct()
            ->select('conciliacions.group_id', 'conciliacions.created_at'); // Select created_at for sorting

        if ($search) {
             // Search logic is harder with groups. 
             // We need groups containing matching invoices/movements.
             $query->where(function($q) use ($search) {
                 $q->where('facturas.nombre', 'like', "%{$search}%")
                   ->orWhere('facturas.rfc', 'like', "%{$search}%")
                   ->orWhere('movimientos.descripcion', 'like', "%{$search}%")
                   ->orWhere('movimientos.referencia', 'like', "%{$search}%")
                   ->orWhere('movimientos.monto', 'like', "%{$search}%");
             });
        }
        
        $groupsPager = $query->latest('conciliacions.created_at')
                             ->paginate(15)
                             ->withQueryString();

        // 2. Fetch details for these groups
        $groupIds = collect($groupsPager->items())->pluck('group_id');
        
        $details = Conciliacion::whereIn('group_id', $groupIds)
            ->with(['factura', 'movimiento', 'user'])
            ->get()
            ->groupBy('group_id');

        // 3. Transform to clean structure
        $transformedGroups = collect($groupsPager->items())->map(function($groupItem) use ($details) {
            $groupId = $groupItem->group_id;
            $items = $details->get($groupId);
            
            if (!$items) return null;

            $first = $items->first();
            
            // Unique Invoices and Movements
            $invoices = $items->pluck('factura')->unique('id')->values();
            $movements = $items->pluck('movimiento')->unique('id')->values();

            $totalInvoices = $invoices->sum('monto');
            $totalMovements = $movements->sum('monto');
            
            // Total Applied in this specific batch? 
            // Sum of monto_aplicado of all items in this group
            $totalApplied = $items->sum('monto_aplicado');

            return [
                'id' => $groupId,
                'created_at' => $first->created_at,
                'user' => $first->user,
                'invoices' => $invoices,
                'movements' => $movements,
                'total_invoices' => $totalInvoices,
                'total_movements' => $totalMovements,
                'total_applied' => $totalApplied,
            ];
        })->filter();

        // Reconstruct paginator with transformed data
        // We use Custom Paginator or just pass 'data' and 'links' manually?
        // Inertia handles LengthAwarePaginator.
        // We can just keep $groupsPager structure but replace 'data'.
        
        $groupsPager->setCollection($transformedGroups);

        return Inertia::render('Reconciliation/History', [
            'reconciledGroups' => $groupsPager,
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

    public function destroyGroup($groupId)
    {
        // Find one record to verify team ownership
        $first = Conciliacion::where('group_id', $groupId)->firstOrFail();
        
        // This check is a bit tricky if we join but simpler:
        // ensure item belongs to user's team.
        // We can just rely on the join logic or check one relation.
        if ($first->factura->team_id !== auth()->user()->current_team_id) {
            abort(403);
        }

        // Delete all with this group_id
        Conciliacion::where('group_id', $groupId)->delete();

        return back()->with('success', 'Grupo de conciliación desvinculado exitosamente.');
    }
}
