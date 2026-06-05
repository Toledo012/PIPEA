<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_reporte', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');                       // "1er Trimestre 2025"
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->date('fecha_limite_reporte');           // hasta cuándo pueden subir avance
            $table->boolean('activo')->default(false);      // el admin abre/cierra el período
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_reporte');
    }
};
