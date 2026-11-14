<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  tarea: {
    type: Object,
    default: null,
  },
  perfilActivo: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'guardado', 'solicitarEditarTarea']);

// Estados posibles: 'cargando' | 'sin_descripcion' | 'revisando' | 'error'
const estado = ref('cargando');
const mensajeError = ref('');
const guardando = ref(false);
const errorGuardado = ref('');

const subtareasPropuestas = ref([]);
const modoGuardado = ref('reemplazar'); // 'reemplazar' | 'anexar'

const tieneSubtareasPrevias = computed(() => {
  return props.tarea && props.tarea.subtareas && props.tarea.subtareas.length > 0;
});

const subtareasSeleccionadas = computed(() => {
  return subtareasPropuestas.value.filter(s => s.seleccionada && s.titulo.trim() !== '');
});

const totalPomodorosEstimados = computed(() => {
  return subtareasSeleccionadas.value.reduce((total, s) => total + (Number(s.estimacionPomodoros) || 1), 0);
});

const solicitarDesglose = async () => {
  if (!props.tarea) return;

  const descripcion = props.tarea.descripcionTarea ? props.tarea.descripcionTarea.trim() : '';
  if (!descripcion) {
    estado.value = 'sin_descripcion';
    return;
  }

  estado.value = 'cargando';
  mensajeError.value = '';
  errorGuardado.value = '';
  subtareasPropuestas.value = [];
  modoGuardado.value = tieneSubtareasPrevias.value ? 'reemplazar' : 'reemplazar';

  try {
    const response = await axios.post(route('tareas.desglosar-ia', props.tarea.idTarea), {}, {
      headers: {
        'Accept': 'application/json',
      }
    });

    const data = response.data;

    if (data.subtareas && Array.isArray(data.subtareas)) {
      subtareasPropuestas.value = data.subtareas.map(s => ({
        titulo: s.titulo || '',
        estimacionPomodoros: Math.max(1, Math.min(4, Number(s.estimacionPomodoros) || 1)),
        seleccionada: true,
      }));
      estado.value = 'revisando';
    } else {
      throw new Error('La respuesta de la IA no contiene una lista de subtareas válida.');
    }
  } catch (err) {
    if (err.response && err.response.status === 422) {
      estado.value = 'sin_descripcion';
      return;
    }
    mensajeError.value = err.response?.data?.error || err.response?.data?.message || err.message || 'Ocurrió un error al comunicarse con el servicio de IA.';
    estado.value = 'error';
  }
};

watch(() => props.show, (newVal) => {
  if (newVal && props.tarea) {
    solicitarDesglose();
  } else {
    estado.value = 'cargando';
    subtareasPropuestas.value = [];
    mensajeError.value = '';
    errorGuardado.value = '';
  }
});

const agregarPasoManual = () => {
  subtareasPropuestas.value.push({
    titulo: '',
    estimacionPomodoros: 1,
    seleccionada: true,
  });
};

const eliminarPaso = (index) => {
  subtareasPropuestas.value.splice(index, 1);
};

const editarTareaContexto = () => {
  emit('close');
  emit('solicitarEditarTarea', props.tarea);
};

const guardar = async () => {
  if (subtareasSeleccionadas.value.length === 0) {
    errorGuardado.value = 'Debes seleccionar al menos una subtarea para guardar.';
    return;
  }

  guardando.value = true;
  errorGuardado.value = '';

  try {
    const payload = {
      subtareas: subtareasSeleccionadas.value.map(s => ({
        titulo: s.titulo.trim(),
        estimacionPomodoros: Math.max(1, Math.min(4, Number(s.estimacionPomodoros) || 1)),
      })),
      modo: tieneSubtareasPrevias.value ? modoGuardado.value : 'reemplazar',
    };

    const response = await axios.post(route('subtareas.guardar-lote', props.tarea.idTarea), payload, {
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = response.data;

    if (!data.success) {
      throw new Error(data.error || data.message || 'Error al persistir las subtareas.');
    }

    emit('guardado', data.tarea);
    emit('close');
  } catch (err) {
    errorGuardado.value = err.response?.data?.error || err.response?.data?.message || err.message || 'Ocurrió un error al guardar el desglose.';
  } finally {
    guardando.value = false;
  }
};
</script>

<template>
  <Teleport to="body">
    <transition name="fade">
      <div v-if="show" class="subtask-breakdown-backdrop" @click="emit('close')">
        <div class="subtask-breakdown-dialog" @click.stop>
          <div class="subtask-breakdown-content shadow-xl rounded-4 overflow-hidden bg-white">
            
            <!-- Header Modal -->
            <div class="header-ia-desglose p-4 text-white d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <div class="icon-spark-ia rounded-circle d-flex align-items-center justify-content-center shadow-sm">
                  <i class="bi bi-magic fs-4 text-white"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-white text-dark rounded-pill px-2.5 py-0.5 small fw-semibold">Asistente IA</span>
                    <span v-if="tarea" class="badge badge-tarea-ref text-truncate" style="max-width: 250px;">
                      {{ tarea.tituloTarea }}
                    </span>
                  </div>
                  <h3 class="h5 fw-bold m-0 text-white">Desglose Automático de Tarea</h3>
                </div>
              </div>
              <button type="button" class="btn-close btn-close-white" aria-label="Cerrar" @click="emit('close')"></button>
            </div>

            <!-- Body Modal -->
            <div class="modal-body p-4 bg-white">

              <!-- ESTADO 1: Faltan detalles de descripción -->
              <div v-if="estado === 'sin_descripcion'" class="text-center py-4">
                <div class="alert-icon-wrap mb-3 mx-auto rounded-circle d-flex align-items-center justify-content-center">
                  <i class="bi bi-file-text-fill fs-1 text-warning"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">Se necesita una descripción para la tarea</h5>
                <p class="text-secondary small mx-auto" style="max-width: 440px;">
                  Para que la Inteligencia Artificial pueda sugerir subtareas secuenciales precisas, la tarea debe contar con una descripción detallada que le proporcione contexto y consignas claras.
                </p>
                <div class="d-flex justify-content-center gap-2 mt-4">
                  <button type="button" class="btn btn-outline-secondary px-4 py-2" @click="emit('close')">
                    Cancelar
                  </button>
                  <button type="button" class="btn btn-marron px-4 py-2 d-flex align-items-center gap-2" @click="editarTareaContexto">
                    <i class="bi bi-pencil-square"></i> Añadir descripción ahora
                  </button>
                </div>
              </div>

              <!-- ESTADO 2: Cargando / Analizando con Gemini -->
              <div v-else-if="estado === 'cargando'" class="text-center py-5">
                <div class="spinner-ia-wrap mb-3">
                  <div class="spinner-border text-marron" style="width: 3.5rem; height: 3.5rem;" role="status">
                    <span class="visually-hidden">Cargando...</span>
                  </div>
                </div>
                <h5 class="fw-bold text-dark mb-1">Analizando tarea con Inteligencia Artificial...</h5>
                <p class="text-muted small mx-auto mb-0" style="max-width: 420px;">
                  Google Gemini está descomponiendo tu objetivo en una secuencia de subtareas accionables y estimando los bloques Pomodoro recomendados.
                </p>
              </div>

              <!-- ESTADO 3: Error de API o Fallback agotado -->
              <div v-else-if="estado === 'error'" class="text-center py-4">
                <div class="alert-icon-wrap mb-3 mx-auto rounded-circle d-flex align-items-center justify-content-center bg-danger-subtle">
                  <i class="bi bi-exclamation-triangle-fill fs-1 text-danger"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">No pudimos generar el desglose</h5>
                <p class="text-danger small mx-auto" style="max-width: 420px;">
                  {{ mensajeError }}
                </p>
                <div class="d-flex justify-content-center gap-2 mt-4">
                  <button type="button" class="btn btn-outline-secondary px-4 py-2" @click="emit('close')">
                    Cerrar
                  </button>
                  <button type="button" class="btn btn-marron px-4 py-2 d-flex align-items-center gap-2" @click="solicitarDesglose">
                    <i class="bi bi-arrow-clockwise"></i> Reintentar
                  </button>
                </div>
              </div>

              <!-- ESTADO 4: Revisión y Personalización de Subtareas -->
              <div v-else-if="estado === 'revisando'">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <h6 class="fw-bold text-dark m-0">Propuesta de Subtareas Sugeridas</h6>
                    <small class="text-muted">Podés editar textos, modificar pomodoros o eliminar sugerencias.</small>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1" @click="agregarPasoManual">
                    <i class="bi bi-plus-lg"></i> Agregar paso
                  </button>
                </div>

                <!-- Lista editable de subtareas -->
                <div class="lista-subtareas-sugeridas mb-3 rounded border p-2 bg-light" style="max-height: 280px; overflow-y: auto;">
                  <div 
                    v-for="(sub, index) in subtareasPropuestas" 
                    :key="index"
                    class="d-flex align-items-center gap-2 p-2 mb-2 rounded border shadow-2xs subtask-suggested-item"
                    :class="{'opacity-50': !sub.seleccionada}"
                  >
                    <!-- Checkbox de selección -->
                    <input 
                      type="checkbox" 
                      class="form-check-input mt-0 cursor-pointer"
                      v-model="sub.seleccionada"
                      title="Incluir subtarea"
                    >
                    
                    <!-- Input de título -->
                    <input 
                      type="text" 
                      v-model="sub.titulo"
                      class="form-control form-control-sm flex-grow-1"
                      placeholder="Título de la subtarea (ej. Diseñar interfaz...)"
                      maxlength="150"
                      :disabled="!sub.seleccionada"
                    >

                    <!-- Selector de Pomodoros -->
                    <div class="d-flex align-items-center gap-1" title="Estimación de Pomodoros (1-4)">
                      <span class="small">🍅</span>
                      <input 
                        type="number" 
                        v-model.number="sub.estimacionPomodoros"
                        class="form-control form-control-sm text-center"
                        style="width: 58px;"
                        min="1"
                        max="4"
                        :disabled="!sub.seleccionada"
                      >
                    </div>

                    <!-- Botón borrar paso -->
                    <button 
                      type="button" 
                      class="btn btn-sm btn-link text-danger p-0 px-1"
                      @click="eliminarPaso(index)"
                      title="Descartar esta sugerencia"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>

                  <div v-if="subtareasPropuestas.length === 0" class="text-center py-3 text-muted small">
                    No hay pasos en la lista. Hacé clic en "Agregar paso" para sumar uno nuevo.
                  </div>
                </div>

                <!-- Barra Resumen de Esfuerzo Proyectado -->
                <div class="d-flex justify-content-between align-items-center p-2.5 rounded mb-3 border subtask-summary-banner">
                  <span class="small fw-semibold">
                    <i class="bi bi-check2-circle text-success me-1"></i>
                    {{ subtareasSeleccionadas.length }} de {{ subtareasPropuestas.length }} subtareas seleccionadas
                  </span>
                  <span class="badge badge-pomodoro-total fw-bold px-2.5 py-1.5" style="font-size: 12px;">
                    🍅 Total: {{ totalPomodorosEstimados }} Pomodoros
                  </span>
                </div>

                <!-- Selector si ya existen subtareas previas -->
                <div v-if="tieneSubtareasPrevias" class="p-3 mb-3 rounded border subtask-existing-banner">
                  <div class="small fw-bold text-dark mb-1">
                    <i class="bi bi-exclamation-circle text-info me-1"></i>
                    Esta tarea ya tiene {{ tarea.subtareas.length }} subtarea(s) guardada(s):
                  </div>
                  <div class="form-check small mb-1">
                    <input 
                      class="form-check-input" 
                      type="radio" 
                      name="modoGuardado" 
                      id="modoReemplazar" 
                      value="reemplazar" 
                      v-model="modoGuardado"
                    >
                    <label class="form-check-label cursor-pointer text-dark" for="modoReemplazar">
                      <strong>Reemplazar existentes</strong> (eliminará las subtareas previas e insertará estas)
                    </label>
                  </div>
                  <div class="form-check small">
                    <input 
                      class="form-check-input" 
                      type="radio" 
                      name="modoGuardado" 
                      id="modoAnexar" 
                      value="anexar" 
                      v-model="modoGuardado"
                    >
                    <label class="form-check-label cursor-pointer text-dark" for="modoAnexar">
                      <strong>Añadir al final</strong> (conservar las anteriores y agregar estas nuevas)
                    </label>
                  </div>
                </div>

                <!-- Error al guardar si ocurre -->
                <div v-if="errorGuardado" class="alert alert-danger py-2 small mb-3">
                  <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ errorGuardado }}
                </div>

                <!-- Botones de Acción -->
                <div class="d-flex gap-2 justify-content-end pt-2 border-top">
                  <button type="button" class="btn btn-outline-secondary px-4 py-2" :disabled="guardando" @click="emit('close')">
                    Cancelar
                  </button>
                  <button 
                    type="button" 
                    class="btn btn-marron px-4 py-2 d-flex align-items-center gap-2"
                    :disabled="guardando || subtareasSeleccionadas.length === 0"
                    @click="guardar"
                  >
                    <i class="bi" :class="guardando ? 'bi-hourglass-split' : 'bi-check-lg'"></i>
                    <span>{{ guardando ? 'Guardando plan...' : 'Aceptar e Insertar' }}</span>
                  </button>
                </div>
              </div>

            </div>

          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<style scoped>
.subtask-breakdown-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(3px);
  z-index: 1060;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.subtask-breakdown-dialog {
  width: 100%;
  max-width: 620px;
}

.header-ia-desglose {
  background: linear-gradient(135deg, #69342e 0%, #3e1b17 100%);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.icon-spark-ia {
  width: 48px;
  height: 48px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.25);
}

.badge-tarea-ref {
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  font-size: 11px;
}

.subtask-suggested-item {
  background-color: #ffffff;
  border-color: #dee2e6 !important;
}

.subtask-summary-banner {
  background-color: #fff3cd;
  border-color: #ffe69c !important;
  color: #664d03;
}

.subtask-existing-banner {
  background-color: #f8f9fa;
  border-color: #dee2e6 !important;
}

.alert-icon-wrap {
  width: 70px;
  height: 70px;
  background-color: #fff3cd;
}

.badge-pomodoro-total {
  background-color: rgba(105, 52, 46, 0.08);
  color: #69342e;
  border: 1px solid rgba(105, 52, 46, 0.2);
}

.btn-marron {
  background-color: #69342e;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-marron:hover:not(:disabled) {
  background-color: #542924;
  color: #fff;
}

.btn-marron:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.text-marron {
  color: #69342e !important;
}

.cursor-pointer {
  cursor: pointer;
}

/* Transiciones */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<style>
/* Modo Oscuro Global para SubtaskBreakdownModal (teleportado a body) */
body.cn-body-dark .subtask-breakdown-content {
  background-color: #4d2323 !important;
  border: 1px solid #7b413f !important;
  color: #ffffff !important;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6) !important;
}

body.cn-body-dark .subtask-breakdown-content .modal-body {
  background-color: #4d2323 !important;
  color: #ffffff !important;
}

body.cn-body-dark .subtask-breakdown-content h5,
body.cn-body-dark .subtask-breakdown-content h6,
body.cn-body-dark .subtask-breakdown-content .text-dark {
  color: #ffffff !important;
}

body.cn-body-dark .subtask-breakdown-content p,
body.cn-body-dark .subtask-breakdown-content .text-secondary,
body.cn-body-dark .subtask-breakdown-content .text-muted {
  color: #fcd5b8 !important;
}

body.cn-body-dark .subtask-breakdown-content .lista-subtareas-sugeridas {
  background-color: #3b1717 !important;
  border-color: #7b413f !important;
}

body.cn-body-dark .subtask-breakdown-content .subtask-suggested-item {
  background-color: #431c1c !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .subtask-breakdown-content .subtask-suggested-item input {
  background-color: #321313 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .subtask-breakdown-content .subtask-suggested-item input::placeholder {
  color: rgba(255, 255, 255, 0.45) !important;
}

body.cn-body-dark .subtask-breakdown-content .subtask-summary-banner {
  background-color: rgba(244, 190, 149, 0.12) !important;
  border-color: rgba(244, 190, 149, 0.28) !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .subtask-breakdown-content .badge-pomodoro-total {
  background-color: rgba(244, 190, 149, 0.2) !important;
  color: #f4be95 !important;
  border-color: rgba(244, 190, 149, 0.4) !important;
}

body.cn-body-dark .subtask-breakdown-content .subtask-existing-banner {
  background-color: #3b1717 !important;
  border-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .subtask-breakdown-content .subtask-existing-banner label {
  color: #ffffff !important;
}

body.cn-body-dark .subtask-breakdown-content .subtask-existing-banner strong {
  color: #f4be95 !important;
}

body.cn-body-dark .subtask-breakdown-content .alert-icon-wrap {
  background-color: rgba(255, 193, 7, 0.15) !important;
}

body.cn-body-dark .subtask-breakdown-content .btn-outline-secondary {
  border-color: #7b413f !important;
  color: #fcd5b8 !important;
}

body.cn-body-dark .subtask-breakdown-content .btn-outline-secondary:hover {
  background-color: #7b413f !important;
  color: #ffffff !important;
}

body.cn-body-dark .subtask-breakdown-content .border-top {
  border-color: #7b413f !important;
}

body.cn-body-dark .subtask-breakdown-content .text-marron {
  color: #f4be95 !important;
}

body.cn-body-dark .subtask-breakdown-content .btn-marron {
  background-color: #f4be95 !important;
  color: #4d2323 !important;
  font-weight: 600 !important;
}

body.cn-body-dark .subtask-breakdown-content .btn-marron:hover:not(:disabled) {
  background-color: #fbe8da !important;
  color: #4d2323 !important;
}
</style>
