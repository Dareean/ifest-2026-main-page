import { test, expect } from '@playwright/test'

test.describe('Landing Page — Roadshow', () => {
  test('page loads with correct heading and URL', async ({ page }) => {
    await page.goto('/roadshow')
    await expect(page).toHaveURL('/roadshow')
    await expect(page.getByText('ROADSHOW INKLUSIF').first()).toBeVisible({ timeout: 10000 })
  })

  test('target audience cards render', async ({ page }) => {
    await page.goto('/roadshow')
    await page.waitForSelector('#roadshow-grid', { timeout: 10000 })
    const cards = page.locator('#roadshow-grid > div')
    expect(await cards.count()).toBeGreaterThanOrEqual(3)
  })

  test('syllabus pillars section renders', async ({ page }) => {
    await page.goto('/roadshow')
    await page.waitForTimeout(2000)
    await expect(page.getByText('PILLAR 01').first()).toBeVisible()
    await expect(page.getByText('PILLAR 02').first()).toBeVisible()
    await expect(page.getByText('PILLAR 03').first()).toBeVisible()
    await expect(page.getByText('PILLAR 04').first()).toBeVisible()
  })

  test('execution stages render', async ({ page }) => {
    await page.goto('/roadshow')
    await page.waitForTimeout(2000)
    await expect(page.getByText('STAGE 01').first()).toBeVisible()
    await expect(page.getByText('STAGE 02').first()).toBeVisible()
    await expect(page.getByText('STAGE 03').first()).toBeVisible()
  })

  test('back navigation to home works', async ({ page }) => {
    await page.goto('/roadshow')
    await page.locator('a[href="/"]').first().click()
    await page.waitForURL(/\//)
  })
})
