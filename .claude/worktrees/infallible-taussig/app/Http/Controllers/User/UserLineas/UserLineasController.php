<?php

namespace App\Http\Controllers\User\UserLineas;

use App\Http\Controllers\Controller;
use App\Models\LineaAccion;
use App\Models\PeriodoReporte;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserLineasController extends Controller
{
    public function index(): Response
    {
        $usuario       = Auth::user();
        $periodoActivo = PeriodoReporte::activo()->first();

        // IDs habilitados para reportar
        $lineasHabilitadas = $periodoActivo
            ? DB::table('periodo_linea')
                ->where('id_periodo', $periodoActivo->id)
                ->where('habilitado', 1)
                ->pluck('id_linea')
                ->map(fn($id) => (int) $id)
                ->toArray()
            : [];

        // Avances ya registrados en este período — keyed por id_linea_accion
        $avancesDelPeriodo = $periodoActivo
            ? DB::table('historial_avances')
                ->where('id_periodo', $periodoActivo->id)
                ->get()
                ->keyBy('id_linea_accion')
            : collect();

        $lineas = LineaAccion::with([
            'eje', 'objetivo', 'prioridad',
            'plazo', 'frecuencia', 'estatus',
            'ultimoAvance',
        ])
            ->where('id_usuario', $usuario->id)
            ->where('activo', true)
            ->orderBy('id')
            ->get()
            ->map(function ($l) use ($lineasHabilitadas, $avancesDelPeriodo) {
                $avance = $avancesDelPeriodo->get($l->id);

                return [
                    'id'                 => $l->id,
                    'id_eje'             => $l->id_eje,
                    'numero_eje'         => $l->eje?->numero_eje,
                    'eje'                => $l->eje?->eje,
                    'numero_objetivo'    => $l->objetivo?->numero_objetivo,
                    'objetivo'           => $l->objetivo?->objetivo,
                    'numero_prioridad'   => $l->prioridad?->numero,
                    'prioridad'          => $l->prioridad?->prioridad,
                    'plazo'              => $l->plazo?->plazo,
                    'id_plazo'           => $l->id_plazo,
                    'frecuencia'         => $l->frecuencia?->frecuencia,
                    'id_frecuencia'      => $l->id_frecuencia,
                    'estatus'            => $l->estatus?->nombre,
                    'id_estatus'         => $l->id_estatus,
                    'nombre_indicador'   => $l->nombre_indicador,
                    'formula'            => $l->formula,
                    'linea_base'         => $l->linea_base,
                    'meta'               => $l->meta,
                    'unidad_medida'      => $l->unidad_medida,
                    'sentido_indicador'  => $l->sentido_indicador,
                    'porcentaje_avance'  => $l->porcentaje_avance,
                    'ultimo_valor_avance'=> $l->ultimo_valor_avance,
                    'puede_reportar'     => in_array((int) $l->id, $lineasHabilitadas, true),
                    'indicador_completo' => $l->indicador_completo,
                    'ya_reporto'         => $avance !== null,
                    'reporte_actual'     => $avance ? [
                        'estatus'            => $avance->estatus,
                        'avance_cualitativo' => $avance->avance_cualitativo,
                        'avance_cuantitativo'=> $avance->avance_cuantitativo,
                        'fecha_avance'       => $avance->fecha_avance,
                        'comentario'         => $avance->comentario,
                        'medio_verificacion' => $avance->medio_verificacion,
                        'url'                => $avance->url,
                        'documento'          => $avance->documento,
                        'archivo_url'        => $avance->archivo_path
                            ? asset('storage/' . $avance->archivo_path)
                            : null,
                    ] : null,
                ];
            });

        $ejes = $lineas
            ->unique('id_eje')
            ->sortBy('numero_eje')
            ->values()
            ->map(fn($l) => [
                'id'         => $l['id_eje'],
                'numero_eje' => $l['numero_eje'],
                'eje'        => $l['eje'],
            ]);

        return Inertia::render('Organismo/Linea/UserLineas_index', [
            'lineas'         => $lineas,
            'ejes'           => $ejes,
            'periodo_activo' => $periodoActivo ? [
                'id'                   => $periodoActivo->id,
                'nombre'               => $periodoActivo->nombre,
                'fecha_limite_reporte' => $periodoActivo->fecha_limite_reporte->format('d/m/Y'),
            ] : null,
        ]);
    }

    public function show(LineaAccion $lineaAccion): Response
    {
        abort_unless(
            $lineaAccion->id_usuario === Auth::id(),
            403,
            'No tienes acceso a esta línea.'
        );

        $lineaAccion->load([
            'eje', 'objetivo', 'prioridad',
            'plazo', 'frecuencia', 'estatus',
            'ultimoAvance',
            'historialAvances.usuario',
        ]);

        $periodo = PeriodoReporte::activo()->first();

        $puedeReportar = false;
        if ($periodo) {
            $pivot = DB::table('periodo_linea')
                ->where('id_periodo', $periodo->id)
                ->where('id_linea', (int) $lineaAccion->id)
                ->where('habilitado', 1)
                ->first();

            if ($pivot) {
                $hoy = now()->startOfDay();
                $puedeReportar = $pivot->prorroga_hasta
                    ? $hoy->lte(\Carbon\Carbon::parse($pivot->prorroga_hasta))
                    : $hoy->lte(\Carbon\Carbon::parse($periodo->fecha_limite_reporte));
            }
        }

        // Avance ya registrado en el período activo para esta línea
        $avanceActual = $periodo
            ? DB::table('historial_avances')
                ->where('id_periodo', $periodo->id)
                ->where('id_linea_accion', (int) $lineaAccion->id)
                ->first()
            : null;

        $linea = [
            'id'                 => $lineaAccion->id,
            'numero_eje'         => $lineaAccion->eje?->numero_eje,
            'eje'                => $lineaAccion->eje?->eje,
            'numero_objetivo'    => $lineaAccion->objetivo?->numero_objetivo,
            'objetivo'           => $lineaAccion->objetivo?->objetivo,
            'numero_prioridad'   => $lineaAccion->prioridad?->numero,
            'prioridad'          => $lineaAccion->prioridad?->prioridad,
            'plazo'              => $lineaAccion->plazo?->plazo,
            'frecuencia'         => $lineaAccion->frecuencia?->frecuencia,
            'estatus'            => $lineaAccion->estatus?->nombre,
            'nombre_indicador'   => $lineaAccion->nombre_indicador,
            'formula'            => $lineaAccion->formula,
            'linea_base'         => $lineaAccion->linea_base,
            'meta'               => $lineaAccion->meta,
            'unidad_medida'      => $lineaAccion->unidad_medida,
            'sentido_indicador'  => $lineaAccion->sentido_indicador,
            'porcentaje_avance'  => $lineaAccion->porcentaje_avance,
            'ultimo_valor_avance'=> $lineaAccion->ultimo_valor_avance,
            'puede_reportar'     => $puedeReportar,
            'ya_reporto'         => $avanceActual !== null,
            'reporte_actual'     => $avanceActual ? [
                'estatus'            => $avanceActual->estatus,
                'avance_cualitativo' => $avanceActual->avance_cualitativo,
                'avance_cuantitativo'=> $avanceActual->avance_cuantitativo,
                'fecha_avance'       => $avanceActual->fecha_avance,
                'comentario'         => $avanceActual->comentario,
                'medio_verificacion' => $avanceActual->medio_verificacion,
                'url'                => $avanceActual->url,
                'documento'          => $avanceActual->documento,
                'archivo_url'        => $avanceActual->archivo_path
                    ? asset('storage/' . $avanceActual->archivo_path)
                    : null,
            ] : null,
        ];

        $historial = $lineaAccion->historialAvances->map(fn($h) => [
            'id'                  => $h->id,
            'estatus'             => $h->estatus,
            'avance_cualitativo'  => $h->avance_cualitativo,
            'avance_cuantitativo' => $h->avance_cuantitativo,
            'fecha_avance'        => $h->fecha_avance?->format('d/m/Y'),
            'comentario'          => $h->comentario,
            'medio_verificacion'  => $h->medio_verificacion,
            'url'                 => $h->url,
            'documento'           => $h->documento,
            'archivo_url'         => $h->archivo_url,
            'avances_linea'       => $h->avances_linea,
            'fecha_registro'      => $h->fecha_registro?->format('d/m/Y H:i'),
            'usuario'             => $h->usuario
                ? trim("{$h->usuario->nombre} {$h->usuario->primer_apellido}")
                : null,
        ]);

        return Inertia::render('Organismo/Linea/LineaDetalle', [
            'linea'          => $linea,
            'historial'      => $historial,
            'periodo_activo' => $periodo ? [
                'id'                   => $periodo->id,
                'nombre'               => $periodo->nombre,
                'fecha_limite_reporte' => $periodo->fecha_limite_reporte->format('d/m/Y'),
            ] : null,
        ]);
    }
}
