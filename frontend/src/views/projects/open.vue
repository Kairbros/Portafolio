<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useProjectStore } from '@/stores/Store'
import { coverUrl, extensionOf, fileUrl, isExternal, isImage, isPage } from '@/utils/media'
import Lightbox from '@/components/ui/Lightbox.vue'
import GameStage from '@/components/ui/GameStage.vue'

const props = defineProps({
    id: { type: String, default: '' },
    typeId: { type: String, default: '' },
    type: { type: Object, default: null },
})

const projectStore = useProjectStore()
const loading = ref(false)
const imageFailed = ref(false)
const zoomed = ref(false)

const project = computed(() => projectStore.project)

const extensions = computed(() => {
    const raw = (project.value as any)?.type?.extension
    const list = Array.isArray(raw) ? raw : String(raw ?? '').split(',')
    return list.map((e: string) => e.trim().toLowerCase()).filter(Boolean)
})

const source = computed(() => String((project.value as any)?.folder ?? ''))
const src = computed(() => fileUrl(source.value))

const isLink = computed(() => extensions.value.includes('.url') || isExternal(source.value))

const isBuild = computed(() => !isLink.value && isPage(source.value))
const isEmbed = computed(() => isLink.value || isBuild.value)
const isPicture = computed(() => !isEmbed.value && isImage(source.value) && !imageFailed.value)

const typeName = computed(() => props.type?.name ?? (project.value as any)?.type?.name ?? 'proyectos')

const icon = computed(() => extensionOf(source.value) || (isLink.value ? 'url' : ''))

const published = computed(() => {
    const raw = (project.value as any)?.created_at
    if (!raw) return ''

    const date = new Date(raw)
    return Number.isNaN(date.getTime())
        ? ''
        : new Intl.DateTimeFormat('es', { day: 'numeric', month: 'long', year: 'numeric' }).format(date)
})

const folder = computed(() => ({ name: 'projects_type', params: { typeId: props.typeId } }))

onMounted(async () => {
    loading.value = true
    await projectStore.ensureProject(props.id)
    loading.value = false
})

onUnmounted(() => {
    projectStore.project = null
})
</script>

<template>
    <div class="view view--single">
        <p v-if="loading" class="viewer__wait loading-dots">abriendo</p>

        <div v-else-if="!project" class="viewer__wait">
            <p>No se pudo abrir el archivo.</p>
            <p class="t-mute">{{ projectStore.error || 'ya no existe o cambió de sitio.' }}</p>
            <RouterLink class="btn btn--ghost" :to="folder">volver a {{ typeName }}</RouterLink>
        </div>

        <article v-else class="viewer">
            <RouterLink class="viewer__up" :to="folder">volver a {{ typeName }}</RouterLink>

            <section class="panel">
                <header class="panel__head"></header>

                <div
                    class="panel__body viewer__screen"
                    :class="{ 'viewer__screen--fit': isPicture }"
                >
                    <GameStage
                        v-if="isBuild"
                        :src="src"
                        :title="project.name"
                        :poster="coverUrl(project)"
                    />

                    <iframe
                        v-else-if="isLink"
                        class="viewer__frame"
                        :src="src"
                        :title="project.name"
                        loading="lazy"
                    />

                    <button
                        v-else-if="isPicture"
                        type="button"
                        class="zoomable"
                        :aria-label="`ampliar ${project.name}`"
                        @click="zoomed = true"
                    >
                        <img
                            class="viewer__img"
                            :src="src"
                            :alt="project.name"
                            loading="lazy"
                            @error="imageFailed = true"
                        >
                    </button>

                    <p v-else class="viewer__none">
                        Este archivo no se puede ver desde el navegador.
                    </p>
                </div>
            </section>

            <section class="panel">
                <header class="panel__head"></header>

                <div class="panel__body viewer__sheet">
                    <header class="viewer__id">
                        <span class="file-icon viewer__icon" :data-ext="icon" aria-hidden="true">
                            <span v-if="coverUrl(project)" class="file-icon__shot">
                                <img class="file-icon__img" :src="coverUrl(project)" alt="">
                            </span>
                            <span v-else class="file-icon__glyph">
                                <span class="file-icon__ext">{{ icon || 'file' }}</span>
                            </span>
                        </span>

                        <div class="viewer__titles">
                            <h1 class="viewer__name">{{ project.name }}</h1>
                            <p class="viewer__sub">{{ typeName }}</p>
                        </div>

                        <div class="viewer__actions">
                            <a
                                v-if="isLink"
                                class="btn btn--primary"
                                :href="src"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                abrir la página
                            </a>

                            <button
                                v-else-if="isPicture"
                                class="btn btn--primary"
                                type="button"
                                @click="zoomed = true"
                            >
                                ver a tamaño completo
                            </button>

                            <a
                                v-if="!isLink"
                                class="btn btn--ghost"
                                :href="src"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                abrir aparte
                            </a>
                        </div>
                    </header>

                    <div class="viewer__cols">
                        <dl class="props">
                            <div class="props__row">
                                <dt class="props__key">Tipo</dt>
                                <dd class="props__val">{{ typeName }}</dd>
                            </div>
                            <div v-if="published" class="props__row">
                                <dt class="props__key">Publicado</dt>
                                <dd class="props__val">{{ published }}</dd>
                            </div>
                        </dl>

                        <div class="viewer__about">
                            <h2 class="t-label viewer__about-title">Sobre el proyecto</h2>

                            <p v-if="project.description" class="viewer__desc">
                                {{ project.description }}
                            </p>
                            <p v-else class="viewer__desc t-mute">Sin descripción todavía.</p>
                        </div>
                    </div>
                </div>
            </section>

            <Lightbox
                v-model="zoomed"
                :src="src"
                :alt="project.name"
                :title="project.name"
            />
        </article>
    </div>
</template>

<style scoped>
.view--single {
    grid-template-rows: minmax(0, 1fr);
}

.viewer {
    display: grid;
    grid-template-rows: auto auto auto;
    align-content: start;
    gap: var(--sp-3);
}

.viewer__up {
    justify-self: start;
    font-family: var(--font-term);
    font-size: var(--fs-sm);
    letter-spacing: var(--tracking);
    color: var(--fg-mute);
    transition: color var(--t-snap);
}

.viewer__up:hover,
.viewer__up:focus-visible {
    color: var(--accent);
}

.viewer__screen {
    display: grid;
    padding: var(--sp-2);
    min-height: min(30rem, 65vh);
}

.viewer__screen--fit {
    min-height: 0;
    place-items: center;
}

.viewer__frame {
    display: block;
    width: 100%;
    height: 100%;
    min-height: min(30rem, 65vh);
    border: 0;
    background: var(--void);
}

.viewer__img {
    display: block;
    width: 100%;
    height: auto;
}

.viewer__none {
    display: grid;
    place-content: center;
    font-family: var(--font-term);
    letter-spacing: var(--tracking);
    color: var(--fg-mute);
}

/* la hoja de propiedades */
.viewer__sheet {
    display: grid;
    gap: var(--sp-6);
}

.viewer__id {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: var(--sp-4);
    padding-bottom: var(--sp-4);
    border-bottom: 2px solid var(--line);
}

.viewer__icon {
    padding: 0;
}

.viewer__titles {
    display: grid;
    gap: var(--sp-1);
    min-width: 0;
}

.viewer__name {
    font-family: var(--font-term);
    font-size: var(--fs-xl);
    line-height: 1.1;
    letter-spacing: var(--tracking);
    color: var(--bone);
    overflow-wrap: anywhere;
}

.viewer__sub {
    font-family: var(--font-term);
    font-size: var(--fs-sm);
    letter-spacing: var(--tracking-wide);
    text-transform: uppercase;
    color: var(--fg-mute);
}

.viewer__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.viewer__cols {
    display: grid;
    grid-template-columns: minmax(0, 20rem) minmax(0, 1fr);
    gap: var(--sp-8);
    align-items: start;
}

.props {
    display: grid;
    gap: 0;
}

.props__row {
    display: grid;
    grid-template-columns: 8rem minmax(0, 1fr);
    gap: var(--sp-3);
    padding: var(--sp-2) var(--sp-2);
}

.props__row:nth-child(odd) {
    background: color-mix(in srgb, var(--panel-lo) 55%, transparent);
}

.props__key {
    font-family: var(--font-term);
    font-size: var(--fs-sm);
    letter-spacing: var(--tracking-wide);
    color: var(--fg-mute);
}

.props__val {
    font-size: var(--fs-sm);
    color: var(--fg-soft);
    overflow-wrap: anywhere;
}

.viewer__about-title {
    padding-bottom: var(--sp-2);
    border-bottom: 2px solid var(--line);
}

.viewer__desc {
    margin-top: var(--sp-3);
    max-width: 62ch;
    line-height: 1.7;
    color: var(--fg-soft);
    overflow-wrap: anywhere;
}

/* estados */
.viewer__wait {
    display: grid;
    justify-items: center;
    align-content: center;
    gap: var(--sp-3);
    padding: var(--sp-12);
    font-family: var(--font-term);
    letter-spacing: var(--tracking-wide);
    color: var(--fg-soft);
}

@media (max-width: 760px) {
    .viewer__cols {
        grid-template-columns: minmax(0, 1fr);
        gap: var(--sp-6);
    }

    .viewer__id {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .viewer__actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 560px) {
    .viewer__screen:not(.viewer__screen--fit),
    .viewer__frame {
        min-height: min(22rem, 55vh);
    }

    .viewer__sheet {
        gap: var(--sp-4);
    }

    .viewer__id {
        gap: var(--sp-3);
        padding-bottom: var(--sp-3);
    }

    .viewer__icon :deep(.file-icon__shot),
    .viewer__icon :deep(.file-icon__glyph) {
        width: 44px;
        height: 54px;
    }

    .props__row {
        grid-template-columns: minmax(0, 1fr);
        gap: 0;
    }

    .viewer__actions {
        display: grid;
    }

    .viewer__actions .btn {
        justify-content: center;
    }
}
</style>
