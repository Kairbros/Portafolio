import { nextTick } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { begin, end } from '@/composables/useLoader'

import params from '@/views/params/index.vue'

import projects_shell from '@/views/projects/index.vue'
import projects_type from '@/views/projects/type.vue'
import project_open from '@/views/projects/open.vue'

import project from '@/views/params/projects/index.vue'
import project_create from '@/views/params/projects/create.vue'

import typeproject from '@/views/params/typeproject/index.vue'
import typeproject_create from '@/views/params/typeproject/create.vue'

import background from '@/views/params/background/index.vue'
import background_create from '@/views/params/background/create.vue'

import profile from '@/views/params/profile/index.vue'
import profile_create from '@/views/params/profile/create.vue'

import music from '@/views/params/music_track/index.vue'
import music_create from '@/views/params/music_track/create.vue'

import skills_params from '@/views/params/skills/index.vue'
import skill_create from '@/views/params/skills/create.vue'

import typeskill from '@/views/params/typeskill/index.vue'
import typeskill_create from '@/views/params/typeskill/create.vue'

import stickers from '@/views/params/stickers/index.vue'
import sticker_create from '@/views/params/stickers/create.vue'

import about_params from '@/views/params/about/index.vue'

import main_shell from '@/views/main/index.vue'
import main_home from '@/views/main/home.vue'
import main_about from '@/views/main/about.vue'
import main_skills from '@/views/main/skills.vue'
import main_notebook from '@/views/main/notebook.vue'
import notebook_params from '@/views/params/notebook/index.vue'
import NotFound from '@/views/error/404.vue'

const routes = [
  {
    path: '/',
    component: main_shell,
    children: [
      { path: '', name: 'home', component: main_home },
      { path: 'about', name: 'about', component: main_about, meta: { title: 'nav.about' } },
      { path: 'skills', name: 'skills_home', component: main_skills, meta: { title: 'nav.skills' } },
      { path: 'notebook', name: 'notebook', component: main_notebook, meta: { title: 'nav.notebook' } },
    ],
  },

  {
    path: '/projects',
    meta: { title: 'nav.projects' },
    component: projects_shell,
    children: [
      { path: '', name: 'projects_home', component: projects_type, props: true },
      { path: 'type/:typeId', name: 'projects_type', component: projects_type, props: true },
      { path: ':slug', name: 'project_open', component: project_open, props: true },
    ],
  },

  {
    path: '/params',
    meta: { title: 'nav.edit', noindex: true },
    component: params,
    children: [
      { path: '', redirect: '/params/projects' },

      { path: 'projects', name: 'projects', component: project },
      { path: 'projects/create', name: 'project_create', component: project_create },
      { path: 'projects/:id/edit', name: 'project_edit', component: project_create, props: true },

      { path: 'types', name: 'types', component: typeproject },
      { path: 'types/create', name: 'typeproject_create', component: typeproject_create },
      { path: 'types/:id/edit', name: 'typeproject_edit', component: typeproject_create, props: true },

      { path: 'backgrounds', name: 'backgrounds', component: background },
      { path: 'backgrounds/create', name: 'background_create', component: background_create },

      { path: 'profiles', name: 'profiles', component: profile },
      { path: 'profiles/create', name: 'profile_create', component: profile_create },

      { path: 'musictrack', name: 'music', component: music },
      { path: 'musictrack/create', name: 'music_create', component: music_create },
      { path: 'musictrack/:id/edit', name: 'music_edit', component: music_create, props: true },

      { path: 'skills', name: 'skills', component: skills_params },
      { path: 'skills/create', name: 'skill_create', component: skill_create },
      { path: 'skills/:id/edit', name: 'skill_edit', component: skill_create, props: true },

      { path: 'typeskills', name: 'typeskills', component: typeskill },
      { path: 'typeskills/create', name: 'typeskill_create', component: typeskill_create },
      { path: 'typeskills/:id/edit', name: 'typeskill_edit', component: typeskill_create, props: true },

      { path: 'about', name: 'about_params', component: about_params },
      { path: 'notebook', name: 'notebook_params', component: notebook_params },

      { path: 'stickers', name: 'stickers', component: stickers },
      { path: 'stickers/create', name: 'sticker_create', component: sticker_create },
    ],
  },

  { path: '/:pathMatch(.*)*', component: NotFound, meta: { title: 'notFound.title', noindex: true } },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(() => {
  begin()
})

router.afterEach(() => {
  nextTick(end)
})

router.onError(() => {
  end()
})

export default router
