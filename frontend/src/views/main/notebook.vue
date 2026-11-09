<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useDrawingStore, type Drawing } from '@/stores/drawings'
import { track } from '@/composables/useLoader'
import PixelEditor from '@/components/notebook/PixelEditor.vue'
import NotebookSheet from '@/components/notebook/NotebookSheet.vue'
import DrawingThumb from '@/components/notebook/DrawingThumb.vue'
import NotebookIcon from '~icons/pixelarticons/notebook'

const { t, locale } = useI18n()
const store = useDrawingStore()

const pixels = ref('')
const colors = ref<string[]>([])
const name = ref('')
const comment = ref('')
const website = ref('')
const sending = ref(false)
const sent = ref(false)
const failure = ref('')

const page = ref(0)
const composing = ref(false)
const opened = ref<Drawing | null>(null)
const composeClose = ref<HTMLButtonElement | null>(null)
const viewClose = ref<HTMLButtonElement | null>(null)
let lastFocus: HTMLElement | null = null

const total = computed(() => store.pages.length)
const current = computed(() => store.pages[page.value] ?? null)

function turn(step: number) {
    page.value = Math.max(0, Math.min(total.value - 1, page.value + step))
}

async function startDrawing() {
    lastFocus = document.activeElement as HTMLElement | null
    sent.value = false
    failure.value = ''
    composing.value = true
    await nextTick()
    composeClose.value?.focus()
}

async function send() {
    if (!pixels.value || sending.value) return
    sending.value = true
    failure.value = ''

    const res = await store.send({
        name: name.value.trim(),
        comment: comment.value.trim(),
        pixels: pixels.value,
        colors: colors.value,
        website: website.value,
    })
    sending.value = false

    if (!res.ok) {
        failure.value = res.message === 'too-many' ? t('notebook.tooMany') : res.message
        return
    }

    pixels.value = ''
    colors.value = []
    name.value = ''
    comment.value = ''
    sent.value = true
    close()
}

async function open(drawing: Drawing) {
    lastFocus = document.activeElement as HTMLElement | null
    opened.value = drawing
    await nextTick()
    viewClose.value?.focus()
}

function close() {
    composing.value = false
    opened.value = null
    lastFocus?.focus()
}

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape' && (opened.value || composing.value)) close()
}

const fullDate = (raw: string) => {
    const date = new Date(raw)
    return Number.isNaN(date.getTime())
        ? ''
        : new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'long', year: 'numeric' }).format(date)
}

onMounted(() => {
    track(store.getPages())
    window.addEventListener('keydown', onKey)
})
onUnmounted(() => window.removeEventListener('keydown', onKey))
</script>

<template>
    <div class="nb">
        <div class="dd-heading">
            <h1 class="dd-title">{{ t('notebook.title') }}</h1>
            <div class="nb__start">
                <p v-if="sent" class="dd-small" role="status">{{ t('notebook.sent') }}</p>
                <button type="button" class="dd-btn dd-btn--tone nb__draw" @click="startDrawing">
                    <NotebookIcon class="nb__draw-icon" aria-hidden="true" />
                    {{ t('notebook.draw') }}
                </button>
            </div>
        </div>

        <p v-if="store.error" class="dd-state dd-state--bad">
            {{ t('state.error') }}
            <span class="dd-state__detail">{{ store.error }}</span>
        </p>

        <section v-else-if="current" class="nb__book" :aria-label="t('notebook.pages')">
            <div class="nb__turn">
                <button type="button" class="dd-btn" :disabled="page === 0" @click="turn(-1)">{{ t('notebook.prev') }}</button>
                <span class="nb__count">{{ t('notebook.page', { n: page + 1, total }) }}</span>
                <button type="button" class="dd-btn" :disabled="page >= total - 1" @click="turn(1)">{{ t('notebook.next') }}</button>
            </div>

            <NotebookSheet :drawings="current.drawings" @open="open" />
        </section>

        <Teleport to="body">
            <div v-if="composing" class="dd-modal" role="dialog" aria-modal="true" :aria-label="t('notebook.draw')" @click.self="close">
                <div class="dd-win dd-modal__win nb-compose">
                    <div class="dd-win__bar">
                        <span class="dd-win__title">{{ t('notebook.draw') }}</span>
                        <button ref="composeClose" type="button" class="dd-modal__action" @click="close">{{ t('zoom.close') }}</button>
                    </div>

                    <div class="nb__compose">
                        <PixelEditor v-model="pixels" v-model:colors="colors" />

                        <form class="nb__fields" @submit.prevent="send">
                            <div class="dd-field">
                                <label class="dd-field__label" for="nb-name">{{ t('notebook.name') }}</label>
                                <input
                                    id="nb-name"
                                    v-model="name"
                                    class="dd-input"
                                    maxlength="24"
                                    autocomplete="off"
                                    :placeholder="t('notebook.anonymous')"
                                >
                            </div>

                            <div class="dd-field">
                                <label class="dd-field__label" for="nb-comment">{{ t('notebook.comment') }}</label>
                                <textarea id="nb-comment" v-model="comment" class="dd-input" maxlength="140" rows="4"></textarea>
                                <span class="dd-field__hint">{{ t('notebook.count', { n: comment.length }) }}</span>
                            </div>

                            <input v-model="website" class="nb__trap" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">

                            <div class="nb__send">
                                <button type="submit" class="dd-btn dd-btn--tone" :disabled="!pixels || sending">
                                    {{ sending ? t('notebook.sending') : t('notebook.send') }}
                                </button>
                                <p v-if="failure" class="dd-field__error" role="status">{{ failure }}</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div v-if="opened" class="dd-modal" role="dialog" aria-modal="true" :aria-label="opened.name || t('notebook.anonymous')" @click.self="close">
                <div class="dd-win dd-modal__win nb-view">
                    <div class="dd-win__bar">
                        <span class="dd-win__title">{{ opened.name || t('notebook.anonymous') }}</span>
                        <button ref="viewClose" type="button" class="dd-modal__action" @click="close">{{ t('zoom.close') }}</button>
                    </div>
                    <div class="nb-view__body">
                        <DrawingThumb class="nb-view__img" :pixels="opened.pixels" :colors="opened.colors" :size="320" />
                        <p class="nb-view__comment">{{ opened.comment || t('notebook.noComment') }}</p>
                        <p class="dd-small">{{ fullDate(opened.created_at) }}</p>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
