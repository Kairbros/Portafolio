<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  message?: string
  target?: string
  confirmLabel?: string
  busy?: boolean
}>(), {
  title: 'confirmar',
  message: '¿Seguro que quieres eliminarlo?',
  target: '',
  confirmLabel: 'eliminar',
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
    <Transition name="lb">
      <div
        v-if="modelValue"
        class="lightbox confirm"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="close"
      >
        <div class="confirm__window" @click.stop>
          <header class="confirm__bar">
            <span class="confirm__title">{{ title }}</span>
          </header>

          <div class="confirm__body">
            <p class="confirm__msg">{{ message }}</p>
            <p v-if="target" class="confirm__target">{{ target }}</p>
            <p class="confirm__warn">esto no se puede deshacer.</p>
          </div>

          <footer class="confirm__foot">
            <button
              ref="cancelButton"
              type="button"
              class="btn btn--ghost"
              :disabled="busy"
              @click="close"
            >
              cancelar
            </button>

            <button
              type="button"
              class="btn btn--danger"
              :disabled="busy"
              @click="emit('confirm')"
            >
              <span v-if="busy" class="loading-dots">borrando</span>
              <span v-else>{{ confirmLabel }}</span>
            </button>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
