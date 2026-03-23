<?php

namespace App\Console\Commands;

use App\Models\Conciliacion;
use App\Models\Movimiento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RecalculateMovementHashes extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:recalculate-movement-hashes {--dry-run : Show what would happen without making changes}';

    /**
     * @var string
     */
    protected $description = 'Recalculate movement hashes using the 3-field formula (fecha + monto + descripcion) and safely remove duplicates';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('[DRY RUN] No changes will be made.');
        }

        // -------------------------------------------------------
        // Phase 1: Add temp column and calculate new hashes
        // -------------------------------------------------------
        $this->info('Phase 1: Calculating new hashes...');

        if (! $dryRun) {
            if (! Schema::hasColumn('movimientos', 'new_hash')) {
                Schema::table('movimientos', function ($table) {
                    $table->string('new_hash', 64)->nullable()->after('hash');
                });
            }
        }

        $totalMovements = Movimiento::count();
        $this->info("Total movements to process: {$totalMovements}");

        $hashChanges = 0;

        Movimiento::query()->chunkById(500, function ($movements) use ($dryRun, &$hashChanges) {
            foreach ($movements as $movement) {
                $newHash = hash('sha256', json_encode([
                    'fecha' => $movement->fecha->format('Y-m-d'),
                    'monto' => number_format((float) $movement->monto, 2, '.', ''),
                    'descripcion' => $movement->descripcion,
                ]));

                if ($movement->hash !== $newHash) {
                    $hashChanges++;
                }

                if (! $dryRun) {
                    DB::table('movimientos')
                        ->where('id', $movement->id)
                        ->update(['new_hash' => $newHash]);
                }
            }
        });

        $this->info("Hashes that will change: {$hashChanges}");

        // -------------------------------------------------------
        // Phase 2: Find collisions in new_hash
        // -------------------------------------------------------
        $this->info('Phase 2: Finding duplicates...');

        if ($dryRun) {
            // For dry run, calculate in memory
            $allMovements = Movimiento::all(['id', 'team_id', 'fecha', 'monto', 'descripcion', 'hash']);
            $groups = [];

            foreach ($allMovements as $mov) {
                $newHash = hash('sha256', json_encode([
                    'fecha' => $mov->fecha->format('Y-m-d'),
                    'monto' => $mov->monto,
                    'descripcion' => $mov->descripcion,
                ]));
                $key = $mov->team_id.'|'.$newHash;
                $groups[$key][] = $mov->id;
            }

            $duplicateGroups = collect($groups)->filter(fn ($ids) => count($ids) > 1);
        } else {
            $duplicateGroups = DB::table('movimientos')
                ->select('team_id', 'new_hash', DB::raw('MIN(id) as keep_id'), DB::raw('GROUP_CONCAT(id ORDER BY id) as all_ids'), DB::raw('COUNT(*) as cnt'))
                ->groupBy('team_id', 'new_hash')
                ->having('cnt', '>', 1)
                ->get()
                ->mapWithKeys(function ($group) {
                    $key = $group->team_id.'|'.$group->new_hash;

                    return [$key => explode(',', $group->all_ids)];
                });
        }

        if ($duplicateGroups->isEmpty()) {
            $this->info('No duplicates found.');

            if (! $dryRun) {
                $this->swapHashColumns();
            }

            $this->info('Done.');

            return self::SUCCESS;
        }

        // -------------------------------------------------------
        // Phase 3: Dry-run report
        // -------------------------------------------------------
        $cleanDeletes = 0;
        $conciliacionMigrations = 0;
        $details = [];

        foreach ($duplicateGroups as $key => $ids) {
            sort($ids);
            $keepId = (int) $ids[0]; // oldest
            $duplicateIds = array_slice($ids, 1);

            foreach ($duplicateIds as $dupId) {
                $dupId = (int) $dupId;
                $concCount = Conciliacion::where('movimiento_id', $dupId)->count();

                if ($concCount > 0) {
                    $conciliacionMigrations++;
                    $details[] = "  Movimiento #{$dupId} → migrate {$concCount} conciliacion(es) to #{$keepId}, then delete";
                } else {
                    $cleanDeletes++;
                    $details[] = "  Movimiento #{$dupId} → delete (no conciliaciones)";
                }
            }
        }

        $this->info('');
        $this->info('=== DUPLICATE REPORT ===');
        $this->info("Duplicate groups found: {$duplicateGroups->count()}");
        $this->info("Clean deletes (no conciliaciones): {$cleanDeletes}");
        $this->info("Conciliacion migrations needed: {$conciliacionMigrations}");

        if ($this->getOutput()->isVerbose() || $dryRun) {
            $this->info('');
            $this->info('Details:');
            foreach ($details as $detail) {
                $this->line($detail);
            }
        }

        if ($dryRun) {
            $this->info('');
            $this->info('[DRY RUN] No changes were made. Run without --dry-run to execute.');

            return self::SUCCESS;
        }

        // -------------------------------------------------------
        // Phase 4: Resolve duplicates (with confirmation)
        // -------------------------------------------------------
        if (! $this->confirm('Proceed with resolving duplicates?')) {
            $this->dropTempColumn();
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $this->info('Phase 4: Resolving duplicates...');

        $removed = 0;
        $migrated = 0;

        DB::transaction(function () use ($duplicateGroups, &$removed, &$migrated) {
            foreach ($duplicateGroups as $key => $ids) {
                sort($ids);
                $keepId = (int) $ids[0];
                $duplicateIds = array_map('intval', array_slice($ids, 1));

                foreach ($duplicateIds as $dupId) {
                    // Migrate conciliaciones from duplicate to keeper
                    $movedCount = Conciliacion::where('movimiento_id', $dupId)
                        ->update(['movimiento_id' => $keepId]);

                    if ($movedCount > 0) {
                        $migrated += $movedCount;
                        Log::info("RecalculateMovementHashes: Migrated {$movedCount} conciliacion(es) from movimiento #{$dupId} to #{$keepId}");
                    }

                    // Now safe to delete — no conciliaciones point to it
                    Movimiento::where('id', $dupId)->delete();
                    $removed++;
                }
            }
        });

        $this->info("Duplicates removed: {$removed}");
        $this->info("Conciliaciones migrated: {$migrated}");

        // -------------------------------------------------------
        // Phase 5: Swap hash columns
        // -------------------------------------------------------
        $this->swapHashColumns();

        $this->info('Done.');

        return self::SUCCESS;
    }

    /**
     * Copy new_hash → hash, then drop the temp column.
     */
    private function swapHashColumns(): void
    {
        $this->info('Phase 5: Updating hashes...');

        // All duplicates are already resolved, so this UPDATE won't violate
        // the UNIQUE(team_id, hash) constraint. No need to drop/re-add it.
        DB::statement('UPDATE movimientos SET hash = new_hash WHERE new_hash IS NOT NULL');

        $this->dropTempColumn();
    }

    private function dropTempColumn(): void
    {
        if (Schema::hasColumn('movimientos', 'new_hash')) {
            Schema::table('movimientos', function ($table) {
                $table->dropColumn('new_hash');
            });
        }
    }
}
