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
</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="login-wrapper d-flex justify-content-center align-items-center min-vh-100 w-100">
        
        <div class="login-fondo p-5 rounded-5 shadow-lg">
            <div class="d-flex justify-content-center">
                <img src="/img/login-icon.png" alt="login-icon" style="height: 7rem;"/>
            </div>
            
            <div class="text-center fs-1 fw-bold mb-4">Iniciar Sesión</div>

            <div v-if="status" class="mb-4 font-medium text-sm text-success text-center">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                
                <div class="input-group mt-4">
                    <div class="input-group-text">
                        <img src="/img/email-iconpng.png" alt="email-icon" style="height: 1rem;"/>
                    </div>
                    <input 
                        v-model="form.email" 
                        class="form-control bg-light" 
                        type="email" 
                        placeholder="Email" 
                        required 
                        autofocus 
                        autocomplete="username" 
                    />
                </div>
                <div v-if="form.errors.email" class="text-danger mt-1 small ms-1">{{ form.errors.email }}</div>

                <div class="input-group mt-3">
                    <div class="input-group-text">
                        <img src="/img/password-icon.png" alt="password-icon" style="height: 1rem;"/>
                    </div>
                    <input 
                        v-model="form.password" 
                        class="form-control bg-light" 
                        type="password" 
                        placeholder="Password" 
                        required 
                        autocomplete="current-password" 
                    />
                </div>
                <div v-if="form.errors.password" class="text-danger mt-1 small ms-1">{{ form.errors.password }}</div>

                <div class="d-flex justify-content-between align-items-center mt-3 px-1">
                    <div class="d-flex align-items-center gap-2">
                        <input class="form-check-input mt-0" type="checkbox" v-model="form.remember" id="remember_me" />
                        <label class="mb-0 text-light" style="font-size: 0.85rem;" for="remember_me">Recordarme</label>
                    </div>
                    <div>
                        <Link v-if="canResetPassword" :href="route('password.request')" class="text-decoration-none fw-semibold fst-italic link-recuperar" style="font-size: 0.85rem;">
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="btn btn-login text-white w-100 mt-4 fw-semibold shadow-sm"
                    :class="{ 'opacity-50': form.processing }" 
                    :disabled="form.processing"
                >
                    Ingresar
                </button>
            </form>

            <div class="d-flex gap-1 justify-content-center mt-3">
                <div class="text-light">¿No tienes una cuenta?</div>
                <Link :href="route('register')" class="text-decoration-none fw-semibold link-registro">
                    Registrarme
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-wrapper {
    background: linear-gradient(to bottom, #4c1352, #1f1e31);
    color: white;
}

.login-fondo {
    background-color: #26253d;
    color: #ddd;
    border-radius: 1rem;
    padding: 2rem;
    width: 25rem;
}

.form-control {
    background-color: #3c3c5a;
    color: #333; /* El texto ingresado será oscuro ya que el fondo es bg-light */
    border: none;
}

.form-control::placeholder {
    color: #aaa;
}

.link-registro, .link-recuperar {
    color: #b136d9;
}

.link-registro:hover, .link-recuperar:hover {
    color: #d156f9;
}

.btn-login {
    background-color: #b136d9;
    border: none;
    color: white;
}

.btn-login:hover {
    background-color: #9226b5;
}
</style>