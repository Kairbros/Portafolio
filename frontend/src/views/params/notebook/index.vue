<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useDrawingStore, type Drawing } from '@/stores/drawings'
import { track } from '@/composables/useLoader'
import NotebookSheet from '@/components/notebook/NotebookSheet.vue'
import DrawingThumb from '@/components/notebook/DrawingThumb.vue'

const store = useDrawingStore()

const page = ref(0)
const selectedId = ref<number | null>(null)
const notice = ref('')

const total = computed(() => store.pages.length)
const current = computed(() => store.pages[page.value] ?? null)
const selected = computed(() => current.value?.drawings.find((d) => d.id === selectedId.value) ?? null)

const nameOf = (d: Drawing) => d.name || 'anónimo'

async function reload() {
    await track(Promise.all([store.getPages(), store.getPending()]))
    if (page.value > total.value - 1) page.value = Math.max(0, total.value - 1)
}

function turn(step: number) {
    page.value = Math.max(0, Math.min(total.value - 1, page.value + step))
    selectedId.value = null
}

async function addPage() {
    const res = await store.addPage()
    if (!res.ok) {
        notice.value = res.message
        return
    }
    page.value = total.value - 1
    selectedId.value = null
}

// los dibujos de la página vuelven a pendientes
async function deletePage() {
    if (!current.value || total.value <= 1) return
    const res = await store.deletePage(current.value)
    if (!res.ok) {
        notice.value = res.message
        return
    }
    selectedId.value = null
    page.value = Math.max(0, page.value - 1)
    await reload()
}

// pega el dibujo en la página abierta, en un lugar y un giro al azar
async function paste(drawing: Drawing) {
    if (!current.value) return
    const res = await store.place(drawing, {
        drawing_page_id: current.value.id,
        x: Math.round(6 + Math.random() * 64),
        y: Math.round(6 + Math.random() * 40),
        rotation: Math.round(Math.random() * 16 - 8),
    })
    if (!res.ok) {
        notice.value = res.message
        return
    }
    selectedId.value = drawing.id
    await reload()
}

async function discard(drawing: Drawing) {
    const res = await store.discard(drawing)
    if (!res.ok) notice.value = res.message
}

async function save(drawing: Drawing) {
    if (!current.value) return
    const res = await store.place(drawing, {
        drawing_page_id: current.value.id,
        x: drawing.x,
        y: drawing.y,
        rotation: drawing.rotation,
        z: drawing.z,
    })
    notice.value = res.ok ? '' : res.message
}

async function remove() {
    if (!selected.value) return
    const res = await store.unplace(selected.value)
    if (!res.ok) {
        notice.value = res.message
        return
    }
    selectedId.value = null
    await reload()
}

onMounted(reload)
</script>

<template>
    <div>
        <div class="dd-heading">
            <h1 class="dd-title">libreta</h1>
        </div>

        <p v-if="store.error" class="dd-state dd-state--bad">
            no se pudo leer
            <span class="dd-state__detail">{{ store.error }}</span>
        </p>

        <div v-else class="nb-admin">
            <section class="dd-win">
                <div class="dd-win__bar">
                    <span>pendientes</span>
                    <span>{{ store.pending.length }}</span>
                </div>

                <ul v-if="store.pending.length" class="nb-queue">
                    <li v-for="d in store.pending" :key="d.id" class="nb-queue__item">
                        <DrawingThumb class="nb-queue__img" :pixels="d.pixels" :colors="d.colors" :size="64" />
                        <div class="nb-queue__text">
                            <span class="nb-queue__name">{{ nameOf(d) }}</span>
                            <span class="nb-queue__comment">{{ d.comment || 'sin comentario' }}</span>
                            <div class="nb-queue__actions">
                                <button type="button" class="dd-btn dd-btn--tone" :disabled="!current" @click="paste(d)">pegar aquí</button>
                                <button type="button" class="dd-link-btn" @click="discard(d)">descartar</button>
                            </div>
                        </div>
                    </li>
                </ul>
                <p v-else class="dd-state nb-queue__empty">nada nuevo</p>
            </section>

            <section class="nb__book">
                <div class="nb__turn">
                    <button type="button" class="dd-btn" :disabled="page === 0" @click="turn(-1)">anterior</button>
                    <span class="nb__count">página {{ page + 1 }} de {{ total }}</span>
                    <div class="nb__turn-end">
                        <button type="button" class="dd-btn" :disabled="total <= 1" @click="deletePage">borrar página</button>
                        <button type="button" class="dd-btn" @click="addPage">página nueva</button>
                        <button type="button" class="dd-btn" :disabled="page >= total - 1" @click="turn(1)">siguiente</button>
                    </div>
                </div>

                <NotebookSheet
                    v-if="current"
                    :drawings="current.drawings"
                    editable
                    :selected-id="selectedId"
                    @select="selectedId = $event.id"
                    @commit="save"
                />

                <div class="nb-admin__tools">
                    <template v-if="selected">
                        <span class="dd-small">{{ nameOf(selected) }}</span>
                        <button type="button" class="dd-btn" @click="remove">quitar</button>
                    </template>
                    <p v-if="notice" class="dd-field__error" role="status">{{ notice }}</p>
                </div>
            </section>
        </div>
    </div>
</template>
