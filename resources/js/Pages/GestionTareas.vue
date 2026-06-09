<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

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
    const modalGestionElement = document.getElementById("modalGestionTarea");
    const modalDetalleElement = document.getElementById("modalVerDetalle");
    const formTarea = document.getElementById("formTarea");
    const contenedorTareas = document.getElementById("contenedor-tareas");
    const btnVistaGrid = document.getElementById("vista-grid-btn");
    const btnVistaList = document.getElementById("vista-list-btn");

    if (!modalGestionElement || !modalDetalleElement || !formTarea || !contenedorTareas || !btnVistaGrid || !btnVistaList) {
        console.warn("Algunos elementos del DOM no están listos en GestionTareas.");
        return;
    }
    
    // Instanciación controlada única de los modales de Bootstrap
    const modalGestionInstance = new bootstrap.Modal(modalGestionElement);
    const modalDetalleInstance = new bootstrap.Modal(modalDetalleElement);

    let esModoEdicion = false;
    let nodoTareaAEditar = null;

    // Actualizar restricciones de fecha (Bloquear pasadas)
    (function configurarFechas() {
        const hoy = new Date();
        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd = String(hoy.getDate()).padStart(2, '0');
        const fechaInput = document.getElementById("inputFechaTarea");
        if (fechaInput) {
            fechaInput.setAttribute("min", `${yyyy}-${mm}-${dd}`);
        }
    })();

    // 1. RECALCULAR BARRA DE RESUMEN INFERIOR AUTOMÁTICAMENTE
    function actualizarResumenContadores() {
        const totalPendientes = contenedorTareas.querySelectorAll(".est-pendiente").length;
        const totalProgreso = contenedorTareas.querySelectorAll(".est-progreso").length;
        const totalFinalizadas = contenedorTareas.querySelectorAll(".est-finalizada").length;
        
        document.getElementById("resumen-pendientes").textContent = totalPendientes;
        document.getElementById("resumen-progreso").textContent = totalProgreso;
        document.getElementById("resumen-finalizadas").textContent = totalFinalizadas;
    }

    // 2. ALTERNANCIA DE VISTAS RECONFIGURABLE
    btnVistaGrid.addEventListener("click", function () {
        btnVistaList.classList.remove("active");
        this.classList.add("active");
        contenedorTareas.classList.remove("vista-lista");
        contenedorTareas.classList.add("vista-grid");
    });

    btnVistaList.addEventListener("click", function () {
        btnVistaGrid.classList.remove("active");
        this.classList.add("active");
        contenedorTareas.classList.remove("vista-grid");
        contenedorTareas.classList.add("vista-lista");
    });

    // 3. RESET DE ESTADOS AL CREAR
    modalGestionElement.addEventListener("show.bs.modal", function (event) {
        const disparador = event.relatedTarget;
        if (!disparador || (disparador && disparador.id !== "disparador-js-edicion")) {
            esModoEdicion = false;
            nodoTareaAEditar = null;
            document.getElementById("tituloModalTarea").textContent = "Agregar Tarea";
            document.getElementById("btnGuardarTarea").textContent = "Guardar Tarea";
            formTarea.reset();
        }
    });

    // Disparadores de apertura rápida
    document.getElementById("btn-abrir-crear-tarea").addEventListener("click", () => modalGestionInstance.show());
    document.getElementById("btn-flotante-add").addEventListener("click", () => modalGestionInstance.show());

    // 4. GUARDAR / EDITAR ACCIÓN
    formTarea.addEventListener("submit", function (e) {
        e.preventDefault();

        const titulo = document.getElementById("inputTituloTarea").value;
        const descripcion = document.getElementById("inputDescTarea").value;
        const fecha = document.getElementById("inputFechaTarea").value;
        const prioridad = document.getElementById("inputPrioridadTarea").value;
        const estado = document.getElementById("inputEstadoTarea").value;

        // Mapeo de clases estilizadas según selecciones
        const pClase = prioridad === "Alta" ? "p-alta" : (prioridad === "Media" ? "p-media" : "p-baja");
        const eClase = estado === "Completado" ? "est-finalizada" : (estado === "En progreso" || estado === "En Progress" ? "est-progreso" : "est-pendiente");
        const eTexto = estado === "Completado" ? "Finalizada" : (estado === "En progreso" || estado === "En Progress" ? "En proceso" : "Pendiente");
        const eIcono = estado === "Completado" ? "bi-check-circle-fill" : (estado === "En progreso" || estado === "En Progress" ? "bi-clock-history" : "bi-dash-circle");

        if (esModoEdicion && nodoTareaAEditar) {
            nodoTareaAEditar.querySelector(".heading-titulo").textContent = titulo;
            nodoTareaAEditar.querySelector(".text-desc").textContent = descripcion;
            nodoTareaAEditar.querySelector(".metadata-fecha").innerHTML = `<i class="bi bi-calendar-event me-1"></i> ${fecha}`;
            
            const badgeP = nodoTareaAEditar.querySelector(".badge-prioridad");
            badgeP.className = `badge badge-prioridad ${pClase}`;
            badgeP.textContent = prioridad;

            const badgeE = nodoTareaAEditar.querySelector(".badge-estado");
            badgeE.className = `badge badge-estado ${eClase}`;
            badgeE.innerHTML = `<i class="bi ${eIcono} me-1"></i> ${eTexto}`;

            esModoEdicion = false;
            nodoTareaAEditar = null;
        } else {
            const nuevaCard = document.createElement("div");
            nuevaCard.className = "col-12 col-md-6 col-xl-4 item-tarea-col";
            nuevaCard.setAttribute("data-id", Date.now());
            nuevaCard.innerHTML = `
                <div class="card card-tarea h-100 p-3 bg-white border">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold m-0 text-dark heading-titulo cursor-pointer btn-ver-detalle">${titulo}</h5>
                        <span class="badge badge-prioridad ${pClase}">${prioridad}</span>
                    </div>
                    <p class="text-secondary small text-desc flex-grow-1 cursor-pointer btn-ver-detalle">${descripcion}</p>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="badge badge-estado ${eClase}"><i class="bi ${eIcono} me-1"></i> ${eTexto}</span>
                        <div class="small text-muted metadata-fecha"><i class="bi bi-calendar-event me-1"></i> ${fecha}</div>
                    </div>
                    <div class="d-flex gap-2 mt-3 botonera-card">
                        <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-tarea"><i class="bi bi-pencil me-1"></i> Editar</button>
                        <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-tarea"><i class="bi bi-trash me-1"></i> Eliminar</button>
                    </div>
                </div>
            `;
            contenedorTareas.appendChild(nuevaCard);
        }

        actualizarResumenContadores();
        formTarea.reset();
        modalGestionInstance.hide();
    });

    // 5. CAPTURA POR DELEGACIÓN DE EVENTOS (Eliminar, Editar y ver características)
    contenedorTareas.addEventListener("click", function (e) {
        const itemCol = e.target.closest(".item-tarea-col");
        if (!itemCol) return;

        // Acción: Eliminar
        if (e.target.classList.contains("btn-eliminar-tarea") || e.target.closest(".btn-outline-danger")) {
            if (confirm("¿Deseas eliminar de forma permanente esta tarea?")) {
                itemCol.remove();
                actualizarResumenContadores();
            }
            return;
        }

        // Acción: Editar
        if (e.target.classList.contains("btn-editar-tarea") || e.target.closest(".btn-editar-tarea")) {
            nodoTareaAEditar = itemCol;
            esModoEdicion = true;

            document.getElementById("inputTituloTarea").value = itemCol.querySelector(".heading-titulo").textContent;
            document.getElementById("inputDescTarea").value = itemCol.querySelector(".text-desc").textContent;
            
            // Extraer la fecha limpia
            const fechaTexto = itemCol.querySelector(".metadata-fecha").textContent.trim();
            document.getElementById("inputFechaTarea").value = fechaTexto;
            
            const pActual = itemCol.querySelector(".badge-prioridad").textContent.trim();
            const eActual = itemCol.querySelector(".badge-estado").textContent.trim();

            document.getElementById("inputPrioridadTarea").value = pActual;
            document.getElementById("inputEstadoTarea").value = eActual === "En proceso" ? "En progreso" : (eActual === "Finalizada" ? "Completado" : "Pendiente");

            document.getElementById("tituloModalTarea").textContent = "Modificar Tarea";
            document.getElementById("btnGuardarTarea").textContent = "Guardar Cambios";

            const falsoDisparador = document.createElement("div");
            falsoDisparador.id = "disparador-js-edicion";
            modalGestionInstance.show(falsoDisparador);
            return;
        }

        // Acción: Ver Características Generales al tocar el texto/título
        if (e.target.classList.contains("btn-ver-detalle") || e.target.classList.contains("heading-titulo") || e.target.classList.contains("text-desc")) {
            document.getElementById("det-titulo").textContent = itemCol.querySelector(".heading-titulo").textContent;
            document.getElementById("det-desc").textContent = itemCol.querySelector(".text-desc").textContent;
            document.getElementById("det-prioridad").textContent = itemCol.querySelector(".badge-prioridad").textContent;
            document.getElementById("det-fecha").textContent = itemCol.querySelector(".metadata-fecha").textContent.trim();
            document.getElementById("det-estado").textContent = itemCol.querySelector(".badge-estado").textContent.trim();
            
            modalDetalleInstance.show();
        }
    });

    actualizarResumenContadores();
}
</script>

<template>
  <AppLayout>
    <div class="main-layout-container p-3 p-md-4">
        
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4 mt-2">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Tareas por Perfil</h1>
                <p class="text-marron-institucional small fw-semibold m-0 mt-1">
                    <i class="bi bi-folder-fill me-1"></i> PROGRAMACIÓN - WEB
                </p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group conmutador-vistas-box" role="group" aria-label="Cambiar vista">
                    <button type="button" class="btn btn-vista active" id="vista-grid-btn" title="Vista Tarjetas">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </button>
                    <button type="button" class="btn btn-vista" id="vista-list-btn" title="Vista Fila Ancha">
                        <i class="bi bi-list-task"></i>
                    </button>
                </div>
                <button class="btn btn-marron d-flex align-items-center gap-2 px-3 py-2" id="btn-abrir-crear-tarea">
                    <i class="bi bi-plus-lg"></i> Agregar tarea
                </button>
            </div>
        </div>

        <div class="row g-3 g-md-4 vista-grid" id="contenedor-tareas">
            
            <div class="col-12 col-md-6 col-xl-4 item-tarea-col" data-id="1">
                <div class="card card-tarea h-100 p-3 bg-white border">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold m-0 text-dark heading-titulo cursor-pointer btn-ver-detalle">Trabajo 1</h5>
                        <span class="badge badge-prioridad p-alta">Alta</span>
                    </div>
                    <p class="text-secondary small text-desc flex-grow-1 cursor-pointer btn-ver-detalle">Implementar la autenticación de usuarios con JWT.</p>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="badge badge-estado est-progreso"><i class="bi bi-clock-history me-1"></i> En proceso</span>
                        <div class="small text-muted metadata-fecha"><i class="bi bi-calendar-event me-1"></i> 2026-05-20</div>
                    </div>
                    <div class="d-flex gap-2 mt-3 botonera-card">
                        <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-tarea"><i class="bi bi-pencil me-1"></i> Editar</button>
                        <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-tarea"><i class="bi bi-trash me-1"></i> Eliminar</button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-4 item-tarea-col" data-id="2">
                <div class="card card-tarea h-100 p-3 bg-white border">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold m-0 text-dark heading-titulo cursor-pointer btn-ver-detalle">Trabajo 2</h5>
                        <span class="badge badge-prioridad p-media">Media</span>
                    </div>
                    <p class="text-secondary small text-desc flex-grow-1 cursor-pointer btn-ver-detalle">Optimizar consultas a la base de datos.</p>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="badge badge-estado est-pendiente"><i class="bi bi-dash-circle me-1"></i> Pendiente</span>
                        <div class="small text-muted metadata-fecha"><i class="bi bi-calendar-event me-1"></i> 2026-05-25</div>
                    </div>
                    <div class="d-flex gap-2 mt-3 botonera-card">
                        <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-tarea"><i class="bi bi-pencil me-1"></i> Editar</button>
                        <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-tarea"><i class="bi bi-trash me-1"></i> Eliminar</button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-4 item-tarea-col" data-id="3">
                <div class="card card-tarea h-100 p-3 bg-white border">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold m-0 text-dark heading-titulo cursor-pointer btn-ver-detalle">Trabajo 3</h5>
                        <span class="badge badge-prioridad p-baja">Baja</span>
                    </div>
                    <p class="text-secondary small text-desc flex-grow-1 cursor-pointer btn-ver-detalle">Actualizar documentación de la API.</p>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="badge badge-estado est-finalizada"><i class="bi bi-check-circle-fill me-1"></i> Finalizada</span>
                        <div class="small text-muted metadata-fecha"><i class="bi bi-calendar-event me-1"></i> 2026-05-15</div>
                    </div>
                    <div class="d-flex gap-2 mt-3 botonera-card">
                        <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-tarea"><i class="bi bi-pencil me-1"></i> Editar</button>
                        <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-tarea"><i class="bi bi-trash me-1"></i> Eliminar</button>
                    </div>
                </div>
            </div>

        </div>

        <footer class="d-flex gap-3 justify-content-start align-items-center mt-5 pt-3 border-top text-secondary small">
            <div class="d-flex align-items-center gap-1.5"><span class="indicador-dot dot-gray"></span> Pendientes: <strong id="resumen-pendientes" class="text-dark">1</strong></div>
            <div class="d-flex align-items-center gap-1.5"><span class="indicador-dot dot-red"></span> En progreso: <strong id="resumen-progreso" class="text-dark">1</strong></div>
            <div class="d-flex align-items-center gap-1.5"><span class="indicador-dot dot-green"></span> Finalizadas: <strong id="resumen-finalizadas" class="text-dark">1</strong></div>
        </footer>
    </div>

  <Teleport to="body">
    <!-- Modal Gestión Tarea -->
    <div class="modal fade" id="modalGestionTarea" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-custom-vertical">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark fs-4" id="tituloModalTarea">Agregar Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <form id="formTarea">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Nombre de la tarea *</label>
                            <input type="text" id="inputTituloTarea" class="form-control input-custom" placeholder="Ej. Trabajo 1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Descripción</label>
                            <textarea id="inputDescTarea" class="form-control input-custom" rows="3" placeholder="Detalles de las consignas..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Fecha límite *</label>
                            <input type="date" id="inputFechaTarea" class="form-control input-custom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Prioridad</label>
                            <select id="inputPrioridadTarea" class="form-select select-custom" required>
                                <option value="Alta">Alta</option>
                                <option value="Media">Media</option>
                                <option value="Baja">Baja</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-medium">Estado</label>
                            <select id="inputEstadoTarea" class="form-select select-custom" required>
                                <option value="Pendiente">Pendiente</option>
                                <option value="En Progress">En Progreso</option>
                                <option value="Completado">Completado</option>
                            </select>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-marron w-100 py-2" id="btnGuardarTarea">Guardar Tarea</button>
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
                        <span class="badge badge-code-proy px-2.5 py-1 text-uppercase fw-bold">PROG - WEB</span>
                        <span class="badge bg-white text-dark rounded-pill px-2.5 py-1 small fw-medium" id="det-prioridad">Alta</span>
                    </div>
                    <h2 class="h4 fw-bold m-0 text-white" id="det-titulo">Nombre de Tarea</h2>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="mb-4">
                        <h6 class="text-uppercase text-secondary small fw-bold tracking-wider mb-2"><i class="bi bi-file-text me-1"></i> Descripción</h6>
                        <p class="text-muted small lh-base m-0" id="det-desc">Descripción detallada.</p>
                    </div>
                    <div class="row g-3 mb-4 pt-2 border-top">
                        <div class="col-6">
                            <div class="small text-uppercase text-secondary fw-bold text-lbl">Fecha Límite</div>
                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark small mt-1">
                                <i class="bi bi-calendar text-danger"></i> <span id="det-fecha">2026-05-20</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-uppercase text-secondary fw-bold text-lbl">Estado Actual</div>
                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark small mt-1">
                                <i class="bi bi-info-circle text-marron-institucional"></i> <span id="det-estado">En progreso</span>
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
    <button class="btn btn-marron btn-flotante-agregar shadow" id="btn-flotante-add" title="Agregar Tarea">
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

/* CARDS TAREAS EN GRID */
.card-tarea {
    border-radius: 14px;
    border-color: #e9ecef !important;
    transition: all 0.2s ease-in-out;
}
.card-tarea:hover {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.04);
}

.text-desc {
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
    overflow: hidden; min-height: 54px; line-height: 1.5;
}

/* BADGES DE PRIORIDAD */
.badge-prioridad {
    font-size: 0.75rem; font-weight: 600; padding: 4px 10px; border-radius: 20px;
}
.p-alta { background-color: #fce4e4; color: #c92a2a; }
.p-media { background-color: #fff4e6; color: #e67e22; }
.p-baja { background-color: #ebfbee; color: #2b8a3e; }

/* BADGES DE ESTADO */
.badge-estado {
    font-size: 0.75rem; font-weight: 500; padding: 4px 8px; border-radius: 6px;
}
.est-progreso { background-color: #f1eae8; color: #69342e; }
.est-pendiente { background-color: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6; }
.est-finalizada { background-color: #e6f4ea; color: #137333; }

/* DOTS INFERIORES DE RESUMEN */
.indicador-dot {
    width: 8px; height: 8px; border-radius: 50%; display: inline-block;
}
.dot-gray { background-color: #6c757d; }
.dot-red { background-color: #69342e; }
.dot-green { background-color: #137333; }

/* CONTROL DINÁMICO DE VISTA */
.vista-lista .item-tarea-col {
    width: 100% !important;
}
.vista-lista .card-tarea {
    display: flex; flex-direction: row !important; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 15px;
}
.vista-lista .heading-titulo { flex: 1 1 150px; }
.vista-lista .badge-prioridad { order: -1; }
.vista-lista .text-desc { flex: 2 1 300px; margin: 0 !important; min-height: auto; }
.vista-lista .mt-3 { margin: 0 !important; padding: 0 !important; border: none !important; }
.vista-lista .botonera-card { flex: 1 1 180px; }

/* MODALES VERTICALES ALTOS */
.modal-custom-vertical { max-width: 400px; margin: 1.75rem auto; }
.header-detalle-marron { background-color: #4c2521; }
.badge-code-proy { background-color: rgba(255, 255, 255, 0.15); color: #fff; font-size: 0.7rem; }
.text-lbl { font-size: 0.7rem; letter-spacing: 0.05em; }

/* INPUTS PERSONALIZADOS */
.input-custom, .select-custom {
    border-radius: 8px; padding: 10px 12px; border: 1px solid #ced4da; font-size: 0.9rem;
    background-color: #fff;
    color: #212529;
}
.input-custom:focus, .select-custom:focus {
    box-shadow: 0 0 0 3px rgba(105, 52, 46, 0.15); border-color: #69342e;
}
.btn-light-custom { background-color: #f3f4f6; border: none; border-radius: 8px; color: #4b5563; font-weight: 500; }
.btn-light-custom:hover { background-color: #e5e7eb; }

/* BOTÓN FLOTANTE MÓVIL ACCESORIO */
.btn-flotante-agregar {
    position: fixed; bottom: 20px; right: 20px; width: 52px; height: 52px;
    border-radius: 50%; display: none; align-items: center; justify-content: center; z-index: 1040;
}

@media (max-width: 768px) {
    .btn-flotante-agregar { display: flex; }
    .conmutador-vistas-box, #btn-abrir-crear-tarea { display: none !important; }
    .modal-custom-vertical { margin: 1rem; max-width: calc(100% - 2rem); }
    .vista-lista .card-tarea { flex-direction: column !important; align-items: stretch; }
}
</style>
