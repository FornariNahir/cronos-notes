<template>
  <AppLayout>
    <div class="pomodoro-page">
      <!-- TIMER: sesión activa -->
      <div v-if="sesionActiva" class="main-container">
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

      <!-- SETUP: sin sesión activa -->
      <div v-else>
        <!-- MODAL DE SELECCIÓN INICIAL -->
        <div v-if="showOptionModal" class="modal fade show d-block" style="background: rgba(10, 10, 20, 0.85); backdrop-filter: blur(8px); z-index: 1100;">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background: #141426; border: 1px solid #d34cf5; border-radius: 24px; box-shadow: 0 10px 30px rgba(211, 76, 245, 0.2); color: white;">
              <div class="modal-body p-5">
                <h2 class="text-center fw-bold mb-2" style="color: #fff; font-size: 2.2rem;">Nueva Sesión Pomodoro</h2>
                <p class="text-center mb-5" style="font-size: 1.1rem; color: #b8b8b8;">Selecciona el modo en el que deseas trabajar hoy.</p>
                
                <div class="row g-4">
                  <!-- Tarjeta de Inicio Rápido -->
                  <div class="col-md-6">
                    <div class="selection-card h-100 p-4 d-flex flex-column justify-content-between align-items-center">
                      <div class="text-center mb-4">
                        <div class="icon-circle mb-3" style="background: rgba(211, 76, 245, 0.15); color: #d34cf5;">
                          <i class="fas fa-bolt" style="font-size: 2rem;"></i>
                        </div>
                        <h4 class="fw-bold" style="color: #fff;">Inicio Rápido</h4>
                        <p class="text-sm mt-2" style="color: #b8b8b8;">Un único ciclo clásico de 25 min de concentración seguido de 5 min de descanso. ¡Ideal para empezar ya!</p>
                      </div>
                      
                      <div class="w-100 mb-4">
                        <label class="form-label text-sm text-start d-block" style="color: #e0e0e0;">Tarea (Opcional)</label>
                        <select v-model="quickStartTaskId" class="form-select" style="background: #1f1f35; border: 1px solid #333; color: white;">
                          <option value="" style="background: #1f1f35; color: white;">Sin tarea específica</option>
                          <option v-for="t in tareas" :key="t.idTarea" :value="t.idTarea" style="background: #1f1f35; color: white;">
                            {{ t.tituloTarea }}
                          </option>
                        </select>
                      </div>
                      
                      <button class="btn btn-primario w-100" @click="iniciarInicioRapido" style="padding: 12px; font-weight: bold; border-radius: 12px;">
                        Iniciar 25 min
                      </button>
                    </div>
                  </div>

                  <!-- Tarjeta de Sesión Completa -->
                  <div class="col-md-6">
                    <div class="selection-card h-100 p-4 d-flex flex-column justify-content-between align-items-center">
                      <div class="text-center mb-4">
                        <div class="icon-circle mb-3" style="background: rgba(0, 180, 216, 0.15); color: #00b4d8;">
                          <i class="fas fa-sliders-h" style="font-size: 2rem;"></i>
                        </div>
                        <h4 class="fw-bold" style="color: #fff;">Sesión Personalizada</h4>
                        <p class="text-sm mt-2" style="color: #b8b8b8;">Elige entre tus diferentes configuraciones de tiempo guardadas, asocia tareas específicas y añade sonidos ambientales.</p>
                      </div>
                      
                      <button class="btn btn-secundario w-100 mt-auto" @click="showOptionModal = false" style="padding: 12px; font-weight: bold; border-radius: 12px;">
                        Personalizar Sesión
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- SETUP: seleccionar config, tarea, sonido -->
        <div v-else class="setup-container">
          <button type="button" class="btn btn-sm btn-outline-light mb-3" @click="showOptionModal = true">
            ← Volver a opciones
          </button>
          <h2>Iniciar Sesión Pomodoro</h2>
          <p class="text-muted" style="color: #b8b8b8;">Perfil activo: <strong style="color: #d34cf5;">{{ perfilActivo?.tituloPerfil }}</strong></p>

          <form @submit.prevent="iniciarSesion">
            <!-- Plantilla guardada -->
            <div class="mb-4 text-start">
              <label class="form-label">Plantillas Guardadas</label>
              <div class="d-flex gap-2">
                <select v-model="selectedConfigId" class="form-select">
                  <option value="">-- Personalizada / Nueva --</option>
                  <option v-for="c in configs" :key="c.idConfiguracionPomodoro" :value="c.idConfiguracionPomodoro">
                    Trabajo: {{ c.duracionSesion }}m · Descansos: {{ c.duracionDescansoCorto }}m/{{ c.duracionDescansoLargo }}m ({{ c.sesionesPrevioDescansoLargo }} ciclos)
                  </option>
                </select>
                <button 
                  v-if="selectedConfigId" 
                  type="button" 
                  class="btn btn-outline-danger" 
                  @click="eliminarConfig" 
                  title="Eliminar plantilla seleccionada"
                  style="border-color: #dc3545; color: #dc3545; background: transparent; border-radius: 8px; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border: 1px solid #dc3545; cursor: pointer;"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </div>

            <!-- Tiempos y ciclos en grid -->
            <div class="row g-3 mb-4 text-start">
              <div class="col-md-6 col-lg-3">
                <label class="form-label text-sm">Concentración (min)</label>
                <input v-model.number="form.duracionSesion" type="number" min="1" max="120" class="form-control" required />
              </div>
              <div class="col-md-6 col-lg-3">
                <label class="form-label text-sm">Descanso Corto (min)</label>
                <input v-model.number="form.duracionDescansoCorto" type="number" min="1" max="30" class="form-control" required />
              </div>
              <div class="col-md-6 col-lg-3">
                <label class="form-label text-sm">Descanso Largo (min)</label>
                <input v-model.number="form.duracionDescansoLargo" type="number" min="5" max="60" class="form-control" required />
              </div>
              <div class="col-md-6 col-lg-3">
                <label class="form-label text-sm">Ciclos Objetivo</label>
                <input v-model.number="form.ciclosObjetivo" type="number" min="1" max="10" class="form-control" required />
              </div>
            </div>

            <!-- Tarea -->
            <div class="mb-4 text-start">
              <label class="form-label">Tarea</label>
              <select v-model="form.idTarea" class="form-select">
                <option value="">Sin tarea específica</option>
                <option v-for="t in tareas" :key="t.idTarea" :value="t.idTarea">
                  {{ t.tituloTarea }}
                </option>
              </select>
            </div>

            <!-- Sonido ambiental -->
            <div class="mb-4 text-start">
              <label class="form-label">Sonido ambiental</label>
              <select v-model="form.sonidoSeleccionado" class="form-select">
                <option value="">Sin sonido</option>
                <option v-for="(nombre, key) in sonidos" :key="key" :value="key">{{ nombre }}</option>
              </select>
              <small class="text-muted d-block mt-1" v-if="form.sonidoSeleccionado">
                <button type="button" class="btn btn-sm btn-outline-light mt-1" @click="previewSonido">▶ Vista previa</button>
              </small>
            </div>

            <!-- Volumen -->
            <div class="mb-4 text-start" v-if="form.sonidoSeleccionado">
              <label class="form-label">Volumen: {{ form.volumenSonido }}%</label>
              <input type="range" v-model.number="form.volumenSonido" min="0" max="100" class="volume-slider" />
            </div>

            <button type="submit" class="btn btn-start">
              Iniciar Pomodoro
            </button>
          </form>
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

const showOptionModal = ref(true);
const quickStartTaskId = ref('');

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
  duracionSesion: 25,
  duracionDescansoCorto: 5,
  duracionDescansoLargo: 15,
  ciclosObjetivo: 4,
  idTarea: '',
  sonidoSeleccionado: '',
  volumenSonido: 50
});

const selectedConfigId = ref('');

watch(selectedConfigId, (newId) => {
  if (newId) {
    const config = props.configs.find(c => c.idConfiguracionPomodoro == newId);
    if (config) {
      form.duracionSesion = config.duracionSesion;
      form.duracionDescansoCorto = config.duracionDescansoCorto;
      form.duracionDescansoLargo = config.duracionDescansoLargo;
      form.ciclosObjetivo = config.sesionesPrevioDescansoLargo;
    }
  }
});

const eliminarConfig = () => {
  if (!selectedConfigId.value) return;
  if (confirm('¿Estás seguro de que deseas eliminar esta configuración de forma permanente?')) {
    router.delete(route('pomodoro.config.destroy', selectedConfigId.value), {
      onSuccess: () => {
        selectedConfigId.value = '';
      }
    });
  }
};

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

const iniciarInicioRapido = () => {
  router.post(route('pomodoro.iniciar'), {
    esInicioRapido: true,
    idTarea: quickStartTaskId.value
  });
};

const getStorageKey = () => {
  return props.sesionActiva ? `pomodoro_session_${props.sesionActiva.idSesion}` : null;
};

const saveStateToStorage = () => {
  const key = getStorageKey();
  if (!key) return;
  const state = {
    currentPhase: currentPhase.value,
    currentCycle: currentCycle.value,
    totalSeconds: totalSeconds.value,
    currentSeconds: currentSeconds.value,
    isRunning: isRunning.value,
    timerStarted: timerStarted.value
  };
  localStorage.setItem(key, JSON.stringify(state));
};

const loadStateFromStorage = () => {
  const key = getStorageKey();
  if (!key) return false;
  const stored = localStorage.getItem(key);
  if (!stored) return false;
  try {
    const state = JSON.parse(stored);
    currentPhase.value = state.currentPhase;
    currentCycle.value = state.currentCycle;
    totalSeconds.value = state.totalSeconds;
    currentSeconds.value = state.currentSeconds;
    isRunning.value = state.isRunning;
    timerStarted.value = state.timerStarted;
    return true;
  } catch (e) {
    return false;
  }
};

const clearStorage = () => {
  const key = getStorageKey();
  if (key) localStorage.removeItem(key);
};

const toggleTimer = () => {
  if (isRunning.value) pauseTimer();
  else startTimer();
};

const startTimer = () => {
  timerStarted.value = true;
  isRunning.value = true;
  saveStateToStorage();

  router.patch(route('pomodoro.estado'), { estado: 'En Progreso' }, {
    preserveState: true,
    preserveScroll: true
  });

  if (ambientAudio.value && props.sesionActiva?.sonidoSeleccionado) {
    ambientAudio.value.play().catch(() => {});
  }

  clearInterval(interval);
  interval = setInterval(() => {
    if (currentSeconds.value > 0) {
      currentSeconds.value--;
      saveStateToStorage();
    } else {
      phaseComplete();
    }
  }, 1000);
};

const pauseTimer = () => {
  isRunning.value = false;
  clearInterval(interval);
  saveStateToStorage();
  
  if (ambientAudio.value) ambientAudio.value.pause();

  router.patch(route('pomodoro.estado'), { estado: 'Pausada' }, {
    preserveState: true,
    preserveScroll: true
  });
};

const resetTimer = () => {
  pauseTimer();
  timerStarted.value = false;
  currentSeconds.value = totalSeconds.value;
  saveStateToStorage();
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
  saveStateToStorage();
};

const skipPhase = () => {
  currentSeconds.value = 0;
  saveStateToStorage();
  if (!isRunning.value) phaseComplete();
};

const endSession = () => {
  let finalEstado = 'Cancelada';
  let minutosIncompletos = 0;

  if (currentPhase.value === 'work') {
    minutosIncompletos = Math.floor((totalSeconds.value - currentSeconds.value) / 60);
  }

  const ciclosCompletados = currentCycle.value - 1 + (currentPhase.value !== 'work' ? 1 : 0);
  const ciclosObjetivo = props.sesionActiva?.sesionesPrevioDescansoLargo || 4;

  if (ciclosCompletados >= ciclosObjetivo) {
    finalEstado = 'Completada';
  }

  pauseTimer();
  clearStorage();

  router.post(route('pomodoro.finalizar'), {
    estado: finalEstado,
    minutosTrabajados: minutosIncompletos
  });
};

const adjustVolume = () => {
  if (ambientAudio.value) {
    ambientAudio.value.volume = volumen.value / 100;
  }
};

const initTimer = () => {
  if (props.sesionActiva) {
    volumen.value = props.sesionActiva.volumenSonido || 50;

    const restored = loadStateFromStorage();
    if (!restored) {
      totalSeconds.value = props.sesionActiva.duracionSesion * 60;
      currentSeconds.value = totalSeconds.value;
      currentCycle.value = 1;
      currentPhase.value = 'work';
      timerStarted.value = false;
      isRunning.value = false;
    } else {
      if (isRunning.value) {
        startTimer();
      }
    }
  }
};

watch(() => props.sesionActiva, (val) => {
  if (val) {
    initTimer();
  } else {
    Object.keys(localStorage).forEach(key => {
      if (key.startsWith('pomodoro_session_')) {
        localStorage.removeItem(key);
      }
    });
  }
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

.selection-card {
  background: rgba(31, 31, 53, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  transition: all 0.3s ease;
  cursor: pointer;
  text-align: center;
  color: white;
}

.selection-card select,
.selection-card select option {
  color: white !important;
  background-color: #1f1f35 !important;
}

.selection-card:hover {
  transform: translateY(-5px);
  border-color: rgba(211, 76, 245, 0.4);
  box-shadow: 0 8px 24px rgba(211, 76, 245, 0.15);
}

.icon-circle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}
</style>
