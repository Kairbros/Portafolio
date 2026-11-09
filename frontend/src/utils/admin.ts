import axios from 'axios'
import { computed, ref } from 'vue'

const API_URL = import.meta.env.VITE_API_URL ?? ''

const KEY = 'portafolio.admin'

const token = ref(sessionStorage.getItem(KEY) ?? '')

export const isAdmin = computed(() => token.value !== '')

function encode(user: string, password: string): string {
    const bytes = new TextEncoder().encode(`${user}:${password}`)
    return btoa(String.fromCharCode(...bytes))
}

axios.interceptors.request.use((config) => {
    const method = (config.method ?? 'get').toLowerCase()

    const privateRead = String(config.url ?? '').includes('/admin/')

    if (token.value && (privateRead || (method !== 'get' && method !== 'head'))) {
        config.headers.Authorization = `Basic ${token.value}`
    }

    return config
})

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (axios.isAxiosError(error) && error.response?.status === 401) signOut()
        return Promise.reject(error)
    },
)

export async function signIn(user: string, password: string) {
    const candidate = encode(user, password)

    try {
        await axios.get(`${API_URL}/admin/check`, {
            headers: { Authorization: `Basic ${candidate}` },
        })

        token.value = candidate
        sessionStorage.setItem(KEY, candidate)

        return { ok: true as const }
    } catch (error) {
        const message = axios.isAxiosError(error)
            ? (error.response?.data as { message?: string })?.message ?? error.message
            : 'No se pudo comprobar el acceso.'

        return { ok: false as const, message }
    }
}

export function signOut() {
    token.value = ''
    sessionStorage.removeItem(KEY)
}
