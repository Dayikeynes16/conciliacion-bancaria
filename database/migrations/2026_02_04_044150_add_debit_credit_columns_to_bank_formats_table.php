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
        Schema::table('bank_formats', function (Blueprint $table) {
            $table->string('debit_column')->nullable()->after('amount_column'); // For "Cargo"
            $table->string('credit_column')->nullable()->after('debit_column'); // For "Abono"
            $table->string('amount_column')->nullable()->change(); // Now optional if debit/credit are used
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_formats', function (Blueprint $table) {
            $table->dropColumn(['debit_column', 'credit_column']);
            $table->string('amount_column')->nullable(false)->change();
        });
    }
};
