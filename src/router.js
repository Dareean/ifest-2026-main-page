import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('./pages/HomePage.vue'),
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
    meta: { skipAuthCheck: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('./pages/RegisterPage.vue'),
    meta: { skipAuthCheck: true },
  },
  {
    path: '/auth/callback',
    name: 'AuthCallback',
    component: () => import('./pages/AuthCallbackPage.vue'),
    meta: { skipAuthCheck: true },
  },
  {
    path: '/verifikasi-email',
    name: 'OtpVerification',
    component: () => import('./pages/OtpVerificationPage.vue'),
    meta: { skipAuthCheck: true },
  },
  {
    path: '/lupa-password',
    name: 'ForgotPassword',
    component: () => import('./pages/ForgotPasswordPage.vue'),
    meta: { skipAuthCheck: true },
  },
  {
    path: '/reset-password/:token',
    name: 'ResetPassword',
    component: () => import('./pages/ResetPasswordPage.vue'),
    meta: { skipAuthCheck: true },
  },
  {
    path: '/invoice/:id',
    name: 'Invoice',
    component: () => import('./pages/InvoicePage.vue'),
    meta: { requiresAuth: true }
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
      {
        path: 'undangan',
        name: 'DashboardInvitations',
        component: () => import('./pages/dashboard/DashboardInvitations.vue'),
      },
      {
        path: 'help',
        name: 'DashboardHelp',
        component: () => import('./pages/dashboard/DashboardHelp.vue'),
      },
    ],
  },
  {
    path: '/dashboard/admin',
    component: () => import('./pages/dashboard/admin/DashboardAdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        name: 'AdminDashboard',
        component: () => import('./pages/dashboard/admin/AdminDashboard.vue'),
      },
      {
        path: 'pendaftaran',
        name: 'AdminPendaftaran',
        component: () => import('./pages/dashboard/admin/AdminPendaftaran.vue'),
      },
      {
        path: 'pendaftaran/:id',
        name: 'AdminPendaftaranDetail',
        component: () => import('./pages/dashboard/admin/AdminPendaftaranDetail.vue'),
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: () => import('./pages/dashboard/admin/AdminUsers.vue'),
      },
      {
        path: 'notifications',
        name: 'AdminNotifications',
        component: () => import('./pages/dashboard/admin/AdminNotifications.vue'),
      },
      {
        path: 'manage',
        name: 'AdminManage',
        component: () => import('./pages/dashboard/admin/AdminManage.vue'),
      },
      {
        path: 'lombas',
        name: 'AdminLombas',
        component: () => import('./pages/dashboard/admin/AdminLombas.vue'),
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
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

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  if (!auth.user && !to.meta?.skipAuthCheck) {
    try {
      await auth.fetchUser()
    } catch {
      // not authenticated
    }
  }

  if (to.meta.requiresAuth) {
    if (!auth.isAuthenticated) {
      next({ name: 'Login', query: { redirect: to.fullPath } })
      return
    }
    if (to.meta.requiresAdmin) {
      if (!auth.isAdmin) {
        next({ name: 'Dashboard' })
        return
      }
    } else {
      // Prevent admin from accessing participant-facing dashboard pages
      if (auth.isAdmin && to.path.startsWith('/dashboard') && !to.path.startsWith('/dashboard/admin')) {
        next({ name: 'AdminDashboard' })
        return
      }
    }
    next()
  } else {
    // If admin is logged in, redirect login/register back to admin dashboard
    if (auth.isAdmin && (to.name === 'Login' || to.name === 'Register')) {
      next({ name: 'AdminDashboard' })
      return
    }
    next()
  }
})

export default router
