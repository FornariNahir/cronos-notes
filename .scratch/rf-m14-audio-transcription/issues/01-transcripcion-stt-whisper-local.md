# 01: Transcripción STT con Whisper Local de Punta a Punta

**What to build:** Un estudiante autenticado con permiso de modificación puede solicitar la transcripción automática de un audio de su apunte mediante el microservicio local de Whisper, persistiendo el texto obtenido y el estado en base de datos, protegido contra accesos indebidos.

**Blocked by:** None (can start immediately)

**Status:** resolved

- [x] Archivo `.env` configurado con variables de entorno para Whisper Local (`WHISPER_LOCAL_URL`, `TRANSCRIPTION_DRIVER`, `WHISPER_FALLBACK_TO_GEMINI`).
- [x] La tabla `ApunteAudio` cuenta con migración para los campos `transcripcion`, `resumen_ia`, `estado` y `error_mensaje`.
- [x] El modelo `ApunteAudio` tiene configurados los campos en `$fillable` y el casteo de `resumen_ia` a `array`.
- [x] La configuración en `config/services.php` incluye los parámetros de Whisper (`url`, `driver`, `fallback_to_gemini`).
- [x] El servicio `AudioTranscriptionService` implementa la llamada multipart hacia el endpoint `/asr` de Whisper Local (`audio_file`).
- [x] El endpoint `POST /apuntes/{id}/audios/{audioId}/transcribir` procesa la transcripción, actualiza el estado a `completado` y retorna la transcripción en JSON.
- [x] Se verifica autorización: usuarios no autenticados reciben 401/redirección, y colaboradores con rol `Lector` reciben 403 Forbidden.
- [x] Suite de pruebas de integración con `Http::fake()` valida el flujo de punta a punta en `tests/Feature/AudioTranscriptionTest.php`.
