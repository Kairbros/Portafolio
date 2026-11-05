<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useSfx } from '@/composables/useSfx'
import { useMusic } from '@/composables/useMusic'
import SpeakerOnIcon from '~icons/pixelarticons/volume-3'
import SpeakerLowIcon from '~icons/pixelarticons/volume-1'
import SpeakerOffIcon from '~icons/pixelarticons/volume-x'

const { muted, toggleMute } = useSfx()
const { muted: musicMuted, volume, toggleMusic, setVolume } = useMusic()

const open = ref(false)
const root = ref<HTMLElement | null>(null)
const trigger = ref<HTMLButtonElement | null>(null)

const allQuiet = computed(() => muted.value && musicMuted.value)

// parlante pixel: tres ondas con música, una sola con efectos, tachado sin nada
const speaker = computed(() => {
  if (allQuiet.value) return SpeakerOffIcon
  return musicMuted.value ? SpeakerLowIcon : SpeakerOnIcon
})
const percent = computed(() => Math.round(volume.value * 100))

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
  <div ref="root" class="dd-sound">
    <button
      ref="trigger"
      type="button"
      class="dd-sound__trigger"
      :aria-expanded="open"
      aria-controls="dd-sound-panel"
      @click="open = !open"
    >
      <component :is="speaker" class="dd-sound__icon" aria-hidden="true" />
      {{ allQuiet ? 'silencio' : 'sonido' }}
    </button>

    <section
      v-if="open"
      id="dd-sound-panel"
      class="dd-win dd-sound__panel"
      aria-label="Controles de sonido"
    >
      <div class="dd-win__bar">sonido</div>

      <div class="dd-sound__rows">
        <div class="dd-sound__row">
          <span>música</span>
          <button type="button" class="dd-switch" :aria-pressed="!musicMuted" @click="toggleMusic">
            {{ musicMuted ? 'no' : 'sí' }}
          </button>
        </div>

        <div class="dd-sound__row">
          <input
            class="dd-range"
            type="range"
            min="0"
            max="100"
            step="5"
            :value="percent"
            :style="{ '--fill': `${percent}%` }"
            :disabled="musicMuted"
            aria-label="Volumen de la música"
            @input="onVolume"
          >
          <span class="dd-small">{{ percent }}</span>
        </div>

        <div class="dd-sound__row">
          <span>efectos</span>
          <button type="button" class="dd-switch" :aria-pressed="!muted" @click="toggleMute">
            {{ muted ? 'no' : 'sí' }}
          </button>
        </div>
      </div>
    </section>
  </div>
</template>
