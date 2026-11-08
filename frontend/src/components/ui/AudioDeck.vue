<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useSfx } from '@/composables/useSfx'
import { useMusic } from '@/composables/useMusic'
import { fileUrl } from '@/utils/media'
import SpeakerOnIcon from '~icons/pixelarticons/volume-3'
import SpeakerLowIcon from '~icons/pixelarticons/volume-1'
import SpeakerOffIcon from '~icons/pixelarticons/volume-x'
import PrevIcon from '~icons/pixelarticons/prev'
import NextIcon from '~icons/pixelarticons/next'
import PlayIcon from '~icons/pixelarticons/play'
import PauseIcon from '~icons/pixelarticons/pause'
import ListIcon from '~icons/pixelarticons/playlist'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const { muted, toggleMute } = useSfx()
const {
  muted: musicMuted,
  volume,
  current,
  playing,
  position,
  duration,
  playlist,
  toggleMusic,
  setVolume,
  togglePlay,
  nextTrack,
  previousTrack,
  chooseTrack,
  seek,
  loadTracks,
} = useMusic()

const open = ref(false)
const showList = ref(false)
const scrub = ref<number | null>(null)
const root = ref<HTMLElement | null>(null)
const trigger = ref<HTMLButtonElement | null>(null)

const allQuiet = computed(() => muted.value && musicMuted.value)

// parlante pixel: tres ondas con música, una sola con efectos, tachado sin nada
const speaker = computed(() => {
  if (allQuiet.value) return SpeakerOffIcon
  return musicMuted.value ? SpeakerLowIcon : SpeakerOnIcon
})
const percent = computed(() => Math.round(volume.value * 100))

const shown = computed(() => scrub.value ?? position.value)
const progress = computed(() => (duration.value ? (shown.value / duration.value) * 100 : 0))
const artwork = computed(() => (current.value?.artwork_path ? fileUrl(current.value.artwork_path) : ''))

function clock(seconds: number) {
  const whole = Math.floor(seconds)
  return `${Math.floor(whole / 60)}:${String(whole % 60).padStart(2, '0')}`
}

function onScrub(event: Event) {
  scrub.value = Number((event.target as HTMLInputElement).value)
}

function commitScrub() {
  if (scrub.value !== null) seek(scrub.value)
  scrub.value = null
}

watch(open, (isOpen) => {
  if (isOpen) loadTracks()
})

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
      {{ allQuiet ? t('sound.off') : t('sound.on') }}
    </button>

    <section
      v-if="open"
      id="dd-sound-panel"
      class="dd-win dd-sound__panel"
      :aria-label="t('sound.panel')"
    >
      <div class="dd-win__bar">{{ t('sound.title') }}</div>

      <div v-if="playlist.length" class="dd-deck">
        <div v-if="current" class="dd-deck__now">
          <img v-if="artwork" class="dd-np__art" :src="artwork" alt="">
          <span v-else class="dd-np__art dd-scan" aria-hidden="true"></span>
          <div class="dd-np__text">
            <p>{{ current.title || t('nowPlaying.untitled') }}</p>
            <p class="dd-np__artist">{{ current.artist || t('nowPlaying.unknownArtist') }}</p>
          </div>
        </div>

        <div class="dd-sound__row">
          <input
            class="dd-range"
            type="range"
            min="0"
            step="1"
            :max="Math.floor(duration)"
            :value="shown"
            :style="{ '--fill': `${progress}%` }"
            :disabled="!duration"
            :aria-label="t('sound.seek')"
            @input="onScrub"
            @change="commitScrub"
          >
          <span class="dd-small dd-deck__time">{{ clock(shown) }}</span>
        </div>

        <div class="dd-deck__keys">
          <button type="button" class="dd-key" :aria-label="t('sound.previous')" :title="t('sound.previous')" @click="previousTrack">
            <PrevIcon aria-hidden="true" />
          </button>
          <button type="button" class="dd-key dd-key--main" :aria-label="playing ? t('sound.pause') : t('sound.play')" :title="playing ? t('sound.pause') : t('sound.play')" @click="togglePlay">
            <component :is="playing ? PauseIcon : PlayIcon" aria-hidden="true" />
          </button>
          <button type="button" class="dd-key" :aria-label="t('sound.next')" :title="t('sound.next')" @click="nextTrack">
            <NextIcon aria-hidden="true" />
          </button>
          <button type="button" class="dd-key" :aria-label="t('sound.list')" :title="t('sound.list')" :aria-pressed="showList" @click="showList = !showList">
            <ListIcon aria-hidden="true" />
          </button>
        </div>

        <ul v-if="showList" class="dd-deck__list">
          <li v-for="track in playlist" :key="track.id">
            <button
              type="button"
              class="dd-deck__track"
              :aria-current="current?.id === track.id ? 'true' : undefined"
              @click="chooseTrack(track)"
            >
              <span class="dd-deck__title">{{ track.title || t('nowPlaying.untitled') }}</span>
              <span class="dd-deck__artist">{{ track.artist || t('nowPlaying.unknownArtist') }}</span>
            </button>
          </li>
        </ul>
      </div>

      <div class="dd-sound__rows">
        <div class="dd-sound__row">
          <span>{{ t('sound.music') }}</span>
          <button type="button" class="dd-switch" :aria-pressed="!musicMuted" @click="toggleMusic">
            {{ musicMuted ? t('sound.no') : t('sound.yes') }}
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
            :aria-label="t('sound.volume')"
            @input="onVolume"
          >
          <span class="dd-small">{{ percent }}</span>
        </div>

        <div class="dd-sound__row">
          <span>{{ t('sound.effects') }}</span>
          <button type="button" class="dd-switch" :aria-pressed="!muted" @click="toggleMute">
            {{ muted ? t('sound.no') : t('sound.yes') }}
          </button>
        </div>
      </div>
    </section>
  </div>
</template>
