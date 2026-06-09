<template>
  <div class="flex flex-wrap items-center gap-2 border-b border-border px-4 py-3 sm:px-6 bg-card text-card-foreground">
    <!-- Font family -->
    <div class="relative w-32">
      <select
        aria-label="Tipo de letra"
        :value="font"
        @change="$emit('update:font', $event.target.value)"
        class="w-full appearance-none rounded-md border border-border bg-card px-3 py-2 pr-8 text-sm text-foreground outline-none focus:ring-2 focus:ring-ring"
      >
        <option v-for="o in FONTS" :key="o" :value="o">{{ o }}</option>
      </select>
      <ChevronDown class="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
    </div>

    <!-- Font size -->
    <div class="relative w-16">
      <select
        aria-label="Tamaño de letra"
        :value="size"
        @change="$emit('update:size', $event.target.value)"
        class="w-full appearance-none rounded-md border border-border bg-card px-3 py-2 pr-8 text-sm text-foreground outline-none focus:ring-2 focus:ring-ring"
      >
        <option v-for="o in SIZES" :key="o" :value="o">{{ o }}</option>
      </select>
      <ChevronDown class="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
    </div>

    <span class="mx-1 hidden h-6 w-px bg-border sm:block"></span>

    <button type="button" @mousedown.prevent @click="$emit('exec', 'bold')" :class="['flex size-8 items-center justify-center rounded-md transition-colors', activeFormats.bold ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground']"><Bold class="size-4" /></button>
    <button type="button" @mousedown.prevent @click="$emit('exec', 'italic')" :class="['flex size-8 items-center justify-center rounded-md transition-colors', activeFormats.italic ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground']"><Italic class="size-4" /></button>
    <button type="button" @mousedown.prevent @click="$emit('exec', 'underline')" :class="['flex size-8 items-center justify-center rounded-md transition-colors', activeFormats.underline ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground']"><Underline class="size-4" /></button>
    <button type="button" @mousedown.prevent @click="$emit('exec', 'strikeThrough')" :class="['flex size-8 items-center justify-center rounded-md transition-colors', activeFormats.strikeThrough ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground']"><Strikethrough class="size-4" /></button>

    <span class="mx-1 hidden h-6 w-px bg-border sm:block"></span>

    <button type="button" @mousedown.prevent @click="$emit('exec', 'justifyLeft')" class="flex size-8 items-center justify-center rounded-md transition-colors text-muted-foreground hover:bg-muted hover:text-foreground"><AlignLeft class="size-4" /></button>
    <button type="button" @mousedown.prevent @click="$emit('exec', 'justifyCenter')" class="flex size-8 items-center justify-center rounded-md transition-colors text-muted-foreground hover:bg-muted hover:text-foreground"><AlignCenter class="size-4" /></button>
    <button type="button" @mousedown.prevent @click="$emit('exec', 'justifyRight')" class="flex size-8 items-center justify-center rounded-md transition-colors text-muted-foreground hover:bg-muted hover:text-foreground"><AlignRight class="size-4" /></button>
    <button type="button" @mousedown.prevent @click="$emit('exec', 'justifyFull')" class="flex size-8 items-center justify-center rounded-md transition-colors text-muted-foreground hover:bg-muted hover:text-foreground"><AlignJustify class="size-4" /></button>

    <span class="mx-1 hidden h-6 w-px bg-border sm:block"></span>

    <button type="button" @mousedown.prevent @click="printWindow" class="flex size-8 items-center justify-center rounded-md transition-colors text-muted-foreground hover:bg-muted hover:text-foreground"><Printer class="size-4" /></button>
    <button type="button" @mousedown.prevent @click="$emit('exec', 'copy')" class="flex size-8 items-center justify-center rounded-md transition-colors text-muted-foreground hover:bg-muted hover:text-foreground"><Copy class="size-4" /></button>

    <button
      type="button"
      @click="$emit('toggleCornell')"
      class="ml-auto rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
    >
      {{ cornellMode ? "Cambiar a Nota Normal" : "Cambiar a Método Cornell" }}
    </button>
  </div>
</template>

<script setup>
import { Bold, Italic, Underline, Strikethrough, AlignLeft, AlignCenter, AlignRight, AlignJustify, Printer, Copy, ChevronDown } from 'lucide-vue-next'

const props = defineProps({
  font: String,
  size: String,
  activeFormats: Object,
  cornellMode: Boolean
})

const emit = defineEmits(['update:font', 'update:size', 'exec', 'toggleCornell'])

const FONTS = ["Arial", "Times New Roman", "Georgia", "Courier New", "Verdana"]
const SIZES = ["8", "9", "10", "11", "12", "14", "18", "24", "36"]

const printWindow = () => {
  window.print()
}
</script>
