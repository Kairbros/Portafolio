<script setup lang="ts">
import { useStickerStore } from '@/stores/Store'
import ImageUploadForm from '@/components/flash/ImageUploadForm.vue'

const store = useStickerStore()
</script>

<template>
    <ImageUploadForm
        title="subir sticker"
        back="stickers"
        accept="image/png,image/webp,image/gif,image/jpeg,image/svg+xml"
        hint="png con fondo transparente, hasta 32 MB"
        shape="contain"
        :upload="store.storeSticker"
    />
</template>
