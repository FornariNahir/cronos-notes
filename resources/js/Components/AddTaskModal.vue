<template>
  <div v-if="isOpen" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: transparent; border: none;">
        <div class="task-modal">
          <button type="button" class="task-modal-close" @click="$emit('close')">×</button>
          <h2>Añadir Nueva Tarea</h2>

          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="add-titulo" class="form-label">Título de la Tarea</label>
              <input id="add-titulo" v-model="form.tituloTarea" class="form-control" type="text" maxlength="30" required />
            </div>

            <div class="mb-3">
              <label for="add-descripcion" class="form-label">Descripción</label>
              <textarea id="add-descripcion" v-model="form.descripcionTarea" class="form-control" rows="3" required></textarea>
            </div>

            <div class="mb-3">
              <label for="add-fechaLimite" class="form-label">Fecha Límite</label>
              <input id="add-fechaLimite" v-model="form.fechaLimite" class="form-control" type="date" required />
            </div>

            <div class="mb-4">
              <label for="add-prioridad" class="form-label">Prioridad</label>
              <select id="add-prioridad" v-model="form.prioridadTarea" class="form-select" required>
                <option value="">Seleccionar prioridad</option>
                <option value="Baja">Baja</option>
                <option value="Media">Media</option>
                <option value="Alta">Alta</option>
              </select>
            </div>

            <div class="d-flex justify-content-between">
              <button type="button" class="btn btn-secundario" @click="$emit('close')">Volver</button>
              <button type="submit" class="btn btn-primario" :disabled="form.processing">
                {{ form.processing ? 'Guardando...' : 'Guardar' }}
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

const props = defineProps({
  isOpen: Boolean
});

const emit = defineEmits(['close']);

const form = useForm({
  tituloTarea: '',
  descripcionTarea: '',
  fechaLimite: '',
  prioridadTarea: ''
});

const submitForm = () => {
  form.post(route('tareas.store'), {
    onSuccess: () => {
      form.reset();
      emit('close');
    }
  });
};
</script>

<style>
.task-modal {
  background-color: #2e2e3e;
  border-radius: 20px;
  padding: 30px;
  max-width: 500px;
  margin: auto;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
  position: relative;
}

.task-modal h2 {
  text-align: center;
  margin-bottom: 25px;
  color: #f0e6f6;
  font-size: 1.8rem;
}

.task-modal-close {
  position: absolute;
  top: 15px;
  right: 20px;
  background: none;
  border: none;
  color: #eee;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  z-index: 10;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.task-modal .form-label {
  color: #dcdcdc;
  font-weight: 500;
}

.task-modal .form-control,
.task-modal .form-select {
  background-color: #533763;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 10px 15px;
}

.task-modal .form-control:focus,
.task-modal .form-select:focus {
  box-shadow: 0 0 5px #a44fd1;
  background-color: #533763;
  color: #fff;
}

.task-modal .form-control::placeholder {
  color: #aaa;
}

.btn-primario {
  background-color: #a44fd1;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 10px 20px;
  font-weight: bold;
}

.btn-primario:hover {
  background-color: #8a3fb8;
  color: white;
}

.btn-secundario {
  background-color: #544b59;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 10px 20px;
  font-weight: bold;
}

.btn-secundario:hover {
  background-color: #6a5f70;
  color: white;
}
</style>
