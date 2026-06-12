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
3. **Clave de la API de Gemini (IA)**: El archivo `.env.example` ya incluye una clave de desarrollo compartida (`GEMINI_API_KEY`) para que todo el equipo pueda probar las funciones de Inteligencia Artificial inmediatamente sin configuraciones manuales. Al copiar el archivo en el paso 1, esta clave ya estará en tu entorno local.

4. Si deseas probar el **inicio de sesión con Google**, debes configurar tus credenciales en el archivo `.env`. Sigue estos pasos para obtenerlas en **Google Cloud Console**:
    - **Paso A. Crear/Seleccionar Proyecto:**
      1. Ingresa a [Google Cloud Console](https://console.cloud.google.com/).
      2. Inicia sesión con tu cuenta de Google.
      3. En la barra superior, haz clic en el selector de proyectos y luego en **"Proyecto nuevo"** (New Project). Dale un nombre al proyecto (ej. `Cronos Notes`) y haz clic en **"Crear"** (Create).
    - **Paso B. Configurar Pantalla de Consentimiento OAuth:**
      1. Abre el menú de navegación izquierdo (icono de tres líneas) y ve a **APIs y servicios** (APIs & Services) > **Pantalla de consentimiento de OAuth** (OAuth consent screen).
      2. Selecciona **"Externo"** (External) como tipo de usuario y haz clic en **"Crear"** (Create).
      3. Completa los campos obligatorios: **Nombre de la aplicación** (ej. `Cronos Notes`), **Correo electrónico de asistencia al usuario**, e **Información de contacto del desarrollador**. Haz clic en **"Guardar y continuar"**.
      4. En la pestaña **Permisos** (Scopes), haz clic en **"Agregar o quitar permisos"**, marca los alcances `/auth/userinfo.email` y `/auth/userinfo.profile`, haz clic en **"Actualizar"** y luego en **"Guardar y continuar"**.
      5. En la pestaña **Usuarios de prueba** (Test users), haz clic en **"Agregar usuarios"** e ingresa los correos de Google con los que vas a realizar las pruebas de desarrollo. Guarda y continúa hasta volver al tablero.
    - **Paso C. Crear Credenciales de Acceso:**
      1. En el menú izquierdo, ve a **APIs y servicios** (APIs & Services) > **Credenciales** (Credentials).
      2. Haz clic en el botón superior **"Crear credenciales"** (Create Credentials) y selecciona **"ID de cliente de OAuth"** (OAuth client ID).
      3. En **Tipo de aplicación**, elige **"Aplicación web"** (Web application).
      4. En **Orígenes de JavaScript autorizados**, haz clic en **"Agregar URI"** e ingresa la URL local de tu servidor (ej. `http://localhost:8000` o `http://127.0.0.1:8000`).
      5. En **URIs de redireccionamiento autorizados**, haz clic en **"Agregar URI"** e ingresa exactamente la ruta de callback: `http://localhost:8000/auth/google/callback` (o `http://127.0.0.1:8000/auth/google/callback` según corresponda).
      6. Haz clic en **"Crear"** (Create).
      7. Copia el **ID de cliente** (Client ID) y el **Secreto de cliente** (Client Secret) del cuadro de diálogo emergente y pégalos en tu archivo `.env`:
         ```env
         GOOGLE_CLIENT_ID=copia_el_id_de_cliente_aqui
         GOOGLE_CLIENT_SECRET=copia_el_secreto_de_cliente_aqui
         GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
         ```

4. Si deseas probar el **envío de correos reales** (como la recuperación de contraseñas o invitaciones a perfiles), debes configurar tus credenciales SMTP de **Gmail**. Sigue estos pasos:
    - **Paso A. Generar Contraseña de Aplicación en Google:**
      1. Ve a los ajustes de la cuenta de Google que deseas usar para enviar correos.
      2. Dirígete a la pestaña **Seguridad** y asegúrate de tener activada la **Verificación en 2 pasos**.
      3. En la misma sección, busca **Contraseñas de aplicaciones** (App Passwords) y crea una nueva (ej. nombrada "Cronos Notes").
      4. Google te generará una contraseña de 16 caracteres. Cópiala; no volverás a verla. **Asegúrate de quitarle los espacios o ponerla entre comillas** al pegarla en el `.env`.
    - **Paso B. Configurar el .env:**
      1. Ve a tu archivo `.env` y busca la sección `MAIL_`. Reemplaza los valores para usar el servidor SMTP de Gmail:
         ```env
         MAIL_MAILER=smtp
         MAIL_HOST=smtp.gmail.com
         MAIL_PORT=465
         MAIL_USERNAME=tu_correo_elegido@gmail.com
         MAIL_PASSWORD="las_16_letras_sin_espacios"
         MAIL_ENCRYPTION=smtps
         MAIL_FROM_ADDRESS=tu_correo_elegido@gmail.com
         MAIL_FROM_NAME="${APP_NAME}"
         ```
      2. Si tu sistema de colas está configurado como `QUEUE_CONNECTION=database`, recuerda que los correos se encolarán y no saldrán hasta que ejecutes en una terminal: `php artisan queue:work`. (Alternativamente puedes usar `QUEUE_CONNECTION=sync` para pruebas instantáneas sin colas).



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

## Guía de Pruebas de Flujos de Autenticación

Para verificar el correcto funcionamiento de la recuperación de contraseñas y la autenticación con Google, sigue los pasos detallados a continuación:

### 1. Prueba de Recuperación de Contraseña

Este flujo simula el caso en que un usuario olvida sus credenciales y solicita un enlace para restablecer su contraseña mediante correo electrónico.

#### Requisitos Previos:
- Tener configuradas las credenciales de **Gmail SMTP** en el archivo `.env` (Paso 4 de la instalación).
- Tener un usuario registrado previamente en la base de datos (puedes registrarte desde la pestaña `/register`).
- Tener abierta la bandeja de entrada del correo destino para visualizar la notificación.

#### Paso a Paso:
1. **Acceder a la pantalla de login:** Abre tu navegador e ingresa a la URL local del proyecto: [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login).
2. **Iniciar la solicitud:** En la tarjeta de login, haz clic en el enlace **"¿Olvidaste tu contraseña?"** (ubicado debajo del campo de password).
3. **Ingresar el correo:** Serás redirigido a la pestaña de solicitud (`/forgot-password`). Introduce el email del usuario registrado que deseas recuperar y haz clic en el botón **"Email Password Reset Link"**.
4. **Verificar el envío:** Verás un mensaje en pantalla indicando que se ha enviado el enlace de restablecimiento (si el correo existe en la base de datos).
5. **Revisar Bandeja de Entrada:** Ve a la bandeja de entrada del correo que intentas recuperar y busca el correo nuevo con el asunto *"Reset Password Notification"* enviado por Cronos Notes.
6. **Restablecer contraseña:** Abre el correo y haz clic en el botón **"Reset Password"**. Esto abrirá una nueva pestaña en tu navegador apuntando al formulario de tu aplicación (`/reset-password/{token}`).
7. **Ingresar las nuevas credenciales:**
   - El campo **Email** estará precargado con tu dirección y deshabilitado para evitar modificaciones.
   - En el campo **Password**, escribe la nueva contraseña.
   - En el campo **Confirm Password**, repite la nueva contraseña.
   - Haz clic en el botón **"Reset Password"** (o en el botón de confirmación).
8. **Validar el cambio:** Tras procesar el cambio, el sistema te redirigirá automáticamente a la pantalla de **Iniciar Sesión** (`/login`) con un mensaje verde confirmando que la contraseña ha sido restablecida. Introduce tu correo y tu nueva contraseña para comprobar que inicias sesión correctamente.

---

### 2. Prueba de Autenticación con Google (Google Sign-In / Register)

Este flujo permite tanto a usuarios nuevos registrarse al instante como a usuarios existentes iniciar sesión utilizando su cuenta de Google de forma segura.

#### Requisitos Previos:
- Tener configuradas las credenciales de **Google Client ID & Secret** en el archivo `.env` (Paso 4 de la instalación).
- Asegurarte de tener una cuenta de Google activa e iniciada en tu navegador (o recordar tus credenciales de Google).

#### Paso a Paso:
1. **Acceder al inicio de sesión o registro:** Abre tu navegador y dirígete a [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login) o [http://127.0.0.1:8000/register](http://127.0.0.1:8000/register).
2. **Iniciar flujo con Google:** Haz clic en el botón **"Continuar con Google"** (identificado con el icono oficial de Google en la parte inferior de la tarjeta).
3. **Seleccionar cuenta en Google:** Serás redirigido automáticamente a la pestaña oficial de autenticación de Google (`accounts.google.com`).
   - El sistema está configurado para forzar la selección de cuenta (`select_account`), por lo que verás la lista de tus cuentas de Google disponibles en el navegador. Haz clic sobre la cuenta con la que deseas probar.
4. **Procesamiento de datos en el retorno (Callback):**
   - **Caso 1 (Usuario Nuevo):** Si es la primera vez que ingresas a la plataforma con ese correo de Google, el sistema creará un registro de usuario automáticamente en la base de datos de MySQL utilizando el nombre, apellido e email provistos por Google. Además, registrará la integración en la tabla de integraciones externas (`IntegracionExterna`) asociando tu cuenta con `GoogleAuth`.
   - **Caso 2 (Usuario Existente):** Si ya tenías una cuenta con el mismo correo, el sistema vinculará la integración externa y procederá al logueo de forma directa.
5. **Redirección al Dashboard:** Una vez que Google verifique la cuenta y retorne al callback, serás redirigido automáticamente a la pestaña del **Dashboard** (`http://127.0.0.1:8000/dashboard`), habiendo iniciado sesión de forma exitosa y segura.

---

## Autores

Proyecto desarrollado por:
- Ayala, José Andrés
- Dellagnolo, Ricardo Agustín
- Fornari, Nahir Agustín
- Romea Acevedo, Clara Agustina

---
2026 - Ingeniería de Software II & Programación en Ambientes Web.
