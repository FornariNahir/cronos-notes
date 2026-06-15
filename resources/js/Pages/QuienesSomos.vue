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

// Tabs switching state
const activeTab = ref('hardware');
const setActiveTab = (tab) => {
    activeTab.value = tab;
};

onMounted(async () => {
    isDarkMode.value = localStorage.getItem('cn-theme') === 'dark';
    if (isDarkMode.value) {
        document.body.classList.add('cn-body-dark');
    } else {
        document.body.classList.add('cn-body-light');
    }

    // Dynamic Bootstrap bundle loader if not already present
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
        await loadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');
    } catch (err) {
        console.error('Error loading Bootstrap:', err);
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

    // ---- Intersection Observer Reveal ----
    const revealEls = document.querySelectorAll("[data-reveal]");
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const delay = Number(entry.target.dataset.delay || 0);
                    setTimeout(() => entry.target.classList.add("is-visible"), delay);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.15, rootMargin: "0px 0px -60px 0px" }
    );
    revealEls.forEach((el) => observer.observe(el));

    // ---- Efecto Parallax en el humo ----
    const smokes = document.querySelectorAll(".smoke");
    let ticking = false;
    const onScrollParallax = () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                const y = window.scrollY;
                smokes.forEach((s, i) => {
                    const factor = (i + 1) * 0.04;
                    s.style.transform = `translateY(${y * factor}px)`;
                });
                ticking = false;
            });
            ticking = true;
        }
    };
    window.addEventListener('scroll', onScrollParallax, { passive: true });

    onUnmounted(() => {
        window.removeEventListener('scroll', onScroll);
        window.removeEventListener('scroll', onScrollParallax);
        observer.disconnect();
        document.body.classList.remove('cn-body-light');
        document.body.classList.remove('cn-body-dark');
    });
});
</script>

<template>
    <Head>
        <title>Cronos Notes — Quiénes Somos y Recursos</title>
        <meta name="description" content="El equipo detrás de Cronos Notes: proyecto final." />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,600;9..144,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    </Head>

    <div class="cn-quienes-page" id="top">
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
                        <li class="nav-item"><Link :href="route('caracteristicas') + '#top'" class="nav-link cn-link">Características</Link></li>
                        <li class="nav-item"><Link :href="route('quienes-somos') + '#top'" class="nav-link cn-link active-link">Quiénes Somos</Link></li>
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

        <!-- Fondo de humo zen animado -->
        <div class="smoke-bg" aria-hidden="true">
            <span class="smoke smoke-1"></span>
            <span class="smoke smoke-2"></span>
            <span class="smoke smoke-3"></span>
        </div>

        <main class="page">
            <!-- ===================== CABECERA INSTITUCIONAL ===================== -->
            <header class="hero">
                <div class="container">
                    <span class="badge-top reveal" data-reveal>
                        <i class="bi bi-mortarboard-fill"></i> Proyecto Final 
                    </span>
                    <h1 class="hero-title reveal" data-reveal data-delay="100">
                        El Equipo Detrás de <span class="accent">Cronos Notes</span>
                    </h1>
                    <p class="hero-text reveal" data-reveal data-delay="200">
                        Somos estudiantes de la carrera de <strong>Licenciatura en Sistemas de Información</strong> de la
                        Universidad de la Cuenca del Plata — Formosa, cursando nuestro cuarto año académico.
                        Cronos Notes nace como nuestro proyecto final: una plataforma de estudio que
                        combina técnica Pomodoro, mezclador de ambientes sonoros y toma de apuntes inteligente.
                    </p>
                </div>
            </header>

            <!-- ===================== SECCIÓN 1: EQUIPO HUMANO ===================== -->
            <section class="qs-section">
                <div class="container">
                    <div class="section-head reveal" data-reveal>
                        <span class="kicker"><i class="bi bi-people-fill"></i> Recursos Humanos</span>
                        <h2 class="section-title">El Equipo Humano</h2>
                        <p class="section-sub">Roles y responsabilidades.</p>
                    </div>

                    <div class="team-grid">
                        <!-- Integrante 1 -->
                        <article class="team-card reveal" data-reveal data-delay="100">
                            <div class="avatar-wrap">
                                <img class="avatar" src="/imagenes/agus.webp"
                                     alt="Foto de Clara Agustina Romea Acevedo"
                                     onerror="this.src='https://placehold.co/300x300/69342e/ffffff?text=CR'" />
                            </div>
                            <h3 class="member-name">Romea Acevedo, Clara Agustina</h3>
                            <span class="member-role">Gestora del Proyecto · Front-End</span>
                            <ul class="task-list">
                                <li><i class="bi bi-clipboard-check"></i> Lider / Gestora del Proyecto</li>
                                <li><i class="bi bi-code-slash"></i> Desarrolladora Front-End</li>
                                <li><i class="bi bi-diagram-3"></i> Diagrama de Casos de Uso</li>
                                <li><i class="bi bi-window"></i> Diseño de Páginas Web</li>
                            </ul>
                            <div class="socials">
                                <a href="https://github.com/agusromea" aria-label="GitHub de Clara"><i class="bi bi-github"></i></a>
                                <a href="mailto:agusromea05@gmail.com" aria-label="Email de Clara"><i class="bi bi-envelope-fill"></i></a>
                            </div>
                        </article>

                        <!-- Integrante 2 -->
                        <article class="team-card reveal" data-reveal data-delay="200">
                            <div class="avatar-wrap">
                                <img class="avatar" src="/imagenes/jose.webp"
                                     alt="Foto de José Andrés Ayala"
                                     onerror="this.src='https://placehold.co/300x300/8a4a3f/ffffff?text=JA'" />
                            </div>
                            <h3 class="member-name">Ayala, José Andrés</h3>
                            <span class="member-role">Desarrollador Front-End</span>
                            <ul class="task-list">
                                <li><i class="bi bi-code-slash"></i> Desarrollador Front-End</li>
                                <li><i class="bi bi-diagram-3"></i> Diagrama de Casos de Uso</li>
                                <li><i class="bi bi-window"></i> Diseño de Páginas Web</li>
                            </ul>
                            <div class="socials">
                                <a href="https://github.com/joseayalaa18" aria-label="GitHub de José"><i class="bi bi-github"></i></a>
                               
                                <a href="mailto:jose18ayala05@gmail.com" aria-label="Email de José"><i class="bi bi-envelope-fill"></i></a>
                            </div>
                        </article>

                        <!-- Integrante 3 -->
                        <article class="team-card reveal" data-reveal data-delay="300">
                            <div class="avatar-wrap">
                                <img class="avatar" src="/imagenes/ricardo.webp"
                                     alt="Foto de Ricardo Agustín Dellagnolo"
                                     onerror="this.src='https://placehold.co/300x300/c17f59/ffffff?text=RD'" />
                            </div>
                            <h3 class="member-name">Dellagnolo, Ricardo Agustín</h3>
                            <span class="member-role">Desarrollador Back-End</span>
                            <ul class="task-list">
                                <li><i class="bi bi-server"></i> Desarrollador Back-End</li>
                                <li><i class="bi bi-clipboard-data"></i> Análisis del Cuestionario</li>
                                <li><i class="bi bi-database"></i> Diseño de la Base de Datos</li>
                                <li><i class="bi bi-diagram-2"></i> Diagrama de Clases</li>
                                <li><i class="bi bi-diagram-3"></i> Diagrama de Casos de Uso</li>
                            </ul>
                            <div class="socials">
                                <a href="https://github.com/dellagnoloRA" aria-label="GitHub de Ricardo"><i class="bi bi-github"></i></a>
                            
                                <a href="mailto:dellagnoloraucp@gmail.com" aria-label="Email de Ricardo"><i class="bi bi-envelope-fill"></i></a>
                            </div>
                        </article>

                        <!-- Integrante 4 -->
                        <article class="team-card reveal" data-reveal data-delay="400">
                            <div class="avatar-wrap">
                                <img class="avatar" src="/imagenes/nahir.webp"
                                     alt="Foto de Nahir Agustín Fornari"
                                     onerror="this.src='https://placehold.co/300x300/4c2521/ffffff?text=NF'" />
                            </div>
                            <h3 class="member-name">Fornari, Nahir Agustín</h3>
                            <span class="member-role">Desarrollador Back-End</span>
                            <ul class="task-list">
                                <li><i class="bi bi-server"></i> Desarrollador Back-End</li>
                                <li><i class="bi bi-database"></i> Diseño de la Base de Datos</li>
                                <li><i class="bi bi-diagram-2"></i> Diagrama de Clases</li>
                                <li><i class="bi bi-diagram-3"></i> Diagrama de Casos de Uso</li>
                            </ul>
                            <div class="socials">
                                <a href="https://github.com/FornariNahir" aria-label="GitHub de Nahir"><i class="bi bi-github"></i></a>
                                
                                <a href="mailto:nahir21fornari@gmail.com" aria-label="Email de Nahir"><i class="bi bi-envelope-fill"></i></a>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <!-- ===================== SECCIÓN 2: RECURSOS TÉCNICOS ===================== -->
            <section class="qs-section qs-section-alt">
                <div class="container">
                    <div class="section-head reveal" data-reveal>
                        <span class="kicker"><i class="bi bi-tools"></i> Recursos del Equipo</span>
                        <h2 class="section-title">Recursos Técnicos de Desarrollo</h2>
                        <p class="section-sub">El  hardware y software que se usó para el sistema.</p>
                    </div>

                    <div class="tabs reveal" data-reveal data-delay="100">
                        <button class="tab-btn" :class="{ active: activeTab === 'hardware' }" @click="setActiveTab('hardware')">
                            <i class="bi bi-pc-display"></i> Hardware
                        </button>
                        <button class="tab-btn" :class="{ active: activeTab === 'software' }" @click="setActiveTab('software')">
                            <i class="bi bi-window-stack"></i> Software
                        </button>
                        <button class="tab-btn" :class="{ active: activeTab === 'frameworks' }" @click="setActiveTab('frameworks')">
                            <i class="bi bi-stack"></i> Frameworks y Lenguajes
                        </button>
                    </div>

                    <div class="tab-panels reveal" data-reveal data-delay="200">
                        <div class="tab-panel" :class="{ active: activeTab === 'hardware' }">
                            <div class="cards-row">
                                <div class="info-card">
                                    <i class="bi bi-pc-display-horizontal info-icon"></i>
                                    <h4>4 Equipos de Cómputo</h4>
                                    <p>Equipos de capacidad media, suficientes para las herramientas de desarrollo del equipo.</p>
                                </div>
                                <div class="info-card">
                                    <i class="bi bi-wifi info-icon"></i>
                                    <h4>Conexión a Internet</h4>
                                    <p>Red estable para investigación, comunicación y testing continuo del desarrollo.</p>
                                </div>
                                <div class="info-card">
                                    <i class="bi bi-hdd-network info-icon"></i>
                                    <h4>Servidor de Hosting</h4>
                                    <p>Servicio de alojamiento para subir y mantener el proyecto web en línea.</p>
                                </div>
                            </div>
                        </div>

                        <div class="tab-panel" :class="{ active: activeTab === 'software' }">
                            <div class="cards-row">
                                <div class="info-card">
                                    <i class="bi bi-code-square info-icon"></i>
                                    <h4>Visual Studio Code</h4>
                                    <p>Entorno de codificación principal utilizado por todo el equipo.</p>
                                </div>
                                <div class="info-card">
                                    <i class="bi bi-github info-icon"></i>
                                    <h4>GitHub</h4>
                                    <p>Control de versiones cooperativo y respaldo ante pérdidas o errores.</p>
                                </div>
                                <div class="info-card">
                                    <i class="bi bi-database-fill info-icon"></i>
                                    <h4>MySQL + Workbench</h4>
                                    <p>Base de datos y herramienta visual para el diseño e implementación de datos.</p>
                                </div>
                            </div>
                        </div>

                        <div class="tab-panel" :class="{ active: activeTab === 'frameworks' }">
                            <div class="cards-row">
                                <div class="info-card">
                                    <i class="bi bi-server info-icon"></i>
                                    <h4>Laravel (PHP)</h4>
                                    <p>Backend robusto que estructura toda la lógica del lado del servidor.</p>
                                </div>
                                <div class="info-card">
                                    <i class="bi bi-bezier2 info-icon"></i>
                                    <h4>Vue.js + Tailwind CSS</h4>
                                    <p>Frontend interactivo y reactivo con un sistema de estilos utilitario.</p>
                                </div>
                                <div class="info-card">
                                    <i class="bi bi-filetype-html info-icon"></i>
                                    <h4>HTML5 y JavaScript</h4>
                                    <p>Bases nativas de la web para estructura e interactividad.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===================== SECCIÓN 3: REQUISITOS DEL CLIENTE ===================== -->
            <section class="qs-section">
                <div class="container">
                    <div class="section-head reveal" data-reveal>
                        <span class="kicker"><i class="bi bi-clipboard-check-fill"></i> Infraestructura</span>
                        <h2 class="section-title">Requisitos del Cliente</h2>
                        <p class="section-sub">Lo necesario para ejecutar Cronos Notes de forma óptima.</p>
                    </div>

                    <div class="req-grid">
                        <div class="req-block reveal" data-reveal data-delay="100">
                            <div class="req-head"><i class="bi bi-cpu-fill"></i> Hardware del Cliente</div>
                            <ul class="req-list">
                                <li>
                                    <i class="bi bi-memory"></i>
                                    <div>
                                        <strong>Mínimo 4 GB de RAM</strong>
                                        <span>Evita que el navegador suspenda la pestaña y se pierda el progreso del Pomodoro.</span>
                                    </div>
                                </li>
                                <li>
                                    <i class="bi bi-volume-up-fill"></i>
                                    <div>
                                        <strong>Salida de audio funcional</strong>
                                        <span>Auriculares o altavoces para alertas de temporizador y mezclador inmersivo.</span>
                                    </div>
                                </li>
                                <li>
                                    <i class="bi bi-mic-fill"></i>
                                    <div>
                                        <strong>Micrófono (recomendado)</strong>
                                        <span>Para las notas de voz del módulo de Toma de Apuntes.</span>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="req-block reveal" data-reveal data-delay="200">
                            <div class="req-head"><i class="bi bi-window-desktop"></i> Software del Cliente</div>
                            <ul class="req-list">
                                <li>
                                    <i class="bi bi-browser-chrome"></i>
                                    <div>
                                        <strong>Navegador moderno</strong>
                                        <span>Chrome, Firefox, Edge, Opera o Safari, óptimos para Vue.js y flujos de audio.</span>
                                    </div>
                                </li>
                                <li>
                                    <i class="bi bi-windows"></i>
                                    <div>
                                        <strong>Sistema operativo independiente</strong>
                                        <span>Windows, macOS o Linux con soporte para navegadores modernos.</span>
                                    </div>
                                </li>
                                <li>
                                    <i class="bi bi-ethernet"></i>
                                    <div>
                                        <strong>Banda ancha estable</strong>
                                        <span>Obligatoria para el motor de IA y la sincronización con Google Calendar y Spotify.</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
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
                        <i class="bi bi-rocket-takeoff me-2"></i> Comenzar
                    </Link>
                    <a href="#top" class="btn btn-outline-premium px-4 py-2.5 rounded-3 fw-bold text-decoration-none shadow-sm">
                        <i class="bi bi-arrow-up me-2"></i> Volver al inicio
                    </a>
                </div>
            </div>
        </main>

        <!-- ======= FOOTER ======= -->
        <footer class="cn-footer">
            <div class="container">
                <p>© 2026 <strong style="color:var(--tierra)">Cronos Notes</strong> · Ingeniería de Software II &amp; Programación Web · Todos los derechos reservados.</p>
            </div>
        </footer>
    </div>
</template>

<style>
.cn-quienes-page {
  --bg-base: #ffffff;
  --bg-soft: #f8f9fa;
  --bordo: #69342e;
  --bordo-deep: #4c2521;
  --tierra: #c17f59;
  --tierra-soft: #e3c3ad;
  --text: #2b211f;
  --text-soft: #6b5d5a;
  --white: #ffffff;
  --shadow: 0 12px 40px rgba(76, 37, 33, 0.12);
  --shadow-strong: 0 22px 60px rgba(76, 37, 33, 0.22);
  --radius: 18px;

  /* Theme toggle custom variables */
  --cn-navbar-bg: rgba(255, 255, 255, 0.85);
  --cn-navbar-scrolled-bg: rgba(255, 255, 255, 0.95);
  --cn-navbar-border: rgba(76, 37, 33, 0.08);
  --cn-btn-ghost-hover-bg: rgba(105, 52, 46, 0.05);

  --cn-team-card-bg: rgba(255, 255, 255, 0.55);
  --cn-team-card-border: rgba(255, 255, 255, 0.7);
  --cn-tab-btn-bg: rgba(255, 255, 255, 0.6);
  --cn-tab-btn-border: rgba(105, 52, 46, 0.2);
  --cn-tab-active-bg: linear-gradient(135deg, var(--bordo), var(--bordo-deep));
  --cn-tab-active-text: #ffffff;

  --cn-info-card-bg: rgba(255, 255, 255, 0.6);
  --cn-info-card-border: rgba(255, 255, 255, 0.7);
  --cn-req-block-bg: rgba(255, 255, 255, 0.6);
  --cn-req-block-border: rgba(255, 255, 255, 0.7);
  --cn-req-head-bg: linear-gradient(135deg, var(--bordo), var(--bordo-deep));
  --cn-req-head-text: #ffffff;
  --cn-req-list-border: rgba(105, 52, 46, 0.15);
  --cn-socials-bg: rgba(105, 52, 46, 0.08);

  --cn-smoke-color-1: var(--tierra);
  --cn-smoke-color-2: var(--bordo);
  --cn-smoke-color-3: var(--tierra-soft);
  --cn-smoke-bg-gradient-1: #fbf6f3;
  --cn-smoke-bg-gradient-2: #f6efe9;

  font-family: "Plus Jakarta Sans", system-ui, -apple-system, sans-serif;
  color: var(--text);
  background: var(--bg-base);
  min-height: 100vh;
}

body.cn-body-dark .cn-quienes-page {
  --bg-base: #612c2d;           /* Fondo base: bordo oscuro */
  --bg-soft: #4c2521;           /* Fondo secundario: bordo mas oscuro */
  --bordo: #e38e76;             /* Terracota para acentos e iconos */
  --bordo-deep: #ffffff;        /* Blanco puro en dark mode para titulos */
  --tierra: #f4be95;            /* Durazno para acentos secundarios */
  --tierra-soft: #7b413f;       /* Bordo intermedio */
  --text: #ffffff;              /* Textos principales a Blanco */
  --text-soft: #fcd5b8;         /* Textos secundarios a Crema durazno suave */
  --white: #612c2d;             /* Fondo base */
  --shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
  --shadow-strong: 0 22px 60px rgba(0, 0, 0, 0.6);
  
  --cn-navbar-bg: rgba(76, 37, 33, 0.85);
  --cn-navbar-scrolled-bg: rgba(97, 44, 45, 0.95);
  --cn-navbar-border: rgba(244, 190, 149, 0.15);
  --cn-btn-ghost-hover-bg: rgba(244, 190, 149, 0.15);
  
  --cn-team-card-bg: rgba(76, 37, 33, 0.6);
  --cn-team-card-border: rgba(244, 190, 149, 0.25);
  --cn-tab-btn-bg: rgba(76, 37, 33, 0.6);
  --cn-tab-btn-border: rgba(244, 190, 149, 0.25);
  --cn-tab-active-bg: linear-gradient(135deg, #e38e76, #a55e57);
  --cn-tab-active-text: #4c2521;

  --cn-info-card-bg: rgba(76, 37, 33, 0.6);
  --cn-info-card-border: rgba(244, 190, 149, 0.25);
  --cn-req-block-bg: rgba(76, 37, 33, 0.6);
  --cn-req-block-border: rgba(244, 190, 149, 0.25);
  --cn-req-head-bg: linear-gradient(135deg, #e38e76, #a55e57);
  --cn-req-head-text: #4c2521;
  --cn-req-list-border: rgba(244, 190, 149, 0.25);
  --cn-socials-bg: rgba(244, 190, 149, 0.12);

  --cn-smoke-color-1: #7b413f;
  --cn-smoke-color-2: #4c2521;
  --cn-smoke-color-3: #8a4a3f;
  --cn-smoke-bg-gradient-1: #502223;
  --cn-smoke-bg-gradient-2: #5c2829;
}

.cn-quienes-page h1,
.cn-quienes-page h2,
.cn-quienes-page h3,
.cn-quienes-page h4 {
  font-family: 'Fraunces', Georgia, serif;
}

/* Evitar que Tailwind collapse oculte la barra de navegación en computadoras */
.cn-quienes-page .collapse {
  visibility: visible !important;
}

/* ===== GLOBAL NAVBAR ===== */
.cn-quienes-page .cn-navbar {
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
.cn-quienes-page .cn-navbar.scrolled {
  padding: 0.1rem 0;
  background: var(--cn-navbar-scrolled-bg);
  border-bottom: 1px solid var(--cn-navbar-border);
  box-shadow: var(--shadow);
}
.cn-quienes-page .cn-brand {
  display: flex;
  align-items: center;
  gap: .6rem;
  text-decoration: none;
  margin-left: -20px !important;
}
.cn-quienes-page .cn-navbar-logo {
  height: 80px;
  width: auto;
  object-fit: contain;
  transition: filter 0.3s ease;
}
.cn-quienes-page .cn-link {
  position: relative;
  font-weight: 600;
  color: var(--text) !important;
  padding: .5rem 1rem !important;
  text-decoration: none;
}
.cn-quienes-page .cn-link::after {
  content: "";
  position: absolute; left: 1rem; right: 1rem; bottom: .35rem;
  height: 2px; background: var(--tierra);
  transform: scaleX(0); transform-origin: left;
  transition: transform .3s ease;
}
.cn-quienes-page .cn-link:hover { color: var(--bordo) !important; }
.cn-quienes-page .cn-link:hover::after,
.cn-quienes-page .cn-link.active-link::after { transform: scaleX(1); }
.cn-quienes-page .cn-link.active-link { color: var(--bordo) !important; }

.cn-quienes-page .cn-btn-primary {
  background: var(--bordo) !important;
  color: var(--bg-base) !important; /* light text in light mode, dark text in dark mode */
  font-weight: 600;
  border-radius: 50px;
  border: 1px solid var(--bordo) !important;
  box-shadow: var(--shadow);
  text-decoration: none;
}
body.cn-body-light .cn-quienes-page .cn-btn-primary {
  color: #fff !important;
}
.cn-quienes-page .cn-btn-primary:hover {
  background: var(--bordo-deep) !important;
  color: #fff !important;
  transform: translateY(-2px);
  box-shadow: var(--shadow-strong);
}
.cn-quienes-page .cn-btn-ghost {
  background: transparent !important;
  color: var(--bordo) !important;
  font-weight: 600;
  border-radius: 50px;
  border: 1px solid var(--bordo) !important;
  text-decoration: none;
}
.cn-quienes-page .cn-btn-ghost:hover {
  background: var(--cn-btn-ghost-hover-bg) !important;
  transform: translateY(-2px);
}
.cn-quienes-page .navbar-toggler { border: none; font-size: 1.6rem; color: var(--bordo) !important; }
.cn-quienes-page .navbar-toggler:focus { box-shadow: none; }

.cn-quienes-page .container { width: min(1180px, 92%); margin: 0 auto; }

/* ===================== FONDO DE HUMO ZEN ANIMADO ===================== */
.cn-quienes-page .smoke-bg {
  position: fixed;
  inset: 0;
  z-index: -1;
  overflow: hidden;
  background:
    radial-gradient(circle at 20% 20%, var(--cn-smoke-bg-gradient-1) 0%, transparent 55%),
    radial-gradient(circle at 80% 80%, var(--cn-smoke-bg-gradient-2) 0%, transparent 55%),
    var(--bg-base);
}

.cn-quienes-page .smoke {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  opacity: 0.35;
  will-change: transform;
}

.cn-quienes-page .smoke-1 {
  width: 480px; height: 480px;
  background: var(--cn-smoke-color-1);
  top: -120px; left: -80px;
  animation: drift1 22s ease-in-out infinite;
}

.cn-quienes-page .smoke-2 {
  width: 560px; height: 560px;
  background: var(--cn-smoke-color-2);
  bottom: -160px; right: -120px;
  opacity: 0.22;
  animation: drift2 28s ease-in-out infinite;
}

.cn-quienes-page .smoke-3 {
  width: 380px; height: 380px;
  background: var(--cn-smoke-color-3);
  top: 45%; left: 55%;
  animation: drift3 25s ease-in-out infinite;
}

@keyframes drift1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(120px, 80px) scale(1.15); }
}
@keyframes drift2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-100px, -90px) scale(1.2); }
}
@keyframes drift3 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50% { transform: translate(-90px, 70px) scale(0.9); }
}

/* ===================== ANIMACIÓN DE APARICIÓN (REVEAL) ===================== */
.cn-quienes-page .reveal {
  opacity: 0;
  transform: translateY(40px);
  transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1),
              transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
.cn-quienes-page .reveal.is-visible { opacity: 1; transform: translateY(0); }

/* ===================== CABECERA / HERO ===================== */
.cn-quienes-page .hero { padding: 9rem 0 70px; text-align: center; }

.cn-quienes-page .badge-top {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--cn-socials-bg);
  color: var(--bordo);
  border: 1px solid var(--cn-tab-btn-border);
  padding: 8px 20px;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.cn-quienes-page .hero-title {
  font-size: clamp(2.2rem, 5vw, 3.6rem);
  font-weight: 800;
  margin: 24px auto 18px;
  line-height: 1.15;
  max-width: 16ch;
}
.cn-quienes-page .accent { color: var(--bordo); }

.cn-quienes-page .hero-text {
  max-width: 70ch;
  margin: 0 auto;
  color: var(--text-soft);
  font-size: 1.08rem;
}

/* ===================== SECCIONES ===================== */
.cn-quienes-page .qs-section { padding: 80px 0; }
.cn-quienes-page .qs-section-alt {
  background: var(--bg-soft);
  backdrop-filter: blur(4px);
  border-top: 1px solid var(--cn-navbar-border);
  border-bottom: 1px solid var(--cn-navbar-border);
}

.cn-quienes-page .section-head { text-align: center; margin-bottom: 54px; }
.cn-quienes-page .kicker {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--tierra);
  font-weight: 700;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 1.5px;
}
.cn-quienes-page .section-title {
  font-size: clamp(1.8rem, 4vw, 2.6rem);
  font-weight: 800;
  color: var(--bordo-deep);
  margin: 10px 0;
}
.cn-quienes-page .section-sub { color: var(--text-soft); font-size: 1.05rem; }

/* ===================== TARJETAS DE EQUIPO ===================== */
.cn-quienes-page .team-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 26px;
}

.cn-quienes-page .team-card {
  position: relative;
  background: var(--cn-team-card-bg);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border: 1px solid var(--cn-team-card-border);
  border-radius: var(--radius);
  padding: 30px 24px 26px;
  text-align: center;
  box-shadow: var(--shadow);
  transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1),
              box-shadow 0.4s ease;
  overflow: hidden;
}
.cn-quienes-page .team-card::before {
  content: "";
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 5px;
  background: linear-gradient(90deg, var(--bordo), var(--tierra));
  transform: scaleX(0);
  transform-origin: left;
  transition: transform 0.45s ease;
}
.cn-quienes-page .team-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-strong);
}
.cn-quienes-page .team-card:hover::before { transform: scaleX(1); }

.cn-quienes-page .avatar-wrap {
  width: 118px; height: 118px;
  margin: 0 auto 18px;
  border-radius: 50%;
  padding: 4px;
  background: linear-gradient(135deg, var(--bordo), var(--tierra));
  box-shadow: 0 8px 22px rgba(105, 52, 46, 0.25);
  transition: transform 0.4s ease;
}
.cn-quienes-page .team-card:hover .avatar-wrap { transform: scale(1.06) rotate(-2deg); }

.cn-quienes-page .avatar {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 50%;
  display: block;
  background: var(--bg-soft);
}

.cn-quienes-page .member-name { font-size: 1.05rem; font-weight: 700; color: var(--bordo-deep); }
.cn-quienes-page .member-role {
  display: inline-block;
  margin: 6px 0 16px;
  font-size: 0.8rem;
  color: var(--tierra);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.cn-quienes-page .task-list { list-style: none; text-align: left; }
.cn-quienes-page .task-list li {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  font-size: 0.88rem;
  color: var(--text-soft);
  padding: 5px 0;
}
.cn-quienes-page .task-list li i { color: var(--bordo); margin-top: 2px; }

.cn-quienes-page .socials {
  display: flex;
  justify-content: center;
  gap: 14px;
  margin-top: 18px;
  opacity: 0;
  transform: translateY(12px);
  transition: opacity 0.4s ease, transform 0.4s ease;
}
.cn-quienes-page .team-card:hover .socials { opacity: 1; transform: translateY(0); }
.cn-quienes-page .socials a {
  width: 38px; height: 38px;
  display: grid; place-items: center;
  border-radius: 50%;
  background: var(--cn-socials-bg);
  color: var(--bordo);
  font-size: 1.05rem;
  text-decoration: none;
  transition: background 0.3s ease, color 0.3s ease, transform 0.3s ease;
}
.cn-quienes-page .socials a:hover {
  background: var(--bordo);
  color: #ffffff !important;
  transform: translateY(-3px);
}

/* ===================== TABS RECURSOS TÉCNICOS ===================== */
.cn-quienes-page .tabs {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 38px;
}
.cn-quienes-page .tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid var(--cn-tab-btn-border);
  background: var(--cn-tab-btn-bg);
  color: var(--bordo);
  font-weight: 600;
  font-size: 0.92rem;
  padding: 11px 22px;
  border-radius: 999px;
  cursor: pointer;
  transition: all 0.3s ease;
}
.cn-quienes-page .tab-btn:hover { border-color: var(--tierra); transform: translateY(-2px); }
.cn-quienes-page .tab-btn.active {
  background: var(--cn-tab-active-bg);
  color: var(--cn-tab-active-text) !important;
  border-color: transparent;
  box-shadow: var(--shadow);
}

.cn-quienes-page .tab-panel { display: none; animation: fadeUp 0.5s ease; }
.cn-quienes-page .tab-panel.active { display: block; }
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.cn-quienes-page .cards-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 22px;
}
.cn-quienes-page .info-card {
  background: var(--cn-info-card-bg);
  backdrop-filter: blur(12px);
  border: 1px solid var(--cn-info-card-border);
  border-radius: var(--radius);
  padding: 30px 26px;
  box-shadow: var(--shadow);
  transition: transform 0.35s ease, box-shadow 0.35s ease;
}
.cn-quienes-page .info-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-strong); }
.cn-quienes-page .info-icon {
  font-size: 2.2rem;
  color: var(--tierra);
  margin-bottom: 14px;
  display: inline-block;
}
.cn-quienes-page .info-card h4 { color: var(--bordo-deep); font-size: 1.15rem; margin-bottom: 8px; }
.cn-quienes-page .info-card p { color: var(--text-soft); font-size: 0.93rem; }

/* ===================== REQUISITOS DEL CLIENTE ===================== */
.cn-quienes-page .req-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 28px;
}
.cn-quienes-page .req-block {
  background: var(--cn-req-block-bg);
  backdrop-filter: blur(12px);
  border: 1px solid var(--cn-req-block-border);
  border-radius: var(--radius);
  padding: 32px 30px;
  box-shadow: var(--shadow);
}
.cn-quienes-page .req-head {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--cn-req-head-text);
  background: var(--cn-req-head-bg);
  padding: 14px 20px;
  border-radius: 12px;
  margin-bottom: 22px;
}
.cn-quienes-page .req-list { list-style: none; }
.cn-quienes-page .req-list li {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 14px 0;
  border-bottom: 1px dashed var(--cn-req-list-border);
}
.cn-quienes-page .req-list li:last-child { border-bottom: none; }
.cn-quienes-page .req-list li i {
  font-size: 1.4rem;
  color: var(--tierra);
  margin-top: 2px;
  flex-shrink: 0;
}
.cn-quienes-page .req-list strong { display: block; color: var(--bordo-deep); }
.cn-quienes-page .req-list span { font-size: 0.9rem; color: var(--text-soft); }

/* CTA BUTTONS */
.cn-quienes-page .btn-premium {
    background-color: var(--bordo);
    color: var(--bg-base) !important;
    border: none;
    font-size: 0.9rem;
    transition: background-color 0.2s, transform 0.2s;
}
body.cn-body-light .cn-quienes-page .btn-premium {
    color: #ffffff !important;
}
.cn-quienes-page .btn-premium:hover {
    background-color: var(--bordo-deep);
    color: #ffffff !important;
    transform: translateY(-1px);
}

.cn-quienes-page .btn-outline-premium {
    border: 2px solid var(--bordo);
    color: var(--bordo);
    background: transparent;
    font-size: 0.9rem;
    transition: all 0.2s;
}
.cn-quienes-page .btn-outline-premium:hover {
    background-color: var(--bordo);
    color: var(--bg-base) !important;
}
body.cn-body-light .cn-quienes-page .btn-outline-premium:hover {
    color: #ffffff !important;
}

/* ===== Theme Toggle Style & Dark mode tweaks ===== */
.cn-theme-toggle-btn {
  background: transparent;
  border: none;
  font-size: 1.35rem;
  padding: 6px;
  color: var(--bordo);
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
  filter: drop-shadow(0 0 1.5px rgba(255, 255, 255, 0.8)) brightness(1.25);
}

/* ===================== FOOTER ===================== */
.cn-quienes-page .cn-footer {
  background: #1c0f0e;
  padding: 2.5rem 0;
  color: rgba(255,255,255,.55);
  text-align: center;
  font-size: .8rem;
}
.cn-quienes-page .cn-footer strong {
  color: var(--tierra);
}

/* ===================== RESPONSIVE ===================== */
@media (max-width: 992px) {
  .cn-quienes-page .team-grid { grid-template-columns: repeat(2, 1fr); }
  .cn-quienes-page .cards-row { grid-template-columns: 1fr; }
  .cn-quienes-page .req-grid { grid-template-columns: 1fr; }
}
@media (max-width: 560px) {
  .cn-quienes-page .team-grid { grid-template-columns: 1fr; }
  .cn-quienes-page .hero { padding: 80px 0 50px; }
}

/* Respeta usuarios que prefieren menos movimiento */
@media (prefers-reduced-motion: reduce) {
  .cn-quienes-page .smoke, .cn-quienes-page .reveal, .cn-quienes-page .team-card, .cn-quienes-page .info-card { animation: none !important; transition: none !important; }
  .cn-quienes-page .reveal { opacity: 1; transform: none; }
}
</style>

<style>
html {
  scroll-behavior: smooth;
}
</style>