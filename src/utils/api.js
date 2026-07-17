import axios from 'axios'

const APP_URL = import.meta.env.VITE_APP_URL || 'http://localhost:8000'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || `${APP_URL}/api`,
  timeout: 15000,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

export async function getCsrf() {
  await api.get(`${APP_URL}/sanctum/csrf-cookie`)
}

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401 && !window.location.pathname.startsWith('/login')) {
      const { default: router } = await import('../router')
      if (router.currentRoute.value?.meta?.requiresAuth) {
        router.push('/login')
      }
    }
    return Promise.reject(error)
  }
)

export default api
