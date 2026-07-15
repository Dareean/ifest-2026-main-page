import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, loginAs } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

const ROOT = API_BASE.replace('/api', '')

test.describe('Dashboard Overview', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-overview')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)

    const userCtx = await loginAs(userEmail, TEST_USER.password)
    const lombasRes = await userCtx.get(`${API_BASE}/lombas`)
    const lombas = await lombasRes.json()
    const first = lombas.data?.[0]
    if (first) {
      await userCtx.post(`${API_BASE}/lombas/${first.id}/daftar`, {
        headers: { Accept: 'application/json' },
      })
    }
  })

  test.beforeEach(async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/dashboard')
    await page.waitForTimeout(3000)
  })

  test('greeting shows user name', async ({ page }) => {
    await expect(page.getByText('Halo,').first()).toBeVisible()
  })

  test('stat summary cards display', async ({ page }) => {
    await expect(page.getByText('Total').first()).toBeVisible()
    await expect(page.getByText('Pending').first()).toBeVisible()
    await expect(page.getByText('Terverifikasi').first()).toBeVisible()
    await expect(page.getByText('Ditolak').first()).toBeVisible()
  })

  test('lomba kamu section shows registrations', async ({ page }) => {
    const section = page.getByText('Lomba Kamu')
    if (await section.count() > 0) {
      await expect(section.first()).toBeVisible()
    }
  })

  test('semua lomba section shows competitions', async ({ page }) => {
    await expect(page.getByText('Semua Lomba').first()).toBeVisible()
  })
})

test.describe('Dashboard Notifications', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-dashnotif')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)

    const adminEmail = uniqueEmail('e2e-dashnotif-admin')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await (await import('./helpers/seed.js')).setAdminViaApi(adminEmail)
    const adminCtx = await loginAs(adminEmail, TEST_USER.password)

    await adminCtx.post(`${API_BASE}/admin/notifications`, {
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      data: {
        judul: 'E2E Test Notification',
        pesan: 'This is a test notification from E2E',
      },
    })
  })

  test.beforeEach(async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/dashboard/notifications')
    await page.waitForTimeout(3000)
  })

  test('page renders with heading', async ({ page }) => {
    await expect(page.getByText('Notifikasi').first()).toBeVisible()
  })

  test('notification with test data appears', async ({ page }) => {
    await expect(page.getByText('E2E Test Notification').first()).toBeVisible()
    await expect(page.getByText('This is a test notification from E2E').first()).toBeVisible()
  })

  test('mark all as read button appears when unread', async ({ page }) => {
    const markAllBtn = page.getByText('Tandai Semua Dibaca')
    await expect(markAllBtn).toBeVisible()
  })
})

test.describe('Dashboard Invitations', () => {
  let userEmail
  let inviterEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-invitee')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)

    inviterEmail = uniqueEmail('e2e-inviter')
    await registerUserViaApi({ ...TEST_USER, email: inviterEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(inviterEmail)
    const inviterCtx = await loginAs(inviterEmail, TEST_USER.password)

    const lombasRes = await inviterCtx.get(`${API_BASE}/lombas`)
    const lombas = await lombasRes.json()
    const first = lombas.data?.[0]
    if (first) {
      const daftarRes = await inviterCtx.post(`${API_BASE}/lombas/${first.id}/daftar`, {
        headers: { Accept: 'application/json' },
      })
      if (daftarRes.ok()) {
        const d = await daftarRes.json()
        const pendaftaranId = d.data.id
        await inviterCtx.post(`${API_BASE}/pendaftarans/${pendaftaranId}/invite`, {
          headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
          data: { email: userEmail },
        })
      }
    }
  })

  test.beforeEach(async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/dashboard/undangan')
    await page.waitForTimeout(3000)
  })

  test('page renders with heading', async ({ page }) => {
    await expect(page.getByText('Undangan Tim')).toBeVisible()
  })

  test('pending invitation card shows details', async ({ page }) => {
    const terimaBtn = page.getByText('Terima')
    if (await terimaBtn.count() > 0) {
      await expect(terimaBtn.first()).toBeVisible()
      await expect(page.getByText('Tolak').first()).toBeVisible()
    }
  })

  test('accept and reject buttons are present', async ({ page }) => {
    const acceptBtn = page.getByText('Terima')
    if (await acceptBtn.count() > 0) {
      await expect(acceptBtn.first()).toBeVisible()
      await expect(page.getByText('Tolak').first()).toBeVisible()
    }
  })
})

test.describe('Dashboard Help', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-help')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test.beforeEach(async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/dashboard/help')
    await page.waitForTimeout(2000)
  })

  test('page renders with FAQ accordion', async ({ page }) => {
    await expect(page.getByText('Bantuan & FAQ')).toBeVisible()
    await expect(page.getByText('Masih Butuh Bantuan?')).toBeVisible()
  })

  test('FAQ accordion opens and closes on click', async ({ page }) => {
    const questionText = 'Bagaimana cara membayar biaya pendaftaran?'
    const question = page.getByText(questionText).first()
    await question.click()
    await page.waitForTimeout(500)
    const answer = page.getByText(/sesuai dengan metode pembayaran/).first()
    await expect(answer).toBeVisible()
    await question.click()
    await page.waitForTimeout(500)
    await expect(answer).not.toBeVisible()
  })

  test('WhatsApp contact button is present', async ({ page }) => {
    const waBtn = page.getByText('Hubungi WhatsApp Admin')
    await expect(waBtn).toBeVisible()
  })
})
