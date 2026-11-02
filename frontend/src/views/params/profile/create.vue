<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { useProfileImgStore } from '@/stores/Store'
import router from '@/router'

const store = useProfileImgStore()

const file = ref<File | null>(null)
const preview = ref('')
const size = ref({ width: 0, height: 0 })
const isSquare = ref(true)
const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

function clearPreview() {
    if (preview.value) URL.revokeObjectURL(preview.value)
    preview.value = ''
    size.value = { width: 0, height: 0 }
    isSquare.value = true
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement
    const picked = target.files?.[0] ?? null

    clearPreview()
    file.value = picked
    if (!picked) return

    preview.value = URL.createObjectURL(picked)

    const probe = new Image()
    probe.onload = () => {
        size.value = { width: probe.width, height: probe.height }
        isSquare.value = probe.width === probe.height
    }
    probe.src = preview.value
}

onBeforeUnmount(clearPreview)

async function upload() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const formData = new FormData()
    if (file.value) formData.append('img', file.value)

    const res = await store.storeProfileImg(formData)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    message.value = res.data?.message ?? 'guardado'
    await store.getProfileImgs()
    setTimeout(() => router.push({ name: 'profiles' }))
}
</script>

<template>
    <form class="forge" @submit.prevent="upload">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body forge__body">
                <header>
                    <h1 class="t-heading">subir retrato</h1>
                    <p class="t-mute">se verá en el inicio, junto a tu nombre.</p>
                </header>

                <div class="forge__fields">
                    <div class="field">
                        <label class="field__label" for="pf-file">imagen</label>
                        <input
                            id="pf-file"
                            type="file"
                            accept="image/jpeg,image/png,image/gif"
                            class="input input--file"
                            @change="onFileChange"
                        >
                        <span v-if="errors.img" class="field__error">{{ errors.img[0] }}</span>
                        <span v-else-if="!isSquare" class="field__error">
                            tiene que ser cuadrada. Esta es {{ size.width }}×{{ size.height }}.
                        </span>
                        <span v-else class="field__hint">jpg, png o gif, cuadrada, hasta 32 MB</span>
                    </div>
                </div>

                <footer class="forge__foot">
                    <RouterLink class="btn btn--ghost" :to="{ name: 'profiles' }">cancelar</RouterLink>
                    <button
                        class="btn btn--primary"
                        type="submit"
                        :disabled="sending || !file || !isSquare"
                    >
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
                    <div class="preview preview--square" :class="{ 'preview--bad': !isSquare }">
                        <img v-if="preview" class="preview__img" :src="preview" alt="">
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
