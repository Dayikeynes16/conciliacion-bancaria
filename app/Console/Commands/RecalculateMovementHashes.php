<?php

namespace App\Console\Commands;

use App\Models\Movimiento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateMovementHashes extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:recalculate-movement-hashes';

    /**
     * @var string
     */
    protected $description = 'Recalculate movement hashes using the 3-field formula (fecha + monto + descripcion) and remove duplicates';

    public function handle(): int
    {
        $this->info('Recalculating movement hashes...');

        $recalculated = 0;
        $removed = 0;
        $skipped = 0;

        // Recalculate all hashes with the new formula
        Movimiento::query()->chunkById(500, function ($movements) use (&$recalculated) {
            foreach ($movements as $movement) {
                $newHash = hash('sha256', json_encode([
                    'fecha' => $movement->fecha->format('Y-m-d'),
                    'monto' => $movement->monto,
                    'descripcion' => $movement->descripcion,
                ]));

                if ($movement->hash !== $newHash) {
                    $movement->update(['hash' => $newHash]);
                    $recalculated++;
                }
            }
        });

        $this->info("Hashes recalculated: {$recalculated}");

        // Find duplicates: same team_id + hash, keep oldest (smallest id)
        $duplicateGroups = DB::table('movimientos')
            ->select('team_id', 'hash', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('team_id', 'hash')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            $duplicates = Movimiento::where('team_id', $group->team_id)
                ->where('hash', $group->hash)
                ->where('id', '!=', $group->keep_id)
                ->get();

            foreach ($duplicates as $duplicate) {
                if ($duplicate->conciliaciones()->exists()) {
                    $this->warn("Skipped movimiento #{$duplicate->id} (has conciliaciones)");
                    Log::warning("RecalculateMovementHashes: Skipped movimiento #{$duplicate->id} — has conciliaciones.");
                    $skipped++;
                } else {
                    $duplicate->delete();
                    $removed++;
                }
            }
        }

        $this->info("Duplicates removed: {$removed}");
        $this->info("Duplicates skipped (had conciliaciones): {$skipped}");
        $this->info('Done.');

        return self::SUCCESS;
    }
}
