<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    motivo: {
        type: String,
        default: 'expirada'
    }
});
</script>

<template>
    <Head title="Invitación no válida" />

    <!-- Formas decorativas -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="shape shape-4"></div>

    <div class="container">
        <div class="form-panel text-center">
            
            <div class="avatar mb-4">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>

            <h1 class="mb-2">Enlace no válido</h1>
            <p class="text-secondary small mb-4">Cronos Notes • Espacios Compartidos</p>
            
            <div class="invitation-card mb-4 p-4 text-center border rounded-3 bg-light">
                <p class="mb-0 text-dark" style="font-size: 15px; line-height: 1.6;">
                    <span v-if="motivo === 'utilizada'">
                        Esta invitación ya ha sido procesada o aceptada previamente por otro usuario.
                    </span>
                    <span v-else>
                        Esta invitación ha expirado o ya no se encuentra disponible. Recuerda que los enlaces de invitación expiran después de 7 días.
                    </span>
                </p>
            </div>

            <div class="d-flex flex-column gap-2">
                <Link 
                    :href="route('dashboard')" 
                    class="btn-dashboard"
                >
                    Ir al Dashboard
                </Link>
            </div>

        </div>
    </div>
</template>

<style>
/* Quitamos scope para que el body en el auth tenga este fondo y se reseten margenes */
html, body {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
    min-height: 100vh;
    background-color: #e8e5e1;
}

#app {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    width: 100%;
}
</style>

<style scoped>
/* Formas decorativas */
.shape {
    position: absolute;
    border-radius: 50%;
    z-index: 0;
}

.shape-1 {
    width: 350px;
    height: 350px;
    background-color: #a67c6d;
    opacity: 0.5;
    bottom: -100px;
    left: -100px;
}

.shape-2 {
    width: 200px;
    height: 200px;
    background-color: #a67c6d;
    bottom: -50px;
    right: 50px;
}

.shape-3 {
    width: 250px;
    height: 250px;
    background-color: #d4c4bc;
    opacity: 0.6;
    bottom: 50px;
    right: -80px;
}

.shape-4 {
    width: 180px;
    height: 180px;
    background-color: #c9b8ae;
    opacity: 0.4;
    top: -50px;
    right: 200px;
}

/* Contenedor principal */
.container {
    display: flex;
    background-color: white;
    border-radius: 20px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    max-width: 480px;
    width: 100%;
    z-index: 1;
    position: relative;
}

/* Panel del formulario */
.form-panel {
    flex: 1;
    padding: 45px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* Estilos para el Avatar */
.avatar {
    width: 85px;
    height: 85px;
    background-color: #dc3545;
    border-radius: 50%;
    margin: 0 auto 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar svg {
    width: 45px;
    height: 45px;
    color: white;
}

h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
    text-align: center;
}

.btn-dashboard {
    width: 100%;
    padding: 12px;
    background-color: #69342e;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.2s ease, transform 0.1s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-dashboard:hover {
    background-color: #542924;
}

.btn-dashboard:active {
    transform: scale(0.98);
}
</style>
