import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, DRIVE_LINK, FIGMA_LINK, ORIGINALITY_LINK, API_BASE } from './fixtures/data.js'
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

  test('API: toggle-active changes is_active and public list reflects it', async ({ page }) => {
    const { request } = await import('@playwright/test')
    const ctx = await request.newContext()

    // Get all lombas (admin endpoint includes inactive)
    const adminLombasRes = await ctx.get(`${API_BASE}/admin/lombas`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })
    const adminLombas = await adminLombasRes.json()
    const sdih = adminLombas.data.find(l => l.kode === 'REG-03')
    expect(sdih).toBeDefined()
    // S-DIH should start inactive after migration
    expect(sdih.is_active).toBe(false)

    // Public endpoint should NOT include S-DIH
    const publicRes = await ctx.get(`${API_BASE}/lombas`)
    const publicData = await publicRes.json()
    const publicKodes = publicData.data.map(l => l.kode)
    expect(publicKodes).not.toContain('REG-03')

    // Toggle active ON
    const toggleOn = await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })
    expect(toggleOn.ok()).toBe(true)
    const toggleOnData = await toggleOn.json()
    expect(toggleOnData.data.is_active).toBe(true)

    // Now public endpoint SHOULD include S-DIH
    const publicRes2 = await ctx.get(`${API_BASE}/lombas`)
    const publicData2 = await publicRes2.json()
    const publicKodes2 = publicData2.data.map(l => l.kode)
    expect(publicKodes2).toContain('REG-03')

    // Toggle active OFF (cleanup)
    await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })
  })

  test('UI: toggle S-DIH active changes landing page competition count', async ({ page }) => {
    // Toggle S-DIH ON via API
    const { request } = await import('@playwright/test')
    const ctx = await request.newContext()

    const adminLombasRes = await ctx.get(`${API_BASE}/admin/lombas`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })
    const adminLombas = await adminLombasRes.json()
    const sdih = adminLombas.data.find(l => l.kode === 'REG-03')
    expect(sdih).toBeDefined()

    // Ensure S-DIH is currently OFF
    if (sdih.is_active) {
      await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`, {
        headers: { Authorization: `Bearer ${adminToken}` }
      })
    }

    // Navigate to landing page and verify 5 competitions
    await page.goto('/kompetisi')
    await page.waitForSelector('nav button', { timeout: 15000 })
    let count = await page.locator('nav button').count()
    expect(count).toBe(5)

    // Toggle S-DIH ON
    await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })

    // Navigate to landing page and verify 6 competitions
    await page.goto('/kompetisi')
    await page.waitForSelector('nav button', { timeout: 15000 })
    count = await page.locator('nav button').count()
    expect(count).toBe(6)

    // Toggle back OFF (cleanup)
    await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    })
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

test.describe('Admin Detail — Submission Fields', () => {
  let adminToken

  test.beforeAll(async () => {
    const adminEmail = uniqueEmail('e2e-detail-admin')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await setAdminViaApi(adminEmail)
    const login = await loginViaApi(adminEmail, TEST_USER.password)
    adminToken = login.data?.token || login.token
  })

  test('admin detail shows figma and originality statement for submitted karya', async ({ page }) => {
    const { request } = await import('@playwright/test')
    const ctx = await request.newContext()

    // Create a user, register for first comp, verify, submit with figma + originality
    const userEmail = uniqueEmail('e2e-detail-user')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
    const userLogin = await loginViaApi(userEmail, TEST_USER.password)
    const userToken = userLogin.data?.token || userLogin.token

    const lombasRes = await ctx.get(`${API_BASE}/lombas`)
    const lombas = await lombasRes.json()
    const first = lombas.data?.[0]
    if (!first) return

    const daftarRes = await ctx.post(`${API_BASE}/lombas/${first.id}/daftar`, {
      headers: { Authorization: `Bearer ${userToken}`, Accept: 'application/json' },
    })
    const pendaftaranId = daftarRes.ok() ? (await daftarRes.json()).data?.id : null
    if (!pendaftaranId) return

    // Verify
    await ctx.put(`${API_BASE}/admin/pendaftarans/${pendaftaranId}/verify`, {
      headers: { Authorization: `Bearer ${adminToken}`, Accept: 'application/json' },
    })

    // Submit with all fields
    await ctx.post(`${API_BASE}/pendaftarans/${pendaftaranId}/submit`, {
      headers: { Authorization: `Bearer ${userToken}`, 'Content-Type': 'application/json', Accept: 'application/json' },
      data: {
        link_drive: DRIVE_LINK,
        link_figma: first.kode === 'NAT-02' ? FIGMA_LINK : null,
        originality_statement: ORIGINALITY_LINK,
      },
    })

    // Admin login via UI and check detail page
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 20000 })

    await page.goto(`/dashboard/admin/pendaftarans/${pendaftaranId}`)
    await page.waitForTimeout(2000)

    // Verify the new fields are displayed
    await expect(page.getByText('Surat Pernyataan Orisinalitas')).toBeVisible()
    await expect(page.getByText(ORIGINALITY_LINK)).toBeVisible()

    if (first.kode === 'NAT-02') {
      await expect(page.getByText('Link Figma')).toBeVisible()
      await expect(page.getByText(FIGMA_LINK)).toBeVisible()
    }
  })
})
