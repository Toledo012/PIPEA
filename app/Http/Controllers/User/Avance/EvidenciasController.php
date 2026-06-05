<?php

namespace App\Http\Controllers\User\Avance;

use App\Exports\MisAvancesExport;
use App\Http\Controllers\Controller;
use App\Models\HistorialAvance;
use App\Models\LineaAccion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class EvidenciasController extends Controller
{
    public function index(): Response
    {
        $usuario = Auth::user();
        $avances = $this->cargarAvancesUsuario($usuario->id);

        // ── KPIs ─────────────────────────────────────────────────────────────
        $kpis = $this->calcularKpis($avances);

        // ── Por estatus (pastel) ──────────────────────────────────────────────
        $porEstatus = $avances
            ->groupBy('estatus')
            ->map(fn ($items, $k) => [
                'name'  => $k ?: 'Sin clasificar',
                'value' => $items->count(),
            ])
            ->values();

        // ── Por período (línea temporal) ──────────────────────────────────────
        $porPeriodo = $avances
            ->filter(fn ($a) => $a['periodo_nombre'])
            ->groupBy('periodo_nombre')
            ->map(function ($items, $nombre) {
                $vals = $items->pluck('avance_cuantitativo')->filter(fn ($v) => $v !== null);
                return [
                    'periodo'      => $nombre,
                    'total'        => $items->count(),
                    'pct_promedio' => $vals->count() ? round($vals->avg() * 100, 1) : 0,
                ];
            })
            ->values();

        // ── Top líneas por % (barras) ─────────────────────────────────────────
        $topLineas = $avances
            ->whereNotNull('avance_cuantitativo')
            ->groupBy('id_linea_accion')
            ->map(function ($items) {
                $reciente = $items->sortByDesc('fecha_registro_raw')->first();
                return [
                    'id_linea_accion'  => $reciente['id_linea_accion'],
                    'numero_prioridad' => $reciente['numero_prioridad'],
                    'prioridad'        => $reciente['prioridad'],
                    'pct'              => round(($reciente['avance_cuantitativo'] ?? 0) * 100, 1),
                ];
            })
            ->sortByDesc('pct')
            ->take(10)
            ->values();

        // ── Catálogo de períodos ──────────────────────────────────────────────
        $periodos = DB::table('periodos_reporte')
            ->whereIn('id', $avances->pluck('id_periodo')->unique()->filter()->values())
            ->orderByDesc('fecha_inicio')
            ->get(['id', 'nombre']);

        return Inertia::render('Organismo/Avance/MisEvidencias', [
            'avances'     => $avances,
            'kpis'        => $kpis,
            'por_estatus' => $porEstatus,
            'por_periodo' => $porPeriodo,
            'top_lineas'  => $topLineas,
            'periodos'    => $periodos,
        ]);
    }

    /**
     * Exporta los avances del usuario (con filtros opcionales) a Excel.
     */
    public function exportXlsx(Request $request): BinaryFileResponse
    {
        $usuario   = Auth::user();
        $todos     = $this->cargarAvancesUsuario($usuario->id);
        $filtrados = $this->aplicarFiltros($todos, $request);

        $filtrosResumen = $this->describirFiltros($request);
        $organismo = optional($usuario->organismo)->nombre ?? '—';

        $fileName = 'mis-avances-' . now()->format('Y-m-d-Hi') . '.xlsx';

        return Excel::download(
            new MisAvancesExport(
                avances:          $filtrados,
                kpis:             $this->calcularKpis($filtrados),
                organismoNombre:  $organismo,
                usuarioNombre:    $usuario->name ?? '—',
                filtrosResumen:   $filtrosResumen,
            ),
            $fileName
        );
    }

    /**
     * Exporta los avances del usuario (con filtros opcionales) a PDF.
     */
    public function exportPdf(Request $request): HttpResponse
    {
        $usuario   = Auth::user();
        $todos     = $this->cargarAvancesUsuario($usuario->id);
        $filtrados = $this->aplicarFiltros($todos, $request);

        $filtrosResumen = $this->describirFiltros($request);
        $organismo = optional($usuario->organismo)->nombre ?? '—';

        $pdf = Pdf::loadView('exports.mis_avances_pdf', [
            'avances'         => $filtrados,
            'kpis'            => $this->calcularKpis($filtrados),
            'organismoNombre' => $organismo,
            'usuarioNombre'   => $usuario->name ?? '—',
            'filtrosResumen'  => $filtrosResumen,
        ])->setPaper('a4', 'landscape');

        $fileName = 'mis-avances-' . now()->format('Y-m-d-Hi') . '.pdf';
        return $pdf->download($fileName);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────────────────────

    private function cargarAvancesUsuario(int $idUsuario): Collection
    {
        $idLineas = LineaAccion::where('id_usuario', $idUsuario)->pluck('id');

        return HistorialAvance::with(['lineaAccion.prioridad', 'periodo'])
            ->whereIn('id_linea_accion', $idLineas)
            ->orderByDesc('fecha_registro')
            ->get()
            ->map(fn ($h) => [
                'id'                         => $h->id,
                'id_linea_accion'            => $h->id_linea_accion,
                'id_periodo'                 => $h->id_periodo,
                'periodo_nombre'             => $h->periodo?->nombre,
                'numero_prioridad'           => $h->lineaAccion?->prioridad?->numero,
                'prioridad'                  => $h->lineaAccion?->prioridad?->prioridad,
                'estatus'                    => $h->estatus,
                'avance_cualitativo'         => $h->avance_cualitativo,
                'avance_cuantitativo'        => $h->avance_cuantitativo,
                'fecha_avance'               => $h->fecha_avance?->format('Y-m-d'),
                'comentario'                 => $h->comentario,
                'medio_verificacion'         => $h->medio_verificacion,
                'documento'                  => $h->documento,
                'archivo_url'                => $h->archivo_url,
                'archivo_tipo'               => $h->archivo_tipo,
                'archivo_tamanio_formateado' => $h->archivo_tamanio_formateado ?? null,
                'url'                        => $h->url,
                'fecha_registro'             => $h->fecha_registro?->format('d/m/Y H:i'),
                'fecha_registro_raw'         => $h->fecha_registro?->toDateString(),
                'tiene_evidencia'            => (bool) ($h->archivo_path || $h->url),
            ])
            ->values();
    }

    private function calcularKpis(Collection $avances): array
    {
        $total         = $avances->count();
        $conEvidencia  = $avances->where('tiene_evidencia', true)->count();
        $promedioPct   = $avances->whereNotNull('avance_cuantitativo')->avg('avance_cuantitativo');

        return [
            'total_avances'     => $total,
            'lineas_con_avance' => $avances->pluck('id_linea_accion')->unique()->count(),
            'pct_promedio'      => $promedioPct ? round($promedioPct * 100, 1) : 0,
            'con_evidencia'     => $conEvidencia,
            'sin_evidencia'     => $total - $conEvidencia,
        ];
    }

    private function aplicarFiltros(Collection $avances, Request $request): Collection
    {
        $periodo  = $request->query('periodo');
        $estatus  = $request->query('estatus');
        $evidencia = $request->query('evidencia');
        $busqueda = strtolower(trim((string) $request->query('busqueda')));

        return $avances->filter(function ($a) use ($periodo, $estatus, $evidencia, $busqueda) {
            if ($periodo && $periodo !== 'todos' && (int) $a['id_periodo'] !== (int) $periodo) return false;
            if ($estatus && $estatus !== 'todos' && $a['estatus'] !== $estatus) return false;
            if ($evidencia === 'con' && ! $a['tiene_evidencia']) return false;
            if ($evidencia === 'sin' &&   $a['tiene_evidencia']) return false;

            if ($busqueda !== '') {
                $haystack = strtolower(implode(' ', array_filter([
                    $a['prioridad'] ?? '',
                    $a['comentario'] ?? '',
                    $a['medio_verificacion'] ?? '',
                    $a['periodo_nombre'] ?? '',
                    $a['documento'] ?? '',
                ])));
                if (! str_contains($haystack, $busqueda)) return false;
            }
            return true;
        })->values();
    }

    private function describirFiltros(Request $request): ?string
    {
        $partes = [];
        if ($request->filled('periodo')   && $request->query('periodo')   !== 'todos') {
            $nombre = DB::table('periodos_reporte')->where('id', $request->query('periodo'))->value('nombre');
            $partes[] = 'Período: ' . ($nombre ?? $request->query('periodo'));
        }
        if ($request->filled('estatus')   && $request->query('estatus')   !== 'todos') {
            $partes[] = 'Estatus: ' . $request->query('estatus');
        }
        if ($request->filled('evidencia') && $request->query('evidencia') !== 'todos') {
            $partes[] = 'Evidencia: ' . ($request->query('evidencia') === 'con' ? 'con archivo o enlace' : 'sin evidencia');
        }
        if ($request->filled('busqueda')) {
            $partes[] = 'Búsqueda: "' . $request->query('busqueda') . '"';
        }
        return $partes ? implode(' · ', $partes) : null;
    }
}
