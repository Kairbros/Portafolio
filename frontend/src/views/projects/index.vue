<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useTypeProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { localized } from '@/i18n'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const route = useRoute()
const router = useRouter()
const typeStore = useTypeProjectStore()

const types = computed(() => typeStore.typeProjects ?? [])

const currentType = computed(() =>
    types.value.find((t) => String(t.id) === String(route.params.typeId)) ?? null,
)

const browsing = computed(() => !route.params.slug)

function fallbackToFirst() {
    if (route.params.typeId || route.params.slug || !types.value.length) return
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
            <h1 class="dd-title">{{ t('projects.title') }}</h1>

            <nav v-if="types.length" class="dd-tabs" :aria-label="t('projects.types')">
                <RouterLink
                    v-for="type in types"
                    :key="type.id"
                    class="dd-tab"
                    :class="{ 'is-active': currentType?.id === type.id }"
                    :to="{ name: 'projects_type', params: { typeId: type.id } }"
                >
                    {{ localized(type, 'name') }}
                </RouterLink>
            </nav>
        </div>

        <p v-if="browsing && typeStore.error" class="dd-state dd-state--bad">
            {{ t('state.error') }}
            <span class="dd-state__detail">{{ typeStore.error }}</span>
        </p>

        <p v-else-if="browsing && typeStore.loaded && !types.length" class="dd-state">{{ t('state.empty') }}</p>

        <RouterView :type="currentType" />
    </div>
</template>
