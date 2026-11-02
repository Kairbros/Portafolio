<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { RouterView } from 'vue-router';
import "./assets/main.css"
import BackgroundStage from '@/components/BackgroundStage.vue';
import NowPlaying from '@/components/NowPlaying.vue';
import AudioDeck from '@/components/ui/AudioDeck.vue';
import { play } from '@/composables/useSfx';
import { startMusic } from '@/composables/useMusic';

const CLICKABLE = '.btn, .row, .folder__tab, .file-icon, .door, .shortcut,'
    + ' .thumb__view, .thumb__del, .type-card__hit, .dd__trigger, .dd__option, .zoomable, .folder__close, .lightbox__close,'
    + ' .deck__key, .deck__trigger, .track__art'

// const CLOSING = '.folder__close, .lightbox__close'
 const CLOSING = '.placeholder' // Añadir sonido de cierre

const INERT = '[disabled], [aria-disabled="true"], .row--locked, .door--locked,'
    + ' .folder__tab--current, .folder__tab.router-link-exact-active, .folder__close--off'

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

    <div class="grain" aria-hidden="true"></div>
    <div class="crt" aria-hidden="true">
        <div class="crt__roll"></div>
    </div>

    <div>
        <div class="glitch"></div>
    </div>

    <main>
        <RouterView />
    </main>

    <NowPlaying />

    <AudioDeck />
</template>
