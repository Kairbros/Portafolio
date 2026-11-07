import { ref, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'

const BRAND = 'Dream Diary'

const SITE = (import.meta.env.VITE_SITE_URL || window.location.origin).replace(/\/+$/, '')

const own = ref('')

export function setPageTitle(title: string) {
    own.value = title
}

function upperFirst(text: string) {
    return text.charAt(0).toLocaleUpperCase() + text.slice(1)
}

function headTag<K extends 'link' | 'meta'>(tag: K, attr: string, value: string): HTMLElementTagNameMap[K] {
    let el = document.head.querySelector<HTMLElementTagNameMap[K]>(`${tag}[${attr}="${value}"]`)
    if (!el) {
        el = document.createElement(tag)
        el.setAttribute(attr, value)
        document.head.appendChild(el)
    }
    return el
}

export function usePageMeta() {
    const route = useRoute()
    const { t } = useI18n()

    watchEffect(() => {
        const key = route.meta.title as string | undefined
        const page = own.value || (key ? t(key) : '')
        document.title = page ? `${upperFirst(page)} · ${BRAND}` : BRAND

        const canonical = headTag('link', 'rel', 'canonical')
        canonical.href = SITE + route.path

        if (route.meta.noindex) headTag('meta', 'name', 'robots').content = 'noindex'
        else document.head.querySelector('meta[name="robots"]')?.remove()
    })
}
