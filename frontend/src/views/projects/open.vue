<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { coverUrl, fileUrl, isExternal, isImage, isPage } from '@/utils/media'
import PhotoZoom from '@/components/flash/PhotoZoom.vue'
import GameStage from '@/components/ui/GameStage.vue'

const props = defineProps({
    id: { type: String, default: '' },
    typeId: { type: String, default: '' },
    type: { type: Object, default: null },
})

const projectStore = useProjectStore()
const done = ref(false)
const imageFailed = ref(false)
const zoomed = ref(false)
const stage = ref<InstanceType<typeof GameStage> | null>(null)

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
    await track(projectStore.ensureProject(props.id))
    done.value = true
})

onUnmounted(() => {
    projectStore.project = null
})
</script>

<template>
    <div>
        <RouterLink class="dd-back" :to="folder">volver a {{ typeName }}</RouterLink>

        <p v-if="done && !project" class="dd-state dd-state--bad">
            no se encontró este proyecto
            <span class="dd-state__detail">{{ projectStore.error || 'puede que se haya borrado o movido' }}</span>
        </p>

        <article v-else-if="project" class="open">
            <section class="dd-win" aria-label="Pantalla del proyecto">
                <div class="dd-win__bar"><span class="dd-win__title">{{ project.name }}</span></div>

                <GameStage
                    v-if="isBuild"
                    ref="stage"
                    :src="src"
                    :title="project.name"
                    :poster="coverUrl(project)"
                />

                <iframe
                    v-else-if="isLink"
                    class="open__frame"
                    :src="src"
                    :title="project.name"
                    loading="lazy"
                />

                <button
                    v-else-if="isPicture"
                    type="button"
                    class="open__zoom"
                    :aria-label="`Ver ${project.name} en grande`"
                    @click="zoomed = true"
                >
                    <img class="open__img" :src="src" :alt="project.name" @error="imageFailed = true">
                </button>

                <p v-else class="dd-state open__none">este archivo no se puede ver desde el navegador</p>
            </section>

            <section class="dd-win" aria-label="Datos del proyecto">
                <div class="dd-win__bar">datos</div>

                <div class="open__data">
                    <h1 class="dd-title open__name">{{ project.name }}</h1>

                    <dl class="open__props">
                        <dt>tipo</dt>
                        <dd>{{ typeName }}</dd>
                        <template v-if="published">
                            <dt>publicado</dt>
                            <dd>{{ published }}</dd>
                        </template>
                    </dl>

                    <p v-if="project.description" class="open__desc">{{ project.description }}</p>

                    <div class="open__actions">
                        <button
                            v-if="isBuild"
                            type="button"
                            class="dd-btn dd-btn--tone"
                            @click="stage?.openTheater()"
                        >
                            jugar en grande
                        </button>

                        <button
                            v-else-if="isPicture"
                            type="button"
                            class="dd-btn dd-btn--tone"
                            @click="zoomed = true"
                        >
                            ver en grande
                        </button>

                        <a
                            v-if="src"
                            class="dd-btn"
                            :class="{ 'dd-btn--tone': isLink }"
                            :href="src"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ isLink ? 'abrir la página' : 'abrir en otra pestaña' }}
                        </a>
                    </div>
                </div>
            </section>

            <PhotoZoom v-model="zoomed" :src="src" :alt="project.name" :title="project.name" />
        </article>
    </div>
</template>

<style scoped>
.open {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 236px;
    gap: 20px;
    align-items: start;
}

.open__frame {
    display: block;
    width: 100%;
    aspect-ratio: 16 / 10;
    border: 0;
    background: var(--dd-well);
}

.open__zoom {
    display: block;
    width: 100%;
    cursor: zoom-in;
}

.open__img {
    width: 100%;
    max-height: 60vh;
    object-fit: contain;
    background: var(--dd-well);
}

.open__none {
    padding: 48px 16px;
    text-align: center;
}

.open__data {
    display: grid;
    gap: 14px;
    padding: 14px;
}

.open__name {
    font-size: 18px;
    line-height: 1.3;
}

.open__props {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 4px 12px;
    font-size: 13px;
}

.open__props dt {
    color: var(--dd-dust);
}

.open__desc {
    font-size: 14px;
    color: var(--dd-lilac);
    white-space: pre-line;
    overflow-wrap: anywhere;
}

.open__actions {
    display: grid;
    gap: 8px;
    padding-top: 4px;
}

@media (max-width: 860px) {
    .open {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
