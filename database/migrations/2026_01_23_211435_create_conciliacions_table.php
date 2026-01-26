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
        Schema::create('conciliacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->foreignId('movimiento_id')->constrained('movimientos')->onDelete('cascade');
            $table->decimal('monto_aplicado', 15, 2);
            $table->enum('estatus', ['conciliado', 'pendiente_revision'])->default('conciliado');
            $table->enum('tipo', ['automatico', 'manual'])->default('automatico');
            $table->timestamp('fecha_conciliacion')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conciliacions');
    }
};
