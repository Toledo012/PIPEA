<?php

namespace App\Http\Controllers\User\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\LineaAccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserDashboardController extends Controller
{
    public function index(): Response
    {
        $usuario = Auth::user();

        $lineas = LineaAccion::with([
            'eje', 'objetivo', 'prioridad',
            'plazo', 'frecuencia', 'estatus',
        ])
            ->where('id_usuario', $usuario->id)
            ->where('activo', true)
            ->orderBy('id')
            ->get()
            ->map(fn($l) => [
                'id'                  => $l->id,
                'numero_eje'          => $l->eje?->numero_eje,
                'eje'                 => $l->eje?->eje,
                'numero_objetivo'     => $l->objetivo?->numero_objetivo,
                'numero_prioridad'    => $l->prioridad?->numero,
                'prioridad'           => $l->prioridad?->prioridad,
                'plazo'               => $l->plazo?->plazo,
                'frecuencia'          => $l->frecuencia?->frecuencia,
                'estatus'             => $l->estatus?->nombre,
                'nombre_indicador'    => $l->nombre_indicador,
                'meta'                => $l->meta,
                'unidad_medida'       => $l->unidad_medida,
                'linea_base'          => $l->linea_base,
                'sentido_indicador'   => $l->sentido_indicador,
                'avance_cuantitativo' => $l->avance_cuantitativo,
                'porcentaje_avance'   => $l->porcentaje_avance,
                'indicador_completo'  => $l->indicador_completo,
            ]);

        // Historial reciente — últimos 8 avances de todas sus líneas
        $historial = \App\Models\HistorialAvance::with('lineaAccion.prioridad')
            ->whereIn('id_linea_accion', $lineas->pluck('id'))
            ->orderByDesc('fecha_registro')
            ->limit(8)
            ->get()
            ->map(fn($h) => [
                'id'                  => $h->id,
                'id_linea_accion'     => $h->id_linea_accion,
                'numero_prioridad'    => $h->lineaAccion?->prioridad?->numero,
                'avance_cuantitativo' => $h->avance_cuantitativo,
                'avance_cualitativo'  => $h->avance_cualitativo,
                'comentario'          => $h->comentario,
                'fecha_registro'      => $h->fecha_registro?->format('d/m/Y H:i'),
            ]);

        // Métricas resumen
        $total      = $lineas->count();
        $conAvance  = $lineas->where('porcentaje_avance', '>', 0)->count();
        $sinAvance  = $total - $conAvance;
        $promedio   = $total > 0
            ? round($lineas->avg('porcentaje_avance'), 1)
            : 0;

        return Inertia::render('Organismo/Dashboard', [
            'lineas'   => $lineas,
            'historial'=> $historial,
            'metricas' => compact('total', 'conAvance', 'sinAvance', 'promedio'),
        ]);
    }

    public function show(LineaAccion $lineaAccion): Response
    {
        // Seguridad: solo puede ver sus propias líneas
        if ($lineaAccion->id_usuario !== Auth::id()) {
            abort(403);
        }

        $lineaAccion->load([
            'eje', 'objetivo', 'prioridad',
            'plazo', 'frecuencia', 'estatus',
        ]);

        $historial = $lineaAccion->historialAvances()
            ->orderByDesc('fecha_registro')
            ->get()
            ->map(fn($h) => [
                'id'                  => $h->id,
                'avance_cuantitativo' => $h->avance_cuantitativo,
                'avance_cualitativo'  => $h->avance_cualitativo,
                'medio_verificacion'  => $h->medio_verificacion,
                'url'                 => $h->url,
                'comentario'          => $h->comentario,
                'fecha_registro'      => $h->fecha_registro?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Organismo/Linea/LineaDetalle', [
            'linea'    => [
                'id'                  => $lineaAccion->id,
                'numero_eje'          => $lineaAccion->eje?->numero_eje,
                'eje'                 => $lineaAccion->eje?->eje,
                'numero_objetivo'     => $lineaAccion->objetivo?->numero_objetivo,
                'objetivo'            => $lineaAccion->objetivo?->objetivo,
                'numero_prioridad'    => $lineaAccion->prioridad?->numero,
                'prioridad'           => $lineaAccion->prioridad?->prioridad,
                'plazo'               => $lineaAccion->plazo?->plazo,
                'frecuencia'          => $lineaAccion->frecuencia?->frecuencia,
                'estatus'             => $lineaAccion->estatus?->nombre,
                'nombre_indicador'    => $lineaAccion->nombre_indicador,
                'formula'             => $lineaAccion->formula,
                'linea_base'          => $lineaAccion->linea_base,
                'sentido_indicador'   => $lineaAccion->sentido_indicador,
                'meta'                => $lineaAccion->meta,
                'unidad_medida'       => $lineaAccion->unidad_medida,
                'avance_cuantitativo' => $lineaAccion->avance_cuantitativo,
                'porcentaje_avance'   => $lineaAccion->porcentaje_avance,
            ],
            'historial' => $historial,
        ]);
    }
}
