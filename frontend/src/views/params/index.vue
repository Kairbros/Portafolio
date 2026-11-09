<script setup lang="ts">
import { ref } from 'vue'
import { isAdmin, signIn } from '@/utils/admin'

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
    <RouterView v-if="isAdmin" :key="$route.fullPath" />

    <div v-else class="dd-gate">
        <form class="dd-win dd-gate__win" @submit.prevent="enter">
            <h1 class="dd-win__bar">acceso</h1>

            <div class="dd-form__fields">
                <div class="dd-field">
                    <label class="dd-field__label" for="gate-user">usuario</label>
                    <input id="gate-user" v-model="user" type="text" class="dd-input" autocomplete="username" autofocus>
                </div>

                <div class="dd-field">
                    <label class="dd-field__label" for="gate-pass">contraseña</label>
                    <input id="gate-pass" v-model="password" type="password" class="dd-input" autocomplete="current-password">
                </div>

                <p v-if="message" class="dd-field__error" role="alert">{{ message }}</p>
            </div>

            <div class="dd-form__foot">
                <button class="dd-btn dd-btn--tone" type="submit" :disabled="sending || !user || !password">
                    {{ sending ? 'entrando' : 'entrar' }}
                </button>
            </div>
        </form>
    </div>
</template>
