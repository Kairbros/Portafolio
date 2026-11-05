<script setup lang="ts">
import { useBackgroundImgStore } from '@/stores/Store'
import ImageUploadForm from '@/components/flash/ImageUploadForm.vue'

const store = useBackgroundImgStore()
</script>

<template>
    <ImageUploadForm
        title="subir fondo"
        back="backgrounds"
        accept="image/jpeg,image/png,image/gif,image/svg+xml"
        hint="jpg, png, gif o svg, hasta 32 MB"
        shape="wide"
        :upload="store.storeBackgroundImg"
    />
</template>
