<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useProjectStore, useTypeProjectStore } from '@/stores/Store';
import { isImage } from '@/utils/media';
import router from '@/router';
import Dropdown from '@/components/ui/Dropdown.vue';

const projectStore = useProjectStore()
const typeStore = useTypeProjectStore()

const name = ref('')
const description = ref('')
const file = ref<File | null>(null)
const cover = ref<File | null>(null)
const coverPreview = ref('')
const url = ref('')

const loadingTypes = ref(true)
const project_types = computed(() => typeStore.typeProjects)
const noTypes = computed(() => !loadingTypes.value && project_types.value.length === 0)

const selected = ref<string | number>('')

const selectedType = computed(() =>
    typeStore.typeProjects.find((t: any) => t.id === selected.value) ?? null,
)

const extensions = computed(() => {
    const raw = (selectedType.value as any)?.extension
    const list = Array.isArray(raw) ? raw : String(raw ?? '').split(',')
    return list.map((e: string) => e.trim().toLowerCase()).filter(Boolean)
})

const isLink = computed(() => extensions.value.includes('.url'))
const accept = computed(() => extensions.value.filter((e) => e !== '.url').join(','))
const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

const iconExt = computed(() =>
    (extensions.value[0] ?? '').replace(/^\./, ''),
)

const previewName = computed(() => {
    const slug = (name.value || 'sin-titulo').toLowerCase().trim().replace(/\s+/g, '-')
    return iconExt.value ? `${slug}.${iconExt.value}` : slug
})

const fileIsImage = computed(() => !!file.value && isImage(file.value.name))
const coverRequired = computed(() => !fileIsImage.value)

const ready = computed(() =>
    !!selectedType.value
    && !!name.value.trim()
    && !!description.value.trim()
    && (isLink.value ? !!url.value.trim() : !!file.value)
    && (!coverRequired.value || !!cover.value),
)


onMounted(async () => {
    await typeStore.getTypeProjects()
    loadingTypes.value = false
})

function clearCoverPreview() {
    if (coverPreview.value) URL.revokeObjectURL(coverPreview.value)
    coverPreview.value = ''
}

onBeforeUnmount(clearCoverPreview)

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement
    file.value = target.files?.[0] ?? null

    if (!cover.value) {
        clearCoverPreview()
        if (fileIsImage.value && file.value) {
            coverPreview.value = URL.createObjectURL(file.value)
        }
    }
}

function onCoverChange(event: Event) {
    const target = event.target as HTMLInputElement
    const picked = target.files?.[0] ?? null

    clearCoverPreview()
    cover.value = picked

    if (picked) coverPreview.value = URL.createObjectURL(picked)
    else if (fileIsImage.value && file.value) {
        coverPreview.value = URL.createObjectURL(file.value)
    }
}

async function createProject() {
    if (!selectedType.value) {
        failed.value = true
        message.value = 'selecciona un tipo del catálogo.'
        return
    }

    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const formData = new FormData()
    formData.append('name', name.value)
    formData.append('type', String(selected.value))
    formData.append('description', description.value)

    if (isLink.value) {
        formData.append('url', url.value)
    } else if (file.value) {
        formData.append('file', file.value)
    }

    if (cover.value) formData.append('cover', cover.value)

    const res = await projectStore.storeProject(formData)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    message.value = res.data?.message ?? 'guardado'
    setTimeout(() => router.push({ name: 'projects' }))
}
</script>

<template>
    <form class="forge" @submit.prevent="createProject">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body forge__body">
                <header>
                    <h1 class="t-heading">creación de proyecto</h1>
                    <p class="t-mute">describe la cosa antes de que se te olvide.</p>
                </header>

                <div class="forge__fields">
                    <div class="field">
                        <label class="field__label" for="p-type">tipo</label>
                        <Dropdown
                            id="p-type"
                            v-model="selected"
                            :options="project_types"
                            option-label="name"
                            option-value="id"
                            :disabled="loadingTypes || noTypes"
                            :placeholder="loadingTypes ? 'leyendo el catálogo...' : 'selecciona un tipo'"
                        />
                        <span v-if="errors.type" class="field__error">{{ errors.type[0] }}</span>
                        <span v-else-if="noTypes" class="field__error">
                            no hay tipos en el catálogo.
                            <RouterLink :to="{ name: 'typeproject_create' }">crea uno primero</RouterLink>
                        </span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="p-name">nombre</label>
                        <input
                            id="p-name"
                            v-model="name"
                            type="text"
                            class="input"
                            placeholder="sin título"
                            autocomplete="off"
                        >
                        <span v-if="errors.name" class="field__error">{{ errors.name[0] }}</span>
                        <span v-else class="field__hint">así se leerá el archivo en el escritorio.</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="p-description">descripcion</label>
                        <input
                            id="p-description"
                            v-model="description"
                            type="text"
                            class="input"
                            placeholder="sin descripción"
                            autocomplete="off"
                        >
                        <span v-if="errors.description" class="field__error">{{ errors.description[0] }}</span>
                        <span v-else class="field__hint">una línea; se lee en la ficha del proyecto.</span>
                    </div>

                    <div v-if="isLink" class="field">
                        <label class="field__label" for="p-url">enlace</label>
                        <input
                            id="p-url"
                            v-model="url"
                            type="url"
                            class="input"
                            placeholder="https://..."
                            autocomplete="off"
                        >
                        <span v-if="errors.url" class="field__error">{{ errors.url[0] }}</span>
                        <span v-else class="field__hint">
                            este tipo guarda un enlace en vez de un archivo.
                        </span>
                    </div>

                    <div v-else class="field">
                        <label class="field__label" for="p-file">archivo</label>
                        <input
                            id="p-file"
                            type="file"
                            :accept="accept || undefined"
                            class="input input--file"
                            @change="onFileChange"
                        >
                        <span v-if="errors.file" class="field__error">{{ errors.file[0] }}</span>
                        <span v-else class="field__hint">
                            {{ accept ? `${accept} ` : 'elige un tipo para saber qué acepta' }}
                        </span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="p-cover">portada</label>
                        <input
                            id="p-cover"
                            type="file"
                            accept="image/jpeg,image/png,image/gif,image/webp,image/avif"
                            class="input input--file"
                            @change="onCoverChange"
                        >
                        <span v-if="errors.cover" class="field__error">{{ errors.cover[0] }}</span>
                        <span v-else-if="!coverRequired" class="field__hint">
                            opcional: el archivo ya es una imagen y sirve de portada.
                        </span>
                        <span v-else class="field__hint">
                            es lo que se ve del proyecto en la carpeta. jpg, png, gif, webp o avif, hasta 8 MB
                        </span>
                    </div>
                </div>

                <footer class="forge__foot">
                    <RouterLink class="btn btn--ghost" :to="{ name: 'projects' }">cancelar</RouterLink>
                    <button
                        class="btn btn--primary"
                        type="submit"
                        :disabled="sending || !ready || loadingTypes || noTypes"
                    >
                        <span v-if="sending" class="loading-dots">enviando</span>
                        <span v-else>guardar</span>
                    </button>
                </footer>

                <p
                    v-if="message"
                    class="forge__msg"
                    :class="{ 'forge__msg--bad': failed }"
                    role="status"
                >
                    {{ failed ? '✕' : '▸' }} {{ message }}
                </p>
            </div>
        </section>

        <aside class="forge__side">
            <section class="panel">
                <header class="panel__head"></header>

                <div class="panel__body preview-shelf">
                    <span class="file-icon file-icon--tile" :data-ext="iconExt">
                        <span v-if="coverPreview" class="file-icon__shot" aria-hidden="true">
                            <img class="file-icon__img" :src="coverPreview" alt="">
                        </span>

                        <span v-else class="file-icon__glyph" aria-hidden="true">
                            <span class="file-icon__ext">{{ iconExt || '?' }}</span>
                        </span>

                        <span class="file-icon__label">{{ previewName }}</span>
                    </span>

                    <p class="t-mute preview-shelf__note">
                        {{ description || 'sin descripción' }}
                    </p>
                </div>
            </section>
        </aside>
    </form>
</template>

<style scoped>
.preview-shelf {
    display: grid;
    justify-items: center;
    gap: var(--sp-4);
    text-align: center;
}

.preview-shelf :deep(.file-icon--tile) {
    width: min(100%, 200px);
}

.preview-shelf__note {
    max-width: 32ch;
    overflow-wrap: anywhere;
}
</style>
