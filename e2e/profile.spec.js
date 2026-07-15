import { test, expect } from '@playwright/test'
import { API_BASE, TEST_USER, PROFILE_UPDATE, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

test.describe('Profile — API', () => {
  let email

  test.beforeAll(async ({ request }) => {
    email = uniqueEmail('e2e-profile-api')
    await registerUserViaApi({
      name: TEST_USER.name,
      email,
      password: TEST_USER.password,
      password_confirmation: TEST_USER.password,
    })
    await verifyUserViaApi(email)
    await request.get(`${API_BASE.replace('/api', '')}/sanctum/csrf-cookie`)
    const loginRes = await request.post(`${API_BASE}/auth/login`, { data: { email, password: TEST_USER.password } })
    expect(loginRes.status()).toBe(200)
  })

  test('PUT /profile updates name successfully', async ({ request }) => {
    const res = await request.put(`${API_BASE}/profile`, {
      data: { name: PROFILE_UPDATE.name },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.message).toContain('berhasil diperbarui')
    expect(body.user.name).toBe(PROFILE_UPDATE.name)
  })

  test('PUT /profile updates all fields', async ({ request }) => {
    const res = await request.put(`${API_BASE}/profile`, {
      data: {
        name: PROFILE_UPDATE.name,
        phone: PROFILE_UPDATE.phone,
        institution: PROFILE_UPDATE.institution,
      },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.user.phone).toBe(PROFILE_UPDATE.phone)
    expect(body.user.institution).toBe(PROFILE_UPDATE.institution)
  })

  test('PUT /profile with empty name returns 422', async ({ request }) => {
    const res = await request.put(`${API_BASE}/profile`, {
      data: { name: '' },
    })
    expect(res.status()).toBe(422)
    const body = await res.json()
    expect(body.errors.name).toBeDefined()
  })

  test('PUT /profile without auth returns 401', async ({ request }) => {
    const res = await request.put(`${API_BASE}/profile`, {
      data: { name: 'Hacker' },
    })
    expect(res.status()).toBe(401)
  })

  test('PUT /password updates password successfully', async ({ request }) => {
    const res = await request.put(`${API_BASE}/password`, {
      data: {
        current_password: TEST_USER.password,
        new_password: 'NewPass789!',
        new_password_confirmation: 'NewPass789!',
      },
    })
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.message).toContain('berhasil diubah')

    await request.put(`${API_BASE}/password`, {
      data: {
        current_password: 'NewPass789!',
        new_password: TEST_USER.password,
        new_password_confirmation: TEST_USER.password,
      },
    })
  })

  test('PUT /password with wrong current password returns 400 or 429 (rate-limited)', async ({ request }) => {
    const res = await request.put(`${API_BASE}/password`, {
      data: {
        current_password: 'WrongPassword1!',
        new_password: 'NewPass789!',
        new_password_confirmation: 'NewPass789!',
      },
    })
    expect([400, 429]).toContain(res.status())
    if (res.status() === 400) {
      const body = await res.json()
      expect(body.message).toContain('salah')
    }
  })

  test('PUT /password without auth returns 401', async ({ request }) => {
    const res = await request.put(`${API_BASE}/password`, {
      data: { current_password: 'x', new_password: 'NewPass789!', new_password_confirmation: 'NewPass789!' },
    })
    expect(res.status()).toBe(401)
  })

  test('PUT /password with mismatched passwords returns 422 or 429', async ({ request }) => {
    const res = await request.put(`${API_BASE}/password`, {
      data: {
        current_password: TEST_USER.password,
        new_password: 'NewPass789!',
        new_password_confirmation: 'Different1!',
      },
    })
    expect([422, 429]).toContain(res.status())
    if (res.status() === 422) {
      const body = await res.json()
      expect(body.errors.new_password).toBeDefined()
    }
  })

  test('PUT /password with short password returns 422 or 429', async ({ request }) => {
    const res = await request.put(`${API_BASE}/password`, {
      data: {
        current_password: TEST_USER.password,
        new_password: '1234567',
        new_password_confirmation: '1234567',
      },
    })
    expect([422, 429]).toContain(res.status())
    if (res.status() === 422) {
      const body = await res.json()
      expect(body.errors.new_password).toBeDefined()
    }
  })

  test('PUT /password without current_password returns 422 (validation) or 429 (rate-limited)', async ({ request }) => {
    const res = await request.put(`${API_BASE}/password`, {
      data: {
        new_password: 'NewPass789!',
        new_password_confirmation: 'NewPass789!',
      },
    })
    expect([422, 429]).toContain(res.status())
  })

  test('POST /avatar without auth returns 401', async ({ request }) => {
    const res = await request.post(`${API_BASE}/avatar`, {})
    expect(res.status()).toBe(401)
  })

  test('POST /avatar with invalid file type returns 422 or 429', async ({ request }) => {
    const res = await request.post(`${API_BASE}/avatar`, {
      multipart: {
        avatar: {
          name: 'test.txt',
          mimeType: 'text/plain',
          buffer: Buffer.from('not an image'),
        },
      },
    })
    expect([422, 429]).toContain(res.status())
    if (res.status() === 422) {
      const body = await res.json()
      expect(body.errors?.avatar || body.message).toBeDefined()
    }
  })
})

test.describe('Profile — UI', () => {
  let email

  test.beforeAll(async () => {
    email = uniqueEmail('e2e-profile-ui')
    await registerUserViaApi({
      name: TEST_USER.name,
      email,
      password: TEST_USER.password,
      password_confirmation: TEST_USER.password,
    })
    await verifyUserViaApi(email)
  })

  async function loginAndGoToProfile(page) {
    await loginViaUI(page, email, TEST_USER.password)
    await page.goto('/dashboard/profile')
    await page.waitForSelector('text=Data Diri')
  }

  test('Profile form pre-fills user data', async ({ page }) => {
    await loginAndGoToProfile(page)
    const nameInput = page.locator('input[type="text"]').first()
    await expect(nameInput).toHaveValue(TEST_USER.name)
  })

  test('Name update shows success message', async ({ page }) => {
    await loginAndGoToProfile(page)
    await page.locator('input[type="text"]').first().fill(PROFILE_UPDATE.name)
    await page.getByRole('button', { name: /simpan perubahan/i }).click()
    await expect(page.getByText('Profil berhasil diperbarui')).toBeVisible({ timeout: 15000 })
  })

  test('Updated profile persists after API update and page reload', async ({ page, request }) => {
    // Update profile via API first
    await request.get(`${API_BASE.replace('/api', '')}/sanctum/csrf-cookie`)
    await request.post(`${API_BASE}/auth/login`, { data: { email, password: TEST_USER.password } })
    const apiRes = await request.put(`${API_BASE}/profile`, {
      data: {
        name: PROFILE_UPDATE.name,
        phone: PROFILE_UPDATE.phone,
        institution: PROFILE_UPDATE.institution,
      },
    })
    expect(apiRes.status()).toBe(200)

    // Reload page and check updated values
    await loginAndGoToProfile(page)
    await expect(page.locator('input[type="text"]').first()).toHaveValue(PROFILE_UPDATE.name, { timeout: 15000 })
  })

  test('Password form has all required fields', async ({ page }) => {
    await loginAndGoToProfile(page)
    const pwForm = page.locator('form').filter({ has: page.getByRole('button', { name: /perbarui password/i }) })
    await expect(pwForm.locator('input[placeholder="Masukkan password saat ini"]')).toBeVisible()
    await expect(pwForm.locator('input[minlength="8"]')).toBeVisible()
    await expect(pwForm.locator('input[type="password"]')).toHaveCount(3)
  })

  test('Password form submit button shows correct label', async ({ page }) => {
    await loginAndGoToProfile(page)
    const btn = page.getByRole('button', { name: /perbarui password/i })
    await expect(btn).toBeVisible()
    await expect(btn).toHaveText('Perbarui Password')
  })

  test('Avatar upload zone is accessible', async ({ page }) => {
    await loginAndGoToProfile(page)
    const fileInput = page.locator('input[type="file"]')
    await expect(fileInput).toBeVisible({ visible: false })
    const avatarZone = page.locator('.group').first()
    await expect(avatarZone).toBeVisible()
  })

  test('Password field shows optional note for Google-connected users', async ({ page, request }) => {
    await request.get(`${API_BASE.replace('/api', '')}/sanctum/csrf-cookie`)
    await request.post(`${API_BASE}/auth/login`, { data: { email, password: TEST_USER.password } })

    await page.route('**/api/auth/user', async route => {
      try {
        const response = await route.fetch()
        const body = await response.json()
        body.user.google_id = 'e2e_google_id'
        await route.fulfill({ response, body: JSON.stringify(body) })
      } catch {
        await route.continue().catch(() => {})
      }
    })

    await page.goto('/dashboard/profile')
    await page.waitForSelector('text=(Opsional untuk login Google)')
    await expect(page.getByText('Opsional untuk login Google')).toBeVisible()
  })
})
