# 🚀 SAI & uKurir SSO Client SDK for Laravel (`drymhd/sso-client-ukurir`)

Package resmi Laravel untuk integrasi instan ke server **SAI SSO (Single Sign-On)** ekosistem PT Sumber Alfaria Trijaya & uKurir.

---

## ✨ Fitur Unggulan

- **Zero Configuration Setup**: Cukup jalankan satu perintah `php artisan sso:install`.
- **Auto-Discovery**: Service provider, facade `SSO`, dan middleware terdaftar otomatis.
- **Just-In-Time (JIT) Auto-Provisioning**: Pengguna baru otomatis dibuatkan akun di database aplikasi anak berdasarkan email SSO tanpa perlu input manual di dashboard.
- **Siklus Hidup Token Terproteksi**: Validasi otomatis batas kedaluwarsa JWT (`exp: 5m`), nonce anti-replay attack, dan tanda tangan enkripsi.
- **Seamless Silent Login**: Middleware `sso.auth` otomatis mengarahkan pengunjung belum login ke portal SSO dan mengembalikannya ke URL tujuan asal (*intended URL*).

---

## 📥 Panduan Instalasi di Aplikasi Anak (LMS, Kargo, Finansial, dll.)

### Langkah 1: Tambahkan Repository di `composer.json` Aplikasi Anak

Tambahkan repository GitHub ini di file `composer.json` aplikasi anak:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/drymhd/sso-client-ukurir.git"
    }
]
```

Kemudian jalankan:
```bash
composer require drymhd/sso-client-ukurir
```

---

### Langkah 2: Jalankan Perintah Instalasi Otomatis

Jalankan perintah interaktif berikut di terminal aplikasi anak:
```bash
php artisan sso:install
```

Perintah ini akan secara otomatis:
1. Menerbitkan file `config/sso-client.php`.
2. Mengisi variabel environment di file `.env`:
   ```env
   SSO_SERVER_URL=https://sso.saitrans.id
   SSO_APP_KEY=app_6745...
   SSO_CLIENT_SECRET=kuncirahasiaoauth...
   SSO_REDIRECT_ROUTE=home
   SSO_AUTO_PROVISION=true
   ```

---

## 🛠️ Penggunaan

### 1. Proteksi Rute Aplikasi Anak
Gunakan middleware `sso.auth` pada rute atau grup rute yang membutuhkan login SSO:

```php
// routes/web.php aplikasi anak
Route::middleware(['sso.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
});
```

*Jika pengguna belum login, middleware ini otomatis mengarahkan ke SSO Server dan kembali ke halaman yang dituju setelah login berhasil.*

---

### 2. Tombol Login Manual (Opsional)
Jika aplikasi anak menyediakan tombol login SSO:
```html
<a href="{{ route('sso.login') }}" class="btn btn-primary">
    Masuk dengan Akun SAI SSO
</a>
```

---

### 3. Mengakses Data Pengguna SSO
Anda dapat mengakses profil SSO pengguna yang sedang aktif melalui Facade `SSO`:

```php
use Saitrans\SsoClient\Facades\SSO;

// Mengambil profil data SSO
$ssoUser = SSO::user();
// Format data:
// [
//     'id'    => 1,
//     'name'  => 'Budi Santoso',
//     'email' => 'budi@saitrans.id',
//     'level' => 'admin'
// ]

// Mengambil Passport Access Token (jika ingin request ke API SSO)
$accessToken = SSO::token();
```

---

### 4. Tombol Logout
Package menyediakan endpoint logout terpadu:
```html
<a href="{{ route('sso.logout') }}">Keluar</a>
```

---

## ⚙️ Konfigurasi Lanjutan (`config/sso-client.php`)

```php
return [
    'server_url'     => env('SSO_SERVER_URL', 'https://sso.saitrans.id'),
    'app_key'        => env('SSO_APP_KEY', ''),
    'client_secret'  => env('SSO_CLIENT_SECRET', ''),
    'redirect_route' => env('SSO_REDIRECT_ROUTE', 'home'),
    'auto_provision' => env('SSO_AUTO_PROVISION', true),
    'user_model'     => env('SSO_USER_MODEL', App\Models\User::class),
    'routes' => [
        'enabled'    => true,
        'prefix'     => 'sso',
        'middleware' => ['web'],
    ],
];
```

---

## 📄 Lisensi
Hak Cipta © PT Sumber Alfaria Trijaya. Lisensi MIT.
