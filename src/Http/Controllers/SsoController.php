<?php

namespace Saitrans\SsoClient\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Saitrans\SsoClient\Facades\SSO;

class SsoController extends Controller
{
    /**
     * Redirect pengguna ke portal login SSO pusat.
     */
    public function login()
    {
        return redirect()->away(SSO::getLoginUrl());
    }

    /**
     * Tangkap callback dan token dari SSO server.
     */
    public function callback(Request $request)
    {
        try {
            $result = SSO::handleCallback($request);
            $localUser = $result['user'];

            // 1. Dukungan API JSON Response (Jika request API / Postman / Mobile / Axios)
            if ($request->wantsJson() || $request->is('api/*')) {
                $apiToken = method_exists($localUser, 'createToken')
                    ? $localUser->createToken('SSO Client Token')->accessToken
                    : null;

                return response()->json([
                    'success'      => true,
                    'message'      => 'Berhasil login melalui Single Sign-On (SSO).',
                    'user'         => $localUser,
                    'access_token' => $apiToken,
                    'sso_payload'  => $result['payload'] ?? null,
                ], 200);
            }

            // 2. Dukungan Redirect ke Frontend SPA (Vue / React / Nuxt / Vite)
            if ($frontendUrl = config('sso-client.frontend_url')) {
                $apiToken = method_exists($localUser, 'createToken')
                    ? $localUser->createToken('SSO Frontend Token')->accessToken
                    : ($result['payload']['token'] ?? '');

                $separator = str_contains($frontendUrl, '?') ? '&' : '?';
                return redirect()->away("{$frontendUrl}{$separator}token={$apiToken}");
            }

            // 3. Fallback Monolith Web: Arahkan ke URL intended atau rute default
            $redirectTarget = config('sso-client.redirect_route', 'home');

            if (\Illuminate\Support\Facades\Route::has($redirectTarget)) {
                return redirect()->intended(route($redirectTarget))
                    ->with('success', 'Berhasil login melalui Single Sign-On (SSO).');
            }

            return redirect()->intended($redirectTarget)
                ->with('success', 'Berhasil login melalui Single Sign-On (SSO).');

        } catch (Exception $e) {
            // Jika request berupa API JSON
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal otentikasi SSO: ' . $e->getMessage(),
                ], 401);
            }

            // Safe route redirection: cek apakah rute 'login' ada sebelum redirect
            if (\Illuminate\Support\Facades\Route::has('login')) {
                return redirect()->route('login')
                    ->with('error', 'Gagal otentikasi SSO: ' . $e->getMessage());
            }

            return redirect('/')
                ->with('error', 'Gagal otentikasi SSO: ' . $e->getMessage());
        }
    }

    /**
     * Logout sesi lokal aplikasi anak.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Anda telah berhasil keluar.');
    }
}
