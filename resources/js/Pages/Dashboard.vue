<template>
  <AppLayout>
    <div class="container pt-4">

      <div v-if="perfilActivo" class="mb-4">
        <h2 class="text-white m-0">
          Perfil Activo: <span style="color: #b136d9;">{{ perfilActivo.tituloPerfil }}</span>
        </h2>
      </div>

      <!-- ESTADÍSTICAS ESTILO CRONOS VIEJO -->
      <div class="mb-5 estadisticas-container">
        <h4 class="text-white mb-4 fs-2 fw-bold text-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);">Tus estadísticas</h4>
        
        <div class="charts-grid">
            <!-- Comparación Total -->
            <div class="chart-card">
                <h3 class="chart-title">Comparación Total</h3>
                <div class="comparison-chart">
                    <div class="comparison-item">
                        <div class="comparison-label">Tareas Creadas</div>
                        <div class="comparison-bar" style="height: 200px;">
                            <div class="comparison-fill comparison-fill-created" :style="`height: ${Math.min(100, (estadistica.tareasTotales / Math.max(1, estadistica.tareasTotales)) * 100)}%;`">
                                <div class="comparison-number">{{ estadistica.tareasTotales }}</div>
                            </div>
                        </div>
                        <div class="comparison-value">{{ estadistica.tareasTotales }}</div>
                    </div>

                    <div class="comparison-item">
                        <div class="comparison-label">Tareas Completadas</div>
                        <div class="comparison-bar" style="height: 200px;">
                            <div class="comparison-fill comparison-fill-completed" :style="`height: ${Math.min(100, (estadistica.tareasCompletadas / Math.max(1, estadistica.tareasTotales)) * 100)}%;`">
                                <div class="comparison-number">{{ estadistica.tareasCompletadas }}</div>
                            </div>
                        </div>
                        <div class="comparison-value">{{ estadistica.tareasCompletadas }}</div>
                    </div>
                </div>
            </div>

            <!-- Progreso circular -->
            <div class="chart-card">
                <h3 class="chart-title">Eficiencia de Completado</h3>
                <div class="circular-progress">
                    <div class="circular-item">
                        <div class="circular-chart">
                            <div class="circular-bg"
                                :style="`--color: #5a175a; --target: ${estadistica.tareasTotales}; --percentage: 100;`"
                                :data-value="estadistica.tareasTotales"></div>
                        </div>
                        <div style="color: white; font-weight: bold;">Creadas</div>
                    </div>

                    <div class="circular-item">
                        <div class="circular-chart">
                            <div class="circular-bg"
                                :style="`--color: #5a175a; --target: ${estadistica.eficiencia}; --percentage: ${estadistica.eficiencia};`"
                                :data-value="estadistica.eficiencia + '%'"></div>
                        </div>
                        <div style="color: white; font-weight: bold;">Eficiencia</div>
                    </div>

                    <div class="circular-item">
                        <div class="circular-chart">
                            <div class="circular-bg"
                                :style="`--color: #5a175a; --target: ${(estadistica.tareasRetrasadas / Math.max(1, estadistica.tareasTotales)) * 100}; --percentage: ${(estadistica.tareasRetrasadas / Math.max(1, estadistica.tareasTotales)) * 100};`"
                                :data-value="estadistica.tareasRetrasadas"></div>
                        </div>
                        <div style="color: white; font-weight: bold;">Completadas con retraso</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas resumen -->
        <div class="summary-stats">
            <div class="stat-box">
                <div class="stat-number stat-created">{{ estadistica.rachaMasLarga }}</div>
                <div class="stat-label">Racha más larga</div>
            </div>

            <div class="stat-box">
                <div class="stat-number stat-completed">{{ estadistica.rachaActual }}</div>
                <div class="stat-label">Racha actual</div>
            </div>

            <div class="stat-box">
                <div class="stat-number stat-minutes">{{ estadistica.tiempoTotalPomodoro }}</div>
                <div class="stat-label">Minutos concentrado</div>
            </div>

            <div class="stat-box">
                <div class="stat-number stat-efficiency">{{ estadistica.eficiencia }}%</div>
                <div class="stat-label">Eficiencia</div>
            </div>
        </div>
      </div>

        <div class="row">
          <div class="col-md-4" v-for="tarea in tareas" :key="tarea.idTarea">
            <div class="task-card">
              <div class="icon"></div>
              <h3>{{ tarea.tituloTarea }}</h3>
              <p>{{ tarea.descripcionTarea }}</p>
            </div>
          </div>

          <div class="col-12 text-center text-white mt-4" v-if="tareas.length === 0">
            <p>No tienes tareas pendientes en este perfil. ¡Aprovecha para descansar!</p>
          </div>
        </div>

      <div class="row justify-content-center mt-5">
        <div class="col-md-8 text-center text-white">
          <div class="task-card p-5" style="display: flex; flex-direction: column; align-items: center;">
            <h2 class="mb-3" style="font-weight: bold; font-size: 2.5rem;">Selecciona un perfil</h2>
            <p class="fs-5 mb-4 text-muted">
              Para ver tus tareas, estadísticas y usar el Pomodoro, primero debes seleccionar con qué perfil deseas trabajar.
            </p>
            
            <Link :href="route('perfiles.index')" class="btn" style="background: #b136d9; color: white; padding: 12px 30px; border-radius: 12px; font-weight: bold; font-size: 1.2rem; text-decoration: none; transition: background 0.3s;">
              Ir a Mis Perfiles
            </Link>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

// Recibimos las variables desde el DashboardController
const props = defineProps({
  perfilActivo: {
    type: Object,
    default: null
  },
  tareas: {
    type: Array,
    default: () => []
  },
  estadistica: {
    type: Object,
    default: () => ({ rachaActual: 0 })
  },
  chartDataSemana: {
    type: Array,
    default: () => []
  },
  chartDataMes: {
    type: Array,
    default: () => []
  },
  chartDataPerfil: {
    type: Array,
    default: () => []
  }
});

const rangoSeleccionado = ref('semana');

const datosActuales = computed(() => {
  return rangoSeleccionado.value === 'semana' ? props.chartDataSemana : props.chartDataMes;
});

const barChartData = computed(() => {
  // Revertimos para que la fecha más antigua esté a la izquierda
  const data = [...datosActuales.value].reverse();
  return {
    labels: data.map(d => d.fecha),
    datasets: [{
      label: 'Horas de estudio',
      backgroundColor: '#b136d9',
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
      grid: { color: 'rgba(255, 255, 255, 0.1)' },
      ticks: { color: 'rgba(255, 255, 255, 0.7)' }
    },
    x: {
      grid: { display: false },
      ticks: { color: 'rgba(255, 255, 255, 0.7)' }
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
      backgroundColor: props.chartDataPerfil.map(d => d.color || '#b136d9'),
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
      labels: { color: 'rgba(255, 255, 255, 0.7)' }
    }
  }
};
</script>

<style scoped>
/* ESTILOS MIGRADO DE CRONOS VIEJO PARA LAS ESTADÍSTICAS */
.estadisticas-container {
    width: 100%;
}

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.chart-card {
    background: #b136d9;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.chart-card:hover {
    transform: translateY(-5px);
}

.chart-title {
    font-size: 1.3em;
    color: white;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 600;
}

/* Gráfico comparativo lado a lado */
.comparison-chart {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    padding: 20px 0;
}

.comparison-item {
    text-align: center;
    padding: 20px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(225, 138, 233, 0.7) 100%);
    transition: transform 0.3s ease;
}

.comparison-item:hover {
    transform: translateY(-3px);
}

.comparison-bar {
    width: 60px;
    margin: 15px auto;
    border-radius: 30px;
    position: relative;
    background: #e9ecef;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.comparison-fill {
    border-radius: 30px;
    transition: height 2s cubic-bezier(0.4, 0, 0.2, 1) 0.5s;
    position: relative;
    position: absolute;
    bottom: 0;
    width: 100%;
}

.comparison-fill-created {
    background: linear-gradient(180deg, #531d55 0%, #e309d8 100%);
}

.comparison-fill-completed {
    background: linear-gradient(180deg, #531d55 0%, #e309d8 100%);
}

.comparison-number {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: bold;
    font-size: 0.8em;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.comparison-label {
    font-size: 0.9em;
    color: #555;
    margin-bottom: 5px;
    font-weight: 500;
}

.comparison-value {
    font-size: 1.8em;
    font-weight: bold;
    color: #333;
    margin-top: 10px;
}

/* Gráfico de progreso circular */
.circular-progress {
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 20px 0;
}

.circular-item {
    text-align: center;
}

.circular-chart {
    width: 120px;
    height: 120px;
    margin: 0 auto 15px;
    position: relative;
}

.circular-bg {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: conic-gradient(var(--color) calc(var(--percentage) * 1%),
            #e9ecef calc(var(--percentage) * 1%));
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    animation: circularFill 2s ease-out 0.5s both;
}

.circular-bg::after {
    content: attr(data-value);
    position: absolute;
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #333;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Estadísticas resumen */
.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.stat-box {
    background: rgba(255, 255, 255, 0.9);
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-created, .stat-completed, .stat-minutes, .stat-efficiency {
    color: #9b59b6;
}

.stat-label {
    color: #666;
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Animaciones */
@keyframes circularFill {
    from { --percentage: 0; }
    to { --percentage: var(--target); }
}

@media (max-width: 768px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    .circular-progress {
        flex-direction: column;
        gap: 20px;
    }
}
</style>