<script setup>
import { computed, onMounted } from 'vue'
import { useProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { coverUrl } from '@/utils/media'

const props = defineProps({
    type: { type: Object, default: null },
    typeId: { type: String, default: '' },
})

const projectStore = useProjectStore()

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

onMounted(() => track(projectStore.ensureProjects()))
</script>

<template>
    <div v-if="typeId">
        <p v-if="type?.description" class="dd-lead type__intro">{{ type.description }}</p>

        <p v-if="projectStore.error" class="dd-state dd-state--bad">
            no se pudo leer
            <span class="dd-state__detail">{{ projectStore.error }}</span>
        </p>

        <ul v-else-if="projects.length" class="dd-grid">
            <li v-for="project in projects" :key="project.id">
                <RouterLink class="dd-card" :to="{ name: 'project_open', params: { typeId, id: project.id } }">
                    <span class="dd-card__shot dd-scan">
                        <img
                            v-if="coverUrl(project)"
                            class="dd-card__img"
                            :src="coverUrl(project)"
                            alt=""
                            loading="lazy"
                        >
                        <span v-else>{{ extension || 'archivo' }}</span>
                    </span>
                    <span class="dd-card__name">{{ project.name }}</span>
                </RouterLink>
            </li>
        </ul>

        <p v-else-if="projectStore.loaded" class="dd-state">todavía no hay nada aquí</p>
    </div>
</template>

<style scoped>
.type__intro {
    margin-bottom: 18px;
    font-size: 14px;
}
</style>
