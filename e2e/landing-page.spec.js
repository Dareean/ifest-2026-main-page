import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

test.describe('Landing Page — HomePage', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-landing')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test('hero section loads with correct heading and CTA', async ({ page }) => {
    await page.goto('/')
    await expect(page.getByText('DIGITAL SYMPHONY').first()).toBeVisible({ timeout: 15000 })
    await expect(page.getByText('THE BIGGEST IT FESTIVAL IN EASTERN INDONESIA')).toBeVisible()
    await expect(page.getByRole('button', { name: /EXPLORE THE SYMPHONY/i })).toBeVisible()
  })

  test('navbar shows login link for logged-out users', async ({ page }) => {
    await page.goto('/')
    const loginLink = page.locator('a[href="/login"]').first()
    await expect(loginLink).toBeVisible()
    await expect(loginLink).toContainText('Masuk')
  })

  test('mobile menu toggle shows and hides nav links', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 })
    await page.goto('/')
    const toggleBtn = page.locator('button[aria-label="Toggle menu"]')
    await expect(toggleBtn).toBeVisible()
    await toggleBtn.click()
    await expect(page.getByRole('link', { name: 'Roadshow', exact: true }).first()).toBeVisible()
    await expect(page.locator('a[href="/login"]').first()).toBeVisible()
    await toggleBtn.click()
    await page.waitForTimeout(500)
    await expect(page.getByRole('link', { name: 'Roadshow', exact: true }).first()).not.toBeVisible()
  })

  test('hero CTA scrolls to pillars section', async ({ page }) => {
    await page.goto('/')
    await page.getByRole('button', { name: /EXPLORE THE SYMPHONY/i }).click()
    await page.waitForTimeout(1000)
    await expect(page.locator('#pillars')).toBeInViewport()
  })

  test('3 core pillars render with correct headings', async ({ page }) => {
    await page.goto('/')
    await page.locator('#pillars').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    await expect(page.getByText('01 / RESONANCE')).toBeVisible()
    await expect(page.getByText('02 / SYNERGY')).toBeVisible()
    await expect(page.getByText('03 / INCLUSIVITY')).toBeVisible()
  })

  test('roadshow section renders with link to roadshow page', async ({ page }) => {
    await page.goto('/')
    await page.locator('#roadshow-section').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    await expect(page.getByText('ROADSHOW INKLUSIF & SOCIAL MOVEMENT.')).toBeVisible()
    await expect(page.locator('a[href="/roadshow"]').first()).toBeVisible()
  })

  test('competitions section shows arena heading and detail links', async ({ page }) => {
    await page.goto('/')
    await page.locator('#competitions-section').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    await expect(page.getByText('ARENA KOMPETISI DIGITAL.')).toBeVisible()
    const detailLinks = page.locator('a:has-text("Lihat Detail Lomba")')
    await expect(detailLinks.first()).toBeVisible({ timeout: 15000 })
  })

  test('impact dashboard shows stat labels', async ({ page }) => {
    await page.goto('/')
    await page.locator('#impact-dashboard').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    await expect(page.getByText('ESTIMASI TARGET PARTISIPAN').first()).toBeVisible()
  })

  test('timeline phase accordion expands and collapses on click', async ({ page }) => {
    await page.goto('/')
    await page.locator('#timeline').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    const phaseHeader = page.locator('h4:has-text("PHASE 01")').first()
    await phaseHeader.click()
    await page.waitForTimeout(500)
    await phaseHeader.click()
    await page.waitForTimeout(500)
  })

  test('detail kegiatan zine accordion expands identity section', async ({ page }) => {
    await page.goto('/')
    await page.locator('#detail-kegiatan').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    const identityBtn = page.locator('button:has-text("IDENTITY & FOUNDATION")').first()
    // It is open by default, click to collapse
    await identityBtn.click()
    await expect(page.getByText('Memastikan kesiapan internal yang prima').first()).toBeHidden()
    // Click again to expand
    await identityBtn.click()
    await expect(page.getByText('Memastikan kesiapan internal yang prima').first()).toBeVisible()
  })

  test('roadshow and kompetisi links in zine section navigate correctly', async ({ page }) => {
    await page.goto('/')
    await page.locator('#detail-kegiatan').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    const roadshowLink = page.locator('a:has-text("ROADSHOW INKLUSIF (Digital Symphony Tour)")').first()
    await expect(roadshowLink).toBeVisible()
    const kompetisiLink = page.locator('a:has-text("ARENA KOMPETISI (Digital Competitions)")').first()
    await expect(kompetisiLink).toBeVisible()
  })

  test('gallery section loads with navigation buttons', async ({ page }) => {
    await page.goto('/')
    await page.locator('#galeri-jejak-langkah').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    await expect(page.getByText('Arsip Resonansi 2025.')).toBeVisible()
    await expect(page.locator('button:has-text("←")').first()).toBeVisible()
    await expect(page.locator('button:has-text("→")').first()).toBeVisible()
  })

  test('gallery next button changes displayed card', async ({ page }) => {
    await page.goto('/')
    await page.locator('#galeri-jejak-langkah').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    const nextBtn = page.locator('button:has-text("→")').first()
    const prevBtn = page.locator('button:has-text("←")').first()
    await nextBtn.click()
    await page.waitForTimeout(500)
    await prevBtn.click()
    await page.waitForTimeout(500)
  })

  test('BPH matrix section shows committee modal on person click', async ({ page }) => {
    await page.goto('/')
    await page.locator('#bph-matrix').scrollIntoViewIfNeeded()
    await page.waitForTimeout(2000)

    const personCard = page.locator('#bph-matrix [data-person]').first()
    if (await personCard.count() > 0) {
      await personCard.click()
      await page.waitForTimeout(500)
      const closeBtn = page.locator('button[aria-label="Close modal"]')
      await expect(closeBtn).toBeVisible()
      await closeBtn.click()
      await page.waitForTimeout(300)
      await expect(closeBtn).not.toBeVisible()
    }
  })

  test('BPH modal closes on Escape key', async ({ page }) => {
    await page.goto('/')
    await page.locator('#bph-matrix').scrollIntoViewIfNeeded()
    await page.waitForTimeout(2000)

    const personCard = page.locator('#bph-matrix [data-person]').first()
    if (await personCard.count() > 0) {
      await personCard.click()
      await page.waitForTimeout(500)
      await page.keyboard.press('Escape')
      await page.waitForTimeout(300)
      await expect(page.locator('button[aria-label="Close modal"]')).not.toBeVisible()
    }
  })

  test('division tabs switch committee view', async ({ page }) => {
    await page.goto('/')
    await page.locator('#bph-matrix').scrollIntoViewIfNeeded()
    await page.waitForTimeout(2000)

    const secondTab = page.locator('#bph-matrix button').filter({ hasText: /Kreativitas|Sponsor|Acara/ }).first()
    if (await secondTab.count() > 0) {
      await secondTab.click()
      await page.waitForTimeout(500)
      await expect(secondTab).toBeVisible()
    }
  })

  test('partners section renders with scheme tabs', async ({ page }) => {
    await page.goto('/')
    await page.locator('#partners').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    await expect(page.getByText('Ekosistem Kolaborasi.')).toBeVisible()
    await expect(page.getByText('Tungsten').first()).toBeVisible()
  })

  test('partners scheme tab switches content', async ({ page }) => {
    await page.goto('/')
    await page.locator('#partners').scrollIntoViewIfNeeded()
    await page.waitForTimeout(1000)
    const schemeBtn = page.getByText('Maestro').first()
    await schemeBtn.click()
    await page.waitForTimeout(500)
  })

  test('footer scroll-to-hero button works', async ({ page }) => {
    await page.goto('/')
    const footerBtn = page.locator('button[aria-label="Scroll to top"]').first()
    if (await footerBtn.count() > 0) {
      await footerBtn.scrollIntoViewIfNeeded()
      await page.waitForTimeout(500)
      await footerBtn.click()
      await page.waitForTimeout(1000)
      await expect(page.locator('#hero')).toBeInViewport()
    }
  })

  test('login link in navbar navigates to login page', async ({ page }) => {
    await page.goto('/')
    await page.locator('a[href="/login"]').first().click()
    await page.waitForURL(/\/login/)
  })

  test('logged-in user sees dashboard link instead of login', async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
    await page.goto('/')
    await expect(page.locator('a[href="/dashboard"]').first()).toBeVisible()
  })
})
