<script setup lang="ts">
import { useProfileImgStore } from '@/stores/Store'
import ImageUploadForm from '@/components/flash/ImageUploadForm.vue'

const store = useProfileImgStore()
</script>

<template>
    <ImageUploadForm
        title="subir retrato"
        back="profiles"
        accept="image/jpeg,image/png,image/gif"
        hint="cuadrada; jpg, png o gif, hasta 32 MB"
        shape="square" square
        :upload="store.storeProfileImg"
    />
</template>
