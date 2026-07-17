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
      if (res.data.user) {
        user.value = res.data.user
      }
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function verifyTwoFactor(code) {
    loading.value = true
    try {
      await getCsrf()
      const res = await api.post('/auth/2fa/verify', { code })
      user.value = res.data.user
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function recoverTwoFactor(recovery_code) {
    loading.value = true
    try {
      await getCsrf()
      const res = await api.post('/auth/2fa/recover', { recovery_code })
      user.value = res.data.user
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function get2faStatus() {
    const res = await api.get('/auth/2fa/status')
    return res.data
  }

  async function enable2fa() {
    await getCsrf()
    const res = await api.post('/auth/2fa/enable')
    return res.data
  }

  async function confirm2fa(code) {
    await getCsrf()
    const res = await api.post('/auth/2fa/verify-setup', { code })
    return res.data
  }

  async function disable2fa(code) {
    await getCsrf()
    const res = await api.post('/auth/2fa/disable', { code })
    return res.data
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
    // Clear any cached PII from localStorage
    ;['cached_pendaftarans', 'cached_lombas', 'cached_invitations', 'cached_notifications', 'last_notif_id'].forEach(k => localStorage.removeItem(k))
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
    verifyTwoFactor, recoverTwoFactor, get2faStatus, enable2fa,
    confirm2fa, disable2fa,
  }
})
