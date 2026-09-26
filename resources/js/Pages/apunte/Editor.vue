<template>
  <Head :title="pageTitle" />
  <AppLayout>
    <div class="notes-editor-page flex flex-col bg-background text-foreground rounded-lg border border-border overflow-hidden" style="height: calc(100vh - 140px)">
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
          @upload-file="onUploadFile"
          @rename="onAudioRename"
          @delete="onAudioDeleted"
          @transcribed="onAudioTranscribed"
          @show-transcription="onShowTranscription"
          @error="(msg) => showCustomAlert('Aviso', msg)"
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
        <div class="zen-custom-modal" style="max-width: 420px; width: 92%;">
          <div class="zen-modal-icon" style="color: #dc3545;">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <h3 class="zen-modal-title" style="color: #612c2d;">¿Eliminar Grabación?</h3>
          <p class="zen-modal-text">¿Estás seguro de que deseas eliminar esta grabación de audio? Esta acción no se puede deshacer.</p>
          <div class="zen-modal-actions">
            <button type="button" class="zen-btn-secondary" @click="cancelDeleteAudio">Cancelar</button>
            <button type="button" class="zen-btn-danger" @click="confirmDeleteAudio">Eliminar</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal para Editar Nombre de Audio -->
    <Teleport to="body">
      <div v-if="showRenameAudioModal" class="zen-custom-modal-overlay" @click.self="cancelRenameAudio">
        <div class="zen-custom-modal" style="max-width: 460px; width: 92%;">
          <div class="zen-modal-icon" style="background: rgba(97, 44, 45, 0.1); color: #612c2d;">
            <i class="ti ti-pencil" style="font-size: 26px;"></i>
          </div>
          <h3 class="zen-modal-title" style="color: #612c2d;">Cambiar Nombre del Audio</h3>
          <p class="zen-modal-text" style="margin-bottom: 16px;">
            Ingresá el nuevo nombre para identificar este archivo de audio:
          </p>
          <div style="margin-bottom: 20px;">
            <input
              type="text"
              v-model="editAudioNameInput"
              @keydown.enter.prevent="confirmRenameAudio"
              placeholder="Ej: AUD_202021920 o Clase de Historia..."
              style="width: 100%; border: 1px solid #8c4e50; border-radius: 8px; padding: 10px 14px; font-size: 14px; color: #612c2d; outline: none; font-family: Figtree, sans-serif; background: #ffffff;"
              class="focus:ring-2 focus:ring-[#612c2d]/25"
              autofocus
            />
          </div>
          <div class="zen-modal-actions">
            <button
              type="button"
              class="zen-btn-secondary"
              @click="cancelRenameAudio"
            >
              Cancelar
            </button>
            <button
              type="button"
              class="zen-btn-primary"
              :disabled="savingRename"
              @click="confirmRenameAudio"
            >
              {{ savingRename ? 'Guardando...' : 'Guardar cambios' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal de Transcripción de Audio y Resumen Cornell -->
    <Teleport to="body">
      <div v-if="showTranscriptionModal" class="zen-custom-modal-overlay" @click.self="showTranscriptionModal = false">
        <div class="zen-custom-modal" style="max-width: 620px; width: 94%; max-height: 90vh; display: flex; flex-direction: column;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div class="zen-modal-icon" style="background: rgba(97, 44, 45, 0.1); color: #612c2d; margin-bottom: 0;">
                <i class="ti ti-sparkles" style="font-size: 24px;" v-if="activeCornellSummary"></i>
                <i class="ti ti-file-text" style="font-size: 24px;" v-else></i>
              </div>
              <div style="text-align: left;">
                <h3 class="zen-modal-title" style="margin: 0; font-size: 1.15rem; color: #612c2d;">
                  {{ activeCornellSummary ? 'Resumen Inteligente Cornell' : 'Transcripción de Audio' }}
                </h3>
                <span style="font-size: 12px; color: #8c4e50; font-weight: 600;">
                  {{ activeTranscriptionAudio?.nombreOriginal || ('AUD_' + activeTranscriptionAudio?.idApunteAudio) }}
                </span>
              </div>
            </div>
            <button
              type="button"
              @click="showTranscriptionModal = false"
              style="background: transparent; border: none; font-size: 18px; cursor: pointer; color: #8c4e50;"
            >
              <i class="ti ti-x"></i>
            </button>
          </div>

          <!-- Pestañas si hay Resumen Cornell disponible -->
          <div v-if="activeCornellSummary" style="display: flex; gap: 8px; border-bottom: 1px solid rgba(97, 44, 45, 0.15); margin-bottom: 12px; padding-bottom: 6px;">
            <button
              type="button"
              @click="activeModalTab = 'cornell'"
              :style="[
                'border: none; background: transparent; padding: 6px 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 6px; transition: all 0.15s;',
                activeModalTab === 'cornell' ? 'background: #612c2d; color: #ffffff;' : 'color: #612c2d; background: rgba(97,44,45,0.06);'
              ]"
            >
              <i class="ti ti-layout-columns" style="margin-right: 4px;"></i>
              Método Cornell (IA)
            </button>
            <button
              type="button"
              @click="activeModalTab = 'transcripcion'"
              :style="[
                'border: none; background: transparent; padding: 6px 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 6px; transition: all 0.15s;',
                activeModalTab === 'transcripcion' ? 'background: #612c2d; color: #ffffff;' : 'color: #612c2d; background: rgba(97,44,45,0.06);'
              ]"
            >
              <i class="ti ti-file-text" style="margin-right: 4px;"></i>
              Transcripción completa
            </button>
          </div>

          <!-- Contenido: Tab Resumen Cornell -->
          <div v-if="activeCornellSummary && activeModalTab === 'cornell'" style="overflow-y: auto; text-align: left; max-height: 48vh; display: flex; flex-direction: column; gap: 12px; padding-right: 4px;">
            <div v-if="activeCornellSummary.titulo_sugerido" style="background: #F7EDE9; border: 1px solid rgba(97, 44, 45, 0.2); border-radius: 8px; padding: 10px 14px;">
              <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #612c2d; display: block; margin-bottom: 2px;">Título Sugerido</span>
              <span style="font-size: 14px; font-weight: 600; color: #2d1314;">{{ activeCornellSummary.titulo_sugerido }}</span>
            </div>

            <!-- Palabras Clave / Preguntas -->
            <div style="background: #FAF8F7; border: 1px solid rgba(97, 44, 45, 0.15); border-radius: 8px; padding: 12px;">
              <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #612c2d; display: block; margin-bottom: 6px;">💡 Palabras Clave y Preguntas</span>
              <ul style="margin: 0; padding-left: 18px; font-size: 13px; color: #444; line-height: 1.5;">
                <li v-for="(idea, idx) in (Array.isArray(activeCornellSummary.ideas_clave) ? activeCornellSummary.ideas_clave : [activeCornellSummary.ideas_clave])" :key="idx" v-html="formatToEditorHtml(idea)">
                </li>
              </ul>
            </div>

            <!-- Notas Principales -->
            <div style="background: #FAF8F7; border: 1px solid rgba(97, 44, 45, 0.15); border-radius: 8px; padding: 12px;">
              <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #612c2d; display: block; margin-bottom: 6px;">📝 Notas de Clase</span>
              <div style="font-size: 13px; color: #333; line-height: 1.6;" v-html="formatToEditorHtml(activeCornellSummary.notas)"></div>
            </div>

            <!-- Resumen -->
            <div style="background: #FAF8F7; border: 1px solid rgba(97, 44, 45, 0.15); border-radius: 8px; padding: 12px;">
              <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #612c2d; display: block; margin-bottom: 4px;">📌 Resumen Integrador</span>
              <div style="font-size: 13px; color: #444; line-height: 1.5;" v-html="formatToEditorHtml(activeCornellSummary.resumen)"></div>
            </div>
          </div>

          <!-- Contenido: Tab Transcripción Completa -->
          <div v-else style="background: #FAF8F7; border: 1px solid rgba(97, 44, 45, 0.2); border-radius: 8px; padding: 14px; max-height: 48vh; overflow-y: auto; text-align: left; font-size: 13px; color: #333; line-height: 1.6; white-space: pre-wrap; font-family: Figtree, sans-serif;">
            {{ activeTranscriptionText }}
          </div>

          <!-- Acciones del Modal -->
          <div class="zen-modal-actions" style="margin-top: 18px; display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap;">
            <button
              type="button"
              class="zen-btn-secondary"
              @click="copyTranscriptionToClipboard"
              style="display: flex; align-items: center; gap: 6px; color: #612c2d;"
            >
              <i class="ti ti-copy" style="font-size: 16px;"></i>
              {{ copiedTranscription ? '¡Copiado!' : 'Copiar texto' }}
            </button>

            <!-- Si no está en Modo Cornell, botón para activarlo y poblar las 3 columnas -->
            <button
              v-if="perfilActivo?.permisoCompartido !== 'Lector' && activeCornellSummary && !cornellMode"
              type="button"
              class="zen-btn-primary"
              @click="insertCornellIntoNote(true)"
              style="display: flex; align-items: center; gap: 6px; background-color: #3B6D11 !important;"
              title="Convierte la nota a Método Cornell y completa las 3 columnas automáticamente"
            >
              <i class="ti ti-layout-columns" style="font-size: 16px;"></i>
              Aplicar en columnas Cornell
            </button>

            <!-- Insertar en el apunte actual -->
            <button
              v-if="perfilActivo?.permisoCompartido !== 'Lector'"
              type="button"
              class="zen-btn-primary"
              @click="insertCornellIntoNote(false)"
              style="display: flex; align-items: center; gap: 6px;"
            >
              <i class="ti ti-file-plus" style="font-size: 16px;"></i>
              {{ cornellMode ? 'Insertar en columnas Cornell' : 'Insertar en el apunte' }}
            </button>

            <button
              type="button"
              class="zen-btn-secondary"
              @click="showTranscriptionModal = false"
            >
              Cerrar
            </button>
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

const onUploadFile = (file) => {
  if (!props.apunte?.idApunte) {
    showCustomAlert('Aviso', 'Guardá este apunte por primera vez para poder adjuntarle audios.')
    return
  }

  const formData = new FormData()
  formData.append('audio', file)

  router.post(route('apuntes.audio.upload', props.apunte.idApunte), formData, {
    preserveScroll: true,
    onSuccess: () => {
      showCustomAlert('Éxito', `Audio "${file.name}" subido correctamente.`)
    },
    onError: (errors) => {
      const msg = errors.audio || 'No se pudo subir el archivo de audio.'
      showCustomAlert('Error', msg)
    }
  })
}

// Estado y funciones del Modal de Transcripción y Resumen Cornell
const showTranscriptionModal = ref(false)
const activeTranscriptionAudio = ref(null)
const activeTranscriptionText = ref('')
const activeCornellSummary = ref(null)
const activeModalTab = ref('cornell')
const copiedTranscription = ref(false)

const onAudioTranscribed = ({ audio, text, resumen_cornell }) => {
  activeTranscriptionAudio.value = audio
  activeTranscriptionText.value = text
  activeCornellSummary.value = resumen_cornell || audio.resumen_ia || null
  activeModalTab.value = activeCornellSummary.value ? 'cornell' : 'transcripcion'
  showTranscriptionModal.value = true
}

const onShowTranscription = ({ audio, text, resumen_cornell }) => {
  activeTranscriptionAudio.value = audio
  activeTranscriptionText.value = text
  activeCornellSummary.value = resumen_cornell || audio.resumen_ia || null
  activeModalTab.value = activeCornellSummary.value ? 'cornell' : 'transcripcion'
  showTranscriptionModal.value = true
}

const copyTranscriptionToClipboard = async () => {
  const textToCopy = activeModalTab.value === 'cornell' && activeCornellSummary.value
    ? `Título: ${activeCornellSummary.value.titulo_sugerido || ''}\n\nIdeas Clave:\n${Array.isArray(activeCornellSummary.value.ideas_clave) ? activeCornellSummary.value.ideas_clave.map(i => '- ' + i).join('\n') : activeCornellSummary.value.ideas_clave}\n\nNotas:\n${activeCornellSummary.value.notas || ''}\n\nResumen:\n${activeCornellSummary.value.resumen || ''}`
    : activeTranscriptionText.value

  if (!textToCopy) return
  try {
    await navigator.clipboard.writeText(textToCopy)
    copiedTranscription.value = true
    setTimeout(() => {
      copiedTranscription.value = false
    }, 2000)
  } catch (err) {
    console.warn('Error al copiar al portapapeles:', err)
  }
}

// Formateador estándar: Títulos en Negrita, Ítems con viñetas en Cursiva y párrafos limpios
const formatToEditorHtml = (rawText) => {
  if (!rawText) return ''

  let text = String(rawText)

  // 1. Convertir encabezados Markdown (# Titulo, ## Titulo, ### Titulo) a Títulos en Negrita
  text = text.replace(/^###+\s*(.*?)$/gim, '<p><strong>$1</strong></p>')
  text = text.replace(/^##\s*(.*?)$/gim, '<p><strong style="font-size: 1.1em;">$1</strong></p>')
  text = text.replace(/^#\s*(.*?)$/gim, '<p><strong style="font-size: 1.2em;">$1</strong></p>')

  // 2. Negrita (**texto** o __texto__)
  text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
  text = text.replace(/__(.*?)__/g, '<strong>$1</strong>')

  // 3. Viñetas Markdown (* o - o •) con texto en cursiva según el estándar solicitado
  text = text.replace(/^\s*[-*•]\s+(.*?)$/gim, (match, itemContent) => {
    const trimmed = itemContent.trim()
    if (trimmed.startsWith('<em>') || trimmed.startsWith('*') || trimmed.startsWith('_')) {
      return `<p style="margin-left: 16px; margin-bottom: 4px;">• ${trimmed}</p>`
    }
    // Si contiene dos puntos (ej: "Propósito principal: Simular..."), poner la etiqueta en negrita y la explicación en cursiva
    if (trimmed.includes(':')) {
      const colonIdx = trimmed.indexOf(':')
      const label = trimmed.slice(0, colonIdx).replace(/<[^>]+>/g, '').trim()
      const rest = trimmed.slice(colonIdx + 1).trim()
      return `<p style="margin-left: 16px; margin-bottom: 4px;">• <strong>${label}</strong>: <em>${rest}</em></p>`
    }
    return `<p style="margin-left: 16px; margin-bottom: 4px;">• <em>${trimmed}</em></p>`
  })

  // 4. Cursiva restante (*texto* o _texto_)
  text = text.replace(/(?<!\*)\*([^\*\n]+)\*(?!\*)/g, '<em>$1</em>')
  text = text.replace(/(?<!_)_([^_\n]+)_(?!_)/g, '<em>$1</em>')

  // 5. Saltos de línea para párrafos limpios
  text = text.replace(/\n\n+/g, '<br><br>')
  text = text.replace(/\n/g, '<br>')

  return text
}

const insertCornellIntoNote = (switchToCornell = false) => {
  const summary = activeCornellSummary.value

  // Si no hay resumen estructurado de Cornell, insertamos la transcripción pura
  if (!summary) {
    insertRawTranscriptionIntoNote()
    return
  }

  if (switchToCornell) {
    cornellMode.value = true
  }

  // Si el título es genérico o está vacío, asignar el sugerido por IA
  if (summary.titulo_sugerido && (!form.tituloApunte || form.tituloApunte === 'Sin título' || form.tituloApunte === 'Nuevo Apunte')) {
    form.tituloApunte = summary.titulo_sugerido
  }

  // Ideas / Palabras clave con viñeta y negrita destacada
  const ideasList = Array.isArray(summary.ideas_clave)
    ? summary.ideas_clave.map(i => `<p style="margin-left: 14px; margin-bottom: 6px;">• <strong>${String(i).replace(/^[•*-]\s*/, '').trim()}</strong></p>`).join('')
    : `<p style="margin-left: 14px; margin-bottom: 6px;">• ${formatToEditorHtml(summary.ideas_clave)}</p>`

  const notasFormatted = formatToEditorHtml(summary.notas)
  const resumenFormatted = formatToEditorHtml(summary.resumen)

  if (cornellMode.value || switchToCornell) {
    // Distribuir en las tres columnas del Método Cornell
    form.ideasApunte = (form.ideasApunte ? form.ideasApunte + '<br><br>' : '') + ideasList
    form.contenidoApunte = (form.contenidoApunte ? form.contenidoApunte + '<br><br>' : '') + notasFormatted
    form.resumenApunte = (form.resumenApunte ? form.resumenApunte + '<br><br>' : '') + resumenFormatted
    showCustomAlert('Éxito', 'Estructura Cornell insertada con éxito en Palabras clave, Notas y Resumen.')
  } else {
    // Modo Normal: insertar bloques secuenciales organizados
    const block = `
      <p><strong style="font-size: 1.15em; color: #612c2d;">💡 Preguntas y Conceptos Clave</strong></p>
      ${ideasList}
      <hr style="border: 0; border-top: 1px solid rgba(97,44,45,0.2); margin: 16px 0;">
      <p><strong style="font-size: 1.15em; color: #612c2d;">📝 Notas</strong></p>
      ${notasFormatted}
      <hr style="border: 0; border-top: 1px solid rgba(97,44,45,0.2); margin: 16px 0;">
      <p><strong style="font-size: 1.15em; color: #612c2d;">📌 Resumen</strong></p>
      ${resumenFormatted}
    `
    form.contenidoApunte = (form.contenidoApunte ? form.contenidoApunte + '<br><br>' : '') + block
    showCustomAlert('Éxito', 'Resumen estructurado insertado en el apunte.')
  }

  showTranscriptionModal.value = false
}

const insertRawTranscriptionIntoNote = () => {
  if (!activeTranscriptionText.value) return
  const formatted = activeTranscriptionText.value.replace(/\n/g, '<br>')
  const block = `<p><strong>[Transcripción]:</strong> ${formatted}</p>`

  form.contenidoApunte = (form.contenidoApunte ? form.contenidoApunte + '<br><br>' : '') + block
  showTranscriptionModal.value = false
  showCustomAlert('Éxito', 'La transcripción se insertó en el apunte correctamente.')
}

// Renombrar Audio
const showRenameAudioModal = ref(false)
const audioToRename = ref(null)
const editAudioNameInput = ref('')
const savingRename = ref(false)

const onAudioRename = (audio) => {
  audioToRename.value = audio
  editAudioNameInput.value = audio.nombreOriginal || ('AUD_' + audio.idApunteAudio)
  showRenameAudioModal.value = true
}

const cancelRenameAudio = () => {
  showRenameAudioModal.value = false
  audioToRename.value = null
  editAudioNameInput.value = ''
}

const confirmRenameAudio = () => {
  if (!audioToRename.value) return
  const nuevoNombre = editAudioNameInput.value.trim()
  if (!nuevoNombre) {
    showCustomAlert('Aviso', 'El nombre del audio no puede estar vacío.')
    return
  }

  savingRename.value = true
  router.put(route('apuntes.audio.rename', audioToRename.value.idApunteAudio), {
    nombre: nuevoNombre
  }, {
    preserveScroll: true,
    onSuccess: () => {
      savingRename.value = false
      showRenameAudioModal.value = false
      if (audioToRename.value) {
        audioToRename.value.nombreOriginal = nuevoNombre
      }
      showCustomAlert('Éxito', 'Nombre del audio actualizado correctamente.')
      audioToRename.value = null
    },
    onError: (errors) => {
      savingRename.value = false
      const msg = errors.nombre || 'No se pudo actualizar el nombre del audio.'
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

/* Modal Estilo Zen */
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
  padding: 28px;
  max-width: 480px;
  text-align: center;
  box-shadow: 0 10px 40px rgba(0,0,0,0.2);
  animation: modalIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  font-family: Figtree, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
}
.zen-modal-icon {
  font-size: 2.5rem;
  margin-bottom: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 50%;
}
.zen-modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 8px;
  color: #612c2d;
  font-family: inherit;
}
.zen-modal-text {
  font-size: 0.95rem;
  color: #555555;
  margin-bottom: 20px;
  line-height: 1.5;
  font-family: inherit;
}
.zen-modal-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}
.zen-btn-secondary {
  flex: 1;
  padding: 10px 18px;
  border: 1px solid #dee2e6;
  background-color: #f1f3f5;
  color: #495057;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.zen-btn-secondary:hover {
  background-color: #e9ecef;
}
.zen-btn-primary {
  flex: 1;
  padding: 10px 18px;
  background-color: #612c2d !important;
  color: white !important;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.zen-btn-primary:hover {
  background-color: #4e2324 !important;
}
.zen-btn-danger {
  flex: 1;
  padding: 10px 18px;
  background-color: #dc3545 !important;
  color: white !important;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}
.zen-btn-danger:hover {
  background-color: #bd2130 !important;
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.9) translateY(20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Estilos de modo oscuro específicos para la vista de edición de Apuntes (Editor.vue) */
body.cn-body-dark .notes-editor-page {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .notes-editor-page header {
  background-color: #4d2323 !important;
  border-bottom-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .notes-editor-page header input {
  color: #ffffff !important;
}

body.cn-body-dark .notes-editor-page header input::placeholder {
  color: rgba(255, 255, 255, 0.4) !important;
}

body.cn-body-dark .notes-editor-page header button.bg-primary {
  background-color: #f4be95 !important;
  color: #612c2d !important;
}

body.cn-body-dark .notes-editor-page header button.bg-primary:hover {
  background-color: #fcd5b8 !important;
  color: #612c2d !important;
}

/* Modal Zen Modo Oscuro */
body.cn-body-dark .zen-custom-modal {
  background-color: #4d2323 !important;
  border: 1px solid #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .zen-modal-title {
  color: #ffffff !important;
}

body.cn-body-dark .zen-modal-text {
  color: #fcd5b8 !important;
}

body.cn-body-dark .zen-btn-secondary {
  border-color: #7b413f !important;
  color: #ffffff !important;
  background-color: transparent !important;
}

body.cn-body-dark .zen-btn-secondary:hover {
  background-color: #542627 !important;
}

/* Barra de herramientas */
body.cn-body-dark .notes-editor-page .border-b.bg-card {
  background-color: #4d2323 !important;
  border-bottom-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .notes-editor-page select {
  background-color: #3b1717 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .notes-editor-page select option {
  background-color: #3b1717 !important;
  color: #ffffff !important;
}

body.cn-body-dark .notes-editor-page button.text-muted-foreground {
  color: #fcd5b8 !important;
}

body.cn-body-dark .notes-editor-page button.text-muted-foreground:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .notes-editor-page button.bg-accent {
  background-color: #f4be95 !important;
  color: #612c2d !important;
}

body.cn-body-dark .notes-editor-page .bg-border {
  background-color: #7b413f !important;
}

body.cn-body-dark .notes-editor-page button.ml-auto.bg-primary {
  background-color: #7b413f !important;
  color: #ffffff !important;
  border: 1px solid #7b413f !important;
}

body.cn-body-dark .notes-editor-page button.ml-auto.bg-primary:hover {
  background-color: #612c2d !important;
}

/* Editor de Notas y Áreas del editor */
body.cn-body-dark .notes-editor-page .editor-area {
  color: #ffffff !important;
}

body.cn-body-dark .notes-editor-page .editor-area:empty::before {
  color: rgba(255, 255, 255, 0.6) !important;
}

/* Fondo para cajas del Método Cornell (Cajas clave, notas y resumen) */
body.cn-body-dark .notes-editor-page .grid-cols-1 .editor-area {
  background-color: #a55e57 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .notes-editor-page .grid-cols-1 .text-muted-foreground {
  color: #ffffff !important;
  font-weight: 600 !important;
}

/* Fondo del área de Nota Normal */
body.cn-body-dark .notes-editor-page .editor-area.mx-auto {
  background-color: #4d2323 !important;
  color: #ffffff !important;
  border-color: #7b413f !important;
}

/* Panel de Grabación de Audio */
body.cn-body-dark aside {
  background-color: #4d2323 !important;
  border-left: 1px solid #7b413f !important;
}

body.cn-body-dark aside h3, 
body.cn-body-dark aside h4 {
  color: #ffffff !important;
}

body.cn-body-dark aside .text-primary-foreground\/70,
body.cn-body-dark aside .text-primary-foreground\/80,
body.cn-body-dark aside .text-primary-foreground\/90 {
  color: #fcd5b8 !important;
}

body.cn-body-dark aside .border-dashed {
  border-color: #7b413f !important;
}

body.cn-body-dark aside .bg-primary-foreground\/10 {
  background-color: rgba(244, 190, 149, 0.1) !important;
}

/* Tarjetas de grabaciones guardadas */
body.cn-body-dark aside .bg-card {
  background-color: #3b1717 !important;
  border: 1px solid #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark aside .bg-card .text-muted-foreground {
  color: #fcd5b8 !important;
}

/* Inversión de colores de control de audio nativo para modo oscuro */
body.cn-body-dark aside audio {
  filter: invert(0.9) hue-rotate(180deg) !important;
}

/* Botón descargar grabación */
body.cn-body-dark aside a.block {
  background-color: #612c2d !important;
  color: #ffffff !important;
}

body.cn-body-dark aside a.block:hover {
  background-color: #7b413f !important;
}

/* Botón de Grabar Audio (Micrófono) */
body.cn-body-dark aside button.size-24.bg-card {
  background-color: #612c2d !important;
  color: #ffffff !important;
  border: 2px solid #7b413f !important;
}

body.cn-body-dark aside button.size-24.bg-card:hover {
  background-color: #7b413f !important;
}

body.cn-body-dark aside button.size-24.bg-card svg {
  color: #ffffff !important;
  stroke: #ffffff !important;
}

body.cn-body-dark aside button.size-24.bg-card .animate-ping {
  background-color: rgba(97, 44, 45, 0.4) !important;
}

/* Botón flotante para abrir panel */
body.cn-body-dark button.fixed.bottom-6.right-6 {
  background-color: #f4be95 !important;
  color: #612c2d !important;
  border: none !important;
}

body.cn-body-dark button.fixed.bottom-6.right-6 svg {
  stroke: #612c2d !important;
}
</style>
