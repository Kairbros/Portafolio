<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { track } from '@/composables/useLoader'
import { fileUrl } from '@/utils/media'
import PhotoZoom from '@/components/flash/PhotoZoom.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

type Item = { id: number | string; img_path: string }
type Result = { ok: boolean; message?: string }

const props = withDefaults(defineProps<{
  title: string
  items: Item[]
  newTo: string
  error?: string
  loaded?: boolean
  shape?: 'wide' | 'square' | 'contain'
  load: () => Promise<unknown>
  remove: (id: string) => Promise<Result>
}>(), {
  error: '',
  loaded: false,
  shape: 'wide',
})

const zoomed = ref('')
const pending = ref<Item | null>(null)
const deleting = ref(false)
const notice = ref('')

function fileName(path: string) {
  return String(path ?? '').split('/').pop() ?? ''
}

async function confirmRemove() {
  if (!pending.value) return

  deleting.value = true
  const res = await props.remove(String(pending.value.id))
  deleting.value = false

  notice.value = res.ok ? 'borrado' : res.message ?? 'no se pudo borrar'
  pending.value = null

  if (res.ok) await track(props.load())
}

onMounted(() => track(props.load()))
</script>

<template>
  <div>
    <div class="dd-heading">
      <h1 class="dd-title">{{ title }}</h1>
      <RouterLink class="dd-btn dd-btn--tone" :to="{ name: newTo }">subir</RouterLink>
    </div>

    <p v-if="error" class="dd-state dd-state--bad">
      no se pudo leer
      <span class="dd-state__detail">{{ error }}</span>
    </p>

    <ul
      v-else-if="items.length"
      class="dd-thumbs"
      :class="{ 'dd-thumbs--square': shape === 'square', 'dd-thumbs--contain': shape === 'contain' }"
    >
      <li v-for="item in items" :key="item.id" class="dd-win">
        <button
          type="button"
          class="dd-thumb__view"
          :aria-label="`ver ${fileName(item.img_path)}`"
          @click="zoomed = fileUrl(item.img_path)"
        >
          <img class="dd-thumb__img" :src="fileUrl(item.img_path)" alt="" loading="lazy">
        </button>
        <div class="dd-thumb__foot">
          <span class="dd-thumb__name">{{ fileName(item.img_path) }}</span>
          <button type="button" class="dd-link-btn" @click="pending = item">borrar</button>
        </div>
      </li>
    </ul>

    <p v-else-if="loaded" class="dd-state">vacío</p>

    <p v-if="notice" class="dd-notice" role="status">{{ notice }}</p>

    <PhotoZoom :model-value="!!zoomed" :src="zoomed" :title="title" @update:model-value="zoomed = ''" />

    <ConfirmDialog
      :model-value="!!pending"
      :title="`borrar de ${title}`"
      :target="pending ? fileName(pending.img_path) : ''"
      :busy="deleting"
      @update:model-value="pending = null"
      @confirm="confirmRemove"
    />
  </div>
</template>
