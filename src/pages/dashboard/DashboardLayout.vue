<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

import {
  LayoutDashboard, Trophy, Bell, User, LogOut, Menu, X,
  Home, ChevronRight,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const sidebarOpen = ref(false)
const unreadCount = ref(0)

const navItems = [
  { path: '/dashboard', name: 'Overview', icon: LayoutDashboard },
  { path: '/dashboard/competitions', name: 'Lomba Saya', icon: Trophy },
  { path: '/dashboard/notifications', name: 'Notifikasi', icon: Bell },
  { path: '/dashboard/profile', name: 'Profil', icon: User },
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
      'fixed md:sticky top-0 left-0 z-50 h-screen bg-white flex flex-col transition-transform duration-300 w-64 flex-shrink-0',
      sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
    ]">
      <!-- Logo -->
      <div class="px-6 py-6 flex items-center justify-between border-b border-[#04000D]/20 border-dashed">
        <router-link to="/" class="flex items-center gap-2.5">
          <div class="w-8 h-8 bg-primary text-on-primary flex items-center justify-center font-mono font-black text-xs">IF</div>
          <div>
            <span class="font-black text-base tracking-tighter text-on-surface block leading-none riso-bleed">I-FEST</span>
            <span class="font-mono text-[9px] font-bold tracking-widest text-accent-magenta">2026</span>
          </div>
        </router-link>
        <button @click="sidebarOpen = false" class="md:hidden text-on-surface-variant hover:text-on-surface transition-colors">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- User card -->
      <div class="mx-4 mt-5 p-3 bg-[#F5F5F5] rounded-xl flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-primary text-on-primary flex items-center justify-center font-mono font-black text-sm flex-shrink-0">
          {{ auth.user?.name?.charAt(0)?.toUpperCase() || '?' }}
        </div>
        <div class="min-w-0 flex-1">
          <p class="font-mono text-sm font-bold text-on-surface truncate leading-tight">{{ auth.user?.name }}</p>
          <p class="font-mono text-[10px] text-on-surface-variant truncate">{{ auth.user?.email }}</p>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 pt-4 space-y-0.5">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          @click="sidebarOpen = false"
          class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider transition-all duration-150"
          :class="route.path === item.path
            ? 'bg-primary text-on-primary'
            : 'text-on-surface-variant hover:bg-[#F5F5F5] hover:text-on-surface'"
        >
          <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
          <span>{{ item.name }}</span>
          <span v-if="item.name === 'Notifikasi' && unreadCount > 0" class="ml-auto bg-accent-magenta text-white font-mono text-[9px] font-black px-1.5 py-0.5 rounded-full">{{ unreadCount }}</span>
          <ChevronRight v-if="route.path === item.path" class="w-3.5 h-3.5 ml-auto opacity-60" />
        </router-link>
      </nav>

      <!-- Bottom -->
      <div class="p-3 border-t border-[#04000D]/20 border-dashed space-y-0.5">
        <router-link to="/" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-on-surface-variant hover:bg-[#F5F5F5] hover:text-on-surface transition-all w-full">
          <Home class="w-4 h-4" /> Beranda
        </router-link>
        <button @click="handleLogout" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-accent-magenta hover:bg-accent-magenta/5 transition-all w-full">
          <LogOut class="w-4 h-4" /> Keluar
        </button>
      </div>
    </aside>

    <!-- Main -->
    <div class="flex-1 min-w-0 flex flex-col">
      <!-- Top bar mobile -->
      <header class="md:hidden flex items-center justify-between px-4 py-3 bg-[#F5F5F5] border-b border-[#04000D]/20 border-dashed">
        <button @click="sidebarOpen = true" class="text-on-surface-variant hover:text-on-surface transition-colors p-1">
          <Menu class="w-5 h-5" />
        </button>
        <span class="font-black text-base tracking-tighter text-on-surface">I-FEST</span>
        <div class="w-7"></div>
      </header>

      <!-- Content -->
      <main class="flex-1 p-4 md:p-8 lg:p-10 xl:p-12">
        <router-view />
      </main>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
