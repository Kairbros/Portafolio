<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'

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
    <Transition name="lb">
      <div
        v-if="modelValue"
        class="lightbox"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="close"
      >
        <figure class="lightbox__window" @click.stop>
          <figcaption class="lightbox__bar">
            <span class="lightbox__title">{{ title }}</span>
            <button
              ref="closeButton"
              type="button"
              class="lightbox__close"
              aria-label="cerrar"
              @click="close"
            >
              ✕
            </button>
          </figcaption>

          <div class="lightbox__stage">
            <img class="lightbox__img" :src="src" :alt="alt">
          </div>
        </figure>
      </div>
    </Transition>
  </Teleport>
</template>
