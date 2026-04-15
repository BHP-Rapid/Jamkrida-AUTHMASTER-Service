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
