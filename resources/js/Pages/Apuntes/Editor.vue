<template>
  <AppLayout>
    <div class="editor-page">
      <div class="cronos-editor-wrapper">
        <!-- Panel de Audio -->
        <div class="audio-panel">
          <button 
            @click="handleRecord" 
            class="btn-record" 
            :class="{ 'recording': isRecording }"
          >
            <!-- SVG Dinámico según si está grabando o no -->
            <svg v-if="!isRecording" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"></path>
              <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
              <line x1="12" y1="19" x2="12" y2="23"></line>
              <line x1="8" y1="23" x2="16" y2="23"></line>
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
              <rect x="6" y="6" width="12" height="12"></rect>
            </svg>
            {{ isRecording ? 'Detener' : 'Grabar' }}
          </button>
          
          <div class="timer">{{ formattedTimer }}</div>
          
          <div v-if="audioUrl" class="audio-playback-container">
            <audio :src="audioUrl" controls class="audio-player"></audio>
          </div>

          <!-- Botón de Guardar a la derecha -->
          <button class="btn-save" @click="saveNote" :disabled="form.processing">
            {{ form.processing ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>

        <!-- Título del Apunte (Requerido por BD) -->
        <input 
          type="text" 
          v-model="form.tituloApunte" 
          placeholder="Título del apunte..." 
          class="note-title-input"
          required
        />

        <!-- Barra de Herramientas -->
        <div class="editor-toolbar">
          <button @click="formatText('bold')" title="Negrita"><b>B</b></button>
          <button @click="formatText('italic')" title="Cursiva"><i>I</i></button>
          <button @click="formatText('underline')" title="Subrayado"><u>U</u></button>
          <div class="divider"></div>
          <button @click="formatText('insertUnorderedList')" title="Lista">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="8" y1="6" x2="21" y2="6"></line>
              <line x1="8" y1="12" x2="21" y2="12"></line>
              <line x1="8" y1="18" x2="21" y2="18"></line>
              <line x1="3" y1="6" x2="3.01" y2="6"></line>
              <line x1="3" y1="12" x2="3.01" y2="12"></line>
              <line x1="3" y1="18" x2="3.01" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Cuerpo del Editor -->
        <div 
          class="note-editor" 
          ref="noteEditor" 
          contenteditable="true" 
          placeholder="Escribe tus notas aquí..."
          @input="updateContent"
        ></div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Recibimos datos desde el ApunteController
const props = defineProps({
  perfilActivo: Object,
  apunte: {
    type: Object,
    default: null
  }
});

// Referencia al elemento DOM del editor editable
const noteEditor = ref(null);

// Formulario de Inertia
const form = useForm({
  tituloApunte: '',
  contenidoApunte: ''
});

// Sincronizar contenido inicial si estamos editando
onMounted(() => {
  if (props.apunte) {
    form.tituloApunte = props.apunte.tituloApunte || '';
    form.contenidoApunte = props.apunte.contenidoApunte || '';
    if (noteEditor.value) {
      noteEditor.value.innerHTML = props.apunte.contenidoApunte || '';
    }
  }
});

// Formatear texto mediante comandos nativos del navegador
const formatText = (command) => {
  document.execCommand(command, false, null);
  if (noteEditor.value) {
    noteEditor.value.focus();
  }
};

// Sincronizar contenido editable al escribir
const updateContent = (event) => {
  form.contenidoApunte = event.target.innerHTML;
};

// Lógica de grabación de audio
let mediaRecorder = null;
let audioChunks = [];
const isRecording = ref(false);
const seconds = ref(0);
let timerInterval = null;
const audioUrl = ref('');

// Mostrar el temporizador en formato MM:SS
const formattedTimer = computed(() => {
  const mins = String(Math.floor(seconds.value / 60)).padStart(2, '0');
  const secs = String(seconds.value % 60).padStart(2, '0');
  return `${mins}:${secs}`;
});

const handleRecord = async () => {
  if (!isRecording.value) {
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      mediaRecorder = new MediaRecorder(stream);
      audioChunks = [];

      mediaRecorder.ondataavailable = (event) => {
        if (event.data.size > 0) {
          audioChunks.push(event.data);
        }
      };

      mediaRecorder.onstop = () => {
        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
        audioUrl.value = URL.createObjectURL(audioBlob);
        audioChunks = [];
      };

      mediaRecorder.start();
      isRecording.value = true;
      seconds.value = 0;
      audioUrl.value = '';
      timerInterval = setInterval(() => {
        seconds.value++;
      }, 1000);
    } catch (err) {
      console.error("Error al acceder al micrófono:", err);
      alert('No se pudo acceder al micrófono. Por favor, verifica los permisos en tu navegador.');
    }
  } else {
    if (mediaRecorder) {
      mediaRecorder.stop();
      mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
    isRecording.value = false;
    clearInterval(timerInterval);
  }
};

// Guardar la nota en la base de datos
const saveNote = () => {
  if (!form.tituloApunte.trim()) {
    alert('Por favor, ingresa un título para el apunte.');
    return;
  }

  if (props.apunte) {
    // Si estamos editando, hacemos un PUT
    form.put(route('apuntes.update', props.apunte.idApunte));
  } else {
    // Si es una nota nueva, hacemos un POST
    form.post(route('apuntes.store'));
  }
};
</script>

<style scoped>
/* Contenedor de la página */
.editor-page {
  background-color: #e8e5e1;
  min-height: 100vh;
  padding: 40px 20px;
  margin: -20px; /* Compensa el padding de AppLayout */
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Envoltura del Editor */
.cronos-editor-wrapper {
  background: #fff;
  border-radius: 12px;
  border: 1px solid #e5e5e5;
  max-width: 800px;
  margin: 0 auto;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

/* Panel de Audio */
.audio-panel {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 20px 24px;
  background-color: #faf8f8;
  border-bottom: 1px solid #e5e5e5;
}

.btn-record {
  display: flex;
  align-items: center;
  gap: 8px;
  background-color: #8b4c4c;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.btn-record svg {
  width: 18px;
  height: 18px;
}

.btn-record.recording {
  background-color: #d9534f;
  animation: pulse 1.5s infinite;
}

.timer {
  font-variant-numeric: tabular-nums;
  font-weight: 600;
  font-size: 16px;
  color: #333;
}

.audio-playback-container {
  display: flex;
  align-items: center;
}

.audio-player {
  height: 36px;
}

/* Botón de Guardar */
.btn-save {
  margin-left: auto;
  background-color: #612c2d;
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 8px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.btn-save:hover {
  background-color: #723f3f;
}

.btn-save:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

/* Título de la Nota */
.note-title-input {
  width: 100%;
  padding: 24px 24px 8px 24px;
  font-size: 24px;
  font-weight: 700;
  color: #333;
  border: none;
  outline: none;
  background: transparent;
  font-family: inherit;
  border-bottom: 1px solid #f5f5f5;
}

.note-title-input::placeholder {
  color: #bbb;
}

/* Barra de Herramientas */
.editor-toolbar {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 12px 24px;
  border-bottom: 1px solid #e5e5e5;
  background-color: #fff;
}

.editor-toolbar button {
  background: transparent;
  border: 1px solid transparent;
  padding: 8px;
  border-radius: 6px;
  cursor: pointer;
  color: #666;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  min-width: 34px;
}

.editor-toolbar button:hover {
  background: #f3f0ed;
  color: #8b4c4c;
}

.divider {
  width: 1px;
  height: 20px;
  background-color: #e5e5e5;
  margin: 0 8px;
}

/* Editor Cuerpo */
.note-editor {
  padding: 24px;
  min-height: 400px;
  font-size: 16px;
  line-height: 1.6;
  color: #333;
  outline: none;
  text-align: left;
}

.note-editor[placeholder]:empty:before {
  content: attr(placeholder);
  color: #999;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); }
  100% { transform: scale(1); }
}
</style>
