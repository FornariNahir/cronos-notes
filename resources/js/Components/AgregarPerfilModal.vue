<template>
  <div 
    v-if="isOpen" 
    class="modal fade show d-block" 
    tabindex="-1" 
    aria-hidden="true"
    style="background: rgba(0, 0, 0, 0.5);"
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: transparent; border: none;">
        
        <div class="cp-form">
          <button type="button" class="cp-btn-close" @click="closeModal" aria-label="Cerrar">×</button>
          
          <div class="cp-title">Agregar Perfil</div>
          
          <form @submit.prevent="submitForm">
            
            <div class="cp-input-container">
              <input id="add-titulo" v-model="form.tituloPerfil" class="cp-input" type="text" placeholder="" required />
              <div class="cp-cut"></div>
              <label for="add-titulo" class="cp-placeholder">Título del perfil</label>
            </div>
            <div v-if="form.errors.tituloPerfil" style="color: #ff6b6b; font-size: 0.8rem; margin-top: 5px; text-align: left; padding-left: 10px;">
              {{ form.errors.tituloPerfil }}
            </div>
            
            <div class="cp-input-container" style="margin-top: 30px;">
              <input id="add-descripcion" v-model="form.descripcionPerfil" class="cp-input" type="text" placeholder=" " />
              <div class="cp-cut"></div>
              <label for="add-descripcion" class="cp-placeholder">Descripción</label>
            </div>
            
            <button type="submit" class="cp-submit" :disabled="form.processing">
              {{ form.processing ? 'Agregando...' : 'Agregar' }}
            </button>
            
          </form>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({
  isOpen: Boolean
});

const emit = defineEmits(['close']);

const form = useForm({
  tituloPerfil: '',
  descripcionPerfil: '',
});

const closeModal = () => {
  form.reset();
  emit('close');
};

const submitForm = () => {
  form.post(route('perfiles.store'), {
    onSuccess: () => closeModal(),
  });
};
</script>

<style>
.cp-form {
  background-color: #4c1352 !important;
  border-radius: 20px !important;
  box-sizing: border-box !important;
  height: 450px !important;
  padding: 20px !important;
  width: 320px !important;
  margin: 0 auto !important;
  position: relative !important;
}

.cp-btn-close {
  position: absolute !important;
  top: 15px !important;
  right: 20px !important;
  background: none !important;
  border: none !important;
  color: #eee !important;
  font-size: 24px !important;
  font-weight: bold !important;
  cursor: pointer !important;
  z-index: 10 !important;
  width: 30px !important;
  height: 30px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  border-radius: 50% !important;
}

.cp-title {
  color: #eee !important;
  font-family: sans-serif !important;
  font-size: 36px !important;
  font-weight: 600 !important;
  margin-top: 30px !important;
  display: flex !important;
  justify-content: center !important;
}

.cp-input-container {
  height: 50px !important;
  position: relative !important;
  width: 100% !important;
  margin-top: 40px !important;
}

.cp-input {
  background-color: #470442 !important;
  border-radius: 12px !important;
  border: 0 !important;
  box-sizing: border-box !important;
  color: #eee !important;
  font-size: 18px !important;
  height: 100% !important;
  outline: 0 !important;
  padding: 4px 20px 0 !important;
  width: 100% !important;
}

.cp-cut {
  background-color: #4c1352 !important;
  border-radius: 10px !important;
  height: 20px !important;
  left: 20px !important;
  position: absolute !important;
  top: -20px !important;
  transform: translateY(0) !important;
  transition: transform 200ms, opacity 200ms !important;
  width: 76px !important;
  opacity: 0 !important;
}

.cp-input:focus ~ .cp-cut,
.cp-input:not(:placeholder-shown) ~ .cp-cut {
  transform: translateY(8px) !important;
  opacity: 1 !important;
}

.cp-placeholder {
  color: #aaa !important;
  font-family: sans-serif !important;
  left: 20px !important;
  line-height: 14px !important;
  pointer-events: none !important;
  position: absolute !important;
  transform-origin: 0 50% !important;
  transition: transform 200ms, color 200ms !important;
  top: 20px !important;
}

.cp-input:focus ~ .cp-placeholder,
.cp-input:not(:placeholder-shown) ~ .cp-placeholder {
  transform: translateY(-30px) translateX(10px) scale(0.75) !important;
}

.cp-input:not(:placeholder-shown) ~ .cp-placeholder {
  color: #ffffff !important;
}

.cp-input:focus ~ .cp-placeholder {
  color: #ffffff !important;
}

.cp-submit {
  background-color: #b136d9 !important;
  border-radius: 12px !important;
  border: 0 !important;
  box-sizing: border-box !important;
  color: #eee !important;
  cursor: pointer !important;
  font-size: 18px !important;
  height: 50px !important;
  margin-top: 38px !important;
  outline: 0 !important;
  text-align: center !important;
  width: 100% !important;
}
</style>