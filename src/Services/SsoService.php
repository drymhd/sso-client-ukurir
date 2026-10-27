<?php

namespace Saitrans\SsoClient\Services;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SsoService
{
    /**
     * Dapatkan URL redirect untuk login ke portal SSO pusat.
     *
     * @return string
     */
    public function getLoginUrl(): string
    {
        $serverUrl = rtrim(config('sso-client.server_url', 'http://localhost:8000'), '/');
        $appKey = config('sso-client.app_key');

        return "{$serverUrl}/auth/sso?app_key=" . urlencode($appKey);
    }

    /**
     * Proses token callback dari SSO, validasi payload, dan login-kan pengguna.
     *
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function handleCallback(Request $request): array
    {
        $token = $request->query('token');

        if (empty($token)) {
            throw new Exception('Token SSO tidak ditemukan pada request callback.');
        }

        $secret = config('sso-client.client_secret');
        if (empty($secret)) {
            throw new Exception('Konfigurasi SSO_CLIENT_SECRET belum diisi pada file .env.');
        }

        // 1. Decode & verifikasi signature JWT
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Exception $e) {
            throw new Exception('Validasi Token SSO Gagal: ' . $e->getMessage());
        }

        $payload = (array) $decoded;

        // 2. Validasi Expiration Time (jika ada klaim exp)
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Sesi Token SSO telah kedaluwarsa. Silakan login kembali.');
        }

        // 3. Ekstrak data user dari payload (Mendukung format 3.0 dan legacy 2.0)
        $userData = $this->extractUserData($payload);

        // 4. Resolve / Auto-provision user ke database lokal aplikasi anak
        $localUser = $this->resolveLocalUser($userData, $payload);

        // 5. Login pengguna ke sesi lokal Laravel
        Auth::login($localUser, true);

        // Simpan token akses & payload SSO ke session aplikasi anak untuk integrasi lanjutan
        session([
            'sso_access_token' => $payload['token'] ?? null,
            'sso_payload'      => $payload,
            'sso_user'         => $userData,
        ]);

        return [
            'user'    => $localUser,
            'payload' => $payload,
        ];
    }

    /**
     * Ekstrak data user dari payload SSO secara aman.
     *
     * @param array $payload
     * @return array
     */
    protected function extractUserData(array $payload): array
    {
        if (isset($payload['user']) && is_object($payload['user'])) {
            $userObj = (array) $payload['user'];
            return [
                'id'    => $userObj['id'] ?? null,
                'name'  => $userObj['name'] ?? 'Pengguna SSO',
                'email' => $userObj['email'] ?? null,
                'level' => $userObj['level'] ?? 'user',
            ];
        }

        if (isset($payload['user']) && is_array($payload['user'])) {
            return [
                'id'    => $payload['user']['id'] ?? null,
                'name'  => $payload['user']['name'] ?? 'Pengguna SSO',
                'email' => $payload['user']['email'] ?? null,
                'level' => $payload['user']['level'] ?? 'user',
            ];
        }

        // Fallback jika SSO versi legacy
        return [
            'id'    => $payload['ref'] ?? null,
            'name'  => 'Pengguna SSO (' . ($payload['app'] ?? 'App') . ')',
            'email' => null,
            'level' => 'user',
        ];
    }

    /**
     * Cari user di database lokal berdasarkan email atau ref ID.
     * Jika tidak ditemukan dan auto_provision aktif, otomatis buatkan record baru (JIT).
     *
     * @param array $userData
     * @param array $payload
     * @return mixed
     * @throws Exception
     */
    protected function resolveLocalUser(array $userData, array $payload)
    {
        $userModelClass = config('sso-client.user_model', 'App\\Models\\User');

        if (!class_exists($userModelClass)) {
            throw new Exception("Model User '{$userModelClass}' tidak ditemukan di aplikasi ini.");
        }

        $userModel = new $userModelClass();
        $user = null;

        // Cari berdasarkan Email (Prioritas Utama untuk Universal ID)
        if (!empty($userData['email'])) {
            $user = $userModel->where('email', $userData['email'])->first();
        }

        // Fallback: Cari berdasarkan primary key (ref_user_id) jika ada
        if (!$user && !empty($payload['ref'])) {
            $user = $userModel->find($payload['ref']);
        }

        // Jika user belum ada dan auto_provision aktif -> Buatkan user baru (JIT)
        if (!$user) {
            if (!config('sso-client.auto_provision', true)) {
                throw new Exception('Akun belum terdaftar di aplikasi ini dan auto-provisioning dinonaktifkan.');
            }

            if (empty($userData['email'])) {
                throw new Exception('Email pengguna tidak ditemukan dalam token SSO untuk auto-provisioning.');
            }

            $user = $userModel->create([
                'name'     => $userData['name'],
                'email'    => $userData['email'],
                'password' => Hash::make(Str::random(32)), // Random password terenkripsi aman
            ]);
        } else {
            // Update nama jika ada perubahan di portal pusat
            if (!empty($userData['name']) && $user->name !== $userData['name']) {
                $user->name = $userData['name'];
                $user->save();
            }
        }

        return $user;
    }

    /**
     * Dapatkan profil user SSO dari sesi aktif.
     *
     * @return array|null
     */
    public function user(): ?array
    {
        return session('sso_user');
    }

    /**
     * Dapatkan access token bearer Passport dari sesi aktif.
     *
     * @return string|null
     */
    public function token(): ?string
    {
        return session('sso_access_token');
    }
}
