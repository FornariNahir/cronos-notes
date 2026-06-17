<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

// Trasladamos la lógica de JavaScript y animaciones de scroll que tenías en uso.php
onMounted(() => {
    // Configuración del observador para la entrada suave de las secciones
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.section').forEach(section => {
        observer.observe(section);
    });

    // Efecto Parallax suave en las imágenes al hacer scroll
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const sections = document.querySelectorAll('.section-image');

        sections.forEach(section => {
            const rate = scrolled * -0.1;
            section.style.transform = `translateY(${rate}px)`;
        });
    });
});
</script>

<template>
    <Head title="Uso de la página - Cronos" />

    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <img src="/imagenes/logo-cronos.png" class="logo-img" alt="logo-cronos">
            </div>
            <div class="nav-buttons">
                <Link href="/" class="btn btn-outline">Página Principal</Link>
                <Link :href="route('login')" class="btn btn-outline">Iniciar sesión</Link>
                <Link :href="route('register')" class="btn btn-outline">Registrarse</Link>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <section class="section">
                <div class="section-content">
                    <h2>Creá los perfiles que necesites.</h2>
                    <p>Pensamos en los perfiles como distintos espacios de trabajo: colocá tus materias, proyectos personales o trabajos.</p>
                </div>
                <div class="section-image">
                    <img src="/imagenes/perfiles_uso.png" alt="Perfiles">
                </div>
            </section>
            <section class="section">
                <div class="section-content">
                    <h2>Organizá las tareas de cada perfil.</h2>
                    <p>Cada perfil tiene su propia lista de tareas. Agregá, editá y marcá como completadas las actividades para mantenerte al día. Así, todo queda ordenado y enfocado en lo que necesitás.</p>
                </div>
                <div class="section-image">
                    <img src="/imagenes/tareas_uso.png" alt="Tareas">
                </div>
            </section>
            <section class="section">
                <div class="section-content">
                    <h2>Dedicale el tiempo necesario a tus tareas.</h2>
                    <p>A través del método Pomodoro, concentrate en una sola tarea.</p>
                    <p>Podés crear tus propias configuraciones, eligiendo los tiempos que se adapten a vos.</p>
                </div>
                <div class="section-image">
                    <img src="/imagenes/sesiones.png" alt="Sesiones">
                </div>
            </section>
            <section class="section">
                <div class="section-content">
                    <h2>Revisá tu productividad en las estadísticas.</h2>
                    <p>Visualizá tu progreso con gráficos y datos útiles. Conocé cuántas tareas completaste, tu nivel de actividad y cómo usás tu tiempo en cada perfil.</p>
                </div>
                <div class="section-image">
                    <img src="/imagenes/estadísticas.png" alt="Perfiles">
                </div>
            </section>
            <section class="section">
                <div class="section-content">
                    <h2>¡Cuidá tu racha!</h2>
                    <p>Entrá todos los días para no perder el ritmo. Cada día activo suma a tu racha y te motiva a seguir avanzando.</p>
                </div>
                <div class="section-image">
                    <img src="/imagenes/racha.png" alt="Racha">
                </div>
            </section>
        </div>
    </main>

    <footer class="ml-footer">
        <div class="ml-grid">
            <div class="img">
                <img src="/imagenes/logo-cronos.png" class="logo-img" alt="logo-cronos">
            </div>
        </div>
    </footer>
</template>

<style scoped>
@import '/resources/css/uso/uso.css';
</style>