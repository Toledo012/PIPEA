<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_lineas_accion_objetivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_eje')->comment('FK al eje al que pertenece');
            $table->tinyInteger('numero_objetivo')->unsigned()->unique();
            $table->string('objetivo');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('id_eje')
                ->references('id')
                ->on('cat_lineas_accion_ejes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_lineas_accion_objetivos');
    }
};
