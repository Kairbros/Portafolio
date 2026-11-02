<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'

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
</script>

<template>
  <div ref="board" class="stage" :class="{ 'stage--live': playing }">
    <div v-if="!playing" class="stage__attract">
      <img v-if="poster" class="stage__poster" :src="poster" :alt="title" loading="lazy">

      <span v-else class="no-signal" aria-hidden="true">
        <span class="no-signal__bars"></span>
        <span class="no-signal__sweep"></span>
      </span>

      <span class="stage__scan" aria-hidden="true"></span>

      <div class="stage__pitch">

        <button type="button" class="stage__play" @click="play">
          <span class="stage__play-glyph" aria-hidden="true">▶</span>
          <span class="stage__play-text">jugar</span>
        </button>

        <div class="stage__side">
          <button type="button" class="btn btn--ghost stage__alt" @click="openTheater">
            abrir en una ventana grande
          </button>
        </div>
      </div>
    </div>

    <template v-else>
      <iframe
        :key="run"
        ref="frame"
        class="stage__frame"
        :src="src"
        :title="title"
        allow="autoplay; fullscreen; gamepad; cross-origin-isolated"
        allowfullscreen
        @load="ready = true"
      />

      <p v-if="!ready" class="stage__booting loading-dots">cargando</p>

      <div class="stage__hud">
        <button type="button" class="stage__key" @click="fullscreen">pantalla completa</button>
        <button type="button" class="stage__key" @click="openTheater">ventana grande</button>
        <button type="button" class="stage__key" @click="restart">reiniciar</button>
        <button type="button" class="stage__key stage__key--off" @click="stop">salir</button>
      </div>
    </template>
  </div>
  <Teleport to="body">
    <Transition name="lb">
      <div
        v-if="theater"
        class="lightbox theater"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="stop"
      >
        <div class="lightbox__window theater__window">
          <div class="lightbox__bar">
            <span class="lightbox__title">{{ title }}</span>

            <button type="button" class="stage__key" @click="fullscreen">pantalla completa</button>
            <button type="button" class="stage__key" @click="restart">reiniciar</button>
            <button type="button" class="lightbox__close" aria-label="cerrar" @click="stop">✕</button>
          </div>

          <div ref="theaterBoard" class="theater__board">
            <iframe
              :key="run"
              ref="theaterFrame"
              class="stage__frame"
              :src="src"
              :title="title"
              allow="autoplay; fullscreen; gamepad; cross-origin-isolated"
              allowfullscreen
              @load="ready = true"
            />

            <p v-if="!ready" class="stage__booting loading-dots">cargando</p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
