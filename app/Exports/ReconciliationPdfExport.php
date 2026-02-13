<?php

namespace App\Exports;

use App\Models\Conciliacion;
use App\Models\Factura;
use App\Models\Movimiento;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReconciliationPdfExport implements FromView, WithTitle
{
    protected $teamId;

    protected $month;

    protected $year;

    protected $dateFrom;

    protected $dateTo;

    public function __construct($teamId, $month, $year, $dateFrom, $dateTo)
    {
        $this->teamId = $teamId;
        $this->month = $month;
        $this->year = $year;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function view(): View
    {
        // --- 1. Fetch Conciliated Groups ---
        $conciliatedQuery = Conciliacion::query()
            ->join('facturas', 'conciliacions.factura_id', '=', 'facturas.id')
            ->join('movimientos', 'conciliacions.movimiento_id', '=', 'movimientos.id')
            ->where('facturas.team_id', $this->teamId);

        if ($this->dateFrom) {
            $conciliatedQuery->whereDate('conciliacions.fecha_conciliacion', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $conciliatedQuery->whereDate('conciliacions.fecha_conciliacion', '<=', $this->dateTo);
        }
        if (! $this->dateFrom && ! $this->dateTo) {
            if ($this->month) {
                $conciliatedQuery->whereMonth('conciliacions.fecha_conciliacion', $this->month);
            }
            if ($this->year) {
                $conciliatedQuery->whereYear('conciliacions.fecha_conciliacion', $this->year);
            }
        }

        // Get all group IDs first to avoid massive join duplication issues if we just get()
        $groupIds = $conciliatedQuery->select('conciliacions.group_id')->distinct()->pluck('group_id');

        // Fetch details
        $details = Conciliacion::whereIn('group_id', $groupIds)
            ->with(['factura', 'movimiento', 'user'])
            ->lazy()
            ->groupBy('group_id');

        $conciliatedGroups = $details->map(function ($items, $groupId) {
            $first = $items->first();
            $invoices = $items->pluck('factura')->unique('id')->values();
            $movements = $items->pluck('movimiento')->unique('id')->values();

            return [
                'id' => $groupId,
                'date' => $first->fecha_conciliacion ?? $first->created_at,
                'user' => $first->user->name ?? 'N/A',
                'invoices' => $invoices,
                'movements' => $movements,
                'total_invoices' => $invoices->sum('monto'),
                'total_movements' => $movements->sum('monto'),
                'difference' => $invoices->sum('monto') - $movements->sum('monto'),
            ];
        })->sortByDesc('date');

        // --- 2. Fetch Pending Invoices ---
        $pendingInvoicesQuery = Factura::where('team_id', $this->teamId)->doesntHave('conciliaciones');
        if ($this->dateFrom) {
            $pendingInvoicesQuery->whereDate('fecha_emision', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $pendingInvoicesQuery->whereDate('fecha_emision', '<=', $this->dateTo);
        }
        if (! $this->dateFrom && ! $this->dateTo) {
            if ($this->month) {
                $pendingInvoicesQuery->whereMonth('fecha_emision', $this->month);
            }
            if ($this->year) {
                $pendingInvoicesQuery->whereYear('fecha_emision', $this->year);
            }
        }
        $pendingInvoices = $pendingInvoicesQuery->orderBy('fecha_emision', 'desc')->lazy();

        // --- 3. Fetch Pending Movements ---
        $pendingMovementsQuery = Movimiento::where('team_id', $this->teamId)
            ->where(function ($q) {
                $q->where('tipo', 'abono')->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones');

        if ($this->dateFrom) {
            $pendingMovementsQuery->whereDate('fecha', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $pendingMovementsQuery->whereDate('fecha', '<=', $this->dateTo);
        }
        if (! $this->dateFrom && ! $this->dateTo) {
            if ($this->month) {
                $pendingMovementsQuery->whereMonth('fecha', $this->month);
            }
            if ($this->year) {
                $pendingMovementsQuery->whereYear('fecha', $this->year);
            }
        }
        $pendingMovements = $pendingMovementsQuery->orderBy('fecha', 'desc')->lazy();

        // --- 4. Summaries ---

        // Strict Data Filtering: Ensure pending items are NOT in conciliated groups
        // Although the query `doesntHave('conciliaciones')` *should* handle this,
        // we enforce it here to be absolutely safe as per user request.

        $conciliatedInvoiceIds = $conciliatedGroups->pluck('invoices')->flatten()->pluck('id')->unique()->toArray();
        $conciliatedMovementIds = $conciliatedGroups->pluck('movements')->flatten()->pluck('id')->unique()->toArray();

        // Convert lazy collections for rejected filtering
        $pendingInvoices = $pendingInvoices->reject(function ($invoice) use ($conciliatedInvoiceIds) {
            return in_array($invoice->id, $conciliatedInvoiceIds);
        });

        $pendingMovements = $pendingMovements->reject(function ($movement) use ($conciliatedMovementIds) {
            return in_array($movement->id, $conciliatedMovementIds);
        });

        $summary = [
            'conciliated_invoices' => $conciliatedGroups->sum('total_invoices'),
            'conciliated_movements' => $conciliatedGroups->sum('total_movements'),
            'pending_invoices' => $pendingInvoices->sum('monto'),
            'pending_movements' => $pendingMovements->sum('monto'),
        ];

        // --- Data Shaping for View ---
        $safeConciliatedGroups = $conciliatedGroups->map(function ($group) {

            // Format dates
            $groupDate = $group['date'] instanceof \Carbon\Carbon
                ? $group['date']
                : \Carbon\Carbon::parse($group['date']);

            return [
                'id' => $group['id'],
                'short_id' => substr($group['id'], 0, 8).'...'.substr($group['id'], -4),
                'date' => $groupDate,
                'user' => $group['user'],
                'total_invoices' => $group['total_invoices'],
                'total_movements' => $group['total_movements'],
                'difference' => $group['difference'],
                'invoices' => $group['invoices']->map(function ($inv) {
                    return (object) [
                        'rfc' => $inv->rfc ?: 'N/A', // Handle null RFC
                        'name' => Str::limit($inv->nombre, 40),
                        'date' => $inv->fecha_emision,
                        'folio' => $inv->folio ?? ($inv->uuid ? substr($inv->uuid, 0, 8) : 'N/A'),
                        'amount' => $inv->monto,
                    ];
                }),
                'movements' => $group['movements']->map(function ($mov) {
                    $bankName = '';
                    if ($mov->banco) {
                        try {
                            if (is_array($mov->banco)) {
                                $bankName = $mov->banco['nombre'] ?? $mov->banco['name'] ?? $mov->banco['codigo'] ?? '';
                            } elseif (is_object($mov->banco)) {
                                $bankName = $mov->banco->nombre ?? $mov->banco->name ?? $mov->banco->codigo ?? '';
                            }
                        } catch (\Exception $e) {
                            $bankName = '';
                        }
                    }

                    return (object) [
                        'description' => Str::limit($mov->descripcion ?? 'Sin descripción', 60),
                        'bank_label' => $bankName ?: 'N/A',
                        'date' => $mov->fecha,
                        'reference' => Str::limit($mov->referencia ?? '', 15),
                        'amount' => $mov->monto,
                    ];
                }),
            ];
        });

        $safePendingInvoices = $pendingInvoices->map(function ($inv) {
            return (object) [
                'rfc' => $inv->rfc,
                'name' => Str::limit($inv->nombre, 50),
                'date' => $inv->fecha_emision,
                'uuid' => $inv->uuid,
                'short_uuid' => substr($inv->uuid, 0, 8).'...',
                'folio' => $inv->folio ?? 'N/A',
                'amount' => $inv->monto,
            ];
        });

        $safePendingMovements = $pendingMovements->map(function ($mov) {
            $bankName = '';
            if ($mov->banco) {
                $bankName = is_array($mov->banco) ? ($mov->banco['nombre'] ?? '') : ($mov->banco->nombre ?? '');
            }

            return (object) [
                'description' => Str::limit($mov->descripcion, 80),
                'bank_label' => $bankName ?: 'N/A',
                'date' => $mov->fecha,
                'reference' => $mov->referencia,
                'amount' => $mov->monto,
            ];
        });

        return view('exports.reconciliation.pdf_report', [
            'conciliatedGroups' => $safeConciliatedGroups,
            'pendingInvoices' => $safePendingInvoices,
            'pendingMovements' => $safePendingMovements,
            'summary' => $summary,
            'filters' => [
                'month' => $this->month,
                'year' => $this->year,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
            ],
            'generated_at' => now(),
        ]);
    }

    public function title(): string
    {
        return 'Conciliacion';
    }
}
