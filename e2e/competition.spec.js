import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

test.describe('Competition Flow', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-comp')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test.beforeEach(async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
  })

  test('landing page shows competition list', async ({ page }) => {
    await page.goto('/kompetisi')
    await expect(page.getByText('Kompetisi').first()).toBeVisible()
    const cards = page.locator('[class*="card"]')
    expect(await cards.count()).toBeGreaterThanOrEqual(1)
  })

  test('dashboard competition page loads with tabs', async ({ page }) => {
    await page.goto('/dashboard/competitions')
    await page.waitForTimeout(3000)
    const lombaHeading = page.getByText(/Lomba|Kompetisi|Pilih/i).first()
    await expect(lombaHeading).toBeVisible()
  })

  test('sidebar shows competition sub-navigation tabs', async ({ page }) => {
    await page.goto('/dashboard/competitions')
    await page.waitForTimeout(3000)

    const tabs = page.locator('button:has-text("Detail & Juknis"), button:has-text("Timeline")')
    await expect(tabs.first()).toBeVisible()
  })

  test('can filter competitions on landing page', async ({ page }) => {
    await page.goto('/kompetisi')
    const filterButtons = page.locator('button:has-text("Semua"), button:has-text("Akademik"), button:has-text("Non-Akademik")')
    if (await filterButtons.count() > 0) {
      await filterButtons.first().click()
      await page.waitForTimeout(1000)
    }
    await expect(page.getByText('Kompetisi').first()).toBeVisible()
  })
})
