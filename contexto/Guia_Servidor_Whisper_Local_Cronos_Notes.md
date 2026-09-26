# Guía de Instalación y Configuración: Servidor Local Whisper AI (Speech-to-Text)
**Proyecto:** Cronos Notes (Season 2) — Requerimiento [RF-M14]
**Fecha:** Septiembre 2026

---

## 1. Introducción y Propósito
Para el requerimiento **RF-M14 (Transcripción Automática y Resumen Inteligente de Audios)**, el backend de Cronos Notes utiliza una arquitectura desacoplada con **patrón Driver**:
1. **Speech-to-Text (STT)**: Transcripción del audio a texto crudo mediante un servidor local de **Whisper AI** (gratuito, sin límites de cuota y privado).
2. **Resumen Cornell**: Envío del texto transcrito a **Google Gemini 2.0 Flash** para clasificar y sintetizar los conceptos clave en formato Cornell (*Ideas Clave*, *Notas*, *Resumen*).
3. **Mecanismo de Fallback**: Si el servidor local de Whisper no está disponible, el backend conmuta automáticamente hacia Gemini Multimodal para no interrumpir el flujo.

---

## 2. Opción 1: Despliegue con Docker (Recomendada)

Dado que Docker aísla las dependencias nativas (`ffmpeg`, Python, PyTorch) en un contenedor autocontenido, esta es la alternativa más simple y robusta.

### Prerrequisitos
- Tener **Docker Desktop** instalado en Windows y en estado **Running** (motor activo).

### Paso a paso

#### 1. Iniciar el contenedor con Whisper
Abrí una terminal (PowerShell o CMD) y ejecutá el siguiente comando:

```bash
docker run -d --name whisper-local -p 9000:9000 -e ASR_MODEL=base -e ASR_ENGINE=openai_whisper onerahmet/openai-whisper-asr-webservice:latest
```

#### Explicación de parámetros:
* `-d`: Ejecuta el contenedor en segundo plano (*detached mode*).
* `--name whisper-local`: Nombre identificatorio para administrar el contenedor.
* `-p 9000:9000`: Mapea el puerto 9000 de tu máquina física al puerto 9000 del contenedor.
* `-e ASR_MODEL=base`: Modelo preentrenado `base` (~140 MB). Es el balance óptimo entre velocidad en CPU y precisión fonética para español.
* `-e ASR_ENGINE=openai_whisper`: Motor oficial de OpenAI.

#### 2. Verificar que el servidor esté activo
- Abrí en tu navegador: `http://localhost:9000/docs`
- Verás la documentación interactiva de Swagger con el endpoint `/asr`.

#### 3. Probar la transcripción desde la consola
```powershell
curl -X POST "http://localhost:9000/asr?task=transcribe&language=es&output=json" -F "audio_file=@ruta/a/tu/audio.mp3"
```
**Respuesta esperada:**
```json
{
  "text": "Texto desgrabado del archivo de audio..."
}
```

#### Comandos útiles de Docker:
- **Detener el servidor:** `docker stop whisper-local`
- **Reanudar el servidor:** `docker start whisper-local`
- **Ver logs en tiempo real:** `docker logs -f whisper-local`

---

## 3. Opción 2: Servidor Nativo en Python con FastAPI (Sin Docker)

Si preferís correrlo sin Docker Desktop como un proceso nativo en Windows:

### Prerrequisitos
1. **Instalar FFmpeg** (necesario para decodificar formatos de audio como `.mp3`, `.webm`, `.wav`):
   ```powershell
   winget install Gyan.FFmpeg
   ```
   *(Cerrar y reabrir la consola tras la instalación para actualizar el PATH)*.

2. **Instalar librerías de Python**:
   ```bash
   pip install fastapi uvicorn faster-whisper python-multipart
   ```

### Archivo del Servidor (`server_whisper.py`)
Crear un archivo llamado `server_whisper.py` con el siguiente código:

```python
from fastapi import FastAPI, UploadFile, File
from faster_whisper import WhisperModel
import tempfile
import os

app = FastAPI(title="Local Whisper STT - Cronos Notes")

# Modelo cuantizado int8 para máxima velocidad en CPU
print("Cargando modelo Whisper 'base'...")
model = WhisperModel("base", device="cpu", compute_type="int8")
print("Modelo cargado y listo en puerto 9000.")

@app.post("/asr")
async def transcribe(audio_file: UploadFile = File(...)):
    suffix = os.path.splitext(audio_file.filename)[1] or ".mp3"
    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        tmp.write(await audio_file.read())
        tmp_path = tmp.name

    try:
        segments, info = model.transcribe(tmp_path, language="es")
        full_text = " ".join([segment.text for segment in segments]).strip()
        return {"text": full_text}
    finally:
        if os.path.exists(tmp_path):
            os.remove(tmp_path)

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=9000)
```

### Ejecutar el servidor Python
```bash
python server_whisper.py
```

---

## 4. Configuración en Laravel (`.env`)

Para conectar Cronos Notes con este servidor local, agregá las siguientes directivas en el archivo `.env` del proyecto Laravel:

```env
# ==============================================================================
# CONFIGURACIÓN DE TRANSCRIPCIÓN IA (RF-M14)
# ==============================================================================
# Driver activo: 'whisper_local' o 'gemini'
TRANSCRIPTION_DRIVER=whisper_local

# URL del endpoint local expuesto por Docker o Python
WHISPER_LOCAL_URL=http://localhost:9000/asr

# Conmutar automáticamente a Gemini si Whisper local no responde o está apagado
WHISPER_FALLBACK_TO_GEMINI=true
```

---

## 5. Contrato de Integración Backend ➔ Whisper
- **Método HTTP:** `POST`
- **URL:** `http://localhost:9000/asr?task=transcribe&language=es&output=json`
- **Content-Type:** `multipart/form-data`
- **Body:** `audio_file` (archivo binario de audio)
- **Status Code de Éxito:** `200 OK`
- **Payload de Respuesta:**
  ```json
  {
    "text": "Transcripción textual completa del audio..."
  }
  ```
