<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
    avancePorOrganismo: { type: Array, default: () => [] },
    avancePorEje:       { type: Array, default: () => [] },
    distribucionPct:    { type: Array, default: () => [] },
    distribucionEstatus:{ type: Array, default: () => [] },
    resumen:            { type: Object, default: () => ({}) },
})

// ── Filtro global de eje ─────────────────────────────────────────────────
const filtroEje = ref('')

const organismosFiltrados = computed(() => {
    if (!filtroEje.value) return props.avancePorOrganismo
    return props.avancePorOrganismo.filter(o => String(o.id_eje) === filtroEje.value)
})

// ── Tipo de gráfica intercambiable ────────────────────────────────────────
const tipoOrganismo = ref('bar')   // 'bar' | 'line' | 'area'
const tipoEje       = ref('donut') // 'donut' | 'pie' | 'bar'

// Colores de marca
const ROSA     = '#b10f57'
const JADE     = '#009887'
const CINABRIO = '#ae192d'
const BEIGE    = '#d3c2b4'
const AZUL     = '#143d6d'

const PALETA = [ROSA, JADE, CINABRIO, '#c0156a', '#3e6f78', '#7a5d95', '#e07b39', '#2e6d73']

// ── Opciones base compartidas ─────────────────────────────────────────────
const baseOpts = {
    chart: { fontFamily: 'Arial, sans-serif', toolbar: { show: false }, animations: { enabled: true, easing: 'easeinout', speed: 900, animateGradually: { enabled: true, delay: 80 } } },
    tooltip: { style: { fontSize: '13px' } },
    grid: { borderColor: '#f0ece9', strokeDashArray: 4 },
}

// ── Gráfica 1: Avance por organismo ──────────────────────────────────────
const chartOrgSeries = computed(() => {
    const data = organismosFiltrados.value.slice(0, 20)
    if (tipoOrganismo.value === 'bar') {
        return [{ name: 'Avance promedio (%)', data: data.map(o => +(o.promedio ?? 0).toFixed(1)) }]
    }
    return [{ name: 'Avance promedio (%)', data: data.map(o => +(o.promedio ?? 0).toFixed(1)) }]
})

const chartOrgOptions = computed(() => ({
    ...baseOpts,
    chart: { ...baseOpts.chart, type: tipoOrganismo.value, id: 'org-chart' },
    colors: [ROSA],
    fill: tipoOrganismo.value === 'area'
        ? { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.2 } }
        : { type: 'solid' },
    xaxis: {
        categories: organismosFiltrados.value.slice(0, 20).map(o => o.siglas || o.organismo?.substring(0, 15) || '—'),
        labels: { style: { fontSize: '11px', colors: '#566268' }, rotate: -35 },
    },
    yaxis: { min: 0, max: 100, labels: { formatter: v => `${v}%`, style: { colors: '#566268' } } },
    plotOptions: {
        bar: { borderRadius: 6, columnWidth: '55%', distributed: false,
            dataLabels: { position: 'top' } },
    },
    dataLabels: { enabled: tipoOrganismo.value === 'bar', formatter: v => `${v}%`, style: { fontSize: '10px', colors: ['#b10f57'] }, offsetY: -18 },
    stroke: { curve: 'smooth', width: tipoOrganismo.value === 'bar' ? 0 : 3 },
    markers: { size: tipoOrganismo.value === 'line' ? 5 : 0, colors: [ROSA] },
    tooltip: { ...baseOpts.tooltip, y: { formatter: v => `${v}%` } },
}))

// ── Gráfica 2: Distribución por eje ──────────────────────────────────────
const chartEjeSeries  = computed(() => props.avancePorEje.map(e => +(e.promedio ?? 0).toFixed(1)))
const chartEjeLabels  = computed(() => props.avancePorEje.map(e => `Eje ${e.numero_eje}`))

const chartEjeOptions = computed(() => {
    if (tipoEje.value === 'bar') {
        return {
            ...baseOpts,
            chart: { ...baseOpts.chart, type: 'bar', id: 'eje-bar' },
            colors: PALETA,
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', distributed: true } },
            xaxis: { categories: chartEjeLabels.value, labels: { style: { fontSize: '12px' } } },
            yaxis: { min: 0, max: 100, labels: { formatter: v => `${v}%` } },
            legend: { show: false },
            dataLabels: { enabled: true, formatter: v => `${v}%`, style: { fontSize: '11px' } },
            series: [{ name: 'Avance (%)', data: chartEjeSeries.value }],
        }
    }
    return {
        ...baseOpts,
        chart: { ...baseOpts.chart, type: tipoEje.value, id: 'eje-chart' },
        colors: PALETA,
        labels: chartEjeLabels.value,
        legend: { position: 'bottom', fontSize: '13px' },
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Promedio', formatter: () => { const avg = chartEjeSeries.value.reduce((a,b)=>a+b,0)/chartEjeSeries.value.length; return `${avg.toFixed(1)}%` } } } } } },
        dataLabels: { formatter: (val, opts) => `${val.toFixed(1)}%` },
        tooltip: { y: { formatter: v => `${v}% avance` } },
    }
})

// ── Gráfica 3: Histograma de distribución de avances ─────────────────────
const chartHistSeries = computed(() => [{
    name: 'Líneas de acción',
    data: props.distribucionPct.map(d => d.count ?? 0),
}])
const chartHistOptions = computed(() => ({
    ...baseOpts,
    chart: { ...baseOpts.chart, type: 'bar', id: 'hist-chart' },
    colors: [JADE],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '85%' } },
    xaxis: {
        categories: props.distribucionPct.map(d => d.rango),
        title: { text: 'Rango de avance', style: { color: '#566268', fontSize: '12px' } },
        labels: { style: { fontSize: '11px' } },
    },
    yaxis: { title: { text: 'N° de líneas', style: { color: '#566268', fontSize: '12px' } }, labels: { style: { colors: '#566268' } } },
    dataLabels: { enabled: true, style: { fontSize: '11px', colors: ['#fff'] } },
    tooltip: { y: { formatter: v => `${v} línea${v !== 1 ? 's' : ''}` } },
}))

// ── Gráfica 4: Distribución por estatus ──────────────────────────────────
const chartEstatusSeries  = computed(() => props.distribucionEstatus.map(e => e.count ?? 0))
const chartEstatusOptions = computed(() => ({
    ...baseOpts,
    chart: { ...baseOpts.chart, type: 'pie', id: 'estatus-chart' },
    colors: [ROSA, JADE, CINABRIO, AZUL, BEIGE, '#7a5d95'],
    labels: props.distribucionEstatus.map(e => e.estatus ?? '—'),
    legend: { position: 'bottom', fontSize: '12px' },
    dataLabels: { formatter: (val) => `${val.toFixed(1)}%` },
}))

// ── Animaciones de scroll ─────────────────────────────────────────────────
let scrollObserver = null
onMounted(() => {
    scrollObserver = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in-view'); scrollObserver.unobserve(e.target) } })
    }, { threshold: 0.08 })
    document.querySelectorAll('.anim').forEach(el => scrollObserver.observe(el))
})
onUnmounted(() => scrollObserver?.disconnect())

// ── Descarga PDF del informe ─────────────────────────────────────────────
function descargarInforme() {
    window.print()
}
</script>

<template>
    <Head title="Estadísticas PI-PEA — Avances y Gráficas" />

    <div class="page-root" id="print-area">

        <!-- BANNER -->
        <div class="public-banner no-print-hide">
            <img src="/images/banner-sesaech.png" alt="SESAECH" class="banner-img" />
        </div>

        <!-- NAV -->
        <nav class="top-nav no-print-hide">
            <div class="nav-inner">
                <Link :href="route('home')" class="nav-back">← Inicio</Link>
                <div class="nav-links">
                    <Link :href="route('consulta')"     class="nav-link">Consulta PI-PEA</Link>
                    <Link :href="route('estadisticas')" class="nav-link nav-link--active">Estadísticas</Link>
                </div>
            </div>
        </nav>

        <main class="page-main">

            <!-- ══ ENCABEZADO ═══════════════════════════════════════════════ -->
            <section class="page-hero">
                <div class="hero-rombos" aria-hidden="true">
                    <span class="hr-r hr-r--1">◆</span>
                    <span class="hr-r hr-r--2">◆</span>
                </div>
                <div class="page-hero-inner">
                    <span class="hero-eyebrow">Análisis de avances</span>
                    <h1 class="hero-title">Estadísticas del PI-PEA</h1>
                    <p class="hero-subtitle">
                        Visualiza el avance de cumplimiento de las líneas de acción por organismo,
                        eje estratégico y distribución general.
                    </p>
                    <button class="btn-print no-print-hide" @click="descargarInforme">
                        ↓ Descargar informe (PDF)
                    </button>
                </div>
            </section>

            <!-- ══ TARJETAS RESUMEN ══════════════════════════════════════════ -->
            <section class="resumen-section">
                <div class="resumen-grid">
                    <div class="res-card anim" style="--d:0ms">
                        <span class="res-icon">◆</span>
                        <span class="res-num">{{ resumen.total_lineas ?? '—' }}</span>
                        <span class="res-lbl">Líneas de acción</span>
                    </div>
                    <div class="res-card anim" style="--d:70ms">
                        <span class="res-icon res-icon--jade">◆</span>
                        <span class="res-num">{{ resumen.con_avance ?? '—' }}</span>
                        <span class="res-lbl">Con avance registrado</span>
                    </div>
                    <div class="res-card anim" style="--d:140ms">
                        <span class="res-icon res-icon--cinabrio">◆</span>
                        <span class="res-num">{{ resumen.promedio_general ? `${resumen.promedio_general}%` : '—' }}</span>
                        <span class="res-lbl">Avance promedio general</span>
                    </div>
                    <div class="res-card anim" style="--d:210ms">
                        <span class="res-icon res-icon--azul">◆</span>
                        <span class="res-num">{{ resumen.total_organismos ?? '—' }}</span>
                        <span class="res-lbl">Organismos con líneas</span>
                    </div>
                </div>
            </section>

            <!-- ══ FILTRO EJE ════════════════════════════════════════════════ -->
            <div class="filtro-eje-bar anim no-print-hide" style="--d:80ms">
                <span class="feb-label">Filtrar por eje:</span>
                <div class="feb-opts">
                    <button :class="['feb-btn', { active: filtroEje === '' }]"  @click="filtroEje = ''">Todos</button>
                    <button
                        v-for="e in avancePorEje"
                        :key="e.id_eje"
                        :class="['feb-btn', { active: filtroEje === String(e.id_eje) }]"
                        @click="filtroEje = String(e.id_eje)"
                    >Eje {{ e.numero_eje }}</button>
                </div>
            </div>

            <!-- ══ GRÁFICAS ══════════════════════════════════════════════════ -->
            <div class="charts-grid">

                <!-- GRÁFICA 1: Avance por organismo -->
                <div class="chart-card anim" style="--d:0ms">
                    <div class="chart-hdr">
                        <div>
                            <h3 class="chart-title">Avance por organismo</h3>
                            <p class="chart-sub">Porcentaje promedio de cumplimiento</p>
                        </div>
                        <div class="tipo-toggle no-print-hide">
                            <button :class="['tt-btn', { active: tipoOrganismo === 'bar' }]"   @click="tipoOrganismo = 'bar'"  title="Barras">▐▌</button>
                            <button :class="['tt-btn', { active: tipoOrganismo === 'line' }]"  @click="tipoOrganismo = 'line'" title="Línea">╱</button>
                            <button :class="['tt-btn', { active: tipoOrganismo === 'area' }]"  @click="tipoOrganismo = 'area'" title="Área">▲</button>
                        </div>
                    </div>
                    <VueApexCharts
                        v-if="avancePorOrganismo.length"
                        :type="tipoOrganismo"
                        height="300"
                        :options="chartOrgOptions"
                        :series="chartOrgSeries"
                    />
                    <div v-else class="chart-empty">
                        <span class="empty-r">◆</span>
                        <p>Sin datos de avance por organismo</p>
                    </div>
                </div>

                <!-- GRÁFICA 2: Por eje -->
                <div class="chart-card anim" style="--d:80ms">
                    <div class="chart-hdr">
                        <div>
                            <h3 class="chart-title">Avance por eje estratégico</h3>
                            <p class="chart-sub">Promedio de cumplimiento por eje</p>
                        </div>
                        <div class="tipo-toggle no-print-hide">
                            <button :class="['tt-btn', { active: tipoEje === 'donut' }]" @click="tipoEje = 'donut'" title="Dona">◉</button>
                            <button :class="['tt-btn', { active: tipoEje === 'pie' }]"   @click="tipoEje = 'pie'"   title="Pastel">◑</button>
                            <button :class="['tt-btn', { active: tipoEje === 'bar' }]"   @click="tipoEje = 'bar'"   title="Barras">▐▌</button>
                        </div>
                    </div>
                    <VueApexCharts
                        v-if="avancePorEje.length"
                        :type="tipoEje === 'bar' ? 'bar' : tipoEje"
                        height="300"
                        :options="chartEjeOptions"
                        :series="tipoEje === 'bar' ? chartEjeOptions.series : chartEjeSeries"
                    />
                    <div v-else class="chart-empty">
                        <span class="empty-r">◆</span>
                        <p>Sin datos por eje</p>
                    </div>
                </div>

                <!-- GRÁFICA 3: Histograma -->
                <div class="chart-card anim" style="--d:160ms">
                    <div class="chart-hdr">
                        <div>
                            <h3 class="chart-title">Distribución de avances</h3>
                            <p class="chart-sub">Líneas por rango de cumplimiento</p>
                        </div>
                    </div>
                    <VueApexCharts
                        v-if="distribucionPct.length"
                        type="bar"
                        height="280"
                        :options="chartHistOptions"
                        :series="chartHistSeries"
                    />
                    <div v-else class="chart-empty">
                        <span class="empty-r">◆</span>
                        <p>Sin datos de distribución</p>
                    </div>
                </div>

                <!-- GRÁFICA 4: Por estatus -->
                <div class="chart-card anim" style="--d:240ms">
                    <div class="chart-hdr">
                        <div>
                            <h3 class="chart-title">Distribución por estatus</h3>
                            <p class="chart-sub">Líneas de acción por tipo de estatus</p>
                        </div>
                    </div>
                    <VueApexCharts
                        v-if="distribucionEstatus.length"
                        type="pie"
                        height="280"
                        :options="chartEstatusOptions"
                        :series="chartEstatusSeries"
                    />
                    <div v-else class="chart-empty">
                        <span class="empty-r">◆</span>
                        <p>Sin datos de estatus</p>
                    </div>
                </div>

            </div>

            <!-- ══ TABLA ORGANISMO DETALLE ════════════════════════════════════ -->
            <section class="tabla-section anim" style="--d:100ms">
                <div class="section-hdr">
                    <div class="section-label"><span class="lbl-r">◆</span> Detalle por organismo</div>
                    <h2 class="section-title">Avance individual</h2>
                </div>

                <div class="tabla-wrap" v-if="avancePorOrganismo.length">
                    <table class="org-tabla">
                        <thead>
                            <tr>
                                <th>Organismo</th>
                                <th>Siglas</th>
                                <th>Líneas asignadas</th>
                                <th>Con avance</th>
                                <th>Promedio de avance</th>
                                <th>Progreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="org in avancePorOrganismo" :key="org.id">
                                <td class="td-org">{{ org.organismo }}</td>
                                <td><span class="siglas-badge">{{ org.siglas || '—' }}</span></td>
                                <td class="td-center">{{ org.total_lineas }}</td>
                                <td class="td-center">{{ org.con_avance ?? '—' }}</td>
                                <td class="td-pct" :style="{ color: org.promedio >= 80 ? '#009887' : org.promedio >= 40 ? '#b10f57' : '#ae192d' }">
                                    {{ org.promedio ? `${(+org.promedio).toFixed(1)}%` : '0%' }}
                                </td>
                                <td>
                                    <div class="prog-track">
                                        <div class="prog-fill"
                                            :style="{
                                                width: `${Math.min(org.promedio || 0, 100)}%`,
                                                background: org.promedio >= 80 ? '#009887' : org.promedio >= 40 ? '#b10f57' : '#ae192d'
                                            }">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="chart-empty chart-empty--lg">
                    <span class="empty-r">◆</span>
                    <p>No hay datos de avance registrados aún</p>
                </div>
            </section>

        </main>

        <!-- FOOTER -->
        <footer class="page-footer no-print-hide">
            <div class="footer-inner">
                <img src="/images/banner-sesaech.png" alt="SESAECH" class="footer-logo" />
                <p class="footer-copy">© {{ new Date().getFullYear() }} SESAECH — Sistema Anticorrupción del Estado de Chiapas</p>
                <Link :href="route('home')" class="footer-link">← Volver al inicio</Link>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.anim { opacity: 0; transform: translateY(22px); transition: opacity 0.55s ease, transform 0.55s ease; transition-delay: var(--d, 0ms); }
.anim.in-view { opacity: 1; transform: translateY(0); }

.page-root { min-height: 100vh; background: #f7f7f7; font-family: Arial, sans-serif; color: #24343a; overflow-x: hidden; }
.public-banner { width: 100%; }
.banner-img { width: 100%; display: block; object-fit: cover; }

/* Nav */
.top-nav { background: #fff; border-bottom: 3px solid #b10f57; padding: 0 2rem; position: sticky; top: 0; z-index: 50; }
.nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 52px; }
.nav-back { color: #b10f57; font-size: 0.82rem; font-weight: 700; text-decoration: none; }
.nav-links { display: flex; gap: 0.5rem; }
.nav-link { padding: 0.4rem 0.85rem; border-radius: 6px; font-size: 0.82rem; font-weight: 600; color: #566268; text-decoration: none; transition: background 0.2s, color 0.2s; }
.nav-link:hover { background: #f7f7f7; color: #b10f57; }
.nav-link--active { background: rgba(177,15,87,0.08); color: #b10f57; }

/* Hero */
.page-hero {
    position: relative;
    background: linear-gradient(135deg, #9e0b4e 0%, #c0156a 60%, #b31060 100%);
    padding: 3rem 2rem 2.5rem; overflow: hidden;
}
.hero-rombos { position: absolute; inset: 0; pointer-events: none; }
.hr-r { position: absolute; line-height: 1; }
.hr-r--1 { top: -5%; right: 2%;  font-size: 8rem; color: rgba(255,255,255,0.05); }
.hr-r--2 { top: 50%; right: 20%; font-size: 2.5rem;color: rgba(255,255,255,0.09); }

.page-hero-inner { position: relative; z-index: 1; max-width: 800px; margin: 0 auto; }
.hero-eyebrow { display: inline-block; background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.28); color: #fff; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; padding: 0.3rem 0.85rem; border-radius: 999px; margin-bottom: 1rem; }
.hero-title  { margin: 0 0 0.8rem; font-size: clamp(1.6rem, 3vw, 2.5rem); font-weight: 800; color: #fff; line-height: 1.2; }
.hero-subtitle { margin: 0 0 1.5rem; font-size: 0.97rem; color: rgba(255,255,255,0.88); line-height: 1.65; max-width: 580px; }
.btn-print { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.5rem; background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.5); border-radius: 10px; color: #fff; font-size: 0.88rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: background 0.2s; }
.btn-print:hover { background: rgba(255,255,255,0.25); }

/* Main */
.page-main { max-width: 1200px; margin: 0 auto; padding: 0 2rem; }

/* Resumen */
.resumen-section { padding: 2rem 0 1.5rem; }
.resumen-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.res-card {
    background: #fff; border: 1px solid #ead8df; border-radius: 16px;
    padding: 1.5rem 1.25rem; display: flex; flex-direction: column; align-items: center; text-align: center;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05); transition: transform 0.22s, box-shadow 0.22s;
}
.res-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(177,15,87,0.1); }
.res-icon { font-size: 1.2rem; color: #b10f57; margin-bottom: 0.5rem; }
.res-icon--jade    { color: #009887; }
.res-icon--cinabrio{ color: #ae192d; }
.res-icon--azul    { color: #143d6d; }
.res-num { font-size: 2.2rem; font-weight: 900; color: #1d2a2e; line-height: 1; margin-bottom: 0.3rem; }
.res-lbl { font-size: 0.78rem; color: #566268; text-align: center; line-height: 1.4; }

/* Filtro eje */
.filtro-eje-bar { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 1rem 0; }
.feb-label { font-size: 0.8rem; font-weight: 700; color: #566268; white-space: nowrap; }
.feb-opts  { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.feb-btn { padding: 0.35rem 0.85rem; border-radius: 999px; border: 1.5px solid #e0c8d0; background: #fff; font-size: 0.78rem; font-weight: 600; color: #566268; cursor: pointer; font-family: inherit; transition: all 0.2s; }
.feb-btn:hover { border-color: #b10f57; color: #b10f57; }
.feb-btn.active { background: #b10f57; border-color: #b10f57; color: #fff; }

/* Gráficas grid */
.charts-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem; padding: 1rem 0 2rem; }

.chart-card { background: #fff; border: 1px solid #ead8df; border-radius: 18px; padding: 1.5rem; box-shadow: 0 3px 14px rgba(0,0,0,0.05); transition: box-shadow 0.22s; }
.chart-card:hover { box-shadow: 0 10px 28px rgba(177,15,87,0.09); }

.chart-hdr { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.25rem; gap: 1rem; }
.chart-title { font-size: 1rem; font-weight: 800; color: #1d2a2e; margin: 0 0 0.2rem; }
.chart-sub   { font-size: 0.78rem; color: #566268; margin: 0; }

.tipo-toggle { display: flex; border: 1.5px solid #e0c8d0; border-radius: 8px; overflow: hidden; flex-shrink: 0; }
.tt-btn { width: 30px; height: 28px; border: none; background: #fff; cursor: pointer; font-size: 0.9rem; color: #aaa; transition: background 0.2s, color 0.2s; display: flex; align-items: center; justify-content: center; }
.tt-btn.active { background: #b10f57; color: #fff; }

.chart-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; color: #aaa; gap: 0.5rem; }
.chart-empty--lg { height: 160px; }
.empty-r { font-size: 2rem; color: rgba(177,15,87,0.2); }
.chart-empty p { font-size: 0.88rem; margin: 0; }

/* Sección */
.section-hdr { text-align: center; margin-bottom: 1.5rem; }
.section-label { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #b10f57; margin-bottom: 0.5rem; }
.lbl-r { font-size: 0.5rem; }
.section-title { font-size: clamp(1.2rem, 2.5vw, 1.75rem); font-weight: 800; color: #1d2a2e; margin: 0; }

/* Tabla detalle */
.tabla-section { padding: 0 0 3rem; }
.tabla-wrap { overflow-x: auto; border-radius: 14px; border: 1px solid #ead8df; box-shadow: 0 3px 12px rgba(0,0,0,0.04); }
.org-tabla { width: 100%; border-collapse: collapse; background: #fff; font-size: 0.85rem; }
.org-tabla thead tr { background: linear-gradient(135deg, #9e0b4e, #b10f57); color: #fff; }
.org-tabla th { padding: 0.75rem 1rem; text-align: left; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; white-space: nowrap; }
.org-tabla tbody tr { border-bottom: 1px solid #f5eaed; transition: background 0.15s; }
.org-tabla tbody tr:hover { background: #fdf5f8; }
.org-tabla td { padding: 0.7rem 1rem; vertical-align: middle; }
.td-org { font-weight: 600; color: #1d2a2e; max-width: 220px; }
.td-center { text-align: center; color: #566268; }
.td-pct { font-weight: 800; font-size: 0.95rem; text-align: center; }
.siglas-badge { font-size: 0.68rem; font-weight: 800; background: #24343a; color: #fff; padding: 0.1rem 0.45rem; border-radius: 4px; letter-spacing: 0.05em; }
.prog-track { height: 8px; background: #f3e6ea; border-radius: 999px; overflow: hidden; min-width: 120px; }
.prog-fill  { height: 100%; border-radius: 999px; transition: width 0.6s ease; }

/* Footer */
.page-footer { background: #0c1a2e; border-top: 3px solid #b10f57; padding: 1.5rem 2rem; }
.footer-inner{ max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.footer-logo { height: 26px; object-fit: contain; filter: brightness(0) invert(0.5); }
.footer-copy { font-size: 0.75rem; color: #4a5a66; }
.footer-link { font-size: 0.78rem; font-weight: 700; color: #b10f57; text-decoration: none; }

/* Print */
@media print {
    .no-print-hide { display: none !important; }
    .page-root { background: #fff; }
    .chart-card, .res-card { box-shadow: none !important; border-color: #ddd; }
    .charts-grid { grid-template-columns: 1fr 1fr; }
    .page-hero { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}

@media (max-width: 900px) {
    .charts-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .page-main { padding: 0 1rem; }
    .resumen-grid { grid-template-columns: repeat(2, 1fr); }
    .page-hero { padding: 2rem 1rem 1.75rem; }
    .footer-inner { flex-direction: column; text-align: center; }
    .org-tabla th:nth-child(3),
    .org-tabla td:nth-child(3),
    .org-tabla th:nth-child(4),
    .org-tabla td:nth-child(4) { display: none; }
}
</style>
