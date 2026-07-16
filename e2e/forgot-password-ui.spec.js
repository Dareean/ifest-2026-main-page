import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, getResetTokenViaApi, verifyUserViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

const NEW_PASSWORD = 'ResetPass789!'

test.describe('Forgot & Reset Password — UI Flow', () => {
  let email

  test.beforeAll(async () => {
    email = uniqueEmail('e2e-forgot-ui')
    await registerUserViaApi({
      name: TEST_USER.name,
      email,
      password: TEST_USER.password,
      password_confirmation: TEST_USER.password,
    })
    await verifyUserViaApi(email)
  })

  test('forgot password form renders and accepts email', async ({ page }) => {
    await page.goto('/lupa-password')
    await expect(page.getByText(/lupa password|reset password/i).first()).toBeVisible({ timeout: 10000 })
    const emailInput = page.locator('input[type="email"]').first()
    await expect(emailInput).toBeVisible()
    await emailInput.fill(email)
    const submitBtn = page.getByRole('button', { name: /kirim|reset|konfirmasi/i }).first()
    if (await submitBtn.count() > 0) {
      await submitBtn.click()
      await page.waitForTimeout(2000)
    }
  })

  test('reset password form loads with token', async ({ page }) => {
    const tokenData = await getResetTokenViaApi(email)
    const token = tokenData.token
    expect(token).toBeDefined()

    await page.goto(`/reset-password/${token}?email=${email}`)
    await page.waitForLoadState('networkidle')
    const pwInputs = page.locator('input[type="password"]')
    expect(await pwInputs.count()).toBeGreaterThanOrEqual(1)
  })

  test('password mismatch shows validation error', async ({ page }) => {
    const tokenData = await getResetTokenViaApi(email)
    const token = tokenData.token

    await page.goto(`/reset-password/${token}?email=${email}`)
    await expect(page.locator('input[type="password"]').first()).toBeVisible({ timeout: 10000 })
    const pwInputs = page.locator('input[type="password"]')
    await expect(pwInputs).toHaveCount(2)
    
    await pwInputs.nth(0).fill(NEW_PASSWORD)
    await pwInputs.nth(1).fill('DifferentPass999!')
    const submitBtn = page.getByRole('button', { name: /reset|simpan|ubah/i }).first()
    await expect(submitBtn).toBeVisible()
    await submitBtn.click()
    
    await expect(page.getByText(/konfirmasi|match|cocok/i).first()).toBeVisible({ timeout: 10000 })
  })

  test('successful password reset allows login with new password', async ({ page }) => {
    const tokenData = await getResetTokenViaApi(email)
    const token = tokenData.token

    await page.goto(`/reset-password/${token}?email=${email}`)
    await expect(page.locator('input[type="password"]').first()).toBeVisible({ timeout: 10000 })
    const pwInputs = page.locator('input[type="password"]')
    await expect(pwInputs).toHaveCount(2)

    await pwInputs.nth(0).fill(NEW_PASSWORD)
    await pwInputs.nth(1).fill(NEW_PASSWORD)
    const submitBtn = page.getByRole('button', { name: /reset|simpan|ubah/i }).first()
    await expect(submitBtn).toBeVisible()
    await submitBtn.click()

    await expect(page.getByText('Password Berhasil Diubah').first()).toBeVisible({ timeout: 10000 })

    await page.goto('/login')
    await page.fill('input[type="email"]', email)
    await page.fill('input[type="password"]', NEW_PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 15000 })
    await expect(page.locator('text=Dashboard').first()).toBeVisible({ timeout: 10000 })
  })
})
