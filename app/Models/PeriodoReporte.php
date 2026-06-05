<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PeriodoReporte extends Model
{
    protected $table = 'periodos_reporte';

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'fecha_limite_reporte',
        'activo',
        'descripcion',
    ];

    protected $casts = [
        'fecha_inicio'         => 'datetime',
        'fecha_fin'            => 'datetime',
        'fecha_limite_reporte' => 'datetime',
        'activo'               => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function lineas(): BelongsToMany
    {
        return $this->belongsToMany(
            LineaAccion::class,
            'periodo_linea',
            'id_periodo',
            'id_linea'
        )
            ->withPivot(['habilitado', 'prorroga_hasta', 'motivo_prorroga'])
            ->withTimestamps();
    }

    public function historialAvances(): HasMany
    {
        return $this->hasMany(HistorialAvance::class, 'id_periodo');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    // ── Estado del período ────────────────────────────────────────────────────

    /**
     * Retorna el estado calculado del período:
     *  - recien_creado:      activo=false (nunca ha sido abierto)
     *  - abierto:            activo=true y hoy <= fecha_limite_reporte
     *  - cerrado_posible:    activo=true, pasó la fecha límite, sin prórroga alguna
     *  - en_prorroga:        activo=true, pasó la fecha límite, hay prórrogas activas
     *  - cerrado_definitivo: activo=true, pasó la fecha límite, hubo prórrogas pero ya vencieron
     */
    public function getEstadoAttribute(): string
    {
        if (! $this->activo) {
            return 'recien_creado';
        }

        $hoy = now()->startOfDay();

        if ($hoy->lte($this->fecha_limite_reporte->copy()->endOfDay())) {
            return 'abierto';
        }

        // Pasó la fecha límite — revisar prórrogas
        if ($this->tieneProrrogaActiva()) {
            return 'en_prorroga';
        }

        if ($this->tuvoProrroga()) {
            return 'cerrado_definitivo';
        }

        return 'cerrado_posible';
    }

    /**
     * ¿Existe al menos una línea con prórroga vigente (hoy <= prorroga_hasta)?
     */
    public function tieneProrrogaActiva(): bool
    {
        return DB::table('periodo_linea')
            ->where('id_periodo', $this->id)
            ->whereNotNull('prorroga_hasta')
            ->where('prorroga_hasta', '>=', now()->toDateString())
            ->exists();
    }

    /**
     * ¿Se otorgó alguna prórroga en este período (sin importar si ya venció)?
     */
    public function tuvoProrroga(): bool
    {
        return DB::table('periodo_linea')
            ->where('id_periodo', $this->id)
            ->whereNotNull('prorroga_hasta')
            ->exists();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Indica si el período está abierto globalmente o para una línea específica.
     *
     * Reglas:
     * - Si el período no está activo => false
     * - Si se envía línea:
     *      - si no existe en el pivote => false
     *      - si está deshabilitada => false
     *      - si tiene prórroga => manda la prórroga
     * - Si no hay prórroga, manda fecha límite normal
     */
    public function estaAbierto(?LineaAccion $linea = null): bool
    {
        if (! $this->activo) {
            return false;
        }

        $hoy = now();

        if ($linea) {
            // Usa relación cargada si ya existe; si no, consulta solo una vez
            $lineaRelacionada = $this->relationLoaded('lineas')
                ? $this->lineas->firstWhere('id', $linea->id)
                : $this->lineas()
                    ->wherePivot('id_linea', $linea->id)
                    ->first();

            $pivot = $lineaRelacionada?->pivot;

            // Si la línea no pertenece al período, no debe reportar
            if (! $pivot) {
                return false;
            }

            // Si está deshabilitada en el pivote, no debe reportar
            if (! $pivot->habilitado) {
                return false;
            }

            // Si existe prórroga, esa fecha manda sobre la fecha límite general
            if ($pivot->prorroga_hasta) {
                return $hoy->lte(\Carbon\Carbon::parse($pivot->prorroga_hasta)->endOfDay());
            }
        }

        return $hoy->lte($this->fecha_limite_reporte->copy()->endOfDay());
    }
}
