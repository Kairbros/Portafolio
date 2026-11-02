<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { useBackgroundImgStore } from '@/stores/Store'
import router from '@/router'

const store = useBackgroundImgStore()

const file = ref<File | null>(null)
const preview = ref('')
const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

function clearPreview() {
    if (preview.value) URL.revokeObjectURL(preview.value)
    preview.value = ''
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement
    const picked = target.files?.[0] ?? null

    clearPreview()
    file.value = picked
    if (picked) preview.value = URL.createObjectURL(picked)
}

onBeforeUnmount(clearPreview)

async function upload() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const formData = new FormData()
    if (file.value) formData.append('img', file.value)

    const res = await store.storeBackgroundImg(formData)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    message.value = res.data?.message ?? 'guardado'
    await store.getBackgroundImgs()
    setTimeout(() => router.push({ name: 'backgrounds' }))
}
</script>

<template>
    <form class="forge" @submit.prevent="upload">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body forge__body">
                <header>
                    <h1 class="t-heading">subir fondo</h1>
                    <p class="t-mute">se verá detrás de las carpetas, bajo el efecto de tele.</p>
                </header>

                <div class="forge__fields">
                    <div class="field">
                        <label class="field__label" for="bg-file">imagen</label>
                        <input
                            id="bg-file"
                            type="file"
                            accept="image/jpeg,image/png,image/gif,image/svg+xml"
                            class="input input--file"
                            @change="onFileChange"
                        >
                        <span v-if="errors.img" class="field__error">{{ errors.img[0] }}</span>
                        <span v-else class="field__hint">jpg, png, gif o svg, hasta 32 MB</span>
                    </div>
                </div>

                <footer class="forge__foot">
                    <RouterLink class="btn btn--ghost" :to="{ name: 'backgrounds' }">cancelar</RouterLink>
                    <button class="btn btn--primary" type="submit" :disabled="sending || !file">
                        <span v-if="sending" class="loading-dots">subiendo</span>
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

                <div class="panel__body">
                    <div class="preview">
                        <template v-if="preview">
                            <img class="preview__img" :src="preview" alt="">
                            <span class="preview__crt" aria-hidden="true"></span>
                        </template>

                        <div v-else class="preview__empty">
                            <span class="preview__empty-glyph fx-flicker" aria-hidden="true">◌</span>
                            <span>elige una imagen</span>
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </form>
</template>
