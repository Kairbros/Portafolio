<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import "./assets/main.css"
import BackgroundStage from '@/components/BackgroundStage.vue';
import NowPlaying from '@/components/NowPlaying.vue';
import DreamStage from '@/components/flash/DreamStage.vue';
import { play } from '@/composables/useSfx';
import { startMusic } from '@/composables/useMusic';
import { useLocaleFor } from '@/i18n';
import { usePageMeta } from '@/composables/usePageMeta';

const route = useRoute()

const admin = computed(() => route.path.startsWith('/params'))

useLocaleFor(() => admin.value)
usePageMeta()

const CLICKABLE = 'a[href], button'

// const CLOSING = '.dd-modal__action'
const CLOSING = '.placeholder' // Añadir sonido de cierre

const INERT = '[disabled], [aria-disabled="true"], [aria-current="page"]'

function hit(event: Event): Element | null {
    const target = event.target
    if (!(target instanceof Element)) return null

    const el = target.closest(CLICKABLE + ', ' + CLOSING)
    if (!el || el.matches(INERT)) return null

    return el
}

let hovered: Element | null = null

function onPointerOver(event: Event) {
    const el = hit(event)

    if (el === hovered) return
    hovered = el

    if (el) play('hover')
}

function onPointerDown(event: PointerEvent) {
    if (event.button !== 0) return

    startMusic()

    const el = hit(event)
    if (!el) return

    play(el.matches(CLOSING) ? 'close' : 'click')
}

onMounted(() => {
    document.addEventListener('pointerover', onPointerOver)
    document.addEventListener('pointerdown', onPointerDown)
})

onUnmounted(() => {
    document.removeEventListener('pointerover', onPointerOver)
    document.removeEventListener('pointerdown', onPointerDown)
})
</script>

<template>
    <BackgroundStage />

    <DreamStage :admin="admin">
        <RouterView />
    </DreamStage>

    <NowPlaying />
</template>
