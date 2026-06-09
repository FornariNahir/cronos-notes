<template>
  <div class="flex min-h-screen flex-col bg-background text-foreground">
    <!-- Header -->
    <header class="flex items-center justify-between gap-4 border-b border-border px-4 py-4 sm:px-6 bg-card text-card-foreground">
      <div class="flex min-w-0 items-center gap-3 w-1/2">
        <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
          <FileText class="size-5" />
        </span>
        <input
          type="text"
          v-model="form.tituloApunte"
          class="truncate text-base font-semibold text-primary sm:text-lg bg-transparent border-none outline-none focus:ring-0 p-0 w-full"
          placeholder="Título del apunte..."
          required
        />
      </div>
      <div class="flex shrink-0 items-center gap-3 sm:gap-4">
        <button
          type="button"
          @click="saveNote"
          :disabled="form.processing"
          class="bg-primary text-primary-foreground px-4 py-2 rounded-md font-medium text-sm transition-colors hover:bg-primary/90"
        >
          {{ form.processing ? 'Guardando...' : 'Guardar' }}
        </button>
        <button
          type="button"
          aria-label="Configuración"
          class="text-muted-foreground transition-colors hover:text-foreground"
        >
          <Settings class="size-5" />
        </button>
        <button
          type="button"
          aria-label="Notificaciones"
          class="text-muted-foreground transition-colors hover:text-foreground"
        >
          <Bell class="size-5" />
        </button>
        <div class="size-9 rounded-full bg-secondary text-secondary-foreground flex items-center justify-center font-bold overflow-hidden">
          <img src="https://ui-avatars.com/api/?name=User&background=random" alt="Avatar" class="w-full h-full object-cover" />
        </div>
      </div>
    </header>

    <!-- Body -->
    <div class="flex flex-1 overflow-hidden">
      <div class="flex min-w-0 flex-1 flex-col">
        <EditorToolbar
          :font="font"
          :size="size"
          :active-formats="activeFormats"
          :cornell-mode="cornellMode"
          @update:font="updateFont"
          @update:size="updateSize"
          @exec="exec"
          @toggleCornell="cornellMode = !cornellMode"
        />
        <NoteEditor
          :cornell-mode="cornellMode"
          :font="font"
          v-model="form.contenidoApunte"
        />
      </div>

      <AudioPanel v-model:open="audioOpen" @recorded="onAudioRecorded" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Settings, Bell, FileText } from 'lucide-vue-next'
import { useForm } from '@inertiajs/vue3'
import EditorToolbar from './Components/EditorToolbar.vue'
import NoteEditor from './Components/NoteEditor.vue'
import AudioPanel from './Components/AudioPanel.vue'

const props = defineProps({
  apunte: {
    type: Object,
    default: null
  }
})

const cornellMode = ref(false)
const audioOpen = ref(true)
const activeFormats = ref({})
const font = ref("Arial")
const size = ref("11")

const form = useForm({
  tituloApunte: 'Programación en Ambientes Web',
  contenidoApunte: ''
})

onMounted(() => {
  if (props.apunte) {
    form.tituloApunte = props.apunte.tituloApunte || 'Programación en Ambientes Web'
    form.contenidoApunte = props.apunte.contenidoApunte || ''
  }
})

const sizeToHtml = (s) => {
  const n = parseInt(s, 10)
  if (n <= 9) return "1"
  if (n <= 11) return "2"
  if (n <= 13) return "3"
  if (n <= 16) return "4"
  if (n <= 20) return "5"
  if (n <= 28) return "6"
  return "7"
}

const updateFont = (f) => {
  font.value = f
  exec("fontName", f)
}

const updateSize = (s) => {
  size.value = s
  exec("fontSize", sizeToHtml(s))
}

const exec = (command, value = null) => {
  document.execCommand(command, false, value)
  refreshFormats()
}

const refreshFormats = () => {
  const next = {}
  for (const cmd of ["bold", "italic", "underline", "strikeThrough"]) {
    try {
      next[cmd] = document.queryCommandState(cmd)
    } catch {
      next[cmd] = false
    }
  }
  activeFormats.value = next
}

const onAudioRecorded = (blob) => {
  console.log("Audio recorded", blob)
  // Logic to upload or save audio blob could be implemented here
}

const saveNote = () => {
  if (!form.tituloApunte.trim()) {
    alert('Por favor, ingresa un título para el apunte.')
    return
  }

  if (props.apunte) {
    form.put(route('apuntes.update', props.apunte.idApunte))
  } else {
    form.post(route('apuntes.store'))
  }
}
</script>
