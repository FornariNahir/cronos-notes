<script setup>
import { computed, ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { usePomodoroTimer } from '@/Composables/usePomodoroTimer';
import { usePage } from '@inertiajs/vue3';

const { isRunning, currentPhase, timeLeft, startTimer, stopTimer, endSession } = usePomodoroTimer();
const page = usePage();

const isPipActive = ref(false);
const pipWindow = ref(null);

const isOnPomodoroPage = computed(() => {
    return page.url.startsWith('/pomodoro');
});

// Solo es visible si el contador tiene tiempo, NO estamos en /pomodoro, y no está en PIP
const isVisible = computed(() => {
  const hasTime = timeLeft.value !== null && timeLeft.value !== undefined;
  const isActiveSession = isRunning.value || timeLeft.value < (currentPhase.value === 'work' ? 25*60 : 5*60);
  return hasTime && isActiveSession && !isOnPomodoroPage.value && !isPipActive.value;
});

const formattedTime = computed(() => {
  if (timeLeft.value == null) return "00:00";
  const mins = Math.floor(timeLeft.value / 60);
  const secs = timeLeft.value % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

const togglePlay = () => {
  if (isRunning.value) {
    stopTimer();
  } else {
    startTimer();
  }
};

const skipPhase = () => {
  timeLeft.value = 0; 
};

// --- DRAGGABLE LOGIC ---
const widgetRef = ref(null);
const position = ref({ x: -1, y: -1 });
const isDragging = ref(false);
const startPos = ref({ x: 0, y: 0 });

const initDrag = (e) => {
    isDragging.value = true;
    startPos.value = {
        x: e.clientX - (position.value.x !== -1 ? position.value.x : widgetRef.value.getBoundingClientRect().left),
        y: e.clientY - (position.value.y !== -1 ? position.value.y : widgetRef.value.getBoundingClientRect().top)
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

// --- PIP LOGIC ---
const abrirDocumentPIP = async () => {
  if (!('documentPictureInPicture' in window)) {
    console.warn("Navegador no soporta Document PIP.");
    return;
  }
  if (isPipActive.value) return;

  try {
    const pip = await window.documentPictureInPicture.requestWindow({
      width: 280,
      height: 180,
    });
    pipWindow.value = pip;
    isPipActive.value = true;
    
    // Inyectar tema Blanco/Marrón de Cronos
    const extraStyle = pip.document.createElement('style');
    extraStyle.textContent = `
        body { 
            background-color: #fdfaf8; /* Blanco hueso */
            color: #4d2323; 
            display: flex; 
            flex-direction: column;
            align-items: center; 
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .pip-container { text-align: center; width: 100%; padding: 10px; display: flex; flex-direction: column; align-items: center; }
        .pip-time { font-size: 3.5rem; font-family: monospace; font-weight: bold; margin: 10px 0; color: #4d2323; text-align: center; }
        .pip-controls { display: flex; justify-content: center; gap: 15px; margin-top: 10px; width: 100%; }
        .pip-controls button {
            background: #4d2323; color: white; border: none; 
            border-radius: 50%; width: 50px; height: 50px; 
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.2s;
        }
        .pip-controls button:hover { background: #7b413f; }
        .pip-title { font-size: 1rem; font-weight: bold; color: #7b413f; text-transform: uppercase; letter-spacing: 2px; text-align: center; width: 100%;}
    `;
    pip.document.head.appendChild(extraStyle);

    // Mover contenedor
    const timerElement = document.getElementById('timer-pip-container-content');
    if (timerElement) {
        pip.document.body.appendChild(timerElement);
        // Ajustes para que en PIP se centre perfectamente
        timerElement.style.position = 'static';
        timerElement.style.boxShadow = 'none';
        timerElement.style.border = 'none';
        timerElement.style.width = '100%';
        timerElement.style.background = 'transparent';
        timerElement.style.display = 'flex';
        timerElement.style.flexDirection = 'column';
        timerElement.style.alignItems = 'center';
        timerElement.style.justifyContent = 'center';
    }

    pip.addEventListener("pagehide", () => {
      isPipActive.value = false;
      const originalContainer = document.getElementById('timer-pip-container-wrapper');
      const movingElement = pip.document.getElementById('timer-pip-container-content');
      if (originalContainer && movingElement) {
         // Restaurar estilos
         movingElement.style.position = '';
         movingElement.style.boxShadow = '';
         movingElement.style.border = '';
         movingElement.style.width = '';
         movingElement.style.background = '';
         movingElement.style.display = '';
         movingElement.style.flexDirection = '';
         movingElement.style.alignItems = '';
         movingElement.style.justifyContent = '';
         originalContainer.appendChild(movingElement);
      }
      pipWindow.value = null;
    });

  } catch (error) {
    console.error("Error abriendo PIP (posiblemente bloqueado por falta de interacción):", error);
  }
};

const handleVisibilityChange = () => {
    // Intentar abrir PIP automáticamente si la pestaña se oculta y el timer está corriendo
    if (document.hidden && isRunning.value && !isPipActive.value && !isOnPomodoroPage.value) {
        abrirDocumentPIP().catch(e => console.log("Auto-PiP bloqueado por el navegador", e));
    }
};

onMounted(() => {
    if ("Notification" in window && Notification.permission !== "granted" && Notification.permission !== "denied") {
        Notification.requestPermission();
    }
    document.addEventListener("visibilitychange", handleVisibilityChange);
});

onUnmounted(() => {
    document.removeEventListener("visibilitychange", handleVisibilityChange);
});

</script>

<template>
  <div v-show="isVisible || isPipActive" id="timer-pip-container-wrapper">
    <div 
      v-show="isVisible || isPipActive" 
      id="timer-pip-container-content" 
      ref="widgetRef"
      class="mini-timer-widget"
      :style="position.x !== -1 && !isPipActive ? { left: position.x + 'px', top: position.y + 'px', right: 'auto', bottom: 'auto' } : {}"
    >
      <!-- Header / Drag Handle -->
      <div 
        class="timer-header"
        @mousedown="!isPipActive && initDrag($event)"
        :style="!isPipActive ? 'cursor: grab' : ''"
      >
        <span class="pip-title">{{ currentPhase === 'work' ? 'TRABAJO' : 'DESCANSO' }}</span>
        <button v-if="!isPipActive" @click.stop="abrirDocumentPIP" title="Picture in Picture" class="pip-btn">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><path d="M12 8v4l3 3"/></svg>
        </button>
      </div>
      
      <!-- Contador -->
      <div class="pip-time">
        {{ formattedTime }}
      </div>
      
      <!-- Controles -->
      <div class="pip-controls">
        <button @click="togglePlay" class="btn-play">
          <svg v-if="isRunning" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
          <svg v-else viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        </button>
        <button @click="skipPhase" class="btn-skip" title="Saltar Fase">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="5" x2="19" y2="19"/></svg>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Tema Blanco/Marrón Cronos Notes */
.mini-timer-widget {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 240px;
  background-color: #ffffff;
  border: 2px solid #e5e7eb;
  color: #4d2323;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  z-index: 9999;
  user-select: none;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.timer-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 8px;
  margin-bottom: 8px;
}

.pip-title {
  font-weight: 700;
  font-size: 0.9rem;
  letter-spacing: 1px;
  color: #7b413f;
}

.pip-btn {
  background: transparent;
  border: none;
  color: #7b413f;
  cursor: pointer;
  transition: opacity 0.2s;
}

.pip-btn:hover {
  opacity: 0.7;
}

.pip-time {
  font-size: 2.8rem;
  font-family: 'Courier New', Courier, monospace;
  font-weight: bold;
  letter-spacing: 1px;
  margin: 5px 0;
  color: #4d2323;
}

.pip-controls {
  display: flex;
  gap: 15px;
  margin-top: 5px;
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
  transition: transform 0.1s, background 0.2s;
}

.btn-play:hover, .btn-skip:hover {
  background: #7b413f;
  transform: scale(1.05);
}

.btn-play:active, .btn-skip:active {
  transform: scale(0.95);
}
</style>
