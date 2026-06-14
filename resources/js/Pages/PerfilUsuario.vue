<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { usePage, useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AlertModal from '@/Components/AlertModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const showAlertModal = ref(false);
const alertTitle = ref('');
const alertMessage = ref('');
const showCustomAlert = (title, message) => {
  alertTitle.value = title;
  alertMessage.value = message;
  showAlertModal.value = true;
};

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

const page = usePage();

const props = defineProps({
    estadisticas: {
        type: Object,
        default: () => ({
            rachaActual: 0,
            tiempoTotalPomodoro: 0,
            tareasTotales: 0
        })
    }
});

const form = useForm({
    nombre: page.props.auth?.user?.nombre || '',
    apellido: page.props.auth?.user?.apellido || '',
    email: page.props.auth?.user?.email || '',
    password: '',
});

const displayName = computed(() => {
    const u = page.props.auth?.user;
    return u ? `${u.nombre} ${u.apellido}` : 'José Andrés Ayala';
});

const email = computed(() => {
    return page.props.auth?.user?.email || 'j.ayala@ucp.edu.ar';
});

const isNameOrSurnameChanged = computed(() => {
    const originalNombre = page.props.auth?.user?.nombre || '';
    const originalApellido = page.props.auth?.user?.apellido || '';
    return form.nombre !== originalNombre || form.apellido !== originalApellido;
});

watch(isNameOrSurnameChanged, (changed) => {
    if (!changed) {
        form.password = '';
    }
});

watch(() => page.props.auth?.user, (newUser) => {
    if (newUser) {
        form.nombre = newUser.nombre || '';
        form.apellido = newUser.apellido || '';
        form.email = newUser.email || '';
    }
}, { deep: true });

const inputNewEmail = ref('');
const inputOldPass = ref('');
const inputNewPass = ref('');
const inputConfirmPass = ref('');

const fileInput = ref(null);
const avatarUrl = ref('https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=150');

let modalCorreoInstance = null;
let modalPasswordInstance = null;

onMounted(() => {
    // 1. Cargar dinámicamente los iconos de Bootstrap si no están en el head
    if (!document.querySelector('link[href*="bootstrap-icons"]')) {
        const linkIcons = document.createElement('link');
        linkIcons.rel = 'stylesheet';
        linkIcons.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
        document.head.appendChild(linkIcons);
    }

    // 2. Cargar dinámicamente el JS de Bootstrap si no está cargado
    const initModals = () => {
        const modalCorreoEl = document.getElementById('modalCambiarCorreo');
        const modalPasswordEl = document.getElementById('modalCambiarPassword');
        if (window.bootstrap) {
            if (modalCorreoEl) modalCorreoInstance = new window.bootstrap.Modal(modalCorreoEl);
            if (modalPasswordEl) modalPasswordInstance = new window.bootstrap.Modal(modalPasswordEl);
        }
    };

    if (!window.bootstrap) {
        const scriptBS = document.createElement('script');
        scriptBS.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';
        scriptBS.onload = () => {
            initModals();
        };
        document.head.appendChild(scriptBS);
    } else {
        setTimeout(() => {
            initModals();
        }, 50);
    }
});

const triggerFileSelect = () => {
    if (fileInput.value) {
        fileInput.value.click();
    }
};

const onAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        avatarUrl.value = URL.createObjectURL(file);
        showCustomAlert("Avatar Actualizado", "¡Avatar seleccionado con éxito (vista previa local)!");
    }
};

const guardarDatosPersonales = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showCustomAlert("Perfil Actualizado", "¡Información del perfil actualizada con éxito!");
            form.reset('password');
        },
        onError: (errors) => {
            const errorMsg = errors.password || errors.nombre || errors.apellido || "Hubo un problema al actualizar la información del perfil.";
            showCustomAlert("Error de Validación", errorMsg);
        }
    });
};

const cambiarCorreo = () => {
    showCustomAlert("Correo", `Solicitud procesada con éxito. Se ha enviado un enlace de confirmación a: ${inputNewEmail.value}`);
    if (modalCorreoInstance) {
        modalCorreoInstance.hide();
    }
    inputNewEmail.value = '';
};

const cambiarPassword = () => {
    if (inputNewPass.value.length < 8) {
        showCustomAlert("Error de Seguridad", "La nueva contraseña debe tener al menos 8 caracteres de seguridad.");
        return;
    }

    if (inputNewPass.value !== inputConfirmPass.value) {
        showCustomAlert("Error", "Las nuevas contraseñas ingresadas no coinciden. Por favor, verificalas.");
        return;
    }

    showCustomAlert("Contraseña Actualizada", "Seguridad: ¡Tu contraseña ha sido actualizada correctamente en el sistema!");
    if (modalPasswordInstance) {
        modalPasswordInstance.hide();
    }
    inputOldPass.value = '';
    inputNewPass.value = '';
    inputConfirmPass.value = '';
};

const eliminarCuenta = () => {
    triggerConfirm({
        title: 'Eliminar Cuenta',
        message: '¿Estás seguro de que deseas eliminar permanentemente tu cuenta de Cronos Notes?',
        confirmText: 'Continuar',
        isDanger: true,
        onConfirm: () => {
            setTimeout(() => {
                triggerConfirm({
                    title: 'Confirmación Final',
                    message: 'Esta acción es irreversible y borrará tus estadísticas de racha, apuntes y perfiles. ¿Proceder?',
                    confirmText: 'Confirmar Eliminación',
                    isDanger: true,
                    onConfirm: () => {
                        showCustomAlert("Cuenta Eliminada", "Cuenta dada de baja simuladamente.");
                        setTimeout(() => {
                          window.location.reload(); 
                        }, 2000);
                    }
                });
            }, 300);
        }
    });
};
</script>

<template>
  <Head title="Mi Perfil" />
  <AppLayout>
    <div class="main-layout-container p-3 p-md-4">
        
        <div class="mb-4 mt-2">
            <h1 style="font-size: 28px; font-weight: 700; color: #69342e; text-align: left; margin-left: -915px;">Mis Datos</h1>
            <p style="font-size: 15px; font-weight: 500; color: #a55e57;">Gestiona tus datos personales y la seguridad de tu cuenta de acceso.</p>
        </div>

        <div class="row g-4">
            
            <div class="col-12 col-md-4">
                
                <div class="card card-ajustes p-4 text-center mb-4">
                    <div class="mx-auto mb-3">
                        <i class="bi bi-person-circle" style="font-size: 5rem; color: #dee2e6; line-height: 1;"></i>
                    </div>
                    <h5 class="fw-bold m-0 text-dark">{{ displayName }}</h5>
                    <p class="text-secondary small mb-0">{{ email }}</p>
                </div>

            </div>

            <div class="col-12 col-md-8">
                <div class="card card-ajustes p-4">
                    
                    <div class="mb-4 pb-4 border-bottom">
                        <h6 class="text-uppercase text-secondary small fw-bold tracking-wider mb-3"><i class="bi bi-person-vcard me-2"></i>Información Personal</h6>
                        <form @submit.prevent="guardarDatosPersonales">
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label text-secondary small fw-medium">Nombre(s)</label>
                                    <input type="text" class="form-control input-custom" v-model="form.nombre" required>
                                    <div v-if="form.errors.nombre" class="text-danger small mt-1">{{ form.errors.nombre }}</div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label class="form-label text-secondary small fw-medium">Apellido(s)</label>
                                    <input type="text" class="form-control input-custom" v-model="form.apellido" required>
                                    <div v-if="form.errors.apellido" class="text-danger small mt-1">{{ form.errors.apellido }}</div>
                                </div>

                                <!-- Password Confirmation Field (visible only if name/surname is changed) -->
                                <Transition
                                    enter-active-class="transition duration-300 ease-out"
                                    enter-from-class="transform -translate-y-2 opacity-0"
                                    enter-to-class="transform translate-y-0 opacity-100"
                                    leave-active-class="transition duration-200 ease-in"
                                    leave-from-class="transform translate-y-0 opacity-100"
                                    leave-to-class="transform -translate-y-2 opacity-0"
                                >
                                    <div v-if="isNameOrSurnameChanged" class="col-12">
                                        <label class="form-label text-secondary small fw-medium">Confirmar con contraseña para cambiar nombre o apellido</label>
                                        <input
                                            type="password"
                                            class="form-control input-custom"
                                            v-model="form.password"
                                            :required="isNameOrSurnameChanged"
                                            placeholder="Ingresá tu contraseña actual"
                                        />
                                        <div v-if="form.errors.password" class="text-danger small mt-1">{{ form.errors.password }}</div>
                                    </div>
                                </Transition>

                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-marron px-4 py-2" :disabled="form.processing">
                                        <i class="bi bi-check-lg me-1"></i> Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="pb-4">
                        <h6 class="text-uppercase text-secondary small fw-bold tracking-wider mb-3"><i class="bi bi-shield-lock me-2"></i>Seguridad de la Cuenta</h6>
                        <p class="text-secondary small mb-3">Hacé clic en cualquiera de los botones para desplegar de forma segura la ventana emergente correspondiente.</p>
                        
                        <div class="row g-2">
                            <div class="col-12 col-sm-6">
                                <button class="btn btn-outline-marron w-100 py-2.5 px-3 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCambiarCorreo">
                                    <i class="bi bi-envelope me-1"></i> Cambiar Correo Electrónico
                                </button>
                            </div>
                            <div class="col-12 col-sm-6">
                                <button class="btn btn-outline-marron w-100 py-2.5 px-3 d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCambiarPassword">
                                    <i class="bi bi-key me-1"></i> Modificar Contraseña
                                </button>
                            </div>
                        </div>

                        <div class="pt-4 border-top mt-5 border-danger border-opacity-25">
                            <h6 class="text-danger fw-bold mb-1 small text-uppercase tracking-wider">Baja del Sistema</h6>
                            <p class="text-secondary small mb-3">Si decidís darte de baja, se eliminarán permanentemente tus perfiles asignados, notas, temporizadores y estadísticas acumuladas.</p>
                            <button class="btn btn-danger btn-sm px-3 py-2" @click="eliminarCuenta"><i class="bi bi-exclamation-triangle me-2"></i>Eliminar mi cuenta definitivamente</button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <Teleport to="body">
        <div class="modal fade" id="modalCambiarCorreo" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-esbelto-popup">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-envelope-at text-secondary me-2"></i>Actualizar Correo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="cambiarCorreo">
                        <div class="modal-body px-4 pb-3">
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Dirección de correo actual</label>
                                <input type="email" class="form-control input-custom" :value="email" disabled>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-secondary small fw-medium">Nuevo correo electrónico *</label>
                                <input type="email" class="form-control input-custom" v-model="inputNewEmail" placeholder="ejemplo@correo.com" required>
                            </div>
                            <p class="text-muted m-0" style="font-size: 0.78rem; line-height: 1.3;">Se enviará un enlace de verificación a la dirección especificada para validar los datos antes del cambio definitivo.</p>
                        </div>
                        <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light-custom px-3 py-2" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-marron px-4 py-2">Enviar Enlace</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalCambiarPassword" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-esbelto-popup">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-shield-lock text-secondary me-2"></i>Modificar Contraseña</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="cambiarPassword">
                        <div class="modal-body px-4 pb-2">
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Contraseña actual *</label>
                                <input type="password" class="form-control input-custom" v-model="inputOldPass" placeholder="••••••••" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Nueva contraseña *</label>
                                <input type="password" class="form-control input-custom" v-model="inputNewPass" placeholder="Mínimo 8 caracteres" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-secondary small fw-medium">Confirmar nueva contraseña *</label>
                                <input type="password" class="form-control input-custom" v-model="inputConfirmPass" placeholder="Repetí la contraseña" required>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 px-4 pb-4 pt-2 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light-custom px-3 py-2" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-marron px-4 py-2">Cambiar Contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <AlertModal 
          :show="showAlertModal" 
          :title="alertTitle" 
          :message="alertMessage" 
          @close="showAlertModal = false" 
        />
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
    </Teleport>
  </AppLayout>
</template>

<style>
/* VARIABLES GLOBALES CRONOS NOTES */
:root {
    --text-marron-institucional: #69342e;
    --text-marron-hover: #542924;
    --borde-card-color: #dee2e6;
}

/* CONTENEDOR ANCHO INTEGRABLE */
.main-layout-container {
    width: 100%;
    max-width: 1140px;
    margin: 0 auto;
}

/* BOTONES CON IDENTIDAD MARRÓN DE LA APP */
.btn-marron {
    background-color: var(--text-marron-institucional);
    color: #ffffff; border: none; border-radius: 8px;
    font-weight: 500; font-size: 0.88rem;
    transition: background-color 0.15s ease-in-out;
}
.btn-marron:hover, .btn-marron:focus {
    background-color: var(--text-marron-hover); color: #ffffff;
}

.btn-outline-marron {
    background-color: transparent;
    border: 1px solid var(--text-marron-institucional);
    color: var(--text-marron-institucional); border-radius: 8px;
    font-weight: 500; font-size: 0.88rem;
    transition: all 0.15s ease;
}
.btn-outline-marron:hover {
    background-color: #fdf8f7;
    color: var(--text-marron-hover);
    border-color: var(--text-marron-hover);
}

.btn-light-custom {
    background-color: #e9ecef; color: #495057;
    border: none; border-radius: 8px;
    font-weight: 500; font-size: 0.85rem;
}
.btn-light-custom:hover { background-color: #dee2e6; }

/* ESTILOS DE CARDS LIMPIAS */
.card-ajustes {
    border-radius: 16px;
    border: 1px solid var(--borde-card-color);
    background-color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.01);
}

/* DIMENSIONES DEL AVATAR */
.profile-avatar-lg {
    width: 105px; height: 105px;
    object-fit: cover;
}

.btn-edit-photo {
    padding: 6px 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.12);
}

/* COMPONENTE GAMIFICADO DE RACHA */
.bg-racha-streak {
    background: linear-gradient(145deg, #fffbfb 0%, #ffffff 100%);
    border: 2px solid #fcdbda;
}

/* INPUTS FORMULARIOS */
.input-custom {
    border-radius: 8px;
    padding: 10px 14px;
    border: 1px solid #ced4da;
    font-size: 0.9rem;
}
.input-custom:focus {
    box-shadow: 0 0 0 3px rgba(105, 52, 46, 0.12);
    border-color: var(--text-marron-institucional);
}

/* MODALES VERTICALES ESBELTOS */
.modal-esbelto-popup {
    max-width: 400px;
    margin: 1.75rem auto;
}

/* ANIMACIONES COMPLEMENTARIAS */
.animate-pulse {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.06); }
    100% { transform: scale(1); }
}

/* AJUSTES RESPONSIVOS MÓVILES (Celulares y Tablets) */
@media (max-width: 768px) {
    .btn-outline-marron, .btn-marron {
        width: 100%;
    }
    .modal-esbelto-popup {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
}
</style>