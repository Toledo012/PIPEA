<script setup>
import OrganismoLayout from '@/Layouts/OrganismoLayout.vue'
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

/**
 * Props que debe mandar UserLineasController:
 *
 * lineas: array de líneas del organismo con:
 *   id, numero_eje, numero_objetivo, numero_prioridad,
 *   prioridad (texto), organismo, nombre_indicador, formula,
 *   meta, linea_base, unidad_medida, sentido_indicador,
 *   porcentaje_avance, ultimo_valor_avance,
 *   puede_reportar (bool),
 *   historial: [ { estatus, avance_cualitativo, avance_cuantitativo,
 *                  fecha_avance, comentario, url, documento,
 *                  archivo_url, fecha_registro, usuario } ]
 *
 * periodo_activo: { nombre, fecha_limite_reporte } | null
 */
const props = defineProps({
    lineas:         { type: Array,  default: () => [] },
    periodo_activo: { type: Object, default: null },
})

// ── Flash ─────────────────────────────────────────────────────────────────────
const page      = usePage()
const flashMsg  = ref(null)
const flashType = ref('success')
let   flashTimer

const mostrarFlash = (msg, tipo = 'success') => {
    flashMsg.value  = msg
    flashType.value = tipo
    clearTimeout(flashTimer)
    flashTimer = setTimeout(() => { flashMsg.value = null }, 5000)
}
watch(() => page.props.flash?.success, v => { if (v) mostrarFlash(v, 'success') })
watch(() => page.props.flash?.error,   v => { if (v) mostrarFlash(v, 'error')   })

// ── Modal detalle / registrar avance ─────────────────────────────────────────
const modalLinea  = ref(false)
const lineaActual = ref(null)
const tabActiva   = ref('detalle') // 'detalle' | 'historial' | 'registrar'

const verLinea = (linea) => {
    lineaActual.value = linea
    tabActiva.value   = 'detalle'
    modalLinea.value  = true
    formAvance.reset()
    formAvance.clearErrors()
}

const cerrarModal = () => {
    modalLinea.value = false
    lineaActual.value = null
    formAvance.reset()
    formAvance.clearErrors()
}

// ── Formulario de avance ──────────────────────────────────────────────────────
const formAvance = useForm({
    estatus:            'Con avances',
    avance_cualitativo: '',
    fecha_avance:       new Date().toISOString().slice(0,10),
    comentario:         '',
    avances_linea:      '',
    medio_verificacion: '',
    url:                '',
    archivo:            null,
})

const archivoNombre = ref('')

const seleccionarArchivo = (e) => {
    const file = e.target.files[0]
    if (!file) return
    formAvance.archivo  = file
    archivoNombre.value = file.name
}

const enviarAvance = () => {
    if (!lineaActual.value) return
    formAvance.post(
        route('organismo.lineas.avances.store', lineaActual.value.id),
        {
            forceFormData: true,
            onSuccess: () => {
                cerrarModal()
            },
        }
    )
}

// ── Filtros ───────────────────────────────────────────────────────────────────
const busqueda      = ref('')
const filtroEstado  = ref('') // '' | 'pendiente' | 'reporto' | 'sin_periodo'

const lineasFiltradas = computed(() =>
    props.lineas.filter(l => {
        const matchQ = !busqueda.value ||
            l.prioridad?.toLowerCase().includes(busqueda.value.toLowerCase()) ||
            l.nombre_indicador?.toLowerCase().includes(busqueda.value.toLowerCase())

        const matchE = !filtroEstado.value ||
            (filtroEstado.value === 'pendiente'   && l.puede_reportar && (l.porcentaje_avance ?? 0) < 100) ||
            (filtroEstado.value === 'reporto'     && (l.porcentaje_avance ?? 0) > 0) ||
            (filtroEstado.value === 'sin_periodo' && !l.puede_reportar)

        return matchQ && matchE
    })
)

// ── Helpers ───────────────────────────────────────────────────────────────────
const fmtNum = (n) => n !== null && n !== undefined && n !== ''
    ? Number(n).toLocaleString('es-MX', { maximumFractionDigits: 4, useGrouping: false })
    : '—'

const colorBarra = (pct) => {
    if (pct >= 100) return 'var(--color-verde)'
    if (pct >= 50)  return 'var(--color-magenta)'
    return 'var(--color-vino)'
}

const avanceEsperado = computed(() => {
    if (!lineaActual.value) return null
    const l   = lineaActual.value
    const meta = Number(l.meta ?? 0)
    const lb   = Number(l.linea_base ?? 0)
    const val  = Number(formAvance.avance_cualitativo)
    if (!meta || !formAvance.avance_cualitativo) return null
    const pct = ((val - lb) / (meta - lb)) * 100
    return Math.round(Math.min(pct, 200) * 100) / 100
})
</script>

<template>
    <OrganismoLayout>

        <Transition name="flash">
            <div v-if="flashMsg" :class="['flash-bar', `flash-bar--${flashType}`]">
                <svg v-if="flashType === 'success'" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <svg v-else viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                {{ flashMsg }}
            </div>
        </Transition>

        <div class="page">

            <!-- Header -->
            <div class="page-head">
                <div>
                    <p class="page-breadcrumb">PI-PEA — Seguimiento</p>
                    <h1 class="page-title">Mis líneas de acción</h1>
                </div>
                <!-- Banner período activo -->
                <div v-if="periodo_activo" class="periodo-banner">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0;color:var(--color-verde)"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>Período abierto: <strong>{{ periodo_activo.nombre }}</strong> — Límite {{ periodo_activo.fecha_limite_reporte }}</span>
                </div>
                <div v-else class="periodo-banner periodo-banner--cerrado">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <span>No hay período de reporte abierto actualmente.</span>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros-bar">
                <div class="search-bar">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px;flex-shrink:0;color:var(--color-gris-400)"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                    <input v-model="busqueda" type="text" placeholder="Buscar por prioridad o indicador..." class="search-input"/>
                    <span class="search-count">{{ lineasFiltradas.length }} de {{ lineas.length }}</span>
                </div>
                <div class="filtro-tabs">
                    <button :class="['tab-btn', filtroEstado === '' ? 'tab-btn--active' : '']" @click="filtroEstado = ''">Todas</button>
                    <button :class="['tab-btn', filtroEstado === 'pendiente' ? 'tab-btn--active' : '']" @click="filtroEstado = 'pendiente'">Pendientes</button>
                    <button :class="['tab-btn', filtroEstado === 'reporto' ? 'tab-btn--active' : '']" @click="filtroEstado = 'reporto'">Con avance</button>
                    <button :class="['tab-btn', filtroEstado === 'sin_periodo' ? 'tab-btn--active' : '']" @click="filtroEstado = 'sin_periodo'">Sin período</button>
                </div>
            </div>

            <!-- Estado vacío -->
            <div v-if="lineasFiltradas.length === 0" class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--color-gris-300)"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p>No hay líneas que coincidan.</p>
            </div>

            <!-- Cards de líneas -->
            <div class="lineas-grid">
                <div
                    v-for="l in lineasFiltradas"
                    :key="l.id"
                    class="linea-card"
                    @click="verLinea(l)"
                >
                    <!-- Badges PI-PEA -->
                    <div class="linea-badges">
                        <span class="badge-num badge-num--vino">{{ String(l.numero_eje).padStart(2,'0') }}</span>
                        <span class="badge-num badge-num--verde">{{ String(l.numero_objetivo).padStart(2,'0') }}</span>
                        <span class="badge-num badge-num--magenta">{{ String(l.numero_prioridad).padStart(2,'0') }}</span>
                        <span class="linea-unidad">{{ l.unidad_medida }}</span>
                        <span class="linea-puede" :class="l.puede_reportar ? 'linea-puede--si' : 'linea-puede--no'">
                            {{ l.puede_reportar ? 'Puede reportar' : 'Sin período' }}
                        </span>
                    </div>

                    <!-- Indicador -->
                    <p class="linea-indicador">{{ l.nombre_indicador }}</p>
                    <p class="linea-prioridad">{{ l.prioridad }}</p>

                    <!-- Barra de avance -->
                    <div class="linea-avance">
                        <div class="avance-track">
                            <div
                                class="avance-fill"
                                :style="{
                                    width: Math.min(l.porcentaje_avance ?? 0, 100) + '%',
                                    background: colorBarra(l.porcentaje_avance ?? 0),
                                }"
                            ></div>
                        </div>
                        <div class="avance-meta-row">
                            <span class="avance-pct">{{ l.porcentaje_avance ?? 0 }}%</span>
                            <span class="avance-meta">
                                {{ fmtNum(l.ultimo_valor_avance) }} / {{ fmtNum(l.meta) }} {{ l.unidad_medida }}
                            </span>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div class="linea-footer">
                        <button
                            v-if="l.puede_reportar"
                            class="btn btn-primary btn-sm btn-block"
                            @click.stop="verLinea(l); tabActiva = 'registrar'"
                        >
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;margin-right:4px"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                            Registrar avance
                        </button>
                        <button v-else class="btn btn-secondary btn-sm btn-block" @click.stop="verLinea(l)">
                            Ver detalle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             MODAL LÍNEA
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="modalLinea" class="modal-overlay" @click.self="cerrarModal">
                <div class="modal modal--xl">

                    <!-- Head -->
                    <div class="modal-head modal-head--ver">
                        <div>
                            <div class="modal-badges">
                                <span class="badge-num badge-num--vino">{{ String(lineaActual?.numero_eje).padStart(2,'0') }}</span>
                                <span class="badge-num badge-num--verde">{{ String(lineaActual?.numero_objetivo).padStart(2,'00') }}</span>
                                <span class="badge-num badge-num--magenta">{{ String(lineaActual?.numero_prioridad).padStart(2,'0') }}</span>
                            </div>
                            <h2 class="modal-title" style="margin-top:6px">{{ lineaActual?.nombre_indicador }}</h2>
                        </div>
                        <button class="modal-close" @click="cerrarModal">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>

                    <!-- Tabs -->
                    <div class="modal-tabs">
                        <button :class="['tab-btn', tabActiva === 'detalle'    ? 'tab-btn--active' : '']" @click="tabActiva = 'detalle'">Detalle</button>
                        <button :class="['tab-btn', tabActiva === 'historial'  ? 'tab-btn--active' : '']" @click="tabActiva = 'historial'">Historial</button>
                        <button
                            v-if="lineaActual?.puede_reportar"
                            :class="['tab-btn tab-btn--cta', tabActiva === 'registrar' ? 'tab-btn--active' : '']"
                            @click="tabActiva = 'registrar'"
                        >
                            + Registrar avance
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body" v-if="lineaActual">

                        <!-- ── TAB DETALLE ── -->
                        <template v-if="tabActiva === 'detalle'">
                            <!-- Avance actual -->
                            <div class="avance-card">
                                <div class="avance-card-head">
                                    <span class="seccion-label">Avance actual</span>
                                    <span class="avance-pct-grande">{{ lineaActual.porcentaje_avance ?? 0 }}%</span>
                                </div>
                                <div class="avance-track-lg">
                                    <div class="avance-fill-lg"
                                         :style="{
                                            width: Math.min(lineaActual.porcentaje_avance ?? 0, 100) + '%',
                                            background: colorBarra(lineaActual.porcentaje_avance ?? 0),
                                        }">
                                    </div>
                                </div>
                                <div class="avance-meta-row" style="margin-top:6px">
                                    <span class="text-xs-muted">Último valor: {{ fmtNum(lineaActual.ultimo_valor_avance) }} {{ lineaActual.unidad_medida }}</span>
                                    <span class="text-xs-muted">Meta: {{ fmtNum(lineaActual.meta) }} {{ lineaActual.unidad_medida }}</span>
                                </div>
                            </div>

                            <!-- Indicador -->
                            <p class="seccion-label" style="margin-top:.5rem">Características del indicador</p>
                            <div class="ver-grid-1">
                                <div class="ver-campo">
                                    <span class="ver-label">Fórmula</span>
                                    <span class="ver-formula">{{ lineaActual.formula ?? '—' }}</span>
                                </div>
                            </div>
                            <div class="ver-grid-3" style="margin-top:10px">
                                <div class="ver-campo">
                                    <span class="ver-label">Línea base</span>
                                    <span class="ver-valor">{{ fmtNum(lineaActual.linea_base) }}</span>
                                </div>
                                <div class="ver-campo">
                                    <span class="ver-label">Meta</span>
                                    <span class="ver-valor ver-valor--destacado">{{ fmtNum(lineaActual.meta) }} {{ lineaActual.unidad_medida }}</span>
                                </div>
                                <div class="ver-campo">
                                    <span class="ver-label">Sentido</span>
                                    <span class="ver-valor">
                                        <span v-if="lineaActual.sentido_indicador === 'Ascendente'" class="chip-sentido chip-sentido--asc">↑ Ascendente</span>
                                        <span v-else-if="lineaActual.sentido_indicador === 'Descendente'" class="chip-sentido chip-sentido--desc">↓ Descendente</span>
                                        <span v-else>—</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Prioridad -->
                            <div class="seccion-sep"></div>
                            <p class="seccion-label">Descripción de la prioridad</p>
                            <p class="prioridad-texto">{{ lineaActual.prioridad }}</p>
                        </template>

                        <!-- ── TAB HISTORIAL ── -->
                        <template v-else-if="tabActiva === 'historial'">
                            <div v-if="!lineaActual.historial?.length" class="empty-state-sm">
                                <p>No hay avances registrados para esta línea.</p>
                            </div>
                            <div v-else class="historial-lista">
                                <div v-for="(h, idx) in lineaActual.historial" :key="idx" class="historial-item">
                                    <div class="historial-item-head">
                                        <div>
                                            <span :class="['badge-estatus', h.estatus === 'Con avances' ? 'badge-estatus--ok' : 'badge-estatus--pend']">
                                                {{ h.estatus }}
                                            </span>
                                            <span class="historial-fecha">{{ h.fecha_avance }}</span>
                                        </div>
                                        <span class="historial-pct">{{ Math.round((h.avance_cuantitativo ?? 0) * 100) }}%</span>
                                    </div>
                                    <p class="historial-comentario">{{ h.comentario }}</p>
                                    <div class="historial-meta">
                                        <span>Valor: <strong>{{ fmtNum(h.avance_cualitativo) }}</strong></span>
                                        <a v-if="h.archivo_url" :href="h.archivo_url" target="_blank" class="historial-archivo">
                                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>
                                            {{ h.documento }}
                                        </a>
                                        <a v-if="h.url" :href="h.url" target="_blank" class="historial-archivo">Enlace externo</a>
                                        <span class="historial-registrado">Registrado: {{ h.fecha_registro }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- ── TAB REGISTRAR AVANCE ── -->
                        <template v-else-if="tabActiva === 'registrar'">
                            <div class="field">
                                <label class="field-label">Estado del avance <span class="field-req">*</span></label>
                                <select v-model="formAvance.estatus" class="field-input">
                                    <option value="Con avances">Con avances</option>
                                    <option value="Por el momento sin avances">Por el momento sin avances</option>
                                </select>
                                <p v-if="formAvance.errors.estatus" class="field-error">{{ formAvance.errors.estatus }}</p>
                            </div>

                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">
                                        Valor alcanzado
                                        <span class="field-hint-inline">en {{ lineaActual.unidad_medida }}</span>
                                    </label>
                                    <input
                                        v-model="formAvance.avance_cualitativo"
                                        type="number"
                                        step="any"
                                        min="0"
                                        class="field-input"
                                        :placeholder="`Ej. ${lineaActual.meta}`"
                                    />
                                    <!-- Preview del % calculado -->
                                    <p v-if="avanceEsperado !== null" class="field-hint">
                                        Esto representa aprox. <strong>{{ avanceEsperado }}%</strong> de cumplimiento
                                    </p>
                                    <p v-if="formAvance.errors.avance_cualitativo" class="field-error">{{ formAvance.errors.avance_cualitativo }}</p>
                                </div>
                                <div class="field">
                                    <label class="field-label">Fecha del avance <span class="field-req">*</span></label>
                                    <input v-model="formAvance.fecha_avance" type="date" class="field-input"/>
                                    <p v-if="formAvance.errors.fecha_avance" class="field-error">{{ formAvance.errors.fecha_avance }}</p>
                                </div>
                            </div>

                            <div class="field">
                                <label class="field-label">Comentario / descripción <span class="field-req">*</span></label>
                                <textarea
                                    v-model="formAvance.comentario"
                                    class="field-input field-textarea"
                                    rows="3"
                                    placeholder="Describe qué se logró, qué actividades se realizaron, observaciones relevantes..."
                                ></textarea>
                                <p v-if="formAvance.errors.comentario" class="field-error">{{ formAvance.errors.comentario }}</p>
                            </div>

                            <div class="field">
                                <label class="field-label">Medio de verificación</label>
                                <input v-model="formAvance.medio_verificacion" type="text" class="field-input" placeholder="Ej. Constancias, acta, portal institucional"/>
                            </div>

                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Archivo de evidencia</label>
                                    <label class="file-upload">
                                        <input type="file" class="file-input" @change="seleccionarArchivo" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"/>
                                        <span class="file-label">
                                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px"><path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                                            {{ archivoNombre || 'Seleccionar archivo' }}
                                        </span>
                                    </label>
                                    <p class="field-hint">PDF, Word, Excel, imagen. Máx 10 MB.</p>
                                </div>
                                <div class="field">
                                    <label class="field-label">Enlace externo</label>
                                    <input v-model="formAvance.url" type="url" class="field-input" placeholder="https://..."/>
                                    <p v-if="formAvance.errors.url" class="field-error">{{ formAvance.errors.url }}</p>
                                </div>
                            </div>

                            <div class="field">
                                <label class="field-label">Notas adicionales</label>
                                <textarea v-model="formAvance.avances_linea" class="field-input field-textarea" rows="2" placeholder="Cambios institucionales, contexto relevante, etc."/>
                            </div>

                            <div class="aviso-inmutable">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                <span>Una vez enviado, el avance <strong>no puede editarse ni eliminarse</strong>. Verifica la información antes de guardar.</span>
                            </div>
                        </template>
                    </div>

                    <!-- Foot -->
                    <div class="modal-foot">
                        <button class="btn btn-ghost" @click="cerrarModal">Cerrar</button>
                        <button
                            v-if="tabActiva === 'registrar' && lineaActual?.puede_reportar"
                            class="btn btn-primary"
                            :disabled="formAvance.processing"
                            @click="enviarAvance"
                        >
                            <svg v-if="formAvance.processing" class="btn-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            {{ formAvance.processing ? 'Guardando...' : 'Guardar avance' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </OrganismoLayout>
</template>

<style scoped>
.flash-bar { position:fixed;top:1.25rem;left:50%;transform:translateX(-50%);z-index:9999;display:flex;align-items:center;gap:.6rem;padding:.65rem 1.25rem;border-radius:var(--radius-lg);font-size:var(--text-sm);font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.15);white-space:nowrap; }
.flash-bar--success { background:var(--color-verde);color:#fff; }
.flash-bar--error   { background:var(--color-vino);color:#fff; }
.flash-enter-active,.flash-leave-active { transition:opacity .25s,transform .25s; }
.flash-enter-from,.flash-leave-to { opacity:0;transform:translateX(-50%) translateY(-8px); }

.page { max-width:1200px; }
.page-head { display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem; }
.page-breadcrumb { font-size:var(--text-xs);color:var(--color-gris-400);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem; }
.page-title { font-family:var(--font-display);font-size:var(--text-2xl);color:var(--color-gris-800); }

.periodo-banner { display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:var(--color-verde-lt);border-radius:var(--radius-md);font-size:var(--text-sm);color:var(--color-gris-700);border:1px solid var(--color-verde); }
.periodo-banner--cerrado { background:var(--color-gris-100);border-color:var(--color-gris-300);color:var(--color-gris-500); }

.filtros-bar { display:flex;gap:.75rem;align-items:center;margin-bottom:1rem;flex-wrap:wrap; }
.search-bar { flex:1;min-width:200px;display:flex;align-items:center;gap:.75rem;background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.6rem 1rem;box-shadow:var(--shadow-sm); }
.search-input { flex:1;border:none;outline:none;font-size:var(--text-sm);color:var(--color-gris-700);background:transparent;font-family:var(--font-body); }
.search-input::placeholder { color:var(--color-gris-400); }
.search-count { font-size:var(--text-xs);color:var(--color-gris-400);white-space:nowrap; }
.filtro-tabs { display:flex;gap:4px;background:var(--color-gris-100);border-radius:var(--radius-md);padding:3px; }
.tab-btn { padding:.35rem .75rem;border-radius:var(--radius-sm);border:none;background:transparent;font-size:var(--text-xs);font-weight:600;color:var(--color-gris-500);cursor:pointer;transition:all var(--transition-base);white-space:nowrap; }
.tab-btn--active { background:var(--color-blanco);color:var(--color-gris-800);box-shadow:var(--shadow-sm); }
.tab-btn--cta { color:var(--color-verde); }

.empty-state { display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:4rem 1rem;color:var(--color-gris-500);font-size:var(--text-sm); }
.empty-state-sm { text-align:center;padding:2rem;color:var(--color-gris-400);font-size:var(--text-sm); }

/* Grid de líneas */
.lineas-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem; }
.linea-card { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);cursor:pointer;display:flex;flex-direction:column;transition:box-shadow var(--transition-base),border-color var(--transition-base); }
.linea-card:hover { box-shadow:var(--shadow-md,0 4px 16px rgba(0,0,0,.1));border-color:var(--color-gris-300); }
.linea-badges { display:flex;align-items:center;gap:.4rem;padding:.75rem 1rem .5rem;flex-wrap:wrap; }
.linea-unidad { font-size:11px;font-weight:600;color:var(--color-gris-500);background:var(--color-gris-100);padding:2px 8px;border-radius:var(--radius-full);margin-left:auto; }
.linea-puede { font-size:11px;font-weight:700;padding:2px 8px;border-radius:var(--radius-full); }
.linea-puede--si  { background:var(--color-verde-lt);color:var(--color-verde); }
.linea-puede--no  { background:var(--color-gris-200);color:var(--color-gris-500); }
.linea-indicador { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-800);padding:0 1rem .25rem;line-height:1.4; }
.linea-prioridad { font-size:var(--text-xs);color:var(--color-gris-500);padding:0 1rem .75rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.linea-avance { padding:0 1rem .75rem; }
.avance-track { width:100%;height:6px;background:var(--color-gris-200);border-radius:var(--radius-full);overflow:hidden; }
.avance-fill  { height:100%;border-radius:var(--radius-full);transition:width .4s ease; }
.avance-meta-row { display:flex;justify-content:space-between;margin-top:4px; }
.avance-pct  { font-size:var(--text-xs);font-weight:700;color:var(--color-gris-700); }
.avance-meta { font-size:11px;color:var(--color-gris-400); }
.linea-footer { padding:.5rem 1rem .75rem;margin-top:auto; }
.btn-block { width:100%;justify-content:center; }

.badge-num { display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:var(--radius-md);font-size:var(--text-xs);font-weight:700;flex-shrink:0; }
.badge-num--vino    { background:var(--color-vino-lt);color:var(--color-vino); }
.badge-num--verde   { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-num--magenta { background:var(--color-magenta-lt);color:var(--color-magenta); }

/* Modal */
.modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:200;padding:1rem; }
.modal { background:var(--color-blanco);border-radius:var(--radius-xl);box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:660px;animation:modal-in .18s ease;max-height:90vh;display:flex;flex-direction:column; }
.modal--xl { max-width:720px; }
@keyframes modal-in { from{opacity:0;transform:translateY(-12px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }
.modal-head { display:flex;align-items:flex-start;justify-content:space-between;padding:1.1rem 1.5rem;border-bottom:1px solid var(--color-gris-200);flex-shrink:0; }
.modal-head--ver { background:var(--color-gris-50,#fafafa);border-radius:var(--radius-xl) var(--radius-xl) 0 0; }
.modal-badges { display:flex;align-items:center;gap:.4rem; }
.modal-title { font-family:var(--font-display);font-size:var(--text-base);color:var(--color-gris-800);font-weight:700; }
.modal-close { width:30px;height:30px;border-radius:var(--radius-md);border:none;background:transparent;cursor:pointer;color:var(--color-gris-400);display:flex;align-items:center;justify-content:center;transition:background var(--transition-base);flex-shrink:0;margin-top:2px; }
.modal-close:hover { background:var(--color-gris-200); }
.modal-tabs { display:flex;gap:0;border-bottom:1px solid var(--color-gris-200);padding:0 1.5rem;flex-shrink:0; }
.modal-tabs .tab-btn { background:transparent;border-radius:0;border-bottom:2px solid transparent;padding:.6rem .75rem;color:var(--color-gris-500); }
.modal-tabs .tab-btn--active { color:var(--color-gris-800);border-bottom-color:var(--color-verde); }
.modal-tabs .tab-btn--cta { color:var(--color-verde); }
.modal-body { padding:1.5rem;display:flex;flex-direction:column;gap:1rem;overflow-y:auto; }
.modal-foot { display:flex;align-items:center;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;border-top:1px solid var(--color-gris-200);flex-shrink:0; }

/* Avance card en detalle */
.avance-card { background:var(--color-gris-50,#fafafa);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:14px 16px; }
.avance-card-head { display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px; }
.avance-pct-grande { font-size:var(--text-2xl);font-weight:700;font-family:var(--font-display);color:var(--color-verde); }
.avance-track-lg { width:100%;height:10px;background:var(--color-gris-200);border-radius:var(--radius-full);overflow:hidden; }
.avance-fill-lg { height:100%;border-radius:var(--radius-full);transition:width .5s ease; }

/* Ver campos */
.seccion-label { font-size:var(--text-xs);font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--color-gris-500); }
.seccion-sep { border-top:1px solid var(--color-gris-200); }
.ver-grid-3 { display:grid;grid-template-columns:repeat(3,1fr);gap:12px; }
.ver-grid-1 { display:grid;grid-template-columns:1fr; }
.ver-campo  { display:flex;flex-direction:column;gap:4px; }
.ver-label  { font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400); }
.ver-valor  { font-size:var(--text-sm);color:var(--color-gris-800);display:flex;align-items:center;flex-wrap:wrap;gap:4px; }
.ver-valor--destacado { font-weight:700; }
.ver-formula { font-family:var(--font-mono,monospace);font-size:12px;background:var(--color-gris-100);padding:8px 12px;border-radius:var(--radius-md);color:var(--color-gris-700);line-height:1.6;border:1px solid var(--color-gris-200); }
.chip-sentido { display:inline-flex;align-items:center;padding:2px 10px;border-radius:var(--radius-full);font-size:12px;font-weight:700; }
.chip-sentido--asc  { background:var(--color-verde-lt);color:var(--color-verde); }
.chip-sentido--desc { background:var(--color-magenta-lt);color:var(--color-magenta); }
.prioridad-texto { font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.6; }
.text-xs-muted { font-size:11px;color:var(--color-gris-400); }

/* Historial */
.historial-lista { display:flex;flex-direction:column;gap:.75rem; }
.historial-item { background:var(--color-gris-50,#fafafa);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.75rem 1rem; }
.historial-item-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem;flex-wrap:wrap;gap:.5rem; }
.badge-estatus { display:inline-flex;align-items:center;padding:.15rem .6rem;border-radius:var(--radius-full);font-size:11px;font-weight:700; }
.badge-estatus--ok   { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-estatus--pend { background:var(--color-magenta-lt);color:var(--color-magenta); }
.historial-fecha { font-size:var(--text-xs);color:var(--color-gris-500);margin-left:.5rem; }
.historial-pct { font-family:var(--font-display);font-size:var(--text-lg);font-weight:700;color:var(--color-verde); }
.historial-comentario { font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.5; }
.historial-meta { display:flex;align-items:center;flex-wrap:wrap;gap:.75rem;margin-top:.4rem;font-size:11px;color:var(--color-gris-500); }
.historial-archivo { display:inline-flex;align-items:center;gap:4px;color:var(--color-verde);font-weight:600;text-decoration:none; }
.historial-archivo:hover { text-decoration:underline; }
.historial-registrado { margin-left:auto; }

/* Form registrar */
.field { display:flex;flex-direction:column;gap:.35rem; }
.field-row { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
.field-label { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-700); }
.field-hint-inline { font-size:var(--text-xs);font-weight:400;color:var(--color-gris-400); }
.field-req { color:var(--color-vino); }
.field-hint { font-size:var(--text-xs);color:var(--color-gris-400); }
.field-error { font-size:var(--text-xs);color:var(--color-vino);font-weight:600; }
.field-textarea { resize:vertical;min-height:80px;font-family:var(--font-body);font-size:var(--text-sm);line-height:1.5; }

.file-upload { display:block;cursor:pointer; }
.file-input { display:none; }
.file-label { display:flex;align-items:center;gap:.5rem;padding:.55rem .85rem;border:1px dashed var(--color-gris-300);border-radius:var(--radius-md);font-size:var(--text-sm);color:var(--color-gris-600);background:var(--color-gris-50,#fafafa);transition:all var(--transition-base);cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.file-label:hover { border-color:var(--color-verde);color:var(--color-verde); }

.aviso-inmutable { display:flex;align-items:flex-start;gap:.5rem;padding:.65rem .85rem;background:var(--color-vino-lt);border-radius:var(--radius-md);font-size:var(--text-xs);color:var(--color-vino);line-height:1.5;border:1px solid var(--color-vino); }

.btn-spin { animation:spin .8s linear infinite; }
@keyframes spin { to{transform:rotate(360deg)} }
</style>
