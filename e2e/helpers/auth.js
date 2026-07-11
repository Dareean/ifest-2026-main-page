export async function loginViaUI(page, email, password) {
  await page.goto('/login')
  await page.waitForSelector('input[type="email"]')

  await page.fill('input[type="email"]', email)
  await page.fill('input[type="password"]', password)

  await page.click('button[type="submit"]')

  await page.waitForURL(/\/dashboard/, { timeout: 20000 })
}

export async function getAuthToken(page) {
  return page.evaluate(() => localStorage.getItem('auth_token'))
}

export async function logout(page) {
  await page.evaluate(() => {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  })
  await page.goto('/')
}
