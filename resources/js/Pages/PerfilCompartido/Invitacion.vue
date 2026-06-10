<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    invitacion: {
        type: Object,
        required: true
    }
});

const procesando = ref(false);

const aceptar = () => {
    procesando.value = true;
    router.post(route('invitacion.aceptar', props.invitacion.token), {}, {
        onFinish: () => procesando.value = false
    });
};

const rechazar = () => {
    if (confirm('¿Estás seguro de rechazar esta invitación?')) {
        procesando.value = true;
        router.post(route('invitacion.rechazar', props.invitacion.token), {}, {
            onFinish: () => procesando.value = false
        });
    }
};
</script>

<template>
    <Head title="Invitación a Perfil Compartido" />

    <!-- Formas decorativas -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="shape shape-4"></div>

    <div class="container">
        <div class="form-panel text-center">
            
            <div class="avatar mb-4">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>

            <h1 class="mb-2">¡Te invitaron a colaborar!</h1>
            <p class="text-secondary small mb-4">Cronos Notes • Espacios Compartidos</p>
            
            <div class="invitation-card mb-4 p-4 text-start border rounded-3 bg-light">
                <p class="mb-3 lead text-dark" style="font-size: 16px; line-height: 1.6;">
                    <strong>{{ invitacion.invitadoPor }}</strong> te ha invitado a unirte a su perfil de trabajo 
                    <strong class="text-marron-brand">"{{ invitacion.perfil }}"</strong>.
                </p>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="text-secondary small fw-medium">Rol asignado:</span>
                    <span class="badge bg-marron-brand px-3 py-1.5" style="font-size: 12px; font-weight: 600;">
                        {{ invitacion.permiso }}
                    </span>
                </div>
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle me-1"></i> Al aceptar, podrás acceder a este espacio de trabajo y colaborar según los permisos asignados.
                </p>
            </div>

            <div class="d-flex flex-column gap-2.5">
                <button 
                    @click="aceptar" 
                    class="btn-accept" 
                    :disabled="procesando"
                >
                    <i class="bi bi-check2-circle me-1.5"></i>
                    {{ procesando ? 'Procesando...' : 'Aceptar Invitación' }}
                </button>
                <button 
                    @click="rechazar" 
                    class="btn-reject"
                    :disabled="procesando"
                >
                    Rechazar
                </button>
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
    background-color: #69342e;
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

.text-marron-brand {
    color: #69342e;
}

.badge-marron-brand {
    background-color: #69342e;
}

.bg-marron-brand {
    background-color: #69342e !important;
    color: white !important;
}

.btn-accept {
    width: 100%;
    padding: 12px;
    background-color: #69342e;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.1s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-accept:hover {
    background-color: #542924;
}

.btn-accept:active {
    transform: scale(0.98);
}

.btn-accept:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-reject {
    width: 100%;
    padding: 10px;
    background-color: transparent;
    color: #dc3545;
    border: 1px solid #f5c2c7;
    border-radius: 8px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.btn-reject:hover {
    background-color: #fff2f2;
}

.btn-reject:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>
