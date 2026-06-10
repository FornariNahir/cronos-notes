<template>
  <div class="flex-1 overflow-auto p-4 sm:p-6" :style="{ fontFamily: font }">
    <template v-if="cornellMode">
      <div class="mx-auto grid max-w-4xl grid-cols-1 gap-4 md:grid-cols-[1fr_2fr]">
        <div class="flex flex-col gap-2">
          <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Palabras clave</span>
          <div
            contenteditable="true"
            data-placeholder="Ideas y conceptos clave..."
            class="editor-area rounded-md border border-border bg-card p-3 text-sm leading-relaxed text-foreground outline-none focus:ring-2 focus:ring-ring min-h-[200px] md:min-h-[480px]"
          ></div>
        </div>
        <div class="flex flex-col gap-2">
          <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Notas</span>
          <div
            contenteditable="true"
            data-placeholder="Empieza a escribir aquí..."
            class="editor-area rounded-md border border-border bg-card p-3 text-sm leading-relaxed text-foreground outline-none focus:ring-2 focus:ring-ring min-h-[200px] md:min-h-[480px]"
            @input="handleInput"
            ref="mainEditor"
          ></div>
        </div>
        <div class="md:col-span-2 flex flex-col gap-2">
          <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Resumen</span>
          <div
            contenteditable="true"
            data-placeholder="Resumen de la sesión..."
            class="editor-area rounded-md border border-border bg-card p-3 text-sm leading-relaxed text-foreground outline-none focus:ring-2 focus:ring-ring min-h-[120px]"
          ></div>
        </div>
      </div>
    </template>
    <template v-else>
      <div
        contenteditable="true"
        data-placeholder="Empieza a escribir aquí..."
        class="editor-area mx-auto min-h-[60vh] max-w-4xl rounded-md text-sm leading-relaxed text-foreground outline-none p-4"
        @input="handleInput"
        ref="mainEditor"
      ></div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'

const props = defineProps({
  cornellMode: Boolean,
  font: String,
  modelValue: String
})

const emit = defineEmits(['update:modelValue'])

const mainEditor = ref(null)

const handleInput = (e) => {
  emit('update:modelValue', e.target.innerHTML)
}

watch(() => props.modelValue, (newVal) => {
  if (mainEditor.value && mainEditor.value.innerHTML !== newVal) {
    mainEditor.value.innerHTML = newVal || ''
  }
})

watch(() => props.cornellMode, () => {
  nextTick(() => {
    if (mainEditor.value) {
      mainEditor.value.innerHTML = props.modelValue || ''
    }
  })
})

onMounted(() => {
  if (mainEditor.value) {
    mainEditor.value.innerHTML = props.modelValue || ''
  }
})
</script>
