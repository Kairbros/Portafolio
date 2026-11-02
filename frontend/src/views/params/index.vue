<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import FolderWindow from '@/components/ui/FolderWindow.vue'
import { isAdmin, signIn, signOut } from '@/utils/admin'

const route = useRoute()

const tabs = [
    { label: 'proyectos', to: '/params/projects' },
    { label: 'tipos', to: '/params/types' },
    { label: 'fondos', to: '/params/backgrounds' },
    { label: 'perfil', to: '/params/profiles' },
    { label: 'musica', to: '/params/musictrack' },
    { label: 'skills', to: '/params/skills' },
    { label: 'tipos skill', to: '/params/typeskills' },
]

const path = computed(() =>
    isAdmin.value
        ? `User:\\params${route.path.replace('/params', '').replace(/\//g, '\\')}`
        : 'User:\\params (bloqueado)',
)

const user = ref('')
const password = ref('')
const sending = ref(false)
const message = ref('')

async function enter() {
    sending.value = true
    message.value = ''

    const res = await signIn(user.value, password.value)
    sending.value = false

    if (!res.ok) {
        message.value = res.message
        return
    }

    password.value = ''
}
</script>

<template>
    <FolderWindow :tabs="isAdmin ? tabs : [{ label: 'acceso' }]" :path="path">
        <RouterView v-if="isAdmin" />

        <div v-else class="gate">
            <form class="panel gate__box" @submit.prevent="enter">
                <header class="panel__head"></header>

                <div class="panel__body gate__body">

                    <div class="gate__fields">
                        <div class="field">
                            <label class="field__label" for="gate-user">usuario</label>
                            <input
                                id="gate-user"
                                v-model="user"
                                type="text"
                                class="input"
                                autocomplete="username"
                                autofocus
                            >
                        </div>

                        <div class="field">
                            <label class="field__label" for="gate-pass">contraseña</label>
                            <input
                                id="gate-pass"
                                v-model="password"
                                type="password"
                                class="input"
                                autocomplete="current-password"
                            >
                        </div>
                    </div>

                    <p v-if="message" class="field__error gate__error">{{ message }}</p>

                    <div class="gate__actions">
                        <button
                            class="btn btn--primary gate__submit"
                            type="submit"
                            :disabled="sending || !user || !password"
                        >
                            {{ sending ? 'comprobando' : 'entrar' }}
                        </button>

                        <RouterLink class="gate__back" to="/">volver al inicio</RouterLink>
                    </div>
                </div>
            </form>
        </div>

        <template #tabs>
            <button v-if="isAdmin" type="button" class="folder__tab gate__out" @click="signOut">
                salir
            </button>
        </template>
    </FolderWindow>
</template>

<style scoped>
.gate {
    display: grid;
    place-items: center;
    min-height: 100%;
    padding: var(--sp-8) var(--sp-4);
}

.gate__box {
    width: min(26rem, 100%);
}

.gate__body {
    display: grid;
    gap: var(--sp-6);
}

.gate__fields {
    display: grid;
    gap: var(--sp-4);
}

.gate__error {
    margin: 0;
}

.gate__actions {
    display: grid;
    justify-items: center;
    gap: var(--sp-4);
}

.gate__submit {
    justify-content: center;
    width: 100%;
}

.gate__back {
    font-family: var(--font-term);
    font-size: var(--fs-sm);
    letter-spacing: var(--tracking);
    color: var(--fg-mute);
    transition: color var(--t-snap);
}

.gate__back:hover,
.gate__back:focus-visible {
    color: var(--accent);
}

.gate__out {
    margin-left: auto;
    cursor: pointer;
}
</style>
