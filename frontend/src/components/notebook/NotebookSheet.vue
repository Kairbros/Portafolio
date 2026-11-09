<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import DrawingThumb from '@/components/notebook/DrawingThumb.vue'
import type { Drawing } from '@/stores/drawings'

const props = withDefaults(defineProps<{
    drawings: Drawing[]
    editable?: boolean
    selectedId?: number | null
    snap?: boolean
}>(), { editable: false, selectedId: null, snap: true })

const GRID_X = 2.5
const LINE_Y = 5.6
const PULL = 1.2

const guides = ref<{ x: number | null; y: number | null }>({ x: null, y: null })

const clamp = (n: number, min: number, max: number) => Math.max(min, Math.min(max, n))

function snapAxis(pos: number, size: number, lines: number[], step: number, free: boolean) {
    if (free) return { pos, guide: null }
    let best: { pos: number; guide: number; dist: number } | null = null
    for (const line of lines) {
        for (const k of [0, 0.5, 1]) {
            const dist = Math.abs(pos + size * k - line)
            if (dist <= PULL && (!best || dist < best.dist)) best = { pos: line - size * k, guide: line, dist }
        }
    }
    return best ? { pos: best.pos, guide: best.guide } : { pos: Math.round(pos / step) * step, guide: null }
}

function cardSize(card: HTMLElement) {
    const sheet = card.parentElement!
    return {
        w: (card.offsetWidth / sheet.clientWidth) * 100,
        h: (card.offsetHeight / sheet.clientHeight) * 100,
    }
}

const emit = defineEmits<{
    open: [drawing: Drawing]
    select: [drawing: Drawing]
    commit: [drawing: Drawing]
}>()

const { t } = useI18n()

const ordered = computed(() => [...props.drawings].sort((a, b) => a.z - b.z))

const nameOf = (d: Drawing) => d.name || t('notebook.anonymous')

function cardStyle(d: Drawing) {
    return {
        left: `${d.x}%`,
        top: `${d.y}%`,
        transform: `rotate(${d.rotation}deg)`,
        zIndex: d.z,
    }
}

function topZ() {
    return Math.max(0, ...props.drawings.map((d) => d.z))
}

// ---------- mover ----------
let drag: {
    d: Drawing
    px: number
    py: number
    x: number
    y: number
    z: number
    w: number
    h: number
    size: { w: number; h: number }
} | null = null

function startDrag(e: PointerEvent, d: Drawing) {
    if (!props.editable) return
    const card = e.currentTarget as HTMLElement
    const sheet = card.parentElement!.getBoundingClientRect()
    const z = d.z
    if (props.drawings.some((other) => other !== d && other.z >= d.z)) d.z = topZ() + 1
    emit('select', d)
    drag = { d, px: e.clientX, py: e.clientY, x: d.x, y: d.y, z, w: sheet.width, h: sheet.height, size: cardSize(card) }
    card.setPointerCapture(e.pointerId)
}

function moveDrag(e: PointerEvent) {
    if (!drag) return
    const { w, h } = drag.size
    const others = props.drawings.filter((o) => o !== drag!.d)
    const free = !props.snap || e.altKey

    const sx = snapAxis(
        drag.x + ((e.clientX - drag.px) / drag.w) * 100,
        w,
        [50, ...others.flatMap((o) => [o.x, o.x + w / 2, o.x + w])],
        GRID_X,
        free,
    )
    const sy = snapAxis(
        drag.y + ((e.clientY - drag.py) / drag.h) * 100,
        h,
        [50, ...others.flatMap((o) => [o.y, o.y + h / 2, o.y + h])],
        LINE_Y,
        free,
    )

    drag.d.x = round(clamp(sx.pos, 0, 100 - w))
    drag.d.y = round(clamp(sy.pos, 0, 100 - h))
    guides.value = { x: sx.guide, y: sy.guide }
}

function endDrag() {
    guides.value = { x: null, y: null }
    if (!drag) return
    const { d, x, y, z } = drag
    drag = null
    if (d.x !== x || d.y !== y || d.z !== z) emit('commit', d)
}

// ---------- girar con la manija ----------
let spin: { d: Drawing; cx: number; cy: number; angle: number; rotation: number } | null = null

const angleOf = (e: PointerEvent, cx: number, cy: number) => Math.atan2(e.clientY - cy, e.clientX - cx) * (180 / Math.PI)

function startSpin(e: PointerEvent, d: Drawing) {
    const card = (e.currentTarget as HTMLElement).parentElement!
    const r = card.getBoundingClientRect()
    const cx = r.left + r.width / 2
    const cy = r.top + r.height / 2
    spin = { d, cx, cy, angle: angleOf(e, cx, cy), rotation: d.rotation }
    ;(e.currentTarget as HTMLElement).setPointerCapture(e.pointerId)
}

function moveSpin(e: PointerEvent) {
    if (!spin) return
    let rotation = spin.rotation + angleOf(e, spin.cx, spin.cy) - spin.angle
    rotation = e.shiftKey ? Math.round(rotation / 15) * 15 : Math.round(rotation)
    const straight = Math.round(rotation / 45) * 45
    if (props.snap && !e.altKey && Math.abs(rotation - straight) <= 3) rotation = straight
    spin.d.rotation = ((rotation + 540) % 360) - 180
}

function endSpin() {
    if (!spin) return
    emit('commit', spin.d)
    spin = null
}

// ---------- teclado ----------
function onKey(e: KeyboardEvent, d: Drawing) {
    if (!props.editable) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault()
            emit('open', d)
        }
        return
    }
    const step = e.shiftKey ? 5 : 1
    const moves: Record<string, [number, number]> = {
        ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step],
    }
    if (e.key === '[' || e.key === ']') {
        d.rotation += e.key === '[' ? -3 : 3
    } else if (moves[e.key]) {
        const size = cardSize(e.currentTarget as HTMLElement)
        d.x = round(clamp(d.x + moves[e.key]![0], 0, 100 - size.w))
        d.y = round(clamp(d.y + moves[e.key]![1], 0, 100 - size.h))
    } else {
        return
    }
    e.preventDefault()
    emit('select', d)
    emit('commit', d)
}

const round = (n: number) => Math.round(n * 100) / 100

function onClick(d: Drawing) {
    if (props.editable) emit('select', d)
    else emit('open', d)
}
</script>

<template>
    <div class="nb-sheet" :class="{ 'nb-sheet--board': editable, 'nb-sheet--snap': editable && snap }">
        <p v-if="!drawings.length" class="nb-sheet__empty">{{ t('notebook.empty') }}</p>

        <span v-if="guides.x !== null" class="nb-guide nb-guide--v" :style="{ left: `${guides.x}%` }" aria-hidden="true"></span>
        <span v-if="guides.y !== null" class="nb-guide nb-guide--h" :style="{ top: `${guides.y}%` }" aria-hidden="true"></span>

        <div
            v-for="d in ordered"
            :key="d.id"
            class="nb-card"
            :class="{ 'is-selected': editable && selectedId === d.id }"
            :style="cardStyle(d)"
            role="button"
            tabindex="0"
            :aria-label="t(editable ? 'notebook.drawingBy' : 'notebook.viewDrawingBy', { name: nameOf(d) })"
            @pointerdown="startDrag($event, d)"
            @pointermove="moveDrag"
            @pointerup="endDrag"
            @pointercancel="endDrag"
            @click="onClick(d)"
            @keydown="onKey($event, d)"
        >
            <DrawingThumb :pixels="d.pixels" :colors="d.colors" />
            <span class="nb-card__name">{{ nameOf(d) }}</span>

            <span
                v-if="editable"
                class="nb-card__rotate"
                aria-hidden="true"
                @pointerdown.stop="startSpin($event, d)"
                @pointermove="moveSpin"
                @pointerup="endSpin"
                @pointercancel="endSpin"
                @click.stop
            ></span>
        </div>
    </div>
</template>
