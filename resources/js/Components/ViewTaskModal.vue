<template>
  <div v-if="isOpen" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: transparent; border: none;">
        <div class="view-modal">
          <button type="button" class="task-modal-close" @click="$emit('close')">×</button>
          <h2>Visualización de Tarea</h2>

          <div class="form-group">
            <label class="form-label">Título:</label>
            <div class="form-texto">{{ tarea.tituloTarea }}</div>
          </div>

          <div class="form-group">
            <label class="form-label">Descripción:</label>
            <div class="form-texto">{{ tarea.descripcionTarea }}</div>
          </div>

          <div class="form-group">
            <label class="form-label">Fecha Límite:</label>
            <div class="form-texto">{{ formatDate(tarea.fechaLimite) }}</div>
          </div>

          <div v-if="tarea.fechaFinTarea" class="form-group">
            <label class="form-label">Tarea Finalizada el día:</label>
            <div class="form-texto">{{ formatDate(tarea.fechaFinTarea) }}</div>
          </div>

          <div class="form-group">
            <label class="form-label">Estado:</label>
            <div class="form-texto">{{ tarea.estadoTarea }}</div>
          </div>

          <div class="form-group">
            <label class="form-label">Prioridad:</label>
            <div class="form-texto">{{ tarea.prioridadTarea }}</div>
          </div>

          <div v-if="tarea.estimacionEsfuerzo" class="form-group">
            <label class="form-label">Estimación de Esfuerzo:</label>
            <div class="form-texto">{{ tarea.estimacionEsfuerzo }} Pomodoro(s)</div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-secundario" @click="$emit('close')">Volver</button>
            <button v-if="tarea.estadoTarea !== 'Completado'" type="button" class="btn btn-success" @click="marcarCompletada">
              Marcar como Completada
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
  isOpen: Boolean,
  tarea: Object
});

const emit = defineEmits(['close']);

const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const marcarCompletada = () => {
  router.patch(route('tareas.completar', props.tarea.idTarea), {}, {
    onSuccess: () => emit('close')
  });
};
</script>

<style>
.view-modal {
  background-color: #2e2e3e;
  border-radius: 20px;
  padding: 30px;
  max-width: 500px;
  margin: auto;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
  position: relative;
}

.view-modal h2 {
  text-align: center;
  margin-bottom: 25px;
  color: #f0e6f6;
  font-size: 1.8rem;
}

.form-group {
  margin-bottom: 20px;
}

.view-modal .form-label {
  font-weight: bold;
  color: #dcdcdc;
}

.form-texto {
  background-color: #533763;
  padding: 10px 15px;
  border-radius: 10px;
  color: #fff;
}

.btn-success {
  background-color: #28a745;
  border: none;
  border-radius: 12px;
  padding: 10px 20px;
  font-weight: bold;
  color: white;
}
</style>
