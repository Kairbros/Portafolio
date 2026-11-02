<script setup lang="ts">
import { ref } from 'vue';
import { useTypeSkillStore } from '@/stores/Store';
import router from '@/router';

const typeStore = useTypeSkillStore()

const name = ref('')
const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

async function createType() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const res = await typeStore.storeTypeSkill({ name: name.value })
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    message.value = res.data?.message ?? 'guardado'
    await typeStore.getTypeSkills()
    setTimeout(() => router.push({ name: 'typeskills' }))
}
</script>

<template>
    <form class="forge" @submit.prevent="createType">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body forge__body">
                <header>
                    <h1 class="t-heading">creación de tipo de skill</h1>
                    <p class="t-mute">un cajón para agrupar lo que sabes hacer.</p>
                </header>

                <div class="forge__fields">
                    <div class="field">
                        <label class="field__label" for="ts-name">nombre</label>
                        <input
                            id="ts-name"
                            v-model="name"
                            type="text"
                            class="input"
                            placeholder="ej. Lenguajes"
                            autocomplete="off"
                        >
                        <span v-if="errors.name" class="field__error">{{ errors.name[0] }}</span>
                        <span v-else class="field__hint">así se lee el grupo en la vitrina.</span>
                    </div>
                </div>

                <footer class="forge__foot">
                    <RouterLink class="btn btn--ghost" :to="{ name: 'typeskills' }">cancelar</RouterLink>
                    <button class="btn btn--primary" type="submit" :disabled="sending || !name.trim()">
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
                    <article class="type-card">
                        <span class="tag">{{ name || 'sin nombre' }}</span>
                        <p class="type-card__uses">sin usar</p>
                    </article>
                </div>
            </section>
        </aside>
    </form>
</template>
