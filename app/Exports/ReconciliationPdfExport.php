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

    protected $search;

    protected $amountMin;

    protected $amountMax;

    public function __construct($teamId, $month, $year, $dateFrom, $dateTo, $search = null, $amountMin = null, $amountMax = null)
    {
        $this->teamId = $teamId;
        $this->month = $month;
        $this->year = $year;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->search = $search;
        $this->amountMin = $amountMin;
        $this->amountMax = $amountMax;
    }

    public function view(): View
    {
        $teamId = $this->teamId;

        // 1. Identify Group IDs that match the filters (Group-Aware Logic)
        $groupIds = $this->getMatchingGroupIds();

        // --- 1. Fetch Conciliated Groups ---
        // Fetch all conciliacion records for the matched groups
        $conciliatedRecords = Conciliacion::where('conciliacions.team_id', $teamId)
            ->whereIn('conciliacions.group_id', $groupIds)
            ->with(['factura', 'movimiento.archivo.bankFormat', 'user', 'movimiento.banco'])
            ->get();

        // Force the same grouping logic as in the history/status views
        $conciliatedGroupsRaw = $conciliatedRecords->groupBy('group_id');

        $conciliatedGroups = $conciliatedGroupsRaw->map(function ($items, $groupId) {
            $first = $items->first();
            $invoices = $items->pluck('factura')->unique('id')->filter()->values();
            $movements = $items->pluck('movimiento')->unique('id')->filter()->values();

            // Correct the date logic - use the latest conciliation date for the group
            $groupDate = $items->max('fecha_conciliacion');

            return [
                'id' => $groupId,
                'short_id' => substr($groupId, 0, 8).'...',
                'date' => $groupDate ? \Carbon\Carbon::parse($groupDate) : now(),
                'user' => $first->user->name ?? 'N/A',
                'invoices' => $invoices,
                'movements' => $movements,
                'total_invoices' => $invoices->sum('monto'),
                'total_movements' => $movements->sum('monto'),
                'difference' => $invoices->sum('monto') - $movements->sum('monto'),
            ];
        })->sortByDesc('date');

        // --- 2. Fetch Pending Invoices ---
        $pendingInvoicesQuery = Factura::where('team_id', $teamId)->doesntHave('conciliaciones');
        if ($this->dateFrom || $this->dateTo) {
            if ($this->dateFrom) {
                $pendingInvoicesQuery->whereDate('fecha_emision', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $pendingInvoicesQuery->whereDate('fecha_emision', '<=', $this->dateTo);
            }
        } elseif ($this->month && $this->year) {
            $pendingInvoicesQuery->whereMonth('fecha_emision', $this->month);
            if ($this->year) {
                $pendingInvoicesQuery->whereYear('fecha_emision', $this->year);
            }
        }

        if ($this->search) {
            $pendingInvoicesQuery->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                    ->orWhere('rfc', 'like', "%{$this->search}%")
                    ->orWhere('folio', 'like', "%{$this->search}%");
            });
        }

        if ($this->amountMin) {
            $pendingInvoicesQuery->where('monto', '>=', $this->amountMin);
        }

        if ($this->amountMax) {
            $pendingInvoicesQuery->where('monto', '<=', $this->amountMax);
        }
        $pendingInvoices = $pendingInvoicesQuery->orderBy('fecha_emision', 'desc')->get();

        // --- 3. Fetch Pending Movements ---
        $pendingMovementsQuery = Movimiento::where('team_id', $teamId)
            ->where(function ($query) {
                $query->where('tipo', 'abono')
                    ->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones');

        if ($this->dateFrom || $this->dateTo) {
            if ($this->dateFrom) {
                $pendingMovementsQuery->whereDate('fecha', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $pendingMovementsQuery->whereDate('fecha', '<=', $this->dateTo);
            }
        } elseif ($this->month && $this->year) {
            $pendingMovementsQuery->whereMonth('fecha', $this->month);
            if ($this->year) {
                $pendingMovementsQuery->whereYear('fecha', $this->year);
            }
        }

        if ($this->search) {
            $pendingMovementsQuery->where(function ($q) {
                $q->where('descripcion', 'like', "%{$this->search}%")
                    ->orWhere('referencia', 'like', "%{$this->search}%");
            });
        }

        if ($this->amountMin) {
            $pendingMovementsQuery->where('monto', '>=', $this->amountMin);
        }

        if ($this->amountMax) {
            $pendingMovementsQuery->where('monto', '<=', $this->amountMax);
        }
        $pendingMovements = $pendingMovementsQuery->orderBy('fecha', 'desc')->get();

        // --- 4. Summaries ---
        $summary = [
            'conciliated_invoices' => $conciliatedGroups->sum('total_invoices'),
            'conciliated_movements' => $conciliatedGroups->sum('total_movements'),
            'pending_invoices' => $pendingInvoices->sum('monto'),
            'pending_movements' => $pendingMovements->sum('monto'),
        ];

        // Clean up data for the view
        $safeConciliatedGroups = $conciliatedGroups->map(function ($group) {
            return (object) [
                'id' => $group['id'],
                'short_id' => $group['short_id'],
                'date' => $group['date'],
                'user' => $group['user'],
                'total_invoices' => $group['total_invoices'],
                'total_movements' => $group['total_movements'],
                'difference' => $group['difference'],
                'invoices' => $group['invoices']->map(function ($inv) {
                    return (object) [
                        'rfc' => $inv->rfc ?: 'N/A',
                        'name' => Str::limit($inv->nombre, 40),
                        'date' => $inv->fecha_emision,
                        'folio' => $inv->folio ?? ($inv->uuid ? substr($inv->uuid, 0, 8) : 'N/A'),
                        'amount' => $inv->monto,
                    ];
                }),
                'movements' => $group['movements']->map(function ($mov) {
                    $bankName = '';
                    if ($mov->banco) {
                        $bankName = is_array($mov->banco) ? ($mov->banco['nombre'] ?? '') : ($mov->banco->nombre ?? '');
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

        return view('exports.reconciliation.pdf_report', [
            'conciliatedGroups' => $safeConciliatedGroups,
            'pendingInvoices' => $pendingInvoices,
            'pendingMovements' => $pendingMovements,
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

    protected function getMatchingGroupIds(): array
    {
        // 1. Groups matching via Invoices
        $invoiceQuery = Factura::where('team_id', $this->teamId)
            ->join('conciliacions', 'facturas.id', '=', 'conciliacions.factura_id');

        $this->applyGenericFilters($invoiceQuery, 'facturas.fecha_emision', 'facturas.monto');

        if ($this->search) {
            $invoiceQuery->where(function ($q) {
                $q->where('facturas.nombre', 'like', "%{$this->search}%")
                    ->orWhere('facturas.rfc', 'like', "%{$this->search}%")
                    ->orWhere('facturas.folio', 'like', "%{$this->search}%")
                    ->orWhere('facturas.referencia', 'like', "%{$this->search}%");
            });
        }

        $groupIdsFromInvoices = $invoiceQuery->pluck('conciliacions.group_id');

        // 2. Groups matching via Movements
        $movementQuery = Movimiento::where('team_id', $this->teamId)
            ->join('conciliacions', 'movimientos.id', '=', 'conciliacions.movimiento_id');

        $this->applyGenericFilters($movementQuery, 'movimientos.fecha', 'movimientos.monto');

        if ($this->search) {
            $movementQuery->where(function ($q) {
                $q->where('movimientos.descripcion', 'like', "%{$this->search}%")
                    ->orWhere('movimientos.referencia', 'like', "%{$this->search}%");
            });
        }

        $groupIdsFromMovements = $movementQuery->pluck('conciliacions.group_id');

        return $groupIdsFromInvoices->merge($groupIdsFromMovements)->unique()->filter()->toArray();
    }

    protected function applyGenericFilters($query, $dateCol, $amountCol)
    {
        if ($this->dateFrom || $this->dateTo) {
            if ($this->dateFrom) {
                $query->whereDate($dateCol, '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $query->whereDate($dateCol, '<=', $this->dateTo);
            }
        } elseif ($this->month && $this->year) {
            $query->whereMonth('conciliacions.fecha_conciliacion', $this->month)
                ->whereYear('conciliacions.fecha_conciliacion', $this->year);
        }

        if ($this->amountMin) {
            $query->where($amountCol, '>=', $this->amountMin);
        }
        if ($this->amountMax) {
            $query->where($amountCol, '<=', $this->amountMax);
        }
    }

    public function title(): string
    {
        return 'Conciliacion';
    }
}
