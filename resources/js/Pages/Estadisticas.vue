<script setup>
import { computed, ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
  rachaMasLarga: Number,
  rachaActual: Number,
  tiempoTotalPomodoro: Number,
  tareasCreadas: Number,
  tareasCompletadas: Number,
  tareasRetrasadas: Number,
  eficiencia: Number,
  chartDataSemana: Array,
  chartDataPerfil: Array,
});

const isDarkMode = ref(false);

// Control de animaciones y MutationObserver para modo oscuro
const animateProgress = ref(false);

onMounted(() => {
  isDarkMode.value = document.body.classList.contains('cn-body-dark') || localStorage.getItem('cn-theme') === 'dark';

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        isDarkMode.value = document.body.classList.contains('cn-body-dark');
      }
    });
  });
  observer.observe(document.body, { attributes: true });

  setTimeout(() => {
    animateProgress.value = true;
  }, 100);
});

// Lógica de Gráficos de Dashboard
const maxBarHours = computed(() => {
  if (!props.chartDataSemana || props.chartDataSemana.length === 0) return 4;
  return Math.max(4, ...props.chartDataSemana.map(d => Math.ceil(d.horas)));
});

const chartDataPerfilConColores = computed(() => {
  if (!props.chartDataPerfil) return [];
  const lightPalette = ['#612c2d', '#c4a5a5', '#e5d5d5', '#8b4c4c', '#531d55'];
  const darkPalette = ['#f4be95', '#f9dac3', '#fbe8da', '#cf997b', '#aa7561'];
  const palette = isDarkMode.value ? darkPalette : lightPalette;
  
  return props.chartDataPerfil.map((p, index) => {
    return {
      ...p,
      color: p.color || palette[index % palette.length]
    };
  });
});

const totalHorasPerfil = computed(() => {
  return chartDataPerfilConColores.value.reduce((sum, p) => sum + p.horas, 0);
});

const getDashArray = (horas, total) => {
  if (total === 0) return 0;
  return (horas / total) * 100;
};

const getDashOffset = (index) => {
  let offset = 0;
  for (let i = 0; i < index; i++) {
    offset += getDashArray(chartDataPerfilConColores.value[i].horas, totalHorasPerfil.value);
  }
  return 25 - offset; 
};
</script>

<template>
  <Head title="Estadísticas" />
  <AppLayout>
    <div class="main-layout-container fade-in">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold m-0" style="color: var(--text-marron-institucional);">Estadísticas de {{ $page.props.auth.user.nombre }} </h1>
      </div>
      <div class="d-flex justify-content-between align-items-center mb-4" style="margin-top: -20px;">
        <h2 class="h3 m-0" style="font-size: 15px; font-weight: 500; color: #a55e57;">Monitoreo detallado de tu actividad y hábitos de enfoque.</h2>
      </div>

      <!-- Fila Superior: Resumen Rápido -->
      <div class="summary-grid">
        <div class="stat-card">
          <div class="stat-icon racha-icon"></div>
          <div class="stat-info">
            <p class="stat-label">Tareas Completadas</p>
            <h4 class="stat-value">{{ tareasCompletadas }}</h4>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon racha-max-icon"></div>
          <div class="stat-info">
            <p class="stat-label">Finalizadas con Retraso</p>
            <h4 class="stat-value">{{ tareasRetrasadas }}</h4>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon time-icon"></div>
          <div class="stat-info">
            <p class="stat-label">Tiempo total de Concentración</p>
            <h4 class="stat-value">{{ tiempoTotalPomodoro }} minutos</h4>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon eff-icon"></div>
          <div class="stat-info">
            <p class="stat-label">Racha más larga</p>
            <h4 class="stat-value">{{ rachaMasLarga }} días</h4>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon eff-icon"></div>
          <div class="stat-info">
            <p class="stat-label">Racha actual</p>
            <h4 class="stat-value">{{ rachaActual }} días</h4>
          </div>
        </div>
      </div>

      <!-- Fila Media: Gráficos de Hábitos (Dashboard style) -->
      <div class="charts-grid mt-4">
        <!-- Donut Chart -->
        <div class="chart-card">
          <h3 class="chart-title">Horas de pomodoro por perfil</h3>
          <div class="donut-content" v-if="animateProgress && chartDataPerfilConColores && chartDataPerfilConColores.length > 0">
            <svg class="donut-chart" viewBox="0 0 36 36">
              <circle cx="18" cy="18" r="15.915" fill="none" :stroke="isDarkMode ? '#612c2d' : '#e5d5d5'" stroke-width="3"/>
              <circle 
                v-for="(perfil, index) in chartDataPerfilConColores"
                :key="index"
                cx="18" cy="18" r="15.915" fill="none" 
                :stroke="perfil.color" stroke-width="3" 
                :stroke-dasharray="getDashArray(perfil.horas, totalHorasPerfil) + ' ' + (100 - getDashArray(perfil.horas, totalHorasPerfil))" 
                :stroke-dashoffset="getDashOffset(index)"
              />
            </svg>
            <div class="donut-legend">
              <div class="legend-item" v-for="(perfil, index) in chartDataPerfilConColores" :key="'leg-'+index">
                <span class="legend-dot" :style="{ backgroundColor: perfil.color }"></span>
                {{ perfil.perfil }}: {{ perfil.horas }}h ({{ perfil.sesiones }} sesiones)
              </div>
            </div>
          </div>
          <div class="donut-content" v-else-if="!animateProgress">
            <p class="text-muted" style="font-size: 14px; text-align: center; width: 100%;">Cargando gráfico...</p>
          </div>
          <div class="donut-content" v-else>
            <p class="text-muted" style="font-size: 14px; text-align: center; width: 100%;">No hay horas registradas aún</p>
          </div>
        </div>

        <!-- Bar Chart -->
        <div class="chart-card">
          <h3 class="chart-title">Horas de estudio por día</h3>
          <div class="bar-chart" v-if="animateProgress && chartDataSemana && chartDataSemana.length > 0">
            <div class="bar-chart-y-axis">
              <span>{{ maxBarHours }}</span>
              <span>{{ (maxBarHours * 0.75).toFixed(1) }}</span>
              <span>{{ (maxBarHours * 0.5).toFixed(1) }}</span>
              <span>{{ (maxBarHours * 0.25).toFixed(1) }}</span>
              <span>0</span>
            </div>
            <div 
              v-for="(dia, index) in chartDataSemana" 
              :key="index"
              class="bar" 
              :style="{ height: (animateProgress ? ((dia.horas / maxBarHours) * 100) : 0) + '%' }"
              :title="dia.fecha + ' - ' + dia.horas + ' hrs'"
            >
              <div class="bar-date">{{ dia.fecha }}</div>
            </div>
          </div>
          <div v-else-if="!animateProgress" class="d-flex align-items-center justify-content-center h-100 text-muted small" style="min-height: 150px;">
            Cargando gráfico...
          </div>
          <div v-else class="d-flex align-items-center justify-content-center h-100 text-muted small" style="min-height: 150px;">
            No hay horas registradas esta semana
          </div>
        </div>
      </div>

      <!-- Fila Inferior: Comparación de Tareas (Old style) -->
      <div class="charts-grid mt-4 mb-5">
        <div class="chart-card">
          <h3 class="chart-title">Comparativa de Tareas</h3>
          <div class="comparison-chart">
            <div class="comparison-item">
              <div class="comparison-label">Creadas</div>
              <div class="comparison-bar" style="height: 160px;">
                <div class="comparison-fill comparison-fill-created" :style="{ height: (animateProgress ? Math.min(100, (tareasCreadas / Math.max(1, tareasCreadas)) * 100) : 0) + '%' }">
                  <div class="comparison-number" v-if="animateProgress">{{ tareasCreadas }}</div>
                </div>
              </div>
            </div>

            <div class="comparison-item">
              <div class="comparison-label">Completadas</div>
              <div class="comparison-bar" style="height: 160px;">
                <div class="comparison-fill comparison-fill-completed" :style="{ height: (animateProgress ? Math.min(100, (tareasCompletadas / Math.max(1, tareasCreadas)) * 100) : 0) + '%' }">
                  <div class="comparison-number" v-if="animateProgress">{{ tareasCompletadas }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="chart-card">
          <h3 class="chart-title">Desempeño y Retrasos</h3>
          <div class="circular-progress">
            <!-- Creadas -->
            <div class="circular-item">
              <div class="circular-chart">
                <div class="circular-bg base-color" :style="'--percentage: ' + (animateProgress ? 100 : 0) + ';'" :data-value="tareasCreadas"></div>
              </div>
              <div class="circular-label">Total Creadas</div>
            </div>
            
            <!-- Eficiencia -->
            <div class="circular-item">
              <div class="circular-chart">
                <div class="circular-bg eff-color" :style="'--percentage: ' + (animateProgress ? eficiencia : 0) + ';'" :data-value="eficiencia + '%'"></div>
              </div>
              <div class="circular-label">Eficiencia</div>
            </div>

            <!-- Retrasadas -->
            <div class="circular-item">
              <div class="circular-chart">
                <div class="circular-bg delay-color" :style="'--percentage: ' + (animateProgress ? Math.min(100, (tareasRetrasadas / Math.max(1, tareasCreadas)) * 100) : 0) + ';'" :data-value="tareasRetrasadas"></div>
              </div>
              <div class="circular-label">Retrasos</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style>
/* CSS Variables for Institutional Colors */
:root {
    --text-marron-institucional: #69342e;
    --borde-card-color: #dee2e6;
}

.main-layout-container {
    width: 100%;
    max-width: 1140px;
    margin: 0 auto;
    padding: 20px 0;
}

.fade-in {
    animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.mt-4 { margin-top: 1.5rem; }
.mb-4 { margin-bottom: 1.5rem; }
.mb-5 { margin-bottom: 3rem; }

/* Summary Grid */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.stat-card {
    background-color: #fff;
    border: 1px solid var(--borde-card-color);
    border-radius: 12px;
    padding: 20px 1px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: #fdf8f7;
}

.stat-info .stat-value {
    margin: 0;
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--text-marron-institucional);
}

.stat-info .stat-label {
    margin: 0;
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 500;
}

/* Charts Grid */
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.chart-card {
    background-color: #fff;
    border: 1px solid var(--borde-card-color);
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.chart-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 25px;
    border-bottom: 1px solid #f1f3f5;
    padding-bottom: 12px;
}

/* Donut Chart (Inherited from Dashboard) */
.donut-content {
    display: flex;
    align-items: center;
    justify-content: space-around;
}
.donut-chart {
    width: 140px;
    height: 140px;
    transform: rotate(-90deg);
}
.donut-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: #555;
    font-weight: 500;
}
.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

/* Bar Chart (Inherited from Dashboard) */
.bar-chart {
    display: flex;
    align-items: flex-end;
    height: 150px;
    gap: 15px;
    position: relative;
    padding-left: 30px;
    margin-top: 10px;
}
.bar-chart-y-axis {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    font-size: 11px;
    color: #888;
    font-weight: 500;
}
.bar {
    flex: 1;
    background-color: #c4a5a5;
    border-radius: 6px 6px 0 0;
    transition: height 1s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
.bar:hover {
    background-color: #8b4c4c;
}
.bar-date {
    position: absolute;
    bottom: -22px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 11px;
    color: #888;
    white-space: nowrap;
    font-weight: 500;
}

/* Vertical Comparison Bars (Old Style ported) */
.comparison-chart {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 180px;
    padding-bottom: 10px;
}
.comparison-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.comparison-label {
    font-weight: 600;
    color: #555;
    font-size: 0.9rem;
}
.comparison-bar {
    width: 70px;
    background-color: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: flex-end;
    position: relative;
    border: 1px solid #e9ecef;
}
.comparison-fill {
    width: 100%;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding-top: 8px;
    transition: height 1.5s cubic-bezier(0.4, 0, 0.2, 1);
}
.comparison-fill-created {
    background-color: #8b4c4c;
}
.comparison-fill-completed {
    background-color: #8b4c4c;
}
.comparison-number {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

/* Circular Progress (Old Style ported) */
.circular-progress {
    display: flex;
    justify-content: space-around;
    align-items: center;
    height: 180px;
}
.circular-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.circular-chart {
    position: relative;
    width: 110px;
    height: 110px;
}
.circular-bg {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: conic-gradient(var(--fill-color, #8b4c4c) calc(var(--percentage) * 1%), #f1f3f5 0);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 1.5s ease-out;
}
.circular-bg::before {
    content: attr(data-value);
    position: absolute;
    width: 86px;
    height: 86px;
    background-color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.3rem;
    color: var(--fill-color, #8b4c4c);
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
}
.base-color {
    --fill-color: #69342e;
}
.eff-color {
    --fill-color: #8b4c4c;
}
.delay-color {
    --fill-color: #8b4c4c;
}
.circular-label {
    font-weight: 600;
    color: #555;
    font-size: 0.85rem;
    text-align: center;
}

@media (max-width: 992px) {
    .summary-grid, .charts-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 576px) {
    .summary-grid {
        grid-template-columns: 1fr 1fr;
    }
}

/* Statistics Page Dark Mode Styles */
body.cn-body-dark .stat-card {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .stat-icon {
  background-color: #612c2d !important;
}

body.cn-body-dark .stat-info .stat-value {
  color: #ffffff !important;
}

body.cn-body-dark .stat-info .stat-label {
  color: #fcd5b8 !important;
}

body.cn-body-dark .chart-card {
  background-color: #4d2323 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .chart-title {
  color: #ffffff !important;
  border-bottom: 1px solid #7b413f !important;
}

body.cn-body-dark .legend-item {
  color: #fcd5b8 !important;
}

body.cn-body-dark .bar {
  background-color: #f4be95 !important;
}

body.cn-body-dark .bar:hover {
  background-color: #fbe8da !important;
}

body.cn-body-dark .bar-chart-y-axis,
body.cn-body-dark .bar-date {
  color: #fcd5b8 !important;
}

body.cn-body-dark .comparison-bar {
  background-color: #612c2d !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .comparison-fill-created {
  background-color: #f4be95 !important;
}

body.cn-body-dark .comparison-fill-completed {
  background-color: #cf997b !important;
}

body.cn-body-dark .comparison-number {
  color: #4d2323 !important;
}

body.cn-body-dark .comparison-label {
  color: #fcd5b8 !important;
}

body.cn-body-dark .circular-bg {
  background: conic-gradient(var(--fill-color, #f4be95) calc(var(--percentage) * 1%), #612c2d 0) !important;
}

body.cn-body-dark .circular-bg::before {
  background-color: #4d2323 !important;
  color: var(--fill-color, #f4be95) !important;
}

body.cn-body-dark .base-color {
  --fill-color: #f4be95 !important;
}

body.cn-body-dark .eff-color {
  --fill-color: #f9dac3 !important;
}

body.cn-body-dark .delay-color {
  --fill-color: #cf997b !important;
}

body.cn-body-dark .circular-label {
  color: #fcd5b8 !important;
}
</style>
