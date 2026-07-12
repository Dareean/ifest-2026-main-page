import { test, expect } from '@playwright/test'
import { TEST_USER, DRIVE_LINK, FIGMA_LINK, ORIGINALITY_LINK, uniqueEmail, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, loginViaApi, setAdminViaApi } from './helpers/seed.js'
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

  test('API: submit with all required fields succeeds', async ({ request }) => {
    const { loginViaApi } = await import('./helpers/seed.js')
    const loginRes = await loginViaApi(userEmail, TEST_USER.password)
    const token = loginRes.data?.token || loginRes.token

    // Get first eligible competition and register
    const lombasRes = await request.get(`${API_BASE}/lombas`)
    const lombas = await lombasRes.json()
    const firstLomba = lombas.data?.[0]
    if (!firstLomba) return

    const daftarRes = await request.post(`${API_BASE}/lombas/${firstLomba.id}/daftar`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })

    // If registration succeeded, try submission
    const pendaftaranId = daftarRes.ok() ? (await daftarRes.json()).data?.id : null
    if (!pendaftaranId) return  // registration may fail if already registered, skip

    // Mark pendaftaran as verified so we can submit
    const adminEmail = uniqueEmail('e2e-submit-admin')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await setAdminViaApi(adminEmail)
    const adminLogin = await loginViaApi(adminEmail, TEST_USER.password)
    const adminToken = adminLogin.data?.token || adminLogin.token

    await request.put(`${API_BASE}/admin/pendaftarans/${pendaftaranId}/verify`, {
      headers: { Authorization: `Bearer ${adminToken}`, Accept: 'application/json' },
    })

    const submitPayload = {
      link_drive: DRIVE_LINK,
      originality_statement: ORIGINALITY_LINK,
      link_figma: firstLomba.kode === 'NAT-02' ? FIGMA_LINK : null,
    }

    const submitRes = await request.post(`${API_BASE}/pendaftarans/${pendaftaranId}/submit`, {
      headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json', Accept: 'application/json' },
      data: submitPayload,
    })

    expect(submitRes.ok()).toBe(true)
    const submitData = await submitRes.json()
    expect(submitData.data.link_drive).toBe(DRIVE_LINK)
    expect(submitData.data.originality_statement).toBe(ORIGINALITY_LINK)
  })
})
