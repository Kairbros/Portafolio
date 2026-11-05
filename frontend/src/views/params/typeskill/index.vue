<script setup>
import { computed, onMounted, ref } from 'vue'
import { useTypeSkillStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import PencilIcon from '~icons/pixelarticons/pencil'

const store = useTypeSkillStore()

const pending = ref(null)
const deleting = ref(false)
const notice = ref('')

const types = computed(() => store.typeSkills ?? [])

async function remove() {
    if (!pending.value) return

    deleting.value = true
    const res = await store.deleteTypeSkill(pending.value.id)
    deleting.value = false

    notice.value = res.ok ? 'borrado' : res.message
    pending.value = null

    if (res.ok) await track(store.getTypeSkills())
}

onMounted(() => track(store.getTypeSkills()))
</script>

<template>
    <div>
        <div class="dd-heading">
            <h1 class="dd-title">tipos de skill</h1>
            <RouterLink class="dd-btn dd-btn--tone" :to="{ name: 'typeskill_create' }">nuevo</RouterLink>
        </div>

        <p v-if="store.error" class="dd-state dd-state--bad">
            no se pudo leer
            <span class="dd-state__detail">{{ store.error }}</span>
        </p>

        <section v-else-if="types.length" class="dd-win">
            <div class="dd-win__bar">{{ types.length }} en total</div>
            <ul class="dd-rows">
                <li v-for="type in types" :key="type.id" class="dd-row dd-row--plain">
                    <div class="dd-row__text">
                        <span class="dd-row__name">{{ type.name }}</span>
                    </div>

                    <div class="dd-row__actions">
                        <RouterLink class="dd-row__edit" :to="{ name: 'typeskill_edit', params: { id: type.id } }" aria-label="Editar" title="editar">
                            <PencilIcon aria-hidden="true" />
                        </RouterLink>
                        <span v-if="type.skills_count" class="dd-row__meta">{{ type.skills_count }} en uso</span>
                        <button v-else type="button" class="dd-link-btn" @click="pending = type">borrar</button>
                    </div>
                </li>
            </ul>
        </section>

        <p v-else-if="store.loaded" class="dd-state">vacío</p>

        <p v-if="notice" class="dd-notice" role="status">{{ notice }}</p>

        <ConfirmDialog
            :model-value="!!pending"
            title="borrar tipo de skill"
            :target="pending?.name ?? ''"
            :busy="deleting"
            @update:model-value="pending = null"
            @confirm="remove"
        />
    </div>
</template>
