<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const isDarkMode = ref(false);

const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.body.classList.remove('cn-body-light');
        document.body.classList.add('cn-body-dark');
        localStorage.setItem('cn-theme', 'dark');
    } else {
        document.body.classList.remove('cn-body-dark');
        document.body.classList.add('cn-body-light');
        localStorage.setItem('cn-theme', 'light');
    }
};

onMounted(async () => {
    isDarkMode.value = localStorage.getItem('cn-theme') === 'dark';
    if (isDarkMode.value) {
        document.body.classList.add('cn-body-dark');
    } else {
        document.body.classList.add('cn-body-light');
    }
    // Dynamically load AOS Stylesheet if not already present
    if (!document.querySelector('link[href*="aos.css"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css';
        document.head.appendChild(link);
    }

    // Dynamic Script Loader
    const loadScript = (src) => {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) { resolve(); return; }
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject();
            document.head.appendChild(script);
        });
    };

    try {
        await Promise.all([
            loadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'),
            loadScript('https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js')
        ]);

        // Initialize AOS
        if (window.AOS) {
            window.AOS.init({
                duration: 800,
                easing: 'ease-out-cubic',
                once: true,
                offset: 80
            });
            setTimeout(() => {
                if (window.AOS) {
                    window.AOS.refresh();
                }
            }, 100);
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

    // Close mobile nav on click
    const navCollapse = document.getElementById('cnNav');
    document.querySelectorAll('#cnNav .nav-link, #cnNav .btn').forEach(el => {
        el.addEventListener('click', () => {
            if (navCollapse && navCollapse.classList.contains('show') && window.bootstrap?.Collapse) {
                const bsCollapse = window.bootstrap.Collapse.getInstance(navCollapse) || new window.bootstrap.Collapse(navCollapse);
                bsCollapse.hide();
            }
        });
    });

    onUnmounted(() => {
        window.removeEventListener('scroll', onScroll);
    });
});

onUnmounted(() => {
    document.body.classList.remove('cn-body-light');
    document.body.classList.remove('cn-body-dark');
});
</script>

<template>
    <Head>
        <title>Cronos Notes — Características Principales</title>
        <meta name="description" content="¿Qué es Cronos Notes? Conoce nuestra misión, visión, alcances, pilares fundamentales y límites del sistema." />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,600;9..144,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    </Head>

    <div class="cn-caracteristicas-page" id="top">
        <!-- ======= NAVBAR ======= -->
        <nav class="navbar navbar-expand-lg fixed-top cn-navbar" id="cnNavbar">
            <div class="container">
                <Link class="navbar-brand cn-brand" href="/">
                    <img src="/img/logo-cronos.png" alt="Cronos Notes Logo" class="cn-navbar-logo" />
                </Link>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#cnNav" aria-controls="cnNav" aria-expanded="false" aria-label="Abrir menú">
                    <i class="bi bi-list"></i>
                </button>

                <div class="collapse navbar-collapse" id="cnNav">
                    <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-1">
                        <li class="nav-item"><Link class="nav-link cn-link" href="/">Inicio</Link></li>
                        <li class="nav-item"><Link :href="route('uso') + '#top'" class="nav-link cn-link">Modo de Uso</Link></li>
                        <li class="nav-item"><Link :href="route('caracteristicas') + '#top'" class="nav-link cn-link active-link">Características</Link></li>
                        <li class="nav-item"><Link :href="route('quienes-somos') + '#top'" class="nav-link cn-link">Quiénes Somos</Link></li>
                    </ul>
                    <div class="d-flex align-items-center gap-2 me-lg-3 mb-2 mb-lg-0 justify-content-center">
                        <button @click="toggleTheme" class="cn-theme-toggle-btn" aria-label="Cambiar tema">
                            <i v-if="isDarkMode" class="bi bi-sun-fill text-warning"></i>
                            <i v-else class="bi bi-moon-fill"></i>
                        </button>
                    </div>
                    <div v-if="$page.props.auth && $page.props.auth.user" class="d-flex gap-2 justify-content-center">
                        <Link :href="route('dashboard')" class="btn cn-btn-primary px-4">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </Link>
                    </div>
                    <div v-else class="d-flex gap-2 align-items-center justify-content-center">
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

        <!-- ======= CONTENIDO PRINCIPAL ======= -->
        <div class="features-container position-relative overflow-hidden py-5 px-3 px-md-5">
            <div class="smoke-layer smoke-1"></div>
            <div class="smoke-layer smoke-2"></div>
            <div class="smoke-layer smoke-3"></div>

            <div class="content-wrapper position-relative mx-auto max-width-layout">
                
                <!-- SECCIÓN INTRODUCTORIA -->
                <section class="row g-5 align-items-center mb-5 pb-5 section-padding-top">
                    <div class="col-12 col-lg-7">
                        <span class="badge badge-premium px-3 py-2 text-uppercase mb-3">Características del Sistema</span>
                        <h1 class="display-4 fw-bold text-dark mb-3">¿Qué es Cronos Notes?</h1>
                        <p class="fs-5 text-secondary lead-custom">
                            Es una plataforma web integral de alto rendimiento diseñada para mitigar la fragmentación de herramientas en el ecosistema de estudio. Al fusionar la gestión de tareas con el control del tiempo, Cronos Notes acompaña activamente tu proceso cognitivo, evitando la sobrecarga, las distracciones constantes y el agotamiento mental.
                        </p>
                        <p class="text-muted small-text mb-4">
                            <strong>Objetivo General:</strong> Desarrollar un sistema informático de gestión de actividades que implemente de forma flexible la técnica Pomodoro, mejorando la organización personal y la productividad de los usuarios mediante una interfaz unificada y amigable.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="#mision-vision" class="btn btn-premium rounded-pill px-4 py-2.5 fw-bold text-decoration-none shadow-sm">
                                Conocer Misión y Visión <i class="bi bi-arrow-down-short ms-1"></i>
                            </a>
                            <a href="#pilares" class="btn btn-outline-premium rounded-pill px-4 py-2.5 fw-bold text-decoration-none">
                                Ver Pilares Fundamentales
                            </a>
                        </div>
                    </div>
                    <div class="col-12 col-lg-5 text-center">
                        <div class="hero-interactive-graphic mx-auto position-relative rounded-circle d-flex align-items-center justify-content-center shadow-lg border">
                            <div class="inner-circle border rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-cpu text-marron fs-1 animate-float"></i>
                            </div>
                            <div class="orbiting-dot dot-1"><i class="bi bi-clock-history"></i></div>
                            <div class="orbiting-dot dot-2"><i class="bi bi-check2-square"></i></div>
                            <div class="orbiting-dot dot-3"><i class="bi bi-journal-bookmark-fill"></i></div>
                        </div>
                    </div>
                </section>

                <!-- MISIÓN Y VISIÓN -->
                <section id="mision-vision" class="row g-4 mb-5 pb-5 align-items-stretch">
                    <div class="col-12 col-md-6">
                        <div class="card h-100 glass-card p-4 p-md-5 rounded-4 border-0">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="icon-box-accent rounded-3 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-bullseye fs-3 text-white"></i>
                                </div>
                                <h2 class="h3 fw-bold m-0 text-dark">Nuestra Misión</h2>
                            </div>
                            <p class="text-secondary card-paragraph m-0">
                                Optimizar la gestión del tiempo y la productividad de estudiantes y profesionales mediante una solución tecnológica integral y accesible. Combatimos la procrastinación implementando metodologías de trabajo ágiles, control del tiempo con pausas programadas y analítica avanzada, promoviendo una rutina saludable que resguarde el bienestar psicológico y la salud mental del usuario.
                            </p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="card h-100 glass-card p-4 p-md-5 rounded-4 border-0">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="icon-box-accent rounded-3 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-eye fs-3 text-white"></i>
                                </div>
                                <h2 class="h3 fw-bold m-0 text-dark">Nuestra Visión</h2>
                            </div>
                            <p class="text-secondary card-paragraph m-0">
                                Evolucionar hacia una plataforma de alto rendimiento de vanguardia que sea pionera en el mercado de la productividad digital. Nos proyectamos como el sistema de organización inteligente líder en el ámbito académico y laboral, reconocido por equilibrar de forma perfecta herramientas avanzadas de gestión de proyectos con entornos inmersivos de estudio cognitivo.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ALCANCES Y PILARES -->
                <section id="pilares" class="mb-5 pb-5">
                    <div class="text-center mb-5">
                        <h2 class="display-6 fw-bold text-dark">Los Alcances y Pilares de Cronos</h2>
                        <p class="text-secondary max-width-sub mx-auto">
                            Diseñados estratégicamente en base a las demandas de los usuarios encuestados para equilibrar la gestión y la concentración profunda.
                        </p>
                    </div>

                    <div class="row g-4">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card pilar-card h-100 p-4 border rounded-4 shadow-sm">
                                <i class="bi bi-folder2-open fs-2 text-marron mb-3"></i>
                                <h3 class="h5 fw-bold text-dark">Organización por Perfiles</h3>
                                <p class="text-secondary small-text m-0">
                                    Permite fragmentar tus responsabilidades en diferentes ámbitos ("Estudio", "Trabajo"). Restringido a un límite saludable de 5 perfiles por usuario para evitar la saturación visual.
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card pilar-card h-100 p-4 border rounded-4 shadow-sm">
                                <i class="bi bi-calendar3-event fs-2 text-marron mb-3"></i>
                                <h3 class="h5 fw-bold text-dark">Calendario e Interfaz Visual</h3>
                                <p class="text-secondary small-text m-0">
                                    Clasificación ágil de pendientes según su prioridad (Alta, Media, Baja) vinculada a un calendario mensual interactivo para monitorear fechas límite de entrega de forma clara.
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card pilar-card h-100 p-4 border rounded-4 shadow-sm">
                                <i class="bi bi-hourglass-split fs-2 text-marron mb-3"></i>
                                <h3 class="h5 fw-bold text-dark">Técnica Pomodoro e Inicio Rápido</h3>
                                <p class="text-secondary small-text m-0">
                                    Temporizador ajustable estructurado en intervalos de concentración con pausas programadas. Cuenta con "Inicio Rápido" a un clic y un Escritorio Visual Minimalista Zen.
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card pilar-card h-100 p-4 border rounded-4 shadow-sm">
                                <i class="bi bi-lightning-charge fs-2 text-marron mb-3"></i>
                                <h3 class="h5 fw-bold text-dark">Inteligencia Artificial Activa</h3>
                                <p class="text-secondary small-text m-0">
                                    Integración de un motor de IA que analiza de forma predictiva tus cargas de trabajo y te ayuda a priorizar tareas críticas basándose estrictamente en las fechas límite de entrega.
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card pilar-card h-100 p-4 border rounded-4 shadow-sm">
                                <i class="bi bi-journal-text fs-2 text-marron mb-3"></i>
                                <h3 class="h5 fw-bold text-dark">Módulo de Toma de Apuntes</h3>
                                <p class="text-secondary small-text m-0">
                                    Permite crear notas asociadas a tus cátedras con soporte estructurado para el Método Cornell (Ideas, Notas, Resumen), esquemas jerárquicos y grabación de notas de voz de clases.
                                </p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card pilar-card h-100 p-4 border rounded-4 shadow-sm">
                                <i class="bi bi-sliders fs-2 text-marron mb-3"></i>
                                <h3 class="h5 fw-bold text-dark">Mezclador de Sonido Ambiente</h3>
                                <p class="text-secondary small-text m-0">
                                    Interfaz inmersiva con soporte multimedia de alto rendimiento de Howler.js para combinar canales de audio personalizados (Lluvia, Ruido Blanco y pistas locales).
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- LÍMITES DEL SISTEMA -->
                <section class="p-4 p-md-5 rounded-4 limits-section border border-dashed mb-5">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-3">
                            <div class="d-flex align-items-center gap-2 mb-2 text-marron font-monospace uppercase small-label">
                                <i class="bi bi-exclamation-triangle-fill"></i> Iteración Actual
                            </div>
                            <h4 class="fw-bold text-dark m-0">Límites del Sistema</h4>
                        </div>
                        <div class="col-12 col-lg-9 mt-3 mt-lg-0">
                            <p class="text-secondary small-text m-0 line-height-relaxed">
                                Para garantizar la estabilidad y la calidad de software bajo la metodología ágil <strong>Extreme Programming (XP)</strong>, quedan fuera del alcance de esta iteración: los ecosistemas competitivos o rankings sociales, el desarrollo de salas de estudio virtuales compartidas en tiempo real, las recompensas estéticas de medallas y las pasarelas de cobro internas. Estos requerimientos quedan relegados a futuras actualizaciones estables de la documentación de arquitectura.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- BOTÓN DE RETORNO / CTA -->
                <div class="text-center pt-4 mb-5 d-flex flex-column align-items-center gap-3">
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <Link v-if="$page.props.auth && $page.props.auth.user" :href="route('dashboard')" class="btn btn-premium px-4 py-2.5 rounded-3 fw-bold text-decoration-none shadow-sm">
                            <i class="bi bi-speedometer2 me-2"></i> Ir al Escritorio Principal
                        </Link>
                        <Link v-else :href="route('register')" class="btn btn-premium px-4 py-2.5 rounded-3 fw-bold text-decoration-none shadow-sm">
                            <i class="bi bi-rocket-takeoff me-2"></i> Comenzar Gratis
                        </Link>
                        <a href="#top" class="btn btn-outline-premium px-4 py-2.5 rounded-3 fw-bold text-decoration-none shadow-sm">
                            <i class="bi bi-arrow-up me-2"></i> Volver al inicio
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- ======= FOOTER ======= -->
        <footer class="cn-footer">
            <div class="container">
                <p>© 2026 <strong style="color:var(--sienna)">Cronos Notes</strong> · Ingeniería de Software II &amp; Programación Web · Todos los derechos reservados.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
body.cn-body-light .cn-caracteristicas-page {
  --brown:   #4c2521;
  --roast:   #69342e;
  --sienna:  #c17f59;
  --sand:    #e8d5c0;
  --cream:   #faf5ef;
  --light:   #f8f9fa;
  --white:   #ffffff;
  --ink:     #1c0f0e;
  --muted:   #7a5e58;
  --sh:      0 12px 40px -14px rgba(76,37,33,.28);
  --sh2:     0 4px 18px -6px rgba(76,37,33,.18);
  
  /* theme specific */
  --cn-navbar-bg: rgba(255, 255, 255, 0.85);
  --cn-navbar-scrolled-bg: rgba(255, 255, 255, 0.95);
  --cn-border-soft: rgba(76, 37, 33, 0.08);
  --cn-btn-ghost-border: rgba(76, 37, 33, 0.25);
  --cn-btn-ghost-hover-bg: rgba(193, 127, 89, 0.12);
  --cn-border-solid: #f0ece9;
  --cn-card-bg: #ffffff;
  --cn-card-icon-bg: rgba(193, 127, 89, 0.14);
  --cn-glass-card-bg: rgba(255, 255, 255, 0.82);
  --cn-glass-card-border: rgba(255, 255, 255, 0.6);
  --cn-smoke-color: rgba(105, 52, 46, 0.035);

  /* Botones principales y ghost */
  --cn-btn-primary-bg: var(--brown);
  --cn-btn-primary-bg-hover: var(--roast);
  --cn-btn-primary-text: #ffffff;
  --cn-btn-primary-text-hover: #ffffff;
  --cn-btn-ghost-text: var(--roast);
  --cn-btn-ghost-text-hover: var(--roast);
}

body.cn-body-dark .cn-caracteristicas-page {
  --brown:   #a55e57;      /* Bordo terracota para hover de botones */
  --roast:   #e38e76;      /* Terracota para acentos e iconos */
  --sienna:  #f4be95;      /* Durazno para links y destaques secundarios */
  --sand:    #7b413f;      /* Bordes secundarios */
  --cream:   #4c2521;      /* Fondo de tarjetas */
  --light:   #4c2521;      /* Fondo de la seccion */
  --white:   #612c2d;      /* FONDO PRINCIPAL: Bordo Oscuro */
  --ink:     #ffffff;      /* TEXTOS PRINCIPALES Y TITULOS: Blanco Puro (Alto contraste!) */
  --muted:   #fcd5b8;      /* TEXTOS SECUNDARIOS: Crema durazno suave (Muy legible!) */
  --sh:      0 12px 40px -14px rgba(0,0,0,.6);
  --sh2:     0 4px 18px -6px rgba(0,0,0,.4);
  
  /* theme specific */
  --cn-navbar-bg: rgba(76, 37, 33, 0.85);
  --cn-navbar-scrolled-bg: rgba(97, 44, 45, 0.95);
  --cn-border-soft: rgba(244, 190, 149, 0.15);
  --cn-btn-ghost-border: rgba(244, 190, 149, 0.35);
  --cn-btn-ghost-hover-bg: rgba(244, 190, 149, 0.15);
  --cn-border-solid: #7b413f;
  --cn-card-bg: #4c2521;
  --cn-card-icon-bg: rgba(244, 190, 149, 0.15);
  --cn-glass-card-bg: rgba(76, 37, 33, 0.82);
  --cn-glass-card-border: rgba(244, 190, 149, 0.25);
  --cn-smoke-color: rgba(244, 190, 149, 0.04);

  /* Botones principales y ghost contrastados */
  --cn-btn-primary-bg: var(--sienna);
  --cn-btn-primary-bg-hover: var(--muted);
  --cn-btn-primary-text: var(--cream);
  --cn-btn-primary-text-hover: var(--cream);
  --cn-btn-ghost-text: var(--sienna);
  --cn-btn-ghost-text-hover: var(--sienna);
}

.cn-caracteristicas-page {
  --r:       14px;
  --r2:      18px;
  --tr:      all 0.3s ease;
  
  font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
  background: var(--white);
  color: var(--ink);
  min-height: 100vh;
}

/* OVERRIDE BOOTSTRAP TEXT COLOR UTILITIES TO ENSURE WHITE TEXT ON DARK BURGUNDY */
.cn-caracteristicas-page .text-dark {
  color: var(--ink) !important;
}
.cn-caracteristicas-page .text-secondary,
.cn-caracteristicas-page .text-muted {
  color: var(--muted) !important;
}

.cn-caracteristicas-page h1,
.cn-caracteristicas-page h2,
.cn-caracteristicas-page h3,
.cn-caracteristicas-page h4 {
  font-family: 'Fraunces', Georgia, serif;
}

/* Evitar que Tailwind collapse oculte la barra de navegación en computadoras */
.cn-caracteristicas-page .collapse {
  visibility: visible !important;
}

/* ===== GLOBAL NAVBAR DE WELCOME ===== */
.cn-caracteristicas-page .cn-navbar {
  padding: 0.2rem 0;
  background: var(--cn-navbar-bg);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid transparent;
  z-index: 1050;
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
}
.cn-caracteristicas-page .cn-navbar.scrolled {
  padding: 0.1rem 0;
  background: var(--cn-navbar-scrolled-bg);
  border-bottom: 1px solid var(--cn-border-soft);
  box-shadow: 0 8px 30px -18px rgba(76, 37, 33, 0.4);
}
.cn-caracteristicas-page .cn-brand {
  display: flex;
  align-items: center;
  gap: .6rem;
  text-decoration: none;
  margin-left: -20px !important;
}
.cn-caracteristicas-page .cn-navbar-logo {
  height: 80px;
  width: auto;
  object-fit: contain;
}
.cn-caracteristicas-page .cn-link {
  position: relative;
  font-weight: 600;
  color: var(--ink) !important;
  padding: .5rem 1rem !important;
  text-decoration: none;
}
.cn-caracteristicas-page .cn-link::after {
  content: "";
  position: absolute; left: 1rem; right: 1rem; bottom: .35rem;
  height: 2px; background: var(--sienna);
  transform: scaleX(0); transform-origin: left;
  transition: transform .3s ease;
}
.cn-caracteristicas-page .cn-link:hover { color: var(--brown) !important; }
.cn-caracteristicas-page .cn-link:hover::after,
.cn-caracteristicas-page .cn-link.active-link::after { transform: scaleX(1); }
.cn-caracteristicas-page .cn-link.active-link { color: var(--brown) !important; }

.cn-caracteristicas-page .cn-btn-primary {
  background: var(--cn-btn-primary-bg, var(--brown)) !important;
  color: var(--cn-btn-primary-text, #fff) !important;
  font-weight: 600;
  border-radius: 50px;
  border: 1px solid var(--cn-btn-primary-bg, var(--brown)) !important;
  box-shadow: var(--sh2);
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.cn-caracteristicas-page .cn-btn-primary:hover {
  background: var(--cn-btn-primary-bg-hover, var(--roast)) !important;
  color: var(--cn-btn-primary-text-hover, #fff) !important;
  transform: translateY(-2px);
  box-shadow: var(--sh);
}
.cn-caracteristicas-page .cn-btn-ghost {
  background: transparent !important;
  color: var(--cn-btn-ghost-text, var(--roast)) !important;
  font-weight: 600;
  border-radius: 50px;
  border: 1px solid var(--cn-btn-ghost-border) !important;
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.cn-caracteristicas-page .cn-btn-ghost:hover {
  background: var(--cn-btn-ghost-hover-bg) !important;
  color: var(--cn-btn-ghost-text-hover, var(--roast)) !important;
  transform: translateY(-2px);
}
.cn-caracteristicas-page .navbar-toggler { border: none; font-size: 1.6rem; color: var(--brown) !important; }
.cn-caracteristicas-page .navbar-toggler:focus { box-shadow: none; }

.cn-caracteristicas-page section {
  scroll-margin-top: 110px;
}

.features-container {
    background-color: var(--light);
    min-height: 100vh;
    padding-top: 9rem !important;
}

.max-width-layout { max-width: 1140px; }
.max-width-sub { max-width: 650px; }
.lead-custom { font-size: 1.15rem; line-height: 1.6; font-weight: 400; }
.small-text { font-size: 0.92rem; line-height: 1.6; }
.card-paragraph { font-size: 0.96rem; line-height: 1.65; }
.line-height-relaxed { line-height: 1.7; }
.border-dashed { border-style: dashed !important; }

/* 🌟 EFECTO INMERSIVO: CAPAS DE HUMO/VIENTO ANIMADAS POR CSS */
.smoke-layer {
    position: absolute;
    top: 0; left: 0;
    width: 200%; height: 100%;
    background: radial-gradient(circle, var(--cn-smoke-color) 0%, rgba(248, 249, 250, 0) 65%);
    transform: translateX(0);
    pointer-events: none;
}
.smoke-1 { animation: flowSmoke 22s linear infinite; }
.smoke-2 { animation: flowSmoke 16s linear infinite reverse; opacity: 0.8; top: 15%; }
.smoke-3 { animation: flowSmoke 30s linear infinite; opacity: 0.6; top: -10%; }

@keyframes flowSmoke {
    0% { transform: translateX(0) translateY(0) scale(1); }
    50% { transform: translateX(-25%) translateY(4vh) scale(1.05); }
    100% { transform: translateX(-50%) translateY(0) scale(1); }
}

/* CARDS CON GLASSMORPHISM (FLOATING EFFECT OVER SMOKE) */
.glass-card {
    background: var(--cn-glass-card-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--cn-glass-card-border) !important;
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.03);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.glass-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.06);
}

/* BADGES Y BOTONES CON PALETA DE COLORES OFICIAL */
.badge-premium {
    background-color: var(--cn-card-icon-bg);
    color: var(--roast);
    font-size: 0.75rem;
    letter-spacing: 0.06em;
    font-weight: 700;
}

.btn-premium {
    background-color: var(--roast);
    color: #ffffff !important;
    border: none;
    font-size: 0.9rem;
    transition: background-color 0.2s, transform 0.2s;
}
.btn-premium:hover {
    background-color: var(--brown);
    color: #ffffff !important;
    transform: translateY(-1px);
}

.btn-outline-premium {
    border: 2px solid var(--roast);
    color: var(--roast);
    background: transparent;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.btn-outline-premium:hover {
    background-color: var(--roast);
    color: #ffffff;
}

/* ELEMENTOS DE DISEÑO ACCENTUADOS */
.icon-box-accent {
    width: 48px; height: 48px;
    background-color: var(--roast);
}

.text-marron { color: var(--roast) !important; }

/* PILARES CARDS GRID */
.pilar-card {
    background-color: var(--cn-card-bg);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    border-color: var(--cn-border-solid) !important;
}
.pilar-card:hover {
    transform: translateY(-6px);
    border-color: rgba(244, 190, 149, 0.35) !important;
    box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important;
}

/* SECCIÓN LÍMITES */
.limits-section {
    background-color: var(--cn-glass-card-bg);
    border-color: var(--cn-border-solid) !important;
}
.small-label { font-size: 0.68rem; letter-spacing: 0.06em; font-weight: 700; }

/* GRÁFICO INTERACTIVO ABSTRACTO HERO (LADO DERECHO) */
.hero-interactive-graphic {
    width: 260px; height: 260px;
    background-color: var(--cream);
    border-color: var(--cn-border-solid) !important;
}
.inner-circle {
    width: 130px; height: 130px;
    background-color: var(--light);
    border-color: var(--cn-border-soft) !important;
}

/* PUNTOS EN ÓRBITA ABSTRACTA */
.orbiting-dot {
    position: absolute;
    width: 40px; height: 40px;
    background: var(--cn-card-bg);
    border: 1px solid var(--cn-border-solid);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--sienna);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
.dot-1 { top: 10px; left: 10px; animation: orbitCW 20s linear infinite; }
.dot-2 { bottom: 20px; right: 10px; animation: orbitCW 15s linear infinite; }
.dot-3 { bottom: 10px; left: 30px; animation: orbitCW 25s linear infinite; }

/* ANIMACIONES EXCLUSIVAS DEL HERO */
.animate-float { animation: floatIcon 4s ease-in-out infinite; }
@keyframes floatIcon {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

/* ===== Theme Toggle Style & Dark mode tweaks ===== */
.cn-theme-toggle-btn {
  background: transparent;
  border: none;
  font-size: 1.35rem;
  padding: 6px;
  color: var(--roast);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.2s ease, color 0.2s ease;
  border-radius: 50%;
  width: 42px;
  height: 42px;
}
.cn-theme-toggle-btn:hover {
  transform: scale(1.1);
  background: var(--cn-btn-ghost-hover-bg);
}

body.cn-body-dark .cn-navbar-logo,
body.cn-body-dark .cn-footer-logo {
  filter: brightness(0) invert(0.95) !important;
  opacity: 0.95;
}
</style>

<style>
html {
  scroll-behavior: smooth;
}
</style>