<script setup>
import { computed, onMounted, ref } from 'vue'
import { useProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { coverUrl } from '@/utils/media'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import PencilIcon from '~icons/pixelarticons/pencil'

const store = useProjectStore()

const pending = ref(null)
const deleting = ref(false)
const notice = ref('')

const projects = computed(() => store.adminProjects ?? [])

async function remove() {
    if (!pending.value) return

    deleting.value = true
    const res = await store.deleteProject(pending.value.id)
    deleting.value = false

    notice.value = res.ok ? 'borrado' : res.message
    pending.value = null

    if (res.ok) await track(store.getAdminProjects())
}

onMounted(() => track(store.getAdminProjects()))
</script>

<template>
    <div>
        <div class="dd-heading">
            <h1 class="dd-title">proyectos</h1>
            <RouterLink class="dd-btn dd-btn--tone" :to="{ name: 'project_create' }">nuevo</RouterLink>
        </div>

        <p v-if="store.error" class="dd-state dd-state--bad">
            no se pudo leer
            <span class="dd-state__detail">{{ store.error }}</span>
        </p>

        <section v-else-if="projects.length" class="dd-win">
            <div class="dd-win__bar">{{ projects.length }} en total</div>
            <ul class="dd-rows">
                <li v-for="project in projects" :key="project.id" class="dd-row">
                    <img v-if="coverUrl(project)" class="dd-row__thumb" :src="coverUrl(project)" alt="" loading="lazy">
                    <span v-else class="dd-row__thumb dd-scan" aria-hidden="true"></span>

                    <div class="dd-row__text">
                        <span class="dd-row__name">{{ project.name }}</span>
                        <span class="dd-row__meta">{{ project.type?.name ?? 'sin tipo' }}{{ project.published ? '' : ', borrador' }}</span>
                    </div>

                    <div class="dd-row__actions">
                        <RouterLink class="dd-row__edit" :to="{ name: 'project_edit', params: { id: project.id } }" aria-label="Editar" title="editar">
                            <PencilIcon aria-hidden="true" />
                        </RouterLink>
                        <button type="button" class="dd-link-btn" @click="pending = project">borrar</button>
                    </div>
                </li>
            </ul>
        </section>

        <p v-else-if="store.adminLoaded" class="dd-state">vacío</p>

        <p v-if="notice" class="dd-notice" role="status">{{ notice }}</p>

        <ConfirmDialog
            :model-value="!!pending"
            title="borrar proyecto"
            :target="pending?.name ?? ''"
            :busy="deleting"
            @update:model-value="pending = null"
            @confirm="remove"
        />
    </div>
</template>
