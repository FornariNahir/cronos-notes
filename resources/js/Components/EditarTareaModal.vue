<template>
  <div v-if="isOpen" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: transparent; border: none;">
        <div class="task-modal">
          <button type="button" class="task-modal-close" @click="$emit('close')">×</button>
          <h2>Modificar Tarea</h2>

          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="edit-titulo" class="form-label">Nuevo título de la Tarea</label>
              <input id="edit-titulo" v-model="form.tituloTarea" class="form-control" type="text" maxlength="30" required />
            </div>

            <div class="mb-3">
              <label for="edit-descripcion" class="form-label">Nueva descripción</label>
              <textarea id="edit-descripcion" v-model="form.descripcionTarea" class="form-control" rows="3"></textarea>
            </div>

            <div class="mb-3">
              <label for="edit-fechaLimite" class="form-label">Nueva Fecha Límite</label>
              <input id="edit-fechaLimite" v-model="form.fechaLimite" class="form-control" type="date" required />
            </div>

            <div class="mb-3">
              <label for="edit-estimacion" class="form-label">Estimación de Esfuerzo (Pomodoros)</label>
              <input id="edit-estimacion" v-model="form.estimacionEsfuerzo" class="form-control" type="number" min="1" placeholder="Ej. 3" />
            </div>

            <div class="mb-3">
              <label for="edit-estado" class="form-label">Actualizar Estado</label>
              <select id="edit-estado" v-model="form.estadoTarea" class="form-select" :disabled="tarea && tarea.estadoTarea === 'Completado'" required>
                <option value="Pendiente">Pendiente</option>
                <option value="En Progreso">En Progreso</option>
                <option value="Completado">Completado</option>
              </select>
            </div>

            <div class="mb-4">
              <label for="edit-prioridad" class="form-label">Actualizar Prioridad</label>
              <select id="edit-prioridad" v-model="form.prioridadTarea" class="form-select" required>
                <option value="Baja">Baja</option>
                <option value="Media">Media</option>
                <option value="Alta">Alta</option>
              </select>
            </div>

            <div class="d-flex justify-content-between">
              <button type="button" class="btn btn-secundario" @click="$emit('close')">Volver</button>
              <button type="submit" class="btn btn-primario" :disabled="form.processing">
                {{ form.processing ? 'Actualizando...' : 'Actualizar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  isOpen: Boolean,
  tarea: Object
});

const emit = defineEmits(['close']);

const form = useForm({
  tituloTarea: '',
  descripcionTarea: '',
  fechaLimite: '',
  estadoTarea: 'Pendiente',
  prioridadTarea: 'Baja',
  estimacionEsfuerzo: ''
});

watch(() => props.tarea, (newTarea) => {
  if (newTarea) {
    form.tituloTarea = newTarea.tituloTarea;
    form.descripcionTarea = newTarea.descripcionTarea;
    form.fechaLimite = newTarea.fechaLimite ? newTarea.fechaLimite.split('T')[0] : '';
    form.estadoTarea = newTarea.estadoTarea;
    form.prioridadTarea = newTarea.prioridadTarea;
    form.estimacionEsfuerzo = newTarea.estimacionEsfuerzo;
  }
}, { immediate: true });

const submitForm = () => {
  form.put(route('tareas.update', props.tarea.idTarea), {
    onSuccess: () => emit('close')
  });
};
</script>
