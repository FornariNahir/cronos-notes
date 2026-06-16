import { ref, computed, watch, onUnmounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useZenMixer } from './useZenMixer';

// Module-level shared states (persistent across page navigations in SPA)
const isRunning = ref(false);
const currentPhase = ref('work');
const currentCycle = ref(1);
const totalSeconds = ref(25 * 60);
const timeLeft = ref(25 * 60);
const quickStartTaskId = ref('');
const selectedConfigId = ref('');
const minutesRegisteredInCurrentPhase = ref(0);
const localSesionActiva = ref(null);

const tickCallback = ref(null);
const phaseCompleteCallback = ref(null);

let timerInterval = null;

const updateDocumentTitle = () => {
  if (isRunning.value && timeLeft.value > 0) {
    const mins = Math.floor(timeLeft.value / 60);
    const secs = timeLeft.value % 60;
    const timeStr = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    const phaseStr = currentPhase.value === 'work' ? 'Trabajo' : 'Descanso';
    
    // Get the base title (without any existing timer prefix)
    const regex = /^\[\d{2}:\d{2}\]\s+[^|]+\s+\|\s+/;
    const baseTitle = document.title.replace(regex, '');
    
    document.title = `[${timeStr}] ${phaseStr} | ${baseTitle}`;
  }
};

const restoreDocumentTitle = () => {
  const regex = /^\[\d{2}:\d{2}\]\s+[^|]+\s+\|\s+/;
  document.title = document.title.replace(regex, '');
};

export function usePomodoroTimer(props) {
  const form = useForm({
    duracionSesion: 25,
    duracionDescansoCorto: 5,
    duracionDescansoLargo: 15,
    ciclosObjetivo: 4,
    idTarea: '',
    sonidoSeleccionado: '',
    volumenSonido: 50
  });

  watch(selectedConfigId, (newId) => {
    if (newId && props?.configs) {
      const config = props.configs.find(c => c.idConfiguracionPomodoro == newId);
      if (config) {
        form.duracionSesion = config.duracionSesion;
        form.duracionDescansoCorto = config.duracionDescansoCorto;
        form.duracionDescansoLargo = config.duracionDescansoLargo;
        form.ciclosObjetivo = config.sesionesPrevioDescansoLargo;
      }
    }
  });

  watch(() => props?.sesionActiva, (newVal) => {
    if (newVal !== undefined) {
      localSesionActiva.value = newVal;
    }
  }, { immediate: true });

  const registerCallbacks = (onTick, onPhaseComplete) => {
    if (onTick) tickCallback.value = onTick;
    if (onPhaseComplete) phaseCompleteCallback.value = onPhaseComplete;
  };

  const iniciarInicioRapido = () => {
    if (props?.isGuest) {
      localSesionActiva.value = {
        idSesion: 'guest',
        duracionSesion: 25,
        duracionDescansoCorto: 5,
        duracionDescansoLargo: 15,
        sesionesPrevioDescansoLargo: 4,
        tituloTarea: 'Sesión Invitado'
      };
      initTimer();
      return;
    }
    router.post(route('pomodoro.iniciar'), {
      esInicioRapido: true,
      idTarea: quickStartTaskId.value
    });
  };

  const iniciarSesion = () => {
    if (props?.isGuest) {
      localSesionActiva.value = {
        idSesion: 'guest_custom',
        duracionSesion: form.duracionSesion || 25,
        duracionDescansoCorto: form.duracionDescansoCorto || 5,
        duracionDescansoLargo: form.duracionDescansoLargo || 15,
        sesionesPrevioDescansoLargo: form.ciclosObjetivo || 4,
        tituloTarea: 'Sesión Personalizada (Invitado)'
      };
      initTimer();
      return;
    }
    form.idTarea = quickStartTaskId.value;
    form.post(route('pomodoro.iniciar'));
  };

  const getStorageKey = () => {
    return localSesionActiva.value ? `pomodoro_session_${localSesionActiva.value.idSesion}` : 'pomodoro_zen_local';
  };

  const saveStateToStorage = () => {
    const key = getStorageKey();
    const state = {
      currentPhase: currentPhase.value,
      currentCycle: currentCycle.value,
      totalSeconds: totalSeconds.value,
      currentSeconds: timeLeft.value,
      isRunning: isRunning.value,
      minutesRegisteredInCurrentPhase: minutesRegisteredInCurrentPhase.value
    };
    localStorage.setItem(key, JSON.stringify(state));
  };

  const loadStateFromStorage = () => {
    const key = getStorageKey();
    const stored = localStorage.getItem(key);
    if (!stored) return false;
    try {
      const state = JSON.parse(stored);
      currentPhase.value = state.currentPhase;
      currentCycle.value = state.currentCycle;
      totalSeconds.value = state.totalSeconds;
      timeLeft.value = state.currentSeconds;
      isRunning.value = state.isRunning;
      minutesRegisteredInCurrentPhase.value = state.minutesRegisteredInCurrentPhase || 0;
      return true;
    } catch (e) {
      return false;
    }
  };

  const clearStorage = () => {
    const key = getStorageKey();
    localStorage.removeItem(key);
  };

  const initTimer = () => {
    if (isRunning.value) return; // Do not interrupt a running background timer
    
    if (localSesionActiva.value) {
      const restored = loadStateFromStorage();
      if (!restored) {
        totalSeconds.value = localSesionActiva.value.duracionSesion * 60;
        timeLeft.value = totalSeconds.value;
        currentCycle.value = 1;
        currentPhase.value = 'work';
        isRunning.value = false;
        minutesRegisteredInCurrentPhase.value = 0;
      }
    } else {
      timeLeft.value = 25 * 60;
      totalSeconds.value = 25 * 60;
      currentPhase.value = 'work';
      isRunning.value = false;
      minutesRegisteredInCurrentPhase.value = 0;
    }
  };

  const startTimer = (onTick, onPhaseComplete, isRestored = false) => {
    if (onTick) tickCallback.value = onTick;
    if (onPhaseComplete) phaseCompleteCallback.value = onPhaseComplete;
    
    if (isRunning.value && !isRestored) return;
    isRunning.value = true;
    
    if (!isRestored) {
      saveStateToStorage();
      if (localSesionActiva.value && !props?.isGuest) {
        window.axios.patch(route('pomodoro.estado'), { estado: 'En Progreso' });
      }

      // Play active sounds from global mixer
      const { playActiveSounds } = useZenMixer();
      playActiveSounds();
    }

    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
      if (timeLeft.value > 0) {
        timeLeft.value--;
        saveStateToStorage();
        updateDocumentTitle();

        if (currentPhase.value === 'work' && localSesionActiva.value && !props?.isGuest) {
          const elapsedSeconds = totalSeconds.value - timeLeft.value;
          if (elapsedSeconds > 0 && elapsedSeconds % 60 === 0 && timeLeft.value > 0) {
            window.axios.post(route('pomodoro.registrar'), {
              minutosTrabajados: 1,
              incrementarCiclo: false
            });
            minutesRegisteredInCurrentPhase.value++;
            saveStateToStorage();
          }
        }

        if (tickCallback.value) tickCallback.value(timeLeft.value);
      } else {
        stopTimer();
        completePhase();
        if (phaseCompleteCallback.value) phaseCompleteCallback.value();
      }
    }, 1000);
  };

  const stopTimer = () => {
    isRunning.value = false;
    clearInterval(timerInterval);
    saveStateToStorage();
    restoreDocumentTitle();

    // Pause active sounds from global mixer
    const { pauseActiveSounds } = useZenMixer();
    pauseActiveSounds();

    if (localSesionActiva.value && !props?.isGuest) {
      window.axios.patch(route('pomodoro.estado'), { estado: 'Pausada' });
    }
  };

  const resetTimer = () => {
    stopTimer();
    timeLeft.value = totalSeconds.value;
    minutesRegisteredInCurrentPhase.value = 0;
    saveStateToStorage();
    restoreDocumentTitle();

    // Stop all sounds from global mixer
    const { stopAllSounds } = useZenMixer();
    stopAllSounds();
  };

  const completePhase = () => {
    stopTimer();
    let currentMinutos = Math.floor((totalSeconds.value - timeLeft.value) / 60);
    
    if (currentPhase.value === 'work') {
      if (localSesionActiva.value && !props?.isGuest) {
        const minutosRestantes = currentMinutos - minutesRegisteredInCurrentPhase.value;
        window.axios.post(route('pomodoro.registrar'), { 
          minutosTrabajados: minutosRestantes > 0 ? minutosRestantes : 0,
          incrementarCiclo: true
        });
      }
      if (localSesionActiva.value) {
        if (currentCycle.value >= localSesionActiva.value.sesionesPrevioDescansoLargo) {
          currentPhase.value = 'longBreak';
          totalSeconds.value = localSesionActiva.value.duracionDescansoLargo * 60;
        } else {
          currentCycle.value++;
          currentPhase.value = 'shortBreak';
          totalSeconds.value = localSesionActiva.value.duracionDescansoCorto * 60;
        }
      }
    } else if (currentPhase.value === 'shortBreak' || currentPhase.value === 'longBreak') {
      if (currentPhase.value === 'longBreak') {
        currentCycle.value = 1;
      }
      currentPhase.value = 'work';
      if (localSesionActiva.value) {
        totalSeconds.value = localSesionActiva.value.duracionSesion * 60;
      }
    }

    minutesRegisteredInCurrentPhase.value = 0;
    timeLeft.value = totalSeconds.value;
    saveStateToStorage();
  };

  const endSession = (marcarTareaCompletada = null) => {
    if (!localSesionActiva.value) return;
    
    stopTimer();
    clearStorage();
    restoreDocumentTitle();

    // Stop all sounds from global mixer
    const { stopAllSounds } = useZenMixer();
    stopAllSounds();

    if (props?.isGuest) {
      localSesionActiva.value = null;
      return;
    }

    let minutosIncompletos = 0;
    if (currentPhase.value === 'work') {
      const elapsedMinutes = Math.floor((totalSeconds.value - timeLeft.value) / 60);
      minutosIncompletos = elapsedMinutes - minutesRegisteredInCurrentPhase.value;
      if (minutosIncompletos < 0) minutosIncompletos = 0;
    }
    
    let finalEstado = 'Cancelada';
    if (marcarTareaCompletada === true) {
      finalEstado = 'Completada';
    } else if (marcarTareaCompletada === false) {
      finalEstado = 'Cancelada';
    } else {
      const ciclosObjetivo = localSesionActiva.value.sesionesPrevioDescansoLargo || 4;
      const ciclosCompletados = currentCycle.value - 1 + (currentPhase.value !== 'work' ? 1 : 0);
      finalEstado = ciclosCompletados >= ciclosObjetivo ? 'Completada' : 'Cancelada';
    }

    router.post(route('pomodoro.finalizar'), {
      estado: finalEstado,
      minutosTrabajados: minutosIncompletos,
      marcarTareaCompletada: marcarTareaCompletada
    });
  };

  onUnmounted(() => {
    tickCallback.value = null;
    phaseCompleteCallback.value = null;
  });

  return {
    isRunning,
    currentPhase,
    currentCycle,
    timeLeft,
    quickStartTaskId,
    selectedConfigId,
    form,
    iniciarInicioRapido,
    iniciarSesion,
    initTimer,
    startTimer,
    stopTimerLogic: stopTimer,
    resetTimerLogic: resetTimer,
    completePhase,
    endSession,
    saveStateToStorage,
    totalSeconds,
    localSesionActiva,
    registerCallbacks
  };
}
