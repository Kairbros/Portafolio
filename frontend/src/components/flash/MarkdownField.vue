<script setup lang="ts">
import { computed, nextTick, ref } from 'vue'
import { renderMarkdown } from '@/utils/markdown'

const props = defineProps<{
    id: string
    label: string
    upload?: (file: File) => Promise<{ ok: boolean; path?: string; message?: string }>
    error?: string
    errorEn?: string
}>()

const es = defineModel<string>({ required: true })
const en = defineModel<string>('en', { required: true })

const tab = ref<'es' | 'en'>('es')
const previewing = ref(false)
const uploading = ref(false)
const uploadError = ref('')
const area = ref<HTMLTextAreaElement | null>(null)

const text = computed({
    get: () => (tab.value === 'es' ? es.value : en.value),
    set: (value: string) => {
        if (tab.value === 'es') es.value = value
        else en.value = value
    },
})

const html = computed(() => (previewing.value ? renderMarkdown(text.value) : ''))

async function insertAtCursor(snippet: string) {
    const start = area.value?.selectionStart ?? text.value.length
    const end = area.value?.selectionEnd ?? start
    text.value = text.value.slice(0, start) + snippet + text.value.slice(end)

    await nextTick()
    const caret = start + snippet.length
    area.value?.focus()
    area.value?.setSelectionRange(caret, caret)
}

async function onImage(event: Event) {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]
    input.value = ''
    if (!file || !props.upload) return

    uploading.value = true
    uploadError.value = ''
    const res = await props.upload(file)
    uploading.value = false

    if (!res.ok || !res.path) {
        uploadError.value = res.message ?? 'no se pudo subir'
        return
    }

    previewing.value = false
    await nextTick()
    await insertAtCursor(`\n![](/storage/${res.path})\n`)
}
</script>

<template>
    <div class="dd-field">
        <div class="dd-field__head">
            <label class="dd-field__label" :for="id">{{ label }}</label>

            <div class="dd-langtabs" role="group" :aria-label="`idioma de ${label}`">
                <button type="button" class="dd-langtabs__btn" :aria-pressed="tab === 'es'" @click="tab = 'es'">
                    español
                </button>
                <button
                    type="button"
                    class="dd-langtabs__btn"
                    :class="{ 'dd-langtabs__btn--empty': !en.trim() }"
                    :aria-pressed="tab === 'en'"
                    @click="tab = 'en'"
                >
                    inglés
                </button>
            </div>
        </div>

        <div class="dd-md__tools">
            <div class="dd-langtabs" role="group" aria-label="modo">
                <button type="button" class="dd-langtabs__btn" :aria-pressed="!previewing" @click="previewing = false">
                    escribir
                </button>
                <button type="button" class="dd-langtabs__btn" :aria-pressed="previewing" @click="previewing = true">
                    vista previa
                </button>
            </div>

            <label v-if="upload" class="dd-btn dd-md__image">
                {{ uploading ? 'subiendo' : 'imagen' }}
                <input
                    type="file"
                    class="dd-md__file"
                    accept="image/jpeg,image/png,image/gif,image/webp,image/avif"
                    :disabled="uploading"
                    @change="onImage"
                >
            </label>
        </div>

        <textarea
            v-show="!previewing"
            :id="id"
            ref="area"
            v-model="text"
            class="dd-input dd-md__area"
            rows="16"
            :lang="tab"
        ></textarea>

        <div v-if="previewing" class="dd-prose dd-md__preview" v-html="html"></div>

        <span v-if="error" class="dd-field__error">{{ error }}</span>
        <span v-if="errorEn" class="dd-field__error">inglés: {{ errorEn }}</span>
        <span v-if="uploadError" class="dd-field__error">{{ uploadError }}</span>
    </div>
</template>
