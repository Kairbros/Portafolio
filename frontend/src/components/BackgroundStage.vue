<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useBackgroundImgStore } from '@/stores/Store'
import { useImageRotator } from '@/composables/useImageRotator'
import { fileUrl } from '@/utils/media'

const ROTATION_MS = 15000

const store = useBackgroundImgStore()

const images = computed(() =>
    (store.backgroundImgs ?? []).map((item) => fileUrl(item.img_path)).filter(Boolean),
)

const { layers, active } = useImageRotator(images, ROTATION_MS)

onMounted(() => store.ensureBackgroundImgs())
</script>

<template>
    <div class="backdrop" aria-hidden="true">
        <div
            v-for="(url, index) in layers"
            :key="index"
            class="backdrop__layer"
            :class="{ 'backdrop__layer--on': index === active && url }"
            :style="url ? { backgroundImage: `url('${url}')` } : null"
        ></div>

        <div class="backdrop__veil"></div>
    </div>
</template>
