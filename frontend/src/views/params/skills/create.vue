<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSkillStore, useTypeSkillStore } from '@/stores/Store'
import { track } from '@/composables/useLoader'
import { fileUrl } from '@/utils/media'

const props = defineProps<{ id?: string }>()

const router = useRouter()
const skillStore = useSkillStore()
const typeStore = useTypeSkillStore()

const name = ref('')
const mastery = ref(50)
const selected = ref<string | number>('')
const icon = ref<File | null>(null)
const preview = ref('')
const currentIcon = ref('')
const missing = ref(false)

const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

const types = computed(() => typeStore.typeSkills ?? [])
const noTypes = computed(() => typeStore.loaded && !types.value.length)
const level = computed(() => Math.round(mastery.value / 10))
const ready = computed(() => !!selected.value && !!name.value.trim() && (!!icon.value || !!props.id))
const shownIcon = computed(() => preview.value || currentIcon.value)

function onIconChange(event: Event) {
    if (preview.value) URL.revokeObjectURL(preview.value)
    icon.value = (event.target as HTMLInputElement).files?.[0] ?? null
    preview.value = icon.value ? URL.createObjectURL(icon.value) : ''
}

onMounted(async () => {
    if (!props.id) {
        await track(typeStore.ensureTypeSkills())
        return
    }
    await track(Promise.all([typeStore.ensureTypeSkills(), skillStore.ensureSkills()]))
    const item = skillStore.skills.find((s: any) => String(s.id) === props.id)
    if (!item) {
        missing.value = true
        return
    }
    name.value = item.name ?? ''
    selected.value = item.id_type_skill ?? ''
    mastery.value = Number(item.mastery ?? 50)
    currentIcon.value = item.icon ? fileUrl(item.icon) : ''
})
onBeforeUnmount(() => {
    if (preview.value) URL.revokeObjectURL(preview.value)
})

async function save() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const data = new FormData()
    data.append('name', name.value)
    data.append('type', String(selected.value))
    data.append('mastery', String(mastery.value))
    if (icon.value) data.append('icon', icon.value)

    const res = props.id
        ? await skillStore.updateSkill(props.id, data)
        : await skillStore.storeSkill(data)
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    router.push({ name: 'skills' })
}
</script>

<template>
    <p v-if="missing" class="dd-state dd-state--bad">
        no existe
        <RouterLink class="dd-state__detail" :to="{ name: 'skills' }">volver</RouterLink>
    </p>

    <form v-else class="dd-form" @submit.prevent="save">
        <section class="dd-win">
            <h1 class="dd-win__bar">{{ id ? 'editar skill' : 'nueva skill' }}</h1>

            <div class="dd-form__fields">
                <div class="dd-field">
                    <label class="dd-field__label" for="s-type">tipo</label>
                    <select id="s-type" v-model="selected" class="dd-input" :disabled="noTypes">
                        <option value="" disabled>elige uno</option>
                        <option v-for="type in types" :key="type.id" :value="type.id">{{ type.name }}</option>
                    </select>
                    <span v-if="errors.type" class="dd-field__error">{{ errors.type[0] }}</span>
                    <span v-else-if="noTypes" class="dd-field__hint">
                        todavía no hay tipos,
                        <RouterLink :to="{ name: 'typeskill_create' }">crea uno</RouterLink>
                    </span>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="s-name">nombre</label>
                    <input id="s-name" v-model="name" type="text" class="dd-input" placeholder="vue" autocomplete="off">
                    <span v-if="errors.name" class="dd-field__error">{{ errors.name[0] }}</span>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="s-icon">ícono</label>
                    <input
                        id="s-icon"
                        type="file"
                        accept="image/svg+xml,image/png,image/webp,image/jpeg,image/gif"
                        class="dd-input"
                        @change="onIconChange"
                    >
                    <span v-if="errors.icon" class="dd-field__error">{{ errors.icon[0] }}</span>
                    <span v-else class="dd-field__hint">
                        {{ id ? 'vacío deja el actual; ' : '' }}svg, png, webp, jpg o gif, hasta 2 MB
                    </span>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="s-mastery">nivel: {{ mastery }}</label>
                    <input
                        id="s-mastery"
                        v-model.number="mastery"
                        type="range"
                        min="0"
                        max="100"
                        step="5"
                        class="dd-range"
                        :style="{ '--fill': `${mastery}%` }"
                    >
                    <span v-if="errors.mastery" class="dd-field__error">{{ errors.mastery[0] }}</span>
                </div>
            </div>

            <div class="dd-form__foot">
                <p v-if="message" class="dd-form__msg" :class="{ 'dd-form__msg--bad': failed }" role="status">
                    {{ message }}
                </p>
                <RouterLink class="dd-btn" :to="{ name: 'skills' }">cancelar</RouterLink>
                <button class="dd-btn dd-btn--tone" type="submit" :disabled="sending || !ready">
                    {{ sending ? 'guardando' : 'guardar' }}
                </button>
            </div>
        </section>

        <section class="dd-win" aria-label="Vista previa">
            <div class="dd-win__bar">vista previa</div>
            <div class="skill-preview">
                <img v-if="shownIcon" class="skill-preview__icon" :src="shownIcon" alt="">
                <span v-else class="skill-preview__icon dd-scan" aria-hidden="true"></span>
                <span class="skill-preview__name">{{ name || 'sin nombre' }}</span>
                <span class="skill-preview__pips" aria-hidden="true">
                    <span v-for="i in 10" :key="i" :class="{ on: i <= level }"></span>
                </span>
            </div>
        </section>
    </form>
</template>

<style scoped>
.skill-preview {
    display: grid;
    grid-template-columns: 20px minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    padding: 12px;
    font-size: 14px;
}

.skill-preview__icon {
    width: 20px;
    height: 20px;
    object-fit: contain;
}

.skill-preview__name {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.skill-preview__pips {
    display: flex;
    gap: 2px;
}

.skill-preview__pips span {
    width: 6px;
    height: 10px;
    border: 1px solid var(--dd-line);
}

.skill-preview__pips .on {
    background: var(--dd-skills);
    border-color: var(--dd-skills);
}
</style>
