<?php

namespace App\Http\Controllers;

use App\Models\Conciliacion;
use App\Models\Factura;
use App\Models\Movimiento;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $teamId = auth()->user()->current_team_id;
        $month = $request->input('month');
        $year = $request->input('year');

        // Statistics
        $pendingInvoicesCount = Factura::where('team_id', $teamId)
            ->whereMonth('fecha_emision', $month)
            ->whereYear('fecha_emision', $year)
            ->doesntHave('conciliaciones')
            ->count();

        $totalPendingInvoicesAmount = Factura::where('team_id', $teamId)
            ->whereMonth('fecha_emision', $month)
            ->whereYear('fecha_emision', $year)
            ->doesntHave('conciliaciones')
            ->sum('monto');

        $pendingMovementsCount = Movimiento::where('team_id', $teamId)
            ->whereMonth('fecha', $month)
            ->whereYear('fecha', $year)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                    ->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones')
            ->count();

        $totalPendingMovementsAmount = Movimiento::where('team_id', $teamId)
            ->whereMonth('fecha', $month)
            ->whereYear('fecha', $year)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                    ->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones')
            ->sum('monto');

        $conciliatedThisMonth = Conciliacion::whereHas('factura', function ($q) use ($teamId) {
            $q->where('team_id', $teamId);
        })
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        // Recent Activity
        $recentConciliations = Conciliacion::with(['factura', 'movimiento', 'user'])
            ->whereHas('factura', function ($q) use ($teamId) {
                $q->where('team_id', $teamId);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'invoice' => $c->factura->nombre,
                    'amount' => $c->monto_aplicado,
                    'user' => $c->user->name ?? 'Sistema',
                    'date' => $c->created_at->diffForHumans(),
                ];
            });

        // Last Month Statistics
        $lastMonth = Carbon::createFromDate($year, $month, 1)->subMonth();
        
        $conciliatedLastMonth = Conciliacion::whereHas('factura', function ($q) use ($teamId) {
            $q->where('team_id', $teamId);
        })
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $paymentsLastMonth = Movimiento::where('team_id', $teamId)
            ->whereMonth('fecha', $lastMonth->month)
            ->whereYear('fecha', $lastMonth->year)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                    ->orWhere('tipo', 'Abono');
            })
            ->count();

        $invoicesLastMonth = Factura::where('team_id', $teamId)
            ->whereMonth('fecha_emision', $lastMonth->month)
            ->whereYear('fecha_emision', $lastMonth->year)
            ->count();

        return Inertia::render('Dashboard', [
            'stats' => [
                'pendingInvoices' => $pendingInvoicesCount,
                'pendingInvoicesAmount' => $totalPendingInvoicesAmount,
                'pendingMovements' => $pendingMovementsCount,
                'pendingMovementsAmount' => $totalPendingMovementsAmount,
                'conciliatedThisMonth' => $conciliatedThisMonth,
                'conciliatedLastMonth' => $conciliatedLastMonth,
                'invoicesLastMonth' => $invoicesLastMonth,
                'paymentsLastMonth' => $paymentsLastMonth,
            ],
            'recentActivity' => $recentConciliations,
        ]);
    }
}
