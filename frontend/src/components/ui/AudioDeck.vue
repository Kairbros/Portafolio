<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useSfx } from '@/composables/useSfx'
import { useMusic } from '@/composables/useMusic'
import DeckIcon from '~icons/pixelarticons/sliders'
import MusicOnIcon from '~icons/pixelarticons/music'
import MusicOffIcon from '~icons/mdi/music-note-off'
import SfxOnIcon from '~icons/pixelarticons/volume-3'
import SfxOffIcon from '~icons/pixelarticons/volume-x'

const { muted, toggleMute } = useSfx()
const { muted: musicMuted, volume, toggleMusic, setVolume } = useMusic()

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const trigger = ref<HTMLButtonElement | null>(null)

const allQuiet = computed(() => muted.value && musicMuted.value)

function onVolume(event: Event) {
  setVolume(Number((event.target as HTMLInputElement).value) / 100)
}

function close(refocus = false) {
  if (!open.value) return
  open.value = false
  if (refocus) trigger.value?.focus()
}

function onPointerDown(event: PointerEvent) {
  const target = event.target
  if (target instanceof Node && !root.value?.contains(target)) close()
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') close(true)
}

onMounted(() => {
  document.addEventListener('pointerdown', onPointerDown)
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onPointerDown)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div ref="root" class="deck">
    <button
      ref="trigger"
      type="button"
      class="deck__trigger"
      :aria-pressed="open"
      :aria-expanded="open"
      aria-haspopup="true"
      aria-controls="deck-panel"
      :title="allQuiet ? 'sonido: en silencio' : 'sonido'"
      aria-label="sonido"
      @click="open = !open"
    >
      <DeckIcon />
      <span v-if="allQuiet" class="deck__off" aria-hidden="true"></span>
    </button>

    <Transition name="deck-pop">
      <div
        v-if="open"
        id="deck-panel"
        class="deck__panel"
        role="group"
        aria-label="controles de sonido"
      >
        <p class="deck__title">sonido</p>

        <div class="deck__row">
          <button
            type="button"
            class="deck__key"
            :aria-pressed="!musicMuted"
            :aria-label="musicMuted ? 'poner música' : 'parar música'"
            @click="toggleMusic"
          >
            <component :is="musicMuted ? MusicOffIcon : MusicOnIcon" />
          </button>

          <span class="deck__name">música</span>
          <span class="deck__state">{{ musicMuted ? 'off' : 'on' }}</span>
        </div>

        <div class="deck__row deck__row--slider">
          <input
            class="deck__vol"
            type="range"
            min="0"
            max="100"
            step="5"
            :value="Math.round(volume * 100)"
            :style="{ '--fill': `${Math.round(volume * 100)}%` }"
            :disabled="musicMuted"
            aria-label="volumen de la música"
            @input="onVolume"
          >

          <span class="deck__state">{{ Math.round(volume * 100) }}%</span>
        </div>

        <div class="deck__row">
          <button
            type="button"
            class="deck__key"
            :aria-pressed="!muted"
            :aria-label="muted ? 'activar efectos' : 'silenciar efectos'"
            @click="toggleMute"
          >
            <component :is="muted ? SfxOffIcon : SfxOnIcon" />
          </button>

          <span class="deck__name">efectos</span>
          <span class="deck__state">{{ muted ? 'off' : 'on' }}</span>
        </div>
      </div>
    </Transition>
  </div>
</template>
