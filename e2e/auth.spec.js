import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

test.describe('Auth Flow', () => {
  let userEmail
  let registeredUser

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-auth')
    const userData = { ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password }
    const res = await registerUserViaApi(userData)
    registeredUser = res.user
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

  test('redirect to login when accessing protected route without auth', async ({ page }) => {
    await page.goto('/dashboard/profile')
    await page.waitForURL(/\/login/)
  })

  test('register a new user and redirect to OTP verification page', async ({ page }) => {
    const email = uniqueEmail('e2e-reg')

    await page.goto('/register')
    await page.getByPlaceholder('Nama kamu').fill('Register Test')
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
    const email = uniqueEmail('e2e-unverif')
    await registerUserViaApi({ ...TEST_USER, email, password_confirmation: TEST_USER.password })

    await page.goto('/login')
    await page.fill('input[type="email"]', email)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')

    await expect(page.getByText(/verifikasi|OTP|kode|verif/i).first()).toBeVisible()
  })
})
