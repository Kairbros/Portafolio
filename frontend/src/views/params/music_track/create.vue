<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { useMusicStore } from '@/stores/Store'
import router from '@/router'

const store = useMusicStore()

const title = ref('')
const artist = ref('')

const music = ref<File | null>(null)
const musicPreview = ref('')

const artwork = ref<File | null>(null)
const artPreview = ref('')
const size = ref({ width: 0, height: 0 })
const isSquare = ref(true)

const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

function revoke(url: string) {
    if (url) URL.revokeObjectURL(url)
}

function onMusicChange(event: Event) {
    const picked = (event.target as HTMLInputElement).files?.[0] ?? null

    revoke(musicPreview.value)
    music.value = picked
    musicPreview.value = picked ? URL.createObjectURL(picked) : ''
}

function onArtworkChange(event: Event) {
    const picked = (event.target as HTMLInputElement).files?.[0] ?? null

    revoke(artPreview.value)
    artwork.value = picked
    size.value = { width: 0, height: 0 }
    isSquare.value = true

    if (!picked) {
        artPreview.value = ''
        return
    }

    artPreview.value = URL.createObjectURL(picked)

    const probe = new Image()
    probe.onload = () => {
        size.value = { width: probe.width, height: probe.height }
        isSquare.value = probe.width === probe.height
    }
    probe.src = artPreview.value
}

onBeforeUnmount(() => {
    revoke(musicPreview.value)
    revoke(artPreview.value)
})

async function upload() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const formData = new FormData()
    formData.append('title', title.value)
    formData.append('artist', artist.value)
    if (music.value) formData.append('music', music.value)
    if (artwork.value) formData.append('img', artwork.value)

    const res = await store.storeMusicTrack(formData)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    message.value = res.data?.message ?? 'guardado'
    await store.getMusicTracks()
    setTimeout(() => router.push({ name: 'music' }), 900)
}
</script>

<template>
    <form class="forge" @submit.prevent="upload">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body forge__body">
                <header>
                    <h1 class="t-heading">subir pista</h1>
                    <p class="t-mute">sonará de fondo mientras se pasea por el cuarto.</p>
                </header>

                <div class="forge__fields">
                    <div class="field">
                        <label class="field__label" for="mt-title">título</label>
                        <input
                            id="mt-title"
                            v-model="title"
                            type="text"
                            class="input"
                            placeholder="ej. Petal Dance"
                            autocomplete="off"
                        >
                        <span v-if="errors.title" class="field__error">{{ errors.title[0] }}</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="mt-artist">artista</label>
                        <input
                            id="mt-artist"
                            v-model="artist"
                            type="text"
                            class="input"
                            placeholder="ej. Toby Fox"
                            autocomplete="off"
                        >
                        <span v-if="errors.artist" class="field__error">{{ errors.artist[0] }}</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="mt-audio">audio</label>
                        <input
                            id="mt-audio"
                            type="file"
                            accept="audio/mpeg,audio/wav,.mp3,.wav"
                            class="input input--file"
                            @change="onMusicChange"
                        >
                        <span v-if="errors.music" class="field__error">{{ errors.music[0] }}</span>
                        <span v-else class="field__hint">mp3 o wav, hasta 125 MB</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="mt-art">carátula</label>
                        <input
                            id="mt-art"
                            type="file"
                            accept="image/jpeg,image/png,image/gif"
                            class="input input--file"
                            @change="onArtworkChange"
                        >
                        <span v-if="errors.img" class="field__error">{{ errors.img[0] }}</span>
                        <span v-else-if="!isSquare" class="field__error">
                            tiene que ser cuadrada. Esta es {{ size.width }}×{{ size.height }}.
                        </span>
                        <span v-else class="field__hint">jpg, png o gif, cuadrada, hasta 32 MB</span>
                    </div>

                </div>

                <footer class="forge__foot">
                    <RouterLink class="btn btn--ghost" :to="{ name: 'music' }">cancelar</RouterLink>
                    <button
                        class="btn btn--primary"
                        type="submit"
                        :disabled="sending || !music || !artwork || !isSquare || !title.trim() || !artist.trim()"
                    >
                        <span v-if="sending" class="loading-dots">subiendo</span>
                        <span v-else>guardar</span>
                    </button>
                </footer>

                <p
                    v-if="message"
                    class="forge__msg"
                    :class="{ 'forge__msg--bad': failed }"
                    role="status"
                >
                    {{ failed ? '✕' : '▸' }} {{ message }}
                </p>
            </div>
        </section>

        <aside class="forge__side">
            <section class="panel">
                <header class="panel__head"></header>

                <div class="panel__body">
                    <div class="tape__body cassette">
                        <div class="tape__art">
                            <img v-if="artPreview" class="tape__img" :src="artPreview" alt="">
                            <span v-else class="tape__art-empty" aria-hidden="true">♪</span>
                        </div>

                        <div class="tape__text">
                            <p class="tape__title">{{ title || 'sin título' }}</p>
                            <p class="tape__artist">{{ artist || 'artista desconocido' }}</p>
                        </div>
                    </div>

                    <div v-if="!isSquare" class="cassette__warn">
                        ✕ la carátula tiene que ser cuadrada
                    </div>
                </div>
            </section>

            <section v-if="musicPreview"  class="panel">
                <header class="panel__head"></header>
                <div class="panel__body">
                    <audio
                        class="forge__audio"
                        :src="musicPreview"
                        controls
                        preload="metadata"
                    ></audio>
                </div>
            </section>

        </aside>
    </form>
</template>

<style scoped>
.cassette {
    padding: var(--sp-3);
}

.cassette__warn {
    margin-top: var(--sp-3);
    font-family: var(--font-term);
    font-size: var(--fs-xs);
    color: var(--pink);
}
</style>
