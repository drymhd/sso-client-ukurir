<?php

namespace Saitrans\SsoClient\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSsoAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'     => 'Unauthenticated',
                    'login_url' => route('sso.login'),
                ], 401);
            }

            // Simpan URL tujuan agar setelah SSO selesai langsung diarahkan kembali ke halaman ini
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('sso.login');
        }

        return $next($request);
    }
}
