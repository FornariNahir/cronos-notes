<script setup>
import { onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import AlertModal from '@/Components/AlertModal.vue';

import { ref } from 'vue';
const showAlertModal = ref(false);
const alertTitle = ref('');
const alertMessage = ref('');
const showCustomAlert = (title, message) => {
  alertTitle.value = title;
  alertMessage.value = message;
  showAlertModal.value = true;
};

const props = defineProps({
    tareasCargadas: {
        type: Array,
        default: () => []
    },
    perfilesDisponibles: {
        type: Array,
        default: () => []
    }
});

onMounted(() => {
    // 1. Cargar dinámicamente los iconos de Bootstrap si no están en el head
    if (!document.querySelector('link[href*="bootstrap-icons"]')) {
        const linkIcons = document.createElement('link');
        linkIcons.rel = 'stylesheet';
        linkIcons.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
        document.head.appendChild(linkIcons);
    }

    // 2. Cargar dinámicamente el JS de Bootstrap si no está cargado
    if (!window.bootstrap) {
        const scriptBS = document.createElement('script');
        scriptBS.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js';
        scriptBS.onload = () => {
            inicializarLogicaDOM();
        };
        document.head.appendChild(scriptBS);
    } else {
        setTimeout(() => {
            inicializarLogicaDOM();
        }, 50);
    }
});

function inicializarLogicaDOM() {
    // 1. BASE DE DATOS LOCAL TEMPORAL (ahora usa la data inyectada desde Inertia)
    let tareasDB = [...props.tareasCargadas];

    // Variables de control cronológico
    let fechaActual = new Date(); 
    let tareaSeleccionadaId = null;

    // Componentes del DOM
    const grillaDiasContainer = document.getElementById("grilla-dias-container");
    const displayMesAño = document.getElementById("display-mes-año");
    const formTarea = document.getElementById("formNuevaTareaCalendario");
    const inputDate = document.getElementById("input-task-date");
    const modalCrearElement = document.getElementById("modalAñadirTareaCalendario");
    const modalDetalleElement = document.getElementById("modalVerDetalleTarea");

    if (!grillaDiasContainer || !displayMesAño || !formTarea || !inputDate || !modalCrearElement || !modalDetalleElement) {
        console.warn("Algunos elementos del DOM no están listos en Calendario.");
        return;
    }

    // Instanciación única controlada de Modales Bootstrap (Previene bloqueos de pantalla)
    const modalCrear = new bootstrap.Modal(modalCrearElement);
    const modalDetalle = new bootstrap.Modal(modalDetalleElement);

    // Mapeo de nombres de meses en español
    const mesesNombres = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

    // ==========================================
    // RESTRICCIÓN: Bloqueo de fechas anteriores en el Mini-Calendario
    // ==========================================
    function configurarRestriccionFecha() {
        const hoy = new Date();
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd = String(hoy.getDate()).padStart(2, '0');
        const fechaMinimaFormato = `${yyyy}-${mm}-${dd}`;
        inputDate.setAttribute("min", fechaMinimaFormato); // No permite seleccionar fechas anteriores a hoy
    }

    // ==========================================
    // MOTOR DE RENDERIZADO DEL CALENDARIO DINÁMICO
    // ==========================================
    function renderizarCalendario() {
        grillaDiasContainer.innerHTML = ""; // Limpiar vista anterior
        
        const año = fechaActual.getFullYear();
        const mes = fechaActual.getMonth();

        // Actualizar etiqueta superior
        displayMesAño.textContent = `${mesesNombres[mes]} ${año}`;

        // Obtener primer día del mes y cantidad total de días
        const primerDiaIndex = new Date(año, mes, 1).getDay(); 
        const totalDiasMes = new Date(año, mes + 1, 0).getDate();
        const totalDiasMesAnterior = new Date(año, mes, 0).getDate();

        // Convertir index de Domingo (0) a formato que empiece el Lunes (6)
        let diasFaltantesLunes = primerDiaIndex === 0 ? 6 : primerDiaIndex - 1;

        // 1. Renderizar días remanentes del mes anterior (Grisados)
        for (let i = diasFaltantesLunes; i > 0; i--) {
            const div = document.createElement("div");
            div.className = "celda-dia dia-fuera-rango";
            div.innerHTML = `<span class="num-dia">${totalDiasMesAnterior - i + 1}</span>`;
            grillaDiasContainer.appendChild(div);
        }

        // 2. Renderizar días reales del mes activo
        const hoy = new Date();
        for (let dia = 1; dia <= totalDiasMes; dia++) {
            const div = document.createElement("div");
            div.className = "celda-dia";
            
            // Evaluar si es el día de hoy
            if (hoy.getDate() === dia && hoy.getMonth() === mes && hoy.getFullYear() === año) {
                div.classList.add("dia-hoy-resaltado");
            }

            div.innerHTML = `<span class="num-dia">${dia}</span>`;

            // Contenedor interno para los items de tareas del casillero
            const listaTareasContenedor = document.createElement("div");
            listaTareasContenedor.className = "lista-tareas-celda";
            div.appendChild(listaTareasContenedor);

            // Armar string de fecha actual para contrastar con la Base de Datos (Formato YYYY-MM-DD)
            const mesFormateado = String(mes + 1).padStart(2, '0');
            const diaFormateado = String(dia).padStart(2, '0');
            const stringFechaFiltro = `${año}-${mesFormateado}-${diaFormateado}`;

            // Filtrar y renderizar tareas asignadas a este día específico
            const tareasDelDia = tareasDB.filter(t => t.fecha === stringFechaFiltro);
            tareasDelDia.forEach(tarea => {
                const bloqueTarea = document.createElement("div");
                let clasePrioridad = tarea.prioridad === "Alta" ? "t-alta" : (tarea.prioridad === "Media" ? "t-media" : "t-baja");
                
                bloqueTarea.className = `bloque-tarea-item ${clasePrioridad}`;
                bloqueTarea.textContent = tarea.nombre;
                bloqueTarea.title = tarea.nombre;

                // EVENTO CLICK: Abre el modal con las características exactas de la tarea seleccionada
                bloqueTarea.addEventListener("click", function (e) {
                    e.stopPropagation(); // Evitar disparadores de la celda completa
                    abrirModalDetalle(tarea);
                });

                listaTareasContenedor.appendChild(bloqueTarea);
            });

            grillaDiasContainer.appendChild(div);
        }

        // 3. Rellenar el final de la grilla con días del mes siguiente si falta espacio
        const celdasTotalesOcupadas = diasFaltantesLunes + totalDiasMes;
        const celdasRestantesSiguienteMes = celdasTotalesOcupadas % 7 === 0 ? 0 : 7 - (celdasTotalesOcupadas % 7);
        for (let i = 1; i <= celdasRestantesSiguienteMes; i++) {
            const div = document.createElement("div");
            div.className = "celda-dia dia-fuera-rango";
            div.innerHTML = `<span class="num-dia">${i}</span>`;
            grillaDiasContainer.appendChild(div);
        }
    }

    // ==========================================
    // ACCIÓN: VENTANA EMERGENTE DE DETALLE
    // ==========================================
    function abrirModalDetalle(tarea) {
        tareaSeleccionadaId = tarea.id;
        document.getElementById("detalle-titulo").textContent = tarea.nombre;
        document.getElementById("detalle-perfil-tag").textContent = tarea.perfil;
        document.getElementById("detalle-prioridad-tag").textContent = tarea.prioridad;
        document.getElementById("detalle-descripcion").textContent = tarea.desc || "Sin descripción adicional.";
        
        // Formatear fecha para legibilidad humana en el modal
        const partes = tarea.fecha.split("-");
        document.getElementById("detalle-fecha").textContent = `${partes[2]}/${partes[1]}/${partes[0]}`;

        // Seteo dinámico de colores del badge de prioridad en la ventana emergente
        const tagPrioridad = document.getElementById("detalle-prioridad-tag");
        tagPrioridad.className = "badge rounded-pill px-2.5 py-1 small fw-medium text-white";
        if (tarea.prioridad === "Alta") tagPrioridad.classList.add("bg-danger");
        else if (tarea.prioridad === "Media") tagPrioridad.classList.add("bg-warning", "text-dark");
        else tagPrioridad.classList.add("bg-primary");

        modalDetalle.show();
    }

    // ==========================================
    // MANEJO DE EVENTOS DE FORMULARIOS Y CONTROLADORES
    // ==========================================
    
    // Lanzar creación manual limpia
    const btnAbrirCrear = document.getElementById("btn-abrir-crear");
    if (btnAbrirCrear) {
        btnAbrirCrear.addEventListener("click", function() {
            formTarea.reset();
            document.getElementById("modal-select-perfil").selectedIndex = 0;
            modalCrear.show();
        });
    }

    // Observar los cambios que provengan del servidor cuando agregamos o borramos tareas
    watch(() => props.tareasCargadas, (newTareas) => {
        tareasDB = [...newTareas];
        renderizarCalendario();
    }, { deep: true });

    // Guardado de nueva tarea e inserción inmediata reactiva
    formTarea.addEventListener("submit", function (e) {
        e.preventDefault();

        const perfilSelect = document.getElementById("modal-select-perfil");
        if (!perfilSelect || !perfilSelect.value) {
            showCustomAlert("Atención", "Por favor, selecciona un perfil para la tarea.");
            return;
        }

        router.post(route('tareas.store'), {
            idPerfil: perfilSelect.value,
            tituloTarea: document.getElementById("input-task-name").value,
            descripcionTarea: document.getElementById("input-task-desc").value,
            fechaLimite: inputDate.value,
            prioridadTarea: document.getElementById("input-task-priority").value,
            estimacionEsfuerzo: 1 // o un default si no está
        }, {
            onSuccess: () => {
                modalCrear.hide();
                formTarea.reset();
            }
        });
    });

    // Acción de eliminar tarea desde la ventana emergente de detalles
    const btnEliminarTarea = document.getElementById("btn-eliminar-tarea");
    if (btnEliminarTarea) {
        btnEliminarTarea.addEventListener("click", function () {
            if (tareaSeleccionadaId && confirm("¿Estás seguro de eliminar esta tarea del calendario de Cronos?")) {
                router.delete(route('tareas.destroy', tareaSeleccionadaId), {
                    onSuccess: () => {
                        modalDetalle.hide();
                        tareaSeleccionadaId = null;
                    }
                });
            }
        });
    }

    // Controladores dinámicos de las flechas de navegación de meses
    const btnPrev = document.getElementById("btn-prev-mes");
    if (btnPrev) {
        btnPrev.addEventListener("click", function () {
            fechaActual.setMonth(fechaActual.getMonth() - 1);
            renderizarCalendario();
        });
    }

    const btnNext = document.getElementById("btn-next-mes");
    if (btnNext) {
        btnNext.addEventListener("click", function () {
            fechaActual.setMonth(fechaActual.getMonth() + 1);
            renderizarCalendario();
        });
    }

    // inicializar configuraciones del sistema
    configurarRestriccionFecha();
    renderizarCalendario();
}
</script>

<template>
  <AppLayout>
    <div class="main-layout-calendario p-3 p-md-4">
        
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h1 style="font-size: 28px; font-weight: 700; color: #69342e; text-align: left; margin-left: -2px;">Calendario de Tareas</h1>
                <p style="font-size: 15px; font-weight: 500; color: #a55e57;">Organiza y prioriza tus entregas.</p>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="btn-group shadow-sm bg-white rounded-3 p-1 border">
                    <button class="btn btn-nav-mes py-1 px-2.5" id="btn-prev-mes"><i class="bi bi-chevron-left"></i></button>
                    <span class="fw-semibold text-dark px-3 align-self-center text-month-year" id="display-mes-año">Junio 2026</span>
                    <button class="btn btn-nav-mes py-1 px-2.5" id="btn-next-mes"><i class="bi bi-chevron-right"></i></button>
                </div>
                <button class="btn btn-marron d-flex align-items-center gap-2 px-3 py-2 shadow-sm" id="btn-abrir-crear" type="button">
                    <i class="bi bi-plus-lg"></i> Agregar nueva tarea
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="grid-semana-cabecera bg-white border-bottom text-center py-2 text-secondary small fw-medium">
                <div>Lun</div>
                <div>Mar</div>
                <div>Mié</div>
                <div>Jue</div>
                <div>Vie</div>
                <div>Sáb</div>
                <div>Dom</div>
            </div>

            <div class="grid-mes-cuerpo bg-light" id="grilla-dias-container"></div>
        </div>
    </div>

    <Teleport to="body">
        <!-- Modal Añadir Tarea -->
        <div class="modal fade" id="modalAñadirTareaCalendario" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-esbelto-vertical">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 bg-white pt-4 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="modal-title fw-bold text-dark fs-4 m-0">Nueva Tarea</h5>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select select-perfil-header" id="modal-select-perfil" form="formNuevaTareaCalendario" required>
                                <option value="" selected disabled>Seleccione Perfil</option>
                                <option v-for="perfil in perfilesDisponibles" :key="perfil.idPerfil" :value="perfil.idPerfil">{{ perfil.tituloPerfil }}</option>
                            </select>
                            <button type="button" class="btn-close fs-small" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>

                    <div class="modal-body px-4 pb-4 bg-white">
                        <form id="formNuevaTareaCalendario">
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">
                                    Nombre de la tarea <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control input-custom-style" id="input-task-name" placeholder="Ej: Revisar informe mensual" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Descripción</label>
                                <textarea class="form-control input-custom-style text-area-resize" id="input-task-desc" rows="3" placeholder="Añade detalles adicionales sobre esta tarea..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-medium">Fecha límite <span class="text-danger">*</span></label>
                                <div class="position-relative container-date-icon">
                                    <input type="date" class="form-control input-custom-style" id="input-task-date" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-secondary small fw-medium">Prioridad</label>
                                <select class="form-select select-custom-style" id="input-task-priority" required>
                                    <option value="" selected disabled>Elige la prioridad</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Media">Media</option>
                                    <option value="Baja">Baja</option>
                                </select>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-2">
                                <button type="button" class="btn btn-cancelar-modal px-4 py-2" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-guardar-modal px-4 py-2">Guardar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Ver Detalle -->
        <div class="modal fade" id="modalVerDetalleTarea" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-esbelto-vertical">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="header-detalle-marron p-4 text-white position-relative">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge badge-code-proy px-2.5 py-1 text-uppercase fw-bold" id="detalle-perfil-tag">Perfil</span>
                            <span class="badge bg-white text-dark rounded-pill px-2.5 py-1 small fw-medium" id="detalle-prioridad-tag">Prioridad</span>
                        </div>
                        <h3 class="h4 fw-bold m-0 text-white" id="detalle-titulo">Título de la Tarea</h3>
                        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-white">
                        <div class="mb-4">
                            <h6 class="text-uppercase text-secondary small fw-bold tracking-wider mb-2"><i class="bi bi-file-text me-1"></i> Descripción</h6>
                            <p class="text-muted small lh-base m-0" id="detalle-descripcion">Sin descripción disponible.</p>
                        </div>
                        <div class="mb-4 pt-2 border-top">
                            <div class="small text-uppercase text-secondary fw-bold">Fecha Límite</div>
                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark small mt-1">
                                <i class="bi bi-calendar text-danger"></i> <span id="detalle-fecha">--/--/----</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <button class="btn btn-outline-danger btn-sm px-3" id="btn-eliminar-tarea"><i class="bi bi-trash me-1"></i> Eliminar Tarea</button>
                            <button class="btn btn-cancelar-modal btn-sm px-3" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
  </Teleport>

    <AlertModal 
      :show="showAlertModal" 
      :title="alertTitle" 
      :message="alertMessage" 
      @close="showAlertModal = false" 
    />
  </AppLayout>
</template>

<style>
/* PALETA DE COLORES INSTITUCIONAL */
:root {
    --text-marron-institucional: #69342e;
    --text-marron-hover: #542924;
    --borde-celda-color: #dee2e6;
    --bg-gris-fuera-rango: #f8f9fa;
    
    /* Colores para las prioridades de tareas en los casilleros */
    --alta-bg: #f4be95; --alta-borde: #612c2d;
    --media-bg: #f4be95; --media-borde: #612c2d;
    --baja-bg: #f4be95; --baja-borde: #612c2d;
}

.main-layout-calendario {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

/* BOTONES Y INTERFAZ */
.btn-marron {
    background-color: var(--text-marron-institucional);
    color: #fff; border: none; border-radius: 8px;
    font-weight: 500; font-size: 0.9rem;
    transition: background-color 0.2s ease;
}
.btn-marron:hover { background-color: var(--text-marron-hover); color: #fff; }

.btn-nav-mes { border: none; background: transparent; color: #495057; transition: background-color 0.15s; }
.btn-nav-mes:hover { background-color: #e9ecef; border-radius: 6px; }

/* REJILLA DINÁMICA DEL CALENDARIO */
.grid-semana-cabecera {
    display: grid; grid-template-columns: repeat(7, 1fr); background-color: #fff;
}

.grid-mes-cuerpo {
    display: grid; grid-template-columns: repeat(7, 1fr);
    grid-auto-rows: minmax(120px, 1fr);
    gap: 1px; background-color: var(--borde-celda-color);
}

.celda-dia {
    background-color: #ffffff; padding: 8px;
    display: flex; flex-direction: column;
    justify-content: flex-start; align-items: flex-start;
    position: relative; overflow: hidden;
}

.num-dia { font-size: 0.9rem; font-weight: 600; color: #495057; margin-bottom: 6px; }
.dia-fuera-rango { background-color: var(--bg-gris-fuera-rango); }
.dia-fuera-rango .num-dia { color: #ced4da; }
.dia-hoy-resaltado { background-color: #fdf6f5; }
.dia-hoy-resaltado .num-dia { color: var(--text-marron-institucional); font-weight: 700; }

/* CONTENEDOR INTERNO DE TAREAS DENTRO DEL CASILLERO */
.lista-tareas-celda {
    width: 100%; display: flex; flex-direction: column; gap: 4px;
    overflow-y: auto; max-height: 80px;
}

/* COMPONENTE INTERACTIVO DE LA TAREA */
.bloque-tarea-item {
    font-size: 0.72rem; padding: 4px 6px; border-radius: 6px;
    font-weight: 600; white-space: nowrap; text-overflow: ellipsis;
    overflow: hidden; cursor: pointer; border-left: 3px solid transparent;
    transition: transform 0.15s ease;
}
.bloque-tarea-item:hover { transform: translateY(-1px); filter: brightness(0.95); }

.t-alta { background-color: var(--alta-bg); color: var(--alta-borde); border-left-color: var(--alta-borde); }
.t-media { background-color: var(--media-bg); color: var(--media-borde); border-left-color: var(--media-borde); }
.t-baja { background-color: var(--baja-bg); color: var(--baja-borde); border-left-color: var(--baja-borde); }

/* VENTANAS EMERGENTES */
.modal-esbelto-vertical { max-width: 420px; margin: 1.75rem auto; }
.modal-backdrop.show { opacity: 0.4 !important; }

.header-detalle-marron { background-color: #4c2521; }
.badge-code-proy { background-color: rgba(255, 255, 255, 0.15); color: #fff; }

.select-perfil-header {
    font-size: 0.8rem; font-weight: 600; background-color: #56241e; color: #fff;
    border: none; border-radius: 6px; padding: 4px 28px 4px 10px;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
}

.input-custom-style, .select-custom-style {
    border-radius: 8px; padding: 10px 14px; border: 1px solid #ced4da; font-size: 0.9rem;
}
.input-custom-style:focus, .select-custom-style:focus {
    border-color: var(--text-marron-institucional); box-shadow: 0 0 0 3px rgba(105, 52, 46, 0.15);
}

.btn-cancelar-modal { background-color: #f1f3f5; color: #495057; font-weight: 500; border: none; border-radius: 8px; }
.btn-guardar-modal { background-color: var(--text-marron-institucional); color: #fff; font-weight: 500; border: none; border-radius: 8px; }

@media (max-width: 768px) {
    .grid-mes-cuerpo { grid-auto-rows: minmax(90px, 1fr); }
    .modal-esbelto-vertical { margin: 1rem; max-width: calc(100% - 2rem); }
}
</style>