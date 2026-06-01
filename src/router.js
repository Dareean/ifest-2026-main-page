import { createRouter, createWebHistory } from 'vue-router'
import HomePage from './pages/HomePage.vue'
import CompetitionsPage from './pages/CompetitionsPage.vue'
import RoadshowPage from './pages/RoadshowPage.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: HomePage
  },
  {
    path: '/kompetisi',
    name: 'Competitions',
    component: CompetitionsPage
  },
  {
    path: '/competitions',
    redirect: '/kompetisi'
  },
  {
    path: '/roadshow',
    name: 'Roadshow',
    component: RoadshowPage
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0, left: 0 }
    }
  }
})

export default router
