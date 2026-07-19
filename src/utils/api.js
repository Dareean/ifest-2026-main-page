import axios from 'axios'

// In dev (no explicit VITE_API_URL / VITE_APP_URL), use relative paths so
// ALL requests (CSRF + API calls) go through the Vite dev proxy.
// This keeps the XSRF-TOKEN and laravel_session cookies on the SAME origin
// (localhost:5173) so Axios can read and send them correctly.
// In production VITE_API_URL points to the real backend API base URL.
const API_BASE = import.meta.env.VITE_API_URL
  || (import.meta.env.VITE_APP_URL ? `${import.meta.env.VITE_APP_URL}/api` : '/api')

const api = axios.create({
  baseURL: API_BASE,
  timeout: 15000,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

export async function getCsrf() {
  // Use axios directly (not `api`) to avoid the /api baseURL prefix.
  // Goes through Vite proxy in dev → same-origin cookie on localhost:5173.
  // In production VITE_APP_URL is the backend (same origin anyway).
  const csrfBase = import.meta.env.VITE_APP_URL || window.location.origin
  await axios.get(`${csrfBase}/sanctum/csrf-cookie`, { withCredentials: true })
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
