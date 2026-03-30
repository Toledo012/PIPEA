<?php

namespace App\Http\Controllers\User\Avance;

use App\Http\Controllers\Controller;
use App\Models\HistorialAvance;
use App\Models\LineaAccion;
use App\Models\PeriodoReporte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HistorialAvanceController extends Controller
{
    public function store(Request $request, LineaAccion $lineaAccion): RedirectResponse
    {
        // ── 1. Verificar período activo y pivot ───────────────────────────────
        $periodo = PeriodoReporte::activo()->first();

        $puedeReportar = false;
        if ($periodo) {
            $pivot = DB::table('periodo_linea')
                ->where('id_periodo', $periodo->id)
                ->where('id_linea', (int) $lineaAccion->id)
                ->where('habilitado', true)
                ->first();

            if ($pivot) {
                $hoy = now()->startOfDay();
                $puedeReportar = $pivot->prorroga_hasta
                    ? $hoy->lte(\Carbon\Carbon::parse($pivot->prorroga_hasta))
                    : $hoy->lte(\Carbon\Carbon::parse($periodo->fecha_limite_reporte));
            }
        }

        abort_unless($puedeReportar, 403, 'No hay un período de reporte habilitado para esta línea.');

        // ── 2. Validar entrada ────────────────────────────────────────────────
        $data = $request->validate([
            'estatus'            => ['required', 'in:Con avances,Por el momento sin avances'],
            'avance_cualitativo' => ['nullable', 'numeric', 'min:0'],
            'fecha_avance'       => ['required', 'date'],
            'comentario'         => ['required', 'string', 'max:2000'],
            'avances_linea'      => ['nullable', 'string', 'max:2000'],
            'medio_verificacion' => ['nullable', 'string', 'max:500'],
            'url'                => ['nullable', 'url', 'max:500'],
            'archivo'            => ['nullable', 'file', 'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);

        // ── 3. Calcular avance_cuantitativo ───────────────────────────────────
        // BUG CORREGIDO: era ->xmeta, debe ser ->meta
        $cuantitativo = null;
        if (!empty($data['avance_cualitativo'])) {
            $meta = (float) ($lineaAccion->meta ?? 0);
            $lb   = (float) ($lineaAccion->linea_base ?? 0);
            if ($meta > $lb) {
                $cuantitativo = round(
                    ((float) $data['avance_cualitativo'] - $lb) / ($meta - $lb),
                    4
                );
            }
        }

        // ── 4. Archivo de evidencia ───────────────────────────────────────────
        $archivoDatos = [];
        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $path = $file->store(
                "evidencias/linea_{$lineaAccion->id}/periodo_{$periodo->id}",
                'public'
            );
            $archivoDatos = [
                'documento'       => $file->getClientOriginalName(),
                'archivo_path'    => $path,
                'archivo_tipo'    => $file->getMimeType(),
                'archivo_tamanio' => $file->getSize(),
            ];
        }

        // ── 5. Insertar inmutable ─────────────────────────────────────────────
        HistorialAvance::create([
            'id_linea_accion'     => $lineaAccion->id,
            'id_periodo'          => $periodo->id,
            'id_usuario'          => Auth::id(),
            'estatus'             => $data['estatus'],
            'avance_cualitativo'  => $data['avance_cualitativo'] ?? null,
            'avance_cuantitativo' => $cuantitativo,
            'fecha_avance'        => $data['fecha_avance'],
            'comentario'          => $data['comentario'],
            'avances_linea'       => $data['avances_linea'] ?? null,
            'medio_verificacion'  => $data['medio_verificacion'] ?? null,
            'url'                 => $data['url'] ?? null,
            ...$archivoDatos,
        ]);

        return back()->with('success', 'Avance registrado correctamente.');
    }
}
