# 🌿 Guía de Flujo de Trabajo con Ramas y Colaboración en Equipo

Esta guía define el estándar de desarrollo y trabajo en equipo para **Cronos Notes**.  
El equipo está compuesto por cuatro integrantes organizados en **dos parejas de desarrollo**.

---

## 👥 1. Organización del Equipo y Responsabilidades

- El desarrollo se divide por requerimientos o módulos funcionales independientes (ver especificaciones en `docs/`).
- Cada pareja es responsable de un requerimiento/módulo a la vez.
- **Regla de oro:** Nadie sube código directamente a la rama `main`. Todo cambio entra mediante **ramas de características (*feature branches*)** y **Pull Requests (PR)**.

---

## 🌳 2. Estrategia de Ramas (GitHub Flow)

```text
main (Rama principal protegida - siempre funcional y estable)
 │
 ├── feature/rf-03-gestion-tareas       ← (Pareja A trabajando en Tareas)
 │
 └── feature/rf-06-estadisticas          ← (Pareja B trabajando en Estadísticas)
```

### Nomenclatura de Ramas
- **Funcionalidades nuevas:** `feature/<rf-numero-nombre>` (ej: `feature/rf-03-gestion-tareas`, `feature/rf-06-estadisticas`).
- **Corrección de errores:** `fix/<descripcion-corta>` (ej: `fix/temporizador-pomodoro-pausa`).
- **Mejoras o refactorización:** `refactor/<descripcion>` (ej: `refactor/controlador-auth`).

---

## 🚀 3. Flujo de Trabajo Paso a Paso

### Paso 1: Actualizar `main` y crear la rama
Antes de empezar una nueva funcionalidad, asegúrate de tener la última versión de `main`:

```bash
# 1. Posicionarse en main
git checkout main

# 2. Descargar los últimos cambios
git pull origin main

# 3. Crear y cambiar a la nueva rama
git checkout -b feature/rf-03-gestion-tareas
```

---

### Paso 2: Publicar la rama en GitHub
Para que tu compañero de pareja y el resto del equipo puedan ver la rama:

```bash
git push -u origin feature/rf-03-gestion-tareas
```

El compañero de pareja puede luego bajarse la rama con:
```bash
git fetch origin
git checkout feature/rf-03-gestion-tareas
```

---

### Paso 3: Desarrollo en Pareja y Commits Frecuentes

#### Dinámica dentro de la pareja:
1. **Pair Programming (Recomendado):** Trabajan juntos en la misma sesión (Meet, Discord o VS Code Live Share).
2. **División de tareas:** Uno se enfoca en Backend (migraciones, modelos, controladores) y otro en Frontend (componentes Vue, estilos Tailwind).

#### Regla de sincronización diaria:
Antes de empezar a codear cada día o antes de subir cambios, haz pull de tu rama para traer lo que tu compañero haya subido:

```bash
git pull origin feature/rf-03-gestion-tareas
```

#### Buenas prácticas de Commits:
Haz commits pequeños, frecuentes y descriptivos (siguiendo Conventional Commits si es posible):

```bash
git add .
git commit -m "feat(tareas): agregar migracion y modelo de categorias"
git push origin feature/rf-03-gestion-tareas
```

*Prefijos útiles:*
- `feat:` para nuevas funcionalidades.
- `fix:` para arreglos de bugs.
- `docs:` para cambios en documentación.
- `style:` para formateo visual o CSS.
- `refactor:` para mejoras de código sin cambiar funcionalidad.
- `test:` para pruebas automatizadas.

---

### Paso 4: Mantener la rama actualizada con `main`

Si la otra pareja ya integró cambios a `main` mientras ustedes siguen trabajando, traigan esos cambios a su rama para evitar conflictos acumulados:

```bash
# Estando en su rama feature:
git pull origin main
```
*Si surgen conflictos simples, se resuelven en el editor, se guardan y se hace commit.*

---

### Paso 5: Crear el Pull Request (PR) y Revisión Cruzada

Cuando la pareja considere que el requerimiento está terminado y probado:

1. **Subir los últimos cambios:**
   ```bash
   git push origin feature/rf-03-gestion-tareas
   ```
2. **Abrir el Pull Request en GitHub:**
   - Ve a la pestaña **Pull Requests** en el repositorio de GitHub.
   - Haz clic en **New Pull Request**.
   - Selecciona `base: main` ← `compare: feature/rf-03-gestion-tareas`.
   - Describe brevemente qué incluye el PR (módulo, cambios en BD, pantallas agregadas).
3. **Revisión de Código (*Code Review*):**
   - La otra pareja (o al menos un compañero) revisa los cambios en GitHub.
   - Se pueden usar agentes de IA con el comando `/code-review` para validar estándares y requerimientos.
4. **Merge a `main`:**
   - Una vez aprobado y verificado que no rompe la build ni los tests, se hace clic en **Merge Pull Request**.
5. **Eliminar la rama:**
   - GitHub ofrece un botón para borrar la rama remota tras el merge. En local pueden borrarla con `git branch -d feature/rf-03-gestion-tareas`.

---

## 🤖 4. Integración con Agentes de IA y `.scratch/`

El proyecto utiliza un **Issue Tracker Local** basado en Markdown dentro de la carpeta `.scratch/`.

### ¿Cómo lo usa cada pareja?
- Cada pareja trabaja en su propia subcarpeta:
  - Pareja A: `.scratch/rf-03-gestion-tareas/`
  - Pareja B: `.scratch/rf-06-estadisticas/`
- En esa carpeta viven:
  - `spec.md` (especificación del requerimiento o funcionalidad).
  - `issues/01-nombre-tarea.md`, `issues/02-nombre-tarea.md` (tickets individuales).

> Al estar aisladas en carpetas distintas por requerimiento, **Git nunca producirá conflictos** al trabajar las parejas en paralelo con sus agentes.

---

## 🛡️ 5. Resumen de Buenas Prácticas

1. **Nunca commitear directo en `main`:** Todo pasa por una rama `feature/` y un PR.
2. **Hacer `git pull origin main` periódicamente** en tu rama para estar al día.
3. **Probar antes de pedir merge:** Asegurarse de que las migraciones (`php artisan migrate`) y la build de frontend (`pnpm build` o `pnpm dev`) funcionen correctamente sin errores.
4. **Comunicación abierta:** Si vas a modificar un archivo compartido crítico (como `routes/web.php`, `config/` o el layout principal), avísale al resto del equipo.
