export type Hsv = { h: number; s: number; v: number }

export function hsvToRgb(h: number, s: number, v: number): [number, number, number] {
    const f = (n: number) => {
        const k = (n + h / 60) % 6
        return Math.round((v - v * s * Math.max(0, Math.min(k, 4 - k, 1))) * 255)
    }
    return [f(5), f(3), f(1)]
}

export function hsvToHex(h: number, s: number, v: number): string {
    return '#' + hsvToRgb(h, s, v).map((c) => c.toString(16).padStart(2, '0')).join('')
}

export function hexToHsv(hex: string): Hsv {
    const n = parseInt(hex.slice(1), 16)
    const r = ((n >> 16) & 255) / 255
    const g = ((n >> 8) & 255) / 255
    const b = (n & 255) / 255
    const max = Math.max(r, g, b)
    const d = max - Math.min(r, g, b)

    let h = 0
    if (d) {
        if (max === r) h = ((g - b) / d) % 6
        else if (max === g) h = (b - r) / d + 2
        else h = (r - g) / d + 4
        h = (h * 60 + 360) % 360
    }

    return { h, s: max ? d / max : 0, v: max }
}
