import axios from 'axios';
import { defineStore } from 'pinia';

const api_url = import.meta.env.VITE_API_URL;

type FieldErrors = Record<string, string[]>

function readError(error: unknown): { message: string; errors: FieldErrors } {
    if (axios.isAxiosError(error)) {
        const data = error.response?.data as { message?: string; errors?: FieldErrors } | undefined
        return {
            message: data?.message ?? error.message,
            errors: data?.errors ?? {},
        }
    }
    return { message: 'Ocurrió un error inesperado.', errors: {} }
}

export const useProjectStore = defineStore('projects', {
    state: () => ({
        projects: [] as any[],
        project: null as any,
        opened: {} as Record<string, any>,
        loaded: false,
        error: '' as string,
    }),

    actions: {
        async getProjects() {
            this.error = ''
            try {
                const res = await axios.get(`${api_url}/projects`)
                this.projects = res.data
                this.loaded = true
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async ensureProjects() {
            if (this.loaded) return
            await this.getProjects()
        },

        async getProjectByID(id: string) {
            this.error = ''
            try {
                const res = await axios.get(`${api_url}/projects/${id}`)
                this.project = res.data
                this.opened[id] = res.data
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async ensureProject(id: string) {
            const hit = this.opened[id]
            if (hit) {
                this.error = ''
                this.project = hit
                return
            }
            await this.getProjectByID(id)
        },

        async storeProject(params: object) {
            try {
                const res = await axios.post(`${api_url}/projects`, params)
                this.forget()
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        forget() {
            this.loaded = false
            this.opened = {}
        },

        async deleteProject(id: string) {
            try {
                const res = await axios.delete(`${api_url}/projects/${id}`)
                this.forget()
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },
    },
})

export const useTypeProjectStore = defineStore('typeProjects', {
    state: () => ({
        typeProjects: [] as any[],
        loaded: false,
        error: '' as string,
    }),

    actions: {
        async getTypeProjects() {
            this.error = ''
            try {
                const res = await axios.get(`${api_url}/typeprojects`)
                this.typeProjects = res.data
                this.loaded = true
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async ensureTypeProjects() {
            if (this.loaded) return
            await this.getTypeProjects()
        },

        async storeTypeProject(params: object) {
            try {
                const res = await axios.post(`${api_url}/typeprojects`, params)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async deleteTypeProject(id: string) {
            try {
                const res = await axios.delete(`${api_url}/typeprojects/${id}`)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        }
    },
})

export const useBackgroundImgStore = defineStore('backgroundImgs', {
    state: () => ({
        backgroundImgs: [] as any[],
        loaded: false,
        error: '' as string,
    }),

    actions: {
        async getBackgroundImgs() {
            this.error = ''
            try {
                const res = await axios.get(`${api_url}/backgroundimgs`)
                this.backgroundImgs = res.data
                this.loaded = true
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async ensureBackgroundImgs() {
            if (this.loaded) return
            await this.getBackgroundImgs()
        },

        async storeBackgroundImg(params: object) {
            try {
                const res = await axios.post(`${api_url}/backgroundimgs`, params)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async deleteBackgroundImg(id: string) {
            try {
                const res = await axios.delete(`${api_url}/backgroundimgs/${id}`)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        }
    },
})

export const useProfileImgStore = defineStore('profileImgs', {
    state: () => ({
        profileImgs: [] as any[],
        loaded: false,
        error: '' as string,
    }),

    actions: {
        async getProfileImgs() {
            this.error = ''
            try {
                const res = await axios.get(`${api_url}/profileimgs`)
                this.profileImgs = res.data
                this.loaded = true
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async ensureProfileImgs() {
            if (this.loaded) return
            await this.getProfileImgs()
        },
        async storeProfileImg(params: object) {
            try {
                const res = await axios.post(`${api_url}/profileimgs`, params)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async deleteProfileImg(id: string) {
            try {
                const res = await axios.delete(`${api_url}/profileimgs/${id}`)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        }
    },
})

export const useMusicStore = defineStore(('musicTracks'), {
    state: () => ({
        musicTracks: [] as any[],
        loaded: false,
        error: '' as string,
    }),
    actions: {
        async getMusicTracks() {
            this.error = ''
            try {
                const res = await axios.get(`${api_url}/musictrack`)
                this.musicTracks = res.data
                this.loaded = true
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async ensureMusicTracks() {
            if (this.loaded) return
            await this.getMusicTracks()
        },

        async storeMusicTrack(params: object) {
            this.error = ''
            try {
                const res = await axios.post(`${api_url}/musictrack`, params)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async deleteMusicTrack(id: string) {
            this.error = ''
            try {
                const res = await axios.delete(`${api_url}/musictrack/${id}`)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        }
    }
})

export const useTypeSkillStore = defineStore('typeSkills', {
    state: () => ({
        typeSkills: [] as any[],
        loaded: false,
        error: '' as string,
    }),

    actions: {
        async getTypeSkills() {
            this.error = ''
            try {
                const res = await axios.get(`${api_url}/typeskill`)
                this.typeSkills = res.data
                this.loaded = true
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async ensureTypeSkills() {
            if (this.loaded) return
            await this.getTypeSkills()
        },

        async storeTypeSkill(params: object) {
            this.error = ''
            try {
                const res = await axios.post(`${api_url}/typeskill`, params)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async deleteTypeSkill(id: string) {
            this.error = ''
            try {
                const res = await axios.delete(`${api_url}/typeskill/${id}`)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },
    },
})

export const useSkillStore = defineStore('skills', {
    state: () => ({
        skills: [] as any[],
        loaded: false,
        error: '' as string,
    }),

    actions: {
        async getSkills() {
            this.error = ''
            try {
                const res = await axios.get(`${api_url}/skills`)
                this.skills = res.data
                this.loaded = true
            } catch (error) {
                this.error = readError(error).message
            }
        },

        async ensureSkills() {
            if (this.loaded) return
            await this.getSkills()
        },

        async storeSkill(params: object) {
            this.error = ''
            try {
                const res = await axios.post(`${api_url}/skills`, params)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },

        async deleteSkill(id: string) {
            this.error = ''
            try {
                const res = await axios.delete(`${api_url}/skills/${id}`)
                this.loaded = false
                return { ok: true as const, data: res.data }
            } catch (error) {
                return { ok: false as const, ...readError(error) }
            }
        },
    },
})