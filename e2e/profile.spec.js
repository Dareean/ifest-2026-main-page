import { test, expect } from '@playwright/test'
import { TEST_USER, PROFILE_UPDATE, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

test.describe('Profile Management', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-profile')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test.beforeEach(async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/dashboard/profile')
    await page.waitForSelector('input[type="text"]')
  })

  test('display user data pre-filled in profile form', async ({ page }) => {
    const nameInput = page.locator('input[type="text"]').first()
    await expect(nameInput).toHaveValue(TEST_USER.name)
  })

  test('update name successfully and show success message', async ({ page }) => {
    await page.locator('input[type="text"]').first().fill(PROFILE_UPDATE.name)
    await page.click('button[type="submit"]')
    await expect(page.getByText('Profil berhasil diperbarui')).toBeVisible({ timeout: 15000 })
  })

  test('update all profile fields and persist after reload', async ({ page }) => {
    const textInputs = page.locator('input[type="text"]')
    await textInputs.nth(0).fill(PROFILE_UPDATE.name)
    await textInputs.nth(1).fill(PROFILE_UPDATE.phone)
    await textInputs.nth(2).fill(PROFILE_UPDATE.institution)
    await page.click('button[type="submit"]')
    await expect(page.getByText('Profil berhasil diperbarui')).toBeVisible({ timeout: 15000 })

    await page.reload()
    await page.waitForSelector('input[type="text"]')
    await expect(page.locator('input[type="text"]').nth(0)).toHaveValue(PROFILE_UPDATE.name)
  })
})
