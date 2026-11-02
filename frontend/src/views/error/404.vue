<script setup lang="ts">
import { useRoute } from 'vue-router'
import FolderWindow from '@/components/ui/FolderWindow.vue'

const route = useRoute()

const path = `User:\\${route.path.slice(1).replace(/\//g, '\\')}`
</script>

<template>
    <FolderWindow :tabs="[{ label: 'no encontrado' }]" :path="path" close-to="/">
        <div class="state lost">
            <h1 class="lost__code fx-glitch" data-text="404">404</h1>

            <p class="t-heading">¿A donde vas?</p>

            <RouterLink class="btn btn--primary lost__back" to="/">volver</RouterLink>
        </div>
    </FolderWindow>
</template>

<style scoped>
.lost {
    gap: var(--sp-4);
    min-height: 100%;
}

.lost__code {
    font-family: var(--font-term);
    font-size: clamp(4rem, 22vw, 9rem);
    line-height: 0.9;
    letter-spacing: var(--tracking-wide);
    color: var(--bone);
    margin: 0;
}

.lost__back {
    margin-top: var(--sp-6);
}
</style>
