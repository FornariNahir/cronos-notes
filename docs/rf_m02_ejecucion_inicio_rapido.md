# [RF-M02] Ejecución de Inicio Rápido

## 1. Descripción y Objetivo
Este requerimiento provee un mecanismo para iniciar una sesión de temporizador Pomodoro de forma inmediata con un solo clic.
- **Evitar Configuración Mandatoria**: El usuario puede presionar el botón de "Inicio Rápido" en el configurador para saltarse la selección de perfiles de trabajo, especificación de tareas activas o configuraciones de tiempo personalizadas.
- **Objetivo**: Facilitar una vía de acceso directa y sin fricción al temporizador de 25 minutos de trabajo y 5 minutos de descanso, aumentando la retención inicial y permitiendo al usuario enfocarse de inmediato ante impulsos espontáneos de concentración.

---

## 2. Tecnologías, Herramientas y Librerías
Este requerimiento interactúa con la lógica de sesiones e interfaz mediante:

- **Vue 3 (Composition API)**: Consumo del método `iniciarInicioRapido()` expuesto por el composable de temporizador.
- **Laravel / MySQL**: Registro de la sesión activa en la tabla `SesionPomodoro` para usuarios registrados una vez que el temporizador inicia su conteo.

---

## 3. Archivos Involucrados en el Requerimiento

### Frontend (Vue 3)
- [SesionZen.vue](/resources/js/Pages/pomodoro/SesionZen.vue) - Contiene la interfaz del panel de configurador y el botón "Inicio Rápido (25 min)" en la pantalla de Setup.
- [usePomodoroTimer.js](/resources/js/Composables/usePomodoroTimer.js) - Composable reactivo que inicializa los valores por defecto (25m de trabajo, 5m de descanso corto, 15m de descanso largo) y coordina el disparo del endpoint en el backend.

### Backend & Controladores (Laravel)
- [PomodoroController.php](/app/Http/Controllers/PomodoroController.php) - Recibe las peticiones HTTP para persistir el inicio de la sesión en el backend (`iniciarSesion()`).

### Modelos y Datos (Eloquent ORM)
- [SesionPomodoro.php](/app/Models/SesionPomodoro.php) - Modelo relacional que representa el registro físico del bloque de concentración en la base de datos MySQL.

---

## 4. Flujo de Datos y Control

### Diagrama de Flujo del Requerimiento
```mermaid
graph TD
    A[Usuario: Presiona 'Inicio Rápido (25 min)'] --> B[Frontend: Asigna idTarea=null y carga tiempos estándar de 25/5/15]
    B --> C[Frontend: Invoca iniciarInicioRapido en usePomodoroTimer]
    C --> D[Frontend: Llama a iniciarSesion enviando tiempos estándar por POST]
    D --> E[Backend: Registra sesión con estado 'Activa' en tabla SesionPomodoro]
    E --> F[Frontend: Comienza la cuenta regresiva visible desde 25:00]
```

### Detalle del Flujo de Control (Pasos)
1. **Frontend:** El usuario abre el espacio de Pomodoro en estado de Setup y presiona el botón "Inicio Rápido".
2. **Establecimiento de Parámetros:** El frontend asigna variables nulas para `idTarea` e inicia la fase de trabajo en 1500 segundos (25 minutos).
3. **Petición del Servidor:** El composable realiza una petición HTTP POST a `/pomodoro/iniciar` informando la configuración básica del ciclo.
4. **Backend (Persistencia):** El controlador recibe la petición y, si el usuario está autenticado, persiste una nueva fila en la tabla `SesionPomodoro` con la fecha y hora de inicio actual.
5. **Inicio de Cronómetro:** El frontend recibe la respuesta exitosa y arranca el contador de segundos, refrescando la interfaz del temporizador dinámicamente cada segundo.

---

## 5. Pruebas y Validación (QA)

1. **Precondición:** Estar en la pantalla de pomodoro en estado de Setup (sin cronómetro activo en el momento).
2. **Paso 1:** Presionar el botón "Inicio Rápido (25 min)" en el widget.
3. **Resultado Esperado:** El widget de Setup debe ocultarse y mostrarse la pantalla activa con la etiqueta "Trabajo" y el temporizador corriendo hacia atrás desde `25:00`.
4. **Paso 2 (Opcional):** Si se está autenticado con una cuenta, ingresar a "Estadísticas" tras completar o pausar esta sesión rápida.
5. **Resultado Esperado 2:** La sesión rápida debe aparecer listada e integrada en el total de horas de concentración o historial de uso.
