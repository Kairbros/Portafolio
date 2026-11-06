<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { LOCALES, preferred, setLocale } from '@/i18n'

const { t } = useI18n()
</script>

<template>
    <div class="dd-lang" role="group" :aria-label="t('lang.label')">
        <button
            v-for="locale in LOCALES"
            :key="locale"
            type="button"
            class="dd-lang__btn"
            :lang="locale"
            :aria-pressed="preferred === locale"
            :title="t(`lang.${locale}Name`)"
            @click="setLocale(locale)"
        >
            {{ t(`lang.${locale}`) }}
        </button>
    </div>
</template>
