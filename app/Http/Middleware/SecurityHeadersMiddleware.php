<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (function_exists('header_remove') && !headers_sent()) {
            @header_remove('X-Powered-By');
        }

        $response = $next($request);

        // Clear redirect response body to prevent scanner alerts regarding big redirect bodies (OWASP CWE-201)
        if (method_exists($response, 'isRedirection') && $response->isRedirection()) {
            $response->setContent('');
        }

        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'DENY');
            $response->header('X-XSS-Protection', '1; mode=block');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

            // Cache-Control: prevent caching sensitive dynamic pages & authentication responses
            if (!$request->is('images/*', 'css/*', 'js/*', '*.png', '*.jpg', '*.ico')) {
                $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->header('Pragma', 'no-cache');
            }

            // Robust Content-Security-Policy with granular directives
            // - Defines frame-ancestors and form-action to prevent clickjacking & form hijacking
            // - Restricts img-src to self, data, and blob (no wildcards)
            // - Removes unsafe-eval completely
            $cspDirectives = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://ajax.googleapis.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
                "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
                "img-src 'self' data: blob:",
                "connect-src 'self' https://psgc.gitlab.io",
                "frame-ancestors 'none'",
                "form-action 'self'",
                "base-uri 'self'",
                "object-src 'none'",
                "upgrade-insecure-requests",
            ];
            $response->header('Content-Security-Policy', implode('; ', $cspDirectives) . ';');
        }

        if (method_exists($response, 'headers')) {
            $response->headers->remove('X-Powered-By');
            $response->headers->remove('Server');
        }

        return $response;
    }
}

