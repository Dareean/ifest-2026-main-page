export const API_BASE = 'http://localhost:8000/api'

export const TEST_USER = {
  name: 'E2E Test User',
  password: 'TestPass123!',
  phone: '08123456789',
  institution: 'Universitas Tadulako',
}

export const PROFILE_UPDATE = {
  name: 'E2E Updated Name',
  phone: '08987654321',
  institution: 'Universitas Indonesia',
}

export const DRIVE_LINK = 'https://drive.google.com/file/d/1e2e-test-submission'
export const FIGMA_LINK = 'https://www.figma.com/file/1e2e-test-figma'
export const ORIGINALITY_LINK = 'https://drive.google.com/file/d/1e2e-test-originality'

export function uniqueEmail(prefix = 'e2e') {
  return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 6)}@test.ifest.com`
}
