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
        Schema::create('bank_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('start_row')->default(1);
            $table->string('date_column');
            $table->string('description_column');
            $table->string('amount_column'); // Can be same as credit/debit or single column
            $table->string('reference_column')->nullable();
            $table->string('type_column')->nullable(); // Helper to distinguish credit/debit if amount is absolute
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_formats');
    }
};
