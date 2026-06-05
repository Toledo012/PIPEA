<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

const props = defineProps({
    periodos:   { type: Array, default: () => [] },
    ejes:       { type: Array, default: () => [] },
    organismos: { type: Array, default: () => [] },
    prioridades:{ type: Array, default: () => [] },
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
    flashTimer = setTimeout(() => { flashMsg.value = null }, 4000)
}
watch(() => page.props.flash?.success, v => { if (v) mostrarFlash(v, 'success') })
watch(() => page.props.flash?.error,   v => { if (v) mostrarFlash(v, 'error')   })

// ── Modal crear período ───────────────────────────────────────────────────────
const modalCrear = ref(false)
const formCrear  = useForm({
    nombre: '', fecha_inicio: '', fecha_fin: '',
    fecha_limite_reporte: '', descripcion: '',
})

const guardarPeriodo = () => {
    formCrear.post(route('admin.periodos.store'), {
        onSuccess: () => { modalCrear.value = false; formCrear.reset() },
    })
}

// ── Modal detalle período ─────────────────────────────────────────────────────
const modalDetalle    = ref(false)
const periodoDetalle  = ref(null)
const detalleData     = ref(null)
const cargandoDetalle = ref(false)
const filaExpandida   = ref(null)   // id de la línea expandida en la tabla

const verDetalle = async (periodo) => {
    periodoDetalle.value  = periodo
    detalleData.value     = null
    filaExpandida.value   = null
    cargandoDetalle.value = true
    modalDetalle.value    = true
    try {
        const res = await fetch(route('admin.periodos.detalle', periodo.id))
        detalleData.value = await res.json()
    } finally {
        cargandoDetalle.value = false
    }
}

const toggleFila = (id) => {
    filaExpandida.value = filaExpandida.value === id ? null : id
}

// ── Modal prórroga ────────────────────────────────────────────────────────────
const modalProrroga = ref(false)
const lineaProrroga = ref(null)
const formProrroga  = useForm({ id_linea: '', prorroga_hasta: '', motivo: '' })

const abrirProrroga = (linea) => {
    lineaProrroga.value         = linea
    formProrroga.id_linea       = linea.id
    formProrroga.prorroga_hasta = ''
    formProrroga.motivo         = ''
    modalProrroga.value         = true
}

const guardarProrroga = () => {
    formProrroga.post(route('admin.periodos.prorroga', detalleData.value.periodo.id), {
        onSuccess: () => {
            modalProrroga.value = false
            verDetalle(periodoDetalle.value)
        },
    })
}

// ── Modal exportar ────────────────────────────────────────────────────────────
const modalExportar  = ref(false)
const exportTipo     = ref('general')        // 'general' | 'personalizado'
const exportFormato  = ref('pdf')            // 'pdf' | 'excel' | 'ambos'
const exportFiltros  = ref({ eje_id: '', organismo_id: '', estatus: '', prioridad_id: '' })

const abrirExportar = () => {
    exportTipo.value    = 'general'
    exportFormato.value = 'pdf'
    exportFiltros.value = { eje_id: '', organismo_id: '', estatus: '', prioridad_id: '' }
    modalExportar.value = true
}

const ejecutarExport = () => {
    if (!detalleData.value) return
    const periodoId = detalleData.value.periodo.id

    const params = new URLSearchParams()
    if (exportTipo.value === 'personalizado') {
        if (exportFiltros.value.eje_id)        params.set('eje_id',        exportFiltros.value.eje_id)
        if (exportFiltros.value.organismo_id)  params.set('organismo_id',  exportFiltros.value.organismo_id)
        if (exportFiltros.value.estatus)       params.set('estatus',       exportFiltros.value.estatus)
        if (exportFiltros.value.prioridad_id)  params.set('prioridad_id',  exportFiltros.value.prioridad_id)
    }
    const qs = params.toString() ? '?' + params.toString() : ''

    if (exportFormato.value === 'pdf' || exportFormato.value === 'ambos') {
        window.open(route('admin.periodos.exportar.pdf', periodoId) + qs, '_blank')
    }
    if (exportFormato.value === 'excel' || exportFormato.value === 'ambos') {
        const delay = exportFormato.value === 'ambos' ? 600 : 0
        setTimeout(() => {
            window.open(route('admin.periodos.exportar.excel', periodoId) + qs, '_blank')
        }, delay)
    }

    modalExportar.value = false
}

// ── Toggle activo ─────────────────────────────────────────────────────────────
const toggling = ref(null)
const togglePeriodo = (periodo) => {
    toggling.value = periodo.id
    useForm({}).patch(route('admin.periodos.toggle', periodo.id), {
        onFinish: () => { toggling.value = null },
    })
}

// ── Filtros ───────────────────────────────────────────────────────────────────
const busqueda       = ref('')
const filtroDetalle  = ref('todos') // 'todos' | 'reportaron' | 'pendientes'

const periodosFiltrados = computed(() =>
    props.periodos.filter(p =>
        !busqueda.value ||
        p.nombre.toLowerCase().includes(busqueda.value.toLowerCase())
    )
)

const lineasDetalleFiltradas = computed(() => {
    if (!detalleData.value) return []
    return detalleData.value.lineas.filter(l => {
        if (filtroDetalle.value === 'reportaron') return l.reporto
        if (filtroDetalle.value === 'pendientes') return !l.reporto
        return true
    })
})

// ── Helpers ───────────────────────────────────────────────────────────────────
const pctColor = (pct) => {
    if (pct >= 80) return 'var(--color-verde)'
    if (pct >= 50) return 'var(--color-magenta)'
    return 'var(--color-vino)'
}

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
</script>

<template>
    <AdminLayout>

        <Transition name="flash">
            <div v-if="flashMsg" :class="['flash-bar', `flash-bar--${flashType}`]">
                <svg v-if="flashType === 'success'" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <svg v-else viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                {{ flashMsg }}
            </div>
        </Transition>

        <div class="page">
            <div class="page-head">
                <div>
                    <p class="page-breadcrumb">PI-PEA</p>
                    <h1 class="page-title">Períodos de reporte</h1>
                </div>
                <button class="btn btn-primary" @click="modalCrear = true">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                    Nuevo período
                </button>
            </div>

            <div class="search-bar" style="margin-bottom:1rem">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px;flex-shrink:0;color:var(--color-gris-400)"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                <input v-model="busqueda" type="text" placeholder="Buscar período..." class="search-input"/>
                <span class="search-count">{{ periodosFiltrados.length }} período{{ periodosFiltrados.length !== 1 ? 's' : '' }}</span>
            </div>

            <div v-if="periodosFiltrados.length === 0" class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--color-gris-300)"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5"/></svg>
                <p>No hay períodos de reporte creados.</p>
                <button class="btn btn-secondary btn-sm" @click="modalCrear = true">Crear primer período</button>
            </div>

            <div class="periodos-grid">
                <div v-for="p in periodosFiltrados" :key="p.id" class="periodo-card">
                    <div class="periodo-card-head">
                        <div class="periodo-nombre-row">
                            <h3 class="periodo-nombre">{{ p.nombre }}</h3>
                            <span :class="['badge-estado', p.esta_abierto ? 'badge-estado--activo' : 'badge-estado--inactivo']">
                                {{ p.esta_abierto ? 'Abierto' : 'Cerrado' }}
                            </span>
                        </div>
                        <p v-if="p.descripcion" class="periodo-desc">{{ p.descripcion }}</p>
                    </div>
                    <div class="periodo-fechas">
                        <div class="fecha-item"><span class="fecha-label">Inicio</span><span class="fecha-val">{{ p.fecha_inicio }}</span></div>
                        <div class="fecha-sep">→</div>
                        <div class="fecha-item"><span class="fecha-label">Fin</span><span class="fecha-val">{{ p.fecha_fin }}</span></div>
                        <div class="fecha-sep" style="margin-left:auto">Límite:</div>
                        <div class="fecha-item"><span class="fecha-val fecha-val--limite">{{ p.fecha_limite_reporte }}</span></div>
                    </div>
                    <div class="periodo-stats">
                        <div class="stat-item"><span class="stat-num">{{ p.total_lineas }}</span><span class="stat-label">líneas</span></div>
                        <div class="stat-item"><span class="stat-num">{{ p.total_avances }}</span><span class="stat-label">avances</span></div>
                        <div class="stat-item" style="margin-left:auto">
                            <div class="mini-bar">
                                <div class="mini-bar-fill" :style="{ width: p.total_lineas > 0 ? Math.min(p.total_avances / p.total_lineas * 100, 100) + '%' : '0%', background: pctColor(p.total_lineas > 0 ? p.total_avances / p.total_lineas * 100 : 0) }"></div>
                            </div>
                            <span class="stat-pct">{{ p.total_lineas > 0 ? Math.round(p.total_avances / p.total_lineas * 100) : 0 }}% reportaron</span>
                        </div>
                    </div>
                    <div class="periodo-acciones">
                        <button class="btn btn-secondary btn-sm" @click="verDetalle(p)">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;margin-right:4px"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                            Ver detalle
                        </button>
                        <button :class="['btn btn-sm', p.activo ? 'btn-danger' : 'btn-primary']" :disabled="toggling === p.id" @click="togglePeriodo(p)">
                            <svg v-if="toggling === p.id" class="btn-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;margin-right:4px"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            {{ p.activo ? 'Cerrar período' : 'Abrir período' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             MODAL CREAR
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="modalCrear" class="modal-overlay" @click.self="modalCrear = false">
                <div class="modal modal--md">
                    <div class="modal-head">
                        <h2 class="modal-title">Nuevo período de reporte</h2>
                        <button class="modal-close" @click="modalCrear = false"><svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></button>
                    </div>
                    <div class="modal-body">
                        <div class="field">
                            <label class="field-label">Nombre <span class="field-req">*</span></label>
                            <input v-model="formCrear.nombre" type="text" class="field-input" placeholder="Ej. 1er Trimestre 2025"/>
                            <p v-if="formCrear.errors.nombre" class="field-error">{{ formCrear.errors.nombre }}</p>
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <label class="field-label">Fecha inicio <span class="field-req">*</span></label>
                                <input v-model="formCrear.fecha_inicio" type="date" class="field-input"/>
                                <p v-if="formCrear.errors.fecha_inicio" class="field-error">{{ formCrear.errors.fecha_inicio }}</p>
                            </div>
                            <div class="field">
                                <label class="field-label">Fecha fin <span class="field-req">*</span></label>
                                <input v-model="formCrear.fecha_fin" type="date" class="field-input"/>
                                <p v-if="formCrear.errors.fecha_fin" class="field-error">{{ formCrear.errors.fecha_fin }}</p>
                            </div>
                        </div>
                        <div class="field">
                            <label class="field-label">Fecha límite de reporte <span class="field-req">*</span></label>
                            <input v-model="formCrear.fecha_limite_reporte" type="date" class="field-input"/>
                            <p class="field-hint">Hasta esta fecha los organismos pueden subir su avance.</p>
                            <p v-if="formCrear.errors.fecha_limite_reporte" class="field-error">{{ formCrear.errors.fecha_limite_reporte }}</p>
                        </div>
                        <div class="field">
                            <label class="field-label">Descripción</label>
                            <textarea v-model="formCrear.descripcion" class="field-input field-textarea" rows="2" placeholder="Opcional"/>
                        </div>
                        <div class="field-info">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0;color:var(--color-verde)"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                            <span>Al crear el período se asignarán automáticamente todas las líneas activas.</span>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn btn-ghost" @click="modalCrear = false" :disabled="formCrear.processing">Cancelar</button>
                        <button class="btn btn-primary" @click="guardarPeriodo" :disabled="formCrear.processing">
                            <svg v-if="formCrear.processing" class="btn-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            {{ formCrear.processing ? 'Creando...' : 'Crear período' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══════════════════════════════════════════════════════════
             MODAL DETALLE — con filas expandibles
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="modalDetalle" class="modal-overlay" @click.self="modalDetalle = false">
                <div class="modal modal--xxl">
                    <div class="modal-head modal-head--ver">
                        <div>
                            <h2 class="modal-title">{{ detalleData?.periodo?.nombre ?? periodoDetalle?.nombre }}</h2>
                            <p v-if="detalleData" class="modal-subtitle">
                                {{ detalleData.stats.reportaron }} de {{ detalleData.stats.total }} líneas reportaron
                                ({{ detalleData.stats.porcentaje }}%)
                            </p>
                        </div>
                        <button class="modal-close" @click="modalDetalle = false"><svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></button>
                    </div>

                    <div class="modal-body">
                        <div v-if="cargandoDetalle" class="detalle-loading">
                            <svg class="btn-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:24px;height:24px;color:var(--color-gris-400)"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            <span>Cargando...</span>
                        </div>

                        <template v-else-if="detalleData">
                            <!-- Stats -->
                            <div class="stats-row">
                                <div class="stat-card stat-card--total"><span class="stat-card-num">{{ detalleData.stats.total }}</span><span class="stat-card-label">Total líneas</span></div>
                                <div class="stat-card stat-card--ok"><span class="stat-card-num">{{ detalleData.stats.reportaron }}</span><span class="stat-card-label">Reportaron</span></div>
                                <div class="stat-card stat-card--pend"><span class="stat-card-num">{{ detalleData.stats.sin_reporte }}</span><span class="stat-card-label">Sin reporte</span></div>
                                <div class="stat-card stat-card--pct"><span class="stat-card-num">{{ detalleData.stats.porcentaje }}%</span><span class="stat-card-label">Cumplimiento</span></div>
                            </div>

                            <!-- Filtro rápido -->
                            <div class="filtro-tabs">
                                <button :class="['tab-btn', filtroDetalle === 'todos' ? 'tab-btn--active' : '']" @click="filtroDetalle = 'todos'">Todas ({{ detalleData.stats.total }})</button>
                                <button :class="['tab-btn', filtroDetalle === 'reportaron' ? 'tab-btn--active' : '']" @click="filtroDetalle = 'reportaron'">Reportaron ({{ detalleData.stats.reportaron }})</button>
                                <button :class="['tab-btn', filtroDetalle === 'pendientes' ? 'tab-btn--active' : '']" @click="filtroDetalle = 'pendientes'">Pendientes ({{ detalleData.stats.sin_reporte }})</button>
                            </div>

                            <!-- Tabla expandible -->
                            <div class="detalle-tabla-wrap">
                                <table class="tabla">
                                    <thead>
                                    <tr>
                                        <th class="th"></th>
                                        <th class="th">Prioridad</th>
                                        <th class="th">Organismo</th>
                                        <th class="th th--center">Estado</th>
                                        <th class="th th--center">Avance</th>
                                        <th class="th th--center">Fecha</th>
                                        <th class="th th--center">Acción</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <template v-for="l in lineasDetalleFiltradas" :key="l.id">
                                        <!-- Fila principal -->
                                        <tr :class="['tr', l.reporto ? 'tr--reporto' : '']">
                                            <!-- Expandir si reportó -->
                                            <td class="td" style="width:32px;padding:.5rem .5rem">
                                                <button v-if="l.reporto" class="btn-expand" @click="toggleFila(l.id)" :title="filaExpandida === l.id ? 'Ocultar detalle' : 'Ver detalle'">
                                                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;transition:transform .2s" :style="{ transform: filaExpandida === l.id ? 'rotate(180deg)' : '' }">
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                    </svg>
                                                </button>
                                            </td>
                                            <td class="td td--desc" :title="l.prioridad">{{ l.prioridad }}</td>
                                            <td class="td">
                                                <p class="org-nombre">{{ l.organismo }}</p>
                                                <p class="org-usuario">{{ l.usuario }}</p>
                                            </td>
                                            <td class="td td--center">
                                                    <span :class="['badge-reporte', l.reporto ? 'badge-reporte--ok' : 'badge-reporte--pend']">
                                                        {{ l.reporto ? 'Reportó' : 'Pendiente' }}
                                                    </span>
                                            </td>
                                            <td class="td td--center">
                                                <template v-if="l.reporto">
                                                    <div class="avance-wrap">
                                                        <div class="avance-bar"><div class="avance-fill" :style="{ width: Math.min(l.porcentaje ?? 0, 100) + '%' }"></div></div>
                                                        <span class="avance-pct">{{ l.porcentaje ?? 0 }}%</span>
                                                    </div>
                                                </template>
                                                <span v-else class="text-muted">—</span>
                                            </td>
                                            <td class="td td--center">
                                                <span class="text-sm">{{ l.fecha_avance ?? '—' }}</span>
                                            </td>
                                            <td class="td td--center">
                                                <button v-if="!l.reporto" class="btn-text" @click="abrirProrroga(l)">Prórroga</button>
                                                <span v-else class="text-muted">—</span>
                                            </td>
                                        </tr>

                                        <!-- Fila expandida con detalle del avance -->
                                        <tr v-if="l.reporto && filaExpandida === l.id" class="tr-detalle">
                                            <td colspan="7" class="td-detalle">
                                                <div class="avance-detalle">

                                                    <!-- Estatus + valor -->
                                                    <div class="avance-detalle-top">
                                                            <span :class="['ev-estatus', l.estatus === 'Con avances' ? 'ev-estatus--ok' : 'ev-estatus--pend']">
                                                                {{ l.estatus }}
                                                            </span>
                                                        <span class="avance-detalle-valor">
                                                                {{ fmtNum(l.valor_avance) }}
                                                                <span class="avance-detalle-unidad">{{ l.unidad_medida }}</span>
                                                            </span>
                                                        <span class="avance-detalle-meta">de {{ fmtNum(l.meta) }} {{ l.unidad_medida }}</span>
                                                    </div>

                                                    <!-- Comentario -->
                                                    <p v-if="l.comentario" class="avance-detalle-comentario">
                                                        {{ l.comentario }}
                                                    </p>

                                                    <!-- Notas adicionales -->
                                                    <p v-if="l.avances_linea" class="avance-detalle-nota">
                                                        <strong>Nota:</strong> {{ l.avances_linea }}
                                                    </p>

                                                    <!-- Evidencias -->
                                                    <div class="avance-detalle-evidencias">
                                                        <div v-if="l.medio_verificacion" class="ev-medio">
                                                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;flex-shrink:0">
                                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                            </svg>
                                                            {{ l.medio_verificacion }}
                                                        </div>

                                                        <a v-if="l.archivo_url" :href="l.archivo_url" target="_blank" class="ev-archivo-link">
                                                            <div class="ev-archivo-icon-sm" :style="{ background: colorIcono(l.archivo_tipo) + '18', color: colorIcono(l.archivo_tipo) }">
                                                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px">
                                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                                                </svg>
                                                            </div>
                                                            {{ l.documento ?? 'Ver archivo' }}
                                                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:11px;height:11px;color:#6b7280">
                                                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                            </svg>
                                                        </a>

                                                        <a v-if="l.url" :href="l.url" target="_blank" class="ev-url-link">
                                                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:12px;height:12px;flex-shrink:0">
                                                                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                                            </svg>
                                                            Ver enlace externo
                                                        </a>
                                                    </div>

                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>

                    <div class="modal-foot">
                        <button v-if="detalleData" class="btn btn-export" @click="abrirExportar">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Exportar reporte
                        </button>
                        <button class="btn btn-ghost" @click="modalDetalle = false">Cerrar</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══════════════════════════════════════════════════════════
             MODAL PRÓRROGA
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="modalProrroga" class="modal-overlay" @click.self="modalProrroga = false">
                <div class="modal modal--sm">
                    <div class="modal-head">
                        <h2 class="modal-title">Habilitar prórroga</h2>
                        <button class="modal-close" @click="modalProrroga = false"><svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></button>
                    </div>
                    <div class="modal-body">
                        <p class="prorroga-linea">{{ lineaProrroga?.prioridad }}</p>
                        <div class="field">
                            <label class="field-label">Nueva fecha límite <span class="field-req">*</span></label>
                            <input v-model="formProrroga.prorroga_hasta" type="date" class="field-input"/>
                            <p v-if="formProrroga.errors.prorroga_hasta" class="field-error">{{ formProrroga.errors.prorroga_hasta }}</p>
                        </div>
                        <div class="field">
                            <label class="field-label">Motivo <span class="field-req">*</span></label>
                            <textarea v-model="formProrroga.motivo" class="field-input field-textarea" rows="3" placeholder="Razón por la que se otorga la prórroga..."/>
                            <p v-if="formProrroga.errors.motivo" class="field-error">{{ formProrroga.errors.motivo }}</p>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn btn-ghost" @click="modalProrroga = false" :disabled="formProrroga.processing">Cancelar</button>
                        <button class="btn btn-primary" @click="guardarProrroga" :disabled="formProrroga.processing">Habilitar prórroga</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══════════════════════════════════════════════════════════
             MODAL EXPORTAR
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="modalExportar" class="modal-overlay" @click.self="modalExportar = false">
                <div class="modal modal--export">
                    <div class="modal-head">
                        <div>
                            <h2 class="modal-title">Exportar reporte</h2>
                            <p class="modal-subtitle">{{ detalleData?.periodo?.nombre }}</p>
                        </div>
                        <button class="modal-close" @click="modalExportar = false">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>

                    <div class="modal-body">

                        <!-- Sección 1: Tipo de reporte -->
                        <div class="export-seccion">
                            <p class="export-seccion-titulo">Tipo de reporte</p>
                            <div class="export-radio-group">
                                <label :class="['export-radio', exportTipo === 'general' && 'export-radio--active']">
                                    <input type="radio" v-model="exportTipo" value="general" class="export-radio-input"/>
                                    <div class="export-radio-body">
                                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0;color:var(--color-verde)"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z" clip-rule="evenodd"/></svg>
                                        <div>
                                            <p class="export-radio-label">General</p>
                                            <p class="export-radio-desc">Todo el período sin filtros</p>
                                        </div>
                                    </div>
                                </label>
                                <label :class="['export-radio', exportTipo === 'personalizado' && 'export-radio--active']">
                                    <input type="radio" v-model="exportTipo" value="personalizado" class="export-radio-input"/>
                                    <div class="export-radio-body">
                                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0;color:var(--color-magenta)"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                                        <div>
                                            <p class="export-radio-label">Personalizado</p>
                                            <p class="export-radio-desc">Con filtros específicos</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Sección 2: Filtros (solo en personalizado) -->
                        <Transition name="filtros-slide">
                            <div v-if="exportTipo === 'personalizado'" class="export-filtros">
                                <p class="export-seccion-titulo">Filtros</p>
                                <div class="export-filtros-grid">
                                    <div class="field">
                                        <label class="field-label">Eje estratégico</label>
                                        <select v-model="exportFiltros.eje_id" class="field-input">
                                            <option value="">Todos los ejes</option>
                                            <option v-for="e in ejes" :key="e.id" :value="e.id">{{ e.eje }}</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label class="field-label">Organismo / Entidad</label>
                                        <select v-model="exportFiltros.organismo_id" class="field-input">
                                            <option value="">Todos los organismos</option>
                                            <option v-for="o in organismos" :key="o.id" :value="o.id">{{ o.nombre }}</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label class="field-label">Estatus</label>
                                        <select v-model="exportFiltros.estatus" class="field-input">
                                            <option value="">Todos</option>
                                            <option value="con_reporte">Con reporte</option>
                                            <option value="sin_reporte">Sin reporte</option>
                                            <option value="bloqueado">Bloqueado</option>
                                        </select>
                                    </div>
                                    <div class="field">
                                        <label class="field-label">Prioridad / Línea de acción</label>
                                        <select v-model="exportFiltros.prioridad_id" class="field-input">
                                            <option value="">Todas las prioridades</option>
                                            <option v-for="p in prioridades" :key="p.id" :value="p.id">{{ p.prioridad }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </Transition>

                        <!-- Sección 3: Formato -->
                        <div class="export-seccion">
                            <p class="export-seccion-titulo">Formato de exportación</p>
                            <div class="export-formato-grid">
                                <button
                                    v-for="fmt in [
                                        { key: 'pdf',   label: 'PDF',   sub: 'Reporte institucional completo', color: '#AE1922', icon: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
                                        { key: 'excel', label: 'Excel', sub: 'Datos tabulares con hoja de detalle', color: '#009887', icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
                                        { key: 'ambos', label: 'Ambos', sub: 'Genera y descarga los dos archivos', color: '#C90166', icon: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4' },
                                    ]"
                                    :key="fmt.key"
                                    :class="['export-fmt-btn', exportFormato === fmt.key && 'export-fmt-btn--active']"
                                    :style="exportFormato === fmt.key ? `--fmt-color:${fmt.color}` : ''"
                                    @click="exportFormato = fmt.key"
                                    type="button"
                                >
                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6"
                                        :style="{ color: exportFormato === fmt.key ? fmt.color : 'var(--color-gris-400)' }"
                                        style="width:24px;height:24px;margin-bottom:6px;flex-shrink:0">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="fmt.icon"/>
                                    </svg>
                                    <span class="export-fmt-label" :style="exportFormato === fmt.key ? `color:${fmt.color}` : ''">{{ fmt.label }}</span>
                                    <span class="export-fmt-sub">{{ fmt.sub }}</span>
                                </button>
                            </div>
                        </div>

                    </div>

                    <div class="modal-foot">
                        <button class="btn btn-ghost" @click="modalExportar = false">Cancelar</button>
                        <button class="btn btn-primary" @click="ejecutarExport">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;flex-shrink:0">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                            Generar y descargar
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>

<style scoped>
.flash-bar { position:fixed;top:1.25rem;left:50%;transform:translateX(-50%);z-index:9999;display:flex;align-items:center;gap:.6rem;padding:.65rem 1.25rem;border-radius:var(--radius-lg);font-size:var(--text-sm);font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.15);white-space:nowrap; }
.flash-bar--success { background:var(--color-verde);color:#fff; }
.flash-bar--error   { background:var(--color-vino);color:#fff; }
.flash-enter-active,.flash-leave-active { transition:opacity .25s,transform .25s; }
.flash-enter-from,.flash-leave-to { opacity:0;transform:translateX(-50%) translateY(-8px); }

.page { max-width:1200px; }
.page-head { display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.5rem;gap:1rem; }
.page-breadcrumb { font-size:var(--text-xs);color:var(--color-gris-400);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem; }
.page-title { font-family:var(--font-display);font-size:var(--text-2xl);color:var(--color-gris-800); }

.search-bar { display:flex;align-items:center;gap:.75rem;background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.6rem 1rem;box-shadow:var(--shadow-sm); }
.search-input { flex:1;border:none;outline:none;font-size:var(--text-sm);color:var(--color-gris-700);background:transparent;font-family:var(--font-body); }
.search-input::placeholder { color:var(--color-gris-400); }
.search-count { font-size:var(--text-xs);color:var(--color-gris-400);white-space:nowrap; }
.empty-state { display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:4rem 1rem;color:var(--color-gris-500);font-size:var(--text-sm); }

.periodos-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:1rem; }
.periodo-card { background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;display:flex;flex-direction:column; }
.periodo-card-head { padding:1rem 1rem .75rem; }
.periodo-nombre-row { display:flex;align-items:center;gap:.6rem;flex-wrap:wrap; }
.periodo-nombre { font-family:var(--font-display);font-size:var(--text-base);font-weight:700;color:var(--color-gris-800); }
.periodo-desc { font-size:var(--text-xs);color:var(--color-gris-500);margin-top:.25rem;line-height:1.5; }
.periodo-fechas { display:flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:var(--color-gris-50,#fafafa);border-top:1px solid var(--color-gris-100);border-bottom:1px solid var(--color-gris-100);flex-wrap:wrap; }
.fecha-item { display:flex;flex-direction:column; }
.fecha-label { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-gris-400); }
.fecha-val { font-size:var(--text-xs);font-weight:600;color:var(--color-gris-700); }
.fecha-val--limite { color:var(--color-vino);font-weight:700; }
.fecha-sep { font-size:var(--text-xs);color:var(--color-gris-400); }
.periodo-stats { display:flex;align-items:center;gap:1rem;padding:.75rem 1rem; }
.stat-item { display:flex;flex-direction:column;align-items:center; }
.stat-num { font-family:var(--font-display);font-size:var(--text-lg);font-weight:700;color:var(--color-gris-800);line-height:1.2; }
.stat-label { font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--color-gris-400); }
.mini-bar { width:100px;height:5px;background:var(--color-gris-200);border-radius:var(--radius-full);overflow:hidden;margin-bottom:4px; }
.mini-bar-fill { height:100%;border-radius:var(--radius-full);transition:width .4s ease; }
.stat-pct { font-size:var(--text-xs);color:var(--color-gris-500);font-weight:600; }
.periodo-acciones { display:flex;gap:.5rem;padding:.75rem 1rem;border-top:1px solid var(--color-gris-100);margin-top:auto; }
.badge-estado { display:inline-flex;align-items:center;padding:.15rem .6rem;border-radius:var(--radius-full);font-size:var(--text-xs);font-weight:700; }
.badge-estado--activo   { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-estado--inactivo { background:var(--color-gris-200);color:var(--color-gris-500); }

/* Modal */
.modal--md  { max-width:540px; }
.modal--xxl { max-width:960px; }
.modal--sm  { max-width:400px; }
.modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:200;padding:1rem; }
.modal { background:var(--color-blanco);border-radius:var(--radius-xl);box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;animation:modal-in .18s ease;max-height:90vh;display:flex;flex-direction:column; }
@keyframes modal-in { from{opacity:0;transform:translateY(-12px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }
.modal-head { display:flex;align-items:flex-start;justify-content:space-between;padding:1.1rem 1.5rem;border-bottom:1px solid var(--color-gris-200);flex-shrink:0; }
.modal-head--ver { background:var(--color-gris-50,#fafafa);border-radius:var(--radius-xl) var(--radius-xl) 0 0; }
.modal-title { font-family:var(--font-display);font-size:var(--text-lg);color:var(--color-gris-800); }
.modal-subtitle { font-size:var(--text-sm);color:var(--color-gris-500);margin-top:2px; }
.modal-close { width:30px;height:30px;border-radius:var(--radius-md);border:none;background:transparent;cursor:pointer;color:var(--color-gris-400);display:flex;align-items:center;justify-content:center;transition:background var(--transition-base);flex-shrink:0; }
.modal-close:hover { background:var(--color-gris-200); }
.modal-body { padding:1.5rem;display:flex;flex-direction:column;gap:1rem;overflow-y:auto; }
.modal-foot { display:flex;align-items:center;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;border-top:1px solid var(--color-gris-200);flex-shrink:0; }
.detalle-loading { display:flex;align-items:center;gap:.75rem;justify-content:center;padding:2rem;color:var(--color-gris-500);font-size:var(--text-sm); }

.stats-row { display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem; }
.stat-card { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1rem;border-radius:var(--radius-md);border:1px solid var(--color-gris-200); }
.stat-card-num { font-family:var(--font-display);font-size:var(--text-2xl);font-weight:700;line-height:1.1; }
.stat-card-label { font-size:var(--text-xs);text-transform:uppercase;letter-spacing:.06em;margin-top:4px; }
.stat-card--total { background:var(--color-gris-50,#fafafa);color:var(--color-gris-700); }
.stat-card--ok    { background:var(--color-verde-lt);color:var(--color-verde); }
.stat-card--pend  { background:var(--color-vino-lt);color:var(--color-vino); }
.stat-card--pct   { background:var(--color-magenta-lt);color:var(--color-magenta); }

/* Filtro tabs */
.filtro-tabs { display:flex;gap:4px;background:var(--color-gris-100);border-radius:var(--radius-md);padding:3px;width:fit-content; }
.tab-btn { padding:.3rem .75rem;border-radius:var(--radius-sm);border:none;background:transparent;font-size:var(--text-xs);font-weight:600;color:var(--color-gris-500);cursor:pointer;transition:all var(--transition-base);white-space:nowrap; }
.tab-btn--active { background:var(--color-blanco);color:var(--color-gris-800);box-shadow:var(--shadow-sm); }

/* Tabla */
.detalle-tabla-wrap { border:1px solid var(--color-gris-200);border-radius:var(--radius-md);overflow:hidden; }
.tabla { width:100%;border-collapse:collapse; }
.th { padding:.6rem .75rem;font-size:var(--text-xs);font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-500);background:var(--color-gris-100);border-bottom:1px solid var(--color-gris-200);text-align:left; }
.th--center { text-align:center; }
.tr { border-bottom:1px solid var(--color-gris-100);transition:background var(--transition-base); }
.tr:last-child { border-bottom:none; }
.tr--reporto:hover { background:var(--color-gris-50,#fafafa); }
.td { padding:.55rem .75rem;font-size:var(--text-sm);color:var(--color-gris-700);vertical-align:middle; }
.td--center { text-align:center; }
.td--desc { max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.org-nombre  { font-weight:600;color:var(--color-gris-800);font-size:var(--text-sm); }
.org-usuario { font-size:11px;color:var(--color-gris-500); }
.badge-reporte { display:inline-flex;align-items:center;padding:.15rem .6rem;border-radius:var(--radius-full);font-size:11px;font-weight:700; }
.badge-reporte--ok   { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-reporte--pend { background:var(--color-magenta-lt);color:var(--color-magenta); }
.avance-wrap { display:flex;flex-direction:column;align-items:center;gap:2px; }
.avance-bar  { width:60px;height:4px;background:var(--color-gris-200);border-radius:var(--radius-full);overflow:hidden; }
.avance-fill { height:100%;background:var(--color-verde);border-radius:var(--radius-full); }
.avance-pct  { font-size:11px;font-weight:700;color:var(--color-gris-600); }
.text-muted { color:var(--color-gris-300);font-size:var(--text-xs); }
.text-sm    { font-size:var(--text-xs);color:var(--color-gris-600); }
.btn-text   { background:none;border:none;cursor:pointer;font-size:var(--text-xs);font-weight:600;color:var(--color-verde);text-decoration:underline;padding:0; }
.btn-text:hover { color:var(--color-vino); }
.btn-expand { width:24px;height:24px;border-radius:var(--radius-sm);border:1px solid var(--color-gris-200);background:var(--color-gris-50,#fafafa);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--color-gris-500);transition:all var(--transition-base); }
.btn-expand:hover { background:var(--color-gris-200);color:var(--color-gris-800); }

/* Fila expandida */
.tr-detalle { background:var(--color-gris-50,#fafafa);border-bottom:2px solid var(--color-verde-lt); }
.td-detalle { padding:0 !important; }
.avance-detalle { padding:1rem 1.25rem;display:flex;flex-direction:column;gap:.625rem;border-left:3px solid var(--color-verde); }
.avance-detalle-top { display:flex;align-items:center;gap:.75rem;flex-wrap:wrap; }
.ev-estatus { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:700; }
.ev-estatus--ok   { background:var(--color-verde-lt);color:var(--color-verde); }
.ev-estatus--pend { background:var(--color-magenta-lt);color:var(--color-magenta); }
.avance-detalle-valor { font-size:var(--text-lg);font-weight:700;font-family:var(--font-display);color:var(--color-gris-800); }
.avance-detalle-unidad { font-size:var(--text-sm);font-weight:400;color:var(--color-gris-500); }
.avance-detalle-meta { font-size:var(--text-xs);color:var(--color-gris-400); }
.avance-detalle-comentario { font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.5;background:var(--color-blanco);padding:.5rem .75rem;border-radius:var(--radius-md);border:1px solid var(--color-gris-200); }
.avance-detalle-nota { font-size:var(--text-xs);color:var(--color-gris-500);padding:.35rem .6rem;background:var(--color-gris-100);border-radius:var(--radius-sm); }
.avance-detalle-evidencias { display:flex;align-items:center;gap:1rem;flex-wrap:wrap; }
.ev-medio { display:flex;align-items:center;gap:5px;font-size:12px;color:var(--color-gris-500); }
.ev-archivo-link { display:inline-flex;align-items:center;gap:6px;font-size:var(--text-xs);font-weight:600;color:var(--color-gris-700);text-decoration:none;padding:.3rem .6rem;background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);transition:border-color var(--transition-base); }
.ev-archivo-link:hover { border-color:var(--color-gris-400); }
.ev-archivo-icon-sm { width:22px;height:22px;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.ev-url-link { display:inline-flex;align-items:center;gap:5px;font-size:var(--text-xs);font-weight:600;color:#2563eb;text-decoration:none; }
.ev-url-link:hover { text-decoration:underline; }

/* Form */
.field { display:flex;flex-direction:column;gap:.35rem; }
.field-row { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
.field-label { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-700); }
.field-req { color:var(--color-vino); }
.field-hint { font-size:var(--text-xs);color:var(--color-gris-400); }
.field-error { font-size:var(--text-xs);color:var(--color-vino);font-weight:600; }
.field-textarea { resize:vertical;min-height:60px;font-family:var(--font-body);font-size:var(--text-sm);line-height:1.5; }
.field-info { display:flex;align-items:flex-start;gap:.5rem;padding:.6rem .75rem;background:var(--color-verde-lt);border-radius:var(--radius-md);font-size:var(--text-xs);color:var(--color-verde);line-height:1.5; }
.prorroga-linea { font-size:var(--text-sm);color:var(--color-gris-700);background:var(--color-gris-50,#fafafa);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.5rem .75rem;line-height:1.5; }

.btn-spin { animation:spin .8s linear infinite; }
@keyframes spin { to{transform:rotate(360deg)} }

/* ── Botón exportar ── */
.btn-export { display:inline-flex;align-items:center;gap:.45rem;padding:.45rem .9rem;border-radius:var(--radius-md);border:1.5px solid var(--color-verde);background:var(--color-verde-lt);color:var(--color-verde);font-size:var(--text-sm);font-weight:600;cursor:pointer;transition:all var(--transition-base); }
.btn-export:hover { background:var(--color-verde);color:white; }

/* ── Modal exportar ── */
.modal--export { max-width:600px; }

/* Secciones */
.export-seccion { display:flex;flex-direction:column;gap:.6rem; }
.export-seccion-titulo { font-size:var(--text-xs);font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--color-gris-500); }

/* Radio tipo */
.export-radio-group { display:grid;grid-template-columns:1fr 1fr;gap:.6rem; }
.export-radio { cursor:pointer;border-radius:var(--radius-md);border:1.5px solid var(--color-gris-200);background:var(--color-blanco);transition:all var(--transition-base);overflow:hidden; }
.export-radio--active { border-color:var(--color-verde);background:var(--color-verde-lt); }
.export-radio:hover:not(.export-radio--active) { border-color:var(--color-gris-300);background:var(--color-gris-50,#fafafa); }
.export-radio-input { display:none; }
.export-radio-body { display:flex;align-items:flex-start;gap:.65rem;padding:.75rem 1rem; }
.export-radio-label { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-800);margin-bottom:1px; }
.export-radio-desc  { font-size:var(--text-xs);color:var(--color-gris-500); }

/* Filtros */
.export-filtros { background:var(--color-gris-50,#fafafa);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.85rem 1rem;display:flex;flex-direction:column;gap:.75rem; }
.export-filtros-grid { display:grid;grid-template-columns:1fr 1fr;gap:.75rem; }
.filtros-slide-enter-active,.filtros-slide-leave-active { transition:all .2s ease; }
.filtros-slide-enter-from,.filtros-slide-leave-to { opacity:0;transform:translateY(-8px); }

/* Formato */
.export-formato-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:.6rem; }
.export-fmt-btn { display:flex;flex-direction:column;align-items:center;padding:.85rem .5rem;border-radius:var(--radius-md);border:1.5px solid var(--color-gris-200);background:var(--color-blanco);cursor:pointer;transition:all var(--transition-base);text-align:center; }
.export-fmt-btn:hover:not(.export-fmt-btn--active) { border-color:var(--color-gris-300);background:var(--color-gris-50,#fafafa); }
.export-fmt-btn--active { border-color:var(--fmt-color, var(--color-verde));background:color-mix(in srgb, var(--fmt-color, var(--color-verde)) 10%, transparent); }
.export-fmt-label { font-size:var(--text-sm);font-weight:700;color:var(--color-gris-800);margin-bottom:2px; }
.export-fmt-sub   { font-size:10px;color:var(--color-gris-500);line-height:1.35; }
.export-fmt-btn--active .export-fmt-label { color:var(--fmt-color, var(--color-verde)); }
</style>
