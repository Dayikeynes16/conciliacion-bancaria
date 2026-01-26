<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Movimiento;
use App\Models\Conciliacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $teamId = auth()->user()->current_team_id;

        // Statistics
        $pendingInvoicesCount = Factura::where('team_id', $teamId)
            ->doesntHave('conciliaciones')
            ->count();

        $totalPendingInvoicesAmount = Factura::where('team_id', $teamId)
            ->doesntHave('conciliaciones')
            ->sum('monto');

        $pendingMovementsCount = Movimiento::where('team_id', $teamId)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                      ->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones')
            ->count();

        $totalPendingMovementsAmount = Movimiento::where('team_id', $teamId)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                      ->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones')
            ->sum('monto');

        $conciliatedThisMonth = Conciliacion::whereHas('factura', function ($q) use ($teamId) {
            $q->where('team_id', $teamId);
        })
        ->whereMonth('created_at', Carbon::now()->month)
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

        return Inertia::render('Dashboard', [
            'stats' => [
                'pendingInvoices' => $pendingInvoicesCount,
                'pendingInvoicesAmount' => $totalPendingInvoicesAmount,
                'pendingMovements' => $pendingMovementsCount,
                'pendingMovementsAmount' => $totalPendingMovementsAmount,
                'conciliatedThisMonth' => $conciliatedThisMonth,
            ],
            'recentActivity' => $recentConciliations,
        ]);
    }
}
