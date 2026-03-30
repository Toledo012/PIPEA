<script setup>
import { ref, computed } from 'vue'
import { useForm, Link, usePage, router } from '@inertiajs/vue3'
import OrganismoLayout from '@/Layouts/OrganismoLayout.vue'

const props = defineProps({
    linea:          { type: Object, default: () => ({}) },
    historial:      { type: Array,  default: () => [] },
    periodo_activo: { type: Object, default: null },
})

// ── Modal preview reporte ya enviado ─────────────────────────────────────────
const modalPreview = ref(false)

// ── Modal registrar avance ────────────────────────────────────────────────────
const modalAvance  = ref(false)
const archivoNombre = ref('')

const form = useForm({
    estatus:            'Con avances',
    avance_cualitativo: '',   // valor bruto en unidades de la meta
    fecha_avance:       new Date().toISOString().slice(0, 10),
    comentario:         '',
    avances_linea:      '',
    medio_verificacion: '',
    url:                '',
    archivo:            null,
})

const abrirModal = () => {
    form.reset()
    form.clearErrors()
    form.fecha_avance   = new Date().toISOString().slice(0, 10)
    form.estatus        = 'Con avances'
    archivoNombre.value = ''
    modalAvance.value   = true
}

const cerrar = () => {
    modalAvance.value   = false
    archivoNombre.value = ''
    form.reset()
    form.clearErrors()
}

const seleccionarArchivo = (e) => {
    const file = e.target.files[0]
    if (!file) return
    form.archivo        = file
    archivoNombre.value = file.name
}

const registrar = () => {
    form.post(
        route('organismo.lineas.avances.store', props.linea.id),
        {
            forceFormData: true,   // necesario para enviar el archivo
            onSuccess: () => {
                cerrar()
                // Inertia recarga la página automáticamente con los nuevos datos
            },
        }
    )
}

// ── Preview del % que resultará ───────────────────────────────────────────────
const avanceEsperado = computed(() => {
    const val  = Number(form.avance_cualitativo)
    const meta = Number(props.linea.meta ?? 0)
    const lb   = Number(props.linea.linea_base ?? 0)
    if (!form.avance_cualitativo || !meta) return null
    const rango = meta - lb
    if (rango <= 0) return null
    return Math.round(((val - lb) / rango) * 10000) / 100
})

// ── Helpers ───────────────────────────────────────────────────────────────────
const truncar = (texto, max = 120) =>
    texto?.length > max ? texto.slice(0, max) + '…' : texto

const fmtNum = (n) =>
    n !== null && n !== undefined && n !== ''
        ? Number(n).toLocaleString('es-MX', { maximumFractionDigits: 4, useGrouping: false })
        : '—'
</script>

<template>
    <OrganismoLayout>
        <div class="detalle">

            <!-- Navegación -->
            <div class="breadcrumb-nav">
                <Link :href="route('organismo.dashboard')" class="back-link">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Volver al dashboard
                </Link>
            </div>

            <!-- Banner período -->
            <div v-if="periodo_activo" class="periodo-banner">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;flex-shrink:0">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Período abierto: <strong>{{ periodo_activo.nombre }}</strong> — Límite {{ periodo_activo.fecha_limite_reporte }}
            </div>
            <div v-else class="periodo-banner periodo-banner--cerrado">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;flex-shrink:0">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                No hay período de reporte abierto actualmente.
            </div>

            <!-- Header -->
            <div class="linea-header">
                <div class="linea-badges">
                    <span class="badge-eje">Eje {{ String(linea.numero_eje).padStart(2,'0') }}</span>
                    <span class="badge-obj">Obj. {{ String(linea.numero_objetivo).padStart(2,'00') }}</span>
                    <span class="badge-pri">Prioridad {{ linea.numero_prioridad }}</span>
                    <span v-if="linea.frecuencia" class="badge-frec">{{ linea.frecuencia }}</span>
                    <span v-if="linea.plazo" class="badge-plazo">{{ linea.plazo }}</span>
                </div>
                <h1 class="linea-titulo">{{ linea.prioridad ?? '—' }}</h1>
                <p v-if="linea.estatus" class="linea-estatus">{{ linea.estatus }}</p>
            </div>

            <div class="content-grid">

                <!-- Columna izquierda -->
                <div class="col-info">

                    <!-- Indicador -->
                    <div class="card">
                        <p class="card-title">Características del indicador</p>
                        <div class="info-row">
                            <span class="info-label">Nombre del indicador</span>
                            <span class="info-val info-val--bold">{{ linea.nombre_indicador ?? '—' }}</span>
                        </div>
                        <div class="info-row" v-if="linea.formula">
                            <span class="info-label">Fórmula</span>
                            <span class="info-formula">{{ linea.formula }}</span>
                        </div>
                        <div class="info-grid-3">
                            <div class="info-row">
                                <span class="info-label">Línea base</span>
                                <span class="info-val">{{ fmtNum(linea.linea_base) }}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Meta</span>
                                <span class="info-val info-val--bold">{{ fmtNum(linea.meta) }} {{ linea.unidad_medida }}</span>
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

                        <!-- Si ya reportó: botón ver reporte -->
                        <button v-if="linea.ya_reporto" class="btn-ver-reporte" @click="modalPreview = true">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            Ver mi reporte enviado
                        </button>

                        <!-- Si no ha reportado: botón registrar -->
                        <button v-else class="btn-registrar" @click="abrirModal">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                            </svg>
                            Registrar avance
                        </button>
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
                            <button v-if="linea.puede_reportar" class="btn-registrar-sm" @click="abrirModal">
                                Registrar primer avance
                            </button>
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

                            <!-- Archivo y enlace -->
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

            <!-- ══════════════════════════════════════════════════════════
                 MODAL REGISTRAR AVANCE
            ══════════════════════════════════════════════════════════ -->
            <Teleport to="body">
                <div v-if="modalAvance" class="modal-overlay" @click.self="cerrar">
                    <div class="modal">
                        <div class="modal-head">
                            <div>
                                <h2 class="modal-title">Registrar avance</h2>
                                <p class="modal-sub">
                                    Prioridad {{ linea.numero_prioridad }}
                                    <span v-if="linea.frecuencia"> · {{ linea.frecuencia }}</span>
                                    <span v-if="periodo_activo"> · {{ periodo_activo.nombre }}</span>
                                </p>
                            </div>
                            <button class="modal-close" @click="cerrar">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>

                        <div class="modal-body">

                            <!-- Contexto -->
                            <div class="modal-context">
                                <p class="context-label">Indicador</p>
                                <p class="context-val">{{ linea.nombre_indicador ?? '—' }}</p>
                                <p v-if="linea.formula" class="context-formula">{{ linea.formula }}</p>
                                <div class="context-meta">
                                    <span>Meta: <strong>{{ fmtNum(linea.meta) }} {{ linea.unidad_medida }}</strong></span>
                                    <span>Línea base: <strong>{{ fmtNum(linea.linea_base) }}</strong></span>
                                    <span>Avance actual: <strong>{{ linea.porcentaje_avance ?? 0 }}%</strong></span>
                                </div>
                            </div>

                            <!-- Estatus -->
                            <div class="field">
                                <label class="field-label">Estado del avance <span class="field-req">*</span></label>
                                <select v-model="form.estatus" class="field-input">
                                    <option value="Con avances">Con avances</option>
                                    <option value="Por el momento sin avances">Por el momento sin avances</option>
                                </select>
                                <p v-if="form.errors.estatus" class="field-error">{{ form.errors.estatus }}</p>
                            </div>

                            <!-- Valor + fecha -->
                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">
                                        Valor alcanzado
                                        <span class="field-hint-inline">en {{ linea.unidad_medida }}</span>
                                    </label>
                                    <div class="field-with-unit">
                                        <input
                                            v-model="form.avance_cualitativo"
                                            type="number"
                                            step="any"
                                            min="0"
                                            class="field-input"
                                            :class="{ 'field-input--error': form.errors.avance_cualitativo }"
                                            :placeholder="`Ej. ${linea.meta}`"
                                        />
                                        <span class="unit-badge">{{ linea.unidad_medida ?? 'u' }}</span>
                                    </div>
                                    <!-- Preview del % -->
                                    <p v-if="avanceEsperado !== null" class="field-hint field-hint--preview">
                                        Esto representa aprox. <strong>{{ avanceEsperado }}%</strong> de cumplimiento
                                    </p>
                                    <p v-if="form.errors.avance_cualitativo" class="field-error">{{ form.errors.avance_cualitativo }}</p>
                                </div>
                                <div class="field">
                                    <label class="field-label">Fecha del avance <span class="field-req">*</span></label>
                                    <input v-model="form.fecha_avance" type="date" class="field-input" :class="{ 'field-input--error': form.errors.fecha_avance }"/>
                                    <p v-if="form.errors.fecha_avance" class="field-error">{{ form.errors.fecha_avance }}</p>
                                </div>
                            </div>

                            <!-- Comentario -->
                            <div class="field">
                                <label class="field-label">Comentario / descripción <span class="field-req">*</span></label>
                                <textarea
                                    v-model="form.comentario"
                                    class="field-input field-textarea"
                                    :class="{ 'field-input--error': form.errors.comentario }"
                                    rows="3"
                                    placeholder="Describe qué se logró, qué actividades se realizaron, observaciones relevantes..."
                                ></textarea>
                                <p class="field-hint">{{ form.comentario?.length ?? 0 }} / 2,000 caracteres</p>
                                <p v-if="form.errors.comentario" class="field-error">{{ form.errors.comentario }}</p>
                            </div>

                            <div class="seccion-sep"></div>
                            <p class="seccion-label">Evidencia (opcional)</p>

                            <!-- Medio de verificación -->
                            <div class="field">
                                <label class="field-label">Medio de verificación</label>
                                <input
                                    v-model="form.medio_verificacion"
                                    type="text"
                                    class="field-input"
                                    placeholder="Ej. Acta de sesión, informe trimestral, oficio..."
                                />
                            </div>

                            <!-- Archivo + URL -->
                            <div class="field-row">
                                <div class="field">
                                    <label class="field-label">Archivo de evidencia</label>
                                    <label class="file-upload">
                                        <input
                                            type="file"
                                            class="file-input"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                            @change="seleccionarArchivo"
                                        />
                                        <span class="file-label">
                                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px">
                                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ archivoNombre || 'Seleccionar archivo' }}
                                        </span>
                                    </label>
                                    <p class="field-hint">PDF, Word, Excel, imagen. Máx 10 MB.</p>
                                </div>
                                <div class="field">
                                    <label class="field-label">Enlace externo</label>
                                    <input
                                        v-model="form.url"
                                        type="url"
                                        class="field-input"
                                        :class="{ 'field-input--error': form.errors.url }"
                                        placeholder="https://..."
                                    />
                                    <p v-if="form.errors.url" class="field-error">{{ form.errors.url }}</p>
                                </div>
                            </div>

                            <!-- Notas adicionales -->
                            <div class="field">
                                <label class="field-label">Notas adicionales</label>
                                <textarea
                                    v-model="form.avances_linea"
                                    class="field-input field-textarea"
                                    rows="2"
                                    placeholder="Cambios institucionales, contexto relevante, decretos, etc."
                                ></textarea>
                            </div>

                            <!-- Aviso sin período — visible pero no bloquea el formulario -->
                            <div v-if="!linea.puede_reportar" class="aviso-sin-periodo">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span>No hay período de reporte habilitado actualmente. Puedes preparar el avance pero <strong>no podrás guardarlo</strong> hasta que el administrador abra un período.</span>
                            </div>

                            <!-- Aviso inmutabilidad -->
                            <div class="aviso-inmutable">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>Una vez enviado, el avance <strong>no puede editarse ni eliminarse</strong>. Verifica la información antes de guardar.</span>
                            </div>

                        </div>

                        <div class="modal-foot">
                            <button class="btn btn-ghost" @click="cerrar" :disabled="form.processing">Cancelar</button>
                            <button class="btn btn-primary" @click="registrar" :disabled="form.processing || !linea.puede_reportar">
                                <svg v-if="form.processing" class="btn-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px">
                                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                </svg>
                                {{ form.processing ? 'Guardando...' : 'Guardar avance' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- ══════════════════════════════════════════════════════════
                 MODAL PREVIEW — VER REPORTE YA ENVIADO
            ══════════════════════════════════════════════════════════ -->
            <Teleport to="body">
                <div v-if="modalPreview && linea.reporte_actual" class="modal-overlay" @click.self="modalPreview = false">
                    <div class="modal">
                        <div class="modal-head">
                            <div>
                                <h2 class="modal-title">Mi reporte enviado</h2>
                                <p class="modal-sub">
                                    Prioridad {{ linea.numero_prioridad }}
                                    <span v-if="periodo_activo"> · {{ periodo_activo.nombre }}</span>
                                </p>
                            </div>
                            <button class="modal-close" @click="modalPreview = false">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>

                        <div class="modal-body">
                            <!-- Badge enviado -->
                            <div class="preview-enviado">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Reporte enviado — no puede modificarse</span>
                            </div>

                            <!-- Estatus y fecha -->
                            <div class="preview-row">
                                <div class="preview-campo">
                                    <span class="preview-label">Estado</span>
                                    <span :class="['badge-estatus', linea.reporte_actual.estatus === 'Con avances' ? 'badge-estatus--ok' : 'badge-estatus--pend']">
                                        {{ linea.reporte_actual.estatus }}
                                    </span>
                                </div>
                                <div class="preview-campo">
                                    <span class="preview-label">Fecha del avance</span>
                                    <span class="preview-val">{{ linea.reporte_actual.fecha_avance }}</span>
                                </div>
                            </div>

                            <!-- Valor alcanzado -->
                            <div class="preview-campo">
                                <span class="preview-label">Valor alcanzado</span>
                                <span class="preview-val preview-val--grande">
                                    {{ fmtNum(linea.reporte_actual.avance_cualitativo) }}
                                    <span class="preview-unidad">{{ linea.unidad_medida }}</span>
                                </span>
                                <span class="preview-sub">
                                    {{ Math.round((linea.reporte_actual.avance_cuantitativo ?? 0) * 100) }}% de cumplimiento
                                    · Meta: {{ fmtNum(linea.meta) }} {{ linea.unidad_medida }}
                                </span>
                            </div>

                            <!-- Comentario -->
                            <div class="preview-campo">
                                <span class="preview-label">Comentario</span>
                                <p class="preview-comentario">{{ linea.reporte_actual.comentario }}</p>
                            </div>

                            <!-- Medio de verificación -->
                            <div v-if="linea.reporte_actual.medio_verificacion" class="preview-campo">
                                <span class="preview-label">Medio de verificación</span>
                                <span class="preview-val">{{ linea.reporte_actual.medio_verificacion }}</span>
                            </div>

                            <!-- Archivo y enlace -->
                            <div v-if="linea.reporte_actual.archivo_url || linea.reporte_actual.url" class="preview-evidencias">
                                <a v-if="linea.reporte_actual.archivo_url" :href="linea.reporte_actual.archivo_url" target="_blank" class="preview-link">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ linea.reporte_actual.documento ?? 'Ver archivo' }}
                                </a>
                                <a v-if="linea.reporte_actual.url" :href="linea.reporte_actual.url" target="_blank" class="preview-link">
                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px">
                                        <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                    </svg>
                                    Ver enlace externo
                                </a>
                            </div>
                        </div>

                        <div class="modal-foot">
                            <button class="btn btn-ghost" @click="modalPreview = false">Cerrar</button>
                        </div>
                    </div>
                </div>
            </Teleport>

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
.card--avance { border-left:3px solid var(--color-verde); }

.info-row { display:flex;flex-direction:column;gap:3px;margin-bottom:.875rem; }
.info-row:last-child { margin-bottom:0; }
.info-grid-3 { display:grid;grid-template-columns:repeat(3,1fr);gap:12px; }
.info-label { font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400); }
.info-val { font-size:var(--text-sm);color:var(--color-gris-800); }
.info-val--bold { font-weight:600; }
.info-formula { font-family:var(--font-mono,monospace);font-size:12px;background:var(--color-gris-100);padding:8px 12px;border-radius:var(--radius-md);color:var(--color-gris-700);line-height:1.6;border:1px solid var(--color-gris-200); }
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

.btn-registrar { width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:.75rem;background:var(--color-verde);color:#fff;border:none;border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:600;cursor:pointer;transition:opacity var(--transition-base); }
.btn-registrar:hover { opacity:.9; }
.btn-registrar-sm { display:inline-flex;align-items:center;gap:6px;font-size:var(--text-sm);font-weight:600;color:var(--color-verde);background:var(--color-verde-lt);padding:.5rem 1rem;border-radius:var(--radius-md);border:none;cursor:pointer;margin-top:.5rem; }
.sin-periodo-msg { font-size:var(--text-xs);color:var(--color-gris-400);text-align:center;padding:.75rem;background:var(--color-gris-50,#fafafa);border-radius:var(--radius-md);border:1px solid var(--color-gris-200); }

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

/* Modal */
.modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:200;padding:1rem; }
.modal { background:var(--color-blanco);border-radius:var(--radius-xl);box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:580px;animation:modal-in .18s ease;max-height:90vh;display:flex;flex-direction:column; }
@keyframes modal-in { from{opacity:0;transform:translateY(-12px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }
.modal-head { display:flex;align-items:flex-start;justify-content:space-between;padding:1.25rem 1.5rem;border-bottom:1px solid var(--color-gris-200);flex-shrink:0; }
.modal-title { font-family:var(--font-display);font-size:var(--text-lg);color:var(--color-gris-800); }
.modal-sub   { font-size:var(--text-xs);color:var(--color-gris-400);margin-top:3px; }
.modal-close { width:30px;height:30px;border-radius:var(--radius-md);border:none;background:transparent;cursor:pointer;color:var(--color-gris-400);display:flex;align-items:center;justify-content:center;transition:background var(--transition-base);flex-shrink:0; }
.modal-close:hover { background:var(--color-gris-200); }
.modal-body { padding:1.5rem;display:flex;flex-direction:column;gap:1rem;overflow-y:auto; }
.modal-foot { display:flex;align-items:center;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;border-top:1px solid var(--color-gris-200);flex-shrink:0; }

.modal-context { background:var(--color-gris-50,#fafafa);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:12px 14px; }
.context-label   { font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400);margin-bottom:3px; }
.context-val     { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-800);margin-bottom:4px; }
.context-formula { font-size:11px;color:var(--color-gris-500);font-family:var(--font-mono,monospace);margin-bottom:6px; }
.context-meta    { display:flex;gap:1rem;flex-wrap:wrap;font-size:11px;color:var(--color-gris-600); }

.seccion-label { font-size:var(--text-xs);font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--color-gris-500); }
.seccion-sep   { border-top:1px solid var(--color-gris-200); }
.field         { display:flex;flex-direction:column;gap:.35rem; }
.field-row     { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
.field-label   { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-700); }
.field-hint-inline { font-size:var(--text-xs);font-weight:400;color:var(--color-gris-400); }
.field-req     { color:var(--color-vino); }
.field-hint    { font-size:var(--text-xs);color:var(--color-gris-400); }
.field-hint--preview { color:var(--color-verde);font-weight:600; }
.field-error   { font-size:var(--text-xs);color:var(--color-vino);font-weight:600; }
.field-textarea { resize:vertical;min-height:80px;font-family:var(--font-body);font-size:var(--text-sm);line-height:1.5; }
.field-with-unit { display:flex;align-items:center;gap:8px; }
.field-with-unit .field-input { flex:1; }
.unit-badge { display:inline-flex;align-items:center;padding:0 10px;height:38px;background:var(--color-gris-100);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);font-size:12px;font-weight:600;color:var(--color-gris-600);white-space:nowrap;flex-shrink:0; }

.file-upload { display:block;cursor:pointer; }
.file-input  { display:none; }
.file-label  { display:flex;align-items:center;gap:.5rem;padding:.55rem .85rem;border:1px dashed var(--color-gris-300);border-radius:var(--radius-md);font-size:var(--text-sm);color:var(--color-gris-600);background:var(--color-gris-50,#fafafa);transition:all var(--transition-base);cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.file-label:hover { border-color:var(--color-verde);color:var(--color-verde); }

.aviso-sin-periodo { display:flex;align-items:flex-start;gap:.5rem;padding:.65rem .85rem;background:#fef9e7;border-radius:var(--radius-md);font-size:var(--text-xs);color:#92610a;line-height:1.5;border:1px solid #f0c040; }
.aviso-inmutable { display:flex;align-items:flex-start;gap:.5rem;padding:.65rem .85rem;background:var(--color-vino-lt);border-radius:var(--radius-md);font-size:var(--text-xs);color:var(--color-vino);line-height:1.5;border:1px solid var(--color-vino); }

.btn-ver-reporte { width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:.75rem;background:var(--color-gris-100);color:var(--color-gris-700);border:1px solid var(--color-gris-300);border-radius:var(--radius-md);font-size:var(--text-sm);font-weight:600;cursor:pointer;transition:all var(--transition-base); }
.btn-ver-reporte:hover { background:var(--color-verde-lt);color:var(--color-verde);border-color:var(--color-verde); }

/* Preview modal */
.preview-enviado { display:flex;align-items:center;gap:.5rem;padding:.55rem .85rem;background:var(--color-verde-lt);border-radius:var(--radius-md);font-size:var(--text-xs);color:var(--color-verde);font-weight:600;border:1px solid var(--color-verde); }
.preview-row { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
.preview-campo { display:flex;flex-direction:column;gap:4px; }
.preview-label { font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400); }
.preview-val { font-size:var(--text-sm);color:var(--color-gris-800);font-weight:500; }
.preview-val--grande { font-size:var(--text-xl);font-weight:700;font-family:var(--font-display);color:var(--color-verde); }
.preview-unidad { font-size:var(--text-sm);font-weight:400;color:var(--color-gris-500); }
.preview-sub { font-size:var(--text-xs);color:var(--color-gris-400);margin-top:2px; }
.preview-comentario { font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.6;background:var(--color-gris-50,#fafafa);padding:.75rem;border-radius:var(--radius-md);border:1px solid var(--color-gris-200); }
.preview-evidencias { display:flex;gap:1rem;flex-wrap:wrap; }
.preview-link { display:inline-flex;align-items:center;gap:5px;font-size:var(--text-xs);font-weight:600;color:#1a73e8;text-decoration:none;padding:.35rem .7rem;background:#e8f0fe;border-radius:var(--radius-md); }
.preview-link:hover { text-decoration:underline; }

.btn-spin { animation:spin .8s linear infinite; }
@keyframes spin { to{transform:rotate(360deg)} }
</style>
