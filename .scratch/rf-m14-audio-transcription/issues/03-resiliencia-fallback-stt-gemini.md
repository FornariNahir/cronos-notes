# 03: Resiliencia y Fallback Automático STT a Gemini Multimodal

**What to build:** Si el microservicio local de Whisper no está disponible o produce un error de conexión/timeout, el sistema conmuta automáticamente a Gemini Multimodal Audio cuando la variable de fallback está activa. Si ambos proveedores fallan, se persiste el estado fallido y se informa el error apropiado.

**Blocked by:** 01: Transcripción STT con Whisper Local de Punta a Punta

**Status:** ready-for-agent

- [ ] Detección de fallo de conexión o HTTP error en llamada a Whisper.
- [ ] Conmutación automática a Gemini Multimodal Audio (`gemini-2.0-flash`) enviando el archivo binario/inlineData.
- [ ] Persistencia de `estado = 'fallido'` y detalle en `error_mensaje` con respuesta HTTP 503 si todos los intentos fallan.
- [ ] Pruebas automatizadas con `Http::fake()` simulando caída de Whisper y fallback exitoso a Gemini.
