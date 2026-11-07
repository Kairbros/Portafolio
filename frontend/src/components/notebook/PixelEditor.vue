<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { MAX_COLORS, PALETTE, PAPER, SIZE, blank, isEmpty, pack, paint } from '@/utils/pixels'
import ColorWheel from '@/components/notebook/ColorWheel.vue'

// el texto del dibujo; vacío cuando no hay nada pintado
const model = defineModel<string>({ required: true })
const colorsModel = defineModel<string[]>('colors', { default: () => [] })

const { t } = useI18n()

const canvas = ref<HTMLCanvasElement | null>(null)
const current = ref(PALETTE[8]!)
const tool = ref<'pen' | 'erase' | 'fill'>('pen')
const canUndo = ref(false)
const used = ref<string[]>([])
const full = ref(false)

type Point = { x: number; y: number }

const MAX_ZOOM = 8

const zoom = ref(1)
const pan = ref<Point>({ x: 0, y: 0 })

let cells = blank()
const palette: string[] = []
const undoStack: Uint8Array[] = []
let painting = false
let stroke = 0
let strokeId = -1
let last: Point | null = null

const pointers = new Map<number, Point>()
let gesture: { dist: number; mid: Point; zoom: number; pan: Point } | null = null
let dragging: { from: Point; pan: Point } | null = null
let pendingFill: { id: number; cell: Point; ink: number } | null = null
let observer: ResizeObserver | null = null

const presets = PALETTE.map((hex, i) => ({ hex, name: i + 1 })).filter((p) => p.hex !== PAPER)

function render() {
    if (canvas.value) paint(canvas.value, cells, palette, true, { zoom: zoom.value, ...pan.value })
    canUndo.value = undoStack.length > 0
    const seen = new Set<number>(cells)
    used.value = palette.filter((_, i) => seen.has(i + 1))
}

function commit() {
    const packed = pack(cells, palette)
    model.value = packed.pixels
    colorsModel.value = packed.colors
    render()
}

function slotFor(hex: string): number {
    const found = palette.indexOf(hex)
    if (found >= 0) return found + 1
    if (palette.length < MAX_COLORS) return palette.push(hex)

    const busy = new Set<number>()
    for (const snapshot of [cells, ...undoStack]) for (const c of snapshot) busy.add(c)
    const free = palette.findIndex((_, i) => !busy.has(i + 1))
    if (free < 0) return 0
    palette[free] = hex
    return free + 1
}

function remember() {
    undoStack.push(cells.slice())
    if (undoStack.length > 40) undoStack.shift()
}

const clamp = (n: number, min: number, max: number) => Math.max(min, Math.min(max, n))

function point(e: { clientX: number; clientY: number }): Point {
    const r = canvas.value!.getBoundingClientRect()
    return { x: (e.clientX - r.left) / r.width, y: (e.clientY - r.top) / r.height }
}

function cellAt(e: PointerEvent): Point {
    const p = point(e)
    const x = Math.floor(((p.x - pan.value.x) / zoom.value) * SIZE)
    const y = Math.floor(((p.y - pan.value.y) / zoom.value) * SIZE)
    return { x: clamp(x, 0, SIZE - 1), y: clamp(y, 0, SIZE - 1) }
}

function setView(next: number, offset: Point) {
    const z = clamp(next, 1, MAX_ZOOM)
    zoom.value = z
    pan.value = { x: clamp(offset.x, 1 - z, 0), y: clamp(offset.y, 1 - z, 0) }
}

function zoomAt(p: Point, next: number, from = { zoom: zoom.value, pan: pan.value, at: p }) {
    const z = clamp(next, 1, MAX_ZOOM)
    const cx = (from.at.x - from.pan.x) / from.zoom
    const cy = (from.at.y - from.pan.y) / from.zoom
    setView(z, { x: p.x - cx * z, y: p.y - cy * z })
}

function zoomBy(factor: number) {
    zoomAt({ x: 0.5, y: 0.5 }, zoom.value * factor)
}

function resetView() {
    setView(1, { x: 0, y: 0 })
}

function wheel(e: WheelEvent) {
    const speed = e.ctrlKey ? 0.01 : 0.002
    zoomAt(point(e), zoom.value * Math.exp(-e.deltaY * speed))
}

function pair(): [Point, Point] {
    const [a, b] = [...pointers.values()]
    return [a!, b!]
}

function startGesture() {
    pendingFill = null
    if (painting) {
        painting = false
        const before = undoStack.pop()
        if (before) cells = before
        render()
    }
    const [a, b] = pair()
    gesture = {
        dist: Math.max(Math.hypot(a.x - b.x, a.y - b.y), 0.001),
        mid: { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 },
        zoom: zoom.value,
        pan: { ...pan.value },
    }
}

function pinch() {
    const g = gesture!
    const [a, b] = pair()
    const mid = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 }
    zoomAt(mid, (g.zoom * Math.hypot(a.x - b.x, a.y - b.y)) / g.dist, { zoom: g.zoom, pan: g.pan, at: g.mid })
}

function fit() {
    const c = canvas.value
    if (!c) return
    const px = Math.round(c.clientWidth * (window.devicePixelRatio || 1))
    if (px && c.width !== px) {
        c.width = px
        c.height = px
    }
    render()
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

function down(e: PointerEvent) {
    canvas.value!.setPointerCapture(e.pointerId)
    pointers.set(e.pointerId, point(e))

    if (pointers.size === 2) return startGesture()
    if (pointers.size > 2) return
    if (e.pointerType === 'mouse' && e.button !== 0) {
        dragging = { from: point(e), pan: { ...pan.value } }
        return
    }

    const ink = tool.value === 'erase' ? 0 : slotFor(current.value)
    full.value = tool.value !== 'erase' && ink === 0
    if (full.value) return

    const cell = cellAt(e)
    if (tool.value === 'fill') {
        pendingFill = { id: e.pointerId, cell, ink }
        return
    }
    remember()
    painting = true
    strokeId = e.pointerId
    stroke = ink
    last = cell
    line(cell, cell, stroke)
    render()
}

function move(e: PointerEvent) {
    if (!pointers.has(e.pointerId)) return
    pointers.set(e.pointerId, point(e))

    if (gesture) {
        if (pointers.size >= 2) pinch()
        return
    }
    if (dragging) {
        const p = point(e)
        setView(zoom.value, { x: dragging.pan.x + p.x - dragging.from.x, y: dragging.pan.y + p.y - dragging.from.y })
        return
    }
    if (!painting || e.pointerId !== strokeId || !last) return
    const cell = cellAt(e)
    line(last, cell, stroke)
    last = cell
    render()
}

function up(e: PointerEvent) {
    if (!pointers.delete(e.pointerId)) return

    if (gesture) {
        if (pointers.size < 2) gesture = null
        return
    }
    if (dragging) {
        dragging = null
        return
    }
    if (pendingFill?.id === e.pointerId) {
        remember()
        fill(pendingFill.cell.x, pendingFill.cell.y, pendingFill.ink)
        pendingFill = null
        commit()
        return
    }
    if (painting && e.pointerId === strokeId) {
        painting = false
        commit()
    }
}

function pick(hex: string) {
    current.value = hex
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
        palette.length = 0
        undoStack.length = 0
        full.value = false
        render()
    }
})

watch(current, () => {
    full.value = false
    if (tool.value === 'erase') tool.value = 'pen'
})

watch([zoom, pan], render)

onMounted(() => {
    observer = new ResizeObserver(fit)
    observer.observe(canvas.value!)
    fit()
})

onUnmounted(() => observer?.disconnect())
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
            @wheel.prevent="wheel"
            @contextmenu.prevent
        ></canvas>

        <div class="nb-zoom" role="group" :aria-label="t('notebook.zoom')">
            <button type="button" class="dd-btn" :aria-label="t('notebook.zoomOut')" :disabled="zoom <= 1" @click="zoomBy(1 / 1.5)">−</button>
            <button type="button" class="dd-btn nb-zoom__level" :aria-label="t('notebook.zoomReset')" :disabled="zoom === 1" @click="resetView">{{ Math.round(zoom * 100) }}%</button>
            <button type="button" class="dd-btn" :aria-label="t('notebook.zoomIn')" :disabled="zoom >= MAX_ZOOM" @click="zoomBy(1.5)">+</button>
            <span class="nb-zoom__hint">{{ t('notebook.zoomHint') }}</span>
        </div>

        <div class="nb-picker">
            <ColorWheel v-model="current" />

            <div class="nb-picker__side">
                <div class="nb-picker__now">
                    <span class="nb-picker__chip" :style="{ background: current }" aria-hidden="true"></span>
                    <span>{{ current }}</span>
                </div>

                <div class="nb-palette" role="group" :aria-label="t('notebook.colors')">
                    <button
                        v-for="p in presets"
                        :key="p.hex"
                        type="button"
                        class="nb-palette__swatch"
                        :style="{ background: p.hex }"
                        :aria-label="t(`notebook.palette.${p.name}`)"
                        :title="t(`notebook.palette.${p.name}`)"
                        :aria-pressed="current === p.hex && tool !== 'erase'"
                        @click="pick(p.hex)"
                    ></button>
                </div>

                <div v-if="used.length" class="nb-picker__used">
                    <span class="nb-picker__label">{{ t('notebook.used', { n: used.length, max: MAX_COLORS }) }}</span>
                    <div class="nb-palette" role="group" :aria-label="t('notebook.usedColors')">
                        <button
                            v-for="hex in used"
                            :key="hex"
                            type="button"
                            class="nb-palette__swatch"
                            :style="{ background: hex }"
                            :aria-label="hex"
                            :title="hex"
                            :aria-pressed="current === hex && tool !== 'erase'"
                            @click="pick(hex)"
                        ></button>
                    </div>
                </div>

                <p v-if="full" class="dd-field__error" role="status">{{ t('notebook.full', { max: MAX_COLORS }) }}</p>
            </div>
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
