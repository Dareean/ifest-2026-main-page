import { API_BASE } from '../fixtures/data.js'

const APP_BASE = API_BASE.replace('/api', '')

let requestContext = null
let csrfToken = ''

async function getRequest() {
  if (!requestContext) {
    const { request } = await import('@playwright/test')
    requestContext = await request.newContext()
  }
  return requestContext
}

function withXsrf(headers = {}) {
  return csrfToken ? { ...headers, 'X-XSRF-TOKEN': csrfToken } : headers
}

function extractXsrfToken(setCookieHeader) {
  if (!setCookieHeader) return ''
  const cookies = Array.isArray(setCookieHeader) ? setCookieHeader : [setCookieHeader]
  for (const c of cookies) {
    const match = c.match(/XSRF-TOKEN=([^;]+)/)
    if (match) return decodeURIComponent(match[1])
  }
  return ''
}

export async function getCsrfCookie() {
  const ctx = await getRequest()
  const res = await ctx.get(`${APP_BASE}/sanctum/csrf-cookie`, {
    headers: { 'Origin': 'http://localhost:5173' },
  })
  if (!csrfToken) {
    csrfToken = extractXsrfToken(res.headers()['set-cookie'])
  }
}

export async function registerUserViaApi(userData) {
  await getCsrfCookie()
  const ctx = await getRequest()
  const res = await ctx.post(`${API_BASE}/auth/register`, {
    headers: withXsrf({ 'Origin': 'http://localhost:5173' }),
    data: userData,
  })
  const body = await res.json()
  if (!res.ok()) throw new Error(`Register failed: ${JSON.stringify(body)}`)
  return body
}

export async function verifyUserViaApi(email) {
  const ctx = await getRequest()
  const res = await ctx.post(`${API_BASE}/e2e/verify-user`, { data: { email } })
  const body = await res.json()
  if (!res.ok()) throw new Error(`Verify failed: ${JSON.stringify(body)}`)
  return body
}

export async function loginViaApi(email, password) {
  await getCsrfCookie()
  const ctx = await getRequest()
  const res = await ctx.post(`${API_BASE}/auth/login`, {
    headers: withXsrf({ 'Origin': 'http://localhost:5173' }),
    data: { email, password },
  })
  const body = await res.json()
  if (!res.ok()) throw new Error(`Login failed: ${JSON.stringify(body)}`)
  return body
}

export async function getResetTokenViaApi(email) {
  const ctx = await getRequest()
  const res = await ctx.post(`${API_BASE}/e2e/reset-token`, { data: { email } })
  const body = await res.json()
  if (!res.ok()) throw new Error(`Get reset token failed: ${JSON.stringify(body)}`)
  return body
}

export async function setAdminViaApi(email) {
  const ctx = await getRequest()
  const res = await ctx.post(`${API_BASE}/e2e/set-admin`, { data: { email } })
  const body = await res.json()
  if (!res.ok()) throw new Error(`Set admin failed: ${JSON.stringify(body)}`)
  return body
}

/**
 * Create a fresh API context logged in as the given user.
 * Useful for tests that need multiple authenticated roles (e.g. admin + user).
 */
export async function loginAs(email, password) {
  const { request } = await import('@playwright/test')
  const ctx = await request.newContext()
  const csrfRes = await ctx.get(`${APP_BASE}/sanctum/csrf-cookie`, {
    headers: { 'Origin': 'http://localhost:5173' },
  })
  const token = extractXsrfToken(csrfRes.headers()['set-cookie'])
  const res = await ctx.post(`${API_BASE}/auth/login`, {
    headers: token ? { 'Origin': 'http://localhost:5173', 'X-XSRF-TOKEN': token } : {},
    data: { email, password },
  })
  if (!res.ok()) throw new Error(`Login as ${email} failed: ${await res.text()}`)
  return ctx
}

/**
 * Perform a POST request on the given context with CSRF protection.
 * Fetches a fresh CSRF cookie and sets the X-XSRF-TOKEN header automatically.
 */
export async function postWithCsrf(ctx, url, data) {
  const csrfRes = await ctx.get(`${APP_BASE}/sanctum/csrf-cookie`, {
    headers: { 'Origin': 'http://localhost:5173' },
  })
  const token = extractXsrfToken(csrfRes.headers()['set-cookie'])
  return ctx.post(url, {
    headers: token ? { 'Origin': 'http://localhost:5173', 'X-XSRF-TOKEN': token, 'Content-Type': 'application/json', Accept: 'application/json' } : {},
    data,
  })
}
