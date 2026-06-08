<script setup>
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
</script>

<template>
    <Head title="Crear Cuenta" />

    <div class="login-wrapper d-flex justify-content-center align-items-center min-vh-100 w-100">
        
        <div class="login-fondo p-5 rounded-5 shadow-lg">
            <div class="d-flex justify-content-center">
                <img src="/img/login-icon.png" alt="login-icon" style="height: 7rem;"/>
            </div>
            
            <div class="text-center fs-2 fw-bold mb-4">Crea tu cuenta</div>

            <form @submit.prevent="submit">
                
                <div class="input-group mt-3">
                    <input 
                        v-model="form.nombre" 
                        class="form-control bg-light rounded" 
                        type="text" 
                        placeholder="Nombre" 
                        required 
                        autofocus 
                        autocomplete="given-name" 
                    />
                </div>
                <div v-if="form.errors.nombre" class="text-danger mt-1 small ms-1">{{ form.errors.nombre }}</div>

                <div class="input-group mt-3">
                    <input 
                        v-model="form.apellido" 
                        class="form-control bg-light rounded" 
                        type="text" 
                        placeholder="Apellido" 
                        required 
                        autocomplete="family-name" 
                    />
                </div>
                <div v-if="form.errors.apellido" class="text-danger mt-1 small ms-1">{{ form.errors.apellido }}</div>

                <div class="input-group mt-3">
                    <div class="input-group-text border-0" style="background-color: #e9ecef;">
                        <img src="/img/email-iconpng.png" alt="email-icon" style="height: 1rem;"/>
                    </div>
                    <input 
                        v-model="form.email" 
                        class="form-control bg-light" 
                        type="email" 
                        placeholder="Email" 
                        required 
                        autocomplete="username" 
                    />
                </div>
                <div v-if="form.errors.email" class="text-danger mt-1 small ms-1">{{ form.errors.email }}</div>

                <div class="input-group mt-3">
                    <div class="input-group-text border-0" style="background-color: #e9ecef;">
                        <img src="/img/password-icon.png" alt="password-icon" style="height: 1rem;"/>
                    </div>
                    <input 
                        v-model="form.password" 
                        class="form-control bg-light" 
                        type="password" 
                        placeholder="Contraseña" 
                        required 
                        autocomplete="new-password" 
                    />
                </div>
                <div v-if="form.errors.password" class="text-danger mt-1 small ms-1">{{ form.errors.password }}</div>

                <div class="input-group mt-3">
                    <div class="input-group-text border-0" style="background-color: #e9ecef;">
                        <img src="/img/password-icon.png" alt="password-icon" style="height: 1rem;"/>
                    </div>
                    <input 
                        v-model="form.password_confirmation" 
                        class="form-control bg-light" 
                        type="password" 
                        placeholder="Confirmar Contraseña" 
                        required 
                        autocomplete="new-password" 
                    />
                </div>
                <div v-if="form.errors.password_confirmation" class="text-danger mt-1 small ms-1">{{ form.errors.password_confirmation }}</div>

                <button 
                    type="submit" 
                    class="btn btn-login text-white w-100 mt-4 fw-semibold shadow-sm"
                    :class="{ 'opacity-50': form.processing }" 
                    :disabled="form.processing"
                >
                    Registrarse
                </button>
            </form>

            <div class="position-relative my-4 d-flex align-items-center">
                <div class="flex-grow-1 border-top border-secondary opacity-50"></div>
                <span class="mx-3 text-secondary text-uppercase fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.05em;">o</span>
                <div class="flex-grow-1 border-top border-secondary opacity-50"></div>
            </div>

            <a :href="route('google.login')" class="btn btn-outline-light w-100 d-flex align-items-center justify-content-center gap-2 py-2 fw-semibold btn-google">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.5 24c0-1.61-.15-3.16-.42-4.67H24v8.83h12.7c-.55 2.87-2.18 5.3-4.63 6.94l7.18 5.57C43.43 36.41 46.5 30.73 46.5 24z"/>
                    <path fill="#FBBC05" d="M10.54 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.98-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.18-5.57c-2.11 1.41-4.8 2.25-8.71 2.25-6.26 0-11.57-4.22-13.46-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                Registrarse con Google
            </a>

            <div class="d-flex gap-1 justify-content-center mt-4">
                <div class="text-light">¿Ya tienes una cuenta?</div>
                <Link :href="route('login')" class="text-decoration-none fw-semibold link-login">
                    Iniciar sesión
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
    color: #333;
    border: none;
}

.form-control::placeholder {
    color: #aaa;
}

.link-login {
    color: #b136d9;
}

.link-login:hover {
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

.btn-google {
    background-color: transparent;
    border: 1px solid #4c3c5c;
    color: #ddd;
    transition: all 0.3s ease;
}

.btn-google:hover {
    background-color: #31304f;
    border-color: #b136d9;
    color: white;
}
</style>