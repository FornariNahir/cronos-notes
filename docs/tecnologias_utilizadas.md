# Tecnologías y Dependencias del Sistema

Este documento recopila las tecnologías, frameworks y librerías que conforman el stack técnico de **Cronos Notes**, detallando su definición, propósito en el proyecto y su funcionamiento interno.

Las dependencias principales se encuentran registradas y gestionadas en los archivos [composer.json](/composer.json) (Backend) y [package.json](/package.json) (Frontend).

---

## 1. Stack Tecnológico Core

### PHP & Laravel Framework
* **¿Qué es?** Un framework de desarrollo web para PHP bajo el patrón de arquitectura MVC (Modelo-Vista-Controlador).
* **Uso en el Proyecto:** Actúa como nuestro motor de Backend. Se encarga de la seguridad, el enrutamiento web, la lógica de negocio pesada, la interacción con la base de datos (MySQL), el envío de notificaciones y la integración con la API de Inteligencia Artificial (Gemini).
* **Funcionamiento Clave:**
  * **Eloquent ORM (Object-Relational Mapping):** Es la herramienta que nos permite interactuar con la base de datos MySQL como si fueran objetos de PHP, sin escribir SQL a mano. Sigue el patrón *Active Record*, lo que significa que cada modelo (como `app/Models/Tarea.php` o `app/Models/Perfil.php`) representa una tabla de la base de datos, y cada instancia de ese modelo representa un registro físico.
  * **Laravel Socialite:** Librería oficial utilizada para el inicio de sesión con Google (OAuth 2.0). Simplifica la comunicación con los servidores de Google para autenticar al usuario y traer sus datos básicos de forma segura.

### Vue 3 (Frontend Framework)
* **¿Qué es?** Un framework progresivo de JavaScript utilizado para construir interfaces de usuario interactivas y dinámicas.
* **Uso en el Proyecto:** Define la interfaz visual de Cronos Notes. Gracias a su reactividad, permite que pantallas como el temporizador Pomodoro se actualicen segundo a segundo en tiempo real sin recargar la página.
* **Funcionamiento Clave:**
  * **Composition API:** Permite estructurar el código de las pantallas organizando la lógica por funciones reactivas (en lugar de opciones fijas), facilitando la creación de **Composables** reutilizables (ej. para el manejo del estado del audio o del temporizador).
  * **Sistema de Reactividad:** Vue utiliza un sistema basado en Proxies de JavaScript. Cuando el estado del temporizador cambia, Vue detecta automáticamente la modificación y repinta en el DOM únicamente el texto de los minutos y segundos, optimizando el rendimiento.

### Inertia.js (El Puente)
* **¿Qué es?** Un framework de embridado (glue framework) que permite conectar un backend monolítico (como Laravel) con un frontend de componentes SPA (como Vue) sin necesidad de crear una API REST compleja con enrutamiento duplicado.
* **Uso en el Proyecto:** Conecta nuestros controladores de Laravel directamente con las páginas Vue en [resources/js/Pages/](/resources/js/Pages/).
* **Funcionamiento Clave:** Intercepta todos los clics en enlaces dentro de la aplicación. En lugar de hacer una recarga completa del navegador, Inertia realiza una petición AJAX al servidor. El controlador de Laravel responde con un objeto JSON que contiene los datos (las `props`) y el nombre de la página Vue que debe cargarse. Inertia reemplaza dinámicamente la vista actual en el navegador, ofreciendo una experiencia SPA fluida.

### Tailwind CSS
* **¿Qué es?** Un framework CSS utilitario orientado al diseño directo en las etiquetas HTML/Vue.
* **Uso en el Proyecto:** Estilizado visual de toda la plataforma (diseño adaptativo, colores, bordes, Modo Zen y Modo Oscuro).
* **Funcionamiento Clave:** En lugar de escribir hojas de estilos separadas (`.css`), aplicamos clases preexistentes en los componentes de Vue (ej. `class="flex items-center justify-between p-4 bg-gray-800 text-white"`). Al momento de compilar para producción, el motor de Tailwind analiza los archivos y genera un archivo CSS compilado ultraligero que contiene estrictamente las clases que usamos.

---

## 2. Librerías Específicas y Utilidades

### Howler.js
* **¿Qué es?** Una librería de JavaScript para el control de audio en navegadores web que simplifica el uso de la API nativa *Web Audio API*.
* **Uso en el Proyecto:** Motor de audio del **Mezclador de Sonidos Ambientales** en el Modo Zen.
* **Funcionamiento Clave:** Permite cargar múltiples pistas de audio (lluvia, teclado, café, bosque) de forma asíncrona, reproducirlas en loop de forma concurrente (simultánea) y controlar el volumen de cada canal de forma independiente mediante deslizadores (*sliders*).

### Chart.js & Vue-Chartjs
* **¿Qué es?** Una librería de gráficos HTML5 basada en Canvas, junto con su envoltorio oficial para Vue.
* **Uso en el Proyecto:** Generación de gráficos interactivos en la pantalla de **Estadísticas de Usuario** (ej. horas de estudio diarias, pomodoros finalizados, tareas completadas por perfil).
* **Funcionamiento Clave:** Recibe los datos procesados desde [app/Http/Controllers/EstadisticaController.php](/app/Http/Controllers/EstadisticaController.php) como `props`, y dibuja en un elemento `<canvas>` del navegador gráficos de barra y dona que responden dinámicamente al pasar el mouse por encima.

### Ziggy (tightenco/ziggy)
* **¿Qué es?** Una librería que expone las rutas del backend de Laravel al frontend de JavaScript.
* **Uso en el Proyecto:** Permite llamar a la función helper de Laravel `route()` dentro de nuestros archivos `.vue`.
* **Funcionamiento Clave:** Ziggy genera un archivo de configuración dinámico en JS con la tabla de enrutamiento de Laravel. Esto nos permite escribir cosas como `route('tareas.store')` en el frontend, evitando hardcodear URLs manuales como `/tareas/crear`. Si mañana cambiamos la URL en Laravel, el frontend se actualiza automáticamente.

### Lucide Icons (lucide-vue-next)
* **¿Qué es?** Un set de iconos vectoriales SVG de código abierto optimizado para Vue.
* **Uso en el Proyecto:** Provee toda la iconografía visual (los iconos del reproductor de audio, los perfiles de estudio, configuración de pomodoros, etc.).
* **Funcionamiento Clave:** Se importan de forma modular únicamente los iconos necesarios (ej. `import { Play, Pause } from 'lucide-vue-next'`), reduciendo el tamaño final de la aplicación compilada.

---

## 3. Herramientas de Construcción y Base de Datos

### Vite
* **¿Qué es?** Un empaquetador de frontend de última generación enfocado en la velocidad de desarrollo.
* **Uso en el Proyecto:** Compila y procesa nuestro JavaScript, Vue, Tailwind CSS y recursos estáticos durante el desarrollo y la preparación para producción.
* **Funcionamiento Clave:** En desarrollo, no realiza una compilación completa de todo el código. En su lugar, sirve los archivos utilizando módulos nativos de ES del navegador (ESM) y actualiza instantáneamente en pantalla únicamente el archivo que editamos (*Hot Module Replacement* - HMR) sin necesidad de recargar el navegador.

### MySQL
* **¿Qué es?** Un sistema de gestión de bases de datos relacionales (RDBMS).
* **Uso en el Proyecto:** Motor de persistencia del sistema. Almacena usuarios, perfiles, tareas, sesiones pomodoro y configuraciones de personalización.
