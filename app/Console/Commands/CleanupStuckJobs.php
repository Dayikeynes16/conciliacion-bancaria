<?php

namespace App\Console\Commands;

use App\Models\Archivo;
use App\Models\ExportRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupStuckJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:cleanup-stuck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fails jobs that have been stuck in processing for too long (2 hours).';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $threshold = Carbon::now()->subHours(2);

        // 1. Cleanup Export Requests
        $stuckExports = ExportRequest::where('status', 'processing')
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($stuckExports as $export) {
            $export->update([
                'status' => 'failed',
                'error_message' => 'Job abandoned by worker (timeout or crash).',
            ]);
            $this->info("Marked ExportRequest #{$export->id} as failed.");
        }

        // 2. Cleanup Archivo (Uploads)
        $stuckUploads = Archivo::where('estatus', 'procesando')
            ->where('updated_at', '<', $threshold)
            ->get();

        foreach ($stuckUploads as $archivo) {
            $archivo->update([
                'estatus' => 'fallido',
            ]);
            $this->info("Marked Archivo #{$archivo->id} as fallido.");
        }

        $this->info('Cleanup completed.');
    }
}
