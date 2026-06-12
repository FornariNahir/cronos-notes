# Estructura del Proyecto y Arquitectura

Este documento describe la organización de carpetas del proyecto **Cronos Notes** y detalla las decisiones arquitectónicas que justifican esta estructura.

---

## 1. Patrón Arquitectónico: Monolito Híbrido

Cronos Notes está construido bajo el patrón de **Monolito Híbrido** utilizando **Inertia.js** como puente entre **Laravel (Backend)** y **Vue 3 (Frontend)**. 

### ¿Por qué esta arquitectura?
En el desarrollo web moderno, es común separar completamente el Frontend (SPA independiente) del Backend (API REST). Sin embargo, para Cronos Notes se decidió por un monolito híbrido debido a las siguientes ventajas:

1. **Desarrollo Ágil y Programación en Parejas (XP):** Al no tener que sincronizar dos repositorios distintos, el flujo de trabajo es mucho más rápido. Las rutas se definen únicamente en el backend (`routes/web.php`).
2. **Seguridad Nativa Simplificada:** La autenticación se maneja mediante cookies y sesiones nativas de Laravel. Esto elimina la necesidad de tokens JWT (que son más propensos a ataques XSS si se guardan en `localStorage`) y provee protección automática contra ataques CSRF.
3. **Paso de Datos Transparente:** Inertia.js permite inyectar datos del backend al componente Vue directamente como `props` desde el controlador, evitando tener que realizar peticiones AJAX manuales (`fetch` o `axios`) para la carga inicial de las pantallas.

---

## 2. Mapa del Directorio Principal

A continuación se detallan las carpetas clave del proyecto y sus responsabilidades:

```
Cronos-Notes/
├── app/                      # Capa Lógica del Backend (PHP)
│   ├── Http/                 # Controladores, Middleware y Requests HTTP
│   ├── Models/               # Modelos de Datos (Eloquent ORM)
│   ├── Services/             # Servicios de Lógica de Negocio (Integraciones, IA)
│   └── Providers/            # Service Providers de Laravel
├── bootstrap/                # Configuración de inicio del Framework
├── config/                   # Archivos de configuración de Laravel
├── database/                 # Persistencia y Esquemas
│   ├── migrations/           # Definición de tablas de la Base de Datos
│   └── seeders/              # Datos de prueba para desarrollo
├── docs/                     # Documentación técnica del sistema
├── public/                   # Recursos estáticos compilados y de acceso público
├── resources/                # Capa Frontend y Vistas
│   ├── js/                   # Código de la aplicación Vue.js
│   │   ├── Components/       # Componentes Vue reutilizables (Botones, Modals, etc.)
│   │   ├── Composables/      # Lógica reactiva reutilizable de Vue (Composition API)
│   │   ├── Layouts/          # Estructuras de página comunes (Sidebar, Zen view, etc.)
│   │   └── Pages/            # Pantallas/Vistas completas renderizadas por Inertia
│   └── css/                  # Estilos globales y configuración de Tailwind CSS
├── routes/                   # Rutas de la Aplicación
│   └── web.php               # Rutas web del sistema (manejadas por Inertia)
├── tests/                    # Pruebas automatizadas (PHPUnit)
└── vite.config.js            # Configuración del empaquetador Vite
```

---

## 3. Justificación de los Directorios Específicos

### Capa Backend: [app/](file:///C:/Users/della/Cronos-Notes/app/)
- **[app/Http/Controllers/](file:///C:/Users/della/Cronos-Notes/app/Http/Controllers/):** Actúan como orquestadores. Su única responsabilidad es recibir la petición del frontend, llamar a los servicios correspondientes para procesar la información y retornar una respuesta Inertia.
- **[app/Services/](file:///C:/Users/della/Cronos-Notes/app/Services/):** Para evitar sobrecargar a los controladores o modelos con lógica de negocio compleja, se crearon clases de servicio dedicadas (ej. llamadas a la API de Gemini o integraciones externas). Esto permite reutilizar lógica y facilita las pruebas unitarias aisladas.
- **[app/Models/](file:///C:/Users/della/Cronos-Notes/app/Models/):** Representa nuestro modelo de dominio. Utiliza Eloquent ORM para que la interacción con la base de datos sea mediante objetos, abstrayendo las consultas SQL crudas.

### Capa Frontend: [resources/js/](file:///C:/Users/della/Cronos-Notes/resources/js/)
- **[resources/js/Pages/](file:///C:/Users/della/Cronos-Notes/resources/js/Pages/):** Contiene las vistas completas (ej. Dashboard, Calendario, Apuntes). Cada archivo aquí mapea 1-a-1 con un retorno del controlador Laravel mediante `Inertia::render('Pages/NombrePagina')`.
- **[resources/js/Components/](file:///C:/Users/della/Cronos-Notes/resources/js/Components/):** Sigue principios de diseño atómico. Componentes pequeños y reutilizables en múltiples páginas para asegurar la consistencia visual y reducir la duplicación de código.
- **[resources/js/Layouts/](file:///C:/Users/della/Cronos-Notes/resources/js/Layouts/):** Define la estructura exterior de la aplicación (ej. la barra lateral de navegación y la racha del usuario). Las páginas se inyectan dentro de estos layouts, lo que evita renderizar la barra lateral individualmente en cada vista.
- **[resources/js/Composables/](file:///C:/Users/della/Cronos-Notes/resources/js/Composables/):** Abstrae la lógica reactiva del frontend (ej. el conteo del temporizador Pomodoro o el control del reproductor de sonido) para que pueda ser importado en cualquier componente que lo requiera.

### Base de Datos y Configuración
- **[database/migrations/](file:///C:/Users/della/Cronos-Notes/database/migrations/):** Control de versiones para nuestra base de datos. En lugar de compartir archivos SQL crudos que se desincronizan rápidamente entre desarrolladores, las migraciones permiten que cualquiera configure la estructura de la base de datos local ejecutando `php artisan migrate`.
- **`vite.config.js` y Tailwind:** Se utiliza **Vite** para compilar los recursos de Vue de forma extremadamente veloz en desarrollo gracias a su Hot Module Replacement (HMR). **Tailwind CSS** permite escribir estilos directamente sobre el HTML/Vue, eliminando la necesidad de mantener hojas de estilo gigantes y asegurando que el diseño sea consistente a nivel de componentes.
