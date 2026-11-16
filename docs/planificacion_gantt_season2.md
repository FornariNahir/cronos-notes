# Planificación y Diagrama de Gantt — Incrementos Season 2 (RF-M14, RF-M15, RF-M16)

Este documento detalla la planificación incremental del desarrollo para los requerimientos **RF-M14**, **RF-M15** y **RF-M16**, asignados al **Grupo The Dinamit** (Dellagnolo, Ricardo & Romea Acevedo, Agustina), en el marco de la Licenciatura en Sistemas de Información (Universidad de la Cuenca del Plata).

---

## 1. Marco Metodológico y Régimen de Trabajo

- **Fecha de Inicio General:** Lunes 10 de Agosto de 2026 (Segunda semana de Agosto).
- **Fecha de Finalización:** Viernes 13 de Noviembre de 2026 (Cierre y entrega: Lunes 16 de Noviembre de 2026).
- **Régimen de Dedicación Semanal:** Trabajo en equipo concentrado exclusivamente los días **Lunes y Viernes** de cada semana (2 jornadas semanales sincrónicas / programación en parejas).
- **Metodología:** Extreme Programming (XP) con ciclo de vida iterativo e incremental.
- **Estructura de Fases por Requerimiento:**
  Cada requerimiento transita de forma rigurosa por las **4 fases del ciclo de vida del software**:
  1. **Análisis:** 1 semana (2 jornadas efectivas: Lunes y Viernes).
  2. **Diseño:** 1 semana (2 jornadas efectivas: Lunes y Viernes).
  3. **Codificación:** 1 semana (2 jornadas efectivas: Lunes y Viernes).
  4. **Prueba:** 1 semana (2 jornadas efectivas: Lunes y Viernes).
- **Duración Estimada:** 4 semanas por requerimiento (**aproximadamente 1 mes calendario / 8 jornadas laborales intensivas**), garantizando una cadencia predecible y justificando la culminación del ciclo en noviembre.

---

## 2. Desglose Estructurado de Tareas por Requerimiento

### 🔹 Fase Previa: Kick-off, Relevamiento y Configuración de Entorno (Season 2)
* **Período:** 10/08/2026 al 21/08/2026 (2 semanas / 4 jornadas: Lun 10/08, Vie 14/08, Lun 17/08, Vie 21/08).
* **Alcance:** Definición del backlog general de Season 2, acuerdos de arquitectura intercátedra, configuración de entornos Docker/Laravel y preparación de repositorios compartidos.

---

### 🔹 Incremento 1: [RF-M14] Transcripción Automática y Resumen Inteligente de Audios
* **Período:** Lunes 24/08/2026 al Viernes 18/09/2026 (4 semanas / 1 mes / 8 jornadas).
* **Módulo Afectado:** Módulo de Toma de Apuntes y Grabaciones Multimedia (`resources/js/Pages/apunte/`).

#### Fase 1: Análisis (24/08/2026 – 28/08/2026) — *Jornadas: Lun 24/08 y Vie 28/08*
* **T1.1 Elicitación y Especificación del Procesamiento de Audio:**
  * *ST1.1.1:* Análisis de formatos multimedia soportados (`.webm`, `.mp3`, `.wav`) y límites de tamaño/duración de grabación.
  * *ST1.1.2:* Especificación de Endpoints para la API de Whisper / Gemini Multimodal (Speech-to-Text).
  * *ST1.1.3:* Definición del esquema JSON estricto para el prompt de resumen estructurado bajo el método Cornell.

#### Fase 2: Diseño (31/08/2026 – 04/09/2026) — *Jornadas: Lun 31/08 y Vie 04/09*
* **T1.2 Diseño de arquitectura, datos e interfaces:**
  * *ST1.2.1:* Prototipado UX/UI de la interfaz y estados de procesamiento (`TranscriptionModal.vue` y `AudioPanel.vue`).
  * *ST1.2.2:* Diseño del modelo relacional: tabla `apunte_audios` con columnas `transcripcion` y `resumen_ia`.
  * *ST1.2.3:* Diagrama de secuencia del flujo asíncrono Frontend ➔ Laravel ➔ Whisper/Gemini API ➔ Base de Datos.

#### Fase 3: Codificación (07/09/2026 – 11/09/2026) — *Jornadas: Lun 07/09 y Vie 11/09*
* **T1.3 Implementación de componentes Backend y Frontend:**
  * *ST1.3.1:* Backend: implementación del servicio de transcripción de audio con Whisper y Gemini (`AudioTranscriptionService.php`).
  * *ST1.3.2:* Backend: creación del controlador y rutas seguras para la web (`AudioTranscriptionController.php`).
  * *ST1.3.3:* Frontend: creación del componente de transcripción e integración del botón para ejecutar la acción.
  * *ST1.3.4:* Frontend: lógica de inserción automática y formateo de texto en las 3 secciones Cornell (`Editor.vue`).

#### Fase 4: Prueba (14/09/2026 – 18/09/2026) — *Jornadas: Lun 14/09 y Vie 18/09*
* **T1.4 Validación y aseguramiento de calidad QA:**
  * *ST1.4.1:* Pruebas unitarias del servicio de transcripción con análisis de las respuestas (`AudioTranscriptionServiceTest.php`).
  * *ST1.4.2:* Pruebas de integración subiendo audios de diferentes duraciones (de 15s a 5min).
  * *ST1.4.3:* Validación del caso de prueba: grabación, transcripción, inserción.
* **Hito 1:** Finalización e integración de **RF-M14** (Viernes 18/09/2026).

---

### 🔹 Incremento 2: [RF-M15] Sincronización Completa con Google Calendar y Spotify
* **Período:** Lunes 21/09/2026 al Viernes 16/10/2026 (4 semanas / 1 mes / 8 jornadas).
* **Módulo Afectado:** Módulo de Integraciones Externas, Calendario y Espacio Zen (`Calendario.vue`, `SesionZen.vue`, `PerfilUsuario.vue`).

#### Fase 1: Análisis (21/09/2026 – 25/09/2026) — *Jornadas: Lun 21/09 y Vie 25/09*
* **T2.1 Análisis de Protocolos de Integración Externa:**
  * *ST2.1.1:* Análisis de alcances OAuth 2.0 requeridos para Calendar (`calendar.events`) y Spotify (`streaming`, `user-read-playback-state`).
  * *ST2.1.2:* Especificación de reglas de sincronización y resolución de conflictos de horarios.
  * *ST2.1.3:* Especificación del ciclo de vida de Spotify en el navegador (Web Playback SDK vs. deeplink en cuenta Free).

#### Fase 2: Diseño (28/09/2026 – 02/10/2026) — *Jornadas: Lun 28/09 y Vie 02/10*
* **T2.2 Diseño de componentes multimedia y arquitectura:**
  * *ST2.2.1:* Diseño UX/UI del widget de Spotify en el espacio de concentración (`SpotifyPlayer.vue` / `SesionZen.vue`).
  * *ST2.2.2:* Diseño de interfaz en el calendario con botón de sincronización con Google Calendar (`Calendario.vue`).
  * *ST2.2.3:* Diseño del esquema de almacenamiento seguro de tokens para las integraciones externas (`integracion_externa`).

#### Fase 3: Codificación (05/10/2026 – 09/10/2026) — *Jornadas: Lun 05/10 y Vie 09/10*
* **T2.3 Desarrollo e implementación:**
  * *ST2.3.1:* Backend: Implementación del servicio de Google Calendar (`GoogleCalendarService.php`).
  * *ST2.3.2:* Backend: Implementación del servicio de Spotify (`SpotifyService.php`).
  * *ST2.3.3:* Backend: Desarrollo de los controladores para los servicios con manejos adecuados (`GoogleCalendarController.php`, `SpotifyController.php`).
  * *ST2.3.4:* Frontend: Integración del Script y componentes necesarios para el uso de Spotify en el espacio.
  * *ST2.3.5:* Frontend: Lógica de sincronización en Calendario y enlace de tareas con eventos en página de tareas.

#### Fase 4: Prueba (12/10/2026 – 16/10/2026) — *Jornadas: Lun 12/10 y Vie 16/10*
* **T2.4 Aseguramiento de calidad e interoperabilidad:**
  * *ST2.4.1:* Pruebas de OAuth 2.0 y renovación automática de tokens (`ExternalTokenServiceTest.php`).
  * *ST2.4.2:* Pruebas de sincronización real de eventos en Google Calendar.
  * *ST2.4.3:* Pruebas de reproducción de audio, cambio de pistas y volumen en Spotify.
* **Hito 2:** Finalización e integración de **RF-M15** (Viernes 16/10/2026).

---

### 🔹 Incremento 3: [RF-M16] Desglose Automático de Tareas con IA (Subtareas Inteligentes)
* **Período:** Lunes 19/10/2026 al Viernes 13/11/2026 (4 semanas / 1 mes / 8 jornadas).
* **Módulo Afectado:** Módulo de Gestión de Tareas y Espacio de Concentración Pomodoro (`GestionTareas.vue`, `SesionZen.vue`).

#### Fase 1: Análisis (19/10/2026 – 23/10/2026) — *Jornadas: Lun 19/10 y Vie 23/10*
* **T3.1 Análisis: Elicitación y Modelado de Subtareas:**
  * *ST3.1.1:* Análisis de patrones de desglose de tareas complejas en actividades simples secuenciales.
  * *ST3.1.2:* Diseño de la estructura del prompt para Gemini API con esquema JSON forzado y estimaciones Pomodoro.
  * *ST3.1.3:* Definición de reglas para el cálculo de avance porcentual de la tarea principal (`recalcularProgreso`).

#### Fase 2: Diseño (26/10/2026 – 30/10/2026) — *Jornadas: Lun 26/10 y Vie 30/10*
* **T3.2 Diseño técnico y prototipado UI:**
  * *ST3.2.1:* Diseño UX/UI del modal de subtareas con Checkboxes, edición y reordenamiento (`SubtaskBreakdownModal.vue`).
  * *ST3.2.2:* Diseño visual de la tarjeta de tarea con checklist interactivo y barra de progreso.
  * *ST3.2.3:* Diseño del modelo relacional: tabla `subtareas` con claves foráneas, índices y reglas de borrado en cascada.

#### Fase 3: Codificación (02/11/2026 – 06/11/2026) — *Jornadas: Lun 02/11 y Vie 06/11*
* **T3.3 Desarrollo e implementación:**
  * *ST3.3.1:* Backend: Migración y modelo de la clase Subtarea con relación a la clase Tarea (`Subtarea.php`, `Tarea.php`).
  * *ST3.3.2:* Backend: Implementación de un servicio de desglose de tareas con prompts en formato JSON y cadena de fallback (`TaskBreakdownService.php`).
  * *ST3.3.3:* Backend: Creación de un controlador del desglose de tareas y métodos de persistencia de datos (`SubtareaController.php`).
  * *ST3.3.4:* Frontend: Implementación del componente de Subtareas con validaciones de edición (`SubtaskBreakdownModal.vue`).
  * *ST3.3.5:* Frontend: Actualización de las tarjetas de tareas para marcar subtareas completadas en tiempo real y Paso en Foco en Sesión Zen (`GestionTareas.vue`, `SesionZen.vue`).

#### Fase 4: Pruebas (09/11/2026 – 13/11/2026) — *Jornadas: Lun 09/11 y Vie 13/11*
* **T3.4 Aseguramiento de calidad y verificación:**
  * *ST3.4.1:* Pruebas unitarias del requerimiento (`TaskBreakdownServiceTest.php` con mocks de fallback).
  * *ST3.4.2:* Pruebas de integración: persistencia de subtareas y cálculo automático de completitud de la tarea padre (`SubtareaFeatureTest.php`).
  * *ST3.4.3:* Pruebas de usabilidad con diferentes tipos de tareas y soporte de temas claro/oscuro en Espacio Zen.
* **Hito 3 & Cierre Season 2:** Finalización de **RF-M16** y cierre formal del ciclo (Viernes 13/11/2026 – Lunes 16/11/2026).

---

## 3. Cronograma de Actividades (Gantt Tabular)

| Requerimiento | Fase del Ciclo de Vida | Fecha Inicio | Fecha Fin | Duración Calendario | Jornadas Efectivas (Lun y Vie) | Responsables | Entregable Clave |
| :--- | :--- | :---: | :---: | :---: | :---: | :--- | :--- |
| **Season 2 Setup** | Relevamiento y Entorno | 10/08/2026 | 21/08/2026 | 2 semanas | 4 jornadas | R. Dellagnolo & A. Romea | Backlog general y entorno Docker/Laravel |
| | | | | | | | |
| **RF-M14** | 1. Análisis | 24/08/2026 | 28/08/2026 | 1 semana | Lun 24/08, Vie 28/08 | R. Dellagnolo & A. Romea | Especificación y contratos de API Whisper |
| **RF-M14** | 2. Diseño | 31/08/2026 | 04/09/2026 | 1 semana | Lun 31/08, Vie 04/09 | R. Dellagnolo & A. Romea | Prototipo UI modal y esquema relacional |
| **RF-M14** | 3. Codificación | 07/09/2026 | 11/09/2026 | 1 semana | Lun 07/09, Vie 11/09 | R. Dellagnolo & A. Romea | Servicio Whisper/Gemini + Editor Cornell |
| **RF-M14** | 4. Prueba | 14/09/2026 | 18/09/2026 | 1 semana | Lun 14/09, Vie 18/09 | R. Dellagnolo & A. Romea | Suite QA y pruebas de grabación |
| **RF-M14** | **Hito 1** | **18/09/2026** | **18/09/2026** | **Hito** | **Cierre Hito** | R. Dellagnolo & A. Romea | **RF-M14 Integrado en Producción** |
| | | | | | | | |
| **RF-M15** | 1. Análisis | 21/09/2026 | 25/09/2026 | 1 semana | Lun 21/09, Vie 25/09 | R. Dellagnolo & A. Romea | Análisis OAuth 2.0 y Web Playback SDK |
| **RF-M15** | 2. Diseño | 28/09/2026 | 02/10/2026 | 1 semana | Lun 28/09, Vie 02/10 | R. Dellagnolo & A. Romea | Widget Spotify + Vista Calendario |
| **RF-M15** | 3. Codificación | 05/10/2026 | 09/10/2026 | 1 semana | Lun 05/10, Vie 09/10 | R. Dellagnolo & A. Romea | Servicios Google/Spotify + Controladores |
| **RF-M15** | 4. Prueba | 12/10/2026 | 16/10/2026 | 1 semana | Lun 12/10, Vie 16/10 | R. Dellagnolo & A. Romea | Pruebas de tokens, streaming y sync |
| **RF-M15** | **Hito 2** | **16/10/2026** | **16/10/2026** | **Hito** | **Cierre Hito** | R. Dellagnolo & A. Romea | **RF-M15 Integrado en Producción** |
| | | | | | | | |
| **RF-M16** | 1. Análisis | 19/10/2026 | 23/10/2026 | 1 semana | Lun 19/10, Vie 23/10 | R. Dellagnolo & A. Romea | Elicitación y estructura prompt Gemini |
| **RF-M16** | 2. Diseño | 26/10/2026 | 30/10/2026 | 1 semana | Lun 26/10, Vie 30/10 | R. Dellagnolo & A. Romea | Modal interactivo + Modelo Subtarea |
| **RF-M16** | 3. Codificación | 02/11/2026 | 06/11/2026 | 1 semana | Lun 02/11, Vie 06/11 | R. Dellagnolo & A. Romea | TaskBreakdownService + Paso en Foco Zen |
| **RF-M16** | 4. Prueba | 09/11/2026 | 13/11/2026 | 1 semana | Lun 09/11, Vie 13/11 | R. Dellagnolo & A. Romea | Tests unitarios, fallback y usabilidad |
| **RF-M16** | **Hito 3** | **13/11/2026** | **16/11/2026** | **Hito** | **Cierre Final** | R. Dellagnolo & A. Romea | **RF-M16 Integrado y Season 2 Finalizada** |

---

## 4. Diagrama de Gantt Visual (Mermaid)

```mermaid
gantt
    title Cronos Notes Season 2 — Planificación Temporal (Lunes y Viernes)
    dateFormat  YYYY-MM-DD
    axisFormat  %d/%m
    
    section Setup Inicial
    Kick-off y Setup de Entorno      :done, pre_setup, 2026-08-10, 2026-08-21
    
    section RF-M14: Transcripción y Resumen IA
    1. Análisis (Whisper & Cornell)  :done, rf14_ana, 2026-08-24, 2026-08-28
    2. Diseño (UX/UI & Modelo Datos) :done, rf14_dis, 2026-08-31, 2026-09-04
    3. Codificación (Service & Editor):done, rf14_cod, 2026-09-07, 2026-09-11
    4. Prueba (Validación QA)        :done, rf14_pru, 2026-09-14, 2026-09-18
    Hito 1: RF-M14 Finalizado        :milestone, rf14_fin, 2026-09-18, 0d
    
    section RF-M15: Calendar y Spotify
    1. Análisis (OAuth & Protocolos) :done, rf15_ana, 2026-09-21, 2026-09-25
    2. Diseño (Widgets & Seguridad)  :done, rf15_dis, 2026-09-28, 2026-10-02
    3. Codificación (APIs & UI Zen)  :done, rf15_cod, 2026-10-05, 2026-10-09
    4. Prueba (Handshake & Streaming):done, rf15_pru, 2026-10-12, 2026-10-16
    Hito 2: RF-M15 Finalizado        :milestone, rf15_fin, 2026-10-16, 0d
    
    section RF-M16: Desglose IA de Tareas
    1. Análisis (Modelado Subtareas) :done, rf16_ana, 2026-10-19, 2026-10-23
    2. Diseño (Modal & Acordeón UI)  :done, rf16_dis, 2026-10-26, 2026-10-30
    3. Codificación (Fallback & Zen) :done, rf16_cod, 2026-11-02, 2026-11-06
    4. Prueba (Tests Unitarios & QA) :done, rf16_pru, 2026-11-09, 2026-11-13
    Hito 3: RF-M16 y Season 2 Cierre :milestone, rf16_fin, 2026-11-13, 0d
```
