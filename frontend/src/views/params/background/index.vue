<script setup lang="ts">
import { useBackgroundImgStore } from '@/stores/Store'
import ImageGallery from '@/components/flash/ImageGallery.vue'

const store = useBackgroundImgStore()
</script>

<template>
    <ImageGallery
        title="fondos"
        new-to="background_create"
        shape="wide"
        :items="store.backgroundImgs"
        :error="store.error"
        :loaded="store.loaded"
        :load="store.getBackgroundImgs"
        :remove="store.deleteBackgroundImg"
    />
</template>
