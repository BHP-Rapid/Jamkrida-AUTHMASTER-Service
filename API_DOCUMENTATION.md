# API Documentation

Base URL: `/api`

## Autentikasi dan Middleware

- `public` routes: default tidak diamankan, kecuali group yang menggunakan `jwt.auth`.
- `internal` routes: menggunakan middleware `internal.service`; sebagian route juga menggunakan `internal.user`.
- Untuk endpoint JWT-protected, gunakan header:
  - `Authorization: Bearer {token}`

---

## 1. Public API Routes (`/api/public`)

### 1.1. Public / tanpa auth

#### GET `/api/public/bank-values`
- Description: Ambil daftar bank values.
- Params: tidak ada

#### GET/POST `/api/public/check-id`
- Description: Cek user ID atau email.
- Body / Query:
  - `user_id` (nullable, string, max:50, pattern `^[a-zA-Z0-9_-]+$`)
  - `email` (nullable, email)

#### POST `/api/public/auth/login`
- Body:
  - `email` (required, email)
  - `password` (required, string)

#### POST `/api/public/auth/admin/login`
- Body:
  - `email` (required, email)
  - `password` (required, string)

#### POST `/api/public/auth/admin/verify-otp`
- Body:
  - `user_id` (required)
  - `otp` (required, digits:5)

#### POST `/api/public/auth/mitra/login`
- Body:
  - `user_id` (required, string)
  - `password` (required, string)

#### POST `/api/public/auth/mitra/verify-otp`
- Body:
  - `user_id` (required, string)
  - `otp` (required, digits:5)

#### POST `/api/public/auth/refresh`
- Description: Refresh token.
- Params: tidak ada

#### GET `/api/public/auth/reset-password/validate`
- Query:
  - `url_key` (required, string)
  - `user_type` (required, string, in: `admin`, `mitra`)

#### POST `/api/public/auth/reset-password`
- Body:
  - `user_id` (required, string)
  - `password` (required, string, min:6, confirmed)
  - `password_confirmation` (required when `password` is confirmed)
  - `user_type` (required, string, in: `admin`, `mitra`)

#### POST `/api/public/auth/reset-password/resend-email`
- Body:
  - `user_type` (required, string, in: `admin`, `mitra`)
  - `user_id` (nullable, string)
  - `email` (nullable, email)

#### GET `/api/public/settings/general`
- Description: Ambil general settings.
- Params: tidak ada

---

### 1.2. Master data routes (`/api/public/master`, requires `jwt.auth`)

#### GET `/api/public/master/mapping-values`
- Params: tidak ada

#### GET `/api/public/master/mapping-values/table`
- Query:
  - `sort` (nullable, string, in: `asc`, `desc`)
  - `sort_column` (nullable, string)
  - `page` (nullable, integer, min:1)
  - `show_page` (nullable, integer, min:1)
  - `filter` (nullable, array)

#### GET `/api/public/master/mapping-values/key/{key}`
- Path:
  - `key` (string)

#### GET `/api/public/master/mapping-values/institutions`
- Params: tidak ada

#### GET `/api/public/master/mapping-values/lampiran`
- Query:
  - `jenis_mitra` (required, string, size:3)
  - `module` (required, string)
  - `jenis_produk` (required, string, max:4)

#### GET `/api/public/master/provinces`
- Query:
  - `code` (nullable, string)
  - `name` (nullable, string)
  - `province_code` (nullable, string)
  - `regency_code` (nullable, string)
  - `district_code` (nullable, string)

#### GET `/api/public/master/regencies`
- Query:
  - `code` (nullable, string)
  - `name` (nullable, string)
  - `province_code` (nullable, string)
  - `regency_code` (nullable, string)
  - `district_code` (nullable, string)

#### GET `/api/public/master/districts`
- Query:
  - `code` (nullable, string)
  - `name` (nullable, string)
  - `province_code` (nullable, string)
  - `regency_code` (nullable, string)
  - `district_code` (nullable, string)

#### GET `/api/public/master/villages`
- Query:
  - `code` (nullable, string)
  - `name` (nullable, string)
  - `province_code` (nullable, string)
  - `regency_code` (nullable, string)
  - `district_code` (nullable, string)

---

### 1.3. Roles routes (`/api/public/roles`, requires `jwt.auth`)

#### GET `/api/public/roles/me`
- Params: tidak ada

#### GET `/api/public/roles/`
- Query:
  - `sort` (nullable, string, in: `asc`, `desc`)
  - `sort_column` (nullable, string)
  - `page` (nullable, integer, min:1)
  - `show_page` (nullable, integer, min:1)
  - `filter` (nullable, array)

#### GET `/api/public/roles/access`
- Body / Query:
  - `id` (required, integer, min:1)

#### PUT `/api/public/roles/access`
- Body:
  - `role_id` (required, integer, min:1)
  - `payload` (required, array, min:1)
  - `payload.*.menu_id` (required, integer, min:1)
  - `payload.*.action` (array)
  - `payload.*.action.*` (required, string)

#### GET `/api/public/roles/type/{roleType}`
- Path:
  - `roleType` (string)

---

### 1.4. Settings routes (`/api/public/settings`, requires `jwt.auth`)

#### GET `/api/public/settings/`
- Params: tidak ada

#### GET `/api/public/settings/detail`
- Query:
  - `module` (required, string)
  - `mitra_id` (required, string)
  - `product_id` (required, string)

#### GET `/api/public/settings/mitra/{mitraId}`
- Path:
  - `mitraId` (string)

#### GET `/api/public/settings/menu`
- Params: tidak ada

#### GET `/api/public/settings/lampiran-menu`
- Query:
  - `jenis_mitra` (required, string, size:3)
  - `module` (required, string)
  - `jenis_produk` (required, string, max:4)

#### POST `/api/public/settings/`
- Body:
  - `mitra_id` (required, string, max:10)
  - `module` (required, string, max:20)
  - `product_details` (required, array, min:1)
  - `product_details.*.product_id` (required, string)
  - `product_details.*.key` (required, string)
  - `product_details.*.value` (nullable, string)
  - `product_details.*.lampiran` (nullable, string)
  - `product_details.*.reason_claim` (nullable, string)
  - `product_details.*.is_mandatory` (required, integer)

#### PUT `/api/public/settings/`
- Body: array of objects
  - `*.mitra_id` (required, string)
  - `*.module` (required, string)
  - `*.product_id` (required, string)
  - `*.lampiran` (required, string)
  - `*.is_mandatory` (required, integer, in: `0`, `1`)

#### PATCH `/api/public/settings/mandatory`
- Body:
  - `mitra_id` (required, string)
  - `generalSettings` (nullable, array)
  - `lampiran` (nullable, array)
  - `reasonClaim` (nullable, array)

---

### 1.5. Mitra routes (`/api/public/mitra`, requires `jwt.auth`)

#### GET `/api/public/mitra/creatio`
- Query:
  - `sort` (nullable, string, in: `asc`, `desc`)
  - `sort_column` (nullable, string)
  - `page` (nullable, integer, min:1)
  - `show_page` (nullable, integer, min:1)
  - `filter` (nullable, array)

#### GET `/api/public/mitra/data`
- Params: tidak ada

#### GET `/api/public/mitra/detail`
- Query:
  - `mitra_id` (required, string)

#### POST `/api/public/mitra/`
- Body:
  - `mitra_id` (required, string, min:3, max:13)
  - `name_mitra` (required, string)
  - `email` (required, email)
  - `phone_number` (required, string)
  - `address` (required, string)
  - `status` (required, string)

#### PUT `/api/public/mitra/`
- Body:
  - `id` (nullable, string)
  - `mitra_id` (nullable, string)
  - `name_mitra` (nullable, string)
  - `email` (nullable, email)
  - `address` (nullable, string)
  - `phone_number` (nullable, string)
  - `status` (nullable, string)

---

### 1.6. Notification routes (`/api/public/notif`, requires `jwt.auth`)

#### GET `/api/public/notif/`
- Query:
  - `id` (nullable, integer)
  - `page` (nullable, integer, min:1)
  - `limit` (nullable, integer, min:1)
  - `role` (nullable, string)

#### GET `/api/public/notif/count`
- Query:
  - `id` (nullable, integer)
  - `role` (nullable, string)

#### PATCH `/api/public/notif/`
- Body:
  - `dataId` (required, integer)

#### PATCH `/api/public/notif/all`
- Body:
  - `user_id` (required, string)

---

### 1.7. Notification Admin routes (`/api/public/notif-admin`, requires `jwt.auth`)

#### GET `/api/public/notif-admin/`
- Query:
  - `sort` (nullable, string, in: `asc`, `desc`)
  - `sort_column` (nullable, string)
  - `page` (nullable, integer, min:1)
  - `show_page` (nullable, integer, min:1)
  - `filter` (nullable, array)

#### GET `/api/public/notif-admin/mitra-recipient`
- Query:
  - `page` (nullable, integer, min:1)
  - `limit` (nullable, integer, min:1)

#### POST `/api/public/notif-admin/`
- Body:
  - `title` (required, string)
  - `message` (required, string)
  - `recipientType` (required, string, in: `all`, `selected`)
  - `recipient` (nullable, array)
  - `recipient.*` (string)

#### GET `/api/public/notif-admin/{id}`
- Path:
  - `id` (integer)

---

### 1.8. Admin user routes (`/api/public/admin-users`)

#### POST `/api/public/admin-users/register`
- Body:
  - `mitra_id` (nullable, string)
  - `role` (required, string, in: `admin_mitra`, `admin`, `super_admin`)
  - `email` (required, email)
  - `name` (required, string, max:255)
  - `phone` (nullable, string, max:20)

#### GET `/api/public/admin-users/` (requires `jwt.auth`)
- Params: tidak ada

#### GET `/api/public/admin-users/list`
- Query:
  - `sort` (nullable, string, in: `asc`, `desc`)
  - `sort_column` (nullable, string)
  - `page` (nullable, integer, min:1)
  - `show_page` (nullable, integer, min:1)
  - `filter` (nullable, array)
  - `search` (nullable, string)

#### GET `/api/public/admin-users/verification`
- Params: tidak ada

#### GET `/api/public/admin-users/role-list`
- Params: tidak ada

#### GET `/api/public/admin-users/mitra-list`
- Params: tidak ada

#### GET `/api/public/admin-users/{userId}`
- Path:
  - `userId` (string)

#### PUT `/api/public/admin-users/{userId}`
- Path:
  - `userId` (string)
- Body:
  - `email` (required, email)
  - `name` (required, string, max:255)
  - `phone` (nullable, string, max:20)
  - `password` (nullable, string, min:6)

#### PATCH `/api/public/admin-users/{userId}/status-approval`
- Path:
  - `userId` (string)
- Body:
  - `statusApproval` (required, string)

#### PATCH `/api/public/admin-users/{userId}/status`
- Path:
  - `userId` (string)
- Body:
  - `status` (required, string, in: `active`, `inactive`)

#### DELETE `/api/public/admin-users/{userId}`
- Path:
  - `userId` (string)

#### POST `/api/public/admin-users/change-password`
- Body:
  - `current_password` (required, string)
  - `new_password` (required, string, min:6, confirmed, different from current_password)
  - `new_password_confirmation` (required when new_password is confirmed)

---

### 1.9. Mitra user routes (`/api/public/mitra-users`)

#### GET `/api/public/mitra-users/`
- Params: tidak ada

#### GET `/api/public/mitra-users/list`
- Query:
  - `sort` (nullable, string, in: `asc`, `desc`)
  - `sort_column` (nullable, string)
  - `page` (nullable, integer, min:1)
  - `show_page` (nullable, integer, min:1)
  - `filter` (nullable, array)

#### GET `/api/public/mitra-users/verification`
- Params: tidak ada

#### POST `/api/public/mitra-users/`
- Body:
  - `mitra_id` (required, string)
  - `role` (required, string, in: `pusat`, `cabang`, `head_admin_mitra`, `mitra`)
  - `email` (required, email)
  - `name` (required, string)
  - `phone` (nullable, string)

#### POST `/api/public/mitra-users/upload-excel`
- Body: expected file upload/form-data untuk Excel import

#### POST `/api/public/mitra-users/change-password`
- Body:
  - `current_password` (required, string)
  - `new_password` (required, string, min:6, confirmed, different from current_password)
  - `new_password_confirmation` (required when new_password is confirmed)

#### GET `/api/public/mitra-users/{userId}`
- Path:
  - `userId` (string)

#### PUT `/api/public/mitra-users/{userId}`
- Path:
  - `userId` (string)
- Body:
  - `role` (required, string)
  - `email` (required, email)
  - `name` (required, string)
  - `phone` (nullable, string)

#### PATCH `/api/public/mitra-users/{userId}/status`
- Path:
  - `userId` (string)
- Body:
  - `status` (required, string)

#### PATCH `/api/public/mitra-users/{userId}/status-approval`
- Path:
  - `userId` (string)
- Body:
  - `statusApproval` (required, string)

#### DELETE `/api/public/mitra-users/{userId}`
- Path:
  - `userId` (string)

---

## 2. Internal API Routes (`/api/internal`)

Middleware:
- `internal.service` for semua route
- `internal.user` for route-group master/roles/settings/mitra/notif/notif-admin/admin-users/mitra-users dan /permissions/check /roles/check /users/{userId}/context

### 2.1. Global internal routes

#### GET `/api/internal/users/{user}`
- Path:
  - `user` (string)

#### POST `/api/internal/admin-users/register`
- Body: sama dengan admin register public

#### POST `/api/internal/mitra-users/register`
- Body: sama dengan mitra register public

---

### 2.2. Internal master routes (`/api/internal/master`)

Sama dengan `/api/public/master` namun tanpa JWT dan menggunakan `internal.user`.

---

### 2.3. Internal roles routes (`/api/internal/roles`)

Sama dengan `/api/public/roles` namun tanpa JWT dan menggunakan `internal.user`.

---

### 2.4. Internal settings routes (`/api/internal/settings`)

Sama dengan `/api/public/settings` namun tanpa JWT dan menggunakan `internal.user`.

---

### 2.5. Internal mitra routes (`/api/internal/mitra`)

Sama dengan `/api/public/mitra` namun tanpa JWT dan menggunakan `internal.user`.

---

### 2.6. Internal notif routes (`/api/internal/notif`)

Sama dengan `/api/public/notif` namun tanpa JWT dan menggunakan `internal.user`.

---

### 2.7. Internal notif-admin routes (`/api/internal/notif-admin`)

Sama dengan `/api/public/notif-admin` namun tanpa JWT and using `internal.user`.

---

### 2.8. Internal admin-users routes (`/api/internal/admin-users`)

Sama dengan `/api/public/admin-users` namun tanpa JWT and using `internal.user`.

---

### 2.9. Internal mitra-users routes (`/api/internal/mitra-users`)

Sama dengan `/api/public/mitra-users` namun tanpa JWT and using `internal.user`.

---

### 2.10. Authorization internal helpers

#### GET `/api/internal/users/{userId}/context`
- Path:
  - `userId` (string)

#### POST `/api/internal/permissions/check`
- Body:
  - `user_id` (nullable)
  - `menu_code` (nullable, string)
  - `menu_id` (nullable)
  - `action` (nullable, string)

#### POST `/api/internal/roles/check`
- Body:
  - `user_id` (nullable)
  - `roles` (required, array, min:1)
  - `roles.*` (required)

---

## 3. Catatan penting

- Semua `POST`, `PUT`, dan `PATCH` umumnya menerima payload JSON.
- Untuk route `upload-excel`, kemungkinan membutuhkan form-data dan file upload.
- Beberapa `confirmed` validation berarti field tambahan `*_confirmation` diperlukan.
- Semua route `internal` berada di bawah prefix `/api/internal`.
- Semua route `public/master`, `public/roles`, `public/settings`, `public/mitra`, `public/notif`, `public/notif-admin`, `public/admin-users`, dan `public/mitra-users` membutuhkan JWT auth.
