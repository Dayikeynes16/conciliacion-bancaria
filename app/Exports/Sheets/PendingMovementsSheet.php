<?php

namespace App\Exports\Sheets;

use App\Exports\Traits\ExcelStylingHelper;
use App\Models\Movimiento;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PendingMovementsSheet implements FromQuery, ShouldAutoSize, WithChunkReading, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle
{
    use ExcelStylingHelper;

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

    public function query()
    {
        $query = Movimiento::query()
            ->with('banco')
            ->where('team_id', $this->teamId)
            ->where(function ($q) {
                $q->where('tipo', 'abono')->orWhere('tipo', 'Abono');
            })
            ->doesntHave('conciliaciones');

        if ($this->dateFrom || $this->dateTo) {
            if ($this->dateFrom) {
                $query->whereDate('fecha', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $query->whereDate('fecha', '<=', $this->dateTo);
            }
        } elseif ($this->month && $this->year) {
            $query->whereMonth('fecha', $this->month)
                ->whereYear('fecha', $this->year);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('descripcion', 'like', "%{$this->search}%")
                    ->orWhere('referencia', 'like', "%{$this->search}%");
            });
        }

        if ($this->amountMin) {
            $query->where('monto', '>=', $this->amountMin);
        }

        if ($this->amountMax) {
            $query->where('monto', '<=', $this->amountMax);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Banco',
            'Referencia',
            'Descripción',
            'Monto Movimiento / Pago',
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->fecha ? \Carbon\Carbon::parse($movement->fecha)->format('d/m/Y') : 'N/A',
            $movement->banco->nombre ?? 'N/A',
            $movement->referencia,
            $movement->descripcion,
            $movement->monto,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function title(): string
    {
        return 'Movimientos Pendientes';
    }

    /**
     * Override column formats.
     */
    public function columnFormats(): array
    {
        return [
            'E' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }
}
