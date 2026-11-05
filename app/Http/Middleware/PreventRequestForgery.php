<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as BasePreventRequestForgery;
use Symfony\Component\HttpFoundation\Cookie;

class PreventRequestForgery extends BasePreventRequestForgery
{
    /**
     * Create a new "XSRF-TOKEN" cookie that contains the CSRF token with HttpOnly enabled.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $config
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function newCookie($request, $config)
    {
        return new Cookie(
            'XSRF-TOKEN',
            $request->session()->token(),
            $this->availableAt(60 * ($config['lifetime'] ?? 15)),
            $config['path'] ?? '/',
            $config['domain'] ?? null,
            $config['secure'] ?? true,
            true, // Enforce HttpOnly to eliminate OWASP ZAP / security scanner alerts
            false,
            $config['same_site'] ?? 'lax'
        );
    }
}
