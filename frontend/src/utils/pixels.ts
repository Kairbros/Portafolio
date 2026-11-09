export const SIZE = 32
export const CELLS = SIZE * SIZE
export const MAX_COLORS = 35

export const PALETTE: readonly string[] = [
    '#0d0913', '#2e2140', '#4a3a5e', '#8a7b9d', '#ece2f4', '#b6a1d0',
    '#8f7cf0', '#5a2a6e', '#e07bb5', '#b8336a', '#e8c27a', '#5f8f8a',
    '#e0603f', '#f0a35e', '#f3e3a1', '#a8c66c', '#3f6b4f', '#7fd0c8',
    '#5b8fd6', '#2f3f8f', '#c98fd0', '#e8b5a0', '#a8684a', '#5a3a2e',
]

export const PAPER = '#0d0913'

export function colorsOf(colors: readonly string[] | null | undefined): readonly string[] {
    return colors?.length ? colors : PALETTE
}

export function blank(): Uint8Array {
    return new Uint8Array(CELLS)
}

export function encode(cells: Uint8Array): string {
    return Array.from(cells, (c) => c.toString(36)).join('')
}

export function decode(text: string): Uint8Array {
    const cells = blank()
    for (let i = 0; i < CELLS; i++) cells[i] = parseInt(text[i] ?? '0', 36) || 0
    return cells
}

export function isEmpty(cells: Uint8Array): boolean {
    return !cells.some(Boolean)
}

export function pack(cells: Uint8Array, colors: readonly string[]): { pixels: string; colors: string[] } {
    if (isEmpty(cells)) return { pixels: '', colors: [] }

    const remap = new Map<number, number>()
    const used: string[] = []
    const packed = cells.map((c) => {
        if (!c) return 0
        if (!remap.has(c)) {
            used.push(colors[c - 1]!)
            remap.set(c, used.length)
        }
        return remap.get(c)!
    })

    return { pixels: encode(packed), colors: used }
}

export type View = { zoom: number; x: number; y: number }

export function paint(
    canvas: HTMLCanvasElement,
    cells: Uint8Array,
    colors: readonly string[] = PALETTE,
    grid = false,
    view: View = { zoom: 1, x: 0, y: 0 },
) {
    const ctx = canvas.getContext('2d')
    if (!ctx) return
    const size = canvas.width
    const s = (size * view.zoom) / SIZE
    const ox = view.x * size
    const oy = view.y * size
    const at = (n: number, offset: number) => Math.round(offset + n * s)

    ctx.imageSmoothingEnabled = false
    ctx.fillStyle = PAPER
    ctx.fillRect(0, 0, size, canvas.height)

    if (grid) {
        const width = Math.max(1, Math.round(size / 320))
        ctx.fillStyle = 'rgba(74, 58, 94, 0.28)'
        for (let i = 1; i < SIZE; i++) {
            const gx = at(i, ox)
            const gy = at(i, oy)
            if (gx >= 0 && gx < size) ctx.fillRect(gx, 0, width, canvas.height)
            if (gy >= 0 && gy < canvas.height) ctx.fillRect(0, gy, size, width)
        }
    }

    for (let i = 0; i < CELLS; i++) {
        const color = cells[i] ? colors[cells[i]! - 1] : undefined
        if (!color) continue
        const col = i % SIZE
        const row = Math.floor(i / SIZE)
        const x0 = at(col, ox)
        const y0 = at(row, oy)
        ctx.fillStyle = color
        ctx.fillRect(x0, y0, at(col + 1, ox) - x0, at(row + 1, oy) - y0)
    }
}
