<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  target?: string
  confirmLabel?: string
  busy?: boolean
}>(), {
  title: 'borrar',
  target: '',
  confirmLabel: 'borrar',
  busy: false,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm'): void
}>()

const cancelButton = ref<HTMLButtonElement | null>(null)

function close() {
  if (props.busy) return
  emit('update:modelValue', false)
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') close()
}

watch(() => props.modelValue, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''

  if (open) {
    document.addEventListener('keydown', onKeydown)
    requestAnimationFrame(() => cancelButton.value?.focus())
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
      role="alertdialog"
      aria-modal="true"
      :aria-label="title"
      @click.self="close"
    >
      <div class="dd-win dd-modal__win dd-confirm">
        <div class="dd-win__bar">{{ title }}</div>

        <div class="dd-confirm__body">
          <p v-if="target" class="dd-confirm__target">{{ target }}</p>
          <p>No se puede deshacer.</p>
        </div>

        <div class="dd-form__foot">
          <button ref="cancelButton" type="button" class="dd-btn" :disabled="busy" @click="close">
            cancelar
          </button>
          <button type="button" class="dd-btn dd-btn--danger" :disabled="busy" @click="emit('confirm')">
            {{ busy ? 'borrando' : confirmLabel }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
