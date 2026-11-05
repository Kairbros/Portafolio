<script setup lang="ts">
import { useProfileImgStore } from '@/stores/Store'
import ImageGallery from '@/components/flash/ImageGallery.vue'

const store = useProfileImgStore()
</script>

<template>
    <ImageGallery
        title="retratos"
        new-to="profile_create"
        shape="square"
        :items="store.profileImgs"
        :error="store.error"
        :loaded="store.loaded"
        :load="store.getProfileImgs"
        :remove="store.deleteProfileImg"
    />
</template>
