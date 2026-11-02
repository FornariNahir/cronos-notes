<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { usePomodoroTimer } from '@/Composables/usePomodoroTimer';
import { usePage, router } from '@inertiajs/vue3';

const {
  isRunning,
  currentPhase,
  currentCycle,
  timeLeft,
  totalSeconds,
  localSesionActiva,
  initTimer,
  startTimer,
  stopTimer,
  completePhase,
  isPipActive,
  pipRequestCounter,
  isGlobalDarkMode,
  setGlobalDarkMode
} = usePomodoroTimer();

const page = usePage();
const pipWindow = ref(null);

// Sincronizar la sesión activa compartida por Inertia en cualquier página de la app
watch(
  () => page.props?.sesionPomodoroActiva,
  (sharedSession) => {
    if (sharedSession && !localSesionActiva.value) {
      localSesionActiva.value = sharedSession;
      initTimer();
    } else if (!sharedSession && localSesionActiva.value && localSesionActiva.value.idSesion !== 'guest' && localSesionActiva.value.idSesion !== 'guest_custom') {
      localSesionActiva.value = null;
    }
  },
  { immediate: true }
);

// Sincronizar clase dark-mode en el body de la ventana PiP cuando cambia el modo oscuro/claro en cualquier lugar
watch(isGlobalDarkMode, (dark) => {
  if (pipWindow.value && pipWindow.value.document && pipWindow.value.document.body) {
    pipWindow.value.document.body.classList.toggle('dark-mode', dark);
  }
});

const togglePipTheme = () => {
  setGlobalDarkMode(!isGlobalDarkMode.value);
};

const isOnPomodoroPage = computed(() => {
  return page.url.startsWith('/pomodoro');
});

// Visible fuera de /pomodoro cuando existe una sesión activa o el reloj está corriendo/avanzado, y no está en ventana PiP
const isVisible = computed(() => {
  const hasTime = timeLeft.value !== null && timeLeft.value !== undefined;
  const hasStarted = isRunning.value || (totalSeconds.value > 0 && timeLeft.value < totalSeconds.value) || !!localSesionActiva.value;
  return hasTime && hasStarted && !isOnPomodoroPage.value && !isPipActive.value;
});

const formattedTime = computed(() => {
  if (timeLeft.value == null) return '00:00';
  const mins = Math.floor(timeLeft.value / 60);
  const secs = timeLeft.value % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

const phaseLabel = computed(() => {
  if (currentPhase.value === 'work') return 'TRABAJO';
  if (currentPhase.value === 'shortBreak') return 'DESCANSO CORTO';
  return 'DESCANSO LARGO';
});

const togglePlay = () => {
  if (isRunning.value) {
    stopTimer();
  } else {
    startTimer();
  }
};

const skipPhase = () => {
  completePhase();
};

const irAPomodoro = () => {
  if (isPipActive.value && pipWindow.value) {
    pipWindow.value.close();
  }
  router.visit(route('pomodoro.index'));
};

// --- DRAGGABLE LOGIC ---
const widgetRef = ref(null);
const position = ref({ x: -1, y: -1 });
const isDragging = ref(false);
const startPos = ref({ x: 0, y: 0 });

const initDrag = (e) => {
  if (!widgetRef.value) return;
  isDragging.value = true;
  const rect = widgetRef.value.getBoundingClientRect();
  startPos.value = {
    x: e.clientX - (position.value.x !== -1 ? position.value.x : rect.left),
    y: e.clientY - (position.value.y !== -1 ? position.value.y : rect.top)
  };
  document.addEventListener('mousemove', onDrag);
  document.addEventListener('mouseup', stopDrag);
};

const onDrag = (e) => {
  if (!isDragging.value) return;
  position.value = {
    x: e.clientX - startPos.value.x,
    y: e.clientY - startPos.value.y
  };
};

const stopDrag = () => {
  isDragging.value = false;
  document.removeEventListener('mousemove', onDrag);
  document.removeEventListener('mouseup', stopDrag);
};

// --- PIP LOGIC (Document Picture-in-Picture + Fallback Popup) ---
const restaurarElementoDesdePip = (win) => {
  isPipActive.value = false;
  const originalContainer = document.getElementById('timer-pip-container-wrapper');
  const movingElement = win?.document?.getElementById('timer-pip-container-content');
  if (originalContainer && movingElement) {
    movingElement.style.position = '';
    movingElement.style.boxShadow = '';
    movingElement.style.border = '';
    movingElement.style.width = '';
    movingElement.style.background = '';
    movingElement.style.display = '';
    movingElement.style.flexDirection = '';
    movingElement.style.alignItems = '';
    movingElement.style.justifyContent = '';
    movingElement.style.padding = '';
    originalContainer.appendChild(movingElement);
  }
  pipWindow.value = null;
};

const abrirDocumentPIP = async () => {
  if (isPipActive.value && pipWindow.value) {
    pipWindow.value.close();
    return;
  }

  try {
    let pip = null;

    if ('documentPictureInPicture' in window) {
      pip = await window.documentPictureInPicture.requestWindow({
        width: 300,
        height: 215
      });
    } else {
      // Fallback para navegadores sin Document PiP (ej. Firefox)
      pip = window.open('', 'CronosMiniTimer', 'width=300,height=215,menubar=no,toolbar=no,location=no,status=no,resizable=yes');
      if (!pip) {
        console.warn('No se pudo abrir la ventana flotante (bloqueada por el navegador).');
        return;
      }
      pip.document.title = 'Cronos Mini-Timer';
    }

    pipWindow.value = pip;
    isPipActive.value = true;

    const extraStyle = pip.document.createElement('style');
    extraStyle.textContent = `
      * { box-sizing: border-box; }
      body {
        background-color: #fdfaf8;
        color: #4d2323;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100vh;
        margin: 0;
        padding: 12px;
        font-family: system-ui, -apple-system, sans-serif;
        user-select: none;
        overflow: hidden;
        transition: background-color 0.25s ease, color 0.25s ease;
      }
      body.dark-mode {
        background-color: #3b1919;
        color: #fdfaf8;
      }
      .timer-header {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e5d5cb;
        padding-bottom: 6px;
        margin-bottom: 4px;
      }
      body.dark-mode .timer-header {
        border-bottom-color: #7b413f;
      }
      .pip-title {
        font-size: 0.85rem;
        font-weight: 800;
        color: #7b413f;
        text-transform: uppercase;
        letter-spacing: 1.5px;
      }
      body.dark-mode .pip-title {
        color: #f4be95;
      }
      .pip-header-actions {
        display: flex;
        align-items: center;
        gap: 6px;
      }
      .pip-btn {
        background: transparent;
        border: none;
        color: #7b413f;
        cursor: pointer;
        padding: 3px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
      }
      .pip-btn:hover {
        background: rgba(123, 65, 63, 0.12);
      }
      body.dark-mode .pip-btn {
        color: #f4be95;
      }
      body.dark-mode .pip-btn:hover {
        background: rgba(244, 190, 149, 0.15);
      }
      .pip-only-hide {
        display: none !important;
      }
      .pip-task {
        font-size: 0.78rem;
        color: #6b4c4b;
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 2px;
      }
      body.dark-mode .pip-task {
        color: #fcd5b8;
      }
      .pip-time {
        font-size: 3.2rem;
        font-family: 'Courier New', Courier, monospace;
        font-weight: 800;
        margin: 6px 0;
        color: #4d2323;
        text-align: center;
        line-height: 1;
      }
      body.dark-mode .pip-time {
        color: #ffffff;
      }
      .pip-controls {
        display: flex;
        justify-content: center;
        gap: 14px;
        margin-top: 6px;
        width: 100%;
      }
      .pip-controls button {
        background: #4d2323;
        color: white;
        border: none;
        border-radius: 50%;
        width: 46px;
        height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
        box-shadow: 0 4px 10px rgba(77, 35, 35, 0.25);
      }
      .pip-controls button:hover {
        background: #7b413f;
        transform: scale(1.05);
      }
      body.dark-mode .pip-controls button {
        background: #f4be95;
        color: #3b1919;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
      }
      body.dark-mode .pip-controls button:hover {
        background: #fcd5b8;
      }
    `;
    pip.document.head.appendChild(extraStyle);
    pip.document.body.classList.toggle('dark-mode', isGlobalDarkMode.value);

    const timerElement = document.getElementById('timer-pip-container-content');
    if (timerElement) {
      pip.document.body.appendChild(timerElement);
      timerElement.style.position = 'static';
      timerElement.style.boxShadow = 'none';
      timerElement.style.border = 'none';
      timerElement.style.width = '100%';
      timerElement.style.background = 'transparent';
      timerElement.style.display = 'flex';
      timerElement.style.flexDirection = 'column';
      timerElement.style.alignItems = 'center';
      timerElement.style.justifyContent = 'center';
      timerElement.style.padding = '0';
    }

    pip.addEventListener('pagehide', () => restaurarElementoDesdePip(pip));
    pip.addEventListener('unload', () => restaurarElementoDesdePip(pip));
  } catch (error) {
    console.error('Error abriendo PIP:', error);
  }
};

// Escuchar solicitudes de apertura de PiP disparadas desde SesionZen.vue u otros componentes
watch(pipRequestCounter, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    abrirDocumentPIP();
  }
});

onMounted(() => {
  if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
    Notification.requestPermission();
  }
});

onUnmounted(() => {
  stopDrag();
});
</script>

<template>
  <div v-show="isVisible || isPipActive" id="timer-pip-container-wrapper">
    <div
      v-show="isVisible || isPipActive"
      id="timer-pip-container-content"
      ref="widgetRef"
      class="mini-timer-widget"
      :class="{ 'dark-mode': isGlobalDarkMode }"
      :style="position.x !== -1 && !isPipActive ? { left: position.x + 'px', top: position.y + 'px', right: 'auto', bottom: 'auto' } : {}"
    >
      <!-- Header / Drag Handle -->
      <div
        class="timer-header"
        @mousedown="!isPipActive && initDrag($event)"
        :style="!isPipActive ? 'cursor: grab' : ''"
      >
        <span class="pip-title">{{ phaseLabel }}</span>
        <div class="pip-header-actions">
          <!-- Botón Modo Claro / Oscuro (visible tanto en el widget como en la ventana PiP) -->
          <button
            @click.stop="togglePipTheme"
            :title="isGlobalDarkMode ? 'Cambiar a Modo Claro' : 'Cambiar a Modo Oscuro'"
            class="pip-btn"
          >
            <!-- Sol (cuando está en modo oscuro) -->
            <svg v-if="isGlobalDarkMode" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="5" />
              <line x1="12" y1="1" x2="12" y2="3" />
              <line x1="12" y1="21" x2="12" y2="23" />
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
              <line x1="1" y1="12" x2="3" y2="12" />
              <line x1="21" y1="12" x2="23" y2="12" />
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
            </svg>
            <!-- Luna (cuando está en modo claro) -->
            <svg v-else viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
            </svg>
          </button>

          <button v-if="!isPipActive" @click.stop="irAPomodoro" title="Ir al Espacio de Concentración" class="pip-btn pip-only-hide">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
            </svg>
          </button>
          <button v-if="!isPipActive" @click.stop="abrirDocumentPIP" title="Desacoplar en Ventana Flotante (Always-on-Top)" class="pip-btn pip-only-hide">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <rect x="12" y="12" width="7" height="6" rx="1" fill="currentColor" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Tarea / Ciclo -->
      <div v-if="localSesionActiva?.tituloTarea" class="pip-task" :title="localSesionActiva.tituloTarea">
        {{ localSesionActiva.tituloTarea }} (Ciclo {{ currentCycle }}/{{ localSesionActiva.sesionesPrevioDescansoLargo || 4 }})
      </div>

      <!-- Contador -->
      <div class="pip-time">
        {{ formattedTime }}
      </div>

      <!-- Controles -->
      <div class="pip-controls">
        <button @click="togglePlay" class="btn-play" :title="isRunning ? 'Pausar' : 'Reanudar'">
          <svg v-if="isRunning" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="6" y="4" width="4" height="16" />
            <rect x="14" y="4" width="4" height="16" />
          </svg>
          <svg v-else viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="5 3 19 12 5 21 5 3" />
          </svg>
        </button>
        <button @click="skipPhase" class="btn-skip" title="Saltar Fase">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="5 4 15 12 5 20 5 4" />
            <line x1="19" y1="5" x2="19" y2="19" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Tema Blanco/Marrón Cronos Notes (Modo Claro y Modo Oscuro) */
.mini-timer-widget {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 250px;
  background-color: #ffffff;
  border: 2px solid #e5e7eb;
  color: #4d2323;
  border-radius: 14px;
  padding: 14px 16px;
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.18);
  z-index: 9999;
  user-select: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease;
}

.mini-timer-widget.dark-mode {
  background-color: #3b1919;
  border-color: #7b413f;
  color: #ffffff;
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.45);
}

.timer-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 8px;
  margin-bottom: 6px;
}

.mini-timer-widget.dark-mode .timer-header {
  border-bottom-color: #7b413f;
}

.pip-header-actions {
  display: flex;
  align-items: center;
  gap: 6px;
}

.pip-title {
  font-weight: 700;
  font-size: 0.82rem;
  letter-spacing: 0.8px;
  color: #7b413f;
}

.mini-timer-widget.dark-mode .pip-title {
  color: #f4be95;
}

.pip-task {
  font-size: 0.76rem;
  color: #6b4c4b;
  max-width: 215px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 2px;
}

.mini-timer-widget.dark-mode .pip-task {
  color: #fcd5b8;
}

.pip-btn {
  background: transparent;
  border: none;
  color: #7b413f;
  cursor: pointer;
  padding: 2px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s, transform 0.15s;
}

.mini-timer-widget.dark-mode .pip-btn {
  color: #f4be95;
}

.pip-btn:hover {
  opacity: 0.75;
  transform: scale(1.08);
}

.pip-time {
  font-size: 2.7rem;
  font-family: 'Courier New', Courier, monospace;
  font-weight: bold;
  letter-spacing: 1px;
  margin: 4px 0;
  color: #4d2323;
}

.mini-timer-widget.dark-mode .pip-time {
  color: #ffffff;
}

.pip-controls {
  display: flex;
  gap: 15px;
  margin-top: 6px;
}

.btn-play, .btn-skip {
  background: #4d2323;
  color: white;
  border: none;
  border-radius: 50%;
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 10px rgba(77, 35, 35, 0.3);
  transition: transform 0.1s, background 0.2s, color 0.2s;
}

.btn-play:hover, .btn-skip:hover {
  background: #7b413f;
  transform: scale(1.05);
}

.mini-timer-widget.dark-mode .btn-play,
.mini-timer-widget.dark-mode .btn-skip {
  background: #f4be95;
  color: #3b1919;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
}

.mini-timer-widget.dark-mode .btn-play:hover,
.mini-timer-widget.dark-mode .btn-skip:hover {
  background: #fcd5b8;
}

.btn-play:active, .btn-skip:active {
  transform: scale(0.95);
}
</style>
