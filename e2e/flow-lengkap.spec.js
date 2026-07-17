import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, API_BASE } from './fixtures/data.js'
import { verifyUserViaApi } from './helpers/seed.js'

test.describe('Full End-to-End: Landing → Register → Verify → Login → Dashboard → Profile → Browse → Logout', () => {
  const email = uniqueEmail('e2e-full')
  const testUser = {
    name: 'Full Flow User',
    email,
    password: 'FlowPass123!',
    phone: '08111111111',
    institution: 'Universitas Tadulako',
  }

  test('complete user journey from landing page', async ({ page }) => {
    // Track test progress
    const results = []

    // ===== STEP 0: START FROM LANDING PAGE =====
    await test.step('Visit landing page and explore hero section', async () => {
      await page.goto('/')
      await expect(page.getByText('DIGITAL SYMPHONY').first()).toBeVisible({ timeout: 15000 })
      await expect(page.getByText('THE BIGGEST IT FESTIVAL IN EASTERN INDONESIA')).toBeVisible()
      const cta = page.getByRole('button', { name: /EXPLORE THE SYMPHONY/i })
      await expect(cta).toBeVisible()
      results.push('Landing: Hero section loaded with CTA')
    })

    await test.step('Scroll to competitions section', async () => {
      await page.locator('#competitions-section').scrollIntoViewIfNeeded()
      await page.waitForTimeout(1000)
      await expect(page.getByText('ARENA KOMPETISI DIGITAL.')).toBeVisible()
      results.push('Landing: Competitions section visible')
    })

    await test.step('Click login link to navigate', async () => {
      await page.locator('a[href="/login"]').first().click()
      await page.waitForURL(/\/login/)
      results.push('Landing: Login link navigated to /login')
    })

    // ===== STEP 1: REGISTER =====
    await test.step('Register new user', async () => {
      await page.goto('/register')
      await page.waitForSelector('input[placeholder="Nama kamu"]')

      await page.getByPlaceholder('Nama kamu').fill(testUser.name)
      await page.getByPlaceholder('email@example.com').fill(testUser.email)
      await page.getByPlaceholder('08xxx').fill(testUser.phone)
      await page.getByPlaceholder('Universitas').fill(testUser.institution)
      await page.getByPlaceholder('Min. 8 karakter').fill(testUser.password)
      await page.getByPlaceholder('Ulangi password').fill(testUser.password)

      await page.click('button[type="submit"]')
      await page.waitForURL(/\/verifikasi-email/, { timeout: 10000 })

      const onOtpPage = page.url().includes('verifikasi-email')
      expect(onOtpPage).toBeTruthy()
      results.push('Register: Redirected to OTP verification')
    })

    // ===== STEP 2: VERIFY (via API helper) =====
    await test.step('Verify email via API', async () => {
      await verifyUserViaApi(testUser.email)
      results.push('Verify: Email verified successfully')
    })

    // ===== STEP 3: LOGIN =====
    await test.step('Login with verified credentials', async () => {
      await page.goto('/login')
      await page.fill('input[type="email"]', testUser.email)
      await page.fill('input[type="password"]', testUser.password)
      await page.click('button[type="submit"]')
      await page.waitForURL(/\/dashboard/, { timeout: 15000 })

      await expect(page.locator('text=Dashboard').first()).toBeVisible()
      results.push('Login: Redirected to dashboard')
    })

    // ===== STEP 4: VIEW DASHBOARD =====
    await test.step('View dashboard overview', async () => {
      const heading = page.getByText(/Dashboard|Overview|Selamat/i).first()
      await expect(heading).toBeVisible()
      results.push('Dashboard: Overview page loaded')
    })

    // ===== STEP 5: EDIT PROFILE =====
    await test.step('Edit profile name', async () => {
      await page.goto('/dashboard/profile')
      await page.waitForSelector('text=Data Diri')

      const nameInput = page.locator('input[type="text"]').first()
      await expect(nameInput).toHaveValue(testUser.name)

      await nameInput.fill('E2E Full Flow Updated')
      await page.getByRole('button', { name: /simpan perubahan/i }).click()
      await expect(page.getByText('Profil berhasil diperbarui')).toBeVisible({ timeout: 15000 })

      results.push('Profile: Name updated successfully')
    })

    // ===== STEP 6: BROWSE COMPETITIONS ON LANDING PAGE =====
    await test.step('Browse competitions on landing page', async () => {
      await page.goto('/kompetisi')
      await page.waitForSelector('nav button', { timeout: 15000 })

      const cards = page.locator('nav button')
      expect(await cards.count()).toBeGreaterThanOrEqual(1)
      results.push('Competitions: Landing page shows competition cards')
    })

    // ===== STEP 7: VISIT DASHBOARD COMPETITIONS =====
    await test.step('Visit dashboard competitions page', async () => {
      await page.goto('/dashboard/competitions')
      await page.waitForTimeout(3000)

      const lombaText = page.getByText(/Lomba|Kompetisi/i).first()
      await expect(lombaText).toBeVisible()
      results.push('Dashboard: Competition page loaded')
    })

    // ===== STEP 8: VISIT PROFILE AGAIN =====
    await test.step('Verify profile page renders', async () => {
      await page.goto('/dashboard/profile')
      await page.waitForSelector('text=Data Diri')

      await expect(page.getByText('Data Diri')).toBeVisible()
      results.push('Profile: Page loaded successfully after navigation')
    })

    // ===== Print Summary =====
    console.log('\n' + '='.repeat(50))
    console.log('  E2E FULL FLOW TEST RESULTS')
    console.log('='.repeat(50))
    results.forEach(r => console.log(`  ${r}`))
    console.log('='.repeat(50))
    console.log(`  All ${results.length} checks passed`)
    console.log('='.repeat(50))
  })
})
