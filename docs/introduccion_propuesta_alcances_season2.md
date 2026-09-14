# Cronos Notes (Season 2) — Marco Introductorio, Propuesta, Alcances y Límites

---

## 1. Introducción

El presente proyecto se desarrolla como una iniciativa intercátedra para la Licenciatura en Sistemas de Información (Universidad de la Cuenca del Plata). El propósito central es la **continuación, evolución y escalamiento de Cronos Notes**, una plataforma web concebida y desarrollada inicialmente a principios de año por el mismo equipo de trabajo, orientada a combatir la fragmentación tecnológica y la procrastinación en entornos académicos y profesionales.

En su primera etapa (Season 1), el proyecto sentó las bases fundamentales del sistema: la implementación de la técnica Pomodoro, la gestión de tareas priorizadas por perfiles de trabajo aislados, un editor de apuntes con soporte para el Método Cornell y grabación de audio, un sistema de gamificación basado en rachas diarias estrictas y métricas visuales de concentración.

En esta **segunda etapa de escalado (Season 2)**, el equipo retoma el desarrollo para transformar la herramienta en un ecosistema de alta productividad y colaboración sincrónica. Esta actualización incorpora modelos de **Inteligencia Artificial Multimodal** para la transcripción y síntesis de clases y el desglose asistido de tareas complejas, integración profunda con plataformas de uso diario (**Google Calendar**, **Spotify** y **Google Meet**), un motor de automatización desacoplado mediante **N8N contenerizado en Docker**, y herramientas ergonómicas de vanguardia como el **Mini-Timer Flotante (Document Picture-in-Picture)** y **alertas sonoras personalizables**.

---

## 2. Contexto, Necesidad y Problemática

### 2.1 Contexto Actual
En los últimos años, la educación superior y el trabajo profesional han consolidado modelos híbridos y a distancia. Si bien esta flexibilidad aporta autonomía, ha generado una sobrecarga de estímulos digitales, multitarea desorganizada y una dispersión constante entre múltiples herramientas aisladas (reproductores de música, calendarios, editores de notas, plataformas de videollamada y gestores de tareas).

### 2.2 Necesidad y Problemática Identificada
Tras validar el uso del sistema inicial durante la primera mitad del año, se identificaron necesidades críticas no resueltas:
1. **Sobrecarga en la Toma de Notas de Audio:** Los usuarios graban explicaciones de clase extensas, pero desgravarlas y extraer los conceptos clave de forma manual insume horas de trabajo que suelen postergarse.
2. **Parálisis por Tareas Complejas:** Los estudiantes frecuentemente postergan proyectos de gran envergadura debido a la dificultad de estructurar un plan de acción dividido en pasos manejables y cuantificables en tiempo.
3. **Aislamiento en el Estudio a Distancia:** La falta de espacios de trabajo compartido o dinámicas de concentración grupal (*Body Doubling*) reduce el compromiso y la motivación frente a sesiones de estudio solitarias.
4. **Fricción por Cambio de Contexto:** La necesidad de alternar constantemente entre la aplicación de estudio, Google Calendar para agendar entregas, Spotify para controlar música ambiental y Google Meet para reuniones genera micro-interrupciones que rompen el estado de flujo cognitivo (*flow*).
5. **Pérdida de Visibilidad del Tiempo:** Al trabajar en un procesador de texto, IDE o lector de PDF externo, el temporizador Pomodoro queda oculto detrás de otras ventanas, obligando al usuario a cambiar de pestaña solo para verificar el tiempo restante.

---

## 3. Propuesta de Desarrollo (Season 2)

La propuesta consiste en evolucionar **Cronos Notes** hacia una plataforma integral de alto rendimiento que no solo registre actividades, sino que **acompañe activamente el proceso cognitivo del usuario**.

El sistema amplía su arquitectura alrededor de **siete pilares fundamentales**:

1. **Enfoque Pomodoro, Mini-Timer Flotante y Alertas Auditivas:** Temporizador adaptable con soporte para ventanas flotantes *Always-on-Top* (Picture-in-Picture) y notificaciones sonoras configurables para transiciones de ciclo.
2. **Gestión Inteligente de Tareas & Desglose con IA:** Organización por perfiles con ordenación automática por criticidad y descomposición asistida de tareas complejas en subtareas jerárquicas mediante Gemini API.
3. **Apuntes Cornell con Transcripción y Resumen IA:** Editor estructurado que procesa notas de voz y clases grabadas mediante Speech-to-Text (Whisper / Gemini Multimodal) y genera automáticamente ideas clave, notas y resúmenes de estudio.
4. **Salas de Estudio Virtuales en Tiempo Real:** Espacios colaborativos sincrónicos vinculados a Google Meet API con temporizadores Pomodoro grupales sincronizados para estudio en equipo.
5. **Entorno Inmersivo y Multimedia:** Modo Zen minimalista con mezclador multicanal de sonidos ambientales (Howler.js) y reproductor embebido de Spotify (Web Playback SDK).
6. **Interoperabilidad con Google Calendar:** Sincronización bidireccional de tareas, fechas límites y sesiones de concentración.
7. **Automatización de Procesos con N8N (Docker):** Orquestación desacoplada de recordatorios multicanal (Telegram, Discord, Email), backups y reportes semanales de productividad.

---

## 4. Alcances del Sistema (Season 2)

El alcance de esta segunda etapa de desarrollo comprende los siguientes módulos y requerimientos técnicos:

* **[RF-M14] Transcripción Automática y Resumen Inteligente de Audios:**
  * Conversión automática de voz a texto (Speech-to-Text) a partir de grabaciones de micrófono o archivos subidos utilizando OpenAI Whisper / Gemini Multimodal Audio.
  * Extracción automática de conceptos clave, generación de resúmenes y población directa en las columnas del Método Cornell.
* **[RF-M15] Sincronización Completa con Google Calendar y Spotify:**
  * Sincronización bidireccional de eventos y tareas con Google Calendar (OAuth 2.0).
  * Reproductor embebido dentro de la Sesión Zen con control de reproducción y catálogo de playlists de estudio (Spotify Web Playback SDK).
* **[RF-M16] Desglose Automático de Tareas con IA (Subtareas Inteligentes):**
  * Asistente impulsado por Gemini API que divide tareas complejas en una lista editable de subtareas secuenciales con estimación de esfuerzo en Pomodoros.
* **[RF-M17] Salas de Estudio Virtuales en Tiempo Real con Google Meet:**
  * Generación dinámica de reuniones de Google Meet y sincronización en tiempo real del reloj Pomodoro para sesiones grupales.
* **[RF-M18] Automatización y Orquestación de Flujos con N8N en Docker:**
  * Despliegue contenerizado de N8N para procesar webhooks de Laravel, emitir alertas por Telegram/Email y generar reportes semanales.
* **[RF-M19] Mini-Timer Flotante (Document Picture-in-Picture):**
  * Desacoplamiento del temporizador en una mini-ventana flotante Always-on-Top con controles interactivos (Play, Pausa, Siguiente).
* **[RF-M20] Sistema de Notificaciones Auditivas y Alertas de Ciclo:**
  * Biblioteca de sonidos (Cuenco Tibetano, Campana Clásica, Chime Digital) y notificaciones nativas push del navegador para transiciones de concentración y descanso.
* **Mantenimiento y Consolidación de Funcionalidades Previas:**
  * Modo Zen, Inicio Rápido, Perfiles Compartidos con Roles Jerárquicos (Lector, Editor, Admin), Sistema de Rachas de 25 min y Estadísticas de Concentración Diaria.

---

## 5. Límites del Sistema (Season 2)

Para acotar el desarrollo al marco temporal y técnico de esta iteración, se establecen los siguientes límites:

1. **Pasarela de Pagos y Monetización Real:** El sistema no integrará pasarelas de cobro bancario real (como Stripe o Mercado Pago). Las funcionalidades exclusivas para perfiles avanzados se gestionarán mediante roles del sistema sin transacciones monetarias reales.
2. **Gamificación Competitiva y Ranking Global Público:** No se incluirá un ranking público competitivo entre usuarios globales, preservando un enfoque de motivación personal no invasivo.
3. **Aplicación Móvil Nativa en Tiendas:** Cronos Notes se distribuye como una aplicación web responsive de alto rendimiento (PWA); no se desarrollarán versiones binarias nativas empaquetadas para Google Play Store o Apple App Store.
4. **Traducción Simultánea Multilingüe de Audio:** El módulo de transcripción y resumen operará enfocado en el idioma español, sin procesamiento de traducción en tiempo real a otros idiomas.

---

## 6. Objetivos

### 6.1 Objetivo General
Escalar y consolidar la plataforma web **Cronos Notes**, integrando capacidades de Inteligencia Artificial generativa y multimodal, interoperabilidad con servicios externos (Google Meet, Google Calendar, Spotify), orquestación de flujos mediante N8N en Docker y utilidades ergonómicas de concentración en tiempo real, con el fin de optimizar la productividad personal y el estudio colaborativo.

### 6.2 Objetivos Específicos
1. Integrar modelos de Inteligencia Artificial (OpenAI Whisper y Google Gemini) para la transcripción automática de grabaciones y la síntesis estructurada bajo el Método Cornell.
2. Implementar un asistente de IA capaz de descomponer tareas complejas en subtareas jerárquicas cuantificadas en unidades Pomodoro.
3. Desarrollar la sincronización bidireccional con Google Calendar y el control de reproducción musical con Spotify Web API en el entorno Pomodoro.
4. Diseñar e implementar el módulo de Salas de Estudio Virtuales con generación dinámica de sesiones de Google Meet y temporizador grupal sincronizado.
5. Desplegar un contenedor Docker con N8N para la orquestación de webhooks, recordatorios externos multicanal y generación de reportes semanales.
6. Desarrollar el Mini-Timer Flotante aprovechando la API de Document Picture-in-Picture para permitir el seguimiento de sesiones fuera de la ventana principal.
7. Implementar un sistema de notificaciones auditivas multicanal y alertas push nativas para los cambios de fase del Pomodoro.
8. Mantener y validar la arquitectura bajo estándares de calidad de software ISO/IEC 25010 mediante pruebas continuas.

---

## 7. Metodología de Desarrollo y Distribución del Equipo

El proyecto continúa bajo la metodología ágil **Extreme Programming (XP)**, aplicando programación en parejas mixtas (Fullstack / Frontend + Backend) para garantizar la calidad y el traspaso continuo de conocimiento:

* **Pareja 1 (Grupo The Dinamit):** Dellagnolo, Ricardo Agustín & Romea Acevedo, Clara Agustina
  * *Módulos:* Transcripción y Resumen Inteligente de Audios (RF-M14), Sincronización con Google Calendar y Spotify (RF-M15), Desglose Automático de Tareas con IA (RF-M16).
* **Pareja 2 (Grupo The Bomb):** Ayala, José Andrés & Fornari, Nahir Agustín
  * *Módulos:* Salas de Estudio Virtuales con Google Meet (RF-M17), Orquestación de Flujos con N8N en Docker (RF-M18), Mini-Timer Flotante (RF-M19), Sistema de Notificaciones Auditivas (RF-M20).
