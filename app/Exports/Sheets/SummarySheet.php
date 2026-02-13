<?php

namespace App\Exports\Sheets;

use App\Models\Conciliacion;
use App\Models\Factura;
use App\Models\Movimiento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SummarySheet implements FromCollection, ShouldAutoSize, WithEvents, WithStyles, WithTitle
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

    public function collection()
    {
        // 1. Pending Invoices
        $pendingInvoicesQuery = Factura::where('team_id', $this->teamId)->doesntHave('conciliaciones');
        $this->applyFilters($pendingInvoicesQuery, 'fecha_emision');
        $pendingInvoicesCount = $pendingInvoicesQuery->count();
        $pendingInvoicesSum = $pendingInvoicesQuery->sum('monto');

        // 2. Pending Movements
        $pendingMovementsQuery = Movimiento::where('team_id', $this->teamId)
            ->where(fn ($q) => $q->where('tipo', 'abono')->orWhere('tipo', 'Abono'))
            ->doesntHave('conciliaciones');
        $this->applyFilters($pendingMovementsQuery, 'fecha');
        $pendingMovementsCount = $pendingMovementsQuery->count();
        $pendingMovementsSum = $pendingMovementsQuery->sum('monto');

        // 3. Reconciliations (Totals)
        $conciliacionQuery = Conciliacion::where('conciliacions.team_id', $this->teamId);
        $this->applyFilters($conciliacionQuery, 'fecha_conciliacion');

        $reconciliationCount = (clone $conciliacionQuery)->distinct('group_id')->count('group_id');

        // Sum of applied amounts (Total Conciliado)
        $totalConciliadoInvoices = (clone $conciliacionQuery)->sum('monto_aplicado');

        // Sum of actual movement amounts linked in these conciliations
        $totalConciliadoMovements = (clone $conciliacionQuery)
            ->join('movimientos', 'conciliacions.movimiento_id', '=', 'movimientos.id')
            ->sum('monto_aplicado'); // In this system, monto_aplicado is what ties them.

        // However, for consistency with user request, we might want the sum of the MOVEMENTS themselves
        // that are touched by these conciliations in this period.
        $movementIds = (clone $conciliacionQuery)->pluck('movimiento_id')->unique();
        $sumOfMovements = Movimiento::whereIn('id', $movementIds)->sum('monto');

        $facturaIds = (clone $conciliacionQuery)->pluck('factura_id')->unique();
        $sumOfFacturas = Factura::whereIn('id', $facturaIds)->sum('monto');

        $period = $this->dateFrom ? ($this->dateFrom.' al '.$this->dateTo) : ($this->month.'/'.$this->year);

        return collect([
            ['REPORTE DE CONCILIACIÓN BANCARIA', ''],
            ['Equipo ID', $this->teamId],
            ['Periodo', $period],
            ['Fecha de Exportación', now()->format('d/m/Y H:i')],
            ['', ''],
            ['DASHBOARD DE RESUMEN', ''],
            ['Facturas Pendientes (Cantidad)', $pendingInvoicesCount],
            ['Facturas Pendientes (Monto Total)', $pendingInvoicesSum],
            ['Movimientos Pendientes (Cantidad)', $pendingMovementsCount],
            ['Movimientos Pendientes (Monto Total)', $pendingMovementsSum],
            ['', ''],
            ['RESUMEN DE CONCILIACIONES', ''],
            ['Total de Grupos Conciliados', $reconciliationCount],
            ['Total Facturas Conciliadas (Monto Bruto)', $sumOfFacturas],
            ['Total Pagos Conciliados (Monto Bruto)', $sumOfMovements],
            ['Total Aplicado/Conciliado', $totalConciliadoInvoices],
        ]);
    }

    protected function applyFilters($query, $dateColumn)
    {
        if ($this->dateFrom || $this->dateTo) {
            if ($this->dateFrom) {
                $query->whereDate($dateColumn, '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $query->whereDate($dateColumn, '<=', $this->dateTo);
            }
        } elseif ($this->month && $this->year) {
            $query->whereMonth($dateColumn, $this->month)->whereYear($dateColumn, $this->year);
        }

        if ($this->search) {
            $query->where(function ($q) use ($query) {
                $model = $query->getModel();
                if ($model instanceof \App\Models\Factura) {
                    $q->where('nombre', 'like', "%{$this->search}%")
                        ->orWhere('rfc', 'like', "%{$this->search}%")
                        ->orWhere('folio', 'like', "%{$this->search}%")
                        ->orWhere('referencia', 'like', "%{$this->search}%");
                } elseif ($model instanceof \App\Models\Movimiento) {
                    $q->where('descripcion', 'like', "%{$this->search}%")
                        ->orWhere('referencia', 'like', "%{$this->search}%");
                } elseif ($model instanceof \App\Models\Conciliacion) {
                    // For conciliations, we search in both relations if joined,
                    // but for simplicity in summary we just use the group context?
                    // Let's at least try to match search if relevant.
                }
            });
        }

        if ($this->amountMin || $this->amountMax) {
            $model = $query->getModel();
            $col = ($model instanceof \App\Models\Conciliacion) ? 'monto_aplicado' : 'monto';
            
            if ($this->amountMin) {
                $query->where($col, '>=', $this->amountMin);
            }
            if ($this->amountMax) {
                $query->where($col, '<=', $this->amountMax);
            }
        }
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('B7:B10')->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('B14:B16')->getNumberFormat()->setFormatCode('$#,##0.00');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']]],
            6 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']]],
            12 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Style Header 1
                $sheet->getStyle('A1:B1')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2C3E50'],
                    ],
                ]);

                // Style Header 2
                $sheet->getStyle('A6:B6')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2C3E50'],
                    ],
                ]);

                // Style Header 3
                $sheet->getStyle('A12:B12')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2C3E50'],
                    ],
                ]);

                $sheet->getColumnDimension('A')->setAutoSize(true);
                $sheet->getColumnDimension('B')->setAutoSize(true);
            },
        ];
    }
}
