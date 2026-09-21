# 04: Aplicación Atómica Cornell al Apunte (Reemplazar y Anexar)

**What to build:** Un estudiante puede volcar las ideas clave, notas y resumen generados directamente en las columnas del apunte principal, seleccionando entre sobrescribir el apunte existente o anexarlo al final de sus notas previas sin perder datos.

**Blocked by:** 02: Síntesis Estructurada Cornell con Google Gemini Flash

**Status:** ready-for-agent

- [ ] Endpoint `POST /apuntes/{id}/audios/{audioId}/aplicar-cornell` creado y protegido bajo sesión y perfil con permiso de modificación.
- [ ] Modo `reemplazar`: Sobrescribe `ideasApunte`, `contenidoApunte` y `resumenApunte`.
- [ ] Modo `anexar`: Concatena de forma limpia y ordenada preservando el contenido preexistente.
- [ ] Validación de que usuarios con rol `Lector` reciben 403 Forbidden.
- [ ] Pruebas automatizadas de integración para ambos modos y casos de seguridad.
