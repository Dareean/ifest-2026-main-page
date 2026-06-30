<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

import {
  LayoutDashboard, Trophy, Bell, User, LogOut, Menu, X,
  Home, ChevronRight, HelpCircle,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const sidebarOpen = ref(false)
const unreadCount = ref(0)

const navItems = [
  { path: '/dashboard', name: 'Overview', icon: LayoutDashboard },
  { path: '/dashboard/competitions', name: 'Lomba', icon: Trophy },
  { path: '/dashboard/notifications', name: 'Notifikasi', icon: Bell },
  { path: '/dashboard/profile', name: 'Profil', icon: User },
  { path: '/dashboard/help', name: 'Bantuan', icon: HelpCircle },
]

watch(() => auth.user, (u) => {
  unreadCount.value = u?.unread_notifications_count || 0
}, { immediate: true })

async function handleLogout() {
  await auth.logout()
  router.push('/')
}

onMounted(() => {
  if (!auth.isAuthenticated) {
    router.push('/login')
  }
})
</script>

<template>
  <div v-if="auth.isAuthenticated" class="min-h-screen riso-canvas bg-background flex">
    <!-- Mobile overlay -->
    <Transition name="fade">
      <div v-if="sidebarOpen" class="fixed inset-0 bg-[#04000D]/30 z-40 md:hidden" @click="sidebarOpen = false"></div>
    </Transition>

    <!-- Sidebar -->
    <aside :class="[
      'fixed md:sticky top-0 left-0 z-50 h-screen bg-white flex flex-col transition-transform duration-300 w-64 flex-shrink-0 border-r border-[#04000D]/5',
      sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
    ]">
      <!-- Logo -->
      <div class="px-6 py-6 flex items-center justify-center border-b border-[#04000D]/5 relative">
        <router-link to="/" class="flex items-center gap-3.5 justify-center">
          <img src="../../assets/logo_utama/logo_untad.webp" alt="UNTAD Logo" class="h-8 object-contain" />
          <img src="../../assets/logo_utama/HMTI LOGO.webp" alt="HMTI Logo" class="h-8 object-contain" />
          <img src="../../assets/logo_utama/Logo-IFEST-2026.webp" alt="I-FEST Logo" class="h-8 object-contain" />
        </router-link>
        <button @click="sidebarOpen = false" class="md:hidden absolute right-6 text-on-surface-variant hover:text-on-surface transition-colors">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- User card -->
      <div class="mx-4 mt-5 p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-black text-[#DCEEB1] flex items-center justify-center font-mono font-black text-sm flex-shrink-0 overflow-hidden">
          <img 
            v-if="auth.user?.avatar && auth.user.avatar !== 'null' && auth.user.avatar !== 'undefined'"
            :src="auth.user.avatar.startsWith('/storage') ? 'http://localhost:8000' + auth.user.avatar : auth.user.avatar"
            class="w-full h-full object-cover"
            alt="Avatar"
          />
          <span v-else>{{ auth.user?.name?.charAt(0)?.toUpperCase() || '?' }}</span>
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-bold text-xs text-on-surface truncate leading-tight">{{ auth.user?.name }}</p>
          <p class="font-mono text-[10px] text-on-surface-variant/70 truncate mt-0.5">{{ auth.user?.email }}</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 pt-5 space-y-1">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          @click="sidebarOpen = false"
          class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all duration-150"
          :class="route.path === item.path
            ? 'bg-[#04000D] text-[#DCEEB1] shadow-sm'
            : 'text-on-surface-variant hover:bg-slate-50 hover:text-on-surface'"
        >
          <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
          <span>{{ item.name }}</span>
          <span v-if="item.name === 'Notifikasi' && unreadCount > 0" class="ml-auto bg-accent-magenta text-white font-mono text-[9px] font-black px-1.5 py-0.5 rounded-full">{{ unreadCount }}</span>
          <ChevronRight v-if="route.path === item.path" class="w-3.5 h-3.5 ml-auto opacity-60" />
        </router-link>
      </nav>

      <!-- Bottom -->
      <div class="p-3 border-t border-[#04000D]/5 space-y-1">
        <router-link to="/" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-on-surface-variant hover:bg-slate-50 hover:text-on-surface transition-all w-full">
          <Home class="w-4 h-4" /> Beranda
        </router-link>
        <button @click="handleLogout" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-accent-magenta hover:bg-accent-magenta/[0.03] transition-all w-full text-left">
          <LogOut class="w-4 h-4" /> Keluar
        </button>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 min-w-0 flex flex-col">
      <!-- Top bar mobile -->
      <header class="md:hidden flex items-center justify-between px-4 py-3 bg-white border-b border-[#04000D]/5 shadow-sm">
        <button @click="sidebarOpen = true" class="text-on-surface-variant hover:text-on-surface transition-colors p-1">
          <Menu class="w-5 h-5" />
        </button>
        <span class="font-extrabold text-base tracking-tight text-on-surface">I-FEST</span>
        <div class="w-7"></div>
      </header>

      <!-- Content -->
      <main class="flex-1 p-5 md:p-8 lg:p-10 xl:p-12 overflow-y-auto">
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
