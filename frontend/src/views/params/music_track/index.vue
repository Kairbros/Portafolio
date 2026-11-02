<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useMusicStore } from '@/stores/Store'
import { fileUrl } from '@/utils/media'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const store = useMusicStore()
const loading = ref(false)

const pending = ref(null)
const deleting = ref(false)
const notice = ref('')

const playingId = ref(null)
let audio = null

const tracks = computed(() => store.musicTracks ?? [])

function trackName(track) {
    if (track.title) return track.title

    const file = String(track.path ?? '').split('/').pop() ?? ''
    return file
        .replace(/^\d+_/, '')
        .replace(/\.[^.]+$/, '')
        .replace(/[-_]+/g, ' ')
        || 'sin título'
}

function stop() {
    audio?.pause()
    audio = null
    playingId.value = null
}

function toggle(track) {
    if (playingId.value === track.id) return stop()

    stop()
    audio = new Audio(fileUrl(track.path))
    audio.volume = 0.6
    audio.addEventListener('ended', stop)
    audio.play().catch(() => stop())
    playingId.value = track.id
}

async function remove() {
    if (!pending.value) return

    if (playingId.value === pending.value.id) stop()

    deleting.value = true
    const res = await store.deleteMusicTrack(pending.value.id)
    deleting.value = false

    notice.value = res.ok ? 'pista eliminada' : res.message
    pending.value = null

    if (res.ok) await store.getMusicTracks()
}

onMounted(async () => {
    loading.value = true
    await store.getMusicTracks()
    loading.value = false
})

onBeforeUnmount(stop)
</script>

<template>
    <div class="view">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body archive__head">
                <h1 class="t-title fx-glitch" data-text="musica">Musica</h1>
                <RouterLink class="btn btn--primary" :to="{ name: 'music_create' }">
                    nueva
                </RouterLink>
            </div>
        </section>

        <div v-if="loading" class="state">
            <p class="state__glyph fx-flicker">✦</p>
            <p class="loading-dots">cargando</p>
        </div>

        <div v-else-if="store.error" class="state state--bad">
            <p class="state__glyph">✕</p>
            <p>no se pudo leer la cinta</p>
            <p class="t-mute">{{ store.error }}</p>
        </div>

        <ul v-else-if="tracks.length" class="track-list">
            <li v-for="track in tracks" :key="track.id">
                <article class="track" :class="{ 'track--playing': playingId === track.id }">
                    <button
                        type="button"
                        class="track__art"
                        :aria-label="playingId === track.id ? 'pausar' : `reproducir ${trackName(track)}`"
                        @click="toggle(track)"
                    >
                        <img
                            v-if="track.artwork_path"
                            class="track__img"
                            :src="fileUrl(track.artwork_path)"
                            alt=""
                            loading="lazy"
                        >
                        <span class="track__state" aria-hidden="true">
                            {{ playingId === track.id ? '❚❚' : '▶' }}
                        </span>
                    </button>

                    <div class="track__text">
                        <h2 class="track__name">{{ trackName(track) }}</h2>
                        <p class="track__artist">{{ track.artist || 'artista desconocido' }}</p>
                        <p class="track__file t-mute">{{ track.path }}</p>
                    </div>

                    <button type="button" class="btn btn--danger" @click="pending = track">
                        borrar
                    </button>
                </article>
            </li>
        </ul>

        <div v-else class="state">
            <p class="state__glyph">◌</p>
            <p>sin pistas</p>
            <p class="t-mute">nada que poner en el radiocassette todavía.</p>
        </div>

        <p v-if="notice" class="notice view__tail" role="status">▸ {{ notice }}</p>

        <ConfirmDialog
            :model-value="!!pending"
            title="eliminar pista"
            message="El audio y su carátula desaparecerán del servidor."
            :target="pending ? trackName(pending) : ''"
            :busy="deleting"
            @update:model-value="pending = null"
            @confirm="remove"
        />
    </div>
</template>

<style scoped>
.track-list {
    display: grid;
    gap: var(--sp-2);
}

.track {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: var(--sp-4);
    padding: var(--sp-3) var(--sp-4);
    background: var(--panel);
    border: var(--bd);
    border-left: 6px solid var(--rot);
    transition: border-color var(--t-snap), background var(--t-snap);
}

.track:hover {
    background: var(--panel-hi);
    border-left-color: var(--pink);
}

.track--playing {
    border-color: var(--pink-deep);
    border-left-color: var(--acid);
}

.track__art {
    position: relative;
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    overflow: hidden;
    background: var(--panel-lo);
    border: 2px solid var(--line);
    cursor: pointer;
}

.track__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.track__state {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    background: rgba(7, 5, 12, 0.6);
    color: var(--bone);
    opacity: 0;
    transition: opacity var(--t-snap);
}

.track__art:hover .track__state,
.track__art:focus-visible .track__state,
.track--playing .track__state {
    opacity: 1;
}

.track--playing .track__state {
    color: var(--acid);
}

.track__name {
    font-family: var(--font-term);
    font-size: var(--fs-lg);
    letter-spacing: var(--tracking);
    color: var(--bone);
    overflow-wrap: anywhere;
}

.track__artist {
    font-size: var(--fs-sm);
    color: var(--fg-soft);
    overflow-wrap: anywhere;
}

.track__file {
    font-size: var(--fs-xs);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 560px) {
    .track {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .track .btn {
        grid-column: 1 / -1;
        justify-content: center;
    }
}
</style>
