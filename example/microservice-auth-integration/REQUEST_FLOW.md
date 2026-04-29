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

### 4. GET data dengan where/filter

Untuk endpoint list, user tetap memanggil microservice lain memakai JWT user. Filter `where` dikirim sebagai query parameter ke microservice tersebut, bukan ke auth service.

Contoh route di microservice `penjaminan`:

```php
Route::get('/penjaminan', [PenjaminanController::class, 'index'])
    ->middleware([
        'auth.context',
        'auth.permission:PENJAMINAN,view',
    ]);
```

Contoh request dari client ke microservice:

```http
GET /api/penjaminan?where[status]=active&where[mitra_id]=145c9591-c7cf-45fe-a8c1-9f620f992d5d
Authorization: Bearer <ACCESS_TOKEN_USER>
Accept: application/json
```

Contoh membaca `where` di controller microservice:

```php
public function index(Request $request)
{
    $where = $request->query('where', []);

    $query = Penjaminan::query();

    if (! empty($where['status'])) {
        $query->where('status', $where['status']);
    }

    if (! empty($where['mitra_id'])) {
        $query->where('mitra_id', $where['mitra_id']);
    }

    return response()->json([
        'success' => true,
        'message' => 'Data penjaminan berhasil diambil.',
        'data' => $query->paginate((int) $request->query('per_page', 10)),
    ]);
}
```

Kalau microservice perlu call service lain lagi dengan query yang sama:

```php
$response = Http::withToken($accessTokenUser)
    ->acceptJson()
    ->get($penjaminanServiceUrl.'/api/penjaminan', [
        'where' => [
            'status' => 'active',
            'mitra_id' => '145c9591-c7cf-45fe-a8c1-9f620f992d5d',
        ],
        'page' => 1,
        'per_page' => 10,
    ]);
```

Catatan:

- auth service hanya dipakai untuk cek user/context/role/permission
- filter `where` diproses oleh microservice pemilik data
- permission untuk GET list biasanya cukup `auth.permission:PENJAMINAN,view`

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
        'actions' => ['edit', 'create'],
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
