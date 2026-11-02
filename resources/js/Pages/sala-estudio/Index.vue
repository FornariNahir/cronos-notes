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
        <div>
          <h1 class="salas-title">Salas de Estudio Virtuales</h1>
          <p class="salas-subtitle">
            Espacios de concentración grupal (<em>Body Doubling</em>) con temporizador Pomodoro sincronizado y videollamada en Google Meet.
          </p>
        </div>
        <button
          v-if="perfiles.length > 0"
          type="button"
          class="btn-crear-sala"
          @click="abrirModalCrear()"
        >
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2">
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Crear Sala de Estudio
        </button>
      </div>

      <!-- Sección 1: Salas Activas en este momento -->
      <section class="mb-5">
        <div class="section-heading">
          <span class="live-dot"></span>
          <h2>Salas Activas Ahora ({{ salasActivas.length }})</h2>
        </div>

        <div v-if="salasActivas.length === 0" class="empty-salas-card card">
          <div class="empty-icon">🎧</div>
          <h3>No hay salas de estudio activas en este momento</h3>
          <p class="text-muted">
            Iniciá una sala en cualquiera de tus perfiles (personales o compartidos) para estudiar en simultáneo con tu equipo.
          </p>
          <button
            v-if="perfiles.length > 0"
            type="button"
            class="btn-crear-sala mt-2"
            @click="abrirModalCrear()"
          >
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
                <span v-if="sala.esCompartido" class="badge-compartido">👥 Equipo</span>
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
              <span class="pomodoro-icon">🍅</span>
              <div>
                <div class="pomodoro-pill-title">
                  {{ formatFase(sala.pomodoro?.fase) }} · Ciclo {{ sala.pomodoro?.cicloActual || 1 }}/{{ sala.pomodoro?.ciclosObjetivo || 4 }}
                </div>
                <div class="pomodoro-pill-sub">
                  {{ sala.pomodoro?.corriendo ? '⏱ Reloj grupal en curso' : '⏸ Reloj en espera' }}
                  ({{ sala.pomodoro?.duracionTrabajo || 25 }}m / {{ sala.pomodoro?.duracionDescansoCorto || 5 }}m)
                </div>
              </div>
            </div>

            <div class="sala-card-actions">
              <Link :href="route('salas.show', sala.idSalaEstudio)" class="btn-entrar-sala">
                Entrar a la Sala
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
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
                📹 Meet
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- Sección 2: Tus Perfiles y Equipos -->
      <section>
        <div class="section-heading">
          <h2>Tus Perfiles y Equipos de Trabajo</h2>
        </div>

        <div v-if="perfiles.length === 0" class="empty-salas-card card">
          <p>Primero debés crear un perfil en la sección <Link :href="route('gestion-perfil')">Mis Perfiles</Link>.</p>
        </div>

        <div v-else class="perfiles-salas-grid">
          <div
            v-for="p in perfiles"
            :key="p.idPerfil"
            class="perfil-sala-item card"
          >
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <h4 class="perfil-item-title">{{ p.tituloPerfil }}</h4>
                <span class="perfil-item-owner">
                  {{ p.esCompartido ? `Compartido por ${p.propietarioNombre}` : 'Perfil propio' }}
                  <template v-if="p.usuarios_compartidos_count > 0">
                    · {{ p.usuarios_compartidos_count + 1 }} miembros
                  </template>
                </span>
              </div>
              <span v-if="salasPorPerfilMap[p.idPerfil]" class="badge-activa">Sala Activa</span>
            </div>

            <p class="perfil-item-desc text-muted">
              {{ p.descripcionPerfil || 'Espacio de organización y estudio.' }}
            </p>

            <div class="mt-auto pt-2">
              <Link
                v-if="salasPorPerfilMap[p.idPerfil]"
                :href="route('salas.show', salasPorPerfilMap[p.idPerfil].idSalaEstudio)"
                class="btn-unirse-perfil"
              >
                Unirse a la Sala Activa →
              </Link>
              <button
                v-else
                type="button"
                class="btn-abrir-perfil"
                @click="abrirModalCrear(p.idPerfil, p.tituloPerfil)"
              >
                + Abrir Sala en este Perfil
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- Modal Crear Sala de Estudio -->
      <div v-if="showCrearModal" class="modal-backdrop-custom" @click.self="cerrarModalCrear">
        <div class="modal-card-custom card">
          <div class="modal-header-custom">
            <h3>Configurar Nueva Sala de Estudio</h3>
            <button type="button" class="btn-close-modal" @click="cerrarModalCrear">&times;</button>
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
                  ✅ Tenés tu cuenta de Google vinculada. Si dejás el campo vacío, Cronos intentará generar el link automáticamente (o podés crear/pegar uno debajo).
                </div>
                <div v-else class="meet-status-hint">
                  💡 Podés generar una sala instantánea en Google Meet con el botón de abajo y pegar el link o código aquí (también podrás actualizarlo desde adentro de la sala).
                </div>

                <div class="d-flex gap-2 mt-2 flex-wrap">
                  <input
                    type="text"
                    v-model="form.enlaceMeet"
                    class="input-custom"
                    style="flex: 1; min-width: 200px;"
                    placeholder="Opcional: https://meet.google.com/abc-defg-hij o código"
                  />
                  <button
                    type="button"
                    class="btn-meet-helper"
                    @click="abrirMeetInstantaneo"
                    title="Abre Google Meet en una pestaña nueva para copiar el link de la reunión"
                  >
                    📹 Abrir meet.google.com/new
                  </button>
                </div>
              </div>
            </div>

            <div class="form-row-grid">
              <div class="form-group-custom">
                <label>Trabajo (min)</label>
                <input type="number" v-model.number="form.duracionTrabajo" class="input-custom" min="1" max="120" required />
              </div>
              <div class="form-group-custom">
                <label>Descanso Corto (min)</label>
                <input type="number" v-model.number="form.duracionDescansoCorto" class="input-custom" min="1" max="30" required />
              </div>
              <div class="form-group-custom">
                <label>Descanso Largo (min)</label>
                <input type="number" v-model.number="form.duracionDescansoLargo" class="input-custom" min="5" max="60" required />
              </div>
              <div class="form-group-custom">
                <label>Ciclos Objetivo</label>
                <input type="number" v-model.number="form.ciclosObjetivo" class="input-custom" min="1" max="10" required />
              </div>
            </div>

            <div class="modal-footer-custom">
              <button type="button" class="btn-cancelar-modal" @click="cerrarModalCrear">Cancelar</button>
              <button type="submit" class="btn-crear-sala" :disabled="form.processing">
                {{ form.processing ? 'Creando Sala...' : 'Crear e Ingresar a la Sala' }}
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
  max-width: 1200px;
  margin: 0 auto;
  padding-bottom: 40px;
}

.salas-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 28px;
}

.salas-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: #4d2323;
  margin: 0 0 4px 0;
}

.salas-subtitle {
  color: #666;
  margin: 0;
  font-size: 0.95rem;
}

.btn-crear-sala {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background-color: #612c2d;
  color: #ffffff;
  border: none;
  border-radius: 10px;
  padding: 11px 20px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.15s;
  box-shadow: 0 4px 12px rgba(97, 44, 45, 0.25);
}

.btn-crear-sala:hover {
  background-color: #7b413f;
  transform: translateY(-1px);
}

.section-heading {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}

.section-heading h2 {
  font-size: 1.2rem;
  font-weight: 700;
  color: #4d2323;
  margin: 0;
}

.live-dot {
  width: 10px;
  height: 10px;
  background-color: #10b981;
  border-radius: 50%;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
}

.empty-salas-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 36px 24px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.empty-icon {
  font-size: 2.5rem;
  margin-bottom: 8px;
}

.salas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
  gap: 20px;
}

.sala-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;
}

.sala-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(0, 0, 0, 0.09);
}

.sala-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.badge-perfil {
  background-color: #f3e8e6;
  color: #612c2d;
  font-weight: 700;
  font-size: 0.78rem;
  padding: 4px 10px;
  border-radius: 999px;
}

.badge-compartido {
  background-color: #fef3c7;
  color: #92400e;
  font-weight: 600;
  font-size: 0.75rem;
  padding: 3px 9px;
  border-radius: 999px;
}

.badge-online {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  font-weight: 600;
  color: #047857;
  background: #ecfdf5;
  padding: 4px 10px;
  border-radius: 999px;
}

.pulse-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
}

.sala-card-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #333;
  margin: 0 0 4px 0;
}

.sala-card-meta {
  font-size: 0.83rem;
  color: #6b7280;
  margin-bottom: 14px;
}

.sala-pomodoro-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  background: #fdfaf8;
  border: 1px solid #eadcd7;
  border-radius: 10px;
  padding: 10px 12px;
  margin-bottom: 16px;
}

.pomodoro-icon {
  font-size: 1.35rem;
}

.pomodoro-pill-title {
  font-size: 0.85rem;
  font-weight: 700;
  color: #4d2323;
}

.pomodoro-pill-sub {
  font-size: 0.76rem;
  color: #6b7280;
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
  gap: 6px;
  background-color: #612c2d;
  color: #ffffff;
  text-decoration: none;
  padding: 10px 16px;
  border-radius: 9px;
  font-weight: 600;
  font-size: 0.9rem;
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
  background-color: #f3f4f6;
  color: #1f2937;
  text-decoration: none;
  padding: 10px 14px;
  border-radius: 9px;
  font-weight: 600;
  font-size: 0.88rem;
  border: 1px solid #d1d5db;
  transition: background-color 0.2s;
}

.btn-meet-directo:hover {
  background-color: #e5e7eb;
}

.perfiles-salas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.perfil-sala-item {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 18px;
  display: flex;
  flex-direction: column;
}

.perfil-item-title {
  font-size: 1.05rem;
  font-weight: 700;
  margin: 0;
  color: #333;
}

.perfil-item-owner {
  font-size: 0.78rem;
  color: #6b7280;
}

.perfil-item-desc {
  font-size: 0.85rem;
  margin: 8px 0 12px 0;
}

.badge-activa {
  background: #dcfce7;
  color: #166534;
  font-size: 0.73rem;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 999px;
}

.btn-abrir-perfil {
  width: 100%;
  background: transparent;
  border: 1.5px dashed #7b413f;
  color: #612c2d;
  border-radius: 8px;
  padding: 8px 12px;
  font-weight: 600;
  font-size: 0.86rem;
  cursor: pointer;
  transition: 0.2s;
}

.btn-abrir-perfil:hover {
  background: rgba(123, 65, 63, 0.08);
}

.btn-unirse-perfil {
  display: block;
  width: 100%;
  text-align: center;
  background: #f4be95;
  color: #4d2323;
  text-decoration: none;
  border-radius: 8px;
  padding: 8px 12px;
  font-weight: 700;
  font-size: 0.86rem;
}

/* Modal */
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal-card-custom {
  background: #ffffff;
  width: 100%;
  max-width: 560px;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25);
}

.modal-header-custom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 12px;
}

.modal-header-custom h3 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 700;
  color: #4d2323;
}

.btn-close-modal {
  background: transparent;
  border: none;
  font-size: 1.6rem;
  line-height: 1;
  color: #666;
  cursor: pointer;
}

.form-group-custom {
  margin-bottom: 14px;
}

.form-group-custom label {
  display: block;
  font-size: 0.84rem;
  font-weight: 600;
  margin-bottom: 5px;
  color: #374151;
}

.input-custom {
  width: 100%;
  padding: 9px 12px;
  border-radius: 8px;
  border: 1px solid #d1d5db;
  font-size: 0.9rem;
  color: #1f2937;
  background: #ffffff;
}

.meet-helper-box {
  background: #fdfaf8;
  border: 1px solid #eadcd7;
  border-radius: 10px;
  padding: 10px 12px;
}

.meet-status-hint {
  font-size: 0.8rem;
  color: #5b3b3a;
}

.btn-meet-helper {
  background: #4d2323;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}

.btn-meet-helper:hover {
  background: #7b413f;
}

.form-row-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.modal-footer-custom {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
}

.btn-cancelar-modal {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 9px 16px;
  font-weight: 600;
  cursor: pointer;
}

/* Dark Mode Overrides */
:global(body.cn-body-dark) .sala-pomodoro-pill,
:global(body.cn-body-dark) .meet-helper-box {
  background-color: #3b1919 !important;
  border-color: #7b413f !important;
}

:global(body.cn-body-dark) .pomodoro-pill-title,
:global(body.cn-body-dark) .meet-status-hint,
:global(body.cn-body-dark) .form-group-custom label {
  color: #fcd5b8 !important;
}

:global(body.cn-body-dark) .btn-abrir-perfil {
  border-color: #f4be95 !important;
  color: #f4be95 !important;
}

:global(body.cn-body-dark) .btn-crear-sala,
:global(body.cn-body-dark) .btn-entrar-sala {
  background-color: #f4be95 !important;
  color: #3b1919 !important;
}
</style>
