<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialAvance extends Model
{
    protected $table = 'historial_avances';

    // Sin updated_at — los avances son inmutables una vez registrados
    const UPDATED_AT = null;

    protected $fillable = [
        'id_linea_accion',
        'id_periodo',
        'id_usuario',
        'estatus',
        'avance_cualitativo',
        'avance_cuantitativo',
        'fecha_avance',
        'comentario',
        'avances_linea',
        'medio_verificacion',
        'documento',
        'archivo_path',
        'archivo_tipo',
        'archivo_tamanio',
        'url',
    ];

    protected $casts = [
        'avance_cualitativo'  => 'float',
        'avance_cuantitativo' => 'float',
        'fecha_avance'        => 'date',
        'fecha_registro'      => 'datetime',
        'created_at'          => 'datetime',
    ];

    // ── Sin update ni delete desde el modelo ─────────────────────────────────

    public function save(array $options = []): bool
    {
        // Solo permite insertar — nunca actualizar
        if (! $this->exists) {
            return parent::save($options);
        }

        throw new \LogicException('Los registros de historial de avances son inmutables.');
    }

    public function delete(): bool
    {
        throw new \LogicException('Los registros de historial de avances no pueden eliminarse.');
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function lineaAccion(): BelongsTo
    {
        return $this->belongsTo(LineaAccion::class, 'id_linea_accion');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(PeriodoReporte::class, 'id_periodo');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Calcula el avance_cuantitativo automáticamente si no se pasa.
     * Fórmula: (cualitativo - linea_base) / (meta - linea_base)
     * resultado entre 0 y 1, igual que el Excel col S.
     */
    public static function calcularCuantitativo(
        float $cualitativo,
        float $meta,
        float $lineaBase = 0
    ): ?float {
        $rango = $meta - $lineaBase;
        if ($rango <= 0) return null;

        $resultado = ($cualitativo - $lineaBase) / $rango;

        // Puede superar 1 si se rebasa la meta — lo permitimos igual que el Excel
        return round($resultado, 4);
    }

    /**
     * URL pública del archivo de evidencia si existe.
     */
    public function getArchivoUrlAttribute(): ?string
    {
        return $this->archivo_path
            ? asset('storage/' . $this->archivo_path)
            : null;
    }
}
