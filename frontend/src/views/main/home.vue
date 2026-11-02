<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import NoSignal from '@/components/ui/NoSignal.vue'
import { useProfileImgStore } from '@/stores/Store'
import { useImageRotator } from '@/composables/useImageRotator'
import { fileUrl } from '@/utils/media'
import IconGithub from '~icons/simple-icons/github'
import IconItch from '~icons/simple-icons/itchdotio'
import GearIcon from '~icons/mdi/gear'
import BrushFillIcon from '~icons/ri/brush-fill'
import GhostBoldIcon from '~icons/solar/ghost-bold'

const entries = [
    { icon: BrushFillIcon, name: 'proyectos', note: 'Mira mis proyectos', to: '/projects' },
    { icon: GearIcon, name: 'parametros', note: 'Añade recuerdos', to: '/params' },
    { icon: GhostBoldIcon, name: '???', note: 'Próximamente', to: null },
]

const links = [
    { icon: IconGithub, name: 'github', note: 'github.com/Kairbros', href: 'https://github.com/Kairbros' },
    { icon: IconItch, name: 'itch.io', note: 'just-kvin.itch.io', href: 'https://just-kvin.itch.io/' },
]

const PORTRAIT_MS = 15_000

const profileStore = useProfileImgStore()

const portraits = computed(() =>
    (profileStore.profileImgs ?? []).map((item) => fileUrl(item.img_path)).filter(Boolean),
)

const { layers: portraitLayers, active: portraitActive } = useImageRotator(portraits, PORTRAIT_MS, 'portrait')

onMounted(() => profileStore.ensureProfileImgs())
</script>

<template>
    <div class="view home">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body intro">
                <div class="portrait">
                    <NoSignal />

                    <div
                        v-for="(url, index) in portraitLayers"
                        :key="index"
                        class="portrait__layer"
                        :class="{ 'portrait__layer--on': index === portraitActive && url }"
                        :style="url ? { backgroundImage: `url('${url}')` } : null"
                    ></div>
                </div>

                <div class="intro__text">
                    <h1 class="t-title fx-glitch" data-text="portfolio">Dream Diary</h1>
                    <p class="t-soft fx-caret">Me gusta jugar</p>
                </div>
            </div>
        </section>

        <section class="panel home__folders">
            <header class="panel__head"></header>

            <ul class="listing">
                <li v-for="entry in entries" :key="entry.name">
                    <component
                        :is="entry.to ? RouterLink : 'div'"
                        :to="entry.to ?? undefined"
                        class="row"
                        :class="{ 'row--locked': !entry.to }"
                    >
                        <span class="row__icon" aria-hidden="true">
                            <component :is="entry.icon" />
                        </span>
                        <span class="row__name">{{ entry.name }}</span>
                        <span class="row__note">{{ entry.note }}</span>
                    </component>
                </li>
            </ul>
        </section>

        <section class="panel">
            <header class="panel__head"></header>

            <ul class="listing">
                <li v-for="link in links" :key="link.name">
                    <a class="row" :href="link.href" target="_blank" rel="noopener noreferrer">
                        <span class="row__icon" aria-hidden="true">
                            <component :is="link.icon" />
                        </span>
                        <span class="row__name">{{ link.name }}</span>
                        <span class="row__note">{{ link.note }}</span>
                    </a>
                </li>
            </ul>
        </section>
    </div>
</template>

<style scoped>
.home {
    grid-template-rows: auto 1fr auto;
}

.home__folders .listing {
    flex: 1;
}

.intro {
    display: grid;
    grid-template-columns: 148px minmax(0, 1fr);
    align-items: center;
    gap: var(--sp-8);
}

.intro__text {
    display: grid;
    gap: var(--sp-3);
}

.portrait {
    position: relative;
    display: grid;
    place-items: center;
    aspect-ratio: 1;
    overflow: hidden;
    background: var(--panel-lo);
    border: 2px solid var(--pink-deep);
    box-shadow: var(--shadow-hard);
}

.portrait__layer {
    position: absolute;
    inset: 0;
    z-index: 1;
    background-position: center;
    background-size: cover;
    opacity: 0;
    transition: opacity 1.25s ease-in-out;
}

.portrait__layer--on {
    opacity: 1;
}

@media (prefers-reduced-motion: reduce) {
    .portrait__layer {
        transition: none;
    }
}

@media (max-width: 560px) {
    .intro {
        grid-template-columns: 1fr;
        justify-items: start;
        gap: var(--sp-6);
    }

    .portrait {
        width: 120px;
    }
}
</style>
