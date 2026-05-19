# Cronos Notes

<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

## Sobre el Proyecto

**Cronos Notes** es una plataforma web integral diseñada para la gestión del tiempo y la mejora de la productividad. Desarrollada como un proyecto intercátedra para la Licenciatura en Sistemas de Información (Universidad de la Cuenca del Plata), busca combatir la procrastinación y el agotamiento mediante la integración de herramientas de estudio y trabajo en un solo lugar.

El sistema evoluciona el concepto de una agenda tradicional hacia una herramienta de alto rendimiento que combina la planificación de actividades con el control efectivo del tiempo.

## Pilares Fundamentales

- **Técnica Pomodoro:** Temporizador personalizable para gestionar ciclos de concentración profunda y descansos.
- **Gestión Inteligente de Tareas:** Clasificación por prioridades y organización mediante perfiles personalizados (Estudio, Trabajo, Personal, etc.).
- **Inteligencia Artificial:** Motor de IA para la priorización automática de tareas según fechas límite y relevancia.
- **Toma de Apuntes Estructurada:** Módulo de notas con soporte para el Método Cornell y grabación de audio.
- **Entorno Inmersivo:** Modo Zen minimalista con mezclador de sonidos ambientales e integración con Spotify y Google Calendar.

## Stack Tecnológico

- **Backend:** [Laravel](https://laravel.com) (PHP)
- **Frontend:** [Vue.js](https://vuejs.org), [Tailwind CSS](https://tailwindcss.com) y JavaScript.
- **Base de Datos:** MySQL.
- **Metodología:** Extreme Programming (XP) y Agile UX.

## Instalación y Configuración

1. Clonar el repositorio.
2. Instalar dependencias de PHP:
   ```bash
   composer install
   ```
3. Instalar dependencias de Node.js:
   ```bash
   npm install
   ```
4. Configurar el archivo `.env` (usar `.env.example` como base).
5. Generar la clave de la aplicación:
   ```bash
   php artisan key:generate
   ```
6. Ejecutar las migraciones:
   ```bash
   php artisan migrate
   ```
7. Compilar los activos:
   ```bash
   pnpm run dev
   ```
8. Iniciar el servidor local:
   ```bash
   php artisan serve
   ```

## Autores

Proyecto desarrollado por:
- Ayala, José Andrés
- Dellagnolo, Ricardo Agustín
- Fornari, Nahir Agustín
- Romea Acevedo, Clara Agustina

---
2026 - Ingeniería de Software II & Programación en Ambientes Web.
