<?php

namespace App\Exports\Sheets;

use App\Exports\Traits\ExcelStylingHelper;
use App\Models\Factura;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PendingInvoicesSheet implements FromQuery, ShouldAutoSize, WithChunkReading, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithTitle
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
        $query = Factura::query()
            ->where('team_id', $this->teamId)
            ->doesntHave('conciliaciones');

        if ($this->dateFrom || $this->dateTo) {
            if ($this->dateFrom) {
                $query->whereDate('fecha_emision', '>=', $this->dateFrom);
            }
            if ($this->dateTo) {
                $query->whereDate('fecha_emision', '<=', $this->dateTo);
            }
        } elseif ($this->month && $this->year) {
            $query->whereMonth('fecha_emision', $this->month)
                ->whereYear('fecha_emision', $this->year);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                    ->orWhere('rfc', 'like', "%{$this->search}%")
                    ->orWhere('folio', 'like', "%{$this->search}%")
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
            'Fecha Emisión',
            'RFC Receptor',
            'Razón Social',
            'UUID',
            'Referencia',
            'Monto Factura',
        ];
    }

    public function map($invoice): array
    {
        return [
            $invoice->fecha_emision ? \Carbon\Carbon::parse($invoice->fecha_emision)->format('d/m/Y') : 'N/A',
            $invoice->rfc,
            $invoice->nombre,
            $invoice->uuid,
            $invoice->referencia,
            $invoice->monto,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function title(): string
    {
        return 'Facturas Pendientes';
    }

    /**
     * Override column formats.
     */
    public function columnFormats(): array
    {
        return [
            'F' => \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD_SIMPLE,
        ];
    }
}
