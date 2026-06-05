<script setup>
import OrganismoLayout from '@/Layouts/Organismolayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    porLinea:    { type: Array, default: () => [] },
    cronologico: { type: Array, default: () => [] },
})

const vista    = ref('linea')
const busqueda = ref('')

const cronologicoFiltrado = computed(() => {
    if (!busqueda.value) return props.cronologico
    const q = busqueda.value.toLowerCase()
    return props.cronologico.filter(a =>
        a.prioridad?.toLowerCase().includes(q) ||
        a.documento?.toLowerCase().includes(q) ||
        a.medio_verificacion?.toLowerCase().includes(q)
    )
})

const porLineaFiltrada = computed(() => {
    if (!busqueda.value) return props.porLinea
    const q = busqueda.value.toLowerCase()
    return props.porLinea
        .map(g => ({
            ...g,
            avances: g.avances.filter(a =>
                a.prioridad?.toLowerCase().includes(q) ||
                a.documento?.toLowerCase().includes(q) ||
                a.medio_verificacion?.toLowerCase().includes(q)
            ),
        }))
        .filter(g => g.avances.length > 0)
})

const expandidos = ref(new Set())
const toggle = (id) => {
    if (expandidos.value.has(id)) expandidos.value.delete(id)
    else expandidos.value.add(id)
    expandidos.value = new Set(expandidos.value)
}

const truncar = (texto, max = 80) =>
    texto?.length > max ? texto.substring(0, max) + '...' : texto

const fmtNum = (n) =>
    n !== null && n !== undefined && n !== ''
        ? Number(n).toLocaleString('es-MX', { maximumFractionDigits: 4, useGrouping: false })
        : '—'

const colorIcono = (tipo) => {
    if (!tipo) return '#6b7280'
    if (tipo.includes('pdf'))   return '#dc2626'
    if (tipo.includes('image')) return '#7c3aed'
    if (tipo.includes('word') || tipo.includes('document')) return '#2563eb'
    return '#6b7280'
}

const totalEvidencias = computed(() => props.cronologico.length)
const conArchivo = computed(() => props.cronologico.filter(a => a.archivo_url).length)
const conUrl     = computed(() => props.cronologico.filter(a => a.url && !a.archivo_url).length)
</script>

<template>
    <OrganismoLayout>
        <div class="page">

            <div class="page-head">
                <div>
                    <p class="page-breadcrumb">Seguimiento</p>
                    <h1 class="page-title">Mis evidencias</h1>
                </div>
            </div>

            <!-- Métricas -->
            <div class="metrics-strip">
                <div class="metric-chip">
                    <span class="metric-chip-val">{{ totalEvidencias }}</span>
                    <span class="metric-chip-label">total evidencias</span>
                </div>
                <div class="metric-sep"></div>
                <div class="metric-chip metric-chip--rojo">
                    <span class="metric-chip-val">{{ conArchivo }}</span>
                    <span class="metric-chip-label">archivos subidos</span>
                </div>
                <div class="metric-sep"></div>
                <div class="metric-chip metric-chip--azul">
                    <span class="metric-chip-val">{{ conUrl }}</span>
                    <span class="metric-chip-label">enlaces externos</span>
                </div>
                <div class="metric-sep"></div>
                <div class="metric-chip metric-chip--gris">
                    <span class="metric-chip-val">{{ porLinea.length }}</span>
                    <span class="metric-chip-label">líneas con evidencia</span>
                </div>
            </div>

            <!-- Controles -->
            <div class="controles">
                <div class="search-bar">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"
                         style="width:15px;height:15px;flex-shrink:0;color:var(--color-gris-400)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input v-model="busqueda" type="text"
                           placeholder="Buscar por prioridad, documento o medio de verificación..."
                           class="search-input"/>
                </div>
                <div class="vista-toggle">
                    <button :class="['vista-btn', vista === 'linea' ? 'vista-btn--active' : '']" @click="vista = 'linea'">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                        </svg>
                        Por línea
                    </button>
                    <button :class="['vista-btn', vista === 'cronologico' ? 'vista-btn--active' : '']" @click="vista = 'cronologico'">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                        Cronológico
                    </button>
                </div>
            </div>

            <!-- ── VISTA: POR LÍNEA ─────────────────────────────────────────── -->
            <div v-if="vista === 'linea'">
                <div v-if="porLineaFiltrada.length === 0" class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--color-gris-300)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    <p>No hay evidencias que coincidan con la búsqueda.</p>
                </div>

                <div v-for="grupo in porLineaFiltrada" :key="grupo.id_linea_accion" class="grupo-card">
                    <div class="grupo-head" @click="toggle(grupo.id_linea_accion)">
                        <div style="display:flex;align-items:center;gap:10px">
                            <span class="badge-pri">P{{ grupo.numero_prioridad }}</span>
                            <span class="grupo-titulo">{{ truncar(grupo.prioridad, 90) }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px">
                            <span class="grupo-count">{{ grupo.total }} evidencia{{ grupo.total !== 1 ? 's' : '' }}</span>
                            <svg viewBox="0 0 20 20" fill="currentColor"
                                 style="width:16px;height:16px;color:var(--color-gris-400);transition:transform .2s"
                                 :style="{ transform: expandidos.has(grupo.id_linea_accion) ? 'rotate(180deg)' : '' }">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>

                    <Transition name="expand">
                        <div v-if="expandidos.has(grupo.id_linea_accion)" class="grupo-body">
                            <!-- ── AVANCE ITEM — inline, sin componente ── -->
                            <div v-for="a in grupo.avances" :key="a.id" class="ev-item">

                                <div class="ev-top-row">
                                    <span :class="['ev-estatus', a.estatus === 'Con avances' ? 'ev-estatus--ok' : 'ev-estatus--pend']">
                                        {{ a.estatus }}
                                    </span>
                                    <span class="ev-pct">{{ Math.round((a.avance_cuantitativo ?? 0) * 100) }}%</span>
                                    <span class="ev-fecha">{{ a.fecha_registro }}</span>
                                </div>

                                <div class="ev-valor-row">
                                    <span class="ev-label">Valor alcanzado</span>
                                    <span class="ev-valor">{{ fmtNum(a.avance_cualitativo) }} <span class="ev-unidad">unidades</span></span>
                                </div>

                                <p v-if="a.comentario" class="ev-comentario">{{ a.comentario }}</p>

                                <div v-if="a.medio_verificacion" class="ev-medio">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;flex-shrink:0">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ a.medio_verificacion }}
                                </div>

                                <a v-if="a.archivo_url" :href="a.archivo_url" target="_blank" class="ev-archivo">
                                    <div class="ev-archivo-icon" :style="{ background: colorIcono(a.archivo_tipo) + '18', color: colorIcono(a.archivo_tipo) }">
                                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ev-archivo-info">
                                        <span class="ev-archivo-nombre">{{ a.documento }}</span>
                                        <span v-if="a.archivo_tamanio_formateado" class="ev-archivo-tamanio">{{ a.archivo_tamanio_formateado }}</span>
                                    </div>
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;flex-shrink:0;color:#6b7280">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </a>

                                <a v-if="a.url" :href="a.url" target="_blank" class="ev-url">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;flex-shrink:0">
                                        <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                    </svg>
                                    Ver enlace externo
                                </a>

                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- ── VISTA: CRONOLÓGICO ───────────────────────────────────────── -->
            <div v-if="vista === 'cronologico'">
                <div v-if="cronologicoFiltrado.length === 0" class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--color-gris-300)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>No hay evidencias registradas aún.</p>
                </div>

                <div class="crono-lista">
                    <div v-for="a in cronologicoFiltrado" :key="a.id" class="crono-item">
                        <div class="crono-fecha-col">
                            <span class="crono-fecha">{{ a.fecha_registro }}</span>
                            <span class="crono-linea-vert"></span>
                        </div>
                        <div class="crono-card">
                            <div class="crono-card-head">
                                <span class="badge-pri">P{{ a.numero_prioridad }}</span>
                                <span class="crono-prioridad">{{ truncar(a.prioridad, 70) }}</span>
                            </div>

                            <!-- Mismo bloque inline -->
                            <div class="ev-item" style="border-top:none">

                                <div class="ev-top-row">
                                    <span :class="['ev-estatus', a.estatus === 'Con avances' ? 'ev-estatus--ok' : 'ev-estatus--pend']">
                                        {{ a.estatus }}
                                    </span>
                                    <span class="ev-pct">{{ Math.round((a.avance_cuantitativo ?? 0) * 100) }}%</span>
                                </div>

                                <div class="ev-valor-row">
                                    <span class="ev-label">Valor alcanzado</span>
                                    <span class="ev-valor">{{ fmtNum(a.avance_cualitativo) }}</span>
                                </div>

                                <p v-if="a.comentario" class="ev-comentario">{{ a.comentario }}</p>

                                <div v-if="a.medio_verificacion" class="ev-medio">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;flex-shrink:0">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ a.medio_verificacion }}
                                </div>

                                <a v-if="a.archivo_url" :href="a.archivo_url" target="_blank" class="ev-archivo">
                                    <div class="ev-archivo-icon" :style="{ background: colorIcono(a.archivo_tipo) + '18', color: colorIcono(a.archivo_tipo) }">
                                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ev-archivo-info">
                                        <span class="ev-archivo-nombre">{{ a.documento }}</span>
                                        <span v-if="a.archivo_tamanio_formateado" class="ev-archivo-tamanio">{{ a.archivo_tamanio_formateado }}</span>
                                    </div>
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;flex-shrink:0;color:#6b7280">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </a>

                                <a v-if="a.url" :href="a.url" target="_blank" class="ev-url">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;flex-shrink:0">
                                        <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                    </svg>
                                    Ver enlace externo
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </OrganismoLayout>
</template>

<style scoped>
.page { max-width:1000px; }
.page-head { display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.25rem; }
.page-breadcrumb { font-size:var(--text-xs);color:var(--color-gris-400);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem; }
.page-title { font-family:var(--font-display);font-size:var(--text-2xl);color:var(--color-gris-800); }

.metrics-strip { display:flex;align-items:center;gap:12px;background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);padding:.875rem 1.25rem;margin-bottom:1rem;box-shadow:var(--shadow-sm);flex-wrap:wrap; }
.metric-sep { width:1px;height:28px;background:var(--color-gris-200);flex-shrink:0; }
.metric-chip { display:flex;flex-direction:column;align-items:center;gap:2px;padding:0 8px; }
.metric-chip-val   { font-size:1.25rem;font-weight:700;font-family:var(--font-display);color:var(--color-gris-800);line-height:1; }
.metric-chip-label { font-size:10px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--color-gris-400); }
.metric-chip--rojo .metric-chip-val { color:#dc2626; }
.metric-chip--azul .metric-chip-val { color:#2563eb; }
.metric-chip--gris .metric-chip-val { color:var(--color-gris-600); }

.controles { display:flex;gap:.75rem;align-items:center;margin-bottom:1rem;flex-wrap:wrap; }
.search-bar { flex:1;min-width:240px;display:flex;align-items:center;gap:.75rem;border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.55rem .875rem;background:var(--color-blanco);box-shadow:var(--shadow-sm); }
.search-input { flex:1;border:none;outline:none;font-size:var(--text-sm);color:var(--color-gris-700);background:transparent;font-family:var(--font-body); }
.search-input::placeholder { color:var(--color-gris-400); }
.vista-toggle { display:flex;border:1px solid var(--color-gris-200);border-radius:var(--radius-md);overflow:hidden;background:var(--color-blanco); }
.vista-btn { display:inline-flex;align-items:center;gap:6px;padding:.5rem .875rem;font-size:var(--text-sm);font-weight:500;color:var(--color-gris-500);background:transparent;border:none;cursor:pointer;transition:background var(--transition-base),color var(--transition-base);white-space:nowrap; }
.vista-btn--active { background:var(--color-verde);color:#fff; }
.vista-btn:not(.vista-btn--active):hover { background:var(--color-gris-100);color:var(--color-gris-700); }

.badge-pri { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-sm);font-size:10px;font-weight:700;background:var(--color-magenta-lt);color:var(--color-magenta);white-space:nowrap;flex-shrink:0; }

.grupo-card { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:.75rem;box-shadow:var(--shadow-sm); }
.grupo-head { display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.25rem;cursor:pointer;user-select:none;transition:background var(--transition-base); }
.grupo-head:hover { background:var(--color-gris-50,#fafafa); }
.grupo-titulo { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-800); }
.grupo-count { font-size:12px;font-weight:600;color:var(--color-gris-500);background:var(--color-gris-100);padding:2px 8px;border-radius:var(--radius-full); }
.grupo-body { border-top:1px solid var(--color-gris-200);display:flex;flex-direction:column; }

/* Evidencia item — directo en template */
.ev-item { padding:1rem 1.25rem;display:flex;flex-direction:column;gap:.625rem;border-top:1px solid var(--color-gris-100); }
.ev-item:first-child { border-top:none; }
.ev-top-row { display:flex;align-items:center;gap:.75rem;flex-wrap:wrap; }
.ev-estatus { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:700; }
.ev-estatus--ok   { background:var(--color-verde-lt);color:var(--color-verde); }
.ev-estatus--pend { background:var(--color-magenta-lt);color:var(--color-magenta); }
.ev-pct   { font-size:1rem;font-weight:700;font-family:var(--font-display);color:var(--color-verde); }
.ev-fecha { font-size:11px;color:var(--color-gris-400);margin-left:auto; }
.ev-valor-row { display:flex;flex-direction:column;gap:2px; }
.ev-label { font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400); }
.ev-valor { font-size:var(--text-base);font-weight:700;color:var(--color-gris-800);font-family:var(--font-display); }
.ev-unidad { font-size:var(--text-xs);font-weight:400;color:var(--color-gris-500); }
.ev-comentario { font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.5;background:var(--color-gris-50,#fafafa);padding:.5rem .75rem;border-radius:var(--radius-md);border-left:3px solid var(--color-gris-300); }
.ev-medio { display:flex;align-items:center;gap:5px;font-size:12px;color:var(--color-gris-500); }
.ev-archivo { display:flex;align-items:center;gap:10px;padding:.625rem .875rem;background:var(--color-gris-50,#fafafa);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);text-decoration:none;transition:border-color var(--transition-base); }
.ev-archivo:hover { border-color:var(--color-gris-400); }
.ev-archivo-icon { width:32px;height:32px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.ev-archivo-info { flex:1;display:flex;flex-direction:column;gap:1px;min-width:0; }
.ev-archivo-nombre { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.ev-archivo-tamanio { font-size:11px;color:var(--color-gris-400); }
.ev-url { display:inline-flex;align-items:center;gap:5px;font-size:var(--text-sm);font-weight:600;color:#2563eb;text-decoration:none; }
.ev-url:hover { text-decoration:underline; }

.crono-lista { display:flex;flex-direction:column;gap:.75rem; }
.crono-item { display:flex;gap:1rem;align-items:flex-start; }
.crono-fecha-col { display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:90px; }
.crono-fecha { font-size:11px;font-weight:600;color:var(--color-gris-500);text-align:center;line-height:1.3; }
.crono-linea-vert { width:1px;flex:1;min-height:20px;background:var(--color-gris-200);margin-top:6px; }
.crono-card { flex:1;background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm); }
.crono-card-head { display:flex;align-items:center;gap:8px;padding:.75rem 1rem;border-bottom:1px solid var(--color-gris-200);background:var(--color-gris-50,#fafafa); }
.crono-prioridad { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-700); }

.empty-state { display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:3rem;text-align:center;color:var(--color-gris-500);font-size:var(--text-sm);background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg); }

.expand-enter-active,.expand-leave-active { transition:max-height .25s ease,opacity .2s ease;overflow:hidden;max-height:800px; }
.expand-enter-from,.expand-leave-to { max-height:0;opacity:0; }
</style>
