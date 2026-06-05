<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed, watch, nextTick } from 'vue'
import { useForm, router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    lineas:      { type: Array, default: () => [] },
    ejes:        { type: Array, default: () => [] },
    estrategias: { type: Array, default: () => [] },
    plazos:      { type: Array, default: () => [] },
    frecuencias: { type: Array, default: () => [] },
    estatus:     { type: Array, default: () => [] },
    usuarios:    { type: Array, default: () => [] },
})

// ── Flash ─────────────────────────────────────────────────────────────────────
const page        = usePage()
const flashMsg    = ref(null)
const flashType   = ref('success') // 'success' | 'error'
let   flashTimer  = null

const mostrarFlash = (msg, tipo = 'success') => {
    flashMsg.value  = msg
    flashType.value = tipo
    clearTimeout(flashTimer)
    flashTimer = setTimeout(() => { flashMsg.value = null }, 4000)
}

watch(() => page.props.flash?.success, v => { if (v) mostrarFlash(v, 'success') })
watch(() => page.props.flash?.error,   v => { if (v) mostrarFlash(v, 'error')   })

// ── Modal / Form ──────────────────────────────────────────────────────────────
const modal       = ref(false)
const lineaActual = ref(null)

const form = useForm({
    id_eje:            null,
    id_objetivo:       null,
    id_prioridad:      null,
    id_estrategia:     null,
    id_plazo:          null,
    id_frecuencia:     null,
    id_estatus:        null,
    id_usuario:        null,
    nombre_indicador:  '',
    formula:           '',
    linea_base:        '',
    sentido_indicador: '',
    meta:              '',
    unidad_medida:     '',
    activo:            true,
})

const objetivos       = ref([])
const prioridades     = ref([])
const loadingObj      = ref(false)
const loadingPri      = ref(false)
const bloqueandoWatch = ref(false)

const cargarObjetivos = async (id_eje) => {
    if (!id_eje) { objetivos.value = []; prioridades.value = []; return }
    loadingObj.value = true
    try {
        const res = await fetch(route('admin.lineas.cascade.objetivos', id_eje))
        objetivos.value = await res.json()
    } finally { loadingObj.value = false }
}

const cargarPrioridades = async (id_objetivo) => {
    if (!id_objetivo) { prioridades.value = []; return }
    loadingPri.value = true
    try {
        const res = await fetch(route('admin.lineas.cascade.prioridades', id_objetivo))
        prioridades.value = await res.json()
    } finally { loadingPri.value = false }
}

// Los watches solo actúan si NO estamos cargando una edición
watch(() => form.id_eje, (val) => {
    if (bloqueandoWatch.value) return
    form.id_objetivo  = null
    form.id_prioridad = null
    prioridades.value = []
    cargarObjetivos(val)
})

watch(() => form.id_objetivo, (val) => {
    if (bloqueandoWatch.value) return
    form.id_prioridad = null
    cargarPrioridades(val)
})

// ── Acciones de modal ─────────────────────────────────────────────────────────
const abrirVer = (linea) => { lineaActual.value = linea; modal.value = 'ver' }

const abrirCrear = () => {
    form.reset()
    form.clearErrors()
    objetivos.value   = []
    prioridades.value = []
    lineaActual.value = null
    modal.value       = 'crear'
}

const abrirEditar = async (linea) => {
    form.clearErrors()
    lineaActual.value    = linea
    bloqueandoWatch.value = true

    // Cargar cascades primero
    await cargarObjetivos(linea.id_eje)
    await cargarPrioridades(linea.id_objetivo)

    // Asignar valores como números para que coincidan con los :value de los option
    form.id_eje           = Number(linea.id_eje)
    form.id_objetivo      = Number(linea.id_objetivo)
    form.id_prioridad     = Number(linea.id_prioridad)
    form.id_estrategia    = linea.id_estrategia   ? Number(linea.id_estrategia)  : null
    form.id_plazo         = linea.id_plazo        ? Number(linea.id_plazo)       : null
    form.id_frecuencia    = linea.id_frecuencia   ? Number(linea.id_frecuencia)  : null
    form.id_estatus       = linea.id_estatus      ? Number(linea.id_estatus)     : null
    form.id_usuario       = Number(linea.id_usuario)
    form.nombre_indicador  = linea.nombre_indicador  ?? ''
    form.formula           = linea.formula           ?? ''
    form.linea_base        = linea.linea_base        ?? ''
    form.sentido_indicador = linea.sentido_indicador ?? ''
    form.meta              = linea.meta              ?? ''
    form.unidad_medida     = linea.unidad_medida     ?? ''
    form.activo            = linea.activo

    // Esperar un tick para que Vue procese los valores antes de reactivar los watches
    await nextTick()
    bloqueandoWatch.value = false
    modal.value = 'editar'
}

const abrirEliminar = (linea) => { lineaActual.value = linea; modal.value = 'eliminar' }

const cerrar = () => {
    modal.value        = false
    lineaActual.value  = null
    bloqueandoWatch.value = false
    form.reset()
    form.clearErrors()
    objetivos.value   = []
    prioridades.value = []
}

const guardar = () => {
    const opciones = { onSuccess: cerrar }
    if (modal.value === 'crear') {
        form.post(route('admin.lineas.store'), opciones)
    } else {
        form.put(route('admin.lineas.update', lineaActual.value.id), opciones)
    }
}

const confirmarEliminar = () => {
    router.delete(route('admin.lineas.destroy', lineaActual.value.id), {
        onSuccess: cerrar,
    })
}

// ── Filtros ───────────────────────────────────────────────────────────────────
const busqueda         = ref('')
const filtroEje        = ref('')
const filtroEstatus    = ref('')
const filtroFrecuencia = ref('')
const filtroPlazo      = ref('')
const filtroIndicador  = ref('')
const filtroActivo     = ref('')

const hayFiltros = computed(() =>
    busqueda.value || filtroEje.value || filtroEstatus.value ||
    filtroFrecuencia.value || filtroPlazo.value ||
    filtroIndicador.value  || filtroActivo.value
)

const limpiarFiltros = () => {
    busqueda.value = ''; filtroEje.value = ''; filtroEstatus.value = ''
    filtroFrecuencia.value = ''; filtroPlazo.value = ''
    filtroIndicador.value  = ''; filtroActivo.value = ''
}

const lineasFiltradas = computed(() =>
    props.lineas.filter(l => {
        const matchEje   = !filtroEje.value        || l.id_eje        == filtroEje.value
        const matchEst   = !filtroEstatus.value    || l.id_estatus    == filtroEstatus.value
        const matchFrec  = !filtroFrecuencia.value || l.id_frecuencia == filtroFrecuencia.value
        const matchPlazo = !filtroPlazo.value      || l.id_plazo      == filtroPlazo.value
        const matchInd   = !filtroIndicador.value  ||
            (filtroIndicador.value === 'completo'  &&  l.indicador_completo) ||
            (filtroIndicador.value === 'pendiente' && !l.indicador_completo)
        const matchAct   = !filtroActivo.value ||
            (filtroActivo.value === 'activo'   &&  l.activo) ||
            (filtroActivo.value === 'inactivo' && !l.activo)
        const q      = busqueda.value.toLowerCase()
        const matchQ = !q ||
            l.prioridad?.toLowerCase().includes(q) ||
            l.organismo?.toLowerCase().includes(q) ||
            l.usuario?.toLowerCase().includes(q)   ||
            String(l.numero_prioridad).includes(q)
        return matchEje && matchEst && matchFrec && matchPlazo && matchInd && matchAct && matchQ
    })
)

// ── Chips activos ─────────────────────────────────────────────────────────────
const chipsActivos = computed(() => {
    const chips = []
    if (filtroEje.value) {
        const e = props.ejes.find(e => e.id == filtroEje.value)
        if (e) chips.push({ key: 'eje',   label: `Eje ${String(e.numero_eje).padStart(2,'0')}`, clear: () => filtroEje.value = '' })
    }
    if (filtroPlazo.value) {
        const p = props.plazos.find(p => p.id == filtroPlazo.value)
        if (p) chips.push({ key: 'plazo', label: p.plazo, clear: () => filtroPlazo.value = '' })
    }
    if (filtroFrecuencia.value) {
        const f = props.frecuencias.find(f => f.id == filtroFrecuencia.value)
        if (f) chips.push({ key: 'frec',  label: f.frecuencia, clear: () => filtroFrecuencia.value = '' })
    }
    if (filtroEstatus.value) {
        const s = props.estatus.find(s => s.id == filtroEstatus.value)
        if (s) chips.push({ key: 'est',   label: s.nombre, clear: () => filtroEstatus.value = '' })
    }
    if (filtroIndicador.value) chips.push({
        key: 'ind',
        label: filtroIndicador.value === 'completo' ? 'Indicador completo' : 'Indicador pendiente',
        clear: () => filtroIndicador.value = '',
    })
    if (filtroActivo.value) chips.push({
        key: 'act',
        label: filtroActivo.value === 'activo' ? 'Solo activas' : 'Solo inactivas',
        clear: () => filtroActivo.value = '',
    })
    if (busqueda.value) chips.push({ key: 'q', label: `"${busqueda.value}"`, clear: () => busqueda.value = '' })
    return chips
})

// ── Paginación ────────────────────────────────────────────────────────────────
const POR_PAGINA   = 15
const pagina       = ref(1)
const totalPaginas = computed(() => Math.max(1, Math.ceil(lineasFiltradas.value.length / POR_PAGINA)))
const lineasPagina = computed(() => {
    const ini = (pagina.value - 1) * POR_PAGINA
    return lineasFiltradas.value.slice(ini, ini + POR_PAGINA)
})
watch([busqueda, filtroEje, filtroEstatus, filtroFrecuencia, filtroPlazo, filtroIndicador, filtroActivo],
    () => { pagina.value = 1 })

const correlativos = computed(() => {
    const map = {}
    lineasFiltradas.value.forEach((l, i) => { map[l.id] = i + 1 })
    return map
})

// ── Helpers ───────────────────────────────────────────────────────────────────
const truncar  = (txt, max = 60) => txt?.length > max ? txt.slice(0, max) + '…' : txt
const fmtNum   = (n) => n !== null && n !== '' && n !== undefined
    ? Number(n).toLocaleString('es-MX', { maximumFractionDigits: 4, useGrouping: false })
    : '—'
</script>

<template>
    <AdminLayout>

        <!-- Flash global -->
        <Transition name="flash">
            <div v-if="flashMsg" :class="['flash-bar', `flash-bar--${flashType}`]">
                <svg v-if="flashType === 'success'" viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <svg v-else viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                {{ flashMsg }}
            </div>
        </Transition>

        <div class="page">

            <div class="page-head">
                <div>
                    <p class="page-breadcrumb">PI-PEA</p>
                    <h1 class="page-title">Líneas de acción</h1>
                </div>
                <button class="btn btn-primary" @click="abrirCrear">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Nueva línea
                </button>
            </div>

            <!-- ── FILTROS ── -->
            <div class="filters">
                <div class="search-bar">
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px;flex-shrink:0;color:var(--color-gris-400)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input v-model="busqueda" type="text" placeholder="Buscar por prioridad, organismo o responsable..." class="search-input"/>
                    <span class="search-count">{{ lineasFiltradas.length }} de {{ lineas.length }}</span>
                </div>

                <!-- Fila 1: Clasificación PI-PEA -->
                <div class="filters-row">
                    <span class="filters-group-label">PI-PEA</span>
                    <select v-model="filtroEje" class="field-input select-filtro">
                        <option value="">Todos los ejes</option>
                        <option v-for="e in ejes" :key="e.id" :value="e.id">
                            Eje {{ String(e.numero_eje).padStart(2,'0') }} — {{ e.eje }}
                        </option>
                    </select>
                    <select v-model="filtroPlazo" class="field-input select-filtro">
                        <option value="">Todos los plazos</option>
                        <option v-for="p in plazos" :key="p.id" :value="p.id">{{ p.plazo }}</option>
                    </select>
                    <select v-model="filtroFrecuencia" class="field-input select-filtro">
                        <option value="">Todas las frecuencias</option>
                        <option v-for="f in frecuencias" :key="f.id" :value="f.id">{{ f.frecuencia }}</option>
                    </select>
                </div>

                <!-- Fila 2: Estado operativo -->
                <div class="filters-row">
                    <span class="filters-group-label">Estado</span>
                    <select v-model="filtroEstatus" class="field-input select-filtro">
                        <option value="">Todos los estatus</option>
                        <option v-for="s in estatus" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                    </select>
                    <select v-model="filtroIndicador" class="field-input select-filtro">
                        <option value="">Indicador: todos</option>
                        <option value="completo">Indicador completo</option>
                        <option value="pendiente">Indicador pendiente</option>
                    </select>
                    <select v-model="filtroActivo" class="field-input select-filtro">
                        <option value="">Activo: todos</option>
                        <option value="activo">Solo activas</option>
                        <option value="inactivo">Solo inactivas</option>
                    </select>
                    <button v-if="hayFiltros" class="btn-limpiar" @click="limpiarFiltros">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Limpiar
                    </button>
                </div>

                <!-- Chips -->
                <div v-if="chipsActivos.length" class="chips-row">
                    <span v-for="chip in chipsActivos" :key="chip.key" class="chip-filtro" @click="chip.clear()">
                        {{ chip.label }}
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:11px;height:11px;flex-shrink:0">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                </div>
            </div>

            <!-- ── TABLA ── -->
            <div class="tabla-wrap">
                <table class="tabla">
                    <thead>
                    <tr>
                        <th class="th th--num">#</th>
                        <th class="th th--sm">Eje</th>
                        <th class="th th--sm">Obj.</th>
                        <th class="th th--sm">Prior.</th>
                        <th class="th">Descripción</th>
                        <th class="th">Organismo / Responsable</th>
                        <th class="th th--center">Indicador</th>
                        <th class="th th--center">Avance</th>
                        <th class="th th--center">Estado</th>
                        <th class="th th--center">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-if="lineasPagina.length === 0">
                        <td colspan="10" class="tabla-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--color-gris-300)">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p>{{ hayFiltros ? 'Sin resultados para los filtros aplicados.' : 'No hay líneas de acción registradas.' }}</p>
                            <button v-if="hayFiltros" class="btn btn-secondary btn-sm" @click="limpiarFiltros">Limpiar filtros</button>
                            <button v-else class="btn btn-secondary btn-sm" @click="abrirCrear">Crear primera línea</button>
                        </td>
                    </tr>
                    <tr v-for="l in lineasPagina" :key="l.id" class="tr tr--clickable" @click="abrirVer(l)">
                        <td class="td td--center">
                            <span class="correlativo">{{ correlativos[l.id] }}</span>
                        </td>
                        <td class="td td--center">
                            <span class="badge-num badge-num--vino">{{ String(l.numero_eje).padStart(2,'0') }}</span>
                        </td>
                        <td class="td td--center">
                            <span class="badge-num badge-num--verde">{{ String(l.numero_objetivo).padStart(2,'0') }}</span>
                        </td>
                        <td class="td td--center">
                            <span class="badge-num badge-num--magenta">{{ String(l.numero_prioridad).padStart(2,'0') }}</span>
                        </td>
                        <td class="td td--desc" :title="l.prioridad">{{ truncar(l.prioridad) }}</td>
                        <td class="td">
                            <p class="org-nombre">{{ l.organismo }}</p>
                            <p class="org-usuario">{{ l.usuario }}</p>
                        </td>
                        <td class="td td--center">
                                <span :class="['badge-indicador', l.indicador_completo ? 'badge-indicador--ok' : 'badge-indicador--pend']">
                                    {{ l.indicador_completo ? 'Completo' : 'Pendiente' }}
                                </span>
                        </td>
                        <td class="td td--center">
                            <div class="avance-wrap">
                                <div class="avance-bar">
                                    <div class="avance-fill" :style="{ width: Math.min(l.porcentaje_avance ?? 0, 100) + '%' }"></div>
                                </div>
                                <span class="avance-pct">{{ l.porcentaje_avance ?? 0 }}%</span>
                            </div>
                        </td>
                        <td class="td td--center">
                                <span :class="['badge-estado', l.activo ? 'badge-estado--activo' : 'badge-estado--inactivo']">
                                    {{ l.activo ? 'Activa' : 'Inactiva' }}
                                </span>
                        </td>
                        <td class="td td--center td--acciones" @click.stop>
                            <button class="btn-ico btn-ico--view"   @click="abrirVer(l)"     title="Ver detalle">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                            </button>
                            <button class="btn-ico btn-ico--edit"   @click="abrirEditar(l)"  title="Editar">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                            </button>
                            <button class="btn-ico btn-ico--delete" @click="abrirEliminar(l)" title="Eliminar">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="totalPaginas > 1" class="paginacion">
                <button class="pag-btn" :disabled="pagina === 1" @click="pagina--">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </button>
                <span class="pag-info">Página {{ pagina }} de {{ totalPaginas }}</span>
                <button class="pag-btn" :disabled="pagina === totalPaginas" @click="pagina++">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                </button>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             MODAL VER
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="modal === 'ver'" class="modal-overlay" @click.self="cerrar">
                <div class="modal modal--xl">
                    <div class="modal-head modal-head--ver">
                        <div style="display:flex;align-items:center;gap:10px">
                            <span class="ver-badge"># {{ correlativos[lineaActual?.id] }}</span>
                            <h2 class="modal-title">Detalle de línea de acción</h2>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center">
                            <button class="btn btn-secondary btn-sm" @click="() => { const l = lineaActual; cerrar(); abrirEditar(l) }">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:13px;height:13px;margin-right:4px"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                Editar
                            </button>
                            <button class="modal-close" @click="cerrar">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body" v-if="lineaActual">
                        <!-- Clasificación -->
                        <p class="seccion-label">Clasificación PI-PEA</p>
                        <div class="ver-grid-3">
                            <div class="ver-campo">
                                <span class="ver-label">Eje estratégico</span>
                                <span class="ver-valor">
                                    <span class="badge-num badge-num--vino">{{ String(lineaActual.numero_eje).padStart(2,'0') }}</span>
                                    {{ lineaActual.eje ?? '—' }}
                                </span>
                            </div>
                            <div class="ver-campo">
                                <span class="ver-label">Objetivo específico</span>
                                <span class="ver-valor">
                                    <span class="badge-num badge-num--verde">{{ String(lineaActual.numero_objetivo).padStart(2,'0') }}</span>
                                    {{ lineaActual.objetivo ?? '—' }}
                                </span>
                            </div>
                            <div class="ver-campo">
                                <span class="ver-label">Prioridad</span>
                                <span class="ver-valor">
                                    <span class="badge-num badge-num--magenta">{{ String(lineaActual.numero_prioridad).padStart(2,'0') }}</span>
                                    {{ lineaActual.prioridad ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <div class="ver-grid-3" style="margin-top:10px">
                            <div class="ver-campo">
                                <span class="ver-label">Estrategia</span>
                                <span class="ver-valor">{{ lineaActual.estrategia ?? '—' }}</span>
                            </div>
                            <div class="ver-campo">
                                <span class="ver-label">Plazo</span>
                                <span class="ver-valor">{{ lineaActual.plazo ?? '—' }}</span>
                            </div>
                            <div class="ver-campo">
                                <span class="ver-label">Frecuencia de medición</span>
                                <span class="ver-valor">{{ lineaActual.frecuencia ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="ver-grid-1" style="margin-top:10px">
                            <div class="ver-campo">
                                <span class="ver-label">Estatus presupuestal / normativo</span>
                                <span class="ver-valor"><span class="chip-estatus">{{ lineaActual.estatus ?? '—' }}</span></span>
                            </div>
                        </div>

                        <div class="seccion-sep"></div>

                        <!-- Indicador -->
                        <p class="seccion-label">Características del indicador</p>
                        <div class="ver-grid-1">
                            <div class="ver-campo">
                                <span class="ver-label">Nombre del indicador</span>
                                <span class="ver-valor ver-valor--destacado">{{ lineaActual.nombre_indicador ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="ver-grid-1" style="margin-top:10px">
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
                                <span class="ver-label">Sentido del indicador</span>
                                <span class="ver-valor">
                                    <span v-if="lineaActual.sentido_indicador === 'Ascendente'" class="chip-sentido chip-sentido--asc">↑ Ascendente</span>
                                    <span v-else-if="lineaActual.sentido_indicador === 'Descendente'" class="chip-sentido chip-sentido--desc">↓ Descendente</span>
                                    <span v-else>—</span>
                                </span>
                            </div>
                            <div class="ver-campo">
                                <span class="ver-label">Unidad de medida</span>
                                <span class="ver-valor">{{ lineaActual.unidad_medida ?? '—' }}</span>
                            </div>
                        </div>

                        <div class="seccion-sep"></div>

                        <!-- Responsabilidad y avance -->
                        <p class="seccion-label">Responsabilidad y avance</p>
                        <div class="ver-grid-3">
                            <div class="ver-campo">
                                <span class="ver-label">Organismo implementador</span>
                                <span class="ver-valor ver-valor--destacado">{{ lineaActual.organismo ?? '—' }}</span>
                            </div>
                            <div class="ver-campo">
                                <span class="ver-label">Responsable</span>
                                <span class="ver-valor">{{ lineaActual.usuario ?? '—' }}</span>
                            </div>
                            <div class="ver-campo">
                                <span class="ver-label">Estado</span>
                                <span class="ver-valor">
                                    <span :class="['badge-estado', lineaActual.activo ? 'badge-estado--activo' : 'badge-estado--inactivo']">
                                        {{ lineaActual.activo ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </span>
                            </div>
                        </div>
                        <div class="ver-avance-card" style="margin-top:12px">
                            <div class="ver-avance-header">
                                <span class="ver-label">Avance actual</span>
                                <span class="ver-avance-pct">{{ lineaActual.porcentaje_avance ?? 0 }}%</span>
                            </div>
                            <div class="ver-avance-track">
                                <div class="ver-avance-fill" :style="{ width: Math.min(lineaActual.porcentaje_avance ?? 0, 100) + '%' }"></div>
                            </div>
                            <div class="ver-avance-footer">
                                <span>Último valor: {{ fmtNum(lineaActual.ultimo_valor_avance) }} {{ lineaActual.unidad_medida }}</span>
                                <span>Meta: {{ fmtNum(lineaActual.meta) }} {{ lineaActual.unidad_medida }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn btn-ghost" @click="cerrar">Cerrar</button>
                        <button class="btn btn-primary" @click="() => { const l = lineaActual; cerrar(); abrirEditar(l) }">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;margin-right:4px"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                            Editar esta línea
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══════════════════════════════════════════════════════════
             MODAL CREAR / EDITAR
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="modal === 'crear' || modal === 'editar'" class="modal-overlay" @click.self="cerrar">
                <div class="modal modal--lg">
                    <div class="modal-head">
                        <h2 class="modal-title">{{ modal === 'crear' ? 'Nueva línea de acción' : 'Editar línea de acción' }}</h2>
                        <button class="modal-close" @click="cerrar">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                    <div class="modal-body">

                        <!-- Clasificación PI-PEA -->
                        <p class="seccion-label">Clasificación PI-PEA</p>
                        <div class="field">
                            <label class="field-label">Eje <span class="field-req">*</span></label>
                            <select v-model="form.id_eje" class="field-input" :class="{ 'field-input--error': form.errors.id_eje }">
                                <option :value="null">Selecciona un eje...</option>
                                <option v-for="e in ejes" :key="e.id" :value="e.id">
                                    Eje {{ String(e.numero_eje).padStart(2,'0') }} — {{ e.eje }}
                                </option>
                            </select>
                            <p v-if="form.errors.id_eje" class="field-error">{{ form.errors.id_eje }}</p>
                        </div>
                        <div class="field">
                            <label class="field-label">Objetivo específico <span class="field-req">*</span></label>
                            <select v-model="form.id_objetivo" class="field-input" :class="{ 'field-input--error': form.errors.id_objetivo }" :disabled="!form.id_eje || loadingObj">
                                <option :value="null">{{ !form.id_eje ? 'Primero selecciona un eje' : loadingObj ? 'Cargando...' : 'Selecciona un objetivo...' }}</option>
                                <option v-for="o in objetivos" :key="o.id" :value="o.id">
                                    Obj. {{ String(o.numero_objetivo).padStart(2,'0') }} — {{ o.objetivo }}
                                </option>
                            </select>
                            <p v-if="form.errors.id_objetivo" class="field-error">{{ form.errors.id_objetivo }}</p>
                        </div>
                        <div class="field">
                            <label class="field-label">Prioridad <span class="field-req">*</span></label>
                            <select v-model="form.id_prioridad" class="field-input" :class="{ 'field-input--error': form.errors.id_prioridad }" :disabled="!form.id_objetivo || loadingPri">
                                <option :value="null">{{ !form.id_objetivo ? 'Primero selecciona un objetivo' : loadingPri ? 'Cargando...' : 'Selecciona una prioridad...' }}</option>
                                <option v-for="p in prioridades" :key="p.id" :value="p.id">
                                    {{ String(p.numero).padStart(2,'0') }} — {{ p.prioridad }}
                                </option>
                            </select>
                            <p v-if="form.errors.id_prioridad" class="field-error">{{ form.errors.id_prioridad }}</p>
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <label class="field-label">Estrategia</label>
                                <select v-model="form.id_estrategia" class="field-input">
                                    <option :value="null">Sin estrategia</option>
                                    <option v-for="e in estrategias" :key="e.id" :value="e.id">{{ e.estrategia }}</option>
                                </select>
                            </div>
                            <div class="field">
                                <label class="field-label">Plazo</label>
                                <select v-model="form.id_plazo" class="field-input">
                                    <option :value="null">Sin plazo</option>
                                    <option v-for="p in plazos" :key="p.id" :value="p.id">{{ p.plazo }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <label class="field-label">Estatus presupuestal / normativo</label>
                                <select v-model="form.id_estatus" class="field-input">
                                    <option :value="null">Sin estatus</option>
                                    <option v-for="s in estatus" :key="s.id" :value="s.id">{{ s.nombre }}</option>
                                </select>
                            </div>
                            <div class="field">
                                <label class="field-label">Frecuencia de medición</label>
                                <select v-model="form.id_frecuencia" class="field-input">
                                    <option :value="null">Sin frecuencia</option>
                                    <option v-for="f in frecuencias" :key="f.id" :value="f.id">{{ f.frecuencia }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="seccion-sep"></div>

                        <!-- Indicador -->
                        <p class="seccion-label">Características del indicador</p>
                        <div class="field">
                            <label class="field-label">Nombre del indicador</label>
                            <input v-model="form.nombre_indicador" type="text" class="field-input" placeholder="Ej. Porcentaje de implementación del sistema"/>
                        </div>
                        <div class="field">
                            <label class="field-label">Fórmula</label>
                            <textarea v-model="form.formula" class="field-input field-textarea" placeholder="Ej. (Módulos implementados / Total programados) × 100" rows="2"></textarea>
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <label class="field-label">Línea base</label>
                                <input v-model="form.linea_base" type="number" step="any" class="field-input" placeholder="0"/>
                                <p class="field-hint">Valor de partida al inicio del programa.</p>
                            </div>
                            <div class="field">
                                <label class="field-label">Sentido del indicador</label>
                                <select v-model="form.sentido_indicador" class="field-input">
                                    <option value="">Sin definir</option>
                                    <option value="Ascendente">Ascendente ↑</option>
                                    <option value="Descendente">Descendente ↓</option>
                                </select>
                                <p class="field-hint">Ascendente: mayor es mejor. Descendente: menor es mejor.</p>
                            </div>
                        </div>

                        <div class="seccion-sep"></div>

                        <!-- Responsabilidad -->
                        <p class="seccion-label">Responsabilidad y medición</p>
                        <div class="field">
                            <label class="field-label">Responsable <span class="field-req">*</span></label>
                            <select v-model="form.id_usuario" class="field-input" :class="{ 'field-input--error': form.errors.id_usuario }">
                                <option :value="null">Selecciona un responsable...</option>
                                <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.label }}</option>
                            </select>
                            <p v-if="form.errors.id_usuario" class="field-error">{{ form.errors.id_usuario }}</p>
                        </div>
                        <div class="field-row">
                            <div class="field">
                                <label class="field-label">Meta</label>
                                <input v-model="form.meta" type="number" step="any" min="0" class="field-input" placeholder="Ej. 300"/>
                                <p class="field-hint">En las mismas unidades que la unidad de medida.</p>
                            </div>
                            <div class="field">
                                <label class="field-label">Unidad de medida</label>
                                <input v-model="form.unidad_medida" type="text" class="field-input" placeholder="Ej. Publicación, Módulo, Persona"/>
                            </div>
                        </div>

                        <!-- El avance ya NO se captura aquí — viene del historial -->

                        <div class="field-check">
                            <label class="check-wrap">
                                <input type="checkbox" v-model="form.activo" class="check-input"/>
                                <span class="check-label">Línea activa</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button class="btn btn-ghost" @click="cerrar" :disabled="form.processing">Cancelar</button>
                        <button class="btn btn-primary" @click="guardar" :disabled="form.processing">
                            <svg v-if="form.processing" class="btn-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                            {{ form.processing ? 'Guardando...' : (modal === 'crear' ? 'Crear línea' : 'Guardar cambios') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ══════════════════════════════════════════════════════════
             MODAL ELIMINAR
        ══════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <div v-if="modal === 'eliminar'" class="modal-overlay" @click.self="cerrar">
                <div class="modal modal--sm">
                    <div class="modal-head modal-head--danger">
                        <h2 class="modal-title modal-title--danger">Eliminar línea de acción</h2>
                        <button class="modal-close modal-close--danger" @click="cerrar">
                            <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="delete-warn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:44px;height:44px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        </div>
                        <p class="delete-msg">¿Eliminar la línea <strong>#{{ correlativos[lineaActual?.id] }}</strong> — Prioridad {{ lineaActual?.numero_prioridad }}?</p>
                        <p class="delete-sub">Si tiene historial de avances registrado, no podrá eliminarse.</p>
                    </div>
                    <div class="modal-foot">
                        <button class="btn btn-ghost" @click="cerrar">Cancelar</button>
                        <button class="btn btn-danger" @click="confirmarEliminar">Sí, eliminar</button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AdminLayout>
</template>

<style scoped>
/* Flash */
.flash-bar { position:fixed;top:1.25rem;left:50%;transform:translateX(-50%);z-index:9999;display:flex;align-items:center;gap:.6rem;padding:.65rem 1.25rem;border-radius:var(--radius-lg);font-size:var(--text-sm);font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.15);white-space:nowrap; }
.flash-bar--success { background:var(--color-verde);color:#fff; }
.flash-bar--error   { background:var(--color-vino);color:#fff; }
.flash-enter-active,.flash-leave-active { transition:opacity .25s,transform .25s; }
.flash-enter-from,.flash-leave-to { opacity:0;transform:translateX(-50%) translateY(-8px); }

.page { max-width:1400px; }
.page-head { display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.5rem;gap:1rem;flex-wrap:wrap; }
.page-breadcrumb { font-size:var(--text-xs);color:var(--color-gris-400);font-weight:600;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem; }
.page-title { font-family:var(--font-display);font-size:var(--text-2xl);color:var(--color-gris-800); }

/* Filtros */
.filters { display:flex;flex-direction:column;gap:.5rem;margin-bottom:1rem; }
.search-bar { display:flex;align-items:center;gap:.75rem;background:var(--color-blanco);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:.6rem 1rem;box-shadow:var(--shadow-sm); }
.search-input { flex:1;border:none;outline:none;font-size:var(--text-sm);color:var(--color-gris-700);background:transparent;font-family:var(--font-body); }
.search-input::placeholder { color:var(--color-gris-400); }
.search-count { font-size:var(--text-xs);color:var(--color-gris-400);white-space:nowrap; }
.filters-row { display:flex;gap:.5rem;flex-wrap:wrap;align-items:center; }
.filters-group-label { font-size:var(--text-xs);font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--color-gris-400);white-space:nowrap;padding:.45rem 0; }
.select-filtro { min-width:155px;flex-shrink:0; }
.btn-limpiar { display:inline-flex;align-items:center;gap:5px;padding:.45rem .9rem;border-radius:var(--radius-md);border:1px solid var(--color-gris-300);background:var(--color-blanco);font-size:var(--text-xs);font-weight:600;color:var(--color-gris-600);cursor:pointer;transition:all var(--transition-base);white-space:nowrap;height:38px; }
.btn-limpiar:hover { background:var(--color-vino-lt);color:var(--color-vino);border-color:var(--color-vino); }
.chips-row { display:flex;gap:.4rem;flex-wrap:wrap; }
.chip-filtro { display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:var(--color-verde-lt);color:var(--color-verde);border-radius:var(--radius-full);font-size:11px;font-weight:600;cursor:pointer;border:1px solid transparent;transition:all var(--transition-base); }
.chip-filtro:hover { background:var(--color-vino-lt);color:var(--color-vino); }

/* Tabla */
.tabla-wrap { background:var(--color-blanco);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--color-gris-200);overflow:hidden; }
.tabla { width:100%;border-collapse:collapse; }
.th { padding:.7rem 1rem;font-size:var(--text-xs);font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--color-gris-500);background:var(--color-gris-100);border-bottom:1px solid var(--color-gris-200);text-align:left; }
.th--num,.th--sm { text-align:center; }
.th--num { width:48px; }
.th--sm  { width:58px; }
.th--center { text-align:center; }
.tr { border-bottom:1px solid var(--color-gris-200);transition:background var(--transition-base); }
.tr:last-child { border-bottom:none; }
.tr--clickable { cursor:pointer; }
.tr--clickable:hover { background:var(--color-gris-50,#fafafa); }
.td { padding:.65rem 1rem;font-size:var(--text-sm);color:var(--color-gris-700);vertical-align:middle; }
.td--center { text-align:center; }
.td--desc { color:var(--color-gris-800);line-height:1.5;max-width:260px; }
.td--acciones { white-space:nowrap; }
.tabla-empty { padding:3rem 1rem;text-align:center;color:var(--color-gris-500);font-size:var(--text-sm);display:flex;flex-direction:column;align-items:center;gap:.75rem; }
.correlativo { display:inline-flex;align-items:center;justify-content:center;min-width:26px;height:20px;padding:0 5px;border-radius:var(--radius-sm);background:var(--color-gris-100);color:var(--color-gris-500);font-size:11px;font-weight:700; }
.badge-num { display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius-md);font-size:var(--text-xs);font-weight:700;flex-shrink:0; }
.badge-num--vino    { background:var(--color-vino-lt);color:var(--color-vino); }
.badge-num--verde   { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-num--magenta { background:var(--color-magenta-lt);color:var(--color-magenta); }
.org-nombre  { font-weight:600;color:var(--color-gris-800);font-size:var(--text-sm); }
.org-usuario { font-size:var(--text-xs);color:var(--color-gris-500);margin-top:2px; }
.avance-wrap { display:flex;flex-direction:column;align-items:center;gap:3px;min-width:80px; }
.avance-bar  { width:68px;height:5px;background:var(--color-gris-200);border-radius:var(--radius-full);overflow:hidden; }
.avance-fill { height:100%;background:var(--color-verde);border-radius:var(--radius-full);transition:width .4s ease; }
.avance-pct  { font-size:var(--text-xs);font-weight:700;color:var(--color-gris-600); }
.badge-estado { display:inline-flex;align-items:center;padding:.2rem .65rem;border-radius:var(--radius-full);font-size:var(--text-xs);font-weight:700; }
.badge-estado--activo   { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-estado--inactivo { background:var(--color-gris-200);color:var(--color-gris-500); }
.badge-indicador { display:inline-flex;align-items:center;padding:.2rem .65rem;border-radius:var(--radius-full);font-size:var(--text-xs);font-weight:700; }
.badge-indicador--ok   { background:var(--color-verde-lt);color:var(--color-verde); }
.badge-indicador--pend { background:var(--color-magenta-lt);color:var(--color-magenta); }
.btn-ico { display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius-md);border:1px solid var(--color-gris-200);background:transparent;cursor:pointer;transition:all var(--transition-base);margin:0 1px; }
.btn-ico--view,.btn-ico--edit,.btn-ico--delete { color:var(--color-gris-400); }
.btn-ico--view:hover   { background:#e8f0fe;color:#1a73e8;border-color:#1a73e8; }
.btn-ico--edit:hover   { background:var(--color-verde-lt);color:var(--color-verde);border-color:var(--color-verde); }
.btn-ico--delete:hover { background:var(--color-vino-lt);color:var(--color-vino);border-color:var(--color-vino); }

/* Paginación */
.paginacion { display:flex;align-items:center;justify-content:center;gap:1rem;margin-top:1rem; }
.pag-btn { display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:var(--radius-md);border:1px solid var(--color-gris-200);background:var(--color-blanco);cursor:pointer;color:var(--color-gris-600);transition:background var(--transition-base); }
.pag-btn:hover:not(:disabled) { background:var(--color-gris-100); }
.pag-btn:disabled { opacity:.4;cursor:not-allowed; }
.pag-info { font-size:var(--text-sm);color:var(--color-gris-500); }

/* Modales */
.modal-overlay { position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:200;padding:1rem; }
.modal { background:var(--color-blanco);border-radius:var(--radius-xl);box-shadow:0 20px 60px rgba(0,0,0,.2);width:100%;max-width:560px;animation:modal-in .18s ease;max-height:90vh;display:flex;flex-direction:column; }
.modal--lg { max-width:640px; }
.modal--xl { max-width:800px; }
.modal--sm { max-width:400px; }
@keyframes modal-in { from{opacity:0;transform:translateY(-12px) scale(.97)} to{opacity:1;transform:translateY(0) scale(1)} }
.modal-head { display:flex;align-items:center;justify-content:space-between;padding:1.1rem 1.5rem;border-bottom:1px solid var(--color-gris-200);flex-shrink:0; }
.modal-head--ver    { background:var(--color-gris-50,#fafafa);border-radius:var(--radius-xl) var(--radius-xl) 0 0; }
.modal-head--danger { border-bottom-color:var(--color-vino-lt); }
.modal-title { font-family:var(--font-display);font-size:var(--text-lg);color:var(--color-gris-800); }
.modal-title--danger { color:var(--color-vino); }
.modal-close { width:30px;height:30px;border-radius:var(--radius-md);border:none;background:transparent;cursor:pointer;color:var(--color-gris-400);display:flex;align-items:center;justify-content:center;transition:background var(--transition-base);flex-shrink:0; }
.modal-close:hover { background:var(--color-gris-200);color:var(--color-gris-700); }
.modal-close--danger:hover { background:var(--color-vino-lt);color:var(--color-vino); }
.modal-body { padding:1.5rem;display:flex;flex-direction:column;gap:1rem;overflow-y:auto; }
.modal-foot { display:flex;align-items:center;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;border-top:1px solid var(--color-gris-200);flex-shrink:0; }

/* Modal ver */
.ver-badge  { display:inline-flex;align-items:center;padding:2px 10px;background:var(--color-gris-200);color:var(--color-gris-600);border-radius:var(--radius-full);font-size:12px;font-weight:700; }
.ver-grid-3 { display:grid;grid-template-columns:repeat(3,1fr);gap:12px; }
.ver-grid-1 { display:grid;grid-template-columns:1fr;gap:12px; }
.ver-campo  { display:flex;flex-direction:column;gap:4px; }
.ver-label  { font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--color-gris-400); }
.ver-valor  { font-size:var(--text-sm);color:var(--color-gris-800);line-height:1.5;display:flex;align-items:center;flex-wrap:wrap;gap:4px; }
.ver-valor--destacado { font-weight:600;color:var(--color-gris-900); }
.ver-formula { font-family:var(--font-mono,monospace);font-size:12px;background:var(--color-gris-100);padding:8px 12px;border-radius:var(--radius-md);color:var(--color-gris-700);line-height:1.6;border:1px solid var(--color-gris-200); }
.chip-estatus { font-size:12px;background:var(--color-gris-100);padding:3px 10px;border-radius:var(--radius-sm);color:var(--color-gris-700);border:1px solid var(--color-gris-200); }
.chip-sentido { display:inline-flex;align-items:center;padding:2px 10px;border-radius:var(--radius-full);font-size:12px;font-weight:700; }
.chip-sentido--asc  { background:var(--color-verde-lt);color:var(--color-verde); }
.chip-sentido--desc { background:var(--color-magenta-lt);color:var(--color-magenta); }
.ver-avance-card   { background:var(--color-gris-50,#fafafa);border:1px solid var(--color-gris-200);border-radius:var(--radius-md);padding:14px 16px; }
.ver-avance-header { display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px; }
.ver-avance-pct    { font-size:var(--text-xl);font-weight:700;font-family:var(--font-display);color:var(--color-verde); }
.ver-avance-track  { width:100%;height:8px;background:var(--color-gris-200);border-radius:var(--radius-full);overflow:hidden; }
.ver-avance-fill   { height:100%;background:var(--color-verde);border-radius:var(--radius-full);transition:width .5s ease; }
.ver-avance-footer { display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--color-gris-400); }

/* Form */
.seccion-label  { font-size:var(--text-xs);font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--color-gris-500); }
.seccion-sep    { border-top:1px solid var(--color-gris-200); }
.field          { display:flex;flex-direction:column;gap:.35rem; }
.field-row      { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
.field-label    { font-size:var(--text-sm);font-weight:600;color:var(--color-gris-700); }
.field-req      { color:var(--color-vino); }
.field-hint     { font-size:var(--text-xs);color:var(--color-gris-400); }
.field-error    { font-size:var(--text-xs);color:var(--color-vino);font-weight:600; }
.field-check    { display:flex;flex-direction:column;gap:.3rem; }
.field-textarea { resize:vertical;min-height:60px;font-family:var(--font-body);font-size:var(--text-sm);line-height:1.5; }
.check-wrap  { display:flex;align-items:center;gap:.5rem;cursor:pointer; }
.check-input { width:16px;height:16px;accent-color:var(--color-verde);cursor:pointer; }
.check-label { font-size:var(--text-sm);font-weight:500;color:var(--color-gris-700); }
.btn-spin { animation:spin .8s linear infinite; }
@keyframes spin { to{transform:rotate(360deg)} }
.delete-warn { display:flex;justify-content:center;color:var(--color-vino); }
.delete-msg  { text-align:center;font-size:var(--text-sm);color:var(--color-gris-700);line-height:1.6; }
.delete-sub  { text-align:center;font-size:var(--text-xs);color:var(--color-gris-400); }
.field-input:disabled { background:var(--color-gris-100);color:var(--color-gris-400);cursor:not-allowed; }
</style>
