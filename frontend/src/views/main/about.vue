<script setup lang="ts">
const stickers = [
    { src: '/about_stickers/mad.png', alt: '' },
    { src: '/about_stickers/rals.png', alt: '' },
    { src: '/about_stickers/madotsuki.png', alt: '' },
]
</script>

<template>
    <article class="view about">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body">
                <div class="about__cols">
                    <h1 class="t-title fx-glitch about__title" data-text="sobre mi">Sobre mi</h1>

                    <div class="about__body">
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

                        <p >
                            También soy bastante curioso con cómo están hechas las cosas.
                            Empecé queriendo entender cómo funcionaban las computadoras y sus
                            programas, y terminé aprendiendo un poco de todo por el camino.
                        </p>
                        <p class="fx-caret">
                            Ahora me gusta mezclar todas esas cosas. Dibujar un personaje,
                            pensar en su historia, imaginar el mundo en el que vive y luego
                            intentar convertir todo eso en un juego.</p>
                    </div>

                    <aside class="about__stickers">
                        <figure
                            v-for="(sticker, i) in stickers"
                            :key="i"
                            class="sticker"
                        >
                            <img
                                v-if="sticker.src"
                                class="sticker__img"
                                :src="sticker.src"
                                :alt="sticker.alt"
                            >
                            <span v-else class="sticker__empty" aria-hidden="true">◌</span>
                        </figure>
                    </aside>
                </div>
            </div>
        </section>
    </article>
</template>

<style scoped>
.about {
    grid-template-rows: 1fr auto;
}

.about :deep(.panel__body) {
    display: flex;
    flex-direction: column;
}

.about__cols {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    grid-template-rows: auto minmax(0, 1fr);
    flex: 1;
    gap: var(--sp-6) var(--sp-8);
    padding-bottom: var(--sp-6);
}

.about__title {
    grid-area: 1 / 1;
}

.about__body {
    grid-area: 2 / 1;
    display: flex;
    flex-direction: column;
    gap: var(--sp-4);
    max-width: 62ch;
    color: var(--fg-soft);
}

.about__stickers {
    grid-area: 1 / 2 / -1 / -1;
    display: grid;
    align-content: center;
    gap: 0;
    padding-inline: var(--sp-4);
}

.sticker {
    --x: 0px;
    --y: 0px;
    --tilt: 0deg;

    display: grid;
    place-items: center;
    position: relative;
    margin: 0;
    transform: translate(var(--x), var(--y)) rotate(var(--tilt));
    filter: drop-shadow(3px 3px 0 var(--void));
    transition: transform var(--t-snap), filter var(--t-snap);
}

.sticker:nth-child(1) {
    width: 150px;
    justify-self: start;
    --x: 32px;
    --tilt: -9deg;
    z-index: 1;
}

.sticker:nth-child(2) {
    width: 130px;
    justify-self: end;
    --x: -32px;
    --y: -22px;
    --tilt: 11deg;
    z-index: 3;
}

.sticker:nth-child(3) {
    width: 280px;
    justify-self: center;
    --x: -40px;
    --y: 16px;
    --tilt: -5deg;
    z-index: 2;
}

.sticker:hover {
    transform: translate(var(--x), var(--y)) rotate(0deg) scale(1.06);
    filter: drop-shadow(5px 5px 0 var(--void));
    z-index: 4;
}

.sticker__img {
    display: block;
    width: 100%;
    height: auto;
}

.sticker__empty {
    display: grid;
    place-items: center;
    width: 100%;
    aspect-ratio: 1;
    background: var(--panel-lo);
    border: 2px dashed var(--line);
    font-size: var(--fs-lg);
    color: var(--fg-mute);
}

@media (max-width: 900px) {
    .about__cols {
        grid-template-columns: minmax(0, 1fr);
        grid-template-rows: auto auto auto;
    }

    .about__title,
    .about__body {
        grid-area: auto;
    }

    .about__stickers {
        grid-area: auto;
        grid-auto-flow: column;
        justify-content: center;
        align-items: center;
    }

    .sticker {
        justify-self: center;
        --x: 0px;
    }

    .sticker:nth-child(1) { width: min(150px, 30vw); }
    .sticker:nth-child(2) { width: min(78px, 16vw); }
    .sticker:nth-child(3) { width: min(114px, 23vw); }
}

@media (max-width: 560px) {
    .about__cols {
        gap: var(--sp-4);
        padding-bottom: var(--sp-4);
    }

    .about__stickers {
        padding-inline: 0;
    }
}
</style>
