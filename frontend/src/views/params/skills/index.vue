<script setup>
import { useSkillStore } from '@/stores/Store';
import { computed, onMounted, ref } from 'vue';
import { fileUrl } from '@/utils/media';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const skill_store = useSkillStore()
const loading = ref(false)

const pending = ref(null)
const deleting = ref(false)
const notice = ref('')

async function remove() {
    if (!pending.value) return

    deleting.value = true
    const res = await skill_store.deleteSkill(pending.value.id)
    deleting.value = false

    notice.value = res.ok ? 'skill eliminada' : res.message
    pending.value = null

    if (res.ok) await skill_store.getSkills()
}

const skills = computed(() => skill_store.skills ?? [])

onMounted(async () => {
    loading.value = true
    await skill_store.getSkills()
    loading.value = false
})
</script>

<template>
    <div class="view">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body archive__head">
                <h1 class="t-title fx-glitch" data-text="skills">Skills</h1>
                <RouterLink class="btn btn--primary" :to="{ name: 'skill_create' }">
                    nueva
                </RouterLink>
            </div>
        </section>

        <div v-if="loading" class="state">
            <p class="state__glyph fx-flicker">✦</p>
            <p class="loading-dots">cargando</p>
        </div>

        <div v-else-if="skill_store.error" class="state state--bad">
            <p class="state__glyph">✕</p>
            <p>no se pudo leer el cajón</p>
            <p class="t-mute">{{ skill_store.error }}</p>
        </div>

        <ul v-else-if="skills.length" class="entry-list">
            <li v-for="skill in skills" :key="skill.id">
                <article class="entry">
                    <span class="entry__index" aria-hidden="true">
                        <img v-if="skill.icon" class="skill-row__icon" :src="fileUrl(skill.icon)" alt="">
                    </span>

                    <div class="entry__text">
                        <h2 class="entry__name">{{ skill.name }}</h2>

                        <div class="entry__meta">
                            <span class="tag">{{ skill.type?.name ?? 'sin tipo' }}</span>
                            <span class="skill-row__pct">{{ skill.mastery }}%</span>
                        </div>

                        <span class="skill-bar" aria-hidden="true">
                            <span class="skill-bar__fill" :style="{ width: `${skill.mastery}%` }"></span>
                        </span>
                    </div>

                    <div class="entry__actions">
                        <button class="btn btn--danger" type="button" @click="pending = skill">
                            borrar
                        </button>
                    </div>
                </article>
            </li>
        </ul>

        <div v-else class="state">
            <p class="state__glyph">◌</p>
            <p>no hay skills todavía</p>
            <p class="t-mute">nada guardado en este cajón.</p>
        </div>

        <p v-if="notice" class="notice view__tail" role="status">▸ {{ notice }}</p>

        <ConfirmDialog
            :model-value="!!pending"
            title="eliminar skill"
            message="Se borrará el registro y su icono del servidor."
            :target="pending?.name ?? ''"
            :busy="deleting"
            @update:model-value="pending = null"
            @confirm="remove"
        />
    </div>
</template>

<style scoped>
.entry__text {
    display: grid;
    gap: var(--sp-1);
    min-width: 0;
}

.skill-row__icon {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.skill-row__pct {
    font-family: var(--font-term);
    font-size: var(--fs-xs);
    color: var(--ash);
}

.entry__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

@media (max-width: 560px) {
    .entry {
        grid-template-columns: auto 1fr;
    }

    .entry .btn {
        grid-column: 1 / -1;
        justify-content: center;
    }
}
</style>
