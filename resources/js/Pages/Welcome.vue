<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

// Trasladamos la lógica de JavaScript / Animaciones que tenían al final de su index.php
onMounted(() => {
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
    <Head title="Sobre Nosotros - Cronos" />

    <div class="blur-container">
        <header class="header">
            <div class="nav-container">
                <div class="logo">
                    <img src="/img/logo-cronos.png" class="logo-img" alt="logo-cronos">
                </div>
                <div class="nav-buttons">
                    <Link href="/uso" class="btn btn-outline">Uso de Cronos</Link>
                    <Link :href="route('login')" class="btn btn-outline">Iniciar sesión</Link>
                    <Link :href="route('register')" class="btn btn-outline">Registrarse</Link>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="container">
                <section class="section">
                    <div class="section-content">
                        <h2>Sobre Cronos</h2>
                        <p>Cronos es un proyecto de estudiantes para estudiantes. Surge como un proyecto de la universidad de diseñar una página web completa para resolver un problema.</p>
                        <p>Como estudiantes, no encontrábamos páginas de estudio que combinen distintas técnicas de tiempo y organización. De este planteo surge Cronos.</p>
                    </div>
                    <div class="section-image">
                        <img src="https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" alt="Equipo trabajando">
                    </div>
                </section>
                <section class="section">
                    <div class="section-content">
                        <h2>Optimiza tu tiempo.</h2>
                        <p>Mediante la técnica “Pomodoro”, Cronos permite dedicarle un tiempo exclusivo a tus tareas, dando un merecido descanso al finalizarlas. </p>
                        <p>Revisa tus estadísticas, tiempo dedicado a las tareas y eficiencia.</p>
                    </div>
                    <div class="section-image">
                        <img src="https://vilmanunez.com/wp-content/uploads/2020/05/informe-investigacion-auditoria-electronica-tableta-o-persona-que-ve-dibujos-animados-planos-estadisticas-analisis_101884-755.jpg" alt="Estadísticas">
                    </div>
                </section>
                <section class="section">
                    <div class="section-content">
                        <h2>De estudiantes para estudiantes.</h2>
                        <p>Nos interesa seguir mejorando, y tomar las opiniones de las personas que utilizan nuestra plataforma.</p>
                        <p>Nuestro sistema de retroalimentación nos ayuda a escuchar y continuar mejorando, tanto en el desarrollo de esta web como en nuestro desarrollo estudiantil.</p>
                    </div>
                    <div class="section-image">
                        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=800&q=80" alt="Comunidad global">
                    </div>
                </section>
            </div>
        </main>

        <footer class="ml-footer">
            <div class="ml-grid">
                <div class="img">
                    <img src="/img/logo-cronos.png" alt="logo-cronos">
                </div>
            </div>
        </footer>
    </div>
</template>