import { ref, computed, watch, onUnmounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

export function usePomodoroTimer(props) {
  const isRunning = ref(false);
  const currentPhase = ref('work');
  const currentCycle = ref(1);
  const totalSeconds = ref(25 * 60);
  const timeLeft = ref(25 * 60);
  let timerInterval = null;

  const quickStartTaskId = ref('');
  const selectedConfigId = ref('');

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

  const localSesionActiva = ref(props.sesionActiva);

  watch(() => props.sesionActiva, (newVal) => {
    localSesionActiva.value = newVal;
  }, { immediate: true });

  const iniciarInicioRapido = () => {
    if (props.isGuest) {
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
    if (props.isGuest) {
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
      isRunning: isRunning.value
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
    if (localSesionActiva.value) {
      const restored = loadStateFromStorage();
      if (!restored) {
        totalSeconds.value = localSesionActiva.value.duracionSesion * 60;
        timeLeft.value = totalSeconds.value;
        currentCycle.value = 1;
        currentPhase.value = 'work';
        isRunning.value = false;
      }
    } else {
      timeLeft.value = 25 * 60;
      totalSeconds.value = 25 * 60;
      currentPhase.value = 'work';
      isRunning.value = false;
    }
  };

  const startTimer = (onTick, onPhaseComplete, isRestored = false) => {
    if (isRunning.value && !isRestored) return;
    isRunning.value = true;
    
    if (!isRestored) {
      saveStateToStorage();
      if (localSesionActiva.value && !props.isGuest) {
        router.patch(route('pomodoro.estado'), { estado: 'En Progreso' }, { preserveScroll: true, preserveState: true });
      }
    }

    clearInterval(timerInterval);
    timerInterval = setInterval(() => {
      if (timeLeft.value > 0) {
        timeLeft.value--;
        saveStateToStorage();
        if (onTick) onTick(timeLeft.value);
      } else {
        stopTimer();
        if (onPhaseComplete) onPhaseComplete();
      }
    }, 1000);
  };

  const stopTimer = () => {
    isRunning.value = false;
    clearInterval(timerInterval);
    saveStateToStorage();
    if (localSesionActiva.value && !props.isGuest) {
      router.patch(route('pomodoro.estado'), { estado: 'Pausada' }, { preserveScroll: true, preserveState: true });
    }
  };

  const resetTimer = () => {
    stopTimer();
    timeLeft.value = totalSeconds.value;
    saveStateToStorage();
  };

  const completePhase = () => {
    stopTimer();
    let currentMinutos = Math.floor((totalSeconds.value - timeLeft.value) / 60);
    
    if (currentPhase.value === 'work') {
      if (!props.isGuest) {
        axios.post(route('pomodoro.registrarTrabajo'), { minutosTrabajados: currentMinutos });
      }
      if (currentCycle.value >= localSesionActiva.value.sesionesPrevioDescansoLargo) {
        currentPhase.value = 'longBreak';
        totalSeconds.value = localSesionActiva.value.duracionDescansoLargo * 60;
      } else {
        currentCycle.value++;
        currentPhase.value = 'shortBreak';
        totalSeconds.value = localSesionActiva.value.duracionDescansoCorto * 60;
      }
    } else if (currentPhase.value === 'shortBreak' || currentPhase.value === 'longBreak') {
      if (currentPhase.value === 'longBreak') {
        currentCycle.value = 1;
      }
      currentPhase.value = 'work';
      totalSeconds.value = localSesionActiva.value.duracionSesion * 60;
    }

    if (localSesionActiva.value && !props.isGuest) {
      router.patch(route('pomodoro.completarFase'), {
        fase: currentPhase.value,
        ciclo: currentCycle.value
      }, { preserveScroll: true, preserveState: true });
    }
    
    timeLeft.value = totalSeconds.value;
    saveStateToStorage();
  };

  const endSession = () => {
    if (!localSesionActiva.value) return;
    
    stopTimer();
    clearStorage();

    if (props.isGuest) {
      localSesionActiva.value = null;
      return;
    }

    let minutosIncompletos = 0;
    if (currentPhase.value === 'work') {
      minutosIncompletos = Math.floor((totalSeconds.value - timeLeft.value) / 60);
    }
    
    const ciclosObjetivo = localSesionActiva.value.sesionesPrevioDescansoLargo || 4;
    const ciclosCompletados = currentCycle.value - 1 + (currentPhase.value !== 'work' ? 1 : 0);
    const finalEstado = ciclosCompletados >= ciclosObjetivo ? 'Completada' : 'Cancelada';

    router.post(route('pomodoro.finalizar'), {
      estado: finalEstado,
      minutosTrabajados: minutosIncompletos
    });
  };

  onUnmounted(() => {
    clearInterval(timerInterval);
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
    localSesionActiva
  };
}
