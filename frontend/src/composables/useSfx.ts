import { ref } from 'vue'

const SOURCES = {
    hover: '/sfx/hover.wav',
    click: '/sfx/click.wav',
    close: '/sfx/close.wav',
    cassette: '/sfx/cassette.wav',
} as const

export type SfxName = keyof typeof SOURCES

const STORAGE_KEY = 'sfx:muted'
const HOVER_THROTTLE_MS = 60

const muted = ref(localStorage.getItem(STORAGE_KEY) === '1')
const volume = ref(0.025)

const buffers = new Map<SfxName, HTMLAudioElement>()
let lastHover = 0

function load(name: SfxName): HTMLAudioElement {
    if (buffers.has(name)) return buffers.get(name)!

    const audio = new Audio(SOURCES[name])
    audio.preload = 'auto'
    audio.load()
    buffers.set(name, audio)
    return audio
}

for (const name of Object.keys(SOURCES) as SfxName[]) load(name)

export function play(name: SfxName) {
    if (muted.value) return

    if (name === 'hover') {
        const now = performance.now()
        if (now - lastHover < HOVER_THROTTLE_MS) return
        lastHover = now
    }

    const source = load(name)

    const shot = source.cloneNode() as HTMLAudioElement
    shot.volume = volume.value
    shot.currentTime = 0
    shot.play().catch(() => {})
}

export function toggleMute() {
    muted.value = !muted.value
    localStorage.setItem(STORAGE_KEY, muted.value ? '1' : '0')
}

export function useSfx() {
    return { play, muted, volume, toggleMute }
}
