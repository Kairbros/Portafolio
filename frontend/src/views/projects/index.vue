<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjectStore, useTypeProjectStore } from '@/stores/Store'
import FolderWindow from '@/components/ui/FolderWindow.vue'

const route = useRoute()
const router = useRouter()
const typeStore = useTypeProjectStore()
const projectStore = useProjectStore()

const types = computed(() => typeStore.typeProjects ?? [])

const tabs = computed(() =>
    types.value.map((type) => ({
        label: type.name,
        to: { name: 'projects_type', params: { typeId: type.id } },
    })),
)

const currentType = computed(() =>
    types.value.find((t) => String(t.id) === String(route.params.typeId)) ?? null,
)

const path = computed(() => {
    const folder = currentType.value ? `\\${currentType.value.name.toLowerCase()}` : ''

    const open = projectStore.project
    const file = route.params.id
        ? `\\${String(open?.name ?? route.params.id).toLowerCase()}`
        : ''

    return `User:\\projects${folder}${file}`
})

function fallbackToFirst() {
    if (route.params.typeId || !types.value.length) return
    router.replace({ name: 'projects_type', params: { typeId: types.value[0].id } })
}

onMounted(async () => {
    await typeStore.ensureTypeProjects()
    fallbackToFirst()
})

watch(() => route.params.typeId, fallbackToFirst)
</script>

<template>
    <FolderWindow :tabs="tabs" :path="path">
        <template #tabs>
            <span v-if="!types.length" class="folder__tab folder__tab--empty">sin tipos</span>
        </template>

        <RouterView :type="currentType" />
    </FolderWindow>
</template>

<style scoped>
.folder__tab--empty {
    cursor: default;
    opacity: 0.5;
}
</style>
