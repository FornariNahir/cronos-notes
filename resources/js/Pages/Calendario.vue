<script setup>
import { onMounted, watch, ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, Head } from '@inertiajs/vue3';
import AlertModal from '@/Components/AlertModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

let tareaSeleccionadaId = null;
let eventoGoogleSeleccionado = null;
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

const props = defineProps({
    tareasCargadas: {
        type: Array,
        default: () => []
    },
    perfilesDisponibles: {
        type: Array,
        default: () => []
    },
    eventosGoogle: {
        type: Array,
        default: () => []
    },
    estaVinculadoGoogle: {
        type: Boolean,
        default: false
    }
});

const googleConectado = computed(() => {
    return !!(props.estaVinculadoGoogle || (props.eventosGoogle && props.eventosGoogle.length > 0));
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
    let eventosGoogleDB = [...props.eventosGoogle];

    // Variables de control cronológico
    let fechaActual = new Date();

    // Componentes del DOM
    const grillaDiasContainer = document.getElementById("grilla-dias-container");
    const displayMesAño = document.getElementById("display-mes-año");
    const formTarea = document.getElementById("formNuevaTareaCalendario");
    const inputDate = document.getElementById("input-task-date");
    const modalCrearElement = document.getElementById("modalAñadirTareaCalendario");
    const modalDetalleElement = document.getElementById("modalVerDetalleTarea");
    const modalDetalleGoogleElement = document.getElementById("modalVerDetalleGoogle");

    if (!grillaDiasContainer || !displayMesAño || !formTarea || !inputDate || !modalCrearElement || !modalDetalleElement) {
        console.warn("Algunos elementos del DOM no están listos en Calendario.");
        return;
    }

    // Instanciación única controlada de Modales Bootstrap (Previene bloqueos de pantalla)
    const modalCrear = new bootstrap.Modal(modalCrearElement);
    const modalDetalle = new bootstrap.Modal(modalDetalleElement);
    const modalDetalleGoogle = modalDetalleGoogleElement ? new bootstrap.Modal(modalDetalleGoogleElement) : null;

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

            // 1. Filtrar y renderizar tareas nativas de Cronos asignadas a este día específico
            const tareasDelDia = tareasDB.filter(t => t.fecha === stringFechaFiltro);
            if (tareasDelDia.length > 0) {
                const bloqueCronos = document.createElement("div");
                bloqueCronos.className = "bloque-cronos-tarea-grupo";
                const nombresTareas = tareasDelDia.map(t => t.nombre).join(" • ");
                bloqueCronos.title = nombresTareas;

                // Barra horizontal bicolor (marrón a la izquierda y durazno claro a la derecha)
                const barra = document.createElement("div");
                barra.className = "barra-bicolor-tarea";
                bloqueCronos.appendChild(barra);

                // Texto contador "1 TAREA" o "X TAREAS"
                const textoContador = document.createElement("div");
                textoContador.className = "texto-contador-tarea";
                textoContador.textContent = `${tareasDelDia.length} ${tareasDelDia.length === 1 ? 'TAREA' : 'TAREAS'}`;
                bloqueCronos.appendChild(textoContador);

                // Clic en la barra o texto abre el modal existente de Cronos
                bloqueCronos.addEventListener("click", function (e) {
                    e.stopPropagation();
                    abrirModalDetalle(tareasDelDia[0]);
                });

                listaTareasContenedor.appendChild(bloqueCronos);
            }

            // 2. Filtrar y renderizar eventos sincronizados de Google Calendar (RF-M15)
            const eventosDelDia = eventosGoogleDB.filter(e => e.fecha === stringFechaFiltro);
            eventosDelDia.forEach(evento => {
                const bloqueEvento = document.createElement("div");
                bloqueEvento.className = "item-google-calendar-texto";
                bloqueEvento.textContent = evento.titulo || '(Sin título)';
                const textoHora = evento.hora_inicio ? ` (${evento.hora_inicio})` : '';
                bloqueEvento.title = `[Google Calendar] ${evento.titulo}${textoHora}`;

                // Clic en el evento de Google abre el modal especial de Google Calendar
                bloqueEvento.addEventListener("click", function (e) {
                    e.stopPropagation();
                    abrirModalDetalleGoogle(evento);
                });

                listaTareasContenedor.appendChild(bloqueEvento);
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
    // ACCIÓN: VENTANA EMERGENTE DE DETALLE (TAREA CRONOS)
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
    // ACCIÓN: VENTANA EMERGENTE DE DETALLE (GOOGLE CALENDAR) (RF-M15)
    // ==========================================
    function abrirModalDetalleGoogle(evento) {
        eventoGoogleSeleccionado = evento;
        const tituloEl = document.getElementById("google-detalle-titulo");
        if (tituloEl) {
            tituloEl.textContent = evento.titulo || 'Evento de Google Calendar';
        }

        // Formatear fecha
        const fechaEl = document.getElementById("google-detalle-fecha");
        if (fechaEl) {
            if (evento.fecha) {
                if (!evento.es_todo_el_dia && evento.hora_inicio) {
                    fechaEl.textContent = `${evento.fecha} (${evento.hora_inicio}${evento.hora_fin ? ' - ' + evento.hora_fin : ''})`;
                } else {
                    fechaEl.textContent = evento.fecha;
                }
            } else {
                fechaEl.textContent = 'Sin fecha';
            }
        }

        // Descripción del evento
        const descEl = document.getElementById("google-detalle-descripcion");
        if (descEl) {
            descEl.textContent = evento.descripcion && evento.descripcion.trim() ? evento.descripcion : "Sin descripción disponible.";
        }

        // Link directo a Google Calendar
        const linkBtn = document.getElementById("btn-ver-en-google");
        if (linkBtn) {
            if (evento.html_link) {
                linkBtn.href = evento.html_link;
            } else {
                linkBtn.href = "https://calendar.google.com/calendar/";
            }
        }

        if (modalDetalleGoogle) {
            modalDetalleGoogle.show();
        }
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

    // Observar los eventos sincronizados de Google Calendar
    watch(() => props.eventosGoogle, (newEventos) => {
        eventosGoogleDB = [...newEventos];
        renderizarCalendario();
    }, { deep: true });

    // Conversión de evento externo de Google a una Tarea Nativa de Cronos
    const btnConvertirTarea = document.getElementById("btn-convertir-a-tarea");
    if (btnConvertirTarea) {
        btnConvertirTarea.addEventListener("click", function () {
            if (!eventoGoogleSeleccionado) return;

            if (modalDetalleGoogle) {
                modalDetalleGoogle.hide();
            }

            // Precargar el modal de creación con los datos del evento de Google
            formTarea.reset();
            document.getElementById("input-task-name").value = eventoGoogleSeleccionado.titulo || '';
            document.getElementById("input-task-desc").value = eventoGoogleSeleccionado.descripcion || `[Importado de Google Calendar: ${eventoGoogleSeleccionado.calendario_nombre || 'Agenda'}]`;
            
            if (eventoGoogleSeleccionado.fecha) {
                inputDate.value = eventoGoogleSeleccionado.fecha;
            }
            
            const selectPrio = document.getElementById("input-task-priority");
            if (selectPrio) {
                selectPrio.value = "Media";
            }

            setTimeout(() => {
                modalCrear.show();
            }, 300);
        });
    }

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
            if (tareaSeleccionadaId) {
                triggerConfirm({
                    title: 'Eliminar Tarea',
                    message: '¿Estás seguro de eliminar esta tarea del calendario de Cronos?',
                    confirmText: 'Eliminar',
                    isDanger: true,
                    onConfirm: () => {
                        router.delete(route('tareas.destroy', tareaSeleccionadaId), {
                            onSuccess: () => {
                                modalDetalle.hide();
                                tareaSeleccionadaId = null;
                            }
                        });
                    }
                });
            }
        });
    }

    // Controladores dinámicos de las flechas de navegación de meses
    const btnHoy = document.getElementById("btn-hoy-mes");
    if (btnHoy) {
        btnHoy.addEventListener("click", function () {
            fechaActual = new Date();
            renderizarCalendario();
        });
    }

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
  <Head title="Calendario" />
  <AppLayout>
    <template #sidebar-extra>
      <div v-if="!googleConectado" class="sidebar-google-section mt-auto pt-3 pb-3 px-2">
        <a href="/auth/google-calendar/redirect" class="btn-vincular-google" title="Vincular con Google Calendar">
          <span class="google-icon-wrapper">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
              <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/>
            </svg>
          </span>
          <span class="btn-vincular-google-texto">Vincular con Google Calendar</span>
        </a>
      </div>
    </template>

    <div class="main-layout-calendario p-3 p-md-4">
        
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
            <div>
                <h1 class="calendario-titulo-mes" id="display-mes-año">Abril 2026</h1>
                <div class="calendario-subtitulo d-flex align-items-center gap-2">
                    <i class="bi bi-calendar3"></i>
                    <span>Vista mensual de actividades académicas</span>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="btn-group-nav-calendario shadow-sm bg-white rounded-3 p-1 border d-flex align-items-center">
                    <button class="btn btn-nav-mes py-1 px-2.5" id="btn-prev-mes" title="Mes anterior"><i class="bi bi-chevron-left"></i></button>
                    <button class="btn btn-nav-hoy py-1 px-3 fw-semibold text-dark" id="btn-hoy-mes">Hoy</button>
                    <button class="btn btn-nav-mes py-1 px-2.5" id="btn-next-mes" title="Mes siguiente"><i class="bi bi-chevron-right"></i></button>
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

        <!-- Modal Ver Detalle Google Calendar (RF-M15) -->
        <div class="modal fade" id="modalVerDetalleGoogle" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-esbelto-vertical">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="header-detalle-marron p-4 text-white position-relative" style="background-color: #612c2d;">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-white rounded-pill px-3 py-1.5 fw-bold" style="color: #1a73e8; font-size: 0.75rem;">
                                Evento de Google Calendar
                            </span>
                        </div>
                        <h3 class="h4 fw-bold m-0 text-white mt-2" id="google-detalle-titulo">Título del Evento</h3>
                        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-4" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 bg-white">
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2 text-dark small fw-bold" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                                <i class="bi bi-calendar3" style="color: #4b5563;"></i>
                                <span class="text-uppercase" style="color: #374151;">DESCRIPCIÓN DEL EVENTO</span>
                            </div>
                            <p class="text-secondary small lh-base m-0" id="google-detalle-descripcion" style="font-size: 0.88rem; color: #4b5563 !important;">
                                Sin descripción disponible.
                            </p>
                        </div>

                        <div class="mb-4">
                            <div class="text-uppercase text-muted fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px; color: #6b7280;">
                                FECHA
                            </div>
                            <div class="d-flex align-items-center gap-2 fw-semibold text-dark small" style="font-size: 0.95rem;">
                                <i class="bi bi-calendar" style="color: #612c2d;"></i>
                                <span id="google-detalle-fecha" style="color: #111827;">--/--/----</span>
                            </div>
                        </div>

                        <div class="pt-3 border-top d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary px-4 py-2 small fw-medium" data-bs-dismiss="modal" style="border-radius: 8px; border-color: #d1d5db; color: #374151; font-size: 0.88rem; background-color: #ffffff;">
                                Cerrar
                            </button>
                            <a href="#" target="_blank" rel="noopener noreferrer" class="btn btn-primary px-4 py-2 small fw-semibold d-inline-flex align-items-center justify-content-center text-white" id="btn-ver-en-google" style="border-radius: 8px; background-color: #1a73e8; border-color: #1a73e8; font-size: 0.88rem;">
                                Ver en Google Calendar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
    overflow-y: auto; max-height: 100px;
}

/* COMPONENTE INTERACTIVO DE LA TAREA */
.bloque-tarea-item {
    font-size: 0.72rem; padding: 4px 6px; border-radius: 6px;
    font-weight: 600; white-space: nowrap; text-overflow: ellipsis;
    overflow: hidden; cursor: pointer; border-left: 3px solid transparent;
    transition: transform 0.15s ease;
}
.bloque-tarea-item:hover { transform: translateY(-1px); filter: brightness(0.95); }

/* COMPONENTE INTERACTIVO DE RECORDATORIO GOOGLE CALENDAR (RF-M15) */
.bloque-google-item {
    font-size: 0.72rem; padding: 3px 6px; border-radius: 6px;
    font-weight: 600; white-space: nowrap; text-overflow: ellipsis;
    overflow: hidden; cursor: pointer; border-left: 3px solid #4285F4;
    background-color: #eef3fc; color: #1967d2;
    transition: transform 0.15s ease, filter 0.15s ease;
    display: flex; align-items: center;
}
.bloque-google-item:hover { transform: translateY(-1px); filter: brightness(0.95); }
.header-detalle-google { background-color: #1a73e8; }

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

/* Dark Mode styles specifically for Calendario.vue to match calendarioOscuro.png */
body.cn-body-dark .main-layout-calendario {
    background-color: #4d2323 !important;
}

body.cn-body-dark .grid-semana-cabecera {
    background-color: #e38e76 !important;
    border-bottom: 1px solid #ffffff !important;
}

body.cn-body-dark .grid-semana-cabecera div {
    color: #ffffff !important;
}

body.cn-body-dark .grid-mes-cuerpo {
    background-color: #ffffff !important;
}

body.cn-body-dark .celda-dia {
    background-color: #f4be95 !important;
}

body.cn-body-dark .celda-dia.dia-fuera-rango {
    background-color: #fadfca !important;
}

body.cn-body-dark .celda-dia.dia-hoy-resaltado {
    background-color: #fadfca !important;
    border: 2px solid #e38e76 !important;
}

body.cn-body-dark .num-dia {
    color: #4d2323 !important;
}

body.cn-body-dark .dia-fuera-rango .num-dia {
    color: #a55e57 !important;
}

body.cn-body-dark .dia-hoy-resaltado .num-dia {
    color: #4d2323 !important;
    font-weight: 700;
}

body.cn-body-dark .btn-group.shadow-sm.bg-white {
    background-color: #e38e76 !important;
    border-color: #e38e76 !important;
}

body.cn-body-dark .btn-nav-mes {
    color: #ffffff !important;
}

body.cn-body-dark .btn-nav-mes:hover {
    background-color: rgba(255, 255, 255, 0.15) !important;
}

body.cn-body-dark .text-month-year,
body.cn-body-dark #display-mes-año {
    color: #ffffff !important;
}

body.cn-body-dark #btn-abrir-crear {
    background-color: #4d2323 !important;
    color: #ffffff !important;
    border: 1px solid #7b413f !important;
}

body.cn-body-dark #btn-abrir-crear:hover {
    background-color: #612c2d !important;
}

body.cn-body-dark .bloque-tarea-item {
    background-color: #4d2323 !important;
    color: #ffffff !important;
}

body.cn-body-dark .bloque-tarea-item:hover {
    background-color: #612c2d !important;
}

body.cn-body-dark .t-alta {
    border-left: 3px solid #ef4444 !important;
}

body.cn-body-dark .t-media {
    border-left: 3px solid #f59e0b !important;
}

body.cn-body-dark .t-baja {
    border-left: 3px solid #3b82f6 !important;
}

body.cn-body-dark .input-custom-style,
body.cn-body-dark .select-custom-style {
    background-color: #612c2d !important;
    color: #ffffff !important;
    border-color: #7b413f !important;
}

body.cn-body-dark .input-custom-style:focus,
body.cn-body-dark .select-custom-style:focus {
    border-color: #f4be95 !important;
    box-shadow: 0 0 0 2px rgba(244, 190, 149, 0.25) !important;
}

body.cn-body-dark .btn-cancelar-modal {
    background-color: #612c2d !important;
    color: #fcd5b8 !important;
    border: 1px solid #7b413f !important;
}

body.cn-body-dark .btn-cancelar-modal:hover {
    background-color: #7b413f !important;
    color: #ffffff !important;
}

body.cn-body-dark .btn-guardar-modal {
    background-color: #f4be95 !important;
    color: #4d2323 !important;
    font-weight: 600 !important;
}

body.cn-body-dark .btn-guardar-modal:hover {
    background-color: #fbe8da !important;
}

body.cn-body-dark .bloque-google-item {
    background-color: #3b2222 !important;
    color: #ffd8bf !important;
}

body.cn-body-dark .bloque-google-item:hover {
    background-color: #4a2b2b !important;
}

body.cn-body-dark .badge.bg-white.text-secondary {
    background-color: #612c2d !important;
    color: #fcd5b8 !important;
    border-color: #7b413f !important;
}

/* ESTILOS ESPECÍFICOS DISEÑO CALENDARIO Y GOOGLE CALENDAR */
.calendario-titulo-mes {
    font-size: 32px;
    font-weight: 700;
    color: #111827;
    margin: 0;
    line-height: 1.2;
}

.calendario-subtitulo {
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    margin-top: 4px;
}

.btn-nav-hoy {
    border: none;
    background: transparent;
    font-size: 0.88rem;
    transition: background-color 0.15s;
    border-radius: 6px;
}
.btn-nav-hoy:hover {
    background-color: #f1f5f9;
}

.sidebar-google-section {
    width: 100%;
}

.btn-vincular-google {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: #1682ec;
    color: #ffffff !important;
    text-decoration: none;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 9px 12px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    transition: background-color 0.15s ease, transform 0.15s ease;
    width: 100%;
    line-height: 1.25;
}
.btn-vincular-google:hover {
    background-color: #126ec8;
    color: #ffffff !important;
    transform: translateY(-1px);
}

.google-icon-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.btn-vincular-google-texto {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* TAREAS CRONOS EN LA CELDA (Barra bicolor + texto contador) */
.bloque-cronos-tarea-grupo {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    cursor: pointer;
    margin-top: 4px;
    margin-bottom: 2px;
    transition: transform 0.15s ease, opacity 0.15s ease;
}
.bloque-cronos-tarea-grupo:hover {
    transform: translateY(-1px);
    opacity: 0.85;
}
.barra-bicolor-tarea {
    width: 62px;
    height: 5px;
    border-radius: 9999px;
    background: linear-gradient(to right, #612c2d 0%, #612c2d 58%, #f4be95 58%, #f4be95 100%);
}
.texto-contador-tarea {
    font-size: 0.65rem;
    font-weight: 700;
    color: #4b5563;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-top: 3px;
    line-height: 1;
}

/* TAREAS / EVENTOS GOOGLE CALENDAR EN LA CELDA */
.item-google-calendar-texto {
    font-size: 0.68rem;
    font-weight: 700;
    color: #0084ff;
    text-transform: uppercase;
    letter-spacing: 0.2px;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    margin-top: 4px;
    cursor: pointer;
    transition: opacity 0.15s ease;
}
.item-google-calendar-texto:hover {
    opacity: 0.75;
    text-decoration: underline;
}

/* Soporte modo oscuro para nuevos elementos */
body.cn-body-dark .calendario-titulo-mes {
    color: #ffffff !important;
}
body.cn-body-dark .calendario-subtitulo {
    color: #fcd5b8 !important;
}
body.cn-body-dark .barra-bicolor-tarea {
    background: linear-gradient(to right, #f4be95 0%, #f4be95 58%, #612c2d 58%, #612c2d 100%) !important;
}
body.cn-body-dark .texto-contador-tarea {
    color: #fcd5b8 !important;
}
body.cn-body-dark .item-google-calendar-texto {
    color: #60a5fa !important;
}
body.cn-body-dark #btn-hoy-mes {
    color: #ffffff !important;
}
body.cn-body-dark .btn-group-nav-calendario {
    background-color: #e38e76 !important;
    border-color: #e38e76 !important;
}
body.cn-body-dark .btn-nav-hoy {
    color: #ffffff !important;
}
body.cn-body-dark #modalVerDetalleGoogle .modal-body {
    background-color: #4d2323 !important;
    color: #ffffff !important;
}
body.cn-body-dark #modalVerDetalleGoogle .text-dark,
body.cn-body-dark #modalVerDetalleGoogle #google-detalle-fecha {
    color: #ffffff !important;
}
body.cn-body-dark #modalVerDetalleGoogle .text-secondary,
body.cn-body-dark #modalVerDetalleGoogle #google-detalle-descripcion {
    color: #fcd5b8 !important;
}
body.cn-body-dark #modalVerDetalleGoogle .btn-outline-secondary {
    background-color: #612c2d !important;
    color: #fcd5b8 !important;
    border-color: #7b413f !important;
}
</style>