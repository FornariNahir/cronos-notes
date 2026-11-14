# 04: Modal interactivo de Desglose con IA, guardado en lote e integración total

**What to build:**
Modal interactivo `SubtaskBreakdownModal.vue` que permite al usuario invocar el desglose de IA para una tarea, visualizar la propuesta con indicador de carga, editar textos, ajustar pomodoros, agregar o eliminar pasos, previsualizar el total acumulado de esfuerzo y guardar la selección en lote (reemplazando o anexando a subtareas previas). Incluye advertencia asistida si la tarea no tiene descripción.

**Blocked by:** 01: Persistencia relacional, endpoints CRUD y gestión manual de Subtareas en Modal de Detalle, 02: Barra de progreso y acordeón interactivo en la Tarjeta de Tarea, 03: Servicio de IA TaskBreakdownService con fallback y validación de contexto

**Status:** resolved

- [x] Endpoint `POST /tareas/{tarea}/subtareas/lote` para inserción/reemplazo en bloque de subtareas y recálculo de esfuerzo de la tarea.
- [x] Componente Vue `SubtaskBreakdownModal.vue` con estados de carga, lista editable, inputs de título, selectores de pomodoros y totalizador en vivo.
- [x] Lógica para elegir entre "Reemplazar existentes" o "Añadir al final" si la tarea ya contenía subtareas.
- [x] Modal o mensaje de advertencia si la tarea no posee descripción, con botón para abrir edición de la tarea.
- [x] Botón de "Desglosar con IA" integrado en la tarjeta de tarea y en el modal de detalle.
- [x] Pruebas end-to-end de la integración completa.
