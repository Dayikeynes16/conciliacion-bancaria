<?php

namespace App\Jobs;

use App\Exports\ReconciliationExport;
use App\Models\ExportRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GenerateReconciliationExcelExportJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 600;

    public $tries = 3;

    public $backoff = [30, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(public ExportRequest $exportRequest)
    {
        $this->onQueue('exports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Mark as Processing
        $this->exportRequest->update(['status' => 'processing']);

        try {
            $filters = $this->exportRequest->filters ?? [];

            // Extract filters
            $teamId = $this->exportRequest->team_id;
            $month = $filters['month'] ?? null;
            $year = $filters['year'] ?? null;
            $dateFrom = $filters['date_from'] ?? null;
            $dateTo = $filters['date_to'] ?? null;
            $search = $filters['search'] ?? null;
            $amountMin = $filters['amount_min'] ?? null;
            $amountMax = $filters['amount_max'] ?? null;

            // Generate filename unique to prevent collisions
            // Format: exports/{team_id}/{user_id}/{uuid}.xlsx
            $uuid = \Illuminate\Support\Str::uuid();
            $path = "exports/{$teamId}/{$this->exportRequest->user_id}/{$uuid}.xlsx";

            // 2. Generate Excel
            // Using store() stores it in the default disk (local or s3)
            Excel::store(
                new ReconciliationExport($teamId, $month, $year, $dateFrom, $dateTo, $search, $amountMin, $amountMax),
                $path
            );

            // 3. Mark as Completed
            $this->exportRequest->update([
                'status' => 'completed',
                'file_path' => $path,
                'file_name' => 'conciliacion_'.($dateFrom ? $dateFrom : $month.'_'.$year).'.xlsx',
            ]);

        } catch (\Throwable $e) {
            Log::error('Excel Export Failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $this->exportRequest->update([
                'status' => 'failed',
                'error_message' => 'Error generating excel: '.$e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->exportRequest->update([
            'status' => 'failed',
            'error_message' => 'Error permanente: '.$exception->getMessage(),
        ]);
    }
}
