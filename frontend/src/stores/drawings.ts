import axios from 'axios'
import { defineStore } from 'pinia'
import { readError } from '@/stores/Store'

const api_url = import.meta.env.VITE_API_URL

export type Drawing = {
    id: number
    name: string | null
    comment: string | null
    pixels: string
    colors: string[] | null
    drawing_page_id: number | null
    x: number
    y: number
    rotation: number
    z: number
    created_at: string
}

export type DrawingPage = {
    id: number
    drawings: Drawing[]
}

export type Placement = Pick<Drawing, 'x' | 'y' | 'rotation'> & { drawing_page_id: number; z?: number }

export const useDrawingStore = defineStore('drawings', {
    state: () => ({
        pages: [] as DrawingPage[],
        pending: [] as Drawing[],
        loaded: false,
        error: '' as string,
    }),

    actions: {
        async getPages() {
            this.error = ''
            try {
                const res = await axios.get(`${api_url}/drawings`)
                this.pages = res.data
                this.loaded = true
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async getPending() {
            try {
                const res = await axios.get(`${api_url}/admin/drawings/pending`)
                this.pending = res.data
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async send(params: { name: string; comment: string; pixels: string; colors: string[]; website: string }) {
            try {
                await axios.post(`${api_url}/drawings`, params)
                return { ok: true as const }
            } catch (error) {
                const read = readError(error)
                if (axios.isAxiosError(error) && error.response?.status === 429) {
                    return { ok: false as const, message: 'too-many', errors: read.errors }
                }
                return { ok: false as const, ...read }
            }
        },

        async addPage() {
            try {
                const res = await axios.post(`${api_url}/drawing-pages`)
                this.pages.push({ ...res.data, drawings: [] })
                return { ok: true as const }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async deletePage(page: DrawingPage) {
            try {
                await axios.delete(`${api_url}/drawing-pages/${page.id}`)
                return { ok: true as const }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async place(drawing: Drawing, placement: Placement) {
            try {
                const res = await axios.put(`${api_url}/drawings/${drawing.id}/place`, placement)
                return { ok: true as const, data: res.data as Drawing }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async unplace(drawing: Drawing) {
            try {
                await axios.put(`${api_url}/drawings/${drawing.id}/unplace`)
                return { ok: true as const }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async discard(drawing: Drawing) {
            try {
                await axios.delete(`${api_url}/drawings/${drawing.id}`)
                this.pending = this.pending.filter((d) => d.id !== drawing.id)
                return { ok: true as const }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },
    },
})
