<template>
  <Head :title="pageTitle" />
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
            :disabled="perfilActivo?.permisoCompartido === 'Lector'"
            class="truncate text-base font-semibold text-primary sm:text-lg bg-transparent border-none outline-none focus:ring-0 p-0 w-full"
            placeholder="Título del apunte..."
            required
          />
        </div>
        <div class="flex shrink-0 items-center gap-3 sm:gap-4">
          <span v-if="form.audio" class="badge bg-success-subtle text-success-emphasis border px-3 py-2 rounded-md font-medium text-xs d-flex align-items-center gap-1">
            <i class="bi bi-file-earmark-music-fill"></i> Grabación lista para guardar
          </span>
          <span v-if="perfilActivo?.permisoCompartido === 'Lector'" class="badge bg-warning-subtle text-warning-emphasis border px-3 py-2 rounded-md font-medium text-xs d-flex align-items-center gap-1">
            <i class="bi bi-lock-fill"></i> Solo lectura
          </span>
          <button
            v-else
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
            v-if="perfilActivo?.permisoCompartido !== 'Lector'"
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
            v-model:ideas="form.ideasApunte"
            v-model:resumen="form.resumenApunte"
            :isReadOnly="perfilActivo?.permisoCompartido === 'Lector'"
          />
        </div>

        <AudioPanel
          v-model:open="audioOpen"
          :audios="props.apunte?.audios || []"
          :apunte-id="props.apunte?.idApunte"
          :is-read-only="perfilActivo?.permisoCompartido === 'Lector'"
          @recorded="onAudioRecorded"
          @delete="onAudioDeleted"
          @error="(msg) => showCustomAlert('Error de Micrófono', msg)"
        />
      </div>
    </div>

    <AlertModal 
      :show="showAlertModal" 
      :title="alertTitle" 
      :message="alertMessage" 
      @close="showAlertModal = false" 
    />

    <!-- Modal de Confirmación para Eliminar Audio -->
    <Teleport to="body">
      <div v-if="showConfirmDeleteAudioModal" class="zen-custom-modal-overlay" @click.self="cancelDeleteAudio">
        <div class="zen-custom-modal">
          <div class="zen-modal-icon">
            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
          </div>
          <h3 class="zen-modal-title">¿Eliminar Grabación?</h3>
          <p class="zen-modal-text">¿Estás seguro de que deseas eliminar esta grabación de audio? Esta acción no se puede deshacer.</p>
          <div class="zen-modal-actions">
            <button class="zen-btn-secondary" @click="cancelDeleteAudio">Cancelar</button>
            <button class="zen-btn-primary bg-danger text-white border-0" @click="confirmDeleteAudio">Eliminar</button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { Settings, Bell, FileText } from 'lucide-vue-next'
import { useForm, router, Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import EditorToolbar from './components/EditorToolbar.vue'
import NoteEditor from './components/NoteEditor.vue'
import AudioPanel from './components/AudioPanel.vue'
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
  },
  perfilActivo: {
    type: Object,
    default: null
  }
})

const cornellMode = ref(false)
const audioOpen = ref(props.perfilActivo?.permisoCompartido !== 'Lector')
const activeFormats = ref({})
const font = ref("Arial")
const size = ref("11")

const form = useForm({
  tituloApunte: '',
  tipoApunte: 'normal',
  contenidoApunte: '',
  ideasApunte: '',
  resumenApunte: ''
})

const pageTitle = computed(() => form.tituloApunte || 'Nuevo Apunte')

onMounted(() => {
  if (props.apunte) {
    form.tituloApunte = props.apunte.tituloApunte || ''
    form.contenidoApunte = props.apunte.contenidoApunte || ''
    form.tipoApunte = props.apunte.tipoApunte || 'normal'
    form.ideasApunte = props.apunte.ideasApunte || ''
    form.resumenApunte = props.apunte.resumenApunte || ''
    cornellMode.value = form.tipoApunte === 'cornell'
  }
})

watch(cornellMode, (newVal) => {
  form.tipoApunte = newVal ? 'cornell' : 'normal'
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
  if (!props.apunte?.idApunte) return

  console.log("Audio recorded, uploading...", blob)
  const file = new File([blob], 'grabacion.webm', { type: blob.type })
  
  const formData = new FormData()
  formData.append('audio', file)

  router.post(route('apuntes.audio.upload', props.apunte.idApunte), formData, {
    preserveScroll: true,
    onSuccess: () => {
      showCustomAlert('Éxito', 'Grabación de audio guardada correctamente.')
    },
    onError: (errors) => {
      const msg = errors.audio || 'No se pudo guardar la grabación de audio.'
      showCustomAlert('Error', msg)
    }
  })
}

const showConfirmDeleteAudioModal = ref(false)
const audioToDeleteId = ref(null)

const onAudioDeleted = (audioId) => {
  audioToDeleteId.value = audioId
  showConfirmDeleteAudioModal.value = true
}

const cancelDeleteAudio = () => {
  showConfirmDeleteAudioModal.value = false
  audioToDeleteId.value = null
}

const confirmDeleteAudio = () => {
  if (audioToDeleteId.value) {
    router.delete(route('apuntes.audio.destroy', audioToDeleteId.value), {
      preserveScroll: true,
      onSuccess: () => {
        showConfirmDeleteAudioModal.value = false
        audioToDeleteId.value = null
        showCustomAlert('Éxito', 'Grabación de audio eliminada correctamente.')
      }
    })
  }
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

/* Modal de Confirmación Estilo Zen */
.zen-custom-modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99999;
}
.zen-custom-modal {
  background: white;
  border-radius: 16px;
  padding: 30px;
  max-width: 400px;
  text-align: center;
  box-shadow: 0 10px 40px rgba(0,0,0,0.2);
  animation: modalIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.zen-modal-icon {
  font-size: 3rem;
  margin-bottom: 10px;
}
.zen-modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 10px;
  color: #333;
}
.zen-modal-text {
  font-size: 0.95rem;
  color: #666;
  margin-bottom: 25px;
}
.zen-modal-actions {
  display: flex;
  gap: 10px;
}
.zen-btn-secondary {
  flex: 1;
  padding: 10px;
  border: 1px solid #ccc;
  background: transparent;
  color: #666;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.zen-btn-secondary:hover {
  background: #f0f0f0;
}
.zen-btn-primary {
  flex: 1;
  padding: 10px;
  background: #dc3545 !important;
  color: white !important;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.zen-btn-primary:hover {
  background: #c82333 !important;
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.9) translateY(20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
