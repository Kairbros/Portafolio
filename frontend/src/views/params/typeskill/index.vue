<script setup>
import { useTypeSkillStore } from '@/stores/Store';
import { computed, onMounted, ref } from 'vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const type_store = useTypeSkillStore()
const loading = ref(false)

const pending = ref(null)
const deleting = ref(false)
const notice = ref('')

async function remove() {
    if (!pending.value) return

    deleting.value = true
    const res = await type_store.deleteTypeSkill(pending.value.id)
    deleting.value = false

    notice.value = res.ok ? 'tipo eliminado' : res.message
    pending.value = null

    if (res.ok) await type_store.getTypeSkills()
}

const types = computed(() => type_store.typeSkills ?? [])

onMounted(async () => {
    loading.value = true
    await type_store.getTypeSkills()
    loading.value = false
})
</script>

<template>
    <div class="view">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body archive__head">
                <h1 class="t-title fx-glitch" data-text="tipos de skill">Tipos De Skill</h1>
                <RouterLink class="btn btn--primary" :to="{ name: 'typeskill_create' }">
                    nuevo
                </RouterLink>
            </div>
        </section>

        <div v-if="loading" class="state">
            <p class="state__glyph fx-flicker">✦</p>
            <p class="loading-dots">cargando</p>
        </div>

        <div v-else-if="type_store.error" class="state state--bad">
            <p class="state__glyph">✕</p>
            <p>no se pudo leer el catálogo</p>
            <p class="t-mute">{{ type_store.error }}</p>
        </div>

        <ul v-else-if="types.length" class="type-grid">
            <li v-for="type in types" :key="type.id ?? type.name">
                <article
                    class="type-card"
                    :title="type.skills_count
                        ? 'no se puede eliminar: tiene skills'
                        : `eliminar ${type.name}`"
                >
                    <span class="tag">{{ type.name }}</span>

                    <p class="type-card__uses" :class="{ 'type-card__uses--busy': type.skills_count }">
                        {{ type.skills_count ? `${type.skills_count} skill(s)` : 'sin usar' }}
                    </p>

                    <button
                        type="button"
                        class="type-card__hit"
                        :disabled="!!type.skills_count"
                        :aria-label="`eliminar ${type.name}`"
                        @click="pending = type"
                    ></button>
                </article>
            </li>
        </ul>

        <div v-else class="state">
            <p class="state__glyph">◌</p>
            <p>no hay tipos todavía</p>
            <p class="t-mute">sin tipos, las skills no tienen dónde agruparse.</p>
        </div>

        <p v-if="notice" class="notice view__tail" role="status">▸ {{ notice }}</p>

        <ConfirmDialog
            :model-value="!!pending"
            title="eliminar tipo"
            message="El tipo saldrá del catálogo y dejará de poder elegirse al crear skills."
            :target="pending?.name ?? ''"
            :busy="deleting"
            @update:model-value="pending = null"
            @confirm="remove"
        />
    </div>
</template>
