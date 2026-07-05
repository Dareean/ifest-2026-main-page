import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../utils/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(JSON.parse(localStorage.getItem('auth_user') || 'null'))
  const token = ref(localStorage.getItem('auth_token') || '')
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin' || user.value?.role === 'super_admin')
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')
  const unreadNotifications = computed(() => user.value?.notifications?.length || 0)

  async function register(data) {
    loading.value = true
    try {
      const res = await api.post('/auth/register', data)
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function login(data) {
    loading.value = true
    try {
      const res = await api.post('/auth/login', data)
      token.value = res.data.token
      user.value = res.data.user
      localStorage.setItem('auth_token', res.data.token)
      localStorage.setItem('auth_user', JSON.stringify(res.data.user))
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function fetchUser() {
    try {
      const res = await api.get('/auth/user')
      user.value = res.data.user
      localStorage.setItem('auth_user', JSON.stringify(res.data.user))
    } catch {
      logout()
    }
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch {
      // ignore
    }
    token.value = ''
    user.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  }

  async function googleLogin() {
    const res = await api.get('/auth/google/redirect')
    window.location.href = res.data.url
  }

  async function connectGoogle() {
    const res = await api.get('/auth/google/connect')
    window.location.href = res.data.url
  }

  async function disconnectGoogle() {
    await api.post('/auth/google/disconnect')
    user.value.google_id = null
    user.value.avatar = null
    localStorage.setItem('auth_user', JSON.stringify(user.value))
  }

  function handleGoogleCallback(authToken, userObj) {
    token.value = authToken
    user.value = userObj
    localStorage.setItem('auth_token', authToken)
    localStorage.setItem('auth_user', JSON.stringify(userObj))
  }

  async function sendOtp(email) {
    return api.post('/auth/send-otp', { email })
  }

  async function verifyOtp(email, otp) {
    const res = await api.post('/auth/verify-otp', { email, otp })
    token.value = res.data.token
    user.value = res.data.user
    localStorage.setItem('auth_token', res.data.token)
    localStorage.setItem('auth_user', JSON.stringify(res.data.user))
    return res.data
  }

  return {
    user, token, loading, isAuthenticated, isSuperAdmin, unreadNotifications,
    register, login, fetchUser, logout, googleLogin, connectGoogle,
    disconnectGoogle, handleGoogleCallback, sendOtp, verifyOtp,
  }
})
