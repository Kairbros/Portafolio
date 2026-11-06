<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { PALETTE, SIZE, blank, encode, isEmpty, paint } from '@/utils/pixels'

// el texto del dibujo; vacío cuando no hay nada pintado
const model = defineModel<string>({ required: true })

const { t } = useI18n()

const canvas = ref<HTMLCanvasElement | null>(null)
const color = ref(9)
const tool = ref<'pen' | 'erase' | 'fill'>('pen')
const canUndo = ref(false)

let cells = blank()
const undoStack: Uint8Array[] = []
let painting = false
let last: { x: number; y: number } | null = null

const colors = PALETTE.map((hex, index) => ({ hex, index })).filter((c) => c.hex)

function render() {
    if (canvas.value) paint(canvas.value, cells, true)
    canUndo.value = undoStack.length > 0
}

function commit() {
    model.value = isEmpty(cells) ? '' : encode(cells)
    render()
}

function remember() {
    undoStack.push(cells.slice())
    if (undoStack.length > 40) undoStack.shift()
}

function cellAt(e: PointerEvent) {
    const r = canvas.value!.getBoundingClientRect()
    const x = Math.floor(((e.clientX - r.left) / r.width) * SIZE)
    const y = Math.floor(((e.clientY - r.top) / r.height) * SIZE)
    return { x: Math.max(0, Math.min(SIZE - 1, x)), y: Math.max(0, Math.min(SIZE - 1, y)) }
}

function put(x: number, y: number, c: number) {
    cells[y * SIZE + x] = c
}

// línea entre dos cuadros para que el trazo no deje huecos
function line(a: { x: number; y: number }, b: { x: number; y: number }, c: number) {
    let { x: x0, y: y0 } = a
    const dx = Math.abs(b.x - x0)
    const dy = -Math.abs(b.y - y0)
    const sx = x0 < b.x ? 1 : -1
    const sy = y0 < b.y ? 1 : -1
    let err = dx + dy
    for (;;) {
        put(x0, y0, c)
        if (x0 === b.x && y0 === b.y) break
        const e2 = 2 * err
        if (e2 >= dy) { err += dy; x0 += sx }
        if (e2 <= dx) { err += dx; y0 += sy }
    }
}

function fill(x: number, y: number, c: number) {
    const target = cells[y * SIZE + x]
    if (target === c) return
    const stack: [number, number][] = [[x, y]]
    while (stack.length) {
        const [px, py] = stack.pop()!
        if (px < 0 || py < 0 || px >= SIZE || py >= SIZE || cells[py * SIZE + px] !== target) continue
        cells[py * SIZE + px] = c
        stack.push([px + 1, py], [px - 1, py], [px, py + 1], [px, py - 1])
    }
}

const ink = () => (tool.value === 'erase' ? 0 : color.value)

function down(e: PointerEvent) {
    remember()
    const cell = cellAt(e)
    if (tool.value === 'fill') {
        fill(cell.x, cell.y, color.value)
        commit()
        return
    }
    painting = true
    last = cell
    canvas.value!.setPointerCapture(e.pointerId)
    line(cell, cell, ink())
    render()
}

function move(e: PointerEvent) {
    if (!painting || !last) return
    const cell = cellAt(e)
    line(last, cell, ink())
    last = cell
    render()
}

function up() {
    if (!painting) return
    painting = false
    commit()
}

function pick(index: number) {
    color.value = index
    if (tool.value === 'erase') tool.value = 'pen'
}

function undo() {
    const previous = undoStack.pop()
    if (!previous) return
    cells = previous
    commit()
}

function clear() {
    if (isEmpty(cells)) return
    remember()
    cells = blank()
    commit()
}

// el formulario vacía el modelo después de enviar
watch(model, (value) => {
    if (value === '' && !isEmpty(cells)) {
        cells = blank()
        undoStack.length = 0
        render()
    }
})

onMounted(render)
</script>

<template>
    <div class="nb-editor">
        <canvas
            ref="canvas"
            class="nb-editor__canvas"
            width="320"
            height="320"
            :aria-label="t('notebook.canvas')"
            @pointerdown="down"
            @pointermove="move"
            @pointerup="up"
            @pointercancel="up"
        ></canvas>

        <div class="nb-palette" role="group" :aria-label="t('notebook.colors')">
            <button
                v-for="c in colors"
                :key="c.index"
                type="button"
                class="nb-palette__swatch"
                :style="{ background: c.hex! }"
                :aria-label="t(`notebook.palette.${c.index}`)"
                :title="t(`notebook.palette.${c.index}`)"
                :aria-pressed="color === c.index && tool !== 'erase'"
                @click="pick(c.index)"
            ></button>
        </div>

        <div class="nb-tools" role="group" :aria-label="t('notebook.tools')">
            <button type="button" class="dd-btn" :aria-pressed="tool === 'pen'" @click="tool = 'pen'">{{ t('notebook.pen') }}</button>
            <button type="button" class="dd-btn" :aria-pressed="tool === 'erase'" @click="tool = 'erase'">{{ t('notebook.eraser') }}</button>
            <button type="button" class="dd-btn" :aria-pressed="tool === 'fill'" @click="tool = 'fill'">{{ t('notebook.fill') }}</button>
            <button type="button" class="dd-btn" :disabled="!canUndo" @click="undo">{{ t('notebook.undo') }}</button>
            <button type="button" class="dd-btn" :disabled="!model" @click="clear">{{ t('notebook.clear') }}</button>
        </div>
    </div>
</template>
