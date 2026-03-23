<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatLineasAccionPrioridad extends Model
{
    protected $table = 'cat_lineas_accion_prioridades';

    protected $fillable = ['id_objetivo', 'numero', 'prioridad', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function objetivo()
    {
        return $this->belongsTo(CatLineasAccionObjetivo::class, 'id_objetivo');
    }

    public function estrategias()
    {
        return $this->belongsToMany(
            CatLineasAccionEstrategia::class,
            'prioridad_estrategia',
            'id_prioridad',
            'id_estrategia'
        );
    }

    public function lineasAccion()
    {
        return $this->hasMany(LineaAccion::class, 'id_prioridad');
    }
}
