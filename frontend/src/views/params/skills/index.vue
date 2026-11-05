<script setup>
import { computed, onMounted, ref } from 'vue'
import { useSkillStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { fileUrl } from '@/utils/media'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import PencilIcon from '~icons/pixelarticons/pencil'

const store = useSkillStore()

const pending = ref(null)
const deleting = ref(false)
const notice = ref('')

const skills = computed(() => store.skills ?? [])

async function remove() {
    if (!pending.value) return

    deleting.value = true
    const res = await store.deleteSkill(pending.value.id)
    deleting.value = false

    notice.value = res.ok ? 'borrado' : res.message
    pending.value = null

    if (res.ok) await track(store.getSkills())
}

onMounted(() => track(store.getSkills()))
</script>

<template>
    <div>
        <div class="dd-heading">
            <h1 class="dd-title">skills</h1>
            <RouterLink class="dd-btn dd-btn--tone" :to="{ name: 'skill_create' }">nueva</RouterLink>
        </div>

        <p v-if="store.error" class="dd-state dd-state--bad">
            no se pudo leer
            <span class="dd-state__detail">{{ store.error }}</span>
        </p>

        <section v-else-if="skills.length" class="dd-win">
            <div class="dd-win__bar">{{ skills.length }} en total</div>
            <ul class="dd-rows">
                <li v-for="skill in skills" :key="skill.id" class="dd-row">
                    <img
                        v-if="skill.icon"
                        class="dd-row__thumb dd-row__thumb--contain"
                        :src="fileUrl(skill.icon)"
                        alt=""
                        loading="lazy"
                    >
                    <span v-else class="dd-row__thumb dd-scan" aria-hidden="true"></span>

                    <div class="dd-row__text">
                        <span class="dd-row__name">{{ skill.name }}</span>
                        <span class="dd-row__meta">{{ skill.type?.name ?? 'sin tipo' }}, {{ skill.mastery }} de 100</span>
                    </div>

                    <div class="dd-row__actions">
                        <RouterLink class="dd-row__edit" :to="{ name: 'skill_edit', params: { id: skill.id } }" aria-label="Editar" title="editar">
                            <PencilIcon aria-hidden="true" />
                        </RouterLink>
                        <button type="button" class="dd-link-btn" @click="pending = skill">borrar</button>
                    </div>
                </li>
            </ul>
        </section>

        <p v-else-if="store.loaded" class="dd-state">vacío</p>

        <p v-if="notice" class="dd-notice" role="status">{{ notice }}</p>

        <ConfirmDialog
            :model-value="!!pending"
            title="borrar skill"
            :target="pending?.name ?? ''"
            :busy="deleting"
            @update:model-value="pending = null"
            @confirm="remove"
        />
    </div>
</template>
