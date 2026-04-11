<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"/>
<style>
/* ── Paleta institucional ── */
:root {
    --verde:   #009887;
    --magenta: #C90166;
    --rojo:    #AE1922;
    --negro:   #000000;
    --crema:   #D3C2B4;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: sans-serif;
    font-size: 9pt;
    color: #1a1a1a;
    background: #fff;
}

/* ── Encabezado ── */
.header {
    display: table;
    width: 100%;
    border-bottom: 3px solid #009887;
    padding-bottom: 8px;
    margin-bottom: 14px;
}
.header-logo  { display: table-cell; width: 120px; vertical-align: middle; }
.header-logo img { max-height: 54px; max-width: 110px; }
.header-info  { display: table-cell; vertical-align: middle; padding-left: 14px; }
.header-sistema { font-size: 7pt; color: #009887; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; }
.header-periodo { font-size: 14pt; font-weight: bold; color: #1a1a1a; margin: 2px 0; }
.header-fechas  { font-size: 7.5pt; color: #555; }
.header-right   { display: table-cell; text-align: right; vertical-align: middle; width: 180px; }
.header-fecha-gen { font-size: 7pt; color: #777; }
.badge-estado {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 7pt;
    font-weight: bold;
    margin-top: 4px;
}
.badge-abierto  { background: #d1fae5; color: #065f46; }
.badge-cerrado  { background: #fee2e2; color: #7f1d1d; }

/* ── Resumen ejecutivo ── */
.resumen {
    display: table;
    width: 100%;
    margin-bottom: 14px;
    border-spacing: 6px;
}
.resumen-cell {
    display: table-cell;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 8px 12px;
    text-align: center;
    width: 25%;
    vertical-align: middle;
}
.resumen-cell.verde   { border-top: 3px solid #009887; }
.resumen-cell.magenta { border-top: 3px solid #C90166; }
.resumen-cell.rojo    { border-top: 3px solid #AE1922; }
.resumen-cell.crema   { border-top: 3px solid #D3C2B4; }
.resumen-num   { font-size: 20pt; font-weight: bold; color: #1a1a1a; display: block; line-height: 1.2; }
.resumen-label { font-size: 7pt; color: #666; text-transform: uppercase; letter-spacing: 0.06em; }

/* ── Sección título ── */
.seccion-titulo {
    font-size: 9pt;
    font-weight: bold;
    color: #fff;
    background: #009887;
    padding: 4px 10px;
    border-radius: 4px;
    margin: 14px 0 8px;
}

/* ── Gráficas: barras horizontales ── */
.graficas-row { display: table; width: 100%; margin-bottom: 14px; border-spacing: 10px; }
.grafica-col  { display: table-cell; width: 50%; vertical-align: top; }
.grafica-titulo { font-size: 8pt; font-weight: bold; color: #333; margin-bottom: 6px; }
.bar-row      { display: table; width: 100%; margin-bottom: 4px; }
.bar-label    { display: table-cell; width: 34%; font-size: 7pt; color: #444; vertical-align: middle; white-space: nowrap; overflow: hidden; }
.bar-track    { display: table-cell; vertical-align: middle; padding-left: 6px; }
.bar-fill-wrap { background: #e5e7eb; border-radius: 3px; height: 11px; width: 100%; }
.bar-fill     { height: 11px; border-radius: 3px; background: #009887; }
.bar-pct      { display: table-cell; width: 32px; text-align: right; font-size: 7pt; font-weight: bold; color: #009887; vertical-align: middle; padding-left: 4px; }

/* ── Dona SVG ── */
.donut-wrap   { text-align: center; padding: 6px 0; }
.donut-leyenda { font-size: 7.5pt; color: #444; margin-top: 6px; }
.ley-punto    { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 3px; vertical-align: middle; }

/* ── Tabla detallada ── */
table.tabla-det {
    width: 100%;
    border-collapse: collapse;
    font-size: 7.5pt;
}
.tabla-det th {
    background: #009887;
    color: #fff;
    padding: 5px 6px;
    text-align: left;
    font-weight: bold;
    white-space: nowrap;
}
.tabla-det th.center { text-align: center; }
.tabla-det td {
    padding: 4px 6px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}
.tabla-det tr:nth-child(even) td { background: #f9fafb; }
.badge-ok    { background: #d1fae5; color: #065f46; padding: 2px 6px; border-radius: 6px; font-size: 6.5pt; white-space: nowrap; }
.badge-pend  { background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 6px; font-size: 6.5pt; white-space: nowrap; }
.badge-bloc  { background: #fee2e2; color: #7f1d1d; padding: 2px 6px; border-radius: 6px; font-size: 6.5pt; white-space: nowrap; }
.avance-bar  { background: #e5e7eb; border-radius: 2px; height: 7px; width: 60px; display: inline-block; vertical-align: middle; }
.avance-fill { height: 7px; border-radius: 2px; background: #009887; display: inline-block; }
.td-center   { text-align: center; }
.td-desc     { max-width: 200px; word-wrap: break-word; }

/* ── Pie de página ── */
.footer {
    margin-top: 18px;
    border-top: 2px solid #D3C2B4;
    padding-top: 6px;
    font-size: 7pt;
    color: #888;
    text-align: center;
}

/* ── Salto de página ── */
.page-break { page-break-after: always; }
</style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════
     ENCABEZADO INSTITUCIONAL
══════════════════════════════════════════════════════ --}}
<div class="header">
    <div class="header-logo">
        @if($logoData)
            <img src="{{ $logoData }}" alt="SESAECH"/>
        @endif
    </div>
    <div class="header-info">
        <div class="header-sistema">Sistema de Seguimiento PI-PEA — SESAECH</div>
        <div class="header-periodo">{{ $periodo->nombre }}</div>
        <div class="header-fechas">
            Período: {{ $periodo->fecha_inicio->format('d/m/Y') }}
            al {{ $periodo->fecha_fin->format('d/m/Y') }}
            &nbsp;|&nbsp; Límite de reporte: {{ $periodo->fecha_limite_reporte->format('d/m/Y') }}
        </div>
    </div>
    <div class="header-right">
        <div class="header-fecha-gen">Generado: {{ $fechaGen }}</div>
        @if($periodo->estaAbierto())
            <div><span class="badge-estado badge-abierto">Período abierto</span></div>
        @else
            <div><span class="badge-estado badge-cerrado">Período cerrado</span></div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     RESUMEN EJECUTIVO
══════════════════════════════════════════════════════ --}}
<div class="seccion-titulo">Resumen ejecutivo</div>
<div class="resumen">
    <div class="resumen-cell verde">
        <span class="resumen-num">{{ $stats['total'] }}</span>
        <span class="resumen-label">Total líneas</span>
    </div>
    <div class="resumen-cell magenta">
        <span class="resumen-num">{{ $stats['reportaron'] }}</span>
        <span class="resumen-label">Con reporte</span>
    </div>
    <div class="resumen-cell rojo">
        <span class="resumen-num">{{ $stats['sin_reporte'] }}</span>
        <span class="resumen-label">Sin reporte</span>
    </div>
    <div class="resumen-cell crema">
        <span class="resumen-num">{{ $stats['porcentaje'] }}%</span>
        <span class="resumen-label">Cumplimiento</span>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     GRÁFICAS
══════════════════════════════════════════════════════ --}}
<div class="seccion-titulo">Análisis gráfico</div>
<div class="graficas-row">

    {{-- Barras por Eje estratégico --}}
    <div class="grafica-col">
        <div class="grafica-titulo">Avance por Eje estratégico</div>
        @forelse($stats['por_eje']->take(10) as $eje => $d)
        <div class="bar-row">
            <div class="bar-label" title="{{ $eje }}">
                {{ \Illuminate\Support\Str::limit($eje, 28) }}
            </div>
            <div class="bar-track">
                <div class="bar-fill-wrap">
                    <div class="bar-fill" style="width:{{ $d['pct'] }}%; background:#009887;"></div>
                </div>
            </div>
            <div class="bar-pct">{{ $d['pct'] }}%</div>
        </div>
        @empty
        <p style="font-size:7.5pt;color:#999;">Sin datos de ejes disponibles.</p>
        @endforelse
    </div>

    {{-- Barras por Organismo --}}
    <div class="grafica-col">
        <div class="grafica-titulo">Cumplimiento por Organismo (Top 10)</div>
        @forelse($stats['por_organismo']->take(10) as $org => $d)
        <div class="bar-row">
            <div class="bar-label" title="{{ $org }}">
                {{ \Illuminate\Support\Str::limit($org, 28) }}
            </div>
            <div class="bar-track">
                <div class="bar-fill-wrap">
                    <div class="bar-fill" style="width:{{ $d['pct'] }}%; background:#C90166;"></div>
                </div>
            </div>
            <div class="bar-pct" style="color:#C90166;">{{ $d['pct'] }}%</div>
        </div>
        @empty
        <p style="font-size:7.5pt;color:#999;">Sin datos de organismos disponibles.</p>
        @endforelse
    </div>
</div>

{{-- Dona de cumplimiento general --}}
@php
    $circ  = 2 * pi() * 40;          // ≈ 251.33
    $dash  = ($stats['porcentaje'] / 100) * $circ;
    $gap   = $circ - $dash;
@endphp
<div style="display:table; width:100%; margin-bottom:14px;">
    <div style="display:table-cell; text-align:center; vertical-align:middle; width:160px;">
        <div class="grafica-titulo" style="margin-bottom:4px;">% Cumplimiento global</div>
        <div class="donut-wrap">
            <svg width="100" height="100" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="14"/>
                <circle cx="50" cy="50" r="40" fill="none" stroke="#009887" stroke-width="14"
                    stroke-dasharray="{{ $dash }} {{ $gap }}"
                    stroke-dashoffset="{{ $circ * 0.25 }}"
                    transform="rotate(-90 50 50)"/>
                <text x="50" y="46" text-anchor="middle" font-size="13" font-weight="bold" fill="#1a1a1a">{{ $stats['porcentaje'] }}%</text>
                <text x="50" y="60" text-anchor="middle" font-size="7" fill="#666">cumplimiento</text>
            </svg>
        </div>
        <div class="donut-leyenda">
            <span class="ley-punto" style="background:#009887;"></span>Con reporte ({{ $stats['reportaron'] }})
            &nbsp;&nbsp;
            <span class="ley-punto" style="background:#AE1922;"></span>Sin reporte ({{ $stats['sin_reporte'] }})
            @if($stats['bloqueados'] > 0)
            &nbsp;&nbsp;
            <span class="ley-punto" style="background:#D3C2B4;"></span>Bloqueados ({{ $stats['bloqueados'] }})
            @endif
        </div>
    </div>
    <div style="display:table-cell; vertical-align:middle; padding-left:20px;">
        <table style="font-size:8pt; border-collapse:collapse; width:100%;">
            <tr>
                <td style="padding:4px 8px; font-weight:bold; color:#009887; font-size:11pt;">{{ $stats['porcentaje'] }}%</td>
                <td style="padding:4px 8px; color:#555;">de las líneas presentaron reporte en este período</td>
            </tr>
            <tr>
                <td style="padding:4px 8px; font-weight:bold; color:#AE1922; font-size:11pt;">{{ 100 - $stats['porcentaje'] }}%</td>
                <td style="padding:4px 8px; color:#555;">de las líneas no reportaron en este período</td>
            </tr>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     TABLA DETALLADA
══════════════════════════════════════════════════════ --}}
<div class="page-break"></div>

<div class="header">
    <div class="header-logo">
        @if($logoData)
            <img src="{{ $logoData }}" alt="SESAECH"/>
        @endif
    </div>
    <div class="header-info">
        <div class="header-sistema">Sistema de Seguimiento PI-PEA — SESAECH</div>
        <div class="header-periodo">{{ $periodo->nombre }} — Detalle de líneas</div>
    </div>
    <div class="header-right">
        <div class="header-fecha-gen">Generado: {{ $fechaGen }}</div>
    </div>
</div>

<div class="seccion-titulo">Tabla detallada de líneas de acción</div>
<table class="tabla-det">
    <thead>
        <tr>
            <th style="width:20%">Prioridad / Línea</th>
            <th style="width:18%">Organismo</th>
            <th class="center" style="width:9%">Estatus</th>
            <th class="center" style="width:9%">Avance</th>
            <th style="width:12%">Fecha</th>
            <th style="width:14%">Medio de verificación</th>
            <th style="width:18%">Comentario</th>
        </tr>
    </thead>
    <tbody>
    @forelse($lineas as $l)
    <tr>
        <td class="td-desc">
            <strong>{{ $l['eje'] }}</strong><br/>
            <span style="color:#555;">{{ $l['prioridad'] }}</span>
        </td>
        <td>
            {{ $l['organismo'] }}<br/>
            <span style="color:#888; font-size:6.5pt;">{{ $l['usuario'] }}</span>
        </td>
        <td class="td-center">
            @if($l['habilitado'] === false)
                <span class="badge-bloc">Bloqueado</span>
            @elseif($l['reporto'])
                <span class="badge-ok">Con reporte</span>
            @else
                <span class="badge-pend">Sin reporte</span>
            @endif
        </td>
        <td class="td-center">
            @if($l['reporto'] && $l['porcentaje'] !== null)
                <span class="avance-bar">
                    <span class="avance-fill" style="width: {{ min($l['porcentaje'], 100) }}%;"></span>
                </span>
                <br/><span style="font-size:7pt; font-weight:bold;">{{ $l['porcentaje'] }}%</span>
            @else
                <span style="color:#ccc;">—</span>
            @endif
        </td>
        <td>{{ $l['fecha_avance'] ?? '—' }}</td>
        <td style="word-break:break-word;">
            {{ $l['medio_verificacion'] ?? '—' }}
            @if($l['documento'])
                <br/><span style="font-size:6.5pt; color:#009887;">{{ $l['documento'] }}</span>
            @endif
            @if($l['url'])
                <br/><span style="font-size:6.5pt; color:#009887;">{{ \Illuminate\Support\Str::limit($l['url'], 40) }}</span>
            @endif
        </td>
        <td style="word-break:break-word; font-size:7pt;">
            {{ \Illuminate\Support\Str::limit($l['comentario'] ?? '', 120) }}
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="7" style="text-align:center; padding:16px; color:#999;">
            No hay líneas que coincidan con los filtros seleccionados.
        </td>
    </tr>
    @endforelse
    </tbody>
</table>

{{-- ══════════════════════════════════════════════════════
     PIE DE PÁGINA
══════════════════════════════════════════════════════ --}}
<div class="footer">
    Secretaría Ejecutiva del Sistema Anticorrupción del Estado de Chiapas — Sistema de Seguimiento PI-PEA
</div>

</body>
</html>
