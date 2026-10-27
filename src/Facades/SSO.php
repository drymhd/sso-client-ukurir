<?php

namespace Saitrans\SsoClient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getLoginUrl()
 * @method static array handleCallback(\Illuminate\Http\Request $request)
 * @method static array|null user()
 * @method static string|null token()
 *
 * @see \Saitrans\SsoClient\Services\SsoService
 */
class SSO extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'sso-client';
    }
}
