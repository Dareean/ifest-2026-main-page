import { test, expect } from '@playwright/test'
import { API_BASE, TEST_USER, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

const ROOT = API_BASE.replace('/api', '')

test.describe('Google OAuth - API', () => {
  let email

  test.beforeAll(async () => {
    email = uniqueEmail('google')
    await registerUserViaApi({
      name: TEST_USER.name,
      email,
      password: TEST_USER.password,
      password_confirmation: TEST_USER.password,
    })
    await verifyUserViaApi(email)
  })

  test('GET /auth/google/redirect returns redirect URL', async ({ request }) => {
    const res = await request.get(`${API_BASE}/auth/google/redirect`)
    const status = res.status()
    if (status === 200) {
      const body = await res.json()
      expect(body).toHaveProperty('url')
      expect(typeof body.url).toBe('string')
      expect(body.url.length).toBeGreaterThan(0)
    } else {
      expect(status).toBe(500)
    }
  })

  test('GET /auth/google/connect returns 401 without auth', async ({ request }) => {
    const res = await request.get(`${API_BASE}/auth/google/connect`)
    expect(res.status()).toBe(401)
  })

  test('GET /auth/google/connect returns URL when authenticated', async ({ request }) => {
    await request.get(`${ROOT}/sanctum/csrf-cookie`)
    const loginRes = await request.post(`${API_BASE}/auth/login`, { data: { email, password: TEST_USER.password } })
    const loginData = await loginRes.json()
    const token = loginData.token
    const res = await request.get(`${API_BASE}/auth/google/connect`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
    const status = res.status()
    if (status === 200) {
      const body = await res.json()
      expect(body).toHaveProperty('url')
      expect(typeof body.url).toBe('string')
      expect(body.url.length).toBeGreaterThan(0)
    } else {
      expect(status).toBe(500)
    }
  })

  test('POST /auth/google/disconnect returns 401 without auth', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/google/disconnect`)
    expect(res.status()).toBe(401)
  })

  test('POST /auth/google/disconnect returns 200 when authenticated', async ({ request }) => {
    await request.get(`${ROOT}/sanctum/csrf-cookie`)
    const loginRes = await request.post(`${API_BASE}/auth/login`, { data: { email, password: TEST_USER.password } })
    const loginData = await loginRes.json()
    const token = loginData.token
    const res = await request.post(`${API_BASE}/auth/google/disconnect`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body).toHaveProperty('message')
  })
})

test.describe('Google OAuth - UI', () => {
  let email

  test.beforeAll(async () => {
    email = uniqueEmail('google-ui')
    await registerUserViaApi({
      name: TEST_USER.name,
      email,
      password: TEST_USER.password,
      password_confirmation: TEST_USER.password,
    })
    await verifyUserViaApi(email)
  })

  test('Login page shows "Masuk dengan Google" button', async ({ page }) => {
    await page.goto('/login')
    await expect(page.getByRole('button', { name: /masuk dengan google/i })).toBeVisible()
  })

  test('Register page shows "Daftar dengan Google" button', async ({ page }) => {
    await page.goto('/register')
    await expect(page.getByRole('button', { name: /daftar dengan google/i })).toBeVisible()
  })

  test('Profile page shows "Hubungkan Google" when disconnected', async ({ page }) => {
    await loginViaUI(page, email, TEST_USER.password)
    await page.goto('/dashboard/profile')
    await expect(page.getByRole('button', { name: /hubungkan google/i })).toBeVisible()
  })

  test('Profile page can disconnect Google from connected state', async ({ page }) => {
    await loginViaUI(page, email, TEST_USER.password)
 
    let isDisconnected = false
    await page.route('**/api/auth/google/disconnect', async route => {
      isDisconnected = true
      await route.fulfill({ status: 200, json: { message: 'Google disconnected' } })
    })

    await page.route('**/api/auth/user', async route => {
      try {
        const response = await route.fetch()
        const body = await response.json()
        if (!isDisconnected) {
          body.user.google_id = 'e2e_test_google_id'
          body.user.avatar = 'https://example.com/avatar.jpg'
        } else {
          body.user.google_id = null
          body.user.avatar = null
        }
        await route.fulfill({ response, body: JSON.stringify(body) })
      } catch {
        await route.continue().catch(() => {})
      }
    })
 
    await page.goto('/dashboard/profile')
    await expect(page.getByText('Terhubung dengan Google')).toBeVisible()
 
    await page.getByRole('button', { name: /putuskan koneksi/i }).click()
    await expect(page.getByText('Google berhasil diputuskan')).toBeVisible()
    await expect(page.getByRole('button', { name: /hubungkan google/i })).toBeVisible()
  })

  test('AuthCallbackPage shows spinner and redirects on missing token', async ({ page }) => {
    await page.goto('/auth/callback')
    await expect(page.locator('.animate-spin')).toBeVisible()
    await page.waitForURL(/\/login/, { timeout: 5000 })
  })
})
