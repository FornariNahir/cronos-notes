# [RF-M19] Mini-Timer Flotante (Picture-in-Picture / Document PiP)

## 1. Descripción y Objetivo
Este requerimiento provee la capacidad de desacoplar el temporizador Pomodoro de la ventana principal del navegador, permitiendo al usuario continuar su flujo de trabajo en cualquier otra aplicación sin perder de vista su tiempo de concentración:
- **Ventana Flotante Always-on-Top**: Aprovecha la moderna API de Document Picture-in-Picture (con fallback a Canvas/Video PiP) para abrir una ventana compacta y siempre visible sobre cualquier software (IDEs, lectores de PDF, procesadores de texto o navegadores).
- **Controles Interactivos Integrados**: La ventana flotante muestra el tiempo restante en formato `MM:SS`, la barra de progreso circular del ciclo, el tipo de intervalo activo (*Concentración*, *Descanso Corto*, *Descanso Largo*) y botones para pausar, reanudar o saltar de bloque.
- **Sincronización Bidireccional en Tiempo Real**: Todo cambio realizado en la mini-ventana flotante se refleja instantáneamente en la aplicación web principal y viceversa.
- **Objetivo**: Evitar que el usuario deba cambiar constantemente a la pestaña de Cronos Notes para comprobar el tiempo restante, eliminando fuentes de distracción y manteniendo el foco en la tarea activa.

---

## 2. Tecnologías, Herramientas y Librerías

- **Document Picture-in-Picture API (W3C Standard)**: API nativa de JavaScript que permite renderizar un árbol DOM completo de HTML/CSS arbitrario dentro de una ventana flotante *Always-on-Top*.
- **HTML5 Canvas API & MediaStream (Fallback)**: Mecanismo de respaldo para navegadores que no soportan Document PiP nativo, generando un stream de video en tiempo real desde un `<canvas>` con el temporizador animado.
- **Vue 3 Composable (`useFloatingTimer.js`)**: Encapsula el estado reactivo del temporizador y sincroniza eventos de apertura, cierre e interacciones entre la ventana principal y la ventana PiP.
- **Tailwind CSS**: Estilos compactos y de alto contraste optimizados para la mini-ventana.

---

## 3. Archivos Involucrados en el Requerimiento

### Frontend (Vue 3 & JavaScript)
- [FloatingTimer.vue](/resources/js/Components/FloatingTimer.vue) - Componente visual que define el layout de la mini-ventana flotante (reloj circular, etiquetas y controles).
- [useFloatingTimer.js](/resources/js/Composables/useFloatingTimer.js) - Composable que administra la API de Document Picture-in-Picture, transfiere estilos CSS e intercambia eventos.
- [SesionZen.vue](/resources/js/Pages/pomodoro/SesionZen.vue) - Incorpora el botón con ícono PiP para activar/desactivar el modo flotante.
- [AppLayout.vue](/resources/js/Layouts/AppLayout.vue) - Permite mantener el mini-timer activo mientras el usuario navega entre diferentes secciones de Cronos Notes (Tareas, Apuntes, Calendario).

---

## 4. Flujo de Datos y Control

### Diagrama de Flujo del Requerimiento
```mermaid
graph TD
    A[Usuario en Sesión Pomodoro: Clic en botón 'Mini-Timer Flotante'] --> B[Frontend: useFloatingTimer verifica soporte de Document PiP]
    B -->|Soportado| C[Navegador: window.documentPictureInPicture.requestWindow()]
    B -->|No soportado| D[Navegador: Genera stream desde Canvas en elemento Video PiP]
    C --> E[Frontend: Clona estilos CSS y monta FloatingTimer.vue en la nueva ventana]
    E --> F[Ventana Flotante: Renderiza reloj y botones Play/Pausa en primer plano]
    F --> G[Usuario: Interactúa con botones en la ventana flotante]
    G --> H[Composable: Actualiza usePomodoroTimer.js reactivamente en ambas ventanas]
```

### Detalle del Flujo de Control (Pasos)
1. **Activación:** Durante una sesión Pomodoro (en `SesionZen.vue` o el widget global), el usuario hace clic en el botón *"Modo Flotante"*.
2. **Solicitud de Ventana PiP:**
   - El composable `useFloatingTimer.js` invoca `await window.documentPictureInPicture.requestWindow({ width: 280, height: 180 })`.
   - Se copian automáticamente las hojas de estilo compiladas de Tailwind a la cabecera de la ventana PiP.
3. **Montaje del Componente:** Se monta una instancia del componente `FloatingTimer.vue` en el `body` de la ventana flotante recién creada.
4. **Sincronización de Estado:** Las variables reactivas (`tiempoRestante`, `estadoCiclo`, `estaPausado`) se vinculan bidireccionalmente:
   - Cada *tick* del temporizador principal actualiza el DOM de la ventana flotante.
   - Cualquier clic en "Pausar", "Reanudar" o "Siguiente" dentro del mini-timer dispara las funciones correspondientes en el temporizador central.
5. **Cierre:** Si el usuario cierra la ventana flotante manualmente o finaliza la sesión en la pestaña principal, el composable limpia los event listeners y restaura el control exclusivo en la ventana principal.

---

## 5. Pruebas y Validación (QA)

1. **Precondición:** Abrir Cronos Notes en un navegador compatible (Chrome/Edge versión 116+) e iniciar una sesión de concentración de 25 minutos.
2. **Paso 1:** En la pantalla de concentración Pomodoro, hacer clic en el botón con ícono de ventana flotante "Abrir Mini-Timer".
3. **Resultado Esperado 1:** Se abre una ventana pequeña flotante (de aproximadamente 280x180 px) que permanece visible en el extremo superior de la pantalla.
4. **Paso 2:** Minimizar el navegador principal o cambiar a otra aplicación (ej. Visual Studio Code o un lector de PDF).
5. **Resultado Esperado 2:** La mini-ventana permanece visible en primer plano (*Always-on-Top*), mostrando el conteo regresivo segundo a segundo.
6. **Paso 3:** Hacer clic en el botón "Pausar" dentro de la ventana flotante.
7. **Resultado Esperado 3:** El contador se detiene en ambas ventanas. Al presionar "Reanudar", la cuenta regresiva continúa normalmente.
8. **Paso 4:** Cerrar la ventana flotante desde su botón de cerrar "X".
9. **Resultado Esperado 4:** El temporizador continúa corriendo sin interrupciones en la pestaña principal de Cronos Notes.
