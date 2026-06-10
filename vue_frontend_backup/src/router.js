import { createRouter, createWebHistory } from 'vue-router'
import HomePage from './pages/HomePage.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: HomePage
  },
  {
    path: '/kompetisi',
    name: 'Competitions',
    component: () => import('./pages/CompetitionsPage.vue')
  },
  {
    path: '/competitions',
    redirect: '/kompetisi'
  },
  {
    path: '/roadshow',
    name: 'Roadshow',
    component: () => import('./pages/RoadshowPage.vue')
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0, left: 0 }
    }
  }
})

// Force viewport scroll reset to the absolute top on every route change
router.afterEach(() => {
  window.scrollTo(0, 0)
})

export default router

