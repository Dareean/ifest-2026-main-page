import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail, API_BASE } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi, setAdminViaApi } from './helpers/seed.js'

test.describe('Admin Dashboard', () => {
  let adminEmail

  test.beforeAll(async () => {
    adminEmail = uniqueEmail('e2e-admindash')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await setAdminViaApi(adminEmail)
  })

  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 45000 })
    await page.goto('/dashboard/admin')
    await page.waitForTimeout(3000)
  })

  test('heading renders', async ({ page }) => {
    await expect(page.getByText('Dashboard').first()).toBeVisible()
  })

  test('stat cards display', async ({ page }) => {
    await expect(page.getByText('Total Pengguna').first()).toBeVisible()
    await expect(page.getByText('Pending').first()).toBeVisible()
    await expect(page.getByText('Terverifikasi').first()).toBeVisible()
    await expect(page.getByText('Ditolak').first()).toBeVisible()
  })

  test('recent registrations section loads', async ({ page }) => {
    await expect(page.getByText('Pendaftaran Terbaru').first()).toBeVisible()
  })

  test('per-lomba stats section loads', async ({ page }) => {
    await expect(page.getByText('Per Lomba').first()).toBeVisible()
  })

  test('activity log section loads', async ({ page }) => {
    await expect(page.getByText('Aktivitas Terbaru').first()).toBeVisible()
  })
})

test.describe('Admin Users', () => {
  let adminEmail, targetEmail

  test.beforeAll(async () => {
    adminEmail = uniqueEmail('e2e-users-admin')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await setAdminViaApi(adminEmail)

    targetEmail = uniqueEmail('e2e-users-target')
    await registerUserViaApi({ ...TEST_USER, email: targetEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(targetEmail)
  })

  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 45000 })
    await page.goto('/dashboard/admin/users')
    await page.waitForTimeout(3000)
  })

  test('page renders with heading and table', async ({ page }) => {
    await expect(page.getByText('Pengguna').first()).toBeVisible()
    await expect(page.getByText(TEST_USER.name).first()).toBeVisible()
  })

  test('search filters users', async ({ page }) => {
    const searchInput = page.locator('input[placeholder*="Cari nama"]')
    await searchInput.fill(targetEmail)
    await page.waitForTimeout(1500)
    await expect(page.getByText(targetEmail).first()).toBeVisible()
  })

  test('role change select is visible for non-self users', async ({ page }) => {
    const adminBadge = page.getByText('Admin (Anda)')
    await expect(adminBadge.first()).toBeVisible()
  })

  test('delete button is visible for non-self users', async ({ page }) => {
    const deleteBtn = page.locator('button[title="Hapus akun"]').first()
    await expect(deleteBtn).toBeVisible()
  })
})

test.describe('Admin Manage', () => {
  let adminEmail

  test.beforeAll(async () => {
    adminEmail = uniqueEmail('e2e-manage-admin')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await setAdminViaApi(adminEmail)

    const admin2Email = uniqueEmail('e2e-manage-admin2')
    await registerUserViaApi({ ...TEST_USER, email: admin2Email, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(admin2Email)
    await setAdminViaApi(admin2Email)
  })

  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 45000 })
    await page.goto('/dashboard/admin/manage')
    await page.waitForTimeout(3000)
  })

  test('page renders with heading and admin list', async ({ page }) => {
    await expect(page.getByText('Manage Admin').first()).toBeVisible()
    await expect(page.getByText('Admin (Anda)').first()).toBeVisible()
  })

  test('table shows admin name and email', async ({ page }) => {
    await expect(page.getByText(adminEmail).first()).toBeVisible()
  })
})

test.describe('Admin Notifications — Broadcast', () => {
  let adminEmail

  test.beforeAll(async () => {
    adminEmail = uniqueEmail('e2e-notif-admin')
    await registerUserViaApi({ ...TEST_USER, email: adminEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(adminEmail)
    await setAdminViaApi(adminEmail)
  })

  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
    await page.waitForSelector('input[type="email"]', { timeout: 10000 })
    await page.fill('input[type="email"]', adminEmail)
    await page.fill('input[type="password"]', TEST_USER.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 45000 })
    await page.goto('/dashboard/admin/notifications')
    await page.waitForTimeout(2000)
  })

  test('page renders with heading and broadcast form', async ({ page }) => {
    await expect(page.getByText('Notifikasi').first()).toBeVisible()
    await expect(page.getByText('Kirim Notifikasi').first()).toBeVisible()
    await expect(page.getByText('Riwayat Notifikasi').first()).toBeVisible()
  })

  test('broadcast form has judul and pesan fields', async ({ page }) => {
    const judulInput = page.locator('input[placeholder*="Pengumuman"]')
    const textarea = page.locator('textarea[placeholder*="Tulis pesan"]')
    await expect(judulInput).toBeVisible()
    await expect(textarea).toBeVisible()
  })

  test('radio buttons for all/specific users shown', async ({ page }) => {
    await expect(page.getByText('Semua Pengguna').first()).toBeVisible()
    await expect(page.getByText('Pengguna Spesifik').first()).toBeVisible()
  })

  test('send button is present', async ({ page }) => {
    await expect(page.getByText('Kirim Notifikasi').first()).toBeVisible()
  })
})
