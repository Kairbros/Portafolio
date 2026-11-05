<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useMusicStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { fileUrl } from '@/utils/media'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import PencilIcon from '~icons/pixelarticons/pencil'

const store = useMusicStore()

const pending = ref(null)
const deleting = ref(false)
const notice = ref('')
const playingId = ref(null)
let audio = null

const tracks = computed(() => store.musicTracks ?? [])

function trackName(item) {
    if (item.title) return item.title
    const file = String(item.path ?? '').split('/').pop() ?? ''
    return file.replace(/^\d+_/, '').replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ') || 'sin título'
}

function stop() {
    audio?.pause()
    audio = null
    playingId.value = null
}

function toggle(item) {
    if (playingId.value === item.id) return stop()

    stop()
    audio = new Audio(fileUrl(item.path))
    audio.volume = 0.6
    audio.addEventListener('ended', stop)
    audio.play().catch(stop)
    playingId.value = item.id
}

async function remove() {
    if (!pending.value) return
    if (playingId.value === pending.value.id) stop()

    deleting.value = true
    const res = await store.deleteMusicTrack(pending.value.id)
    deleting.value = false

    notice.value = res.ok ? 'borrada' : res.message
    pending.value = null

    if (res.ok) await track(store.getMusicTracks())
}

onMounted(() => track(store.getMusicTracks()))
onBeforeUnmount(stop)
</script>

<template>
    <div>
        <div class="dd-heading">
            <h1 class="dd-title">música</h1>
            <RouterLink class="dd-btn dd-btn--tone" :to="{ name: 'music_create' }">subir</RouterLink>
        </div>

        <p v-if="store.error" class="dd-state dd-state--bad">
            no se pudo leer
            <span class="dd-state__detail">{{ store.error }}</span>
        </p>

        <section v-else-if="tracks.length" class="dd-win">
            <div class="dd-win__bar">{{ tracks.length }} en total</div>
            <ul class="dd-rows">
                <li v-for="item in tracks" :key="item.id" class="dd-row">
                    <img
                        v-if="item.artwork_path"
                        class="dd-row__thumb"
                        :src="fileUrl(item.artwork_path)"
                        alt=""
                        loading="lazy"
                    >
                    <span v-else class="dd-row__thumb dd-scan" aria-hidden="true"></span>

                    <div class="dd-row__text">
                        <span class="dd-row__name">{{ trackName(item) }}</span>
                        <span class="dd-row__meta">{{ item.artist || 'sin artista' }}</span>
                    </div>

                    <div class="dd-row__actions">
                        <RouterLink class="dd-row__edit" :to="{ name: 'music_edit', params: { id: item.id } }" aria-label="Editar" title="editar">
                            <PencilIcon aria-hidden="true" />
                        </RouterLink>
                        <button
                            type="button"
                            class="dd-btn"
                            :class="{ 'dd-btn--tone': playingId === item.id }"
                            :aria-pressed="playingId === item.id"
                            @click="toggle(item)"
                        >
                            {{ playingId === item.id ? 'parar' : 'oír' }}
                        </button>
                        <button type="button" class="dd-link-btn" @click="pending = item">borrar</button>
                    </div>
                </li>
            </ul>
        </section>

        <p v-else-if="store.loaded" class="dd-state">vacío</p>

        <p v-if="notice" class="dd-notice" role="status">{{ notice }}</p>

        <ConfirmDialog
            :model-value="!!pending"
            title="borrar canción"
            :target="pending ? trackName(pending) : ''"
            :busy="deleting"
            @update:model-value="pending = null"
            @confirm="remove"
        />
    </div>
</template>
