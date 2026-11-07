import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv, type Plugin } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import Icons from 'unplugin-icons/vite'

const PUBLIC_ROUTES = ['/', '/projects', '/about', '/skills', '/notebook']

function seo(siteUrl: string): Plugin {
  const site = siteUrl.replace(/\/+$/, '')

  return {
    name: 'dd-seo',
    transformIndexHtml: (html) => html.replaceAll('__SITE_URL__', site),
    generateBundle() {
      this.emitFile({
        type: 'asset',
        fileName: 'robots.txt',
        source: `User-agent: *\nDisallow: /params\n\nSitemap: ${site}/sitemap.xml\n`,
      })

      const urls = PUBLIC_ROUTES.map((path) => `  <url><loc>${site}${path === '/' ? '/' : path}</loc></url>`)
      this.emitFile({
        type: 'asset',
        fileName: 'sitemap.xml',
        source: `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls.join('\n')}\n</urlset>\n`,
      })
    },
  }
}

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [
      Icons({ compiler: 'vue3', autoInstall: true }),
      vue(),
      vueDevTools(),
      seo(env.VITE_SITE_URL || 'http://localhost:5173'),
    ],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
  }
})
