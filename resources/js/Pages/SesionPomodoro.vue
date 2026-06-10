<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();

// State variables
const isDarkMode = ref(false);
const isRunning = ref(false);
const timeLeft = ref(25 * 60);
const selectedSound = ref('Ninguno');
const selectedLandscape = ref('paisaje1');
const isFullscreen = ref(false);

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
const activeTab = ref('tab-fondos');

const timerWidget = ref(null);
const settingsToggle = ref(null);

let timerInterval = null;
let sonidoHowlerActivo = null;

// Sound and landscape database (root-relative public paths)
const bancoSonidos = {
  'Ninguno': null,
  'Tormenta': '/audios/Tormenta (N).wav',
  'Agua': '/audios/agua (N).mp3',
  'Fogata': '/audios/fogata (N).mp3',
  'Lluvia': '/audios/lluvia (N).wav',
  'Olas del Mar': '/audios/olas (N).wav',
  'Aire': '/audios/air (A).mp3',
  'Ambiente de Fondo': '/audios/background (A).mp3',
  'Cafetería': '/audios/caffee (A).mp3',
  'Meditación del Tigre': '/audios/meditativetiger (M).mp3',
  'Ruido Blanco': '/audios/ruidoBlanco (M).mp3',
  'Tibetanos': '/audios/tibetan (M).mp3'
};

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

const displayTime = computed(() => {
  const mins = Math.floor(timeLeft.value / 60);
  const secs = timeLeft.value % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

// Sound Lists categorized
const soundCategories = {
  Naturaleza: {
    'Tormenta': 'Tormenta Dinámica',
    'Agua': 'Agua Fluyendo',
    'Fogata': 'Fogata Caliente',
    'Lluvia': 'Lluvia Relajante',
    'Olas del Mar': 'Olas del Mar'
  },
  Ambiente: {
    'Aire': 'Corriente de Aire',
    'Ambiente de Fondo': 'Fondo Relajante',
    'Cafetería': 'Cafetería Humana'
  },
  Meditacion: {
    'Meditación del Tigre': 'Meditación del Tigre',
    'Ruido Blanco': 'Ruido Blanco',
    'Tibetanos': 'Cuencos Tibetanos'
  }
};

const getSoundEmoji = (key) => {
  switch (key) {
    case 'Tormenta': return '⚡';
    case 'Agua': return '💧';
    case 'Fogata': return '🔥';
    case 'Lluvia': return '🌧️';
    case 'Olas del Mar': return '🌊';
    case 'Aire': return '💨';
    case 'Ambiente de Fondo': return '👤';
    case 'Cafetería': return '☕';
    case 'Meditación del Tigre': return '🐯';
    case 'Ruido Blanco': return '📺';
    case 'Tibetanos': return '🥣';
    default: return '🎵';
  }
};

const inicializarWidgets = () => {
  if (timerWidget.value) makeDraggable(timerWidget.value);
  if (settingsToggle.value) makeDraggable(settingsToggle.value);
};

onMounted(() => {
  // 1. Cargar dinámicamente los iconos de Bootstrap si no están en el head
  if (!document.querySelector('link[href*="bootstrap-icons"]')) {
      const linkIcons = document.createElement('link');
      linkIcons.rel = 'stylesheet';
      linkIcons.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
      document.head.appendChild(linkIcons);
  }

  // 2. Escuchar cambios de pantalla completa nativa
  document.addEventListener('fullscreenchange', syncFullscreenState);

  // 3. Load Howler.js dynamically
  if (!window.Howl) {
    const scriptHowl = document.createElement('script');
    scriptHowl.src = 'https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.4/howler.min.js';
    scriptHowl.onload = () => {
      console.log("Howler.js cargado correctamente.");
      inicializarWidgets();
    };
    document.head.appendChild(scriptHowl);
  } else {
    inicializarWidgets();
  }

  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  clearInterval(timerInterval);
  if (sonidoHowlerActivo) {
    sonidoHowlerActivo.stop();
    sonidoHowlerActivo.unload();
  }
  window.removeEventListener('keydown', handleKeyDown);
  document.removeEventListener('fullscreenchange', syncFullscreenState);
});

const handleKeyDown = (e) => {
  if (e.key === 'Escape') cerrarModalAvanzado();
};

const ejecutarAudioHowler = (nombreSonido) => {
  if (sonidoHowlerActivo) {
    sonidoHowlerActivo.stop();
    sonidoHowlerActivo.unload();
    sonidoHowlerActivo = null;
  }

  if (nombreSonido === 'Ninguno' || !bancoSonidos[nombreSonido]) {
    return;
  }

  if (window.Howl) {
    sonidoHowlerActivo = new window.Howl({
      src: [bancoSonidos[nombreSonido]],
      html5: true,
      loop: true,
      volume: 0.6,
      onplayerror: function() {
        if (sonidoHowlerActivo) {
          sonidoHowlerActivo.once('unlock', function() {
            sonidoHowlerActivo.play();
          });
        }
      }
    });

    sonidoHowlerActivo.play();
  }
};

const startTimer = () => {
  if (isRunning.value) return;
  isRunning.value = true;
  
  ejecutarAudioHowler(selectedSound.value);

  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--;
    } else {
      stopTimer();
      if (sonidoHowlerActivo) sonidoHowlerActivo.stop();
      alert("¡Tiempo cumplido! Fin del ciclo Pomodoro en Cronos Notes.");
    }
  }, 1000);
};

const stopTimer = () => {
  isRunning.value = false;
  clearInterval(timerInterval);
  if (sonidoHowlerActivo) sonidoHowlerActivo.pause();
};

const resetTimer = () => {
  stopTimer();
  timeLeft.value = 25 * 60;
  if (sonidoHowlerActivo) sonidoHowlerActivo.stop();
};

const aplicarCambioModo = (oscuro) => {
  isDarkMode.value = oscuro;
};

const toggleSound = (soundKey) => {
  const isCurrentlyActive = selectedSound.value === soundKey;
  
  if (isCurrentlyActive && sonidoHowlerActivo && sonidoHowlerActivo.playing()) {
    sonidoHowlerActivo.pause();
  } else {
    selectedSound.value = soundKey;
    ejecutarAudioHowler(soundKey);
  }
};

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

// Drag and drop helper
function makeDraggable(element) {
  if (!element) return;
  let isDragging = false;
  let startX, startY, initialX, initialY;
  let hasDragged = false;

  const dragHandle = element.querySelector('.drag-handle') || element;

  dragHandle.addEventListener('mousedown', (e) => {
    if (e.target.closest('.settings-panel') || e.target.closest('.toggle-switch') || e.target.closest('button')) return;
    if (element.querySelector('.drag-handle') && !e.target.closest('.drag-handle')) return;

    isDragging = true;
    hasDragged = false;
    element.classList.add('dragging');
    startX = e.clientX; 
    startY = e.clientY;
    initialX = element.offsetLeft; 
    initialY = element.offsetTop;
    
    const onMouseMove = (ev) => {
      if (!isDragging) return;
      const dx = ev.clientX - startX;
      const dy = ev.clientY - startY;
      
      if (Math.abs(dx) > 4 || Math.abs(dy) > 4) {
        hasDragged = true;
      }
      
      if (hasDragged) {
        element.style.left = `${initialX + dx}px`;
        element.style.top = `${initialY + dy}px`;
      }
    };
    
    const onMouseUp = () => {
      isDragging = false; 
      element.classList.remove('dragging');
      document.removeEventListener('mousemove', onMouseMove);
      document.removeEventListener('mouseup', onMouseUp);
      
      if (hasDragged) {
        element.dataset.preventClick = "true";
        setTimeout(() => {
          delete element.dataset.preventClick;
        }, 50);
      }
    };
    
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
  });
}
</script>

<template>
  <AppLayout>
    <div 
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
      <div ref="timerWidget" class="widget timer-widget" style="left: 40px; top: 40px;">
        <div class="drag-handle">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="8" y1="6" x2="8" y2="18"></line>
            <line x1="16" y1="6" x2="16" y2="18"></line>
          </svg>
        </div>
        <div class="timer-content">
          <div class="timer-controls">
            <button v-show="!isRunning" @click="startTimer" class="control-btn" aria-label="Iniciar">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="5,3 19,12 5,21"></polygon>
              </svg>
            </button>
            <button v-show="isRunning" @click="stopTimer" class="control-btn" aria-label="Pausar">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <rect x="6" y="4" width="4" height="16"></rect>
                <rect x="14" y="4" width="4" height="16"></rect>
              </svg>
            </button>
            <button @click="resetTimer" class="control-btn" aria-label="Reiniciar">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
              </svg>
            </button>
          </div>
          <div id="timer-display" class="timer-display">{{ displayTime }}</div>
        </div>
      </div>

      <!-- Draggable Sound Settings widget -->
      <div ref="settingsToggle" class="widget settings-toggle" style="left: 200px; top: 40px;">
        <button id="toggle-settings-btn" class="settings-btn" @click="toggleSettingsPanel">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
            <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
          </svg>
          <span id="current-sound">{{ selectedSound === 'Ninguno' ? 'Ningún sonido' : selectedSound }}</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </button>

        <div class="settings-panel" :class="{ open: settingsPanelOpen }">
          <div class="settings-content">
            <div class="sound-header">SONIDO DE SESIÓN</div>
            <div class="sound-options">
              <button 
                class="sound-option" 
                :class="{ active: selectedSound === 'Ninguno' }" 
                @click="toggleSound('Ninguno')"
              >
                Ningún sonido
              </button>
              <button 
                class="sound-option" 
                :class="{ active: selectedSound === 'Ruido Blanco' }" 
                @click="toggleSound('Ruido Blanco')"
              >
                Ruido Blanco
              </button>
              <button 
                class="sound-option" 
                :class="{ active: selectedSound === 'Olas del Mar' }" 
                @click="toggleSound('Olas del Mar')"
              >
                Olas del Mar
              </button>
              <button 
                class="sound-option" 
                :class="{ active: selectedSound === 'Fogata' }" 
                @click="toggleSound('Fogata')"
              >
                Fogata
              </button>
              <button 
                class="sound-option" 
                :class="{ active: selectedSound === 'Lluvia' }" 
                @click="toggleSound('Lluvia')"
              >
                Lluvia
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
            <button 
              class="tab-link" 
              :class="{ active: activeTab === 'tab-sonidos' }" 
              @click="activeTab = 'tab-sonidos'"
            >Sonidos Howler.js</button>
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
                @click="selectedLandscape = key"
              >
                <div class="img-preview"></div>
                <span class="landscape-name">{{ info.nombre }}</span>
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
                @click="selectedLandscape = key"
              >
                <div class="img-preview"></div>
                <span class="landscape-name">{{ info.nombre }}</span>
                <div class="mode-selectors">
                  <span class="mode-badge claro" :class="{ active: !isDarkMode }" @click.stop="aplicarCambioModo(false)">Sol</span>
                  <span class="mode-badge oscuro" :class="{ active: isDarkMode }" @click.stop="aplicarCambioModo(true)">Luna</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Sonidos Avanzados -->
          <div v-show="activeTab === 'tab-sonidos'" class="tab-panel active">
            <p class="section-hint-text">Elegí una pista inmersiva de Howler en bucle continuo:</p>
            
            <div v-for="(group, catName) in soundCategories" :key="catName" class="seccion-sonidos">
              <div class="sound-category-header">
                {{ catName === 'Naturaleza' ? '🌳 Sonidos de Naturaleza' : catName === 'Ambiente' ? '🏢 Sonidos de Ambiente' : '🧘 Sonidos de Meditación' }}
              </div>
              <div class="lista-audios-avanzados">
                <div 
                  v-for="(nombre, key) in group" 
                  :key="key" 
                  class="audio-row-item" 
                  :class="{ playing: selectedSound === key }"
                  @click="toggleSound(key)"
                >
                  <div class="d-flex-align">
                    <i class="icon-audio-state">{{ getSoundEmoji(key) }}</i>
                    <span>{{ nombre }}</span>
                  </div>
                  <span class="badge-howler">{{ catName }}</span>
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
  </AppLayout>
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
.pomodoro-zen-container.dark-mode .settings-btn, 
.pomodoro-zen-container.dark-mode .settings-panel {
  background: rgba(97, 44, 45, 0.94) !important; /* #612c2d wine red */
  color: #ffffff !important;
  border-color: rgba(255, 255, 255, 0.15) !important;
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
.settings-btn {
  display: flex; align-items: center; gap: 8px; padding: 10px 16px;
  background: rgba(255, 255, 255, 0.88); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
  border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.3); cursor: pointer;
  font-size: 14px; color: #6b4c3f; font-weight: 500;
  transition: all 0.2s ease;
}
.settings-btn:hover {
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 4px 12px rgba(107, 76, 63, 0.08);
}
.settings-btn:active {
  transform: scale(0.97);
}
.settings-panel {
  display: none; position: absolute; top: 50px; left: 0; min-width: 260px;
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
</style>
