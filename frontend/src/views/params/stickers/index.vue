<script setup lang="ts">
import { useStickerStore } from '@/stores/Store'
import ImageGallery from '@/components/flash/ImageGallery.vue'

const store = useStickerStore()
</script>

<template>
    <ImageGallery
        title="stickers"
        new-to="sticker_create"
        shape="contain"
        :items="store.stickers"
        :error="store.error"
        :loaded="store.loaded"
        :load="store.getStickers"
        :remove="store.deleteSticker"
    />
</template>
