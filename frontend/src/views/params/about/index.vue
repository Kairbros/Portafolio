<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useAboutStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import LangField from '@/components/flash/LangField.vue'

const store = useAboutStore()

const body = ref('')
const bodyEn = ref('')
const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

const changed = computed(() =>
    body.value.trim() !== store.body.trim() || bodyEn.value.trim() !== store.body_en.trim(),
)

onMounted(async () => {
    await track(store.getAbout())
    body.value = store.body
    bodyEn.value = store.body_en
})

async function save() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const res = await store.updateAbout(body.value, bodyEn.value)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    body.value = store.body
    bodyEn.value = store.body_en
    message.value = 'guardado'
}
</script>

<template>
    <div>
        <div class="dd-heading">
            <h1 class="dd-title">sobre mí</h1>
            <RouterLink class="dd-btn" to="/about">ver en la web</RouterLink>
        </div>

        <p v-if="store.error" class="dd-state dd-state--bad">
            no se pudo leer
            <span class="dd-state__detail">{{ store.error }}</span>
        </p>

        <form v-else class="dd-win" @submit.prevent="save">
            <div class="dd-win__bar">texto</div>

            <div class="dd-form__fields">
                <LangField
                    id="about-body"
                    v-model="body"
                    v-model:en="bodyEn"
                    label="texto"
                    multiline
                    input-class="about-edit__text"
                    :error="errors.body?.[0]"
                    :error-en="errors.body_en?.[0]"
                />
            </div>

            <div class="dd-form__foot">
                <p v-if="message" class="dd-form__msg" :class="{ 'dd-form__msg--bad': failed }" role="status">
                    {{ message }}
                </p>
                <button class="dd-btn dd-btn--tone" type="submit" :disabled="sending || !changed || !body.trim()">
                    {{ sending ? 'guardando' : 'guardar' }}
                </button>
            </div>
        </form>
    </div>
</template>

<style scoped>
:deep(.about-edit__text) {
    height: 240px;
    min-height: 140px;
    resize: vertical;
    line-height: 1.7;
}
</style>
