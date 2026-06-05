<?php

namespace App\Http\Controllers\User\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\HistorialAvance;
use App\Models\LineaAccion;
use App\Models\PeriodoReporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserDashboardController extends Controller
{
    public function index(): Response
    {
        $usuario = Auth::user();

        $lineasModelos = LineaAccion::with([
            'eje', 'objetivo', 'prioridad',
            'plazo', 'frecuencia', 'estatus',
            'ultimoAvance',
        ])
            ->where('id_usuario', $usuario->id)
            ->where('activo', true)
            ->orderBy('id')
            ->get();

        // ── Período activo + qué líneas del usuario están habilitadas ────────
        $periodoActivo = PeriodoReporte::activo()->first();
        $periodoData   = null;
        $habilitadas   = collect();  // ids
        $yaReportaron  = collect();  // ids con avance ya enviado en este período
        $diasRestantes = null;

        if ($periodoActivo) {
            $habilitadas = DB::table('periodo_linea')
                ->where('id_periodo', $periodoActivo->id)
                ->where('habilitado', true)
                ->whereIn('id_linea', $lineasModelos->pluck('id'))
                ->pluck('id_linea');

            $yaReportaron = HistorialAvance::where('id_periodo', $periodoActivo->id)
                ->whereIn('id_linea_accion', $lineasModelos->pluck('id'))
                ->pluck('id_linea_accion');

            $limite = \Carbon\Carbon::parse($periodoActivo->fecha_limite_reporte);
            $diasRestantes = (int) now()->startOfDay()->diffInDays($limite, false);

            $periodoData = [
                'id'                   => $periodoActivo->id,
                'nombre'               => $periodoActivo->nombre,
                'fecha_limite_reporte' => $limite->format('Y-m-d'),
                'dias_restantes'       => $diasRestantes,
                'esta_abierto'         => $diasRestantes >= 0,
            ];
        }

        $lineas = $lineasModelos->map(fn ($l) => [
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
            'ultimo_valor_avance' => $l->ultimo_valor_avance,
            'porcentaje_avance'   => $l->porcentaje_avance,
            'indicador_completo'  => $l->indicador_completo,
            // ── Flags de período (UI usa esto para CTA dirigido) ──
            'habilitada_periodo'  => $habilitadas->contains($l->id),
            'ya_reporto_periodo'  => $yaReportaron->contains($l->id),
        ]);

        // ── Historial reciente — últimos 8 avances ───────────────────────────
        $historial = HistorialAvance::with('lineaAccion.prioridad')
            ->whereIn('id_linea_accion', $lineasModelos->pluck('id'))
            ->orderByDesc('fecha_registro')
            ->limit(8)
            ->get()
            ->map(fn ($h) => [
                'id'                  => $h->id,
                'id_linea_accion'     => $h->id_linea_accion,
                'numero_prioridad'    => $h->lineaAccion?->prioridad?->numero,
                'estatus'             => $h->estatus,
                'avance_cuantitativo' => $h->avance_cuantitativo,
                'avance_cualitativo'  => $h->avance_cualitativo,
                'comentario'          => $h->comentario,
                'fecha_registro'      => $h->fecha_registro?->format('d/m/Y H:i'),
            ]);

        // ── Métricas resumen ─────────────────────────────────────────────────
        $total     = $lineas->count();
        $conAvance = $lineas->where('porcentaje_avance', '>', 0)->count();
        $sinAvance = $total - $conAvance;
        $promedio  = $total > 0 ? round($lineas->avg('porcentaje_avance'), 1) : 0;
        $porReportar = $periodoActivo
            ? $habilitadas->diff($yaReportaron)->count()
            : 0;

        // ── Series para charts ───────────────────────────────────────────────
        // Promedio de % por eje (donut)
        $porEje = $lineas
            ->filter(fn ($l) => $l['eje'])
            ->groupBy(fn ($l) => 'Eje ' . str_pad((string) $l['numero_eje'], 2, '0', STR_PAD_LEFT))
            ->map(fn ($items) => round($items->avg('porcentaje_avance'), 1))
            ->sortKeys()
            ->map(fn ($v, $k) => ['name' => $k, 'value' => $v])
            ->values();

        // Tendencia de avance promedio por período (línea temporal — todos los avances del usuario)
        $tendencia = HistorialAvance::with('periodo')
            ->whereIn('id_linea_accion', $lineasModelos->pluck('id'))
            ->whereNotNull('avance_cuantitativo')
            ->get()
            ->filter(fn ($h) => $h->periodo)
            ->groupBy(fn ($h) => $h->periodo->nombre)
            ->map(fn ($items, $nombre) => [
                'periodo'      => $nombre,
                'pct_promedio' => round($items->avg('avance_cuantitativo') * 100, 1),
            ])
            ->values();

        return Inertia::render('Organismo/Dashboard', [
            'lineas'         => $lineas,
            'historial'      => $historial,
            'metricas'       => compact('total', 'conAvance', 'sinAvance', 'promedio', 'porReportar'),
            'periodo_activo' => $periodoData,
            'por_eje'        => $porEje,
            'tendencia'      => $tendencia,
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

        // ── Período activo + reglas de reporte (igual que UserLineasController) ──
        $periodoActivo = PeriodoReporte::activo()->first();
        $puedeReportar = false;
        $periodoData   = null;

        if ($periodoActivo) {
            $pivot = DB::table('periodo_linea')
                ->where('id_periodo', $periodoActivo->id)
                ->where('id_linea', $lineaAccion->id)
                ->where('habilitado', true)
                ->first();

            if ($pivot) {
                $hoy = now()->startOfDay();
                $limite = $pivot->prorroga_hasta
                    ? \Carbon\Carbon::parse($pivot->prorroga_hasta)
                    : \Carbon\Carbon::parse($periodoActivo->fecha_limite_reporte);
                $puedeReportar = $hoy->lte($limite);
            }

            $periodoData = [
                'id'                   => $periodoActivo->id,
                'nombre'               => $periodoActivo->nombre,
                'fecha_limite_reporte' => optional($periodoActivo->fecha_limite_reporte)->format('Y-m-d'),
                'estado'               => ($pivot && $pivot->prorroga_hasta) ? 'en_prorroga' : 'abierto',
            ];
        }

        // ── ¿Ya reportó en este período? + datos del reporte actual ──────────
        $reporteActual = null;
        $yaReporto     = false;
        if ($periodoActivo) {
            $reporte = HistorialAvance::where('id_periodo', $periodoActivo->id)
                ->where('id_linea_accion', $lineaAccion->id)
                ->first();
            if ($reporte) {
                $yaReporto = true;
                $reporteActual = [
                    'id'                  => $reporte->id,
                    'estatus'             => $reporte->estatus,
                    'avance_cualitativo'  => $reporte->avance_cualitativo,
                    'avance_cuantitativo' => $reporte->avance_cuantitativo,
                    'fecha_avance'        => optional($reporte->fecha_avance)->format('Y-m-d'),
                    'comentario'          => $reporte->comentario,
                    'medio_verificacion'  => $reporte->medio_verificacion,
                    'documento'           => $reporte->documento,
                    'archivo_url'         => $reporte->archivo_url,
                    'url'                 => $reporte->url,
                ];
            }
        }

        // ── Historial completo ───────────────────────────────────────────────
        $historial = $lineaAccion->historialAvances()
            ->with('usuario:id,nombre,primer_apellido,segundo_apellido')
            ->orderByDesc('fecha_registro')
            ->get()
            ->map(fn ($h) => [
                'id'                  => $h->id,
                'estatus'             => $h->estatus,
                'avance_cuantitativo' => $h->avance_cuantitativo,
                'avance_cualitativo'  => $h->avance_cualitativo,
                'fecha_avance'        => optional($h->fecha_avance)->format('Y-m-d'),
                'comentario'          => $h->comentario,
                'avances_linea'       => $h->avances_linea,
                'medio_verificacion'  => $h->medio_verificacion,
                'documento'           => $h->documento,
                'archivo_url'         => $h->archivo_url,
                'url'                 => $h->url,
                'fecha_registro'      => $h->fecha_registro?->format('d/m/Y H:i'),
                'usuario'             => $h->usuario
                    ? trim(($h->usuario->nombre ?? '') . ' ' . ($h->usuario->primer_apellido ?? '') . ' ' . ($h->usuario->segundo_apellido ?? ''))
                    : null,
            ]);

        // ── Último valor reportado (para el card "Avance actual") ────────────
        $ultimoValor = $historial->first()['avance_cualitativo'] ?? null;

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
                // ── Campos nuevos para el flujo de reporte ──
                'ultimo_valor_avance' => $ultimoValor,
                'puede_reportar'      => $puedeReportar,
                'ya_reporto'          => $yaReporto,
                'reporte_actual'      => $reporteActual,
            ],
            'historial'      => $historial,
            'periodo_activo' => $periodoData,
        ]);
    }
}
