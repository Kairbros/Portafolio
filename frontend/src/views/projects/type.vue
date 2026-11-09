<script setup>
import { computed, onMounted } from 'vue'
import { useProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { coverUrl } from '@/utils/media'
import { localized } from '@/i18n'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

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

onMounted(() => track(projectStore.ensureProjects()))
</script>

<template>
    <div v-if="typeId">
        <p v-if="localized(type, 'description')" class="dd-lead type__intro">{{ localized(type, 'description') }}</p>

        <p v-if="projectStore.error" class="dd-state dd-state--bad">
            {{ t('state.error') }}
            <span class="dd-state__detail">{{ projectStore.error }}</span>
        </p>

        <ul v-else-if="projects.length" class="dd-grid">
            <li v-for="project in projects" :key="project.id">
                <RouterLink class="dd-card" :to="{ name: 'project_open', params: { slug: project.slug } }">
                    <span class="dd-card__shot dd-scan">
                        <img
                            v-if="coverUrl(project)"
                            class="dd-card__img"
                            :src="coverUrl(project)"
                            alt=""
                            loading="lazy"
                        >
                    </span>
                    <span class="dd-card__name">{{ project.name }}</span>
                </RouterLink>
            </li>
        </ul>

        <p v-else-if="projectStore.loaded" class="dd-state">{{ t('state.emptyHere') }}</p>
    </div>
</template>

<style scoped>
.type__intro {
    margin-bottom: 18px;
    font-size: 14px;
}
</style>
