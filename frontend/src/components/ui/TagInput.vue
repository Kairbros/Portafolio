<script setup lang="ts">
import { ref } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: string[]
  placeholder?: string
  id?: string
  normalize?: (raw: string) => string
}>(), {
  placeholder: 'escribe y pulsa Enter',
  id: undefined,
  normalize: (raw: string) => raw.trim(),
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string[]): void
}>()

const draft = ref('')

function add() {
  const parts = draft.value.split(',').map(props.normalize).filter(Boolean)
  if (!parts.length) return

  const next = [...props.modelValue]
  for (const part of parts) {
    if (!next.includes(part)) next.push(part)
  }

  emit('update:modelValue', next)
  draft.value = ''
}

function remove(tag: string) {
  emit('update:modelValue', props.modelValue.filter((t) => t !== tag))
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Enter' || event.key === ',') {
    event.preventDefault()
    add()
    return
  }

  if (event.key === 'Backspace' && !draft.value) {
    emit('update:modelValue', props.modelValue.slice(0, -1))
  }
}
</script>

<template>
  <div class="tags">
    <span v-for="tag in modelValue" :key="tag" class="tags__item">
      {{ tag }}
      <button
        type="button"
        class="tags__remove"
        :aria-label="`quitar ${tag}`"
        @click="remove(tag)"
      >
        ✕
      </button>
    </span>

    <input
      :id="props.id"
      v-model="draft"
      type="text"
      class="tags__input"
      :placeholder="modelValue.length ? '' : placeholder"
      autocomplete="off"
      @keydown="onKeydown"
      @blur="add"
    >
  </div>
</template>
