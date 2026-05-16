<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="bg-[#3c3c5a] border-none text-white focus:border-[#b136d9] focus:ring focus:ring-[#b136d9] rounded-md shadow-sm"
        v-model="model"
        ref="input"
    />
</template>
