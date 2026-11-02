<script setup>
import { computed, onMounted, ref } from 'vue'
import { useProjectStore } from '@/stores/Store'
import { coverUrl } from '@/utils/media'

const props = defineProps({
    type: { type: Object, default: null },
    typeId: { type: String, default: '' },
})

const projectStore = useProjectStore()
const loading = ref(false)

const projects = computed(() =>
    (projectStore.projects ?? []).filter(
        (p) => String(p.type_project_id ?? p.type?.id) === String(props.typeId),
    ),
)

const extension = computed(() => {
    const raw = props.type?.extension
    const first = String(raw ?? '').split(',')[0]
    return first.trim().replace(/^\./, '').toLowerCase()
})

onMounted(async () => {
    loading.value = true
    await projectStore.ensureProjects()
    loading.value = false
})
</script>

<template>
    <div class="view">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body">
                <h1 class="t-title fx-glitch" :data-text="type?.name ?? 'proyectos'">
                    {{ type?.name ?? 'Proyectos' }}
                </h1>
            </div>
        </section>

        <div v-if="loading" class="state">
            <p class="state__glyph fx-flicker">✦</p>
            <p class="loading-dots">montando unidad</p>
        </div>

        <div v-else-if="projectStore.error" class="state state--bad">
            <p class="state__glyph">✕</p>
            <p>no se pudo leer la unidad</p>
            <p class="t-mute">{{ projectStore.error }}</p>
        </div>

        <ul v-else-if="projects.length" class="icon-grid icon-grid--tiles shelf__grid">
            <li v-for="project in projects" :key="project.id">
                <RouterLink
                    class="file-icon file-icon--tile"
                    :data-ext="extension"
                    :to="{ name: 'project_open', params: { typeId, id: project.id } }"
                >
                    <span v-if="coverUrl(project)" class="file-icon__shot" aria-hidden="true">
                        <img class="file-icon__img" :src="coverUrl(project)" alt="" loading="lazy">
                    </span>

                    <span v-else class="file-icon__glyph" aria-hidden="true">
                        <span class="file-icon__ext">{{ extension || '?' }}</span>
                    </span>

                    <span class="file-icon__label">{{ project.name }}</span>
                </RouterLink>
            </li>
        </ul>

        <div v-else class="state">
            <p class="state__glyph">◌</p>
            <p>carpeta vacía</p>
            <p class="t-mute">no hay nada de este tipo todavía.</p>
        </div>
    </div>
</template>

<style scoped>
.shelf__grid {
    padding: var(--sp-4);
}
</style>
