import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'
import HomePage from './pages/HomePage.vue'
import CompetitionsPage from './pages/CompetitionsPage.vue'
import RoadshowPage from './pages/RoadshowPage.vue'
import LoginPage from './pages/LoginPage.vue'
import RegisterPage from './pages/RegisterPage.vue'
import AuthCallbackPage from './pages/AuthCallbackPage.vue'
import InvoicePage from './pages/InvoicePage.vue'

import DashboardLayout from './pages/dashboard/DashboardLayout.vue'
import DashboardOverview from './pages/dashboard/DashboardOverview.vue'
import DashboardCompetitions from './pages/dashboard/DashboardCompetitions.vue'
import DashboardNotifications from './pages/dashboard/DashboardNotifications.vue'
import DashboardProfile from './pages/dashboard/DashboardProfile.vue'
import DashboardInvitations from './pages/dashboard/DashboardInvitations.vue'
import DashboardHelp from './pages/dashboard/DashboardHelp.vue'

import DashboardAdminLayout from './pages/dashboard/admin/DashboardAdminLayout.vue'
import AdminDashboard from './pages/dashboard/admin/AdminDashboard.vue'
import AdminPendaftaran from './pages/dashboard/admin/AdminPendaftaran.vue'
import AdminPendaftaranDetail from './pages/dashboard/admin/AdminPendaftaranDetail.vue'
import AdminUsers from './pages/dashboard/admin/AdminUsers.vue'
import AdminNotifications from './pages/dashboard/admin/AdminNotifications.vue'
import AdminManage from './pages/dashboard/admin/AdminManage.vue'
import AdminLombas from './pages/dashboard/admin/AdminLombas.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: HomePage,
  },
  {
    path: '/kompetisi',
    name: 'Competitions',
    component: CompetitionsPage,
  },
  {
    path: '/competitions',
    redirect: '/kompetisi',
  },
  {
    path: '/roadshow',
    name: 'Roadshow',
    component: RoadshowPage,
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginPage,
    meta: { skipAuthCheck: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: RegisterPage,
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
    component: InvoicePage,
    meta: { requiresAuth: true }
  },
  {
    path: '/dashboard',
    component: DashboardLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: DashboardOverview,
      },
      {
        path: 'competitions',
        name: 'DashboardCompetitions',
        component: DashboardCompetitions,
      },
      {
        path: 'notifications',
        name: 'DashboardNotifications',
        component: DashboardNotifications,
      },
      {
        path: 'profile',
        name: 'DashboardProfile',
        component: DashboardProfile,
      },
      {
        path: 'undangan',
        name: 'DashboardInvitations',
        component: DashboardInvitations,
      },
      {
        path: 'help',
        name: 'DashboardHelp',
        component: DashboardHelp,
      },
    ],
  },
  // Admin routes (moved to top level to prevent nesting under DashboardLayout)
  {
    path: '/dashboard/admin',
    component: DashboardAdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        name: 'AdminDashboard',
        component: AdminDashboard,
      },
      {
        path: 'pendaftaran',
        name: 'AdminPendaftaran',
        component: AdminPendaftaran,
      },
      {
        path: 'pendaftaran/:id',
        name: 'AdminPendaftaranDetail',
        component: AdminPendaftaranDetail,
      },
      {
        path: 'users',
        name: 'AdminUsers',
        component: AdminUsers,
      },
      {
        path: 'notifications',
        name: 'AdminNotifications',
        component: AdminNotifications,
      },
      {
        path: 'manage',
        name: 'AdminManage',
        component: AdminManage,
      },
      {
        path: 'lombas',
        name: 'AdminLombas',
        component: AdminLombas,
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
