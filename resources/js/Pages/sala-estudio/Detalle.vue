<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
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
  miEstadoInicial: {
    type: String,
    default: 'EnLinea'
  },
  puedeControlarSala: {
    type: Boolean,
    default: true
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

// Diferencia entre el reloj del servidor y el navegador del usuario para sincronización exacta al segundo
const clockOffset = ref(props.serverTime - Math.floor(Date.now() / 1000));
const displaySeconds = ref(props.pomodoroInicial.segundosRestantes ?? 1500);

const editandoMeet = ref(salaState.googleMeetUrl === 'https://meet.google.com/new');
const nuevoEnlaceMeet = ref(
  salaState.googleMeetUrl !== 'https://meet.google.com/new' ? salaState.googleMeetUrl : ''
);
const errorMeet = ref('');
const guardandoMeet = ref(false);
const copiadoExito = ref(false);

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

const sincronizarConMiniTimerGlobal = () => {
  globalIsRunning.value = !!pomodoro.corriendo;
  globalPhase.value = pomodoro.fase || 'work';
  globalCycle.value = pomodoro.cicloActual || 1;
  globalTimeLeft.value = displaySeconds.value;
  globalTotalSeconds.value = pomodoro.segundosTotalesFase || 1500;
  globalSesionActiva.value = {
    idSesion: `sala_${salaState.idSalaEstudio}`,
    tituloTarea: `Sala: ${salaState.tituloEvento}`,
    duracionSesion: pomodoro.duracionTrabajo,
    duracionDescansoCorto: pomodoro.duracionDescansoCorto,
    duracionDescansoLargo: pomodoro.duracionDescansoLargo,
    sesionesPrevioDescansoLargo: pomodoro.ciclosObjetivo
  };
};

const actualizarRelojLocal = () => {
  if (pomodoro.corriendo && pomodoro.finFaseTimestamp) {
    const ahoraServidorEstimado = Math.floor(Date.now() / 1000) + clockOffset.value;
    const restantes = Math.max(0, pomodoro.finFaseTimestamp - ahoraServidorEstimado);

    if (displaySeconds.value > 0 && restantes === 0) {
      // Llegó a 00:00 -> Disparar alerta sonora instantánea
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
  if (nuevoEstadoManual) {
    miEstado.value = nuevoEstadoManual;
  }

  try {
    const { data } = await window.axios.post(route('salas.estado', salaState.idSalaEstudio), {
      estadoUsuario: miEstado.value
    });

    if (data.finalizada) {
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

const finalizarSala = () => {
  if (!confirm('¿Estás seguro de que querés finalizar esta sala de estudio para todos los participantes?')) {
    return;
  }
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

const etiquetaFase = computed(() => {
  if (pomodoro.fase === 'work') return 'TIEMPO DE ENFOQUE GRUPAL';
  if (pomodoro.fase === 'shortBreak') return 'DESCANSO CORTO GRUPAL';
  return 'DESCANSO LARGO GRUPAL';
});

const badgeEstadoInfo = (estado) => {
  if (estado === 'EnPomodoro') return { texto: 'En Pomodoro', clase: 'status-pomodoro', tipo: 'pomodoro' };
  if (estado === 'EnMeet') return { texto: 'En Google Meet', clase: 'status-meet', tipo: 'meet' };
  return { texto: 'En Línea', clase: 'status-online', tipo: 'online' };
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
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="badge-perfil-top">{{ salaState.perfilTitulo }}</span>
            <span class="badge-live-room">
              <span class="live-dot-indicator"></span>
              SALA EN VIVO
            </span>
          </div>
          <h1 class="sala-main-title">{{ salaState.tituloEvento }}</h1>
          <span class="sala-host-label">Anfitrión: <strong>{{ salaState.creadorNombre }}</strong></span>
        </div>

        <div class="sala-topbar-actions">
          <button
            type="button"
            class="btn-top-action btn-pip-room"
            @click="requestPipWindow"
            :title="isPipActive ? 'Cerrar Mini-Timer Flotante' : 'Desacoplar reloj en ventana flotante Always-on-Top para ver durante el Meet'"
          >
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <rect x="12" y="12" width="7" height="6" rx="1" fill="currentColor" />
            </svg>
            <span>{{ isPipActive ? 'Cerrar Mini-Timer' : 'Mini-Timer (PiP)' }}</span>
          </button>

          <button type="button" class="btn-top-action btn-salir-sala" @click="salirDeSala">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
              <polyline points="16 17 21 12 16 7" />
              <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            <span>Salir</span>
          </button>

          <button
            v-if="puedeControlarSala"
            type="button"
            class="btn-top-action btn-finalizar-sala"
            @click="finalizarSala"
          >
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <line x1="9" y1="9" x2="15" y2="15" />
              <line x1="15" y1="9" x2="9" y2="15" />
            </svg>
            <span>Finalizar Sala</span>
          </button>
        </div>
      </div>

      <div class="sala-main-grid">
        <!-- Columna Izquierda: Temporizador Pomodoro Grupal Sincronizado -->
        <div class="pomodoro-grupal-card card">
          <div class="pomodoro-phase-chip" :class="pomodoro.fase">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="13" r="8" />
              <path d="M12 9v4l2 2" />
              <path d="M9 3h6" />
            </svg>
            <span>{{ etiquetaFase }}</span>
          </div>

          <!-- Indicador de Ciclos con dots estilizados -->
          <div class="cycles-row">
            <span class="cycles-counter">Ciclo {{ pomodoro.cicloActual }} de {{ pomodoro.ciclosObjetivo }}</span>
            <div class="cycles-dots-list" :title="`Ciclo ${pomodoro.cicloActual} de ${pomodoro.ciclosObjetivo}`">
              <span
                v-for="idx in pomodoro.ciclosObjetivo"
                :key="idx"
                class="cycle-dot"
                :class="{
                  completed: idx < pomodoro.cicloActual,
                  current: idx === pomodoro.cicloActual
                }"
              ></span>
            </div>
          </div>

          <!-- Reloj Gigante Sincronizado -->
          <div class="clock-giant">
            {{ formattedTime }}
          </div>

          <!-- Barra de Progreso del Ciclo -->
          <div class="progress-track">
            <div class="progress-bar-fill" :style="{ width: porcentajeProgreso + '%' }"></div>
          </div>

          <p class="sync-caption">
            Sincronizado en tiempo real con todos los miembros de la sala
            <span v-if="pomodoro.actualizadoPor"> · Última acción: {{ pomodoro.actualizadoPor }}</span>
          </p>

          <!-- Controles del Pomodoro Grupal -->
          <div class="group-controls">
            <button
              v-if="!pomodoro.corriendo"
              type="button"
              class="btn-ctrl-primary"
              @click="ejecutarAccionPomodoro('iniciar')"
            >
              <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none">
                <polygon points="6 4 20 12 6 20 6 4" />
              </svg>
              <span>Iniciar Pomodoro Grupal</span>
            </button>
            <button
              v-else
              type="button"
              class="btn-ctrl-pause"
              @click="ejecutarAccionPomodoro('pausar')"
            >
              <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" stroke="none">
                <rect x="6" y="4" width="4" height="16" rx="1" />
                <rect x="14" y="4" width="4" height="16" rx="1" />
              </svg>
              <span>Pausar Reloj Grupal</span>
            </button>

            <button
              type="button"
              class="btn-ctrl-secondary"
              @click="ejecutarAccionPomodoro('saltar')"
              title="Avanzar a la siguiente fase (Descanso / Trabajo)"
            >
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="5 4 15 12 5 20 5 4" />
                <line x1="19" y1="5" x2="19" y2="19" />
              </svg>
              <span>Saltar Fase</span>
            </button>

            <button
              type="button"
              class="btn-ctrl-secondary"
              @click="ejecutarAccionPomodoro('reiniciar')"
              title="Reiniciar el tiempo de la fase actual"
            >
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                <path d="M3 3v5h5" />
              </svg>
              <span>Reiniciar</span>
            </button>

            <button
              type="button"
              class="btn-ctrl-secondary"
              :class="{ 'active-toggle': showConfigPomodoro }"
              @click="showConfigPomodoro = !showConfigPomodoro"
              title="Configurar duraciones del Pomodoro grupal"
            >
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3" />
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
              </svg>
              <span>Duración</span>
            </button>
          </div>

          <!-- Configuración inline de tiempos del Pomodoro grupal (Grid de 2 columnas balanceado) -->
          <div v-if="showConfigPomodoro" class="config-inline-box">
            <div class="config-inline-header">
              <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="13" r="8" />
                <path d="M12 9v4l2 2" />
                <path d="M9 3h6" />
              </svg>
              <span>Ajustar tiempos del temporizador grupal</span>
            </div>
            <div class="config-grid">
              <div class="config-field">
                <label>Trabajo (min)</label>
                <input type="number" v-model.number="configForm.duracionTrabajo" min="1" max="120" class="input-sm-custom" />
              </div>
              <div class="config-field">
                <label>Descanso Corto (min)</label>
                <input type="number" v-model.number="configForm.duracionDescansoCorto" min="1" max="30" class="input-sm-custom" />
              </div>
              <div class="config-field">
                <label>Descanso Largo (min)</label>
                <input type="number" v-model.number="configForm.duracionDescansoLargo" min="5" max="60" class="input-sm-custom" />
              </div>
              <div class="config-field">
                <label>Ciclos</label>
                <input type="number" v-model.number="configForm.ciclosObjetivo" min="1" max="10" class="input-sm-custom" />
              </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
              <button type="button" class="btn-ctrl-secondary btn-sm-cfg" @click="showConfigPomodoro = false">Cancelar</button>
              <button
                type="button"
                class="btn-ctrl-primary btn-sm-cfg"
                @click="ejecutarAccionPomodoro('configurar', configForm)"
              >
                Guardar Tiempos
              </button>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Google Meet + Compañeros en Vivo -->
        <div class="sala-side-column">
          <!-- Tarjeta Google Meet -->
          <div class="meet-card card">
            <div class="side-card-header">
              <div class="side-card-title-wrap">
                <span class="side-card-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="23 7 16 12 23 17 23 7" />
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2" />
                  </svg>
                </span>
                <h3 class="side-card-title">Videollamada de Equipo (Google Meet)</h3>
              </div>
              <button
                type="button"
                class="btn-link-edit"
                @click="editandoMeet = !editandoMeet"
              >
                {{ editandoMeet ? 'Cerrar edición' : 'Cambiar link' }}
              </button>
            </div>

            <p v-if="salaState.googleMeetUrl === 'https://meet.google.com/new'" class="meet-info-text">
              Hacé clic en <strong>Crear / Abrir Google Meet</strong> para iniciar la videollamada y pegá abajo el código o link de la sala para que tus compañeros entren directo al mismo Meet.
            </p>
            <p v-else class="meet-info-text">
              Enlace activo: <code class="meet-code-pill">{{ salaState.googleMeetUrl }}</code>
            </p>

            <div class="d-flex gap-2 flex-wrap mb-1">
              <button type="button" class="btn-join-meet" @click="abrirGoogleMeet">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="23 7 16 12 23 17 23 7" />
                  <rect x="1" y="5" width="15" height="14" rx="2" ry="2" />
                </svg>
                <span>
                  {{ salaState.googleMeetUrl === 'https://meet.google.com/new' ? 'Crear / Abrir Google Meet' : 'Unirse a Google Meet' }}
                </span>
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="7" y1="17" x2="17" y2="7" />
                  <polyline points="7 7 17 7 17 17" />
                </svg>
              </button>

              <button
                v-if="salaState.googleMeetUrl !== 'https://meet.google.com/new'"
                type="button"
                class="btn-copy-meet"
                @click="copiarEnlaceMeet"
              >
                <svg v-if="copiadoExito" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12" />
                </svg>
                <svg v-else viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                  <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                </svg>
                <span>{{ copiadoExito ? 'Copiado' : 'Copiar Link' }}</span>
              </button>
            </div>

            <!-- Editor rápido para pegar el link de Meet compartido -->
            <div v-if="editandoMeet" class="meet-edit-box">
              <label class="meet-edit-label">Pegá el enlace o código de la reunión (ej: <code>abc-defg-hij</code>):</label>
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
                  {{ guardandoMeet ? '...' : 'Guardar' }}
                </button>
              </div>
              <div v-if="errorMeet" class="text-danger mt-1" style="font-size: 0.78rem;">
                {{ errorMeet }}
              </div>
            </div>
          </div>

          <!-- Tarjeta de Presencia en Tiempo Real (Body Doubling) -->
          <div class="presencia-card card">
            <div class="side-card-header">
              <div class="side-card-title-wrap">
                <span class="side-card-icon">
                  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                </span>
                <h3 class="side-card-title">
                  Compañeros en la Sala ({{ participantes.length }})
                </h3>
              </div>
              <span class="live-sync-badge">
                <span class="live-dot-indicator"></span>
                En vivo
              </span>
            </div>

            <!-- Selector rápido del propio estado -->
            <div class="mi-estado-selector">
              <span class="mi-estado-label">Tu estado actual:</span>
              <div class="estado-pills">
                <button
                  type="button"
                  class="estado-pill-btn"
                  :class="{ active: miEstado === 'EnLinea' }"
                  @click="sincronizarEstadoConServidor('EnLinea')"
                >
                  <span class="pill-dot online"></span>
                  <span>En Línea</span>
                </button>
                <button
                  type="button"
                  class="estado-pill-btn"
                  :class="{ active: miEstado === 'EnPomodoro' }"
                  @click="sincronizarEstadoConServidor('EnPomodoro')"
                >
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="13" r="8" />
                    <path d="M12 9v4l2 2" />
                    <path d="M9 3h6" />
                  </svg>
                  <span>En Pomodoro</span>
                </button>
                <button
                  type="button"
                  class="estado-pill-btn"
                  :class="{ active: miEstado === 'EnMeet' }"
                  @click="sincronizarEstadoConServidor('EnMeet')"
                >
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="23 7 16 12 23 17 23 7" />
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2" />
                  </svg>
                  <span>En Meet</span>
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
                    {{ p.nombreCompleto }}
                    <span v-if="p.idUsuario === salaState.idUsuarioCreador" class="host-tag">Anfitrión</span>
                  </div>
                  <div class="participante-email">{{ p.email }}</div>
                </div>
                <span class="participante-status-badge" :class="badgeEstadoInfo(p.estadoUsuario).clase">
                  <svg
                    v-if="badgeEstadoInfo(p.estadoUsuario).tipo === 'pomodoro'"
                    viewBox="0 0 24 24"
                    width="12"
                    height="12"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.3"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <circle cx="12" cy="13" r="8" />
                    <path d="M12 9v4l2 2" />
                    <path d="M9 3h6" />
                  </svg>
                  <svg
                    v-else-if="badgeEstadoInfo(p.estadoUsuario).tipo === 'meet'"
                    viewBox="0 0 24 24"
                    width="12"
                    height="12"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.3"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  >
                    <polygon points="23 7 16 12 23 17 23 7" />
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2" />
                  </svg>
                  <span v-else class="status-online-dot"></span>
                  <span>{{ badgeEstadoInfo(p.estadoUsuario).texto }}</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.sala-detalle-container {
  max-width: 1200px;
  margin: 0 auto;
  padding-bottom: 48px;
}

/* Barra superior de la Sala */
.sala-topbar {
  background: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 16px;
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 18px;
  margin-bottom: 26px;
  box-shadow: 0 2px 10px rgba(74, 35, 40, 0.04);
}

.badge-perfil-top {
  background: #f5ebe8;
  color: #4A2328;
  font-weight: 700;
  font-size: 0.75rem;
  padding: 4px 11px;
  border-radius: 999px;
  border: 1px solid #e6d3cd;
}

.badge-live-room {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
  font-weight: 800;
  font-size: 0.72rem;
  padding: 4px 10px;
  border-radius: 999px;
  letter-spacing: 0.5px;
}

.live-dot-indicator {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
  display: inline-block;
  flex-shrink: 0;
}

.sala-main-title {
  font-size: 1.45rem;
  font-weight: 800;
  color: #4A2328;
  margin: 4px 0 2px 0;
  letter-spacing: -0.01em;
}

.sala-host-label {
  font-size: 0.84rem;
  color: #6b7280;
}

/* Botones superiores discretos y uniformes */
.sala-topbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.btn-top-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  height: 38px;
  padding: 0 14px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.83rem;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-pip-room {
  background: #fdfaf8;
  color: #4A2328;
  border: 1px solid #d5beb7;
}

.btn-pip-room:hover {
  background: #f5ebe8;
  border-color: #4A2328;
}

.btn-salir-sala {
  background: #f9fafb;
  color: #4b5563;
  border: 1px solid #d1d5db;
}

.btn-salir-sala:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.btn-finalizar-sala {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

.btn-finalizar-sala:hover {
  background: #fee2e2;
  border-color: #fca5a5;
}

/* Grid Principal con espaciado holgado */
.sala-main-grid {
  display: grid;
  grid-template-columns: 1.25fr 1fr;
  gap: 26px;
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
  padding: 34px 28px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  box-shadow: 0 4px 16px rgba(74, 35, 40, 0.05);
}

.pomodoro-phase-chip {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 1.2px;
  padding: 6px 16px;
  border-radius: 999px;
  background: #f5ebe8;
  color: #4A2328;
  border: 1px solid #e6d3cd;
  margin-bottom: 16px;
}

.pomodoro-phase-chip.shortBreak,
.pomodoro-phase-chip.longBreak {
  background: #ecfdf5;
  color: #047857;
  border-color: #a7f3d0;
}

/* Indicador de Ciclos con Dots estilizados */
.cycles-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.cycles-counter {
  font-size: 0.86rem;
  font-weight: 600;
  color: #6b7280;
}

.cycles-dots-list {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.cycle-dot {
  width: 11px;
  height: 11px;
  border-radius: 50%;
  background: transparent;
  border: 2px solid #d1d5db;
  transition: all 0.25s ease;
}

.cycle-dot.completed {
  background: #4A2328;
  border-color: #4A2328;
}

.cycle-dot.current {
  width: 24px;
  border-radius: 999px;
  background: #612c2d;
  border-color: #612c2d;
  box-shadow: 0 0 0 3px rgba(97, 44, 45, 0.16);
}

.clock-giant {
  font-size: 5.4rem;
  font-family: 'Inter', 'Segoe UI', Roboto, monospace;
  font-variant-numeric: tabular-nums;
  font-weight: 800;
  color: #4A2328;
  line-height: 1.05;
  margin: 14px 0 18px 0;
  letter-spacing: -0.03em;
}

.progress-track {
  width: 100%;
  max-width: 440px;
  height: 8px;
  background: #f3ecea;
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 14px;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #4A2328 0%, #7b413f 100%);
  border-radius: 999px;
  transition: width 0.5s ease;
}

.sync-caption {
  font-size: 0.8rem;
  color: #6b7280;
  margin-bottom: 24px;
}

.group-controls {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
}

.btn-ctrl-primary {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #4A2328;
  color: #ffffff;
  border: 1px solid #4A2328;
  border-radius: 10px;
  padding: 11px 22px;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 3px 10px rgba(74, 35, 40, 0.18);
}

.btn-ctrl-primary:hover {
  background: #612c2d;
  border-color: #612c2d;
}

.btn-ctrl-pause {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #b45309;
  color: #ffffff;
  border: 1px solid #b45309;
  border-radius: 10px;
  padding: 11px 22px;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-ctrl-pause:hover {
  background: #92400e;
}

.btn-ctrl-secondary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #fdfaf8;
  color: #4A2328;
  border: 1px solid #dfd1cc;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-ctrl-secondary:hover,
.btn-ctrl-secondary.active-toggle {
  background: #f5ebe8;
  border-color: #c5ada5;
}

.btn-sm-cfg {
  padding: 7px 14px;
  font-size: 0.83rem;
}

/* Configuración inline en Grid de 2 columnas */
.config-inline-box {
  width: 100%;
  max-width: 460px;
  margin-top: 22px;
  padding: 18px;
  border-radius: 12px;
  background: #fdfaf8;
  border: 1px solid #eadcd7;
  text-align: left;
}

.config-inline-header {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 0.82rem;
  font-weight: 700;
  color: #4A2328;
  margin-bottom: 12px;
}

.config-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px 14px;
}

@media (max-width: 480px) {
  .config-grid {
    grid-template-columns: 1fr;
  }
}

.config-field label {
  display: block;
  font-size: 0.76rem;
  font-weight: 700;
  color: #5b3b3a;
  margin-bottom: 5px;
}

.input-sm-custom {
  width: 100%;
  padding: 8px 11px;
  border-radius: 8px;
  border: 1px solid #d5c5c0;
  font-size: 0.86rem;
  font-weight: 600;
  color: #1f2937;
  background: #ffffff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.input-sm-custom:focus {
  outline: none;
  border-color: #612c2d;
  box-shadow: 0 0 0 3px rgba(97, 44, 45, 0.12);
}

/* Columna Derecha */
.sala-side-column {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.meet-card,
.presencia-card {
  background: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 16px;
  padding: 22px 24px;
  box-shadow: 0 4px 16px rgba(74, 35, 40, 0.04);
}

.side-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}

.side-card-title-wrap {
  display: flex;
  align-items: center;
  gap: 9px;
}

.side-card-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f5ebe8;
  color: #4A2328;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.side-card-title {
  font-size: 1rem;
  font-weight: 700;
  color: #4A2328;
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
  padding: 2px 4px;
}

.btn-link-edit:hover {
  color: #4A2328;
}

.meet-info-text {
  font-size: 0.84rem;
  color: #4b5563;
  line-height: 1.45;
  margin-bottom: 14px;
  word-break: break-word;
}

.meet-code-pill {
  background: #f5ebe8;
  color: #4A2328;
  padding: 2px 7px;
  border-radius: 6px;
  font-size: 0.8rem;
}

/* Botón Google Meet alineado a la paleta institucional Cronos (#4A2328) */
.btn-join-meet {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #4A2328;
  color: #ffffff;
  border: 1px solid #4A2328;
  border-radius: 10px;
  padding: 10px 16px;
  font-weight: 700;
  font-size: 0.88rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-join-meet:hover {
  background: #612c2d;
  border-color: #612c2d;
}

.btn-copy-meet {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #fdfaf8;
  color: #4A2328;
  border: 1px solid #d5beb7;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 600;
  font-size: 0.84rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-copy-meet:hover {
  background: #f5ebe8;
}

.meet-edit-box {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px dashed #e5d6d1;
}

.meet-edit-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: #5b3b3a;
  margin-bottom: 6px;
}

.btn-save-meet {
  background: #4A2328;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  padding: 8px 15px;
  font-weight: 600;
  font-size: 0.83rem;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-save-meet:hover {
  background: #612c2d;
}

/* Presencia / Compañeros */
.live-sync-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.73rem;
  font-weight: 700;
  color: #047857;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  padding: 3px 10px;
  border-radius: 999px;
}

.mi-estado-selector {
  background: #fdfaf8;
  border: 1px solid #eadcd7;
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 16px;
}

.mi-estado-label {
  display: block;
  font-size: 0.76rem;
  font-weight: 700;
  color: #5b3b3a;
  margin-bottom: 8px;
}

.estado-pills {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.estado-pill-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: #ffffff;
  color: #4b5563;
  border: 1px solid #d5c5c0;
  border-radius: 8px;
  padding: 7px 10px;
  font-size: 0.77rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.18s ease;
}

.estado-pill-btn:hover {
  border-color: #7b413f;
  color: #4A2328;
}

.estado-pill-btn.active {
  background: #4A2328;
  color: #ffffff;
  border-color: #4A2328;
}

.pill-dot.online {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
  display: inline-block;
}

.estado-pill-btn.active .pill-dot.online {
  background: #6ee7b7;
}

.participantes-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.participante-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 11px;
  background: #fdfaf8;
  border: 1px solid #efe4e0;
}

.participante-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #4A2328;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.88rem;
  flex-shrink: 0;
}

.participante-info {
  flex: 1;
  min-width: 0;
}

.participante-nombre {
  font-size: 0.88rem;
  font-weight: 700;
  color: #2b1819;
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
}

.host-tag {
  font-size: 0.68rem;
  font-weight: 700;
  background: #f5ebe8;
  color: #612c2d;
  border: 1px solid #e2cdc6;
  padding: 1px 7px;
  border-radius: 999px;
}

.participante-email {
  font-size: 0.75rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.participante-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.73rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  white-space: nowrap;
}

.status-online-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  display: inline-block;
}

.status-online {
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
}

.status-pomodoro {
  background: #f5ebe8;
  color: #612c2d;
  border: 1px solid #e2cdc6;
}

.status-meet {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}
</style>

<!-- Estilos de Modo Oscuro no-scoped para garantizar especificidad sobre el tema global -->
<style>
body.cn-body-dark .sala-topbar,
body.cn-body-dark .pomodoro-grupal-card,
body.cn-body-dark .meet-card,
body.cn-body-dark .presencia-card {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.28) !important;
}

body.cn-body-dark .sala-main-title,
body.cn-body-dark .side-card-title,
body.cn-body-dark .clock-giant {
  color: #ffffff !important;
}

body.cn-body-dark .sala-host-label,
body.cn-body-dark .cycles-counter,
body.cn-body-dark .sync-caption,
body.cn-body-dark .meet-info-text,
body.cn-body-dark .participante-email {
  color: #e2b99b !important;
}

body.cn-body-dark .badge-perfil-top,
body.cn-body-dark .side-card-icon,
body.cn-body-dark .meet-code-pill,
body.cn-body-dark .host-tag {
  background-color: #3b1919 !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .badge-live-room,
body.cn-body-dark .live-sync-badge,
body.cn-body-dark .status-online {
  background-color: rgba(16, 185, 129, 0.16) !important;
  color: #6ee7b7 !important;
  border-color: rgba(16, 185, 129, 0.35) !important;
}

body.cn-body-dark .pomodoro-phase-chip {
  background-color: #3b1919 !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .pomodoro-phase-chip.shortBreak,
body.cn-body-dark .pomodoro-phase-chip.longBreak {
  background-color: rgba(16, 185, 129, 0.16) !important;
  color: #6ee7b7 !important;
  border-color: rgba(16, 185, 129, 0.35) !important;
}

body.cn-body-dark .cycle-dot {
  border-color: #8c5350 !important;
  background-color: transparent !important;
}

body.cn-body-dark .cycle-dot.completed {
  background-color: #f4be95 !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .cycle-dot.current {
  background-color: #fcd5b8 !important;
  border-color: #fcd5b8 !important;
  box-shadow: 0 0 0 3px rgba(244, 190, 149, 0.25) !important;
}

body.cn-body-dark .progress-track {
  background-color: #3b1919 !important;
}

body.cn-body-dark .progress-bar-fill {
  background: linear-gradient(90deg, #f4be95 0%, #fcd5b8 100%) !important;
}

body.cn-body-dark .btn-ctrl-primary,
body.cn-body-dark .btn-join-meet,
body.cn-body-dark .btn-save-meet {
  background-color: #f4be95 !important;
  color: #3b1919 !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .btn-ctrl-primary:hover,
body.cn-body-dark .btn-join-meet:hover,
body.cn-body-dark .btn-save-meet:hover {
  background-color: #fcd5b8 !important;
  border-color: #fcd5b8 !important;
}

body.cn-body-dark .btn-ctrl-secondary,
body.cn-body-dark .btn-pip-room,
body.cn-body-dark .btn-salir-sala,
body.cn-body-dark .btn-copy-meet {
  background-color: #3b1919 !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .btn-ctrl-secondary:hover,
body.cn-body-dark .btn-pip-room:hover,
body.cn-body-dark .btn-salir-sala:hover,
body.cn-body-dark .btn-copy-meet:hover {
  background-color: #612c2d !important;
  color: #ffffff !important;
}

body.cn-body-dark .btn-finalizar-sala {
  background-color: rgba(239, 68, 68, 0.16) !important;
  color: #fca5a5 !important;
  border-color: rgba(239, 68, 68, 0.4) !important;
}

body.cn-body-dark .btn-finalizar-sala:hover {
  background-color: rgba(239, 68, 68, 0.28) !important;
}

body.cn-body-dark .config-inline-box,
body.cn-body-dark .mi-estado-selector,
body.cn-body-dark .participante-row {
  background-color: #3b1919 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .config-inline-header,
body.cn-body-dark .config-field label,
body.cn-body-dark .mi-estado-label,
body.cn-body-dark .meet-edit-label,
body.cn-body-dark .participante-nombre,
body.cn-body-dark .btn-link-edit {
  color: #fcd5b8 !important;
}

body.cn-body-dark .meet-edit-box {
  border-top-color: #7b413f !important;
}

body.cn-body-dark .input-sm-custom {
  background-color: #2f1212 !important;
  color: #ffffff !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .input-sm-custom::placeholder {
  color: #b58d7d !important;
}

body.cn-body-dark .estado-pill-btn {
  background-color: #2f1212 !important;
  color: #e2b99b !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .estado-pill-btn.active {
  background-color: #f4be95 !important;
  color: #3b1919 !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .participante-avatar {
  background-color: #f4be95 !important;
  color: #3b1919 !important;
}

body.cn-body-dark .status-pomodoro {
  background-color: rgba(244, 190, 149, 0.16) !important;
  color: #fcd5b8 !important;
  border-color: rgba(244, 190, 149, 0.35) !important;
}

body.cn-body-dark .status-meet {
  background-color: rgba(251, 191, 36, 0.16) !important;
  color: #fde68a !important;
  border-color: rgba(251, 191, 36, 0.35) !important;
}
</style>
