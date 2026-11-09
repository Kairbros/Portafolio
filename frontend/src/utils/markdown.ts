import DOMPurify from 'dompurify'
import { marked } from 'marked'
import { ORIGIN } from '@/utils/media'

DOMPurify.addHook('afterSanitizeAttributes', (node) => {
    if (node.tagName === 'A' && node.hasAttribute('href')) {
        node.setAttribute('target', '_blank')
        node.setAttribute('rel', 'noopener noreferrer')
    }

    if (node.tagName === 'IMG') {
        const src = node.getAttribute('src') ?? ''
        if (src.startsWith('/storage/')) node.setAttribute('src', ORIGIN + src)
        node.setAttribute('loading', 'lazy')
    }
})

export function renderMarkdown(text: string | null | undefined): string {
    const source = String(text ?? '')
    if (!source.trim()) return ''

    return DOMPurify.sanitize(marked.parse(source, { async: false, gfm: true, breaks: true }))
}
