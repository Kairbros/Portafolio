<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import FolderWindow from '@/components/ui/FolderWindow.vue'

const route = useRoute()

const tabs = [
    { label: 'inicio', to: { name: 'home' }, exact: true },
    { label: 'sobre mi', to: { name: 'about' } },
    { label: 'skills', to: { name: 'skills_home' } },
]

const path = computed(() => `User:\\${route.path.slice(1).replace(/\//g, '\\')}`)

const closeTo = computed(() => (route.name === 'home' ? null : { name: 'home' }))
</script>

<template>
    <FolderWindow :tabs="tabs" :path="path" :close-to="closeTo">
        <RouterView />
    </FolderWindow>
</template>
