<template>
  <div>
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
        'fixed inset-y-0 right-0 z-40 flex w-80 max-w-[85vw] flex-col bg-primary p-5 text-primary-foreground transition-transform duration-300 lg:static lg:z-auto lg:max-w-none lg:translate-x-0',
        open ? 'translate-x-0' : 'translate-x-full lg:hidden'
      ]"
    >
      <button
        type="button"
        aria-label="Cerrar panel de audio"
        @click="$emit('update:open', false)"
        class="mb-5 flex size-8 items-center justify-center rounded-md text-primary-foreground/90 transition-colors hover:bg-primary-foreground/10"
      >
        <X class="size-5" />
      </button>

      <div class="space-y-2 rounded-lg bg-card p-2.5 text-card-foreground">
        <div class="relative">
          <select
            aria-label="Micrófono"
            class="w-full appearance-none rounded-md border border-border bg-card px-2.5 py-1.5 pr-8 text-xs text-foreground outline-none focus:ring-2 focus:ring-ring"
          >
            <option>Micrófono</option>
            <option>Micrófono integrado</option>
            <option>Auriculares</option>
          </select>
          <ChevronDown class="pointer-events-none absolute right-2 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" />
        </div>
        
        <div class="relative">
          <select
            aria-label="Guardar en"
            class="w-full appearance-none rounded-md border border-border bg-card px-2.5 py-1.5 pr-8 text-xs text-foreground outline-none focus:ring-2 focus:ring-ring"
          >
            <option>Guardar en</option>
            <option>Mis notas</option>
            <option>Carpeta de audio</option>
          </select>
          <ChevronDown class="pointer-events-none absolute right-2 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground" />
        </div>
      </div>

      <br>
      <div class="flex flex-1 flex-col items-center justify-center gap-4">
        <button
          type="button"
          @click="toggleRecording"
          :aria-label="recording ? 'Detener grabación' : 'Grabar audio'"
          class="relative flex size-28 sm:size-32 items-center justify-center rounded-full bg-card text-primary transition-transform hover:scale-105"
        >
          <span v-if="recording" class="absolute inset-0 animate-ping rounded-full bg-card/40" aria-hidden="true"></span>
          <Square v-if="recording" class="size-8 sm:size-10 fill-current" />
          <Mic v-else class="size-10 sm:size-12" />
        </button>

        <div class="text-center">
          <p class="text-xl sm:text-2xl font-bold">{{ recording ? formattedTimer : "Grabar audio" }}</p>
          <p v-if="recording" class="mt-0.5 text-xs sm:text-sm text-primary-foreground/80">Grabando...</p>
        </div>

        <div v-if="audioUrl && !recording" class="w-full space-y-2">
          <audio controls :src="audioUrl" class="w-full"></audio>
          <a
            :href="audioUrl"
            download="grabacion.webm"
            class="block rounded-md bg-card py-2 text-center text-sm font-medium text-primary transition-colors hover:bg-card/90"
          >
            Descargar audio
          </a>
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { Mic, X, ChevronDown, Square } from 'lucide-vue-next'

const props = defineProps({
  open: Boolean
})

const emit = defineEmits(['update:open', 'recorded'])

const recording = ref(false)
const seconds = ref(0)
const audioUrl = ref(null)
let mediaRecorder = null
let chunks = []
let timerInterval = null

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
      audioUrl.value = URL.createObjectURL(blob)
      stream.getTracks().forEach((t) => t.stop())
      emit('recorded', blob)
    }
    
    mediaRecorder.start()
    audioUrl.value = null
    startTimer()
    recording.value = true
  } catch (err) {
    // Simulate recording if no mic
    startTimer()
    recording.value = true
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
