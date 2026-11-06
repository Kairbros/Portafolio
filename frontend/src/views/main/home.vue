<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useProfileImgStore, useProjectStore } from '@/stores/Store'
import { useImageRotator } from '@/composables/useImageRotator'
import { track } from '@/composables/useLoader'
import { fileUrl } from '@/utils/media'
import { projectTypeId } from '@/utils/project'
import { localized } from '@/i18n'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const PORTRAIT_MS = 15_000
const LATEST = 5

const profileStore = useProfileImgStore()
const projectStore = useProjectStore()

const portraits = computed(() =>
    (profileStore.profileImgs ?? []).map((item) => fileUrl(item.img_path)).filter(Boolean),
)

const { layers: portraitLayers, active: portraitActive } = useImageRotator(portraits, PORTRAIT_MS, 'portrait')

const latest = computed(() =>
    [...(projectStore.projects ?? [])]
        .sort((a, b) => String(b.created_at ?? '').localeCompare(String(a.created_at ?? '')))
        .slice(0, LATEST),
)

function shortDate(raw: string | null | undefined) {
    const date = new Date(raw ?? '')
    if (Number.isNaN(date.getTime())) return ''
    return new Intl.DateTimeFormat(locale.value, { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date)
}

onMounted(() => {
    track(profileStore.ensureProfileImgs())
    track(projectStore.ensureProjects())
})
</script>

<template>
    <div class="home">
        <section class="dd-win home__portrait" :aria-label="t('home.portrait')">
            <div class="dd-win__bar">{{ t('home.portrait') }}</div>
            <div class="home__frame dd-scan">
                <div
                    v-for="(url, index) in portraitLayers"
                    :key="index"
                    class="home__layer"
                    :class="{ 'home__layer--on': index === portraitActive && url }"
                    :style="url ? { backgroundImage: `url('${url}')` } : undefined"
                ></div>
            </div>
        </section>

        <div class="home__side">
            <div class="home__intro">
                <h1 class="dd-title">{{ t('home.title') }}</h1>
                <p class="dd-lead">{{ t('home.lead') }}</p>
            </div>

            <section class="dd-win" :aria-label="t('home.news')">
                <div class="dd-win__bar">{{ t('home.news') }}</div>

                <p v-if="projectStore.error" class="dd-state dd-state--bad home__state">
                    {{ t('state.error') }}
                    <span class="dd-state__detail">{{ projectStore.error }}</span>
                </p>

                <ul v-else-if="latest.length" class="home__news">
                    <li v-for="project in latest" :key="project.id">
                        <RouterLink
                            class="home__row"
                            :to="{ name: 'project_open', params: { typeId: projectTypeId(project), id: project.id } }"
                        >
                            <span class="home__date">{{ shortDate(project.created_at) }}</span>
                            <span class="home__name">{{ project.name }}</span>
                            <span class="home__type">{{ localized(project.type, 'name') }}</span>
                        </RouterLink>
                    </li>
                </ul>

                <p v-else-if="projectStore.loaded" class="dd-state home__state">{{ t('state.empty') }}</p>
            </section>
        </div>
    </div>
</template>

<style scoped>
.home {
    display: grid;
    grid-template-columns: 220px minmax(0, 1fr);
    gap: 28px;
    align-items: start;
}

.home__frame {
    position: relative;
    aspect-ratio: 1;
}

.home__layer {
    position: absolute;
    inset: 0;
    background-position: center;
    background-size: cover;
    opacity: 0;
    transition: opacity 1.2s ease-in-out;
}

.home__layer--on {
    opacity: 1;
}

.home__side {
    display: grid;
    gap: 24px;
    min-width: 0;
}

.home__intro {
    display: grid;
    gap: 8px;
}

.home__news {
    padding: 4px 0;
    font-size: 14px;
}

.home__row {
    display: grid;
    grid-template-columns: 96px minmax(0, 1fr) auto;
    gap: 12px;
    padding: 8px 12px;
    color: var(--dd-bone);
}

.home__row:hover,
.home__row:focus-visible {
    color: var(--dd-bone);
    background: var(--dd-hover);
}

.home__date {
    color: var(--dd-dust);
}

.home__name {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.home__type {
    color: var(--dd-projects);
}

.home__state {
    padding: 12px;
}

@media (max-width: 720px) {
    .home {
        grid-template-columns: 112px minmax(0, 1fr);
        gap: 20px 16px;
    }

    .home__portrait {
        align-self: center;
    }

    .home__side {
        display: contents;
    }

    .home__intro .dd-title {
        font-size: 20px;
    }

    .home__side > .dd-win {
        grid-column: 1 / -1;
    }

    .home__row {
        grid-template-columns: minmax(0, 1fr) auto;
        row-gap: 0;
    }

    .home__date {
        grid-row: 2;
        font-size: 12px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .home__layer {
        transition: none;
    }
}
</style>
