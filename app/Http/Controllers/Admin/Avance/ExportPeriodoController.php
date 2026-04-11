<?php

namespace App\Http\Controllers\Admin\Avance;

use App\Http\Controllers\Controller;
use App\Models\CatLineasAccionEje;
use App\Models\OrganismoImplementador;
use App\Models\PeriodoReporte;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PeriodoExcelExport;

class ExportPeriodoController extends Controller
{
    /**
     * Construye el conjunto de líneas ya filtradas para el reporte.
     */
    private function buildLineas(PeriodoReporte $periodo, Request $request): \Illuminate\Support\Collection
    {
        $query = $periodo->lineas()
            ->with(['usuario.organismo', 'eje', 'prioridad',
                'ultimoAvance' => fn($q) => $q->where('id_periodo', $periodo->id),
            ]);

        if ($request->filled('eje_id')) {
            $query->where('id_eje', $request->integer('eje_id'));
        }

        if ($request->filled('organismo_id')) {
            $query->whereHas('usuario', fn($q) =>
                $q->where('id_organismo', $request->integer('organismo_id'))
            );
        }

        if ($request->filled('prioridad_id')) {
            $query->where('id_prioridad', $request->integer('prioridad_id'));
        }

        $lineas = $query->get()->map(function ($linea) use ($periodo) {
            $avance = $linea->ultimoAvance;
            $habilitado = $linea->pivot->habilitado;
            $reporto    = $avance !== null;

            if (! $habilitado) {
                $estatus = 'Bloqueado';
            } elseif ($reporto) {
                $estatus = 'Con reporte';
            } else {
                $estatus = 'Sin reporte';
            }

            return [
                'id'                 => $linea->id,
                'eje'                => $linea->eje?->eje ?? '—',
                'prioridad'          => $linea->prioridad?->prioridad ?? '—',
                'organismo'          => $linea->usuario?->organismo?->nombre ?? '—',
                'usuario'            => $linea->usuario
                    ? trim("{$linea->usuario->nombre} {$linea->usuario->primer_apellido}")
                    : '—',
                'meta'               => $linea->meta,
                'unidad_medida'      => $linea->unidad_medida,
                'habilitado'         => $habilitado,
                'reporto'            => $reporto,
                'estatus_display'    => $estatus,
                'estatus_avance'     => $avance?->estatus,
                'porcentaje'         => $reporto ? round(($avance->avance_cuantitativo ?? 0) * 100, 2) : null,
                'valor_avance'       => $avance?->avance_cualitativo,
                'fecha_avance'       => $avance?->fecha_avance?->format('d/m/Y'),
                'comentario'         => $avance?->comentario,
                'avances_linea'      => $avance?->avances_linea,
                'medio_verificacion' => $avance?->medio_verificacion,
                'documento'          => $avance?->documento,
                'url'                => $avance?->url,
            ];
        });

        // Filtro por estatus (aplicado sobre colección ya mapeada)
        if ($request->filled('estatus')) {
            $filtroEstatus = $request->string('estatus')->lower()->toString();
            $lineas = $lineas->filter(function ($l) use ($filtroEstatus) {
                return match ($filtroEstatus) {
                    'con_reporte'  => $l['reporto'] === true,
                    'sin_reporte'  => ! $l['reporto'] && $l['habilitado'],
                    'bloqueado'    => ! $l['habilitado'],
                    default        => true,
                };
            })->values();
        }

        return $lineas;
    }

    /**
     * Construye las estadísticas y datos para gráficas.
     */
    private function buildStats(\Illuminate\Support\Collection $lineas): array
    {
        $total      = $lineas->count();
        $reportaron = $lineas->where('reporto', true)->count();
        $bloqueados = $lineas->where('habilitado', false)->count();
        $sinReporte = $total - $reportaron - $bloqueados;

        // Agrupación por eje
        $porEje = $lineas->groupBy('eje')->map(fn($g) => [
            'total'      => $g->count(),
            'reportaron' => $g->where('reporto', true)->count(),
            'pct'        => $g->count() > 0 ? round($g->where('reporto', true)->count() / $g->count() * 100) : 0,
        ])->sortByDesc('total');

        // Agrupación por organismo
        $porOrganismo = $lineas->groupBy('organismo')->map(fn($g) => [
            'total'      => $g->count(),
            'reportaron' => $g->where('reporto', true)->count(),
            'pct'        => $g->count() > 0 ? round($g->where('reporto', true)->count() / $g->count() * 100) : 0,
        ])->sortByDesc('reportaron');

        return [
            'total'        => $total,
            'reportaron'   => $reportaron,
            'sin_reporte'  => $sinReporte,
            'bloqueados'   => $bloqueados,
            'porcentaje'   => $total > 0 ? round($reportaron / $total * 100, 1) : 0,
            'por_eje'      => $porEje,
            'por_organismo'=> $porOrganismo,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────

    public function pdf(Request $request, PeriodoReporte $periodo): Response
    {
        $lineas = $this->buildLineas($periodo, $request);
        $stats  = $this->buildStats($lineas);

        $logoPath = public_path('images/Logotipo nuevo.png');
        $logoData = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('exports.periodo_pdf', [
            'periodo'   => $periodo,
            'lineas'    => $lineas,
            'stats'     => $stats,
            'logoData'  => $logoData,
            'fechaGen'  => now()->format('d/m/Y H:i'),
            'filtros'   => $request->only(['eje_id','organismo_id','estatus','prioridad_id']),
        ])
        ->setPaper('letter', 'landscape')
        ->setOptions(['defaultFont' => 'sans-serif', 'isRemoteEnabled' => false]);

        $filename = 'Reporte_' . str_replace(' ', '_', $periodo->nombre) . '_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    public function excel(Request $request, PeriodoReporte $periodo)
    {
        $lineas = $this->buildLineas($periodo, $request);
        $stats  = $this->buildStats($lineas);

        $filename = 'Reporte_' . str_replace(' ', '_', $periodo->nombre) . '_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(
            new PeriodoExcelExport($periodo, $lineas, $stats),
            $filename
        );
    }
}
