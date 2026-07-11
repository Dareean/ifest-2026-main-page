<script setup>
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const statusMsg = ref('Memproses login Google...')

onMounted(() => {
  const token = route.query.token
  const error = route.query.error
  const role = route.query.role || ''
  const action = route.query.action || ''

  if (error) {
    statusMsg.value = 'Login Google gagal. Mengalihkan...'
    setTimeout(() => router.push('/login?error=google_failed'), 1500)
    return
  }

  if (!token) {
    statusMsg.value = 'Data tidak valid. Mengalihkan...'
    setTimeout(() => router.push('/login'), 1500)
    return
  }

  localStorage.setItem('auth_token', token)
  auth.token = token

  // Determine role: from URL param, fallback to existing localStorage
  let userRole = role
  if (!userRole) {
    try {
      const existing = JSON.parse(localStorage.getItem('auth_user') || 'null')
      if (existing?.role) userRole = existing.role
    } catch {}
  }

  // Set minimal user for router guard, then fetch full data in background
  if (userRole) {
    auth.user = { role: userRole }
    localStorage.setItem('auth_user', JSON.stringify({ role: userRole }))
  }

  statusMsg.value = 'Berhasil! Mengalihkan...'

  if (action === 'connect') {
    router.push('/dashboard/profile?google=connected')
  } else {
    router.push(userRole === 'admin' ? '/dashboard/admin' : '/dashboard')
  }

  // Fetch full user data asynchronously — doesn't block navigation
  auth.fetchUser()
})
</script>

<template>
  <div class="min-h-screen bg-surface flex flex-col items-center justify-center gap-4">
    <!-- Spinner -->
    <div class="w-12 h-12 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></div>
    <p class="font-mono text-sm font-bold text-on-surface-variant">{{ statusMsg }}</p>
  </div>
</template>
