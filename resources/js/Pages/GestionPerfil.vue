<script setup>
import { ref, onMounted, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    perfiles: {
        type: Array,
        default: () => []
    }
});

const esVistaGrid = ref(true);
const filtroActivo = ref('todos');

const perfilesFiltrados = computed(() => {
    if (filtroActivo.value === 'recientes') {
        return [...props.perfiles].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    }
    return props.perfiles;
});

const iconosDisponibles = [
    'bi-folder-fill', 'bi-briefcase-fill', 'bi-book-fill', 'bi-laptop', 
    'bi-star-fill', 'bi-heart-fill', 'bi-house-fill', 'bi-palette-fill', 
    'bi-rocket-takeoff-fill', 'bi-lightning-fill'
];

// Formularios Inertia
const formPerfil = useForm({
    tituloPerfil: '',
    descripcionPerfil: '',
    iconoPerfil: 'bi-folder-fill'
});

const formTarea = useForm({
    idPerfil: '',
    tituloTarea: '',
    descripcionTarea: '',
    fechaLimite: '',
    prioridadTarea: 'Media',
    estimacionEsfuerzo: 1
});

let modalPerfilInstance = null;
let modalTareaInstance = null;
const esModoEdicion = ref(false);
let perfilEditandoId = null;

onMounted(() => {
    // 1. Cargar dinámicamente los iconos de Bootstrap si no están en el head
    if (!document.querySelector('link[href*="bootstrap-icons"]')) {
        const linkIcons = document.createElement('link');
        linkIcons.rel = 'stylesheet';
        linkIcons.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
        document.head.appendChild(linkIcons);
    }

    // 2. Instanciar modales Bootstrap
    const initModals = () => {
        const modalPerfilEl = document.getElementById('modalCrearPerfil');
        const modalTareaEl = document.getElementById('modalAñadirTarea');
        if (window.bootstrap) {
            if (modalPerfilEl) modalPerfilInstance = new window.bootstrap.Modal(modalPerfilEl);
            if (modalTareaEl) modalTareaInstance = new window.bootstrap.Modal(modalTareaEl);
        }
    };

    if (!window.bootstrap) {
        const scriptBS = document.createElement('script');
        scriptBS.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';
        scriptBS.onload = () => initModals();
        document.head.appendChild(scriptBS);
    } else {
        setTimeout(() => initModals(), 50);
    }
});

// ACCIONES DE PERFILES
const abrirCrearPerfil = () => {
    esModoEdicion.value = false;
    perfilEditandoId = null;
    formPerfil.reset();
    formPerfil.iconoPerfil = 'bi-folder-fill';
    formPerfil.clearErrors();
    if (modalPerfilInstance) modalPerfilInstance.show();
};

const abrirEditarPerfil = (perfil) => {
    esModoEdicion.value = true;
    perfilEditandoId = perfil.idPerfil;
    formPerfil.tituloPerfil = perfil.tituloPerfil;
    formPerfil.descripcionPerfil = perfil.descripcionPerfil || '';
    formPerfil.iconoPerfil = perfil.iconoPerfil || 'bi-folder-fill';
    formPerfil.clearErrors();
    if (modalPerfilInstance) modalPerfilInstance.show();
};

const guardarPerfil = () => {
    if (esModoEdicion.value) {
        formPerfil.put(route('perfiles.update', perfilEditandoId), {
            onSuccess: () => {
                if (modalPerfilInstance) modalPerfilInstance.hide();
                formPerfil.reset();
            }
        });
    } else {
        formPerfil.post(route('perfiles.store'), {
            onSuccess: () => {
                if (modalPerfilInstance) modalPerfilInstance.hide();
                formPerfil.reset();
            }
        });
    }
};

const eliminarPerfil = (perfil) => {
    if (confirm(`¿Estás seguro de eliminar el perfil/espacio "${perfil.tituloPerfil}"? Se borrarán todas las tareas asociadas.`)) {
        router.delete(route('perfiles.destroy', perfil.idPerfil));
    }
};

const seleccionarPerfil = (idPerfil) => {
    router.post(route('perfiles.activar'), { 
        idPerfil: idPerfil,
        redirect: '/gestion-tareas'
    });
};

// ACCIONES DE TAREA
const abrirCrearTarea = () => {
    formTarea.reset();
    if (props.perfiles.length > 0) {
        formTarea.idPerfil = props.perfiles[0].idPerfil;
    }
    formTarea.clearErrors();
    if (modalTareaInstance) modalTareaInstance.show();
};

const guardarTarea = () => {
    formTarea.post(route('tareas.store'), {
        onSuccess: () => {
            if (modalTareaInstance) modalTareaInstance.hide();
            formTarea.reset();
            // Optional: Redirect to task management to see the newly created task
            // router.visit('/gestion-tareas');
        }
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return 'Recién creado';
    const d = new Date(dateStr);
    return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};
</script>

<template>
  <AppLayout>
    <div class="main-layout-container p-3 p-md-4">
        
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4 mt-2">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Gestión de Perfiles</h1>
                <p class="text-secondary small m-0 mt-1">Configura y personaliza los accesos de tus espacios de trabajo.</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group conmutador-vistas-box" role="group" aria-label="Cambiar tipo de vista">
                    <button type="button" class="btn btn-vista" :class="{ 'active': esVistaGrid }" @click="esVistaGrid = true" title="Vista de Tarjetas">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </button>
                    <button type="button" class="btn btn-vista" :class="{ 'active': !esVistaGrid }" @click="esVistaGrid = false" title="Vista de Lista Ancha">
                        <i class="bi bi-list-task"></i>
                    </button>
                </div>
                <button class="btn btn-marron d-flex align-items-center gap-2 px-3 py-2" @click="abrirCrearPerfil">
                    <i class="bi bi-plus-lg"></i> Crear perfil
                </button>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4 overflow-x-auto pb-2 barra-filtros-scroll">
            <button class="btn btn-filtro" :class="{ active: filtroActivo === 'todos' }" @click="filtroActivo = 'todos'">Todos los Perfiles</button>
            <button class="btn btn-filtro" :class="{ active: filtroActivo === 'recientes' }" @click="filtroActivo = 'recientes'">Recientes</button>
        </div>

        <div class="row g-3 g-md-4" :class="esVistaGrid ? 'vista-grid' : 'vista-lista'" id="contenedor-perfiles">
            
            <div v-for="perfil in perfilesFiltrados" :key="perfil.idPerfil" class="col-12 col-md-6 col-xl-4 item-perfil-col">
                <div class="card card-perfil h-100 p-3 bg-white border cursor-pointer" @click="seleccionarPerfil(perfil.idPerfil)">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2 header-card-titulo">
                            <div class="icon-box-perfil p-2 border rounded"><i :class="'bi ' + (perfil.iconoPerfil || 'bi-folder-fill') + ' text-marron fs-5'"></i></div>
                            <h5 class="fw-bold m-0 text-dark heading-titulo">{{ perfil.tituloPerfil }}</h5>
                        </div>
                        <i class="bi bi-three-dots-vertical text-secondary cursor-pointer" @click.stop></i>
                    </div>
                    <p class="text-secondary small text-desc flex-grow-1">{{ perfil.descripcionPerfil || 'Sin descripción' }}</p>
                    <div class="small text-muted mb-3 metadata-tiempo"><i class="bi bi-clock me-1"></i> Creado: {{ formatDate(perfil.created_at) }}</div>
                    <div class="d-flex gap-2 botonera-card">
                        <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-dinamico" @click.stop="abrirEditarPerfil(perfil)">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </button>
                        <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-dinamico" @click.stop="eliminarPerfil(perfil)">
                            <i class="bi bi-trash me-1"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="perfilesFiltrados.length === 0" class="col-12 text-center py-5">
                <div class="text-muted mb-3">
                    <i class="bi bi-inboxes fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark">Aún no tienes perfiles</h5>
                <p class="text-secondary small">Crea tu primer espacio de trabajo para comenzar a organizarte.</p>
                <button class="btn btn-marron px-4 mt-2" @click="abrirCrearPerfil">Crear mi primer perfil</button>
            </div>

        </div>
    </div>

    <!-- Botón Flotante Tarea -->
    <button class="btn btn-marron btn-flotante-tarea shadow" @click="abrirCrearTarea" title="Nueva Tarea">
        <i class="bi bi-plus-lg fs-5"></i>
    </button>

    <Teleport to="body">
        <!-- Modal Crear/Editar Perfil -->
        <div class="modal fade" id="modalCrearPerfil" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-custom-vertical">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-white border-bottom-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold text-dark fs-5">{{ esModoEdicion ? 'Modificar Perfil' : 'Crear Espacio / Perfil' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 pb-4">
                        <form @submit.prevent="guardarPerfil">
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Título del Perfil</label>
                                <input type="text" v-model="formPerfil.tituloPerfil" class="form-control input-custom" placeholder="Ej. Trabajo de Campo" required>
                                <div v-if="formPerfil.errors.tituloPerfil" class="text-danger small mt-1">{{ formPerfil.errors.tituloPerfil }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Icono del Perfil</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button 
                                        type="button" 
                                        v-for="icono in iconosDisponibles" 
                                        :key="icono"
                                        class="btn p-2 d-flex align-items-center justify-content-center border"
                                        :class="formPerfil.iconoPerfil === icono ? 'border-marron text-marron bg-light shadow-sm' : 'border-secondary-subtle text-secondary bg-white'"
                                        @click="formPerfil.iconoPerfil = icono"
                                        style="width: 42px; height: 42px; border-radius: 10px; transition: all 0.2s;">
                                        <i :class="'bi ' + icono + ' fs-5'"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Descripción Corta</label>
                                <textarea v-model="formPerfil.descripcionPerfil" class="form-control input-custom" rows="4" placeholder="Descripción de las actividades de este entorno..."></textarea>
                                <div v-if="formPerfil.errors.descripcionPerfil" class="text-danger small mt-1">{{ formPerfil.errors.descripcionPerfil }}</div>
                            </div>
                            <div class="d-flex flex-column gap-2 mt-4">
                                <button type="submit" class="btn btn-marron w-100 py-2" :disabled="formPerfil.processing">
                                    {{ formPerfil.processing ? 'Guardando...' : (esModoEdicion ? 'Guardar Cambios' : 'Crear Espacio') }}
                                </button>
                                <button type="button" class="btn btn-light-custom w-100 py-2" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Añadir Tarea -->
        <div class="modal fade" id="modalAñadirTarea" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-custom-vertical">
                <div class="modal-content border-0 shadow-lg rounded-4 scroll-modal-fijo">
                    <div class="modal-header bg-white border-bottom-0 pt-4 px-4">
                        <h5 class="modal-title fw-bold text-dark fs-5">Nueva Tarea</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 pb-4">
                        <form @submit.prevent="guardarTarea">
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Seleccionar Perfil</label>
                                <select v-model="formTarea.idPerfil" class="form-select select-custom" required>
                                    <option value="" disabled>Selecciona un perfil...</option>
                                    <option v-for="p in perfiles" :key="p.idPerfil" :value="p.idPerfil">{{ p.tituloPerfil }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Nombre de la tarea *</label>
                                <input type="text" v-model="formTarea.tituloTarea" class="form-control input-custom" placeholder="Ej. Desarrollar endpoints JWT" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Descripción</label>
                                <textarea v-model="formTarea.descripcionTarea" class="form-control input-custom" rows="3" placeholder="Detalles de la entrega..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Fecha límite *</label>
                                <input type="date" v-model="formTarea.fechaLimite" class="form-control input-custom" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-secondary small fw-medium">Prioridad</label>
                                <select v-model="formTarea.prioridadTarea" class="form-select select-custom" required>
                                    <option value="Alta">Alta</option>
                                    <option value="Media">Media</option>
                                    <option value="Baja">Baja</option>
                                </select>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <button type="submit" class="btn btn-marron w-100 py-2" :disabled="formTarea.processing">
                                    {{ formTarea.processing ? 'Guardando...' : 'Guardar Tarea' }}
                                </button>
                                <button type="button" class="btn btn-light-custom w-100 py-2" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
  </AppLayout>
</template>

<style>
/* PALETA DE COLORES INSTITUCIONAL CRONOS NOTES */
:root {
    --text-marron: #69342e;
    --text-marron-hover: #542924;
    --border-card-color: #e9ecef;
    --bg-filtro-active: #2c1a18;
}

.main-layout-container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

.cursor-pointer {
    cursor: pointer;
}

/* FILTROS DE CATEGORÍA CON SCROLL EN MÓVILES */
.barra-filtros-scroll::-webkit-scrollbar {
    display: none;
}
.barra-filtros-scroll {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.btn-filtro {
    background-color: #fff;
    border: 1px solid #dee2e6;
    color: #4b5563;
    border-radius: 20px;
    padding: 6px 16px;
    font-size: 0.85rem;
    font-weight: 500;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.btn-filtro.active, .btn-filtro:hover {
    background-color: #69342e;
    color: #fff;
    border-color: #69342e;
}

/* BOTONES GLOBALES */
.btn-marron {
    background-color: #69342e;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    transition: background-color 0.2s;
}

.btn-marron:hover, .btn-marron:focus {
    background-color: #542924;
    color: #fff;
}

/* CONMUTADOR DE VISTAS */
.conmutador-vistas-box {
    border: 1px solid #dee2e6;
    background-color: #fff;
    border-radius: 8px;
    padding: 2px;
}

.btn-vista {
    border: none;
    background: transparent;
    color: #6b7280;
    padding: 6px 12px;
    border-radius: 6px !important;
    transition: all 0.2s;
}

.btn-vista.active, .btn-vista:hover {
    background-color: #f3f4f6;
    color: #69342e;
}

/* CARDS DE PERFILES */
.card-perfil {
    border-radius: 14px;
    border-color: #e9ecef !important;
    transition: all 0.2s ease-in-out;
}

.card-perfil:hover {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}

.icon-box-perfil {
    background-color: #faf5f5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Control dinámico de cambio de vista */
.vista-grid .text-desc {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 54px;
}

.vista-lista .item-perfil-col {
    width: 100% !important;
}

.vista-lista .card-perfil {
    display: flex;
    flex-direction: row !important;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}

.vista-lista .header-card-titulo {
    flex: 1 1 250px;
}

.vista-lista .text-desc {
    flex: 2 1 350px;
    margin: 0 !important;
}

.vista-lista .botonera-card {
    flex: 1 1 180px;
}

/* MODALES */
.modal-custom-vertical {
    max-width: 420px;
    margin: 1.75rem auto;
}

@media (max-width: 576px) {
    .modal-custom-vertical {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    .vista-lista .card-perfil {
        flex-direction: column !important;
        align-items: stretch;
    }
}

.input-custom, .select-custom {
    border-radius: 8px;
    padding: 10px 12px;
    border: 1px solid #ced4da;
    font-size: 0.9rem;
    background-color: #fff;
    color: #212529;
}

.input-custom:focus, .select-custom:focus {
    box-shadow: 0 0 0 3px rgba(105, 52, 46, 0.15);
    border-color: #69342e;
}

.btn-light-custom {
    background-color: #f3f4f6;
    border: none;
    border-radius: 8px;
    color: #4b5563;
    font-weight: 500;
}

/* BOTÓN TAREA FLOTANTE */
.btn-flotante-tarea {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1030;
}

.scroll-modal-fijo .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
</style>