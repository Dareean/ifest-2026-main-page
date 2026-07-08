<script setup>
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../utils/api'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const statusMsg = ref('Memproses login Google...')

onMounted(async () => {
  const token = route.query.token
  const error = route.query.error

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

  try {
    localStorage.setItem('auth_token', token)
    auth.token = token

    const res = await api.get('/auth/user')
    const userData = res.data.user

    auth.user = userData
    localStorage.setItem('auth_user', JSON.stringify(userData))

    statusMsg.value = 'Berhasil! Mengalihkan...'

    if (route.query.action === 'connect') {
      router.push('/dashboard/profile?google=connected')
      return
    }

    router.push(userData.role === 'admin' ? '/dashboard/admin' : '/dashboard')
  } catch {
    statusMsg.value = 'Terjadi kesalahan. Mengalihkan...'
    setTimeout(() => router.push('/login'), 1500)
  }
})
</script>

<template>
  <div class="min-h-screen bg-surface flex flex-col items-center justify-center gap-4">
    <!-- Spinner -->
    <div class="w-12 h-12 rounded-full border-4 border-primary/20 border-t-primary animate-spin"></div>
    <p class="font-mono text-sm font-bold text-on-surface-variant">{{ statusMsg }}</p>
  </div>
</template>
