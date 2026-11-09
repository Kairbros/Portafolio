<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useMusicStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { fileUrl } from '@/utils/media'

const props = defineProps<{ id?: string }>()

const router = useRouter()
const store = useMusicStore()

const title = ref('')
const artist = ref('')
const music = ref<File | null>(null)
const musicPreview = ref('')
const artwork = ref<File | null>(null)
const artPreview = ref('')
const size = ref({ width: 0, height: 0 })
const isSquare = ref(true)
const currentArt = ref('')
const currentAudio = ref('')
const missing = ref(false)

const shownArt = computed(() => artPreview.value || currentArt.value)
const shownAudio = computed(() => musicPreview.value || currentAudio.value)
const ready = computed(() =>
    isSquare.value
    && !!title.value.trim()
    && !!artist.value.trim()
    && (!!props.id || (!!music.value && !!artwork.value)),
)

const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

function revoke(url: string) {
    if (url) URL.revokeObjectURL(url)
}

function onMusicChange(event: Event) {
    revoke(musicPreview.value)
    music.value = (event.target as HTMLInputElement).files?.[0] ?? null
    musicPreview.value = music.value ? URL.createObjectURL(music.value) : ''
}

function onArtworkChange(event: Event) {
    revoke(artPreview.value)
    artwork.value = (event.target as HTMLInputElement).files?.[0] ?? null
    isSquare.value = true

    if (!artwork.value) {
        artPreview.value = ''
        return
    }

    artPreview.value = URL.createObjectURL(artwork.value)

    const probe = new Image()
    probe.onload = () => {
        size.value = { width: probe.width, height: probe.height }
        isSquare.value = probe.width === probe.height
    }
    probe.src = artPreview.value
}

onMounted(async () => {
    if (!props.id) return
    await track(store.ensureMusicTracks())
    const item = store.musicTracks.find((t: any) => String(t.id) === props.id)
    if (!item) {
        missing.value = true
        return
    }
    title.value = item.title ?? ''
    artist.value = item.artist ?? ''
    currentArt.value = item.artwork_path ? fileUrl(item.artwork_path) : ''
    currentAudio.value = item.path ? fileUrl(item.path) : ''
})

onBeforeUnmount(() => {
    revoke(musicPreview.value)
    revoke(artPreview.value)
})

async function save() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const data = new FormData()
    data.append('title', title.value)
    data.append('artist', artist.value)
    if (music.value) data.append('music', music.value)
    if (artwork.value) data.append('img', artwork.value)

    const res = props.id
        ? await store.updateMusicTrack(props.id, data)
        : await store.storeMusicTrack(data)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    router.push({ name: 'music' })
}
</script>

<template>
    <p v-if="missing" class="dd-state dd-state--bad">
        no existe
        <RouterLink class="dd-state__detail" :to="{ name: 'music' }">volver</RouterLink>
    </p>

    <form v-else class="dd-form" @submit.prevent="save">
        <section class="dd-win">
            <h1 class="dd-win__bar">{{ id ? 'editar canción' : 'subir canción' }}</h1>

            <div class="dd-form__fields">
                <div class="dd-field">
                    <label class="dd-field__label" for="mt-title">título</label>
                    <input id="mt-title" v-model="title" type="text" class="dd-input" autocomplete="off">
                    <span v-if="errors.title" class="dd-field__error">{{ errors.title[0] }}</span>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="mt-artist">artista</label>
                    <input id="mt-artist" v-model="artist" type="text" class="dd-input" autocomplete="off">
                    <span v-if="errors.artist" class="dd-field__error">{{ errors.artist[0] }}</span>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="mt-audio">audio</label>
                    <input id="mt-audio" type="file" accept="audio/mpeg,audio/wav,.mp3,.wav" class="dd-input" @change="onMusicChange">
                    <span v-if="errors.music" class="dd-field__error">{{ errors.music[0] }}</span>
                    <span v-else class="dd-field__hint">mp3 o wav, hasta 125 MB</span>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="mt-art">carátula</label>
                    <input id="mt-art" type="file" accept="image/jpeg,image/png,image/gif" class="dd-input" @change="onArtworkChange">
                    <span v-if="errors.img" class="dd-field__error">{{ errors.img[0] }}</span>
                    <span v-else-if="!isSquare" class="dd-field__error">
                        tiene que ser cuadrada, esta es de {{ size.width }} por {{ size.height }}
                    </span>
                    <span v-else class="dd-field__hint">cuadrada; jpg, png o gif, hasta 32 MB</span>
                </div>
            </div>

            <div class="dd-form__foot">
                <p v-if="message" class="dd-form__msg" :class="{ 'dd-form__msg--bad': failed }" role="status">
                    {{ message }}
                </p>
                <RouterLink class="dd-btn" :to="{ name: 'music' }">cancelar</RouterLink>
                <button
                    class="dd-btn dd-btn--tone"
                    type="submit"
                    :disabled="sending || !ready"
                >
                    {{ sending ? 'guardando' : 'guardar' }}
                </button>
            </div>
        </section>

        <section class="dd-win" aria-label="Vista previa">
            <div class="dd-win__bar">vista previa</div>
            <img v-if="shownArt" class="dd-preview-img" :src="shownArt" alt="">
            <div v-else class="dd-preview-img dd-scan"></div>
            <div class="dd-form__preview">
                <span>{{ title || 'sin título' }}</span>
                <span class="dd-small">{{ artist || 'sin artista' }}</span>
                <audio v-if="shownAudio" class="dd-audio" :src="shownAudio" controls preload="metadata"></audio>
            </div>
        </section>
    </form>
</template>
