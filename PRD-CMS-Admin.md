# PRD — CMS Admin Panel I-FEST 2026

## 1. Tujuan

Membangun Content Management System untuk admin mengelola konten landing page secara real-time tanpa deploy ulang.

---

## 2. Scope

### Modul A: ✅ Sudah Ada (jangan diubah)

| Modul | Routes | Keterangan |
|-------|--------|------------|
| Dashboard | `GET /api/admin/stats` | Total user, pendaftaran per lomba |
| Pendaftaran | CRUD `/api/admin/pendaftarans/*` | Verifikasi, reject, batch, export CSV |
| Users | `GET /api/admin/users`, `PUT role`, `DELETE` | Manajemen user + role |
| Lombas | CRUD `/api/admin/lombas/*` | Edit lomba, toggle submission/active |
| Notifications | `GET/POST /api/admin/notifications` | Broadcast notification + email via Brevo |
| Activity Logs | `GET /api/admin/activity-logs` | Riwayat aksi admin |
| Admin Manage | `GET /api/admin/super/admins` | Daftar admin |

### Modul B: 🆕 Perlu Dibangun

#### B1 — Partners

**Deskripsi:** Kelola sponsor, media partner, organizer, dan sponsorship tier.

**Tabel:** `partners`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint, auto-increment | |
| type | string | Enum: `main_strategic`, `strategic_partner`, `media_partner`, `organizer`, `sponsorship_tier` |
| name | string | Nama partner |
| logo_url | string | URL logo |
| instagram_url | string, nullable | Link Instagram |
| description | text, nullable | Deskripsi |
| tier_data | JSON, nullable | Data tambahan untuk sponsorship tier |
| order | integer, default 0 | Urutan tampil |
| is_active | boolean, default true | Tampil/tidak di landing page |
| timestamps | | created_at, updated_at |

**Backend:**
- Controller: `PartnerController`
- Routes Admin (auth:sanctum + admin):
  - `GET /api/admin/partners` → `adminIndex()`
  - `POST /api/admin/partners` → `store()`
  - `GET /api/admin/partners/{partner}` → `show()`
  - `PUT /api/admin/partners/{partner}` → `update()`
  - `DELETE /api/admin/partners/{partner}` → `destroy()`
- Public Route:
  - `GET /api/partners` → `index()` — hanya `is_active=true`, urut by `order`

**Validation Rules (store/update):**
```php
'type' => 'required|string|in:main_strategic,strategic_partner,media_partner,organizer,sponsorship_tier'
'name' => 'required|string|max:255'
'logo_url' => 'required|string|max:1000'
'instagram_url' => 'nullable|string|max:500'
'description' => 'nullable|string|max:2000'
'tier_data' => 'nullable|array'
'order' => 'nullable|integer|min:0'
```

**Model fillable:**
```php
['type', 'name', 'logo_url', 'instagram_url', 'description', 'tier_data', 'order', 'is_active']
```

**Model casts:**
```php
['tier_data' => 'array', 'is_active' => 'boolean']
```

**Frontend Admin:**
- File: `src/pages/dashboard/admin/AdminPartners.vue`
- Form fields: `type` (dropdown), `name`, `logo_url`, `instagram_url`, `description`, `order`
- JANGAN kirim field yang tidak ada di fillable model

**Landing Page:**
- Section di `HomePage.vue`
- Tampilkan partner per type dalam swimlane/logo grid
- Urut berdasarkan `order`

---

#### B2 — Timeline Events

**Deskripsi:** Kelola jadwal acara I-FEST yang ditampilkan di timeline landing page.

**Tabel:** `timeline_events`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint, auto-increment | |
| phase | string | Nomor/fase (ex: "1") |
| title | string | Judul event |
| date_range | string | Rentang tanggal (ex: "9 Juli - 9 Agustus 2026") |
| description_items | JSON, nullable | Array string deskripsi |
| accent_color | string, nullable | Warna aksen (hex) |
| status | string, default 'upcoming' | Enum: `upcoming`, `ongoing`, `completed` |
| order | integer, default 0 | Urutan tampil |
| is_active | boolean, default true | Tampil/tidak |
| timestamps | | created_at, updated_at |

**Backend:**
- Controller: `TimelineController`
- Routes Admin (auth:sanctum + admin):
  - `GET /api/admin/timeline` → `adminIndex()`
  - `POST /api/admin/timeline` → `store()`
  - `GET /api/admin/timeline/{timelineEvent}` → `show()`
  - `PUT /api/admin/timeline/{timelineEvent}` → `update()`
  - `DELETE /api/admin/timeline/{timelineEvent}` → `destroy()`
- Public Route:
  - `GET /api/timeline` → `index()` — hanya `is_active=true`, urut by `order`

**Validation Rules (store/update):**
```php
'phase' => 'required|string|max:10'
'title' => 'required|string|max:255'
'date_range' => 'required|string|max:255'
'description_items' => 'nullable|array'
'description_items.*' => 'string'
'accent_color' => 'nullable|string|max:20'
'status' => 'nullable|string|in:upcoming,ongoing,completed'
'order' => 'nullable|integer|min:0'
```

**Model fillable:**
```php
['phase', 'title', 'date_range', 'description_items', 'accent_color', 'status', 'order', 'is_active']
```

**Model casts:**
```php
['description_items' => 'array', 'is_active' => 'boolean']
```

**Frontend Admin:**
- File: `src/pages/dashboard/admin/AdminTimeline.vue`
- Form fields: `phase`, `title`, `date_range`, `description_items` (textarea, 1 baris per item → split ke array), `accent_color`, `status` (dropdown: upcoming/ongoing/completed), `order`
- ⚠️ JANGAN pakai field `date`, `description`, atau `icon` — field2 itu tidak exist di backend
- ⚠️ JANGAN lupa kirim `phase` — required di backend

**Landing Page:**
- Section di `HomePage.vue`
- Tampilkan timeline vertikal dengan status badge

---

#### B3 — FAQ Items

**Deskripsi:** Kelola pertanyaan yang sering diajukan (accordion FAQ).

**Tabel:** `faq_items`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | bigint, auto-increment | |
| question | string | Pertanyaan |
| answer | text | Jawaban |
| order | integer, default 0 | Urutan tampil |
| is_active | boolean, default true | Tampil/tidak |
| timestamps | | created_at, updated_at |

**Backend:**
- Controller: `FaqController`
- Routes Admin (auth:sanctum + admin):
  - `GET /api/admin/faqs` → `adminIndex()`
  - `POST /api/admin/faqs` → `store()`
  - `GET /api/admin/faqs/{faqItem}` → `show()`
  - `PUT /api/admin/faqs/{faqItem}` → `update()`
  - `DELETE /api/admin/faqs/{faqItem}` → `destroy()`
- Public Route:
  - `GET /api/faqs` → `index()` — hanya `is_active=true`, urut by `order`

**Validation Rules (store/update):**
```php
'question' => 'required|string|max:500'
'answer' => 'required|string|max:5000'
'order' => 'nullable|integer|min:0'
```

**Model fillable:**
```php
['question', 'answer', 'order', 'is_active']
```

**Model casts:**
```php
['is_active' => 'boolean']
```

**Frontend Admin:**
- File: `src/pages/dashboard/admin/AdminFaqs.vue`
- Form fields: `question`, `answer` (textarea), `order`
- ⚠️ JANGAN kirim field `category` — tidak ada kolom `category` di tabel `faq_items`

**Landing Page:**
- Section di `HomePage.vue`
- Tampilkan FAQ sebagai accordion

---

## 3. Update yang Diperlukan di File Lain

### routes/api.php

Di dalam grup admin (`prefix('admin')` + `middleware('auth:sanctum', 'admin')`), tambahkan:

```php
Route::get('/partners', [PartnerController::class, 'adminIndex']);
Route::post('/partners', [PartnerController::class, 'store']);
Route::get('/partners/{partner}', [PartnerController::class, 'show']);
Route::put('/partners/{partner}', [PartnerController::class, 'update']);
Route::delete('/partners/{partner}', [PartnerController::class, 'destroy']);

Route::get('/timeline', [TimelineController::class, 'adminIndex']);
Route::post('/timeline', [TimelineController::class, 'store']);
Route::get('/timeline/{timelineEvent}', [TimelineController::class, 'show']);
Route::put('/timeline/{timelineEvent}', [TimelineController::class, 'update']);
Route::delete('/timeline/{timelineEvent}', [TimelineController::class, 'destroy']);

Route::get('/faqs', [FaqController::class, 'adminIndex']);
Route::post('/faqs', [FaqController::class, 'store']);
Route::get('/faqs/{faqItem}', [FaqController::class, 'show']);
Route::put('/faqs/{faqItem}', [FaqController::class, 'update']);
Route::delete('/faqs/{faqItem}', [FaqController::class, 'destroy']);
```

Di luar grup admin (public):

```php
Route::get('/partners', [PartnerController::class, 'index']);
Route::get('/timeline', [TimelineController::class, 'index']);
Route::get('/faqs', [FaqController::class, 'index']);
```

### router.js (Frontend)

Tambah routes admin:

```js
{ path: 'partners', name: 'AdminPartners', component: () => import('../pages/dashboard/admin/AdminPartners.vue') }
{ path: 'timeline', name: 'AdminTimeline', component: () => import('../pages/dashboard/admin/AdminTimeline.vue') }
{ path: 'faqs', name: 'AdminFaqs', component: () => import('../pages/dashboard/admin/AdminFaqs.vue') }
```

### DashboardAdminLayout.vue (Sidebar Navigation)

Tambah menu navigasi:
- Partner → icon `Handshake`
- Timeline → icon `CalendarDays`
- FAQ → icon `HelpCircle`

---

## 4. Hal yang JANGAN Diubah

⚠️ File-file ini TIDAK BOLEH disentuh:

| File | Alasan |
|------|--------|
| `config/session.php` | Ubah `same_site` bisa mematahkan Google login (cookie cross-origin) |
| `app/Http/Middleware/SecurityHeadersMiddleware.php` | Menangani SameSite cookie — jika diubah, Google login rusak |
| `.env` / `.env.example` / `.env.production` | Konfigurasi environment, jangan di-track git |

---

## 5. Anti-Mistake Checklist

Wajib dicek satu per satu sebelum commit:

- [ ] Setiap field di form frontend NAMA-NYA SAMA persis dengan field di backend validation & fillable
- [ ] Setiap field di form frontend ADA di migration table & model `$fillable`
- [ ] Coba submit form → data masuk ke database (cek via `GET /api/admin/{resource}`)
- [ ] Coba `GET /api/{resource}` (public) → data tampil dengan `is_active=true`
- [ ] Login sebagai admin (`admin@ifest.com` / `admin123`) → bisa akses semua halaman admin baru
- [ ] Login Google tetap berfungsi setelah perubahan (test cross-origin Vercel → Render)
- [ ] `php artisan config:cache` jalan tanpa error
- [ ] Tidak ada perubahan ke `config/session.php`
- [ ] Tidak ada perubahan ke `SecurityHeadersMiddleware.php`
- [ ] Tidak ada perubahan ke `.env`, `.env.example`, `.env.production`
- [ ] Tidak ada secret/credential yang ke-track git

---

## 6. Catatan untuk Implementasi

1. **Urutan eksekusi:** Migration → Model → Controller → Routes → Frontend Admin → Landing Page
2. **Seed data opsional** — bisa ditambahkan di `DatabaseSeeder` untuk testing
3. `description_items` di timeline HARUS dikirim sebagai **array of strings**, bukan JSON string — Laravel cast `array` handle otomatis
4. `tier_data` di partner opsional, dikirim sebagai **array** atau `null`
5. Semua index public endpoint Wajib filter `is_active=true` + `orderBy('order')` — jangan expose data non-active ke publik
