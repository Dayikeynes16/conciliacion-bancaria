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
        return [
            new SummarySheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax),
            new ConciliatedInvoicesSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax),
            new ConciliatedMovementsSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax),
            new PendingInvoicesSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax),
            new PendingMovementsSheet($this->teamId, $this->month, $this->year, $this->dateFrom, $this->dateTo, $this->search, $this->amountMin, $this->amountMax),
        ];
    }
}
