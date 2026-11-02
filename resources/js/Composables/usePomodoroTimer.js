import { ref, computed, watch, onUnmounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useZenMixer } from './useZenMixer';
import { Howl, Howler } from 'howler';

export const ALERT_SOUNDS_CATALOG = [
  { id: 'zen-bell', nombre: 'Campana Zen Cálida', emoji: '🔔', src: '/audios/alertas/zen-bell.wav' },
  { id: 'marimba-chime', nombre: 'Acorde de Marimba', emoji: '🎶', src: '/audios/alertas/marimba-chime.wav' },
  { id: 'harp-break', nombre: 'Arpa de Descanso', emoji: '🪕', src: '/audios/alertas/harp-break.wav' },
  { id: 'focus-double-tone', nombre: 'Doble Toque Enfoque', emoji: '✨', src: '/audios/alertas/focus-double-tone.wav' }
];

// Precargar todas las instancias de audio en memoria desde el inicio para latencia 0ms
const howlInstances = {};
if (typeof window !== 'undefined') {
  for (const item of ALERT_SOUNDS_CATALOG) {
    howlInstances[item.id] = new Howl({
      src: [item.src],
      preload: true,
      html5: false // Web Audio API buffer en memoria para disparo instantáneo sin delay
    });
  }
}

const getHowlForAlert = (soundId) => {
  const item = ALERT_SOUNDS_CATALOG.find(s => s.id === soundId) || ALERT_SOUNDS_CATALOG[0];
  if (!howlInstances[item.id]) {
    howlInstances[item.id] = new Howl({
      src: [item.src],
      preload: true,
      html5: false
    });
  }
  return howlInstances[item.id];
};

// Estado global reactivo de Modo Oscuro / Claro compartido entre AppLayout, SesionZen y MiniTimerFlotante
const isGlobalDarkMode = ref(typeof window !== 'undefined' ? localStorage.getItem('cn-theme') === 'dark' : false);

export const setGlobalDarkMode = (dark) => {
  isGlobalDarkMode.value = !!dark;
  if (typeof window !== 'undefined') {
    localStorage.setItem('cn-theme', isGlobalDarkMode.value ? 'dark' : 'light');
    if (isGlobalDarkMode.value) {
      document.body.classList.remove('cn-body-light');
      document.body.classList.add('cn-body-dark');
    } else {
      document.body.classList.remove('cn-body-dark');
      document.body.classList.add('cn-body-light');
    }
  }
};

export const syncGlobalDarkModeFromStorage = () => {
  if (typeof window !== 'undefined') {
    const dark = localStorage.getItem('cn-theme') === 'dark';
    setGlobalDarkMode(dark);
  }
};

// Preferencias reactivas de alertas sonoras (persistidas en localStorage)
const alertEnabled = ref(typeof window !== 'undefined' ? localStorage.getItem('cronos_alert_enabled') !== 'false' : true);
const alertVolume = ref(typeof window !== 'undefined' ? parseFloat(localStorage.getItem('cronos_alert_volume') ?? '0.85') : 0.85);
const alertSoundWork = ref((typeof window !== 'undefined' && localStorage.getItem('cronos_alert_work')) || 'zen-bell');
const alertSoundBreak = ref((typeof window !== 'undefined' && localStorage.getItem('cronos_alert_break')) || 'focus-double-tone');

watch(alertEnabled, (val) => localStorage.setItem('cronos_alert_enabled', String(val)));
watch(alertVolume, (val) => localStorage.setItem('cronos_alert_volume', String(val)));
watch(alertSoundWork, (val) => localStorage.setItem('cronos_alert_work', val));
watch(alertSoundBreak, (val) => localStorage.setItem('cronos_alert_break', val));

const unlockAudioContext = () => {
  try {
    if (Howler.ctx && Howler.ctx.state === 'suspended') {
      Howler.ctx.resume();
    }
  } catch (e) {
    // ignore
  }
};

const playAlertSound = (soundId, forcePlay = false) => {
  if (!alertEnabled.value && !forcePlay) return;
  try {
    unlockAudioContext();
    const howl = getHowlForAlert(soundId);
    howl.stop();
    howl.volume(Math.max(0, Math.min(1, alertVolume.value)));
    howl.play();
  } catch (e) {
    console.error('Error reproduciendo sonido de alerta:', e);
  }
};

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
const isPipActive = ref(false);
const pipRequestCounter = ref(0);

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
    unlockAudioContext();
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
    unlockAudioContext();
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
      } else if (isRunning.value) {
        isRunning.value = false;
        startTimer(null, null, true);
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
    unlockAudioContext();
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
      if (timeLeft.value > 1) {
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
        // Llegó exactamente a 00:00 -> disparar sonido y cambio de fase al instante sin 1 seg extra de espera
        timeLeft.value = 0;
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

  const showNotification = (title, body) => {
    if ("Notification" in window && Notification.permission === "granted") {
      new Notification(title, { body, icon: '/imagenes/logo-cronos.png' });
    }
  };

  const completePhase = () => {
    // Reproducir sonido en el primer milisegundo antes de cualquier llamada de red
    if (currentPhase.value === 'work') {
      playAlertSound(alertSoundWork.value);
    } else {
      playAlertSound(alertSoundBreak.value);
    }

    stopTimer();
    let currentMinutos = Math.floor((totalSeconds.value - timeLeft.value) / 60);
    
    if (currentPhase.value === 'work') {
      showNotification("Pomodoro Completado", "Gran trabajo. Tómate un respiro.");
      
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
      showNotification("Descanso Terminado", "Es hora de volver a enfocarse.");
      
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

  const requestPipWindow = () => {
    pipRequestCounter.value++;
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
    stopTimer,
    stopTimerLogic: stopTimer,
    resetTimer,
    resetTimerLogic: resetTimer,
    completePhase,
    endSession,
    saveStateToStorage,
    totalSeconds,
    localSesionActiva,
    registerCallbacks,
    isPipActive,
    pipRequestCounter,
    requestPipWindow,
    alertEnabled,
    alertVolume,
    alertSoundWork,
    alertSoundBreak,
    playAlertSound,
    alertSoundsCatalog: ALERT_SOUNDS_CATALOG,
    isGlobalDarkMode,
    setGlobalDarkMode,
    syncGlobalDarkModeFromStorage
  };
}
