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
  if (estado === 'EnPomodoro') return { texto: '🍅 En Pomodoro', clase: 'status-pomodoro' };
  if (estado === 'EnMeet') return { texto: '📹 En Google Meet', clase: 'status-meet' };
  return { texto: '🟢 En Línea', clase: 'status-online' };
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
            <span class="badge-live-room">● SALA EN VIVO</span>
          </div>
          <h1 class="sala-main-title">{{ salaState.tituloEvento }}</h1>
          <span class="sala-host-label">Anfitrión: <strong>{{ salaState.creadorNombre }}</strong></span>
        </div>

        <div class="sala-topbar-actions">
          <button
            type="button"
            class="btn-pip-room"
            @click="requestPipWindow"
            :title="isPipActive ? 'Cerrar Mini-Timer Flotante' : 'Desacoplar reloj en ventana flotante Always-on-Top para ver durante el Meet'"
          >
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <rect x="12" y="12" width="7" height="6" rx="1" fill="currentColor" />
            </svg>
            {{ isPipActive ? 'Cerrar Mini-Timer PiP' : 'Mini-Timer Flotante (PiP)' }}
          </button>

          <button type="button" class="btn-salir-sala" @click="salirDeSala">
            Salir de la Sala
          </button>

          <button
            v-if="puedeControlarSala"
            type="button"
            class="btn-finalizar-sala"
            @click="finalizarSala"
          >
            Finalizar Sala
          </button>
        </div>
      </div>

      <div class="sala-main-grid">
        <!-- Columna Izquierda: Temporizador Pomodoro Grupal Sincronizado -->
        <div class="pomodoro-grupal-card card">
          <div class="pomodoro-phase-chip" :class="pomodoro.fase">
            {{ etiquetaFase }}
          </div>

          <!-- Indicador de Ciclos con tomates -->
          <div class="cycles-row">
            <span class="cycles-counter">Ciclo {{ pomodoro.cicloActual }} de {{ pomodoro.ciclosObjetivo }}</span>
            <div class="tomatoes-list">
              <span
                v-for="idx in pomodoro.ciclosObjetivo"
                :key="idx"
                class="tomato-dot"
                :class="{
                  completed: idx < pomodoro.cicloActual,
                  current: idx === pomodoro.cicloActual
                }"
              >
                🍅
              </span>
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
              ▶ Iniciar Pomodoro Grupal
            </button>
            <button
              v-else
              type="button"
              class="btn-ctrl-pause"
              @click="ejecutarAccionPomodoro('pausar')"
            >
              ⏸ Pausar Reloj Grupal
            </button>

            <button
              type="button"
              class="btn-ctrl-secondary"
              @click="ejecutarAccionPomodoro('saltar')"
              title="Avanzar a la siguiente fase (Descanso / Trabajo)"
            >
              ⏭ Saltar Fase
            </button>

            <button
              type="button"
              class="btn-ctrl-secondary"
              @click="ejecutarAccionPomodoro('reiniciar')"
              title="Reiniciar el tiempo de la fase actual"
            >
              ↺ Reiniciar
            </button>

            <button
              type="button"
              class="btn-ctrl-secondary"
              @click="showConfigPomodoro = !showConfigPomodoro"
              title="Configurar duraciones del Pomodoro grupal"
            >
              ⚙ Duración
            </button>
          </div>

          <!-- Configuración inline de tiempos del Pomodoro grupal -->
          <div v-if="showConfigPomodoro" class="config-inline-box">
            <div class="config-grid">
              <div>
                <label>Trabajo (min)</label>
                <input type="number" v-model.number="configForm.duracionTrabajo" min="1" max="120" class="input-sm-custom" />
              </div>
              <div>
                <label>Desc. Corto (min)</label>
                <input type="number" v-model.number="configForm.duracionDescansoCorto" min="1" max="30" class="input-sm-custom" />
              </div>
              <div>
                <label>Desc. Largo (min)</label>
                <input type="number" v-model.number="configForm.duracionDescansoLargo" min="5" max="60" class="input-sm-custom" />
              </div>
              <div>
                <label>Ciclos</label>
                <input type="number" v-model.number="configForm.ciclosObjetivo" min="1" max="10" class="input-sm-custom" />
              </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-2">
              <button type="button" class="btn-ctrl-secondary" @click="showConfigPomodoro = false">Cancelar</button>
              <button
                type="button"
                class="btn-ctrl-primary"
                style="padding: 6px 14px; font-size: 0.84rem;"
                @click="ejecutarAccionPomodoro('configurar', configForm)"
              >
                Guardar Tiempos Grupales
              </button>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Google Meet + Compañeros en Vivo -->
        <div class="sala-side-column">
          <!-- Tarjeta Google Meet -->
          <div class="meet-card card">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h3 class="side-card-title">📹 Videollamada de Equipo (Google Meet)</h3>
              <button
                type="button"
                class="btn-link-edit"
                @click="editandoMeet = !editandoMeet"
              >
                {{ editandoMeet ? 'Cerrar edición' : 'Cambiar link' }}
              </button>
            </div>

            <p v-if="salaState.googleMeetUrl === 'https://meet.google.com/new'" class="meet-info-text">
              Hacé clic en <strong>Abrir Google Meet</strong> para iniciar la videollamada y pegá abajo el código o link de la sala para que tus compañeros entren directo al mismo Meet.
            </p>
            <p v-else class="meet-info-text">
              Enlace activo: <code>{{ salaState.googleMeetUrl }}</code>
            </p>

            <div class="d-flex gap-2 flex-wrap mb-2">
              <button type="button" class="btn-join-meet" @click="abrirGoogleMeet">
                {{ salaState.googleMeetUrl === 'https://meet.google.com/new' ? 'Crear / Abrir Google Meet' : 'Unirse a Google Meet' }}
                ↗
              </button>

              <button
                v-if="salaState.googleMeetUrl !== 'https://meet.google.com/new'"
                type="button"
                class="btn-copy-meet"
                @click="copiarEnlaceMeet"
              >
                {{ copiadoExito ? '✓ ¡Copiado!' : 'Copiar Link' }}
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
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h3 class="side-card-title">
                👥 Compañeros en la Sala ({{ participantes.length }})
              </h3>
              <span class="live-sync-badge">En vivo</span>
            </div>

            <!-- Selector rápido del propio estado -->
            <div class="mi-estado-selector mb-3">
              <span class="mi-estado-label">Tu estado actual:</span>
              <div class="estado-pills">
                <button
                  type="button"
                  class="estado-pill-btn"
                  :class="{ active: miEstado === 'EnLinea' }"
                  @click="sincronizarEstadoConServidor('EnLinea')"
                >
                  🟢 En Línea
                </button>
                <button
                  type="button"
                  class="estado-pill-btn"
                  :class="{ active: miEstado === 'EnPomodoro' }"
                  @click="sincronizarEstadoConServidor('EnPomodoro')"
                >
                  🍅 En Pomodoro
                </button>
                <button
                  type="button"
                  class="estado-pill-btn"
                  :class="{ active: miEstado === 'EnMeet' }"
                  @click="sincronizarEstadoConServidor('EnMeet')"
                >
                  📹 En Meet
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
                  {{ badgeEstadoInfo(p.estadoUsuario).texto }}
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
  padding-bottom: 40px;
}

.sala-topbar {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 18px 22px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 22px;
}

.badge-perfil-top {
  background: #f3e8e6;
  color: #612c2d;
  font-weight: 700;
  font-size: 0.76rem;
  padding: 3px 10px;
  border-radius: 999px;
}

.badge-live-room {
  background: #dcfce7;
  color: #15803d;
  font-weight: 800;
  font-size: 0.72rem;
  padding: 3px 9px;
  border-radius: 999px;
  letter-spacing: 0.5px;
}

.sala-main-title {
  font-size: 1.45rem;
  font-weight: 800;
  color: #4d2323;
  margin: 2px 0;
}

.sala-host-label {
  font-size: 0.83rem;
  color: #6b7280;
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
  background: #fdfaf8;
  color: #612c2d;
  border: 1.5px solid #612c2d;
  border-radius: 9px;
  padding: 8px 14px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
  transition: 0.2s;
}

.btn-pip-room:hover {
  background: #612c2d;
  color: #ffffff;
}

.btn-salir-sala {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 9px;
  padding: 8px 14px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
}

.btn-finalizar-sala {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fca5a5;
  border-radius: 9px;
  padding: 8px 14px;
  font-weight: 700;
  font-size: 0.85rem;
  cursor: pointer;
}

.sala-main-grid {
  display: grid;
  grid-template-columns: 1.25fr 1fr;
  gap: 22px;
}

@media (max-width: 960px) {
  .sala-main-grid {
    grid-template-columns: 1fr;
  }
}

/* Tarjeta Pomodoro Grupal */
.pomodoro-grupal-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 30px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.pomodoro-phase-chip {
  font-size: 0.82rem;
  font-weight: 800;
  letter-spacing: 1.4px;
  padding: 6px 16px;
  border-radius: 999px;
  background: #f3e8e6;
  color: #612c2d;
  margin-bottom: 14px;
}

.pomodoro-phase-chip.shortBreak,
.pomodoro-phase-chip.longBreak {
  background: #ecfdf5;
  color: #047857;
}

.cycles-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.cycles-counter {
  font-size: 0.88rem;
  font-weight: 600;
  color: #6b7280;
}

.tomatoes-list {
  display: flex;
  gap: 4px;
}

.tomato-dot {
  font-size: 1.1rem;
  opacity: 0.3;
  transition: 0.2s;
}

.tomato-dot.completed,
.tomato-dot.current {
  opacity: 1;
}

.clock-giant {
  font-size: 5.2rem;
  font-family: 'Courier New', Courier, monospace;
  font-weight: 800;
  color: #4d2323;
  line-height: 1.05;
  margin: 12px 0 16px 0;
}

.progress-track {
  width: 100%;
  max-width: 420px;
  height: 8px;
  background: #f3f4f6;
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 12px;
}

.progress-bar-fill {
  height: 100%;
  background: #612c2d;
  border-radius: 999px;
  transition: width 0.5s ease;
}

.sync-caption {
  font-size: 0.8rem;
  color: #6b7280;
  margin-bottom: 20px;
}

.group-controls {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
}

.btn-ctrl-primary {
  background: #612c2d;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  padding: 11px 22px;
  font-weight: 700;
  font-size: 0.92rem;
  cursor: pointer;
  transition: 0.2s;
}

.btn-ctrl-primary:hover {
  background: #7b413f;
}

.btn-ctrl-pause {
  background: #d97706;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  padding: 11px 22px;
  font-weight: 700;
  font-size: 0.92rem;
  cursor: pointer;
}

.btn-ctrl-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  padding: 10px 14px;
  font-weight: 600;
  font-size: 0.86rem;
  cursor: pointer;
}

.btn-ctrl-secondary:hover {
  background: #e5e7eb;
}

.config-inline-box {
  width: 100%;
  max-width: 460px;
  margin-top: 18px;
  padding: 14px;
  border-radius: 12px;
  background: #fdfaf8;
  border: 1px solid #eadcd7;
  text-align: left;
}

.config-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.config-grid label {
  display: block;
  font-size: 0.74rem;
  font-weight: 700;
  margin-bottom: 4px;
}

.input-sm-custom {
  width: 100%;
  padding: 6px 8px;
  border-radius: 7px;
  border: 1px solid #d1d5db;
  font-size: 0.85rem;
  color: #1f2937;
  background: #ffffff;
}

/* Columna Derecha */
.sala-side-column {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.meet-card,
.presencia-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 20px;
}

.side-card-title {
  font-size: 1.02rem;
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
}

.meet-info-text {
  font-size: 0.83rem;
  color: #4b5563;
  margin-bottom: 12px;
  word-break: break-all;
}

.btn-join-meet {
  flex: 1;
  background: #0b57d0;
  color: #ffffff;
  border: none;
  border-radius: 9px;
  padding: 10px 16px;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-join-meet:hover {
  background: #0842a0;
}

.btn-copy-meet {
  background: #f3f4f6;
  color: #1f2937;
  border: 1px solid #d1d5db;
  border-radius: 9px;
  padding: 10px 14px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
}

.meet-edit-box {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px dashed #e5e7eb;
}

.meet-edit-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  margin-bottom: 6px;
}

.btn-save-meet {
  background: #612c2d;
  color: #ffffff;
  border: none;
  border-radius: 7px;
  padding: 6px 14px;
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
  padding: 3px 8px;
  border-radius: 999px;
}

.mi-estado-selector {
  background: #fdfaf8;
  border: 1px solid #eadcd7;
  border-radius: 10px;
  padding: 10px 12px;
}

.mi-estado-label {
  display: block;
  font-size: 0.76rem;
  font-weight: 700;
  color: #5b3b3a;
  margin-bottom: 6px;
}

.estado-pills {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.estado-pill-btn {
  flex: 1;
  background: #ffffff;
  color: #4b5563;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 6px 8px;
  font-size: 0.76rem;
  font-weight: 600;
  cursor: pointer;
  transition: 0.15s;
}

.estado-pill-btn.active {
  background: #612c2d;
  color: #ffffff;
  border-color: #612c2d;
}

.participantes-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.participante-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 10px;
  background: #f9fafb;
  border: 1px solid #f3f4f6;
}

.participante-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #612c2d;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.participante-info {
  flex: 1;
  min-width: 0;
}

.participante-nombre {
  font-size: 0.88rem;
  font-weight: 700;
  color: #1f2937;
  display: flex;
  align-items: center;
  gap: 6px;
}

.host-tag {
  font-size: 0.68rem;
  background: #fef3c7;
  color: #92400e;
  padding: 1px 6px;
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
  font-size: 0.74rem;
  font-weight: 700;
  padding: 4px 9px;
  border-radius: 999px;
  white-space: nowrap;
}

.status-online {
  background: #ecfdf5;
  color: #047857;
}

.status-pomodoro {
  background: #fef2f2;
  color: #b91c1c;
}

.status-meet {
  background: #eff6ff;
  color: #1d4ed8;
}

/* Dark Mode Overrides */
:global(body.cn-body-dark) .clock-giant {
  color: #ffffff !important;
}

:global(body.cn-body-dark) .progress-bar-fill {
  background-color: #f4be95 !important;
}

:global(body.cn-body-dark) .progress-track {
  background-color: #3b1919 !important;
}

:global(body.cn-body-dark) .mi-estado-selector,
:global(body.cn-body-dark) .config-inline-box,
:global(body.cn-body-dark) .participante-row {
  background-color: #3b1919 !important;
  border-color: #7b413f !important;
}

:global(body.cn-body-dark) .participante-nombre,
:global(body.cn-body-dark) .mi-estado-label,
:global(body.cn-body-dark) .meet-info-text {
  color: #fcd5b8 !important;
}

:global(body.cn-body-dark) .btn-ctrl-primary,
:global(body.cn-body-dark) .btn-pip-room {
  background-color: #f4be95 !important;
  color: #3b1919 !important;
  border-color: #f4be95 !important;
}
</style>
