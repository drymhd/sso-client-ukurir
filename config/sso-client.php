<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SAI SSO Server Base URL
    |--------------------------------------------------------------------------
    | URL domain server pusat Single Sign-On (contoh: https://sso.saitrans.id)
    */
    'server_url' => env('SSO_SERVER_URL', 'http://localhost:8000'),

    /*
    |--------------------------------------------------------------------------
    | SSO Application Key
    |--------------------------------------------------------------------------
    | Kunci unik aplikasi (App Key) yang terdaftar di portal SSO.
    */
    'app_key' => env('SSO_APP_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | OAuth Client Secret
    |--------------------------------------------------------------------------
    | Secret key bersama (shared secret) untuk memvalidasi tanda tangan JWT.
    */
    'client_secret' => env('SSO_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Post-Login Redirect Route or URL
    |--------------------------------------------------------------------------
    | Rute atau URL tujuan di aplikasi anak setelah pengguna berhasil login.
    */
    'redirect_route' => env('SSO_REDIRECT_ROUTE', 'home'),

    /*
    |--------------------------------------------------------------------------
    | Just-In-Time (JIT) User Auto-Provisioning
    |--------------------------------------------------------------------------
    | Jika true: Akun pengguna yang belum terdaftar di database aplikasi anak
    | akan dibuatkan otomatis menggunakan data (nama & email) dari SSO.
    */
    'auto_provision' => env('SSO_AUTO_PROVISION', true),

    /*
    |--------------------------------------------------------------------------
    | Target User Model
    |--------------------------------------------------------------------------
    | Model Eloquent User yang digunakan oleh aplikasi anak untuk login sesi.
    */
    'user_model' => env('SSO_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Routing Options
    |--------------------------------------------------------------------------
    | Mengaktifkan rute otomatis /sso/login dan /sso/callback bawaan package.
    */
    'routes' => [
        'enabled' => true,
        'prefix'  => 'sso',
        'middleware' => ['web'],
    ],
];
