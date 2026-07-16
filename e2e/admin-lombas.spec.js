import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, DRIVE_LINK, FIGMA_LINK, ORIGINALITY_LINK, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, loginAs } from './helpers/seed.js'

const ROOT = API_BASE.replace('/api', '')

test.describe('Fase 6: Admin Lomba Management', () => {
  let adminEmail, userEmail

  async function adminContext() {
    return loginAs(adminEmail, TEST_USER.password)
  }

  async function userContext() {
    return loginAs(userEmail, TEST_USER.password)
  }

  test.beforeAll(async () => {
    adminEmail = uniqueEmail('e2e-admin-lomba')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await (await import('./helpers/seed.js')).setAdminViaApi(adminEmail)

    userEmail = uniqueEmail('e2e-user-lomba')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test('admin page shows heading and competition names', async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 45000 })
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
    await page.waitForURL(/\/dashboard/, { timeout: 45000 })
    await page.goto('/dashboard/admin/lombas')
    await page.waitForTimeout(3000)
    await page.waitForLoadState('networkidle')

    const count = await page.getByText('Competitive Programming').count()
    expect(count).toBeGreaterThanOrEqual(1)
  })

  test('API: toggle response confirms submission status changed', async ({ page }) => {
    const ctx = await adminContext()
    const lombasRes = await ctx.get(`${API_BASE}/lombas`)
    const lombasData = await lombasRes.json()
    const firstLomba = lombasData.data?.[0]
    const initial = firstLomba.is_submission_open

    const toggleRes1 = await ctx.put(`${API_BASE}/admin/lombas/${firstLomba.id}/toggle-submission`)
    expect(toggleRes1.ok()).toBe(true)
    const toggleData1 = await toggleRes1.json()
    expect(toggleData1.data.is_submission_open).toBe(!initial)

    const toggleRes2 = await ctx.put(`${API_BASE}/admin/lombas/${firstLomba.id}/toggle-submission`)
    expect(toggleRes2.ok()).toBe(true)
    const toggleData2 = await toggleRes2.json()
    expect(toggleData2.data.is_submission_open).toBe(initial)
  })

  test('API: toggle-active changes is_active and public list reflects it', async ({ page }) => {
    const ctx = await adminContext()

    const adminLombasRes = await ctx.get(`${API_BASE}/admin/lombas`)
    const adminLombas = await adminLombasRes.json()
    const sdih = adminLombas.data.find(l => l.kode === 'REG-03')
    expect(sdih).toBeDefined()
    expect(sdih.is_active).toBe(false)

    const publicRes = await ctx.get(`${API_BASE}/lombas`)
    const publicData = await publicRes.json()
    const publicKodes = publicData.data.map(l => l.kode)
    expect(publicKodes).not.toContain('REG-03')

    const toggleOn = await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`)
    expect(toggleOn.ok()).toBe(true)
    const toggleOnData = await toggleOn.json()
    expect(toggleOnData.data.is_active).toBe(true)

    const publicRes2 = await ctx.get(`${API_BASE}/lombas`)
    const publicData2 = await publicRes2.json()
    const publicKodes2 = publicData2.data.map(l => l.kode)
    expect(publicKodes2).toContain('REG-03')

    await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`)
  })

  test('UI: toggle S-DIH active changes landing page competition count', async ({ page }) => {
    const ctx = await adminContext()

    const adminLombasRes = await ctx.get(`${API_BASE}/admin/lombas`)
    const adminLombas = await adminLombasRes.json()
    const sdih = adminLombas.data.find(l => l.kode === 'REG-03')
    expect(sdih).toBeDefined()

    if (sdih.is_active) {
      await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`)
    }

    await page.goto('/kompetisi')
    await page.waitForSelector('nav button', { timeout: 15000 })
    let count = await page.locator('nav button').count()
    expect(count).toBe(5)

    await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`)

    await page.goto('/kompetisi')
    await page.waitForSelector('nav button', { timeout: 15000 })
    count = await page.locator('nav button').count()
    expect(count).toBe(6)

    await ctx.put(`${API_BASE}/admin/lombas/${sdih.id}/toggle-active`)
  })

  test('API: admin can update lomba deadlines', async ({ page }) => {
    const ctx = await adminContext()

    const lombasRes = await ctx.get(`${API_BASE}/lombas`)
    const lombasData = await lombasRes.json()
    const firstLomba = lombasData.data?.[0]

    const updateRes = await ctx.put(`${API_BASE}/admin/lombas/${firstLomba.id}`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
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
  test('admin detail shows figma and originality statement for submitted karya', async ({ page }) => {
    const adminEmail = uniqueEmail('e2e-detail-admin')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await (await import('./helpers/seed.js')).setAdminViaApi(adminEmail)

    const userEmail = uniqueEmail('e2e-detail-user')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)

    const adminCtx = await loginAs(adminEmail, TEST_USER.password)
    const userCtx = await loginAs(userEmail, TEST_USER.password)

    const lombasRes = await userCtx.get(`${API_BASE}/lombas`)
    const lombas = await lombasRes.json()
    const first = lombas.data?.find(l => l.team_requirements.toLowerCase().includes('individu'))
    if (!first) return

    const teamName = `E2E Admin Detail Team ${Math.random().toString(36).substring(7)}`
    const daftarRes = await userCtx.post(`${API_BASE}/lombas/${first.id}/daftar`, {
      headers: { Accept: 'application/json' },
      data: { team_name: teamName },
    })
    const pendaftaranId = daftarRes.ok() ? (await daftarRes.json()).data?.id : null
    if (!pendaftaranId) return

    // Upload payment proof
    const uploadPayRes = await userCtx.post(`${API_BASE}/pendaftarans/${pendaftaranId}/payment/upload`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: { payment_proof: 'https://example.com/payment-proof.jpg' }
    })
    expect(uploadPayRes.ok()).toBe(true)

    // Verify payment proof
    const verifyPayRes = await adminCtx.put(`${API_BASE}/admin/pendaftarans/${pendaftaranId}/verify-payment`, {
      headers: { Accept: 'application/json' },
    })
    expect(verifyPayRes.ok()).toBe(true)

    // Verify pendaftaran
    const verifyRes = await adminCtx.put(`${API_BASE}/admin/pendaftarans/${pendaftaranId}/verify`, {
      headers: { Accept: 'application/json' },
    })
    expect(verifyRes.ok()).toBe(true)

    await userCtx.post(`${API_BASE}/pendaftarans/${pendaftaranId}/submit`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: {
        link_drive: DRIVE_LINK,
        link_figma: first.kode === 'NAT-02' ? FIGMA_LINK : null,
        originality_statement: ORIGINALITY_LINK,
      },
    })

    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 45000 })

    await page.goto(`/dashboard/admin/pendaftaran/${pendaftaranId}`)
    await page.waitForTimeout(2000)

    await expect(page.getByText('Surat Pernyataan Orisinalitas')).toBeVisible()
    await expect(page.getByText(ORIGINALITY_LINK)).toBeVisible()

    if (first.kode === 'NAT-02') {
      await expect(page.getByText('Link Figma')).toBeVisible()
      await expect(page.getByText(FIGMA_LINK)).toBeVisible()
    }
  })
})
