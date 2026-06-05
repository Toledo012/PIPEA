<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LineaAccion extends Model
{
    protected $table = 'lineas_accion';

    protected $fillable = [
        'id_eje',
        'id_objetivo',
        'id_prioridad',
        'id_estrategia',
        'id_plazo',
        'id_frecuencia',
        'id_estatus',
        'id_usuario',
        'nombre_indicador',
        'formula',
        'linea_base',
        'sentido_indicador',
        'meta',
        'unidad_medida',
        'activo',
        // avance_legacy existe en BD pero no es fillable — solo lectura histórica
    ];

    protected $casts = [
        'activo' => 'boolean',
        'meta' => 'float',
        'linea_base' => 'float',
    ];

    // ── Catálogos PI-PEA ──────────────────────────────────────────────────────

    public function eje(): BelongsTo
    {
        return $this->belongsTo(CatLineasAccionEje::class, 'id_eje');
    }

    public function objetivo(): BelongsTo
    {
        return $this->belongsTo(CatLineasAccionObjetivo::class, 'id_objetivo');
    }

    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(CatLineasAccionPrioridad::class, 'id_prioridad');
    }

    public function estrategia(): BelongsTo
    {
        return $this->belongsTo(CatLineasAccionEstrategia::class, 'id_estrategia');
    }

    public function plazo(): BelongsTo
    {
        return $this->belongsTo(CatLineasAccionPlazo::class, 'id_plazo');
    }

    public function frecuencia(): BelongsTo
    {
        return $this->belongsTo(CatLineasAccionFrecMedicion::class, 'id_frecuencia');
    }

    public function estatus(): BelongsTo
    {
        return $this->belongsTo(CatLineasAccionEstatus::class, 'id_estatus');
    }

    // ── Responsabilidad ───────────────────────────────────────────────────────

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public function getOrganismoAttribute()
    {
        return $this->usuario?->organismo;
    }

    // ── Períodos y avances ────────────────────────────────────────────────────

    public function periodos(): BelongsToMany
    {
        return $this->belongsToMany(
            PeriodoReporte::class,
            'periodo_linea',
            'id_linea',
            'id_periodo'
        )->using(PeriodoLinea::class)
            ->withPivot(['habilitado', 'prorroga_hasta', 'motivo_prorroga'])
            ->withTimestamps();
    }

    public function historialAvances(): HasMany
    {
        return $this->hasMany(HistorialAvance::class, 'id_linea_accion')
            ->orderByDesc('fecha_registro');
    }

    public function ultimoAvance(): HasOne
    {
        return $this->hasOne(HistorialAvance::class, 'id_linea_accion')
            ->latestOfMany('fecha_registro');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Porcentaje 0-100 calculado desde el último avance del historial.
     * avance_cuantitativo en historial va de 0 a 1 (igual que Excel col S).
     */
    public function getPorcentajeAvanceAttribute(): float
    {
        $ultimo = $this->ultimoAvance;
        if (!$ultimo || $ultimo->avance_cuantitativo === null) return 0;
        return round($ultimo->avance_cuantitativo * 100, 2);
    }

    /**
     * Valor bruto del último avance (Excel col R) en unidades de la meta.
     */
    public function getUltimoValorAvanceAttribute(): ?float
    {
        return $this->ultimoAvance?->avance_cualitativo;
    }

    /**
     * ¿Tiene todos los campos del indicador llenos?
     * Controla el badge "Completo / Pendiente" en la tabla.
     */
    public function getIndicadorCompletoAttribute(): bool
    {
        return filled($this->nombre_indicador)
            && filled($this->formula)
            && !is_null($this->linea_base)
            && !is_null($this->id_estatus)
            && !is_null($this->id_frecuencia);
    }

    /**
     * ¿Puede registrar avance ahora mismo?
     * Requiere período activo + habilitado para esta línea + dentro de fechas.
     */
    public function getPuedeReportarAttribute(): bool
    {
        $periodo = PeriodoReporte::activo()->first();
        if (! $periodo) return false;

        $pivot = \DB::table('periodo_linea')
            ->where('id_periodo', $periodo->id)
            ->where('id_linea', $this->id)
            ->where('habilitado', true)
            ->first();

        if (! $pivot) return false;

        $hoy = now()->startOfDay();

        if ($pivot->prorroga_hasta) {
            return $hoy->lte(\Carbon\Carbon::parse($pivot->prorroga_hasta));
        }

        return $hoy->lte(\Carbon\Carbon::parse($periodo->fecha_limite_reporte));
    }

}
