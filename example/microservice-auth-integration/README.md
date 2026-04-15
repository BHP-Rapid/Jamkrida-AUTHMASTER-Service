## Microservice Auth Integration Example

Folder ini berisi template sederhana untuk microservice lain yang perlu:

- menerima JWT user dari request masuk
- memanggil auth service internal
- cek context user
- cek role
- cek permission

File yang disediakan:

- `bootstrap/app.php`
  Daftar alias middleware `auth.context`, `auth.role`, dan `auth.permission`
- `config/services.php`
  Konfigurasi URL auth service dan internal token
- `.env.example`
  Contoh environment variable yang perlu diisi
- `app/Services/AuthInternalClient.php`
  Client wrapper untuk memanggil endpoint `api/internal`
- `app/Http/Middleware/AuthenticateAuthContext.php`
  Middleware untuk ambil context user dari auth service
- `app/Http/Middleware/CheckAuthRole.php`
  Middleware untuk cek role user ke auth service
- `app/Http/Middleware/CheckAuthPermission.php`
  Middleware untuk cek permission user ke auth service
- `routes/api.php`
  Contoh route microservice `penjaminan`
- `REQUEST_FLOW.md`
  Contoh nyata pemanggilan `Http::` ke auth service

## Header Yang Dikirim Ke Auth Service

Saat microservice lain memanggil auth service internal, ada 2 header yang perlu dikirim:

1. `Authorization: Bearer <AUTH_INTERNAL_TOKEN>`
   Token antar-service dari `.env` microservice pemanggil.
2. `X-User-Token: <JWT_USER>`
   JWT user asli yang datang dari request masuk ke microservice pemanggil.

## Pisahkan 2 Flow Ini

Supaya tidak membingungkan, ada 2 flow yang berbeda:

1. `Login / refresh token flow`
   Ini terjadi antara client dan auth service.
2. `Internal authorization flow`
   Ini terjadi antara microservice lain dan auth service.

Folder example ini fokus ke flow nomor 2.

## Flow Yang Lebih Ideal Untuk Auth User

Kalau mau flow yang lebih rapi, saat login pertama kali auth service sebaiknya mengembalikan:

- `access_token` atau JWT yang short-lived
- `refresh_token` yang umur lebih panjang

Lalu saat `access_token` expired:

- client mengirim `refresh_token` ke auth service
- auth service mengembalikan `access_token` baru
- idealnya sekaligus rotate `refresh_token` baru juga

Penting:

- `refresh_token` dipakai hanya antara client dan auth service
- `refresh_token` tidak perlu diteruskan ke microservice lain
- microservice lain cukup menerima `access_token` user
- saat microservice lain memanggil auth service internal, yang diteruskan hanya `X-User-Token` berisi `access_token` user

## Contoh Route Di Microservice Lain

```php
use App\Http\Controllers\Api\PenjaminanController;
use Illuminate\Support\Facades\Route;

Route::prefix('penjaminan')->group(function (): void {
    Route::post('/create', [PenjaminanController::class, 'store'])
        ->middleware([
            'auth.context',
            'auth.role:admin,super_admin,admin_mitra',
            'auth.permission:PENJAMINAN,create',
        ]);
});
```

## Kapan Pakai File Ini

- pakai `REQUEST_FLOW.md` kalau tim integrasi butuh contoh request mentah `Http::`
- pakai `AuthInternalClient.php` kalau mau bungkus semua call ke auth service di satu service class
- pakai middleware contoh kalau mau proteksi route di microservice lain dengan pola yang rapi
