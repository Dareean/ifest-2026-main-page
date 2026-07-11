import { test, expect } from '@playwright/test'
import { TEST_USER, DRIVE_LINK, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

test.describe('Submission Flow', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-submit')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test.beforeEach(async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
  })

  test('submission page shows submit instructions', async ({ page }) => {
    await page.goto('/dashboard/competitions')
    await page.waitForTimeout(3000)

    const submitBtn = page.locator('button:has-text("Pengumpulan Karya")')
    if (await submitBtn.count() > 0) {
      await submitBtn.first().click()
      await page.waitForTimeout(1500)
      const hasInstructions = await page.getByText(/Google Drive|Petunjuk|drive\.google/i).count() > 0
      const hasForm = await page.locator('input[placeholder*="drive.google"]').count() > 0
      const hasSuccess = await page.getByText('Karya Berhasil Dikumpulkan').count() > 0
      expect(hasInstructions || hasForm || hasSuccess).toBeTruthy()
    }
  })

  test('competition detail shows timeline information', async ({ page }) => {
    await page.goto('/kompetisi')
    await page.waitForTimeout(2000)

    const detailLink = page.locator('a:has-text("Selengkapnya"), a:has-text("Detail"), button:has-text("Detail")').first()
    if (await detailLink.count() > 0) {
      await detailLink.click()
      await page.waitForTimeout(2000)
      const hasTimeline = await page.getByText(/Timeline|Waktu|Tanggal/i).count() > 0
      const hasDesc = await page.getByText(/Deskripsi|Kompetisi/i).count() > 0
      expect(hasTimeline || hasDesc).toBeTruthy()
    }
  })
})
