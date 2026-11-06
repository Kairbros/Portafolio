<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
    <div class="lost">
        <h1 class="dd-title">{{ t('notFound.title') }}</h1>
        <p class="dd-lead">{{ t('notFound.lead') }}</p>
        <RouterLink class="dd-btn dd-btn--tone lost__back" to="/">{{ t('notFound.back') }}</RouterLink>
    </div>
</template>

<style scoped>
.lost {
    display: grid;
    justify-items: start;
    gap: 10px;
}

.lost__back {
    margin-top: 12px;
}
</style>
