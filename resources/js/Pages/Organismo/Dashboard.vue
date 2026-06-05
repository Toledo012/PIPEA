<script setup>

import OrganismoLayout from '@/Layouts/OrganismoLayout.vue'
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    lineas:         { type: Array,  default: () => [] },
    historial:      { type: Array,  default: () => [] },
    metricas:       { type: Object, default: () => ({}) },
    periodo_activo: { type: Object, default: () => null },
    por_eje:        { type: Array,  default: () => [] },
    tendencia:      { type: Array,  default: () => [] },
})

const truncar = (texto, max = 70) =>
    texto?.length > max ? texto.substring(0, max) + '...' : texto

const colorAvance = (pct) => {
    if (pct >= 75) return 'avance-fill--verde'
    if (pct >= 40) return 'avance-fill--amarillo'
    return 'avance-fill--rojo'
}

// ─── Período banner ────────────────────────────────────────────────────────
const tonoPeriodo = computed(() => {
    if (!props.periodo_activo) return 'gris'
    const d = props.periodo_activo.dias_restantes
    if (d === null || d < 0)   return 'cerrado'
    if (d <= 3)                return 'urgente'
    if (d <= 7)                return 'pronto'
    return 'abierto'
})

const tituloPeriodo = computed(() => {
    if (!props.periodo_activo) return null
    const d = props.periodo_activo.dias_restantes
    if (d === null) return 'Período activo'
    if (d < 0)      return `Período cerrado hace ${Math.abs(d)} día(s)`
    if (d === 0)    return 'Hoy vence el plazo'
    if (d === 1)    return 'Vence mañana'
    return `Faltan ${d} día(s) para el cierre`
})

// ─── Charts: por eje ───────────────────────────────────────────────────────
const porEjeOptions = computed(() => ({
    chart:   { type: 'donut', toolbar: { show: false }, fontFamily: 'inherit' },
    labels:  props.por_eje.map(e => e.name),
    colors:  ['#009887', '#C90166', '#7C2D12', '#1a73e8', '#d97706', '#7c3aed', '#0891b2', '#16a34a'],
    legend:  { position: 'bottom', fontSize: '12px' },
    dataLabels: { enabled: true, formatter: (v) => Math.round(v) + '%' },
    plotOptions: {
        pie: {
            donut: {
                size: '60%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Promedio',
                        formatter: () => {
                            const vs = props.por_eje.map(e => e.value).filter(v => v != null)
                            return vs.length ? Math.round(vs.reduce((a,b) => a+b, 0) / vs.length) + '%' : '0%'
                        },
                    },
                },
            },
        },
    },
    tooltip: { y: { formatter: (v) => v + '%' } },
}))

const porEjeSeries = computed(() => props.por_eje.map(e => e.value))

// ─── Charts: tendencia ─────────────────────────────────────────────────────
const tendenciaOptions = computed(() => ({
    chart:   { type: 'line', toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false } },
    stroke:  { curve: 'smooth', width: 3 },
    colors:  ['#009887'],
    xaxis:   {
        categories: props.tendencia.map(t => t.periodo),
        labels: { style: { fontSize: '11px' } },
    },
    yaxis:   { labels: { formatter: (v) => v + '%' } },
    markers: { size: 5, hover: { size: 7 } },
    dataLabels: { enabled: false },
    grid:    { borderColor: '#e5e7eb', strokeDashArray: 4 },
    tooltip: { y: { formatter: (v) => v + '%' } },
}))

const tendenciaSeries = computed(() => [{
    name: '% cumplimiento',
    data: props.tendencia.map(t => t.pct_promedio),
}])

// ─── Helpers para CTA por línea ────────────────────────────────────────────
const rutaRegistrar = (l) => `${route('organismo.lineas.index')}?registrar=${l.id}`
</script>

<template>
    <OrganismoLayout>
    <div class="dashboard">

        <!-- Header -->
        <div class="dash-header">
            <div>
                <p class="dash-breadcrumb">Organismo implementador</p>
                <h1 class="dash-title">Mis líneas de acción</h1>
            </div>
        </div>

        <!-- Banner período activo -->
        <div v-if="periodo_activo" class="periodo-banner" :class="`periodo-banner--${tonoPeriodo}`">
            <div class="periodo-banner__left">
                <span class="periodo-banner__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:22px;height:22px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <div>
                    <p class="periodo-banner__sub">Período de reporte · {{ periodo_activo.nombre }}</p>
                    <p class="periodo-banner__title">{{ tituloPeriodo }}</p>
                </div>
            </div>
            <div class="periodo-banner__right">
                <div class="periodo-banner__stat">
                    <span class="periodo-banner__stat-val">{{ metricas.porReportar ?? 0 }}</span>
                    <span class="periodo-banner__stat-lbl">por reportar</span>
                </div>
                <div class="periodo-banner__stat">
                    <span class="periodo-banner__stat-val">{{ periodo_activo.fecha_limite_reporte }}</span>
                    <span class="periodo-banner__stat-lbl">fecha límite</span>
                </div>
            </div>
        </div>

        <!-- Métricas -->
        <div class="metrics-grid">
            <div class="metric-card">
                <span class="metric-label">Total de líneas</span>
                <span class="metric-val">{{ metricas.total }}</span>
            </div>
            <div class="metric-card metric-card--verde">
                <span class="metric-label">Con avance</span>
                <span class="metric-val">{{ metricas.conAvance }}</span>
            </div>
            <div class="metric-card metric-card--alerta">
                <span class="metric-label">Sin avance</span>
                <span class="metric-val">{{ metricas.sinAvance }}</span>
            </div>
            <div class="metric-card metric-card--azul">
                <span class="metric-label">Avance promedio</span>
                <span class="metric-val">{{ metricas.promedio }}%</span>
            </div>
            <div v-if="periodo_activo" class="metric-card metric-card--magenta">
                <span class="metric-label">Por reportar</span>
                <span class="metric-val">{{ metricas.porReportar ?? 0 }}</span>
            </div>
        </div>

        <!-- Charts grid -->
        <div v-if="por_eje.length || tendencia.length" class="charts-grid">
            <div v-if="por_eje.length" class="panel chart-panel">
                <p class="panel-title">Promedio por eje</p>
                <VueApexCharts
                    type="donut"
                    height="280"
                    :options="porEjeOptions"
                    :series="porEjeSeries"
                />
            </div>
            <div v-if="tendencia.length" class="panel chart-panel">
                <p class="panel-title">Tendencia por período</p>
                <VueApexCharts
                    type="line"
                    height="280"
                    :options="tendenciaOptions"
                    :series="tendenciaSeries"
                />
            </div>
        </div>

        <div class="main-grid">

            <!-- Listado de líneas -->
            <div class="panel panel--lineas">
                <p class="panel-title">Mis líneas</p>

                <div v-if="lineas.length === 0" class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--color-gris-300)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p>No tienes líneas de acción asignadas.</p>
                </div>

                <div v-for="l in lineas" :key="l.id" class="linea-card"
                     :class="{ 'linea-card--alerta': l.habilitada_periodo && !l.ya_reporto_periodo }">
                    <div class="linea-badges">
                        <span class="badge-eje">Eje {{ String(l.numero_eje).padStart(2,'0') }}</span>
                        <span class="badge-pri">Prior. {{ l.numero_prioridad }}</span>
                        <span v-if="l.frecuencia" class="badge-frec">{{ l.frecuencia }}</span>
                        <span v-if="l.habilitada_periodo && !l.ya_reporto_periodo" class="badge-alerta">
                            Por reportar
                        </span>
                        <span v-else-if="l.ya_reporto_periodo" class="badge-ok">
                            Ya reportado
                        </span>
                    </div>

                    <p class="linea-desc" :title="l.prioridad">{{ truncar(l.prioridad) }}</p>

                    <div v-if="l.nombre_indicador" class="linea-indicador">
                        <span class="linea-indicador-label">Indicador:</span>
                        {{ l.nombre_indicador }}
                    </div>

                    <!-- Barra de avance -->
                    <div class="avance-section">
                        <div class="avance-info">
                            <span class="avance-label">Avance</span>
                            <span class="avance-pct" :class="l.porcentaje_avance >= 75 ? 'avance-pct--verde' : l.porcentaje_avance >= 40 ? 'avance-pct--amarillo' : 'avance-pct--rojo'">
                                {{ l.porcentaje_avance }}%
                            </span>
                        </div>
                        <div class="avance-track">
                            <div
                                class="avance-fill"
                                :class="colorAvance(l.porcentaje_avance)"
                                :style="{ width: Math.min(l.porcentaje_avance, 100) + '%' }"
                            ></div>
                        </div>
                        <div class="avance-meta">
                            <span>{{ l.ultimo_valor_avance ?? 0 }} / {{ l.meta ?? '—' }} {{ l.unidad_medida }}</span>
                        </div>
                    </div>

                    <!-- CTA: Registrar avance si está habilitada y no ha reportado; si no, ver detalle -->
                    <div class="linea-actions">
                        <Link
                            v-if="l.habilitada_periodo && !l.ya_reporto_periodo"
                            :href="rutaRegistrar(l)"
                            class="btn-linea btn-linea--primary"
                        >
                            Registrar avance
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </Link>
                        <Link
                            :href="route('organismo.lineas.show', l.id)"
                            class="btn-linea"
                        >
                            Ver detalle
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Historial reciente -->
            <div class="panel panel--historial">
                <p class="panel-title">Últimos avances registrados</p>

                <div v-if="historial.length === 0" class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--color-gris-300)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>Aún no hay avances registrados.</p>
                </div>

                <div v-for="h in historial" :key="h.id" class="hist-item">
                    <div class="hist-header">
                        <span class="hist-prior">Prioridad {{ h.numero_prioridad }}</span>
                        <span class="hist-fecha">{{ h.fecha_registro }}</span>
                    </div>
                    <p v-if="h.avance_cualitativo" class="hist-desc">{{ truncar(h.avance_cualitativo, 100) }}</p>
                    <div class="hist-cuant">
                        <span class="hist-cuant-label">Avance registrado:</span>
                        <span class="hist-cuant-val">{{ h.avance_cuantitativo }}</span>
                    </div>
                </div>
            </div>

        </div>

    </div>

        </OrganismoLayout>
</template>

<style scoped>
.dashboard { max-width: 1200px; padding: 0; }
.dash-header { display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.5rem; }
.dash-breadcrumb { font-size:var(--text-xs);color:var(--color-gris-400);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem; }
.dash-title { font-family:var(--font-display);font-size:var(--text-2xl);color:var(--color-gris-800); }

/* Banner período */
.periodo-banner {
    display:flex; justify-content:space-between; align-items:center; gap:1rem;
    border-radius:var(--radius-lg); padding:1rem 1.25rem; margin-bottom:1.25rem;
    border:1px solid; box-shadow:var(--shadow-sm);
}
.periodo-banner__left { display:flex; align-items:center; gap:.75rem; }
.periodo-banner__icon {
    width:40px; height:40px; display:flex; align-items:center; justify-content:center;
    border-radius:var(--radius-full); background:rgba(255,255,255,.65);
}
.periodo-banner__sub   { font-size:11px; font-weight:600; letter-spacing:.06em; text-transform:uppercase; opacity:.8; }
.periodo-banner__title { font-size:16px; font-weight:700; font-family:var(--font-display); margin-top:2px; }
.periodo-banner__right { display:flex; gap:1.5rem; }
.periodo-banner__stat  { display:flex; flex-direction:column; align-items:flex-end; }
.periodo-banner__stat-val { font-size:20px; font-weight:700; font-family:var(--font-display); }
.periodo-banner__stat-lbl { font-size:10px; font-weight:600; letter-spacing:.05em; text-transform:uppercase; opacity:.75; }

.periodo-banner--abierto  { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
.periodo-banner--pronto   { background:#fffbeb; border-color:#fde68a; color:#92400e; }
.periodo-banner--urgente  { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
.periodo-banner--cerrado  { background:#f3f4f6; border-color:#d1d5db; color:#374151; }
.periodo-banner--gris     { background:#f9fafb; border-color:#e5e7eb; color:#4b5563; }

/* Métricas */
.metrics-grid { display:grid;grid-template-columns:repeat(auto-fit, minmax(170px, 1fr));gap:12px;margin-bottom:1.5rem; }
.metric-card { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);padding:1rem 1.25rem;display:flex;flex-direction:column;gap:6px;box-shadow:var(--shadow-sm); }
.metric-card--verde   { border-left:3px solid var(--color-verde); }
.metric-card--alerta  { border-left:3px solid var(--color-vino); }
.metric-card--azul    { border-left:3px solid #1a73e8; }
.metric-card--magenta { border-left:3px solid var(--color-magenta); }
.metric-label { font-size:12px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400); }
.metric-val   { font-size:28px;font-weight:700;font-family:var(--font-display);color:var(--color-gris-800); }

/* Charts */
.charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1.5rem; }
.chart-panel { min-height:320px; }
@media (max-width: 900px) {
    .charts-grid { grid-template-columns:1fr; }
}

/* Grid principal */
.main-grid { display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start; }

/* Panels */
.panel { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);padding:1.25rem;box-shadow:var(--shadow-sm); }
.panel-title { font-size:var(--text-xs);font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--color-gris-500);margin-bottom:1rem; }

/* Línea card */
.linea-card { border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:1rem;margin-bottom:.75rem;transition:border-color var(--transition-base), box-shadow var(--transition-base); }
.linea-card:hover { border-color:var(--color-gris-400); }
.linea-card:last-child { margin-bottom:0; }
.linea-card--alerta {
    border-color:#fcd34d; background:linear-gradient(180deg, #fffbeb 0%, #fff 60%);
    box-shadow:0 0 0 3px rgba(252, 211, 77, .12);
}
.linea-badges { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:.5rem; }
.badge-eje  { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-sm);font-size:11px;font-weight:700;background:var(--color-vino-lt);color:var(--color-vino); }
.badge-pri  { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-sm);font-size:11px;font-weight:700;background:var(--color-magenta-lt);color:var(--color-magenta); }
.badge-frec { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-sm);font-size:11px;font-weight:600;background:var(--color-gris-100);color:var(--color-gris-600); }
.badge-alerta { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-sm);font-size:11px;font-weight:700;background:#fef3c7;color:#92400e; }
.badge-ok     { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-sm);font-size:11px;font-weight:700;background:#d1fae5;color:#065f46; }
.linea-desc { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-800);line-height:1.5;margin-bottom:.5rem; }
.linea-indicador { font-size:12px;color:var(--color-gris-500);margin-bottom:.75rem;line-height:1.4; }
.linea-indicador-label { font-weight:700;color:var(--color-gris-600); }

/* Avance */
.avance-section { margin-bottom:.75rem; }
.avance-info { display:flex;justify-content:space-between;align-items:baseline;margin-bottom:4px; }
.avance-label { font-size:11px;font-weight:600;color:var(--color-gris-400);text-transform:uppercase;letter-spacing:.05em; }
.avance-pct { font-size:var(--text-sm);font-weight:700;font-family:var(--font-display); }
.avance-pct--verde    { color:var(--color-verde); }
.avance-pct--amarillo { color:#d97706; }
.avance-pct--rojo     { color:var(--color-vino); }
.avance-track { width:100%;height:6px;background:var(--color-gris-200);border-radius:var(--radius-full);overflow:hidden; }
.avance-fill { height:100%;border-radius:var(--radius-full);transition:width .4s ease; }
.avance-fill--verde    { background:var(--color-verde); }
.avance-fill--amarillo { background:#d97706; }
.avance-fill--rojo     { background:var(--color-vino); }
.avance-meta { font-size:11px;color:var(--color-gris-400);margin-top:3px; }

/* Botón linea */
.linea-actions { display:flex; gap:1rem; align-items:center; flex-wrap:wrap; }
.btn-linea {
    display:inline-flex; align-items:center; gap:6px; font-size:var(--text-sm); font-weight:600;
    color:#1a73e8; text-decoration:none; transition:color var(--transition-base);
}
.btn-linea:hover { color:#0d5bba; }
.btn-linea--primary {
    padding:.4rem .75rem; border-radius:var(--radius-sm); color:#fff; background:var(--color-verde);
}
.btn-linea--primary:hover { color:#fff; background:#007a6c; }

/* Historial */
.hist-item { padding:.875rem 0;border-bottom:1px solid var(--color-gris-200); }
.hist-item:last-child { border-bottom:none;padding-bottom:0; }
.hist-header { display:flex;justify-content:space-between;align-items:center;margin-bottom:4px; }
.hist-prior { font-size:12px;font-weight:700;color:var(--color-gris-700); }
.hist-fecha { font-size:11px;color:var(--color-gris-400); }
.hist-desc  { font-size:var(--text-sm);color:var(--color-gris-600);line-height:1.5;margin-bottom:4px; }
.hist-cuant { display:flex;gap:6px;align-items:center;font-size:12px; }
.hist-cuant-label { color:var(--color-gris-400); }
.hist-cuant-val   { font-weight:700;color:var(--color-gris-700); }

/* Empty */
.empty-state { display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:2rem;text-align:center;color:var(--color-gris-500);font-size:var(--text-sm); }
</style>
