<script setup lang="ts">
import { nextTick, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import type { RouteLocationRaw } from 'vue-router'

type Tab = {
  label: string
  to?: RouteLocationRaw
  icon?: unknown
  exact?: boolean
}

withDefaults(defineProps<{
  tabs?: Tab[]
  path: string
  closeTo?: RouteLocationRaw | null
}>(), {
  tabs: () => [],
  closeTo: '/',
})

const route = useRoute()
const tabStrip = ref<HTMLElement | null>(null)

function revealCurrentTab() {
  const current = tabStrip.value?.querySelector(
    '.router-link-active, .folder__tab--current',
  )

  current?.scrollIntoView({ inline: 'nearest', block: 'nearest' })
}

onMounted(() => nextTick(revealCurrentTab))

watch(() => route.fullPath, () => nextTick(revealCurrentTab))
</script>

<template>
  <div class="desk">
    <section class="folder">
      <nav ref="tabStrip" class="folder__tabs" aria-label="secciones">
        <template v-for="tab in tabs" :key="tab.label">
          <RouterLink
            v-if="tab.to"
            class="folder__tab"
            :to="tab.to"
            :active-class="tab.exact ? 'folder__tab--near' : 'router-link-active'"
            exact-active-class="router-link-active"
          >
            <span v-if="tab.icon" class="folder__tab-icon" aria-hidden="true">
              <component :is="tab.icon" />
            </span>
            {{ tab.label }}
          </RouterLink>

          <span v-else class="folder__tab folder__tab--current">
            <span v-if="tab.icon" class="folder__tab-icon" aria-hidden="true">
              <component :is="tab.icon" />
            </span>
            {{ tab.label }}
          </span>
        </template>

        <slot name="tabs" />
      </nav>

      <div class="folder__body">
        <header class="folder__bar">
          <span class="folder__path">{{ path }}</span>

          <RouterLink
            v-if="closeTo"
            class="folder__close"
            :to="closeTo"
            title="cerrar"
            aria-label="cerrar"
          >
            ✕
          </RouterLink>
          <span v-else class="folder__close folder__close--off" aria-hidden="true">✕</span>
        </header>

        <div class="folder__content">
          <slot />
        </div>

      </div>
    </section>
  </div>
</template>
