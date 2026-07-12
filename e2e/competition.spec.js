import { test, expect } from '@playwright/test'

test.describe('Fase 5: Competition Landing Page', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/kompetisi')
  })

  test('page loads with correct URL and main heading', async ({ page }) => {
    await expect(page).toHaveURL('/kompetisi')
    await expect(page.getByText('DIGITAL COMPETITIONS.')).toBeVisible()
  })

  test('sidebar shows 5 competition navigation buttons', async ({ page }) => {
    const navButtons = page.locator('nav button')
    await expect(navButtons).toHaveCount(5)
  })

  test('first competition (Competitive Programming) is pre-selected by default', async ({ page }) => {
    const firstBtn = page.locator('nav button').first()
    await expect(firstBtn).toContainText('Competitive Programming')
  })

  test('clicking a different competition shows its details in the content area', async ({ page }) => {
    await page.locator('nav button').filter({ hasText: 'REG-02' }).click()
    await expect(page.locator('h2')).toContainText('Digital Education Poster')
  })

  test('detail view shows competition description', async ({ page }) => {
    await expect(page.getByText('Uji ketajaman pemikiran logis')).toBeVisible()
  })

  test('detail view shows technical specs grid', async ({ page }) => {
    await expect(page.getByText('Target Peserta')).toBeVisible()
    await expect(page.getByText('Ketentuan Tim')).toBeVisible()
    await expect(page.getByText('Biaya Registrasi')).toBeVisible()
  })

  test('detail view shows sub-themes section', async ({ page }) => {
    await expect(page.getByText('SUB-THEMES / SUB-TEMA PILIHAN')).toBeVisible()
  })

  test('detail view shows rules section', async ({ page }) => {
    await expect(page.getByText('RULES & INSTRUCTIONS')).toBeVisible()
  })

  test('daftar button shows correct text for logged-out user', async ({ page }) => {
    await expect(page.getByText('DAFTAR SEKARANG')).toBeVisible()
  })

  test('guidebook download link is present for competitions that have one', async ({ page }) => {
    const guidebookLink = page.locator('a:has-text("UNDUH JUKNIS")')
    await expect(guidebookLink).toBeVisible()
  })
})
