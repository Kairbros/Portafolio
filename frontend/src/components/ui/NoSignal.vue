<script setup lang="ts">
withDefaults(defineProps<{ label?: string }>(), {
  label: '',
})
</script>

<template>
  <div class="no-signal" aria-hidden="true">
    <span class="no-signal__bars"></span>
    <span class="no-signal__sweep"></span>
    <span v-if="label" class="no-signal__label">{{ label }}</span>
  </div>
</template>
