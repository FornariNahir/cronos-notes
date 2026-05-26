# Cronos Notes

<p align="center">
    <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

## Sobre el Proyecto

**Cronos Notes** es una plataforma web integral diseñada para la gestión del tiempo y la mejora de la productividad. Desarrollada como un proyecto intercátedra para la Licenciatura en Sistemas de Información (Universidad de la Cuenca del Plata), busca combatir la procrastinación y el agotamiento mediante la integración de herramientas de estudio y trabajo en un solo lugar.

El sistema evoluciona el concepto de una agenda tradicional hacia una herramienta de alto rendimiento que combina la planificación de actividades con el control efectivo del tiempo.

---

## Pilares Fundamentales

- **Técnica Pomodoro:** Temporizador personalizable para gestionar ciclos de concentración profunda y descansos.
- **Gestión Inteligente de Tareas:** Clasificación por prioridades y organización mediante perfiles personalizados (Estudio, Trabajo, Personal, etc.).
- **Inteligencia Artificial:** Motor de IA para la priorización automática de tareas según fechas límite y relevancia.
- **Toma de Apuntes Estructurada:** Módulo de notas con soporte para el Método Cornell y grabación de audio.
- **Entorno Inmersivo:** Modo Zen minimalista con mezclador de sonidos ambientales e integración con Spotify y Google Calendar.

---

## Stack Tecnológico

- **Backend:** [Laravel](https://laravel.com) (PHP)
- **Frontend:** [Vue.js](https://vuejs.org) + [Inertia.js](https://inertiajs.com) + [Tailwind CSS](https://tailwindcss.com)
- **Base de Datos:** MySQL
- **Gestores de paquetes:** Composer (PHP) y PNPM (Node.js)

---

## Requisitos Previos

Antes de configurar el proyecto, necesitas tener instaladas las siguientes herramientas en tu computadora:

1. **Servidor Local de Base de Datos y PHP**:
   - Para Windows, se recomienda instalar [Laragon](https://laragon.org/) (recomendado por su velocidad y facilidad de uso) o [XAMPP](https://www.apachefriends.org/). Esto instalará automáticamente **PHP (8.2+)**, **MySQL** y el servidor web.
2. **Composer** (Gestor de dependencias de PHP):
   - Descárgalo e instálalo desde [getcomposer.org](https://getcomposer.org/doc/00-intro.md#installation-windows).
3. **Node.js** (Entorno de ejecución para JavaScript):
   - Descárgalo e instálalo desde [nodejs.org](https://nodejs.org/) (se recomienda la versión LTS).
4. **PNPM** (Gestor de paquetes rápido para JavaScript):
   - Puedes instalarlo abriendo la terminal y ejecutando `npm install -g pnpm`.
5. **Git** (Sistema de control de versiones):
   - Descárgalo desde [git-scm.com](https://git-scm.com/).

---

## Guía de Instalación Paso a Paso

Sigue estos pasos detallados para poner en marcha el proyecto desde cero:

### 1. Clonar el repositorio
Abre tu terminal (Git Bash, Command Prompt o PowerShell) y ejecuta:
```bash
git clone https://github.com/FornariNahir/Cronos-Notes.git
cd Cronos-Notes
```

### 2. Instalar dependencias de PHP (Backend)
En la carpeta del proyecto, ejecuta el siguiente comando para descargar las librerías necesarias del backend:
```bash
composer install
```

### 3. Instalar dependencias de Node.js (Frontend)
Instala las dependencias y librerías del lado del cliente:
```bash
pnpm install
```

### 4. Configurar el archivo de entorno (`.env`)
Laravel utiliza un archivo de configuración llamado `.env` para conectarse a tu base de datos y guardar claves secretas:
1. Copia el archivo `.env.example` y crea uno nuevo llamado `.env` en la raíz del proyecto.
   - *En Windows (CMD/PowerShell)*: `copy .env.example .env`
   - *En Git Bash / Linux / macOS*: `cp .env.example .env`
2. Abre el archivo `.env` en tu editor de código de preferencia y actualiza la sección de la base de datos con tus credenciales locales de MySQL:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=cronosnotes
   DB_USERNAME=root
   DB_PASSWORD=      # Coloca tu contraseña si usas XAMPP; déjalo vacío si usas Laragon
   ```

### 5. Crear la Base de Datos en MySQL
Antes de migrar, debes crear una base de datos vacía. Puedes hacerlo a través de phpMyAdmin, la consola de MySQL o el administrador de base de datos de tu servidor local (como HeidiSQL en Laragon):
- Crea una nueva base de datos llamada **`cronosnotes`** (o el nombre que hayas definido en la línea `DB_DATABASE` del `.env`).

### 6. Generar la clave única de la aplicación
Genera la clave de seguridad interna para la encriptación de sesiones de Laravel:
```bash
php artisan key:generate
```

### 7. Ejecutar las migraciones (Creación de tablas)
Crea la estructura de tablas de Cronos en tu base de datos recién creada:
```bash
php artisan migrate
```

### 8. Ejecutar el proyecto
Para ver el sitio web en funcionamiento, debes mantener corriendo dos procesos en tu terminal:

1. **Compilador del Frontend (Vite)**: Compila y refresca los cambios de JavaScript y Vue en tiempo real:
   ```bash
   pnpm run dev
   ```
2. **Servidor del Backend (Laravel)** (abre otra pestaña de la terminal en el mismo directorio):
   ```bash
   php artisan serve
   ```

¡Listo! Abre tu navegador e ingresa a la dirección que te proporcione el comando anterior (normalmente [http://127.0.0.1:8000](http://127.0.0.1:8000)).

---

## Autores

Proyecto desarrollado por:
- Ayala, José Andrés
- Dellagnolo, Ricardo Agustín
- Fornari, Nahir Agustín
- Romea Acevedo, Clara Agustina

---
2026 - Ingeniería de Software II & Programación en Ambientes Web.
