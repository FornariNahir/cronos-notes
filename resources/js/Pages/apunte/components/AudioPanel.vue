<template>
  <div :class="{ 'h-full flex flex-col shrink-0': open }">
    <!-- Floating toggle when closed -->
    <button
      v-if="!open"
      type="button"
      @click="$emit('update:open', true)"
      aria-label="Abrir panel de audio"
      class="fixed bottom-6 right-6 z-30 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-105"
    >
      <Mic class="size-6" />
    </button>

    <!-- Overlay on small screens -->
    <button
      v-if="open"
      type="button"
      aria-label="Cerrar panel"
      @click="$emit('update:open', false)"
      class="fixed inset-0 z-30 bg-foreground/40 lg:hidden"
    ></button>

    <aside
      :class="[
        'fixed inset-y-0 right-0 z-40 flex w-80 max-w-[85vw] flex-col bg-primary p-5 text-primary-foreground transition-transform duration-300 lg:static lg:z-auto lg:max-w-none lg:translate-x-0 lg:h-full',
        open ? 'translate-x-0' : 'translate-x-full lg:hidden'
      ]"
    >
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-sm font-semibold uppercase tracking-wider text-primary-foreground/90">Panel de Audio</h3>
        <button
          type="button"
          aria-label="Cerrar panel de audio"
          @click="$emit('update:open', false)"
          class="flex size-8 items-center justify-center rounded-md text-primary-foreground/90 transition-colors hover:bg-primary-foreground/10"
        >
          <X class="size-5" />
        </button>
      </div>

      <!-- If new note (no apunteId) -->
      <div v-if="!apunteId" class="flex flex-1 flex-col items-center justify-center text-center p-5 border border-dashed border-primary-foreground/20 rounded-lg">
        <div class="mb-4 rounded-full bg-primary-foreground/10 p-4">
          <Mic class="size-8 text-primary-foreground/80" />
        </div>
        <p class="text-sm font-semibold">Grabación deshabilitada</p>
        <p class="mt-2 text-xs text-primary-foreground/70 leading-relaxed">Guardá este apunte por primera vez para poder empezar a grabar y guardar audios de clase.</p>
      </div>

      <!-- If existing note -->
      <div v-else class="flex flex-1 flex-col overflow-hidden">
        <!-- Recording controls (only if !isReadOnly) -->
        <div v-if="!isReadOnly" class="flex flex-col items-center justify-center py-4 border-b border-primary-foreground/10 shrink-0">
          <button
            type="button"
            @click="audios.length < 5 ? toggleRecording() : null"
            :disabled="audios.length >= 5"
            :aria-label="recording ? 'Detener grabación' : 'Grabar audio'"
            :class="[
              'relative flex size-24 items-center justify-center rounded-full transition-transform border-0',
              audios.length >= 5 ? 'bg-card/50 text-muted-foreground cursor-not-allowed' : 'bg-card text-primary hover:scale-105'
            ]"
          >
            <span v-if="recording" class="absolute inset-0 animate-ping rounded-full bg-card/40" aria-hidden="true"></span>
            <Square v-if="recording" class="size-6 fill-current" />
            <Mic v-else class="size-8" />
          </button>

          <div class="text-center mt-3">
            <template v-if="audios.length >= 5">
              <span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1 rounded text-xs font-semibold">
                Límite de 5 audios alcanzado
              </span>
            </template>
            <template v-else>
              <p class="text-lg font-bold">{{ recording ? formattedTimer : "Grabar audio" }}</p>
              <p v-if="recording" class="text-xs text-primary-foreground/80">Grabando...</p>
            </template>
          </div>
        </div>

        <div v-else-if="isReadOnly && (!audios || audios.length === 0)" class="flex flex-1 flex-col items-center justify-center text-center p-4">
          <p class="text-xs text-primary-foreground/60">Este apunte no contiene audios.</p>
        </div>

        <!-- List of audios -->
        <div v-if="audios && audios.length > 0" class="mt-4 flex-1 overflow-y-auto space-y-3 pr-1">
          <h4 class="text-xs font-semibold uppercase tracking-wider text-primary-foreground/70 mb-2">Grabaciones ({{ audios.length }}/5)</h4>
          
          <div v-for="audio in audios" :key="audio.idApunteAudio" class="rounded-lg bg-card p-3 text-card-foreground space-y-2">
            <div class="flex items-center justify-between text-xs font-medium text-muted-foreground">
              <span>{{ formatDate(audio.fechaCreacion) }}</span>
              <button
                v-if="!isReadOnly"
                type="button"
                @click="$emit('delete', audio.idApunteAudio)"
                class="text-red-500 hover:text-red-700 bg-transparent border-0 cursor-pointer p-0"
                title="Eliminar audio"
              >
                <i class="bi bi-trash3-fill" style="font-size: 13px;"></i>
              </button>
            </div>
            
            <audio controls :src="`/storage/${audio.rutaAudio}`" class="w-full" style="height: 32px;"></audio>
            
            <a
              :href="`/storage/${audio.rutaAudio}`"
              download="grabacion.webm"
              class="block text-center text-xs font-semibold text-primary bg-primary/10 hover:bg-primary/20 py-1.5 rounded-md transition-colors text-decoration-none"
            >
              Descargar archivo
            </a>
          </div>
        </div>
        <div v-else-if="apunteId && !recording" class="mt-6 text-center text-xs text-primary-foreground/60 border border-dashed border-primary-foreground/30 p-4 rounded-lg">
          No hay grabaciones de audio en este apunte.
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted, onMounted, watch } from 'vue'
import { Mic, X, Square } from 'lucide-vue-next'

const props = defineProps({
  open: Boolean,
  audios: {
    type: Array,
    default: () => []
  },
  isReadOnly: {
    type: Boolean,
    default: false
  },
  apunteId: {
    type: [Number, String],
    default: null
  }
})

const emit = defineEmits(['update:open', 'recorded', 'delete', 'error'])

const recording = ref(false)
const seconds = ref(0)
let mediaRecorder = null
let chunks = []
let timerInterval = null

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const startTimer = () => {
  seconds.value = 0
  timerInterval = setInterval(() => {
    seconds.value++
  }, 1000)
}

const stopTimer = () => {
  if (timerInterval) clearInterval(timerInterval)
}

const toggleRecording = async () => {
  if (recording.value) {
    mediaRecorder?.stop()
    stopTimer()
    recording.value = false
    return
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    mediaRecorder = new MediaRecorder(stream)
    chunks = []
    
    mediaRecorder.ondataavailable = (e) => {
      if (e.data.size > 0) chunks.push(e.data)
    }
    
    mediaRecorder.onstop = () => {
      const blob = new Blob(chunks, { type: 'audio/webm' })
      stream.getTracks().forEach((t) => t.stop())
      emit('recorded', blob)
    }
    
    mediaRecorder.start()
    startTimer()
    recording.value = true
  } catch (err) {
    console.error("Error al acceder al micrófono:", err)
    emit('error', "No se pudo acceder al micrófono. Por favor, verificá que tengas un micrófono conectado y que el navegador tenga permisos para usarlo.")
  }
}

const formattedTimer = computed(() => {
  const mins = String(Math.floor(seconds.value / 60)).padStart(2, '0')
  const secs = String(seconds.value % 60).padStart(2, '0')
  return `${mins}:${secs}`
})

onUnmounted(() => {
  stopTimer()
  if (mediaRecorder && recording.value) {
    mediaRecorder.stop()
    mediaRecorder.stream.getTracks().forEach(t => t.stop())
  }
})
</script>
