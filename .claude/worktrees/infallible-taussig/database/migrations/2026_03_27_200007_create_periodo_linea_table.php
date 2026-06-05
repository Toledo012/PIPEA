<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodo_linea', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_periodo')
                ->constrained('periodos_reporte')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('id_linea');
            $table->foreign('id_linea')
                ->references('id')->on('lineas_accion')
                ->cascadeOnDelete();

            // el admin puede deshabilitar una línea específica dentro del período
            // o habilitar una prórroga individual si no cumplió
            $table->boolean('habilitado')->default(true);
            $table->timestamp('prorroga_hasta')->nullable();
            $table->text('motivo_prorroga')->nullable();

            $table->timestamps();

            $table->unique(['id_periodo', 'id_linea']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodo_linea');
    }
};
