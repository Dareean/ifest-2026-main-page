# E2E Testing — I-FEST 2026

Playwright End-to-End tests untuk seluruh flow aplikasi, dari registrasi sampai pengumpulan karya.

## Struktur

```
e2e/
├── fixtures/data.js          # Data dummy dan utility
├── helpers/
│   ├── auth.js               # Helper login via UI (browser)
│   └── seed.js               # Helper seeding data via API
├── auth.spec.js              # Test login, register, OTP redirect
├── profile.spec.js           # Test edit profil
├── competition.spec.js       # Test lihat & filter kompetisi
├── submission.spec.js        # Test pengumpulan karya
├── flow-lengkap.spec.js      # Test end-to-end penuh
└── README.md                 # Dokumentasi ini
```

## Prasyarat

| Komponen | Status | Keterangan |
|----------|--------|------------|
| Backend Laravel | ✅ Wajib running | `php artisan serve` atau Docker |
| Frontend Vite | ✅ Otomatis | Playwright `webServer` akan start `npm run dev` |
| `APP_ENV=local` | ✅ Wajib | Di `.env` Laravel, biar `/api/e2e/verify-user` aktif |
| Playwright browser | ✅ Terinstall | `npx playwright install chromium` |

## Quick Start

```bash
# 1. Pastikan backend jalan
cd backend && php artisan serve &
# atau via Docker: docker-compose up -d backend

# 2. Jalankan semua test E2E
cd .. && npm run test:e2e
```

## Available Scripts

| Perintah | Fungsi |
|----------|--------|
| `npm run test:e2e` | Run semua E2E headless |
| `npm run test:e2e:headed` | Run dengan browser terbuka (lihat langsung) |
| `npm run test:e2e:debug` | Run dengan Playwright Inspector |
| `npm run test:e2e:report` | Buka HTML report hasil test terakhir |
| `npm run test:backend` | Run PHPUnit backend |
| `npm run check:all` | Run E2E + backend sekaligus |

## Cara Kerja Helper `verify-user`

Setiap test yang butuh user login menggunakan pola ini:

1. **Register user via API** → `POST /api/auth/register`
2. **Verify email via helper** → `POST /api/e2e/verify-user` (hanya aktif di `APP_ENV=local`)
3. **Login via UI** → `loginViaUI(page, email, password)` (isi form → klik submit → tunggu redirect)
4. **Jalankan test scenario** → edit profil, lihat kompetisi, dll.

Email setiap test unique (`e2e-{timestamp}-{random}@test.ifest.com`) jadi tidak tabrakan.

## Menambahkan Test Baru

```javascript
// e2e/feature-saya.spec.js
import { test, expect } from '@playwright/test'
import { TEST_USER, uniqueEmail } from './fixtures/data.js'
import { registerUserViaApi, verifyUserViaApi } from './helpers/seed.js'
import { loginViaUI } from './helpers/auth.js'

test.describe('Fitur Saya', () => {
  let userEmail

  test.beforeAll(async () => {
    userEmail = uniqueEmail('e2e-saya')
    await registerUserViaApi({ ...TEST_USER, email: userEmail, password_confirmation: TEST_USER.password })
    await verifyUserViaApi(userEmail)
  })

  test.beforeEach(async ({ page }) => {
    await loginViaUI(page, userEmail, TEST_USER.password)
  })

  test('melakukan sesuatu', async ({ page }) => {
    await page.goto('/dashboard/fitur-saya')
    await expect(page.getByText('Judul Halaman')).toBeVisible()
  })
})
```

## Tips

- **Pertahankan isolasi**: Setiap `test.describe` buat user sendiri pakai `uniqueEmail()`
- **Gunakan `test.step()`** untuk grouping langkah dalam satu test (lihat `flow-lengkap.spec.js`)
- **Screenshot otomatis** di-capture kalau test fail, ada di `test-results/`
- **Report HTML** lengkap dengan trace & video bisa dibuka via `npm run test:e2e:report`

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| `Cannot find module` | Cek path import relatif ke `e2e/` — pakai `./fixtures/...` bukan `../fixtures/...` |
| `Error: 403 — Email belum diverifikasi` | Pastikan backend running dengan `APP_ENV=local` |
| `Error: 401 — Unauthenticated` | Cek apakah backend pakai rate limiting — tunggu sebentar |
| `Timeout 30000ms` | Naikkan timeout di `playwright.config.js` atau pake `{ timeout: 60000 }` per test |
| Port 5173 sudah dipakai | Matikan Vite lain, atau set `webServer` di config ke port beda |
| Report HTML kosong | Pastikan test sudah pernah jalan minimal sekali |
