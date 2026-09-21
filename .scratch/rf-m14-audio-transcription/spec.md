# Spec: [RF-M14] Transcripción Automática y Resumen Inteligente de Audios
Status: ready-for-agent

## Problem Statement

Los estudiantes que graban clases teóricas, debates o notas de voz experimentan una sobrecarga cognitiva severa al tener que escuchar y desgrabar manualmente horas de grabaciones para poder estudiar. Además, las transcripciones de texto plano sin procesar suelen ser extensas, desordenadas y poco pedagógicas. Los alumnos necesitan transformar sus audios en apuntes estructurados bajo metodologías de estudio comprobadas (como el Método Cornell) sin riesgo de perder o sobreescribir involuntariamente las notas que ya tenían redactadas en su editor, y sin que el entorno de desarrollo dependa exclusivamente de servicios de pago en la nube.

## Solution

Un pipeline desacoplado de procesamiento de audio en dos etapas, integrado en el módulo de apuntes:
1. **Speech-to-Text (STT)**: Conversión fonética automática del audio a texto con puntuación, utilizando un contenedor local de Whisper para procesamiento privado, ilimitado y gratuito en desarrollo, con conmutación por fallback transparente a Inteligencia Artificial multimodal en la nube (Gemini) en caso de indisponibilidad.
2. **Síntesis Estructurada Cornell**: Procesamiento analítico del texto transcrito mediante un modelo de lenguaje que genera una estructura en tres cuadrantes pedagógicos (*Ideas Clave / Preguntas*, *Notas de Clase*, *Resumen Integrador*) y sugiere un título contextual.
3. **Persistencia No Destructiva e Inserción Flexible**: Los resultados se almacenan en la entidad de audio para previsualización, permitiendo al estudiante decidir explícitamente si desea anexar el nuevo contenido o reemplazar el apunte existente.

## User Stories

1. Como estudiante con clases grabadas, quiero solicitar la transcripción automática de un audio cargado, para no tener que desgrabarlo manualmente palabra por palabra.
2. Como estudiante, quiero que el audio transcrito se organice automáticamente bajo el Método Cornell (Ideas Clave, Notas y Resumen), para poder repasar conceptos clave de forma estructurada antes de un examen.
3. Como estudiante, quiero que la IA me sugiera un título conciso basado en el tema del audio, para mantener mi biblioteca de apuntes identificada y ordenada.
4. Como estudiante que ya redactó notas a mano en su apunte, quiero que la transcripción no sobreescriba mis notas automáticamente, para evitar la pérdida accidental de mi trabajo previo.
5. Como estudiante, quiero tener una opción explícita para "anexar" las notas generadas por la IA al final de mi apunte existente, para enriquecer mi apunte actual sin borrar lo ya escrito.
6. Como estudiante, quiero tener una opción explícita para "reemplazar" el contenido de mi apunte por la estructura generada por la IA, para inicializar rápidamente una nota desde cero a partir de un audio.
7. Como estudiante, quiero visualizar el estado del procesamiento (`pendiente`, `procesando`, `completado`, `fallido`), para saber cuándo mi apunte enriquecido está listo.
8. Como estudiante, quiero recibir mensajes descriptivos comprensibles en caso de que ocurra un error de red o timeout durante la transcripción, para entender qué sucedió y poder reintentar.
9. Como usuario colaborador con permiso de "modificar" (rol Editor o Administrador) en un perfil compartido, quiero solicitar la transcripción y aplicar los resúmenes a los apuntes del grupo, para colaborar activamente en el material de estudio compartido.
10. Como propietario de un perfil compartido, quiero que los usuarios con rol "Lector" no puedan ejecutar peticiones de transcripción de audios, para prevenir modificaciones no autorizadas y consumos accidentales de recursos de procesamiento.
11. Como estudiante, quiero que las solicitudes de transcripción estén protegidas contra disparos repetidos accidentales mediante un límite de peticiones (rate limiting), para no saturar el servidor ni generar llamadas duplicadas si hago doble clic.
12. Como usuario no autenticado, quiero que cualquier intento de interactuar con los endpoints de transcripción sea bloqueado con un error de autorización, para proteger la privacidad de los audios y notas de estudio.
13. Como desarrollador trabajando en local, quiero que las peticiones de transcripción utilicen mi contenedor local de Whisper, para poder desarrollar y probar de forma ilimitada sin costo ni consumo de cuota de APIs de terceros.
14. Como evaluador o docente ejecutando la aplicación sin el contenedor local de Whisper encendido, quiero que el sistema conmute automáticamente al servicio multimodal en la nube, para que el sistema funcione de punta a punta sin configuraciones manuales complejas.
15. Como estudiante, quiero que tanto la transcripción textual completa como el resumen estructurado en JSON queden almacenados junto a la grabación de audio, para poder consultarlos en cualquier momento sin tener que volver a procesar el archivo.

## Implementation Decisions

- **Pipeline Desacoplado en Dos Fases**:
  Separación estricta entre la fase fonética de conversión de voz a texto (STT) y la fase analítica de razonamiento y síntesis (LLM). Whisper no realiza síntesis; la síntesis Cornell la ejecuta exclusivamente el modelo analítico.

- **Patrón Driver para Speech-to-Text**:
  El backend implementa un mecanismo conmutador configurable mediante variables de entorno (`TRANSCRIPTION_DRIVER`, `WHISPER_LOCAL_URL`, `WHISPER_FALLBACK_TO_GEMINI`):
  - Driver local: Transmite el binario de audio mediante HTTP `multipart/form-data` al microservicio local de Whisper.
  - Conmutación por Fallback: Si el microservicio local no responde, no está disponible o produce un timeout, y la variable de fallback está activa, se redirige la petición de audio a la API multimodal en la nube.

- **Contrato de Salida Estructurada para Cornell**:
  La fase de síntesis exige un esquema JSON estricto:
  ```json
  {
    "titulo_sugerido": "string",
    "ideas_clave": ["string"],
    "notas": "string (formato markdown estructurado)",
    "resumen": "string (síntesis de 3 a 5 oraciones)"
  }
  ```

- **Persistencia No Destructiva de Estados y Resultados**:
  La entidad que representa el audio del apunte almacena la transcripción de texto largo, el objeto estructurado de resumen en formato JSON nativo, el estado del ciclo de vida (`pendiente`, `procesando`, `completado`, `fallido`) y el eventual mensaje de error técnico. Las columnas del apunte principal permanecen intactas tras el procesamiento inicial.

- **Endpoint Atómico de Fusión/Aplicación Cornell**:
  Se expone una operación separada para aplicar el resultado al apunte, aceptando dos modalidades operativas:
  - `reemplazar`: Sobrescribe los cuadrantes de notas, ideas y resumen del apunte con los datos generados por la IA.
  - `anexar`: Preserva el contenido previo del apunte y concatena de manera ordenada las nuevas ideas y notas al final.

- **Control de Acceso y Rate Limiting**:
  Las operaciones requieren sesión activa autenticada y verificación de permisos del perfil activo. Solo los usuarios con capacidad de modificación pueden ejecutar la transcripción o aplicar los cambios. Se aplica una restricción de tasa de solicitudes (10 peticiones por minuto por usuario).

## Testing Decisions

- **Criterio de Calidad de Pruebas**:
  Las pruebas deben enfocarse exclusivamente en el comportamiento observable externamente (contratos de respuesta HTTP, códigos de estado, restricciones de autorización basadas en roles y mutaciones de estado en la base de datos), evitando acoplarse a detalles de implementación privada o nombres de métodos internos.

- **Módulos y Flujos Bajo Prueba**:
  - Endpoint de procesamiento de transcripción y generación Cornell.
  - Endpoint de aplicación al apunte en modos `reemplazar` y `anexar`.
  - Matriz de autorización: Bloqueo a usuarios no autenticados (401/redirect) y bloqueo a usuarios con rol `Lector` en perfiles compartidos (403).
  - Simulación de red y resiliencia: Emulación de respuestas exitosas del motor local, degradación elegante con fallback a la nube ante caídas de conexión local, y persistencia del estado `fallido` con código 503 cuando fallan ambos servicios.

- **Costura de Prueba (Test Seams) y Arte Previo**:
  - Se selecciona una única costura de prueba de alto nivel: la frontera HTTP a través de pruebas de funcionalidad (`Feature Tests`) de Laravel.
  - Todo el tráfico de red saliente hacia Whisper y APIs externas se aísla en las pruebas utilizando el sistema de simulación de clientes HTTP del framework, garantizando una ejecución determinista, rápida e independiente de la disponibilidad del contenedor o credenciales de pago.
  - Arte previo en el repositorio: `tests/Feature/PerfilCompartidoTest.php` y `tests/Feature/ProfileTest.php`, que sirven como modelo de aserciones de sesión, autenticación y base de datos con `RefreshDatabase`.

## Out of Scope

- Transcripción en tiempo real (streaming palabra por palabra durante la grabación con el micrófono).
- Componentes y modales de interfaz de usuario en el frontend (Vue 3 / Inertia), los cuales formarán parte de la fase de integración visual.
- Diarización de interlocutores (identificación y etiquetado de qué persona habló en cada fragmento de la clase).
- Conversión o compresión de formatos de audio en el servidor; el backend procesa los archivos en los formatos multimedia soportados subidos por el cliente.

## Further Notes

- Alineado con el Architecture Decision Record (ADR) `0001-hybrid-stt-and-gemini-transcription.md`.
- El microservicio local utiliza la imagen de contenedor Docker estándar de Whisper webservice escuchando en el puerto local 9000.
