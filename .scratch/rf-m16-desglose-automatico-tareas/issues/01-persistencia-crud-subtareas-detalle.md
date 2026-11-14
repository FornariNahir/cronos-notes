# 01: Persistencia relacional, endpoints CRUD y gestión manual de Subtareas en Modal de Detalle

**What to build:**
Permitir a los usuarios crear, editar, eliminar y alternar la completitud de subtareas manualmente desde el modal de detalle de cualquier tarea existente (`modalVerDetalle`). Al modificar o marcar subtareas, el sistema sincroniza automáticamente el estado de la tarea padre (`Pendiente`, `En Progreso`, `Completado`) y recalcula su estimación acumulada en Pomodoros. Se restringe la mutación para usuarios con rol `Lector` en perfiles compartidos.

**Blocked by:** None (can start immediately)

**Status:** resolved

- [x] Migración de la tabla `subtareas` con `idSubtarea`, `idTarea` (FK cascade), `titulo`, `completada`, `estimacionEsfuerzo`, `orden` y timestamps.
- [x] Modelo Eloquent `Subtarea` con casts, fillable y relación `belongsTo(Tarea::class)`.
- [x] Relación `hasMany(Subtarea::class)` en `Tarea` y métodos de recálculo de esfuerzo y estado.
- [x] Controlador `SubtareaController` con métodos `store`, `update`, `destroy` y `toggle`, validando permisos del perfil.
- [x] Actualización en cascada cuando la tarea padre se marca como 'Completado' para marcar sus subtareas como completadas.
- [x] Rutas registradas en `routes/web.php` protegidas por autenticación.
- [x] Interfaz en `modalVerDetalle` (en `GestionTareas.vue`) para listar subtareas, agregar nueva subtarea con título y pomodoros, editar, eliminar y marcar casilla de completada.
- [x] Pruebas automatizadas en `tests/Feature/SubtareaFeatureTest.php` cubriendo CRUD, permisos por rol y recálculo automático de estado y esfuerzo.
