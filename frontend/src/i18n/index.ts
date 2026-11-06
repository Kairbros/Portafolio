import { ref, watchEffect } from 'vue'
import { createI18n } from 'vue-i18n'
import es from './locales/es.json'
import en from './locales/en.json'

export type Locale = 'es' | 'en'

export const LOCALES: Locale[] = ['es', 'en']

const KEY = 'portafolio.locale'

function readSaved(): Locale | null {
    try {
        const saved = localStorage.getItem(KEY)
        return saved === 'es' || saved === 'en' ? saved : null
    } catch {
        return null
    }
}

// la primera visita usa el idioma del navegador
function guess(): Locale {
    return navigator.language?.toLowerCase().startsWith('es') ? 'es' : 'en'
}

// el idioma que eligió el visitante; el panel de edición siempre va en español
export const preferred = ref<Locale>(readSaved() ?? guess())

export const i18n = createI18n({
    legacy: false,
    locale: preferred.value,
    fallbackLocale: 'es',
    messages: { es, en },
})

export function setLocale(locale: Locale) {
    preferred.value = locale
    try {
        localStorage.setItem(KEY, locale)
    } catch {
        // sin almacenamiento el idioma dura hasta recargar
    }
}

export function useLocaleFor(admin: () => boolean) {
    watchEffect(() => {
        const locale = admin() ? 'es' : preferred.value
        i18n.global.locale.value = locale
        document.documentElement.lang = locale
    })
}

// devuelve campo_en si el sitio está en inglés y existe; si no, el español
export function localized(item: any, field: string): string {
    if (!item) return ''
    const english = item[`${field}_en`]
    if (i18n.global.locale.value === 'en' && typeof english === 'string' && english.trim()) {
        return english
    }
    return item[field] ?? ''
}
