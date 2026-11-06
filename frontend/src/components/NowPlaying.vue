<script setup>
import { computed } from 'vue'
import { useMusic } from '@/composables/useMusic'
import { fileUrl } from '@/utils/media'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const { current, announcing, muted } = useMusic()

const artwork = computed(() =>
    current.value?.artwork_path ? fileUrl(current.value.artwork_path) : '',
)
</script>

<template>
    <Transition name="dd-fade">
        <aside
            v-if="announcing && current && !muted"
            class="dd-win dd-np"
            role="status"
            aria-live="polite"
        >
            <div class="dd-win__bar">{{ t('nowPlaying.title') }}</div>

            <div class="dd-np__body">
                <img v-if="artwork" class="dd-np__art" :src="artwork" alt="" />
                <span v-else class="dd-np__art dd-scan" aria-hidden="true"></span>

                <div class="dd-np__text">
                    <p>{{ current.title || t('nowPlaying.untitled') }}</p>
                    <p class="dd-np__artist">{{ current.artist || t('nowPlaying.unknownArtist') }}</p>
                </div>
            </div>
        </aside>
    </Transition>
</template>
