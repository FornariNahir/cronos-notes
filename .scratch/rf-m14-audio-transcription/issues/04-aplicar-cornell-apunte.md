# 04: Aplicación Atómica Cornell al Apunte (Reemplazar y Anexar)

**What to build:** Un estudiante puede volcar las ideas clave, notas y resumen generados directamente en las columnas del apunte principal, seleccionando entre sobrescribir el apunte existente o anexarlo al final de sus notas previas sin perder datos.

**Blocked by:** 02: Síntesis Estructurada Cornell con Google Gemini Flash

**Status:** resolved

- [x] Endpoint `POST /apuntes/{id}/audios/{audioId}/aplicar-cornell` creado en `AudioTranscriptionController@aplicarCornell` y protegido bajo sesión y perfil con permiso de modificación.
- [x] Modo `reemplazar`: Sobrescribe `ideasApunte`, `contenidoApunte` y `resumenApunte` y actualiza el título si es genérico.
- [x] Modo `anexar`: Concatena de forma limpia y ordenada preservando el contenido preexistente del alumno.
- [x] Validación de que usuarios con rol `Lector` reciben 403 Forbidden y validación de existencia previa de `resumen_ia` (422).
- [x] Soporte para volcado unificado en `Modo Normal` (`formato: 'normal'`) con jerarquía secuencial: Preguntas Clave -> Notas -> Resumen.
- [x] Pruebas automatizadas de integración para ambos modos y casos de seguridad en `tests/Feature/AudioTranscriptionTest.php`.
