<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  salasActivas: {
    type: Array,
    default: () => []
  },
  perfiles: {
    type: Array,
    default: () => []
  },
  perfilActivoId: {
    type: Number,
    default: null
  },
  tieneGoogleVinculado: {
    type: Boolean,
    default: false
  },
  minutosInactividadMax: {
    type: Number,
    default: 30
  }
});

const showCrearModal = ref(false);
const filtroPerfiles = ref('todos'); // 'todos' | 'activas' | 'compartidos'
const presetActivo = ref('clasico');

const form = useForm({
  idPerfil: props.perfilActivoId || (props.perfiles[0]?.idPerfil ?? ''),
  tituloEvento: 'Sesión de Estudio Grupal',
  enlaceMeet: '',
  duracionTrabajo: 25,
  duracionDescansoCorto: 5,
  duracionDescansoLargo: 15,
  ciclosObjetivo: 4
});

const aplicarPreset = (tipo) => {
  presetActivo.value = tipo;
  if (tipo === 'clasico') {
    form.duracionTrabajo = 25;
    form.duracionDescansoCorto = 5;
    form.duracionDescansoLargo = 15;
    form.ciclosObjetivo = 4;
  } else if (tipo === 'profundo') {
    form.duracionTrabajo = 50;
    form.duracionDescansoCorto = 10;
    form.duracionDescansoLargo = 20;
    form.ciclosObjetivo = 3;
  } else if (tipo === 'rapido') {
    form.duracionTrabajo = 15;
    form.duracionDescansoCorto = 3;
    form.duracionDescansoLargo = 10;
    form.ciclosObjetivo = 4;
  }
};

const abrirModalCrear = (perfilId = null, tituloSugerido = null) => {
  if (perfilId) {
    form.idPerfil = perfilId;
  }
  if (tituloSugerido) {
    form.tituloEvento = `Estudio — ${tituloSugerido}`;
  } else {
    const perfilSel = props.perfiles.find(p => p.idPerfil == form.idPerfil);
    form.tituloEvento = perfilSel ? `Estudio — ${perfilSel.tituloPerfil}` : 'Sesión de Estudio Grupal';
  }
  form.enlaceMeet = '';
  aplicarPreset('clasico');
  showCrearModal.value = true;
};

const cerrarModalCrear = () => {
  showCrearModal.value = false;
};

const alCambiarPerfilModal = () => {
  const perfilSel = props.perfiles.find(p => p.idPerfil == form.idPerfil);
  if (perfilSel) {
    form.tituloEvento = `Estudio — ${perfilSel.tituloPerfil}`;
  }
};

const abrirMeetInstantaneo = () => {
  window.open('https://meet.google.com/new', '_blank', 'noopener,noreferrer');
};

const crearSala = () => {
  form.post(route('salas.store'), {
    onSuccess: () => {
      showCrearModal.value = false;
    }
  });
};

const salasPorPerfilMap = computed(() => {
  const map = {};
  for (const s of props.salasActivas) {
    map[s.idPerfil] = s;
  }
  return map;
});

const perfilesFiltrados = computed(() => {
  if (filtroPerfiles.value === 'activas') {
    return props.perfiles.filter(p => !!salasPorPerfilMap.value[p.idPerfil]);
  }
  if (filtroPerfiles.value === 'compartidos') {
    return props.perfiles.filter(p => p.esCompartido || (p.usuarios_compartidos_count ?? 0) > 0);
  }
  return props.perfiles;
});

const totalPersonasEstudiando = computed(() => {
  return props.salasActivas.reduce((acc, s) => acc + (Number(s.conectadosCount) || 0), 0);
});

const formatFase = (fase) => {
  if (fase === 'work') return 'Tiempo de enfoque';
  if (fase === 'shortBreak') return 'Descanso corto';
  return 'Descanso largo';
};

const formatSegundos = (segundos) => {
  const s = Math.max(0, Number(segundos) || 0);
  const mins = Math.floor(s / 60);
  const secs = s % 60;
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
};

const calcularProgresoSala = (pomodoro) => {
  if (!pomodoro) return 0;
  const total = Number(pomodoro.segundosTotalesFase) || 1500;
  const rest = Number(pomodoro.segundosRestantes) || total;
  if (total <= 0) return 0;
  return Math.min(100, Math.max(0, Math.round(((total - rest) / total) * 100)));
};

const obtenerIniciales = (nombre) => {
  if (!nombre) return 'U';
  return nombre
    .split(' ')
    .filter(Boolean)
    .map(p => p[0].toUpperCase())
    .slice(0, 2)
    .join('');
};
</script>

<template>
  <Head title="Salas de Estudio" />
  <AppLayout>
    <div class="salas-page-container">
      <!-- Encabezado principal -->
      <div class="salas-hero-bar">
        <div class="salas-hero-text">
          <h1 class="salas-title">Salas de Estudio</h1>
          <p class="salas-subtitle">
            Estudiá en simultáneo con tu equipo usando un reloj Pomodoro compartido y videollamada en Google Meet.
          </p>
        </div>

        <div class="salas-hero-right">
          <div class="salas-metrics-row">
            <div class="metric-chip">
              <span class="metric-value">{{ salasActivas.length }}</span>
              <span class="metric-label">{{ salasActivas.length === 1 ? 'Sala activa' : 'Salas activas' }}</span>
            </div>
            <div class="metric-chip">
              <span class="metric-value">{{ totalPersonasEstudiando }}</span>
              <span class="metric-label">{{ totalPersonasEstudiando === 1 ? 'Persona en sala' : 'Personas en sala' }}</span>
            </div>
          </div>

          <button
            v-if="perfiles.length > 0"
            type="button"
            class="btn-crear-sala"
            @click="abrirModalCrear()"
          >
            <i class="bi bi-plus-lg"></i>
            <span>Nueva Sala de Estudio</span>
          </button>
        </div>
      </div>

      <!-- Sección 1: Salas Activas en este momento -->
      <section class="salas-section">
        <div class="section-header-bar">
          <div class="section-heading">
            <span class="live-dot"></span>
            <h2>Salas activas en este momento ({{ salasActivas.length }})</h2>
          </div>
          <span class="inactivity-hint">
            Cierre automático tras {{ minutosInactividadMax }} min sin actividad
          </span>
        </div>

        <div v-if="salasActivas.length === 0" class="empty-salas-card card">
          <div class="empty-icon-circle">
            <i class="bi bi-people"></i>
          </div>
          <h3 class="empty-title">No hay salas de estudio activas</h3>
          <p class="empty-subtitle">
            Iniciá una sala en cualquiera de tus perfiles personales o compartidos para organizar una sesión de estudio sincronizada.
          </p>
          <button
            v-if="perfiles.length > 0"
            type="button"
            class="btn-crear-sala mt-2"
            @click="abrirModalCrear()"
          >
            Iniciar primera sala
          </button>
        </div>

        <div v-else class="salas-grid">
          <div
            v-for="sala in salasActivas"
            :key="sala.idSalaEstudio"
            class="sala-card card"
          >
            <div class="sala-card-top">
              <div class="sala-badges-group">
                <span class="badge-perfil">{{ sala.perfilTitulo }}</span>
                <span v-if="sala.esCompartido" class="badge-compartido">Equipo</span>
              </div>
              <span class="badge-online">
                <span class="pulse-dot"></span>
                {{ sala.conectadosCount }} {{ sala.conectadosCount === 1 ? 'conectado' : 'conectados' }}
              </span>
            </div>

            <h3 class="sala-card-title">{{ sala.tituloEvento }}</h3>

            <div class="sala-creator-row">
              <span class="creator-avatar">{{ obtenerIniciales(sala.creadorNombre) }}</span>
              <span class="sala-card-meta">
                Anfitrión: <strong>{{ sala.creadorNombre }}</strong> · {{ sala.fechaCreacion }}
              </span>
            </div>

            <!-- Estado del reloj grupal -->
            <div class="sala-pomodoro-pill">
              <div class="pomodoro-pill-header">
                <div class="pomodoro-pill-title">
                  <span class="status-indicator" :class="{ running: sala.pomodoro?.corriendo }"></span>
                  {{ formatFase(sala.pomodoro?.fase) }} · Ciclo {{ sala.pomodoro?.cicloActual || 1 }} de {{ sala.pomodoro?.ciclosObjetivo || 4 }}
                </div>
                <span class="pomodoro-pill-time">
                  {{ formatSegundos(sala.pomodoro?.segundosRestantes) }}
                </span>
              </div>

              <div class="mini-progress-track">
                <div
                  class="mini-progress-fill"
                  :style="{ width: calcularProgresoSala(sala.pomodoro) + '%' }"
                ></div>
              </div>

              <div class="pomodoro-pill-sub">
                <span>{{ sala.pomodoro?.corriendo ? 'Reloj grupal en curso' : 'Reloj en pausa' }}</span>
                <span>{{ sala.pomodoro?.duracionTrabajo || 25 }}m enfoque / {{ sala.pomodoro?.duracionDescansoCorto || 5 }}m descanso</span>
              </div>
            </div>

            <div class="sala-card-actions">
              <Link :href="route('salas.show', sala.idSalaEstudio)" class="btn-entrar-sala">
                <span>Entrar a la sala</span>
              </Link>
              <a
                v-if="sala.googleMeetUrl && sala.googleMeetUrl !== 'https://meet.google.com/new'"
                :href="sala.googleMeetUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="btn-meet-directo"
                title="Abrir videollamada en Google Meet"
              >
                Google Meet
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- Sección 2: Tus Perfiles y Equipos -->
      <section class="salas-section">
        <div class="section-header-bar">
          <div class="section-heading">
            <h2>Perfiles y espacios de trabajo</h2>
          </div>

          <div v-if="perfiles.length > 0" class="filter-pills-group">
            <button
              type="button"
              class="btn-filtro-sala"
              :class="{ active: filtroPerfiles === 'todos' }"
              @click="filtroPerfiles = 'todos'"
            >
              Todos ({{ perfiles.length }})
            </button>
            <button
              type="button"
              class="btn-filtro-sala"
              :class="{ active: filtroPerfiles === 'activas' }"
              @click="filtroPerfiles = 'activas'"
            >
              Con sala activa ({{ salasActivas.length }})
            </button>
            <button
              type="button"
              class="btn-filtro-sala"
              :class="{ active: filtroPerfiles === 'compartidos' }"
              @click="filtroPerfiles = 'compartidos'"
            >
              En equipo
            </button>
          </div>
        </div>

        <div v-if="perfiles.length === 0" class="empty-salas-card card">
          <p class="empty-subtitle mb-0">
            Primero necesitás crear un perfil en la sección <Link :href="route('gestion-perfil')" class="fw-bold">Mis Perfiles</Link>.
          </p>
        </div>

        <div v-else-if="perfilesFiltrados.length === 0" class="empty-salas-card card py-4">
          <p class="empty-subtitle mb-0">
            No hay perfiles que coincidan con el filtro seleccionado.
          </p>
        </div>

        <div v-else class="perfiles-salas-grid">
          <div
            v-for="p in perfilesFiltrados"
            :key="p.idPerfil"
            class="perfil-sala-item card"
            :class="{ 'has-active-room': !!salasPorPerfilMap[p.idPerfil] }"
          >
            <div class="perfil-header-row">
              <div class="perfil-identity">
                <div class="perfil-icon-box">
                  <i :class="'bi ' + (p.iconoPerfil || 'bi-folder-fill')"></i>
                </div>
                <div class="perfil-title-wrap">
                  <h4 class="perfil-item-title" :title="p.tituloPerfil">{{ p.tituloPerfil }}</h4>
                  <span class="perfil-item-owner">
                    {{ p.esCompartido ? `Compartido por ${p.propietarioNombre}` : 'Perfil personal' }}
                    <template v-if="p.usuarios_compartidos_count > 0">
                      · {{ p.usuarios_compartidos_count + 1 }} miembros
                    </template>
                  </span>
                </div>
              </div>

              <span v-if="salasPorPerfilMap[p.idPerfil]" class="badge-activa">
                <span class="badge-activa-dot"></span>
                Sala activa
              </span>
            </div>

            <p class="perfil-item-desc">
              {{ p.descripcionPerfil || 'Espacio de organización, tareas y sesiones de estudio.' }}
            </p>

            <div class="perfil-card-footer">
              <Link
                v-if="salasPorPerfilMap[p.idPerfil]"
                :href="route('salas.show', salasPorPerfilMap[p.idPerfil].idSalaEstudio)"
                class="btn-unirse-perfil"
              >
                Unirse a la sala activa
              </Link>
              <button
                v-else
                type="button"
                class="btn-abrir-perfil"
                @click="abrirModalCrear(p.idPerfil, p.tituloPerfil)"
              >
                Abrir sala en este perfil
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Modal Crear Sala de Estudio -->
      <Teleport to="body">
        <div v-if="showCrearModal" class="sala-modal-backdrop" @click.self="cerrarModalCrear">
          <div class="sala-modal-card card">
            <div class="sala-modal-header">
              <div>
                <h3 class="sala-modal-title">Configurar Sala de Estudio</h3>
                <p class="sala-modal-subtitle">Definí el espacio de trabajo y la duración de los bloques de concentración.</p>
              </div>
              <button type="button" class="btn-close-sala-modal" @click="cerrarModalCrear" aria-label="Cerrar">&times;</button>
            </div>

            <form @submit.prevent="crearSala" class="sala-modal-body">
              <div class="sala-form-group">
                <label class="sala-form-label">Perfil o equipo de trabajo</label>
                <select v-model="form.idPerfil" @change="alCambiarPerfilModal" class="sala-input" required>
                  <option v-for="p in perfiles" :key="p.idPerfil" :value="p.idPerfil">
                    {{ p.tituloPerfil }} {{ p.esCompartido ? '(Compartido)' : '(Personal)' }}
                  </option>
                </select>
              </div>

              <div class="sala-form-group">
                <label class="sala-form-label">Nombre de la sesión</label>
                <input
                  type="text"
                  v-model="form.tituloEvento"
                  class="sala-input"
                  placeholder="Ej: Repaso para el parcial"
                  maxlength="150"
                  required
                />
              </div>

              <div class="sala-form-group">
                <label class="sala-form-label">Videollamada en Google Meet (opcional)</label>
                <div class="meet-helper-box">
                  <p v-if="tieneGoogleVinculado" class="meet-status-hint">
                    Tenés tu cuenta de Google vinculada. Si dejás el campo vacío, se intentará crear el enlace automáticamente, o podés pegar uno debajo.
                  </p>
                  <p v-else class="meet-status-hint">
                    Podés abrir una reunión nueva en Google Meet y pegar su enlace o código acá para que todos accedan directo.
                  </p>

                  <div class="meet-input-row">
                    <input
                      type="text"
                      v-model="form.enlaceMeet"
                      class="sala-input"
                      placeholder="https://meet.google.com/abc-defg-hij o código"
                    />
                    <button
                      type="button"
                      class="btn-meet-helper"
                      @click="abrirMeetInstantaneo"
                    >
                      Crear reunión en Meet
                    </button>
                  </div>
                </div>
              </div>

              <!-- Presets rápidos de tiempo -->
              <div class="sala-form-group">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                  <label class="sala-form-label mb-0">Ritmo del temporizador</label>
                  <div class="preset-pills">
                    <button
                      type="button"
                      class="preset-pill"
                      :class="{ active: presetActivo === 'clasico' }"
                      @click="aplicarPreset('clasico')"
                    >
                      Clásico (25 / 5)
                    </button>
                    <button
                      type="button"
                      class="preset-pill"
                      :class="{ active: presetActivo === 'profundo' }"
                      @click="aplicarPreset('profundo')"
                    >
                      Profundo (50 / 10)
                    </button>
                    <button
                      type="button"
                      class="preset-pill"
                      :class="{ active: presetActivo === 'rapido' }"
                      @click="aplicarPreset('rapido')"
                    >
                      Breve (15 / 3)
                    </button>
                  </div>
                </div>

                <div class="sala-form-grid">
                  <div>
                    <label class="sala-sub-label">Enfoque (min)</label>
                    <input type="number" v-model.number="form.duracionTrabajo" @input="presetActivo = 'custom'" class="sala-input" min="1" max="120" required />
                  </div>
                  <div>
                    <label class="sala-sub-label">Descanso corto (min)</label>
                    <input type="number" v-model.number="form.duracionDescansoCorto" @input="presetActivo = 'custom'" class="sala-input" min="1" max="30" required />
                  </div>
                  <div>
                    <label class="sala-sub-label">Descanso largo (min)</label>
                    <input type="number" v-model.number="form.duracionDescansoLargo" @input="presetActivo = 'custom'" class="sala-input" min="5" max="60" required />
                  </div>
                  <div>
                    <label class="sala-sub-label">Ciclos</label>
                    <input type="number" v-model.number="form.ciclosObjetivo" @input="presetActivo = 'custom'" class="sala-input" min="1" max="10" required />
                  </div>
                </div>
              </div>

              <div class="sala-modal-footer">
                <button type="button" class="btn-cancelar-sala-modal" @click="cerrarModalCrear">
                  Cancelar
                </button>
                <button type="submit" class="btn-crear-sala" :disabled="form.processing">
                  {{ form.processing ? 'Creando sala...' : 'Crear e ingresar a la sala' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>
    </div>
  </AppLayout>
</template>

<style scoped>
.salas-page-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 4px 8px 48px 8px;
}

.salas-hero-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 32px;
  padding-bottom: 20px;
  border-bottom: 1px solid #eadcd7;
}

.salas-hero-text {
  max-width: 620px;
}

.salas-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: #4d2323;
  margin: 0 0 6px 0;
  letter-spacing: -0.3px;
}

.salas-subtitle {
  color: #7b413f;
  margin: 0;
  font-size: 0.95rem;
  line-height: 1.5;
}

.salas-hero-right {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.salas-metrics-row {
  display: flex;
  gap: 10px;
}

.metric-chip {
  background: #faf3ef;
  border: 1px solid #eadcd7;
  border-radius: 10px;
  padding: 8px 14px;
  display: flex;
  align-items: baseline;
  gap: 7px;
}

.metric-value {
  font-size: 1.1rem;
  font-weight: 800;
  color: #612c2d;
}

.metric-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #7b413f;
  white-space: nowrap;
}

.btn-crear-sala {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background-color: #612c2d;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  padding: 11px 20px;
  font-weight: 600;
  font-size: 0.92rem;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.15s;
  box-shadow: 0 4px 12px rgba(97, 44, 45, 0.2);
  white-space: nowrap;
}

.btn-crear-sala:hover {
  background-color: #7b413f;
  transform: translateY(-1px);
}

.salas-section {
  margin-bottom: 36px;
}

.section-header-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}

.section-heading {
  display: flex;
  align-items: center;
  gap: 10px;
}

.section-heading h2 {
  font-size: 1.15rem;
  font-weight: 700;
  color: #4d2323;
  margin: 0;
}

.inactivity-hint {
  font-size: 0.78rem;
  font-weight: 500;
  color: #8c5854;
  background: #faf3ef;
  border: 1px solid #eadcd7;
  padding: 4px 10px;
  border-radius: 999px;
}

.live-dot {
  width: 9px;
  height: 9px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.18);
  flex-shrink: 0;
}

.empty-salas-card {
  background: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 14px;
  padding: 36px 24px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-icon-circle {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #faf3ef;
  border: 1px solid #eadcd7;
  color: #612c2d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  margin-bottom: 12px;
}

.empty-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #4d2323;
  margin: 0 0 6px 0;
}

.empty-subtitle {
  font-size: 0.9rem;
  color: #7b413f;
  max-width: 500px;
  margin: 0 0 8px 0;
}

/* Grid de Salas Activas */
.salas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 20px;
}

.sala-card {
  background: #ffffff;
  border: 1px solid #eadcd7;
  border-top: 3px solid #612c2d;
  border-radius: 14px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 14px rgba(77, 35, 35, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;
}

.sala-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(77, 35, 35, 0.1);
}

.sala-card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
  margin-bottom: 12px;
}

.sala-badges-group {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  min-width: 0;
}

.badge-perfil {
  background-color: #f3e8e6;
  color: #612c2d;
  font-weight: 700;
  font-size: 0.76rem;
  padding: 4px 10px;
  border-radius: 999px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 190px;
}

.badge-compartido {
  background-color: #faebd7;
  color: #7b413f;
  font-weight: 600;
  font-size: 0.74rem;
  padding: 4px 9px;
  border-radius: 999px;
  white-space: nowrap;
  flex-shrink: 0;
}

.badge-online {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.76rem;
  font-weight: 600;
  color: #047857;
  background: #ecfdf5;
  padding: 4px 10px;
  border-radius: 999px;
  white-space: nowrap;
  flex-shrink: 0;
}

.pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
  flex-shrink: 0;
}

.sala-card-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #3b1919;
  margin: 0 0 8px 0;
  line-height: 1.35;
}

.sala-creator-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
}

.creator-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #612c2d;
  color: #ffffff;
  font-size: 0.68rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.sala-card-meta {
  font-size: 0.82rem;
  color: #7b413f;
  margin: 0;
}

.sala-pomodoro-pill {
  background: #faf3ef;
  border: 1px solid #eadcd7;
  border-radius: 11px;
  padding: 12px 14px;
  margin-bottom: 16px;
}

.pomodoro-pill-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.pomodoro-pill-title {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 0.84rem;
  font-weight: 700;
  color: #4d2323;
}

.status-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #b08980;
  flex-shrink: 0;
}

.status-indicator.running {
  background: #10b981;
}

.pomodoro-pill-time {
  font-size: 0.92rem;
  font-weight: 800;
  color: #612c2d;
  font-variant-numeric: tabular-nums;
}

.mini-progress-track {
  width: 100%;
  height: 5px;
  background: #eadcd7;
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 8px;
}

.mini-progress-fill {
  height: 100%;
  background: #612c2d;
  border-radius: 999px;
  transition: width 0.3s ease;
}

.pomodoro-pill-sub {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  font-size: 0.75rem;
  color: #7b413f;
}

.sala-card-actions {
  display: flex;
  gap: 10px;
  margin-top: auto;
}

.btn-entrar-sala {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: #612c2d;
  color: #ffffff;
  text-decoration: none;
  padding: 10px 16px;
  border-radius: 9px;
  font-weight: 600;
  font-size: 0.88rem;
  transition: background-color 0.2s;
}

.btn-entrar-sala:hover {
  background-color: #7b413f;
  color: #ffffff;
}

.btn-meet-directo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: #faf3ef;
  color: #612c2d;
  text-decoration: none;
  padding: 10px 14px;
  border-radius: 9px;
  font-weight: 600;
  font-size: 0.85rem;
  border: 1px solid #dcc5be;
  transition: background-color 0.2s;
  white-space: nowrap;
}

.btn-meet-directo:hover {
  background-color: #f2e4dc;
}

/* Filtros de perfiles */
.filter-pills-group {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-filtro-sala {
  background: #ffffff;
  border: 1px solid #dcc5be;
  color: #612c2d;
  border-radius: 999px;
  padding: 5px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-filtro-sala:hover {
  background: #faf3ef;
}

.btn-filtro-sala.active {
  background: #612c2d;
  color: #ffffff;
  border-color: #612c2d;
}

/* Grid de Perfiles */
.perfiles-salas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 18px;
}

.perfil-sala-item {
  background: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 14px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.perfil-sala-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(77, 35, 35, 0.07);
}

.perfil-sala-item.has-active-room {
  border-color: #a55e57;
}

.perfil-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 10px;
}

.perfil-identity {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  min-width: 0;
  flex: 1;
}

.perfil-icon-box {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #faf3ef;
  border: 1px solid #eadcd7;
  color: #612c2d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  flex-shrink: 0;
}

.perfil-title-wrap {
  min-width: 0;
  flex: 1;
}

.perfil-item-title {
  font-size: 1.02rem;
  font-weight: 700;
  margin: 0 0 2px 0;
  color: #3b1919;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
}

.perfil-item-owner {
  display: block;
  font-size: 0.78rem;
  color: #7b413f;
  line-height: 1.3;
}

.badge-activa {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #ecfdf5;
  color: #047857;
  border: 1px solid #a7f3d0;
  font-size: 0.74rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  white-space: nowrap;
  flex-shrink: 0;
  line-height: 1.2;
}

.badge-activa-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
  flex-shrink: 0;
}

.perfil-item-desc {
  font-size: 0.85rem;
  color: #6b4c49;
  margin: 6px 0 16px 0;
  line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.perfil-card-footer {
  margin-top: auto;
}

.btn-abrir-perfil {
  width: 100%;
  background: #faf3ef;
  border: 1px solid #dcc5be;
  color: #612c2d;
  border-radius: 9px;
  padding: 9px 14px;
  font-weight: 600;
  font-size: 0.86rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-abrir-perfil:hover {
  background: #612c2d;
  color: #ffffff;
  border-color: #612c2d;
}

.btn-unirse-perfil {
  display: block;
  width: 100%;
  text-align: center;
  background: #f4be95;
  color: #4d2323;
  text-decoration: none;
  border-radius: 9px;
  padding: 9px 14px;
  font-weight: 700;
  font-size: 0.86rem;
  transition: background-color 0.2s;
}

.btn-unirse-perfil:hover {
  background: #eab083;
  color: #3b1919;
}
</style>

<style>
/* Estilos del Modal (Teleported a body) y Modo Oscuro Institucional */
.sala-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(28, 12, 12, 0.6);
  backdrop-filter: blur(3px);
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.sala-modal-card {
  background: #ffffff;
  border: 1px solid #eadcd7;
  width: 100%;
  max-width: 560px;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
}

.sala-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 18px;
  border-bottom: 1px solid #eadcd7;
  padding-bottom: 14px;
}

.sala-modal-title {
  margin: 0 0 4px 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: #4d2323;
}

.sala-modal-subtitle {
  margin: 0;
  font-size: 0.83rem;
  color: #7b413f;
}

.btn-close-sala-modal {
  background: transparent;
  border: none;
  font-size: 1.6rem;
  line-height: 1;
  color: #7b413f;
  cursor: pointer;
  padding: 0 4px;
}

.btn-close-sala-modal:hover {
  color: #4d2323;
}

.sala-form-group {
  margin-bottom: 16px;
}

.sala-form-label {
  display: block;
  font-size: 0.85rem;
  font-weight: 700;
  margin-bottom: 6px;
  color: #4d2323;
}

.sala-sub-label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  margin-bottom: 5px;
  color: #612c2d;
}

.sala-input {
  width: 100%;
  padding: 9px 12px;
  border-radius: 9px;
  border: 1px solid #dcc5be;
  font-size: 0.9rem;
  color: #3b1919;
  background: #ffffff;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.sala-input:focus {
  border-color: #612c2d;
  box-shadow: 0 0 0 3px rgba(97, 44, 45, 0.12);
}

.meet-helper-box {
  background: #faf3ef;
  border: 1px solid #eadcd7;
  border-radius: 11px;
  padding: 12px 14px;
}

.meet-status-hint {
  font-size: 0.8rem;
  color: #612c2d;
  margin: 0 0 10px 0;
  line-height: 1.45;
}

.meet-input-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.meet-input-row .sala-input {
  flex: 1;
  min-width: 200px;
}

.btn-meet-helper {
  background: #612c2d;
  color: #ffffff;
  border: 1px solid #612c2d;
  border-radius: 9px;
  padding: 9px 14px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background-color 0.2s;
}

.btn-meet-helper:hover {
  background: #7b413f;
}

.preset-pills {
  display: flex;
  gap: 6px;
}

.preset-pill {
  background: #faf3ef;
  border: 1px solid #dcc5be;
  color: #612c2d;
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 0.74rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.preset-pill.active {
  background: #612c2d;
  color: #ffffff;
  border-color: #612c2d;
}

.sala-form-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

@media (max-width: 540px) {
  .sala-form-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.sala-modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
  padding-top: 14px;
  border-top: 1px solid #eadcd7;
}

.btn-cancelar-sala-modal {
  background: #faf3ef;
  color: #612c2d;
  border: 1px solid #dcc5be;
  border-radius: 9px;
  padding: 10px 18px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-cancelar-sala-modal:hover {
  background: #f2e4dc;
}

/* =====================================================
   MODO OSCURO — SALAS DE ESTUDIO (Index + Modal)
   ===================================================== */
body.cn-body-dark .salas-hero-bar {
  border-bottom-color: #7b413f !important;
}

body.cn-body-dark .metric-chip,
body.cn-body-dark .inactivity-hint {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .metric-value {
  color: #f4be95 !important;
}

body.cn-body-dark .metric-label {
  color: #fcd5b8 !important;
}

body.cn-body-dark .empty-icon-circle,
body.cn-body-dark .perfil-icon-box {
  background-color: #612c2d !important;
  border-color: #7b413f !important;
  color: #f4be95 !important;
}

body.cn-body-dark .sala-card {
  border-top-color: #f4be95 !important;
}

body.cn-body-dark .badge-perfil {
  background-color: #612c2d !important;
  color: #f4be95 !important;
  border: 1px solid #7b413f !important;
}

body.cn-body-dark .badge-compartido {
  background-color: #3b1717 !important;
  color: #fcd5b8 !important;
  border: 1px solid #7b413f !important;
}

body.cn-body-dark .badge-online,
body.cn-body-dark .badge-activa {
  background-color: rgba(16, 185, 129, 0.16) !important;
  color: #6ee7b7 !important;
  border-color: rgba(16, 185, 129, 0.35) !important;
}

body.cn-body-dark .creator-avatar {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
}

body.cn-body-dark .sala-card-meta,
body.cn-body-dark .perfil-item-owner,
body.cn-body-dark .perfil-item-desc {
  color: #fcd5b8 !important;
}

body.cn-body-dark .sala-pomodoro-pill {
  background-color: #3b1717 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .pomodoro-pill-title {
  color: #ffffff !important;
}

body.cn-body-dark .pomodoro-pill-time {
  color: #f4be95 !important;
}

body.cn-body-dark .mini-progress-track {
  background-color: #612c2d !important;
}

body.cn-body-dark .mini-progress-fill {
  background-color: #f4be95 !important;
}

body.cn-body-dark .pomodoro-pill-sub {
  color: #fcd5b8 !important;
}

body.cn-body-dark .btn-crear-sala,
body.cn-body-dark .btn-entrar-sala {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
}

body.cn-body-dark .btn-crear-sala:hover,
body.cn-body-dark .btn-entrar-sala:hover {
  background-color: #fcd5b8 !important;
  color: #3b1919 !important;
}

body.cn-body-dark .btn-meet-directo,
body.cn-body-dark .btn-abrir-perfil {
  background-color: #612c2d !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .btn-meet-directo:hover,
body.cn-body-dark .btn-abrir-perfil:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .btn-filtro-sala {
  background-color: #4d2323 !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .btn-filtro-sala.active {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
  border-color: #f4be95 !important;
}

/* Modal Crear Sala en Modo Oscuro */
body.cn-body-dark .sala-modal-card {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .sala-modal-header,
body.cn-body-dark .sala-modal-footer {
  border-color: #7b413f !important;
}

body.cn-body-dark .sala-modal-title {
  color: #ffffff !important;
}

body.cn-body-dark .sala-modal-subtitle,
body.cn-body-dark .sala-form-label,
body.cn-body-dark .sala-sub-label {
  color: #fcd5b8 !important;
}

body.cn-body-dark .btn-close-sala-modal {
  color: #fcd5b8 !important;
}

body.cn-body-dark .btn-close-sala-modal:hover {
  color: #ffffff !important;
}

body.cn-body-dark .sala-input {
  background-color: #612c2d !important;
  color: #ffffff !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .sala-input::placeholder {
  color: #d9b29c !important;
  opacity: 0.85;
}

body.cn-body-dark .sala-input:focus {
  border-color: #f4be95 !important;
  box-shadow: 0 0 0 2px rgba(244, 190, 149, 0.25) !important;
}

body.cn-body-dark .meet-helper-box {
  background-color: #3b1717 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .meet-status-hint {
  color: #fcd5b8 !important;
}

body.cn-body-dark .btn-meet-helper {
  background-color: #7b413f !important;
  color: #ffffff !important;
  border-color: #a55e57 !important;
}

body.cn-body-dark .btn-meet-helper:hover {
  background-color: #a55e57 !important;
}

body.cn-body-dark .preset-pill {
  background-color: #612c2d !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .preset-pill.active {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .btn-cancelar-sala-modal {
  background-color: #612c2d !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .btn-cancelar-sala-modal:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
}
</style>
