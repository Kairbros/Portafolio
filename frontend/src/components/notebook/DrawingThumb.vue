<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { colorsOf, decode, paint } from '@/utils/pixels'

const props = withDefaults(defineProps<{ pixels: string; colors?: string[] | null; size?: number }>(), { size: 96, colors: null })

const canvas = ref<HTMLCanvasElement | null>(null)

function render() {
    if (canvas.value) paint(canvas.value, decode(props.pixels), colorsOf(props.colors))
}

onMounted(render)
watch(() => [props.pixels, props.colors], render)
</script>

<template>
    <canvas ref="canvas" class="nb-thumb" :width="size" :height="size" aria-hidden="true"></canvas>
</template>
