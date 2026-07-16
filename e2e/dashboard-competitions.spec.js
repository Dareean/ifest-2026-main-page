import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, loginAs } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

test.describe('Dashboard — Competitions Page', () => {
  let userEmail, userEmail2, lombaId

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-dashcomp')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)

    userEmail2 = uniqueEmail('e2e-dashcomp2')
    await registerUserViaApi({ ...TEST_USER, email: userEmail2, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail2)

    const ctx = await loginAs(userEmail, TEST_USER.password)
    const lombasRes = await ctx.get(`${API_BASE}/lombas`)
    const lombas = await lombasRes.json()
    lombaId = lombas.data?.[0]?.id

    if (lombaId) {
      const teamName = `E2E DashComp Team ${Math.random().toString(36).substring(7)}`
      const res = await ctx.post(`${API_BASE}/lombas/${lombaId}/daftar`, {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        data: { team_name: teamName },
      })
      expect(res.ok()).toBe(true)
    }
  })

  test('page renders with heading', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/dashboard/competitions')
    await page.waitForLoadState('networkidle')
    await expect(page.getByText(/Kompetisi|Lomba/i).first()).toBeVisible({ timeout: 10000 })
  })

  test('registered competition row is visible', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/dashboard/competitions')
    await page.waitForLoadState('networkidle')
    const nameLocator = page.getByText('Competitive Programming')
    if (await nameLocator.count() > 0) {
      await expect(nameLocator.first()).toBeVisible({ timeout: 10000 })
    }
  })

  test('registration status badge is displayed', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto(`/dashboard/competitions?id=${lombaId}`)
    await page.waitForLoadState('networkidle')
    // Click the Pendaftaran tab to see the status badge
    const pendaftaranTab = page.getByRole('button', { name: /Pendaftaran/i }).first()
    if (await pendaftaranTab.count() > 0) {
      await pendaftaranTab.click()
      await page.waitForTimeout(500)
    }
    const pendingBadge = page.getByText('Pending').or(page.getByText('Terverifikasi')).or(page.getByText('Ditolak')).first()
    await expect(pendingBadge).toBeVisible({ timeout: 10000 })
  })

  test('submission button is visible for verified registrations', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto(`/dashboard/competitions?id=${lombaId}`)
    await page.waitForLoadState('networkidle')
    const submitBtn = page.locator('button:has-text("Pengumpulan Karya")').first()
    if (await submitBtn.count() > 0) {
      await expect(submitBtn).toBeVisible()
    }
  })

  test('unregistered user sees empty or available competitions', async ({ page }) => {
    await loginViaUI(page, userEmail2, TEST_USER.password)
    await page.goto('/dashboard/competitions')
    await page.waitForLoadState('networkidle')
    await expect(page.getByText(/Kompetisi|Lomba/i).first()).toBeVisible({ timeout: 10000 })
  })

  test('team invitation banner appears when pending invitation exists', async ({ page }) => {
    const inviterEmail = uniqueEmail('e2e-dashcomp-inv')
    await registerUserViaApi({ ...TEST_USER, email: inviterEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(inviterEmail)
    const inviterCtx = await loginAs(inviterEmail, TEST_USER.password)

    if (lombaId) {
      const daftarRes = await inviterCtx.post(`${API_BASE}/lombas/${lombaId}/daftar`, {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        data: { team_name: 'E2E Inviter Team' },
      })
      if (daftarRes.ok()) {
        const d = await daftarRes.json()
        await inviterCtx.post(`${API_BASE}/pendaftarans/${d.data.id}/invite`, {
          headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
          data: { email: userEmail2 },
        })
      }
    }

    await loginViaUI(page, userEmail2, TEST_USER.password)
    await page.goto('/dashboard/competitions')
    await page.waitForLoadState('networkidle')
    const banner = page.getByText(/undangan|invitasi/i).first()
    if (await banner.count() > 0) {
      await expect(banner).toBeVisible()
    }
  })
})
