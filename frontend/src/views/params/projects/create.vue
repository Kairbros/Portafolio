<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectStore, useTypeProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { coverUrl, isImage } from '@/utils/media'
import LangField from '@/components/flash/LangField.vue'

const props = defineProps<{ id?: string }>()

const router = useRouter()
const projectStore = useProjectStore()
const typeStore = useTypeProjectStore()

const name = ref('')
const description = ref('')
const descriptionEn = ref('')
const selected = ref<string | number>('')
const file = ref<File | null>(null)
const cover = ref<File | null>(null)
const coverPreview = ref('')
const url = ref('')
const currentFolder = ref('')
const currentCover = ref('')
const currentCoverUrl = ref('')
const missing = ref(false)

const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

const types = computed(() => typeStore.typeProjects ?? [])
const noTypes = computed(() => typeStore.loaded && !types.value.length)

const selectedType = computed(() => types.value.find((t: any) => t.id === selected.value) ?? null)

const extensions = computed(() => {
    const raw = (selectedType.value as any)?.extension
    const list = Array.isArray(raw) ? raw : String(raw ?? '').split(',')
    return list.map((e: string) => e.trim().toLowerCase()).filter(Boolean)
})

const isLink = computed(() => extensions.value.includes('.url'))
const accept = computed(() => extensions.value.filter((e) => e !== '.url').join(','))

const fileIsImage = computed(() => !!file.value && isImage(file.value.name))
const replacing = computed(() => (isLink.value ? !!url.value.trim() && url.value !== currentFolder.value : !!file.value))
// al editar, la portada actual se queda salvo que fuera el mismo archivo que se reemplaza
const coverSurvives = computed(() =>
    !!props.id && !!currentCover.value && (!replacing.value || currentCover.value !== currentFolder.value),
)
const coverRequired = computed(() => !fileIsImage.value && !coverSurvives.value)
const shownCover = computed(() => coverPreview.value || (coverSurvives.value ? currentCoverUrl.value : ''))

const ready = computed(() =>
    !!selectedType.value
    && !!name.value.trim()
    && !!description.value.trim()
    && (isLink.value ? !!url.value.trim() : !!file.value || !!props.id)
    && (!coverRequired.value || !!cover.value),
)

function setPreview(source: File | null) {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value)
    coverPreview.value = source ? URL.createObjectURL(source) : ''
}

function onFileChange(event: Event) {
    file.value = (event.target as HTMLInputElement).files?.[0] ?? null
    if (!cover.value) setPreview(fileIsImage.value ? file.value : null)
}

function onCoverChange(event: Event) {
    cover.value = (event.target as HTMLInputElement).files?.[0] ?? null
    setPreview(cover.value ?? (fileIsImage.value ? file.value : null))
}

onMounted(async () => {
    if (!props.id) {
        await track(typeStore.ensureTypeProjects())
        return
    }
    await track(Promise.all([typeStore.ensureTypeProjects(), projectStore.ensureProject(props.id)]))
    const item = projectStore.project
    if (projectStore.error || !item || String(item.id) !== props.id) {
        missing.value = true
        return
    }
    name.value = item.name ?? ''
    description.value = item.description ?? ''
    descriptionEn.value = item.description_en ?? ''
    selected.value = item.type_project_id ?? ''
    currentFolder.value = String(item.folder ?? '')
    currentCover.value = String(item.cover ?? '')
    currentCoverUrl.value = coverUrl(item)
    if (isLink.value) url.value = currentFolder.value
})
onBeforeUnmount(() => setPreview(null))

async function save() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const data = new FormData()
    data.append('name', name.value)
    if (!props.id) data.append('type', String(selected.value))
    data.append('description', description.value)
    data.append('description_en', descriptionEn.value)
    if (isLink.value) data.append('url', url.value)
    else if (file.value) data.append('file', file.value)
    if (cover.value) data.append('cover', cover.value)

    const res = props.id
        ? await projectStore.updateProject(props.id, data)
        : await projectStore.storeProject(data)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    router.push({ name: 'projects' })
}
</script>

<template>
    <p v-if="missing" class="dd-state dd-state--bad">
        no existe
        <RouterLink class="dd-state__detail" :to="{ name: 'projects' }">volver</RouterLink>
    </p>

    <form v-else class="dd-form" @submit.prevent="save">
        <section class="dd-win">
            <h1 class="dd-win__bar">{{ id ? 'editar proyecto' : 'nuevo proyecto' }}</h1>

            <div class="dd-form__fields">
                <div class="dd-field">
                    <label class="dd-field__label" for="p-type">tipo</label>
                    <select id="p-type" v-model="selected" class="dd-input" :disabled="noTypes || !!id">
                        <option value="" disabled>elige uno</option>
                        <option v-for="type in types" :key="type.id" :value="type.id">{{ type.name }}</option>
                    </select>
                    <span v-if="errors.type" class="dd-field__error">{{ errors.type[0] }}</span>
                    <span v-else-if="id" class="dd-field__hint">el tipo no se cambia</span>
                    <span v-else-if="noTypes" class="dd-field__hint">
                        todavía no hay tipos,
                        <RouterLink :to="{ name: 'typeproject_create' }">crea uno</RouterLink>
                    </span>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="p-name">nombre</label>
                    <input id="p-name" v-model="name" type="text" class="dd-input" autocomplete="off">
                    <span v-if="errors.name" class="dd-field__error">{{ errors.name[0] }}</span>
                </div>

                <LangField
                    id="p-description"
                    v-model="description"
                    v-model:en="descriptionEn"
                    label="descripción"
                    multiline
                    :error="errors.description?.[0]"
                    :error-en="errors.description_en?.[0]"
                />

                <div v-if="isLink" class="dd-field">
                    <label class="dd-field__label" for="p-url">enlace</label>
                    <input id="p-url" v-model="url" type="url" class="dd-input" placeholder="https://" autocomplete="off">
                    <span v-if="errors.url" class="dd-field__error">{{ errors.url[0] }}</span>
                </div>

                <div v-else class="dd-field">
                    <label class="dd-field__label" for="p-file">archivo</label>
                    <input id="p-file" type="file" :accept="accept || undefined" class="dd-input" @change="onFileChange">
                    <span v-if="errors.file" class="dd-field__error">{{ errors.file[0] }}</span>
                    <span v-else-if="id" class="dd-field__hint">vacío deja el actual{{ accept ? `; ${accept}` : '' }}</span>
                    <span v-else-if="accept" class="dd-field__hint">{{ accept }}</span>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="p-cover">
                        portada{{ coverRequired ? '' : ' (opcional)' }}
                    </label>
                    <input
                        id="p-cover"
                        type="file"
                        accept="image/jpeg,image/png,image/gif,image/webp,image/avif"
                        class="dd-input"
                        @change="onCoverChange"
                    >
                    <span v-if="errors.cover" class="dd-field__error">{{ errors.cover[0] }}</span>
                    <span v-else class="dd-field__hint">
                        {{ coverSurvives ? 'vacío deja la actual; ' : '' }}jpg, png, gif, webp o avif, hasta 8 MB
                    </span>
                </div>
            </div>

            <div class="dd-form__foot">
                <p v-if="message" class="dd-form__msg" :class="{ 'dd-form__msg--bad': failed }" role="status">
                    {{ message }}
                </p>
                <RouterLink class="dd-btn" :to="{ name: 'projects' }">cancelar</RouterLink>
                <button class="dd-btn dd-btn--tone" type="submit" :disabled="sending || !ready">
                    {{ sending ? 'guardando' : 'guardar' }}
                </button>
            </div>
        </section>

        <section class="dd-win" aria-label="Vista previa">
            <div class="dd-win__bar">vista previa</div>
            <img v-if="shownCover" class="dd-preview-img dd-preview-img--wide" :src="shownCover" alt="">
            <div v-else class="dd-preview-img dd-preview-img--wide dd-scan"></div>
            <div class="dd-form__preview">
                <span>{{ name || 'sin nombre' }}</span>
                <span class="dd-small">{{ selectedType?.name ?? 'sin tipo' }}</span>
            </div>
        </section>
    </form>
</template>
