import { computed, ref } from 'vue'
import { useMusicStore } from '@/stores/Store'
import { fileUrl } from '@/utils/media'
import { play as playSfx } from './useSfx'

const DEFAULT_VOLUME = 0.1
const FADE_MS = 1500
const GAP_MS = 25_000
const ANNOUNCE_MS = 7000
const START_DELAY_MS = 20_000

const RESTART_AFTER_S = 3
const HISTORY_LIMIT = 50

const STORAGE_KEY = 'music:muted'
const VOLUME_KEY = 'music:volume'

const savedMute = localStorage.getItem(STORAGE_KEY)
const muted = ref(savedMute === null ? true : savedMute === '1')

const stored = Number(localStorage.getItem(VOLUME_KEY))
const volume = ref(Number.isFinite(stored) && stored > 0 ? stored : DEFAULT_VOLUME)
const current = ref<any>(null)
const announcing = ref(false)
const playing = ref(false)
const position = ref(0)
const duration = ref(0)
const playlist = computed<any[]>(() => useMusicStore().musicTracks ?? [])

let store: ReturnType<typeof useMusicStore> | null = null
let audio: HTMLAudioElement | null = null
let started = false
let request = 0
let gapTimer: number | null = null
let announceTimer: number | null = null
let startTimer: number | null = null
let history: any[] = []
let cursor = -1

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

function remember(track: any) {
    history = history.slice(0, cursor + 1)
    history.push(track)
    if (history.length > HISTORY_LIMIT) history.shift()
    cursor = history.length - 1
}

function advance() {
    if (cursor < history.length - 1) {
        cursor++
        playTrack(history[cursor])
        return
    }

    const track = pick()
    if (!track) return
    remember(track)
    playTrack(track)
}

const live = new Set<HTMLAudioElement>()

function release(el: HTMLAudioElement) {
    el.pause()
    el.removeEventListener('timeupdate', onTimeUpdate)
    el.removeEventListener('ended', onEnded)
    el.removeEventListener('play', onPlayState)
    el.removeEventListener('pause', onPlayState)
    live.delete(el)

    if (audio === el) {
        audio = null
        playing.value = false
    }
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
    describeSession(track)

    playSfx('cassette')

    if (announceTimer) window.clearTimeout(announceTimer)
    announceTimer = window.setTimeout(() => { announcing.value = false }, ANNOUNCE_MS)
}

function scheduleReplay() {
    if (gapTimer) window.clearTimeout(gapTimer)
    gapTimer = window.setTimeout(advance, GAP_MS)
}

function onTimeUpdate(this: HTMLAudioElement) {
    if (this !== audio) return

    position.value = this.currentTime
    if (Number.isFinite(this.duration)) duration.value = this.duration

    if (muted.value || this.paused) return
    if (!this.duration || Number.isNaN(this.duration)) return

    const left = (this.duration - this.currentTime) * 1000
    if (left <= FADE_MS && tracks().length > 1) advance()
}

function onEnded(this: HTMLAudioElement) {
    if (this !== audio) return

    if (tracks().length > 1) advance()
    else scheduleReplay()
}

function onPlayState(this: HTMLAudioElement) {
    if (this !== audio) return

    playing.value = !this.paused
    if ('mediaSession' in navigator) navigator.mediaSession.playbackState = this.paused ? 'paused' : 'playing'
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
    next.addEventListener('play', onPlayState)
    next.addEventListener('pause', onPlayState)
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
    position.value = 0
    duration.value = 0
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
    playing.value = false
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
    startTimer = window.setTimeout(advance, delayMs)
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

async function ensureOn() {
    if (muted.value) {
        muted.value = false
        localStorage.setItem(STORAGE_KEY, '0')
    }

    started = true
    if (gapTimer) window.clearTimeout(gapTimer)
    if (startTimer) window.clearTimeout(startTimer)
    gapTimer = null
    startTimer = null

    store = useMusicStore()
    await store.ensureMusicTracks()
}

export async function togglePlay() {
    if (!muted.value && audio) {
        if (gapTimer) window.clearTimeout(gapTimer)
        gapTimer = null

        if (audio.paused) audio.play().catch(() => {})
        else audio.pause()
        return
    }

    await ensureOn()
    advance()
}

export async function nextTrack() {
    await ensureOn()
    advance()
}

export async function previousTrack() {
    await ensureOn()

    if (audio && (audio.currentTime > RESTART_AFTER_S || cursor <= 0)) {
        seek(0)
        return
    }
    if (cursor <= 0) {
        advance()
        return
    }

    cursor--
    playTrack(history[cursor])
}

export async function chooseTrack(track: any) {
    if (audio && !muted.value && current.value?.id === track.id) return togglePlay()

    await ensureOn()
    remember(track)
    playTrack(track)
}

export function seek(seconds: number) {
    if (!audio) return

    audio.currentTime = seconds
    position.value = seconds
}

export function loadTracks() {
    return useMusicStore().ensureMusicTracks()
}

function describeSession(track: any) {
    if (!('mediaSession' in navigator)) return

    navigator.mediaSession.metadata = new MediaMetadata({
        title: track.title ?? '',
        artist: track.artist ?? '',
        artwork: track.artwork_path ? [{ src: fileUrl(track.artwork_path) }] : [],
    })
}

if ('mediaSession' in navigator) {
    navigator.mediaSession.setActionHandler('play', () => { if (!playing.value) togglePlay() })
    navigator.mediaSession.setActionHandler('pause', () => { if (playing.value) togglePlay() })
    navigator.mediaSession.setActionHandler('previoustrack', previousTrack)
    navigator.mediaSession.setActionHandler('nexttrack', nextTrack)
}

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) reap()
})

export function useMusic() {
    return {
        muted,
        volume,
        current,
        announcing,
        playing,
        position,
        duration,
        playlist,
        toggleMusic,
        startMusic,
        setVolume,
        togglePlay,
        nextTrack,
        previousTrack,
        chooseTrack,
        seek,
        loadTracks,
    }
}
