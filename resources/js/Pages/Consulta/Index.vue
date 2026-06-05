<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    ejes:         { type: Array, default: () => [] },
    organismos:   { type: Array, default: () => [] },
    lineasAccion: { type: Array, default: () => [] },
    documentos:   { type: Array, default: () => [] },
})

// ── Filtros ────────────────────────────────────────────────────────────────
const busqueda        = ref('')
const filtroOrganismo = ref('')
const filtroEje       = ref('')
const filtroPlazo     = ref('')
const filtroEstatus   = ref('')
const vistaCards      = ref(true)

const EJE_META = [
    { numero: 1, color: '#b10f57' },
    { numero: 2, color: '#ae192d' },
    { numero: 3, color: '#009887' },
    { numero: 4, color: '#3e6f78' },
]

const lineasFiltradas = computed(() =>
    props.lineasAccion.filter(l => {
        const q = busqueda.value.toLowerCase().trim()
        if (q) {
            const ok = [l.nombre_indicador, l.organismo?.nombre, l.organismo?.siglas, l.eje?.eje]
                .some(v => v?.toLowerCase().includes(q))
            if (!ok) return false
        }
        if (filtroOrganismo.value && String(l.organismo?.id) !== filtroOrganismo.value) return false
        if (filtroEje.value       && String(l.eje?.id)       !== filtroEje.value)       return false
        if (filtroPlazo.value     && String(l.plazo?.id)     !== filtroPlazo.value)     return false
        if (filtroEstatus.value   && String(l.estatus?.id)   !== filtroEstatus.value)   return false
        return true
    })
)

const opcionesPlazos = computed(() => {
    const seen = new Set()
    return props.lineasAccion.filter(l => l.plazo && !seen.has(l.plazo.id) && seen.add(l.plazo.id)).map(l => l.plazo)
})
const opcionesEstatus = computed(() => {
    const seen = new Set()
    return props.lineasAccion.filter(l => l.estatus && !seen.has(l.estatus.id) && seen.add(l.estatus.id)).map(l => l.estatus)
})
const hayFiltros = computed(() =>
    busqueda.value || filtroOrganismo.value || filtroEje.value || filtroPlazo.value || filtroEstatus.value
)

function limpiarFiltros() {
    busqueda.value = filtroOrganismo.value = filtroEje.value = filtroPlazo.value = filtroEstatus.value = ''
}

function colorEje(n) { return EJE_META.find(m => m.numero === n)?.color ?? '#b10f57' }

function colorBarra(pct) {
    if (pct >= 80) return '#009887'
    if (pct >= 40) return '#b10f57'
    return '#ae192d'
}

// ── Animaciones ────────────────────────────────────────────────────────────
let scrollObserver = null
onMounted(() => {
    scrollObserver = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in-view'); scrollObserver.unobserve(e.target) } })
    }, { threshold: 0.1 })
    document.querySelectorAll('.anim').forEach(el => scrollObserver.observe(el))
})
onUnmounted(() => scrollObserver?.disconnect())

// ── Doc icons ─────────────────────────────────────────────────────────────
function docIcon(tipo) {
    return tipo === 'PPTX' ? '📊' : tipo === 'PDF' ? '📄' : '📁'
}
</script>

<template>
    <Head title="Consulta PI-PEA — Líneas de Acción y Documentos" />

    <div class="page-root">

        <!-- BANNER -->
        <div class="public-banner">
            <img src="/images/banner-sesaech.png" alt="SESAECH" class="banner-img" />
        </div>

        <!-- NAV PÚBLICA -->
        <nav class="top-nav">
            <div class="nav-inner">
                <Link :href="route('home')" class="nav-back">
                    ← Inicio
                </Link>
                <div class="nav-links">
                    <Link :href="route('consulta')" class="nav-link nav-link--active">Consulta PI-PEA</Link>
                    <Link :href="route('estadisticas')" class="nav-link">Estadísticas</Link>
                </div>
            </div>
        </nav>

        <main class="page-main">

            <!-- ══ ENCABEZADO ═══════════════════════════════════════════════ -->
            <section class="page-hero">
                <div class="hero-rombos" aria-hidden="true">
                    <span class="hr-r hr-r--1">◆</span>
                    <span class="hr-r hr-r--2">◆</span>
                    <span class="hr-r hr-r--3">◆</span>
                </div>
                <div class="page-hero-inner">
                    <span class="hero-eyebrow">Información pública</span>
                    <h1 class="hero-title">Consulta del PI-PEA</h1>
                    <p class="hero-subtitle">
                        Explora las líneas de acción del Programa de Implementación, filtra por organismo,
                        eje o plazo, y descarga los documentos oficiales.
                    </p>
                </div>
            </section>

            <!-- ══ DOCUMENTOS OFICIALES ══════════════════════════════════════ -->
            <section class="docs-section">
                <div class="section-hdr anim">
                    <div class="section-label"><span class="lbl-r">◆</span> Documentos oficiales</div>
                    <h2 class="section-title">Descarga documentos del PI-PEA</h2>
                    <p class="section-desc">Accede a los documentos oficiales, manuales y materiales de referencia del programa.</p>
                </div>

                <div class="docs-grid">
                    <a
                        v-for="(doc, i) in documentos"
                        :key="i"
                        :href="route('documentos.download', i)"
                        class="doc-card anim"
                        :style="{ '--d': `${i * 60}ms` }"
                        target="_blank"
                        rel="noopener"
                    >
                        <div class="doc-icon-wrap">
                            <span class="doc-icon">{{ docIcon(doc.tipo) }}</span>
                            <span class="doc-tipo" :class="`doc-tipo--${doc.tipo.toLowerCase()}`">{{ doc.tipo }}</span>
                        </div>
                        <div class="doc-info">
                            <h4 class="doc-nombre">{{ doc.nombre }}</h4>
                            <p v-if="doc.descripcion" class="doc-desc">{{ doc.descripcion }}</p>
                        </div>
                        <div class="doc-dl">
                            <span class="doc-dl-icon">↓</span>
                            <span class="doc-dl-txt">Descargar</span>
                        </div>
                    </a>
                </div>
            </section>

            <div class="rombo-div" aria-hidden="true"><span>◆</span><span>◆</span><span>◆</span></div>

            <!-- ══ BUSCADOR Y FILTROS ════════════════════════════════════════ -->
            <section class="consulta-section">
                <div class="section-hdr anim">
                    <div class="section-label"><span class="lbl-r">◆</span> Líneas de acción</div>
                    <h2 class="section-title">Consulta por organismo implementador</h2>
                </div>

                <!-- Barra de filtros -->
                <div class="filtros anim" style="--d:60ms">
                    <div class="search-wrap">
                        <svg class="search-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                        <input v-model="busqueda" type="text" class="search-input" placeholder="Buscar indicador, organismo o siglas…" />
                        <button v-if="busqueda" class="search-clear" @click="busqueda = ''">✕</button>
                    </div>

                    <div class="filtros-selects">
                        <select v-model="filtroOrganismo" class="f-sel">
                            <option value="">Todos los organismos</option>
                            <option v-for="org in organismos" :key="org.id" :value="String(org.id)">
                                {{ org.siglas ? `${org.siglas} — ` : '' }}{{ org.nombre }}
                            </option>
                        </select>

                        <select v-model="filtroEje" class="f-sel">
                            <option value="">Todos los ejes</option>
                            <option v-for="e in ejes" :key="e.id" :value="String(e.id)">Eje {{ e.numero_eje }} — {{ e.eje }}</option>
                        </select>

                        <select v-if="opcionesPlazos.length" v-model="filtroPlazo" class="f-sel">
                            <option value="">Todos los plazos</option>
                            <option v-for="p in opcionesPlazos" :key="p.id" :value="String(p.id)">{{ p.plazo }}</option>
                        </select>

                        <select v-if="opcionesEstatus.length" v-model="filtroEstatus" class="f-sel">
                            <option value="">Todos los estatus</option>
                            <option v-for="e in opcionesEstatus" :key="e.id" :value="String(e.id)">{{ e.nombre }}</option>
                        </select>
                    </div>

                    <div class="filtros-actions">
                        <span class="result-cnt">
                            <strong>{{ lineasFiltradas.length }}</strong>
                            resultado{{ lineasFiltradas.length !== 1 ? 's' : '' }}
                        </span>
                        <button v-if="hayFiltros" class="btn-clear" @click="limpiarFiltros">✕ Limpiar filtros</button>
                        <!-- Toggle vista -->
                        <div class="vista-toggle">
                            <button :class="['vt-btn', { active: vistaCards }]"  @click="vistaCards = true"  title="Vista tarjetas">⊞</button>
                            <button :class="['vt-btn', { active: !vistaCards }]" @click="vistaCards = false" title="Vista tabla">☰</button>
                        </div>
                    </div>
                </div>

                <!-- ── VISTA TARJETAS ───────────────────────────────────────── -->
                <div v-if="vistaCards && lineasFiltradas.length" class="lineas-grid">
                    <article v-for="linea in lineasFiltradas" :key="linea.id" class="linea-card">
                        <div class="lc-head">
                            <div class="lc-tags">
                                <span v-if="linea.eje" class="tag tag-eje" :style="{ background: colorEje(linea.eje.numero_eje) }">
                                    Eje {{ linea.eje.numero_eje }}
                                </span>
                                <span v-if="linea.plazo"   class="tag tag-plazo">{{ linea.plazo.plazo }}</span>
                                <span v-if="linea.estatus" class="tag tag-estatus">{{ linea.estatus.nombre }}</span>
                            </div>
                            <div v-if="linea.organismo" class="lc-org">
                                <span v-if="linea.organismo.siglas" class="org-siglas">{{ linea.organismo.siglas }}</span>
                                <span class="org-nombre">{{ linea.organismo.nombre }}</span>
                            </div>
                        </div>

                        <h4 class="lc-nombre">{{ linea.nombre_indicador || 'Sin nombre de indicador' }}</h4>

                        <div class="lc-meta">
                            <div v-if="linea.objetivo" class="meta-item">
                                <span class="meta-lbl">Objetivo</span>
                                <span class="meta-val">{{ linea.objetivo.objetivo }}</span>
                            </div>
                            <div v-if="linea.prioridad" class="meta-item">
                                <span class="meta-lbl">Prioridad</span>
                                <span class="meta-val">{{ linea.prioridad.prioridad }}</span>
                            </div>
                            <div v-if="linea.estrategia" class="meta-item">
                                <span class="meta-lbl">Estrategia</span>
                                <span class="meta-val">{{ linea.estrategia.estrategia }}</span>
                            </div>
                            <div class="meta-row">
                                <div v-if="linea.frecuencia" class="meta-item">
                                    <span class="meta-lbl">Frecuencia</span>
                                    <span class="meta-val">{{ linea.frecuencia.frecuencia }}</span>
                                </div>
                                <div v-if="linea.meta" class="meta-item">
                                    <span class="meta-lbl">Meta</span>
                                    <span class="meta-val">{{ linea.meta }} {{ linea.unidad_medida }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="lc-avance">
                            <div class="av-hdr">
                                <span class="av-lbl">Avance</span>
                                <span class="av-pct" :style="{ color: colorBarra(linea.porcentaje_avance || 0) }">
                                    {{ linea.porcentaje_avance ? `${linea.porcentaje_avance}%` : 'Sin avance' }}
                                </span>
                            </div>
                            <div class="av-track">
                                <div class="av-fill"
                                    :style="{ width: `${Math.min(linea.porcentaje_avance || 0, 100)}%`, background: colorBarra(linea.porcentaje_avance || 0) }">
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- ── VISTA TABLA ─────────────────────────────────────────── -->
                <div v-else-if="!vistaCards && lineasFiltradas.length" class="tabla-wrap">
                    <table class="lineas-tabla">
                        <thead>
                            <tr>
                                <th>Indicador</th>
                                <th>Organismo</th>
                                <th>Eje</th>
                                <th>Plazo</th>
                                <th>Meta</th>
                                <th>Avance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="linea in lineasFiltradas" :key="linea.id">
                                <td class="td-nombre">{{ linea.nombre_indicador || '—' }}</td>
                                <td>
                                    <span v-if="linea.organismo?.siglas" class="org-siglas-sm">{{ linea.organismo.siglas }}</span>
                                    <span v-else class="text-gray">{{ linea.organismo?.nombre || '—' }}</span>
                                </td>
                                <td>
                                    <span v-if="linea.eje" class="tag tag-eje tag-sm" :style="{ background: colorEje(linea.eje.numero_eje) }">
                                        Eje {{ linea.eje.numero_eje }}
                                    </span>
                                </td>
                                <td class="text-gray">{{ linea.plazo?.plazo || '—' }}</td>
                                <td class="text-gray">{{ linea.meta ? `${linea.meta} ${linea.unidad_medida ?? ''}` : '—' }}</td>
                                <td>
                                    <div class="av-inline">
                                        <div class="av-track av-track--sm">
                                            <div class="av-fill" :style="{ width: `${Math.min(linea.porcentaje_avance || 0, 100)}%`, background: colorBarra(linea.porcentaje_avance || 0) }"></div>
                                        </div>
                                        <span class="av-pct-sm" :style="{ color: colorBarra(linea.porcentaje_avance || 0) }">
                                            {{ linea.porcentaje_avance ? `${linea.porcentaje_avance}%` : '—' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Estado vacío -->
                <div v-else-if="!lineasFiltradas.length" class="estado-vacio">
                    <div class="vacio-rombo" aria-hidden="true">◆</div>
                    <h3 class="vacio-titulo">
                        {{ lineasAccion.length ? 'No se encontraron resultados' : 'Sin datos disponibles' }}
                    </h3>
                    <p class="vacio-desc">
                        {{ lineasAccion.length ? 'Ajusta o limpia los filtros para ver más líneas de acción.' : 'Las líneas de acción estarán disponibles próximamente.' }}
                    </p>
                    <button v-if="hayFiltros" class="btn-clear-vacio" @click="limpiarFiltros">Limpiar filtros</button>
                </div>
            </section>

        </main>

        <!-- FOOTER -->
        <footer class="page-footer">
            <div class="footer-inner">
                <img src="/images/banner-sesaech.png" alt="SESAECH" class="footer-logo" />
                <p class="footer-copy">© {{ new Date().getFullYear() }} SESAECH — Sistema Anticorrupción del Estado de Chiapas</p>
                <Link :href="route('home')" class="footer-link">← Volver al inicio</Link>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.anim { opacity: 0; transform: translateY(24px); transition: opacity 0.55s ease, transform 0.55s ease; transition-delay: var(--d, 0ms); }
.anim.in-view { opacity: 1; transform: translateY(0); }

.page-root { min-height: 100vh; background: #f7f7f7; font-family: Arial, sans-serif; color: #24343a; overflow-x: hidden; }
.public-banner { width: 100%; }
.banner-img    { width: 100%; display: block; object-fit: cover; }

/* Nav */
.top-nav { background: #fff; border-bottom: 3px solid #b10f57; padding: 0 2rem; position: sticky; top: 0; z-index: 50; }
.nav-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 52px; }
.nav-back { color: #b10f57; font-size: 0.82rem; font-weight: 700; text-decoration: none; transition: opacity 0.2s; }
.nav-back:hover { opacity: 0.75; }
.nav-links { display: flex; gap: 0.5rem; }
.nav-link { padding: 0.4rem 0.85rem; border-radius: 6px; font-size: 0.82rem; font-weight: 600; color: #566268; text-decoration: none; transition: background 0.2s, color 0.2s; }
.nav-link:hover { background: #f7f7f7; color: #b10f57; }
.nav-link--active { background: rgba(177,15,87,0.08); color: #b10f57; }

/* Hero */
.page-hero {
    position: relative;
    background: linear-gradient(135deg, #9e0b4e 0%, #c0156a 60%, #b31060 100%);
    padding: 3rem 2rem 2.5rem;
    overflow: hidden;
}
.hero-rombos { position: absolute; inset: 0; pointer-events: none; }
.hr-r { position: absolute; line-height: 1; }
.hr-r--1 { top: -5%; right: 2%;   font-size: 8rem; color: rgba(255,255,255,0.05); }
.hr-r--2 { top: 50%; right: 20%;  font-size: 2rem; color: rgba(255,255,255,0.09); }
.hr-r--3 { bottom:8%;left: 5%;    font-size: 3.5rem;color: rgba(255,255,255,0.06); }

.page-hero-inner { position: relative; z-index: 1; max-width: 800px; margin: 0 auto; }
.hero-eyebrow { display: inline-block; background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.28); color: #fff; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; padding: 0.3rem 0.85rem; border-radius: 999px; margin-bottom: 1rem; }
.hero-title   { margin: 0 0 0.8rem; font-size: clamp(1.6rem, 3vw, 2.5rem); font-weight: 800; color: #fff; line-height: 1.2; }
.hero-subtitle{ margin: 0; font-size: 0.97rem; color: rgba(255,255,255,0.88); line-height: 1.65; max-width: 600px; }

/* Utiles sección */
.page-main { max-width: 1200px; margin: 0 auto; padding: 0 2rem; }
.section-hdr { text-align: center; margin-bottom: 1.75rem; }
.section-label { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #b10f57; margin-bottom: 0.5rem; }
.lbl-r { font-size: 0.5rem; }
.section-title { font-size: clamp(1.3rem, 2.5vw, 1.9rem); font-weight: 800; color: #1d2a2e; margin: 0 0 0.6rem; }
.section-desc  { font-size: 0.92rem; color: #566268; line-height: 1.6; max-width: 580px; margin: 0 auto; }

.rombo-div { display: flex; align-items: center; justify-content: center; gap: 0.6rem; padding: 0.3rem 0; }
.rombo-div span { font-size: 0.52rem; color: rgba(177,15,87,0.28); }
.rombo-div span:nth-child(2) { font-size: 0.78rem; color: rgba(177,15,87,0.5); }

/* Documentos */
.docs-section { padding: 2.5rem 0 2rem; }
.docs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.doc-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #fff;
    border: 1px solid #ead8df;
    border-radius: 14px;
    padding: 1.1rem 1.25rem;
    text-decoration: none;
    color: inherit;
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    position: relative;
    overflow: hidden;
}
.doc-card::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: #b10f57;
    transform: scaleY(0);
    transform-origin: bottom;
    transition: transform 0.25s ease;
}
.doc-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(177,15,87,0.12); border-color: #d6899f; }
.doc-card:hover::before { transform: scaleY(1); }

.doc-icon-wrap { display: flex; flex-direction: column; align-items: center; gap: 0.25rem; flex-shrink: 0; }
.doc-icon { font-size: 1.8rem; line-height: 1; }
.doc-tipo {
    font-size: 0.58rem; font-weight: 800; letter-spacing: 0.06em;
    padding: 0.1rem 0.4rem; border-radius: 4px;
}
.doc-tipo--pdf  { background: rgba(174,25,45,0.12); color: #ae192d; }
.doc-tipo--pptx { background: rgba(0,120,212,0.1);  color: #0078d4; }

.doc-info { flex: 1; min-width: 0; }
.doc-nombre { font-size: 0.88rem; font-weight: 700; color: #1d2a2e; margin: 0 0 0.2rem; line-height: 1.35; }
.doc-desc   { font-size: 0.78rem; color: #7a8a90; margin: 0; line-height: 1.4; }

.doc-dl { display: flex; flex-direction: column; align-items: center; gap: 0.15rem; flex-shrink: 0; opacity: 0.4; transition: opacity 0.2s; }
.doc-card:hover .doc-dl { opacity: 1; }
.doc-dl-icon { font-size: 1.1rem; color: #b10f57; }
.doc-dl-txt  { font-size: 0.62rem; font-weight: 700; color: #b10f57; text-transform: uppercase; letter-spacing: 0.06em; }

/* Consulta */
.consulta-section { padding: 2rem 0 3rem; }

/* Filtros */
.filtros {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    background: #fff;
    border: 1px solid #ead8df;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.75rem;
    box-shadow: 0 3px 12px rgba(0,0,0,0.05);
}

.search-wrap { position: relative; }
.search-ico { position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: #b10f57; pointer-events: none; }
.search-input { width: 100%; padding: 0.65rem 2.5rem 0.65rem 2.25rem; border: 1.5px solid #e0c8d0; border-radius: 10px; font-size: 0.9rem; font-family: inherit; color: #24343a; outline: none; transition: border-color 0.2s; box-sizing: border-box; }
.search-input:focus { border-color: #b10f57; }
.search-clear { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #999; font-size: 0.85rem; padding: 0; }
.search-clear:hover { color: #b10f57; }

.filtros-selects { display: flex; gap: 0.65rem; flex-wrap: wrap; }
.f-sel { padding: 0.55rem 0.75rem; border: 1.5px solid #e0c8d0; border-radius: 8px; font-size: 0.82rem; font-family: inherit; color: #24343a; background: #fff; outline: none; cursor: pointer; transition: border-color 0.2s; min-width: 160px; flex: 1; }
.f-sel:focus { border-color: #b10f57; }

.filtros-actions { display: flex; align-items: center; gap: 0.85rem; flex-wrap: wrap; }
.result-cnt { font-size: 0.82rem; color: #566268; }
.result-cnt strong { color: #b10f57; font-size: 1rem; }
.btn-clear { font-size: 0.78rem; font-weight: 700; color: #b10f57; background: none; border: 1.5px solid #b10f57; border-radius: 6px; padding: 0.3rem 0.75rem; cursor: pointer; font-family: inherit; transition: background 0.2s, color 0.2s; }
.btn-clear:hover { background: #b10f57; color: #fff; }

.vista-toggle { display: flex; border: 1.5px solid #e0c8d0; border-radius: 8px; overflow: hidden; margin-left: auto; }
.vt-btn { width: 34px; height: 30px; border: none; background: #fff; cursor: pointer; font-size: 1rem; color: #aaa; transition: background 0.2s, color 0.2s; display: flex; align-items: center; justify-content: center; }
.vt-btn.active { background: #b10f57; color: #fff; }

/* Grid de tarjetas */
.lineas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.1rem; }

.linea-card {
    background: #fff; border: 1px solid #ead8df; border-radius: 16px;
    padding: 1.2rem; display: flex; flex-direction: column; gap: 0.85rem;
    box-shadow: 0 3px 12px rgba(0,0,0,0.04);
    transition: box-shadow 0.22s, transform 0.22s;
}
.linea-card:hover { box-shadow: 0 10px 28px rgba(177,15,87,0.1); transform: translateY(-2px); }

.lc-head  { display: flex; flex-direction: column; gap: 0.4rem; }
.lc-tags  { display: flex; flex-wrap: wrap; gap: 0.3rem; }
.tag      { font-size: 0.67rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 999px; }
.tag-eje  { color: #fff; }
.tag-sm   { font-size: 0.62rem; padding: 0.15rem 0.45rem; }
.tag-plazo  { background: #f0ece9; color: #566268; }
.tag-estatus{ background: rgba(0,152,135,0.1); color: #009887; }

.lc-org   { display: flex; align-items: baseline; gap: 0.45rem; flex-wrap: wrap; }
.org-siglas    { font-size: 0.7rem; font-weight: 800; background: #24343a; color: #fff; padding: 0.12rem 0.5rem; border-radius: 4px; letter-spacing: 0.05em; flex-shrink: 0; }
.org-siglas-sm { font-size: 0.68rem; font-weight: 800; background: #24343a; color: #fff; padding: 0.1rem 0.4rem; border-radius: 3px; }
.org-nombre    { font-size: 0.78rem; color: #566268; line-height: 1.3; }
.lc-nombre     { font-size: 0.92rem; font-weight: 700; color: #1d2a2e; line-height: 1.4; margin: 0; }

.lc-meta  { display: flex; flex-direction: column; gap: 0.38rem; }
.meta-row { display: flex; gap: 1rem; }
.meta-item{ display: flex; flex-direction: column; gap: 0.08rem; min-width: 0; }
.meta-lbl { font-size: 0.62rem; text-transform: uppercase; letter-spacing: 0.07em; color: #b10f57; font-weight: 700; }
.meta-val { font-size: 0.8rem; color: #3a3a3a; line-height: 1.4; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

.lc-avance { margin-top: auto; }
.av-hdr { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem; }
.av-lbl { font-size: 0.67rem; color: #b10f57; text-transform: uppercase; letter-spacing: 0.07em; font-weight: 700; }
.av-pct { font-size: 0.82rem; font-weight: 800; }
.av-track { height: 6px; background: #f3e6ea; border-radius: 999px; overflow: hidden; }
.av-track--sm { height: 5px; width: 80px; flex-shrink: 0; }
.av-fill  { height: 100%; border-radius: 999px; transition: width 0.4s ease; }
.av-inline { display: flex; align-items: center; gap: 0.5rem; }
.av-pct-sm { font-size: 0.75rem; font-weight: 700; white-space: nowrap; }

/* Tabla */
.tabla-wrap { overflow-x: auto; border-radius: 14px; border: 1px solid #ead8df; box-shadow: 0 3px 12px rgba(0,0,0,0.04); }
.lineas-tabla { width: 100%; border-collapse: collapse; background: #fff; font-size: 0.85rem; }
.lineas-tabla thead tr { background: linear-gradient(135deg, #9e0b4e, #b10f57); color: #fff; }
.lineas-tabla th { padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; white-space: nowrap; }
.lineas-tabla tbody tr { border-bottom: 1px solid #f5eaed; transition: background 0.15s; }
.lineas-tabla tbody tr:hover { background: #fdf5f8; }
.lineas-tabla td { padding: 0.75rem 1rem; vertical-align: middle; }
.td-nombre { font-weight: 600; color: #1d2a2e; max-width: 260px; }
.text-gray { color: #566268; }

/* Estado vacío */
.estado-vacio { text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 18px; border: 2px dashed #ead8df; }
.vacio-rombo { font-size: 2.5rem; color: rgba(177,15,87,0.22); margin-bottom: 1rem; display: block; }
.vacio-titulo{ font-size: 1.05rem; font-weight: 800; color: #1d2a2e; margin-bottom: 0.5rem; }
.vacio-desc  { font-size: 0.88rem; color: #566268; max-width: 360px; margin: 0 auto 1.5rem; line-height: 1.6; }
.btn-clear-vacio { background: linear-gradient(135deg, #b10f57, #c0156a); color: #fff; border: none; border-radius: 10px; padding: 0.65rem 1.5rem; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: filter 0.2s, transform 0.15s; }
.btn-clear-vacio:hover { filter: brightness(1.08); transform: translateY(-1px); }

/* Footer */
.page-footer { background: #0c1a2e; border-top: 3px solid #b10f57; padding: 1.5rem 2rem; }
.footer-inner{ max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
.footer-logo { height: 26px; object-fit: contain; filter: brightness(0) invert(0.5); }
.footer-copy { font-size: 0.75rem; color: #4a5a66; }
.footer-link { font-size: 0.78rem; font-weight: 700; color: #b10f57; text-decoration: none; }
.footer-link:hover { text-decoration: underline; }

@media (max-width: 768px) {
    .page-main { padding: 0 1rem; }
    .filtros-selects { flex-direction: column; }
    .f-sel { min-width: 0; }
    .lineas-grid { grid-template-columns: 1fr; }
    .docs-grid { grid-template-columns: 1fr; }
    .page-hero { padding: 2rem 1rem 1.75rem; }
    .footer-inner { flex-direction: column; text-align: center; }
}
</style>
