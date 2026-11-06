<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

withDefaults(defineProps<{
  src: string
  title?: string
  poster?: string
}>(), {
  title: 'proyecto',
  poster: '',
})

const frame = ref<HTMLIFrameElement | null>(null)
const theaterFrame = ref<HTMLIFrameElement | null>(null)
const board = ref<HTMLElement | null>(null)
const theaterBoard = ref<HTMLElement | null>(null)

const playing = ref(false)
const theater = ref(false)
const ready = ref(false)

// cambia la key del iframe para reiniciar el juego
const run = ref(0)

function play() {
  theater.value = false
  start(() => { playing.value = true })
}

function openTheater() {
  playing.value = false
  start(() => { theater.value = true })
}

function start(open: () => void) {
  ready.value = false
  run.value++
  open()
  nextTick(() => requestAnimationFrame(() => {
    (theater.value ? theaterFrame : frame).value?.focus()
  }))
}

function restart() {
  ready.value = false
  run.value++
}

function stop() {
  playing.value = false
  theater.value = false
  ready.value = false
}

async function fullscreen() {
  const target = (theater.value ? theaterBoard : board).value
  if (!target) return

  try {
    if (document.fullscreenElement) await document.exitFullscreen()
    else await target.requestFullscreen()
  } catch {
  }
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && ! document.fullscreenElement) stop()
}

watch(theater, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''

  if (open) document.addEventListener('keydown', onKeydown)
  else document.removeEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})

defineExpose({ openTheater })
</script>

<template>
  <div class="dd-player">
    <div ref="board" class="dd-player__screen dd-scan">
      <template v-if="!playing">
        <img v-if="poster" class="dd-player__poster" :src="poster" alt="" loading="lazy">

        <div class="dd-player__cover">
          <button type="button" class="dd-btn dd-btn--big" @click="play">{{ t('player.play') }}</button>
        </div>
      </template>

      <template v-else>
        <iframe
          :key="run"
          ref="frame"
          class="dd-player__frame"
          :src="src"
          :title="title"
          allow="autoplay; fullscreen; gamepad; cross-origin-isolated"
          allowfullscreen
          @load="ready = true"
        />

        <p v-if="!ready" class="dd-player__booting">{{ t('player.loading') }}</p>
      </template>
    </div>

    <div v-if="playing" class="dd-player__hud">
      <button type="button" class="dd-btn" @click="fullscreen">{{ t('player.fullscreen') }}</button>
      <button type="button" class="dd-btn" @click="restart">{{ t('player.restart') }}</button>
      <button type="button" class="dd-btn" @click="stop">{{ t('player.exit') }}</button>
    </div>
  </div>

  <Teleport to="body">
    <div
      v-if="theater"
      class="dd-modal"
      role="dialog"
      aria-modal="true"
      :aria-label="title"
      @click.self="stop"
    >
      <div class="dd-win dd-modal__win dd-modal__win--wide">
        <div class="dd-win__bar">
          <span class="dd-win__title">{{ title }}</span>
          <button type="button" class="dd-modal__action" @click="fullscreen">{{ t('player.fullscreen') }}</button>
          <button type="button" class="dd-modal__action" @click="restart">{{ t('player.restart') }}</button>
          <button type="button" class="dd-modal__action" @click="stop">{{ t('player.close') }}</button>
        </div>

        <div ref="theaterBoard" class="dd-modal__board">
          <iframe
            :key="run"
            ref="theaterFrame"
            class="dd-player__frame"
            :src="src"
            :title="title"
            allow="autoplay; fullscreen; gamepad; cross-origin-isolated"
            allowfullscreen
            @load="ready = true"
          />

          <p v-if="!ready" class="dd-player__booting">{{ t('player.loading') }}</p>
        </div>
      </div>
    </div>
  </Teleport>
</template>
