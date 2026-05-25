<template>
  <AppLayout>
    <div class="pomodoro-page">
      <!-- SETUP: seleccionar config, tarea, sonido -->
      <div v-if="!sesionActiva" class="setup-container">
        <h2>Iniciar Sesión Pomodoro</h2>
        <p class="text-muted" style="color: #b8b8b8;">Perfil activo: <strong style="color: #d34cf5;">{{ perfilActivo?.tituloPerfil }}</strong></p>

        <form @submit.prevent="iniciarSesion">
          <div class="mb-3">
            <label class="form-label">Configuración</label>
            <select v-model="form.idConfiguracionPomodoro" class="form-select" required>
              <option value="">Seleccionar configuración</option>
              <option v-for="c in configs" :key="c.idConfiguracionPomodoro" :value="c.idConfiguracionPomodoro">
                {{ c.duracionSesion }}min trabajo · {{ c.duracionDescansoCorto }}min descanso · {{ c.sesionesPrevioDescansoLargo }} ciclos
              </option>
            </select>
            <small class="text-muted d-block mt-1">
              ¿Sin configuraciones? <Link href="/pomodoro/config" style="color: #d34cf5;">Crea una aquí</Link>
            </small>
          </div>

          <div class="mb-3">
            <label class="form-label">Tarea</label>
            <select v-model="form.idTarea" class="form-select">
              <option value="">Sin tarea específica</option>
              <option v-for="t in tareas" :key="t.idTarea" :value="t.idTarea">
                {{ t.tituloTarea }}
              </option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Sonido ambiental</label>
            <select v-model="form.sonidoSeleccionado" class="form-select">
              <option value="">Sin sonido</option>
              <option v-for="(nombre, key) in sonidos" :key="key" :value="key">{{ nombre }}</option>
            </select>
            <small class="text-muted d-block mt-1" v-if="form.sonidoSeleccionado">
              <button type="button" class="btn btn-sm btn-outline-light mt-1" @click="previewSonido">▶ Vista previa</button>
            </small>
          </div>

          <div class="mb-4" v-if="form.sonidoSeleccionado">
            <label class="form-label">Volumen: {{ form.volumenSonido }}%</label>
            <input type="range" v-model.number="form.volumenSonido" min="0" max="100" class="volume-slider" />
          </div>

          <button type="submit" class="btn btn-start" :disabled="!form.idConfiguracionPomodoro">
            Iniciar Pomodoro
          </button>
        </form>
      </div>

      <!-- TIMER: sesión activa -->
      <div v-else class="main-container">
        <div class="timer-container glass">
          <div class="timer-display">{{ displayTime }}</div>
          <button class="pause-btn" @click="toggleTimer">
            {{ isRunning ? 'PAUSA' : (timerStarted ? 'CONTINUAR' : 'INICIAR') }}
          </button>
          <div class="session-status">
            <span class="status-text">{{ phaseText }}</span>
            <span class="cycle-counter">{{ cycleText }}</span>
          </div>
        </div>

        <div class="cards-container">
          <div class="task-container glass">
            <div class="task-indicator"></div>
            <div class="task-title">{{ sesionActiva.tituloTarea }}</div>
            <div class="task-description">{{ sesionActiva.descripcionTarea }}</div>
          </div>

          <div class="sound-container glass">
            <div class="sound-title">
              {{ soundLabel }}
            </div>
            <div v-if="sesionActiva.sonidoSeleccionado" class="volume-control">
              <input type="range" v-model.number="volumen" min="0" max="100" class="volume-slider" @input="adjustVolume" />
              <span class="volume-display">{{ volumen }}%</span>
            </div>
          </div>
        </div>

        <div class="additional-controls">
          <button class="control-btn" @click="resetTimer">🔄 Reiniciar</button>
          <button class="control-btn" @click="skipPhase">⏭️ Saltar Fase</button>
          <button class="control-btn" @click="endSession">🛑 Terminar Sesión</button>
        </div>
      </div>

      <audio ref="ambientAudio" loop preload="auto" v-if="audioSrc">
        <source :src="audioSrc" type="audio/mpeg" />
      </audio>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  configs: Array,
  tareas: Array,
  perfilActivo: Object,
  sesionActiva: Object
});

const sonidos = {
  lluvia: '🌧️ Lluvia',
  cascada: '💧 Cascada',
  tormenta: '⛈️ Tormenta',
  ventilador: '🌀 Ventilador',
  'ruido-marron': '🟤 Ruido Marrón',
  'ruido-blanco': '⚪ Ruido Blanco',
  lofi: '🎵 Lo-fi',
  jazz: '🎷 Jazz'
};

const form = useForm({
  idConfiguracionPomodoro: '',
  idTarea: '',
  sonidoSeleccionado: '',
  volumenSonido: 50
});

const ambientAudio = ref(null);
const currentPhase = ref('work');
const currentCycle = ref(1);
const totalSeconds = ref(0);
const currentSeconds = ref(0);
const isRunning = ref(false);
const timerStarted = ref(false);
let interval = null;
const volumen = ref(50);

const displayTime = computed(() => {
  const mins = Math.floor(currentSeconds.value / 60);
  const secs = currentSeconds.value % 60;
  return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
});

const phaseText = computed(() => {
  switch (currentPhase.value) {
    case 'work': return 'Sesión de Trabajo';
    case 'shortBreak': return 'Descanso Corto';
    case 'longBreak': return 'Descanso Largo';
    default: return 'Pomodoro';
  }
});

const cycleText = computed(() => {
  if (currentPhase.value === 'work') {
    return `Ciclo ${currentCycle.value} de ${props.sesionActiva?.sesionesPrevioDescansoLargo || 4}`;
  } else if (currentPhase.value === 'shortBreak') {
    return `entre ciclos ${currentCycle.value - 1} y ${currentCycle.value}`;
  } else {
    return 'Fin del ciclo completo';
  }
});

const audioSrc = computed(() => {
  if (!props.sesionActiva?.sonidoSeleccionado) return null;
  return `/sonidos/${props.sesionActiva.sonidoSeleccionado}.mp3`;
});

const soundLabel = computed(() => {
  if (!props.sesionActiva?.sonidoSeleccionado) return 'Sin sonido ambiente';
  const key = props.sesionActiva.sonidoSeleccionado;
  return `Estás escuchando: ${sonidos[key] || key}`;
});

const previewSonido = () => {
  const audio = new Audio(`/sonidos/${form.sonidoSeleccionado}.mp3`);
  audio.volume = form.volumenSonido / 100;
  audio.play().catch(() => {});
};

const iniciarSesion = () => {
  form.post(route('pomodoro.iniciar'));
};

const toggleTimer = () => {
  if (isRunning.value) pauseTimer();
  else startTimer();
};

const startTimer = () => {
  timerStarted.value = true;
  isRunning.value = true;

  if (ambientAudio.value && props.sesionActiva?.sonidoSeleccionado) {
    ambientAudio.value.play().catch(() => {});
  }

  interval = setInterval(() => {
    if (currentSeconds.value > 0) {
      currentSeconds.value--;
    } else {
      phaseComplete();
    }
  }, 1000);
};

const pauseTimer = () => {
  isRunning.value = false;
  clearInterval(interval);
  if (ambientAudio.value) ambientAudio.value.pause();
};

const resetTimer = () => {
  pauseTimer();
  timerStarted.value = false;
  currentSeconds.value = totalSeconds.value;
};

const phaseComplete = () => {
  pauseTimer();

  if (currentPhase.value === 'work') {
    router.post(route('pomodoro.registrar'), {}, {
      preserveState: true,
      preserveScroll: true
    });

    if (currentCycle.value >= (props.sesionActiva?.sesionesPrevioDescansoLargo || 4)) {
      currentPhase.value = 'longBreak';
      totalSeconds.value = (props.sesionActiva?.duracionDescansoLargo || 15) * 60;
    } else {
      currentPhase.value = 'shortBreak';
      totalSeconds.value = (props.sesionActiva?.duracionDescansoCorto || 5) * 60;
    }
  } else if (currentPhase.value === 'shortBreak') {
    currentPhase.value = 'work';
    currentCycle.value++;
    totalSeconds.value = (props.sesionActiva?.duracionSesion || 25) * 60;
  } else if (currentPhase.value === 'longBreak') {
    currentPhase.value = 'work';
    currentCycle.value = 1;
    totalSeconds.value = (props.sesionActiva?.duracionSesion || 25) * 60;
  }

  currentSeconds.value = totalSeconds.value;
};

const skipPhase = () => {
  currentSeconds.value = 0;
  if (!isRunning.value) phaseComplete();
};

const endSession = () => {
  if (currentPhase.value === 'work' && isRunning.value) {
    const completedMin = Math.floor((totalSeconds.value - currentSeconds.value) / 60);
    if (completedMin > 0) {
      router.post(route('pomodoro.registrar'), { minutosTrabajados: completedMin }, {
        preserveState: true,
        preserveScroll: true
      });
    }
  }

  pauseTimer();
  router.post(route('pomodoro.finalizar'));
};

const adjustVolume = () => {
  if (ambientAudio.value) {
    ambientAudio.value.volume = volumen.value / 100;
  }
};

const initTimer = () => {
  if (props.sesionActiva) {
    totalSeconds.value = props.sesionActiva.duracionSesion * 60;
    currentSeconds.value = totalSeconds.value;
    currentCycle.value = 1;
    currentPhase.value = 'work';
    volumen.value = props.sesionActiva.volumenSonido || 50;
  }
};

watch(() => props.sesionActiva, (val) => {
  if (val) initTimer();
}, { immediate: true });

onMounted(() => {
  if (ambientAudio.value) {
    ambientAudio.value.volume = volumen.value / 100;
  }
});

onUnmounted(() => {
  clearInterval(interval);
});

window.addEventListener('beforeunload', (e) => {
  if (isRunning.value) {
    e.preventDefault();
    e.returnValue = 'El temporizador está en ejecución.';
  }
});
</script>

<style scoped>
.pomodoro-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 80vh;
  padding: 20px;
  color: #fff;
}

.setup-container {
  background: #2e2e3e;
  border-radius: 20px;
  padding: 30px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
}

.setup-container h2 {
  text-align: center;
  margin-bottom: 25px;
  color: #f0e6f6;
}

.setup-container .form-label {
  color: #dcdcdc;
  font-weight: 500;
}

.setup-container .form-select,
.setup-container .form-control {
  background-color: #533763;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 10px 15px;
}

.setup-container .form-select:focus {
  box-shadow: 0 0 5px #a44fd1;
}

.btn-start {
  background-color: #a44fd1;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 12px 30px;
  font-weight: bold;
  width: 100%;
  font-size: 1.1rem;
}

.btn-start:hover {
  background-color: #8a3fb8;
  color: white;
}

.btn-start:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.main-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 30px;
  max-width: 1200px;
  width: 100%;
}

.glass {
  background: rgba(124, 129, 164, 0.3);
  backdrop-filter: blur(10px);
  border-radius: 30px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: transform 0.3s ease;
}

.glass:hover {
  transform: translateY(-3px);
}

.timer-container {
  padding: 40px 60px;
  text-align: center;
  position: relative;
  width: 100%;
  max-width: 800px;
}

.timer-display {
  font-size: 9rem;
  font-weight: 600;
  color: #fff;
  text-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
  letter-spacing: 2px;
  margin: 10px 0;
  line-height: 1;
}

.pause-btn {
  background: rgba(255, 255, 255, 0.9);
  color: #2D1B69;
  border: none;
  padding: 15px 40px;
  border-radius: 25px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-top: 20px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.pause-btn:hover {
  background: white;
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.session-status {
  margin-top: 20px;
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.1rem;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.status-text {
  font-weight: 600;
  letter-spacing: 0.5px;
}

.cycle-counter {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.7);
}

.cards-container {
  display: flex;
  justify-content: center;
  gap: 30px;
  flex-wrap: wrap;
  width: 100%;
}

.task-container {
  padding: 35px;
  width: 375px;
  max-width: 100%;
  position: relative;
}

.task-indicator {
  width: 35px;
  height: 35px;
  background: linear-gradient(135deg, #E91E63, #9C27B0);
  border-radius: 50%;
  position: absolute;
  top: 35px;
  left: 35px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.task-title {
  color: #fff;
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 15px;
  margin-left: 50px;
}

.task-description {
  color: rgb(177, 164, 164);
  font-size: 1.1rem;
  line-height: 1.6;
  margin-left: 50px;
}

.sound-container {
  padding: 30px;
  width: 375px;
  max-width: 100%;
  text-align: center;
}

.sound-title {
  color: rgb(177, 164, 164);
  font-weight: 600;
  font-size: 20px;
  margin-bottom: 15px;
}

.volume-control {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.volume-slider {
  width: 80%;
  height: 6px;
  -webkit-appearance: none;
  appearance: none;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  outline: none;
}

.volume-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  background: white;
  border-radius: 50%;
  cursor: pointer;
}

.volume-display {
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.9rem;
}

.additional-controls {
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
}

.control-btn {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  padding: 12px 20px;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(5px);
}

.control-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.btn-outline-light {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: #ddd;
  padding: 4px 12px;
  border-radius: 8px;
  font-size: 0.85rem;
  cursor: pointer;
}

.btn-outline-light:hover {
  background: rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
  .timer-display {
    font-size: 5rem;
  }
  .timer-container,
  .task-container,
  .sound-container {
    width: 100%;
  }
}
</style>
