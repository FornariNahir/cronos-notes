# 03: Servicio de IA TaskBreakdownService con fallback y validación de contexto

**What to build:**
Servicio de backend que procesa una tarea y genera un desglose inteligente de 3 a 7 subtareas accionables con estimación de 1 a 4 Pomodoros cada una mediante la API de Google Gemini, utilizando una cadena de modelos resiliente (`gemini-2.5-flash` ➔ `gemini-2.5-flash-lite` ➔ `gemini-flash-latest`), esquema JSON estructurado y validación de contexto (título y descripción obligatorios).

**Blocked by:** None (can start immediately)

**Status:** resolved

- [x] Servicio `app/Services/TaskBreakdownService.php` con llamada HTTP a Gemini API y fallback en cascada.
- [x] Prompt especializado con rol de tutor Pomodoro y esquema JSON estricto (`responseSchema`).
- [x] Endpoint `POST /tareas/{tarea}/desglosar-ia` en `TareaController` que invoca el servicio y retorna la sugerencia estructurada en formato JSON.
- [x] Validación de precondición: rechazo con HTTP 422 si la tarea no tiene `descripcionTarea` detallada.
- [x] Validación de permisos en perfil activo (solo administradores y editores).
- [x] Pruebas en `tests/Feature/TaskBreakdownServiceTest.php` usando `Http::fake()` para escenarios exitosos, fallback de modelos y validación de descripción faltante.
