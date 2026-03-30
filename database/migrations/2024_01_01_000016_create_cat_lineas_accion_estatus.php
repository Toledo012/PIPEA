<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_lineas_accion_estatus', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::table('cat_lineas_accion_estatus')->insert([
            ['nombre' => 'Requiere voluntad y trabajo técnico',   'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Sí requiere presupuesto adicional',     'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'No requiere presupuesto adicional',     'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Requiere adecuación normativa interna', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Requiere reforma legislativa',          'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_lineas_accion_estatus');
    }
};
