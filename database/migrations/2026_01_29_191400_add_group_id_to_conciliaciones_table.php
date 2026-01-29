<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conciliacions', function (Blueprint $table) {
            $table->uuid('group_id')->nullable()->after('id');
        });

        // Backfill existing records
        // Group by created_at (approx) to identify batches
        $conciliaciones = \Illuminate\Support\Facades\DB::table('conciliacions')
            ->orderBy('created_at')
            ->get();

        // Simple grouping strategy: if created_at is within 1 second of previous, same group?
        // Or just Group By created_at exact equality?
        // Laravel's created_at is second precision.
        
        $groups = $conciliaciones->groupBy(function($item) {
            return $item->created_at . '-' . $item->user_id;
        });

        foreach ($groups as $group) {
            $uuid = \Illuminate\Support\Str::uuid();
            $ids = $group->pluck('id');
            \Illuminate\Support\Facades\DB::table('conciliacions')
                ->whereIn('id', $ids)
                ->update(['group_id' => $uuid]);
        }
        
        // Make it required after backfill if needed, but nullable is safer for now.
    }

    public function down(): void
    {
        Schema::table('conciliacions', function (Blueprint $table) {
            $table->dropColumn('group_id');
        });
    }
};
