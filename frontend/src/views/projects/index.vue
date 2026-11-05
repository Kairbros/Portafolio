<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTypeProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'

const route = useRoute()
const router = useRouter()
const typeStore = useTypeProjectStore()

const types = computed(() => typeStore.typeProjects ?? [])

const currentType = computed(() =>
    types.value.find((t) => String(t.id) === String(route.params.typeId)) ?? null,
)

// dentro de un proyecto la cabecera la pone la vista del proyecto
const browsing = computed(() => !route.params.id)

function fallbackToFirst() {
    if (route.params.typeId || !types.value.length) return
    router.replace({ name: 'projects_type', params: { typeId: types.value[0].id } })
}

onMounted(async () => {
    await track(typeStore.ensureTypeProjects())
    fallbackToFirst()
})

watch(() => route.params.typeId, fallbackToFirst)
</script>

<template>
    <div>
        <div v-if="browsing" class="dd-heading">
            <h1 class="dd-title">proyectos</h1>

            <nav v-if="types.length" class="dd-tabs" aria-label="Tipos de proyecto">
                <RouterLink
                    v-for="type in types"
                    :key="type.id"
                    class="dd-tab"
                    :class="{ 'is-active': currentType?.id === type.id }"
                    :to="{ name: 'projects_type', params: { typeId: type.id } }"
                >
                    {{ type.name }}
                </RouterLink>
            </nav>
        </div>

        <p v-if="browsing && typeStore.error" class="dd-state dd-state--bad">
            no se pudo leer
            <span class="dd-state__detail">{{ typeStore.error }}</span>
        </p>

        <p v-else-if="browsing && typeStore.loaded && !types.length" class="dd-state">todavía no hay nada</p>

        <RouterView :type="currentType" />
    </div>
</template>
