<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import DrawingThumb from '@/components/notebook/DrawingThumb.vue'
import type { Drawing } from '@/stores/drawings'

const props = withDefaults(defineProps<{
    drawings: Drawing[]
    editable?: boolean
    selectedId?: number | null
}>(), { editable: false, selectedId: null })

const emit = defineEmits<{
    open: [drawing: Drawing]
    select: [drawing: Drawing]
    // al soltar una tarjeta movida o girada
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
let drag: { d: Drawing; card: HTMLElement; px: number; py: number; x: number; y: number; z: number; w: number; h: number } | null = null

function startDrag(e: PointerEvent, d: Drawing) {
    if (!props.editable) return
    const card = e.currentTarget as HTMLElement
    const sheet = card.parentElement!.getBoundingClientRect()
    const z = d.z
    // tocar una tarjeta la sube al frente
    if (props.drawings.some((other) => other !== d && other.z >= d.z)) d.z = topZ() + 1
    emit('select', d)
    drag = { d, card, px: e.clientX, py: e.clientY, x: d.x, y: d.y, z, w: sheet.width, h: sheet.height }
    card.setPointerCapture(e.pointerId)
}

function moveDrag(e: PointerEvent) {
    if (!drag) return
    const maxX = 100 - (drag.card.offsetWidth / drag.w) * 100
    const maxY = 100 - (drag.card.offsetHeight / drag.h) * 100
    drag.d.x = round(Math.max(0, Math.min(maxX, drag.x + ((e.clientX - drag.px) / drag.w) * 100)))
    drag.d.y = round(Math.max(0, Math.min(maxY, drag.y + ((e.clientY - drag.py) / drag.h) * 100)))
}

function endDrag() {
    if (!drag) return
    const { d, x, y, z } = drag
    drag = null
    // un clic sin mover no guarda nada
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
    // con shift salta de 15 en 15
    rotation = e.shiftKey ? Math.round(rotation / 15) * 15 : Math.round(rotation)
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
        d.x = round(Math.max(0, Math.min(82, d.x + moves[e.key]![0])))
        d.y = round(Math.max(0, Math.min(70, d.y + moves[e.key]![1])))
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
    <div class="nb-sheet" :class="{ 'nb-sheet--board': editable }">
        <p v-if="!drawings.length" class="nb-sheet__empty">{{ t('notebook.empty') }}</p>

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
            <DrawingThumb :pixels="d.pixels" />
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
