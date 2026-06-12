<template>
  <AppLayout>
    <div class="flex flex-col bg-background text-foreground rounded-lg border border-border overflow-hidden" style="height: calc(100vh - 140px)">
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

    <AlertModal 
      :show="showAlertModal" 
      :title="alertTitle" 
      :message="alertMessage" 
      @close="showAlertModal = false" 
    />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Settings, Bell, FileText } from '@lucide/vue'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import EditorToolbar from './Components/EditorToolbar.vue'
import NoteEditor from './Components/NoteEditor.vue'
import AudioPanel from './Components/AudioPanel.vue'
import AlertModal from '@/Components/AlertModal.vue'

const showAlertModal = ref(false)
const alertTitle = ref('')
const alertMessage = ref('')
const showCustomAlert = (title, message) => {
  alertTitle.value = title
  alertMessage.value = message
  showAlertModal.value = true
}

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
  tituloApunte: 'Titulo',
  contenidoApunte: ''
})

onMounted(() => {
  if (props.apunte) {
    form.tituloApunte = props.apunte.tituloApunte || 'Acá va el nombre del perfil da'
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
    showCustomAlert('Aviso', 'Por favor, ingresa un título para el apunte.')
    return
  }

  if (props.apunte) {
    form.put(route('apuntes.update', props.apunte.idApunte))
  } else {
    form.post(route('apuntes.store'))
  }
}
</script>

<style>
/* Forzar el color de marca #612c2d en lugar del azul */
.bg-primary {
  background-color: #612c2d !important;
}
.text-primary {
  color: #612c2d !important;
}
.bg-primary\/10 {
  background-color: rgba(97, 44, 45, 0.1) !important;
}
.hover\:bg-primary\/90:hover {
  background-color: #4e2324 !important;
}
.text-primary-foreground {
  color: #ffffff !important;
}
.text-primary-foreground\/90 {
  color: rgba(255, 255, 255, 0.9) !important;
}
.hover\:bg-primary-foreground\/10:hover {
  background-color: rgba(255, 255, 255, 0.1) !important;
}
.focus\:ring-ring:focus {
  --tw-ring-color: #612c2d !important;
}
</style>
