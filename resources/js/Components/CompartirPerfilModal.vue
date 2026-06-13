<template>
  <div 
    v-if="isOpen" 
    class="modal fade show d-block" 
    tabindex="-1" 
    aria-hidden="true"
    style="background: rgba(0, 0, 0, 0.6); z-index: 1050; backdrop-filter: blur(4px);"
    @click.self="closeModal"
  >
    <div class="modal-dialog modal-dialog-centered modal-custom-share">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <!-- Modal Header -->
        <div class="modal-header bg-white border-bottom-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="modal-title fw-bold text-dark fs-5">Compartir "{{ perfil?.tituloPerfil }}"</h5>
            <p class="text-secondary small mb-0">Administra los colaboradores de este espacio de trabajo</p>
          </div>
          <button type="button" class="btn-close shadow-none" @click="closeModal" aria-label="Close"></button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body px-4 pb-4 pt-2">
          <!-- Add Member Form (Only visible to owner or admin) -->
          <div v-if="esPropietario" class="mb-4">
            <form @submit.prevent="invitarUsuario" class="d-flex flex-column gap-2">
              <label class="form-label text-secondary small fw-medium mb-1">Añadir personas por correo electrónico</label>
              <div class="d-flex gap-2">
                <div class="flex-grow-1 position-relative">
                  <input 
                    type="email" 
                    v-model="formCompartir.email" 
                    class="form-control input-custom" 
                    placeholder="ejemplo@correo.com" 
                    required
                    :disabled="formCompartir.processing"
                  />
                </div>
                <select 
                  v-model="formCompartir.permiso" 
                  class="form-select select-custom" 
                  style="width: 130px;"
                  :disabled="formCompartir.processing"
                >
                  <option value="Lector">Lector</option>
                  <option value="Editor">Editor</option>
                </select>
                <button 
                  type="submit" 
                  class="btn btn-marron px-3" 
                  :disabled="formCompartir.processing || !formCompartir.email"
                >
                  <i class="bi bi-person-plus-fill me-1"></i>
                  {{ formCompartir.processing ? 'Enviando...' : 'Invitar' }}
                </button>
              </div>
              <div v-if="formCompartir.errors.email" class="text-danger small mt-1">
                <i class="bi bi-exclamation-circle me-1"></i>{{ formCompartir.errors.email }}
              </div>
              <div v-if="formCompartir.errors.permiso" class="text-danger small mt-1">
                <i class="bi bi-exclamation-circle me-1"></i>{{ formCompartir.errors.permiso }}
              </div>
            </form>
          </div>

          <!-- Section: People with access -->
          <div class="mb-4">
            <h6 class="text-dark fw-bold small mb-3">Personas con acceso</h6>
            <div class="d-flex flex-column gap-3 access-list-container">
              
              <!-- Owner (Propietario) -->
              <div class="d-flex align-items-center justify-content-between p-2 rounded hover-row">
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar-badge-share">
                    {{ obtenerIniciales(perfilPropietarioNombre) }}
                  </div>
                  <div>
                    <div class="fw-semibold text-dark small">
                      {{ perfilPropietarioNombre }}
                      <span class="badge bg-light text-secondary border ms-1" style="font-size: 10px;">Propietario</span>
                    </div>
                    <div class="text-secondary" style="font-size: 11px;">{{ perfilPropietarioEmail }}</div>
                  </div>
                </div>
                <span class="text-muted small px-3">Propietario</span>
              </div>

              <!-- Shared Users Loop -->
              <div 
                v-for="usuario in perfil?.usuarios_compartidos" 
                :key="usuario.idUsuario" 
                class="d-flex align-items-center justify-content-between p-2 rounded hover-row"
              >
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar-badge-share">
                    {{ obtenerIniciales(usuario.nombre + ' ' + usuario.apellido) }}
                  </div>
                  <div>
                    <div class="fw-semibold text-dark small">{{ usuario.nombre }} {{ usuario.apellido }}</div>
                    <div class="text-secondary" style="font-size: 11px;">{{ usuario.email }}</div>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <!-- Permiso Select (Only editable if esPropietario) -->
                  <select 
                    v-if="esPropietario"
                    :value="usuario.pivot?.permiso" 
                    @change="actualizarPermiso(usuario.idUsuario, $event.target.value)"
                    class="form-select select-custom select-sm py-1 px-2 border-0 bg-transparent text-secondary small"
                    style="width: 110px; font-size: 12px; cursor: pointer;"
                  >
                    <option value="Lector">Lector (vista)</option>
                    <option value="Editor">Editor (edición)</option>
                  </select>
                  <span v-else class="text-muted small px-2">
                    {{ usuario.pivot?.permiso === 'Editor' ? 'Editor' : 'Lector' }}
                  </span>

                  <!-- Revoke Access Button -->
                  <button 
                    v-if="esPropietario" 
                    type="button" 
                    class="btn btn-link text-danger p-1 fs-5 border-0 shadow-none d-flex align-items-center justify-content-center btn-hover-danger" 
                    @click="revocarAcceso(usuario.idUsuario)"
                    title="Quitar acceso"
                  >
                    <i class="bi bi-x-circle"></i>
                  </button>
                </div>
              </div>

              <!-- Pending Invitations Loop -->
              <div 
                v-for="invitacion in perfil?.invitaciones" 
                :key="invitacion.idInvitacion" 
                class="d-flex align-items-center justify-content-between p-2 rounded hover-row border-dashed"
              >
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar-badge-share bg-warning-subtle text-warning-emphasis">
                    <i class="bi bi-clock-history"></i>
                  </div>
                  <div>
                    <div class="fw-semibold text-dark small">
                      {{ invitacion.emailInvitado }}
                      <span class="badge bg-warning-subtle text-warning-emphasis ms-1" style="font-size: 10px;">Pendiente</span>
                    </div>
                    <div class="text-secondary" style="font-size: 11px;">Rol ofrecido: {{ invitacion.permisoOfrecido }}</div>
                  </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                  <!-- Cancel Invitation Button -->
                  <button 
                    v-if="esPropietario" 
                    type="button" 
                    class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0" 
                    style="font-size: 11px;"
                    @click="cancelarInvitacion(invitacion.idInvitacion)"
                  >
                    Cancelar
                  </button>
                </div>
              </div>

              <!-- Empty state inside sharing -->
              <div v-if="(!perfil?.usuarios_compartidos?.length) && (!perfil?.invitaciones?.length)" class="text-center py-2 text-muted small">
                <i class="bi bi-info-circle me-1"></i> Este perfil no está compartido con otros miembros.
              </div>

            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="modal-footer bg-light border-top-0 px-4 py-3 d-flex justify-content-end">
          <button type="button" class="btn btn-light-custom px-4 py-2" @click="closeModal">Cerrar</button>
        </div>
      </div>
    </div>

    <!-- Confirm Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      :title="confirmConfig.title"
      :message="confirmConfig.message"
      :confirm-text="confirmConfig.confirmText"
      :cancel-text="confirmConfig.cancelText"
      :is-danger="confirmConfig.isDanger"
      @close="showConfirmModal = false"
      @confirm="handleConfirm"
    />
  </div>
</template>

<script setup>
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const showConfirmModal = ref(false);
const confirmConfig = ref({
  title: '',
  message: '',
  confirmText: 'Aceptar',
  cancelText: 'Cancelar',
  isDanger: false,
  onConfirm: null
});

const triggerConfirm = (config) => {
  confirmConfig.value = {
    title: config.title || 'Confirmación',
    message: config.message || '',
    confirmText: config.confirmText || 'Aceptar',
    cancelText: config.cancelText || 'Cancelar',
    isDanger: config.isDanger || false,
    onConfirm: config.onConfirm
  };
  showConfirmModal.value = true;
};

const handleConfirm = () => {
  if (confirmConfig.value.onConfirm) {
    confirmConfig.value.onConfirm();
  }
  showConfirmModal.value = false;
};

const props = defineProps({
  isOpen: Boolean,
  perfil: Object
});

const emit = defineEmits(['close']);

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

// Check if current user is owner of this profile
const esPropietario = computed(() => {
  if (!props.perfil || !currentUser.value) return false;
  return props.perfil.idUsuario === currentUser.value.idUsuario;
});

// Resolve owner details
const perfilPropietarioNombre = computed(() => {
  if (!props.perfil) return '';
  if (esPropietario.value) {
    return currentUser.value.nombre + ' ' + currentUser.value.apellido;
  }
  return props.perfil.propietario || 'Propietario';
});

const perfilPropietarioEmail = computed(() => {
  if (!props.perfil) return '';
  if (esPropietario.value) {
    return currentUser.value.email;
  }
  return props.perfil.usuario?.email || '';
});

// Forms
const formCompartir = useForm({
  email: '',
  permiso: 'Lector'
});

const closeModal = () => {
  formCompartir.reset();
  formCompartir.clearErrors();
  emit('close');
};

const obtenerIniciales = (nombreCompleto) => {
  if (!nombreCompleto) return 'U';
  return nombreCompleto
    .split(' ')
    .filter(n => n)
    .map(n => n[0].toUpperCase())
    .slice(0, 2)
    .join('');
};

// Actions
const invitarUsuario = () => {
  formCompartir.post(route('perfil-compartido.compartir', props.perfil.idPerfil), {
    onSuccess: () => {
      formCompartir.reset('email');
    }
  });
};

const actualizarPermiso = (idUsuario, nuevoPermiso) => {
  router.put(route('perfil-compartido.actualizar', { idPerfil: props.perfil.idPerfil, idUsuario: idUsuario }), {
    permiso: nuevoPermiso
  }, {
    preserveScroll: true
  });
};

const revocarAcceso = (idUsuario) => {
  triggerConfirm({
    title: 'Quitar Acceso',
    message: '¿Estás seguro de quitarle el acceso a este usuario?',
    confirmText: 'Quitar acceso',
    isDanger: true,
    onConfirm: () => {
      router.delete(route('perfil-compartido.revocar', { idPerfil: props.perfil.idPerfil, idUsuario: idUsuario }), {
        preserveScroll: true
      });
    }
  });
};

const cancelarInvitacion = (idInvitacion) => {
  triggerConfirm({
    title: 'Cancelar Invitación',
    message: '¿Estás seguro de cancelar esta invitación?',
    confirmText: 'Cancelar invitación',
    isDanger: true,
    onConfirm: () => {
      router.delete(route('perfil-compartido.cancelar-invitacion', idInvitacion), {
        preserveScroll: true
      });
    }
  });
};
</script>

<style scoped>
.modal-custom-share {
  max-width: 500px;
  margin: 1.75rem auto;
}

@media (max-width: 576px) {
  .modal-custom-share {
    margin: 1rem;
    max-width: calc(100% - 2rem);
  }
}

.avatar-badge-share {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: #faf5f5;
  color: #69342e;
  font-weight: bold;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #ebdada;
}

.hover-row {
  transition: background-color 0.2s ease;
}

.hover-row:hover {
  background-color: #f8f9fa;
}

.border-dashed {
  border: 1px dashed #dee2e6;
  border-radius: 8px;
}

.access-list-container {
  max-height: 250px;
  overflow-y: auto;
  padding-right: 4px;
}

/* Scrollbar styles for modal list */
.access-list-container::-webkit-scrollbar {
  width: 6px;
}

.access-list-container::-webkit-scrollbar-track {
  background: transparent;
}

.access-list-container::-webkit-scrollbar-thumb {
  background-color: #dee2e6;
  border-radius: 3px;
}

.btn-hover-danger {
  color: #dc3545;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.btn-hover-danger:hover {
  transform: scale(1.15);
  opacity: 0.8;
}

.select-sm {
  padding-right: 24px !important;
  border-radius: 4px !important;
}

.select-sm:focus {
  box-shadow: none !important;
}
</style>
