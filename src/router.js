import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'
import HomePage from './pages/HomePage.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: HomePage,
  },
  {
    path: '/kompetisi',
    name: 'Competitions',
    component: () => import('./pages/CompetitionsPage.vue'),
  },
  {
    path: '/competitions',
    redirect: '/kompetisi',
  },
  {
    path: '/roadshow',
    name: 'Roadshow',
    component: () => import('./pages/RoadshowPage.vue'),
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('./pages/LoginPage.vue'),
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('./pages/RegisterPage.vue'),
  },
  {
    path: '/auth/callback',
    name: 'AuthCallback',
    component: () => import('./pages/AuthCallbackPage.vue'),
  },
  {
    path: '/dashboard',
    component: () => import('./pages/dashboard/DashboardLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('./pages/dashboard/DashboardOverview.vue'),
      },
      {
        path: 'competitions',
        name: 'DashboardCompetitions',
        component: () => import('./pages/dashboard/DashboardCompetitions.vue'),
      },
      {
        path: 'notifications',
        name: 'DashboardNotifications',
        component: () => import('./pages/dashboard/DashboardNotifications.vue'),
      },
      {
        path: 'profile',
        name: 'DashboardProfile',
        component: () => import('./pages/dashboard/DashboardProfile.vue'),
      },
    ],
  },
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
  },
})

router.afterEach(() => {
  window.scrollTo(0, 0)
})

router.beforeEach((to, from, next) => {
  if (to.meta.requiresAuth) {
    const token = localStorage.getItem('auth_token')
    if (!token) {
      next({ name: 'Login' })
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router
