<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '', // Mapeado como Nombre de Usuario
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
    <GuestLayout>
        <Head title="Crea tu cuenta" />

        <div class="flex flex-col items-center justify-center mb-6">
            <img src="/img/login-icon.png" alt="login-icon" class="h-28 w-28 object-contain mb-2" />
            <h1 class="text-3xl font-bold text-white tracking-wide">Crea tu cuenta</h1>
        </div>

        <form @submit.prevent="submit">
            <div>
                <div class="flex items-center bg-[#3c3c5a] rounded-lg overflow-hidden shadow-inner focus-within:ring-2 focus-within:ring-[#b136d9] transition">
                    <div class="px-3 py-2 bg-[#2d2d44] border-r border-[#4e4e6a]">
                        <img src="/img/email-icon.png" alt="email-icon" class="h-4 w-4" />
                    </div>
                    <TextInput
                        id="email"
                        type="email"
                        class="w-full !bg-transparent border-none text-white focus:ring-0 placeholder-gray-400"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="Email"
                    />
                </div>
                <InputError class="mt-2 text-red-400" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <div class="flex items-center bg-[#3c3c5a] rounded-lg overflow-hidden shadow-inner focus-within:ring-2 focus-within:ring-[#b136d9] transition">
                    <div class="px-3 py-2 bg-[#2d2d44] border-r border-[#4e4e6a]">
                        <img src="/img/email-icon.png" alt="user-icon" class="h-4 w-4" />
                    </div>
                    <TextInput
                        id="name"
                        type="text"
                        class="w-full !bg-transparent border-none text-white focus:ring-0 placeholder-gray-400"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Nombre de usuario"
                    />
                </div>
                <InputError class="mt-2 text-red-400" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <div class="flex items-center bg-[#3c3c5a] rounded-lg overflow-hidden shadow-inner focus-within:ring-2 focus-within:ring-[#b136d9] transition">
                    <div class="px-3 py-2 bg-[#2d2d44] border-r border-[#4e4e6a]">
                        <img src="/img/password-icon.png" alt="password-icon" class="h-4 w-4" />
                    </div>
                    <TextInput
                        id="password"
                        type="password"
                        class="w-full !bg-transparent border-none text-white focus:ring-0 placeholder-gray-400"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Contraseña"
                    />
                </div>
                <InputError class="mt-2 text-red-400" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <div class="flex items-center bg-[#3c3c5a] rounded-lg overflow-hidden shadow-inner focus-within:ring-2 focus-within:ring-[#b136d9] transition">
                    <div class="px-3 py-2 bg-[#2d2d44] border-r border-[#4e4e6a]">
                        <img src="/img/password-icon.png" alt="password-icon" class="h-4 w-4" />
                    </div>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="w-full !bg-transparent border-none text-white focus:ring-0 placeholder-gray-400"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Confirmar contraseña"
                    />
                </div>
                <InputError class="mt-2 text-red-400" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-6">
                <button
                    type="submit"
                    class="w-full py-2.5 px-4 bg-gradient-to-r from-[#b136d9] to-[#8022a3] text-white font-semibold rounded-lg shadow-md hover:from-[#c54be8] hover:to-[#932cb8] focus:outline-none focus:ring-2 focus:ring-[#b136d9] focus:ring-offset-2 focus:ring-offset-[#26253d] transition-all duration-200"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Registrarse
                </button>
            </div>
        </form>

        <div class="flex gap-1 justify-center mt-6 text-sm text-gray-300">
            <div>¿Ya tienes una cuenta?</div>
            <Link :href="route('login')" class="text-[#b136d9] font-semibold hover:underline">
                Iniciar sesión
            </Link>
        </div>
    </GuestLayout>
</template>