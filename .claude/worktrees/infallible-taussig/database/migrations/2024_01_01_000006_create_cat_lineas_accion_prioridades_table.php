<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_lineas_accion_prioridades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_objetivo')->comment('FK al objetivo específico al que pertenece');
            $table->tinyInteger('numero')->unsigned()->unique();
            $table->text('prioridad');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('id_objetivo')
                ->references('id')
                ->on('cat_lineas_accion_objetivos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_lineas_accion_prioridades');
    }
};
