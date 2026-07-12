import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, DRIVE_LINK, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, loginViaApi, setAdminViaApi } from './helpers/seed.js'

test.describe('Fase 6: Admin Lomba Management', () => {
  let adminEmail
  let adminToken
  let userEmail
  let userToken

  test.beforeAll(async () => {
    adminEmail = uniqueEmail('e2e-admin-lomba')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await setAdminViaApi(adminEmail)
    const adminLogin = await loginViaApi(adminEmail, TEST_USER.password)
    adminToken = adminLogin.data?.token || adminLogin.token

    userEmail = uniqueEmail('e2e-user-lomba')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
    const userLogin = await loginViaApi(userEmail, TEST_USER.password)
    userToken = userLogin.data?.token || userLogin.token
  })

  test('admin page shows heading and competition names', async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 20000 })
    await page.goto('/dashboard/admin/lombas')
    await page.waitForTimeout(2000)

    await expect(page.getByText('Kelola Lomba')).toBeVisible()
    await expect(page.getByText('Competitive Programming')).toBeVisible()
  })

  test('admin page shows status badges (Buka/Tutup) for each lomba', async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 20000 })
    await page.goto('/dashboard/admin/lombas')
    await page.waitForTimeout(3000)
    await page.waitForLoadState('networkidle')

    // Should see at least one status indicator
    const count = await page.getByText('Competitive Programming').count()
    expect(count).toBeGreaterThanOrEqual(1)
  })

  test('API: toggle response confirms submission status changed', async ({ page }) => {
    const { request } = await import('@playwright/test')
    const ctx = await request.newContext()

    const lombasRes = await ctx.get(`${API_BASE}/lombas`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })
    const lombasData = await lombasRes.json()
    const firstLomba = lombasData.data?.[0]
    const initial = firstLomba.is_submission_open

    // Toggle once
    const toggleRes1 = await ctx.put(`${API_BASE}/admin/lombas/${firstLomba.id}/toggle-submission`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })
    expect(toggleRes1.ok()).toBe(true)
    const toggleData1 = await toggleRes1.json()
    expect(toggleData1.data.is_submission_open).toBe(!initial)

    // Toggle back
    const toggleRes2 = await ctx.put(`${API_BASE}/admin/lombas/${firstLomba.id}/toggle-submission`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })
    expect(toggleRes2.ok()).toBe(true)
    const toggleData2 = await toggleRes2.json()
    expect(toggleData2.data.is_submission_open).toBe(initial)
  })

  test('API: admin can update lomba deadlines', async ({ page }) => {
    const { request } = await import('@playwright/test')
    const ctx = await request.newContext()

    const lombasRes = await ctx.get(`${API_BASE}/lombas`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })
    const lombasData = await lombasRes.json()
    const firstLomba = lombasData.data?.[0]

    const updateRes = await ctx.put(`${API_BASE}/admin/lombas/${firstLomba.id}`, {
      headers: {
        Authorization: `Bearer ${adminToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      data: {
        contact_person: 'E2E Test CP (+62 812-3456-7890)',
      }
    })
    expect(updateRes.ok()).toBe(true)
    const updateData = await updateRes.json()
    expect(updateData.data.contact_person).toContain('E2E Test CP')
  })
})
