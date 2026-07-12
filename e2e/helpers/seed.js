import { API_BASE } from '../fixtures/data.js'

let requestContext = null

async function getRequest() {
  if (!requestContext) {
    const { request } = await import('@playwright/test')
    requestContext = await request.newContext()
  }
  return requestContext
}

export async function registerUserViaApi(userData) {
  const ctx = await getRequest()
  const res = await ctx.post(`${API_BASE}/auth/register`, { data: userData })
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
  const ctx = await getRequest()
  const res = await ctx.post(`${API_BASE}/auth/login`, { data: { email, password } })
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
