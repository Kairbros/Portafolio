<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { decode, paint } from '@/utils/pixels'

const props = withDefaults(defineProps<{ pixels: string; size?: number }>(), { size: 96 })

const canvas = ref<HTMLCanvasElement | null>(null)

function render() {
    if (canvas.value) paint(canvas.value, decode(props.pixels))
}

onMounted(render)
watch(() => props.pixels, render)
</script>

<template>
    <canvas ref="canvas" class="nb-thumb" :width="size" :height="size" aria-hidden="true"></canvas>
</template>
