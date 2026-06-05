<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PeriodoLinea extends Pivot
{
    protected $table = 'periodo_linea';

    protected $fillable = [
        'id_periodo',
        'id_linea',
        'habilitado',
        'prorroga_hasta',
        'motivo_prorroga',
    ];

    protected $casts = [
        'habilitado'     => 'boolean',
        'prorroga_hasta' => 'datetime',
    ];

    /**
     * ¿Puede reportar ahora mismo?
     * Habilitado + dentro de fecha límite del período o de su prórroga individual.
     */
    public function puedeReportar(PeriodoReporte $periodo): bool
    {
        if (! $this->habilitado) return false;

        $hoy = now()->startOfDay();

        if ($this->prorroga_hasta) {
            return $hoy->lte($this->prorroga_hasta);
        }

        return $hoy->lte($periodo->fecha_limite_reporte);
    }
}
