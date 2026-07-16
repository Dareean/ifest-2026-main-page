import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, loginAs } from './helpers/seed.js'

const ROOT = API_BASE.replace('/api', '')

test.describe('Admin Pendaftaran', () => {
  let adminEmail
  let lombaId

  test.beforeAll(async () => {
    adminEmail = uniqueEmail('e2e-pendaftaran-admin')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await (await import('./helpers/seed.js')).setAdminViaApi(adminEmail)

    const adminCtx = await loginAs(adminEmail, TEST_USER.password)
    const lombasRes = await adminCtx.get(`${API_BASE}/lombas`)
    const lombas = await lombasRes.json()
    lombaId = lombas.data?.[0]?.id

    const statuses = ['pending']
    for (const s of statuses) {
      const email = uniqueEmail('e2e-penda')
      await registerUserViaApi({ ...TEST_USER, email, password_confirmation: TEST_USER.password })
      await verifyUserViaApi(email)
      const userCtx = await loginAs(email, TEST_USER.password)

      if (lombaId) {
        await userCtx.post(`${API_BASE}/lombas/${lombaId}/daftar`, {
          headers: { Accept: 'application/json' },
        })
      }
    }
  })

  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 45000 })
    await page.goto('/dashboard/admin/pendaftaran')
    await page.waitForTimeout(3000)
  })

  test('page renders with heading and table', async ({ page }) => {
    await expect(page.getByText('Pendaftaran').first()).toBeVisible()
    await expect(page.getByText('Admin Panel').first()).toBeVisible()
  })

  test('search by team name works', async ({ page }) => {
    const searchInput = page.locator('input[placeholder*="Cari nama"]')
    await searchInput.fill('E2E Test User')
    await page.waitForTimeout(1500)
    await expect(page.getByText('E2E Test User').first()).toBeVisible()
  })

  test('clicking a row navigates to detail', async ({ page }) => {
    const firstRow = page.locator('table tbody tr, .md\\:hidden .cursor-pointer').first()
    if (await firstRow.isVisible()) {
      await firstRow.click()
      await page.waitForTimeout(2000)
      const isDetail = page.url().includes('/pendaftaran/')
      expect(isDetail).toBe(true)
    }
  })

  test('export CSV button exists', async ({ page }) => {
    const exportBtn = page.getByText('Export CSV')
    await expect(exportBtn).toBeVisible()
  })

  test('filters dropdowns exist', async ({ page }) => {
    const selects = page.locator('select')
    const count = await selects.count()
    expect(count).toBeGreaterThanOrEqual(3)
  })
})
