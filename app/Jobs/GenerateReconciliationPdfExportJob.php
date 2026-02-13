<?php

namespace App\Jobs;

use App\Exports\ReconciliationPdfExport;
use App\Models\ExportRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateReconciliationPdfExportJob implements ShouldQueue
{
    use Queueable;

    // Timeout: 10 minutes
    public $timeout = 600;

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
        $this->exportRequest->update(['status' => 'processing']);

        try {
            $filters = $this->exportRequest->filters ?? [];

            $teamId = $this->exportRequest->team_id;
            $month = $filters['month'] ?? null;
            $year = $filters['year'] ?? null;
            $dateFrom = $filters['date_from'] ?? null;
            $dateTo = $filters['date_to'] ?? null;

            $uuid = \Illuminate\Support\Str::uuid();
            $path = "exports/{$teamId}/{$this->exportRequest->user_id}/{$uuid}.pdf";

            // Generate Data
            $export = new ReconciliationPdfExport($teamId, $month, $year, $dateFrom, $dateTo);
            $data = $export->view()->getData();

            // Render PDF
            $pdf = Pdf::loadView('exports.reconciliation.pdf_report', $data);
            $pdf->setPaper('a4', 'portrait');

            // Save to Storage
            Storage::put($path, $pdf->output());

            $this->exportRequest->update([
                'status' => 'completed',
                'file_path' => $path,
                'file_name' => 'conciliacion_'.($dateFrom ? $dateFrom : $month.'_'.$year).'.pdf',
            ]);

        } catch (\Throwable $e) {
            Log::error('PDF Export Failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            $this->exportRequest->update([
                'status' => 'failed',
                'error_message' => 'Error generating pdf: '.$e->getMessage(),
            ]);

            throw $e;
        }
    }
}
