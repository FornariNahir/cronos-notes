<template>
  <div class="flex-1 overflow-auto p-4 sm:p-6" :style="{ fontFamily: font }">
    <template v-if="cornellMode">
      <div class="mx-auto grid max-w-4xl grid-cols-1 gap-4 md:grid-cols-[1fr_2fr]">
        <div class="flex flex-col gap-2">
          <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Palabras clave</span>
          <div
            :contenteditable="isReadOnly ? 'false' : 'true'"
            data-placeholder="Ideas y conceptos clave..."
            class="editor-area rounded-md border border-border bg-card p-3 text-sm leading-relaxed text-foreground outline-none focus:ring-2 focus:ring-ring min-h-[200px] md:min-h-[480px]"
            @input="handleIdeasInput"
            ref="ideasEditor"
          ></div>
        </div>
        <div class="flex flex-col gap-2">
          <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Notas</span>
          <div
            :contenteditable="isReadOnly ? 'false' : 'true'"
            data-placeholder="Empieza a escribir aquí..."
            class="editor-area rounded-md border border-border bg-card p-3 text-sm leading-relaxed text-foreground outline-none focus:ring-2 focus:ring-ring min-h-[200px] md:min-h-[480px]"
            @input="handleInput"
            ref="mainEditor"
          ></div>
        </div>
        <div class="md:col-span-2 flex flex-col gap-2">
          <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Resumen</span>
          <div
            :contenteditable="isReadOnly ? 'false' : 'true'"
            data-placeholder="Resumen de la sesión..."
            class="editor-area rounded-md border border-border bg-card p-3 text-sm leading-relaxed text-foreground outline-none focus:ring-2 focus:ring-ring min-h-[120px]"
            @input="handleResumenInput"
            ref="resumenEditor"
          ></div>
        </div>
      </div>
    </template>
    <template v-else>
      <div
        :contenteditable="isReadOnly ? 'false' : 'true'"
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
  modelValue: String,
  ideas: String,
  resumen: String,
  isReadOnly: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'update:ideas', 'update:resumen'])

const mainEditor = ref(null)
const ideasEditor = ref(null)
const resumenEditor = ref(null)

const handleInput = (e) => {
  emit('update:modelValue', e.target.innerHTML)
}

const handleIdeasInput = (e) => {
  emit('update:ideas', e.target.innerHTML)
}

const handleResumenInput = (e) => {
  emit('update:resumen', e.target.innerHTML)
}

watch(() => props.modelValue, (newVal) => {
  if (mainEditor.value && mainEditor.value.innerHTML !== newVal) {
    mainEditor.value.innerHTML = newVal || ''
  }
})

watch(() => props.ideas, (newVal) => {
  if (ideasEditor.value && ideasEditor.value.innerHTML !== newVal) {
    ideasEditor.value.innerHTML = newVal || ''
  }
})

watch(() => props.resumen, (newVal) => {
  if (resumenEditor.value && resumenEditor.value.innerHTML !== newVal) {
    resumenEditor.value.innerHTML = newVal || ''
  }
})

watch(() => props.cornellMode, () => {
  nextTick(() => {
    if (mainEditor.value) {
      mainEditor.value.innerHTML = props.modelValue || ''
    }
    if (ideasEditor.value) {
      ideasEditor.value.innerHTML = props.ideas || ''
    }
    if (resumenEditor.value) {
      resumenEditor.value.innerHTML = props.resumen || ''
    }
  })
})

onMounted(() => {
  if (mainEditor.value) {
    mainEditor.value.innerHTML = props.modelValue || ''
  }
  if (ideasEditor.value) {
    ideasEditor.value.innerHTML = props.ideas || ''
  }
  if (resumenEditor.value) {
    resumenEditor.value.innerHTML = props.resumen || ''
  }
})
</script>
