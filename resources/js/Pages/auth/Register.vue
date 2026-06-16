<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    nombre: '',
    apellido: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const setFocus = (event) => {
    const color = localStorage.getItem('cn-theme') === 'dark' ? '#e38e76' : '#8b5a4b';
    event.target.parentElement.querySelector('.icon').style.color = color;
};

const removeFocus = (event) => {
    event.target.parentElement.querySelector('.icon').style.color = '#888';
};

onMounted(() => {
    const isDark = localStorage.getItem('cn-theme') === 'dark';
    if (isDark) {
        document.body.classList.add('cn-body-dark');
    } else {
        document.body.classList.add('cn-body-light');
    }
});

onUnmounted(() => {
    document.body.classList.remove('cn-body-light');
    document.body.classList.remove('cn-body-dark');
});
</script>

<template>
    <Head title="Crea tu cuenta" />

    <div class="cn-auth-wrapper">
        <!-- Formas decorativas -->
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>

        <!-- Contenedor principal -->
        <div class="container">
            <!-- Panel del formulario -->
            <div class="form-panel">
                <h1>Crea tu cuenta</h1>
                
                <form @submit.prevent="submit">
                    <div class="input-group" :style="{ marginBottom: form.errors.nombre ? '5px' : '18px' }">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <input 
                            type="text" 
                            v-model="form.nombre" 
                            placeholder="Nombre" 
                            required
                            @focus="setFocus"
                            @blur="removeFocus"
                        >
                    </div>
                    <div v-if="form.errors.nombre" class="error-msg">{{ form.errors.nombre }}</div>

                    <div class="input-group" :style="{ marginBottom: form.errors.apellido ? '5px' : '18px' }">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <input 
                            type="text" 
                            v-model="form.apellido" 
                            placeholder="Apellido" 
                            required
                            @focus="setFocus"
                            @blur="removeFocus"
                        >
                    </div>
                    <div v-if="form.errors.apellido" class="error-msg">{{ form.errors.apellido }}</div>

                    <div class="input-group" :style="{ marginBottom: form.errors.email ? '5px' : '18px' }">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input 
                            type="email" 
                            v-model="form.email" 
                            placeholder="Correo electrónico" 
                            required
                            @focus="setFocus"
                            @blur="removeFocus"
                        >
                    </div>
                    <div v-if="form.errors.email" class="error-msg">{{ form.errors.email }}</div>

                    <div class="input-group" :style="{ marginBottom: form.errors.password ? '5px' : '18px' }">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input 
                            type="password" 
                            v-model="form.password" 
                            placeholder="Contraseña" 
                            required
                            @focus="setFocus"
                            @blur="removeFocus"
                        >
                    </div>
                    <div v-if="form.errors.password" class="error-msg">{{ form.errors.password }}</div>

                    <div class="input-group" :style="{ marginBottom: form.errors.password_confirmation ? '5px' : '18px' }">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input 
                            type="password" 
                            v-model="form.password_confirmation" 
                            placeholder="Confirmar contraseña" 
                            required
                            @focus="setFocus"
                            @blur="removeFocus"
                        >
                    </div>
                    <div v-if="form.errors.password_confirmation" class="error-msg">{{ form.errors.password_confirmation }}</div>

                    <button 
                        type="submit" 
                        class="btn-login"
                        :class="{ 'opacity-50': form.processing }"
                        :disabled="form.processing"
                    >
                        Registrarse
                    </button>
                </form>

                <p class="login-link">
                    ¿Ya tienes cuenta? <Link :href="route('login')">Inicia sesión acá</Link>
                </p>

                <a :href="route('google.login')" class="btn-google">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" class="google-icon">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.5 24c0-1.61-.15-3.16-.42-4.67H24v8.83h12.7c-.55 2.87-2.18 5.3-4.63 6.94l7.18 5.57C43.43 36.41 46.5 30.73 46.5 24z"/>
                        <path fill="#FBBC05" d="M10.54 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.98-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.18-5.57c-2.11 1.41-4.8 2.25-8.71 2.25-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    Registrarse con Google
                </a>
            </div>

            <!-- Panel de ilustración -->
            <div class="illustration-panel">
                <img src="/img/registro.png" alt="Registro">
            </div>
        </div>
    </div>
</template>

<style>
/* Estilos del wrapper exclusivo de autenticación para evitar leaks globales */
.cn-auth-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    width: 100%;
    background-color: #e8e5e1;
    transition: background-color 0.3s ease;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

body.cn-body-dark .cn-auth-wrapper {
    background-color: #612c2d !important;
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
    max-width: 900px;
    width: 100%;
    z-index: 1;
    position: relative;
}

/* Panel del formulario */
.form-panel {
    flex: 1;
    padding: 50px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.form-panel h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    text-align: center;
    margin-bottom: 35px;
}

.input-group {
    width: 100%;
    position: relative;
}

.input-group input {
    width: 100%;
    padding: 16px 16px 16px 50px;
    border: none;
    background-color: #f3f0ed;
    border-radius: 10px;
    font-size: 15px;
    color: #333;
    transition: all 0.3s ease;
}

.input-group input::placeholder {
    color: #999;
}

.input-group input:focus {
    outline: none;
    background-color: #ebe7e3;
    box-shadow: 0 0 0 2px rgba(139, 90, 75, 0.2);
}

.input-group .icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: #888;
    transition: color 0.3s ease;
}

.error-msg {
    color: #ef4444;
    font-size: 12px;
    margin-bottom: 12px;
    margin-left: 4px;
}

.btn-register {
    width: 100%;
    padding: 16px;
    background-color: #7b413f;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.btn-register:hover {
    background-color: #724a3d;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 90, 75, 0.3);
}

.btn-register:active {
    transform: translateY(0);
}

.login-link {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #666;
}

.login-link a {
    color: #612c2d;
    text-decoration: none;
    font-weight: 500;
}

.login-link a:hover {
    text-decoration: underline;
}

.btn-google {
    width: 100%;
    padding: 14px;
    background-color: transparent;
    color: #555;
    border: 1px solid #d4c4bc;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    text-decoration: none;
}

.btn-google:hover {
    background-color: #f3f0ed;
    border-color: #7b413f;
}

.google-icon {
    width: 18px;
    height: 18px;
}

/* Panel de ilustración */
.illustration-panel {
    width: 380px;
    background-color: #7b413f;
    border-radius: 20px;
    margin: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px;
}

.illustration-panel img {
    width: 100%;
    height: auto;
    max-width: 320px;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .illustration-panel {
        width: auto;
        min-height: 250px;
        order: -1;
        margin: 15px 15px 0 15px;
    }

    .form-panel {
        padding: 30px 25px;
    }
}

/* Animación de entrada */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.container {
    animation: fadeIn 0.6s ease-out;
}

/* Dark Mode Overrides */
body.cn-body-dark .shape-1 {
    background-color: #4c2521;
    opacity: 0.8;
}

body.cn-body-dark .shape-2 {
    background-color: #7b413f;
    opacity: 0.4;
}

body.cn-body-dark .shape-3 {
    background-color: #7b413f;
    opacity: 0.3;
}

body.cn-body-dark .shape-4 {
    background-color: #8a4a3f;
    opacity: 0.2;
}

body.cn-body-dark .container {
    background-color: rgba(76, 37, 33, 0.75);
    border: 1px solid rgba(244, 190, 149, 0.15);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

body.cn-body-dark .form-panel h1 {
    color: #ffffff;
}

body.cn-body-dark .input-group input {
    background-color: #f3f0ed;
    color: #2b211f;
}

body.cn-body-dark .input-group input:focus {
    background-color: #ffffff;
    box-shadow: 0 0 0 2px rgba(227, 142, 118, 0.4);
}

body.cn-body-dark .btn-register {
    background-color: #e38e76;
    color: #ffffff;
}

body.cn-body-dark .btn-register:hover {
    background-color: #a55e57;
    box-shadow: 0 4px 12px rgba(227, 142, 118, 0.3);
}

body.cn-body-dark .login-link {
    color: #fcd5b8;
}

body.cn-body-dark .login-link a {
    color: #ffffff;
    text-decoration: underline;
    font-weight: 700;
}

body.cn-body-dark .login-link a:hover {
    color: #e38e76;
}

body.cn-body-dark .btn-google {
    color: #ffffff;
    border: 1px solid rgba(244, 190, 149, 0.3);
}

body.cn-body-dark .btn-google:hover {
    background-color: rgba(244, 190, 149, 0.1);
    border-color: #e38e76;
}

body.cn-body-dark .illustration-panel {
    background-color: #e38e76;
}
</style>