<?php

namespace App\Http\Controllers\User\Avance;

use App\Http\Controllers\Controller;
use App\Models\HistorialAvance;
use App\Models\LineaAccion;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EvidenciasController extends Controller
{
    public function index(): Response
    {
        $usuario = Auth::user();

        // IDs de líneas del usuario
        $idLineas = LineaAccion::where('id_usuario', $usuario->id)
            ->pluck('id');

        // Todos los avances con evidencia (archivo o URL)
        $avances = HistorialAvance::with('lineaAccion.prioridad')
            ->whereIn('id_linea_accion', $idLineas)
            ->where(function ($q) {
                $q->whereNotNull('archivo_path')
                    ->orWhereNotNull('url');
            })
            ->orderByDesc('fecha_registro')
            ->get()
            ->map(fn($h) => [
                'id'                       => $h->id,
                'id_linea_accion'          => $h->id_linea_accion,
                'numero_prioridad'         => $h->lineaAccion?->prioridad?->numero,
                'prioridad'                => $h->lineaAccion?->prioridad?->prioridad,
                'avance_cuantitativo'      => $h->avance_cuantitativo,
                'avance_cualitativo'       => $h->avance_cualitativo,
                'medio_verificacion'       => $h->medio_verificacion,
                // Archivo
                'documento'                => $h->documento,
                'archivo_url'              => $h->archivo_url,
                'archivo_tipo'             => $h->archivo_tipo,
                'archivo_tamanio_formateado' => $h->archivo_tamanio_formateado,
                'archivo_icono'            => $h->archivo_icono,
                // URL externa
                'url'                      => $h->url,
                'comentario'               => $h->comentario,
                'fecha_registro'           => $h->fecha_registro?->format('d/m/Y H:i'),
                'fecha_registro_raw'       => $h->fecha_registro?->toDateString(),
            ]);

        // Vista 1: agrupado por línea de acción
        $porLinea = $avances
            ->groupBy('id_linea_accion')
            ->map(fn($items, $idLinea) => [
                'id_linea_accion'  => $idLinea,
                'numero_prioridad' => $items->first()['numero_prioridad'],
                'prioridad'        => $items->first()['prioridad'],
                'total'            => $items->count(),
                'avances'          => $items->values(),
            ])
            ->values();

        // Vista 2: cronológico (ya está ordenado por fecha desc)
        $cronologico = $avances->values();

        return Inertia::render('Organismo/Avance/MisEvidencias', [
            'porLinea'    => $porLinea,
            'cronologico' => $cronologico,
        ]);
    }
}
