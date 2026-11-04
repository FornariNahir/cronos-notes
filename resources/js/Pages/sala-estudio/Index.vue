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
  }
});

const showCrearModal = ref(false);

const form = useForm({
  idPerfil: props.perfilActivoId || (props.perfiles[0]?.idPerfil ?? ''),
  tituloEvento: 'Sesión de Estudio Grupal',
  enlaceMeet: '',
  duracionTrabajo: 25,
  duracionDescansoCorto: 5,
  duracionDescansoLargo: 15,
  ciclosObjetivo: 4
});

const abrirModalCrear = (perfilId = null, tituloSugerido = null) => {
  if (perfilId) {
    form.idPerfil = perfilId;
  }
  if (tituloSugerido) {
    form.tituloEvento = `Estudio: ${tituloSugerido}`;
  } else {
    const perfilSel = props.perfiles.find(p => p.idPerfil == form.idPerfil);
    form.tituloEvento = perfilSel ? `Estudio — ${perfilSel.tituloPerfil}` : 'Sesión de Estudio Grupal';
  }
  form.enlaceMeet = '';
  showCrearModal.value = true;
};

const cerrarModalCrear = () => {
  showCrearModal.value = false;
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

const formatFase = (fase) => {
  if (fase === 'work') return 'Enfoque';
  if (fase === 'shortBreak') return 'Descanso Corto';
  return 'Descanso Largo';
};
</script>

<template>
  <Head title="Salas de Estudio Virtuales" />
  <AppLayout>
    <div class="salas-container">
      <!-- Encabezado principal -->
      <div class="salas-header">
        <div class="salas-header-text">
          <h1 class="salas-title">Salas de Estudio Virtuales</h1>
          <p class="salas-subtitle">
            Espacios de concentración grupal (<em>Body Doubling</em>) con temporizador Pomodoro sincronizado y videollamada integrada.
          </p>
        </div>
        <button
          v-if="perfiles.length > 0"
          type="button"
          class="btn-crear-sala"
          @click="abrirModalCrear()"
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Crear Sala de Estudio
        </button>
      </div>

      <!-- Sección 1: Salas Activas en este momento -->
      <section class="salas-section">
        <div class="section-heading">
          <span class="live-dot"></span>
          <h2>Salas Activas Ahora ({{ salasActivas.length }})</h2>
        </div>

        <div v-if="salasActivas.length === 0" class="empty-salas-card card">
          <div class="empty-icon-badge">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
              <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
          </div>
          <h3 class="empty-title">No hay salas de estudio activas en este momento</h3>
          <p class="empty-desc">
            Iniciá una sala en cualquiera de tus perfiles (personales o compartidos) para estudiar en simultáneo con tu equipo.
          </p>
          <button
            v-if="perfiles.length > 0"
            type="button"
            class="btn-crear-sala mt-2"
            @click="abrirModalCrear()"
          >
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
            Iniciar Primera Sala
          </button>
        </div>

        <div v-else class="salas-grid">
          <div
            v-for="sala in salasActivas"
            :key="sala.idSalaEstudio"
            class="sala-card card"
          >
            <div class="sala-card-top">
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge-perfil">{{ sala.perfilTitulo }}</span>
                <span v-if="sala.esCompartido" class="badge-compartido">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                  Equipo
                </span>
              </div>
              <span class="badge-online">
                <span class="pulse-dot"></span>
                {{ sala.conectadosCount }} {{ sala.conectadosCount === 1 ? 'conectado' : 'conectados' }}
              </span>
            </div>

            <h3 class="sala-card-title">{{ sala.tituloEvento }}</h3>
            <p class="sala-card-meta">
              Creada por <strong>{{ sala.creadorNombre }}</strong> · {{ sala.fechaCreacion }}
            </p>

            <!-- Estado del reloj grupal -->
            <div class="sala-pomodoro-pill">
              <div class="pomodoro-icon-box">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="13" r="8" />
                  <path d="M12 9v4l2.5 1.5" />
                  <path d="M9 3h6" />
                </svg>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                  <span class="pomodoro-pill-title">
                    {{ formatFase(sala.pomodoro?.fase) }} · Ciclo {{ sala.pomodoro?.cicloActual || 1 }}/{{ sala.pomodoro?.ciclosObjetivo || 4 }}
                  </span>
                  <div class="mini-cycles-dots" :title="`Ciclo ${sala.pomodoro?.cicloActual || 1} de ${sala.pomodoro?.ciclosObjetivo || 4}`">
                    <span
                      v-for="idx in (sala.pomodoro?.ciclosObjetivo || 4)"
                      :key="idx"
                      class="mini-cycle-dot"
                      :class="{
                        completed: idx < (sala.pomodoro?.cicloActual || 1),
                        current: idx === (sala.pomodoro?.cicloActual || 1)
                      }"
                    ></span>
                  </div>
                </div>
                <div class="pomodoro-pill-sub">
                  <svg v-if="sala.pomodoro?.corriendo" viewBox="0 0 24 24" width="12" height="12" fill="currentColor" stroke="none">
                    <polygon points="5 3 19 12 5 21 5 3" />
                  </svg>
                  <svg v-else viewBox="0 0 24 24" width="12" height="12" fill="currentColor" stroke="none">
                    <rect x="6" y="4" width="4" height="16" rx="1" />
                    <rect x="14" y="4" width="4" height="16" rx="1" />
                  </svg>
                  <span>
                    {{ sala.pomodoro?.corriendo ? 'Reloj grupal en curso' : 'Reloj en espera' }}
                    ({{ sala.pomodoro?.duracionTrabajo || 25 }}m / {{ sala.pomodoro?.duracionDescansoCorto || 5 }}m)
                  </span>
                </div>
              </div>
            </div>

            <div class="sala-card-actions">
              <Link :href="route('salas.show', sala.idSalaEstudio)" class="btn-entrar-sala">
                Entrar a la Sala
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M12 5l7 7-7 7" />
                </svg>
              </Link>
              <a
                v-if="sala.googleMeetUrl"
                :href="sala.googleMeetUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="btn-meet-directo"
                title="Abrir videollamada en Google Meet"
              >
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polygon points="23 7 16 12 23 17 23 7" />
                  <rect x="1" y="5" width="15" height="14" rx="2" ry="2" />
                </svg>
                Meet
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- Sección 2: Tus Perfiles y Equipos -->
      <section class="salas-section">
        <div class="section-heading">
          <h2>Tus Perfiles y Equipos de Trabajo</h2>
        </div>

        <div v-if="perfiles.length === 0" class="empty-salas-card card">
          <p class="empty-desc mb-0">
            Primero debés crear un perfil en la sección <Link :href="route('gestion-perfil')" class="link-cronos">Mis Perfiles</Link>.
          </p>
        </div>

        <div v-else class="perfiles-salas-grid">
          <div
            v-for="p in perfiles"
            :key="p.idPerfil"
            class="perfil-sala-item card"
          >
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <div class="min-w-0">
                <h4 class="perfil-item-title">{{ p.tituloPerfil }}</h4>
                <span class="perfil-item-owner">
                  <svg v-if="p.esCompartido || p.usuarios_compartidos_count > 0" viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                  {{ p.esCompartido ? `Compartido por ${p.propietarioNombre}` : 'Perfil propio' }}
                  <template v-if="p.usuarios_compartidos_count > 0">
                    · {{ p.usuarios_compartidos_count + 1 }} miembros
                  </template>
                </span>
              </div>
              <span v-if="salasPorPerfilMap[p.idPerfil]" class="badge-activa">
                <span class="pulse-dot"></span>
                Sala Activa
              </span>
            </div>

            <p class="perfil-item-desc">
              {{ p.descripcionPerfil || 'Espacio de organización y estudio.' }}
            </p>

            <div class="mt-auto pt-2">
              <Link
                v-if="salasPorPerfilMap[p.idPerfil]"
                :href="route('salas.show', salasPorPerfilMap[p.idPerfil].idSalaEstudio)"
                class="btn-unirse-perfil"
              >
                <span>Unirse a la Sala Activa</span>
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M12 5l7 7-7 7" />
                </svg>
              </Link>
              <button
                v-else
                type="button"
                class="btn-abrir-perfil"
                @click="abrirModalCrear(p.idPerfil, p.tituloPerfil)"
              >
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19" />
                  <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                <span>Abrir Sala en este Perfil</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Modal Crear Sala de Estudio -->
      <div v-if="showCrearModal" class="modal-backdrop-custom" @click.self="cerrarModalCrear">
        <div class="modal-card-custom card">
          <div class="modal-header-custom">
            <div class="d-flex align-items-center gap-2">
              <div class="modal-title-icon">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                  <circle cx="9" cy="7" r="4" />
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                  <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
              </div>
              <h3>Configurar Nueva Sala de Estudio</h3>
            </div>
            <button type="button" class="btn-close-modal" @click="cerrarModalCrear" aria-label="Cerrar">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="crearSala" class="modal-body-custom">
            <div class="form-group-custom">
              <label>Perfil / Equipo de Trabajo</label>
              <select v-model="form.idPerfil" class="input-custom" required>
                <option v-for="p in perfiles" :key="p.idPerfil" :value="p.idPerfil">
                  {{ p.tituloPerfil }} {{ p.esCompartido ? '(Compartido)' : '(Propio)' }}
                </option>
              </select>
            </div>

            <div class="form-group-custom">
              <label>Título de la Sesión Grupal</label>
              <input
                type="text"
                v-model="form.tituloEvento"
                class="input-custom"
                placeholder="Ej: Repaso Final de Programación Web"
                maxlength="150"
                required
              />
            </div>

            <div class="form-group-custom">
              <label>Videollamada de Google Meet</label>
              <div class="meet-helper-box">
                <div v-if="tieneGoogleVinculado" class="meet-status-hint">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hint-svg">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                  </svg>
                  <span>
                    Tenés tu cuenta de Google vinculada. Si dejás el campo vacío, Cronos generará el enlace automáticamente (o podés crear y pegar uno debajo).
                  </span>
                </div>
                <div v-else class="meet-status-hint">
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hint-svg">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="16" x2="12" y2="12" />
                    <line x1="12" y1="8" x2="12.01" y2="8" />
                  </svg>
                  <span>
                    Podés generar una sala instantánea en Google Meet con el botón adjunto y pegar el enlace o código aquí (también podrás actualizarlo desde la sala).
                  </span>
                </div>

                <div class="meet-input-row">
                  <input
                    type="text"
                    v-model="form.enlaceMeet"
                    class="input-custom meet-url-input"
                    placeholder="Opcional: https://meet.google.com/abc-defg-hij o código"
                  />
                  <button
                    type="button"
                    class="btn-meet-helper"
                    @click="abrirMeetInstantaneo"
                    title="Abre Google Meet en una pestaña nueva para copiar el link de la reunión"
                  >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polygon points="23 7 16 12 23 17 23 7" />
                      <rect x="1" y="5" width="15" height="14" rx="2" ry="2" />
                    </svg>
                    <span>Abrir Google Meet</span>
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                      <polyline points="15 3 21 3 21 9" />
                      <line x1="10" y1="14" x2="21" y2="3" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <div class="pomodoro-config-section">
              <div class="pomodoro-config-header">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="13" r="8" />
                  <path d="M12 9v4l2.5 1.5" />
                  <path d="M9 3h6" />
                </svg>
                <span>Parámetros del Temporizador Pomodoro Grupal</span>
              </div>

              <div class="form-row-grid">
                <div class="form-group-custom mb-0">
                  <label>Trabajo (minutos)</label>
                  <input type="number" v-model.number="form.duracionTrabajo" class="input-custom" min="1" max="120" required />
                </div>
                <div class="form-group-custom mb-0">
                  <label>Descanso Corto (minutos)</label>
                  <input type="number" v-model.number="form.duracionDescansoCorto" class="input-custom" min="1" max="30" required />
                </div>
                <div class="form-group-custom mb-0">
                  <label>Descanso Largo (minutos)</label>
                  <input type="number" v-model.number="form.duracionDescansoLargo" class="input-custom" min="5" max="60" required />
                </div>
                <div class="form-group-custom mb-0">
                  <label>Ciclos Objetivo</label>
                  <input type="number" v-model.number="form.ciclosObjetivo" class="input-custom" min="1" max="10" required />
                </div>
              </div>
            </div>

            <div class="modal-footer-custom">
              <button type="button" class="btn-cancelar-modal" @click="cerrarModalCrear">Cancelar</button>
              <button type="submit" class="btn-crear-sala" :disabled="form.processing">
                <span>{{ form.processing ? 'Creando Sala...' : 'Crear e Ingresar a la Sala' }}</span>
                <svg v-if="!form.processing" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M12 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.salas-container {
  max-width: 1220px;
  margin: 0 auto;
  padding: 6px 4px 48px 4px;
}

.salas-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 32px;
}

.salas-header-text {
  max-width: 720px;
}

.salas-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: #4A2328;
  margin: 0 0 6px 0;
  letter-spacing: -0.02em;
}

.salas-subtitle {
  color: #6b5352;
  margin: 0;
  font-size: 0.94rem;
  line-height: 1.5;
}

.salas-section {
  margin-bottom: 38px;
}

.btn-crear-sala {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background-color: #4A2328;
  color: #ffffff;
  border: 1px solid #4A2328;
  border-radius: 10px;
  padding: 11px 20px;
  font-weight: 600;
  font-size: 0.92rem;
  cursor: pointer;
  transition: background-color 0.2s, border-color 0.2s, transform 0.15s, box-shadow 0.2s;
  box-shadow: 0 4px 12px rgba(74, 35, 40, 0.2);
}

.btn-crear-sala:hover:not(:disabled) {
  background-color: #612c2d;
  border-color: #612c2d;
  transform: translateY(-1px);
}

.btn-crear-sala:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.section-heading {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 18px;
}

.section-heading h2 {
  font-size: 1.18rem;
  font-weight: 700;
  color: #4A2328;
  margin: 0;
}

.live-dot {
  width: 9px;
  height: 9px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.18);
}

.empty-salas-card {
  background: #ffffff;
  border: 1px solid #eadcd7;
  border-radius: 16px;
  padding: 40px 28px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  box-shadow: 0 4px 14px rgba(74, 35, 40, 0.04);
}

.empty-icon-badge {
  width: 58px;
  height: 58px;
  border-radius: 16px;
  background-color: #f6ecea;
  color: #612c2d;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}

.empty-title {
  font-size: 1.12rem;
  font-weight: 700;
  color: #4A2328;
  margin: 0 0 6px 0;
}

.empty-desc {
  color: #6b5352;
  font-size: 0.9rem;
  max-width: 520px;
  margin: 0 0 10px 0;
}

.link-cronos {
  color: #612c2d;
  font-weight: 700;
  text-decoration: underline;
}

.salas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 22px;
}

.sala-card {
  background: #ffffff;
  border: 1px solid #e6d7d2;
  border-radius: 16px;
  padding: 22px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 14px rgba(74, 35, 40, 0.05);
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}

.sala-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(74, 35, 40, 0.1);
  border-color: #cdb3ab;
}

.sala-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 14px;
}

.badge-perfil {
  background-color: #f4eae7;
  color: #4A2328;
  font-weight: 700;
  font-size: 0.76rem;
  padding: 4px 11px;
  border-radius: 999px;
  border: 1px solid #e2ceca;
}

.badge-compartido {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background-color: #fbf3ea;
  color: #7c4223;
  border: 1px solid #edd9c4;
  font-weight: 600;
  font-size: 0.74rem;
  padding: 3px 10px;
  border-radius: 999px;
}

.badge-online {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.76rem;
  font-weight: 600;
  color: #065f46;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  padding: 4px 10px;
  border-radius: 999px;
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
  color: #2c181a;
  margin: 0 0 4px 0;
}

.sala-card-meta {
  font-size: 0.82rem;
  color: #6b5352;
  margin-bottom: 16px;
}

.sala-pomodoro-pill {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #faf6f4;
  border: 1px solid #e6d7d2;
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 18px;
}

.pomodoro-icon-box {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background-color: #efe2de;
  color: #4A2328;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.pomodoro-pill-title {
  font-size: 0.84rem;
  font-weight: 700;
  color: #4A2328;
}

.mini-cycles-dots {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.mini-cycle-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: transparent;
  border: 1.5px solid #bfa59f;
}

.mini-cycle-dot.completed {
  background-color: #612c2d;
  border-color: #612c2d;
}

.mini-cycle-dot.current {
  background-color: #a55e57;
  border-color: #4A2328;
  box-shadow: 0 0 0 2px rgba(97, 44, 45, 0.2);
}

.pomodoro-pill-sub {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.76rem;
  color: #6b5352;
  margin-top: 3px;
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
  gap: 8px;
  background-color: #4A2328;
  color: #ffffff;
  text-decoration: none;
  padding: 10px 16px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.88rem;
  transition: background-color 0.2s;
}

.btn-entrar-sala:hover {
  background-color: #612c2d;
  color: #ffffff;
}

.btn-meet-directo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background-color: #faf6f4;
  color: #4A2328;
  text-decoration: none;
  padding: 10px 14px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.86rem;
  border: 1px solid #d8c3bd;
  transition: background-color 0.2s, border-color 0.2s;
}

.btn-meet-directo:hover {
  background-color: #efe2de;
  border-color: #bfa099;
  color: #4A2328;
}

.perfiles-salas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
  gap: 18px;
}

.perfil-sala-item {
  background: #ffffff;
  border: 1px solid #e6d7d2;
  border-radius: 14px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.perfil-sala-item:hover {
  border-color: #cdb3ab;
  box-shadow: 0 6px 18px rgba(74, 35, 40, 0.06);
}

.perfil-item-title {
  font-size: 1.05rem;
  font-weight: 700;
  margin: 0 0 3px 0;
  color: #2c181a;
}

.perfil-item-owner {
  display: inline-flex;
  align-items: center;
  font-size: 0.78rem;
  color: #6b5352;
}

.perfil-item-desc {
  font-size: 0.85rem;
  color: #6b5352;
  margin: 10px 0 14px 0;
  line-height: 1.45;
}

.badge-activa {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: #ecfdf5;
  color: #065f46;
  border: 1px solid #a7f3d0;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 999px;
  white-space: nowrap;
}

.btn-abrir-perfil {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  background: #faf6f4;
  border: 1.5px dashed #8c524f;
  color: #4A2328;
  border-radius: 10px;
  padding: 9px 14px;
  font-weight: 600;
  font-size: 0.85rem;
  cursor: pointer;
  transition: background-color 0.2s, border-color 0.2s;
}

.btn-abrir-perfil:hover {
  background: #f2e6e3;
  border-color: #4A2328;
}

.btn-unirse-perfil {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  width: 100%;
  text-align: center;
  background: #f4be95;
  color: #4A2328;
  border: 1px solid #e6aa7d;
  text-decoration: none;
  border-radius: 10px;
  padding: 9px 14px;
  font-weight: 700;
  font-size: 0.85rem;
  transition: background-color 0.2s;
}

.btn-unirse-perfil:hover {
  background: #fcd5b8;
  color: #4A2328;
}

/* Modal */
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(24, 10, 11, 0.62);
  backdrop-filter: blur(3px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal-card-custom {
  background: #ffffff;
  border: 1px solid #e6d7d2;
  width: 100%;
  max-width: 580px;
  border-radius: 18px;
  padding: 26px;
  box-shadow: 0 22px 48px rgba(0, 0, 0, 0.28);
  max-height: 92vh;
  overflow-y: auto;
}

.modal-header-custom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  border-bottom: 1px solid #eadcd7;
  padding-bottom: 14px;
}

.modal-title-icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background-color: #f4eae7;
  color: #4A2328;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.modal-header-custom h3 {
  margin: 0;
  font-size: 1.18rem;
  font-weight: 700;
  color: #4A2328;
}

.btn-close-modal {
  background: transparent;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b5352;
  cursor: pointer;
  transition: background-color 0.2s, color 0.2s;
}

.btn-close-modal:hover {
  background: #f4eae7;
  color: #4A2328;
}

.form-group-custom {
  margin-bottom: 16px;
}

.form-group-custom label {
  display: block;
  font-size: 0.83rem;
  font-weight: 600;
  margin-bottom: 6px;
  color: #4A2328;
}

.input-custom {
  width: 100%;
  padding: 10px 13px;
  border-radius: 9px;
  border: 1px solid #d5c2bc;
  font-size: 0.9rem;
  color: #2c181a;
  background: #ffffff;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.meet-helper-box {
  background: #faf6f4;
  border: 1px solid #e6d7d2;
  border-radius: 12px;
  padding: 14px;
}

.meet-status-hint {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  font-size: 0.81rem;
  line-height: 1.45;
  color: #5b3b3a;
}

.hint-svg {
  flex-shrink: 0;
  margin-top: 2px;
  color: #612c2d;
}

.meet-input-row {
  display: flex;
  gap: 10px;
  margin-top: 12px;
  flex-wrap: wrap;
}

.meet-url-input {
  flex: 1;
  min-width: 210px;
}

.btn-meet-helper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  background: #4A2328;
  color: #ffffff;
  border: 1px solid #4A2328;
  border-radius: 9px;
  padding: 9px 14px;
  font-size: 0.83rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background-color 0.2s;
}

.btn-meet-helper:hover {
  background: #612c2d;
}

.pomodoro-config-section {
  background: #faf6f4;
  border: 1px solid #e6d7d2;
  border-radius: 12px;
  padding: 15px;
  margin-top: 4px;
}

.pomodoro-config-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.82rem;
  font-weight: 700;
  color: #4A2328;
  margin-bottom: 12px;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.form-row-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

@media (max-width: 520px) {
  .form-row-grid {
    grid-template-columns: 1fr;
  }
}

.modal-footer-custom {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 10px;
  margin-top: 22px;
  padding-top: 14px;
  border-top: 1px solid #eadcd7;
}

.btn-cancelar-modal {
  background: #f4eae7;
  color: #4A2328;
  border: 1px solid #d8c3bd;
  border-radius: 10px;
  padding: 10px 18px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-cancelar-modal:hover {
  background: #e9d9d4;
}
</style>

<style>
/* Dark Mode Overrides para Salas de Estudio (Index.vue) */
body.cn-body-dark .salas-container .salas-title,
body.cn-body-dark .salas-container .section-heading h2,
body.cn-body-dark .salas-container .sala-card-title,
body.cn-body-dark .salas-container .perfil-item-title,
body.cn-body-dark .salas-container .empty-title,
body.cn-body-dark .salas-container .modal-header-custom h3 {
  color: #ffffff !important;
}

body.cn-body-dark .salas-container .salas-subtitle,
body.cn-body-dark .salas-container .sala-card-meta,
body.cn-body-dark .salas-container .perfil-item-owner,
body.cn-body-dark .salas-container .perfil-item-desc,
body.cn-body-dark .salas-container .empty-desc,
body.cn-body-dark .salas-container .pomodoro-pill-sub {
  color: #fcd5b8 !important;
}

body.cn-body-dark .salas-container .sala-card,
body.cn-body-dark .salas-container .perfil-sala-item,
body.cn-body-dark .salas-container .empty-salas-card,
body.cn-body-dark .salas-container .modal-card-custom {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .salas-container .sala-card:hover,
body.cn-body-dark .salas-container .perfil-sala-item:hover {
  border-color: #f4be95 !important;
}

body.cn-body-dark .salas-container .empty-icon-badge,
body.cn-body-dark .salas-container .pomodoro-icon-box,
body.cn-body-dark .salas-container .modal-title-icon {
  background-color: #3b1919 !important;
  color: #f4be95 !important;
  border: 1px solid #7b413f !important;
}

body.cn-body-dark .salas-container .sala-pomodoro-pill,
body.cn-body-dark .salas-container .meet-helper-box,
body.cn-body-dark .salas-container .pomodoro-config-section {
  background-color: #3b1919 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .salas-container .pomodoro-pill-title,
body.cn-body-dark .salas-container .pomodoro-config-header,
body.cn-body-dark .salas-container .meet-status-hint,
body.cn-body-dark .salas-container .form-group-custom label,
body.cn-body-dark .salas-container .link-cronos {
  color: #fcd5b8 !important;
}

body.cn-body-dark .salas-container .hint-svg {
  color: #f4be95 !important;
}

body.cn-body-dark .salas-container .mini-cycle-dot {
  border-color: #8c524f !important;
}

body.cn-body-dark .salas-container .mini-cycle-dot.completed {
  background-color: #f4be95 !important;
  border-color: #f4be95 !important;
}

body.cn-body-dark .salas-container .mini-cycle-dot.current {
  background-color: #e38e76 !important;
  border-color: #fcd5b8 !important;
  box-shadow: 0 0 0 2px rgba(244, 190, 149, 0.25) !important;
}

body.cn-body-dark .salas-container .badge-perfil {
  background-color: #3b1919 !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .salas-container .badge-compartido {
  background-color: rgba(244, 190, 149, 0.14) !important;
  color: #f4be95 !important;
  border-color: rgba(244, 190, 149, 0.35) !important;
}

body.cn-body-dark .salas-container .badge-online,
body.cn-body-dark .salas-container .badge-activa {
  background-color: rgba(16, 185, 129, 0.15) !important;
  color: #6ee7b7 !important;
  border-color: rgba(16, 185, 129, 0.35) !important;
}

body.cn-body-dark .salas-container .btn-crear-sala,
body.cn-body-dark .salas-container .btn-entrar-sala,
body.cn-body-dark .salas-container .btn-unirse-perfil {
  background-color: #f4be95 !important;
  border-color: #f4be95 !important;
  color: #3b1919 !important;
}

body.cn-body-dark .salas-container .btn-crear-sala:hover:not(:disabled),
body.cn-body-dark .salas-container .btn-entrar-sala:hover,
body.cn-body-dark .salas-container .btn-unirse-perfil:hover {
  background-color: #fcd5b8 !important;
  border-color: #fcd5b8 !important;
  color: #3b1919 !important;
}

body.cn-body-dark .salas-container .btn-meet-directo,
body.cn-body-dark .salas-container .btn-meet-helper,
body.cn-body-dark .salas-container .btn-cancelar-modal {
  background-color: #3b1919 !important;
  color: #fcd5b8 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .salas-container .btn-meet-directo:hover,
body.cn-body-dark .salas-container .btn-meet-helper:hover,
body.cn-body-dark .salas-container .btn-cancelar-modal:hover {
  background-color: #542627 !important;
  border-color: #f4be95 !important;
  color: #ffffff !important;
}

body.cn-body-dark .salas-container .btn-abrir-perfil {
  background-color: rgba(59, 25, 25, 0.5) !important;
  border-color: #f4be95 !important;
  color: #f4be95 !important;
}

body.cn-body-dark .salas-container .btn-abrir-perfil:hover {
  background-color: #3b1919 !important;
  color: #ffffff !important;
}

body.cn-body-dark .salas-container .modal-header-custom,
body.cn-body-dark .salas-container .modal-footer-custom {
  border-color: #7b413f !important;
}

body.cn-body-dark .salas-container .btn-close-modal {
  color: #fcd5b8 !important;
}

body.cn-body-dark .salas-container .btn-close-modal:hover {
  background-color: #3b1919 !important;
  color: #ffffff !important;
}

body.cn-body-dark .salas-container .input-custom {
  background-color: #2f1212 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}
</style>
