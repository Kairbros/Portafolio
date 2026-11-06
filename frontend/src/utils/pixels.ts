// dibujos de la libreta: 32x32 cuadros guardados como texto, un carácter por cuadro
export const SIZE = 32
export const CELLS = SIZE * SIZE

// 0 es vacío; los demás salen de la paleta del sitio
export const PALETTE = [
    null,
    '#0d0913', '#2e2140', '#4a3a5e', '#8a7b9d', '#ece2f4', '#b6a1d0',
    '#8f7cf0', '#5a2a6e', '#e07bb5', '#b8336a', '#e8c27a', '#5f8f8a',
] as const

export const PAPER = '#0d0913'

export function blank(): Uint8Array {
    return new Uint8Array(CELLS)
}

export function encode(cells: Uint8Array): string {
    return Array.from(cells, (c) => c.toString(16)).join('')
}

export function decode(text: string): Uint8Array {
    const cells = blank()
    for (let i = 0; i < CELLS; i++) cells[i] = parseInt(text[i] ?? '0', 16) || 0
    return cells
}

export function isEmpty(cells: Uint8Array): boolean {
    return !cells.some(Boolean)
}

export function paint(canvas: HTMLCanvasElement, cells: Uint8Array, grid = false) {
    const ctx = canvas.getContext('2d')
    if (!ctx) return
    const s = canvas.width / SIZE
    ctx.imageSmoothingEnabled = false
    ctx.fillStyle = PAPER
    ctx.fillRect(0, 0, canvas.width, canvas.height)

    if (grid) {
        ctx.fillStyle = 'rgba(74, 58, 94, 0.28)'
        for (let i = 1; i < SIZE; i++) {
            ctx.fillRect(Math.round(i * s), 0, 1, canvas.height)
            ctx.fillRect(0, Math.round(i * s), canvas.width, 1)
        }
    }

    for (let i = 0; i < CELLS; i++) {
        const color = PALETTE[cells[i]!]
        if (!color) continue
        ctx.fillStyle = color
        ctx.fillRect((i % SIZE) * s, Math.floor(i / SIZE) * s, s, s)
    }
}
