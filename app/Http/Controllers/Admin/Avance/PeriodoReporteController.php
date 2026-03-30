<?php

namespace App\Http\Controllers\Admin\Avance;

use App\Http\Controllers\Controller;
use App\Models\LineaAccion;
use App\Models\PeriodoReporte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class PeriodoReporteController extends Controller
{
    public function index(): Response
    {
        $periodos = PeriodoReporte::withCount('historialAvances')
            ->orderByDesc('fecha_inicio')
            ->get()
            ->map(fn($p) => [
                'id'                   => $p->id,
                'nombre'               => $p->nombre,
                'fecha_inicio'         => $p->fecha_inicio->format('d/m/Y'),
                'fecha_fin'            => $p->fecha_fin->format('d/m/Y'),
                'fecha_limite_reporte' => $p->fecha_limite_reporte->format('d/m/Y'),
                'activo'               => $p->activo,
                'descripcion'          => $p->descripcion,
                'total_avances'        => $p->historial_avances_count,
                'total_lineas'         => DB::table('periodo_linea')
                    ->where('id_periodo', $p->id)
                    ->count(),
            ]);

        return Inertia::render('Admin/Periodos/Index', [
            'periodos' => $periodos,
        ]);
    }

    public function detalle(PeriodoReporte $periodo): JsonResponse
    {
        $lineas = $periodo->lineas()
            ->with([
                'usuario.organismo',
                'ultimoAvance' => fn($q) => $q->where('id_periodo', $periodo->id),
            ])
            ->get()
            ->map(fn($linea) => [
                'id'             => $linea->id,
                'prioridad'      => $linea->prioridad?->prioridad,
                'organismo'      => $linea->usuario?->organismo?->nombre ?? '—',
                'usuario'        => $linea->usuario
                    ? trim("{$linea->usuario->nombre} {$linea->usuario->primer_apellido}")
                    : '—',
                'habilitado'     => $linea->pivot->habilitado,
                'prorroga_hasta' => $linea->pivot->prorroga_hasta
                    ? \Carbon\Carbon::parse($linea->pivot->prorroga_hasta)->format('d/m/Y')
                    : null,
                'meta'           => $linea->meta,
                'unidad_medida'  => $linea->unidad_medida,
                // ── Lo que reportó ────────────────────────────────────────────
                'reporto'              => $linea->ultimoAvance !== null,
                'estatus'              => $linea->ultimoAvance?->estatus,
                'porcentaje'           => $linea->ultimoAvance
                    ? round(($linea->ultimoAvance->avance_cuantitativo ?? 0) * 100, 2)
                    : null,
                'valor_avance'         => $linea->ultimoAvance?->avance_cualitativo,
                'fecha_avance'         => $linea->ultimoAvance?->fecha_avance?->format('d/m/Y'),
                'comentario'           => $linea->ultimoAvance?->comentario,
                'avances_linea'        => $linea->ultimoAvance?->avances_linea,
                'medio_verificacion'   => $linea->ultimoAvance?->medio_verificacion,
                'url'                  => $linea->ultimoAvance?->url,
                'documento'            => $linea->ultimoAvance?->documento,
                'archivo_url'          => $linea->ultimoAvance?->archivo_url,
                'archivo_tipo'         => $linea->ultimoAvance?->archivo_tipo,
            ]);

        $total      = $lineas->count();
        $reportaron = $lineas->where('reporto', true)->count();

        return response()->json([
            'periodo' => [
                'id'                   => $periodo->id,
                'nombre'               => $periodo->nombre,
                'fecha_inicio'         => $periodo->fecha_inicio->format('d/m/Y'),
                'fecha_fin'            => $periodo->fecha_fin->format('d/m/Y'),
                'fecha_limite_reporte' => $periodo->fecha_limite_reporte->format('d/m/Y'),
                'activo'               => $periodo->activo,
                'descripcion'          => $periodo->descripcion,
            ],
            'stats' => [
                'total'       => $total,
                'reportaron'  => $reportaron,
                'sin_reporte' => $total - $reportaron,
                'porcentaje'  => $total > 0 ? round($reportaron / $total * 100, 1) : 0,
            ],
            'lineas' => $lineas,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre'               => ['required', 'string', 'max:100'],
            'fecha_inicio'         => ['required', 'date'],
            'fecha_fin'            => ['required', 'date', 'after:fecha_inicio'],
            'fecha_limite_reporte' => ['required', 'date', 'after_or_equal:fecha_fin'],
            'descripcion'          => ['nullable', 'string', 'max:500'],
        ]);

        $periodo = PeriodoReporte::create([...$data, 'activo' => false]);

        $lineas = LineaAccion::where('activo', true)->pluck('id');

        $pivots = $lineas->map(fn($id) => [
            'id_periodo'  => $periodo->id,
            'id_linea'    => $id,
            'habilitado'  => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ])->all();

        foreach (array_chunk($pivots, 100) as $chunk) {
            DB::table('periodo_linea')->insert($chunk);
        }

        return back()->with('success', "Período '{$periodo->nombre}' creado con {$lineas->count()} líneas asignadas.");
    }

    public function toggleActivo(PeriodoReporte $periodo): RedirectResponse
    {
        if (! $periodo->activo) {
            PeriodoReporte::where('activo', true)->update(['activo' => false]);
        }

        $periodo->update(['activo' => ! $periodo->activo]);
        $estado = $periodo->fresh()->activo ? 'abierto' : 'cerrado';

        return back()->with('success', "Período '{$periodo->nombre}' {$estado}.");
    }

    public function prorroga(Request $request, PeriodoReporte $periodo): RedirectResponse
    {
        $data = $request->validate([
            'id_linea'       => ['required', 'exists:lineas_accion,id'],
            'prorroga_hasta' => ['required', 'date', 'after:today'],
            'motivo'         => ['required', 'string', 'max:500'],
        ]);

        $actualizado = DB::table('periodo_linea')
            ->where('id_periodo', $periodo->id)
            ->where('id_linea', $data['id_linea'])
            ->update([
                'habilitado'      => true,
                'prorroga_hasta'  => $data['prorroga_hasta'],
                'motivo_prorroga' => $data['motivo'],
                'updated_at'      => now(),
            ]);

        if (! $actualizado) {
            return back()->withErrors(['id_linea' => 'Esa línea no pertenece a este período.']);
        }

        return back()->with('success', 'Prórroga habilitada correctamente.');
    }
}
