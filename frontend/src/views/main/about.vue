<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useStickerStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { fileUrl } from '@/utils/media'

const stickerStore = useStickerStore()

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

onMounted(() => track(stickerStore.ensureStickers()))
</script>

<template>
    <article class="about">
        <div class="about__text">
            <h1 class="dd-title">sobre mí</h1>

            <div class="dd-prose">
                <p>
                    Me gusta dibujar, hacer videojuegos y pasar demasiado tiempo
                    intentando convertir las ideas de mi cabeza en cosas
                    que realmente funcionen.
                </p>
                <p>
                    Siempre me han gustado los videojuegos, especialmente aquellos
                    que tienen algo que contar. De ahí empecé a escribir mis propias
                    historias, crear personajes y, eventualmente, aprender a programar
                    para poder hacerlos realidad.
                </p>
                <p>
                    También soy bastante curioso con cómo están hechas las cosas.
                    Empecé queriendo entender cómo funcionaban las computadoras y sus
                    programas, y terminé aprendiendo un poco de todo por el camino.
                </p>
                <p>
                    Ahora me gusta mezclar todas esas cosas: dibujar un personaje,
                    pensar en su historia, imaginar el mundo en el que vive y luego
                    intentar convertir todo eso en un juego.
                </p>
            </div>
        </div>

        <section v-if="stickers.length" class="dd-win about__board" aria-label="Dibujos">
            <div class="dd-win__bar">dibujos</div>
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
