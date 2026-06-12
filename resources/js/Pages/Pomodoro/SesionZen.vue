<script setup>
import { ref, computed, onMounted, onUnmounted, watch, reactive } from 'vue';
import { Link, usePage, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePomodoroTimer } from '@/Composables/usePomodoroTimer';
import { vDraggable } from '@/Directives/vDraggable';
import ProfileSelectionModal from '@/Components/ProfileSelectionModal.vue';
import AlertModal from '@/Components/AlertModal.vue';

const props = defineProps({
  configs: {
    type: Array,
    default: () => []
  },
  tareas: {
    type: Array,
    default: () => []
  },
  perfilActivo: Object,
  sesionActiva: Object,
  mustSelectProfile: Boolean,
  perfiles: Array,
  isGuest: {
    type: Boolean,
    default: false
  }
});

const showAlertModal = ref(false);
const alertTitle = ref('');
const alertMessage = ref('');
const showCustomAlert = (title, message) => {
  alertTitle.value = title;
  alertMessage.value = message;
  showAlertModal.value = true;
};

const showConfirmEndModal = ref(false);
const showTaskCompletePrompt = ref(false);

const cancelEndSession = () => {
  showConfirmEndModal.value = false;
};

const confirmEndSession = () => {
  showConfirmEndModal.value = false;
  if (localSesionActiva.value && localSesionActiva.value.idTarea) {
    showTaskCompletePrompt.value = true;
  } else {
    proceedEndSession(null);
  }
};

const handleTaskCompletionPrompt = (completed) => {
  showTaskCompletePrompt.value = false;
  proceedEndSession(completed);
};

const proceedEndSession = (marcarTareaCompletada) => {
  for (const key in howlerInstances) {
    if (howlerInstances[key]) howlerInstances[key].stop();
  }
  endSession(marcarTareaCompletada);
};

const page = usePage();

// State variables
const isDarkMode = ref(false);
const selectedSound = ref('Ninguno');
const timeLeft = ref(25 * 60);
const selectedLandscape = ref('paisaje1');
const isFullscreen = ref(false);
const isMinimized = ref(false);
const isDistractionFree = ref(false);

const toggleDistractionFree = () => {
  isDistractionFree.value = !isDistractionFree.value;
  if (isDistractionFree.value) {
    document.body.classList.add('distraction-free-mode');
  } else {
    document.body.classList.remove('distraction-free-mode');
  }
};

const toggleMinimize = () => {
  isMinimized.value = !isMinimized.value;
};

const syncFullscreenState = () => {
  isFullscreen.value = !!document.fullscreenElement;
};

const toggleFullscreen = () => {
  const container = document.getElementById('app-pomodoro');
  if (!container) return;

  if (!document.fullscreenElement) {
    container.requestFullscreen().then(() => {
      isFullscreen.value = true;
    }).catch(err => {
      console.error(`Error entering fullscreen: ${err.message}`);
    });
  } else {
    document.exitFullscreen();
    isFullscreen.value = false;
  }
};

const settingsPanelOpen = ref(false);
const modalAvanzadoOpen = ref(false);
const modalRegistroOpen = ref(false);
const mensajeRegistro = ref('');
const activeTab = ref('tab-fondos');

const timerWidget = ref(null);
const settingsToggle = ref(null);

let timerInterval = null;
const howlerInstances = {};
const mixerState = reactive({});

// Sound and landscape database (root-relative public paths)
const bancoSonidos = {
  'Tormenta': '/audios/storm',
  'Agua': '/audios/water',
  'Fogata': '/audios/campfire',
  'Lluvia': '/audios/rain',
  'Olas del Mar': '/audios/waves',
  'Aire': '/audios/air',
  'Viento': '/audios/wind',
  'Bosque': '/audios/forest',
  'Lluvia Bosque': '/audios/forest-rain',
  'Ambiente de Fondo': '/audios/background-music',
  'Cafetería': '/audios/cafe',
  'Tráfico': '/audios/city-traffic',
  'Teclado': '/audios/keyboard',
  'Meditación del Tigre': '/audios/meditative-tiger',
  'Ruido Blanco': '/audios/white-noise',
  'Ruido Rosa': '/audios/pink-noise',
  'Ruido Marrón': '/audios/brown-noise',
  'Tibetanos': '/audios/tibetan-meditation'
};

for (const key in bancoSonidos) {
  mixerState[key] = { active: false, volume: 0.5 };
}

const bancoFondos = {
  'paisaje1': { claro: '/imagenes/atardecer.webp', oscuro: '/imagenes/noche.webp', nombre: 'Paisaje 1' },
  'paisaje2': { claro: '/imagenes/paisaje2-claro.webp', oscuro: '/imagenes/paisaje2-oscuro.webp', nombre: 'Paisaje 2' },
  'paisaje3': { claro: '/imagenes/paisaje3-claro.webp', oscuro: '/imagenes/paisaje3-oscuro.webp', nombre: 'Paisaje 3' },
  'paisaje4': { claro: '/imagenes/paisaje4-claro.webp', oscuro: '/imagenes/paisaje4-oscuro.webp', nombre: 'Paisaje 4' },
  'paisaje5': { claro: '/imagenes/paisaje5-claro.webp', oscuro: '/imagenes/paisaje5-oscuro.webp', nombre: 'Paisaje 5' },
  'paisaje6': { claro: '/imagenes/paisaje6-claro.webp', oscuro: '/imagenes/paisaje6-oscuro.webp', nombre: 'Paisaje 6' },
  'paisaje7': { claro: '/imagenes/paisaje7-claro.webp', oscuro: '/imagenes/paisaje7-oscuro.webp', nombre: 'Paisaje 7' },
  'paisaje8': { claro: '/imagenes/paisaje8-claro.webp', oscuro: '/imagenes/paisaje8-oscuro.webp', nombre: 'Paisaje 8' },
  'paisaje9': { claro: '/imagenes/paisaje9-claro.webp', oscuro: '/imagenes/paisaje9-oscuro.webp', nombre: 'Paisaje 9' },
  'paisaje4-gif': { 
    claro: '/gif/paisaje4-claroGif.mp4', 
    oscuro: '/gif/paisaje4-oscuroGif.mp4', 
    claroPreview: '/imagenes/paisaje4-claro.webp',
    oscuroPreview: '/imagenes/paisaje4-oscuro.webp',
    nombre: 'Paisaje 4 Animado', 
    isVideo: true 
  },
  'paisaje9-gif': { 
    claro: '/gif/paisaje9-claroGif.mp4', 
    oscuro: '/gif/paisaje9-oscuroGif.mp4', 
    claroPreview: '/imagenes/paisaje9-claro.webp',
    oscuroPreview: '/imagenes/paisaje9-oscuro.webp',
    nombre: 'Paisaje 9 Animado', 
    isVideo: true 
  }
};

const staticLandscapes = computed(() => {
  const result = {};
  for (const [key, val] of Object.entries(bancoFondos)) {
    if (!val.isVideo) result[key] = val;
  }
  return result;
});

const videoLandscapes = computed(() => {
  const result = {};
  for (const [key, val] of Object.entries(bancoFondos)) {
    if (val.isVideo) result[key] = val;
  }
  return result;
});

const isLandscapeLocked = (key) => {
  if (!props.isGuest) return false;
  return !['paisaje1', 'paisaje2', 'paisaje3', 'paisaje4'].includes(key);
};

const isSoundLocked = (key) => {
  if (!props.isGuest) return false;
  return !['Lluvia', 'Cafetería', 'Viento'].includes(key);
};

const handleSelectLandscape = (key) => {
  if (isLandscapeLocked(key)) {
    mensajeRegistro.value = 'Este fondo es exclusivo para usuarios registrados. ¿Deseas crear una cuenta gratis para desbloquearlo?';
    modalRegistroOpen.value = true;
    return;
  }
  selectedLandscape.value = key;
};

const appBackgroundClass = computed(() => {
  const info = bancoFondos[selectedLandscape.value];
  if (info && !info.isVideo) {
    return `fondo-${selectedLandscape.value}-${isDarkMode.value ? 'oscuro' : 'claro'}`;
  }
  return '';
});

const currentVideoSrc = computed(() => {
  const info = bancoFondos[selectedLandscape.value];
  if (info && info.isVideo) {
    return isDarkMode.value ? info.oscuro : info.claro;
  }
  return '';
});

const {
  isRunning,
  currentPhase,
  currentCycle,
  timeLeft: timerTime,
  quickStartTaskId,
  selectedConfigId,
  form,
  iniciarInicioRapido,
  iniciarSesion,
  initTimer,
  startTimer,
  stopTimerLogic,
  resetTimerLogic,
  completePhase,
  endSession,
  saveStateToStorage,
  totalSeconds,
  localSesionActiva
} = usePomodoroTimer(props);

watch(() => localSesionActiva.value, () => {
  initTimer();
}, { immediate: true });

const displayTime = computed(() => {
  const mins = Math.floor(timerTime.value / 60);
  const secs = timerTime.value % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

const soundCategories = {
  Naturaleza: ['Tormenta', 'Agua', 'Fogata', 'Lluvia', 'Olas del Mar', 'Bosque', 'Lluvia Bosque', 'Viento'],
  Ambiente: ['Aire', 'Ambiente de Fondo', 'Cafetería', 'Tráfico', 'Teclado'],
  Meditacion: ['Meditación del Tigre', 'Ruido Blanco', 'Ruido Rosa', 'Ruido Marrón', 'Tibetanos']
};

const getSoundEmoji = (key) => {
  switch (key) {
    case 'Tormenta': return '⚡';
    case 'Agua': return '💧';
    case 'Fogata': return '🔥';
    case 'Lluvia': return '🌧️';
    case 'Olas del Mar': return '🌊';
    case 'Aire': return '💨';
    case 'Viento': return '🌬️';
    case 'Bosque': return '🌲';
    case 'Lluvia Bosque': return '🌿';
    case 'Ambiente de Fondo': return '👤';
    case 'Cafetería': return '☕';
    case 'Tráfico': return '🚗';
    case 'Teclado': return '⌨️';
    case 'Meditación del Tigre': return '🐯';
    case 'Ruido Blanco': return '📺';
    case 'Ruido Rosa': return '🌸';
    case 'Ruido Marrón': return '🐻';
    case 'Tibetanos': return '🥣';
    default: return '🎵';
  }
};

onMounted(() => {
  if (!document.querySelector('link[href*="bootstrap-icons"]')) {
      const linkIcons = document.createElement('link');
      linkIcons.rel = 'stylesheet';
      linkIcons.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
      document.head.appendChild(linkIcons);
  }

  document.addEventListener('fullscreenchange', syncFullscreenState);

  if (!window.Howl) {
    const scriptHowl = document.createElement('script');
    scriptHowl.src = 'https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.4/howler.min.js';
    scriptHowl.onload = () => { console.log("Howler.js loaded."); };
    document.head.appendChild(scriptHowl);
  }

  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  clearInterval(timerInterval);
  if (sonidoHowlerActivo) {
    sonidoHowlerActivo.stop();
    sonidoHowlerActivo.unload();
  }
  document.body.classList.remove('distraction-free-mode');
  window.removeEventListener('keydown', handleKeyDown);
  document.removeEventListener('fullscreenchange', syncFullscreenState);
});

const handleKeyDown = (e) => {
  if (e.key === 'Escape') cerrarModalAvanzado();
};

const updateMixerVolume = (soundKey) => {
  if (howlerInstances[soundKey]) {
    howlerInstances[soundKey].volume(mixerState[soundKey].volume);
  }
};

const toggleMixerSound = (soundKey) => {
  if (isSoundLocked(soundKey)) {
    mensajeRegistro.value = 'Este sonido es exclusivo para usuarios registrados. ¿Deseas crear una cuenta gratis para desbloquearlo?';
    modalRegistroOpen.value = true;
    return;
  }
  const state = mixerState[soundKey];
  state.active = !state.active;

  if (state.active) {
    if (!howlerInstances[soundKey] && window.Howl) {
      howlerInstances[soundKey] = new window.Howl({
        src: [`${bancoSonidos[soundKey]}.webm`, `${bancoSonidos[soundKey]}.mp3`],
        html5: true,
        loop: true,
        volume: state.volume,
        onplayerror: function() {
          if (howlerInstances[soundKey]) {
            howlerInstances[soundKey].once('unlock', function() {
              howlerInstances[soundKey].play();
            });
          }
        }
      });
    }
    if (howlerInstances[soundKey]) howlerInstances[soundKey].play();
  } else {
    if (howlerInstances[soundKey]) howlerInstances[soundKey].pause();
  }
};

const startTimerWrapper = (isRestored = false) => {
  if (settingsPanelOpen.value) {
    settingsPanelOpen.value = false;
  }
  startTimer(null, () => {
    if (localSesionActiva.value) {
      showCustomAlert("¡Tiempo cumplido!", "El pomodoro ha finalizado exitosamente.");
    } else {
      if (currentPhase.value === 'work') {
        showCustomAlert("¡Sesión finalizada!", "¡Sesión de trabajo finalizada! Es hora de un descanso.");
      } else {
        showCustomAlert("¡Descanso terminado!", "¡Descanso terminado! Volvemos al trabajo.");
      }
    }
    completePhase();
  }, isRestored);
  
  if (!isRestored) {
    for (const key in howlerInstances) {
      if (mixerState[key] && mixerState[key].active && howlerInstances[key]) {
        howlerInstances[key].play();
      }
    }
  }
};

const stopTimerWrapper = () => {
  stopTimerLogic();
  for (const key in howlerInstances) {
    if (mixerState[key] && mixerState[key].active && howlerInstances[key]) {
      howlerInstances[key].pause();
    }
  }
};

const resetTimerWrapper = () => {
  resetTimerLogic();
  for (const key in howlerInstances) {
    if (howlerInstances[key]) howlerInstances[key].stop();
  }
};

const pageTitle = computed(() => {
  if (!localSesionActiva.value) return 'Temporizador Libre';
  return `${localSesionActiva.value.tituloTarea} - Pomodoro`;
});

const endSessionWrapper = () => {
  stopTimerWrapper();
  showConfirmEndModal.value = true;
};

const phaseText = computed(() => {
  if (!localSesionActiva.value) return 'Temporizador Libre';
  if (currentPhase.value === 'work') return 'Trabajo';
  if (currentPhase.value === 'shortBreak') return 'Descanso Corto';
  return 'Descanso Largo';
});

const aplicarCambioModo = (oscuro) => {
  isDarkMode.value = oscuro;
};

// Removed legacy toggleSound logic

const toggleSettingsPanel = (e) => {
  if (settingsToggle.value && settingsToggle.value.dataset.preventClick === "true") {
    e.preventDefault();
    e.stopPropagation();
    return;
  }
  settingsPanelOpen.value = !settingsPanelOpen.value;
};

const abrirModalAvanzado = (e) => {
  if (e) {
    if (typeof e.preventDefault === 'function') e.preventDefault();
    if (typeof e.stopPropagation === 'function') e.stopPropagation();
  }
  settingsPanelOpen.value = false;
  modalAvanzadoOpen.value = true;
};

const cerrarModalAvanzado = () => {
  modalAvanzadoOpen.value = false;
};

// Directives handle dragging now
</script>

<template>
  <component :is="isGuest ? 'div' : AppLayout" :class="{ 'min-vh-100': isGuest }">
    <ProfileSelectionModal v-if="mustSelectProfile" :perfiles="perfiles || []" />

    <div 
      v-else
      id="app-pomodoro" 
      class="pomodoro-zen-container" 
      :class="[
        isDarkMode ? 'dark-mode' : 'light-mode', 
        appBackgroundClass,
        { 'fullscreen-zen': isFullscreen }
      ]"
    >
      <!-- Floating Action Controls (Top Right) -->
      <div class="floating-controls">
        <Link 
          v-if="isGuest"
          href="/" 
          class="floating-btn text-decoration-none d-flex align-items-center justify-content-center bg-danger text-white border-0" 
          title="Volver al inicio"
        >
          <i class="bi bi-box-arrow-left"></i>
        </Link>
        <button 
          @click="toggleFullscreen" 
          class="floating-btn" 
          :title="isFullscreen ? 'Salir de Pantalla Completa' : 'Pantalla Completa'"
        >
          <i class="bi" :class="isFullscreen ? 'bi-fullscreen-exit' : 'bi-fullscreen'"></i>
        </button>
        <Link 
          href="/dashboard" 
          class="floating-btn" 
          title="Volver al Dashboard"
        >
          <i class="bi bi-house-door-fill"></i>
        </Link>
        <button 
          @click="aplicarCambioModo(!isDarkMode)" 
          class="floating-btn" 
          :title="isDarkMode ? 'Modo Claro' : 'Modo Oscuro'"
        >
          <i class="bi" :class="isDarkMode ? 'bi-sun-fill' : 'bi-moon-stars-fill'"></i>
        </button>
        <button 
          @click="toggleDistractionFree" 
          class="floating-btn" 
          :title="isDistractionFree ? 'Mostrar Interfaz' : 'Ocultar Interfaz (Modo Zen)'"
        >
          <i class="bi" :class="isDistractionFree ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
        </button>
        <button 
          @click="abrirModalAvanzado" 
          class="floating-btn" 
          title="Configuración Avanzada"
        >
          <i class="bi bi-gear-fill"></i>
        </button>
      </div>
      <!-- Background Video support -->
      <video 
        v-show="bancoFondos[selectedLandscape]?.isVideo" 
        id="background-video" 
        class="background-video" 
        :src="currentVideoSrc" 
        autoplay 
        loop 
        muted 
        playsinline
      ></video>
      
      <!-- Draggable Timer Widget -->
      <div v-draggable ref="timerWidget" class="widget timer-widget" :class="{ 'minimized': isMinimized }" style="left: 40px; bottom: 40px;">
        <div class="widget-header-controls">
          <div class="drag-handle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="8" y1="6" x2="8" y2="18"></line>
              <line x1="16" y1="6" x2="16" y2="18"></line>
            </svg>
          </div>
          <button class="minimize-btn" @click="toggleMinimize" :title="isMinimized ? 'Maximizar' : 'Minimizar'">
            <svg v-if="!isMinimized" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="4 14 12 6 20 14"></polyline>
            </svg>
          </button>
        </div>
        
        <div v-show="!isMinimized" class="timer-content" :class="{ 'with-setup': !localSesionActiva }">
          
          <template v-if="localSesionActiva">
            <div class="phase-indicator">{{ phaseText }} <span v-if="currentPhase === 'work'">- Ciclo {{ currentCycle }}</span></div>
            <div v-if="localSesionActiva.tituloTarea" class="task-badge">{{ localSesionActiva.tituloTarea }}</div>
            
            <div class="timer-controls mt-2">
              <button v-show="!isRunning" @click="startTimerWrapper(false)" class="control-btn" aria-label="Iniciar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="5,3 19,12 5,21"></polygon>
                </svg>
              </button>
              <button v-show="isRunning" @click="stopTimerWrapper" class="control-btn" aria-label="Pausar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <rect x="6" y="4" width="4" height="16"></rect>
                  <rect x="14" y="4" width="4" height="16"></rect>
                </svg>
              </button>
              <button @click="resetTimerWrapper" class="control-btn" aria-label="Reiniciar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                </svg>
              </button>
            </div>
            <div id="timer-display" class="timer-display">{{ displayTime }}</div>
            
            <button @click="endSessionWrapper" class="btn-cancel-session mt-2">Terminar Sesión</button>
          </template>

          <template v-else>
            <div class="setup-header">Configurar Sesión</div>
            <div class="setup-body mt-2">
              <div v-if="!isGuest">
                <label class="zen-label">Tarea a realizar (Opcional)</label>
                <select v-model="quickStartTaskId" class="form-select-zen mb-3">
                  <option value="">Sin tarea específica</option>
                  <option v-for="t in tareas" :key="t.idTarea" :value="t.idTarea">{{ t.tituloTarea }}</option>
                </select>
              </div>
              
              <button @click="iniciarInicioRapido" class="btn-zen-primary w-100 mb-3">Inicio Rápido (25 min)</button>
              
              <div class="zen-divider"><span>O PERSONALIZADO</span></div>
              
              <div v-if="!isGuest">
                <label class="zen-label">Plantilla Guardada</label>
                <select v-model="selectedConfigId" class="form-select-zen mb-2">
                  <option value="">-- Manual --</option>
                  <option v-for="c in configs" :key="c.idConfiguracionPomodoro" :value="c.idConfiguracionPomodoro">
                    Trabajo: {{ c.duracionSesion }}m | Desc: {{ c.duracionDescansoCorto }}m
                  </option>
                </select>
              </div>
              
              <div v-if="!selectedConfigId" class="custom-times mb-3">
                 <div class="time-input-group">
                   <label>Trabajo</label>
                   <input type="number" v-model="form.duracionSesion" class="input-zen" min="1" max="120" />
                 </div>
                 <div class="time-input-group">
                   <label>Descanso</label>
                   <input type="number" v-model="form.duracionDescansoCorto" class="input-zen" min="1" max="30" />
                 </div>
                 <div class="time-input-group">
                   <label>Ciclos</label>
                   <input type="number" v-model="form.ciclosObjetivo" class="input-zen" min="1" max="10" />
                 </div>
              </div>

              <button @click="iniciarSesion" class="btn-zen-secondary w-100">Iniciar Sesión Personalizada</button>
            </div>
          </template>
        </div>

        <!-- Minimized View -->
        <div v-show="isMinimized" class="timer-minimized-view" @click="toggleMinimize">
          <span v-if="localSesionActiva" class="mini-time">{{ displayTime }}</span>
          <span v-else class="mini-icon">⚙️ Setup</span>
        </div>
      </div>

      <!-- Draggable Sound Settings widget -->
      <div v-draggable ref="settingsToggle" class="widget settings-toggle-widget" style="left: 40px; top: 40px;">
        <div class="widget-header-controls sound-drag-handle-container">
          <div class="drag-handle sound-drag-handle">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="8" y1="6" x2="8" y2="18"></line>
              <line x1="16" y1="6" x2="16" y2="18"></line>
            </svg>
          </div>
        </div>
        
        <button id="toggle-settings-btn" class="settings-btn" @click="toggleSettingsPanel">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
          </svg>
          <span id="current-sound">Mezclador de Sonidos</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </button>

        <div class="settings-panel" :class="{ open: settingsPanelOpen }">
          <div class="settings-content">
            
            <div class="sound-header mt-2">MEZCLADOR DE SONIDOS</div>
            <div class="sound-options mixer-options">
              <div 
                v-for="(state, soundKey) in mixerState" 
                :key="soundKey" 
                class="mixer-row"
              >
                <div class="mixer-label" @click="toggleMixerSound(soundKey)">
                  {{ soundKey }} <span v-if="isSoundLocked(soundKey)" title="Exclusivo para registrados">🔒</span>
                </div>
                <div class="mixer-control-pill" :class="{ active: state.active }">
                  <div class="mixer-icon" @click="toggleMixerSound(soundKey)">
                    {{ getSoundEmoji(soundKey) }}
                  </div>
                  <div class="mixer-slider-container">
                    <input 
                      type="range" 
                      class="mixer-slider" 
                      v-model.number="state.volume" 
                      min="0" max="1" step="0.05"
                      @input="updateMixerVolume(soundKey)"
                      :disabled="!state.active"
                    />
                  </div>
                </div>
              </div>
              
              <button type="button" class="sound-option opcion-tuerca-mas" @click="abrirModalAvanzado">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px; vertical-align: -2px;">
                  <circle cx="12" cy="12" r="3"></circle>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <strong>Más opciones...</strong>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Advanced Settings Modal (Teleported to avoid layout clipping) -->
    <Teleport to="body">
      <div v-show="modalAvanzadoOpen" class="modal-avanzado-backdrop" :class="{ 'dark-mode': isDarkMode }" @click.self="cerrarModalAvanzado">
        <div class="modal-avanzado-content" :class="{ 'dark-mode': isDarkMode }">
          <div class="modal-avanzado-header">
            <h5 class="m-0">Configuración Avanzada</h5>
            <button @click="cerrarModalAvanzado" class="btn-close-x">&times;</button>
          </div>
          
          <div class="modal-tabs">
            <button 
              class="tab-link" 
              :class="{ active: activeTab === 'tab-fondos' }" 
              @click="activeTab = 'tab-fondos'"
            >Fondo Estático</button>
            <button 
              class="tab-link" 
              :class="{ active: activeTab === 'tab-gifs' }" 
              @click="activeTab = 'tab-gifs'"
            >Fondos Animados (GIF)</button>
          </div>

          <!-- Tab Fondos Estaticos -->
          <div v-show="activeTab === 'tab-fondos'" class="tab-panel active">
            <div class="setting-row pb-3 mb-3 border-bottom">
              <div class="setting-label">
                <svg class="mode-icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="5"></circle>
                  <line x1="12" y1="1" x2="12" y2="3"></line>
                  <line x1="12" y1="21" x2="12" y2="23"></line>
                </svg>
                <span class="fw-medium ms-2">Alternar Modo Oscuro de Ventana</span>
              </div>
              <button class="toggle-switch" :class="{ active: isDarkMode }" @click="aplicarCambioModo(!isDarkMode)">
                <span class="toggle-slider"></span>
              </button>
            </div>

            <p class="section-hint-text">Seleccioná un entorno visual de fondo:</p>
            <div class="opciones-grid-imagenes">
              <div 
                v-for="(info, key) in staticLandscapes" 
                :key="key"
                class="opcion-img-card" 
                :class="{ active: selectedLandscape === key }"
                :data-landscape="key"
                @click="handleSelectLandscape(key)"
              >
                <div class="img-preview"></div>
                <span class="landscape-name">{{ info.nombre }} <span v-if="isLandscapeLocked(key)" title="Exclusivo para registrados">🔒</span></span>
                <div class="mode-selectors">
                  <span class="mode-badge claro" :class="{ active: !isDarkMode }" @click.stop="aplicarCambioModo(false)">Sol</span>
                  <span class="mode-badge oscuro" :class="{ active: isDarkMode }" @click.stop="aplicarCambioModo(true)">Luna</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Gifs/Videos Animados -->
          <div v-show="activeTab === 'tab-gifs'" class="tab-panel active">
            <p class="section-hint-text">Seleccioná un entorno animado con movimiento continuo:</p>
            <div class="opciones-grid-imagenes">
              <div 
                v-for="(info, key) in videoLandscapes" 
                :key="key"
                class="opcion-img-card" 
                :class="{ active: selectedLandscape === key }"
                :data-landscape="key"
                @click="handleSelectLandscape(key)"
              >
                <div class="img-preview"></div>
                <span class="landscape-name">{{ info.nombre }} <span v-if="isLandscapeLocked(key)" title="Exclusivo para registrados">🔒</span></span>
                <div class="mode-selectors">
                  <span class="mode-badge claro" :class="{ active: !isDarkMode }" @click.stop="aplicarCambioModo(false)">Sol</span>
                  <span class="mode-badge oscuro" :class="{ active: isDarkMode }" @click.stop="aplicarCambioModo(true)">Luna</span>
                </div>
              </div>
            </div>
          </div>

          <div class="modal-avanzado-footer">
            <button @click="cerrarModalAvanzado" class="btn-guardar-zen">Aplicar y Cerrar</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Personalizado para Promoción de Registro -->
    <Teleport to="body">
      <div v-if="modalRegistroOpen" class="zen-custom-modal-overlay">
        <div class="zen-custom-modal">
          <div class="zen-modal-icon">
            <i class="bi bi-star-fill text-warning"></i>
          </div>
          <h3 class="zen-modal-title">Contenido Exclusivo</h3>
          <p class="zen-modal-text">{{ mensajeRegistro }}</p>
          <div class="zen-modal-actions">
            <button class="zen-btn-secondary" @click="modalRegistroOpen = false">Cancelar</button>
            <Link :href="route('register')" class="zen-btn-primary">Registrarse Gratis</Link>
          </div>
        </div>
      </div>
    </Teleport>
    <!-- Modal de Confirmación para Terminar Sesión -->
    <Teleport to="body">
      <div v-if="showConfirmEndModal" class="zen-custom-modal-overlay">
        <div class="zen-custom-modal">
          <div class="zen-modal-icon">
            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
          </div>
          <h3 class="zen-modal-title">¿Terminar Sesión?</h3>
          <p class="zen-modal-text">¿Estás seguro de que querés dar por terminada esta sesión de concentración? El cronómetro se pausará y tu progreso parcial será guardado.</p>
          <div class="zen-modal-actions">
            <button class="zen-btn-secondary" @click="cancelEndSession">No, continuar</button>
            <button class="zen-btn-primary bg-danger text-white border-0" @click="confirmEndSession">Sí, terminar</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal de Pregunta sobre la Tarea Activa -->
    <Teleport to="body">
      <div v-if="showTaskCompletePrompt" class="zen-custom-modal-overlay">
        <div class="zen-custom-modal">
          <div class="zen-modal-icon">
            <i class="bi bi-check-circle-fill text-success"></i>
          </div>
          <h3 class="zen-modal-title">¿Completaste la tarea?</h3>
          <p class="zen-modal-text">¿Lograste terminar la tarea: <strong>"{{ localSesionActiva?.tituloTarea }}"</strong>?</p>
          <div class="zen-modal-actions">
            <button class="zen-btn-secondary" @click="handleTaskCompletionPrompt(false)">No, sigue pendiente</button>
            <button class="zen-btn-primary" @click="handleTaskCompletionPrompt(true)">Sí, completada</button>
          </div>
        </div>
      </div>
    </Teleport>

    <AlertModal 
      :show="showAlertModal" 
      :title="alertTitle" 
      :message="alertMessage" 
      @close="showAlertModal = false" 
    />
  </component>
</template>

<style>
/* FONDOS AMBIENTALES DINÁMICOS POR CLASES (Resuelven rutas relativas al CSS) */
.pomodoro-zen-container.fondo-paisaje1-claro { background-image: url('/imagenes/atardecer.webp'); }
.pomodoro-zen-container.fondo-paisaje1-oscuro { background-image: url('/imagenes/noche.webp'); }

.pomodoro-zen-container.fondo-paisaje2-claro { background-image: url('/imagenes/paisaje2-claro.webp'); }
.pomodoro-zen-container.fondo-paisaje2-oscuro { background-image: url('/imagenes/paisaje2-oscuro.webp'); }

.pomodoro-zen-container.fondo-paisaje3-claro { background-image: url('/imagenes/paisaje3-claro.webp'); }
.pomodoro-zen-container.fondo-paisaje3-oscuro { background-image: url('/imagenes/paisaje3-oscuro.webp'); }

.pomodoro-zen-container.fondo-paisaje4-claro { background-image: url('/imagenes/paisaje4-claro.webp'); }
.pomodoro-zen-container.fondo-paisaje4-oscuro { background-image: url('/imagenes/paisaje4-oscuro.webp'); }

.pomodoro-zen-container.fondo-paisaje5-claro { background-image: url('/imagenes/paisaje5-claro.webp'); }
.pomodoro-zen-container.fondo-paisaje5-oscuro { background-image: url('/imagenes/paisaje5-oscuro.webp'); }

.pomodoro-zen-container.fondo-paisaje6-claro { background-image: url('/imagenes/paisaje6-claro.webp'); }
.pomodoro-zen-container.fondo-paisaje6-oscuro { background-image: url('/imagenes/paisaje6-oscuro.webp'); }

.pomodoro-zen-container.fondo-paisaje7-claro { background-image: url('/imagenes/paisaje7-claro.webp'); }
.pomodoro-zen-container.fondo-paisaje7-oscuro { background-image: url('/imagenes/paisaje7-oscuro.webp'); }

.pomodoro-zen-container.fondo-paisaje8-claro { background-image: url('/imagenes/paisaje8-claro.webp'); }
.pomodoro-zen-container.fondo-paisaje8-oscuro { background-image: url('/imagenes/paisaje8-oscuro.webp'); }

.pomodoro-zen-container.fondo-paisaje9-claro { background-image: url('/imagenes/paisaje9-claro.webp'); }
.pomodoro-zen-container.fondo-paisaje9-oscuro { background-image: url('/imagenes/paisaje9-oscuro.webp'); }

/* Contenedor Zen Pomodoro adaptado al Layout */
.pomodoro-zen-container {
  min-height: calc(100vh - 40px);
  width: 100%;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  transition: background-image 0.7s ease-in-out;
  position: relative;
  overflow: hidden;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

/* Floating controls and Fullscreen Zen Mode overrides */
.floating-controls {
  position: absolute;
  top: 20px;
  right: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  z-index: 1005; /* Above widgets */
}

.floating-btn {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: #6b4c3f;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  text-decoration: none;
}

.floating-btn:hover {
  background: rgba(255, 255, 255, 0.96);
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
  color: #69342e;
}

.pomodoro-zen-container.dark-mode .floating-btn {
  background: rgba(97, 44, 45, 0.94) !important;
  color: #ffffff !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
}

.pomodoro-zen-container.dark-mode .floating-btn:hover {
  background: rgba(105, 52, 46, 1) !important;
}

.pomodoro-zen-container.fullscreen-zen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 2050; /* Above sidebar and toggle button */
  border-radius: 0 !important;
  box-shadow: none !important;
}

.pomodoro-zen-container:fullscreen {
  width: 100vw;
  height: 100vh;
  border-radius: 0 !important;
  box-shadow: none !important;
}

/* Modo Oscuro sobre la interfaz base */
.pomodoro-zen-container.dark-mode .timer-widget, 
.pomodoro-zen-container.dark-mode .settings-toggle-widget,
.pomodoro-zen-container.dark-mode .settings-panel {
  background: rgba(97, 44, 45, 0.94) !important; /* #612c2d wine red */
  color: #ffffff !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
}
.pomodoro-zen-container.dark-mode .settings-btn {
  color: #ffffff !important;
}
.pomodoro-zen-container.dark-mode .timer-display, 
.pomodoro-zen-container.dark-mode .sound-option, 
.pomodoro-zen-container.dark-mode .control-btn {
  color: #ffffff !important;
  border-color: #ffffff !important;
}

/* Widgets */
.widget { position: absolute; cursor: grab; user-select: none; z-index: 10; }
.widget:active { cursor: grabbing; }
.widget.dragging { transform: scale(1.01); z-index: 1000; }
.drag-handle { position: absolute; top: 8px; left: 8px; opacity: 0.4; cursor: grab; padding: 4px; z-index: 12; }
.drag-handle:hover { opacity: 1; }

/* Timer */
.timer-widget {
  background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
  border-radius: 20px; padding: 20px 24px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.3);
}
.timer-content { display: flex; flex-direction: column; align-items: center; gap: 8px; }
.timer-controls { display: flex; gap: 8px; }
.control-btn {
  width: 36px; height: 36px; border-radius: 50%; border: 2px solid #6b4c3f;
  background: transparent; color: #6b4c3f; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
.control-btn:hover { background: #6b4c3f; color: white; }
.timer-display { font-size: 2rem; font-weight: 600; color: #6b4c3f; font-variant-numeric: tabular-nums; }

/* Dropdown Settings Toggle Container */
.settings-toggle-widget {
  background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
  border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  padding-left: 28px; /* space for the drag handle */
  position: absolute;
}
.sound-drag-handle-container {
  position: absolute;
  left: 6px;
  top: 50%;
  transform: translateY(-50%);
  z-index: 12;
}
.sound-drag-handle {
  position: static;
  opacity: 0.4;
  cursor: grab;
  padding: 4px;
  display: flex;
  align-items: center;
}
.sound-drag-handle:hover {
  opacity: 1;
}

.settings-btn {
  display: flex; align-items: center; gap: 8px; padding: 10px 16px;
  background: transparent; border: none; cursor: pointer;
  font-size: 14px; color: #6b4c3f; font-weight: 500;
  transition: all 0.2s ease;
}
.settings-btn:hover {
  color: #543a2f;
}
.settings-btn:active {
  transform: scale(0.97);
}
.settings-panel {
  display: none; position: absolute; top: 110%; left: 0; min-width: 260px;
  background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(16px);
  border-radius: 16px; box-shadow: 0 16px 48px rgba(107, 76, 63, 0.12), 0 4px 16px rgba(0, 0, 0, 0.04);
  border: 1px solid rgba(107, 76, 63, 0.08); overflow: hidden; z-index: 100;
}
.settings-panel.open { display: block; }
.settings-content { padding: 12px; }
.sound-header { font-size: 11px; font-weight: 700; color: #8a7065; margin: 8px 4px; letter-spacing: 0.5px; }
.sound-options { display: flex; flex-direction: column; gap: 4px; }
.sound-option {
  display: flex; align-items: center; gap: 10px; padding: 8px 12px;
  border: none; background: transparent; border-radius: 8px;
  text-align: left; font-size: 13.5px; color: #6b4c3f; cursor: pointer; width: 100%;
  transition: all 0.2s ease;
}
.sound-option span:first-child {
  font-size: 15px; display: inline-flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; border-radius: 6px; background: rgba(107, 76, 63, 0.05);
  flex-shrink: 0; transition: background-color 0.2s;
}
.sound-option:hover {
  background: rgba(107, 76, 63, 0.06);
  padding-left: 16px;
}
.sound-option.active {
  background: #6b4c3f; color: white !important; font-weight: 600;
}
.sound-option.active span:first-child {
  background: rgba(255, 255, 255, 0.25) !important;
}


/* Mixer CSS */
.mixer-options { max-height: 280px; overflow-y: auto; padding-right: 8px; margin-right: -4px; }
.mixer-options::-webkit-scrollbar { width: 4px; }
.mixer-options::-webkit-scrollbar-thumb { background: rgba(107, 76, 63, 0.2); border-radius: 4px; }

.mixer-row { display: flex; align-items: center; justify-content: space-between; padding: 6px 4px 6px 10px; margin-bottom: 8px; gap: 12px; }

.mixer-label { font-size: 13px; font-weight: 600; color: rgba(107, 76, 63, 0.85); flex: 1; text-align: left; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color 0.2s; }
.pomodoro-zen-container.dark-mode .mixer-label { color: rgba(255, 255, 255, 0.75); }
.mixer-row:hover .mixer-label { color: #6b4c3f; }
.pomodoro-zen-container.dark-mode .mixer-row:hover .mixer-label { color: #ffffff; }

.mixer-control-pill { display: flex; align-items: center; background: rgba(107, 76, 63, 0.08); border-radius: 20px; padding: 4px; width: 140px; transition: all 0.3s ease; }
.pomodoro-zen-container.dark-mode .mixer-control-pill { background: rgba(255, 255, 255, 0.05); }

.mixer-control-pill.active { background: rgba(107, 76, 63, 0.15); }
.pomodoro-zen-container.dark-mode .mixer-control-pill.active { background: rgba(255, 255, 255, 0.12); }

.mixer-icon { display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; background: #ffffff; color: #6b4c3f; font-size: 13px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.06); transition: all 0.3s ease; flex-shrink: 0; }
.pomodoro-zen-container.dark-mode .mixer-icon { background: #3a3a3a; color: #ffffff; }

.mixer-control-pill.active .mixer-icon { background: #d97706; color: #ffffff; box-shadow: 0 0 8px rgba(217, 119, 6, 0.4); text-shadow: 0 1px 2px rgba(0,0,0,0.2); }

.mixer-slider-container { flex: 1; padding: 0 8px 0 10px; display: flex; align-items: center; opacity: 0.3; transition: opacity 0.3s, filter 0.3s; filter: grayscale(1); }
.mixer-control-pill.active .mixer-slider-container { opacity: 1; filter: grayscale(0); }

.mixer-slider { width: 100%; height: 4px; -webkit-appearance: none; background: rgba(107, 76, 63, 0.2); border-radius: 2px; outline: none; cursor: pointer; }
.pomodoro-zen-container.dark-mode .mixer-slider { background: rgba(255, 255, 255, 0.15); }

.mixer-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 12px; height: 12px; border-radius: 50%; background: #a3a3a3; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
.mixer-control-pill.active .mixer-slider::-webkit-slider-thumb { background: #d97706; }

/* Modo Oscuro Overrides para Sonidos */
.pomodoro-zen-container.dark-mode .sound-option {
  color: #ffffff !important;
}
.pomodoro-zen-container.dark-mode .sound-option span:first-child {
  background: rgba(255, 255, 255, 0.1);
}
.pomodoro-zen-container.dark-mode .sound-option:hover {
  background: rgba(255, 255, 255, 0.08) !important;
}
.pomodoro-zen-container.dark-mode .sound-option.active {
  background: #ffffff !important;
  color: #612c2d !important;
}
.pomodoro-zen-container.dark-mode .sound-option.active span:first-child {
  background: rgba(97, 44, 45, 0.15) !important;
}


/* Switches de Modos Sincronizados */
.setting-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.setting-label { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #8a7065; }


.toggle-switch {
  position: relative; width: 44px; height: 22px; background: #ced4da; border-radius: 22px; border: none; cursor: pointer;
}
.toggle-switch.active { background: #69342e; }
.toggle-slider {
  position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%; transition: transform 0.2s;
}
.toggle-switch.active .toggle-slider { transform: translateX(22px); }

/* VENTANA EMERGENTE (ESTADO INICIAL: TOTALMENTE OCULTO) */
.modal-avanzado-backdrop {
  position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
  background-color: rgba(0, 0, 0, 0.4); z-index: 2000;
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.modal-avanzado-backdrop.hidden { display: none !important; }

.modal-avanzado-content {
  background: #ffffff; color: #333333; width: 100%; max-width: 460px; height: 550px; max-height: 90vh; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);
  display: flex; flex-direction: column; overflow: hidden;
}
.modal-avanzado-header { padding: 16px 20px; border-bottom: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.modal-avanzado-header h5 { font-size: 15px; font-weight: 700; color: #333; }
.btn-close-x { background: none; border: none; font-size: 22px; cursor: pointer; color: #aaa; }
.modal-tabs { display: flex; background: #f8f9fa; padding: 4px; gap: 4px; flex-shrink: 0; }
.tab-link { flex: 1; padding: 10px; border: none; background: transparent; font-size: 13px; font-weight: 600; color: #666; cursor: pointer; border-radius: 8px; }
.tab-link.active { background: #ffffff; color: #69342e; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.tab-panel { display: none; padding: 20px; flex: 1; overflow-y: auto; }
.tab-panel.active { display: block; }
.section-hint-text { font-size: 13px; color: #6c757d; margin-bottom: 14px; }

/* Grid de Fondos */
.opciones-grid-imagenes {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  max-height: 330px;
  overflow-y: auto;
  padding: 4px;
}
.opcion-img-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 5px;
  padding: 6px;
  border: 2px solid #e9ecef;
  border-radius: 10px;
  cursor: pointer;
  background: #ffffff;
  transition: all 0.2s ease;
  position: relative;
}
.opcion-img-card:hover {
  border-color: #d1b8b5;
  transform: translateY(-1px);
}
.opcion-img-card.active {
  border-color: #69342e;
  background-color: #faf8f7;
  box-shadow: 0 4px 10px rgba(105, 52, 46, 0.08);
}
.img-preview {
  width: 100%;
  height: 55px;
  border-radius: 6px;
  background-size: cover;
  background-position: center;
  transition: background-image 0.3s ease;
}

/* Previsualizaciones de paisajes (Rutas absolutas) */
.opcion-img-card[data-landscape="paisaje1"] .img-preview { background-image: url('/imagenes/atardecer.webp'); }
.opcion-img-card[data-landscape="paisaje2"] .img-preview { background-image: url('/imagenes/paisaje2-claro.webp'); }
.opcion-img-card[data-landscape="paisaje3"] .img-preview { background-image: url('/imagenes/paisaje3-claro.webp'); }
.opcion-img-card[data-landscape="paisaje4"] .img-preview { background-image: url('/imagenes/paisaje4-claro.webp'); }
.opcion-img-card[data-landscape="paisaje5"] .img-preview { background-image: url('/imagenes/paisaje5-claro.webp'); }
.opcion-img-card[data-landscape="paisaje6"] .img-preview { background-image: url('/imagenes/paisaje6-claro.webp'); }
.opcion-img-card[data-landscape="paisaje7"] .img-preview { background-image: url('/imagenes/paisaje7-claro.webp'); }
.opcion-img-card[data-landscape="paisaje8"] .img-preview { background-image: url('/imagenes/paisaje8-claro.webp'); }
.opcion-img-card[data-landscape="paisaje9"] .img-preview { background-image: url('/imagenes/paisaje9-claro.webp'); }

.dark-mode .opcion-img-card[data-landscape="paisaje1"] .img-preview { background-image: url('/imagenes/noche.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje2"] .img-preview { background-image: url('/imagenes/paisaje2-oscuro.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje3"] .img-preview { background-image: url('/imagenes/paisaje3-oscuro.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje4"] .img-preview { background-image: url('/imagenes/paisaje4-oscuro.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje5"] .img-preview { background-image: url('/imagenes/paisaje5-oscuro.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje6"] .img-preview { background-image: url('/imagenes/paisaje6-oscuro.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje7"] .img-preview { background-image: url('/imagenes/paisaje7-oscuro.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje8"] .img-preview { background-image: url('/imagenes/paisaje8-oscuro.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje9"] .img-preview { background-image: url('/imagenes/paisaje9-oscuro.webp'); }

.landscape-name {
  font-size: 11px;
  font-weight: 700;
  color: #495057;
}
.mode-selectors {
  display: flex;
  gap: 3px;
  width: 100%;
  justify-content: center;
}
.mode-badge {
  font-size: 8.5px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  background: #f1f3f5;
  color: #495057;
  border: 1px solid transparent;
  transition: all 0.15s ease;
}
.mode-badge:hover {
  background: #e9ecef;
}
.mode-badge.active {
  background: #69342e;
  color: #ffffff;
}

/* Sonidos avanzados */
.lista-audios-avanzados { display: flex; flex-direction: column; gap: 6px; }
.audio-row-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; border: 1px solid #e9ecef; border-radius: 10px; cursor: pointer; color: #495057; }
.audio-row-item.playing { border-color: #69342e; background-color: #fdf8f7; font-weight: 600; color: #69342e; }
.d-flex-align { display: flex; align-items: center; gap: 10px; font-size: 13.5px; }
.badge-howler { font-size: 10px; background: #fdf0ef; color: #69342e; padding: 2px 6px; border-radius: 4px; }
.modal-avanzado-footer { padding: 14px 20px; border-top: 1px solid #f1f3f5; text-align: right; flex-shrink: 0; }
.btn-guardar-zen { background: #69342e; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
.hidden { display: none !important; }

/* Soporte para Fondo de Video Animado (Gifs/MP4) */
.background-video {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  z-index: 0;
  pointer-events: none;
}

/* Previsualizaciones del paisaje animado (Rutas absolutas) */
.opcion-img-card[data-landscape="paisaje4-gif"] .img-preview { background-image: url('/imagenes/paisaje4-claro.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje4-gif"] .img-preview { background-image: url('/imagenes/paisaje4-oscuro.webp'); }

.opcion-img-card[data-landscape="paisaje9-gif"] .img-preview { background-image: url('/imagenes/paisaje9-claro.webp'); }
.dark-mode .opcion-img-card[data-landscape="paisaje9-gif"] .img-preview { background-image: url('/imagenes/paisaje9-oscuro.webp'); }

.sound-category-header {
  font-size: 11px;
  font-weight: 800;
  color: #69342e;
  margin-top: 15px;
  margin-bottom: 6px;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  border-bottom: 1px solid rgba(105, 52, 46, 0.08);
  padding-bottom: 4px;
}
.seccion-sonidos {
  margin-bottom: 12px;
}

/* Modal Avanzado Dark Mode Premium Style Overrides */
.modal-avanzado-backdrop.dark-mode {
  background-color: rgba(0, 0, 0, 0.6);
}
.modal-avanzado-content.dark-mode {
  background: #3a1a1b; /* darker wine red/brown for main modal background */
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}
.modal-avanzado-content.dark-mode .modal-avanzado-header {
  border-bottom-color: rgba(255, 255, 255, 0.08);
}
.modal-avanzado-content.dark-mode .modal-avanzado-header h5 {
  color: #ffffff;
}
.modal-avanzado-content.dark-mode .btn-close-x {
  color: #ffffff;
  opacity: 0.8;
}
.modal-avanzado-content.dark-mode .btn-close-x:hover {
  opacity: 1;
}
.modal-avanzado-content.dark-mode .modal-tabs {
  background: #2b1314;
}
.modal-avanzado-content.dark-mode .tab-link {
  color: #c9b1b2;
}
.modal-avanzado-content.dark-mode .tab-link.active {
  background: #3a1a1b;
  color: #ffffff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}
.modal-avanzado-content.dark-mode .section-hint-text {
  color: #dfcfcf;
}
.modal-avanzado-content.dark-mode .opcion-img-card {
  background: #2b1314;
  border-color: rgba(255, 255, 255, 0.08);
}
.modal-avanzado-content.dark-mode .opcion-img-card:hover {
  border-color: #612c2d;
}
.modal-avanzado-content.dark-mode .opcion-img-card.active {
  border-color: #ffffff;
  background-color: #472021;
}
.modal-avanzado-content.dark-mode .landscape-name {
  color: #ffffff;
}
.modal-avanzado-content.dark-mode .mode-badge {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}
.modal-avanzado-content.dark-mode .mode-badge.active {
  background: #ffffff;
  color: #3a1a1b;
}
.modal-avanzado-content.dark-mode .audio-row-item {
  border-color: rgba(255, 255, 255, 0.08);
  color: #ffffff;
}
.modal-avanzado-content.dark-mode .audio-row-item.playing {
  border-color: #ffffff;
  background-color: #472021;
  color: #ffffff;
}
.modal-avanzado-content.dark-mode .badge-howler {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}
.modal-avanzado-content.dark-mode .modal-avanzado-footer {
  border-top-color: rgba(255, 255, 255, 0.08);
}
.modal-avanzado-content.dark-mode .btn-guardar-zen {
  background: #ffffff;
  color: #3a1a1b;
}
.modal-avanzado-content.dark-mode .btn-guardar-zen:hover {
  background: #f1f3f5;
}
.modal-avanzado-content.dark-mode .sound-category-header {
  color: #ffb5b5;
  border-bottom-color: rgba(255, 255, 255, 0.1);
}

/* Zen Setup Widget Styles */
.timer-content.with-setup {
  min-width: 260px;
  align-items: stretch;
}

.widget-header-controls {
  position: absolute;
  top: 8px;
  left: 8px;
  right: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  z-index: 12;
}

.widget-header-controls .drag-handle {
  position: static;
  display: inline-flex;
}

.minimize-btn {
  background: transparent;
  border: none;
  color: #6b4c3f;
  opacity: 0.6;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.minimize-btn:hover {
  opacity: 1;
}

.pomodoro-zen-container.dark-mode .minimize-btn {
  color: #ffffff;
}

.timer-widget.minimized {
  padding: 12px 16px;
  border-radius: 30px;
}

.timer-widget.minimized .widget-header-controls {
  display: none; /* Hide header controls when minimized, we use the whole body */
}

.timer-minimized-view {
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #6b4c3f;
}

.pomodoro-zen-container.dark-mode .timer-minimized-view {
  color: #ffffff;
}

.mini-time {
  font-size: 1.2rem;
  font-variant-numeric: tabular-nums;
}

.mini-icon {
  font-size: 0.9rem;
}

.setup-header {
  font-size: 1.1rem;
  font-weight: 700;
  color: #6b4c3f;
  text-align: center;
  border-bottom: 1px solid rgba(107, 76, 63, 0.2);
  padding-bottom: 8px;
  margin-bottom: 8px;
}
.zen-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #8a7065;
  margin-bottom: 4px;
  display: block;
}
.form-select-zen, .input-zen {
  width: 100%;
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid rgba(107, 76, 63, 0.2);
  background: rgba(255, 255, 255, 0.6);
  color: #6b4c3f;
  font-size: 0.9rem;
  outline: none;
}
.form-select-zen:focus, .input-zen:focus {
  border-color: #69342e;
  background: rgba(255, 255, 255, 0.9);
}
.btn-zen-primary {
  background: #6b4c3f;
  color: white;
  border: none;
  padding: 10px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-zen-primary:hover {
  background: #543a2f;
  transform: translateY(-1px);
}
.btn-zen-secondary {
  background: transparent;
  color: #6b4c3f;
  border: 1px solid #6b4c3f;
  padding: 8px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-zen-secondary:hover {
  background: rgba(107, 76, 63, 0.1);
}
.zen-divider {
  text-align: center;
  position: relative;
  margin: 12px 0;
}
.zen-divider::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: rgba(107, 76, 63, 0.2);
}
.zen-divider span {
  position: relative;
  background: #f7f1eb; /* Fallback matches light widget roughly */
  padding: 0 8px;
  font-size: 0.75rem;
  font-weight: 700;
  color: #8a7065;
  letter-spacing: 0.5px;
}
.custom-times {
  display: flex;
  gap: 8px;
}
.time-input-group {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.time-input-group label {
  font-size: 0.7rem;
  color: #8a7065;
  font-weight: 600;
}
.phase-indicator {
  font-size: 0.85rem;
  font-weight: 700;
  color: #8a7065;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.task-badge {
  background: rgba(107, 76, 63, 0.1);
  color: #6b4c3f;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  margin-top: 4px;
}
.btn-cancel-session {
  background: transparent;
  color: #e63946;
  border: 1px solid rgba(230, 57, 70, 0.3);
  padding: 6px 16px;
  border-radius: 16px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-cancel-session:hover {
  background: rgba(230, 57, 70, 0.1);
}

.pomodoro-zen-container.dark-mode .setup-header,
.pomodoro-zen-container.dark-mode .zen-label,
.pomodoro-zen-container.dark-mode .phase-indicator {
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.2);
}
.pomodoro-zen-container.dark-mode .time-input-group label {
  color: #e0e0e0;
}
.pomodoro-zen-container.dark-mode .form-select-zen,
.pomodoro-zen-container.dark-mode .input-zen {
  background: rgba(0, 0, 0, 0.2);
  color: white;
  border-color: rgba(255, 255, 255, 0.2);
}
.pomodoro-zen-container.dark-mode .form-select-zen:focus,
.pomodoro-zen-container.dark-mode .input-zen:focus {
  border-color: #ffffff;
  background: rgba(0, 0, 0, 0.4);
}
.pomodoro-zen-container.dark-mode .btn-zen-primary {
  background: #ffffff;
  color: #612c2d;
}
.pomodoro-zen-container.dark-mode .btn-zen-secondary {
  color: #ffffff;
  border-color: #ffffff;
}
.pomodoro-zen-container.dark-mode .btn-zen-secondary:hover {
  background: rgba(255, 255, 255, 0.1);
}
.pomodoro-zen-container.dark-mode .zen-divider::before {
  background: rgba(255, 255, 255, 0.2);
}
.pomodoro-zen-container.dark-mode .zen-divider span {
  background: #612c2d;
  color: #ffffff;
}
.pomodoro-zen-container.dark-mode .task-badge {
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
}
.pomodoro-zen-container.dark-mode .btn-cancel-session {
  color: #ffb5b5;
  border-color: rgba(255, 181, 181, 0.3);
}
.pomodoro-zen-container.dark-mode .btn-cancel-session:hover {
  background: rgba(255, 181, 181, 0.1);
}
</style>

<style>
/* Global styles to hide AppLayout elements when Zen Mode is active */
body.distraction-free-mode .sidebar {
  display: none !important;
}
body.distraction-free-mode .top-bar {
  display: none !important;
}
body.distraction-free-mode #content {
  margin-left: 0 !important;
  padding: 0 !important;
}
body.distraction-free-mode .pomodoro-zen-container {
  height: 100vh !important;
  border-radius: 0 !important;
}

/* Modal Personalizado */
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
.pomodoro-zen-container.dark-mode ~ .zen-custom-modal-overlay .zen-custom-modal,
body.dark-mode .zen-custom-modal {
  background: #2a2a2a;
  color: white;
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
.pomodoro-zen-container.dark-mode ~ .zen-custom-modal-overlay .zen-modal-title {
  color: #fff;
}
.zen-modal-text {
  font-size: 0.95rem;
  color: #666;
  margin-bottom: 25px;
}
.pomodoro-zen-container.dark-mode ~ .zen-custom-modal-overlay .zen-modal-text {
  color: #ccc;
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
  background: #f7a072;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
}
.zen-btn-primary:hover {
  background: #e68d5e;
  color: white;
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.9) translateY(20px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
