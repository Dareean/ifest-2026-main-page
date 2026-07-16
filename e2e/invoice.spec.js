import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, loginAs } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

const ROOT = API_BASE.replace('/api', '')

test.describe('Invoice Page', () => {
  let userEmail, pendaftaranId

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-invoice')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)

    const adminEmail = uniqueEmail('e2e-invoice-admin')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await (await import('./helpers/seed.js')).setAdminViaApi(adminEmail)

    const userCtx = await loginAs(userEmail, TEST_USER.password)
    const adminCtx = await loginAs(adminEmail, TEST_USER.password)

    const lombasRes = await userCtx.get(`${API_BASE}/lombas`)
    const lombas = await lombasRes.json()
    const first = lombas.data?.find(l => l.team_requirements.toLowerCase().includes('individu'))
    if (!first) throw new Error('No individual competition found')

    const teamName = `E2E Invoice Team ${Math.random().toString(36).substring(7)}`
    const daftarRes = await userCtx.post(`${API_BASE}/lombas/${first.id}/daftar`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: { team_name: teamName },
    })
    const daftarData = await daftarRes.json()
    pendaftaranId = daftarData.data?.id
    if (!pendaftaranId) throw new Error(`Registration failed: ${JSON.stringify(daftarData)}`)

    const payRes = await userCtx.post(`${API_BASE}/pendaftarans/${pendaftaranId}/payment/upload`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: { payment_proof: 'https://drive.google.com/file/d/123/view' }
    })
    if (!payRes.ok()) throw new Error(`Payment upload failed: ${await payRes.text()}`)

    const verifyPayRes = await adminCtx.put(`${API_BASE}/admin/pendaftarans/${pendaftaranId}/verify-payment`, {
      headers: { Accept: 'application/json' },
    })
    if (!verifyPayRes.ok()) throw new Error(`Verify payment failed: ${await verifyPayRes.text()}`)

    const verifyRegRes = await adminCtx.put(`${API_BASE}/admin/pendaftarans/${pendaftaranId}/verify`, {
      headers: { Accept: 'application/json' },
    })
    if (!verifyRegRes.ok()) throw new Error(`Verify registration failed: ${await verifyRegRes.text()}`)
  })

  test('invoice page renders with team details', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto(`/invoice/${pendaftaranId}`)
    await page.waitForLoadState('networkidle')
    await expect(page.getByText(TEST_USER.name).first()).toBeVisible({ timeout: 10000 })
  })

  test('payment status badge is visible', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto(`/invoice/${pendaftaranId}`)
    await page.waitForLoadState('networkidle')
    const statusBadge = page.locator('text=verified').or(page.locator('text=lunas')).or(page.locator('text=pending')).or(page.locator('text=ditolak')).or(page.locator('text=rejected')).first()
    await expect(statusBadge).toBeVisible({ timeout: 10000 })
  })

  test('team member name appears in member list', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto(`/invoice/${pendaftaranId}`)
    await page.waitForLoadState('networkidle')
    await expect(page.getByText(TEST_USER.name).first()).toBeVisible({ timeout: 10000 })
  })

  test('back button navigates to dashboard competitions', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/dashboard/competitions')
    await page.goto(`/invoice/${pendaftaranId}`)
    await page.waitForLoadState('networkidle')
    const backBtn = page.getByRole('button', { name: /kembali/i }).first()
    await backBtn.click()
    await page.waitForURL(/\/dashboard\/competitions/)
  })

  test('print button is present', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto(`/invoice/${pendaftaranId}`)
    await page.waitForLoadState('networkidle')
    const printBtn = page.getByRole('button', { name: /cetak|print/i })
    await expect(printBtn).toBeVisible({ timeout: 10000 })
  })

  test('non-existent invoice redirects to dashboard', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/invoice/999999')
    await page.waitForURL(/\/dashboard\//)
  })
})
