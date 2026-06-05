<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_avances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_linea_accion');
            $table->foreign('id_linea_accion')
                ->references('id')->on('lineas_accion')
                ->cascadeOnDelete();

            $table->foreignId('id_periodo')
                ->constrained('periodos_reporte')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('id_usuario');
            $table->foreign('id_usuario')
                ->references('id')->on('usuarios');

            // ── Avance — espejo exacto de las columnas del Excel ──────────────
            //
            // Col Q: estatus general de la línea en este período
            $table->enum('estatus', [
                'Con avances',
                'Por el momento sin avances',
            ])->default('Con avances');

            // Col R: valor bruto real logrado (45 personas, 6000 vistas, 3 módulos…)
            // en las mismas unidades que la meta de la línea de acción
            $table->decimal('avance_cualitativo', 15, 4)->nullable();

            // Col S: porcentaje de cumplimiento expresado de 0 a 1
            // (0.93 = 93%) — alimenta la barra de progreso
            // puede capturarse directo o calcularse: (cualit - lb) / (meta - lb)
            $table->decimal('avance_cuantitativo', 8, 4)->nullable();

            // Col T: fecha que declara el organismo para el avance
            $table->date('fecha_avance')->nullable();

            // Col U: comentario / justificación del avance
            $table->text('comentario')->nullable();

            // Col V: nota adicional (p.ej. cambios institucionales, decretos, etc.)
            $table->text('avances_linea')->nullable();

            // ── Evidencia ─────────────────────────────────────────────────────
            $table->string('medio_verificacion')->nullable();
            $table->string('documento')->nullable();          // nombre original del archivo
            $table->string('archivo_path')->nullable();       // ruta en storage
            $table->string('archivo_tipo')->nullable();       // mime type
            $table->unsignedInteger('archivo_tamanio')->nullable(); // bytes
            $table->string('url')->nullable();                // enlace externo opcional

            // ── Control — INMUTABLE ───────────────────────────────────────────
            // Los avances no se editan ni eliminan una vez registrados.
            // Por eso NO hay updated_at — es intencional.
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_avances');
    }
};
