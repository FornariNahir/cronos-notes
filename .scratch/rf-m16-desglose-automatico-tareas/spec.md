# Spec: RF-M16 Desglose Automático de Tareas con IA (Subtareas Inteligentes)

Status: `ready-for-agent`

## Problem Statement

Los usuarios de Cronos Notes a menudo se enfrentan a tareas complejas, amplias o intimidantes (por ejemplo: *"Desarrollar módulo de autenticación"* o *"Preparar informe final de pasantías"*). Esto genera sobrecarga cognitiva, parálisis por análisis y procrastinación.

Aunque la aplicación ofrece temporizadores Pomodoro para enfocar el tiempo, los usuarios carecen de una herramienta estructurada que descomponga objetivos desafiantes en pasos pequeños, accionables y con una estimación de esfuerzo realista. Asimismo, no existe un mecanismo para visualizar el progreso parcial de una tarea sin marcarla directamente como completada en su totalidad, lo que dificulta el seguimiento diario de avances intermedios.

## Solution

Incorporar un asistente de Inteligencia Artificial que desglosa automáticamente tareas complejas en una secuencia lógica de subtareas accionables, junto con un sistema de gestión y seguimiento de subtareas integrado en el gestor de tareas:

1. **Desglose Inteligente con IA**: A través de un servicio de backend que consume la API de Google Gemini mediante una cadena de modelos resiliente (`gemini-2.5-flash`, `gemini-2.5-flash-lite`, `gemini-flash-latest`), el sistema genera entre 3 y 7 subtareas estructuradas con títulos en infinitivo y estimación en bloques Pomodoro (1 a 4 por subtarea).
2. **Revisión y Personalización**: El usuario previsualiza la propuesta en un modal interactivo antes de guardarla, pudiendo ajustar textos, modificar estimaciones, añadir nuevos pasos manualmente o descartar sugerencias.
3. **Persistencia Relacional y Ciclo de Vida**: Cada subtarea se almacena como una entidad independiente vinculada a su tarea principal.
4. **Sincronización Bidireccional de Estados y Esfuerzo**: El progreso de las subtareas actualiza dinámicamente el estado (`Pendiente`, `En Progreso`, `Completado`) y el esfuerzo total estimado de la tarea padre.
5. **Visualización Ágil**: La tarjeta de la tarea en la vista principal expone una barra de progreso porcentual y un acordeón interactivo para marcar subtareas rápidamente, complementado por la gestión completa en el modal de detalle.

## User Stories

1. As a registered user, I want to trigger an AI breakdown on an existing task with title and description, so that I can decompose intimidating goals into clear, actionable steps.
2. As a registered user, I want the AI to suggest between 3 and 7 sequential subtasks for a task, so that I receive a structured roadmap without feeling overwhelmed.
3. As a registered user, I want each suggested subtask to include an estimated effort in Pomodoros (between 1 and 4 blocks of 25 minutes), so that I can plan my focus sessions realistically.
4. As a registered user, I want each suggested subtask title to start with an action verb in the infinitive, so that the steps are immediately clear and actionable.
5. As a registered user, I want to receive a helpful warning modal when trying to break down a task that lacks a description, so that I understand why the AI needs context and can jump directly to editing the task.
6. As a registered user, I want to preview all AI-suggested subtasks in a dedicated modal before saving them, so that I remain in full control of my project plan.
7. As a registered user, I want to edit the title and Pomodoro estimate of any suggested subtask in the breakdown modal, so that the plan adapts to my specific needs.
8. As a registered user, I want to add additional custom subtasks manually in the breakdown modal, so that I can incorporate steps the AI may have missed.
9. As a registered user, I want to delete unwanted subtask suggestions in the breakdown modal, so that only relevant steps are adopted.
10. As a registered user, I want to see a real-time recalculation of the total estimated Pomodoros in the breakdown modal as I modify subtasks, so that I know the cumulative effort before confirming.
11. As a registered user, I want to choose between "Replace existing" or "Append to existing" when re-breaking down a task that already has subtasks, so that I do not accidentally lose prior work.
12. As a registered user, I want to see a visual progress bar and completion counter (e.g., `2/5 subtareas (40%)`) directly on the task card in the grid, so that I can monitor my progress at a glance.
13. As a registered user, I want to expand an accordion on the task card to see and check off subtasks directly, so that I can record my progress with a single click without opening deep modals.
14. As a registered user, I want the parent task status to automatically transition to 'En Progreso' as soon as at least one subtask is completed, so that the task status accurately reflects ongoing activity.
15. As a registered user, I want the parent task status to automatically transition to 'Completado' and record its completion date when all subtasks are checked off, so that I do not need to perform manual duplicate updates.
16. As a registered user, I want all subtasks of a task to be marked as completed automatically if I mark the parent task as 'Completado' directly, so that subtask state remains consistent with the task.
17. As a registered user, I want the parent task's total effort estimate to automatically recalculate to the sum of its subtasks' Pomodoros when subtasks are saved or edited, so that the workload displayed on the card is always accurate.
18. As a registered user, I want to view, add, edit, and delete subtasks manually within the task detail modal, so that I can maintain my task list over time without relying exclusively on the AI.
19. As a collaborator with 'Lector' permissions in a shared profile, I want to view subtasks and progress in read-only mode with checkboxes and breakdown buttons disabled, so that I cannot alter tasks I do not own.
20. As a collaborator with 'Editor' or 'Administrador' permissions in a shared profile, I want to trigger AI breakdowns and manage subtasks, so that I can contribute fully to shared team deliverables.
21. As a registered user, I want the system to handle AI provider timeouts or quota errors gracefully using automatic model fallbacks, so that temporary external outages do not crash the application.
22. As a registered user, I want all associated subtasks to be automatically deleted when their parent task is deleted, so that the database remains clean without orphaned records.

## Implementation Decisions

### Domain Model & Architecture
- **Canonical Vocabulary**: The concepts `Tarea`, `Subtarea`, and `Estimación de Esfuerzo` are used consistently across domain documentation, ADRs, models, and UI text, in strict accordance with the repository glossary.
- **Relational Persistence**: Subtasks are persisted in a dedicated relational table `subtareas` with a dedicated Eloquent model `Subtarea` linked to `Tarea` via foreign key `idTarea` with cascade deletion, as documented in ADR `0001-tabla-relacional-subtareas.md`.

### Database Schema
- Table name: `subtareas`
- Attributes:
  - `idSubtarea`: Primary key auto-increment.
  - `idTarea`: Unsigned big integer, foreign key referencing `Tarea.idTarea` with `onDelete('cascade')`.
  - `titulo`: String (varchar 150), mandatory.
  - `completada`: Boolean, default `false`.
  - `estimacionEsfuerzo`: Unsigned tiny integer (1 to 4 Pomodoros), default `1`.
  - `orden`: Unsigned small integer, default `0`.
  - `timestamps`: `created_at` and `updated_at`.

### AI Integration & Service Layer
- **Service encapsulation**: A dedicated backend service `TaskBreakdownService` handles all interactions with Google Gemini Generative Language API.
- **Model Fallback Chain**: Sequential fallback: `gemini-2.5-flash` ➔ `gemini-2.5-flash-lite` ➔ `gemini-flash-latest`, using `Http::retry(2, 500)`.
- **Structured Schema**: The API call specifies `responseMimeType: 'application/json'` and an explicit `responseSchema` expecting an object with a `subtareas` array containing `titulo` and `estimacionPomodoros`.
- **Context Prerequisite**: The AI breakdown endpoint requires both `tituloTarea` and `descripcionTarea` to exist on the task. If description is null or empty, the request is rejected with a validation error (HTTP 422), prompting the user to supply description details.
- **Prompt Directives**: System prompt instructs the model to act as a productivity and Pomodoro tutor, providing 3 to 7 sequential steps starting with an infinitive verb, with 1 to 4 Pomodoros per step.

### State & Progress Synchronization
- When a subtask's completion status is toggled:
  - If completed count == total count (> 0): parent task transitions to `estadoTarea = 'Completado'`, setting `fechaFinTarea = today()`.
  - If completed count > 0 and < total count: parent task transitions to `estadoTarea = 'En Progreso'`, setting `fechaFinTarea = null`.
  - If completed count == 0: parent task transitions to `estadoTarea = 'Pendiente'`, setting `fechaFinTarea = null`.
- When parent task is marked `Completado` directly: all related subtasks are updated to `completada = true`.
- When subtasks are saved, created, or deleted: parent task's `estimacionEsfuerzo` is updated to equal `SUM(subtareas.estimacionEsfuerzo)`.

### Frontend & User Experience
- **Task Card (GestionTareas.vue)**: Displays a progress bar with percentage and text (e.g., `2/4 subtareas (50%)`) and an expandable accordion showing the checklist. Checkboxes allow quick toggling of subtask completion.
- **Breakdown Modal (SubtaskBreakdownModal.vue)**: Interactive dialog displaying loading indicator during inference, editable title inputs, Pomodoro number pickers, delete buttons, an "Añadir paso" button, and real-time total Pomodoro counter. If the task already has subtasks, provides a choice between "Reemplazar existentes" and "Añadir a las existentes".
- **Task Detail Modal**: Offers full subtask management (inline addition, editing, and deletion).
- **Missing Description Guard**: Clicking "Desglosar con IA" on a task without description opens an explanatory warning dialog offering a direct shortcut to edit the task description.

### Authorization & Security
- All endpoints enforce profile-level permissions:
  - Read access allows viewing tasks, subtasks, and progress.
  - Write access (`crear`/`editar`) is strictly required to toggle, create, modify, delete, or break down subtasks.
  - Users with `Lector` role receive HTTP 403 Forbidden on any mutation attempt.

### API Contract (Web / Inertia Routes)
- `POST /tareas/{tarea}/desglosar-ia`: Generates breakdown suggestion without persisting. Returns `{ subtareas: [...] }`.
- `POST /tareas/{tarea}/subtareas/lote`: Persists confirmed subtasks. Accepts `{ subtareas: [...], modo: 'reemplazar' | 'anexar' }`.
- `POST /tareas/{tarea}/subtareas`: Creates a single manual subtask. Accepts `{ titulo: string, estimacionEsfuerzo: int }`.
- `PATCH /subtareas/{subtarea}/toggle`: Toggles `completada` flag and recalculates parent task state.
- `PUT /subtareas/{subtarea}`: Updates subtask title and/or effort estimation.
- `DELETE /subtareas/{subtarea}`: Deletes subtask and recalculates parent task effort and progress.

## Testing Decisions

### What makes a good test
- Tests must verify external, observable behavior through the public HTTP boundary, asserting on database state, JSON response structures, and HTTP status codes, never asserting on private implementation details.
- External third-party dependencies (Google Gemini API) must be intercepted using `Http::fake()` to guarantee deterministic, reproducible, and fast test execution.

### Tested Modules
- **TaskBreakdownServiceTest**:
  - Successfully parses Gemini response and returns structured subtasks array.
  - Automatically falls back to secondary model if primary model returns 503 or 429.
  - Rejects task without description with informative exception or error.
- **SubtareaFeatureTest**:
  - Owner and Editor can trigger AI breakdown and receive valid payload.
  - Reader (`Lector`) is forbidden (403) from generating or mutating subtasks.
  - Batch insertion in 'reemplazar' mode deletes old subtasks and creates new ones, updating parent task effort.
  - Batch insertion in 'anexar' mode appends subtasks with sequential ordering.
  - Toggling subtasks automatically updates parent `estadoTarea` between 'Pendiente', 'En Progreso', and 'Completado'.
  - Marking parent task as completed cascades `completada = true` to all child subtasks.
  - Deleting a parent task cascades deletion to all child subtasks.

### Prior Art
- `tests/Feature/PerfilCompartidoTest.php`: Pattern for testing profile permission authorization and role boundaries using `actingAs` and database assertions.
- `tests/Feature/IntegracionExternaTest.php`: Pattern for testing external service communication and mocking external HTTP calls.

## Out of Scope

- Linking individual Pomodoro timer sessions (`SesionPomodoro`) directly to individual subtasks (sessions remain tracked at the parent `Tarea` level).
- Third-party drag-and-drop libraries for custom drag reordering in this increment (ordering is maintained numerically via `orden`).
- Multi-level hierarchical nesting (subtasks of subtasks).
- Triggering AI breakdown inside the new task creation form prior to saving the task (tasks must be saved with title and description first).

## Further Notes

- The Google Gemini API key is already configured in the environment (`GEMINI_API_KEY`) and tested with `gemini-2.5-flash`.
- Backward compatibility: Existing tasks without subtasks continue to operate seamlessly with standard Pomodoro counts and manual status transitions.
