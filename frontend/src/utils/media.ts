const API_URL = (import.meta.env.VITE_API_URL ?? '').replace(/\/+$/, '')

export const ORIGIN = API_URL.replace(/\/api$/, '')

export function fileUrl(path: string | null | undefined): string {
    const value = String(path ?? '')
    if (!value) return ''
    if (/^https?:\/\//i.test(value)) return value
    return `${ORIGIN}/storage/${value.replace(/^\/+/, '')}`
}

export function coverUrl(project: any): string {
    return fileUrl(project?.cover)
}

export function downloadUrl(fileId: number | string): string {
    return `${API_URL}/projects/files/${fileId}/download`
}

export function formatSize(bytes: number): string {
    if (bytes >= 1024 ** 3) return `${(bytes / 1024 ** 3).toFixed(1)} GB`
    if (bytes >= 1024 ** 2) return `${Math.round(bytes / 1024 ** 2)} MB`
    return `${Math.max(1, Math.round(bytes / 1024))} KB`
}
