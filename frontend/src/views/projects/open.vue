<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watchEffect } from 'vue'
import { useProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { setPageTitle } from '@/composables/usePageMeta'
import { coverUrl, downloadUrl, formatSize } from '@/utils/media'
import { renderMarkdown } from '@/utils/markdown'
import { localized } from '@/i18n'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
    slug: { type: String, default: '' },
    type: { type: Object, default: null },
})

const projectStore = useProjectStore()
const done = ref(false)

const project = computed(() => projectStore.project)
const summary = computed(() => localized(project.value, 'description'))
const body = computed(() => renderMarkdown(localized(project.value, 'body')))
const typeName = computed(() => localized(props.type ?? project.value?.type, 'name') || t('projects.title'))
const folder = computed(() => ({ name: 'projects_type', params: { typeId: project.value?.type_project_id } }))

onMounted(async () => {
    await track(projectStore.ensureProject(props.slug))
    done.value = true
})

watchEffect(() => setPageTitle(project.value?.name ?? ''))

onUnmounted(() => {
    projectStore.project = null
    setPageTitle('')
})
</script>

<template>
    <div>
        <RouterLink v-if="project" class="dd-back" :to="folder">{{ t('projects.back', { name: typeName }) }}</RouterLink>

        <p v-if="done && !project" class="dd-state dd-state--bad">
            {{ t('projects.notFound') }}
            <span class="dd-state__detail">{{ projectStore.error || t('projects.notFoundHint') }}</span>
        </p>

        <article v-else-if="project" class="open">
            <div class="open__main">
                <img v-if="coverUrl(project)" class="open__cover" :src="coverUrl(project)" alt="">

                <section v-if="body" class="dd-win open__body">
                    <div class="dd-win__bar"><span class="dd-win__title">{{ project.name }}</span></div>
                    <div class="dd-prose" v-html="body"></div>
                </section>
            </div>

            <section class="dd-win open__side" :aria-label="t('projects.data')">
                <div class="dd-win__bar">{{ t('projects.data') }}</div>

                <div class="open__data">
                    <h1 class="dd-title open__name">{{ project.name }}</h1>

                    <p v-if="summary" class="open__desc">{{ summary }}</p>

                    <dl class="open__props">
                        <dt>{{ t('projects.type') }}</dt>
                        <dd>{{ typeName }}</dd>
                        <dt>{{ t('projects.state') }}</dt>
                        <dd>{{ t(`projects.status.${project.status}`) }}</dd>
                        <template v-if="project.year">
                            <dt>{{ t('projects.year') }}</dt>
                            <dd>{{ project.year }}</dd>
                        </template>
                    </dl>

                    <ul v-if="project.tags?.length" class="open__tags">
                        <li v-for="tag in project.tags" :key="tag">{{ tag }}</li>
                    </ul>

                    <div v-if="project.links?.length" class="open__actions">
                        <a
                            v-for="link in project.links"
                            :key="link.id"
                            class="dd-btn"
                            :href="link.url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ link.label }}
                        </a>
                    </div>

                    <div v-if="project.files?.length" class="open__actions">
                        <a
                            v-for="file in project.files"
                            :key="file.id"
                            class="dd-btn dd-btn--tone open__download"
                            :href="downloadUrl(file.id)"
                            download
                        >
                            <span>{{ file.label }}</span>
                            <span class="open__size">{{ formatSize(file.size) }}</span>
                        </a>
                    </div>
                </div>
            </section>
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

.open__main {
    display: grid;
    gap: 20px;
    min-width: 0;
}

.open__cover {
    display: block;
    width: 100%;
    max-height: 56vh;
    object-fit: cover;
    border: 1px solid var(--dd-frame);
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

.open__desc {
    font-size: 14px;
    color: var(--dd-lilac);
    white-space: pre-line;
    overflow-wrap: anywhere;
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

.open__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.open__tags li {
    padding: 2px 6px;
    font-size: 12px;
    color: var(--dd-lilac);
    border: 1px solid var(--dd-line);
}

.open__actions {
    display: grid;
    gap: 8px;
}

.open__download {
    justify-content: space-between;
    gap: 10px;
    text-align: left;
}

.open__size {
    flex: none;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 860px) {
    .open {
        grid-template-columns: minmax(0, 1fr);
    }

    .open__main {
        display: contents;
    }

    .open__cover {
        order: 1;
    }

    .open__side {
        order: 2;
    }

    .open__body {
        order: 3;
    }
}
</style>
