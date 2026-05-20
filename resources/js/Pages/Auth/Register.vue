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

            <div class="d-flex gap-1 justify-content-center mt-3">
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
</style>