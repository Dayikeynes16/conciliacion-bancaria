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
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
        });

        // Sync existing records from facturas
        \Illuminate\Support\Facades\DB::table('conciliacions')
            ->join('facturas', 'conciliacions.factura_id', '=', 'facturas.id')
            ->update(['conciliacions.team_id' => \Illuminate\Support\Facades\DB::raw('facturas.team_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conciliacions', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
