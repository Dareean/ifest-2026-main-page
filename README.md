# I-FEST 2026 — Informatics Festival Universitas Tadulako

Portal resmi pendaftaran dan informasi **I-FEST 2026 (Informatics Festival)**, acara teknologi tahunan terbesar oleh **HMTI Universitas Tadulako**. Meliputi kompetisi nasional, seminar, dan festival IT.

**Live:** [https://ifest-hmti-untad.vercel.app](https://ifest-hmti-untad.vercel.app)

---

## Fitur Utama

- Landing page & informasi festival
- Registrasi peserta (email + Google OAuth)
- Verifikasi email via OTP
- Pendaftaran kompetisi (individu & tim)
- Manajemen tim (undang anggota, kelola)
- Upload bukti pembayaran + verifikasi admin
- Pengumpulan karya kompetisi
- Two-Factor Authentication (2FA)
- Dashboard peserta (profil, notifikasi, kompetisi)
- Dashboard admin (kelola pendaftaran, user, lomba, notifikasi, stats)
- AI Chatbot (Google Gemini)
- Notifikasi broadcast ke seluruh user

---

## Tech Stack

| Lapisan | Teknologi |
|---------|-----------|
| Frontend | Vue 3 (Composition API), Vite 6, Tailwind CSS 3 |
| Backend | Laravel 13, PHP 8.3+ |
| State | Pinia 3 |
| Auth | Laravel Sanctum (SPA session) + Google Socialite + 2FA |
| Database | PostgreSQL 16 (Supabase) |
| AI | Google Gemini AI |
| Animasi | GSAP 3, @vueuse/motion |
| Testing E2E | Playwright 1.61 |
| Testing Backend | PHPUnit 12 |
| Icons | Lucide Vue Next |
| Deploy FE | Vercel |
| Deploy BE | Render.com |

---

## Cara Menjalankan

### Prasyarat

- Node.js 20+ & npm
- PHP 8.3+ & Composer
- PostgreSQL (atau Supabase)
- Docker (opsional, lebih mudah)

### Cepat — Docker

```bash
docker-compose up -d
# Frontend: http://localhost:5173
# Backend:  http://localhost:8000
```

### Manual — dua terminal

**Terminal 1 — Backend:**

```bash
cd backend
cp .env.example .env   # isi konfigurasi database
composer install
php artisan key:generate
php artisan migrate
php artisan serve      # http://localhost:8000
```

**Terminal 2 — Frontend:**

```bash
npm install
npm run dev            # http://localhost:5173
```

---

## Cara Testing

```bash
npm run test:e2e             # E2E Playwright (headless)
npm run test:e2e:headed      # E2E dengan browser terbuka
npm run test:backend         # PHPUnit
npm run check:all            # Semua test
```

**Catatan:** E2E test butuh backend berjalan (`php artisan serve`) dengan `APP_ENV=local`.

---

## Cara Deploy

### Frontend — Vercel

```bash
npm run build     # menghasilkan dist/
```
Deploy folder `dist/` ke Vercel. Konfigurasi ada di `vercel.json`.

### Backend — Render

Konfigurasi ada di `backend/render.yaml`. Set environment variables di dashboard Render:
- `APP_KEY`, `DB_*`, `GOOGLE_*`, `GEMINI_API_KEY`, `BREVO_API_KEY`, dll.

### Docker Production

```bash
docker build -f docker/prod/Dockerfile -t ifest-frontend .
docker run -p 80:80 ifest-frontend
```

---

## Struktur Proyek

```
├── src/                  # Frontend Vue 3
│   ├── pages/            # Halaman (home, login, register, dashboard, dll)
│   ├── components/       # Komponen reusable (chat, toast, loader)
│   ├── stores/           # Pinia store (auth, admin)
│   ├── composables/      # Vue composables (toast, confirm, navigasi)
│   └── utils/            # Axios instance
├── backend/              # Laravel API
│   ├── app/Http/Controllers/   # Controller (Auth, Admin, Lomba, dll)
│   ├── app/Models/             # Model
│   ├── database/migrations/    # 41 migration files
│   └── routes/                 # API routes
├── e2e/                  # Playwright E2E tests
├── docker/               # Dockerfile dev & production
├── public/               # Assets statis (sitemap, robots.txt, favicon)
└── docs/                 # Dokumentasi tambahan
```

---

## API Endpoints Ringkasan

| Grup | Endpoint |
|------|----------|
| **Auth** | register, login, logout, send-otp, verify-otp, forgot-password, reset-password, 2FA, Google OAuth |
| **Kompetisi** | GET /lombas, GET /lombas/{id} |
| **Pendaftaran** | daftar, index, show, upload-payment, upload-social-proof, request-unlock |
| **Tim** | invite, accept, reject, remove-member, social-proof |
| **Submissions** | submit, index |
| **Profil** | update, password, avatar |
| **Admin** | stats, pendaftarans (CRUD + batch), users, notifications, lombas, activity-logs, exports |
| **AI** | chat (Gemini proxy) |

Semua endpoint diawali `/api/*`. Auth via Sanctum SPA cookie (CSRF-protected).

---

## Security

- **Auth:** Sanctum SPA session (cookie-based, no Bearer token), CSRF-protected
- **2FA:** Time-based One-Time Password (TOTP), recovery codes di-hash
- **Rate Limiting:** Per-endpoint throttle (lebih ketat di production)
- **Headers:** Content-Security-Policy, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- **CORS:** Origin-restricted, method/header terbatas
- **Input Validation:** Semua input divalidasi, XSS dicegah (DOMPurify + strip_tags)
- **Dependencies:** 0 kerentanan (composer & npm audit)
- **Audit:** Keamanan telah diaudit menyeluruh — 31+ celah ditutup

---

## Scripts Penting

| Script | Fungsi |
|--------|--------|
| `npm run dev` | Jalankan frontend development |
| `npm run build` | Build production frontend |
| `npm run test:e2e` | E2E test dengan Playwright |
| `npm run test:backend` | PHPUnit backend test |
| `npm run check:all` | Semua test (E2E + backend) |
| `npm run generate-panitia` | Generate data kepanitiaan |
| `npm run optimize-images` | Optimasi gambar |

---

## Tim Pengembang

Dikembangkan oleh **HMTI Universitas Tadulako** — Himpunan Mahasiswa Teknik Informatika.

Untuk laporan bug atau saran, buka issue di repository ini.
