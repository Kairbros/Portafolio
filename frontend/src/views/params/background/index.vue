<script setup >
import { computed, onMounted, ref } from 'vue'
import { useBackgroundImgStore } from '@/stores/Store'
import { fileUrl } from '@/utils/media'
import Lightbox from '@/components/ui/Lightbox.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const store = useBackgroundImgStore()
const loading = ref(false)

const zoomed = ref('')

const pending = ref(null)
const deleting = ref(false)
const notice = ref('')

async function remove() {
    if (!pending.value) return

    deleting.value = true
    const res = await store.deleteBackgroundImg(pending.value.id)
    deleting.value = false

    notice.value = res.ok ? 'fondo eliminado' : res.message
    pending.value = null

    if (res.ok) await store.getBackgroundImgs()
}

const images = computed(() => store.backgroundImgs ?? [])

function fileName(path) {
    return String(path ?? '').split('/').pop()
}

onMounted(async () => {
    loading.value = true
    await store.getBackgroundImgs()
    loading.value = false
})
</script>

<template>
    <div class="view">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body archive__head">
                <h1 class="t-title fx-glitch" data-text="fondos">Fondos</h1>
                <RouterLink class="btn btn--primary" :to="{ name: 'background_create' }">
                    nuevo
                </RouterLink>
            </div>
        </section>

        <div v-if="loading" class="state">
            <p class="state__glyph fx-flicker">✦</p>
            <p class="loading-dots">cargando</p>
        </div>

        <div v-else-if="store.error" class="state state--bad">
            <p class="state__glyph">✕</p>
            <p>no se pudo leer la galería</p>
            <p class="t-mute">{{ store.error }}</p>
        </div>

        <ul v-else-if="images.length" class="thumb-grid">
            <li v-for="image in images" :key="image.id">
                <article class="thumb">
                    <button
                        type="button"
                        class="thumb__view"
                        :aria-label="`ampliar ${fileName(image.img_path)}`"
                        @click="zoomed = fileUrl(image.img_path)"
                    >
                        <img class="thumb__img" :src="fileUrl(image.img_path)" alt="" loading="lazy">
                    </button>

                    <div class="thumb__foot">
                        <span class="thumb__name">{{ fileName(image.img_path) }}</span>
                        <button
                            type="button"
                            class="thumb__del"
                            :aria-label="`eliminar ${fileName(image.img_path)}`"
                            @click="pending = image"
                        >
                            ✕
                        </button>
                    </div>
                </article>
            </li>
        </ul>

        <div v-else class="state">
            <p class="state__glyph">◌</p>
            <p>galería vacía</p>
            <p class="t-mute">sin fondos, las carpetas se quedan a oscuras.</p>
        </div>

        <p v-if="notice" class="notice view__tail" role="status">▸ {{ notice }}</p>

        <Lightbox
            :model-value="!!zoomed"
            :src="zoomed"
            alt="fondo"
            title="fondo"
            @update:model-value="zoomed = ''"
        />

        <ConfirmDialog
            :model-value="!!pending"
            title="eliminar fondo"
            message="El archivo desaparecerá del servidor y dejará de salir de fondo."
            :target="pending ? fileName(pending.img_path) : ''"
            :busy="deleting"
            @update:model-value="pending = null"
            @confirm="remove"
        />
    </div>
</template>
