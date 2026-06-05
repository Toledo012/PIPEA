<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    canLogin: Boolean,
    estadisticas: {
        type: Object,
        default: () => ({ ejes: 4, objetivos: 10, prioridades: 67, organismos: 0, lineas: 0 }),
    },
    ejes:         { type: Array, default: () => [] },
    organismos:   { type: Array, default: () => [] },
    lineasAccion: { type: Array, default: () => [] },
})

// ── Carrusel ──────────────────────────────────────────────────────────────
const INTERVAL = 5000
const carouselIndex = ref(0)
const progressBar = ref(null)
let timer = null

const dependencias = [
    {
        nombre: 'Consejo de Participación Ciudadana',
        img: '/images/dependencias/CPC.png',
        desc: 'Órgano ciudadano encargado de ser el canal de comunicación entre la sociedad y las autoridades dentro del Sistema Anticorrupción del Estado de Chiapas (SAECH).',
    },
    {
        nombre: 'Secretaría Anticorrupción y Buen Gobierno',
        img: '/images/dependencias/Buen_Gobierno.jpg',
        desc: 'Dependencia del Poder Ejecutivo de Chiapas encargada de la fiscalización, transparencia y combate a la corrupción en la administración pública estatal.',
    },
    {
        nombre: 'Fiscalía General del Estado de Chiapas',
        img: '/images/dependencias/Fiscalia_general.jpg',
        desc: 'Órgano constitucional autónomo encargado de la procuración de justicia, la investigación de delitos y el ejercicio de la acción penal en el estado.',
    },
    {
        nombre: 'Auditoría Superior del Estado de Chiapas',
        img: '/images/dependencias/ASE.jpeg',
        desc: 'Órgano técnico encargado de revisar y fiscalizar el uso de los recursos públicos en el estado y sus municipios.',
    },
    {
        nombre: 'Tribunal de Justicia Administrativa del Estado de Chiapas',
        img: '/images/dependencias/LOGO_TJA.png',
        desc: 'Órgano autónomo encargado de resolver las controversias entre la administración pública (estatal o municipal) y los particulares.',
    },
    {
        nombre: 'Poder Judicial del Estado de Chiapas',
        img: '/images/dependencias/PJE.jpg',
        desc: 'Institución pública responsable de administrar e impartir justicia en el estado de Chiapas, asegurando el respeto a las leyes y los derechos humanos.',
    },
]

function resetProgress() {
    if (!progressBar.value) return
    progressBar.value.style.transition = 'none'
    progressBar.value.style.width = '0%'
    requestAnimationFrame(() =>
        requestAnimationFrame(() => {
            progressBar.value.style.transition = `width ${INTERVAL}ms linear`
            progressBar.value.style.width = '100%'
        }),
    )
}

function goTo(index) {
    carouselIndex.value = ((index % dependencias.length) + dependencias.length) % dependencias.length
    resetProgress()
    clearInterval(timer)
    timer = setInterval(() => goTo(carouselIndex.value + 1), INTERVAL)
}

// ── Animaciones de scroll ─────────────────────────────────────────────────
let scrollObserver = null

function setupAnimations() {
    scrollObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in-view')
                scrollObserver.unobserve(entry.target)
            }
        })
    }, { threshold: 0.1 })
    document.querySelectorAll('.anim').forEach(el => scrollObserver.observe(el))
}

// ── Counter animation ─────────────────────────────────────────────────────
const statRefs = ref([])
function animateCounter(el, target) {
    if (!el || typeof target !== 'number' || target === 0) return
    const duration = 1400
    const start = performance.now()
    function tick(now) {
        const p = Math.min((now - start) / duration, 1)
        const eased = 1 - Math.pow(1 - p, 3)
        el.textContent = Math.round(eased * target)
        if (p < 1) requestAnimationFrame(tick)
    }
    requestAnimationFrame(tick)
}

onMounted(() => {
    resetProgress()
    timer = setInterval(() => goTo(carouselIndex.value + 1), INTERVAL)
    setTimeout(setupAnimations, 100)

    // Contadores stats
    const nums = document.querySelectorAll('[data-count]')
    nums.forEach(el => {
        const target = parseInt(el.dataset.count)
        const obs = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                animateCounter(el, target)
                obs.disconnect()
            }
        }, { threshold: 0.5 })
        obs.observe(el)
    })
})

onUnmounted(() => {
    clearInterval(timer)
    scrollObserver?.disconnect()
})

// ── Metadatos de ejes ─────────────────────────────────────────────────────
const EJE_META = [
    { numero: 1, color: '#b10f57', descripcion: 'Fortalecer los mecanismos de sanción e investigación para erradicar la corrupción y la impunidad en Chiapas.' },
    { numero: 2, color: '#ae192d', descripcion: 'Limitar el ejercicio discrecional del poder público mediante controles institucionales y rendición de cuentas.' },
    { numero: 3, color: '#009887', descripcion: 'Modernizar la administración pública y reducir espacios de corrupción en los puntos de contacto gobierno-sociedad.' },
    { numero: 4, color: '#3e6f78', descripcion: 'Fomentar la participación activa de ciudadanos, organizaciones y empresas en el combate a la corrupción.' },
]

const ejesData = computed(() => {
    if (!props.ejes.length) {
        return EJE_META.map(m => ({ id: m.numero, numero_eje: m.numero, eje: '', lineas_accion_count: 0, color: m.color, descripcion: m.descripcion }))
    }
    return props.ejes.map(e => ({
        ...e,
        color:       EJE_META.find(m => m.numero === e.numero_eje)?.color       ?? '#b10f57',
        descripcion: EJE_META.find(m => m.numero === e.numero_eje)?.descripcion ?? '',
    }))
})
</script>

<template>
    <Head title="PIPEA — Secretaría Anticorrupción y Buen Gobierno · Chiapas" />

    <div class="public-root">

        <!-- ══ BANNER ════════════════════════════════════════════════════════ -->
        <div class="public-banner">
            <img src="/images/banner-sesaech.png" alt="SESAECH" class="banner-img" />
        </div>

        <main class="landing-wrap">

            <!-- ══ HERO STRIP ════════════════════════════════════════════════ -->
            <section class="hero-strip">
                <div class="hero-rombos" aria-hidden="true">
                    <span class="hr-r hr-r--1">◆</span>
                    <span class="hr-r hr-r--2">◆</span>
                    <span class="hr-r hr-r--3">◆</span>
                    <span class="hr-r hr-r--4">◆</span>
                    <span class="hr-r hr-r--5">◆</span>
                </div>

                <div class="hero-inner">
                    <div class="hero-text">
                        <p class="hero-eyebrow">Sistema de Seguimiento · Chiapas 2024–2030</p>
                        <h1 class="hero-title">
                            Programa de Implementación de la<br />
                            Política Estatal Anticorrupción
                        </h1>
                        <p class="hero-subtitle">
                            Consulta, seguimiento y reporte de avances de las líneas de acción
                            del PI-PEA en el estado de Chiapas.
                        </p>
                        <div class="hero-actions">
                            <Link v-if="canLogin" :href="route('login')" class="hero-btn-primary">
                                Acceder al sistema
                            </Link>
                            <Link :href="route('consulta')" class="hero-btn-outline">
                                Consultar PI-PEA
                            </Link>
                            <Link :href="route('estadisticas')" class="hero-btn-ghost">
                                Ver estadísticas
                            </Link>
                        </div>
                    </div>

                    <div class="hero-stats">
                        <div class="hstat">
                            <span class="hstat-num" :data-count="estadisticas.ejes || 4">{{ estadisticas.ejes || 4 }}</span>
                            <span class="hstat-lbl">Ejes</span>
                        </div>
                        <div class="hstat-sep" aria-hidden="true">◆</div>
                        <div class="hstat">
                            <span class="hstat-num" :data-count="estadisticas.objetivos || 10">{{ estadisticas.objetivos || 10 }}</span>
                            <span class="hstat-lbl">Objetivos</span>
                        </div>
                        <div class="hstat-sep" aria-hidden="true">◆</div>
                        <div class="hstat">
                            <span class="hstat-num" :data-count="estadisticas.prioridades || 67">{{ estadisticas.prioridades || 67 }}</span>
                            <span class="hstat-lbl">Prioridades</span>
                        </div>
                        <div class="hstat-sep" aria-hidden="true">◆</div>
                        <div class="hstat">
                            <span class="hstat-num" :data-count="estadisticas.organismos">{{ estadisticas.organismos || '—' }}</span>
                            <span class="hstat-lbl">Organismos</span>
                        </div>
                        <template v-if="estadisticas.lineas">
                            <div class="hstat-sep" aria-hidden="true">◆</div>
                            <div class="hstat">
                                <span class="hstat-num" :data-count="estadisticas.lineas">{{ estadisticas.lineas }}</span>
                                <span class="hstat-lbl">Líneas de acción</span>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <!-- ══ ¿QUÉ ES? ══════════════════════════════════════════════════ -->
            <section id="que-es" class="intro-block">
                <div class="anim intro-label">
                    <span class="label-rombo">◆</span> Sobre el programa
                </div>
                <h2 class="anim section-main-title">
                    ¿Qué es el Programa de Implementación de la<br />
                    Política Estatal Anticorrupción?
                </h2>

                <div class="que-es-grid">

                    <article class="que-es-card anim" style="--d:0ms">
                        <div class="qec-num-wrap">
                            <span class="qec-diamond" aria-hidden="true">◆</span>
                            <span class="qec-num">01</span>
                        </div>
                        <div class="qec-content">
                            <span class="qec-tag">Política anticorrupción</span>
                            <h4 class="qec-title">Política Estatal Anticorrupción</h4>
                            <p class="qec-text">
                                Establece las líneas de acción que deben seguir los organismos
                                implementadores de Chiapas para combatir la corrupción de manera
                                sistemática, con indicadores y plazos definidos.
                            </p>
                        </div>
                        <div class="qec-footer">
                            <span class="qec-tag-bottom">Planeación estratégica</span>
                        </div>
                    </article>

                    <article class="que-es-card anim" style="--d:100ms">
                        <div class="qec-num-wrap">
                            <span class="qec-diamond" aria-hidden="true">◆</span>
                            <span class="qec-num">02</span>
                        </div>
                        <div class="qec-content">
                            <span class="qec-tag">Implementación</span>
                            <h4 class="qec-title">Organismos implementadores</h4>
                            <p class="qec-text">
                                Las secretarías y entidades del gobierno estatal son responsables de
                                ejecutar y reportar el avance de sus líneas asignadas con evidencia
                                documental periódica y verificable.
                            </p>
                        </div>
                        <div class="qec-footer">
                            <span class="qec-tag-bottom">Seguimiento de avances</span>
                        </div>
                    </article>

                    <article class="que-es-card anim" style="--d:200ms">
                        <div class="qec-num-wrap">
                            <span class="qec-diamond" aria-hidden="true">◆</span>
                            <span class="qec-num">03</span>
                        </div>
                        <div class="qec-content">
                            <span class="qec-tag">Supervisión</span>
                            <h4 class="qec-title">Monitoreo y seguimiento</h4>
                            <p class="qec-text">
                                La SESAECH supervisa el cumplimiento del programa a través de esta
                                plataforma, registrando avances, evidencias e historial de
                                cumplimiento de cada línea de acción.
                            </p>
                        </div>
                        <div class="qec-footer">
                            <span class="qec-tag-bottom">Coordinación institucional</span>
                        </div>
                    </article>

                </div>

                <!-- CTA links -->
                <div class="intro-ctas anim" style="--d:300ms">
                    <Link :href="route('consulta')" class="intro-cta-link">
                        Explorar líneas de acción <span aria-hidden="true">→</span>
                    </Link>
                    <Link :href="route('estadisticas')" class="intro-cta-link intro-cta-link--jade">
                        Ver estadísticas de avance <span aria-hidden="true">→</span>
                    </Link>
                </div>
            </section>

            <!-- divisor -->
            <div class="rombo-divider" aria-hidden="true"><span>◆</span><span>◆</span><span>◆</span></div>

            <!-- ══ EJES ═══════════════════════════════════════════════════════ -->
            <section id="ejes" class="ejes-block">
                <div class="ejes-watermark" aria-hidden="true"></div>

                <div class="ejes-head">
                    <div class="anim intro-label intro-label--center">
                        <span class="label-rombo">◆</span> Estructura estratégica
                    </div>
                    <h2 class="anim ejes-title">Ejes del PI-PEA</h2>
                    <p class="anim ejes-subtitle">
                        Cuatro ejes articulan las prioridades para un control efectivo de la
                        corrupción en Chiapas
                    </p>
                </div>

                <div class="ejes-grid">
                    <article
                        v-for="(eje, i) in ejesData"
                        :key="eje.id"
                        class="eje-card anim"
                        :style="{ '--d': `${i * 80}ms`, '--eje-color': eje.color }"
                        tabindex="0"
                    >
                        <!-- Icono izquierdo -->
                        <div class="eje-icon-wrap">
                            <img
                                :src="`/images/ejes/EJE${eje.numero_eje}.png`"
                                :alt="`Eje ${eje.numero_eje}`"
                                class="eje-icon-img"
                            />
                            <div class="eje-num-badge" :style="{ background: eje.color }">
                                {{ eje.numero_eje }}
                            </div>
                        </div>

                        <!-- Info derecha -->
                        <div class="eje-body">
                            <span class="eje-label" :style="{ color: eje.color }">
                                <span aria-hidden="true">◆</span> Eje {{ eje.numero_eje }}
                            </span>
                            <p class="eje-text">{{ eje.eje }}</p>
                            <p class="eje-desc">{{ eje.descripcion }}</p>
                            <div class="eje-foot">
                                <span v-if="eje.lineas_accion_count" class="eje-badge" :style="{ borderColor: eje.color, color: eje.color }">
                                    {{ eje.lineas_accion_count }} líneas
                                </span>
                                <Link :href="route('consulta')" class="eje-link" :style="{ color: eje.color }">Ver líneas →</Link>
                            </div>
                        </div>
                    </article>
                </div>

                <div class="anim ejes-cta" style="--d:320ms">
                    <Link :href="route('consulta')" class="ejes-cta-btn">
                        Ver todas las líneas de acción
                    </Link>
                </div>
            </section>

            <!-- divisor -->
            <div class="rombo-divider" aria-hidden="true"><span>◆</span><span>◆</span><span>◆</span></div>

            <!-- ══ ACCESO ══════════════════════════════════════════════════════ -->
            <section id="acceso" class="access-block">
                <div class="access-card anim">
                    <div class="access-rombos" aria-hidden="true">
                        <span class="ar ar--1">◆</span>
                        <span class="ar ar--2">◆</span>
                        <span class="ar ar--3">◆</span>
                    </div>
                    <span class="access-badge">Acceso institucional</span>
                    <h3 class="access-title">¿Eres servidor público autorizado?</h3>
                    <p class="access-text">
                        Ingresa al sistema para registrar, consultar y dar seguimiento al avance de
                        tu organismo en el cumplimiento de la PI-PEA.
                    </p>
                    <div class="access-actions">
                        <Link v-if="canLogin" :href="route('login')" class="access-btn">
                            Acceder al sistema
                        </Link>
                    </div>
                </div>
            </section>

            <!-- ══ CARRUSEL ═══════════════════════════════════════════════════ -->
            <section class="carousel-section">
                <div
                    v-for="(dep, i) in dependencias"
                    :key="i"
                    class="carousel-slide"
                    :class="{ active: carouselIndex === i }"
                >
                    <div class="carousel-bg" :style="{ backgroundImage: `url(${dep.img})` }"></div>
                    <div class="carousel-overlay"></div>
                    <div class="carousel-content">
                        <span class="carousel-dep-tag">Organismo implementador</span>
                        <h3 class="carousel-title">{{ dep.nombre }}</h3>
                        <p class="carousel-desc">{{ dep.desc }}</p>
                    </div>
                </div>

                <div class="carousel-dots">
                    <button v-for="(_, i) in dependencias" :key="i" class="dot" :class="{ active: carouselIndex === i }" @click="goTo(i)" />
                </div>
                <div class="carousel-controls">
                    <button class="carousel-btn" @click="goTo(carouselIndex - 1)">&#8592;</button>
                    <button class="carousel-btn" @click="goTo(carouselIndex + 1)">&#8594;</button>
                </div>
                <div class="carousel-progress" ref="progressBar"></div>
            </section>

        </main>
    </div>
</template>

<style scoped>
/* ── Animaciones de scroll ────────────────────────────────────────────────── */
.anim {
    opacity: 0;
    transform: translateY(26px);
    transition: opacity 0.6s ease, transform 0.6s ease;
    transition-delay: var(--d, 0ms);
}
.anim.in-view {
    opacity: 1;
    transform: translateY(0);
}

/* ── Base ─────────────────────────────────────────────────────────────────── */
.public-root {
    min-height: 100vh;
    width: 100%;
    background: #f7f7f7;
    color: #24343a;
    font-family: Arial, sans-serif;
    overflow-x: hidden;
}

.public-banner { width: 100%; }
.banner-img { width: 100%; display: block; object-fit: cover; }

.landing-wrap { width: 100%; background: #fff; overflow: hidden; }

/* ── Utilidades ───────────────────────────────────────────────────────────── */
.intro-label {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #b10f57;
    margin-bottom: 0.6rem;
}
.intro-label--center { justify-content: center; }
.label-rombo { font-size: 0.5rem; color: #b10f57; }

.section-main-title {
    margin: 0 0 2rem;
    text-align: center;
    font-size: clamp(1.3rem, 2.5vw, 2rem);
    font-weight: 800;
    color: #1d2a2e;
    line-height: 1.25;
}

.rombo-divider {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    padding: 0.3rem 0;
    background: #fff;
}
.rombo-divider span { font-size: 0.55rem; color: rgba(177,15,87,0.28); }
.rombo-divider span:nth-child(2) { font-size: 0.8rem; color: rgba(177,15,87,0.50); }

/* ── HERO STRIP ───────────────────────────────────────────────────────────── */
.hero-strip {
    position: relative;
    width: 100%;
    background: linear-gradient(135deg, #9e0b4e 0%, #c0156a 60%, #b31060 100%);
    padding: 3rem 2rem 2.5rem;
    overflow: hidden;
}

.hero-rombos { position: absolute; inset: 0; pointer-events: none; }
.hr-r { position: absolute; line-height: 1; }
.hr-r--1 { top: -5%;  right: 3%;  font-size: 9rem;  color: rgba(255,255,255,0.05); }
.hr-r--2 { top: 50%;  right: 18%; font-size: 2.5rem;color: rgba(255,255,255,0.09); }
.hr-r--3 { bottom: 8%;left: 3%;   font-size: 4rem;  color: rgba(255,255,255,0.06); }
.hr-r--4 { top: 15%;  left: 40%;  font-size: 1.2rem;color: rgba(255,255,255,0.12); }
.hr-r--5 { bottom:15%;right: 40%; font-size: 0.9rem;color: rgba(255,255,255,0.15); }

.hero-inner { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; }

.hero-eyebrow {
    display: inline-block;
    background: rgba(255,255,255,0.16);
    border: 1px solid rgba(255,255,255,0.28);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 0.3rem 0.85rem;
    border-radius: 999px;
    margin-bottom: 1rem;
}

.hero-title {
    margin: 0 0 0.9rem;
    font-size: clamp(1.55rem, 3.5vw, 2.7rem);
    font-weight: 800;
    color: #fff;
    line-height: 1.2;
    animation: fadeUp 0.7s ease both;
}

.hero-subtitle {
    margin: 0 0 1.75rem;
    font-size: 1rem;
    color: rgba(255,255,255,0.88);
    line-height: 1.65;
    max-width: 580px;
    animation: fadeUp 0.7s 0.1s ease both;
}

.hero-actions {
    display: flex;
    gap: 0.85rem;
    flex-wrap: wrap;
    margin-bottom: 2.5rem;
    animation: fadeUp 0.7s 0.2s ease both;
}

.hero-btn-primary {
    display: inline-flex;
    align-items: center;
    padding: 0.78rem 1.5rem;
    background: #fff;
    color: #b10f57;
    font-weight: 800;
    font-size: 0.88rem;
    border-radius: 10px;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 6px 18px rgba(0,0,0,0.18);
}
.hero-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(0,0,0,0.22); }

.hero-btn-outline {
    display: inline-flex;
    align-items: center;
    padding: 0.78rem 1.5rem;
    border: 2px solid rgba(255,255,255,0.65);
    color: #fff;
    font-weight: 700;
    font-size: 0.88rem;
    border-radius: 10px;
    text-decoration: none;
    transition: background 0.2s, border-color 0.2s;
}
.hero-btn-outline:hover { background: rgba(255,255,255,0.15); border-color: #fff; }

.hero-btn-ghost {
    display: inline-flex;
    align-items: center;
    padding: 0.78rem 1.5rem;
    border: 1px solid rgba(255,255,255,0.3);
    color: rgba(255,255,255,0.8);
    font-weight: 600;
    font-size: 0.88rem;
    border-radius: 10px;
    text-decoration: none;
    transition: background 0.2s, color 0.2s;
}
.hero-btn-ghost:hover { background: rgba(255,255,255,0.1); color: #fff; }

/* Stats */
.hero-stats {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    background: rgba(0,0,0,0.18);
    border-radius: 14px;
    padding: 1rem 1.5rem;
    width: fit-content;
    max-width: 100%;
    animation: fadeUp 0.7s 0.3s ease both;
}
.hstat { display: flex; flex-direction: column; align-items: center; padding: 0.25rem 1.4rem; text-align: center; }
.hstat-num { font-size: 2rem; font-weight: 900; color: #fff; line-height: 1; }
.hstat-lbl { font-size: 0.63rem; color: rgba(255,255,255,0.72); margin-top: 0.2rem; text-transform: uppercase; letter-spacing: 0.07em; }
.hstat-sep { font-size: 0.42rem; color: rgba(255,255,255,0.28); padding: 0 0.2rem; align-self: center; }

/* ── ¿QUÉ ES? ─────────────────────────────────────────────────────────────── */
.intro-block {
    width: 100%;
    padding: 3rem 2rem 2.5rem;
    background: #f7f7f7;
}

.que-es-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.que-es-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #ead8df;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    transition: transform 0.28s ease, box-shadow 0.28s ease;
}
.que-es-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, #b10f57, #c0156a);
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.35s ease;
}
.que-es-card:hover::before { transform: scaleX(1); }
.que-es-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px rgba(177,15,87,0.13); }

.qec-num-wrap {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 80px;
    background: linear-gradient(135deg, #fdf0f5 0%, #fff 100%);
    border-bottom: 1px solid #f2e2e8;
}
.qec-diamond {
    position: absolute;
    font-size: 3.8rem;
    color: rgba(177,15,87,0.1);
    line-height: 1;
    user-select: none;
    transition: color 0.3s, transform 0.3s;
}
.que-es-card:hover .qec-diamond { color: rgba(177,15,87,0.18); transform: scale(1.08) rotate(5deg); }
.qec-num {
    position: relative;
    z-index: 1;
    font-size: 2rem;
    font-weight: 900;
    color: #b10f57;
    letter-spacing: -0.02em;
}

.qec-content { padding: 1.4rem 1.4rem 0.8rem; flex: 1; }
.qec-tag {
    display: inline-block;
    font-size: 0.67rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #b10f57;
    margin-bottom: 0.6rem;
    padding: 0.2rem 0.6rem;
    background: rgba(177,15,87,0.08);
    border-radius: 999px;
}
.qec-title { font-size: 1.02rem; font-weight: 800; color: #1d2a2e; margin: 0 0 0.65rem; line-height: 1.3; }
.qec-text  { font-size: 0.88rem; color: #4a5a60; line-height: 1.65; margin: 0; }

.qec-footer {
    padding: 0.9rem 1.4rem 1.2rem;
    border-top: 1px solid #f5eaed;
    margin-top: auto;
}
.qec-tag-bottom {
    font-size: 0.72rem;
    font-weight: 700;
    color: #8a9ea4;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.qec-tag-bottom::before { content: '◆'; font-size: 0.45rem; color: #b10f57; }

/* CTAs intro */
.intro-ctas {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
}
.intro-cta-link {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #b10f57;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    border-bottom: 2px solid rgba(177,15,87,0.3);
    padding-bottom: 0.15rem;
    transition: border-color 0.2s, gap 0.2s;
}
.intro-cta-link:hover { border-color: #b10f57; gap: 0.6rem; }
.intro-cta-link--jade { color: #009887; border-bottom-color: rgba(0,152,135,0.3); }
.intro-cta-link--jade:hover { border-color: #009887; }

/* ── EJES ─────────────────────────────────────────────────────────────────── */
.ejes-block {
    position: relative;
    width: 100%;
    padding: 3rem 2rem 2.5rem;
    background: #fff;
    overflow: hidden;
}

.ejes-watermark {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(45deg,  transparent 46%, rgba(0,152,135,0.06)  46%, rgba(0,152,135,0.06)  54%, transparent 54%),
        linear-gradient(-45deg, transparent 46%, rgba(177,15,87,0.06)  46%, rgba(177,15,87,0.06)  54%, transparent 54%);
    background-size: 200px 200px;
    pointer-events: none;
}

.ejes-head { position: relative; z-index: 1; text-align: center; margin-bottom: 1.8rem; }
.ejes-title { margin: 0 0 0.4rem; font-size: clamp(1.4rem, 2.5vw, 2.1rem); font-weight: 800; color: #b10f57; }
.ejes-subtitle { max-width: 650px; margin: 0 auto; font-size: 0.95rem; line-height: 1.6; color: #566268; }

.ejes-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.1rem;
}

.eje-card {
    background: #fff;
    border: 1px solid #e5d5da;
    border-left: 4px solid var(--eje-color, #b10f57);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: row;
    align-items: stretch;
    cursor: pointer;
    outline: none;
    transition: transform 0.28s ease, box-shadow 0.28s ease, border-color 0.28s ease;
}
.eje-card:hover, .eje-card:focus {
    transform: translateY(-4px);
    box-shadow: 0 14px 32px rgba(0,0,0,0.11);
    border-left-color: var(--eje-color, #b10f57);
    border-color: var(--eje-color, #b10f57);
}

/* Icono izquierdo */
.eje-icon-wrap {
    position: relative;
    width: 110px;
    min-width: 110px;
    background: #f5eff3;
    overflow: hidden;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.6rem;
}
.eje-icon-img {
    width: 100%;
    height: auto;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.4s ease;
}
.eje-card:hover .eje-icon-img { transform: scale(1.08); }

.eje-num-badge {
    position: absolute;
    bottom: 0.5rem;
    left: 50%;
    transform: translateX(-50%);
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 900;
    color: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.35);
}

/* Contenido derecho */
.eje-body { padding: 1rem 1.1rem 0.9rem; flex: 1; display: flex; flex-direction: column; gap: 0.35rem; }
.eje-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; display: flex; align-items: center; gap: 0.25rem; }
.eje-text { margin: 0; font-size: 0.93rem; font-weight: 700; color: #1d2a2e; line-height: 1.35; }
.eje-desc { margin: 0; font-size: 0.8rem; color: #566268; line-height: 1.5; flex: 1; }
.eje-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 0.5rem; }
.eje-badge {
    font-size: 0.68rem; font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 999px; border: 1.5px solid;
    background: transparent;
}
.eje-link { font-size: 0.8rem; font-weight: 600; text-decoration: none; transition: opacity 0.2s; }
.eje-link:hover { opacity: 0.7; }

.ejes-cta { position: relative; z-index: 1; text-align: center; margin-top: 2rem; }
.ejes-cta-btn {
    display: inline-flex;
    align-items: center;
    padding: 0.85rem 2rem;
    background: linear-gradient(135deg, #b10f57 0%, #c0156a 100%);
    color: #fff;
    font-weight: 800;
    font-size: 0.9rem;
    border-radius: 12px;
    text-decoration: none;
    box-shadow: 0 8px 22px rgba(177,15,87,0.3);
    transition: filter 0.2s, transform 0.2s;
}
.ejes-cta-btn:hover { filter: brightness(1.08); transform: translateY(-2px); }

/* ── ACCESO ───────────────────────────────────────────────────────────────── */
.access-block {
    padding: 2.5rem 2rem;
    background: linear-gradient(180deg, rgba(177,15,87,0.04) 0%, rgba(0,152,135,0.04) 100%);
}
.access-card {
    position: relative;
    max-width: 820px;
    margin: 0 auto;
    padding: 2.2rem 2rem;
    background: linear-gradient(135deg, #143d6d 0%, #0c2d52 100%);
    border-radius: 24px;
    box-shadow: 0 18px 44px rgba(10,33,59,0.25);
    text-align: center;
    color: #fff;
    overflow: hidden;
}
.access-rombos { position: absolute; inset: 0; pointer-events: none; }
.ar { position: absolute; line-height: 1; }
.ar--1 { top: -10px;   right: 25px;  font-size: 5rem; color: rgba(177,15,87,0.15); }
.ar--2 { bottom: 10px; left:  20px;  font-size: 3rem; color: rgba(0,152,135,0.15); }
.ar--3 { top:    40%;  left:  48%;   font-size: 1.5rem;color: rgba(255,255,255,0.06); }

.access-badge {
    position: relative;
    display: inline-block;
    margin-bottom: 0.85rem;
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    background: rgba(177,15,87,0.5);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
}
.access-title {
    position: relative;
    margin: 0 0 0.7rem;
    font-size: clamp(1.3rem, 2.2vw, 1.85rem);
    font-weight: 800;
    color: #fff;
}
.access-text {
    position: relative;
    margin: 0 auto 1.3rem;
    max-width: 560px;
    font-size: 0.97rem; line-height: 1.65;
    color: rgba(255,255,255,0.9);
}
.access-actions { position: relative; display: flex; justify-content: center; }
.access-btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0.9rem 2rem;
    border-radius: 12px; text-decoration: none;
    font-weight: 800; font-size: 0.95rem; color: #fff;
    background: linear-gradient(135deg, #b10f57 0%, #d2196d 100%);
    border: 2px solid rgba(255,255,255,0.18);
    box-shadow: 0 10px 24px rgba(177,15,87,0.35);
    transition: transform 0.2s, filter 0.2s, box-shadow 0.2s;
}
.access-btn:hover { transform: translateY(-2px); filter: brightness(1.08); box-shadow: 0 16px 30px rgba(177,15,87,0.42); }

/* ── CARRUSEL ─────────────────────────────────────────────────────────────── */
.carousel-section { position: relative; width: 100%; height: 420px; overflow: hidden; background: #0c1a2e; }
.carousel-slide { position: absolute; inset: 0; opacity: 0; transition: opacity 1.2s ease; pointer-events: none; }
.carousel-slide.active { opacity: 1; pointer-events: auto; }
.carousel-bg { position: absolute; inset: 0; background-size: cover; background-position: center; transform: scale(1.04); transition: transform 6s ease; }
.carousel-slide.active .carousel-bg { transform: scale(1); }
.carousel-overlay { position: absolute; inset: 0; background: linear-gradient(90deg, rgba(8,20,40,0.88) 0%, rgba(8,20,40,0.58) 45%, rgba(8,20,40,0.18) 100%); }
.carousel-content { position: absolute; inset: 0; display: flex; flex-direction: column; justify-content: center; padding: 0 5% 0 6%; max-width: 580px; }
.carousel-dep-tag { display: inline-block; background: rgba(177,15,87,0.85); color: #fff; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; padding: 4px 12px; border-radius: 999px; margin-bottom: 14px; width: fit-content; }
.carousel-title { font-size: clamp(1.2rem, 2.4vw, 1.85rem); font-weight: 800; color: #fff; line-height: 1.25; margin: 0 0 12px; }
.carousel-desc { font-size: 0.93rem; color: rgba(255,255,255,0.82); line-height: 1.65; margin: 0; max-width: 440px; }
.carousel-dots { position: absolute; bottom: 28px; left: 6%; display: flex; gap: 7px; z-index: 10; }
.dot { width: 7px; height: 7px; border-radius: 50%; background: rgba(255,255,255,0.35); border: none; cursor: pointer; padding: 0; transition: background 0.3s, transform 0.2s; }
.dot.active { background: #b10f57; transform: scale(1.4); }
.carousel-controls { position: absolute; bottom: 20px; right: 32px; display: flex; align-items: center; gap: 10px; z-index: 10; }
.carousel-btn { width: 40px; height: 40px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,0.4); background: rgba(255,255,255,0.08); color: #fff; font-size: 1.1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s, border-color 0.2s, transform 0.15s; }
.carousel-btn:hover { background: rgba(177,15,87,0.75); border-color: rgba(255,255,255,0.7); transform: scale(1.08); }
.carousel-progress { position: absolute; bottom: 0; left: 0; height: 3px; background: #b10f57; width: 0%; z-index: 10; }

/* ── Keyframes ────────────────────────────────────────────────────────────── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(22px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 980px) {
    .que-es-grid { grid-template-columns: 1fr; }
    .ejes-grid   { grid-template-columns: 1fr; }
    .eje-icon-wrap { width: 90px; min-width: 90px; }
}

@media (max-width: 768px) {
    .hero-strip   { padding: 2.2rem 1.25rem 2rem; }
    .hero-actions { flex-direction: column; }
    .hero-btn-primary, .hero-btn-outline, .hero-btn-ghost { text-align: center; justify-content: center; }
    .hero-stats   { width: 100%; justify-content: center; }
    .hstat        { padding: 0.25rem 1rem; }
    .intro-block, .ejes-block, .access-block { padding-left: 1.1rem; padding-right: 1.1rem; }
    .section-main-title br { display: none; }
    .intro-ctas   { flex-direction: column; align-items: center; gap: 1rem; }
    .eje-icon-wrap { width: 80px; min-width: 80px; }
    .carousel-section { height: 360px; }
    .carousel-content { padding: 0 4%; max-width: 100%; }
    .carousel-title   { font-size: 1.1rem; }
    .carousel-desc    { font-size: 0.85rem; }
    .access-card      { padding: 1.6rem 1.1rem; border-radius: 18px; }
    .access-btn       { width: 100%; max-width: 320px; }
}

@media (max-width: 480px) {
    .hstat-sep  { display: none; }
    .hero-stats { gap: 0.5rem; }
    .hstat-num  { font-size: 1.6rem; }
}
</style>
