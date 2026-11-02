<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useSkillStore } from '@/stores/Store'
import { fileUrl } from '@/utils/media'

const skillStore = useSkillStore()
const loading = ref(false)

const groups = computed(() => {
    const byType = new Map<string, { name: string; skills: any[] }>()

    for (const skill of skillStore.skills ?? []) {
        const key = String(skill.type?.id ?? 'sin-tipo')
        if (!byType.has(key)) {
            byType.set(key, { name: skill.type?.name ?? 'otras', skills: [] })
        }
        byType.get(key)!.skills.push(skill)
    }

    for (const group of byType.values()) {
        group.skills.sort((a, b) => (b.mastery ?? 0) - (a.mastery ?? 0))
    }

    return [...byType.values()]
})

function pips(mastery: number) {
    return Math.round((mastery ?? 0) / 10)
}

onMounted(async () => {
    loading.value = true
    await skillStore.getSkills()
    loading.value = false
})
</script>

<template>
    <div class="view">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body">
                <h1 class="t-title fx-glitch" data-text="skills">Skills</h1>
            </div>
        </section>

        <div v-if="loading" class="state">
            <p class="state__glyph fx-flicker">✦</p>
            <p class="loading-dots">leyendo</p>
        </div>

        <div v-else-if="skillStore.error" class="state state--bad">
            <p class="state__glyph">✕</p>
            <p>no se pudo leer el cajón</p>
            <p class="t-mute">{{ skillStore.error }}</p>
        </div>

        <div v-else-if="groups.length" class="skills">
            <section v-for="group in groups" :key="group.name" class="panel">
                <header class="panel__head"></header>

                <div class="panel__body skills__group">
                    <h2 class="skills__title">{{ group.name }}</h2>

                    <ul class="skills__list">
                        <li v-for="skill in group.skills" :key="skill.id">
                            <article class="skill" :title="`${skill.name} (${skill.mastery}%)`">
                                <span class="skill__icon">
                                    <img
                                        v-if="skill.icon"
                                        class="skill__img"
                                        :src="fileUrl(skill.icon)"
                                        :alt="skill.name"
                                        loading="lazy"
                                    >
                                    <span v-else class="skill__glyph" aria-hidden="true">◌</span>
                                </span>

                                <span class="skill__pips" aria-hidden="true">
                                    <span
                                        v-for="i in 10"
                                        :key="i"
                                        class="skill__pip"
                                        :class="{ 'skill__pip--on': i <= pips(skill.mastery) }"
                                    ></span>
                                </span>
                            </article>
                        </li>
                    </ul>
                </div>
            </section>
        </div>

        <div v-else class="state">
            <p class="state__glyph">◌</p>
            <p>carpeta vacia</p>
            <p class="t-mute">todavía no hay nada guardado en este cajón.</p>
        </div>
    </div>
</template>

<style scoped>
.skills {
    display: grid;
    gap: var(--sp-4);
    align-content: start;
}

.skills__group {
    display: grid;
    gap: var(--sp-2);
    padding-block: var(--sp-2) var(--sp-4);
}

.skills__title {
    font-family: var(--font-term);
    font-size: var(--fs-lg);
    letter-spacing: var(--tracking-wide);
    text-transform: capitalize;
    color: var(--accent);
}

.skills__list {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-evenly;
    gap: var(--sp-4) var(--sp-6);
}

@media (max-width: 560px) {
    .skills {
        gap: var(--sp-3);
    }

    .skills__list {
        justify-content: space-around;
        gap: var(--sp-4) var(--sp-3);
    }
}
</style>
