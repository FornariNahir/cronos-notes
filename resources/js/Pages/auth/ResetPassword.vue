<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
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
    <Head title="Restablecer contraseña" />

    <!-- Formas decorativas -->
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
    <div class="shape shape-4"></div>

    <div class="container">
        <div class="illustration-panel">
            <img src="/img/registro.png" alt="Ilustración Restablecer Contraseña">
        </div>

        <div class="form-panel">
            <div class="avatar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>

            <h1>Restablecer contraseña</h1>

            <form @submit.prevent="submit">
                <!-- Email (Read Only) -->
                <div class="input-group opacity-60" style="margin-bottom: 18px;">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="4"/>
                        <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94"/>
                    </svg>
                    <input 
                        type="email" 
                        v-model="form.email" 
                        placeholder="Correo electrónico" 
                        required
                        readonly
                        style="cursor: not-allowed; background-color: #e5e2de;"
                    >
                </div>
                <div v-if="form.errors.email" class="error-msg">{{ form.errors.email }}</div>

                <!-- Password -->
                <div class="input-group" :style="{ marginBottom: form.errors.password ? '5px' : '18px' }">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input 
                        type="password" 
                        v-model="form.password" 
                        placeholder="Nueva contraseña" 
                        required
                        autofocus
                        @focus="setFocus"
                        @blur="removeFocus"
                    >
                </div>
                <div v-if="form.errors.password" class="error-msg">{{ form.errors.password }}</div>

                <!-- Password Confirmation -->
                <div class="input-group" :style="{ marginBottom: form.errors.password_confirmation ? '5px' : '18px' }">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input 
                        type="password" 
                        v-model="form.password_confirmation" 
                        placeholder="Confirmar nueva contraseña" 
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
                    Restablecer contraseña
                </button>
            </form>
        </div>
    </div>
</template>

<style>
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

.form-panel {
    flex: 1;
    padding: 40px 50px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

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

.container {
    animation: fadeIn 0.6s ease-out;
}

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
</style>
