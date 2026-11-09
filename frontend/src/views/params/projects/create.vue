<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectStore, useTypeProjectStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { coverUrl, formatSize } from '@/utils/media'
import LangField from '@/components/flash/LangField.vue'
import MarkdownField from '@/components/flash/MarkdownField.vue'
import TagInput from '@/components/ui/TagInput.vue'

type Link = { label: string; url: string }
type StoredFile = { id: number; label: string; original_name: string; size: number }

const props = defineProps<{ id?: string }>()

const router = useRouter()
const projectStore = useProjectStore()
const typeStore = useTypeProjectStore()

const projectId = ref(props.id ?? '')
const name = ref('')
const description = ref('')
const descriptionEn = ref('')
const body = ref('')
const bodyEn = ref('')
const selected = ref<string | number>('')
const status = ref('in_progress')
const year = ref<string | number>('')
const tags = ref<string[]>([])
const published = ref(false)
const links = ref<Link[]>([])
const cover = ref<File | null>(null)
const coverPreview = ref('')
const currentCoverUrl = ref('')
const files = ref<StoredFile[]>([])
const missing = ref(false)

const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

const fileLabel = ref('')
const newFile = ref<File | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)
const fileError = ref('')

const statuses = [
    { value: 'in_progress', label: 'en curso' },
    { value: 'finished', label: 'terminado' },
    { value: 'paused', label: 'en pausa' },
]

const types = computed(() => typeStore.typeProjects ?? [])
const noTypes = computed(() => typeStore.loaded && !types.value.length)
const selectedType = computed(() => types.value.find((t: any) => t.id === selected.value) ?? null)
const shownCover = computed(() => coverPreview.value || currentCoverUrl.value)
const ready = computed(() => !!selectedType.value && !!name.value.trim() && !!description.value.trim())

function setPreview(source: File | null) {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value)
    coverPreview.value = source ? URL.createObjectURL(source) : ''
}

function onCoverChange(event: Event) {
    cover.value = (event.target as HTMLInputElement).files?.[0] ?? null
    setPreview(cover.value)
}

function fill(item: any) {
    name.value = item.name ?? ''
    description.value = item.description ?? ''
    descriptionEn.value = item.description_en ?? ''
    body.value = item.body ?? ''
    bodyEn.value = item.body_en ?? ''
    selected.value = item.type_project_id ?? ''
    status.value = item.status ?? 'in_progress'
    year.value = item.year ?? ''
    tags.value = item.tags ?? []
    published.value = !!item.published
    links.value = (item.links ?? []).map((link: Link) => ({ label: link.label, url: link.url }))
    files.value = item.files ?? []
    currentCoverUrl.value = coverUrl(item)
}

onMounted(async () => {
    await track(typeStore.ensureTypeProjects())
    if (!projectId.value) return

    const res = await track(projectStore.getProjectForEdit(projectId.value))
    if (!res.ok) {
        missing.value = true
        return
    }
    fill(res.data)
})

onBeforeUnmount(() => setPreview(null))

async function save() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const data = new FormData()
    data.append('name', name.value)
    data.append('type', String(selected.value))
    data.append('description', description.value)
    data.append('description_en', descriptionEn.value)
    data.append('body', body.value)
    data.append('body_en', bodyEn.value)
    data.append('status', status.value)
    data.append('year', String(year.value ?? ''))
    data.append('published', published.value ? '1' : '0')
    tags.value.forEach((tag, i) => data.append(`tags[${i}]`, tag))
    links.value.forEach((link, i) => {
        data.append(`links[${i}][label]`, link.label)
        data.append(`links[${i}][url]`, link.url)
    })
    if (cover.value) data.append('cover', cover.value)

    const res = projectId.value
        ? await projectStore.updateProject(projectId.value, data)
        : await projectStore.storeProject(data)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    if (!projectId.value) {
        projectId.value = String(res.data.project.id)
        router.replace({ name: 'project_edit', params: { id: projectId.value } })
    }
    currentCoverUrl.value = coverUrl(res.data.project)
    cover.value = null
    setPreview(null)
    message.value = 'guardado'
}

function uploadImage(file: File) {
    return projectStore.uploadProjectImage(projectId.value, file)
}

function onFileChange(event: Event) {
    newFile.value = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function addFile() {
    if (!newFile.value || !fileLabel.value.trim()) return

    uploading.value = true
    fileError.value = ''

    const data = new FormData()
    data.append('label', fileLabel.value)
    data.append('file', newFile.value)

    const res = await projectStore.addProjectFile(projectId.value, data)
    uploading.value = false

    if (!res.ok) {
        fileError.value = res.errors.file?.[0] ?? res.errors.label?.[0] ?? res.message
        return
    }

    files.value.push(res.data)
    fileLabel.value = ''
    newFile.value = null
    if (fileInput.value) fileInput.value.value = ''
}

async function removeFile(file: StoredFile) {
    fileError.value = ''
    const res = await projectStore.deleteProjectFile(file.id)
    if (!res.ok) {
        fileError.value = res.message
        return
    }
    files.value = files.value.filter((item) => item.id !== file.id)
}
</script>

<template>
    <p v-if="missing" class="dd-state dd-state--bad">
        no existe
        <RouterLink class="dd-state__detail" :to="{ name: 'projects' }">volver</RouterLink>
    </p>

    <div v-else class="dd-form">
        <form class="dd-win" @submit.prevent="save">
            <h1 class="dd-win__bar">{{ projectId ? 'editar proyecto' : 'nuevo proyecto' }}</h1>

            <div class="dd-form__fields">
                <div class="dd-field">
                    <label class="dd-field__label" for="p-type">tipo</label>
                    <select id="p-type" v-model="selected" class="dd-input" :disabled="noTypes">
                        <option value="" disabled>elige uno</option>
                        <option v-for="type in types" :key="type.id" :value="type.id">{{ type.name }}</option>
                    </select>
                    <span v-if="errors.type" class="dd-field__error">{{ errors.type[0] }}</span>
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
                    label="resumen"
                    multiline
                    :error="errors.description?.[0]"
                    :error-en="errors.description_en?.[0]"
                />

                <div class="dd-form__pair">
                    <div class="dd-field">
                        <label class="dd-field__label" for="p-status">estado</label>
                        <select id="p-status" v-model="status" class="dd-input">
                            <option v-for="option in statuses" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>

                    <div class="dd-field">
                        <label class="dd-field__label" for="p-year">año</label>
                        <input id="p-year" v-model="year" type="number" min="1990" max="2100" class="dd-input">
                        <span v-if="errors.year" class="dd-field__error">{{ errors.year[0] }}</span>
                    </div>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="p-tags">etiquetas</label>
                    <TagInput id="p-tags" v-model="tags" placeholder="Unity, C#, Vue" />
                    <span v-if="errors.tags" class="dd-field__error">{{ errors.tags[0] }}</span>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="p-cover">portada</label>
                    <input
                        id="p-cover"
                        type="file"
                        accept="image/jpeg,image/png,image/gif,image/webp,image/avif"
                        class="dd-input"
                        @change="onCoverChange"
                    >
                    <span v-if="errors.cover" class="dd-field__error">{{ errors.cover[0] }}</span>
                </div>

                <MarkdownField
                    id="p-body"
                    v-model="body"
                    v-model:en="bodyEn"
                    label="artículo"
                    :upload="projectId ? uploadImage : undefined"
                    :error="errors.body?.[0]"
                    :error-en="errors.body_en?.[0]"
                />

                <div class="dd-field">
                    <span class="dd-field__label">enlaces</span>

                    <div v-for="(link, i) in links" :key="i" class="dd-links__group">
                        <div class="dd-links__row">
                            <input v-model="link.label" type="text" class="dd-input" placeholder="GitHub" maxlength="60" autocomplete="off" aria-label="nombre del enlace">
                            <input v-model="link.url" type="url" class="dd-input" placeholder="https://" autocomplete="off" aria-label="dirección del enlace">
                            <button type="button" class="dd-link-btn" @click="links.splice(i, 1)">quitar</button>
                        </div>
                        <span v-if="errors[`links.${i}.label`]" class="dd-field__error">{{ errors[`links.${i}.label`]?.[0] }}</span>
                        <span v-if="errors[`links.${i}.url`]" class="dd-field__error">{{ errors[`links.${i}.url`]?.[0] }}</span>
                    </div>

                    <button type="button" class="dd-btn dd-links__add" :disabled="links.length >= 12" @click="links.push({ label: '', url: '' })">
                        añadir enlace
                    </button>
                </div>

                <div class="dd-links__row dd-links__row--publish">
                    <span class="dd-field__label">publicado</span>
                    <button type="button" class="dd-switch" :aria-pressed="published" @click="published = !published">
                        {{ published ? 'sí' : 'no' }}
                    </button>
                </div>
            </div>

            <div class="dd-form__foot">
                <p v-if="message" class="dd-form__msg" :class="{ 'dd-form__msg--bad': failed }" role="status">
                    {{ message }}
                </p>
                <RouterLink class="dd-btn" :to="{ name: 'projects' }">volver</RouterLink>
                <button class="dd-btn dd-btn--tone" type="submit" :disabled="sending || !ready">
                    {{ sending ? 'guardando' : 'guardar' }}
                </button>
            </div>
        </form>

        <div class="dd-form__aside">
            <section class="dd-win" aria-label="Vista previa">
                <div class="dd-win__bar">vista previa</div>
                <img v-if="shownCover" class="dd-preview-img dd-preview-img--wide" :src="shownCover" alt="">
                <div v-else class="dd-preview-img dd-preview-img--wide dd-scan"></div>
                <div class="dd-form__preview">
                    <span>{{ name || 'sin nombre' }}</span>
                    <span class="dd-small">{{ selectedType?.name ?? 'sin tipo' }}</span>
                </div>
            </section>

            <section v-if="projectId" class="dd-win">
                <div class="dd-win__bar">descargas</div>

                <ul v-if="files.length" class="dd-rows">
                    <li v-for="file in files" :key="file.id" class="dd-files__row">
                        <div class="dd-row__text">
                            <span class="dd-row__name">{{ file.label }}</span>
                            <span class="dd-row__meta">{{ formatSize(file.size) }}</span>
                        </div>
                        <button type="button" class="dd-link-btn" @click="removeFile(file)">borrar</button>
                    </li>
                </ul>

                <form class="dd-files__add" @submit.prevent="addFile">
                    <input v-model="fileLabel" type="text" class="dd-input" placeholder="Windows v1.0" maxlength="80" autocomplete="off" aria-label="nombre de la descarga">
                    <input ref="fileInput" type="file" class="dd-input" aria-label="archivo" @change="onFileChange">
                    <span v-if="fileError" class="dd-field__error">{{ fileError }}</span>
                    <button class="dd-btn" type="submit" :disabled="uploading || !newFile || !fileLabel.trim()">
                        {{ uploading ? 'subiendo' : 'subir' }}
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>
