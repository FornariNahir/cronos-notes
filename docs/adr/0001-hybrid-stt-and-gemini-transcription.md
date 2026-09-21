# 0001. Arquitectura Híbrida para Transcripción STT (Whisper Local) y Resumen Cornell (Gemini)

## Contexto y Decisión
Para el requerimiento RF-M14 (Transcripción Automática y Resumen Inteligente de Audios), el sistema necesita convertir grabaciones de audio de clases en texto y estructurarlas pedagógicamente bajo el Método Cornell (Ideas clave, Notas, Resumen).

Se decide desacoplar el procesamiento en dos etapas mediante un **patrón Driver**:
1. **Speech-to-Text (STT)**: Se implementa un servicio conmutador configurable por entorno (TRANSCRIPTION_DRIVER) que soporta **Whisper Local** (vía microservicio HTTP local http://localhost:9000/asr) y **Gemini 2.0 Multimodal** como alternativa en la nube.
2. **Resumen Cornell**: Whisper únicamente realiza desgrabado fonético; por lo tanto, el texto transcrito se canaliza hacia **Google Gemini 2.0 Flash** con un esquema de salida JSON estructurado para poblar los cuadrantes Cornell.

## Justificación y Consecuencias
- **Whisper Local**: Permite transcribir audios de forma gratuita e ilimitada en el entorno de desarrollo sin depender de costos o cuotas de terceros.
- **Fallback / Portabilidad**: Al soportar Gemini de forma transparente sin alterar el contrato del controlador, cualquier evaluador o miembro del equipo puede ejecutar el flujo sin necesidad de instalar o levantar un servidor local de Whisper.
- **Mantenibilidad**: La persona encargada del frontend consume un único contrato JSON unificado independientemente de qué motor STT se encuentre activo en el backend.
