<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useSkillStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { fileUrl } from '@/utils/media'

const skillStore = useSkillStore()

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

function level(mastery: number) {
    return Math.round(Math.max(0, Math.min(100, mastery ?? 0)) / 10)
}

onMounted(() => track(skillStore.ensureSkills()))
</script>

<template>
    <div class="skills">
        <h1 class="dd-title skills__title">skills</h1>

        <p v-if="skillStore.error" class="dd-state dd-state--bad">
            no se pudo leer
            <span class="dd-state__detail">{{ skillStore.error }}</span>
        </p>

        <div v-else-if="groups.length" class="skills__groups">
            <section v-for="group in groups" :key="group.name" class="dd-win">
                <h2 class="dd-win__bar">{{ group.name }}</h2>

                <ul class="skills__list">
                    <li v-for="skill in group.skills" :key="skill.id" class="skill-row">
                        <img
                            v-if="skill.icon"
                            class="skill-row__icon"
                            :src="fileUrl(skill.icon)"
                            alt=""
                            loading="lazy"
                        >
                        <span v-else class="skill-row__icon dd-scan" aria-hidden="true"></span>

                        <span class="skill-row__name">{{ skill.name }}</span>

                        <span class="skill-row__pips" role="img" :aria-label="`${level(skill.mastery)} de 10`">
                            <span
                                v-for="i in 10"
                                :key="i"
                                class="skill-row__pip"
                                :class="{ 'skill-row__pip--on': i <= level(skill.mastery) }"
                            ></span>
                        </span>
                    </li>
                </ul>
            </section>
        </div>

        <p v-else-if="skillStore.loaded" class="dd-state">todavía no hay nada</p>
    </div>
</template>

<style scoped>
.skills__title {
    margin-bottom: 18px;
}

.skills__groups {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
    align-items: start;
}

/* todas las ventanas miden lo mismo: unas 4 filas, el resto se scrollea */
.skills__list {
    height: 160px;
    padding: 6px 0;
    overflow-y: auto;
    overscroll-behavior: contain;
}

.skill-row {
    display: grid;
    grid-template-columns: 20px minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    padding: 7px 12px;
    font-size: 14px;
}

.skill-row__icon {
    width: 20px;
    height: 20px;
    object-fit: contain;
}

.skill-row__name {
    overflow-wrap: anywhere;
}

.skill-row__pips {
    display: flex;
    gap: 2px;
}

.skill-row__pip {
    width: 6px;
    height: 10px;
    border: 1px solid var(--dd-line);
}

.skill-row__pip--on {
    background: var(--dd-skills);
    border-color: var(--dd-skills);
}

@media (max-width: 860px) {
    .skills__groups {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
