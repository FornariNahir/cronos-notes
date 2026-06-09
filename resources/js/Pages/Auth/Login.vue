<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const setFocus = (event) => {
    event.target.parentElement.querySelector('.icon').style.color = '#8b5a4b';
};

const removeFocus = (event) => {
    event.target.parentElement.querySelector('.icon').style.color = '#888';
};
</script>

<template>
    <Head title="Inicia sesión" />

    <!-- Formas decorativas -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="shape shape-4"></div>

    <div class="container">
        
        <div class="illustration-panel">
            <img src="/img/login.png" alt="Ilustración Login">
        </div>

        <div class="form-panel">
            
            <div class="avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>

            <h1>Inicia sesión</h1>
            
            <div v-if="status" class="status-msg">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div class="input-group" :style="{ marginBottom: form.errors.email ? '5px' : '18px' }">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input 
                        type="email" 
                        v-model="form.email" 
                        placeholder="Correo electrónico" 
                        required
                        autofocus
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

                <div class="options-group">
                    <label class="remember-me">
                        <input type="checkbox" v-model="form.remember">
                        <span>Recordarme</span>
                    </label>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="forgot-pwd">
                        ¿Olvidaste tu contraseña?
                    </Link>
                </div>

                <button 
                    type="submit" 
                    class="btn-login"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    Ingresar
                </button>
            </form>

            <p class="login-link">
                ¿No tienes una cuenta? <Link :href="route('register')">Regístrate acá</Link>
            </p>

            <a :href="route('google.login')" class="btn-google">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48" class="google-icon">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.5 24c0-1.61-.15-3.16-.42-4.67H24v8.83h12.7c-.55 2.87-2.18 5.3-4.63 6.94l7.18 5.57C43.43 36.41 46.5 30.73 46.5 24z"/>
                    <path fill="#FBBC05" d="M10.54 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.98-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.18-5.57c-2.11 1.41-4.8 2.25-8.71 2.25-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                Continuar con Google
            </a>

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
    max-width: 900px;
    width: 100%;
    z-index: 1;
    position: relative;
}

/* Panel del formulario */
.form-panel {
    flex: 1;
    padding: 40px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* Estilos para el Avatar */
.avatar {
    width: 85px;
    height: 85px;
    background-color: #7b413f;
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

.form-panel h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    text-align: center;
    margin-bottom: 30px;
}

.status-msg {
    color: #10b981;
    font-size: 14px;
    text-align: center;
    margin-bottom: 20px;
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

.options-group {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 14px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    cursor: pointer;
}

.forgot-pwd {
    color: #8b5a4b;
    text-decoration: none;
    font-weight: 500;
}

.forgot-pwd:hover {
    text-decoration: underline;
}

.btn-login {
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

.btn-login:hover {
    background-color: #724a3d;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 90, 75, 0.3);
}

.btn-login:active {
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
    width: 420px;
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
    max-width: 350px;
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
</style>