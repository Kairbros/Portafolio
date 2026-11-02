import { ref } from 'vue'
import { useMusicStore } from '@/stores/Store'
import { fileUrl } from '@/utils/media'
import { play as playSfx } from './useSfx'

const DEFAULT_VOLUME = 0.1
const FADE_MS = 1500
const GAP_MS = 25_000
const ANNOUNCE_MS = 7000
const START_DELAY_MS = 20_000

const STORAGE_KEY = 'music:muted'
const VOLUME_KEY = 'music:volume'

const savedMute = localStorage.getItem(STORAGE_KEY)
const muted = ref(savedMute === null ? true : savedMute === '1')

const stored = Number(localStorage.getItem(VOLUME_KEY))
const volume = ref(Number.isFinite(stored) && stored > 0 ? stored : DEFAULT_VOLUME)
const current = ref<any>(null)
const announcing = ref(false)

let store: ReturnType<typeof useMusicStore> | null = null
let audio: HTMLAudioElement | null = null
let started = false
let request = 0
let gapTimer: number | null = null
let announceTimer: number | null = null
let startTimer: number | null = null

function tracks(): any[] {
    return store?.musicTracks ?? []
}

function pick(): any {
    const list = tracks()
    if (!list.length) return null
    if (list.length === 1) return list[0]

    const others = list.filter((t) => t.id !== current.value?.id)
    return others[Math.floor(Math.random() * others.length)]
}

const live = new Set<HTMLAudioElement>()

function release(el: HTMLAudioElement) {
    el.pause()
    el.removeEventListener('timeupdate', onTimeUpdate)
    el.removeEventListener('ended', onEnded)
    live.delete(el)

    if (audio === el) audio = null
}

function reap() {
    for (const el of live) if (el !== audio) release(el)
}

function fadeTo(el: HTMLAudioElement, target: number, ms: number) {
    return new Promise<void>((resolve) => {
        const from = el.volume
        const start = performance.now()

        // rAF se congela con la pestaña en segundo plano, setInterval no
        const timer = window.setInterval(() => {
            const t = Math.min(1, (performance.now() - start) / ms)
            el.volume = Math.max(0, Math.min(1, from + (target - from) * t))

            if (t < 1) return

            window.clearInterval(timer)
            resolve()
        }, 40)
    })
}

function announce(track: any) {
    current.value = track
    announcing.value = true

    playSfx('cassette')

    if (announceTimer) window.clearTimeout(announceTimer)
    announceTimer = window.setTimeout(() => { announcing.value = false }, ANNOUNCE_MS)
}

function scheduleReplay() {
    if (gapTimer) window.clearTimeout(gapTimer)
    gapTimer = window.setTimeout(() => playTrack(pick()), GAP_MS)
}

function onTimeUpdate(this: HTMLAudioElement) {
    if (this !== audio || muted.value) return
    if (!this.duration || Number.isNaN(this.duration)) return

    const left = (this.duration - this.currentTime) * 1000
    if (left <= FADE_MS && tracks().length > 1) playTrack(pick())
}

function onEnded(this: HTMLAudioElement) {
    if (this !== audio) return

    if (tracks().length > 1) playTrack(pick())
    else scheduleReplay()
}

function playTrack(track: any) {
    if (!track || muted.value) return

    reap()

    const token = ++request

    const next = new Audio(fileUrl(track.path))
    next.volume = 0
    next.preload = 'auto'
    next.addEventListener('timeupdate', onTimeUpdate)
    next.addEventListener('ended', onEnded)
    live.add(next)

    next.play()
        .then(() => {
            if (token !== request || muted.value) return release(next)

            announce(track)
            fadeTo(next, volume.value, FADE_MS)
        })
        .catch(() => {
            release(next)
        })

    const previous = audio
    if (previous) fadeTo(previous, 0, FADE_MS).then(() => release(previous))

    audio = next
}

function stop() {
    if (gapTimer) window.clearTimeout(gapTimer)
    if (announceTimer) window.clearTimeout(announceTimer)
    if (startTimer) window.clearTimeout(startTimer)
    gapTimer = null
    announceTimer = null
    startTimer = null

    request++

    reap()

    const leaving = audio
    audio = null
    announcing.value = false

    if (!leaving) return

    fadeTo(leaving, 0, FADE_MS).then(() => release(leaving))
}

export function setVolume(value: number) {
    volume.value = Math.max(0, Math.min(1, value))
    localStorage.setItem(VOLUME_KEY, String(volume.value))

    if (audio) audio.volume = volume.value
}

export async function startMusic(delayMs = START_DELAY_MS) {
    if (started || muted.value) return
    started = true

    store = useMusicStore()
    await store.ensureMusicTracks()

    if (startTimer) window.clearTimeout(startTimer)
    startTimer = window.setTimeout(() => playTrack(pick()), delayMs)
}

export function toggleMusic() {
    muted.value = !muted.value
    localStorage.setItem(STORAGE_KEY, muted.value ? '1' : '0')

    if (muted.value) {
        stop()
        started = false
    } else {
        startMusic(0)
    }
}

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) reap()
})

export function useMusic() {
    return { muted, volume, current, announcing, toggleMusic, startMusic, setVolume }
}
