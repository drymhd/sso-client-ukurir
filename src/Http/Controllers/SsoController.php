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

            // Arahkan ke URL intended (yang awalnya ingin dibuka) atau rute default
            $redirectTarget = config('sso-client.redirect_route', 'home');

            if (\Illuminate\Support\Facades\Route::has($redirectTarget)) {
                return redirect()->intended(route($redirectTarget))
                    ->with('success', 'Berhasil login melalui Single Sign-On (SSO).');
            }

            return redirect()->intended($redirectTarget)
                ->with('success', 'Berhasil login melalui Single Sign-On (SSO).');

        } catch (Exception $e) {
            return redirect()->route('login')
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
