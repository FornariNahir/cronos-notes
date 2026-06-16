<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

let revealObserver = null;
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

    // Scroll reveal logic
    const allRev = document.querySelectorAll('.reveal,.reveal-l,.reveal-r');
    revealObserver = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('in');
                revealObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.08 });
    allRev.forEach(el => revealObserver.observe(el));

    // Scrollspy Sidebar logic
    const sections = document.querySelectorAll('section[id]');
    const spyLinks = document.querySelectorAll('.spy-link');
    const navLinks = document.querySelectorAll('.nav-link-cn');

    const updateSpy = () => {
        let current = '';
        sections.forEach(sec => {
            const top = sec.getBoundingClientRect().top;
            if (top < 160) current = sec.id;
        });
        spyLinks.forEach(a => {
            a.classList.toggle('active', a.getAttribute('href') === '#' + current);
        });
        navLinks.forEach(a => {
            a.classList.toggle('active-lnk', a.getAttribute('href') === '#' + current);
        });
    };
    window.addEventListener('scroll', updateSpy, { passive: true });
    updateSpy();

    // Tabs — Apuntes
    document.querySelectorAll('.note-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.note-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.note-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            const targetPanel = document.getElementById('tab-' + btn.dataset.tab);
            if (targetPanel) targetPanel.classList.add('active');
        });
    });

    // Play Button (video placeholder)
    const playBtn = document.getElementById('playBtn');
    if (playBtn) {
        playBtn.addEventListener('click', function () {
            const video = document.getElementById('tutorialVideo');
            if (video) {
                if (video.paused) {
                    video.play();
                    this.innerHTML = '<i class="bi bi-pause-fill"></i>';
                } else {
                    video.pause();
                    this.innerHTML = '<i class="bi bi-play-fill"></i>';
                }
            }
        });
    }

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
        window.removeEventListener('scroll', updateSpy);
        window.removeEventListener('scroll', onScroll);
    });
});

onUnmounted(() => {
    if (revealObserver) revealObserver.disconnect();
    document.body.classList.remove('cn-body-light');
    document.body.classList.remove('cn-body-dark');
});
</script>

<template>
    <Head>
        <title>Cronos Notes — Modo de Uso</title>
        <meta name="description" content="Guía de aprendizaje paso a paso para dominar tu tiempo y tareas con Cronos Notes." />
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,600;9..144,800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
    </Head>

    <div class="cn-uso-page">
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

        <!-- ======= HERO ======= -->
        <header class="page-hero" id="top">
            <div class="hero-grain" aria-hidden="true"></div>
            <div class="container">
                <div class="hero-eyebrow"><i class="bi bi-book-half"></i> Guía de Aprendizaje</div>
                <h1>Aprende a dominar tu tiempo<br><span>con Cronos Notes</span></h1>
                <p class="hero-lead">
                    Combinamos la legendaria <strong>Técnica Pomodoro</strong>, <strong>Inteligencia Artificial</strong>
                    y métodos de estudio probados para que alcances el máximo rendimiento sin agotamiento.
                    Seguí esta guía paso a paso y dominá la app en minutos.
                </p>
                <div class="hero-pills">
                    <span class="h-pill"><i class="bi bi-hourglass-split"></i> Método Pomodoro</span>
                    <span class="h-pill"><i class="bi bi-cpu"></i> Priorización con IA</span>
                    <span class="h-pill"><i class="bi bi-journal-text"></i> Notas Cornell</span>
                    <span class="h-pill"><i class="bi bi-music-note-beamed"></i> Audio Zen</span>
                    <span class="h-pill"><i class="bi bi-bar-chart"></i> Analítica</span>
                </div>
                <div class="hero-steps-row">
                    <div class="hs-item"><div class="hs-num">1</div> Perfiles</div>
                    <div class="hs-sep"></div>
                    <div class="hs-item"><div class="hs-num">2</div> Tareas</div>
                    <div class="hs-sep"></div>
                    <div class="hs-item"><div class="hs-num">3</div> Pomodoro</div>
                    <div class="hs-sep"></div>
                    <div class="hs-item"><div class="hs-num">4</div> Apuntes</div>
                    <div class="hs-sep"></div>
                    <div class="hs-item"><div class="hs-num">5</div> Estadísticas</div>
                </div>
            </div>
        </header>

        <!-- ======= BODY: SIDEBAR + MAIN ======= -->
        <div class="container">
            <div class="page-body">
                <!-- SCROLLSPY SIDEBAR -->
                <aside class="spy-sidebar">
                    <div class="spy-title">Contenido</div>
                    <ul class="spy-nav" id="spyNav">
                        <li><a href="#paso1" class="spy-link"><i class="bi bi-person-badge"></i> Paso 1 · Perfiles</a></li>
                        <li><a href="#paso2" class="spy-link"><i class="bi bi-list-check"></i> Paso 2 · Tareas</a></li>
                        <li><a href="#paso3" class="spy-link"><i class="bi bi-hourglass-split"></i> Paso 3 · Pomodoro</a></li>
                        <li><a href="#paso4" class="spy-link"><i class="bi bi-journal-text"></i> Paso 4 · Apuntes</a></li>
                        <li><a href="#paso5" class="spy-link"><i class="bi bi-bar-chart-line"></i> Paso 5 · Estadísticas</a></li>
                    </ul>
                </aside>

                <!-- MAIN CONTENT -->
                <main class="page-main ps-lg-5">
                    <!-- ============================================================
                         PASO 1 — PERFILES
                    ============================================================ -->
                    <section class="step-section" id="paso1">
                        <div class="row align-items-center g-5">
                            <div class="col-lg-6 reveal-l">
                                <div class="img-container">
                                    <img src="/imagenes/perfiles.png" class="img-fluid rounded shadow" alt="Gestión de perfiles" style="width: 100%; height: auto; border: 1px solid rgba(76, 37, 33, 0.1);" />
                                </div>
                            </div>
                            <div class="col-lg-6 reveal-r">
                                <div class="step-badge"><div class="step-num-badge">1</div> Primeros pasos</div>
                                <h2 class="step-title">Creá tu espacio<br><em>por cátedra</em></h2>
                                <p class="step-desc">Cronos Notes te permite organizar tu vida académica creando perfiles independientes para cada materia universitaria. Cada espacio tiene sus propias tareas, apuntes y estadísticas.</p>
                                <ul class="feat-list">
                                    <li><i class="bi bi-check-circle-fill"></i> Creá un perfil por cada cátedra o proyecto.</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Asigná color, ícono y descripción a cada espacio.</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Alternás entre perfiles con un solo clic desde el panel.</li>
                                    <li><i class="bi bi-check-circle-fill"></i> Las estadísticas se calculan de forma separada para cada cátedra.</li>
                                </ul>
                                <div class="mt-3">
                                    <div class="row g-3 mt-1">
                                        <div class="col-6 d1">
                                            <div class="cn-card">
                                                <div class="card-icon"><i class="bi bi-folder2-open"></i></div>
                                                <div class="card-title">Cátedras separadas</div>
                                                <p class="card-text">Cada materia vive en su propio espacio sin mezclar contenido.</p>
                                            </div>
                                        </div>
                                        <div class="col-6 d2">
                                            <div class="cn-card">
                                                <div class="card-icon"><i class="bi bi-people"></i></div>
                                                <div class="card-title">Modo Invitado</div>
                                                <p class="card-text">Probá la app sin registrarte y guardá tu progreso cuando estés listo.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ============================================================
                         PASO 2 — TAREAS
                    ============================================================ -->
                    <section class="step-section" id="paso2">
                        <div class="row">
                            <div class="col-lg-8 mx-auto reveal">
                                <div class="step-badge"><div class="step-num-badge">2</div> Organización</div>
                                <h2 class="step-title">Tareas <em>inteligentes</em><br>con IA</h2>
                                <p class="step-desc">Añadí tus pendientes y dejá que la Inteligencia Artificial analice plazos, dificultad e historial de trabajo para sugerirte el orden óptimo de cada sesión.</p>
                                <ul class="feat-list mb-4">
                                    <li><i class="bi bi-cpu"></i> IA que sugiere qué hacer primero, automáticamente.</li>
                                    <li><i class="bi bi-flag-fill"></i> Etiquetá tareas por prioridad: Alta, Media o Baja.</li>
                                    <li><i class="bi bi-arrow-repeat"></i> Las sugerencias de la IA se actualizan en tiempo real.</li>
                                </ul>
                                <div class="img-container">
                                    <img src="/imagenes/tareas.png" class="img-fluid rounded shadow" alt="Organización de tareas" style="width: 100%; height: auto; border: 1px solid rgba(76, 37, 33, 0.1);" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ============================================================
                         PASO 3 — POMODORO
                    ============================================================ -->
                    <section class="step-section" id="paso3" style="margin:0 -12px;padding:3.5rem 12px;border-radius:18px">
                        <div class="row align-items-center g-5">
                            <div class="col-lg-5 reveal-l">
                                <div class="step-badge"><div class="step-num-badge">3</div> Sesión Inmersiva</div>
                                <h2 class="step-title">Tu primera sesión<br><em>Pomodoro</em></h2>
                                <p class="step-desc">Configura el reloj exactamente a tu gusto: elegí los minutos de foco, el tiempo de descanso corto y largo, y personalizá tu entorno visual y sonoro.</p>
                                <ul class="feat-list">
                                    <li><i class="bi bi-sliders"></i> Reloj <strong>100% configurable</strong>: duraciones a tu medida.</li>
                                    <li><i class="bi bi-music-note-beamed"></i> Sonidos Zen con Howler.js: lluvia, bosque, café.</li>
                                    <li><i class="bi bi-eye"></i> Modo Zen: interfaz minimalista sin distracciones.</li>
                                    <li><i class="bi bi-bell"></i> Notificaciones automáticas al terminar cada bloque.</li>
                                </ul>
                                <div class="pomo-config">
                                    <div class="pomo-chip">
                                        <i class="bi bi-stopwatch"></i>
                                        <div><span class="pomo-chip-label">Foco</span><span class="pomo-chip-val">25 min</span></div>
                                    </div>
                                    <div class="pomo-chip">
                                        <i class="bi bi-cup-hot"></i>
                                        <div><span class="pomo-chip-label">Descanso</span><span class="pomo-chip-val">5 min</span></div>
                                    </div>
                                    <div class="pomo-chip">
                                        <i class="bi bi-arrow-repeat"></i>
                                        <div><span class="pomo-chip-label">Rondas</span><span class="pomo-chip-val">4 ciclos</span></div>
                                    </div>
                                    <div class="pomo-chip">
                                        <i class="bi bi-moon-stars"></i>
                                        <div><span class="pomo-chip-label">Descanso largo</span><span class="pomo-chip-val">15 min</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7 reveal-r">
                                <div class="video-frame">
                                    <div class="video-badge"><i class="bi bi-broadcast"></i> Tutorial</div>
                                    <div class="video-placeholder">
                                        <video id="tutorialVideo" class="w-100" loop muted playsinline poster="/imagenes/apuntes.png" src="/imagenes/videoPomodoro.mp4"></video>
                                        <div class="video-overlay">
                                            <div class="play-btn" id="playBtn"><i class="bi bi-play-fill"></i></div>
                                            <div class="video-caption">Tutorial: Tu primera sesión Pomodoro</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-6">
                                        <img src="/imagenes/modoZen.png" class="img-fluid rounded shadow" alt="Temporizador Pomodoro" style="width: 100%; height: auto; border: 1px solid rgba(76, 37, 33, 0.1);" />
                                    </div>
                                    <div class="col-6">
                                        <img src="/imagenes/student-focus.png" class="img-fluid rounded shadow" alt="Modo Zen" style="width: 100%; height: auto; border: 1px solid rgba(76, 37, 33, 0.1);" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ============================================================
                         PASO 4 — APUNTES
                    ============================================================ -->
                    <section class="step-section" id="paso4">
                        <div class="reveal mb-4">
                            <div class="step-badge"><div class="step-num-badge">4</div> Módulo de Apuntes</div>
                            <h2 class="step-title">Apuntes <em>avanzados</em>:<br>tres métodos integrados</h2>
                            <p class="step-desc">Elegí el método que mejor se adapte a tu estilo de aprendizaje. Cronos Notes integra notas rápidas tradicionales, el estructurado Método Cornell y grabación de voz directamente en la app.</p>
                        </div>

                        <!-- TABS -->
                        <div class="notes-tabs reveal">
                            <button class="note-tab active" data-tab="traditional"><i class="bi bi-pencil"></i> Método Tradicional</button>
                            <button class="note-tab" data-tab="cornell"><i class="bi bi-layout-text-sidebar"></i> Método Cornell</button>
                            <button class="note-tab" data-tab="voice"><i class="bi bi-mic-fill"></i> Notas de Voz</button>
                        </div>

                        <!-- PANEL: TRADICIONAL -->
                        <div class="note-panel active reveal" id="tab-traditional">
                            <div class="row">
                                <div class="col-lg-8 mx-auto">
                                    <h4 style="font-size:1.2rem;font-weight:700;margin-bottom:.7rem;color:var(--ink)">Notas rápidas y ágiles</h4>
                                    <p style="font-size:.88rem;color:var(--muted);line-height:1.7;margin-bottom:1rem">Ideal para clases dinámicas donde la velocidad importa. Capturás ideas, fórmulas y referencias en segundos con texto libre, etiquetas y categorías.</p>
                                    <ul class="feat-list mb-4">
                                        <li><i class="bi bi-lightning-charge-fill"></i> Escritura libre sin estructura forzada.</li>
                                        <li><i class="bi bi-tag-fill"></i> Sistema de etiquetas para clasificar al instante.</li>
                                        <li><i class="bi bi-search"></i> Búsqueda full-text entre todas tus notas.</li>
                                    </ul>
                                    <div class="img-container">
                                        <img src="/imagenes/apuntes.png" class="img-fluid rounded shadow" alt="Panel de Apuntes" style="width: 100%; height: auto; border: 1px solid rgba(76, 37, 33, 0.1);" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL: CORNELL -->
                        <div class="note-panel reveal" id="tab-cornell">
                            <div class="row">
                                <div class="col-lg-8 mx-auto">
                                    <h4 style="font-size:1.2rem;font-weight:700;margin-bottom:.7rem;color:var(--ink)">Estructura profesional Cornell</h4>
                                    <p style="font-size:.88rem;color:var(--muted);line-height:1.7;margin-bottom:1rem">El método Cornell divide cada nota en tres zonas: <strong>Ideas clave</strong> (columna izquierda para preguntas y conceptos), <strong>Notas</strong> (espacio principal de clase) y <strong>Resumen</strong> (síntesis al pie). Ideal para retención profunda.</p>
                                    <ul class="feat-list mb-4">
                                        <li><i class="bi bi-layout-text-sidebar-reverse"></i> Layout dividido en tres zonas automáticamente.</li>
                                        <li><i class="bi bi-recycle"></i> Perfecto para repasar antes de exámenes.</li>
                                        <li><i class="bi bi-download"></i> Exportable a PDF con formato Cornell real.</li>
                                    </ul>
                                    <div class="img-container">
                                        <img src="/imagenes/cornell.png" class="img-fluid rounded shadow" alt="Método Cornell" style="width: 100%; height: auto; border: 1px solid rgba(76, 37, 33, 0.1);" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL: VOZ -->
                        <div class="note-panel reveal" id="tab-voice">
                            <div class="row">
                                <div class="col-lg-8 mx-auto">
                                    <h4 style="font-size:1.2rem;font-weight:700;margin-bottom:.7rem;color:var(--ink)">Grabá tus clases directamente</h4>
                                    <p style="font-size:.88rem;color:var(--muted);line-height:1.7;margin-bottom:1rem">Cronos Notes incluye un grabador de voz integrado para capturar las clases en tiempo real. Las grabaciones quedan vinculadas a la cátedra y podés escucharlas mientras tomás notas.</p>
                                    <ul class="feat-list mb-4">
                                        <li><i class="bi bi-mic-fill"></i> Grabación directa desde el navegador.</li>
                                        <li><i class="bi bi-link-45deg"></i> Vinculada automáticamente a la cátedra activa.</li>
                                        <li><i class="bi bi-scissors"></i> Podés marcar y recortar fragmentos importantes.</li>
                                        <li><i class="bi bi-cloud-arrow-up"></i> Almacenamiento seguro en tu perfil.</li>
                                    </ul>
                                    <div class="img-container">
                                        <img src="/imagenes/panelAudio.png" class="img-fluid rounded shadow" alt="Grabación de voz" style="width: 100%; height: auto; border: 1px solid rgba(76, 37, 33, 0.1);" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ============================================================
                         PASO 5 — ESTADÍSTICAS
                    ============================================================ -->
                    <section class="step-section" id="paso5">
                        <div class="row g-5 align-items-center">
                            <div class="col-lg-5 reveal-l">
                                <div class="step-badge"><div class="step-num-badge">5</div> Analítica</div>
                                <h2 class="step-title">Medí tu<br><em>progreso real</em></h2>
                                <p class="step-desc">El panel de estadísticas te muestra exactamente cómo evolucionás: horas de foco, tareas completadas, racha de días activos y distribución del tiempo por cátedra.</p>
                                <ul class="feat-list">
                                    <li><i class="bi bi-bar-chart-line-fill"></i> Gráficas semanales y mensuales de foco.</li>
                                    <li><i class="bi bi-pie-chart-fill"></i> Distribución de tiempo por materia.</li>
                                    <li><i class="bi bi-fire"></i> Racha diaria para mantener la motivación.</li>
                                    <li><i class="bi bi-download"></i> Exportá reportes en PDF para entregar o guardar.</li>
                                </ul>
                            </div>
                            <div class="col-lg-7 reveal-r">
                                <div class="img-container">
                                    <img src="/imagenes/estadistica.png" class="img-fluid rounded shadow" alt="Estadísticas generales" style="width: 100%; height: auto; border: 1px solid rgba(76, 37, 33, 0.1);" />
                                </div>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </div>

        <!-- ======= CTA FINAL ======= -->
        <section class="final-cta">
            <div class="container">
                <h2>¿Listo para dominar tu tiempo?</h2>
                <p>Aplicá todo lo que aprendiste y empezá tu primera sesión Pomodoro hoy. Es gratis, sin tarjeta de crédito.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <Link :href="route('register')" class="btn-final"><i class="bi bi-rocket-takeoff"></i> Comenzar ahora</Link>
                    <a href="#top" class="btn-final-ghost"><i class="bi bi-arrow-up"></i> Volver al inicio</a>
                </div>
            </div>
        </section>

        <!-- ======= FOOTER ======= -->
        <footer class="cn-footer">
            <div class="container">
                <p>© 2026 <strong style="color:var(--sienna)">Cronos Notes</strong> · Ingeniería de Software II &amp; Programación Web · Todos los derechos reservados.</p>
            </div>
        </footer>
    </div>
</template>

<style>
/* Scoped styles logic using the .cn-uso-page parent selector to prevent leaks */
body.cn-body-light .cn-uso-page {
  --brown:   #4c2521;
  --roast:   #69342e;
  --sienna:  #c17f59;
  --sand:    #e8d5c0;
  --cream:   #faf5ef;
  --light:   #f8f9fa;
  --white:   #ffffff;
  --ink:     #1c0f0e;
  --muted:   #7a5e58;
  --r:       14px;
  --r2:      18px;
  --sh:      0 12px 40px -14px rgba(76,37,33,.28);
  --sh2:     0 4px 18px -6px rgba(76,37,33,.18);
  --tr:      all 0.3s ease;
  
  /* Theme toggle light custom variables */
  --cn-navbar-bg: rgba(255, 255, 255, 0.85);
  --cn-navbar-scrolled-bg: rgba(255, 255, 255, 0.95);
  --cn-border-soft: rgba(76, 37, 33, 0.08);
  --cn-btn-ghost-border: rgba(76, 37, 33, 0.25);
  --cn-btn-ghost-hover-bg: rgba(193, 127, 89, 0.12);
  --cn-border-solid: #f0ece9;
  --cn-card-icon-bg: rgba(193, 127, 89, 0.14);
  --cn-socials-bg: rgba(76, 37, 33, 0.06);
  --cn-pomo-bg: linear-gradient(135deg,#4c2521 0%,#69342e 60%,#8b4a3a 100%);
  --cn-cta-bg: var(--brown);
  --cn-card-bg: #ffffff;
}

body.cn-body-dark .cn-uso-page {
  --brown:   #f4be95;      /* durazno/crema para titulos y acentos */
  --roast:   #e38e76;      /* terracota para botones y acentos de scrollspy */
  --sienna:  #e38e76;      /* rosa suave */
  --sand:    #7b413f;      /* bordo intermedio para bordes y fondos secundarios */
  --cream:   #4c2521;      /* bordo mas oscuro para fondo de contenedores */
  --light:   #4c2521;      /* bordo mas oscuro para fondo de secciones */
  --white:   #612c2d;      /* FONDO PRINCIPAL: Bordo Oscuro */
  --ink:     #fffcfb;      /* Texto principal (blanco calido) */
  --muted:   #f4be95;      /* Texto secundario (durazno) */
  --r:       14px;
  --r2:      18px;
  --sh:      0 12px 40px -14px rgba(0,0,0,.6);
  --sh2:     0 4px 18px -6px rgba(0,0,0,.4);
  --tr:      all 0.3s ease;
  
  /* Theme toggle dark custom variables */
  --cn-navbar-bg: rgba(76, 37, 33, 0.85);
  --cn-navbar-scrolled-bg: rgba(97, 44, 45, 0.95);
  --cn-border-soft: rgba(244, 190, 149, 0.15);
  --cn-btn-ghost-border: rgba(244, 190, 149, 0.3);
  --cn-btn-ghost-hover-bg: rgba(244, 190, 149, 0.15);
  --cn-border-solid: #7b413f;
  --cn-card-icon-bg: rgba(244, 190, 149, 0.15);
  --cn-socials-bg: rgba(244, 190, 149, 0.12);
  --cn-pomo-bg: linear-gradient(135deg,#4c2521 0%,#321415 60%,#612c2d 100%);
  --cn-cta-bg: #4c2521;
  --cn-card-bg: #4c2521;
}

.cn-uso-page {
  font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
  background: var(--white);
  color: var(--ink);
  min-height: 100vh;
}

.cn-uso-page h1,
.cn-uso-page h2,
.cn-uso-page h3,
.cn-uso-page h4,
.cn-uso-page .serif {
  font-family: 'Fraunces', Georgia, serif;
}

/* Evitar que Tailwind collapse oculte la barra de navegación en computadoras */
.cn-uso-page .collapse {
  visibility: visible !important;
}

/* ===== GLOBAL NAVBAR DE WELCOME EN MODO USO ===== */
.cn-uso-page .cn-navbar {
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
.cn-uso-page .cn-navbar.scrolled {
  padding: 0.1rem 0;
  background: var(--cn-navbar-scrolled-bg);
  border-bottom: 1px solid var(--cn-border-soft);
  box-shadow: 0 8px 30px -18px rgba(76, 37, 33, 0.4);
}
.cn-uso-page .cn-brand {
  display: flex;
  align-items: center;
  gap: .6rem;
  text-decoration: none;
  margin-left: -20px !important; /* Forces the logo to align further to the left, bypassing container padding */
}
.cn-uso-page .cn-navbar-logo {
  height: 80px;
  width: auto;
  object-fit: contain;
}
.cn-uso-page .cn-link {
  position: relative;
  font-weight: 600;
  color: var(--ink) !important;
  padding: .5rem 1rem !important;
  text-decoration: none;
}
.cn-uso-page .cn-link::after {
  content: "";
  position: absolute; left: 1rem; right: 1rem; bottom: .35rem;
  height: 2px; background: var(--sienna);
  transform: scaleX(0); transform-origin: left;
  transition: transform .3s ease;
}
.cn-uso-page .cn-link:hover { color: var(--brown) !important; }
.cn-uso-page .cn-link:hover::after { transform: scaleX(1); }

.cn-uso-page .cn-btn-primary {
  background: var(--brown) !important;
  color: #fff !important;
  font-weight: 600;
  border-radius: 50px;
  border: 1px solid var(--brown) !important;
  box-shadow: var(--sh2);
  text-decoration: none;
}
.cn-uso-page .cn-btn-primary:hover {
  background: var(--roast) !important;
  color: #fff !important;
  transform: translateY(-2px);
  box-shadow: var(--sh);
}
.cn-uso-page .cn-btn-ghost {
  background: transparent !important;
  color: var(--brown) !important;
  font-weight: 600;
  border-radius: 50px;
  border: 1px solid var(--brown) !important;
  text-decoration: none;
}
.cn-uso-page .cn-btn-ghost:hover {
  background: var(--cn-btn-ghost-hover-bg) !important;
  transform: translateY(-2px);
}
.cn-uso-page .navbar-toggler { border: none; font-size: 1.6rem; color: var(--brown) !important; }
.cn-uso-page .navbar-toggler:focus { box-shadow: none; }
.cn-uso-page .nav-link-cn {
  position: relative;
  font-weight: 600;
  font-size: .84rem;
  color: var(--ink) !important;
  padding: .45rem .9rem !important;
  border-radius: 6px;
  transition: var(--tr) !important;
}
.cn-uso-page .nav-link-cn::after {
  content: '';
  position: absolute;
  left: .9rem;
  right: .9rem;
  bottom: .25rem;
  height: 2px;
  background: var(--sienna);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform .3s;
}
.cn-uso-page .nav-link-cn:hover {
  color: var(--brown) !important;
}
.cn-uso-page .nav-link-cn:hover::after,
.cn-uso-page .nav-link-cn.active-lnk::after {
  transform: scaleX(1);
}
.cn-uso-page .nav-link-cn.active-lnk {
  color: var(--brown) !important;
}
.cn-uso-page .btn-nav {
  background: var(--brown);
  color: #fff !important;
  font-weight: 700;
  font-size: .82rem;
  padding: .5rem 1.2rem;
  border-radius: 50px;
  border: none;
  text-decoration: none;
  transition: var(--tr) !important;
}
.cn-uso-page .btn-nav:hover {
  background: var(--roast);
  transform: translateY(-2px);
}
.cn-uso-page .btn-nav-ghost {
  background: transparent;
  color: var(--brown) !important;
  font-weight: 700;
  font-size: .82rem;
  padding: .5rem 1.2rem;
  border-radius: 50px;
  border: 1px solid var(--brown);
  text-decoration: none;
  transition: var(--tr) !important;
}
.cn-uso-page .btn-nav-ghost:hover {
  background: var(--cn-btn-ghost-hover-bg);
  transform: translateY(-2px);
}
.cn-uso-page .navbar-toggler {
  border: none;
  color: var(--brown);
  font-size: 1.4rem;
}
.cn-uso-page .navbar-toggler:focus {
  box-shadow: none;
}

/* HERO / CABECERA */
.cn-uso-page .page-hero {
  padding: 9rem 0 5rem;
  background:
    radial-gradient(800px 400px at 80% -10%,rgba(193,127,89,.16),transparent 65%),
    radial-gradient(500px 300px at 5% 90%,rgba(76,37,33,.06),transparent 60%),
    var(--cream);
  position: relative;
  overflow: hidden;
  text-align: center;
}
.cn-uso-page .hero-grain {
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: .022;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}
.cn-uso-page .hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  background: rgba(193,127,89,.13);
  border: 1px solid rgba(193,127,89,.3);
  color: var(--brown);
  font-weight: 700;
  font-size: .72rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: .38rem 1rem;
  border-radius: 50px;
  margin-bottom: 1.4rem;
}
.cn-uso-page .page-hero h1 {
  font-size: clamp(2rem, 5vw, 3.4rem);
  font-weight: 800;
  letter-spacing: -1.5px;
  line-height: 1.1;
  color: var(--ink);
  margin-bottom: 1rem;
}
.cn-uso-page .page-hero h1 span {
  color: var(--sienna);
}
.cn-uso-page .hero-lead {
  font-size: 1.05rem;
  color: var(--muted);
  line-height: 1.72;
  max-width: 580px;
  margin: 0 auto 2rem;
}
.cn-uso-page .hero-pills {
  display: flex;
  flex-wrap: wrap;
  gap: .6rem;
  justify-content: center;
  margin-bottom: 2.2rem;
}
.cn-uso-page .h-pill {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  background: var(--cn-card-bg);
  border: 1px solid var(--cn-border-soft);
  color: var(--brown);
  font-weight: 600;
  font-size: .78rem;
  padding: .38rem .9rem;
  border-radius: 50px;
  box-shadow: var(--sh2);
}
.cn-uso-page .h-pill i {
  color: var(--sienna);
}
.cn-uso-page .hero-steps-row {
  display: flex;
  justify-content: center;
  gap: 0;
  flex-wrap: wrap;
  margin-top: 2.5rem;
}
.cn-uso-page .hs-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-size: .78rem;
  font-weight: 600;
  color: var(--muted);
}
.cn-uso-page .hs-num {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--brown);
  color: #fff;
  display: grid;
  place-items: center;
  font-size: .7rem;
  font-weight: 800;
  flex-shrink: 0;
}
.cn-uso-page .hs-sep {
  width: 30px;
  height: 1px;
  background: var(--cn-border-soft);
  margin: 0 .2rem;
}

/* LAYOUT: SIDEBAR + MAIN */
.cn-uso-page .page-body {
  display: flex;
  align-items: flex-start;
  gap: 0;
}

/* SCROLLSPY SIDEBAR */
.cn-uso-page .spy-sidebar {
  position: sticky;
  top: 100px;
  width: 230px;
  flex-shrink: 0;
  padding: 1.6rem 0;
  display: none;
}
@media(min-width: 992px) {
  .cn-uso-page .spy-sidebar {
    display: block;
  }
}
.cn-uso-page .spy-title {
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: var(--sienna);
  margin-bottom: 1rem;
  padding-left: .8rem;
}
.cn-uso-page .spy-nav {
  list-style: none;
  padding: 0;
  margin: 0;
}
.cn-uso-page .spy-nav li a {
  display: flex;
  align-items: center;
  gap: .55rem;
  font-size: .82rem;
  font-weight: 600;
  color: var(--muted);
  padding: .52rem .8rem;
  border-radius: 8px;
  text-decoration: none;
  transition: var(--tr);
  border-left: 2px solid transparent;
}
.cn-uso-page .spy-nav li a i {
  font-size: .9rem;
  width: 16px;
  text-align: center;
  flex-shrink: 0;
}
.cn-uso-page .spy-nav li a:hover {
  color: var(--brown);
  background: var(--cn-btn-ghost-hover-bg);
}
.cn-uso-page .spy-nav li a.active {
  color: var(--brown);
  background: var(--cn-btn-ghost-hover-bg);
  border-left-color: var(--sienna);
  font-weight: 700;
}

/* MAIN CONTENT */
.cn-uso-page .page-main {
  flex: 1;
  min-width: 0;
  padding: 3rem 0 6rem;
}

/* SECTION SHARED */
.cn-uso-page .step-section {
  padding: 3.5rem 0;
  border-bottom: 1px solid var(--cn-border-soft);
  scroll-margin-top: 110px;
}
.cn-uso-page .step-section:last-child {
  border-bottom: none;
}
.cn-uso-page .step-badge {
  display: inline-flex;
  align-items: center;
  gap: .45rem;
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--sienna);
  margin-bottom: .7rem;
}
.cn-uso-page .step-num-badge {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--brown);
  color: #fff;
  display: grid;
  place-items: center;
  font-size: .65rem;
  font-weight: 800;
}
.cn-uso-page .step-title {
  font-size: clamp(1.5rem, 3vw, 2.1rem);
  font-weight: 800;
  letter-spacing: -1px;
  color: var(--ink);
  margin-bottom: .8rem;
  line-height: 1.15;
}
.cn-uso-page .step-title em {
  color: var(--sienna);
  font-style: normal;
}
.cn-uso-page .step-desc {
  font-size: .95rem;
  color: var(--muted);
  line-height: 1.72;
  margin-bottom: 1.4rem;
}

.cn-uso-page .img-container {
  border-radius: var(--r2);
  overflow: hidden;
  box-shadow: var(--sh);
}

/* Feature list */
.cn-uso-page .feat-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: .65rem;
}
.cn-uso-page .feat-list li {
  display: flex;
  align-items: flex-start;
  gap: .65rem;
  font-size: .88rem;
  font-weight: 500;
  color: var(--ink);
}
.cn-uso-page .feat-list i {
  color: var(--sienna);
  font-size: 1.05rem;
  flex-shrink: 0;
  margin-top: .1rem;
}

/* Cards */
.cn-uso-page .cn-card {
  background: var(--cn-card-bg);
  border-radius: var(--r2);
  padding: 1.8rem 1.6rem;
  border: 1px solid var(--cn-border-solid);
  box-shadow: var(--sh2);
  transition: var(--tr);
  height: 100%;
}
.cn-uso-page .cn-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--sh);
  border-color: rgba(193,127,89,.3);
}
.cn-uso-page .card-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: var(--cn-card-icon-bg);
  color: var(--brown);
  display: grid;
  place-items: center;
  font-size: 1.3rem;
  margin-bottom: 1rem;
  transition: var(--tr);
}
.cn-uso-page .cn-card:hover .card-icon {
  background: var(--brown);
  color: #fff;
  transform: rotate(-8deg);
}
.cn-uso-page .card-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--ink);
  margin-bottom: .45rem;
}
.cn-uso-page .card-text {
  font-size: .83rem;
  color: var(--muted);
  line-height: 1.62;
}



/* PASO 3 — Pomodoro */
.cn-uso-page #paso3 {
  background: var(--cn-pomo-bg) !important;
  position: relative;
  overflow: hidden;
}
.cn-uso-page #paso3::before {
  content: '';
  position: absolute;
  top: -120px;
  right: -120px;
  width: 350px;
  height: 350px;
  border-radius: 50%;
  background: radial-gradient(circle,rgba(193,127,89,.3),transparent 70%);
  filter: blur(20px);
}
.cn-uso-page #paso3 .step-badge {
  color: var(--sand);
}
.cn-uso-page #paso3 .step-title {
  color: #fff;
}
.cn-uso-page #paso3 .step-title em {
  color: var(--sand);
}
.cn-uso-page #paso3 .step-desc {
  color: rgba(255,255,255,.75);
}
.cn-uso-page #paso3 .feat-list li {
  color: rgba(255,255,255,.88);
}
.cn-uso-page #paso3 .feat-list i {
  color: var(--sand);
}
.cn-uso-page .video-frame {
  border-radius: var(--r2);
  overflow: hidden;
  box-shadow: 0 24px 60px rgba(0,0,0,.4);
  border: 1px solid rgba(255,255,255,.12);
  position: relative;
}
.cn-uso-page .video-badge {
  position: absolute;
  top: .9rem;
  left: .9rem;
  z-index: 5;
  background: rgba(28,15,14,.8);
  color: #fff;
  font-size: .72rem;
  font-weight: 700;
  padding: .35rem .8rem;
  border-radius: 50px;
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  gap: .4rem;
}
.cn-uso-page .video-badge i {
  color: #e06c5e;
  animation: blink 1.6s ease-in-out infinite;
}
@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: .25; }
}
.cn-uso-page .video-placeholder {
  aspect-ratio: 16/9;
  background: rgba(0,0,0,.35);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .8rem;
  position: relative;
}
.cn-uso-page .video-placeholder video {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.cn-uso-page .video-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 2;
  text-align: center;
}
.cn-uso-page .play-btn {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: rgba(255,255,255,.15);
  border: 2px solid rgba(255,255,255,.4);
  color: #fff;
  font-size: 1.8rem;
  display: grid;
  place-items: center;
  margin: 0 auto .8rem;
  cursor: pointer;
  transition: var(--tr);
  backdrop-filter: blur(6px);
}
.cn-uso-page .play-btn:hover {
  background: rgba(255,255,255,.25);
  transform: scale(1.08);
}
.cn-uso-page .video-caption {
  color: rgba(255,255,255,.9);
  font-size: .78rem;
  font-weight: 600;
}
.cn-uso-page .pomo-config {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .7rem;
  margin-top: 1.2rem;
}
.cn-uso-page .pomo-chip {
  background: rgba(255,255,255,.09);
  border: 1px solid rgba(255,255,255,.14);
  border-radius: 10px;
  padding: .75rem .9rem;
  display: flex;
  align-items: center;
  gap: .55rem;
}
.cn-uso-page .pomo-chip i {
  color: var(--sand);
  font-size: 1rem;
  flex-shrink: 0;
}
.cn-uso-page .pomo-chip-label {
  font-size: .72rem;
  font-weight: 600;
  color: rgba(255,255,255,.6);
  display: block;
}
.cn-uso-page .pomo-chip-val {
  font-size: .9rem;
  font-weight: 700;
  color: #fff;
}

/* PASO 4 — Apuntes */
.cn-uso-page .notes-tabs {
  display: flex;
  gap: .5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}
.cn-uso-page .note-tab {
  font-size: .8rem;
  font-weight: 700;
  padding: .5rem 1.1rem;
  border-radius: 50px;
  border: 1.5px solid var(--cn-border-solid);
  color: var(--muted);
  background: var(--cn-card-bg);
  cursor: pointer;
  transition: var(--tr);
  display: flex;
  align-items: center;
  gap: .4rem;
}
.cn-uso-page .note-tab:hover {
  border-color: var(--sienna);
  color: var(--brown);
}
.cn-uso-page .note-tab.active {
  background: var(--brown);
  color: #fff;
  border-color: var(--brown);
}
.cn-uso-page .note-panel {
  display: none;
  animation: fadeIn .35s ease;
}
.cn-uso-page .note-panel.active {
  display: block;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: none; }
}



/* CTA final */
.cn-uso-page .final-cta {
  background: var(--cn-cta-bg);
  background-image: radial-gradient(700px 400px at 90% -10%,rgba(193,127,89,.28),transparent 60%);
  padding: 5rem 0;
  text-align: center;
}
.cn-uso-page .final-cta h2 {
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  font-weight: 800;
  color: #fff;
  letter-spacing: -1px;
  margin-bottom: .9rem;
}
.cn-uso-page .final-cta p {
  color: rgba(255,255,255,.75);
  font-size: 1rem;
  margin-bottom: 2rem;
  max-width: 480px;
  margin-inline: auto;
}
.cn-uso-page .btn-final {
  background: var(--sienna);
  color: #fff !important;
  font-weight: 700;
  font-size: .95rem;
  padding: .9rem 2.2rem;
  border-radius: 50px;
  border: none;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  box-shadow: 0 8px 28px rgba(0,0,0,.25);
  transition: var(--tr);
}
.cn-uso-page .btn-final:hover {
  background: #d4956a;
  color: #fff !important;
  transform: translateY(-3px);
}
.cn-uso-page .btn-final-ghost {
  background: transparent;
  color: rgba(255,255,255,.8);
  font-weight: 700;
  font-size: .95rem;
  padding: .9rem 2.2rem;
  border-radius: 50px;
  border: 1.5px solid rgba(255,255,255,.25);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  transition: var(--tr);
}
.cn-uso-page .btn-final-ghost:hover {
  background: rgba(255,255,255,.1);
  color: #fff;
  border-color: rgba(255,255,255,.5);
}

/* FOOTER */
.cn-uso-page .cn-footer {
  background: #1c0f0e;
  padding: 2.5rem 0;
  color: rgba(255,255,255,.55);
  text-align: center;
  font-size: .8rem;
}
.cn-uso-page .cn-footer a {
  color: var(--sienna);
}

/* SCROLL REVEAL */
.cn-uso-page .reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity .6s ease, transform .6s ease;
}
.cn-uso-page .reveal.in {
  opacity: 1;
  transform: none;
}
.cn-uso-page .reveal-l {
  opacity: 0;
  transform: translateX(-24px);
  transition: opacity .6s ease, transform .6s ease;
}
.cn-uso-page .reveal-l.in {
  opacity: 1;
  transform: none;
}
.cn-uso-page .reveal-r {
  opacity: 0;
  transform: translateX(24px);
  transition: opacity .6s ease, transform .6s ease;
}
.cn-uso-page .reveal-r.in {
  opacity: 1;
  transform: none;
}
.cn-uso-page .d1 { transition-delay: .08s; }
.cn-uso-page .d2 { transition-delay: .16s; }
.cn-uso-page .d3 { transition-delay: .24s; }
.cn-uso-page .d4 { transition-delay: .32s; }

/* SCROLLBAR */
.cn-uso-page ::-webkit-scrollbar {
  width: 5px;
}
.cn-uso-page ::-webkit-scrollbar-track {
  background: var(--cream);
}
.cn-uso-page ::-webkit-scrollbar-thumb {
  background: var(--sienna);
  border-radius: 3px;
}

/* RESPONSIVE */
@media(max-width: 767px) {
  .cn-uso-page .pomo-config {
    grid-template-columns: 1fr 1fr;
  }
  .cn-uso-page .stats-row {
    gap: .5rem;
  }
}
@media(max-width: 575px) {
  .cn-uso-page .pomo-config {
    grid-template-columns: 1fr;
  }
}

/* ===== Theme Toggle Style & Dark mode tweaks ===== */
.cn-theme-toggle-btn {
  background: transparent;
  border: none;
  font-size: 1.35rem;
  padding: 6px;
  color: var(--brown);
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
  filter: brightness(0) invert(1) !important;
}

body.cn-body-dark .accordion-button::after {
  filter: invert(1) brightness(2);
}
</style>

<style>
html {
  scroll-behavior: smooth;
}
</style>