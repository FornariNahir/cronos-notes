<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-b from-[#4c1352] to-[#1f1e31]"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div
           class="w-full sm:max-w-md mt-6 px-6 py-4 bg-[#26253d] shadow-2xl overflow-hidden sm:rounded-3xl text-gray-300"
        >
            <slot />
        </div>
    </div>
</template>
