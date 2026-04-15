## Request Flow Internal Auth

Dokumen ini fokus ke contoh nyata saat microservice lain memanggil `api/internal` milik auth service.

## Catatan Penting

Flow di dokumen ini bukan flow login user.

Dokumen ini hanya membahas:

- bagaimana microservice lain meneruskan JWT user ke auth service
- bagaimana microservice lain cek context
- bagaimana microservice lain cek role
- bagaimana microservice lain cek permission

Kalau bicara flow auth yang lebih ideal, pisahannya seperti ini:

1. Saat login pertama kali, auth service mengembalikan:
   - `access_token` atau JWT dengan masa hidup pendek
   - `refresh_token` dengan masa hidup lebih panjang
2. Client memakai `access_token` untuk memanggil microservice lain.
3. Saat `access_token` expired, client memanggil endpoint refresh ke auth service memakai `refresh_token`.
4. Auth service mengembalikan `access_token` baru.
5. Microservice lain tidak ikut memegang `refresh_token` user untuk call internal sehari-hari.

Jadi untuk call internal:

- `Authorization: Bearer <AUTH_INTERNAL_TOKEN>` = identitas service
- `X-User-Token: <ACCESS_TOKEN_USER>` = identitas user

## Kasus Umum

Misalnya microservice `penjaminan` punya endpoint:

```php
Route::post('/penjaminan/create', [PenjaminanController::class, 'store'])
    ->middleware([
        'auth.context',
        'auth.role:admin,super_admin,admin_mitra',
        'auth.permission:PENJAMINAN,create',
    ]);
```

User memanggil microservice `penjaminan` dengan:

```http
Authorization: Bearer <ACCESS_TOKEN_USER>
```

Lalu microservice `penjaminan` akan memanggil auth service memakai:

```http
Authorization: Bearer <AUTH_INTERNAL_TOKEN>
X-User-Token: <ACCESS_TOKEN_USER>
```

## Contoh Nyata

### 1. Cek permission create

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.auth_internal.token'))
    ->withHeaders([
        'X-User-Token' => $accessTokenUser,
    ])
    ->post(rtrim(config('services.auth_internal.url'), '/') . '/api/internal/permissions/check', [
        'menu_code' => 'PENJAMINAN',
        'action' => 'create',
    ]);
```

Contoh response sukses:

```json
{
  "success": true,
  "message": "Permission check berhasil diproses.",
  "data": {
    "allowed": true,
    "action": "create",
    "menu_identifier": "PENJAMINAN",
    "user": {
      "user_id": "ADM24001",
      "role": "admin"
    }
  }
}
```

### 2. Cek role user

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.auth_internal.token'))
    ->withHeaders([
        'X-User-Token' => $accessTokenUser,
    ])
    ->post(rtrim(config('services.auth_internal.url'), '/') . '/api/internal/roles/check', [
        'roles' => ['admin', 'super_admin', 'admin_mitra'],
    ]);
```

Contoh response sukses:

```json
{
  "success": true,
  "message": "Role check berhasil diproses.",
  "data": {
    "allowed": true,
    "roles": ["admin", "super_admin", "admin_mitra"],
    "user": {
      "user_id": "ADM24001",
      "role": "admin"
    }
  }
}
```

### 3. Ambil context user

Kalau microservice lain sudah tahu `user_id`, bisa langsung panggil seperti ini:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.auth_internal.token'))
    ->withHeaders([
        'X-User-Token' => $accessTokenUser,
    ])
    ->get(rtrim(config('services.auth_internal.url'), '/') . '/api/internal/users/ADM24001/context');
```

Kalau `user_id` belum ada, biasanya microservice lain decode payload JWT dulu atau pakai helper seperti `AuthInternalClient`.

Contoh response sukses:

```json
{
  "success": true,
  "message": "User context berhasil diambil.",
  "data": {
    "user_id": "ADM24001",
    "name": "Admin Portal",
    "email": "admin@example.com",
    "role": "admin",
    "role_id": 3,
    "role_code": "ADMIN",
    "mitra_id": "JMKRD",
    "status": "active"
  }
}
```

## Contoh Di Dalam Middleware Microservice Lain

### AuthenticateAuthContext

```php
$response = Http::withToken(config('services.auth_internal.token'))
    ->withHeaders([
        'X-User-Token' => $userToken,
    ])
    ->get(rtrim(config('services.auth_internal.url'), '/') . "/api/internal/users/{$userId}/context");

$context = $response->throw()->json();

$request->attributes->set('auth_user', $context['data'] ?? null);
```

### CheckAuthRole

```php
$response = Http::withToken(config('services.auth_internal.token'))
    ->withHeaders([
        'X-User-Token' => $userToken,
    ])
    ->post(rtrim(config('services.auth_internal.url'), '/') . '/api/internal/roles/check', [
        'roles' => ['admin', 'super_admin'],
    ]);

$result = $response->throw()->json();

if (!($result['data']['allowed'] ?? false)) {
    return response()->json([
        'success' => false,
        'message' => 'Forbidden: insufficient role.',
    ], 403);
}
```

### CheckAuthPermission

```php
$response = Http::withToken(config('services.auth_internal.token'))
    ->withHeaders([
        'X-User-Token' => $userToken,
    ])
    ->post(rtrim(config('services.auth_internal.url'), '/') . '/api/internal/permissions/check', [
        'menu_code' => 'PENJAMINAN',
        'action' => 'create',
    ]);

$result = $response->throw()->json();

if (!($result['data']['allowed'] ?? false)) {
    return response()->json([
        'success' => false,
        'message' => 'Forbidden: insufficient permission.',
    ], 403);
}
```

## Ringkasnya

- request user ke microservice lain membawa JWT user
- microservice lain meneruskan access token user ke auth service lewat `X-User-Token`
- microservice lain mengirim token antar-service lewat header `Authorization`
- auth service melakukan validasi user, role, dan permission
- hasilnya dipakai oleh middleware di microservice lain
