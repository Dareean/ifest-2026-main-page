import { test, expect } from '@playwright/test'
import { TEST_USER, DRIVE_LINK, FIGMA_LINK, ORIGINALITY_LINK, uniqueEmail, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, loginAs, forceVerifyPendaftaranViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

const ROOT = API_BASE.replace('/api', '')

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

  test('submission page shows submit instructions with new fields', async ({ page }) => {
    await page.goto('/dashboard/competitions')
    await page.waitForTimeout(3000)

    const submitBtn = page.locator('button:has-text("Pengumpulan Karya")')
    if (await submitBtn.count() > 0) {
      await submitBtn.first().click()
      await page.waitForTimeout(1500)
      const hasInstructions = await page.getByText(/Google Drive|Petunjuk|drive\.google/i).count() > 0
      const hasDriveForm = await page.locator('input[placeholder*="drive.google"]').count() > 0
      const hasOriginalityField = await page.getByText('Surat Pernyataan Orisinalitas').count() > 0
      const hasSuccess = await page.getByText('Karya Berhasil Dikumpulkan').count() > 0
      expect(hasInstructions || hasDriveForm || hasSuccess).toBeTruthy()
      if (hasDriveForm) {
        expect(hasOriginalityField).toBeTruthy()
      }
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

  test('API: submit with all required fields succeeds', async () => {
    const userCtx = await loginAs(userEmail, TEST_USER.password)

    const lombasRes = await userCtx.get(`${API_BASE}/lombas`)
    expect(lombasRes.ok()).toBe(true)
    const lombas = await lombasRes.json()
    const firstLomba = lombas.data?.find(l => l.team_requirements.toLowerCase().includes('individu'))
    expect(firstLomba).toBeDefined()

    const daftarRes = await userCtx.post(`${API_BASE}/lombas/${firstLomba.id}/daftar`, {
      headers: { Accept: 'application/json' },
    })
    expect(daftarRes.ok()).toBe(true)
    const pendaftaranId = (await daftarRes.json()).data?.id
    expect(pendaftaranId).toBeDefined()

    await forceVerifyPendaftaranViaApi(pendaftaranId)

    const submitPayload = {
      link_drive: DRIVE_LINK,
      originality_statement: ORIGINALITY_LINK,
      link_figma: firstLomba.kode === 'NAT-02' ? FIGMA_LINK : null,
    }

    const submitRes = await userCtx.post(`${API_BASE}/pendaftarans/${pendaftaranId}/submit`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: submitPayload,
    })

    expect(submitRes.ok()).toBe(true)
    const submitData = await submitRes.json()
    expect(submitData.data.link_drive).toBe(DRIVE_LINK)
    expect(submitData.data.originality_statement).toBe(ORIGINALITY_LINK)
  })
})

test.describe('Submission Flow — Validation Errors', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-val')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test('API: submit without originality_statement returns 422', async () => {
    const userCtx = await loginAs(userEmail, TEST_USER.password)

    const lombasRes = await userCtx.get(`${API_BASE}/lombas`)
    expect(lombasRes.ok()).toBe(true)
    const lombas = await lombasRes.json()
    const first = lombas.data?.find(l => l.team_requirements.toLowerCase().includes('individu'))
    expect(first).toBeDefined()

    const daftarRes = await userCtx.post(`${API_BASE}/lombas/${first.id}/daftar`, {
      headers: { Accept: 'application/json' },
    })
    expect(daftarRes.ok()).toBe(true)
    const pendaftaranId = (await daftarRes.json()).data?.id
    expect(pendaftaranId).toBeDefined()

    await forceVerifyPendaftaranViaApi(pendaftaranId)

    const submitRes = await userCtx.post(`${API_BASE}/pendaftarans/${pendaftaranId}/submit`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: { link_drive: DRIVE_LINK },
    })
    expect(submitRes.status()).toBe(422)
  })

  test('API: submit UI/UX without link_figma succeeds (optional field)', async () => {
    const userCtx = await loginAs(userEmail, TEST_USER.password)

    const teamName = `E2E UIUX Team ${Math.random().toString(36).substring(7)}`
    const daftarRes = await userCtx.post(`${API_BASE}/lombas/2/daftar`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: { team_name: teamName },
    })
    expect(daftarRes.ok()).toBe(true)
    const pendaftaranId = (await daftarRes.json()).data?.id
    expect(pendaftaranId).toBeDefined()

    await forceVerifyPendaftaranViaApi(pendaftaranId)

    const submitRes = await userCtx.post(`${API_BASE}/pendaftarans/${pendaftaranId}/submit`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: { link_drive: DRIVE_LINK, originality_statement: ORIGINALITY_LINK },
    })
    expect(submitRes.status()).toBe(201)
  })
})

test.describe('Submission Flow — UI Submit', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-ui-submit')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test('UI: submit karya via form shows success and displays new fields', async ({ page }) => {
    const userCtx = await loginAs(userEmail, TEST_USER.password)

    const lombasRes = await userCtx.get(`${API_BASE}/lombas`)
    expect(lombasRes.ok()).toBe(true)
    const lombas = await lombasRes.json()
    const first = lombas.data?.find(l => l.team_requirements.toLowerCase().includes('individu'))
    expect(first).toBeDefined()

    const daftarRes = await userCtx.post(`${API_BASE}/lombas/${first.id}/daftar`, {
      headers: { Accept: 'application/json' },
    })
    expect(daftarRes.ok()).toBe(true)
    const pendaftaranId = (await daftarRes.json()).data?.id
    expect(pendaftaranId).toBeDefined()

    await forceVerifyPendaftaranViaApi(pendaftaranId)

    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto(`/dashboard/competitions?id=${first.id}`)
    await page.waitForLoadState('networkidle')

    const submitBtn = page.locator('button:has-text("Pengumpulan Karya")')
    await expect(submitBtn.first()).toBeVisible({ timeout: 15000 })
    await submitBtn.first().click()
    await page.waitForTimeout(1000)

    const inputs = page.locator('input')
    const count = await inputs.count()

    await inputs.nth(0).fill(DRIVE_LINK)
    await inputs.nth(count - 1).fill(ORIGINALITY_LINK)

    await page.click('button:has-text("Kirim Karya")')

    await expect(page.getByText('Karya Berhasil Dikumpulkan')).toBeVisible({ timeout: 15000 })

    await expect(page.getByText(DRIVE_LINK)).toBeVisible()
    await expect(page.getByText(ORIGINALITY_LINK)).toBeVisible()
  })
})
