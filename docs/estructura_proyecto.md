# Estructura del Proyecto y Arquitectura

Este documento describe la organización de carpetas del proyecto **Cronos Notes** y detalla las decisiones arquitectónicas que justifican esta estructura, incluyendo los módulos de escalado (*Season 2*).

---

## 1. Patrón Arquitectónico: Monolito Híbrido con Servicios Asíncronos

Cronos Notes está construido bajo el patrón de **Monolito Híbrido** utilizando **Inertia.js** como puente entre **Laravel (Backend)** y **Vue 3 (Frontend)**, complementado con un motor de automatización contenerizado (**N8N en Docker**) y micro-integraciones con APIs de Inteligencia Artificial y servicios de Google y Spotify.

### Ventajas de esta Arquitectura:
1. **Desarrollo Ágil y Programación en Parejas (XP):** Todo el código vive en un único repositorio sincronizado. Las rutas principales se definen en el backend (`routes/web.php` y `routes/api.php`).
2. **Seguridad y Gestión de Sesiones:** Autenticación protegida mediante cookies `HttpOnly` y sesiones de Laravel, protegiendo las credenciales de integraciones OAuth (Google y Spotify) en el servidor.
3. **Paso de Datos Reactivo:** Inertia.js inyecta el estado y las propiedades del backend directamente a los componentes Vue como `props` sin requerir endpoints REST intermedios redundantes.
4. **Desacoplamiento de Procesos Pesados:** Los flujos de recordatorios externos y automatizaciones periódicas se delegan a un contenedor de **N8N** mediante webhooks.

---

## 2. Mapa del Directorio Principal

```
Cronos-Notes/
├── app/                              # Capa Lógica del Backend (PHP / Laravel)
│   ├── Http/                         # Controladores, Middleware y Requests HTTP
│   │   ├── Controllers/              # Controladores de dominio (Pomodoro, Tareas, Apuntes, Salas)
│   │   │   ├── Api/                  # Controladores de Webhooks (N8N)
│   │   │   └── Auth/                 # Controladores de Autenticación y OAuth
│   │   └── Middleware/               # Filtros de sesión y autorización
│   ├── Models/                       # Modelos de Datos (Eloquent ORM)
│   ├── Notifications/                # Notificaciones por correo (Invitaciones, Alertas)
│   ├── Policies/                     # Reglas de autorización (PerfilPolicy)
│   ├── Providers/                    # Service Providers de Laravel
│   └── Services/                     # Capa de Lógica de Negocio e Integraciones
│       ├── AudioTranscriptionService.php  # Whisper / Gemini STT & Resumen
│       ├── GoogleCalendarService.php     # Sincronización con Google Calendar
│       ├── GoogleMeetService.php         # Generación de reuniones de Meet
│       ├── SpotifyService.php            # Control de reproducción de Spotify
│       ├── TaskBreakdownService.php      # Desglose de tareas con Gemini API
│       ├── N8nService.php                # Emisión de webhooks hacia N8N
│       └── EstadisticaService.php        # Cómputo de horas, sesiones y rachas
├── docker/                           # Configuración de Contenedores
│   └── n8n/                          # Workflows y configuración del motor N8N
├── docker-compose.yml                # Orquestación de servicios (App, MySQL, N8N)
├── docs/                             # Documentación técnica y especificaciones (RFs / RF-Ms)
│   ├── agents/                       # Directivas y contexto para agentes de IA
│   ├── rf_1_*.md a rf_6_*.md         # Requerimientos Funcionales Base
│   └── rf_m01_*.md a rf_m20_*.md     # Requerimientos Funcionales de Mejora
├── public/                           # Recursos estáticos de acceso público
│   ├── audios/                       # Pistas de sonido ambiental (Lluvia, Fogata, Café)
│   └── sounds/alerts/                # Campanas y alertas sonoras del Pomodoro
├── resources/                        # Capa Frontend (Vue 3 / JavaScript / CSS)
│   ├── js/                           # Código de la aplicación Vue.js
│   │   ├── Components/               # Componentes Vue reutilizables (Modals, Botones, UI)
│   │   ├── Composables/              # Hooks reactivos (usePomodoroTimer, useFloatingTimer, etc.)
│   │   ├── Layouts/                  # Estructuras de página (AppLayout, GuestLayout)
│   │   └── Pages/                    # Vistas completas renderizadas por Inertia
│   │       ├── apunte/               # Editor Cornell, panel de audio y transcripción
│   │       ├── auth/                 # Login, Registro, Recuperación y Google Sign-In
│   │       ├── pomodoro/             # Sesión Zen, mezclador y reproductor Spotify
│   │       ├── sala-estudio/         # Salas virtuales con Google Meet
│   │       ├── Calendario.vue        # Vista visual de tareas y Google Calendar
│   │       ├── Dashboard.vue         # Panel principal de productividad
│   │       ├── Estadisticas.vue      # Gráficos y métricas de concentración
│   │       ├── GestionPerfil.vue     # Administración de perfiles y colaboradores
│   │       └── GestionTareas.vue     # Panel de tareas, priorización y desglose IA
│   └── css/                          # Estilos globales y configuración de Tailwind CSS
├── routes/                           # Definición de Rutas
│   ├── web.php                       # Rutas web SPA manejadas por Inertia
│   ├── api.php                       # Rutas de webhooks (N8N) y servicios REST
│   └── auth.php                      # Rutas de autenticación y callbacks OAuth
└── tests/                            # Pruebas automatizadas (PHPUnit)
```

---

## 3. Justificación de los Módulos Específicos

### Capa de Servicios del Backend: [app/Services/](/app/Services/)
- **AudioTranscriptionService:** Centraliza la comunicación con Whisper API y Gemini Multimodal para convertir audio a texto y estructurar el resumen en formato Cornell.
- **TaskBreakdownService:** Construye prompts con restricciones de formato JSON para que Gemini API devuelva listas de subtareas jerárquicas con estimaciones en Pomodoros.
- **GoogleCalendarService & SpotifyService:** Encapsulan los llamados autorizados mediante tokens OAuth 2.0 evitando acoplar las APIs externas a los controladores.
- **GoogleMeetService:** Genera dinámicamente enlaces de videoconferencia para las salas de estudio en vivo.
- **N8nService:** Envía eventos del sistema (tareas por vencer, resúmenes semanales) hacia los webhooks de N8N en Docker.

### Capa de Composables del Frontend: [resources/js/Composables/](/resources/js/Composables/)
- **`usePomodoroTimer.js`:** Motor reactivo central del temporizador de concentración y descansos.
- **`useFloatingTimer.js`:** Administra el ciclo de vida de la ventana flotante *Always-on-Top* mediante la API de Document Picture-in-Picture.
- **`useZenMixer.js`:** Controla la reproducción multicanal concurrente de sonidos ambientales con Howler.js.
- **`usePomodoroAudioAlerts.js`:** Ejecuta campanas y alertas sonoras de transición de ciclo y emite notificaciones push nativas.

---

## 4. Estándar de Nomenclatura del Proyecto

* **Directorios de Dominio:** En minúsculas, kebab-case y en singular en español (`apunte/`, `sala-estudio/`, `perfil-compartido/`).
* **Componentes Vue (.vue):** En `PascalCase` con nombres representativos (`FloatingTimer.vue`, `TranscriptionModal.vue`, `SubtaskBreakdownModal.vue`, `SpotifyPlayer.vue`).
* **Clases Backend (PHP):** PSR-12 y `PascalCase` (`SalaEstudioController.php`, `AudioTranscriptionService.php`, `Subtarea.php`).
