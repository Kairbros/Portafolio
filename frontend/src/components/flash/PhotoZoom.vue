<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = withDefaults(defineProps<{
  modelValue: boolean
  src: string
  alt?: string
  title?: string
}>(), {
  alt: '',
  title: 'vista previa',
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

const closeButton = ref<HTMLButtonElement | null>(null)

function close() {
  emit('update:modelValue', false)
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') close()
}

watch(() => props.modelValue, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''

  if (open) {
    document.addEventListener('keydown', onKeydown)
    requestAnimationFrame(() => closeButton.value?.focus())
  } else {
    document.removeEventListener('keydown', onKeydown)
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="dd-modal"
      role="dialog"
      aria-modal="true"
      :aria-label="title"
      @click.self="close"
    >
      <figure class="dd-win dd-modal__win">
        <figcaption class="dd-win__bar">
          <span class="dd-win__title">{{ title }}</span>
          <button ref="closeButton" type="button" class="dd-modal__action" @click="close">{{ t('zoom.close') }}</button>
        </figcaption>

        <img class="dd-modal__img" :src="src" :alt="alt">
      </figure>
    </div>
  </Teleport>
</template>
