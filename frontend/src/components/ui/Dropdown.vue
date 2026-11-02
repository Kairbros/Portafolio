<script setup lang="ts">
import { computed, ref, onBeforeUnmount, onMounted } from 'vue'

type OptionObject = Record<string, unknown>
type Option = string | number | OptionObject

const props = withDefaults(defineProps<{
  options: Option[]
  modelValue?: string | number
  optionLabel?: string
  optionValue?: string
  placeholder?: string
  disabled?: boolean
  id?: string
}>(), {
  modelValue: '',
  optionLabel: 'label',
  optionValue: 'value',
  placeholder: 'Seleccionar...',
  disabled: false,
  id: undefined,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: unknown): void
  (e: 'change', value: unknown): void
}>()

const open = ref(false)
const root = ref<HTMLElement | null>(null)

const normalizedOptions = computed(() =>
  props.options.map((option) => {
    if (option !== null && typeof option === 'object') {
      const obj = option as OptionObject
      return { label: String(obj[props.optionLabel]), value: obj[props.optionValue], raw: option }
    }
    return { label: String(option), value: option, raw: option }
  }),
)

const selectedOption = computed(() =>
  normalizedOptions.value.find((option) => option.value === props.modelValue) ?? null,
)

function toggle() {
  if (props.disabled) return
  open.value = !open.value
}

function selectOption(value: unknown) {
  emit('update:modelValue', value)
  emit('change', value)
  open.value = false
}

function onClickOutside(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) {
    open.value = false
  }
}

function onEscape(event: KeyboardEvent) {
  if (event.key === 'Escape') open.value = false
}

onMounted(() => {
  document.addEventListener('click', onClickOutside)
  document.addEventListener('keydown', onEscape)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', onClickOutside)
  document.removeEventListener('keydown', onEscape)
})
</script>

<template>
  <div ref="root" class="dd">
    <button
      :id="props.id"
      type="button"
      class="dd__trigger"
      :disabled="disabled"
      :aria-expanded="open"
      aria-haspopup="listbox"
      @click="toggle"
    >
      <span class="dd__value" :class="{ 'dd__value--empty': !selectedOption }">
        {{ selectedOption ? selectedOption.label : placeholder }}
      </span>
      <span class="dd__arrow" aria-hidden="true">▾</span>
    </button>

    <Transition name="dd-pop">
      <ul v-if="open" class="dd__menu" role="listbox">
        <li v-if="normalizedOptions.length === 0" class="dd__empty">
          sin opciones
        </li>
        <li
          v-for="option in normalizedOptions"
          :key="String(option.value)"
          class="dd__option"
          :class="{ 'dd__option--active': option.value === modelValue }"
          role="option"
          :aria-selected="option.value === modelValue"
          @click="selectOption(option.value)"
        >
          {{ option.label }}
        </li>
      </ul>
    </Transition>
  </div>
</template>
