<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<title>Mis avances — {{ $usuarioNombre }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: sans-serif; font-size: 9pt; color: #1a1a1a; }

    .header { border-bottom: 3px solid #009887; padding-bottom: 8px; margin-bottom: 14px; }
    .header-sistema { font-size: 7pt; color: #009887; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; }
    .header-titulo  { font-size: 14pt; font-weight: bold; margin: 2px 0; }
    .header-meta    { font-size: 8pt; color: #555; }

    .kpi-row { display: table; width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .kpi-cell { display: table-cell; width: 25%; text-align: center; padding: 10px 6px; border: 1px solid #e5e7eb; background: #fafafa; }
    .kpi-val { font-size: 16pt; font-weight: bold; color: #009887; }
    .kpi-val-magenta { color: #C90166; }
    .kpi-lbl { font-size: 7pt; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; font-weight: bold; margin-top: 2px; }

    .filtros-box { background: #fff8e1; border: 1px solid #f0c040; padding: 6px 10px; margin-bottom: 12px; font-size: 8pt; color: #92610a; }

    table.avances { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.avances th { background: #009887; color: #fff; padding: 6px 5px; font-size: 8pt; text-align: left; border: 1px solid #007a6c; }
    table.avances td { padding: 5px; font-size: 8pt; border: 1px solid #d1d5db; vertical-align: top; }
    table.avances tr:nth-child(even) td { background: #fafafa; }

    .badge { display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 7pt; font-weight: bold; }
    .badge-ok   { background: #d1fae5; color: #065f46; }
    .badge-pend { background: #fef3c7; color: #92400e; }

    .pct-val { font-weight: bold; color: #009887; font-size: 9pt; }

    .empty { text-align: center; padding: 30px; color: #9ca3af; font-size: 9pt; }

    .footer { margin-top: 16px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 7pt; color: #6b7280; text-align: center; }
</style>
</head>
<body>

<div class="header">
    <div class="header-sistema">Sistema PI-PEA · SESAECH</div>
    <div class="header-titulo">Mis avances registrados</div>
    <div class="header-meta">
        Organismo: <strong>{{ $organismoNombre }}</strong> · Usuario: <strong>{{ $usuarioNombre }}</strong><br/>
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>
</div>

@if (!empty($filtrosResumen))
    <div class="filtros-box"><strong>Filtros aplicados:</strong> {{ $filtrosResumen }}</div>
@endif

<div class="kpi-row">
    <div class="kpi-cell">
        <div class="kpi-val">{{ $kpis['total_avances'] ?? 0 }}</div>
        <div class="kpi-lbl">Total avances</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-val">{{ $kpis['lineas_con_avance'] ?? 0 }}</div>
        <div class="kpi-lbl">Líneas con avance</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-val kpi-val-magenta">{{ $kpis['pct_promedio'] ?? 0 }}%</div>
        <div class="kpi-lbl">% Promedio</div>
    </div>
    <div class="kpi-cell">
        <div class="kpi-val">{{ $kpis['con_evidencia'] ?? 0 }}/{{ $kpis['total_avances'] ?? 0 }}</div>
        <div class="kpi-lbl">Con evidencia</div>
    </div>
</div>

@if (count($avances) === 0)
    <div class="empty">No hay avances registrados con los filtros aplicados.</div>
@else
    <table class="avances">
        <thead>
            <tr>
                <th style="width:5%">Pri</th>
                <th style="width:25%">Línea de acción</th>
                <th style="width:11%">Período</th>
                <th style="width:9%">Estatus</th>
                <th style="width:8%">Fecha</th>
                <th style="width:7%">Valor</th>
                <th style="width:7%">%</th>
                <th style="width:13%">Medio verif.</th>
                <th style="width:15%">Evidencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($avances as $a)
                <tr>
                    <td><strong>P{{ $a['numero_prioridad'] ?? '—' }}</strong></td>
                    <td>{{ \Illuminate\Support\Str::limit($a['prioridad'] ?? '—', 120) }}</td>
                    <td>{{ $a['periodo_nombre'] ?? '—' }}</td>
                    <td>
                        <span class="badge {{ ($a['estatus'] ?? '') === 'Con avances' ? 'badge-ok' : 'badge-pend' }}">
                            {{ \Illuminate\Support\Str::limit($a['estatus'] ?? '—', 22) }}
                        </span>
                    </td>
                    <td>{{ $a['fecha_avance'] ?? '—' }}</td>
                    <td>{{ $a['avance_cualitativo'] ?? '—' }}</td>
                    <td><span class="pct-val">{{ isset($a['avance_cuantitativo']) ? round($a['avance_cuantitativo'] * 100, 1) . '%' : '—' }}</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($a['medio_verificacion'] ?? '—', 50) }}</td>
                    <td>
                        @if (!empty($a['documento']))
                            📎 {{ \Illuminate\Support\Str::limit($a['documento'], 30) }}<br/>
                        @endif
                        @if (!empty($a['url']))
                            🔗 {{ \Illuminate\Support\Str::limit($a['url'], 30) }}
                        @endif
                        @if (empty($a['documento']) && empty($a['url']))
                            <em style="color:#9ca3af">Sin evidencia</em>
                        @endif
                    </td>
                </tr>
                @if (!empty($a['comentario']))
                    <tr>
                        <td></td>
                        <td colspan="8" style="background:#f9fafb;font-style:italic;font-size:7.5pt;color:#4b5563">
                            <strong>Comentario:</strong> {{ \Illuminate\Support\Str::limit($a['comentario'], 400) }}
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
@endif

<div class="footer">
    Documento generado automáticamente — Sistema PI-PEA · SESAECH · {{ now()->format('Y') }}
</div>

</body>
</html>
