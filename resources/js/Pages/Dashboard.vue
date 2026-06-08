<template>
  <AppLayout>
    <div class="dashboard-page">
      <!-- Top Bar -->
      <div class="topbar">
        <div class="topbar-left">
          <span>{{ currentFormattedDate }}</span>
        </div>
        <div class="topbar-right">
          <span class="user-fullname">{{ $page.props.auth.user.nombre }} {{ $page.props.auth.user.apellido }}</span>
        </div>
      </div>

      <!-- Main Content Area -->
      <main class="main-content">
        <h1 class="welcome-title">¡Bienvenida, {{ $page.props.auth.user.nombre }}!</h1>

        <!-- Profiles Section -->
        <div class="section-header">
          <div>
            <h2 class="section-title">Tus perfiles</h2>
            <p class="section-subtitle">Seguí organizando y avanzando en tus lugares de trabajo.</p>
          </div>
          <button class="add-button" @click="irAPerfiles">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Agregar perfil
          </button>
        </div>

        <div class="profiles-grid">
          <!-- Perfiles Dinámicos -->
          <div 
            v-for="perfil in perfiles" 
            :key="perfil.idPerfil" 
            class="profile-card"
            :class="{ 'active-card': perfilActivo && perfilActivo.idPerfil === perfil.idPerfil }"
            @click="seleccionarPerfil(perfil.idPerfil)"
          >
            <div class="profile-icon">
              <!-- Icono dinámico según el índice o nombre, o uno por defecto -->
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                <polyline points="2 17 12 22 22 17"/>
                <polyline points="2 12 12 17 22 12"/>
              </svg>
            </div>
            <h3 class="profile-name">{{ perfil.tituloPerfil }}</h3>
            <p class="profile-date">
              {{ perfilActivo && perfilActivo.idPerfil === perfil.idPerfil ? '🟢 Perfil Activo' : 'Haz clic para seleccionar' }}
            </p>
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: (animateProgress ? calcularProgreso(perfil) : 0) + '%' }"></div>
            </div>
          </div>

          <!-- Mensaje cuando no hay perfiles creados -->
          <div class="profile-card add-profile-card" @click="irAPerfiles" v-if="perfiles.length === 0">
            <div class="profile-icon add-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
            </div>
            <h3 class="profile-name">Crea tu primer perfil</h3>
            <p class="profile-date">Comienza a organizar tus tareas</p>
          </div>
        </div>

        <!-- Statistics Section -->
        <div class="stats-section">
          <div class="section-header">
            <div>
              <h2 class="section-title">Tus estadísticas</h2>
              <p class="section-subtitle">Notamos y acompañamos tu esfuerzo. ¡Seguí así!</p>
            </div>
          </div>

          <div class="stats-grid">
            <div class="stats-left">
              <!-- Streak Card -->
              <div class="streak-card">
                <div>
                  <div class="streak-number">{{ estadisticas.rachaActual }}</div>
                  <div class="streak-label">días de racha</div>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="streak-icon">
                  <path d="M12 3q1 4 4 6.5t3 5.5a1 1 0 0 1-14 0 5 5 0 0 1 1-3 1 1 0 0 0 5 0c0-2-1.5-3-1.5-5q0-2 2.5-4"/>
                </svg>
              </div>

              <!-- Donut Chart Card -->
              <div class="donut-card">
                <h3 class="donut-card-title">Horas de pomodoro por perfil</h3>
                <div class="donut-content">
                  <svg class="donut-chart" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.915" fill="none" stroke="#e5d5d5" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.915" fill="none" stroke="#c4a5a5" stroke-width="3" stroke-dasharray="30 70" stroke-dashoffset="0"/>
                    <circle cx="18" cy="18" r="15.915" fill="none" stroke="#8b4c4c" stroke-width="3" stroke-dasharray="50 50" stroke-dashoffset="-30"/>
                  </svg>
                  <div class="donut-legend">
                    <div class="legend-item">
                      <span class="legend-dot a"></span>
                      Perfil A
                    </div>
                    <div class="legend-item">
                      <span class="legend-dot b"></span>
                      Perfil B
                    </div>
                    <div class="legend-item">
                      <span class="legend-dot c"></span>
                      Perfil C
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bar Chart Card -->
            <div class="bar-chart-card">
              <h3 class="bar-chart-title">Horas de estudio por día</h3>
              <div class="bar-chart">
                <div class="bar-chart-y-axis">
                  <span>4</span>
                  <span>3</span>
                  <span>2</span>
                  <span>1</span>
                  <span>0</span>
                </div>
                <div class="bar" :style="{ height: (animateProgress ? 75 : 0) + '%' }"></div>
                <div class="bar" :style="{ height: (animateProgress ? 82 : 0) + '%' }"></div>
                <div class="bar" :style="{ height: (animateProgress ? 80 : 0) + '%' }"></div>
                <div class="bar" :style="{ height: (animateProgress ? 70 : 0) + '%' }"></div>
                <div class="bar" :style="{ height: (animateProgress ? 90 : 0) + '%' }"></div>
                <div class="bar" :style="{ height: (animateProgress ? 60 : 0) + '%' }"></div>
                <div class="bar" :style="{ height: (animateProgress ? 70 : 0) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Props recibidos desde DashboardController
const props = defineProps({
  perfiles: {
    type: Array,
    default: () => []
  },
  perfilActivo: {
    type: Object,
    default: null
  },
  tareas: {
    type: Array,
    default: () => []
  },
  estadisticas: {
    type: Object,
    default: () => ({
      rachaActual: 0,
      rachaMasLarga: 0,
      tareasTotales: 0,
      tiempoTotalPomodoro: 0,
      horasConcentracionDiaria: 0
    })
  }
});

// Control de animaciones
const animateProgress = ref(false);

onMounted(() => {
  setTimeout(() => {
    animateProgress.value = true;
  }, 150);
});

// Formatear la fecha actual para el topbar
const currentFormattedDate = computed(() => {
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  const date = new Date();
  return date.toLocaleDateString('es-ES', options);
});

// Calcular el progreso del perfil basándose en tareas completadas
const calcularProgreso = (perfil) => {
  if (!perfil.tareas_count) return 0;
  return Math.round((perfil.tareas_completadas_count / perfil.tareas_count) * 100);
};

// Acción para activar un perfil
const seleccionarPerfil = (idPerfil) => {
  router.post(route('perfiles.activar'), { idPerfil: idPerfil });
};

// Redireccionar a la gestión de perfiles
const irAPerfiles = () => {
  router.visit(route('perfiles.index'));
};
</script>

<style scoped>
/* Contenedor principal de la página con fondo claro */
.dashboard-page {
  background-color: #f5f5f5;
  color: #333;
  min-height: 100vh;
  margin: -20px; /* Anula el padding por defecto del contenedor del content */
  display: flex;
  flex-direction: column;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Top Bar */
.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 32px;
  background-color: #fff;
  border-bottom: 1px solid #e5e5e5;
}

.topbar-left {
  font-size: 14px;
  font-weight: 500;
  color: #666;
  text-transform: capitalize;
}

.topbar-right {
  font-size: 14px;
  font-weight: 600;
  color: #333;
}

/* Main Content Area */
.main-content {
  padding: 32px;
  flex: 1;
}

.welcome-title {
  font-size: 28px;
  font-weight: 700;
  color: #333;
  margin-bottom: 32px;
}

/* Profiles Section */
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #612c2d;
}

.section-subtitle {
  font-size: 13px;
  color: #666;
  margin-top: 4px;
}

.add-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background-color: #612c2d;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.add-button:hover {
  background-color: #723f3f;
}

.add-button svg {
  width: 18px;
  height: 18px;
}

/* Profile Cards */
.profiles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}

.profile-card {
  background-color: #fff;
  border-radius: 12px;
  padding: 20px;
  border: 1px solid #e5e5e5;
  cursor: pointer;
  transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
}

.profile-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.profile-card.active-card {
  border-color: #612c2d;
  box-shadow: 0 2px 8px rgba(97, 44, 45, 0.15);
}

.profile-icon {
  width: 48px;
  height: 48px;
  border: 2px solid #e5e5e5;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  color: #612c2d;
}

.profile-icon svg {
  width: 24px;
  height: 24px;
}

.profile-name {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 4px;
}

.profile-date {
  font-size: 12px;
  color: #612c2d;
  margin-bottom: 16px;
}

.progress-bar {
  height: 8px;
  background-color: #e5e5e5;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background-color: #612c2d;
  border-radius: 4px;
  transition: width 1s ease-out;
}

/* Statistics Section */
.stats-section {
  margin-top: 16px;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 20px;
  margin-top: 20px;
}

.stats-left {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Streak Card */
.streak-card {
  background-color: #fff;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #e5e5e5;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.streak-number {
  font-size: 48px;
  font-weight: 700;
  color: #612c2d;
}

.streak-label {
  font-size: 14px;
  color: #666;
}

.streak-icon {
  width: 64px;
  height: 64px;
  color: #612c2c;
}

/* Donut Chart Card */
.donut-card {
  background-color: #fff;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #e5e5e5;
}

.donut-card-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 20px;
}

.donut-content {
  display: flex;
  align-items: center;
  gap: 24px;
}

.donut-chart {
  width: 120px;
  height: 120px;
  position: relative;
}

.donut-legend {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #333;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.legend-dot.a { background-color: #612c2d; }
.legend-dot.b { background-color: #c4a5a5; }
.legend-dot.c { background-color: #e5d5d5; }

/* Bar Chart Card */
.bar-chart-card {
  background-color: #fff;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #e5e5e5;
  height: 100%;
}

.bar-chart-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
  margin-bottom: 20px;
  text-align: center;
}

.bar-chart {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  height: 200px;
  padding: 10px 0;
  border-left: 1px solid #e5e5e5;
  border-bottom: 1px solid #e5e5e5;
  position: relative;
}

.bar-chart-y-axis {
  position: absolute;
  left: -10px;
  top: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  font-size: 12px;
  color: #999;
}

.bar {
  width: 32px;
  background-color: #612c2d;
  border-radius: 4px 4px 0 0;
  transition: height 1s ease-out;
}

/* Responsive */
@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .main-wrapper {
    margin-left: 0;
  }

  .profiles-grid {
    grid-template-columns: 1fr;
  }
}
</style>