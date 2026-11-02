<script setup>
import { computed } from 'vue'
import { useMusic } from '@/composables/useMusic'
import { fileUrl } from '@/utils/media'

const { current, announcing, muted } = useMusic()

const artwork = computed(() =>
    current.value?.artwork_path ? fileUrl(current.value.artwork_path) : '',
)
</script>

<template>
    <Transition name="tape">
        <aside
            v-if="announcing && current && !muted"
            class="tape"
            role="status"
            aria-live="polite"
        >
            <header class="tape__head">
                <span class="tape__eq" aria-hidden="true"><i></i><i></i><i></i></span>
            </header>

            <div class="tape__body">
                <div class="tape__art">
                    <img v-if="artwork" class="tape__img" :src="artwork" alt="" />
                    <span v-else class="tape__art-empty" aria-hidden="true">♪</span>
                </div>

                <div class="tape__text">
                    <p class="tape__title">{{ current.title || 'sin título' }}</p>
                    <p class="tape__artist">{{ current.artist || 'artista desconocido' }}</p>
                </div>
            </div>
        </aside>
    </Transition>
</template>
