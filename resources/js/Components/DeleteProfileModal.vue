<template>
  <div v-if="isOpen" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="background: transparent; border: none;">
        <div class="form">
          <button type="button" class="btn-close-custom" @click="$emit('close')">×</button>
          <div class="title" style="font-size: 28px;">Borrar Perfil</div>
          
          <div class="mensaje my-4 text-white text-center px-3" style="font-size: 16px; line-height: 1.5;">
            ¿Estás seguro de que deseas eliminar el perfil "<span style="color: #b136d9; font-weight: bold;">{{ perfil?.tituloPerfil }}</span>"?
            <br><span style="color: #ff4a4a; font-size: 14px;">Esta acción no se puede deshacer.</span>
          </div>

          <form @submit.prevent="submitDelete">
            <button type="submit" class="submit" style="background-color: #d93636;" :disabled="form.processing">
              {{ form.processing ? 'Eliminando...' : 'Eliminar' }}
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  isOpen: Boolean,
  perfil: Object
});

const emit = defineEmits(['close']);
const form = useForm({});

const submitDelete = () => {
  form.delete(route('perfiles.destroy', props.perfil.idPerfil), {
    onSuccess: () => emit('close')
  });
};
</script>