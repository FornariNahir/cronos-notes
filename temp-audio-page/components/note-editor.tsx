"use client"

type Props = {
  cornellMode: boolean
  font: string
}

export function NoteEditor({ cornellMode, font }: Props) {
  if (cornellMode) {
    return (
      <div className="flex-1 overflow-auto p-4 sm:p-6" style={{ fontFamily: font }}>
        <div className="mx-auto grid max-w-4xl grid-cols-1 gap-4 md:grid-cols-[1fr_2fr]">
          <Editable
            label="Palabras clave"
            placeholder="Ideas y conceptos clave..."
            className="min-h-[200px] md:min-h-[480px]"
          />
          <Editable
            label="Notas"
            placeholder="Empieza a escribir aquí..."
            className="min-h-[200px] md:min-h-[480px]"
          />
          <div className="md:col-span-2">
            <Editable label="Resumen" placeholder="Resumen de la sesión..." className="min-h-[120px]" />
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="flex-1 overflow-auto p-4 sm:p-6" style={{ fontFamily: font }}>
      <div
        contentEditable
        suppressContentEditableWarning
        data-placeholder="Empieza a escribir aquí..."
        className="editor-area mx-auto min-h-[60vh] max-w-4xl rounded-md text-sm leading-relaxed text-foreground outline-none"
      />
    </div>
  )
}

function Editable({
  label,
  placeholder,
  className = "",
}: {
  label: string
  placeholder: string
  className?: string
}) {
  return (
    <div className="flex flex-col gap-2">
      <span className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</span>
      <div
        contentEditable
        suppressContentEditableWarning
        data-placeholder={placeholder}
        className={`editor-area rounded-md border border-border bg-card p-3 text-sm leading-relaxed text-foreground outline-none focus:ring-2 focus:ring-ring ${className}`}
      />
    </div>
  )
}
