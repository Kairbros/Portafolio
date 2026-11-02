const API_URL = import.meta.env.VITE_API_URL ?? ''

const ORIGIN = API_URL.replace(/\/+$/, '').replace(/\/api$/, '')

export const IMAGE_EXTENSIONS = [
    'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'bmp', 'svg', 'gif', 'tiff', 'ico', 'heic', 'heif', 'jfif', 'pjpeg', 'pjp', 'apng', 'cur', 'xbm', 'dib', 'tga', 'psd', 'raw', 'arw', 'cr2', 'nef', 'orf', 'sr2',
]

export function extensionOf(path: string | null | undefined): string {
    const clean = String(path ?? '').split(/[?#]/)[0] ?? ''
    const match = clean.match(/\.([a-z0-9]+)$/i)
    return match?.[1]?.toLowerCase() ?? ''
}

export function isExternal(path: string | null | undefined): boolean {
    return /^https?:\/\//i.test(String(path ?? ''))
}

export function isImage(path: string | null | undefined): boolean {
    return IMAGE_EXTENSIONS.includes(extensionOf(path))
}

export function isPage(path: string | null | undefined): boolean {
    return ['html', 'htm'].includes(extensionOf(path))
}

export function fileUrl(path: string | null | undefined): string {
    const value = String(path ?? '')
    if (!value) return ''
    if (isExternal(value)) return value
    return `${ORIGIN}/storage/${value.replace(/^\/+/, '')}`
}

export function coverUrl(project: any): string {
    const cover = String(project?.cover ?? '')
    if (cover) return fileUrl(cover)

    const folder = String(project?.folder ?? '')
    return !isExternal(folder) && isImage(folder) ? fileUrl(folder) : ''
}
