<?php

namespace App\Exports;

use App\Exports\Sheets\ConciliatedInvoicesSheet;
use App\Exports\Sheets\ConciliatedMovementsSheet;
use App\Exports\Sheets\PendingInvoicesSheet;
use App\Exports\Sheets\PendingMovementsSheet;
use App\Exports\Sheets\SummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReconciliationExport implements WithMultipleSheets
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

    public function sheets(): array
    {
        $groupIds = $this->getMatchingGroupIds();

        return [
            new SummarySheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax, $groupIds),
            new ConciliatedInvoicesSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax, $groupIds),
            new ConciliatedMovementsSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax, $groupIds),
            new PendingInvoicesSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax),
            new PendingMovementsSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax),
        ];
    }

    protected function getMatchingGroupIds(): array
    {
        // 1. Groups matching via Invoices
        $invoiceQuery = \App\Models\Factura::where('team_id', $this->teamId)
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
        $movementQuery = \App\Models\Movimiento::where('team_id', $this->teamId)
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
            // For conciliated items, filters usually apply to the reconciliation date?
            // Actually, based on user feedback, they might be filtering by invoice/movement attributes.
            // But if they picked a "Period" (Month/Year), we usually check the conciliation date in history.
            // In Status.vue, month/year filters the "Period" being worked on.
            // Let's stick to the current logic in sheets: if month/year are set, we filter by fecha_conciliacion.
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
}
