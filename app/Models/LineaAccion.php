<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'id_usuario',
        'meta',
        'unidad_medida',
        'avance_cuantitativo',
        'fecha_registro',
        'fecha_actualizacion',
        'activo',
    ];

    protected $casts = [
        'activo'              => 'boolean',
        'meta'                => 'decimal:4',
        'avance_cuantitativo' => 'decimal:4',
        'fecha_registro'      => 'datetime',
        'fecha_actualizacion' => 'datetime',
    ];

    // ── Catálogos PI-PEA ──────────────────────────────────────────────────────
    public function eje()
    {
        return $this->belongsTo(CatLineasAccionEje::class, 'id_eje');
    }

    public function objetivo()
    {
        return $this->belongsTo(CatLineasAccionObjetivo::class, 'id_objetivo');
    }

    public function prioridad()
    {
        return $this->belongsTo(CatLineasAccionPrioridad::class, 'id_prioridad');
    }

    public function estrategia()
    {
        return $this->belongsTo(CatLineasAccionEstrategia::class, 'id_estrategia');
    }

    public function plazo()
    {
        return $this->belongsTo(CatLineasAccionPlazo::class, 'id_plazo');
    }

    public function frecuencia()
    {
        return $this->belongsTo(CatLineasAccionFrecMedicion::class, 'id_frecuencia');
    }

    // ── Responsabilidad ───────────────────────────────────────────────────────
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    // Acceso directo al organismo via usuario
    public function getOrganismoAttribute()
    {
        return $this->usuario?->organismo;
    }

    // ── Avances ───────────────────────────────────────────────────────────────
    public function historialAvances()
    {
        return $this->hasMany(HistorialAvance::class, 'id_linea_accion');
    }

    // ── Helper — porcentaje de avance ─────────────────────────────────────────
    public function getPorcentajeAvanceAttribute(): float
    {
        if (!$this->meta || $this->meta == 0) return 0;
        return round(($this->avance_cuantitativo / $this->meta) * 100, 2);
    }
}
