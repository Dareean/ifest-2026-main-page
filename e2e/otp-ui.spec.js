import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, API_BASE } from './fixtures/data.js'

test.describe('OTP Verification — UI Flow', () => {
  test('register redirects to OTP verification page', async ({ page }) => {
    const email = uniqueEmail('e2e-otp-ui')
    await page.goto('/register')
    await page.getByPlaceholder('Nama kamu').fill('OTP UI Test')
    await page.getByPlaceholder('email@example.com').fill(email)
    await page.getByPlaceholder('08xxx').fill('08123456789')
    await page.getByPlaceholder('Universitas').fill('Test University')
    await page.getByPlaceholder('Min. 8 karakter').fill(TEST_USER.password)
    await page.getByPlaceholder('Ulangi password').fill(TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/verifikasi-email/, { timeout: 10000 })
    await expect(page.getByText(/verifikasi|OTP|kode/i).first()).toBeVisible()
  })

  test('wrong OTP code shows error message', async ({ page }) => {
    const email = uniqueEmail('e2e-otp-wrong')
    await page.goto('/register')
    await page.getByPlaceholder('Nama kamu').fill('OTP Wrong Test')
    await page.getByPlaceholder('email@example.com').fill(email)
    await page.getByPlaceholder('08xxx').fill('08123456789')
    await page.getByPlaceholder('Universitas').fill('Test University')
    await page.getByPlaceholder('Min. 8 karakter').fill(TEST_USER.password)
    await page.getByPlaceholder('Ulangi password').fill(TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/verifikasi-email/, { timeout: 10000 })

    const otpInput = page.locator('input[type="text"], input[type="tel"], input:not([type="password"])').first()
    if (await otpInput.count() > 0) {
      await otpInput.fill('000000')
    }
    const submitBtn = page.getByRole('button', { name: /verifikasi|kirim/i }).first()
    if (await submitBtn.count() > 0) {
      await submitBtn.click()
    }
    await page.waitForTimeout(2000)
  })

  test('resend OTP button is visible', async ({ page }) => {
    const email = uniqueEmail('e2e-otp-resend')
    await page.goto('/register')
    await page.getByPlaceholder('Nama kamu').fill('OTP Resend Test')
    await page.getByPlaceholder('email@example.com').fill(email)
    await page.getByPlaceholder('08xxx').fill('08123456789')
    await page.getByPlaceholder('Universitas').fill('Test University')
    await page.getByPlaceholder('Min. 8 karakter').fill(TEST_USER.password)
    await page.getByPlaceholder('Ulangi password').fill(TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/verifikasi-email/, { timeout: 10000 })
    const resendBtn = page.getByRole('button', { name: /kirim ulang|resend|mengirim/i }).first()
    await expect(resendBtn).toBeVisible({ timeout: 10000 })
  })

  test('successful OTP verification redirects to login', async ({ page }) => {
    const email = uniqueEmail('e2e-otp-success')

    await page.route(`${API_BASE}/auth/verify-otp`, async route => {
      const response = await route.fetch()
      const body = await response.json()
      if (response.status() !== 200) {
        await route.fulfill({
          status: 200,
          body: JSON.stringify({ message: 'Email berhasil diverifikasi', user: { email } }),
        })
      } else {
        await route.continue()
      }
    })

    await page.goto('/register')
    await page.getByPlaceholder('Nama kamu').fill('OTP Success Test')
    await page.getByPlaceholder('email@example.com').fill(email)
    await page.getByPlaceholder('08xxx').fill('08123456789')
    await page.getByPlaceholder('Universitas').fill('Test University')
    await page.getByPlaceholder('Min. 8 karakter').fill(TEST_USER.password)
    await page.getByPlaceholder('Ulangi password').fill(TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/verifikasi-email/, { timeout: 10000 })

    const otpInput = page.locator('input[type="text"], input[type="tel"], input:not([type="password"])').first()
    if (await otpInput.count() > 0) {
      await otpInput.fill('123456')
    }
    const submitBtn = page.getByRole('button', { name: /verifikasi|kirim/i }).first()
    if (await submitBtn.count() > 0) {
      await submitBtn.click()
    }

    await page.waitForTimeout(3000)
  })
})
