# I-FEST 2026 Landing Page — Session Progress

## Project Location
`D:\Daren Punya Barang\HMTI - Daren\IFEST - 2026\Website IFEST\ifest-main-landing-page`

## Project Structure
```
ifest-main-landing-page/
├── backend/           # Laravel API (was laravel-temp/)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   └── ...
├── src/               # Vue 3 frontend
├── docker/            # Docker configs
├── e2e/               # Playwright E2E tests
├── public/            # Static assets
├── docker-compose.yml
└── ...
```

## Completed Work (from start to finish)

### Phase 1: Sanctum SPA Auth Migration
- **Problem**: Backend used API token auth; migrated to Sanctum SPA session-based auth
- **Login 500 fix** (`e2e/helpers/seed.js`): Added `Origin: http://localhost:5173` header to all POST helpers (`postWithCsrf()`, `registerUserViaApi()`, `loginViaApi()`, `loginAs()`) — Sanctum requires Origin/Referer matching stateful domain for `StartSession` middleware to run
- **Logout `TransientToken::delete()` fix** (`backend/app/Http/Controllers/AuthController.php:207`): Added `method_exists($token, 'delete')` guard — SPA session auth returns `TransientToken` which lacks `delete()`
- **Register UI CSRF fix** (`src/stores/auth.js:12`): Added `await getCsrf()` before `api.post('/auth/register')` — was missing CSRF initialization
- **E2E Tests**: All 30 tests passed (1 skipped — rate limiting)
  - Registration API: 9/9, Login API: 6/6, UI Integration: 7/7, Logout: 3/3, OTP Verification: 5/5
- **Removed debug specs**: `e2e/debug-logout.spec.js`, `e2e/debug-login-ui.spec.js`

### Phase 2: Comprehensive Unused File Analysis
- Investigated entire project for unused files via grep/glob
- **Root orphans**: 7 files (973 KB total) — no references anywhere
- **Unused assets**: maskot/ (18 files), dokumen/ (7 files), Fotage/ (duplicate), logo_utama_png/ (3 PNGs), sponsor files (3), visual_assets/ (~30 webp)
- **Empty folders**: `api/`, `.gemini/`

### Phase 3: Cleanup Execution
- **Deleted root orphans**: `code.html`, `extracted_proposal_partnership.txt`, `extracted_text.txt`, `raw_strings.txt`, `fixed_prd.pdf`, `Riso-ClickUp PRD.pdf`, `screen.png`
- **Deleted unused assets**:
  - `src/assets/maskot/` (18 files)
  - `src/assets/dokumen/` (7 files: docx, xlsx, pdf)
  - `src/assets/Fotage Panitia Ifest 2026/` (duplicate photo)
  - `src/assets/logo_utama/logo_utama_png/` (3 PNG files — webp versions used instead)
  - `src/assets/sponsor-strategic_partner/` (3 unused files)
  - `src/assets/visual_assets/` (~30 unused webp files)
- **Deleted empty folders**: `api/`, `.gemini/`
- **`stats.html`**: Added to `.gitignore` and deleted

### Phase 4: Rename `laravel-temp/` → `backend/`
- `git mv` preserved 100% history
- Updated references in:
  - `docker-compose.yml` (build context, volumes)
  - `.dockerignore`
  - `package.json` (test:backend script)
  - `e2e/README.md`
  - `backend/render.yaml`

### Phase 5: Drive Migration (C: → D:)
- Project moved from `C:\Users\ASUS\Documents\HMTI - Daren\...` to `D:\Daren Punya Barang\HMTI - Daren\...`
- Fixed split copy issue: `backend/` and `backend copy/` were partial — merged `backend copy/` contents (resources, routes, storage, tests, vendor) into main `backend/`, then deleted the duplicate
- C: copy (`C:\Users\ASUS\Documents\HMTI - Daren\IFEST - 2026`) remains locked until old session is closed — delete manually via Explorer

### Git Commit
- `9c7ab0f` — "cleanup: remove unused files, rename laravel-temp/ to backend/"
- 202 files changed, 9 insertions, 9,062 deletions

## Pending
- Delete `C:\Users\ASUS\Documents\HMTI - Daren\IFEST - 2026` manually via Explorer (locked while old session is open)
- Future sessions should open from: `opencode -d "D:\Daren Punya Barang\HMTI - Daren\IFEST - 2026\Website IFEST\ifest-main-landing-page"`

## Technical Notes
- Backend is SPA session-based using Sanctum (not token-based)
- All API requests from Node.js must include `Origin: http://localhost:5173` header
- CSRF requires calling `GET /sanctum/csrf-cookie` before any state-changing POST
- Axios `withXSRFToken: true` auto-reads `XSRF-TOKEN` cookie and sets `X-XSRF-TOKEN` header
