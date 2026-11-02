<script setup>
import { useProjectStore } from '@/stores/Store';
import { computed, onMounted, ref } from 'vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const project_store = useProjectStore()
const loading = ref(false)

const pending = ref(null)
const deleting = ref(false)
const notice = ref('')

async function remove() {
    if (!pending.value) return

    deleting.value = true
    const res = await project_store.deleteProject(pending.value.id)
    deleting.value = false

    notice.value = res.ok ? 'proyecto eliminado' : res.message
    pending.value = null

    if (res.ok) await project_store.getProjects()
}

const projects = computed(() => project_store.projects ?? [])

function typeName(project) {
    return typeof project.type === 'object' && project.type !== null
        ? project.type.name
        : project.type
}

onMounted(async () => {
    loading.value = true
    await project_store.getProjects()
    loading.value = false
})
</script>

<template>
    <div class="view">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body archive__head">
                <h1 class="t-title fx-glitch" data-text="proyectos">Proyectos</h1>
                <RouterLink class="btn btn--primary" :to="{ name: 'project_create' }">
                    nuevo
                </RouterLink>
            </div>
        </section>

        <div v-if="loading" class="state">
            <p class="state__glyph fx-flicker">✦</p>
            <p class="loading-dots">cargando</p>
        </div>

        <div v-else-if="project_store.error" class="state state--bad">
            <p class="state__glyph">✕</p>
            <p>no se pudo leer el archivo</p>
            <p class="t-mute">{{ project_store.error }}</p>
        </div>

        <ul v-else-if="projects.length" class="entry-list">
            <li v-for="project in projects" :key="project.id">
                <article class="entry">
                    <div class="entry__text">
                        <h2 class="entry__name">{{ project.name }}</h2>
                        <p class="entry__desc t-mute">
                            {{ project.description || 'sin descripción' }}
                        </p>
                        <div class="entry__meta">
                            <span class="tag" :data-type="typeName(project)">
                                {{ typeName(project) }}
                            </span>
                            <span v-if="project.folder" class="entry__file">
                                {{ project.folder }}
                            </span>
                        </div>
                    </div>

                    <div class="entry__actions">
                        <button class="btn btn--danger" type="button" @click="pending = project">
                            borrar
                        </button>
                    </div>
                </article>
            </li>
        </ul>

        <div v-else class="state">
            <p class="state__glyph">◌</p>
            <p>el cuarto está vacío</p>
            <p class="t-mute">nada guardado todavía.</p>
        </div>

        <p v-if="notice" class="notice view__tail" role="status">▸ {{ notice }}</p>

        <ConfirmDialog
            :model-value="!!pending"
            title="eliminar proyecto"
            message="Se borrará el registro y su archivo del servidor."
            :target="pending?.name ?? ''"
            :busy="deleting"
            @update:model-value="pending = null"
            @confirm="remove"
        />
    </div>
</template>

<style scoped>
.entry {
    grid-template-columns: minmax(0, 1fr) auto;
}

.entry__text {
    display: grid;
    gap: var(--sp-1);
    min-width: 0;
}

.entry__desc {
    overflow-wrap: anywhere;
}

.entry__file {
    min-width: 0;
    font-family: var(--font-term);
    font-size: var(--fs-xs);
    color: var(--ash);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.entry__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

@media (max-width: 560px) {
    .entry {
        grid-template-columns: minmax(0, 1fr);
    }

    .entry .btn {
        grid-column: 1 / -1;
        justify-content: center;
    }
}
</style>
