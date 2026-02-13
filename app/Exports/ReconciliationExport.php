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

    public function __construct($teamId, $month, $year, $dateFrom, $dateTo)
    {
        $this->teamId = $teamId;
        $this->month = $month;
        $this->year = $year;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function sheets(): array
    {
        return [
            new SummarySheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo),
            new ConciliatedInvoicesSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo),
            new ConciliatedMovementsSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo),
            new PendingInvoicesSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo),
            new PendingMovementsSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo),
        ];
    }
}
