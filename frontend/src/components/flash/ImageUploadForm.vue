<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'

type Result = { ok: boolean; message?: string; errors?: Record<string, string[]> }

const props = withDefaults(defineProps<{
  title: string
  back: string
  accept: string
  hint: string
  square?: boolean
  shape?: 'wide' | 'square' | 'contain'
  upload: (data: FormData) => Promise<Result>
}>(), {
  square: false,
  shape: 'wide',
})

const router = useRouter()

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
  isSquare.value = true
}

function onFileChange(event: Event) {
  const picked = (event.target as HTMLInputElement).files?.[0] ?? null

  clearPreview()
  file.value = picked
  if (!picked) return

  preview.value = URL.createObjectURL(picked)

  if (props.square) {
    const probe = new Image()
    probe.onload = () => {
      size.value = { width: probe.width, height: probe.height }
      isSquare.value = probe.width === probe.height
    }
    probe.src = preview.value
  }
}

onBeforeUnmount(clearPreview)

async function save() {
  sending.value = true
  message.value = ''
  failed.value = false
  errors.value = {}

  const data = new FormData()
  if (file.value) data.append('img', file.value)

  const res = await props.upload(data)
  sending.value = false

  if (!res.ok) {
    failed.value = true
    message.value = res.message ?? 'no se pudo guardar'
    errors.value = res.errors ?? {}
    return
  }

  router.push({ name: props.back })
}
</script>

<template>
  <form class="dd-form" @submit.prevent="save">
    <section class="dd-win">
      <h1 class="dd-win__bar">{{ title }}</h1>

      <div class="dd-form__fields">
        <div class="dd-field">
          <label class="dd-field__label" for="upload-file">imagen</label>
          <input id="upload-file" type="file" :accept="accept" class="dd-input" @change="onFileChange">
          <span v-if="errors.img" class="dd-field__error">{{ errors.img[0] }}</span>
          <span v-else-if="!isSquare" class="dd-field__error">
            tiene que ser cuadrada, esta es de {{ size.width }} por {{ size.height }}
          </span>
          <span v-else class="dd-field__hint">{{ hint }}</span>
        </div>
      </div>

      <div class="dd-form__foot">
        <p v-if="message" class="dd-form__msg" :class="{ 'dd-form__msg--bad': failed }" role="status">
          {{ message }}
        </p>
        <RouterLink class="dd-btn" :to="{ name: back }">cancelar</RouterLink>
        <button class="dd-btn dd-btn--tone" type="submit" :disabled="sending || !file || !isSquare">
          {{ sending ? 'subiendo' : 'guardar' }}
        </button>
      </div>
    </section>

    <section class="dd-win" aria-label="Vista previa">
      <div class="dd-win__bar">vista previa</div>
      <img
        v-if="preview"
        class="dd-preview-img"
        :class="{ 'dd-preview-img--wide': shape === 'wide', 'dd-preview-img--contain': shape === 'contain' }"
        :src="preview"
        alt=""
      >
      <div
        v-else
        class="dd-preview-img dd-scan"
        :class="{ 'dd-preview-img--wide': shape === 'wide' }"
      ></div>
    </section>
  </form>
</template>
