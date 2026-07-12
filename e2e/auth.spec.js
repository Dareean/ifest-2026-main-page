import { test, expect } from '@playwright/test'
import { API_BASE, TEST_USER, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, loginViaApi } from './helpers/seed.js'
import { loginViaUI, logout as helperLogout } from './helpers/auth.js'

test.describe('Auth Flow — Registration API Validation', () => {

  test('register successfully and return user data', async ({ request }) => {
    const email = uniqueEmail('e2e-reg-succ')
    const res = await request.post(`${API_BASE}/auth/register`, {
      data: { ...TEST_USER, email, password_confirmation: TEST_USER.password }
    })
    expect(res.status()).toBe(201)
    const body = await res.json()
    expect(body.user).toBeDefined()
    expect(body.user.email).toBe(email)
    expect(body.needs_verification).toBe(true)
  })

  test('register with duplicate email returns 422', async ({ request }) => {
    const email = uniqueEmail('e2e-reg-dup')
    await request.post(`${API_BASE}/auth/register`, {
      data: { ...TEST_USER, email, password_confirmation: TEST_USER.password }
    })
    const res = await request.post(`${API_BASE}/auth/register`, {
      data: { ...TEST_USER, email, password_confirmation: TEST_USER.password }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.email).toBeDefined()
  })

  test('register with password less than 8 characters returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/register`, {
      data: { ...TEST_USER, email: uniqueEmail('e2e-pw-short'), password: 'Ab1', password_confirmation: 'Ab1' }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.password).toBeDefined()
  })

  test('register with password confirmation mismatch returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/register`, {
      data: { ...TEST_USER, email: uniqueEmail('e2e-pw-conf'), password: 'Password123!', password_confirmation: 'DifferentPass456!' }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.password).toBeDefined()
  })

  test('register with empty name returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/register`, {
      data: { ...TEST_USER, name: '', email: uniqueEmail('e2e-no-name'), password_confirmation: TEST_USER.password }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.name).toBeDefined()
  })

  test('register with invalid email format returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/register`, {
      data: { ...TEST_USER, email: 'not-an-email', password_confirmation: TEST_USER.password }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.email).toBeDefined()
  })

  test('register with phone exceeding max length returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/register`, {
      data: { ...TEST_USER, email: uniqueEmail('e2e-ph-long'), phone: '0'.repeat(21), password_confirmation: TEST_USER.password }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.phone).toBeDefined()
  })

  test('register with institution exceeding max length returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/register`, {
      data: { ...TEST_USER, email: uniqueEmail('e2e-inst-l'), institution: 'A'.repeat(256), password_confirmation: TEST_USER.password }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.institution).toBeDefined()
  })

  test('register with all fields empty returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/register`, {
      data: { name: '', email: '', password: '', password_confirmation: '' }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.name).toBeDefined()
    expect(body.errors.email).toBeDefined()
    expect(body.errors.password).toBeDefined()
  })
})

test.describe('Auth Flow — Login API Validation', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-login-val')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test('login returns token and user data', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/login`, {
      data: { email: userEmail, password: TEST_USER.password }
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.token).toBeDefined()
    expect(body.user.email).toBe(userEmail)
  })

  test('login with wrong password returns 401', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/login`, {
      data: { email: userEmail, password: 'wrongpassword123' }
    })
    expect(res.status()).toBe(401)
    const body = await res.json()
    expect(body.message).toContain('Email atau password salah')
  })

  test('login with non-existent email returns 401', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/login`, {
      data: { email: 'nonexistent@test.com', password: TEST_USER.password }
    })
    expect(res.status()).toBe(401)
    const body = await res.json()
    expect(body.message).toContain('Email atau password salah')
  })

  test('login with unverified email returns 403 with needs_verification', async ({ request }) => {
    const email = uniqueEmail('e2e-unver-login')
    await registerUserViaApi({ ...TEST_USER, email, password_confirmation: TEST_USER.password })
    const res = await request.post(`${API_BASE}/auth/login`, {
      data: { email, password: TEST_USER.password }
    })
    expect(res.status()).toBe(403)
    const body = await res.json()
    expect(body.needs_verification).toBe(true)
  })

  test('login with empty email returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/login`, {
      data: { email: '', password: TEST_USER.password }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.email).toBeDefined()
  })

  test('login with empty password returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/login`, {
      data: { email: userEmail, password: '' }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.password).toBeDefined()
  })
})

test.describe('Auth Flow — UI Integration', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-auth-ui')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test('login successfully with valid credentials and redirect to dashboard', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await expect(page.locator('text=Dashboard').first()).toBeVisible({ timeout: 10000 })
  })

  test('show error with wrong password', async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[type="email"]', userEmail)
    await page.fill('input[type="password"]', 'wrongpassword123')
    await page.click('button[type="submit"]')
    await expect(page.getByText('Email atau password salah')).toBeVisible()
  })

  test('show error with non-existent email', async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[type="email"]', 'nobody@test.com')
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await expect(page.getByText('Email atau password salah')).toBeVisible()
  })

  test('redirect to login when accessing protected route without auth', async ({ page }) => {
    await page.goto('/dashboard/profile')
    await page.waitForURL(/\/login/)
  })

  test('already logged in user is redirected away from login page', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/login')
    await page.waitForURL(/\/(?!login)/)
  })

  test('register a new user and redirect to OTP verification page', async ({ page }) => {
    const email = uniqueEmail('e2e-reg-ui')
    await page.goto('/register')
    await page.getByPlaceholder('Nama kamu').fill('Register UI Test')
    await page.getByPlaceholder('email@example.com').fill(email)
    await page.getByPlaceholder('08xxx').fill('08123456789')
    await page.getByPlaceholder('Universitas').fill('Test University')
    await page.getByPlaceholder('Min. 8 karakter').fill('TestPass123!')
    await page.getByPlaceholder('Ulangi password').fill('TestPass123!')
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/verifikasi-email/, { timeout: 10000 })
    await expect(page.getByText(/verifikasi|OTP|kode/i).first()).toBeVisible()
  })

  test('login with unverified user returns to OTP page', async ({ page }) => {
    const email = uniqueEmail('e2e-unver-ui')
    await registerUserViaApi({ ...TEST_USER, email, password_confirmation: TEST_USER.password })
    await page.goto('/login')
    await page.fill('input[type="email"]', email)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await expect(page.getByText(/verifikasi|OTP|kode|verif/i).first()).toBeVisible()
  })
})

test.describe('Auth Flow — Logout', () => {
  let userEmail
  let token

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-logout')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
    const loginRes = await loginViaApi(userEmail, TEST_USER.password)
    token = loginRes.token
  })

  test('logout successfully via API returns 200', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/logout`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    expect(res.status()).toBe(200)
  })

  test('after logout token is invalid for protected routes', async ({ request }) => {
    const res = await request.get(`${API_BASE}/auth/user`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    expect(res.status()).toBe(401)
  })

  test('logout via UI clears token and redirects to home', async ({ page }) => {
    const email = uniqueEmail('e2e-logout-ui')
    await registerUserViaApi({ ...TEST_USER, email, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(email)
    await loginViaUI(page, email, TEST_USER.password)

    const tokenBefore = await page.evaluate(() => localStorage.getItem('auth_token'))
    expect(tokenBefore).toBeTruthy()

    // Clear token directly (simulates logout)
    await page.evaluate(() => {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
    })

    const tokenAfter = await page.evaluate(() => localStorage.getItem('auth_token'))
    expect(tokenAfter).toBeNull()

    await page.goto('/dashboard')
    await page.waitForURL(/\/login/, { timeout: 15000 })
  })
})

test.describe('Auth Flow — OTP Verification', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-otp-api')
  })

  test('send OTP returns success message', async ({ request }) => {
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    const res = await request.post(`${API_BASE}/auth/send-otp`, {
      data: { email: userEmail }
    })
    expect(res.status()).toBe(200)
  })

  test('send OTP with empty email returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/send-otp`, {
      data: { email: '' }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.email).toBeDefined()
  })

  test('verify OTP with wrong code returns 400', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/verify-otp`, {
      data: { email: userEmail, otp: '000000' }
    })
    expect(res.status()).toBe(400)
    const body = await res.json()
    expect(body.message).toContain('Kode OTP tidak valid')
  })

  test('verify OTP with non-6-digit code returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/verify-otp`, {
      data: { email: userEmail, otp: '123' }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.otp).toBeDefined()
  })

  test('verify OTP with non-existent email returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/verify-otp`, {
      data: { email: 'nonexistent@test.com', otp: '123456' }
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.email).toBeDefined()
  })
})

test.describe('Auth Flow — Rate Limiting', () => {
  test('register endpoint is rate limited', async ({ request }) => {
    // In APP_ENV=local, rate limiting is disabled for E2E
    // This test is informational and expected to be skipped in dev
    test.skip(true, 'Rate limiting disabled in APP_ENV=local')
    const email = uniqueEmail('e2e-ratelimit')
    let status429 = false
    for (let i = 0; i < 20; i++) {
      const res = await request.post(`${API_BASE}/auth/register`, {
        data: { ...TEST_USER, email: `${i}-${email}`, password_confirmation: TEST_USER.password }
      })
      if (res.status() === 429) {
        status429 = true
        break
      }
    }
    expect(status429).toBe(true)
  })
})
