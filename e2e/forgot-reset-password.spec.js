import { test, expect } from '@playwright/test'
import { API_BASE, TEST_USER, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, getResetTokenViaApi } from './helpers/seed.js'

const NEW_PASSWORD = 'NewPass456!'

test.describe('Forgot Password — API', () => {
  let email

  test.beforeAll(async () => {
    email = uniqueEmail('e2e-forgot')
    await registerUserViaApi({
      name: TEST_USER.name,
      email,
      password: TEST_USER.password,
      password_confirmation: TEST_USER.password,
    })
  })

  test('forgot-password with valid email returns 200', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/forgot-password`, {
      data: { email },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.message).toContain('Link reset password')
  })

  test('forgot-password with non-existent email returns 200 (no enumeration)', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/forgot-password`, {
      data: { email: 'nonexistent-' + email },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.message).toContain('Link reset password')
  })

  test('forgot-password with missing email returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/forgot-password`, {
      data: {},
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.email).toBeDefined()
  })

  test('forgot-password with invalid email format returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/forgot-password`, {
      data: { email: 'not-an-email' },
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.email).toBeDefined()
  })
})

test.describe('Reset Password — API', () => {
  let email, resetToken

  test.beforeAll(async () => {
    email = uniqueEmail('e2e-reset')
    await registerUserViaApi({
      name: TEST_USER.name,
      email,
      password: TEST_USER.password,
      password_confirmation: TEST_USER.password,
    })
    const res = await getResetTokenViaApi(email)
    resetToken = res.token
  })

  test('reset-password with valid token returns 200', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/reset-password`, {
      data: {
        email,
        token: resetToken,
        password: NEW_PASSWORD,
        password_confirmation: NEW_PASSWORD,
      },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.message).toContain('berhasil')
  })

  test('reset-password with invalid token returns 400', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/reset-password`, {
      data: {
        email,
        token: 'invalid-token-123',
        password: NEW_PASSWORD,
        password_confirmation: NEW_PASSWORD,
      },
    })
    expect(res.status()).toBe(400)
    const body = await res.json()
    expect(body.message).toContain('tidak valid')
  })

  test('reset-password with mismatched passwords returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/reset-password`, {
      data: {
        email,
        token: resetToken,
        password: NEW_PASSWORD,
        password_confirmation: 'DifferentPass1',
      },
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.password).toBeDefined()
  })

  test('reset-password with short password returns 422', async ({ request }) => {
    const res = await request.post(`${API_BASE}/auth/reset-password`, {
      data: {
        email,
        token: resetToken,
        password: '1234567',
        password_confirmation: '1234567',
      },
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.password).toBeDefined()
  })
})

test.describe('Forgot & Reset Password — UI', () => {
  let email

  test.beforeAll(async () => {
    email = uniqueEmail('e2e-forgot-ui')
    await registerUserViaApi({
      name: TEST_USER.name,
      email,
      password: TEST_USER.password,
      password_confirmation: TEST_USER.password,
    })
  })

  test('ForgotPasswordPage renders and submits successfully', async ({ page }) => {
    await page.goto('/lupa-password')
    await expect(page.getByText('Lupa Password')).toBeVisible()
    await expect(page.getByRole('button', { name: /kirim link reset/i })).toBeVisible()

    await page.fill('input[type="email"]', email)
    await page.getByRole('button', { name: /kirim link reset/i }).click()
    await expect(page.getByText('Link terkirim!')).toBeVisible()
    await expect(page.getByText(email)).toBeVisible()
  })

  test('ResetPasswordPage shows error when email query is missing', async ({ page }) => {
    await page.goto('/reset-password/some-token')
    await expect(page.getByText('Link reset password tidak valid')).toBeVisible()
  })

  test('ResetPasswordPage renders form with valid token and resets password', async ({ page }) => {
    const { token } = await getResetTokenViaApi(email)
    const testEmail = email

    await page.goto(`/reset-password/${token}?email=${encodeURIComponent(testEmail)}`)
    await expect(page.getByRole('heading', { name: /reset password/i })).toBeVisible()
    await expect(page.getByRole('button', { name: /reset password/i })).toBeVisible()

    await page.fill('input[placeholder="Minimal 8 karakter"]', NEW_PASSWORD)
    await page.fill('input[placeholder="Ulangi password baru"]', NEW_PASSWORD)
    await page.getByRole('button', { name: /reset password/i }).click()
    await expect(page.getByText('Password Berhasil Diubah!')).toBeVisible()
  })
})
