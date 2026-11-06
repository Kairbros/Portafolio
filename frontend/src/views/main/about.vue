<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAboutStore, useStickerStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { fileUrl } from '@/utils/media'
import { localized } from '@/i18n'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const stickerStore = useStickerStore()
const aboutStore = useAboutStore()

// posiciones fijas del collage; si hay más stickers se repiten
const SPOTS = [
    { left: '4%', top: '3%', width: '58%', tilt: -6 },
    { left: '50%', top: '18%', width: '46%', tilt: 7 },
    { left: '14%', top: '46%', width: '72%', tilt: -3 },
    { left: '56%', top: '58%', width: '40%', tilt: 5 },
]

const stickers = computed(() =>
    (stickerStore.stickers ?? [])
        .map((item: any, i: number) => {
            const spot = SPOTS[i % SPOTS.length]!
            return {
                id: item.id,
                src: fileUrl(item.img_path),
                style: {
                    left: spot.left,
                    top: spot.top,
                    width: spot.width,
                    transform: `rotate(${spot.tilt}deg)`,
                    zIndex: i + 1,
                },
            }
        })
        .filter((s) => s.src),
)

// un párrafo por cada bloque separado por una línea vacía
const paragraphs = computed(() =>
    localized(aboutStore, 'body')
        .split(/\n\s*\n/)
        .map((p) => p.trim())
        .filter(Boolean),
)

onMounted(() => track(Promise.all([aboutStore.ensureAbout(), stickerStore.ensureStickers()])))
</script>

<template>
    <article class="about">
        <div class="about__text">
            <h1 class="dd-title">{{ t('about.title') }}</h1>

            <p v-if="aboutStore.error" class="dd-state dd-state--bad">
                {{ t('state.error') }}
                <span class="dd-state__detail">{{ aboutStore.error }}</span>
            </p>

            <div v-else class="dd-prose">
                <p v-for="(paragraph, i) in paragraphs" :key="i">{{ paragraph }}</p>
            </div>
        </div>

        <section v-if="stickers.length" class="dd-win about__board" :aria-label="t('about.drawings')">
            <div class="dd-win__bar">{{ t('about.drawings') }}</div>
            <div class="about__collage">
                <img
                    v-for="sticker in stickers"
                    :key="sticker.id"
                    class="about__sticker"
                    :src="sticker.src"
                    :style="sticker.style"
                    alt=""
                    loading="lazy"
                >
            </div>
        </section>
    </article>
</template>

<style scoped>
.about {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 260px;
    gap: 28px;
    align-items: start;
}

.about__text {
    display: grid;
    gap: 14px;
    min-width: 0;
}

.about__collage {
    position: relative;
    height: 380px;
    overflow: hidden;
    background: var(--dd-well);
}

.about__sticker {
    position: absolute;
    height: auto;
}

@media (max-width: 860px) {
    .about {
        grid-template-columns: minmax(0, 1fr);
    }

    .about__board {
        max-width: 360px;
    }
}
</style>
