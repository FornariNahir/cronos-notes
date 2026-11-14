# Persistencia Relacional de Subtareas con Eloquent

## Contexto y Decisión
Para soportar el desglose automático de tareas con IA (RF-M16) y la descomposición en pasos accionables, decidimos implementar una tabla relacional dedicada `subtareas` con su respectivo modelo Eloquent `Subtarea`, vinculado mediante clave foránea `idTarea` con eliminación en cascada.

## Opciones Consideradas
- **Columna JSON en tabla Tarea**: Se descartó porque impedía actualizaciones atómicas de estado por subtarea, carecía de integridad referencial y dificultaba métricas de completitud agregadas.
- **Relación jerárquica autorreferencial (tarea_padre_id)**: Se descartó para no sobrecargar el modelo `Tarea` con complejidad de anidamiento innecesaria y atributos que no aplican a pasos atómicos (fechas límites, prioridades, etc.).
- **Tabla relacional independiente `subtareas`**: Seleccionada por permitir ciclo de vida granular, orden explícito y sincronización reactiva del estado con la tarea padre.

## Consecuencias
- La tabla `subtareas` cuenta con campos: `idSubtarea`, `idTarea`, `titulo`, `completada` (booleano), `estimacionEsfuerzo` (pomodoros de 1 a 4) y `orden`.
- Las actualizaciones de una subtarea sincronizan automáticamente el estado general (`estadoTarea`) y la estimación acumulada de la tarea padre.
