<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useSkillStore, useTypeSkillStore } from '@/stores/Store';
import router from '@/router';
import Dropdown from '@/components/ui/Dropdown.vue';

const skillStore = useSkillStore()
const typeStore = useTypeSkillStore()

const name = ref('')
const mastery = ref(50)
const icon = ref<File | null>(null)
const preview = ref('')

const loadingTypes = ref(true)
const types = computed(() => typeStore.typeSkills ?? [])
const noTypes = computed(() => !loadingTypes.value && types.value.length === 0)

const selected = ref<string | number>('')

const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

const ready = computed(() => !!selected.value && !!name.value.trim() && !!icon.value)

const pips = computed(() => Math.round(mastery.value / 10))

onMounted(async () => {
    await typeStore.getTypeSkills()
    loadingTypes.value = false
})

function clearPreview() {
    if (preview.value) URL.revokeObjectURL(preview.value)
    preview.value = ''
}

onBeforeUnmount(clearPreview)

function onIconChange(event: Event) {
    const target = event.target as HTMLInputElement
    const picked = target.files?.[0] ?? null

    clearPreview()
    icon.value = picked
    if (picked) preview.value = URL.createObjectURL(picked)
}

async function createSkill() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const formData = new FormData()
    formData.append('name', name.value)
    formData.append('type', String(selected.value))
    formData.append('mastery', String(mastery.value))
    if (icon.value) formData.append('icon', icon.value)

    const res = await skillStore.storeSkill(formData)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    message.value = res.data?.message ?? 'guardado'
    setTimeout(() => router.push({ name: 'skills' }))
}
</script>

<template>
    <form class="forge" @submit.prevent="createSkill">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body forge__body">
                <header>
                    <h1 class="t-heading">nueva skill</h1>
                    <p class="t-mute">algo que sabes hacer, con su logo y cuánto lo dominas.</p>
                </header>

                <div class="forge__fields">
                    <div class="field">
                        <label class="field__label" for="s-type">tipo</label>
                        <Dropdown
                            id="s-type"
                            v-model="selected"
                            :options="types"
                            option-label="name"
                            option-value="id"
                            :disabled="loadingTypes || noTypes"
                            :placeholder="loadingTypes ? 'leyendo el catálogo...' : 'selecciona un tipo'"
                        />
                        <span v-if="errors.type" class="field__error">{{ errors.type[0] }}</span>
                        <span v-else-if="noTypes" class="field__error">
                            no hay tipos en el catálogo.
                            <RouterLink :to="{ name: 'typeskill_create' }">crea uno primero</RouterLink>
                        </span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="s-name">nombre</label>
                        <input
                            id="s-name"
                            v-model="name"
                            type="text"
                            class="input"
                            placeholder="ej. Vue"
                            autocomplete="off"
                        >
                        <span v-if="errors.name" class="field__error">{{ errors.name[0] }}</span>
                        <span v-else class="field__hint">así se lee en la vitrina.</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="s-icon">icono</label>
                        <input
                            id="s-icon"
                            type="file"
                            accept="image/svg+xml,image/png,image/webp,image/jpeg,image/gif"
                            class="input input--file"
                            @change="onIconChange"
                        >
                        <span v-if="errors.icon" class="field__error">{{ errors.icon[0] }}</span>
                        <span v-else class="field__hint">svg, png, webp, jpg o gif, hasta 2 MB</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="s-mastery">dominio: {{ mastery }}%</label>
                        <input
                            id="s-mastery"
                            v-model.number="mastery"
                            type="range"
                            min="0"
                            max="100"
                            step="5"
                            class="deck__vol skill-range"
                            :style="{ '--fill': `${mastery}%` }"
                        >
                        <span v-if="errors.mastery" class="field__error">{{ errors.mastery[0] }}</span>
                        <span v-else class="field__hint">de 0 a 100; es lo que llena la barra.</span>
                    </div>
                </div>

                <footer class="forge__foot">
                    <RouterLink class="btn btn--ghost" :to="{ name: 'skills' }">cancelar</RouterLink>
                    <button
                        class="btn btn--primary"
                        type="submit"
                        :disabled="sending || !ready || loadingTypes || noTypes"
                    >
                        <span v-if="sending" class="loading-dots">enviando</span>
                        <span v-else>guardar</span>
                    </button>
                </footer>

                <p
                    v-if="message"
                    class="forge__msg"
                    :class="{ 'forge__msg--bad': failed }"
                    role="status"
                >
                    {{ failed ? '✕' : '▸' }} {{ message }}
                </p>
            </div>
        </section>

        <aside class="forge__side">
            <section class="panel">
                <header class="panel__head"></header>

                <div class="panel__body">
                    <article class="skill preview-skill" :title="`${name || 'sin nombre'} (${mastery}%)`">
                        <span class="skill__icon">
                            <img v-if="preview" class="skill__img" :src="preview" alt="">
                            <span v-else class="skill__glyph fx-flicker" aria-hidden="true">◌</span>
                        </span>

                        <span class="skill__pips" aria-hidden="true">
                            <span
                                v-for="i in 10"
                                :key="i"
                                class="skill__pip"
                                :class="{ 'skill__pip--on': i <= pips }"
                            ></span>
                        </span>
                    </article>

                    <p class="preview-skill__name">{{ name || 'sin nombre' }}</p>
                </div>
            </section>
        </aside>
    </form>
</template>

<style scoped>
.skill-range {
    width: 100%;
}

.preview-skill {
    width: min(100%, 180px);
    margin-inline: auto;
}

.preview-skill__name {
    margin-top: var(--sp-3);
    font-family: var(--font-term);
    font-size: var(--fs-md);
    letter-spacing: var(--tracking);
    text-transform: capitalize;
    text-align: center;
    color: var(--fg-soft);
    overflow-wrap: anywhere;
}
</style>
