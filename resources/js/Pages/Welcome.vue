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
                    <img src="/img/logo-cronos.png" class="logo-img" alt="Logo Cronos">
                </div>
                <div class="nav-buttons">
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

