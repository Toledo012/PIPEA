<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Garantiza un único avance por (período, línea de acción).
     *
     * Regla de negocio: cada organismo registra UN avance por línea
     * en cada período de reporte. La inmutabilidad del registro
     * (sin updated_at, sin save()/delete() en el modelo) ya impedía
     * editar/eliminar, pero la BD seguía aceptando duplicados.
     */
    public function up(): void
    {
        // ── Defensa: limpiar duplicados si los hubiera ────────────────────────
        // Conserva el registro más reciente (mayor id) por (periodo, línea).
        $duplicados = DB::table('historial_avances')
            ->select('id_periodo', 'id_linea_accion', DB::raw('MAX(id) as keep_id'))
            ->groupBy('id_periodo', 'id_linea_accion')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicados as $grupo) {
            DB::table('historial_avances')
                ->where('id_periodo', $grupo->id_periodo)
                ->where('id_linea_accion', $grupo->id_linea_accion)
                ->where('id', '<>', $grupo->keep_id)
                ->delete();
        }

        Schema::table('historial_avances', function (Blueprint $table) {
            $table->unique(
                ['id_periodo', 'id_linea_accion'],
                'historial_avances_periodo_linea_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('historial_avances', function (Blueprint $table) {
            $table->dropUnique('historial_avances_periodo_linea_unique');
        });
    }
};
