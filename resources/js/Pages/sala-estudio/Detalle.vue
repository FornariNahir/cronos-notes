<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { usePomodoroTimer } from '@/Composables/usePomodoroTimer';

const props = defineProps({
  sala: {
    type: Object,
    required: true
  },
  participantesIniciales: {
    type: Array,
    default: () => []
  },
  pomodoroInicial: {
    type: Object,
    required: true
  },
  tareasPerfil: {
    type: Array,
    default: () => []
  },
  miEstadoInicial: {
    type: String,
    default: 'EnLinea'
  },
  puedeControlarSala: {
    type: Boolean,
    default: true
  },
  minutosInactividadMax: {
    type: Number,
    default: 30
  },
  serverTime: {
    type: Number,
    default: () => Math.floor(Date.now() / 1000)
  }
});

const {
  isRunning: globalIsRunning,
  currentPhase: globalPhase,
  currentCycle: globalCycle,
  timeLeft: globalTimeLeft,
  totalSeconds: globalTotalSeconds,
  localSesionActiva: globalSesionActiva,
  isPipActive,
  requestPipWindow,
  alertSoundWork,
  alertSoundBreak,
  playAlertSound
} = usePomodoroTimer();

const salaState = reactive({ ...props.sala });
const participantes = ref([...props.participantesIniciales]);
const miEstado = ref(props.miEstadoInicial || 'EnLinea');
const pomodoro = reactive({ ...props.pomodoroInicial });
const tareaSeleccionadaId = ref(props.tareasPerfil[0]?.idTarea ?? null);

// Diferencia entre el reloj del servidor y el navegador del usuario para sincronización exacta al segundo
const clockOffset = ref(props.serverTime - Math.floor(Date.now() / 1000));
const displaySeconds = ref(props.pomodoroInicial.segundosRestantes ?? 1500);
const segundosInactividad = ref(0);

const editandoMeet = ref(salaState.googleMeetUrl === 'https://meet.google.com/new');
const nuevoEnlaceMeet = ref(
  salaState.googleMeetUrl !== 'https://meet.google.com/new' ? salaState.googleMeetUrl : ''
);
const errorMeet = ref('');
const guardandoMeet = ref(false);
const copiadoExito = ref(false);
const showFinalizarModal = ref(false);

const showConfigPomodoro = ref(false);
const configForm = reactive({
  duracionTrabajo: pomodoro.duracionTrabajo || 25,
  duracionDescansoCorto: pomodoro.duracionDescansoCorto || 5,
  duracionDescansoLargo: pomodoro.duracionDescansoLargo || 15,
  ciclosObjetivo: pomodoro.ciclosObjetivo || 4
});

let localTickInterval = null;
let pollingInterval = null;
let isSendingAction = false;

const tareaEnfoqueActual = computed(() => {
  return props.tareasPerfil.find(t => t.idTarea === tareaSeleccionadaId.value) || null;
});

const sincronizarConMiniTimerGlobal = () => {
  globalIsRunning.value = !!pomodoro.corriendo;
  globalPhase.value = pomodoro.fase || 'work';
  globalCycle.value = pomodoro.cicloActual || 1;
  globalTimeLeft.value = displaySeconds.value;
  globalTotalSeconds.value = pomodoro.segundosTotalesFase || 1500;
  globalSesionActiva.value = {
    idSesion: `sala_${salaState.idSalaEstudio}`,
    tituloTarea: tareaEnfoqueActual.value
      ? `${salaState.tituloEvento}: ${tareaEnfoqueActual.value.tituloTarea}`
      : `Sala: ${salaState.tituloEvento}`,
    duracionSesion: pomodoro.duracionTrabajo,
    duracionDescansoCorto: pomodoro.duracionDescansoCorto,
    duracionDescansoLargo: pomodoro.duracionDescansoLargo,
    sesionesPrevioDescansoLargo: pomodoro.ciclosObjetivo
  };
};

const actualizarRelojLocal = () => {
  const ahoraServidorEstimado = Math.floor(Date.now() / 1000) + clockOffset.value;

  if (pomodoro.corriendo && pomodoro.finFaseTimestamp) {
    segundosInactividad.value = 0;
    const restantes = Math.max(0, pomodoro.finFaseTimestamp - ahoraServidorEstimado);

    if (displaySeconds.value > 0 && restantes === 0) {
      if (pomodoro.fase === 'work') {
        playAlertSound(alertSoundWork.value);
      } else {
        playAlertSound(alertSoundBreak.value);
      }
      displaySeconds.value = 0;
      pomodoro.corriendo = false;
      sincronizarEstadoConServidor();
    } else {
      displaySeconds.value = restantes;
    }
  } else {
    displaySeconds.value = pomodoro.segundosRestantes ?? pomodoro.segundosRestantesAlPausar ?? 1500;
    const ultimaAct = pomodoro.ultimaActividadAt || ahoraServidorEstimado;
    segundosInactividad.value = Math.max(0, ahoraServidorEstimado - ultimaAct);
  }

  sincronizarConMiniTimerGlobal();
};

const aplicarPomodoroRecibido = (nuevoPomodoro, serverTimeRecibido) => {
  if (serverTimeRecibido) {
    clockOffset.value = serverTimeRecibido - Math.floor(Date.now() / 1000);
  }
  const faseAnterior = pomodoro.fase;
  const estabaCorriendo = pomodoro.corriendo;

  Object.assign(pomodoro, nuevoPomodoro);

  if (estabaCorriendo && !pomodoro.corriendo && faseAnterior !== pomodoro.fase) {
    if (faseAnterior === 'work') {
      playAlertSound(alertSoundWork.value);
    } else {
      playAlertSound(alertSoundBreak.value);
    }
  }

  actualizarRelojLocal();
};

const sincronizarEstadoConServidor = async (nuevoEstadoManual = null) => {
  const esCambioManual = !!nuevoEstadoManual;
  if (nuevoEstadoManual) {
    miEstado.value = nuevoEstadoManual;
  }

  try {
    const { data } = await window.axios.post(route('salas.estado', salaState.idSalaEstudio), {
      estadoUsuario: miEstado.value,
      actividadUsuario: esCambioManual
    });

    if (data.finalizada) {
      globalSesionActiva.value = null;
      globalIsRunning.value = false;
      router.visit(route('salas.index'));
      return;
    }

    if (data.sala) {
      salaState.googleMeetUrl = data.sala.googleMeetUrl;
      salaState.googleMeetId = data.sala.googleMeetId;
      salaState.tituloEvento = data.sala.tituloEvento;
    }

    if (Array.isArray(data.participantes)) {
      participantes.value = data.participantes;
    }

    if (data.pomodoro && !isSendingAction) {
      aplicarPomodoroRecibido(data.pomodoro, data.serverTime);
    }
  } catch (e) {
    console.warn('Error sincronizando estado de la sala:', e);
  }
};

const ejecutarAccionPomodoro = async (accion, extraPayload = {}) => {
  isSendingAction = true;
  try {
    const { data } = await window.axios.post(route('salas.pomodoro', salaState.idSalaEstudio), {
      accion,
      ...extraPayload
    });
    if (data.pomodoro) {
      aplicarPomodoroRecibido(data.pomodoro, data.serverTime);
    }
    if (accion === 'iniciar') {
      miEstado.value = 'EnPomodoro';
    }
    if (accion === 'configurar') {
      showConfigPomodoro.value = false;
    }
  } catch (e) {
    console.error('Error ejecutando acción Pomodoro grupal:', e);
  } finally {
    isSendingAction = false;
  }
};

const aplicarPresetInline = (trabajo, corto, largo, ciclos) => {
  configForm.duracionTrabajo = trabajo;
  configForm.duracionDescansoCorto = corto;
  configForm.duracionDescansoLargo = largo;
  configForm.ciclosObjetivo = ciclos;
};

const abrirGoogleMeet = () => {
  sincronizarEstadoConServidor('EnMeet');
  window.open(salaState.googleMeetUrl || 'https://meet.google.com/new', '_blank', 'noopener,noreferrer');
};

const copiarEnlaceMeet = async () => {
  try {
    await navigator.clipboard.writeText(salaState.googleMeetUrl);
    copiadoExito.value = true;
    setTimeout(() => {
      copiadoExito.value = false;
    }, 2200);
  } catch (e) {
    // ignore
  }
};

const guardarEnlaceMeet = async () => {
  errorMeet.value = '';
  if (!nuevoEnlaceMeet.value.trim()) {
    errorMeet.value = 'Ingresá el enlace o código de la reunión de Google Meet.';
    return;
  }

  guardandoMeet.value = true;
  try {
    const { data } = await window.axios.patch(route('salas.actualizar-meet', salaState.idSalaEstudio), {
      enlaceMeet: nuevoEnlaceMeet.value
    });
    salaState.googleMeetUrl = data.googleMeetUrl;
    salaState.googleMeetId = data.googleMeetId;
    nuevoEnlaceMeet.value = data.googleMeetUrl;
    editandoMeet.value = false;
  } catch (e) {
    errorMeet.value = e.response?.data?.error || 'El formato del enlace o código de Google Meet no es válido.';
  } finally {
    guardandoMeet.value = false;
  }
};

const salirDeSala = () => {
  globalSesionActiva.value = null;
  globalIsRunning.value = false;
  router.post(route('salas.salir', salaState.idSalaEstudio));
};

const confirmarFinalizarSala = () => {
  showFinalizarModal.value = false;
  globalSesionActiva.value = null;
  globalIsRunning.value = false;
  router.post(route('salas.finalizar', salaState.idSalaEstudio));
};

const formattedTime = computed(() => {
  const s = Math.max(0, Number(displaySeconds.value) || 0);
  const mins = Math.floor(s / 60);
  const secs = s % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
});

const porcentajeProgreso = computed(() => {
  const total = pomodoro.segundosTotalesFase || 1500;
  if (total <= 0) return 0;
  return Math.min(100, Math.max(0, Math.round(((total - displaySeconds.value) / total) * 100)));
});

// Geometría del anillo circular SVG (radio 108 -> circunferencia ~678.58)
const circleCircumference = 2 * Math.PI * 108;
const circleDashoffset = computed(() => {
  return circleCircumference - (porcentajeProgreso.value / 100) * circleCircumference;
});

const etiquetaFase = computed(() => {
  if (pomodoro.fase === 'work') return 'Tiempo de enfoque grupal';
  if (pomodoro.fase === 'shortBreak') return 'Descanso corto grupal';
  return 'Descanso largo grupal';
});

const minutosRestantesAntesDeCierre = computed(() => {
  const maxSegundos = (props.minutosInactividadMax || 30) * 60;
  const restantes = Math.max(0, maxSegundos - segundosInactividad.value);
  return Math.ceil(restantes / 60);
});

const badgeEstadoInfo = (estado) => {
  if (estado === 'EnPomodoro') return { texto: 'En enfoque', clase: 'status-pomodoro' };
  if (estado === 'EnMeet') return { texto: 'En videollamada', clase: 'status-meet' };
  return { texto: 'En línea', clase: 'status-online' };
};

onMounted(() => {
  actualizarRelojLocal();
  localTickInterval = setInterval(actualizarRelojLocal, 1000);
  pollingInterval = setInterval(() => sincronizarEstadoConServidor(), 4000);
});

onUnmounted(() => {
  clearInterval(localTickInterval);
  clearInterval(pollingInterval);
});
</script>

<template>
  <Head :title="`Sala: ${salaState.tituloEvento}`" />
  <AppLayout>
    <div class="sala-detalle-container">
      <!-- Barra superior de la Sala -->
      <div class="sala-topbar card">
        <div class="sala-topbar-left">
          <div class="topbar-badges-row">
            <Link :href="route('salas.index')" class="back-salas-link">
              <i class="bi bi-arrow-left"></i>
              <span>Salas</span>
            </Link>
            <span class="badge-perfil-top">{{ salaState.perfilTitulo }}</span>
            <span class="badge-live-room">
              <span class="live-room-dot"></span>
              Sala activa
            </span>
          </div>
          <h1 class="sala-main-title">{{ salaState.tituloEvento }}</h1>
          <span class="sala-host-label">
            Anfitrión: <strong>{{ salaState.creadorNombre }}</strong>
            <template v-if="salaState.fechaCreacionTexto"> · Iniciada {{ salaState.fechaCreacionTexto }}</template>
          </span>
        </div>

        <div class="sala-topbar-actions">
          <button
            type="button"
            class="btn-pip-room"
            @click="requestPipWindow"
            :title="isPipActive ? 'Cerrar reloj flotante' : 'Abrir reloj en ventana flotante para ver mientras usás otras pestañas'"
          >
            <i class="bi bi-pip"></i>
            <span>{{ isPipActive ? 'Cerrar reloj flotante' : 'Reloj flotante' }}</span>
          </button>

          <button type="button" class="btn-salir-sala" @click="salirDeSala">
            Salir de la sala
          </button>

          <button
            v-if="puedeControlarSala"
            type="button"
            class="btn-finalizar-sala"
            @click="showFinalizarModal = true"
          >
            Finalizar sala
          </button>
        </div>
      </div>

      <div class="sala-main-grid">
        <!-- Columna Izquierda: Temporizador Pomodoro Grupal Sincronizado -->
        <div class="pomodoro-grupal-card card">
          <div class="pomodoro-top-meta">
            <span class="pomodoro-phase-chip" :class="pomodoro.fase">
              {{ etiquetaFase }}
            </span>

            <span v-if="!pomodoro.corriendo" class="inactivity-pill" title="Las salas en pausa prolongada se cierran solas para mantener limpio el espacio">
              Cierre por inactividad en {{ minutosRestantesAntesDeCierre }} min
            </span>
          </div>

          <!-- Indicador de Ciclos con barras de progreso limpias -->
          <div class="cycles-row">
            <span class="cycles-counter">Ciclo {{ pomodoro.cicloActual }} de {{ pomodoro.ciclosObjetivo }}</span>
            <div class="cycle-steps">
              <span
                v-for="idx in pomodoro.ciclosObjetivo"
                :key="idx"
                class="cycle-step-pill"
                :class="{
                  completed: idx < pomodoro.cicloActual,
                  current: idx === pomodoro.cicloActual
                }"
                :title="`Ciclo ${idx}`"
              ></span>
            </div>
          </div>

          <!-- Anillo de Progreso Circular + Reloj Sincronizado -->
          <div class="timer-ring-wrapper">
            <svg class="timer-ring-svg" viewBox="0 0 240 240">
              <circle
                class="timer-ring-track"
                cx="120"
                cy="120"
                r="108"
                fill="none"
                stroke-width="8"
              />
              <circle
                class="timer-ring-progress"
                :class="pomodoro.fase"
                cx="120"
                cy="120"
                r="108"
                fill="none"
                stroke-width="8"
                stroke-linecap="round"
                :stroke-dasharray="circleCircumference"
                :stroke-dashoffset="circleDashoffset"
              />
            </svg>
            <div class="timer-ring-center">
              <div class="clock-giant">{{ formattedTime }}</div>
              <div class="clock-sub-status">
                {{ pomodoro.corriendo ? 'En curso' : 'En pausa' }} · {{ porcentajeProgreso }}%
              </div>
            </div>
          </div>

          <!-- Objetivo de enfoque seleccionado -->
          <div v-if="tareaEnfoqueActual" class="active-focus-banner">
            <span class="focus-label">Objetivo actual:</span>
            <strong class="focus-task-name">{{ tareaEnfoqueActual.tituloTarea }}</strong>
          </div>

          <p class="sync-caption">
            Reloj sincronizado con todos los miembros de la sala
            <span v-if="pomodoro.actualizadoPor"> · Última acción por <strong>{{ pomodoro.actualizadoPor }}</strong></span>
          </p>

          <!-- Controles del Pomodoro Grupal -->
          <div class="group-controls">
            <button
              v-if="!pomodoro.corriendo"
              type="button"
              class="btn-ctrl-primary"
              @click="ejecutarAccionPomodoro('iniciar')"
            >
              Iniciar reloj grupal
            </button>
            <button
              v-else
              type="button"
              class="btn-ctrl-pause"
              @click="ejecutarAccionPomodoro('pausar')"
            >
              Pausar reloj
            </button>

            <button
              type="button"
              class="btn-ctrl-secondary"
              @click="ejecutarAccionPomodoro('saltar')"
              title="Avanzar a la siguiente etapa"
            >
              Saltar etapa
            </button>

            <button
              type="button"
              class="btn-ctrl-secondary"
              @click="ejecutarAccionPomodoro('reiniciar')"
              title="Reiniciar el tiempo de la etapa actual"
            >
              Reiniciar
            </button>

            <button
              type="button"
              class="btn-ctrl-secondary"
              :class="{ active: showConfigPomodoro }"
              @click="showConfigPomodoro = !showConfigPomodoro"
              title="Configurar duraciones del reloj grupal"
            >
              Ajustar tiempos
            </button>
          </div>

          <!-- Configuración inline de tiempos del Pomodoro grupal -->
          <div v-if="showConfigPomodoro" class="config-inline-box">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
              <span class="config-box-title">Duración de bloques</span>
              <div class="inline-presets">
                <button type="button" class="inline-preset-btn" @click="aplicarPresetInline(25, 5, 15, 4)">25 / 5</button>
                <button type="button" class="inline-preset-btn" @click="aplicarPresetInline(50, 10, 20, 3)">50 / 10</button>
                <button type="button" class="inline-preset-btn" @click="aplicarPresetInline(15, 3, 10, 4)">15 / 3</button>
              </div>
            </div>

            <div class="config-grid">
              <div>
                <label>Enfoque (min)</label>
                <input type="number" v-model.number="configForm.duracionTrabajo" min="1" max="120" class="input-sm-custom" />
              </div>
              <div>
                <label>Desc. corto</label>
                <input type="number" v-model.number="configForm.duracionDescansoCorto" min="1" max="30" class="input-sm-custom" />
              </div>
              <div>
                <label>Desc. largo</label>
                <input type="number" v-model.number="configForm.duracionDescansoLargo" min="5" max="60" class="input-sm-custom" />
              </div>
              <div>
                <label>Ciclos</label>
                <input type="number" v-model.number="configForm.ciclosObjetivo" min="1" max="10" class="input-sm-custom" />
              </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
              <button type="button" class="btn-ctrl-secondary py-1 px-3" @click="showConfigPomodoro = false">Cancelar</button>
              <button
                type="button"
                class="btn-ctrl-primary py-1 px-3"
                style="font-size: 0.84rem;"
                @click="ejecutarAccionPomodoro('configurar', configForm)"
              >
                Guardar tiempos
              </button>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Google Meet + Compañeros + Tareas del Perfil -->
        <div class="sala-side-column">
          <!-- Tarjeta Google Meet -->
          <div class="side-panel-card card">
            <div class="side-card-header">
              <h3 class="side-card-title">Videollamada en Google Meet</h3>
              <button
                type="button"
                class="btn-link-edit"
                @click="editandoMeet = !editandoMeet"
              >
                {{ editandoMeet ? 'Cerrar edición' : 'Cambiar enlace' }}
              </button>
            </div>

            <p v-if="salaState.googleMeetUrl === 'https://meet.google.com/new'" class="meet-info-text">
              Iniciá una llamada con el botón de abajo y pegá el código o enlace de la reunión para que todo el equipo entre directo.
            </p>
            <div v-else class="meet-active-url-box">
              <span class="meet-url-label">Reunión vinculada:</span>
              <code class="meet-url-code">{{ salaState.googleMeetUrl }}</code>
            </div>

            <div class="d-flex gap-2 flex-wrap">
              <button type="button" class="btn-join-meet" @click="abrirGoogleMeet">
                {{ salaState.googleMeetUrl === 'https://meet.google.com/new' ? 'Crear o abrir Google Meet' : 'Unirse a la videollamada' }}
              </button>

              <button
                v-if="salaState.googleMeetUrl !== 'https://meet.google.com/new'"
                type="button"
                class="btn-copy-meet"
                @click="copiarEnlaceMeet"
              >
                {{ copiadoExito ? 'Enlace copiado' : 'Copiar enlace' }}
              </button>
            </div>

            <!-- Editor rápido para pegar el link de Meet compartido -->
            <div v-if="editandoMeet" class="meet-edit-box">
              <label class="meet-edit-label">Enlace o código de Google Meet (ej: <code>abc-defg-hij</code>)</label>
              <div class="d-flex gap-2">
                <input
                  type="text"
                  v-model="nuevoEnlaceMeet"
                  class="input-sm-custom"
                  style="flex: 1;"
                  placeholder="https://meet.google.com/abc-defg-hij"
                />
                <button
                  type="button"
                  class="btn-save-meet"
                  :disabled="guardandoMeet"
                  @click="guardarEnlaceMeet"
                >
                  {{ guardandoMeet ? 'Guardando...' : 'Guardar' }}
                </button>
              </div>
              <div v-if="errorMeet" class="text-danger mt-1" style="font-size: 0.78rem;">
                {{ errorMeet }}
              </div>
            </div>
          </div>

          <!-- Tarjeta de Compañeros en la Sala -->
          <div class="side-panel-card card">
            <div class="side-card-header">
              <h3 class="side-card-title">
                Compañeros en la sala ({{ participantes.length }})
              </h3>
              <span class="live-sync-badge">Sincronizado</span>
            </div>

            <!-- Selector rápido del propio estado -->
            <div class="mi-estado-selector">
              <span class="mi-estado-label">Tu actividad actual</span>
              <div class="estado-pills">
                <button
                  type="button"
                  class="estado-pill-btn"
                  :class="{ active: miEstado === 'EnLinea' }"
                  @click="sincronizarEstadoConServidor('EnLinea')"
                >
                  <span class="pill-dot dot-online"></span>
                  En línea
                </button>
                <button
                  type="button"
                  class="estado-pill-btn"
                  :class="{ active: miEstado === 'EnPomodoro' }"
                  @click="sincronizarEstadoConServidor('EnPomodoro')"
                >
                  <span class="pill-dot dot-focus"></span>
                  En enfoque
                </button>
                <button
                  type="button"
                  class="estado-pill-btn"
                  :class="{ active: miEstado === 'EnMeet' }"
                  @click="sincronizarEstadoConServidor('EnMeet')"
                >
                  <span class="pill-dot dot-meet"></span>
                  En videollamada
                </button>
              </div>
            </div>

            <!-- Lista de participantes activos -->
            <div class="participantes-list">
              <div
                v-for="p in participantes"
                :key="p.idUsuario"
                class="participante-row"
              >
                <div class="participante-avatar">
                  {{ p.inicial }}
                </div>
                <div class="participante-info">
                  <div class="participante-nombre">
                    <span class="text-truncate">{{ p.nombreCompleto }}</span>
                    <span v-if="p.idUsuario === salaState.idUsuarioCreador" class="host-tag">Anfitrión</span>
                  </div>
                  <div class="participante-email">{{ p.email }}</div>
                </div>
                <span class="participante-status-badge" :class="badgeEstadoInfo(p.estadoUsuario).clase">
                  {{ badgeEstadoInfo(p.estadoUsuario).texto }}
                </span>
              </div>
            </div>
          </div>

          <!-- Tarjeta de Tareas Pendientes del Perfil -->
          <div class="side-panel-card card">
            <div class="side-card-header">
              <h3 class="side-card-title">Tareas pendientes del perfil ({{ tareasPerfil.length }})</h3>
              <Link :href="route('gestion-tareas')" class="btn-link-edit">Ver todas</Link>
            </div>

            <div v-if="tareasPerfil.length === 0" class="empty-tasks-Note">
              No hay tareas pendientes en este perfil.
            </div>

            <div v-else class="sala-tasks-list">
              <div
                v-for="t in tareasPerfil"
                :key="t.idTarea"
                class="sala-task-item"
                :class="{ selected: tareaSeleccionadaId === t.idTarea }"
                @click="tareaSeleccionadaId = (tareaSeleccionadaId === t.idTarea ? null : t.idTarea)"
                title="Hacé clic para fijar como objetivo actual de la sesión"
              >
                <div class="sala-task-main">
                  <span class="sala-task-title">{{ t.tituloTarea }}</span>
                  <span v-if="t.fechaLimite" class="sala-task-date">Vence {{ t.fechaLimite }}</span>
                </div>
                <span
                  class="sala-task-priority"
                  :class="'prio-' + (t.prioridadTarea || 'Media').toLowerCase()"
                >
                  {{ t.prioridadTarea }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Institucional de Confirmación para Finalizar Sala -->
      <ConfirmModal
        :show="showFinalizarModal"
        title="Finalizar Sala de Estudio"
        message="¿Estás seguro de que querés cerrar esta sala para todos los participantes? El temporizador grupal se detendrá."
        confirm-text="Sí, finalizar sala"
        cancel-text="Cancelar"
        :is-danger="true"
        @close="showFinalizarModal = false"
        @confirm="confirmarFinalizarSala"
      />
    </div>
  </AppLayout>
</template>

<style scoped>
.sala-detalle-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 4px 8px 48px 8px;
}

.sala-topbar {
  background: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 14px;
  padding: 18px 22px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 22px;
}

.topbar-badges-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 6px;
}

.back-salas-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.78rem;
  font-weight: 700;
  color: #612c2d;
  text-decoration: none;
  padding: 3px 10px;
  border-radius: 999px;
  background: #faf3ef;
  border: 1px solid #dcc5be;
  transition: background-color 0.2s;
}

.back-salas-link:hover {
  background: #f2e4dc;
}

.badge-perfil-top {
  background: #f3e8e6;
  color: #612c2d;
  font-weight: 700;
  font-size: 0.76rem;
  padding: 4px 11px;
  border-radius: 999px;
  white-space: nowrap;
}

.badge-live-room {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
  font-weight: 700;
  font-size: 0.74rem;
  padding: 3px 10px;
  border-radius: 999px;
  white-space: nowrap;
}

.live-room-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
}

.sala-main-title {
  font-size: 1.45rem;
  font-weight: 800;
  color: #4d2323;
  margin: 0 0 2px 0;
}

.sala-host-label {
  font-size: 0.83rem;
  color: #7b413f;
}

.sala-topbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-pip-room {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: #faf3ef;
  color: #612c2d;
  border: 1px solid #612c2d;
  border-radius: 9px;
  padding: 8px 14px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  transition: 0.2s;
  white-space: nowrap;
}

.btn-pip-room:hover {
  background: #612c2d;
  color: #ffffff;
}

.btn-salir-sala {
  background: #faf3ef;
  color: #612c2d;
  border: 1px solid #dcc5be;
  border-radius: 9px;
  padding: 8px 14px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  white-space: nowrap;
}

.btn-salir-sala:hover {
  background: #f2e4dc;
}

.btn-finalizar-sala {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  border-radius: 9px;
  padding: 8px 14px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  white-space: nowrap;
}

.btn-finalizar-sala:hover {
  background: #fee2e2;
}

.sala-main-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 22px;
  align-items: start;
}

@media (max-width: 992px) {
  .sala-main-grid {
    grid-template-columns: 1fr;
  }
}

/* Tarjeta Pomodoro Grupal */
.pomodoro-grupal-card {
  background: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 16px;
  padding: 28px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.pomodoro-top-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}

.pomodoro-phase-chip {
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  padding: 5px 14px;
  border-radius: 999px;
  background: #f3e8e6;
  color: #612c2d;
}

.pomodoro-phase-chip.shortBreak,
.pomodoro-phase-chip.longBreak {
  background: #ecfdf5;
  color: #047857;
}

.inactivity-pill {
  font-size: 0.74rem;
  font-weight: 600;
  color: #8c5854;
  background: #faf3ef;
  border: 1px solid #eadcd7;
  padding: 4px 10px;
  border-radius: 999px;
}

.cycles-row {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.cycles-counter {
  font-size: 0.84rem;
  font-weight: 600;
  color: #7b413f;
}

.cycle-steps {
  display: flex;
  gap: 6px;
}

.cycle-step-pill {
  width: 28px;
  height: 6px;
  border-radius: 999px;
  background: #eadcd7;
  transition: all 0.25s;
}

.cycle-step-pill.completed {
  background: #612c2d;
}

.cycle-step-pill.current {
  background: #a55e57;
  width: 38px;
}

/* Anillo Circular del Reloj */
.timer-ring-wrapper {
  position: relative;
  width: 236px;
  height: 236px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 6px 0 18px 0;
}

.timer-ring-svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.timer-ring-track {
  stroke: #f3e8e6;
}

.timer-ring-progress {
  stroke: #612c2d;
  transition: stroke-dashoffset 0.5s ease;
}

.timer-ring-progress.shortBreak,
.timer-ring-progress.longBreak {
  stroke: #10b981;
}

.timer-ring-center {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.clock-giant {
  font-size: 3.6rem;
  font-weight: 800;
  color: #4d2323;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  letter-spacing: -1px;
}

.clock-sub-status {
  margin-top: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  color: #7b413f;
}

.active-focus-banner {
  background: #faf3ef;
  border: 1px solid #eadcd7;
  border-radius: 10px;
  padding: 8px 14px;
  margin-bottom: 12px;
  font-size: 0.83rem;
  color: #4d2323;
  max-width: 440px;
}

.focus-label {
  color: #7b413f;
  margin-right: 6px;
}

.sync-caption {
  font-size: 0.8rem;
  color: #7b413f;
  margin-bottom: 20px;
}

.group-controls {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
}

.btn-ctrl-primary {
  background: #612c2d;
  color: #ffffff;
  border: none;
  border-radius: 9px;
  padding: 10px 20px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-ctrl-primary:hover {
  background: #7b413f;
}

.btn-ctrl-pause {
  background: #b45309;
  color: #ffffff;
  border: none;
  border-radius: 9px;
  padding: 10px 20px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
}

.btn-ctrl-secondary {
  background: #faf3ef;
  color: #612c2d;
  border: 1px solid #dcc5be;
  border-radius: 9px;
  padding: 9px 14px;
  font-weight: 600;
  font-size: 0.84rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-ctrl-secondary:hover,
.btn-ctrl-secondary.active {
  background: #f2e4dc;
  border-color: #a55e57;
}

.config-inline-box {
  width: 100%;
  max-width: 480px;
  margin-top: 18px;
  padding: 16px;
  border-radius: 12px;
  background: #faf3ef;
  border: 1px solid #eadcd7;
  text-align: left;
}

.config-box-title {
  font-size: 0.82rem;
  font-weight: 700;
  color: #4d2323;
}

.inline-presets {
  display: flex;
  gap: 6px;
}

.inline-preset-btn {
  background: #ffffff;
  border: 1px solid #dcc5be;
  color: #612c2d;
  border-radius: 999px;
  padding: 2px 9px;
  font-size: 0.73rem;
  font-weight: 600;
  cursor: pointer;
}

.inline-preset-btn:hover {
  background: #612c2d;
  color: #ffffff;
}

.config-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

@media (max-width: 540px) {
  .config-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.config-grid label {
  display: block;
  font-size: 0.74rem;
  font-weight: 700;
  color: #612c2d;
  margin-bottom: 4px;
}

.input-sm-custom {
  width: 100%;
  padding: 7px 10px;
  border-radius: 8px;
  border: 1px solid #dcc5be;
  font-size: 0.85rem;
  color: #3b1919;
  background: #ffffff;
}

/* Columna Derecha */
.sala-side-column {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.side-panel-card {
  background: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 14px;
  padding: 18px 20px;
}

.side-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.side-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #4d2323;
  margin: 0;
}

.btn-link-edit {
  background: transparent;
  border: none;
  color: #7b413f;
  font-size: 0.8rem;
  font-weight: 600;
  text-decoration: underline;
  cursor: pointer;
  white-space: nowrap;
}

.meet-info-text {
  font-size: 0.83rem;
  color: #6b4c49;
  margin-bottom: 12px;
  line-height: 1.45;
}

.meet-active-url-box {
  background: #faf3ef;
  border: 1px solid #eadcd7;
  border-radius: 9px;
  padding: 8px 12px;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  flex-wrap: wrap;
}

.meet-url-label {
  font-size: 0.76rem;
  font-weight: 600;
  color: #7b413f;
}

.meet-url-code {
  font-size: 0.78rem;
  color: #4d2323;
  word-break: break-all;
}

.btn-join-meet {
  flex: 1;
  background: #612c2d;
  color: #ffffff;
  border: none;
  border-radius: 9px;
  padding: 10px 16px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-join-meet:hover {
  background: #7b413f;
}

.btn-copy-meet {
  background: #faf3ef;
  color: #612c2d;
  border: 1px solid #dcc5be;
  border-radius: 9px;
  padding: 10px 14px;
  font-weight: 600;
  font-size: 0.84rem;
  cursor: pointer;
  white-space: nowrap;
}

.btn-copy-meet:hover {
  background: #f2e4dc;
}

.meet-edit-box {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px dashed #eadcd7;
}

.meet-edit-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: #612c2d;
  margin-bottom: 6px;
}

.btn-save-meet {
  background: #612c2d;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  padding: 7px 14px;
  font-weight: 600;
  font-size: 0.83rem;
  cursor: pointer;
}

/* Presencia / Compañeros */
.live-sync-badge {
  font-size: 0.72rem;
  font-weight: 700;
  color: #047857;
  background: #ecfdf5;
  padding: 3px 9px;
  border-radius: 999px;
  white-space: nowrap;
}

.mi-estado-selector {
  background: #faf3ef;
  border: 1px solid #eadcd7;
  border-radius: 10px;
  padding: 10px 12px;
  margin-bottom: 12px;
}

.mi-estado-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 700;
  color: #612c2d;
  margin-bottom: 6px;
}

.estado-pills {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.estado-pill-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: #ffffff;
  color: #612c2d;
  border: 1px solid #dcc5be;
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 0.76rem;
  font-weight: 600;
  cursor: pointer;
  transition: 0.15s;
  white-space: nowrap;
}

.estado-pill-btn.active {
  background: #612c2d;
  color: #ffffff;
  border-color: #612c2d;
}

.pill-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.dot-online { background: #10b981; }
.dot-focus { background: #e11d48; }
.dot-meet { background: #2563eb; }

.participantes-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.participante-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 10px;
  background: #faf3ef;
  border: 1px solid #eadcd7;
}

.participante-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #612c2d;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.participante-info {
  flex: 1;
  min-width: 0;
}

.participante-nombre {
  font-size: 0.86rem;
  font-weight: 700;
  color: #3b1919;
  display: flex;
  align-items: center;
  gap: 6px;
}

.host-tag {
  font-size: 0.68rem;
  background: #faebd7;
  color: #7b413f;
  padding: 1px 7px;
  border-radius: 999px;
  flex-shrink: 0;
}

.participante-email {
  font-size: 0.74rem;
  color: #7b413f;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.participante-status-badge {
  font-size: 0.73rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  white-space: nowrap;
  flex-shrink: 0;
}

.status-online {
  background: #ecfdf5;
  color: #047857;
}

.status-pomodoro {
  background: #f3e8e6;
  color: #612c2d;
}

.status-meet {
  background: #eff6ff;
  color: #1d4ed8;
}

/* Tareas del Perfil en Sala */
.empty-tasks-Note {
  font-size: 0.83rem;
  color: #7b413f;
  padding: 8px 0;
}

.sala-tasks-list {
  display: flex;
  flex-direction: column;
  gap: 7px;
  max-height: 220px;
  overflow-y: auto;
}

.sala-task-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 9px 12px;
  border-radius: 9px;
  background: #faf3ef;
  border: 1px solid #eadcd7;
  cursor: pointer;
  transition: all 0.15s;
}

.sala-task-item:hover {
  border-color: #a55e57;
}

.sala-task-item.selected {
  border-color: #612c2d;
  background: #f3e8e6;
}

.sala-task-main {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
}

.sala-task-title {
  font-size: 0.84rem;
  font-weight: 600;
  color: #3b1919;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sala-task-date {
  font-size: 0.72rem;
  color: #7b413f;
}

.sala-task-priority {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 999px;
  flex-shrink: 0;
}

.prio-alta {
  background: #fef2f2;
  color: #b91c1c;
}

.prio-media {
  background: #fffbeb;
  color: #b45309;
}

.prio-baja {
  background: #ecfdf5;
  color: #047857;
}
</style>

<style>
/* =====================================================
   MODO OSCURO — DETALLE DE SALA DE ESTUDIO
   ===================================================== */
body.cn-body-dark .back-salas-link {
  background-color: #612c2d !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .badge-perfil-top {
  background-color: #612c2d !important;
  color: #f4be95 !important;
  border: 1px solid #7b413f !important;
}

body.cn-body-dark .badge-live-room,
body.cn-body-dark .live-sync-badge {
  background-color: rgba(16, 185, 129, 0.16) !important;
  color: #6ee7b7 !important;
  border-color: rgba(16, 185, 129, 0.35) !important;
}

body.cn-body-dark .sala-host-label,
body.cn-body-dark .cycles-counter,
body.cn-body-dark .clock-sub-status,
body.cn-body-dark .sync-caption,
body.cn-body-dark .meet-info-text,
body.cn-body-dark .participante-email,
body.cn-body-dark .sala-task-date,
body.cn-body-dark .empty-tasks-Note {
  color: #fcd5b8 !important;
}

body.cn-body-dark .inactivity-pill {
  background-color: #3b1717 !important;
  border-color: #7b413f !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .pomodoro-phase-chip {
  background-color: #612c2d !important;
  color: #f4be95 !important;
}

body.cn-body-dark .cycle-step-pill {
  background-color: #3b1717 !important;
}

body.cn-body-dark .cycle-step-pill.completed {
  background-color: #a55e57 !important;
}

body.cn-body-dark .cycle-step-pill.current {
  background-color: #f4be95 !important;
}

body.cn-body-dark .timer-ring-track {
  stroke: #3b1717 !important;
}

body.cn-body-dark .timer-ring-progress {
  stroke: #f4be95 !important;
}

body.cn-body-dark .clock-giant {
  color: #ffffff !important;
}

body.cn-body-dark .active-focus-banner,
body.cn-body-dark .config-inline-box,
body.cn-body-dark .meet-active-url-box,
body.cn-body-dark .mi-estado-selector,
body.cn-body-dark .participante-row,
body.cn-body-dark .sala-task-item {
  background-color: #3b1717 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .sala-task-item.selected {
  background-color: #612c2d !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .focus-label,
body.cn-body-dark .meet-url-label,
body.cn-body-dark .mi-estado-label,
body.cn-body-dark .config-grid label,
body.cn-body-dark .meet-edit-label {
  color: #fcd5b8 !important;
}

body.cn-body-dark .focus-task-name,
body.cn-body-dark .config-box-title,
body.cn-body-dark .meet-url-code,
body.cn-body-dark .participante-nombre,
body.cn-body-dark .sala-task-title {
  color: #ffffff !important;
}

body.cn-body-dark .btn-ctrl-primary,
body.cn-body-dark .btn-join-meet,
body.cn-body-dark .btn-save-meet,
body.cn-body-dark .btn-pip-room {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .btn-ctrl-secondary,
body.cn-body-dark .btn-salir-sala,
body.cn-body-dark .btn-copy-meet,
body.cn-body-dark .inline-preset-btn,
body.cn-body-dark .estado-pill-btn {
  background-color: #612c2d !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .estado-pill-btn.active {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .btn-finalizar-sala {
  background-color: rgba(220, 38, 38, 0.2) !important;
  color: #fca5a5 !important;
  border-color: rgba(248, 113, 113, 0.4) !important;
}

body.cn-body-dark .participante-avatar {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
}

body.cn-body-dark .host-tag {
  background-color: #612c2d !important;
  color: #f4be95 !important;
}

body.cn-body-dark .status-pomodoro {
  background-color: #612c2d !important;
  color: #f4be95 !important;
}

body.cn-body-dark .status-online {
  background-color: rgba(16, 185, 129, 0.16) !important;
  color: #6ee7b7 !important;
}

body.cn-body-dark .status-meet {
  background-color: rgba(59, 130, 246, 0.2) !important;
  color: #93c5fd !important;
}

body.cn-body-dark .btn-link-edit {
  color: #f4be95 !important;
}
</style>
