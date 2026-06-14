"use client"

import { useEffect, useRef, useState } from "react"
import { Mic, X, ChevronDown, Square } from "lucide-react"

type Props = {
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function AudioPanel({ open, onOpenChange }: Props) {
  const [recording, setRecording] = useState(false)
  const [seconds, setSeconds] = useState(0)
  const [audioUrl, setAudioUrl] = useState<string | null>(null)
  const mediaRecorderRef = useRef<MediaRecorder | null>(null)
  const chunksRef = useRef<Blob[]>([])
  const timerRef = useRef<ReturnType<typeof setInterval> | null>(null)

  useEffect(() => {
    return () => {
      if (timerRef.current) clearInterval(timerRef.current)
    }
  }, [])

  function startTimer() {
    setSeconds(0)
    timerRef.current = setInterval(() => setSeconds((s) => s + 1), 1000)
  }
  function stopTimer() {
    if (timerRef.current) clearInterval(timerRef.current)
  }

  async function toggleRecording() {
    if (recording) {
      mediaRecorderRef.current?.stop()
      stopTimer()
      setRecording(false)
      return
    }

    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
      const recorder = new MediaRecorder(stream)
      chunksRef.current = []
      recorder.ondataavailable = (e) => {
        if (e.data.size > 0) chunksRef.current.push(e.data)
      }
      recorder.onstop = () => {
        const blob = new Blob(chunksRef.current, { type: "audio/webm" })
        setAudioUrl(URL.createObjectURL(blob))
        stream.getTracks().forEach((t) => t.stop())
      }
      recorder.start()
      mediaRecorderRef.current = recorder
      setAudioUrl(null)
      startTimer()
      setRecording(true)
    } catch {
      // No mic permission — simulate recording so the UI stays interactive
      startTimer()
      setRecording(true)
    }
  }

  const mmss = `${String(Math.floor(seconds / 60)).padStart(2, "0")}:${String(seconds % 60).padStart(2, "0")}`

  return (
    <>
      {/* Floating toggle when closed */}
      {!open && (
        <button
          type="button"
          onClick={() => onOpenChange(true)}
          aria-label="Abrir panel de audio"
          className="fixed bottom-6 right-6 z-30 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform hover:scale-105"
        >
          <Mic className="size-6" />
        </button>
      )}

      {/* Overlay on small screens */}
      {open && (
        <button
          type="button"
          aria-label="Cerrar panel"
          onClick={() => onOpenChange(false)}
          className="fixed inset-0 z-30 bg-foreground/40 lg:hidden"
        />
      )}

      <aside
        className={`fixed inset-y-0 right-0 z-40 flex w-80 max-w-[85vw] flex-col bg-primary p-5 text-primary-foreground transition-transform duration-300 lg:static lg:z-auto lg:max-w-none lg:translate-x-0 ${
          open ? "translate-x-0" : "translate-x-full lg:hidden"
        }`}
      >
        <button
          type="button"
          aria-label="Cerrar panel de audio"
          onClick={() => onOpenChange(false)}
          className="mb-5 flex size-8 items-center justify-center rounded-md text-primary-foreground/90 transition-colors hover:bg-primary-foreground/10"
        >
          <X className="size-5" />
        </button>

        <div className="space-y-3 rounded-lg bg-card p-3">
          <PanelSelect label="Micrófono" options={["Micrófono", "Micrófono integrado", "Auriculares"]} />
          <PanelSelect label="Guardar en" options={["Guardar en", "Mis notas", "Carpeta de audio"]} />
        </div>

        <div className="flex flex-1 flex-col items-center justify-center gap-6">
          <button
            type="button"
            onClick={toggleRecording}
            aria-label={recording ? "Detener grabación" : "Grabar audio"}
            className="relative flex size-40 items-center justify-center rounded-full bg-card text-primary transition-transform hover:scale-105"
          >
            {recording && (
              <span className="absolute inset-0 animate-ping rounded-full bg-card/40" aria-hidden="true" />
            )}
            {recording ? <Square className="size-12 fill-current" /> : <Mic className="size-16" />}
          </button>

          <div className="text-center">
            <p className="text-2xl font-bold">{recording ? mmss : "Grabar audio"}</p>
            {recording && <p className="mt-1 text-sm text-primary-foreground/80">Grabando...</p>}
          </div>

          {audioUrl && !recording && (
            <div className="w-full space-y-2">
              {/* eslint-disable-next-line jsx-a11y/media-has-caption */}
              <audio controls src={audioUrl} className="w-full" />
              <a
                href={audioUrl}
                download="grabacion.webm"
                className="block rounded-md bg-card py-2 text-center text-sm font-medium text-primary transition-colors hover:bg-card/90"
              >
                Descargar audio
              </a>
            </div>
          )}
        </div>
      </aside>
    </>
  )
}

function PanelSelect({ label, options }: { label: string; options: string[] }) {
  return (
    <div className="relative">
      <select
        aria-label={label}
        defaultValue={label}
        className="w-full appearance-none rounded-md border border-border bg-card px-3 py-2 pr-8 text-sm text-foreground outline-none focus:ring-2 focus:ring-ring"
      >
        {options.map((o) => (
          <option key={o} value={o}>
            {o}
          </option>
        ))}
      </select>
      <ChevronDown className="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
    </div>
  )
}
