import { computed, ref } from 'vue'

// Pantalla de carga con tiempo real: está visible sólo mientras hay trabajo
// pendiente (cambio de ruta o pedidos de datos) y el número cuenta pedidos
// terminados sobre pedidos hechos. Si todo termina antes de SHOW_DELAY_MS no
// llega a aparecer, para no parpadear cuando los datos ya están en memoria.

const SHOW_DELAY_MS = 150

const pending = ref(0)
const started = ref(0)
const finished = ref(0)

// al abrir la web se muestra desde el primer instante
const visible = ref(true)

let showTimer: number | null = null

function clearShow() {
    if (showTimer) window.clearTimeout(showTimer)
    showTimer = null
}

export function begin() {
    if (pending.value === 0) {
        started.value = 0
        finished.value = 0
    }

    pending.value++
    started.value++

    if (!visible.value && !showTimer) {
        showTimer = window.setTimeout(() => {
            showTimer = null
            if (pending.value > 0) visible.value = true
        }, SHOW_DELAY_MS)
    }
}

export function end() {
    if (pending.value === 0) return

    pending.value--
    finished.value++

    if (pending.value === 0) {
        clearShow()
        visible.value = false
    }
}

export async function track<T>(work: Promise<T>): Promise<T> {
    begin()
    try {
        return await work
    } finally {
        end()
    }
}

export function useLoader() {
    return {
        visible,
        progress: computed(() =>
            started.value ? Math.round((finished.value / started.value) * 100) : 0,
        ),
    }
}
