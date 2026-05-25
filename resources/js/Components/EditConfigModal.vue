<template>
  <div v-if="isOpen" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: transparent; border: none;">
        <div class="config-modal">
          <button type="button" class="config-modal-close" @click="$emit('close')">×</button>
          <h2>Modificar Configuración</h2>

          <form @submit.prevent="submitForm">
            <div class="mb-3">
              <label class="form-label">Duración de la sesión (minutos)</label>
              <input v-model.number="form.duracionSesion" class="form-control" type="number" min="1" max="120" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Descanso corto (minutos)</label>
              <input v-model.number="form.duracionDescansoCorto" class="form-control" type="number" min="1" max="30" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Descanso largo (minutos)</label>
              <input v-model.number="form.duracionDescansoLargo" class="form-control" type="number" min="5" max="60" required />
            </div>
            <div class="mb-4">
              <label class="form-label">Sesiones antes de descanso largo</label>
              <select v-model.number="form.sesionesPrevioDescansoLargo" class="form-select" required>
                <option v-for="n in 9" :key="n + 1" :value="n + 1">{{ n + 1 }}</option>
              </select>
            </div>
            <div class="d-flex justify-content-between">
              <button type="button" class="btn btn-secundario" @click="$emit('close')">Volver</button>
              <button type="submit" class="btn btn-primario" :disabled="form.processing">Actualizar</button>
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
  config: Object
});

const emit = defineEmits(['close']);

const form = useForm({
  duracionSesion: 25,
  duracionDescansoCorto: 5,
  duracionDescansoLargo: 15,
  sesionesPrevioDescansoLargo: 4
});

watch(() => props.config, (cfg) => {
  if (cfg) {
    form.duracionSesion = cfg.duracionSesion;
    form.duracionDescansoCorto = cfg.duracionDescansoCorto;
    form.duracionDescansoLargo = cfg.duracionDescansoLargo;
    form.sesionesPrevioDescansoLargo = cfg.sesionesPrevioDescansoLargo;
  }
}, { immediate: true });

const submitForm = () => {
  form.put(route('pomodoro.config.update', props.config.idConfiguracionPomodoro), {
    onSuccess: () => emit('close')
  });
};
</script>

<style scoped>
.config-modal {
  background-color: #2e2e3e;
  border-radius: 20px;
  padding: 30px;
  max-width: 450px;
  margin: auto;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.6);
  position: relative;
}

.config-modal h2 {
  text-align: center;
  margin-bottom: 25px;
  color: #f0e6f6;
}

.config-modal-close {
  position: absolute;
  top: 15px;
  right: 20px;
  background: none;
  border: none;
  color: #eee;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

.config-modal .form-label {
  color: #dcdcdc;
  font-weight: 500;
}

.config-modal .form-control,
.config-modal .form-select {
  background-color: #533763;
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 10px 15px;
}

.config-modal .form-control:focus,
.config-modal .form-select:focus {
  box-shadow: 0 0 5px #a44fd1;
}

.btn-primario {
  background-color: #a44fd1;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 10px 20px;
  font-weight: bold;
}

.btn-primario:hover { background-color: #8a3fb8; color: white; }

.btn-secundario {
  background-color: #544b59;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 10px 20px;
  font-weight: bold;
}

.btn-secundario:hover { background-color: #6a5f70; color: white; }
</style>
