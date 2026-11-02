import { onUnmounted, ref, watch, type Ref } from 'vue'

type Rotator = { layers: Ref<[string, string]>; active: Ref<number> }

const shared = new Map<string, Rotator>()

function stateFor(key?: string): Rotator {
    if (!key) return { layers: ref<[string, string]>(['', '']), active: ref(0) }

    let state = shared.get(key)
    if (!state) {
        state = { layers: ref<[string, string]>(['', '']), active: ref(0) }
        shared.set(key, state)
    }
    return state
}

export function useImageRotator(images: Ref<string[]>, intervalMs = 15_000, key?: string) {
    const { layers, active } = stateFor(key)
    let timer: number | null = null

    function pick(): string {
        const list = images.value
        if (!list.length) return ''
        if (list.length === 1) return list[0]!

        const current = layers.value[active.value]
        const others = list.filter((url) => url !== current)
        return others[Math.floor(Math.random() * others.length)]!
    }

    function preload(url: string) {
        return new Promise<void>((resolve) => {
            const img = new Image()
            img.onload = () => resolve()
            img.onerror = () => resolve()
            img.src = url
        })
    }

    async function swap() {
        const next = pick()
        if (!next) return

        const idle = active.value === 0 ? 1 : 0
        await preload(next)

        layers.value[idle] = next
        active.value = idle
    }

    function stop() {
        if (timer) window.clearInterval(timer)
        timer = null
    }

    async function start() {
        stop()

        if (!images.value.length) {
            layers.value = ['', '']
            return
        }

        const current = layers.value[active.value]
        if (current && !images.value.includes(current)) {
            layers.value[active.value] = ''
        }

        if (!layers.value[active.value]) {
            const first = pick()
            if (!first) return
            await preload(first)
            layers.value[active.value] = first
        }

        if (images.value.length > 1) {
            timer = window.setInterval(swap, intervalMs)
        }
    }

    watch(images, start, { immediate: true })
    onUnmounted(stop)

    return { layers, active }
}
