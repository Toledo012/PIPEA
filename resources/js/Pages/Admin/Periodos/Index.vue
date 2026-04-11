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
const page = usePage()
const flashMsg = ref(null)
const flashType = ref('success')
let flashTimer

const mostrarFlash = (msg, tipo = 'success') => {
    flashMsg.value = msg
    flashType.value = tipo
    clearTimeout(flashTimer)
    flashTimer = setTimeout(() => {
        flashMsg.value = null
    }, 4000)
}

watch(() => page.props.flash?.success, v => { if (v) mostrarFlash(v, 'success') })
watch(() => page.props.flash?.error, v => { if (v) mostrarFlash(v, 'error') })

// ── Modal crear período ───────────────────────────────────────────────────────
const modalCrear = ref(false)
const formCrear = useForm({
    nombre: '',
    fecha_inicio: '',
    fecha_fin: '',
    fecha_limite_reporte: '',
    descripcion: '',
})

const cerrarModalCrear = () => {
    modalCrear.value = false
    formCrear.reset()
    formCrear.clearErrors()
}

const guardarPeriodo = () => {
    formCrear.post(route('admin.periodos.store'), {
        preserveScroll: true,
        onSuccess: () => cerrarModalCrear(),
        onError: () => mostrarFlash('Revisa los campos del período.', 'error'),
    })
}

// ── Modal detalle período ─────────────────────────────────────────────────────
const modalDetalle = ref(false)
const periodoDetalle = ref(null)
const detalleData = ref(null)
const cargandoDetalle = ref(false)
const filaExpandida = ref(null)

const cerrarModalDetalle = () => {
    modalDetalle.value = false
    periodoDetalle.value = null
    detalleData.value = null
    filaExpandida.value = null
    filtroDetalle.value = 'todos'
    busquedaDetalle.value = ''
    sortBy.value = 'id'
    sortDir.value = 'asc'
}

const verDetalle = async (periodo) => {
    periodoDetalle.value = periodo
    detalleData.value = null
    filaExpandida.value = null
    cargandoDetalle.value = true
    modalDetalle.value = true

    try {
        const res = await fetch(route('admin.periodos.detalle', periodo.id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })

        if (!res.ok) {
            throw new Error('No se pudo cargar el detalle del período.')
        }

        detalleData.value = await res.json()
    } catch (error) {
        mostrarFlash(error.message || 'Ocurrió un error al cargar el detalle.', 'error')
        cerrarModalDetalle()
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
const formProrroga = useForm({
    id_linea: '',
    prorroga_hasta: '',
    motivo: '',
})

const cerrarModalProrroga = () => {
    modalProrroga.value = false
    lineaProrroga.value = null
    formProrroga.reset()
    formProrroga.clearErrors()
}

const abrirProrroga = (linea) => {
    lineaProrroga.value = linea
    formProrroga.id_linea = linea.id
    formProrroga.prorroga_hasta = ''
    formProrroga.motivo = ''
    formProrroga.clearErrors()
    modalProrroga.value = true
}

const guardarProrroga = () => {
    formProrroga.post(route('admin.periodos.prorroga', detalleData.value.periodo.id), {
        preserveScroll: true,
        onSuccess: async () => {
            cerrarModalProrroga()
            await verDetalle(periodoDetalle.value)
            mostrarFlash('Prórroga guardada correctamente.', 'success')
        },
        onError: () => mostrarFlash('Revisa los datos de la prórroga.', 'error'),
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
        preserveScroll: true,
        onFinish: () => {
            toggling.value = null
        },
    })
}

// ── Filtros generales ─────────────────────────────────────────────────────────
const busqueda = ref('')

const periodosFiltrados = computed(() =>
    props.periodos.filter(p =>
        !busqueda.value ||
        (p.nombre ?? '').toLowerCase().includes(busqueda.value.toLowerCase())
    )
)

// ── Filtros y orden del detalle ───────────────────────────────────────────────
const filtroDetalle = ref('todos') // todos | reportaron | pendientes
const busquedaDetalle = ref('')
const sortBy = ref('id')
const sortDir = ref('asc')

const toggleSort = (field) => {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortBy.value = field
        sortDir.value = field === 'fecha_avance' ? 'desc' : 'asc'
    }
}

const sortIcon = (field) => {
    if (sortBy.value !== field) return '↕'
    return sortDir.value === 'asc' ? '↑' : '↓'
}

const lineasDetalleProcesadas = computed(() => {
    if (!detalleData.value?.lineas) return []

    let rows = [...detalleData.value.lineas]

    // filtro por tabs
    rows = rows.filter(l => {
        if (filtroDetalle.value === 'reportaron') return !!l.reporto
        if (filtroDetalle.value === 'pendientes') return !l.reporto
        return true
    })

    // búsqueda de detalle
    const q = busquedaDetalle.value.trim().toLowerCase()
    if (q) {
        rows = rows.filter(l => {
            const blob = [
                l.id,
                l.prioridad,
                l.organismo,
                l.usuario,
                l.estatus,
                l.fecha_avance,
                l.documento,
                l.medio_verificacion,
                l.comentario,
                l.avances_linea,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase()

            return blob.includes(q)
        })
    }

    // orden
    rows.sort((a, b) => {
        let av = a?.[sortBy.value]
        let bv = b?.[sortBy.value]

        if (sortBy.value === 'fecha_avance') {
            const ad = a.fecha_avance
                ? a.fecha_avance.split('/').reverse().join('-')
                : ''
            const bd = b.fecha_avance
                ? b.fecha_avance.split('/').reverse().join('-')
                : ''
            av = ad
            bv = bd
        }

        if (sortBy.value === 'porcentaje') {
            av = Number(av ?? -1)
            bv = Number(bv ?? -1)
        }

        if (sortBy.value === 'id') {
            av = Number(av ?? 0)
            bv = Number(bv ?? 0)
        }

        if (av === null || av === undefined) av = ''
        if (bv === null || bv === undefined) bv = ''

        if (typeof av === 'string') av = av.toLowerCase()
        if (typeof bv === 'string') bv = bv.toLowerCase()

        if (av < bv) return sortDir.value === 'asc' ? -1 : 1
        if (av > bv) return sortDir.value === 'asc' ? 1 : -1
        return 0
    })

    return rows
})

// ── Helpers ───────────────────────────────────────────────────────────────────
const pctColor = (pct) => {
    const n = Number(pct ?? 0)
    if (n >= 80) return 'var(--color-verde)'
    if (n >= 50) return 'var(--color-magenta)'
    return 'var(--color-vino)'
}

const fmtNum = (n) =>
    n !== null && n !== undefined && n !== ''
        ? Number(n).toLocaleString('es-MX', {
            maximumFractionDigits: 4,
            useGrouping: false,
        })
        : '—'

const colorIcono = (tipo) => {
    if (!tipo) return '#6b7280'

    const t = tipo.toLowerCase()
    if (t.includes('pdf')) return '#dc2626'
    if (t.includes('image')) return '#7c3aed'
    if (t.includes('word') || t.includes('document')) return '#2563eb'

    return '#6b7280'
}
</script>

<template>
    <AdminLayout>
        <Transition name="flash">
            <div v-if="flashMsg" :class="['flash-bar', `flash-bar--${flashType}`]">
                <svg
                    v-if="flashType === 'success'"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    style="width:16px;height:16px;flex-shrink:0"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd"
                    />
                </svg>

                <svg
                    v-else
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    style="width:16px;height:16px;flex-shrink:0"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>

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
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px">
                        <path
                            fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    Nuevo período
                </button>
            </div>

            <div class="search-bar" style="margin-bottom:1rem">
                <svg
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    style="width:16px;height:16px;flex-shrink:0;color:var(--color-gris-400)"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"
                    />
                </svg>

                <input
                    v-model="busqueda"
                    type="text"
                    placeholder="Buscar período..."
                    class="search-input"
                />

                <span class="search-count">
                    {{ periodosFiltrados.length }} período{{ periodosFiltrados.length !== 1 ? 's' : '' }}
                </span>
            </div>

            <div v-if="periodosFiltrados.length === 0" class="empty-state">
                <p>No hay períodos de reporte creados.</p>
                <button class="btn btn-secondary btn-sm" @click="modalCrear = true">
                    Crear primer período
                </button>
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
                        <div class="fecha-item">
                            <span class="fecha-label">Inicio</span>
                            <span class="fecha-val">{{ p.fecha_inicio }}</span>
                        </div>

                        <div class="fecha-sep">→</div>

                        <div class="fecha-item">
                            <span class="fecha-label">Fin</span>
                            <span class="fecha-val">{{ p.fecha_fin }}</span>
                        </div>

                        <div class="fecha-sep" style="margin-left:auto">Límite:</div>

                        <div class="fecha-item">
                            <span class="fecha-val fecha-val--limite">{{ p.fecha_limite_reporte }}</span>
                        </div>
                    </div>

                    <div class="periodo-stats">
                        <div class="stat-item">
                            <span class="stat-num">{{ p.total_lineas }}</span>
                            <span class="stat-label">líneas</span>
                        </div>

                        <div class="stat-item">
                            <span class="stat-num">{{ p.total_avances }}</span>
                            <span class="stat-label">avances</span>
                        </div>

                        <div class="stat-item" style="margin-left:auto">
                            <div class="mini-bar">
                                <div
                                    class="mini-bar-fill"
                                    :style="{
                                        width: Math.min(p.porcentaje ?? 0, 100) + '%',
                                        background: pctColor(p.porcentaje ?? 0)
                                    }"
                                ></div>
                            </div>
                            <span class="stat-pct">{{ Math.round(p.porcentaje ?? 0) }}% reportaron</span>
                        </div>
                    </div>

                    <div class="periodo-acciones">
                        <button class="btn btn-secondary btn-sm" @click="verDetalle(p)">
                            Ver detalle
                        </button>

                        <button
                            :class="['btn btn-sm', p.activo ? 'btn-danger' : 'btn-primary']"
                            :disabled="toggling === p.id"
                            @click="togglePeriodo(p)"
                        >
                            {{ p.activo ? 'Cerrar período' : 'Abrir período' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL CREAR -->
        <Teleport to="body">
            <div v-if="modalCrear" class="modal-overlay" @click.self="cerrarModalCrear">
                <div class="modal modal--md">
                    <div class="modal-head">
                        <h2 class="modal-title">Nuevo período de reporte</h2>
                        <button class="modal-close" @click="cerrarModalCrear">×</button>
                    </div>

                    <div class="modal-body">
                        <div class="field">
                            <label class="field-label">Nombre *</label>
                            <input v-model="formCrear.nombre" type="text" class="field-input" />
                            <p v-if="formCrear.errors.nombre" class="field-error">{{ formCrear.errors.nombre }}</p>
                        </div>

                        <div class="field-row">
                            <div class="field">
                                <label class="field-label">Fecha inicio *</label>
                                <input v-model="formCrear.fecha_inicio" type="date" class="field-input" />
                                <p v-if="formCrear.errors.fecha_inicio" class="field-error">{{ formCrear.errors.fecha_inicio }}</p>
                            </div>

                            <div class="field">
                                <label class="field-label">Fecha fin *</label>
                                <input v-model="formCrear.fecha_fin" type="date" class="field-input" />
                                <p v-if="formCrear.errors.fecha_fin" class="field-error">{{ formCrear.errors.fecha_fin }}</p>
                            </div>
                        </div>

                        <div class="field">
                            <label class="field-label">Fecha límite de reporte *</label>
                            <input v-model="formCrear.fecha_limite_reporte" type="date" class="field-input" />
                            <p v-if="formCrear.errors.fecha_limite_reporte" class="field-error">
                                {{ formCrear.errors.fecha_limite_reporte }}
                            </p>
                        </div>

                        <div class="field">
                            <label class="field-label">Descripción</label>
                            <textarea v-model="formCrear.descripcion" class="field-input field-textarea" rows="2" />
                        </div>
                    </div>

                    <div class="modal-foot">
                        <button class="btn btn-ghost" @click="cerrarModalCrear">Cancelar</button>
                        <button class="btn btn-primary" @click="guardarPeriodo" :disabled="formCrear.processing">
                            {{ formCrear.processing ? 'Creando...' : 'Crear período' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL DETALLE -->
        <Teleport to="body">
            <div v-if="modalDetalle" class="modal-overlay" @click.self="cerrarModalDetalle">
                <div class="modal modal--xxxl">
                    <div class="modal-head modal-head--ver">
                        <div>
                            <h2 class="modal-title">{{ detalleData?.periodo?.nombre ?? periodoDetalle?.nombre }}</h2>
                            <p v-if="detalleData" class="modal-subtitle">
                                {{ detalleData.stats.reportaron }} de {{ detalleData.stats.total }} líneas reportaron
                                ({{ detalleData.stats.porcentaje }}%)
                            </p>
                        </div>

                        <button class="modal-close" @click="cerrarModalDetalle">×</button>
                    </div>

                    <div class="modal-body">
                        <div v-if="cargandoDetalle" class="detalle-loading">
                            <span>Cargando...</span>
                        </div>

                        <template v-else-if="detalleData">
                            <div class="stats-row">
                                <div class="stat-card stat-card--total">
                                    <span class="stat-card-num">{{ detalleData.stats.total }}</span>
                                    <span class="stat-card-label">Total líneas</span>
                                </div>

                                <div class="stat-card stat-card--ok">
                                    <span class="stat-card-num">{{ detalleData.stats.reportaron }}</span>
                                    <span class="stat-card-label">Reportaron</span>
                                </div>

                                <div class="stat-card stat-card--pend">
                                    <span class="stat-card-num">{{ detalleData.stats.sin_reporte }}</span>
                                    <span class="stat-card-label">Sin reporte</span>
                                </div>

                                <div class="stat-card stat-card--pct">
                                    <span class="stat-card-num">{{ detalleData.stats.porcentaje }}%</span>
                                    <span class="stat-card-label">Cumplimiento</span>
                                </div>
                            </div>

                            <div class="detalle-layout">
                                <!-- FILTROS VERTICALES -->
                                <aside class="detalle-sidebar">
                                    <div class="sidebar-card">
                                        <h3 class="sidebar-title">Filtros</h3>

                                        <label class="field-label">Buscar</label>
                                        <input
                                            v-model="busquedaDetalle"
                                            type="text"
                                            class="field-input"
                                            placeholder="ID, prioridad, organismo..."
                                        />

                                        <div class="sidebar-group">
                                            <button
                                                :class="['side-filter-btn', filtroDetalle === 'todos' ? 'side-filter-btn--active' : '']"
                                                @click="filtroDetalle = 'todos'"
                                            >
                                                Todas ({{ detalleData.stats.total }})
                                            </button>

                                            <button
                                                :class="['side-filter-btn', filtroDetalle === 'reportaron' ? 'side-filter-btn--active' : '']"
                                                @click="filtroDetalle = 'reportaron'"
                                            >
                                                Reportaron ({{ detalleData.stats.reportaron }})
                                            </button>

                                            <button
                                                :class="['side-filter-btn', filtroDetalle === 'pendientes' ? 'side-filter-btn--active' : '']"
                                                @click="filtroDetalle = 'pendientes'"
                                            >
                                                Pendientes ({{ detalleData.stats.sin_reporte }})
                                            </button>
                                        </div>

                                        <div class="sidebar-meta">
                                            Mostrando {{ lineasDetalleProcesadas.length }} de {{ detalleData.stats.total }}
                                        </div>
                                    </div>
                                </aside>

                                <!-- TABLA -->
                                <section class="detalle-main">
                                    <div class="detalle-tabla-wrap">
                                        <table class="tabla">
                                            <thead>
                                            <tr>
                                                <th class="th"></th>
                                                <th class="th th-sort" @click="toggleSort('id')">
                                                    ID <span class="sort-mark">{{ sortIcon('id') }}</span>
                                                </th>
                                                <th class="th th-sort" @click="toggleSort('prioridad')">
                                                    Prioridad <span class="sort-mark">{{ sortIcon('prioridad') }}</span>
                                                </th>
                                                <th class="th th-sort" @click="toggleSort('organismo')">
                                                    Organismo <span class="sort-mark">{{ sortIcon('organismo') }}</span>
                                                </th>
                                                <th class="th th--center th-sort" @click="toggleSort('estatus')">
                                                    Estado <span class="sort-mark">{{ sortIcon('estatus') }}</span>
                                                </th>
                                                <th class="th th--center th-sort" @click="toggleSort('porcentaje')">
                                                    Avance <span class="sort-mark">{{ sortIcon('porcentaje') }}</span>
                                                </th>
                                                <th class="th th--center th-sort" @click="toggleSort('fecha_avance')">
                                                    Fecha <span class="sort-mark">{{ sortIcon('fecha_avance') }}</span>
                                                </th>
                                                <th class="th th--center">Acción</th>
                                            </tr>
                                            </thead>

                                            <tbody>
                                            <template v-for="l in lineasDetalleProcesadas" :key="l.id">
                                                <tr :class="['tr', l.reporto ? 'tr--reporto' : '']">
                                                    <td class="td" style="width:32px;padding:.5rem .5rem">
                                                        <button
                                                            v-if="l.reporto"
                                                            class="btn-expand"
                                                            @click="toggleFila(l.id)"
                                                        >
                                                            {{ filaExpandida === l.id ? '⌃' : '⌄' }}
                                                        </button>
                                                    </td>

                                                    <td class="td">{{ l.id }}</td>

                                                    <td class="td td--desc" :title="l.prioridad">
                                                        {{ l.prioridad }}
                                                    </td>

                                                    <td class="td">
                                                        <p class="org-nombre">{{ l.organismo }}</p>
                                                        <p class="org-usuario">{{ l.usuario }}</p>
                                                        <p v-if="l.prorroga_hasta" class="org-usuario">
                                                            Prórroga hasta: {{ l.prorroga_hasta }}
                                                        </p>
                                                    </td>

                                                    <td class="td td--center">
                                                            <span :class="['badge-reporte', l.reporto ? 'badge-reporte--ok' : 'badge-reporte--pend']">
                                                                {{ l.reporto ? 'Reportó' : 'Pendiente' }}
                                                            </span>
                                                    </td>

                                                    <td class="td td--center">
                                                        <template v-if="l.reporto">
                                                            <div class="avance-wrap">
                                                                <div class="avance-bar">
                                                                    <div
                                                                        class="avance-fill"
                                                                        :style="{ width: Math.min(l.porcentaje ?? 0, 100) + '%' }"
                                                                    ></div>
                                                                </div>
                                                                <span class="avance-pct">{{ l.porcentaje ?? 0 }}%</span>
                                                            </div>
                                                        </template>
                                                        <span v-else class="text-muted">—</span>
                                                    </td>

                                                    <td class="td td--center">
                                                        <span class="text-sm">{{ l.fecha_avance ?? '—' }}</span>
                                                    </td>

                                                    <td class="td td--center">
                                                        <button v-if="!l.reporto" class="btn-text" @click="abrirProrroga(l)">
                                                            Prórroga
                                                        </button>
                                                        <span v-else class="text-muted">—</span>
                                                    </td>
                                                </tr>

                                                <tr v-if="l.reporto && filaExpandida === l.id" class="tr-detalle">
                                                    <td colspan="8" class="td-detalle">
                                                        <div class="avance-detalle">
                                                            <div class="avance-detalle-top">
                                                                    <span
                                                                        :class="[
                                                                            'ev-estatus',
                                                                            l.estatus === 'Con avances'
                                                                                ? 'ev-estatus--ok'
                                                                                : 'ev-estatus--pend'
                                                                        ]"
                                                                    >
                                                                        {{ l.estatus ?? 'Sin estatus' }}
                                                                    </span>

                                                                <span class="avance-detalle-valor">
                                                                        {{ fmtNum(l.valor_avance) }}
                                                                        <span class="avance-detalle-unidad">{{ l.unidad_medida }}</span>
                                                                    </span>

                                                                <span class="avance-detalle-meta">
                                                                        de {{ fmtNum(l.meta) }} {{ l.unidad_medida }}
                                                                    </span>
                                                            </div>

                                                            <p v-if="l.comentario" class="avance-detalle-comentario">
                                                                {{ l.comentario }}
                                                            </p>

                                                            <p v-if="l.avances_linea" class="avance-detalle-nota">
                                                                <strong>Nota:</strong> {{ l.avances_linea }}
                                                            </p>

                                                            <div class="avance-detalle-evidencias">
                                                                <div v-if="l.medio_verificacion" class="ev-medio">
                                                                    {{ l.medio_verificacion }}
                                                                </div>

                                                                <a
                                                                    v-if="l.archivo_url"
                                                                    :href="l.archivo_url"
                                                                    target="_blank"
                                                                    class="ev-archivo-link"
                                                                >
                                                                    <div
                                                                        class="ev-archivo-icon-sm"
                                                                        :style="{
                                                                                background: colorIcono(l.archivo_tipo) + '18',
                                                                                color: colorIcono(l.archivo_tipo)
                                                                            }"
                                                                    >
                                                                        📄
                                                                    </div>
                                                                    {{ l.documento ?? 'Ver archivo' }}
                                                                </a>

                                                                <a
                                                                    v-if="l.url"
                                                                    :href="l.url"
                                                                    target="_blank"
                                                                    class="ev-url-link"
                                                                >
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
                                </section>
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
                        <button class="btn btn-ghost" @click="cerrarModalDetalle">Cerrar</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- MODAL PRÓRROGA -->
        <Teleport to="body">
            <div v-if="modalProrroga" class="modal-overlay" @click.self="cerrarModalProrroga">
                <div class="modal modal--sm">
                    <div class="modal-head">
                        <h2 class="modal-title">Habilitar prórroga</h2>
                        <button class="modal-close" @click="cerrarModalProrroga">×</button>
                    </div>

                    <div class="modal-body">
                        <p class="prorroga-linea">{{ lineaProrroga?.prioridad }}</p>

                        <div class="field">
                            <label class="field-label">Nueva fecha límite *</label>
                            <input v-model="formProrroga.prorroga_hasta" type="date" class="field-input" />
                            <p v-if="formProrroga.errors.prorroga_hasta" class="field-error">
                                {{ formProrroga.errors.prorroga_hasta }}
                            </p>
                        </div>

                        <div class="field">
                            <label class="field-label">Motivo *</label>
                            <textarea
                                v-model="formProrroga.motivo"
                                class="field-input field-textarea"
                                rows="3"
                            />
                            <p v-if="formProrroga.errors.motivo" class="field-error">{{ formProrroga.errors.motivo }}</p>
                        </div>
                    </div>

                    <div class="modal-foot">
                        <button class="btn btn-ghost" @click="cerrarModalProrroga">Cancelar</button>
                        <button class="btn btn-primary" @click="guardarProrroga" :disabled="formProrroga.processing">
                            {{ formProrroga.processing ? 'Guardando...' : 'Habilitar prórroga' }}
                        </button>
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
.flash-bar--error { background:var(--color-vino);color:#fff; }

.page { max-width:1200px; }
.page-head { display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.5rem;gap:1rem; }
.page-breadcrumb { font-size:var(--text-xs);color:var(--color-gris-400);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem; }
.page-title { font-family:var(--font-display);font-size:var(--text-2xl);color:var(--color-gris-800); }

.search-bar { display:flex;align-items:center;gap:.75rem;background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.6rem 1rem;box-shadow:var(--shadow-sm); }
.search-input { flex:1;border:none;outline:none;font-size:var(--text-sm);color:var(--color-gris-700);background:transparent; }
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
.badge-estado--activo { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-estado--inactivo { background:var(--color-gris-200);color:var(--color-gris-500); }

.modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:200;padding:1rem; }
.modal { background:var(--color-blanco);border-radius:var(--radius-xl);box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-height:92vh;display:flex;flex-direction:column; }
.modal--md { max-width:540px; }
.modal--sm { max-width:400px; }
.modal--xxxl { max-width:1280px; }
.modal-head { display:flex;align-items:flex-start;justify-content:space-between;padding:1.1rem 1.5rem;border-bottom:1px solid var(--color-gris-200);flex-shrink:0; }
.modal-head--ver { background:var(--color-gris-50,#fafafa); }
.modal-title { font-family:var(--font-display);font-size:var(--text-lg);color:var(--color-gris-800); }
.modal-subtitle { font-size:var(--text-sm);color:var(--color-gris-500);margin-top:2px; }
.modal-close { width:30px;height:30px;border-radius:var(--radius-md);border:none;background:transparent;cursor:pointer;color:var(--color-gris-400);font-size:24px;line-height:1; }
.modal-body { padding:1.5rem;display:flex;flex-direction:column;gap:1rem;overflow:auto; }
.modal-foot { display:flex;align-items:center;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;border-top:1px solid var(--color-gris-200);flex-shrink:0; }

.stats-row { display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem; }
.stat-card { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1rem;border-radius:var(--radius-md);border:1px solid var(--color-gris-200); }
.stat-card-num { font-family:var(--font-display);font-size:var(--text-2xl);font-weight:700;line-height:1.1; }
.stat-card-label { font-size:var(--text-xs);text-transform:uppercase;letter-spacing:.06em;margin-top:4px; }
.stat-card--total { background:var(--color-gris-50,#fafafa);color:var(--color-gris-700); }
.stat-card--ok { background:var(--color-verde-lt);color:var(--color-verde); }
.stat-card--pend { background:var(--color-vino-lt);color:var(--color-vino); }
.stat-card--pct { background:var(--color-magenta-lt);color:var(--color-magenta); }

.detalle-layout { display:grid;grid-template-columns:260px 1fr;gap:1rem;align-items:start; }
.detalle-sidebar { position:sticky;top:0; }
.sidebar-card { border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:1rem;background:#fff;display:flex;flex-direction:column;gap:.75rem; }
.sidebar-title { font-size:var(--text-sm);font-weight:700;color:var(--color-gris-700); }
.sidebar-group { display:flex;flex-direction:column;gap:.5rem; }
.side-filter-btn { text-align:left;padding:.6rem .75rem;border:1px solid var(--color-gris-200);border-radius:var(--radius-md);background:#fff;cursor:pointer;font-size:var(--text-sm);font-weight:600;color:var(--color-gris-600); }
.side-filter-btn--active { border-color:var(--color-verde);color:var(--color-verde);background:var(--color-verde-lt); }
.sidebar-meta { font-size:var(--text-xs);color:var(--color-gris-500); }

.detalle-tabla-wrap { border:1px solid var(--color-gris-200);border-radius:var(--radius-md);overflow:auto;max-height:62vh; }
.tabla { width:100%;border-collapse:collapse;min-width:1000px; }
.th { padding:.7rem .75rem;font-size:var(--text-xs);font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-500);background:var(--color-gris-100);border-bottom:1px solid var(--color-gris-200);text-align:left;white-space:nowrap; }
.th--center { text-align:center; }
.th-sort { cursor:pointer;user-select:none; }
.sort-mark { margin-left:4px;font-size:11px;color:var(--color-gris-400); }

.tr { border-bottom:1px solid var(--color-gris-100);transition:background .2s; }
.tr:last-child { border-bottom:none; }
.tr--reporto:hover { background:var(--color-gris-50,#fafafa); }
.td { padding:.55rem .75rem;font-size:var(--text-sm);color:var(--color-gris-700);vertical-align:middle; }
.td--center { text-align:center; }
.td--desc { max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.org-nombre { font-weight:600;color:var(--color-gris-800);font-size:var(--text-sm); }
.org-usuario { font-size:11px;color:var(--color-gris-500); }
.badge-reporte { display:inline-flex;align-items:center;padding:.15rem .6rem;border-radius:var(--radius-full);font-size:11px;font-weight:700; }
.badge-reporte--ok { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-reporte--pend { background:var(--color-magenta-lt);color:var(--color-magenta); }
.avance-wrap { display:flex;flex-direction:column;align-items:center;gap:2px; }
.avance-bar { width:60px;height:4px;background:var(--color-gris-200);border-radius:var(--radius-full);overflow:hidden; }
.avance-fill { height:100%;background:var(--color-verde);border-radius:var(--radius-full); }
.avance-pct { font-size:11px;font-weight:700;color:var(--color-gris-600); }
.text-muted { color:var(--color-gris-300);font-size:var(--text-xs); }
.text-sm { font-size:var(--text-xs);color:var(--color-gris-600); }
.btn-text { background:none;border:none;cursor:pointer;font-size:var(--text-xs);font-weight:600;color:var(--color-verde);text-decoration:underline;padding:0; }
.btn-expand { width:24px;height:24px;border-radius:var(--radius-sm);border:1px solid var(--color-gris-200);background:var(--color-gris-50,#fafafa);cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--color-gris-500); }

.tr-detalle { background:var(--color-gris-50,#fafafa);border-bottom:2px solid var(--color-verde-lt); }
.td-detalle { padding:0 !important; }
.avance-detalle { padding:1rem 1.25rem;display:flex;flex-direction:column;gap:.625rem;border-left:3px solid var(--color-verde); }
.avance-detalle-top { display:flex;align-items:center;gap:.75rem;flex-wrap:wrap; }
.ev-estatus { display:inline-flex;align-items:center;padding:2px 8px;border-radius:var(--radius-full);font-size:11px;font-weight:700; }
.ev-estatus--ok { background:var(--color-verde-lt);color:var(--color-verde); }
.ev-estatus--pend { background:var(--color-magenta-lt);color:var(--color-magenta); }
.avance-detalle-valor { font-size:var(--text-lg);font-weight:700;font-family:var(--font-display);color:var(--color-gris-800); }
.avance-detalle-unidad { font-size:var(--text-sm);font-weight:400;color:var(--color-gris-500); }
.avance-detalle-meta { font-size:var(--text-xs);color:var(--color-gris-400); }
.avance-detalle-comentario { font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.5;background:var(--color-blanco);padding:.5rem .75rem;border-radius:var(--radius-md);border:1px solid var(--color-gris-200); }
.avance-detalle-nota { font-size:var(--text-xs);color:var(--color-gris-500);padding:.35rem .6rem;background:var(--color-gris-100);border-radius:var(--radius-sm); }
.avance-detalle-evidencias { display:flex;align-items:center;gap:1rem;flex-wrap:wrap; }
.ev-medio { display:flex;align-items:center;gap:5px;font-size:12px;color:var(--color-gris-500); }
.ev-archivo-link { display:inline-flex;align-items:center;gap:6px;font-size:var(--text-xs);font-weight:600;color:var(--color-gris-700);text-decoration:none;padding:.3rem .6rem;background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-md); }
.ev-archivo-icon-sm { width:22px;height:22px;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.ev-url-link { display:inline-flex;align-items:center;gap:5px;font-size:var(--text-xs);font-weight:600;color:#2563eb;text-decoration:none; }

.field { display:flex;flex-direction:column;gap:.35rem; }
.field-row { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
.field-label { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-700); }
.field-input { width:100%;padding:.7rem .85rem;border:1px solid var(--color-gris-200);border-radius:var(--radius-md);background:#fff; }
.field-error { font-size:var(--text-xs);color:var(--color-vino);font-weight:600; }
.field-textarea { resize:vertical;min-height:60px; }
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

/* ── Responsive ── */
@media (max-width: 1100px) {
    .detalle-layout { grid-template-columns:1fr; }
    .detalle-sidebar { position:static; }
}

@media (max-width: 900px) {
    .stats-row { grid-template-columns:repeat(2,1fr); }
    .field-row { grid-template-columns:1fr; }
    .periodos-grid { grid-template-columns:1fr; }
}

@media (max-width: 640px) {
    .page-head { flex-direction:column;align-items:stretch; }
    .periodo-acciones { flex-direction:column; }
    .stats-row { grid-template-columns:1fr; }
}
</style>
