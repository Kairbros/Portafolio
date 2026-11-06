<script setup lang="ts">
import { ref } from 'vue'

// un campo con su versión en inglés; el inglés vacío muestra el español en la web
withDefaults(defineProps<{
    id: string
    label: string
    multiline?: boolean
    rows?: number
    placeholder?: string
    hint?: string
    error?: string
    errorEn?: string
    inputClass?: string
}>(), { multiline: false, rows: 3 })

const es = defineModel<string>({ required: true })
const en = defineModel<string>('en', { required: true })

const tab = ref<'es' | 'en'>('es')
</script>

<template>
    <div class="dd-field">
        <div class="dd-field__head">
            <label class="dd-field__label" :for="tab === 'es' ? id : `${id}-en`">{{ label }}</label>

            <div class="dd-langtabs" role="group" :aria-label="`idioma de ${label}`">
                <button type="button" class="dd-langtabs__btn" :aria-pressed="tab === 'es'" @click="tab = 'es'">
                    español
                </button>
                <button
                    type="button"
                    class="dd-langtabs__btn"
                    :class="{ 'dd-langtabs__btn--empty': !en.trim() }"
                    :aria-pressed="tab === 'en'"
                    @click="tab = 'en'"
                >
                    inglés
                </button>
            </div>
        </div>

        <template v-if="multiline">
            <textarea v-show="tab === 'es'" :id="id" v-model="es" class="dd-input" :class="inputClass" :rows="rows" :placeholder="placeholder"></textarea>
            <textarea v-show="tab === 'en'" :id="`${id}-en`" v-model="en" class="dd-input" :class="inputClass" :rows="rows" lang="en"></textarea>
        </template>
        <template v-else>
            <input v-show="tab === 'es'" :id="id" v-model="es" type="text" class="dd-input" :class="inputClass" :placeholder="placeholder" autocomplete="off">
            <input v-show="tab === 'en'" :id="`${id}-en`" v-model="en" type="text" class="dd-input" :class="inputClass" lang="en" autocomplete="off">
        </template>

        <span v-if="error" class="dd-field__error">{{ error }}</span>
        <span v-if="errorEn" class="dd-field__error">inglés: {{ errorEn }}</span>
        <span v-if="!error && !errorEn" class="dd-field__hint">
            {{ tab === 'en' ? 'vacío muestra el español' : hint }}
        </span>
    </div>
</template>
