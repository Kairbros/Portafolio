<script setup lang="ts">
import { ref } from 'vue';
import { useTypeProjectStore } from '@/stores/Store';
import router from '@/router';
import TagInput from '@/components/ui/TagInput.vue';

const typeStore = useTypeProjectStore()

const name = ref('')
const description = ref('')
const extensions = ref<string[]>([])
const sending = ref(false)
const message = ref('')
const failed = ref(false)
const errors = ref<Record<string, string[]>>({})

function normalizeExtension(raw: string) {
    const clean = raw.trim().toLowerCase().replace(/^\.+/, '')
    return clean ? `.${clean}` : ''
}

async function createType() {
    sending.value = true
    message.value = ''
    failed.value = false
    errors.value = {}

    const res = await typeStore.storeTypeProject({
        name: name.value,
        description: description.value,
        extension: extensions.value.join(','),
    })
    sending.value = false

    if (!res.ok) {
        failed.value = true
        message.value = res.message
        errors.value = res.errors
        return
    }

    message.value = res.data?.message ?? 'guardado'
    await typeStore.getTypeProjects()
    setTimeout(() => router.push({ name: 'types' }))
}
</script>

<template>
    <form class="forge" @submit.prevent="createType">
        <section class="panel">
            <header class="panel__head"></header>

            <div class="panel__body forge__body">
                <header>
                    <h1 class="t-heading">creación de tipo</h1>
                    <p class="t-mute">una categoría para agrupar proyectos.</p>
                </header>

                <div class="forge__fields">
                    <div class="field">
                        <label class="field__label" for="t-name">nombre</label>
                        <input
                            id="t-name"
                            v-model="name"
                            type="text"
                            class="input"
                            placeholder="ej. Game"
                            autocomplete="off"
                        >
                        <span v-if="errors.name" class="field__error">{{ errors.name[0] }}</span>
                        <span v-else class="field__hint">como saldrá en la etiqueta del proyecto.</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="t-desc">descripción</label>
                        <input
                            id="t-desc"
                            v-model="description"
                            type="text"
                            class="input"
                            placeholder="Descripcion"
                            autocomplete="off"
                        >
                        <span v-if="errors.description" class="field__error">
                            {{ errors.description[0] }}
                        </span>
                        <span v-else class="field__hint">una línea para saber qué guarda este cajón.</span>
                    </div>

                    <div class="field">
                        <label class="field__label" for="t-ext">extensiones</label>
                        <TagInput
                            id="t-ext"
                            v-model="extensions"
                            :normalize="normalizeExtension"
                            placeholder="ej. .zip (Enter para añadir)"
                        />
                        <span v-if="errors.extension" class="field__error">
                            {{ errors.extension[0] }}
                        </span>
                        <span v-else class="field__hint">
                            qué archivos acepta este tipo. Enter o coma para añadir, ✕ para quitar.
                        </span>
                    </div>
                </div>

                <footer class="forge__foot">
                    <RouterLink class="btn btn--ghost" :to="{ name: 'types' }">cancelar</RouterLink>
                    <button class="btn btn--primary" type="submit" :disabled="sending">
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
                        <span class="tag" :data-type="name">{{ name || 'sin nombre' }}</span>

                        <p class="type-card__desc t-mute">
                            {{ description || 'sin descripción' }}
                        </p>

                        <ul v-if="extensions.length" class="type-card__ext">
                            <li v-for="ext in extensions" :key="ext">{{ ext }}</li>
                        </ul>
                        <p v-else class="type-card__desc t-mute">acepta cualquier archivo</p>

                        <p class="type-card__uses">sin usar</p>
                    </article>
                </div>
            </section>

        </aside>
    </form>
</template>
