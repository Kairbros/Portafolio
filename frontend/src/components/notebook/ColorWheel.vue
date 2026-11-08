<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { hexToHsv, hsvToHex, hsvToRgb } from '@/utils/color'

const model = defineModel<string>({ required: true })

const { t } = useI18n()

const RES = 48
const INNER = 0.42
const GAP = 0.08
const VOID: [number, number, number] = [7, 5, 12]

const canvas = ref<HTMLCanvasElement | null>(null)
const hue = ref(0)
const sat = ref(0)
const val = ref(1)
let dragging = false

const mark = computed(() => {
    const angle = (hue.value * Math.PI) / 180
    const radius = INNER + sat.value * (1 - INNER)
    return {
        left: `${50 + Math.cos(angle) * radius * 50}%`,
        top: `${50 + Math.sin(angle) * radius * 50}%`,
    }
})

function hexToRgb(hex: string): [number, number, number] {
    const n = parseInt(hex.slice(1), 16)
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255]
}

const track = computed(() => `linear-gradient(to right, #000, ${hsvToHex(hue.value, sat.value, 1)})`)

function sync(hex: string) {
    if (hex === hsvToHex(hue.value, sat.value, val.value)) return
    const hsv = hexToHsv(hex)
    if (hsv.v > 0 && hsv.s > 0) hue.value = hsv.h
    if (hsv.v > 0) sat.value = hsv.s
    val.value = hsv.v
}

function draw() {
    const ctx = canvas.value?.getContext('2d')
    if (!ctx) return
    const img = ctx.createImageData(RES, RES)
    const c = RES / 2
    const hole = hexToRgb(model.value)

    for (let y = 0; y < RES; y++) {
        for (let x = 0; x < RES; x++) {
            const dx = x + 0.5 - c
            const dy = y + 0.5 - c
            const r = Math.hypot(dx, dy) / c
            if (r > 1) continue
            const h = ((Math.atan2(dy, dx) * 180) / Math.PI + 360) % 360
            const [red, green, blue] =
                r < INNER - GAP ? hole : r < INNER ? VOID : hsvToRgb(h, (r - INNER) / (1 - INNER), val.value)
            const i = (y * RES + x) * 4
            img.data[i] = red
            img.data[i + 1] = green
            img.data[i + 2] = blue
            img.data[i + 3] = 255
        }
    }

    ctx.putImageData(img, 0, 0)
}

function emit() {
    model.value = hsvToHex(hue.value, sat.value, val.value)
}

function pickAt(e: PointerEvent, start = false): boolean {
    const box = canvas.value!.getBoundingClientRect()
    const dx = ((e.clientX - box.left) / box.width) * 2 - 1
    const dy = ((e.clientY - box.top) / box.height) * 2 - 1
    const r = Math.hypot(dx, dy)
    if (start && (r < INNER - GAP || r > 1)) return false
    hue.value = ((Math.atan2(dy, dx) * 180) / Math.PI + 360) % 360
    sat.value = Math.max(0, Math.min(1, (r - INNER) / (1 - INNER)))
    if (val.value < 0.2) val.value = 1
    emit()
    return true
}

function down(e: PointerEvent) {
    const disc = e.currentTarget as HTMLElement
    if (!pickAt(e, true)) return
    dragging = true
    disc.setPointerCapture(e.pointerId)
}

function move(e: PointerEvent) {
    if (dragging) pickAt(e)
}

function up() {
    dragging = false
}

function key(e: KeyboardEvent) {
    const steps: Record<string, [number, number]> = {
        ArrowLeft: [-10, 0],
        ArrowRight: [10, 0],
        ArrowUp: [0, 0.1],
        ArrowDown: [0, -0.1],
    }
    const step = steps[e.key]
    if (!step) return
    e.preventDefault()
    hue.value = (hue.value + step[0] + 360) % 360
    sat.value = Math.max(0, Math.min(1, sat.value + step[1]))
    emit()
}

function setValue(e: Event) {
    val.value = Number((e.target as HTMLInputElement).value) / 100
    emit()
}

watch(
    model,
    (hex) => {
        sync(hex)
        draw()
    },
    { immediate: true },
)
onMounted(draw)
</script>

<template>
    <div class="nb-wheel">
        <div
            class="nb-wheel__disc"
            tabindex="0"
            role="group"
            :aria-label="t('notebook.wheel')"
            @pointerdown="down"
            @pointermove="move"
            @pointerup="up"
            @pointercancel="up"
            @keydown="key"
        >
            <canvas ref="canvas" class="nb-wheel__canvas" :width="RES" :height="RES" aria-hidden="true"></canvas>
            <span class="nb-wheel__mark" :style="{ ...mark, background: model }" aria-hidden="true"></span>
        </div>

        <input
            class="nb-wheel__value"
            type="range"
            min="0"
            max="100"
            :value="Math.round(val * 100)"
            :style="{ background: track }"
            :aria-label="t('notebook.brightness')"
            @input="setValue"
        >
    </div>
</template>
