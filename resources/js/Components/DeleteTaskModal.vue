<template>
  <div v-if="isOpen" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: transparent; border: none;">
        <div class="delete-modal">
          <h2>Eliminar Tarea</h2>
          <p class="text-center mb-4" style="color: #dcdcdc;">
            ¿Estás seguro de eliminar la tarea <strong>"{{ tarea?.tituloTarea }}"</strong>?
          </p>
          <div class="d-flex justify-content-center gap-3">
            <button type="button" class="btn btn-secundario" @click="$emit('close')">Cancelar</button>
            <button type="button" class="btn btn-danger" @click="eliminar">Eliminar</button>
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

const eliminar = () => {
  router.delete(route('tareas.destroy', props.tarea.idTarea), {
    onSuccess: () => emit('close')
  });
};
</script>

<style>
.delete-modal {
  background-color: #2e2e3e;
  border-radius: 20px;
  padding: 30px;
  max-width: 400px;
  margin: auto;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
}

.delete-modal h2 {
  text-align: center;
  margin-bottom: 15px;
  color: #f0e6f6;
  font-size: 1.5rem;
}

.btn-danger {
  background-color: #dc3545;
  border: none;
  border-radius: 12px;
  padding: 10px 20px;
  font-weight: bold;
  color: white;
}

.btn-danger:hover {
  background-color: #c82333;
}
</style>
