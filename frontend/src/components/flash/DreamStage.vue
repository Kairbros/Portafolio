<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import AudioDeck from '@/components/ui/AudioDeck.vue'
import { useLoader } from '@/composables/useLoader'
import { isAdmin, signOut } from '@/utils/admin'
import GithubIcon from '~icons/simple-icons/github'
import ItchIcon from '~icons/simple-icons/itchdotio'
import PencilIcon from '~icons/pixelarticons/pencil'
import HomeIcon from '~icons/pixelarticons/home'
import LogoutIcon from '~icons/pixelarticons/logout'

const props = withDefaults(defineProps<{ admin?: boolean }>(), { admin: false })

type Item = { key: string; label: string; to: string; tone: string; exact?: boolean }

const publicSections: Item[] = [
    { key: 'home', label: 'inicio', to: '/', tone: 'var(--dd-home)', exact: true },
    { key: 'projects', label: 'proyectos', to: '/projects', tone: 'var(--dd-projects)' },
    { key: 'about', label: 'sobre mí', to: '/about', tone: 'var(--dd-about)' },
    { key: 'skills', label: 'skills', to: '/skills', tone: 'var(--dd-skills)' },
]

const adminSections: Item[] = [
    { key: 'projects', label: 'proyectos', to: '/params/projects' },
    { key: 'types', label: 'tipos', to: '/params/types' },
    { key: 'backgrounds', label: 'fondos', to: '/params/backgrounds' },
    { key: 'profiles', label: 'retratos', to: '/params/profiles' },
    { key: 'music', label: 'música', to: '/params/musictrack' },
    { key: 'skills', label: 'skills', to: '/params/skills' },
    { key: 'typeskills', label: 'tipos de skill', to: '/params/typeskills' },
    { key: 'stickers', label: 'stickers', to: '/params/stickers' },
].map((item) => ({ ...item, tone: 'var(--dd-bone)' }))

const elsewhere = [
    { label: 'github', href: 'https://github.com/Kairbros', icon: GithubIcon },
    { label: 'itch.io', href: 'https://just-kvin.itch.io/', icon: ItchIcon },
]

const route = useRoute()
const { visible: loading, progress } = useLoader()

const sections = computed(() => {
    if (!props.admin) return publicSections
    return isAdmin.value ? adminSections : []
})

function matches(item: Item) {
    if (item.exact) return route.path === item.to
    return route.path === item.to || route.path.startsWith(`${item.to}/`)
}

const current = computed(() => sections.value.find(matches) ?? null)

const tone = computed(() => {
    if (props.admin) return 'var(--dd-lilac)'
    return current.value?.tone ?? 'var(--dd-closed)'
})
</script>

<template>
    <div class="dd-shell">
        <div class="dd-stage" :style="{ '--dd-tone': tone }">
            <header class="dd-head">
                <RouterLink class="dd-brand" :to="admin ? '/params' : '/'">
                    dream diary<span v-if="admin" class="dd-brand__mode"> edición</span>
                </RouterLink>
                <AudioDeck />
            </header>

            <div class="dd-body">
                <nav class="dd-nav" aria-label="Secciones">
                    <ul class="dd-nav__list">
                        <li v-for="item in sections" :key="item.key">
                            <RouterLink
                                class="dd-nav__link"
                                :class="{
                                    'is-active': current?.key === item.key,
                                    'dd-nav__link--plain': admin || item.key === 'home',
                                }"
                                :style="{ '--tone': item.tone }"
                                :to="item.to"
                                :aria-current="current?.key === item.key ? 'page' : undefined"
                            >
                                <span class="dd-nav__mark" aria-hidden="true"></span>
                                {{ item.label }}
                            </RouterLink>
                        </li>
                        <li v-if="!admin">
                            <span class="dd-nav__closed" aria-disabled="true" title="todavía no abre">cerrado</span>
                        </li>
                    </ul>

                    <ul v-if="admin" class="dd-nav__out">
                        <li>
                            <RouterLink to="/">
                                <HomeIcon class="dd-nav__icon" aria-hidden="true" />
                                ver la web
                            </RouterLink>
                        </li>
                        <li v-if="isAdmin">
                            <button type="button" class="dd-nav__out-btn" @click="signOut">
                                <LogoutIcon class="dd-nav__icon" aria-hidden="true" />
                                salir
                            </button>
                        </li>
                    </ul>

                    <ul v-else class="dd-nav__out">
                        <li v-for="link in elsewhere" :key="link.label">
                            <a :href="link.href" target="_blank" rel="noopener noreferrer">
                                <component :is="link.icon" class="dd-nav__icon" aria-hidden="true" />
                                {{ link.label }}
                            </a>
                        </li>
                        <li>
                            <RouterLink class="dd-nav__edit" to="/params" aria-label="Editar" title="editar">
                                <PencilIcon class="dd-nav__icon" aria-hidden="true" />
                            </RouterLink>
                        </li>
                    </ul>
                </nav>

                <main class="dd-main">
                    <slot />
                </main>
            </div>

            <div
                v-if="loading"
                class="dd-loader"
                role="progressbar"
                aria-label="Cargando"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-valuenow="progress"
            >
                <p class="dd-loader__name">dream diary</p>
                <div class="dd-loader__meter">
                    <div class="dd-loader__bar">
                        <div class="dd-loader__fill" :style="{ width: `${progress}%` }"></div>
                    </div>
                    <span class="dd-loader__label">cargando {{ progress }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
