<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tareas: {
        type: Array,
        default: () => []
    },
    perfilActivo: {
        type: Object,
        default: () => null
    }
});

const esVistaGrid = ref(true);

const formTarea = useForm({
    tituloTarea: '',
    descripcionTarea: '',
    fechaLimite: '',
    prioridadTarea: 'Media',
    estadoTarea: 'Pendiente',
    estimacionEsfuerzo: 1
});

let modalGestionInstance = null;
let modalDetalleInstance = null;

const esModoEdicion = ref(false);
let tareaEditandoId = null;
const tareaDetalle = ref({});

// Estadísticas dinámicas calculadas desde las tareas
const cantidadPendientes = computed(() => props.tareas.filter(t => t.estadoTarea === 'Pendiente').length);
const cantidadProgreso = computed(() => props.tareas.filter(t => t.estadoTarea === 'En Progreso' || t.estadoTarea === 'En proceso').length);
const cantidadFinalizadas = computed(() => props.tareas.filter(t => t.estadoTarea === 'Completado').length);

onMounted(() => {
    if (!document.querySelector('link[href*="bootstrap-icons"]')) {
        const linkIcons = document.createElement('link');
        linkIcons.rel = 'stylesheet';
        linkIcons.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
        document.head.appendChild(linkIcons);
    }

    const initModals = () => {
        const modalGestionEl = document.getElementById('modalGestionTarea');
        const modalDetalleEl = document.getElementById('modalVerDetalle');
        if (window.bootstrap) {
            if (modalGestionEl) modalGestionInstance = new window.bootstrap.Modal(modalGestionEl);
            if (modalDetalleEl) modalDetalleInstance = new window.bootstrap.Modal(modalDetalleEl);
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

const formatDateInput = (dateStr) => {
    if (!dateStr) return '';
    return dateStr.split(/[ T]/)[0];
};

const formatDateView = (dateStr) => {
    if (!dateStr) return '';
    const date = dateStr.split(/[ T]/)[0];
    const [year, month, day] = date.split('-');
    const d = new Date(year, month - 1, day);
    return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

// Acciones de Gestión Modal
const abrirCrearTarea = () => {
    esModoEdicion.value = false;
    tareaEditandoId = null;
    formTarea.reset();
    formTarea.clearErrors();
    if (modalGestionInstance) modalGestionInstance.show();
};

const abrirEditarTarea = (tarea) => {
    esModoEdicion.value = true;
    tareaEditandoId = tarea.idTarea;
    formTarea.tituloTarea = tarea.tituloTarea;
    formTarea.descripcionTarea = tarea.descripcionTarea || '';
    formTarea.fechaLimite = formatDateInput(tarea.fechaLimite);
    formTarea.prioridadTarea = tarea.prioridadTarea;
    formTarea.estadoTarea = tarea.estadoTarea;
    formTarea.estimacionEsfuerzo = tarea.estimacionEsfuerzo || 1;
    formTarea.clearErrors();
    if (modalGestionInstance) modalGestionInstance.show();
};

const guardarTarea = () => {
    if (esModoEdicion.value) {
        formTarea.put(route('tareas.update', tareaEditandoId), {
            onSuccess: () => {
                if (modalGestionInstance) modalGestionInstance.hide();
                formTarea.reset();
            }
        });
    } else {
        formTarea.post(route('tareas.store'), {
            onSuccess: () => {
                if (modalGestionInstance) modalGestionInstance.hide();
                formTarea.reset();
            }
        });
    }
};

const eliminarTarea = (tarea) => {
    if (confirm(`¿Deseas eliminar de forma permanente la tarea "${tarea.tituloTarea}"?`)) {
        router.delete(route('tareas.destroy', tarea.idTarea));
    }
};

const abrirDetalle = (tarea) => {
    tareaDetalle.value = tarea;
    if (modalDetalleInstance) modalDetalleInstance.show();
};

const clasePrioridad = (prioridad) => {
    if (prioridad === 'Alta') return 'p-alta';
    if (prioridad === 'Media') return 'p-media';
    return 'p-baja';
};

const claseEstado = (estado) => {
    if (estado === 'Completado') return 'est-finalizada';
    if (estado === 'En Progreso' || estado === 'En proceso') return 'est-progreso';
    return 'est-pendiente';
};

const iconoEstado = (estado) => {
    if (estado === 'Completado') return 'bi-check-circle-fill';
    if (estado === 'En Progreso' || estado === 'En proceso') return 'bi-clock-history';
    return 'bi-dash-circle';
};

const textoEstado = (estado) => {
    if (estado === 'Completado') return 'Finalizada';
    if (estado === 'En Progreso' || estado === 'En proceso') return 'En proceso';
    return 'Pendiente';
};
</script>

<template>
  <AppLayout>
    <div class="main-layout-container p-3 p-md-4">
        
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4 mt-2">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Tareas por Perfil</h1>
                <p class="text-marron-institucional small fw-semibold m-0 mt-1">
                    <i class="bi bi-folder-fill me-1"></i> 
                    <span class="text-uppercase">{{ perfilActivo ? perfilActivo.tituloPerfil : 'Sin perfil seleccionado' }}</span>
                </p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group conmutador-vistas-box" role="group" aria-label="Cambiar vista">
                    <button type="button" class="btn btn-vista" :class="{'active': esVistaGrid}" @click="esVistaGrid = true" title="Vista Tarjetas">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </button>
                    <button type="button" class="btn btn-vista" :class="{'active': !esVistaGrid}" @click="esVistaGrid = false" title="Vista Fila Ancha">
                        <i class="bi bi-list-task"></i>
                    </button>
                </div>
                <button v-if="perfilActivo" class="btn btn-marron d-flex align-items-center gap-2 px-3 py-2" @click="abrirCrearTarea">
                    <i class="bi bi-plus-lg"></i> Agregar tarea
                </button>
            </div>
        </div>

        <div v-if="perfilActivo">
            <div v-if="tareas.length > 0" class="row g-3 g-md-4" :class="esVistaGrid ? 'vista-grid' : 'vista-lista'" id="contenedor-tareas">
                
                <div v-for="tarea in tareas" :key="tarea.idTarea" class="col-12 col-md-6 col-xl-4 item-tarea-col">
                    <div class="card card-tarea h-100 p-3 bg-white border">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold m-0 text-dark heading-titulo cursor-pointer btn-ver-detalle" @click="abrirDetalle(tarea)">
                                {{ tarea.tituloTarea }}
                            </h5>
                            <span class="badge badge-prioridad" :class="clasePrioridad(tarea.prioridadTarea)">{{ tarea.prioridadTarea }}</span>
                        </div>
                        <p class="text-secondary small text-desc flex-grow-1 cursor-pointer btn-ver-detalle" @click="abrirDetalle(tarea)">
                            {{ tarea.descripcionTarea || 'Sin descripción' }}
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <span class="badge badge-estado" :class="claseEstado(tarea.estadoTarea)">
                                <i class="bi me-1" :class="iconoEstado(tarea.estadoTarea)"></i> {{ textoEstado(tarea.estadoTarea) }}
                            </span>
                            <div class="small text-muted metadata-fecha">
                                <i class="bi bi-calendar-event me-1"></i> {{ formatDateView(tarea.fechaLimite) }}
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3 botonera-card">
                            <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-tarea" @click="abrirEditarTarea(tarea)">
                                <i class="bi bi-pencil me-1"></i> Editar
                            </button>
                            <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-tarea" @click="eliminarTarea(tarea)">
                                <i class="bi bi-trash me-1"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Empty State Tareas -->
            <div v-else class="text-center py-5">
                <div class="text-muted mb-3">
                    <i class="bi bi-card-checklist fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark">Sin tareas asignadas</h5>
                <p class="text-secondary small">Este perfil aún no cuenta con tareas pendientes o finalizadas.</p>
                <button class="btn btn-outline-secondary px-4 mt-2" @click="abrirCrearTarea">Añadir Tarea</button>
            </div>

            <footer class="d-flex gap-3 justify-content-start align-items-center mt-5 pt-3 border-top text-secondary small flex-wrap">
                <div class="d-flex align-items-center gap-1.5"><span class="indicador-dot dot-gray"></span> Pendientes: <strong class="text-dark">{{ cantidadPendientes }}</strong></div>
                <div class="d-flex align-items-center gap-1.5"><span class="indicador-dot dot-red"></span> En progreso: <strong class="text-dark">{{ cantidadProgreso }}</strong></div>
                <div class="d-flex align-items-center gap-1.5"><span class="indicador-dot dot-green"></span> Finalizadas: <strong class="text-dark">{{ cantidadFinalizadas }}</strong></div>
            </footer>
        </div>
        
        <!-- Empty State Perfil -->
        <div v-else class="text-center py-5">
            <div class="text-muted mb-3">
                <i class="bi bi-folder-x fs-1"></i>
            </div>
            <h5 class="fw-bold text-dark">No hay perfil activo</h5>
            <p class="text-secondary small">Debes activar un perfil de trabajo desde la Gestión de Perfiles para poder visualizar sus tareas.</p>
        </div>
    </div>

  <Teleport to="body">
    <!-- Modal Gestión Tarea -->
    <div class="modal fade" id="modalGestionTarea" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-custom-vertical">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark fs-4">{{ esModoEdicion ? 'Modificar Tarea' : 'Agregar Tarea' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <form @submit.prevent="guardarTarea">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Nombre de la tarea *</label>
                            <input type="text" v-model="formTarea.tituloTarea" class="form-control input-custom" placeholder="Ej. Trabajo 1" required>
                            <div v-if="formTarea.errors.tituloTarea" class="text-danger small mt-1">{{ formTarea.errors.tituloTarea }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Descripción</label>
                            <textarea v-model="formTarea.descripcionTarea" class="form-control input-custom" rows="3" placeholder="Detalles de las consignas..."></textarea>
                            <div v-if="formTarea.errors.descripcionTarea" class="text-danger small mt-1">{{ formTarea.errors.descripcionTarea }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Fecha límite *</label>
                            <input type="date" v-model="formTarea.fechaLimite" class="form-control input-custom" required>
                            <div v-if="formTarea.errors.fechaLimite" class="text-danger small mt-1">{{ formTarea.errors.fechaLimite }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Prioridad</label>
                            <select v-model="formTarea.prioridadTarea" class="form-select select-custom" required>
                                <option value="Alta">Alta</option>
                                <option value="Media">Media</option>
                                <option value="Baja">Baja</option>
                            </select>
                        </div>
                        <div v-if="esModoEdicion" class="mb-4">
                            <label class="form-label text-secondary small fw-medium">Estado</label>
                            <select v-model="formTarea.estadoTarea" class="form-select select-custom" required>
                                <option value="Pendiente">Pendiente</option>
                                <option value="En Progreso">En Progreso</option>
                                <option value="Completado">Completado</option>
                            </select>
                        </div>
                        <div class="d-flex flex-column gap-2 mt-4">
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

    <!-- Modal Ver Detalle -->
    <div class="modal fade" id="modalVerDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom-vertical">
            <div class="modal-content border-0 overflow-hidden shadow-xl rounded-4">
                <div class="header-detalle-marron p-4 text-white">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge badge-code-proy px-2.5 py-1 text-uppercase fw-bold">{{ perfilActivo ? perfilActivo.tituloPerfil : '' }}</span>
                        <span class="badge bg-white text-dark rounded-pill px-2.5 py-1 small fw-medium">{{ tareaDetalle.prioridadTarea }}</span>
                    </div>
                    <h2 class="h4 fw-bold m-0 text-white">{{ tareaDetalle.tituloTarea }}</h2>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="mb-4">
                        <h6 class="text-uppercase text-secondary small fw-bold tracking-wider mb-2"><i class="bi bi-file-text me-1"></i> Descripción</h6>
                        <p class="text-muted small lh-base m-0">{{ tareaDetalle.descripcionTarea || 'Sin descripción.' }}</p>
                    </div>
                    <div class="row g-3 mb-4 pt-2 border-top">
                        <div class="col-6">
                            <div class="small text-uppercase text-secondary fw-bold text-lbl">Fecha Límite</div>
                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark small mt-1">
                                <i class="bi bi-calendar text-danger"></i> <span>{{ formatDateView(tareaDetalle.fechaLimite) }}</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-uppercase text-secondary fw-bold text-lbl">Estado Actual</div>
                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark small mt-1">
                                <i class="bi bi-info-circle text-marron-institucional"></i> <span>{{ textoEstado(tareaDetalle.estadoTarea) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2 pt-3 border-top">
                        <button class="btn btn-marron w-100 py-2 fw-medium" data-bs-dismiss="modal">Entendido</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </Teleport>

    <!-- Botón Flotante Agregar Tarea -->
    <button v-if="perfilActivo" class="btn btn-marron btn-flotante-agregar shadow" @click="abrirCrearTarea" title="Agregar Tarea">
        <i class="bi bi-plus-lg fs-4"></i>
    </button>
  </AppLayout>
</template>

<style>
/* PALETA DE COLORES OFICIAL CRONOS NOTES */
:root {
    --text-marron-institucional: #69342e;
    --text-marron-hover: #542924;
    --border-card-color: #e9ecef;
    
    /* Prioridades Badges */
    --p-alta-bg: #fce4e4; --p-alta-txt: #c92a2a;
    --p-media-bg: #fff4e6; --p-media-txt: #e67e22;
    --p-baja-bg: #ebfbee; --p-baja-txt: #2b8a3e;
}

.text-marron-institucional {
    color: #69342e !important;
}

.main-layout-container {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

.cursor-pointer {
    cursor: pointer;
}

/* BOTONES */
.btn-marron {
    background-color: #69342e;
    color: #fff; border: none; border-radius: 8px;
    font-weight: 500; font-size: 0.9rem;
    transition: background-color 0.2s;
}
.btn-marron:hover { background-color: #542924; color: #fff; }

/* VIEW CONMUTADOR BOX */
.conmutador-vistas-box {
    border: 1px solid #dee2e6; background-color: #fff;
    border-radius: 8px; padding: 2px;
}
.btn-vista {
    border: none; background: transparent; color: #6b7280;
    padding: 6px 12px; border-radius: 6px !important; transition: all 0.2s;
}
.btn-vista.active, .btn-vista:hover {
    background-color: #f3f4f6; color: #69342e;
}

/* CARDS DE TAREAS */
.card-tarea {
    border-radius: 12px;
    border-color: var(--border-card-color) !important;
    transition: box-shadow 0.2s ease-in-out, transform 0.2s ease;
}
.card-tarea:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.04);
}

.heading-titulo {
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.badge-prioridad {
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.75rem;
}
.p-alta { background-color: var(--p-alta-bg); color: var(--p-alta-txt); }
.p-media { background-color: var(--p-media-bg); color: var(--p-media-txt); }
.p-baja { background-color: var(--p-baja-bg); color: var(--p-baja-txt); }

.badge-estado {
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.8rem;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    color: #4b5563;
}
.est-finalizada { background-color: #ebfbee; color: #2b8a3e; border-color: #d3f9d8; }
.est-progreso { background-color: #fff3bf; color: #e67e22; border-color: #ffec99; }
.est-pendiente { background-color: #f8f9fa; color: #6c757d; border-color: #dee2e6; }

/* Control de vistas */
.vista-grid .text-desc {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 40px;
}

.vista-lista .item-tarea-col { width: 100% !important; }
.vista-lista .card-tarea {
    display: flex; flex-direction: row !important;
    align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 15px; padding: 15px 20px !important;
}
.vista-lista .heading-titulo { max-width: 200px; -webkit-line-clamp: 1; }
.vista-lista .text-desc { flex: 2 1 300px; margin: 0 !important; -webkit-line-clamp: 1; }
.vista-lista .botonera-card { flex: 0 0 auto; margin-top: 0 !important; width: 220px; }
.vista-lista .border-top { border: none !important; margin-top: 0 !important; padding-top: 0 !important; flex: 1 1 200px; }

/* FOOTER INDICADORES */
.indicador-dot {
    display: inline-block;
    width: 10px; height: 10px;
    border-radius: 50%;
}
.dot-gray { background-color: #adb5bd; }
.dot-red { background-color: #ff922b; }
.dot-green { background-color: #51cf66; }

/* MODALES */
.modal-custom-vertical {
    max-width: 420px;
    margin: 1.75rem auto;
}
.header-detalle-marron { background-color: #4c2521; }
.badge-code-proy {
    background-color: rgba(255,255,255,0.15);
    color: #fff; font-size: 0.7rem;
}
.text-lbl { font-size: 0.7rem; letter-spacing: 0.05em; }

.input-custom, .select-custom {
    border-radius: 8px; padding: 10px 12px;
    border: 1px solid #ced4da; font-size: 0.9rem;
}
.input-custom:focus, .select-custom:focus {
    box-shadow: 0 0 0 3px rgba(105, 52, 46, 0.15);
    border-color: #69342e;
}
.btn-light-custom {
    background-color: #f3f4f6; color: #4b5563;
}

/* BOTÓN FLOTANTE AGREGAR TAREA */
.btn-flotante-agregar {
    position: fixed;
    bottom: 24px; right: 24px;
    width: 56px; height: 56px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    z-index: 1030;
}
@media (max-width: 576px) {
    .vista-lista .card-tarea { flex-direction: column !important; align-items: stretch; }
    .vista-lista .botonera-card { width: 100%; margin-top: 15px !important; }
    .vista-lista .border-top { padding-top: 15px !important; border-top: 1px solid #e9ecef !important; }
    .modal-custom-vertical { margin: 1rem; max-width: calc(100% - 2rem); }
}
</style>
