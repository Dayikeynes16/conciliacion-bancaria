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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_team_id')->nullable()->after('id')->constrained('teams')->nullOnDelete();
        });

        // Migrate existing data: Assign current team_id to current_team_id and pivot
        if (Schema::hasColumn('users', 'team_id')) {
            $users = \Illuminate\Support\Facades\DB::table('users')->whereNotNull('team_id')->get();
            foreach ($users as $user) {
                // Set current_team_id
                \Illuminate\Support\Facades\DB::table('users')
                    ->where('id', $user->id)
                    ->update(['current_team_id' => $user->team_id]);

                // Add to pivot if not exists
                $exists = \Illuminate\Support\Facades\DB::table('team_user')
                    ->where('team_id', $user->team_id)
                    ->where('user_id', $user->id)
                    ->exists();
                
                if (!$exists) {
                    \Illuminate\Support\Facades\DB::table('team_user')->insert([
                        'team_id' => $user->team_id,
                        'user_id' => $user->id,
                        'role' => 'owner',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
