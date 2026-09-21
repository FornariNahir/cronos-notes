# 02: Síntesis Estructurada Cornell con Google Gemini Flash

**What to build:** Tras la transcripción fonética, el sistema envía automáticamente el texto plano a Gemini 2.0 Flash con prompt y esquema estructurado del Método Cornell (ideas clave, notas de clase, resumen y título sugerido), guardando el objeto JSON y retornándolo al cliente.

**Blocked by:** 01: Transcripción STT con Whisper Local de Punta a Punta

**Status:** ready-for-agent

- [ ] `AudioTranscriptionService` implementa `summarizeCornell(string $text): array` usando Google Gemini 2.0 Flash.
- [ ] El payload de respuesta sigue estrictamente el esquema JSON con `titulo_sugerido`, `ideas_clave`, `notas` y `resumen`.
- [ ] El JSON resultante se persiste en `ApunteAudio.resumen_ia`.
- [ ] Pruebas automatizadas con `Http::fake()` validan el contrato de respuesta y la persistencia estructurada.
