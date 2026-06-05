<script setup>
import OrganismoLayout from '@/Layouts/OrganismoLayout.vue'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    avances:      { type: Array,  default: () => [] },
    kpis:         { type: Object, default: () => ({}) },
    por_estatus:  { type: Array,  default: () => [] },
    por_periodo:  { type: Array,  default: () => [] },
    top_lineas:   { type: Array,  default: () => [] },
    periodos:     { type: Array,  default: () => [] },
})

// ── Filtros ──────────────────────────────────────────────────────────────────
const filtroPeriodo  = ref('todos')   // id_periodo o 'todos'
const filtroEstatus  = ref('todos')   // 'Con avances' | 'Por el momento sin avances' | 'todos'
const filtroEvidencia = ref('todos')  // 'con' | 'sin' | 'todos'
const busqueda       = ref('')

const avancesFiltrados = computed(() => {
    const q = busqueda.value.trim().toLowerCase()
    return props.avances.filter(a => {
        if (filtroPeriodo.value !== 'todos' && Number(a.id_periodo) !== Number(filtroPeriodo.value)) return false
        if (filtroEstatus.value !== 'todos' && a.estatus !== filtroEstatus.value) return false
        if (filtroEvidencia.value === 'con' && !a.tiene_evidencia) return false
        if (filtroEvidencia.value === 'sin' &&  a.tiene_evidencia) return false
        if (q) {
            const hay = (a.prioridad ?? '').toLowerCase().includes(q)
                || (a.comentario ?? '').toLowerCase().includes(q)
                || (a.medio_verificacion ?? '').toLowerCase().includes(q)
                || (a.periodo_nombre ?? '').toLowerCase().includes(q)
                || (a.documento ?? '').toLowerCase().includes(q)
            if (!hay) return false
        }
        return true
    })
})

const limpiarFiltros = () => {
    filtroPeriodo.value   = 'todos'
    filtroEstatus.value   = 'todos'
    filtroEvidencia.value = 'todos'
    busqueda.value        = ''
}

const filtrosActivos = computed(() =>
    filtroPeriodo.value !== 'todos' ||
    filtroEstatus.value !== 'todos' ||
    filtroEvidencia.value !== 'todos' ||
    busqueda.value.length > 0
)

// ── Descargar reporte (XLSX / PDF) — reusa los filtros activos vía query ─────
const queryFiltros = computed(() => {
    const p = new URLSearchParams()
    if (filtroPeriodo.value   !== 'todos') p.set('periodo',   filtroPeriodo.value)
    if (filtroEstatus.value   !== 'todos') p.set('estatus',   filtroEstatus.value)
    if (filtroEvidencia.value !== 'todos') p.set('evidencia', filtroEvidencia.value)
    if (busqueda.value.trim() !== '')      p.set('busqueda',  busqueda.value.trim())
    const s = p.toString()
    return s ? '?' + s : ''
})

const descargarXlsx = () => {
    window.location.href = route('organismo.evidencias.export.xlsx') + queryFiltros.value
}
const descargarPdf = () => {
    window.location.href = route('organismo.evidencias.export.pdf') + queryFiltros.value
}

const menuDescarga = ref(false)
const descargaRef  = ref(null)
const toggleMenuDescarga = () => { menuDescarga.value = !menuDescarga.value }
const cerrarMenuDescarga = () => { menuDescarga.value = false }

const handleClickFuera = (e) => {
    if (menuDescarga.value && descargaRef.value && !descargaRef.value.contains(e.target)) {
        menuDescarga.value = false
    }
}
onMounted(() => document.addEventListener('click', handleClickFuera))
onBeforeUnmount(() => document.removeEventListener('click', handleClickFuera))

// ── Helpers ──────────────────────────────────────────────────────────────────
const truncar = (texto, max = 100) =>
    texto?.length > max ? texto.slice(0, max) + '…' : texto

const fmtNum = (n) =>
    n !== null && n !== undefined && n !== ''
        ? Number(n).toLocaleString('es-MX', { maximumFractionDigits: 4, useGrouping: false })
        : '—'

const colorIcono = (tipo) => {
    if (!tipo) return '#6b7280'
    if (tipo.includes('pdf'))   return '#dc2626'
    if (tipo.includes('image')) return '#7c3aed'
    if (tipo.includes('word') || tipo.includes('document')) return '#2563eb'
    if (tipo.includes('sheet') || tipo.includes('excel'))   return '#16a34a'
    return '#6b7280'
}

// ── Paleta institucional ─────────────────────────────────────────────────────
const PALETA = ['#0f766e', '#7c2d12', '#b45309', '#1e40af', '#9f1239', '#365314', '#6b21a8']

// ── Chart: pastel por estatus ────────────────────────────────────────────────
const chartEstatusSeries = computed(() => props.por_estatus.map(s => s.value))
const chartEstatusOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit' },
    labels: props.por_estatus.map(s => s.name),
    colors: ['#0f766e', '#9f1239', '#6b7280'],
    legend: { position: 'bottom', fontSize: '12px' },
    dataLabels: {
        formatter: (val, opts) => {
            const v = opts.w.config.series[opts.seriesIndex]
            return `${v} (${val.toFixed(0)}%)`
        },
        style: { fontSize: '11px', fontWeight: 600 },
    },
    plotOptions: { pie: { donut: { size: '55%' } } },
    tooltip: { y: { formatter: (val) => `${val} avance${val !== 1 ? 's' : ''}` } },
    noData: { text: 'Sin datos' },
}))

// ── Chart: línea — % promedio por período ────────────────────────────────────
const chartPeriodoSeries = computed(() => [{
    name: '% Cumplimiento promedio',
    data: props.por_periodo.map(p => p.pct_promedio),
}])
const chartPeriodoOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit', zoom: { enabled: false } },
    stroke: { curve: 'smooth', width: 3 },
    colors: ['#0f766e'],
    markers: { size: 5, hover: { size: 7 } },
    xaxis: {
        categories: props.por_periodo.map(p => p.periodo),
        labels: { style: { fontSize: '11px' } },
    },
    yaxis: {
        min: 0,
        max: 100,
        labels: { formatter: (val) => `${Math.round(val)}%`, style: { fontSize: '11px' } },
    },
    grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
    tooltip: {
        y: { formatter: (val, opts) => {
            const idx = opts.dataPointIndex
            const total = props.por_periodo[idx]?.total ?? 0
            return `${val}% (${total} avance${total !== 1 ? 's' : ''})`
        }},
    },
    dataLabels: { enabled: false },
    noData: { text: 'Sin datos por período' },
}))

// ── Chart: barras — top 10 líneas por % ──────────────────────────────────────
const chartLineasSeries = computed(() => [{
    name: '% Cumplimiento',
    data: props.top_lineas.map(l => l.pct),
}])
const chartLineasOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit' },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '70%', distributed: true } },
    colors: PALETA,
    legend: { show: false },
    xaxis: {
        max: 100,
        labels: { formatter: (val) => `${Math.round(val)}%`, style: { fontSize: '11px' } },
    },
    yaxis: {
        labels: {
            style: { fontSize: '11px' },
            formatter: (val) => `P${val}`,
        },
    },
    dataLabels: {
        enabled: true,
        style: { fontSize: '11px', fontWeight: 600, colors: ['#fff'] },
        formatter: (val) => `${val}%`,
    },
    tooltip: {
        custom: ({ dataPointIndex, w }) => {
            const linea = props.top_lineas[dataPointIndex]
            return `<div style="padding:8px 12px;font-size:12px">
                <strong>P${linea.numero_prioridad}</strong> · ${linea.pct}%<br/>
                <span style="color:#6b7280">${linea.prioridad ?? ''}</span>
            </div>`
        },
    },
    grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
    noData: { text: 'Sin datos de cumplimiento' },
}))

// Reemplazar categorías del eje Y con numero_prioridad
const chartLineasCategorias = computed(() => props.top_lineas.map(l => l.numero_prioridad))
</script>

<template>
    <OrganismoLayout>
        <div class="page">

            <div class="page-head">
                <div>
                    <p class="page-breadcrumb">Seguimiento</p>
                    <h1 class="page-title">Mis avances</h1>
                    <p class="page-sub">Resumen, indicadores y registro completo de tus avances reportados.</p>
                </div>
                <div class="page-actions" ref="descargaRef">
                    <button class="btn-descargar" :disabled="!avances.length" @click="toggleMenuDescarga">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Descargar reporte
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:11px;height:11px">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <Transition name="dropdown">
                        <div v-if="menuDescarga" class="dropdown-menu">
                            <p class="dropdown-aviso" v-if="filtrosActivos">Incluirá solo los avances filtrados ({{ avancesFiltrados.length }})</p>
                            <p class="dropdown-aviso" v-else>Incluirá los {{ avances.length }} avances</p>
                            <button class="dropdown-item" @click="descargarXlsx(); cerrarMenuDescarga()">
                                <span class="dropdown-icon dropdown-icon--xlsx">XLS</span>
                                <span>
                                    <strong>Excel</strong>
                                    <small>Tabla completa para análisis</small>
                                </span>
                            </button>
                            <button class="dropdown-item" @click="descargarPdf(); cerrarMenuDescarga()">
                                <span class="dropdown-icon dropdown-icon--pdf">PDF</span>
                                <span>
                                    <strong>PDF institucional</strong>
                                    <small>Listo para imprimir o compartir</small>
                                </span>
                            </button>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- ── KPIs ──────────────────────────────────────────────────── -->
            <div class="kpi-grid">
                <div class="kpi">
                    <span class="kpi-label">Total avances</span>
                    <span class="kpi-val">{{ kpis.total_avances ?? 0 }}</span>
                    <span class="kpi-foot">{{ kpis.con_evidencia ?? 0 }} con evidencia · {{ kpis.sin_evidencia ?? 0 }} sin</span>
                </div>
                <div class="kpi kpi--verde">
                    <span class="kpi-label">Líneas con avance</span>
                    <span class="kpi-val">{{ kpis.lineas_con_avance ?? 0 }}</span>
                    <span class="kpi-foot">de tus líneas asignadas</span>
                </div>
                <div class="kpi kpi--magenta">
                    <span class="kpi-label">% Promedio</span>
                    <span class="kpi-val">{{ kpis.pct_promedio ?? 0 }}%</span>
                    <span class="kpi-foot">cumplimiento agregado</span>
                </div>
                <div class="kpi kpi--gris">
                    <span class="kpi-label">Períodos</span>
                    <span class="kpi-val">{{ periodos.length }}</span>
                    <span class="kpi-foot">con avances registrados</span>
                </div>
            </div>

            <!-- ── Gráficas ──────────────────────────────────────────────── -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-hdr">
                        <h3 class="chart-title">Distribución por estatus</h3>
                        <p class="chart-sub">Con avances vs. sin avances</p>
                    </div>
                    <VueApexCharts
                        v-if="por_estatus.length"
                        type="donut"
                        height="260"
                        :options="chartEstatusOptions"
                        :series="chartEstatusSeries"
                    />
                    <div v-else class="chart-empty">Sin datos</div>
                </div>

                <div class="chart-card chart-card--wide">
                    <div class="chart-hdr">
                        <h3 class="chart-title">Cumplimiento por período</h3>
                        <p class="chart-sub">% promedio agregado a través del tiempo</p>
                    </div>
                    <VueApexCharts
                        v-if="por_periodo.length"
                        type="line"
                        height="260"
                        :options="chartPeriodoOptions"
                        :series="chartPeriodoSeries"
                    />
                    <div v-else class="chart-empty">Sin datos por período</div>
                </div>

                <div class="chart-card chart-card--full">
                    <div class="chart-hdr">
                        <h3 class="chart-title">Top 10 líneas por cumplimiento</h3>
                        <p class="chart-sub">Avance más reciente de cada línea (mayor a menor)</p>
                    </div>
                    <VueApexCharts
                        v-if="top_lineas.length"
                        type="bar"
                        height="320"
                        :options="{ ...chartLineasOptions, xaxis: { ...chartLineasOptions.xaxis, categories: chartLineasCategorias } }"
                        :series="chartLineasSeries"
                    />
                    <div v-else class="chart-empty">Sin datos de cumplimiento</div>
                </div>
            </div>

            <!-- ── Filtros ───────────────────────────────────────────────── -->
            <div class="filtros-card">
                <div class="filtros-head">
                    <h3 class="filtros-titulo">Avances registrados</h3>
                    <span class="filtros-count">{{ avancesFiltrados.length }} de {{ avances.length }}</span>
                </div>
                <div class="filtros-row">
                    <div class="search-bar">
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" style="width:15px;height:15px;color:var(--color-gris-400)">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input v-model="busqueda" type="text" placeholder="Buscar prioridad, comentario, período, documento..." class="search-input"/>
                    </div>
                    <select v-model="filtroPeriodo" class="filtro-select">
                        <option value="todos">Todos los períodos</option>
                        <option v-for="p in periodos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                    </select>
                    <select v-model="filtroEstatus" class="filtro-select">
                        <option value="todos">Todos los estatus</option>
                        <option value="Con avances">Con avances</option>
                        <option value="Por el momento sin avances">Sin avances</option>
                    </select>
                    <select v-model="filtroEvidencia" class="filtro-select">
                        <option value="todos">Todos</option>
                        <option value="con">Con evidencia</option>
                        <option value="sin">Sin evidencia</option>
                    </select>
                    <button v-if="filtrosActivos" class="filtro-clear" @click="limpiarFiltros" title="Limpiar filtros">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- ── Tabla / Lista ─────────────────────────────────────────── -->
            <div v-if="avancesFiltrados.length === 0" class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--color-gris-300)">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p v-if="avances.length === 0">Aún no has registrado avances.</p>
                <p v-else>Ningún avance coincide con los filtros aplicados.</p>
            </div>

            <div v-else class="avance-lista">
                <div v-for="a in avancesFiltrados" :key="a.id" class="avance-card">
                    <div class="avance-top">
                        <div class="avance-top-left">
                            <span class="badge-pri">P{{ a.numero_prioridad }}</span>
                            <span :class="['badge-estatus', a.estatus === 'Con avances' ? 'badge-estatus--ok' : 'badge-estatus--pend']">
                                {{ a.estatus }}
                            </span>
                            <span v-if="a.periodo_nombre" class="badge-periodo">{{ a.periodo_nombre }}</span>
                        </div>
                        <div class="avance-top-right">
                            <span class="avance-pct">{{ Math.round((a.avance_cuantitativo ?? 0) * 100) }}%</span>
                            <span class="avance-fecha">{{ a.fecha_avance }}</span>
                        </div>
                    </div>

                    <p class="avance-prioridad">{{ truncar(a.prioridad, 140) }}</p>

                    <div class="avance-grid">
                        <div>
                            <span class="avance-label">Valor alcanzado</span>
                            <span class="avance-val">{{ fmtNum(a.avance_cualitativo) }}</span>
                        </div>
                        <div v-if="a.medio_verificacion">
                            <span class="avance-label">Medio de verificación</span>
                            <span class="avance-val">{{ truncar(a.medio_verificacion, 80) }}</span>
                        </div>
                        <div>
                            <span class="avance-label">Registrado</span>
                            <span class="avance-val avance-val--muted">{{ a.fecha_registro }}</span>
                        </div>
                    </div>

                    <p v-if="a.comentario" class="avance-comentario">{{ a.comentario }}</p>

                    <div v-if="a.archivo_url || a.url" class="avance-evidencias">
                        <a v-if="a.archivo_url" :href="a.archivo_url" target="_blank" class="ev-archivo">
                            <div class="ev-archivo-icon" :style="{ background: colorIcono(a.archivo_tipo) + '18', color: colorIcono(a.archivo_tipo) }">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ev-archivo-info">
                                <span class="ev-archivo-nombre">{{ a.documento ?? 'Archivo adjunto' }}</span>
                                <span v-if="a.archivo_tamanio_formateado" class="ev-archivo-tamanio">{{ a.archivo_tamanio_formateado }}</span>
                            </div>
                        </a>
                        <a v-if="a.url" :href="a.url" target="_blank" class="ev-url">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;flex-shrink:0">
                                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                            </svg>
                            Ver enlace externo
                        </a>
                    </div>
                    <div v-else class="avance-sin-ev">Sin evidencia adjunta</div>
                </div>
            </div>

        </div>
    </OrganismoLayout>
</template>

<style scoped>
.page { max-width:1180px; }
.page-head { margin-bottom:1.25rem;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap; }
.page-breadcrumb { font-size:var(--text-xs);color:var(--color-gris-400);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem; }
.page-title { font-family:var(--font-display);font-size:var(--text-2xl);color:var(--color-gris-800); }
.page-sub { font-size:var(--text-sm);color:var(--color-gris-500);margin-top:.25rem; }

/* Botón descargar reporte + dropdown */
.page-actions { position:relative; }
.btn-descargar { display:inline-flex;align-items:center;gap:8px;padding:.55rem 1rem;background:var(--color-verde);color:#fff;border:none;border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:600;cursor:pointer;transition:opacity var(--transition-base);box-shadow:var(--shadow-sm); }
.btn-descargar:hover:not(:disabled) { opacity:.9; }
.btn-descargar:disabled { background:var(--color-gris-300);cursor:not-allowed; }
.dropdown-menu { position:absolute;top:calc(100% + 6px);right:0;background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);box-shadow:0 10px 30px rgba(0,0,0,.12);padding:.5rem;min-width:280px;z-index:50; }
.dropdown-aviso { font-size:11px;color:var(--color-gris-500);padding:.4rem .6rem;border-bottom:1px solid var(--color-gris-100);margin-bottom:.3rem; }
.dropdown-item { width:100%;display:flex;align-items:center;gap:.75rem;padding:.6rem .65rem;background:transparent;border:none;border-radius:var(--radius-md);cursor:pointer;text-align:left;transition:background var(--transition-base); }
.dropdown-item:hover { background:var(--color-gris-100); }
.dropdown-item span:last-child { display:flex;flex-direction:column;gap:1px; }
.dropdown-item strong { font-size:var(--text-sm);color:var(--color-gris-800);font-weight:600; }
.dropdown-item small  { font-size:11px;color:var(--color-gris-500); }
.dropdown-icon { width:36px;height:36px;border-radius:var(--radius-md);display:inline-flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;letter-spacing:.05em;color:#fff;flex-shrink:0; }
.dropdown-icon--xlsx { background:#16a34a; }
.dropdown-icon--pdf  { background:#dc2626; }
.dropdown-enter-active,.dropdown-leave-active { transition:opacity .15s,transform .15s; }
.dropdown-enter-from,.dropdown-leave-to { opacity:0;transform:translateY(-4px); }

/* KPIs */
.kpi-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:.875rem;margin-bottom:1.25rem; }
.kpi { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-left:3px solid var(--color-vino);border-radius:var(--radius-lg);padding:1rem 1.1rem;box-shadow:var(--shadow-sm);display:flex;flex-direction:column;gap:.25rem; }
.kpi--verde   { border-left-color:var(--color-verde); }
.kpi--magenta { border-left-color:var(--color-magenta); }
.kpi--gris    { border-left-color:var(--color-gris-400); }
.kpi-label { font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-500); }
.kpi-val { font-size:1.75rem;font-weight:700;font-family:var(--font-display);color:var(--color-gris-800);line-height:1; }
.kpi-foot { font-size:11px;color:var(--color-gris-400); }

/* Charts */
.charts-grid { display:grid;grid-template-columns:1fr 1.4fr;gap:1rem;margin-bottom:1.25rem; }
.chart-card { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);padding:1.1rem;box-shadow:var(--shadow-sm); }
.chart-card--wide { /* hereda */ }
.chart-card--full { grid-column:1 / -1; }
.chart-hdr { margin-bottom:.5rem; }
.chart-title { font-size:var(--text-sm);font-weight:700;color:var(--color-gris-800); }
.chart-sub { font-size:11px;color:var(--color-gris-400);margin-top:2px; }
.chart-empty { padding:3rem 1rem;text-align:center;color:var(--color-gris-400);font-size:var(--text-sm); }

/* Filtros */
.filtros-card { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);padding:1rem 1.1rem;box-shadow:var(--shadow-sm);margin-bottom:.875rem; }
.filtros-head { display:flex;justify-content:space-between;align-items:baseline;margin-bottom:.625rem; }
.filtros-titulo { font-size:var(--text-sm);font-weight:700;color:var(--color-gris-800); }
.filtros-count { font-size:11px;font-weight:600;color:var(--color-gris-500);background:var(--color-gris-100);padding:2px 9px;border-radius:var(--radius-full); }
.filtros-row { display:flex;gap:.625rem;flex-wrap:wrap;align-items:center; }
.search-bar { flex:1;min-width:220px;display:flex;align-items:center;gap:.625rem;border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.45rem .75rem;background:var(--color-gris-50,#fafafa); }
.search-input { flex:1;border:none;outline:none;font-size:var(--text-sm);color:var(--color-gris-700);background:transparent;font-family:var(--font-body); }
.search-input::placeholder { color:var(--color-gris-400); }
.filtro-select { border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.45rem .65rem;background:var(--color-gris-50,#fafafa);font-size:var(--text-sm);color:var(--color-gris-700);font-family:var(--font-body);cursor:pointer;outline:none; }
.filtro-select:focus { border-color:var(--color-verde); }
.filtro-clear { display:inline-flex;align-items:center;gap:5px;padding:.45rem .8rem;background:var(--color-vino-lt);color:var(--color-vino);border:1px solid var(--color-vino);border-radius:var(--radius-md);font-size:12px;font-weight:600;cursor:pointer; }
.filtro-clear:hover { background:var(--color-vino);color:#fff; }

/* Lista de avances */
.avance-lista { display:flex;flex-direction:column;gap:.75rem; }
.avance-card { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);padding:1rem 1.15rem;box-shadow:var(--shadow-sm); }
.avance-top { display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:.625rem; }
.avance-top-left { display:flex;align-items:center;gap:6px;flex-wrap:wrap; }
.avance-top-right { display:flex;align-items:center;gap:.75rem; }
.badge-pri { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-sm);font-size:10px;font-weight:700;background:var(--color-magenta-lt);color:var(--color-magenta);white-space:nowrap; }
.badge-estatus { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:700; }
.badge-estatus--ok   { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-estatus--pend { background:#fef3c7;color:#92400e; }
.badge-periodo { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-sm);font-size:11px;font-weight:600;background:var(--color-gris-100);color:var(--color-gris-600); }
.avance-pct { font-size:1rem;font-weight:700;font-family:var(--font-display);color:var(--color-verde); }
.avance-fecha { font-size:11px;color:var(--color-gris-400); }
.avance-prioridad { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-800);line-height:1.45;margin-bottom:.625rem; }
.avance-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:.625rem;margin-bottom:.625rem; }
.avance-grid > div { display:flex;flex-direction:column;gap:2px; }
.avance-label { font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400); }
.avance-val   { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-800); }
.avance-val--muted { color:var(--color-gris-500);font-weight:500;font-size:12px; }
.avance-comentario { font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.5;background:var(--color-gris-50,#fafafa);padding:.5rem .75rem;border-radius:var(--radius-md);border-left:3px solid var(--color-gris-300);margin-bottom:.625rem; }
.avance-evidencias { display:flex;gap:.625rem;flex-wrap:wrap;align-items:center; }
.avance-sin-ev { font-size:11px;color:var(--color-gris-400);font-style:italic;background:var(--color-gris-50,#fafafa);padding:.4rem .7rem;border-radius:var(--radius-md);display:inline-block; }

.ev-archivo { display:flex;align-items:center;gap:10px;padding:.5rem .75rem;background:var(--color-gris-50,#fafafa);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);text-decoration:none;transition:border-color var(--transition-base); }
.ev-archivo:hover { border-color:var(--color-gris-400); }
.ev-archivo-icon { width:28px;height:28px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.ev-archivo-info { display:flex;flex-direction:column;gap:1px;min-width:0;max-width:240px; }
.ev-archivo-nombre { font-size:var(--text-xs);font-weight:600;color:var(--color-gris-800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.ev-archivo-tamanio { font-size:10px;color:var(--color-gris-400); }
.ev-url { display:inline-flex;align-items:center;gap:5px;font-size:var(--text-xs);font-weight:600;color:#1a73e8;text-decoration:none;padding:.4rem .7rem;background:#e8f0fe;border-radius:var(--radius-md); }
.ev-url:hover { text-decoration:underline; }

.empty-state { display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:3rem;text-align:center;color:var(--color-gris-500);font-size:var(--text-sm);background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg); }

@media (max-width: 960px) {
    .kpi-grid    { grid-template-columns:repeat(2,1fr); }
    .charts-grid { grid-template-columns:1fr; }
    .avance-grid { grid-template-columns:1fr; }
}
</style>
