import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api, { getCsrf } from '../utils/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  async function register(data) {
    loading.value = true
    try {
      await getCsrf()
      const res = await api.post('/auth/register', data)
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function login(data) {
    loading.value = true
    try {
      await getCsrf()
      const res = await api.post('/auth/login', data)
      user.value = res.data.user
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    try {
      const res = await api.get('/auth/user')
      user.value = res.data.user
    } catch {
      user.value = null
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch {
      // ignore
    }
    user.value = null
  }

  async function googleLogin() {
    try {
      await getCsrf()
      const res = await api.get('/auth/google/redirect')
      window.location.href = res.data.url
    } catch (e) {
      throw new Error(e.response?.data?.message || 'Gagal membuka Google login')
    }
  }

  async function connectGoogle() {
    try {
      await getCsrf()
      const res = await api.get('/auth/google/connect')
      window.location.href = res.data.url
    } catch (e) {
      throw new Error(e.response?.data?.message || 'Gagal menghubungkan Google')
    }
  }

  async function disconnectGoogle() {
    await api.post('/auth/google/disconnect')
    await fetchUser()
  }

  async function handleGoogleCallback() {
    await fetchUser()
  }

  async function sendOtp(email) {
    return api.post('/auth/send-otp', { email })
  }

  async function verifyOtp(email, otp) {
    await getCsrf()
    const res = await api.post('/auth/verify-otp', { email, otp })
    user.value = res.data.user
    return res.data
  }

  return {
    user, loading, isAuthenticated, isAdmin,
    register, login, fetchUser, logout, googleLogin, connectGoogle,
    disconnectGoogle, handleGoogleCallback, sendOtp, verifyOtp,
  }
})
