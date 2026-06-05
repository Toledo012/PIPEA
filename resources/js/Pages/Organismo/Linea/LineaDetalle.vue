<script setup>
import { Link } from '@inertiajs/vue3'
import OrganismoLayout from '@/Layouts/OrganismoLayout.vue'

const props = defineProps({
    linea:          { type: Object, default: () => ({}) },
    historial:      { type: Array,  default: () => [] },
    periodo_activo: { type: Object, default: null },
})

// ── Helpers ───────────────────────────────────────────────────────────────────
const truncar = (texto, max = 120) =>
    texto?.length > max ? texto.slice(0, max) + '…' : texto

const fmtNum = (n) =>
    n !== null && n !== undefined && n !== ''
        ? Number(n).toLocaleString('es-MX', { maximumFractionDigits: 4, useGrouping: false })
        : '—'

// ── Ruta al panel con auto-open del modal de registro ───────────────────────
const rutaRegistrar = `${route('organismo.lineas.index')}?registrar=${props.linea.id}`
</script>

<template>
    <OrganismoLayout>
        <div class="detalle">

            <!-- Navegación -->
            <div class="breadcrumb-nav">
                <Link :href="route('organismo.lineas.index')" class="back-link">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Volver a mis líneas
                </Link>
            </div>

            <!-- Banner período -->
            <div v-if="periodo_activo && linea.puede_reportar" class="periodo-banner">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;flex-shrink:0">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span v-if="periodo_activo.estado === 'en_prorroga'">
                    Período en prórroga: <strong>{{ periodo_activo.nombre }}</strong> — Tu línea tiene plazo extendido
                </span>
                <span v-else>
                    Período abierto: <strong>{{ periodo_activo.nombre }}</strong> — Límite {{ periodo_activo.fecha_limite_reporte }}
                </span>
            </div>
            <div v-else class="periodo-banner periodo-banner--cerrado">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;flex-shrink:0">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span v-if="periodo_activo && !linea.puede_reportar">
                    Período cerrado — El plazo de reporte venció el {{ periodo_activo.fecha_limite_reporte }}.
                </span>
                <span v-else>
                    No hay período de reporte abierto actualmente.
                </span>
            </div>

            <!-- Header completo: badges + título + estatus -->
            <div class="linea-header">
                <div class="linea-badges">
                    <span class="badge-eje">Eje {{ String(linea.numero_eje).padStart(2,'0') }}</span>
                    <span class="badge-obj">Obj. {{ String(linea.numero_objetivo).padStart(2,'0') }}</span>
                    <span class="badge-pri">Prioridad {{ linea.numero_prioridad }}</span>
                    <span v-if="linea.frecuencia" class="badge-frec">{{ linea.frecuencia }}</span>
                    <span v-if="linea.plazo" class="badge-plazo">{{ linea.plazo }}</span>
                </div>
                <h1 class="linea-titulo">{{ linea.nombre_indicador ?? '—' }}</h1>
                <p v-if="linea.estatus" class="linea-estatus">{{ linea.estatus }}</p>
            </div>

            <div class="content-grid">

                <!-- Columna izquierda -->
                <div class="col-info">

                    <!-- Características del indicador -->
                    <div class="card">
                        <p class="card-title">Características del indicador</p>

                        <div class="info-grid-3">
                            <div class="info-row">
                                <span class="info-label">Meta</span>
                                <span class="info-val info-val--bold">{{ fmtNum(linea.meta) }} {{ linea.unidad_medida }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Línea base</span>
                                <span class="info-val info-val--bold">{{ fmtNum(linea.linea_base) }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Sentido</span>
                                <span class="info-val">
                                    <span v-if="linea.sentido_indicador === 'Ascendente'" class="chip-asc">↑ Ascendente</span>
                                    <span v-else-if="linea.sentido_indicador === 'Descendente'" class="chip-desc">↓ Descendente</span>
                                    <span v-else>—</span>
                                </span>
                            </div>
                        </div>

                        <div v-if="linea.formula" class="info-row" style="margin-top:.875rem">
                            <span class="info-label">Fórmula de cálculo</span>
                            <p class="info-formula">{{ linea.formula }}</p>
                        </div>

                        <div v-if="linea.unidad_medida" class="info-row" style="margin-top:.875rem">
                            <span class="info-label">Unidad de medida</span>
                            <span class="info-val">{{ linea.unidad_medida }}</span>
                        </div>
                    </div>

                    <!-- Avance actual -->
                    <div class="card card--avance">
                        <div class="avance-header">
                            <div>
                                <p class="card-title" style="margin-bottom:0">Avance actual</p>
                                <p class="avance-sub">
                                    {{ fmtNum(linea.ultimo_valor_avance) }} de {{ fmtNum(linea.meta) }} {{ linea.unidad_medida }}
                                </p>
                            </div>
                            <span class="avance-pct-grande">{{ linea.porcentaje_avance ?? 0 }}%</span>
                        </div>
                        <div class="avance-track">
                            <div
                                class="avance-fill"
                                :class="(linea.porcentaje_avance ?? 0) >= 75 ? 'fill--verde' : (linea.porcentaje_avance ?? 0) >= 40 ? 'fill--amarillo' : 'fill--rojo'"
                                :style="{ width: Math.min(linea.porcentaje_avance ?? 0, 100) + '%' }"
                            ></div>
                        </div>

                        <!-- CTA: redirige al panel con auto-open del modal -->
                        <Link
                            v-if="linea.puede_reportar && !linea.ya_reporto"
                            :href="rutaRegistrar"
                            class="btn-registrar"
                        >
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                            </svg>
                            Registrar avance
                        </Link>

                        <!-- Ya reportó -->
                        <div v-else-if="linea.ya_reporto" class="aviso-reportado">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Ya registraste avance en este período.</span>
                        </div>

                        <!-- Período cerrado -->
                        <div v-else class="periodo-cerrado-notice">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                            </svg>
                            <span>Período cerrado — el registro de avances no está disponible.</span>
                        </div>
                    </div>

                    <!-- Mi avance del período (inline, si ya reportó) -->
                    <div v-if="linea.ya_reporto && linea.reporte_actual" class="card card--reporte">
                        <div class="reporte-head">
                            <p class="card-title" style="margin:0">Mi avance — Período actual</p>
                            <span :class="['badge-estatus', linea.reporte_actual.estatus === 'Con avances' ? 'badge-estatus--ok' : 'badge-estatus--pend']">
                                {{ linea.reporte_actual.estatus }}
                            </span>
                        </div>
                        <div class="reporte-grid">
                            <div>
                                <span class="reporte-label">Valor alcanzado</span>
                                <span class="reporte-val">{{ fmtNum(linea.reporte_actual.avance_cualitativo) }} {{ linea.unidad_medida }}</span>
                            </div>
                            <div>
                                <span class="reporte-label">Fecha</span>
                                <span class="reporte-val">{{ linea.reporte_actual.fecha_avance }}</span>
                            </div>
                            <div>
                                <span class="reporte-label">% Cumplimiento</span>
                                <span class="reporte-val reporte-val--pct">{{ Math.round((linea.reporte_actual.avance_cuantitativo ?? 0) * 100) }}%</span>
                            </div>
                        </div>
                        <div class="reporte-comentario">
                            <span class="reporte-label">Comentario</span>
                            <p>{{ linea.reporte_actual.comentario }}</p>
                        </div>
                        <div v-if="linea.reporte_actual.medio_verificacion" class="reporte-comentario">
                            <span class="reporte-label">Medio de verificación</span>
                            <p>{{ linea.reporte_actual.medio_verificacion }}</p>
                        </div>
                        <div v-if="linea.reporte_actual.archivo_url || linea.reporte_actual.url" class="reporte-links">
                            <a v-if="linea.reporte_actual.archivo_url" :href="linea.reporte_actual.archivo_url" target="_blank" class="reporte-link">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                                {{ linea.reporte_actual.documento ?? 'Descargar archivo' }}
                            </a>
                            <a v-if="linea.reporte_actual.url" :href="linea.reporte_actual.url" target="_blank" class="reporte-link">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px"><path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/></svg>
                                Ver enlace externo
                            </a>
                        </div>
                    </div>

                </div>

                <!-- Columna derecha: historial -->
                <div class="col-historial">
                    <div class="card">
                        <p class="card-title">Historial de avances</p>

                        <div v-if="historial.length === 0" class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--color-gris-300)">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p>Aún no hay avances registrados.</p>
                            <Link v-if="linea.puede_reportar" :href="rutaRegistrar" class="btn-registrar-sm">
                                Registrar primer avance
                            </Link>
                        </div>

                        <div v-for="h in historial" :key="h.id" class="hist-item">
                            <div class="hist-top">
                                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                                    <span :class="['badge-estatus', h.estatus === 'Con avances' ? 'badge-estatus--ok' : 'badge-estatus--pend']">
                                        {{ h.estatus }}
                                    </span>
                                    <span class="hist-fecha">{{ h.fecha_avance }}</span>
                                </div>
                                <span class="hist-pct">{{ Math.round((h.avance_cuantitativo ?? 0) * 100) }}%</span>
                            </div>

                            <p v-if="h.comentario" class="hist-comentario-principal">{{ h.comentario }}</p>

                            <div class="hist-meta-row">
                                <span class="hist-valor">
                                    Valor: <strong>{{ fmtNum(h.avance_cualitativo) }} {{ linea.unidad_medida }}</strong>
                                </span>
                                <span v-if="h.medio_verificacion" class="hist-evidencia-item">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:11px;height:11px">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ h.medio_verificacion }}
                                </span>
                            </div>

                            <div v-if="h.archivo_url || h.url" class="hist-links">
                                <a v-if="h.archivo_url" :href="h.archivo_url" target="_blank" class="hist-link">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ h.documento ?? 'Ver archivo' }}
                                </a>
                                <a v-if="h.url" :href="h.url" target="_blank" class="hist-link">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px">
                                        <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                    </svg>
                                    Ver enlace
                                </a>
                            </div>

                            <p v-if="h.avances_linea" class="hist-nota">{{ truncar(h.avances_linea) }}</p>

                            <p class="hist-registrado">
                                Registrado {{ h.fecha_registro }}
                                <span v-if="h.usuario"> · {{ h.usuario }}</span>
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </OrganismoLayout>
</template>

<style scoped>
.detalle { max-width:1100px; }

.breadcrumb-nav { margin-bottom:.75rem; }
.back-link { display:inline-flex;align-items:center;gap:6px;font-size:var(--text-sm);color:var(--color-gris-500);text-decoration:none;transition:color var(--transition-base); }
.back-link:hover { color:var(--color-gris-800); }

.periodo-banner { display:flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:var(--color-verde-lt);border-radius:var(--radius-md);font-size:var(--text-sm);color:var(--color-gris-700);border:1px solid var(--color-verde);margin-bottom:1.25rem; }
.periodo-banner--cerrado { background:var(--color-gris-100);border-color:var(--color-gris-300);color:var(--color-gris-500); }

.linea-header { margin-bottom:1.5rem; }
.linea-badges { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:.75rem; }
.badge-eje   { display:inline-flex;align-items:center;padding:3px 10px;border-radius:var(--radius-sm);font-size:11px;font-weight:700;background:var(--color-vino-lt);color:var(--color-vino); }
.badge-obj   { display:inline-flex;align-items:center;padding:3px 10px;border-radius:var(--radius-sm);font-size:11px;font-weight:700;background:var(--color-verde-lt);color:var(--color-verde); }
.badge-pri   { display:inline-flex;align-items:center;padding:3px 10px;border-radius:var(--radius-sm);font-size:11px;font-weight:700;background:var(--color-magenta-lt);color:var(--color-magenta); }
.badge-frec  { display:inline-flex;align-items:center;padding:3px 10px;border-radius:var(--radius-sm);font-size:11px;font-weight:600;background:var(--color-gris-100);color:var(--color-gris-600); }
.badge-plazo { display:inline-flex;align-items:center;padding:3px 10px;border-radius:var(--radius-sm);font-size:11px;font-weight:600;background:var(--color-gris-100);color:var(--color-gris-600); }
.linea-titulo  { font-family:var(--font-display);font-size:var(--text-xl);color:var(--color-gris-800);line-height:1.4;margin-bottom:.5rem; }
.linea-estatus { font-size:12px;color:var(--color-gris-500);background:var(--color-gris-100);display:inline-block;padding:2px 10px;border-radius:var(--radius-sm); }

.content-grid { display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start; }

.card { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);padding:1.25rem;box-shadow:var(--shadow-sm);margin-bottom:1rem; }
.card:last-child { margin-bottom:0; }
.card-title { font-size:var(--text-xs);font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--color-gris-500);margin-bottom:.875rem; }
.card--avance  { border-left:3px solid var(--color-verde); }
.card--reporte { border-left:3px solid var(--color-verde); }

.info-row { display:flex;flex-direction:column;gap:3px;margin-bottom:.875rem; }
.info-row:last-child { margin-bottom:0; }
.info-grid-3 { display:grid;grid-template-columns:repeat(3,1fr);gap:12px; }
.info-label { font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400); }
.info-val { font-size:var(--text-sm);color:var(--color-gris-800); }
.info-val--bold { font-weight:600; }
.info-formula { font-family:var(--font-mono,monospace);font-size:12px;background:var(--color-gris-100);padding:8px 12px;border-radius:var(--radius-md);color:var(--color-gris-700);line-height:1.6;border:1px solid var(--color-gris-200);margin:0; }
.chip-asc  { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:700;background:var(--color-verde-lt);color:var(--color-verde); }
.chip-desc { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:700;background:var(--color-magenta-lt);color:var(--color-magenta); }

.avance-header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.875rem; }
.avance-sub { font-size:12px;color:var(--color-gris-400);margin-top:2px; }
.avance-pct-grande { font-size:2rem;font-weight:700;font-family:var(--font-display);color:var(--color-verde); }
.avance-track { width:100%;height:8px;background:var(--color-gris-200);border-radius:var(--radius-full);overflow:hidden;margin-bottom:1rem; }
.avance-fill { height:100%;border-radius:var(--radius-full);transition:width .5s ease; }
.fill--verde    { background:var(--color-verde); }
.fill--amarillo { background:#d97706; }
.fill--rojo     { background:var(--color-vino); }

.btn-registrar { width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:.75rem;background:var(--color-verde);color:#fff;border:none;border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:600;cursor:pointer;transition:opacity var(--transition-base);text-decoration:none; }
.btn-registrar:hover { opacity:.9; }
.btn-registrar-sm { display:inline-flex;align-items:center;gap:6px;font-size:var(--text-sm);font-weight:600;color:var(--color-verde);background:var(--color-verde-lt);padding:.5rem 1rem;border-radius:var(--radius-md);border:none;cursor:pointer;margin-top:.5rem;text-decoration:none; }

.aviso-reportado { display:flex;align-items:center;gap:.5rem;padding:.65rem .85rem;background:var(--color-verde-lt);border:1px solid var(--color-verde);border-radius:var(--radius-md);font-size:var(--text-sm);color:var(--color-verde);font-weight:600; }
.periodo-cerrado-notice { display:flex;align-items:flex-start;gap:.5rem;padding:.75rem;background:var(--color-gris-100);border:1px solid var(--color-gris-300);border-radius:var(--radius-md);font-size:var(--text-sm);color:var(--color-gris-500);line-height:1.5; }

/* Card mi avance del período */
.reporte-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:.875rem;flex-wrap:wrap;gap:.5rem; }
.reporte-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:.875rem; }
.reporte-grid > div { display:flex;flex-direction:column;gap:2px; }
.reporte-label { font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400); }
.reporte-val { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-800); }
.reporte-val--pct { font-size:var(--text-base);font-family:var(--font-display);color:var(--color-verde); }
.reporte-comentario { display:flex;flex-direction:column;gap:3px;margin-bottom:.75rem; }
.reporte-comentario p { font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.5;margin:0; }
.reporte-links { display:flex;gap:.5rem;flex-wrap:wrap; }
.reporte-link { display:inline-flex;align-items:center;gap:5px;font-size:var(--text-xs);font-weight:600;color:#1a73e8;text-decoration:none;padding:.3rem .65rem;background:#e8f0fe;border-radius:var(--radius-md); }
.reporte-link:hover { text-decoration:underline; }

/* Historial */
.hist-item { padding:.875rem 0;border-bottom:1px solid var(--color-gris-100); }
.hist-item:last-child { border-bottom:none;padding-bottom:0; }
.hist-top { display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;flex-wrap:wrap;gap:4px; }
.badge-estatus { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:700; }
.badge-estatus--ok   { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-estatus--pend { background:var(--color-magenta-lt);color:var(--color-magenta); }
.hist-fecha { font-size:11px;color:var(--color-gris-400); }
.hist-pct   { font-size:var(--text-base);font-weight:700;color:var(--color-verde);font-family:var(--font-display); }
.hist-comentario-principal { font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.5;margin-bottom:5px; }
.hist-meta-row { display:flex;align-items:center;flex-wrap:wrap;gap:.75rem;margin-bottom:4px; }
.hist-valor { font-size:12px;color:var(--color-gris-600); }
.hist-evidencia-item { display:inline-flex;align-items:center;gap:4px;font-size:11px;color:var(--color-gris-500); }
.hist-links { display:flex;align-items:center;gap:.75rem;margin-bottom:4px;flex-wrap:wrap; }
.hist-link { display:inline-flex;align-items:center;gap:4px;font-size:11px;color:#1a73e8;text-decoration:none;font-weight:600; }
.hist-link:hover { text-decoration:underline; }
.hist-nota { font-size:11px;color:var(--color-gris-500);background:var(--color-gris-50,#fafafa);padding:4px 8px;border-radius:var(--radius-sm);margin-bottom:4px; }
.hist-registrado { font-size:11px;color:var(--color-gris-400);font-style:italic; }

.empty-state { display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:2rem;text-align:center;color:var(--color-gris-500);font-size:var(--text-sm); }

@media (max-width: 900px) {
    .content-grid { grid-template-columns:1fr; }
}
</style>
