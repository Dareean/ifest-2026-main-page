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

export function extractXsrfToken(setCookieHeader) {
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
  // Always refresh — after a login the session regenerates and the old XSRF token becomes stale
  const fresh = extractXsrfToken(res.headers()['set-cookie'])
  if (fresh) csrfToken = fresh
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

export async function getTokenForUser(email) {
  const ctx = await getRequest()
  const res = await ctx.post(`${API_BASE}/e2e/token`, { data: { email } })
  const body = await res.json()
  if (!res.ok()) throw new Error(`Get token failed: ${JSON.stringify(body)}`)
  return body.token
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

const ORIGIN = 'http://localhost:5173'

async function fetchXsrfToken(ctx) {
  const res = await ctx.get(`${APP_BASE}/sanctum/csrf-cookie`, {
    headers: { Origin: ORIGIN },
  })
  return extractXsrfToken(res.headers()['set-cookie'])
}

/**
 * Create a fresh API context logged in as the given user.
 * Returns a wrapper that auto-injects CSRF token + Origin header on every
 * request, so spec files never need to worry about Sanctum session auth.
 * The wrapper preserves the Playwright APIRequestContext method signatures,
 * making this a drop-in replacement.
 */
export async function loginAs(email, password) {
  const { request } = await import('@playwright/test')
  const ctx = await request.newContext()
  const token = await fetchXsrfToken(ctx)
  const res = await ctx.post(`${API_BASE}/auth/login`, {
    headers: token ? { Origin: ORIGIN, 'X-XSRF-TOKEN': token } : {},
    data: { email, password },
  })
  if (!res.ok()) throw new Error(`Login as ${email} failed: ${await res.text()}`)

  async function withCsrf(method, url, options = {}) {
    const xsrfToken = await fetchXsrfToken(ctx)
    return ctx[method](url, {
      ...options,
      headers: { Origin: ORIGIN, 'X-XSRF-TOKEN': xsrfToken, ...options.headers },
    })
  }

  return {
    get:     (url, opts) => ctx.get(url, { ...opts, headers: { Origin: ORIGIN, Accept: 'application/json', ...opts?.headers } }),
    post:    (url, opts) => withCsrf('post', url, opts),
    put:     (url, opts) => withCsrf('put', url, opts),
    patch:   (url, opts) => withCsrf('patch', url, opts),
    delete:  (url, opts) => withCsrf('delete', url, opts),
  }
}

export async function authPost(ctx, url, data) {
  const csrfRes = await ctx.get(`${APP_BASE}/sanctum/csrf-cookie`, {
    headers: { 'Origin': 'http://localhost:5173' },
  })
  const token = extractXsrfToken(csrfRes.headers()['set-cookie'])
  return ctx.post(url, {
    headers: { 'Origin': 'http://localhost:5173', 'X-XSRF-TOKEN': token, Accept: 'application/json' },
    data,
  })
}

export async function authPut(ctx, url, data) {
  const csrfRes = await ctx.get(`${APP_BASE}/sanctum/csrf-cookie`, {
    headers: { 'Origin': 'http://localhost:5173' },
  })
  const token = extractXsrfToken(csrfRes.headers()['set-cookie'])
  return ctx.put(url, {
    headers: { 'Origin': 'http://localhost:5173', 'X-XSRF-TOKEN': token, 'Content-Type': 'application/json', Accept: 'application/json' },
    data,
  })
}

export async function authGet(ctx, url) {
  await ctx.get(`${APP_BASE}/sanctum/csrf-cookie`, {
    headers: { 'Origin': 'http://localhost:5173' },
  })
  return ctx.get(url, {
    headers: { 'Origin': 'http://localhost:5173', Accept: 'application/json' },
  })
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

export async function forceVerifyPendaftaranViaApi(pendaftaranId) {
  const ctx = await getRequest()
  const res = await ctx.post(`${API_BASE}/e2e/force-verify`, { data: { pendaftaran_id: pendaftaranId } })
  const body = await res.json()
  if (!res.ok()) throw new Error(`Force verify failed: ${JSON.stringify(body)}`)
  return body
}
