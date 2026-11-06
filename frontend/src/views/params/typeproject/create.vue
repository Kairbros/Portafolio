<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useTypeProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import TagInput from '@/components/ui/TagInput.vue'
import LangField from '@/components/flash/LangField.vue'

const props = defineProps<{ id?: string }>()

const router = useRouter()
const store = useTypeProjectStore()

const name = ref('')
const description = ref('')
const nameEn = ref('')
const descriptionEn = ref('')
const extensions = ref<string[]>([])
const missing = ref(false)
const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

function normalizeExtension(raw: string) {
    const clean = raw.trim().toLowerCase().replace(/^\.+/, '')
    return clean ? `.${clean}` : ''
}

onMounted(async () => {
    if (!props.id) return
    await track(store.ensureTypeProjects())
    const item = store.typeProjects.find((t: any) => String(t.id) === props.id)
    if (!item) {
        missing.value = true
        return
    }
    name.value = item.name ?? ''
    description.value = item.description ?? ''
    nameEn.value = item.name_en ?? ''
    descriptionEn.value = item.description_en ?? ''
    const raw = Array.isArray(item.extension) ? item.extension : String(item.extension ?? '').split(',')
    extensions.value = raw.map(normalizeExtension).filter(Boolean)
})

async function save() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const payload = {
        name: name.value,
        description: description.value,
        name_en: nameEn.value,
        description_en: descriptionEn.value,
        extension: extensions.value.join(','),
    }
    const res = props.id
        ? await store.updateTypeProject(props.id, payload)
        : await store.storeTypeProject(payload)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    router.push({ name: 'types' })
}
</script>

<template>
    <p v-if="missing" class="dd-state dd-state--bad">
        no existe
        <RouterLink class="dd-state__detail" :to="{ name: 'types' }">volver</RouterLink>
    </p>

    <form v-else class="dd-form" @submit.prevent="save">
        <section class="dd-win">
            <h1 class="dd-win__bar">{{ id ? 'editar tipo' : 'nuevo tipo' }}</h1>

            <div class="dd-form__fields">
                <LangField
                    id="t-name"
                    v-model="name"
                    v-model:en="nameEn"
                    label="nombre"
                    placeholder="juegos"
                    :error="errors.name?.[0]"
                    :error-en="errors.name_en?.[0]"
                />

                <LangField
                    id="t-desc"
                    v-model="description"
                    v-model:en="descriptionEn"
                    label="descripción"
                    :error="errors.description?.[0]"
                    :error-en="errors.description_en?.[0]"
                />

                <div class="dd-field">
                    <label class="dd-field__label" for="t-ext">extensiones</label>
                    <TagInput id="t-ext" v-model="extensions" :normalize="normalizeExtension" placeholder=".zip, .png, .url" />
                    <span v-if="errors.extension" class="dd-field__error">{{ errors.extension[0] }}</span>
                    <span v-else class="dd-field__hint">vacío acepta cualquier archivo; .url guarda un enlace</span>
                </div>
            </div>

            <div class="dd-form__foot">
                <p v-if="message" class="dd-form__msg" :class="{ 'dd-form__msg--bad': failed }" role="status">
                    {{ message }}
                </p>
                <RouterLink class="dd-btn" :to="{ name: 'types' }">cancelar</RouterLink>
                <button class="dd-btn dd-btn--tone" type="submit" :disabled="sending || !name.trim()">
                    {{ sending ? 'guardando' : 'guardar' }}
                </button>
            </div>
        </section>
    </form>
</template>
