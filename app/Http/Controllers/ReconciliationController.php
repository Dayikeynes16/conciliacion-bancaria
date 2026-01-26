<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\Conciliacion;
use App\Services\Reconciliation\MatcherService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $teamId = auth()->user()->current_team_id;

        // Fetch unreconciled items
        $invoices = Factura::where('team_id', $teamId)
            ->doesntHave('conciliaciones')
            ->orderBy('fecha_emision', 'desc')
            ->get();

        $movements = Movimiento::where('team_id', $teamId)
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
        $matches = $matcher->findMatches($teamId);

        // Apply high confidence matches automatically? 
        // For now, just return them as suggestions or apply them.
        // Let's apply them if score is 100 (exact amount, close date)
        
        $count = 0;
        foreach ($matches as $match) {
             if ($match['score'] > 95) { // Strict auto-match threshold
                 $matcher->reconcile(
                     [$match['invoice']->id], 
                     [$match['movement']->id], 
                     'automatic'
                 );
                 $count++;
             }
        }

        return back()->with('success', "Auto-reconciliación completada. {$count} coincidencias encontradas y aplicadas.");
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

        // Fetch Invoices that have at least one conciliation
        // grouped by invoice essentially
        $reconciledInvoices = Factura::where('team_id', $teamId)
            ->has('conciliaciones')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', "%{$search}%")
                      ->orWhere('rfc', 'like', "%{$search}%")
                      ->orWhere('uuid', 'like', "%{$search}%")
                      ->orWhere('total', 'like', "%{$search}%");
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
            ],
        ]);
    }

    public function status(Request $request)
    {
        $teamId = auth()->user()->current_team_id;
        $search = $request->input('search');

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
            ->where($invoiceSearch)
            ->with(['conciliaciones.user'])
            ->orderBy('fecha_emision', 'desc')
            ->limit(50)
            ->get();

        $conciliatedMovements = Movimiento::where('team_id', $teamId)
            ->has('conciliaciones')
            ->where($movementSearch)
            ->with(['conciliaciones.user'])
            ->orderBy('fecha', 'desc')
            ->limit(50)
            ->get();

        // Pending Items
        $pendingInvoices = Factura::where('team_id', $teamId)
            ->doesntHave('conciliaciones')
            ->where($invoiceSearch)
            ->orderBy('fecha_emision', 'desc')
            ->get();

        $pendingMovements = Movimiento::where('team_id', $teamId)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                      ->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones')
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
            'filters' => ['search' => $search],
        ]);
    }
}
