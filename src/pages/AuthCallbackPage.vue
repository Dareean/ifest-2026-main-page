<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()
const statusMsg = ref('Memproses login Google...')

onMounted(async () => {
  // Session already set by backend — fetch user data
  await auth.handleGoogleCallback()

  if (!auth.isAuthenticated) {
    statusMsg.value = 'Login gagal. Mengalihkan...'
    setTimeout(() => router.push('/login?error=google_failed'), 1500)
    return
  }

  statusMsg.value = 'Berhasil! Mengalihkan...'

  if (window.location.hash.includes('action=connect')) {
    router.push('/dashboard/profile?google=connected')
  } else {
    router.push(auth.isAdmin ? '/dashboard/admin' : '/dashboard')
  }
})
</script>

<template>
  <div class="min-h-screen bg-surface flex flex-col items-center justify-center gap-4">
    <div class="w-12 h-12 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></div>
    <p class="font-mono text-sm font-bold text-on-surface-variant">{{ statusMsg }}</p>
  </div>
</template>
