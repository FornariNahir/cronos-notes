# 02: Barra de progreso y acordeón interactivo en la Tarjeta de Tarea

**What to build:**
Permitir al usuario visualizar el porcentaje de avance de subtareas directamente en cada tarjeta de tarea en la vista principal (`GestionTareas.vue`) y expandir un acordeón interactivo para marcar o desmarcar subtareas con un solo clic sin necesidad de abrir el modal de detalle, actualizando reactivamente la tarjeta.

**Blocked by:** 01: Persistencia relacional, endpoints CRUD y gestión manual de Subtareas en Modal de Detalle

**Status:** resolved

- [x] Eager loading o carga de la relación `subtareas` en `TareaController::index` ordenada por `orden` o fecha.
- [x] Barra de progreso visual en la tarjeta de tarea mostrando porcentaje y texto (ej. `2/5 subtareas (40%)`).
- [x] Acordeón desplegable en la tarjeta con lista de subtareas y casillas interactivas para toggle directo.
- [x] Actualización reactiva instantánea en el frontend al tildar subtareas desde la tarjeta.
- [x] Deshabilitar casillas interactivas si el perfil es de solo lectura (`Lector`).
