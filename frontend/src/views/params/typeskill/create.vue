<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useTypeSkillStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'

const props = defineProps<{ id?: string }>()

const router = useRouter()
const store = useTypeSkillStore()

const name = ref('')
const missing = ref(false)
const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

onMounted(async () => {
    if (!props.id) return
    await track(store.ensureTypeSkills())
    const item = store.typeSkills.find((t: any) => String(t.id) === props.id)
    if (!item) {
        missing.value = true
        return
    }
    name.value = item.name ?? ''
})

async function save() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const payload = { name: name.value }
    const res = props.id
        ? await store.updateTypeSkill(props.id, payload)
        : await store.storeTypeSkill(payload)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    router.push({ name: 'typeskills' })
}
</script>

<template>
    <p v-if="missing" class="dd-state dd-state--bad">
        no existe
        <RouterLink class="dd-state__detail" :to="{ name: 'typeskills' }">volver</RouterLink>
    </p>

    <form v-else class="dd-form" @submit.prevent="save">
        <section class="dd-win">
            <h1 class="dd-win__bar">{{ id ? 'editar tipo de skill' : 'nuevo tipo de skill' }}</h1>

            <div class="dd-form__fields">
                <div class="dd-field">
                    <label class="dd-field__label" for="ts-name">nombre</label>
                    <input id="ts-name" v-model="name" type="text" class="dd-input" placeholder="lenguajes" autocomplete="off">
                    <span v-if="errors.name" class="dd-field__error">{{ errors.name[0] }}</span>
                </div>
            </div>

            <div class="dd-form__foot">
                <p v-if="message" class="dd-form__msg" :class="{ 'dd-form__msg--bad': failed }" role="status">
                    {{ message }}
                </p>
                <RouterLink class="dd-btn" :to="{ name: 'typeskills' }">cancelar</RouterLink>
                <button class="dd-btn dd-btn--tone" type="submit" :disabled="sending || !name.trim()">
                    {{ sending ? 'guardando' : 'guardar' }}
                </button>
            </div>
        </section>
    </form>
</template>
