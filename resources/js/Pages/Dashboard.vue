<template>
  <AppLayout>
    <div class="dashboard-page">
      <!-- Top Bar -->

      <!-- Main Content Area -->
      <main class="main-content">
        <h1 class="welcome-title">¡Bienvenida, {{ $page.props.auth.user.nombre }}!</h1>

        <!-- Profiles Section -->
        <div class="section-header">
          <div>
            <h2 class="section-title">Tus perfiles</h2>
            <p class="section-subtitle">Seguí organizando y avanzando en tus lugares de trabajo.</p>
          </div>
          
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
            <h3 class="profile-name">¡Aún no tienes perfiles creados!</h3>
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
                  <div class="streak-number">{{ estadisticas.rachaActual || 0 }}</div>
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
                  <Doughnut v-if="chartDataPerfil && chartDataPerfil.length > 0" :data="doughnutChartData" :options="doughnutChartOptions" />
                  <div v-else class="no-data-text">No hay sesiones de pomodoro registradas.</div>
                </div>
              </div>
            </div>

            <!-- Bar Chart Card -->
            <div class="bar-chart-card">
              <h3 class="bar-chart-title">Horas de estudio por día</h3>
              <div class="bar-chart-container" v-if="chartDataSemana && chartDataSemana.length > 0">
                <Bar :data="barChartData" :options="barChartOptions" />
              </div>
              <div v-else class="no-data-text" style="display: flex; align-items: center; justify-content: center; height: 100%;">
                No hay horas de estudio registradas esta semana.
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
import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

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
  },
  chartDataSemana: {
    type: Array,
    default: () => []
  },
  chartDataPerfil: {
    type: Array,
    default: () => []
  }
});

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
  router.visit('/gestion-perfil');
};

const formatDate = (date) => {
  if (!date) return '';
  const dateStr = date.split(/[ T]/)[0];
  const [year, month, day] = dateStr.split('-');
  const d = new Date(year, month - 1, day);
  return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const barChartData = computed(() => {
  const data = [...props.chartDataSemana].reverse();
  return {
    labels: data.map(d => d.fecha),
    datasets: [{
      label: 'Horas de estudio',
      backgroundColor: '#612c2d', // Color de marca del tema claro
      data: data.map(d => d.horas),
      borderRadius: 4
    }]
  }
});

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: 'rgba(0, 0, 0, 0.05)' },
      ticks: { color: '#666' }
    },
    x: {
      grid: { display: false },
      ticks: { color: '#666' }
    }
  },
  plugins: {
    legend: { display: false }
  }
};

const doughnutChartData = computed(() => {
  return {
    labels: props.chartDataPerfil.map(d => d.perfil),
    datasets: [{
      backgroundColor: props.chartDataPerfil.map((d, index) => {
        const colorPalette = ['#612c2d', '#c4a5a5', '#e5d5d5', '#8b4c4c', '#531d55'];
        return d.color || colorPalette[index % colorPalette.length];
      }),
      data: props.chartDataPerfil.map(d => d.horas),
      borderWidth: 0
    }]
  }
});

const doughnutChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
      labels: { color: '#333' }
    }
  }
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
  color: #612c2d;
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
  justify-content: center;
  height: 150px;
  position: relative;
}

.no-data-text {
  font-size: 13px;
  color: #666;
  text-align: center;
}

/* Bar Chart Card */
.bar-chart-card {
  background-color: #fff;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #e5e5e5;
  display: flex;
  flex-direction: column;
}

.bar-chart-title {
  font-size: 15px;
  font-weight: 600;
  color: #333;
}

.bar-chart-container {
  height: 230px;
  position: relative;
  margin-top: 10px;
}

.range-select {
  border: 1px solid #e5e5e5;
  border-radius: 6px;
  padding: 4px 8px;
  font-size: 13px;
  color: #333;
  background-color: #fff;
  cursor: pointer;
  outline: none;
}

.range-select:focus {
  border-color: #612c2d;
}

/* Tasks styles */
.task-card-light {
  background-color: #fff;
  border-radius: 12px;
  padding: 20px;
  border: 1px solid #e5e5e5;
  display: flex;
  flex-direction: column;
  gap: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s, box-shadow 0.2s;
}

.task-card-light:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.add-profile-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 2px dashed #c4a5a5;
  background-color: transparent;
  color: #612c2d;
}

.add-profile-card:hover {
  background-color: rgba(97, 44, 45, 0.02);
  border-color: #612c2d;
}

.add-icon {
  margin-bottom: 8px;
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