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
        // Add team_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('id')->constrained();
        });

        // Add team_id to facturas
        Schema::table('facturas', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add team_id to movimientos
        Schema::table('movimientos', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add team_id to bancos
        Schema::table('bancos', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->nullable()->constrained()->onDelete('cascade');
        });

        // Add team_id to archivos (optional, but good for filtering)
        Schema::table('archivos', function (Blueprint $table) {
            $table->foreignId('team_id')->after('id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::table('bancos', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });

        Schema::table('archivos', function (Blueprint $table) {
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }
};
