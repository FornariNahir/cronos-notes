"use client"

import { useState } from "react"
import { Settings, Bell, FileText } from "lucide-react"
import { EditorToolbar } from "@/components/editor-toolbar"
import { NoteEditor } from "@/components/note-editor"
import { AudioPanel } from "@/components/audio-panel"

export function CronosNotes() {
  const [cornellMode, setCornellMode] = useState(false)
  const [audioOpen, setAudioOpen] = useState(true)
  const [activeFormats, setActiveFormats] = useState<Record<string, boolean>>({})
  const [font, setFont] = useState("Arial")
  const [size, setSize] = useState("11")

  function exec(command: string, value?: string) {
    document.execCommand(command, false, value)
    // refresh active states
    const next: Record<string, boolean> = {}
    for (const cmd of ["bold", "italic", "underline", "strikeThrough"]) {
      try {
        next[cmd] = document.queryCommandState(cmd)
      } catch {
        next[cmd] = false
      }
    }
    setActiveFormats(next)
  }

  return (
    <div className="flex min-h-screen flex-col bg-background text-foreground">
      {/* Header */}
      <header className="flex items-center justify-between gap-4 border-b border-border px-4 py-4 sm:px-6">
        <div className="flex min-w-0 items-center gap-3">
          <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
            <FileText className="size-5" />
          </span>
          <h1 className="truncate text-base font-semibold text-primary sm:text-lg">
            Programación en Ambientes Web
          </h1>
        </div>
        <div className="flex shrink-0 items-center gap-3 sm:gap-4">
          <button
            type="button"
            aria-label="Configuración"
            className="text-muted-foreground transition-colors hover:text-foreground"
          >
            <Settings className="size-5" />
          </button>
          <button
            type="button"
            aria-label="Notificaciones"
            className="text-muted-foreground transition-colors hover:text-foreground"
          >
            <Bell className="size-5" />
          </button>
          <img
            src="/woman-avatar.png"
            alt="Avatar de usuario"
            className="size-9 rounded-full object-cover"
          />
        </div>
      </header>

      {/* Body */}
      <div className="flex flex-1 overflow-hidden">
        <div className="flex min-w-0 flex-1 flex-col">
          <EditorToolbar
            font={font}
            size={size}
            onFont={(f) => {
              setFont(f)
              exec("fontName", f)
            }}
            onSize={(s) => {
              setSize(s)
              exec("fontSize", sizeToHtml(s))
            }}
            activeFormats={activeFormats}
            onExec={exec}
            cornellMode={cornellMode}
            onToggleCornell={() => setCornellMode((v) => !v)}
          />
          <NoteEditor cornellMode={cornellMode} font={font} />
        </div>

        <AudioPanel open={audioOpen} onOpenChange={setAudioOpen} />
      </div>
    </div>
  )
}

// execCommand fontSize accepts 1-7; map pt-ish values into that range
function sizeToHtml(size: string) {
  const n = Number.parseInt(size, 10)
  if (n <= 9) return "1"
  if (n <= 11) return "2"
  if (n <= 13) return "3"
  if (n <= 16) return "4"
  if (n <= 20) return "5"
  if (n <= 28) return "6"
  return "7"
}
