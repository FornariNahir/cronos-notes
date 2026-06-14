"use client"

import {
  Bold,
  Italic,
  Underline,
  Strikethrough,
  AlignLeft,
  AlignCenter,
  AlignRight,
  AlignJustify,
  Printer,
  Copy,
  ChevronDown,
} from "lucide-react"

const FONTS = ["Arial", "Times New Roman", "Georgia", "Courier New", "Verdana"]
const SIZES = ["8", "9", "10", "11", "12", "14", "18", "24", "36"]

type Props = {
  font: string
  size: string
  onFont: (f: string) => void
  onSize: (s: string) => void
  activeFormats: Record<string, boolean>
  onExec: (command: string, value?: string) => void
  cornellMode: boolean
  onToggleCornell: () => void
}

export function EditorToolbar({
  font,
  size,
  onFont,
  onSize,
  activeFormats,
  onExec,
  cornellMode,
  onToggleCornell,
}: Props) {
  return (
    <div className="flex flex-wrap items-center gap-2 border-b border-border px-4 py-3 sm:px-6">
      {/* Font family */}
      <Select value={font} onChange={onFont} options={FONTS} ariaLabel="Tipo de letra" width="w-32" />
      {/* Font size */}
      <Select value={size} onChange={onSize} options={SIZES} ariaLabel="Tamaño de letra" width="w-16" />

      <Divider />

      <ToolButton label="Negrita" active={activeFormats.bold} onClick={() => onExec("bold")}>
        <Bold className="size-4" />
      </ToolButton>
      <ToolButton label="Cursiva" active={activeFormats.italic} onClick={() => onExec("italic")}>
        <Italic className="size-4" />
      </ToolButton>
      <ToolButton label="Subrayado" active={activeFormats.underline} onClick={() => onExec("underline")}>
        <Underline className="size-4" />
      </ToolButton>
      <ToolButton
        label="Tachado"
        active={activeFormats.strikeThrough}
        onClick={() => onExec("strikeThrough")}
      >
        <Strikethrough className="size-4" />
      </ToolButton>

      <Divider />

      <ToolButton label="Alinear a la izquierda" onClick={() => onExec("justifyLeft")}>
        <AlignLeft className="size-4" />
      </ToolButton>
      <ToolButton label="Centrar" onClick={() => onExec("justifyCenter")}>
        <AlignCenter className="size-4" />
      </ToolButton>
      <ToolButton label="Alinear a la derecha" onClick={() => onExec("justifyRight")}>
        <AlignRight className="size-4" />
      </ToolButton>
      <ToolButton label="Justificar" onClick={() => onExec("justifyFull")}>
        <AlignJustify className="size-4" />
      </ToolButton>

      <Divider />

      <ToolButton label="Imprimir" onClick={() => window.print()}>
        <Printer className="size-4" />
      </ToolButton>
      <ToolButton label="Copiar" onClick={() => onExec("copy")}>
        <Copy className="size-4" />
      </ToolButton>

      <button
        type="button"
        onClick={onToggleCornell}
        className="ml-auto rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
      >
        {cornellMode ? "Cambiar a Nota Normal" : "Cambiar a Método Cornell"}
      </button>
    </div>
  )
}

function ToolButton({
  children,
  label,
  active,
  onClick,
}: {
  children: React.ReactNode
  label: string
  active?: boolean
  onClick: () => void
}) {
  return (
    <button
      type="button"
      aria-label={label}
      aria-pressed={active}
      onMouseDown={(e) => e.preventDefault()}
      onClick={onClick}
      className={`flex size-8 items-center justify-center rounded-md transition-colors ${
        active
          ? "bg-accent text-accent-foreground"
          : "text-muted-foreground hover:bg-muted hover:text-foreground"
      }`}
    >
      {children}
    </button>
  )
}

function Select({
  value,
  onChange,
  options,
  ariaLabel,
  width,
}: {
  value: string
  onChange: (v: string) => void
  options: string[]
  ariaLabel: string
  width: string
}) {
  return (
    <div className={`relative ${width}`}>
      <select
        aria-label={ariaLabel}
        value={value}
        onChange={(e) => onChange(e.target.value)}
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

function Divider() {
  return <span className="mx-1 hidden h-6 w-px bg-border sm:block" />
}
