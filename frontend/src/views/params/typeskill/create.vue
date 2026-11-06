<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useTypeSkillStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import LangField from '@/components/flash/LangField.vue'

const props = defineProps<{ id?: string }>()

const router = useRouter()
const store = useTypeSkillStore()

const name = ref('')
const nameEn = ref('')
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
    nameEn.value = item.name_en ?? ''
})

async function save() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const payload = { name: name.value, name_en: nameEn.value }
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
                <LangField
                    id="ts-name"
                    v-model="name"
                    v-model:en="nameEn"
                    label="nombre"
                    placeholder="lenguajes"
                    :error="errors.name?.[0]"
                    :error-en="errors.name_en?.[0]"
                />
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
