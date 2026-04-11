<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    canLogin: Boolean,
})

const INTERVAL = 5000
const carouselIndex = ref(0)
const progressBar = ref(null)
let timer = null

const dependencias = [
    {
        nombre: 'Consejo de Participación Ciudadana',
        img: '/images/dependencias/CPC.png',
        desc: 'Órgano ciudadano encargado de ser el canal de comunicación entre la sociedad y las autoridades dentro del Sistema Anticorrupción del Estado de Chiapas (SAECH). Su función principal es vigilar, proponer políticas públicas y dar seguimiento a las acciones de combate a la corrupción en la entidad. ',
    },
    {
        nombre: 'Secretaría Anticorrupción y Buen Gobierno',
        img: '/images/dependencias/Buen_Gobierno.jpg',
        desc: 'dependencia del Poder Ejecutivo de Chiapas encargada de la fiscalización, transparencia y combate a la corrupción en la administración pública estatal.',
    },
    {
        nombre: 'Fiscalia General del Estado de Chiapas',
        img: '/images/dependencias/Fiscalia_general.jpg',
        desc: 'Órgano constitucional autónomo encargado de la procuración de justicia, la investigación de delitos y el ejercicio de la acción penal en el estado.',
    },
    {
        nombre: 'Auditoría Superior del Estado de Chiapas',
        img: '/images/dependencias/ASE.jpeg',
        desc: 'Órgano técnico encargado de revisar y fiscalizar el uso de los recursos públicos en el estado y sus municipios. Es una institución con autonomía técnica y de gestión que depende del Honorable Congreso del Estado.',
    },
    {
        nombre: 'Tribunal de Justicia Administrativa del Estado de Chiapas',
        img: '/images/dependencias/LOGO_TJA.png',
        desc: 'Órgano autónomo encargado de resolver las controversias entre la administración pública (estatal o municipal) y los particulares, además de sancionar responsabilidades administrativas graves.',
    },
    {
        nombre: 'Poder Judicial del Estado de Chiapas',
        img: '/images/dependencias/PJE.jpg',
        desc: 'Institución pública responsable de administrar e impartir justicia en el estado de Chiapas. Su función principal es resolver conflictos entre ciudadanos o entre estos y el gobierno, asegurando que se respeten las leyes y los derechos humanos..',
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

onMounted(() => {
    resetProgress()
    timer = setInterval(() => goTo(carouselIndex.value + 1), INTERVAL)
})

onUnmounted(() => clearInterval(timer))
</script>

<template>
    <Head title="PIPEA — Secretaría Anticorrupción y Buen Gobierno · Chiapas" />

    <div class="public-root">
        <!-- BANNER INSTITUCIONAL -->
        <div class="public-banner">
            <div class="public-banner-inner">
                <img src="/images/banner-sesaech.png" alt="SESAECH" class="banner-img" />
            </div>
        </div>

        <main class="landing-wrap">
            <!-- CABECERA SUPERIOR -->

            <!-- FRANJA PRINCIPAL -->

            <!-- ¿QUÉ ES? -->
            <section id="que-es" class="intro-block">
                <h3 class="section-main-title">
                    ¿Qué es el Programa de Implementación de la
                    <br />
                    Política Estatal Anticorrupción (PI PEA)?
                </h3>

                <div class="info-grid">
                    <article class="info-box info-box--light" tabindex="0">
                        <div class="info-box-accent"></div>
                        <h4 class="info-box-title">Política Estatal Anticorrupción</h4>
                        <p class="info-box-text">
                            El programa de implementación de la PEA establece las líneas de acción
                            que deben seguir las dependencias del gobierno de Chiapas para combatir
                            la corrupción de manera sistemática.
                        </p>
                    </article>

                    <article class="info-box info-box--light" tabindex="0">
                        <div class="info-box-accent"></div>
                        <h4 class="info-box-title">Organismos implementadores</h4>
                        <p class="info-box-text">
                            Las secretarías o entidades del gobierno estatal son responsables de
                            ejecutar y reportar el avance de las líneas asignadas conforme al
                            programa.
                        </p>
                    </article>

                    <article class="info-box info-box--light" tabindex="0">
                        <div class="info-box-accent"></div>
                        <h4 class="info-box-title">Monitoreo y Seguimiento</h4>
                        <p class="info-box-text">
                            La SESAECH supervisa el cumplimiento del programa a través de esta
                            plataforma, registrando avances, evidencias y experiencia institucional.
                        </p>
                    </article>
                </div>
            </section>

            <!-- EJES -->
            <section id="objetivos" class="ejes-block">
                <div class="ejes-watermark" aria-hidden="true"></div>

                <div class="ejes-head">
                    <h3 class="ejes-title">Ejes de la PI PEA</h3>
                    <p class="ejes-subtitle">
                        Generar las condiciones necesarias para un control efectivo de la corrupción
                        en Chiapas
                    </p>
                </div>

                <div class="ejes-grid">
                    <article class="eje-card" tabindex="0">
                        <div class="eje-icon">💵</div>
                        <div>
                            <span class="eje-num">Eje 1</span>
                            <p class="eje-text">Combatir la corrupción y la impunidad</p>
                        </div>
                    </article>

                    <article class="eje-card" tabindex="0">
                        <div class="eje-icon">⚖️</div>
                        <div>
                            <span class="eje-num">Eje 2</span>
                            <p class="eje-text">Combatir la arbitrariedad y el abuso de poder</p>
                        </div>
                    </article>

                    <article class="eje-card" tabindex="0">
                        <div class="eje-icon">👁️</div>
                        <div>
                            <span class="eje-num">Eje 3</span>
                            <p class="eje-text">
                                Promover la mejora de la gestión pública y de los puntos de contacto
                                gobierno-sociedad
                            </p>
                        </div>
                    </article>

                    <article class="eje-card" tabindex="0">
                        <div class="eje-icon">🤝</div>
                        <div>
                            <span class="eje-num">Eje 4</span>
                            <p class="eje-text">Involucrar a la sociedad y el sector privado</p>
                        </div>
                    </article>
                </div>
            </section>

            <!-- ACCESO -->
            <section id="organismos" class="access-block">
                <div class="access-card">
                    <span class="access-badge">Acceso institucional</span>

                    <h3 class="access-title">¿Eres servidor público autorizado?</h3>

                    <p class="access-text">
                        Ingresa al sistema para registrar, consultar y dar seguimiento al avance de
                        tu organismo en el cumplimiento de la PI PEA.
                    </p>

                    <div class="access-actions">
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="access-btn"
                        >
                            Acceder al sistema
                        </Link>
                    </div>
                </div>
            </section>

            <!-- ═══════════════════════════════════════
                 CARRUSEL DE DEPENDENCIAS
            ════════════════════════════════════════ -->
            <section class="carousel-section">
                <div
                    v-for="(dep, i) in dependencias"
                    :key="i"
                    class="carousel-slide"
                    :class="{ active: carouselIndex === i }"
                >
                    <div
                        class="carousel-bg"
                        :style="{ backgroundImage: `url(${dep.img})` }"
                    ></div>
                    <div class="carousel-overlay"></div>
                    <div class="carousel-content">
                        <span class="carousel-dep-tag">Organismo implementador</span>
                        <h3 class="carousel-title">{{ dep.nombre }}</h3>
                        <p class="carousel-desc">{{ dep.desc }}</p>
                    </div>
                </div>

                <!-- Dots -->
                <div class="carousel-dots">
                    <button
                        v-for="(_, i) in dependencias"
                        :key="i"
                        class="dot"
                        :class="{ active: carouselIndex === i }"
                        @click="goTo(i)"
                    />
                </div>

                <!-- Flechas -->
                <div class="carousel-controls">
                    <button class="carousel-btn" @click="goTo(carouselIndex - 1)">&#8592;</button>
                    <button class="carousel-btn" @click="goTo(carouselIndex + 1)">&#8594;</button>
                </div>

                <!-- Barra de progreso -->
                <div class="carousel-progress" ref="progressBar"></div>
            </section>

        </main>
    </div>
</template>

<style scoped>
.public-root {
    min-height: 100vh;
    width: 100%;
    margin: 0;
    padding: 0;
    background: linear-gradient(180deg, #f3f3f3 0%, #ececec 100%);
    color: #24343a;
    font-family: Arial, sans-serif;
    overflow-x: hidden;
}

.public-banner {
    width: 100%;
    margin: 0;
    padding: 0;
}

.public-banner-inner {
    width: 100%;
    margin: 0;
    padding: 0;
}

.banner-img {
    width: 100%;
    display: block;
    object-fit: cover;
}

/* CONTENEDOR GENERAL */
.landing-wrap {
    width: 100%;
    max-width: 100%;
    margin: 0;
    background: #ffffff;
    box-shadow: none;
    overflow: hidden;
}

/* CABECERA */
.top-brand {
    width: 100%;
    background: #f5f5f5;
    padding: 1rem 2rem;
    border-bottom: 2px solid #c20f62;
}

.top-brand-grid {
    width: 100%;
    display: grid;
    grid-template-columns: 140px 1fr 140px;
    align-items: center;
    gap: 1rem;
}

.top-brand-spacer {
    width: 100%;
    height: 1px;
}

.top-brand-center {
    text-align: center;
    min-width: 0;
}

.top-brand-title {
    margin: 0;
    font-size: clamp(1.7rem, 3.4vw, 4rem);
    line-height: 1.08;
    font-weight: 800;
    color: #2e6d73;
    word-break: break-word;
}

.top-brand-logo {
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand-right-box {
    text-align: center;
    color: #7a5d95;
}

.brand-right-text {
    display: block;
    font-size: clamp(1.4rem, 2vw, 2.4rem);
    font-weight: 800;
    letter-spacing: 0.06em;
}

.brand-right-sub {
    font-size: clamp(0.8rem, 1vw, 1rem);
    opacity: 0.95;
}

/* FRANJA PRINCIPAL */
.hero-strip {
    width: 100%;
    background: linear-gradient(135deg, #b40d5d 0%, #c00f63 100%);
    padding: 1.2rem 1rem;
    text-align: center;
}

.hero-strip-title {
    margin: 0;
    color: #ffffff;
    font-size: clamp(1.5rem, 3vw, 2.8rem);
    line-height: 1.18;
    font-weight: 800;
}

/* INTRO */
.intro-block {
    width: 100%;
    padding: 2.2rem 2rem 2rem;
    background: #f7f7f7;
}

.section-main-title {
    margin: 0 0 1.8rem;
    text-align: center;
    font-size: clamp(1.4rem, 2.7vw, 2.6rem);
    line-height: 1.25;
    font-weight: 800;
    color: #3e6f78;
}

.info-grid {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1rem;
}

.info-box {
    position: relative;
    min-height: 210px;
    padding: 1.2rem 1.1rem 1.1rem 1.2rem;
    border-radius: 18px;
    overflow: hidden;
    cursor: pointer;
    outline: none;
    background: #f6eff2;
    border: 1px solid #e6c9d2;
    box-shadow:
        0 8px 22px rgba(0, 0, 0, 0.06),
        inset 0 0 0 1px rgba(177, 15, 87, 0.06);
    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease,
        border-color 0.25s ease,
        background 0.25s ease;
}

.info-box-accent {
    position: absolute;
    inset: 0 auto 0 0;
    width: 6px;
    background: linear-gradient(180deg, #c3122f 0%, #b10f57 100%);
    opacity: 0.95;
}

.info-box:hover,
.info-box:focus {
    transform: translateY(-6px);
    background: #fbf5f7;
    border-color: #d6899f;
    box-shadow:
        0 18px 38px rgba(159, 13, 61, 0.14),
        0 0 0 3px rgba(181, 15, 87, 0.08);
}

.info-box-title {
    position: relative;
    margin: 0 0 0.8rem;
    font-size: 1.1rem;
    line-height: 1.2;
    font-weight: 800;
    color: #1d1d1d;
}

.info-box-text {
    position: relative;
    margin: 0;
    font-size: 1rem;
    line-height: 1.6;
    color: #4a4a4a;
}

/* EJES */
.ejes-block {
    position: relative;
    width: 100%;
    padding: 2.4rem 2rem;
    background: #ffffff;
    overflow: hidden;
}

.ejes-watermark {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(45deg, transparent 46%, rgba(0, 152, 135, 0.07) 46%, rgba(0, 152, 135, 0.07) 54%, transparent 54%),
        linear-gradient(-45deg, transparent 46%, rgba(177, 15, 87, 0.07) 46%, rgba(177, 15, 87, 0.07) 54%, transparent 54%);
    background-size: 260px 260px;
    background-position: center;
    pointer-events: none;
}

.ejes-head {
    position: relative;
    z-index: 1;
    text-align: center;
    margin-bottom: 1.4rem;
}

.ejes-title {
    margin: 0;
    font-size: clamp(1.5rem, 2.7vw, 2.5rem);
    font-weight: 800;
    color: #b10f57;
}

.ejes-subtitle {
    max-width: 780px;
    margin: 0.5rem auto 0;
    font-size: 1rem;
    line-height: 1.55;
    color: #566268;
}

.ejes-grid {
    position: relative;
    z-index: 1;
    width: 100%;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.eje-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    min-height: 112px;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid #e5d5da;
    border-radius: 18px;
    padding: 1rem 1rem;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease,
        border-color 0.25s ease,
        background 0.25s ease;
    cursor: pointer;
    outline: none;
}

.eje-card:hover,
.eje-card:focus {
    transform: translateY(-5px);
    border-color: #d78aa0;
    background: linear-gradient(180deg, #ffffff 0%, #fff6f8 100%);
    box-shadow:
        0 18px 34px rgba(177, 15, 87, 0.12),
        0 0 0 3px rgba(177, 15, 87, 0.08);
}

.eje-icon {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f9eef2 0%, #f1f5f6 100%);
    font-size: 1.7rem;
    box-shadow: inset 0 0 0 1px rgba(177, 15, 87, 0.08);
}

.eje-num {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.92rem;
    font-weight: 800;
    color: #6a7f84;
}

.eje-text {
    margin: 0;
    font-size: 1rem;
    line-height: 1.45;
    font-weight: 700;
    color: #33474e;
}

/* ACCESO */
.access-block {
    width: 100%;
    padding: 2rem;
    background:
        radial-gradient(circle at top left, rgba(177, 15, 87, 0.08), transparent 32%),
        radial-gradient(circle at top right, rgba(0, 152, 135, 0.08), transparent 32%),
        linear-gradient(180deg, #f6f8fb 0%, #edf3f8 100%);
}

.access-card {
    width: 100%;
    max-width: 820px;
    margin: 0 auto;
    padding: 2rem 1.5rem;
    background: linear-gradient(135deg, #143d6d 0%, #0c2d52 100%);
    border-radius: 24px;
    box-shadow: 0 18px 44px rgba(10, 33, 59, 0.25);
    text-align: center;
    color: #ffffff;
    position: relative;
    overflow: hidden;
}

.access-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px);
    background-size: 24px 24px;
    pointer-events: none;
}

.access-badge,
.access-title,
.access-text,
.access-actions {
    position: relative;
    z-index: 1;
}

.access-badge {
    display: inline-block;
    margin-bottom: 0.85rem;
    padding: 0.45rem 0.8rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.access-title {
    margin: 0 0 0.7rem;
    font-size: clamp(1.4rem, 2.2vw, 2rem);
    font-weight: 800;
    line-height: 1.2;
    color: #ffffff;
}

.access-text {
    margin: 0 auto 1.3rem;
    max-width: 600px;
    font-size: 1rem;
    line-height: 1.65;
    color: rgba(255, 255, 255, 0.93);
}

.access-actions {
    display: flex;
    justify-content: center;
}

.access-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.95rem 1.6rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 800;
    font-size: 1rem;
    color: #ffffff;
    background: linear-gradient(135deg, #b10f57 0%, #d2196d 100%);
    border: 2px solid rgba(255, 255, 255, 0.18);
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        filter 0.2s ease;
    box-shadow: 0 10px 24px rgba(177, 15, 87, 0.35);
}

.access-btn:hover,
.access-btn:focus {
    transform: translateY(-2px);
    filter: brightness(1.05);
    box-shadow: 0 16px 30px rgba(177, 15, 87, 0.42);
}

/* ═══════════════════════════════════════
   CARRUSEL DE DEPENDENCIAS
════════════════════════════════════════ */
.carousel-section {
    position: relative;
    width: 100%;
    height: 420px;
    overflow: hidden;
    background: #0c1a2e;
}

.carousel-slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 1.2s ease;
    pointer-events: none;
}

.carousel-slide.active {
    opacity: 1;
    pointer-events: auto;
}

.carousel-bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    transform: scale(1.04);
    transition: transform 6s ease;
}

.carousel-slide.active .carousel-bg {
    transform: scale(1);
}

.carousel-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        90deg,
        rgba(8, 20, 40, 0.88) 0%,
        rgba(8, 20, 40, 0.60) 45%,
        rgba(8, 20, 40, 0.18) 100%
    );
}

.carousel-content {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0 5% 0 6%;
    max-width: 580px;
}

.carousel-dep-tag {
    display: inline-block;
    background: rgba(177, 15, 87, 0.85);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 999px;
    margin-bottom: 14px;
    width: fit-content;
}

.carousel-title {
    font-size: clamp(1.3rem, 2.4vw, 1.9rem);
    font-weight: 800;
    color: #ffffff;
    line-height: 1.25;
    margin: 0 0 12px;
    font-family: Arial, sans-serif;
}

.carousel-desc {
    font-size: 0.95rem;
    color: rgba(255, 255, 255, 0.82);
    line-height: 1.65;
    margin: 0;
    max-width: 440px;
    font-family: Arial, sans-serif;
}

.carousel-dots {
    position: absolute;
    bottom: 28px;
    left: 6%;
    display: flex;
    gap: 7px;
    z-index: 10;
}

.dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.35);
    border: none;
    cursor: pointer;
    padding: 0;
    transition: background 0.3s, transform 0.2s;
}

.dot.active {
    background: #ffffff;
    transform: scale(1.35);
}

.carousel-controls {
    position: absolute;
    bottom: 20px;
    right: 32px;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 10;
}

.carousel-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1.5px solid rgba(255, 255, 255, 0.45);
    background: rgba(255, 255, 255, 0.10);
    color: #fff;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, border-color 0.2s, transform 0.15s;
}

.carousel-btn:hover {
    background: rgba(177, 15, 87, 0.75);
    border-color: rgba(255, 255, 255, 0.7);
    transform: scale(1.08);
}

.carousel-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: rgba(177, 15, 87, 0.9);
    width: 0%;
    z-index: 10;
}

/* RESPONSIVE */
@media (max-width: 980px) {
    .top-brand {
        padding: 1rem;
    }

    .top-brand-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    .top-brand-spacer {
        display: none;
    }

    .top-brand-logo--right {
        justify-content: center;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .ejes-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .hero-strip {
        padding: 0.9rem 0.8rem;
    }

    .intro-block,
    .ejes-block,
    .access-block {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .section-main-title br,
    .top-brand-title br,
    .hero-strip-title br {
        display: none;
    }

    .info-box {
        min-height: auto;
    }

    .eje-card {
        align-items: flex-start;
        min-height: auto;
    }

    .access-card {
        padding: 1.5rem 1rem;
        border-radius: 18px;
    }

    .access-btn {
        width: 100%;
        max-width: 320px;
    }

    .carousel-section {
        height: 360px;
    }

    .carousel-title {
        font-size: 1.1rem;
    }

    .carousel-desc {
        font-size: 0.85rem;
    }

    .carousel-content {
        padding: 0 4% 0 4%;
        max-width: 100%;
    }
}
</style>
