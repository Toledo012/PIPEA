<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatLineasAccionEstatus extends Model
{
    protected $table = 'cat_lineas_accion_estatus';

    protected $fillable = ['nombre', 'activo'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = ['activo' => 'boolean'];

    // Un estatus puede estar asignado a muchas líneas de acción
    public function lineasAccion()
    {
        return $this->hasMany(LineaAccion::class, 'id_estatus');
    }
}
