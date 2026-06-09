<script setup>
import { onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
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
    const modalPerfilElement = document.getElementById("modalCrearPerfil");
    const modalTareaElement = document.getElementById("modalAñadirTarea");
    const modalDetalleElement = document.getElementById("modalDetalleTarea");
    const formPerfil = document.getElementById("formPerfil");
    const contenedorPerfiles = document.getElementById("contenedor-perfiles");
    const btnVistaGrid = document.getElementById("vista-grid-btn");
    const btnVistaList = document.getElementById("vista-list-btn");

    if (!modalPerfilElement || !modalTareaElement || !modalDetalleElement || !formPerfil || !contenedorPerfiles || !btnVistaGrid || !btnVistaList) {
        console.warn("Algunos elementos del DOM no están listos en GestionPerfil.");
        return;
    }

    // Instancias únicas globales de los modales
    const modalPerfilInstance = new bootstrap.Modal(modalPerfilElement);
    const modalTareaInstance = new bootstrap.Modal(modalTareaElement);

    let esModoEdicion = false;
    let nodoPerfilAEditar = null;

    // Resetear estados al abrir el modal
    modalPerfilElement.addEventListener("show.bs.modal", function (event) {
        const botonDisparador = event.relatedTarget;
        
        if (botonDisparador && botonDisparador.id !== "falso-disparador-javascript") {
            esModoEdicion = false;
            nodoPerfilAEditar = null;
            
            document.getElementById("tituloModalPerfil").textContent = "Crear Espacio / Perfil";
            document.getElementById("btnGuardarPerfil").textContent = "Crear Espacio";
            formPerfil.reset();
        }
    });

    // 1. MANEJO DE ALTERNANCIA DE VISTAS (GRID VS ANCHO)
    btnVistaGrid.addEventListener("click", function () {
        btnVistaList.classList.remove("active");
        this.classList.add("active");
        contenedorPerfiles.classList.remove("vista-lista");
        contenedorPerfiles.classList.add("vista-grid");
    });

    btnVistaList.addEventListener("click", function () {
        btnVistaGrid.classList.remove("active");
        this.classList.add("active");
        contenedorPerfiles.classList.remove("vista-grid");
        contenedorPerfiles.classList.add("vista-lista");
    });

    // 2. CREACIÓN Y EDICIÓN DE ELEMENTOS PERFIL
    formPerfil.addEventListener("submit", function (e) {
        e.preventDefault();

        const titulo = document.getElementById("inputTituloPerfil").value;
        const descripcion = document.getElementById("inputDescPerfil").value;

        if (esModoEdicion && nodoPerfilAEditar) {
            nodoPerfilAEditar.querySelector(".heading-titulo").textContent = titulo;
            nodoPerfilAEditar.querySelector(".text-desc").textContent = descripcion;
            
            const btnEditar = nodoPerfilAEditar.querySelector(".btn-editar-dinamico");
            if (btnEditar) {
                btnEditar.setAttribute("onclick", `abrirEditarPerfil('${titulo.replace(/'/g, "\\'")}', '${descripcion.replace(/'/g, "\\'")}')`);
            }
        } else {
            const nuevaCardCol = document.createElement("div");
            nuevaCardCol.className = "col-12 col-md-6 col-xl-4 item-perfil-col";
            nuevaCardCol.innerHTML = `
                <div class="card card-perfil h-100 p-3 bg-white border cursor-pointer">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2 header-card-titulo">
                            <div class="icon-box-perfil p-2 border rounded"><i class="bi bi-folder-fill text-marron fs-5"></i></div>
                            <h5 class="fw-bold m-0 text-dark heading-titulo">${titulo}</h5>
                        </div>
                        <i class="bi bi-three-dots-vertical text-secondary cursor-pointer"></i>
                    </div>
                    <p class="text-secondary small text-desc flex-grow-1">${descripcion}</p>
                    <div class="small text-muted mb-3 metadata-tiempo"><i class="bi bi-clock me-1"></i> Última vez: Recién creado</div>
                    <div class="d-flex gap-2 botonera-card">
                        <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-dinamico"><i class="bi bi-pencil me-1"></i> Editar</button>
                        <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-dinamico"><i class="bi bi-trash me-1"></i> Eliminar</button>
                    </div>
                </div>
            `;
            contenedorPerfiles.appendChild(nuevaCardCol);
        }

        formPerfil.reset();
        modalPerfilInstance.hide();
    });

    // 3. CAPTURA POR DELEGACIÓN DE EVENTOS (Para tarjetas dinámicas)
    contenedorPerfiles.addEventListener("click", function (e) {
        // 1. Eliminar perfil
        if (e.target.classList.contains("btn-eliminar-dinamico") || e.target.closest(".btn-outline-danger")) {
            const cardCol = e.target.closest(".item-perfil-col");
            if (confirm("¿Estás seguro de eliminar este perfil/espacio de trabajo de Cronos Notes?")) {
                cardCol.remove();
            }
            return;
        }

        // 2. Editar perfil (dinámico)
        const btnEditar = e.target.closest(".btn-editar-dinamico");
        if (btnEditar) {
            if (!btnEditar.hasAttribute("onclick")) {
                const cardBox = e.target.closest(".card-perfil");
                nodoPerfilAEditar = cardBox;
                esModoEdicion = true;

                const tituloActual = cardBox.querySelector(".heading-titulo").textContent;
                const descActual = cardBox.querySelector(".text-desc").textContent;

                document.getElementById("inputTituloPerfil").value = tituloActual;
                document.getElementById("inputDescPerfil").value = descActual;
                document.getElementById("tituloModalPerfil").textContent = "Modificar Perfil";
                document.getElementById("btnGuardarPerfil").textContent = "Guardar Cambios";

                modalPerfilInstance.show(document.getElementById("falso-disparador-javascript"));
            }
            return;
        }

        // 3. Ignorar clics en el botón de tres puntos o el menú desplegable
        if (e.target.classList.contains("bi-three-dots-vertical") || e.target.closest(".dropdown-menu")) {
            return;
        }

        // 4. Navegar a la gestión de tareas al hacer clic en cualquier parte de la tarjeta de perfil
        const cardBox = e.target.closest(".card-perfil");
        if (cardBox) {
            router.visit('/gestion-tareas');
        }
    });

    document.getElementById("formNuevaTarea").addEventListener("submit", function (e) {
        e.preventDefault();
        this.reset();
        modalTareaInstance.hide();
    });

    window.abrirEditarPerfil = function(titulo, descripcion) {
        const headings = contenedorPerfiles.querySelectorAll(".heading-titulo");
        let cardBoxEncontrada = null;
        
        headings.forEach(h => {
            if (h.textContent.trim() === titulo.trim()) {
                cardBoxEncontrada = h.closest(".card-perfil");
            }
        });

        nodoPerfilAEditar = cardBoxEncontrada;
        esModoEdicion = true;

        document.getElementById("inputTituloPerfil").value = titulo;
        document.getElementById("inputDescPerfil").value = descripcion;
        document.getElementById("tituloModalPerfil").textContent = "Modificar Perfil";
        document.getElementById("btnGuardarPerfil").textContent = "Guardar Cambios";
        
        let falsoDisparador = document.getElementById("falso-disparador-javascript");
        if (!falsoDisparador) {
            falsoDisparador = document.createElement("div");
            falsoDisparador.id = "falso-disparador-javascript";
            falsoDisparador.style.display = "none";
            document.body.appendChild(falsoDisparador);
        }

        modalPerfilInstance.show(falsoDisparador);
    };
}
</script>

<template>
  <AppLayout>
    <div class="main-layout-container p-3 p-md-4">
        
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4 mt-2">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Gestión de Perfiles</h1>
                <p class="text-secondary small m-0 mt-1">Configura y personaliza los accesos de tus colaboradores y espacios de trabajo.</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group conmutador-vistas-box" role="group" aria-label="Cambiar tipo de vista">
                    <button type="button" class="btn btn-vista active" id="vista-grid-btn" title="Vista de Tarjetas">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </button>
                    <button type="button" class="btn btn-vista" id="vista-list-btn" title="Vista de Lista Ancha">
                        <i class="bi bi-list-task"></i>
                    </button>
                </div>
                <button class="btn btn-marron d-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalCrearPerfil">
                    <i class="bi bi-plus-lg"></i> Crear perfil
                </button>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4 overflow-x-auto pb-2 barra-filtros-scroll">
            <button class="btn btn-filtro active">Todos los Perfiles</button>
            <button class="btn btn-filtro">Recientes</button>
            <button class="btn btn-filtro">Administración</button>
            <button class="btn btn-filtro">Invitados</button>
            <button class="btn btn-filtro">Inactivos</button>
        </div>

        <div class="row g-3 g-md-4 vista-grid" id="contenedor-perfiles">
            
            <div class="col-12 col-md-6 col-xl-4 item-perfil-col">
                <div class="card card-perfil h-100 p-3 bg-white border cursor-pointer">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2 header-card-titulo">
                            <div class="icon-box-perfil p-2 border rounded"><i class="bi bi-code-slash text-marron fs-5"></i></div>
                            <h5 class="fw-bold m-0 text-dark heading-titulo">Programación en Ambientes WEB</h5>
                        </div>
                        <i class="bi bi-three-dots-vertical text-secondary cursor-pointer" data-bs-toggle="dropdown"></i>
                    </div>
                    <p class="text-secondary small text-desc flex-grow-1">Control total de la infraestructura técnica, gestión de servidores y protocolos de seguridad de red.</p>
                    <div class="small text-muted mb-3 metadata-tiempo"><i class="bi bi-clock me-1"></i> Última vez: Hace 12 min</div>
                    <div class="d-flex gap-2 botonera-card">
                        <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-dinamico" onclick="abrirEditarPerfil('Programación en Ambientes WEB', 'Control total de la infraestructura técnica, gestión de servidores y protocolos de seguridad de red.')"><i class="bi bi-pencil me-1"></i> Editar</button>
                        <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-dinamico"><i class="bi bi-trash me-1"></i> Eliminar</button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-4 item-perfil-col">
                <div class="card card-perfil h-100 p-3 bg-white border cursor-pointer">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2 header-card-titulo">
                            <div class="icon-box-perfil p-2 border rounded"><i class="bi bi-gear-fill text-marron fs-5"></i></div>
                            <h5 class="fw-bold m-0 text-dark heading-titulo">Ingeniería de Software II</h5>
                        </div>
                        <i class="bi bi-three-dots-vertical text-secondary cursor-pointer"></i>
                    </div>
                    <p class="text-secondary small text-desc flex-grow-1">Haga clic aquí para ver la gestión de tareas de este perfil.</p>
                    <div class="small text-muted mb-3 metadata-tiempo"><i class="bi bi-clock me-1"></i> Última vez: Hace 5 días</div>
                    <div class="d-flex gap-2 botonera-card">
                        <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-dinamico" onclick="abrirEditarPerfil('Ingeniería de Software II', 'Haga clic aquí para ver la gestión de tareas de este perfil.')"><i class="bi bi-pencil me-1"></i> Editar</button>
                        <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-dinamico"><i class="bi bi-trash me-1"></i> Eliminar</button>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-4 item-perfil-col">
                <div class="card card-perfil h-100 p-3 bg-white border cursor-pointer">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2 header-card-titulo">
                            <div class="icon-box-perfil p-2 border rounded"><i class="bi bi-bar-chart-fill text-marron fs-5"></i></div>
                            <h5 class="fw-bold m-0 text-dark heading-titulo">Modelos, Simulación y Teoría de la Decisión</h5>
                        </div>
                        <i class="bi bi-three-dots-vertical text-secondary cursor-pointer"></i>
                    </div>
                    <p class="text-secondary small text-desc flex-grow-1">Análisis de optimización de flujos y procesos organizacionales mediante simulación dinámica.</p>
                    <div class="small text-muted mb-3 metadata-tiempo"><i class="bi bi-clock me-1"></i> Última vez: Hace 5 días</div>
                    <div class="d-flex gap-2 botonera-card">
                        <button class="btn btn-outline-secondary w-50 btn-sm btn-editar-dinamico" onclick="abrirEditarPerfil('Modelos, Simulación y Teoría de la Decisión', 'Análisis de optimización de flujos y procesos organizacionales mediante simulación dinámica.')"><i class="bi bi-pencil me-1"></i> Editar</button>
                        <button class="btn btn-outline-danger w-50 btn-sm btn-eliminar-dinamico"><i class="bi bi-trash me-1"></i> Eliminar</button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Botón Flotante Tarea -->
    <button class="btn btn-marron btn-flotante-tarea shadow" data-bs-toggle="modal" data-bs-target="#modalAñadirTarea" title="Nueva Tarea">
        <i class="bi bi-plus-lg fs-5"></i>
    </button>

    <Teleport to="body">
    <!-- Modal Detalle Tarea -->
    <div class="modal fade" id="modalDetalleTarea" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom-vertical">
            <div class="modal-content border-0 overflow-hidden shadow-xl rounded-4">
                
                <div class="header-detalle-marron p-4 text-white">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge badge-code-proy px-2.5 py-1 text-uppercase fw-bold">Proy-Web-01</span>
                        <span class="badge bg-white text-dark rounded-pill px-2.5 py-1 small fw-medium">En proceso</span>
                    </div>
                    <h2 class="h4 fw-bold m-0 text-white lh-sm">Desarrollo de API de Autenticación</h2>
                </div>
                
                <div class="modal-body p-4 bg-white">
                    <div class="seccion-bloque mb-4">
                        <h6 class="text-uppercase text-secondary small fw-bold tracking-wider mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-file-text"></i> Descripción del trabajo
                        </h6>
                        <p class="text-muted small lh-base m-0">Implementar endpoints para login, registro y recuperación de contraseña utilizando JWT. Se requiere una validación exhaustiva de los campos de entrada y manejo de errores de red para asegurar una experiencia de usuario fluida y segura.</p>
                    </div>

                    <div class="row g-3 mb-4 pt-2 border-top">
                        <div class="col-6">
                            <div class="small text-uppercase text-secondary fw-bold metadata-label">Fecha Límite</div>
                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark small mt-1">
                                <i class="bi bi-calendar text-danger"></i> 2024-05-20
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-uppercase text-secondary fw-bold metadata-label">Prioridad</div>
                            <div class="d-flex align-items-center gap-2 fw-semibold text-danger small mt-1">
                                <i class="bi bi-exclamation-circle-fill"></i> Alta
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-uppercase text-secondary fw-bold metadata-label">Finalización</div>
                            <div class="d-flex align-items-center gap-2 text-secondary small mt-1">
                                <i class="bi bi-check2-circle"></i> Pendiente
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-uppercase text-secondary fw-bold metadata-label">Asignado a</div>
                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark small mt-1">
                                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=100" alt="Avatar" class="rounded-circle" width="20" height="20">
                                Carlos Ruiz
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2 pt-3 border-top">
                        <button class="btn btn-marron w-100 py-2 fw-medium">Marcar como Completada</button>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary flex-grow-1 py-2 text-dark btn-sm"><i class="bi bi-pencil me-1"></i> Editar</button>
                            <button class="btn btn-outline-danger flex-grow-1 py-2 btn-sm"><i class="bi bi-trash me-1"></i> Eliminar</button>
                        </div>
                        <button class="btn btn-link text-secondary text-decoration-none w-100 btn-sm mt-1" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Perfil -->
    <div class="modal fade" id="modalCrearPerfil" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-custom-vertical">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-white border-bottom-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark fs-5" id="tituloModalPerfil">Crear Espacio / Perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <form id="formPerfil">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Título del Perfil</label>
                            <input type="text" id="inputTituloPerfil" class="form-control input-custom" placeholder="Ej. Trabajo de Campo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Descripción Corta</label>
                            <textarea id="inputDescPerfil" class="form-control input-custom" rows="4" placeholder="Descripción de las actividades de este entorno..." required></textarea>
                        </div>
                        <div class="d-flex flex-column gap-2 mt-4">
                            <button type="submit" class="btn btn-marron w-100 py-2" id="btnGuardarPerfil">Crear Espacio</button>
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
                    <form id="formNuevaTarea">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Seleccionar Perfil</label>
                            <select class="form-select select-custom">
                                <option>Programación en Ambientes WEB</option>
                                <option>Ingeniería de Software II</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Nombre de la tarea *</label>
                            <input type="text" class="form-control input-custom" placeholder="Ej. Desarrollar endpoints JWT" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Descripción</label>
                            <textarea class="form-control input-custom" rows="3" placeholder="Detalles de la entrega..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-medium">Fecha límite</label>
                            <input type="date" class="form-control input-custom">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-medium">Prioridad</label>
                            <select class="form-select select-custom">
                                <option value="Alta">Alta</option>
                                <option value="Media">Media</option>
                                <option value="Baja">Baja</option>
                            </select>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-marron w-100 py-2">Guardar Tarea</button>
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

.header-detalle-marron {
    background-color: #4c2521;
}

.badge-code-proy {
    background-color: rgba(255, 255, 255, 0.15);
    color: #fff;
    font-size: 0.7rem;
}

.metadata-label {
    font-size: 0.7rem;
    letter-spacing: 0.05em;
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