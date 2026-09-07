# Tecnologías y Dependencias del Sistema

Este documento recopila las tecnologías, frameworks, APIs y librerías que conforman el stack técnico integral de **Cronos Notes**, detallando su definición, propósito en el proyecto y su funcionamiento interno tanto para el núcleo del sistema como para las mejoras de escalado (*Season 2*).

Las dependencias principales se encuentran registradas y gestionadas en los archivos [composer.json](/composer.json) (Backend), [package.json](/package.json) (Frontend) y [docker-compose.yml](/docker-compose.yml) (Infraestructura).

---

## 1. Stack Tecnológico Core

### PHP & Laravel Framework
* **¿Qué es?** Un framework de desarrollo web para PHP bajo el patrón de arquitectura MVC (Modelo-Vista-Controlador).
* **Uso en el Proyecto:** Actúa como nuestro motor de Backend. Se encarga de la seguridad, el enrutamiento web, la lógica de negocio pesada, la interacción con la base de datos (MySQL), el envío de notificaciones y la integración con las APIs de Inteligencia Artificial (Gemini y Whisper).
* **Funcionamiento Clave:**
  * **Eloquent ORM (Object-Relational Mapping):** Permite interactuar con la base de datos MySQL mediante objetos PHP bajo el patrón *Active Record* (`app/Models/Tarea.php`, `app/Models/Perfil.php`, `app/Models/Apunte.php`, `app/Models/SalaEstudio.php`).
  * **Laravel Socialite:** Librería oficial utilizada para los flujos de autorización OAuth 2.0 (Google Auth, Google Calendar, Google Meet y Spotify).

### Vue 3 (Frontend Framework)
* **¿Qué es?** Un framework progresivo de JavaScript utilizado para construir interfaces de usuario interactivas y reactivas.
* **Uso en el Proyecto:** Define toda la interfaz visual de Cronos Notes, gestionando temporizadores en tiempo real, editores de notas, reproductores multimedia y ventanas flotantes.
* **Funcionamiento Clave:**
  * **Composition API:** Permite estructurar el código mediante funciones reactivas y crear **Composables** reutilizables (`usePomodoroTimer`, `useFloatingTimer`, `useZenMixer`, `usePomodoroAudioAlerts`).
  * **Sistema de Reactividad:** Basado en Proxies de JavaScript para actualizar eficientemente solo los nodos del DOM modificados en cada segundo del temporizador.

### Inertia.js (El Puente Monolítico)
* **¿Qué es?** Un glue framework que conecta Laravel (Backend) con Vue 3 (Frontend) como una SPA sin necesidad de construir APIs REST duplicadas.
* **Uso en el Proyecto:** Conecta nuestros controladores de Laravel directamente con las vistas en [resources/js/Pages/](/resources/js/Pages/).
* **Funcionamiento Clave:** Intercepta peticiones de navegación y envía payloads JSON con las `props` necesarias para renderizar la vista correspondiente de forma instantánea.

### Tailwind CSS
* **¿Qué es?** Un framework CSS utilitario orientado al diseño directo en las plantillas HTML/Vue.
* **Uso en el Proyecto:** Estilizado visual de toda la plataforma, soportando diseño adaptativo, Modo Zen, Modo Oscuro y componentes flotantes compactos.

---

## 2. Inteligencia Artificial y Procesamiento Multimedia

### Google Gemini API (`gemini-2.0-flash`)
* **¿Qué es?** Modelo multimodal de lenguaje avanzado de Google.
* **Uso en el Proyecto:**
  1. **Priorización Inteligente de Tareas ([RF-M04](docs/rf_m04_procesamiento_ia_prioridad.md)):** Clasifica tareas según plazos de entrega y criticidad.
  2. **Desglose Automático de Tareas ([RF-M16](docs/rf_m16_desglose_automatico_tareas.md)):** Descompone objetivos complejos en subtareas jerárquicas con estimaciones en Pomodoros.
  3. **Resumen Inteligente Cornell ([RF-M14](docs/rf_m14_transcripcion_resumen_audios.md)):** Estructura transcripciones de audio en notas, ideas clave y resúmenes de estudio.

### OpenAI Whisper API / Gemini Multimodal Audio
* **¿Qué es?** Modelos de reconocimiento automático del habla (Speech-to-Text) de alta precisión multilingüe.
* **Uso en el Proyecto:** Transcripción automática de notas de voz y clases grabadas en el editor de apuntes ([RF-M14](docs/rf_m14_transcripcion_resumen_audios.md)).

---

## 3. Integraciones Externas y Colaboración

### Google Workspace APIs (Calendar & Meet)
* **¿Qué es?** Suite de servicios en la nube de Google accesibles mediante REST APIs.
* **Uso en el Proyecto:**
  * **Google Calendar API ([RF-M15](docs/rf_m15_sincronizacion_calendar_spotify.md)):** Sincroniza bloques de estudio y tareas con el calendario personal del usuario.
  * **Google Meet API ([RF-M17](docs/rf_m17_salas_estudio_virtuales_meet.md)):** Genera salas de videollamada dinámicas para sesiones de estudio grupal sincronizadas.

### Spotify Web API & Web Playback SDK
* **¿Qué es?** Plataforma de streaming de música y APIs de control de reproducción de Spotify.
* **Uso en el Proyecto:** Permite escuchar playlists de concentración (Lofi, Clásica, Ruido Marrón) y controlar la reproducción directamente desde el widget del Modo Zen ([RF-M15](docs/rf_m15_sincronizacion_calendar_spotify.md)).

---

## 4. Automatización e Infraestructura

### N8N (Workflow Automation)
* **¿Qué es?** Plataforma de automatización de flujos de trabajo basada en nodos y de código abierto.
* **Uso en el Proyecto:** Orquesta recordatorios externos (Telegram, Email, Discord), backups periódicos y reportes semanales de productividad sin sobrecargar el servidor web ([RF-M18](docs/rf_m18_orquestacion_automatizacion_n8n.md)).

### Docker & Docker Compose
* **¿Qué es?** Plataforma de contenerización para desplegar aplicaciones y servicios aislados.
* **Uso en el Proyecto:** Orquestación de los contenedores de MySQL, Laravel App y la instancia de N8N.

---

## 5. APIs Web Avanzadas del Navegador

### Document Picture-in-Picture API & HTML5 Canvas
* **¿Qué es?** Nueva API estándar de la web que permite abrir ventanas flotantes *Always-on-Top* con contenido HTML/CSS arbitrario.
* **Uso en el Proyecto:** Permite desanclar el temporizador Pomodoro en un **Mini-Timer Flotante** para monitorear el tiempo mientras se usan otras aplicaciones ([RF-M19](docs/rf_m19_minitimer_flotante.md)).

### Web Audio API & Howler.js
* **¿Qué es?** API nativa de JavaScript y librería especializada para la manipulación y reproducción de audio espacial multicanal.
* **Uso en el Proyecto:**
  * **Mezclador de Sonidos Ambientales ([RF-M06](docs/rf_m06_mezclador_sonidos.md)):** Reproducción concurrente en loop con volumen independiente (lluvia, fogata, cafetería).
  * **Notificaciones Auditivas ([RF-M20](docs/rf_m20_notificaciones_sonoras.md)):** Campanas y alertas sonoras para cambios de ciclo de Pomodoro.

### HTML5 Notifications API
* **¿Qué es?** Interfaz nativa para emitir notificaciones push del sistema operativo.
* **Uso en el Proyecto:** Avisos al completar sesiones Pomodoro y descansos cuando la pestaña está en segundo plano.

---

## 6. Utilidades de Frontend y Construcción

### Chart.js & Vue-Chartjs
* **Uso:** Generación de gráficos interactivos de horas de estudio y sesiones por perfil ([RF-06](docs/rf_6_sistema_estadisticas.md) y [RF-M13](docs/rf_m13_cambios_estadisticas.md)).

### Lucide Icons (`lucide-vue-next`)
* **Uso:** Iconografía vectorial SVG optimizada para interfaces web modernas.

### Vite & MySQL
* **Vite:** Empaquetador ultrarrápido con Hot Module Replacement (HMR).
* **MySQL:** Base de datos relacional para persistencia de usuarios, tareas, apuntes, salas y estadísticas.
