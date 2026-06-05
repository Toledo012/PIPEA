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
use Illuminate\Validation\Rule;

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

        // ── 1.b Un solo avance por (período, línea) ───────────────────────────
        // Defensa en profundidad: la UI ya oculta el botón si ya_reporto = true,
        // pero el backend debe garantizar la regla porque los avances son inmutables.
        $yaExiste = HistorialAvance::where('id_periodo', $periodo->id)
            ->where('id_linea_accion', $lineaAccion->id)
            ->exists();

        if ($yaExiste) {
            return back()
                ->withInput()
                ->with('error', 'Ya registraste un avance para esta línea en el período actual. Solo se permite un avance por período.');
        }

        // ── 2. Validar entrada ────────────────────────────────────────────────
        // Reglas condicionales según el estatus:
        //  • "Con avances"                → debe traer valor numérico y al menos un medio de evidencia
        //  • "Por el momento sin avances" → solo justifica con comentario
        $conAvance = $request->input('estatus') === 'Con avances';

        $data = $request->validate([
            'estatus'            => ['required', 'in:Con avances,Por el momento sin avances'],
            'avance_cualitativo' => [$conAvance ? 'required' : 'nullable', 'numeric', 'min:0'],
            'fecha_avance'       => ['required', 'date', 'before_or_equal:today'],
            'comentario'         => ['required', 'string', 'min:10', 'max:2000'],
            'avances_linea'      => ['nullable', 'string', 'max:2000'],
            'medio_verificacion' => [$conAvance ? 'required' : 'nullable', 'string', 'min:3', 'max:500'],
            'url'                => ['nullable', 'url', 'max:500',
                Rule::requiredIf(fn () => $conAvance && ! $request->hasFile('archivo'))],
            'archivo'            => ['nullable', 'file', 'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                Rule::requiredIf(fn () => $conAvance && blank($request->input('url')))],
        ], [
            'avance_cualitativo.required'  => 'Indica el valor alcanzado en este período.',
            'comentario.min'               => 'El comentario debe tener al menos 10 caracteres.',
            'medio_verificacion.required'  => 'Especifica el medio de verificación.',
            'medio_verificacion.min'       => 'Describe el medio de verificación con un mínimo de 3 caracteres.',
            'url.required'                 => 'Adjunta un archivo o proporciona un enlace como evidencia.',
            'archivo.required'             => 'Adjunta un archivo o proporciona un enlace como evidencia.',
            'fecha_avance.before_or_equal' => 'La fecha del avance no puede ser futura.',
        ]);

        // Defensa adicional: comentario no puede ser solo espacios en blanco.
        if (trim($data['comentario']) === '') {
            return back()->withInput()->withErrors([
                'comentario' => 'El comentario no puede estar vacío.',
            ]);
        }

        // ── 3. Calcular avance_cuantitativo ───────────────────────────────────
        // Cubre los 3 casos reales:
        //   meta > lb  → proporción dentro del rango   (val - lb) / (meta - lb)
        //   meta == lb → meta absoluta                  val / meta
        //   meta == 0  → no calculable                  null
        $cuantitativo = null;
        if (!empty($data['avance_cualitativo'])) {
            $val  = (float) $data['avance_cualitativo'];
            $meta = (float) ($lineaAccion->meta ?? 0);
            $lb   = (float) ($lineaAccion->linea_base ?? 0);

            if ($meta > $lb) {
                $cuantitativo = round(($val - $lb) / ($meta - $lb), 4);
            } elseif ($meta > 0) {
                // meta == lb (línea base coincide con meta): tratar como meta absoluta
                $cuantitativo = round($val / $meta, 4);
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
