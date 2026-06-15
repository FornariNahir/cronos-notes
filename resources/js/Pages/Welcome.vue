<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

onMounted(async () => {
    // Add page-specific body class to force light theme
    document.body.classList.add('cn-body-light');

    // Dynamically load AOS Stylesheet if not already present
    if (!document.querySelector('link[href*="aos.css"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css';
        document.head.appendChild(link);
    }

    // Helper to load dynamic scripts
    const loadScript = (src) => {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve();
                return;
            }
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject();
            document.head.appendChild(script);
        });
    };

    // Load libraries
    try {
        await Promise.all([
            loadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'),
            loadScript('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js'),
            loadScript('https://cdn.jsdelivr.net/npm/typed.js@2.1.0/dist/typed.umd.js')
        ]);

        // ---- AOS (Animate On Scroll) ----
        if (window.AOS) {
            window.AOS.init({ duration: 800, easing: 'ease-out-cubic', once: true, offset: 80 });
        }

        // ---- Typed.js (texto dinámico del hero) ----
        if (window.Typed) {
            new window.Typed('#typed', {
                strings: [
                    'gestionar tu tiempo.',
                    'optimizar tus materias.',
                    'dominar tu Pomodoro.',
                    'priorizar con IA.'
                ],
                typeSpeed: 60,
                backSpeed: 35,
                backDelay: 1600,
                startDelay: 400,
                loop: true,
                smartBackspace: true
            });
        }
    } catch (err) {
        console.error('Error loading scripts:', err);
    }

    // ---- Navbar con cambio al hacer scroll ----
    const navbar = document.getElementById('cnNavbar');
    const onScroll = () => {
        if (!navbar) return;
        if (window.scrollY > 30) navbar.classList.add('scrolled');
        else navbar.classList.remove('scrolled');
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // ---- Cerrar el menú colapsable al hacer clic en un enlace (móvil) ----
    const navCollapse = document.getElementById('cnNav');
    document.querySelectorAll('#cnNav .nav-link, #cnNav .btn').forEach((link) => {
        link.addEventListener('click', () => {
            if (navCollapse && navCollapse.classList.contains('show')) {
                const bsCollapse = window.bootstrap?.Collapse?.getInstance(navCollapse) || new window.bootstrap.Collapse(navCollapse);
                bsCollapse.hide();
            }
        });
    });


    // ---- Efecto Tilt + Parallax en el mockup del hero ----
    const tilt = document.getElementById('cnTilt');
    if (tilt && window.matchMedia('(pointer:fine)').matches) {
        const inner = tilt.querySelector('.cn-mockup-inner');
        const depthEls = tilt.querySelectorAll('[data-depth]');
        const maxTilt = 10;

        tilt.addEventListener('mousemove', (e) => {
            if (!inner) return;
            const rect = tilt.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;

            inner.style.transform = `rotateY(${x * maxTilt}deg) rotateX(${-y * maxTilt}deg)`;

            depthEls.forEach((el) => {
                const depth = parseFloat(el.dataset.depth) || 0.5;
                el.style.transform = `translate(${x * depth * 22}px, ${y * depth * 22}px)`;
            });
        });

        tilt.addEventListener('mouseleave', () => {
            if (!inner) return;
            inner.style.transform = 'rotateY(0) rotateX(0)';
            depthEls.forEach((el) => { el.style.transform = 'translate(0,0)'; });
        });
    }
});

onUnmounted(() => {
    // Clean up class to return body to dashboard style
    document.body.classList.remove('cn-body-light');
});
</script>

<template>
    <Head>
        <title>Cronos Notes — Domina tu tiempo con el método Pomodoro e IA</title>
        <meta name="description" content="Cronos Notes fusiona el método Pomodoro y la Inteligencia Artificial para priorizar tus tareas y optimizar tu enfoque." />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    </Head>

    <!-- ===== NAVBAR ===== -->
    <nav class="navbar navbar-expand-lg fixed-top cn-navbar" id="cnNavbar">
        <div class="container">
            <a class="navbar-brand cn-brand" href="#hero">
                <img src="/img/logo-cronos.png" alt="Cronos Notes Logo" class="cn-navbar-logo" />
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#cnNav" aria-controls="cnNav" aria-expanded="false" aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>

            <div class="collapse navbar-collapse" id="cnNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
                    <li class="nav-item"><a class="nav-link cn-link" href="#hero">Inicio</a></li>
                    <li class="nav-item"><Link :href="route('uso') + '#top'" class="nav-link cn-link">Modo de Uso</Link></li>
                    <li class="nav-item"><Link :href="route('caracteristicas') + '#top'" class="nav-link cn-link">Características</Link></li>
                    <li class="nav-item"><Link :href="route('quienes-somos') + '#top'" class="nav-link cn-link">Quiénes Somos</Link></li>
                </ul>
                <div v-if="$page.props.auth && $page.props.auth.user" class="d-flex gap-2">
                    <Link :href="route('dashboard')" class="btn cn-btn-primary px-4">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </Link>
                </div>
                <div v-else class="d-flex gap-2 align-items-center">
                    <Link :href="route('login')" class="btn cn-btn-ghost px-3">
                        Iniciar Sesión
                    </Link>
                    <Link :href="route('register')" class="btn cn-btn-primary px-4">
                        Registrarse
                    </Link>
                </div>
            </div>
        </div>
    </nav>

    <!-- ===== HERO ===== -->
    <header class="cn-hero" id="hero">
        <div class="cn-hero-glow" aria-hidden="true"></div>
        <div class="container">
            <div class="row align-items-center g-5">
                <!-- Texto -->
                <div class="col-lg-6" data-aos="fade-right" data-aos-duration="900">
                    <span class="cn-badge mb-3"><i class="bi bi-stars me-1"></i> Productividad impulsada por IA</span>
                    <h1 class="cn-hero-title" style="text-align: left !important; line-height: 0.9 !important;">
                        <span class="cn-typed-prefix" style="text-align: left !important;">Cronos te ayuda a</span><br />
                        <span id="typed" class="cn-accent-text" style="text-align: left !important; display: inline !important;">gestionar tu tiempo.</span>
                    </h1>
                    <p class="cn-hero-lead">
                        Cronos Notes fusiona el legendario <strong>Método Pomodoro</strong> con
                        <strong>Inteligencia Artificial</strong> para priorizar tus tareas de forma
                        inteligente. Enfócate en lo que realmente importa, sesión tras sesión.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <Link v-if="!($page.props.auth && $page.props.auth.user)" :href="route('register')" class="btn cn-btn-primary btn-lg px-4 cn-cta">
                            Comenzar Ahora <i class="bi bi-arrow-right ms-1"></i>
                        </Link>
                        <Link v-else :href="route('dashboard')" class="btn cn-btn-primary btn-lg px-4 cn-cta">
                            Ir al Dashboard <i class="bi bi-arrow-right ms-1"></i>
                        </Link>
                        <Link :href="route('uso')" class="btn cn-btn-ghost btn-lg px-4">
                            <i class="bi bi-play-circle me-1"></i> Ver Modo de Uso
                        </Link>
                    </div>
                    <div class="cn-hero-meta mt-4">
                        <div class="cn-avatars">
                            <span></span><span></span><span></span><span></span>
                        </div>
                        <span class="cn-meta-text">Empieza a Optimiar tu Tiempo Ahora</span>
                    </div>
                </div>

                <!-- Mockup interactivo (tilt + parallax) -->
                <div class="col-lg-6" data-aos="fade-left" data-aos-duration="900">
                    <div class="cn-mockup" id="cnTilt">
                        <div class="cn-mockup-inner">
                            <div class="cn-mockup-head">
                                <span class="dot dot-r"></span><span class="dot dot-y"></span><span class="dot dot-g"></span>
                                <span class="cn-mockup-title">Sesión de Enfoque · Cálculo II</span>
                            </div>

                            <div class="cn-timer" data-depth="0.6">
                                <div class="cn-timer-ring">
                                    <svg viewBox="0 0 120 120">
                                        <circle class="ring-bg" cx="60" cy="60" r="52"></circle>
                                        <circle class="ring-fg" cx="60" cy="60" r="52"></circle>
                                    </svg>
                                    <div class="cn-timer-label">
                                        <span class="cn-timer-time">24:18</span>
                                        <span class="cn-timer-state">Enfocado</span>
                                    </div>
                                </div>
                            </div>

                            <div class="cn-tasks" data-depth="0.3">
                                <div class="cn-task done">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Repasar derivadas parciales</span>
                                    <span class="cn-pill cn-pill-soft">Alta</span>
                                </div>
                                <div class="cn-task active">
                                    <i class="bi bi-record-circle"></i>
                                    <span>Resolver guía de integrales</span>
                                    <span class="cn-pill cn-pill-accent">IA · Ahora</span>
                                </div>
                                <div class="cn-task">
                                    <i class="bi bi-circle"></i>
                                    <span>Resumen capítulo 4</span>
                                    <span class="cn-pill cn-pill-soft">Media</span>
                                </div>
                            </div>
                        </div>
                        <div class="cn-float-card cn-float-1" data-depth="1.2">
                            <i class="bi bi-music-note-beamed"></i> Audio Zen
                        </div>
                        <div class="cn-float-card cn-float-2" data-depth="0.9">
                            <i class="bi bi-graph-up-arrow"></i> +98% foco
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- ===== CÓMO FUNCIONA / CARACTERÍSTICAS ===== -->
    <section class="cn-section" id="como-funciona">
        <div class="container">
            <div class="text-center mx-auto cn-section-head" data-aos="fade-up">
                <span class="cn-eyebrow">Cómo funciona</span>
                <h2 class="cn-section-title">Todo lo que necesitas para enfocarte</h2>
                <p class="cn-section-sub">Herramientas diseñadas para estudiantes y profesionales que quieren resultados reales, sin distracciones.</p>
            </div>

            <div class="row g-4 mt-2" id="caracteristicas">
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
                    <article class="cn-card cn-card-img h-100">
                        <div class="cn-card-media">
                            <img src="/imagenes/feature-app.png" alt="Panel de gestión de cátedras de Cronos Notes" loading="lazy" />
                        </div>
                        <div class="cn-card-icon"><i class="bi bi-folder2-open"></i></div>
                        <h3 class="cn-card-title">Gestión de Perfiles</h3>
                        <p class="cn-card-text">Espacios de trabajo organizados por áreas. Mantén cada cátedra ordenada con sus notas, tareas y recursos en un solo lugar.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="150">
                    <article class="cn-card cn-card-img h-100">
                        <div class="cn-card-media">
                            <img src="/imagenes/student-focus.png" alt="Estudiante concentrado usando el temporizador Pomodoro" loading="lazy" />
                        </div>
                        <div class="cn-card-icon"><i class="bi bi-hourglass-split"></i></div>
                        <h3 class="cn-card-title">Temporizador Pomodoro</h3>
                        <p class="cn-card-text">Sesiones de enfoque profundo con audio mezclado integrado y descansos para mantener tu energía durante horas.</p>
                    </article>
                </div>
                <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="300">
                    <article class="cn-card cn-card-img h-100">
                        <div class="cn-card-media">
                            <img src="/imagenes/hero-workspace.jpg" alt="Escritorio de estudio organizado con IA" loading="lazy" />
                        </div>
                        <div class="cn-card-icon"><i class="bi bi-cpu"></i></div>
                        <h3 class="cn-card-title">Priorización por IA</h3>
                        <p class="cn-card-text">Algoritmos que analizan tus pendientes, plazos y dificultad para ordenar automáticamente qué deberías hacer primero.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SHOWCASE / VIDEO DEMO ===== -->
    <section class="cn-showcase">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <span class="cn-eyebrow">En acción</span>
                    <h2 class="cn-section-title">Mira cómo Cronos transforma tu estudio</h2>
                    <p class="cn-section-sub">Una interfaz cálida y sin distracciones que combina temporizador, tareas y enfoque profundo. Reproduce el video y descubre el flujo de una sesión real.</p>
                    <ul class="cn-check-list">
                        <li><i class="bi bi-check-circle-fill"></i> Audio ambiental para concentración total</li>
                        <li><i class="bi bi-check-circle-fill"></i> Fondos estáticos y animados</li>
                        <li><i class="bi bi-check-circle-fill"></i> Estadísticas de enfoque tras cada sesión</li>
                    </ul>
                    <Link v-if="!($page.props.auth && $page.props.auth.user)" :href="route('register')" class="btn cn-btn-primary btn-lg px-4 mt-2">Probar ahora <i class="bi bi-arrow-right ms-1"></i></Link>
                    <Link v-else :href="route('dashboard')" class="btn cn-btn-primary btn-lg px-4 mt-2">Probar ahora <i class="bi bi-arrow-right ms-1"></i></Link>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="cn-video-frame">
                        <video class="cn-video" autoplay muted loop playsinline poster="/imagenes/feature-app.png" src="/imagenes/videoPomodoro.mp4"></video>
                        <div class="cn-video-badge"><i class="bi bi-broadcast"></i> Demo en vivo</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== PASO A PASO (Cómo empezar) ===== -->
    <section class="cn-stats" id="nosotros">
        <div class="container">
            <div class="text-center mx-auto cn-section-head" data-aos="fade-up">
                <span class="cn-eyebrow">Paso a paso</span>
                <h2 class="cn-section-title">Comienza a optimizar tu tiempo en 4 simples pasos</h2>
            </div>
            <div class="row g-4 mt-2">
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                    <div class="cn-step-card text-center h-100">
                        <div class="cn-step-num-badge">1</div>
                        <div class="cn-step-icon"><i class="bi bi-folder-plus"></i></div>
                        <h3 class="cn-step-title">1er Paso: Créate un perfil</h3>
                        <p class="cn-step-desc">Organiza tu estudio o trabajo creando perfiles específicos para tus materias o proyectos.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="cn-step-card text-center h-100">
                        <div class="cn-step-num-badge">2</div>
                        <div class="cn-step-icon"><i class="bi bi-list-task"></i></div>
                        <h3 class="cn-step-title">2do Paso: Créate una tarea</h3>
                        <p class="cn-step-desc">Agrega tus pendientes y deja que la Inteligencia Artificial priorice lo más urgente por ti.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="cn-step-card text-center h-100">
                        <div class="cn-step-num-badge">3</div>
                        <div class="cn-step-icon"><i class="bi bi-hourglass-split"></i></div>
                        <h3 class="cn-step-title">3er Paso: Inicia tu sesión Pomodoro</h3>
                        <p class="cn-step-desc">Activa el temporizador de enfoque, relájate con audios zen y concéntrate sin interrupciones.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="cn-step-card text-center h-100">
                        <div class="cn-step-num-badge">4</div>
                        <div class="cn-step-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <h3 class="cn-step-title">4to Paso: Mira tus estadísticas</h3>
                        <p class="cn-step-desc">Revisa tus horas de enfoque, tu racha de días activos y analiza cómo mejora tu productividad.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FAQ ===== -->
    <section class="cn-section" id="faq">
        <div class="container">
            <div class="text-center mx-auto cn-section-head" data-aos="fade-up">
                <span class="cn-eyebrow">Dudas &amp; Aclaraciones</span>
                <h2 class="cn-section-title">Preguntas frecuentes</h2>
                <p class="cn-section-sub">Empieza gratis hoy. Sin tarjeta de crédito, sin complicaciones.</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
                    <div class="accordion cn-accordion" id="cnFaq">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    ¿Cronos Notes es realmente gratis?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#cnFaq">
                                <div class="accordion-body">Sí. Cronos Notes es gratis en su totalidad, integrando un modo invitado, para aquellos que quieren hacerlo rápido</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    ¿Cómo prioriza mis tareas la IA?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#cnFaq">
                                <div class="accordion-body">Analiza fechas de entrega, dificultad estimada y tu historial de enfoque para sugerirte el orden óptimo de trabajo en cada sesión.</div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    ¿Funciona en el móvil?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#cnFaq">
                                <div class="accordion-body">Totalmente. Cronos Notes es 100% responsive y se adapta a cualquier dispositivo para que estudies estés donde estés.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="cn-footer">
        <div class="container">
            <div class="row g-4 align-items-start justify-content-between">
                <div class="col-lg-6">
                    <a class="navbar-brand cn-brand cn-brand-footer" href="#hero">
                        <img src="/img/logo-cronos.png" alt="Cronos Notes Logo" class="cn-footer-logo" />
                    </a>
                    <p class="cn-footer-text mt-3">El espacio inteligente donde tu tiempo y tus tareas trabajan a tu favor.</p>
                </div>
                <div class="col-lg-3">
                    <h4 class="cn-footer-head">Producto</h4>
                    <ul class="cn-footer-links">
                        <li><Link :href="route('caracteristicas')">Características</Link></li>
                        <li><Link :href="route('uso')">Modo de uso</Link></li>
                        <li><a href="#faq">Preguntas frecuentes</a></li>
                    </ul>
                </div>
            </div>
            <hr class="cn-footer-divider" />
            <p class="cn-footer-credits">© 2026 Cronos Notes. Hecho con enfoque y un par de Pomodoros.</p>
        </div>
    </footer>
</template>

<style>
/* ===================================================================
   CRONOS NOTES — Estilos personalizados
   Paleta institucional marrón - LIGERA/PREDETERMINADA
   =================================================================== */
:root {
  --cn-brown-dark: #4c2521;
  --cn-brown-dark-2: #69342e;
  --cn-brown-soft: #c17f59;
  --cn-brown-hover: #542924;
  --cn-bg-light: #f8f9fa;
  --cn-white: #ffffff;
  --cn-text: #212529;
  --cn-text-muted: #6b5e59;
  --cn-radius: 18px;
  --cn-shadow: 0 18px 50px -20px rgba(76, 37, 33, 0.35);
  --cn-shadow-soft: 0 10px 30px -15px rgba(76, 37, 33, 0.25);
}

/* Evitar conflictos con hojas globales en el landing */
body.cn-body-light {
  font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
  color: var(--cn-text) !important;
  background-color: var(--cn-white) !important;
  overflow-x: hidden !important;
}

body.cn-body-light * {
  border-color: #f0ece9;
}

/* Evitar que la utilidad .collapse de Tailwind oculte los elementos colapsables de Bootstrap (Navbar y FAQ Accordion) */
body.cn-body-light .collapse {
  visibility: visible !important;
}

body.cn-body-light h1,
body.cn-body-light h2,
body.cn-body-light h3,
body.cn-body-light h4,
body.cn-body-light h5,
body.cn-body-light h6,
body.cn-body-light p,
body.cn-body-light li {
  color: inherit;
}

html { scroll-behavior: smooth; }

h1, h2, h3, .cn-brand-text { font-family: 'Fraunces', Georgia, serif; }

/* ===== NAVBAR ===== */
.cn-navbar {
  padding: 0.2rem 0;
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid transparent;
  z-index: 1050;
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
}
.cn-navbar.scrolled {
  padding: 0.1rem 0;
  background: rgba(255, 255, 255, 0.95);
  border-bottom: 1px solid rgba(76, 37, 33, 0.08);
  box-shadow: 0 8px 30px -18px rgba(76, 37, 33, 0.4);
}
.cn-brand {
  display: flex;
  align-items: center;
  gap: .6rem;
  text-decoration: none;
  margin-left: -20px !important; /* Forces the logo to align further to the left, bypassing container padding */
}
.cn-navbar-logo {
  height: 80px; /* Make it large and visible, but balanced so it is not too thick */
  width: auto;
  object-fit: contain;
}
.cn-footer-logo {
  height: 100px;
  width: auto;
  object-fit: contain;
}
.cn-brand-mark {
  width: 40px; height: 40px;
  display: grid; place-items: center;
  border-radius: 12px;
  background: var(--cn-brown-dark);
  color: #fff !important; font-size: 1.2rem;
}
.cn-brand-text { font-weight: 700; font-size: 1.4rem; color: var(--cn-brown-dark) !important; letter-spacing: -.5px; }
.cn-brand-accent { color: var(--cn-brown-soft) !important; }
.navbar-toggler { border: none; font-size: 1.6rem; color: var(--cn-brown-dark) !important; }
.navbar-toggler:focus { box-shadow: none; }

.cn-link {
  position: relative;
  font-weight: 600;
  color: var(--cn-text) !important;
  padding: .5rem 1rem !important;
  text-decoration: none;
}
.cn-link::after {
  content: "";
  position: absolute; left: 1rem; right: 1rem; bottom: .35rem;
  height: 2px; background: var(--cn-brown-soft);
  transform: scaleX(0); transform-origin: left;
  transition: transform .3s ease;
}
.cn-link:hover { color: var(--cn-brown-dark) !important; }
.cn-link:hover::after { transform: scaleX(1); }

/* ===== BOTONES ===== */
.cn-btn-primary {
  background: var(--cn-brown-dark) !important;
  color: #fff !important;
  font-weight: 600;
  border-radius: 50px;
  border: 1px solid var(--cn-brown-dark) !important;
  box-shadow: var(--cn-shadow-soft);
  text-decoration: none;
}
.cn-btn-primary:hover {
  background: var(--cn-brown-hover) !important;
  color: #fff !important;
  transform: translateY(-2px);
  box-shadow: 0 14px 30px -12px rgba(76, 37, 33, 0.55);
}
.cn-btn-ghost {
  background: transparent !important;
  color: var(--cn-brown-dark) !important;
  font-weight: 600;
  border-radius: 50px;
  border: 1px solid rgba(76, 37, 33, 0.25) !important;
  text-decoration: none;
}
.cn-btn-ghost:hover { background: rgba(193, 127, 89, 0.12) !important; color: var(--cn-brown-dark) !important; transform: translateY(-2px); }
.cn-cta:hover { letter-spacing: .3px; }

/* ===== HERO ===== */
.cn-hero {
  position: relative;
  padding: 11rem 0 6rem;
  background: radial-gradient(1200px 600px at 80% -10%, rgba(193, 127, 89, 0.18), transparent 60%), var(--cn-bg-light);
  overflow: hidden;
  text-align: left; /* Explicitly left-align on desktop */
}
.cn-hero-glow {
  position: absolute; top: -120px; right: -120px;
  width: 420px; height: 420px; border-radius: 50%;
  background: radial-gradient(circle, rgba(105, 52, 46, 0.25), transparent 70%);
  filter: blur(20px);
}
.cn-badge {
  display: inline-flex; align-items: center;
  background: rgba(193, 127, 89, 0.15) !important;
  color: var(--cn-brown-dark) !important;
  font-weight: 600; font-size: .85rem;
  padding: .45rem 1rem; border-radius: 50px;
  border: 1px solid rgba(193, 127, 89, 0.3) !important;
}
.cn-hero-title { font-size: clamp(2.3rem, 5vw, 3.6rem); font-weight: 700; line-height: 0.9 !important; letter-spacing: -1.5px; text-align: left !important; }
.cn-typed-prefix { color: var(--cn-text); text-align: left !important; }
.cn-accent-text {
  color: var(--cn-brown-dark) !important;
  display: inline;
  white-space: nowrap;
  text-align: left !important;
}
.typed-cursor {
  color: var(--cn-brown-soft) !important;
  font-size: 1em;
  display: inline;
}
.cn-hero-lead { font-size: 1.12rem; color: var(--cn-text-muted); max-width: 520px; margin-top: 1.2rem; line-height: 1.6; text-align: left; }
.cn-hero-lead strong { color: var(--cn-brown-dark); }

.cn-hero-meta { display: flex; align-items: center; gap: .9rem; }
.cn-avatars { display: flex; }
.cn-avatars span {
  width: 36px; height: 36px; border-radius: 50%; margin-left: -10px;
  border: 2px solid #fff;
  background: linear-gradient(135deg, var(--cn-brown-soft), var(--cn-brown-dark));
}
.cn-avatars span:first-child { margin-left: 0; }
.cn-meta-text { font-size: .9rem; color: var(--cn-text-muted); font-weight: 500; }

/* ===== MOCKUP ===== */
.cn-mockup { position: relative; transform-style: preserve-3d; perspective: 1000px; }
.cn-mockup-inner {
  background: #fff;
  border-radius: 24px;
  padding: 1.4rem;
  box-shadow: var(--cn-shadow);
  border: 1px solid rgba(76, 37, 33, 0.07);
  transform-style: preserve-3d;
}
.cn-mockup-head { display: flex; align-items: center; gap: .4rem; padding-bottom: 1rem; border-bottom: 1px solid #f0ece9; }
.dot { width: 11px; height: 11px; border-radius: 50%; display: inline-block; }
.dot-r { background: #e06c5e; } .dot-y { background: #e0b25e; } .dot-g { background: #7fb87a; }
.cn-mockup-title { margin-left: auto; font-size: .8rem; font-weight: 600; color: var(--cn-text-muted); }

.cn-timer { display: grid; place-items: center; padding: 1.6rem 0 1rem; transform: translateZ(40px); }
.cn-timer-ring { position: relative; width: 170px; height: 170px; }
.cn-timer-ring svg { transform: rotate(-90deg); width: 100%; height: 100%; }
.ring-bg { fill: none; stroke: #f0ece9; stroke-width: 8; }
.ring-fg {
  fill: none; stroke: var(--cn-brown-soft); stroke-width: 8; stroke-linecap: round;
  stroke-dasharray: 327; stroke-dashoffset: 80;
  animation: ringPulse 4s ease-in-out infinite alternate;
}
@keyframes ringPulse { from { stroke-dashoffset: 110; } to { stroke-dashoffset: 60; } }
.cn-timer-label { position: absolute; inset: 0; display: grid; place-content: center; text-align: center; }
.cn-timer-time { font-family: 'Fraunces', serif; font-size: 2.1rem; font-weight: 700; color: var(--cn-brown-dark) !important; }
.cn-timer-state { font-size: .8rem; color: var(--cn-brown-soft) !important; font-weight: 600; }

.cn-tasks { display: flex; flex-direction: column; gap: .6rem; transform: translateZ(20px); }
.cn-task {
  display: flex; align-items: center; gap: .7rem;
  background: var(--cn-bg-light);
  padding: .7rem .9rem; border-radius: 12px;
  font-size: .9rem; font-weight: 500;
  border: 1px solid #f0ece9;
  color: var(--cn-text) !important;
}
.cn-task i { font-size: 1.1rem; color: var(--cn-text-muted) !important; }
.cn-task.done { color: var(--cn-text-muted) !important; }
.cn-task.done i { color: #7fb87a !important; }
.cn-task.done span:nth-child(2) { text-decoration: line-through; }
.cn-task.active { border-color: var(--cn-brown-soft); background: rgba(193, 127, 89, 0.08); }
.cn-task.active i { color: var(--cn-brown-dark) !important; }
.cn-task span:nth-child(2) { flex: 1; }
.cn-pill { font-size: .7rem; font-weight: 700; padding: .2rem .6rem; border-radius: 50px; }
.cn-pill-soft { background: #ece6e2; color: var(--cn-text-muted) !important; }
.cn-pill-accent { background: var(--cn-brown-dark); color: #fff !important; }

.cn-float-card {
  position: absolute;
  background: #fff;
  padding: .6rem 1rem; border-radius: 14px;
  font-size: .85rem; font-weight: 600; color: var(--cn-brown-dark) !important;
  box-shadow: var(--cn-shadow-soft);
  border: 1px solid rgba(76, 37, 33, 0.07);
  display: flex; align-items: center; gap: .5rem;
}
.cn-float-card i { color: var(--cn-brown-soft) !important; }
.cn-float-1 { top: 10%; left: -8%; animation: floaty 5s ease-in-out infinite; }
.cn-float-2 { bottom: 12%; right: -6%; animation: floaty 6s ease-in-out infinite reverse; }
@keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }

/* ===== SECCIONES ===== */
.cn-section { padding: 6rem 0; }
.cn-section-head { max-width: 640px; margin-bottom: 1rem; }
.cn-eyebrow {
  display: inline-block; font-weight: 700; font-size: .8rem; letter-spacing: 2px; text-transform: uppercase;
  color: var(--cn-brown-soft) !important; margin-bottom: .6rem;
}
.cn-section-title { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 700; letter-spacing: -.5px; color: var(--cn-brown-dark) !important; }
.cn-section-sub { color: var(--cn-text-muted); font-size: 1.05rem; margin-top: .8rem; line-height: 1.6; }

/* ===== CARDS ===== */
.cn-card {
  background: #fff;
  border-radius: var(--cn-radius);
  padding: 2.2rem 1.8rem;
  border: 1px solid #f0ece9;
  box-shadow: var(--cn-shadow-soft);
  color: var(--cn-text) !important;
}
.cn-card-img { padding: 0; overflow: hidden; }
.cn-card-img .cn-card-icon { margin-left: 1.8rem; margin-top: -29px; position: relative; z-index: 2; }
.cn-card-img .cn-card-title { padding-inline: 1.8rem; }
.cn-card-img .cn-card-text { padding: 0 1.8rem 2rem; }
.cn-card-media { height: 190px; overflow: hidden; }
.cn-card-media img { width: 100%; height: 100%; object-fit: cover; transform: scale(1.02); }
.cn-card-img:hover .cn-card-media img { transform: scale(1.1); }
.cn-card:hover { transform: translateY(-8px); box-shadow: var(--cn-shadow); border-color: rgba(193, 127, 89, 0.4); }
.cn-card-icon {
  width: 58px; height: 58px; border-radius: 16px;
  display: grid; place-items: center;
  background: rgba(193, 127, 89, 0.16);
  color: var(--cn-brown-dark) !important; font-size: 1.6rem;
  margin-bottom: 1.3rem;
}
.cn-card:hover .cn-card-icon { background: var(--cn-brown-dark); color: #fff !important; transform: rotate(-6deg); }
.cn-card-title { font-size: 1.25rem; font-weight: 700; margin-bottom: .6rem; color: var(--cn-brown-dark) !important; }
.cn-card-text { color: var(--cn-text-muted); line-height: 1.6; margin: 0; }

/* ===== SHOWCASE / VIDEO ===== */
.cn-showcase { padding: 6rem 0; background: var(--cn-bg-light); }
.cn-check-list { list-style: none; padding: 0; margin: 1.6rem 0 2rem; }
.cn-check-list li { display: flex; align-items: center; gap: .7rem; font-weight: 500; color: var(--cn-text) !important; margin-bottom: .8rem; }
.cn-check-list i { color: var(--cn-brown-soft) !important; font-size: 1.2rem; }
.cn-video-frame {
  position: relative; border-radius: 24px; overflow: hidden;
  box-shadow: var(--cn-shadow); border: 1px solid rgba(76, 37, 33, 0.1);
}
.cn-video { display: block; width: 100%; height: 100%; object-fit: cover; aspect-ratio: 16 / 11; }
.cn-video-badge {
  position: absolute; top: 1rem; left: 1rem;
  background: rgba(255, 255, 255, 0.9); color: var(--cn-brown-dark) !important; backdrop-filter: blur(6px);
  font-size: .8rem; font-weight: 600; padding: .4rem .9rem; border-radius: 50px;
  display: flex; align-items: center; gap: .45rem;
  border: 1px solid rgba(76, 37, 33, 0.12);
  box-shadow: var(--cn-shadow-soft);
}
.cn-video-badge i { color: #e06c5e !important; animation: blink 1.6s ease-in-out infinite; }
@keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: .3; } }

/* ===== STATS (LIGERO) ===== */
.cn-stats {
  padding: 5.5rem 0;
  background: var(--cn-white);
  background-image: radial-gradient(800px 400px at 90% 0%, rgba(193, 127, 89, 0.08), transparent 60%);
  color: var(--cn-text);
  border-top: 1px solid #f0ece9;
  border-bottom: 1px solid #f0ece9;
}
/* ===== STEP CARDS ===== */
.cn-step-card {
  background: var(--cn-white);
  border-radius: var(--cn-radius);
  padding: 2.2rem 1.6rem;
  border: 1px solid #f0ece9;
  box-shadow: var(--cn-shadow-soft);
  position: relative;
  transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
}
.cn-step-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--cn-shadow);
  border-color: rgba(193, 127, 89, 0.4);
}
.cn-step-num-badge {
  position: absolute;
  top: 15px;
  right: 20px;
  font-family: 'Fraunces', serif;
  font-size: 2.2rem;
  font-weight: 700;
  color: rgba(193, 127, 89, 0.18);
  line-height: 1;
}
.cn-step-card:hover .cn-step-num-badge {
  color: rgba(193, 127, 89, 0.35);
}
.cn-step-icon {
  width: 58px;
  height: 58px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  background: rgba(193, 127, 89, 0.12);
  color: var(--cn-brown-dark);
  font-size: 1.6rem;
  margin: 0 auto 1.4rem;
  transition: background-color .3s ease, color .3s ease;
}
.cn-step-card:hover .cn-step-icon {
  background: var(--cn-brown-dark);
  color: #fff;
  transform: scale(1.05);
}
.cn-step-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--cn-brown-dark) !important;
  margin-bottom: .6rem;
  font-family: 'Plus Jakarta Sans', sans-serif;
}
.cn-step-desc {
  color: var(--cn-text-muted);
  font-size: .9rem;
  line-height: 1.5;
  margin: 0;
}

/* ===== FAQ / ACCORDION ===== */
.cn-accordion .accordion-item { border: 1px solid #f0ece9; border-radius: 14px !important; margin-bottom: .8rem; overflow: hidden; }
.cn-accordion .accordion-button {
  font-weight: 600; color: var(--cn-text); background: #fff; padding: 1.2rem 1.4rem;
}
.cn-accordion .accordion-button:not(.collapsed) { color: var(--cn-brown-dark) !important; background: rgba(193, 127, 89, 0.08); box-shadow: none; }
.cn-accordion .accordion-button:focus { box-shadow: none; border-color: var(--cn-brown-soft); }
.cn-accordion .accordion-button::after { filter: sepia(1) saturate(3) hue-rotate(-10deg); }
.cn-accordion .accordion-body { color: var(--cn-text-muted); line-height: 1.6; }

/* ===== FOOTER (LIGERO) ===== */
.cn-footer {
  background: var(--cn-bg-light);
  color: var(--cn-text-muted);
  padding: 4rem 0 2rem;
  border-top: 1px solid #f0ece9;
}
.cn-brand-footer .cn-brand-text { color: var(--cn-brown-dark) !important; }
.cn-footer-text { color: var(--cn-text-muted); max-width: 280px; line-height: 1.6; }
.cn-socials { display: flex; gap: .6rem; margin-top: 1.2rem; }
.cn-socials a {
  width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center;
  background: rgba(76, 37, 33, 0.06); color: var(--cn-brown-dark) !important; font-size: 1.1rem;
  text-decoration: none;
}
.cn-socials a:hover { background: var(--cn-brown-soft); color: #fff !important; transform: translateY(-3px); }
.cn-footer-head { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--cn-brown-dark) !important; font-weight: 700; font-size: 1rem; margin-bottom: 1.1rem; }
.cn-footer-links { list-style: none; padding: 0; margin: 0; }
.cn-footer-links li { margin-bottom: .6rem; }
.cn-footer-links a { color: var(--cn-text-muted); text-decoration: none; }
.cn-footer-links a:hover { color: var(--cn-brown-soft); padding-left: 4px; }
.cn-footer-divider { border-color: rgba(76, 37, 33, 0.08); margin: 2.5rem 0 1.5rem; }
.cn-footer-credits { text-align: center; color: var(--cn-text-muted); font-size: .9rem; margin: 0; }

/* ===== RESPONSIVE ===== */
@media (max-width: 991.98px) {
  .cn-navbar { background: rgba(255, 255, 255, 0.95); }
  .navbar-collapse { background: #fff; margin-top: 1rem; padding: 1rem; border-radius: 16px; box-shadow: var(--cn-shadow-soft); }
  .cn-hero { padding-top: 8rem; text-align: left !important; }
  .cn-hero-title { text-align: left !important; }
  .cn-hero-lead { text-align: left !important; }
  .cn-section-head { margin-inline: auto; text-align: center !important; }
  .cn-mockup { margin-top: 3rem; max-width: 420px; margin-inline: auto; }
  .cn-float-1 { left: 0; } .cn-float-2 { right: 0; }
}
</style>