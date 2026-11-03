<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (function_exists('header_remove') && !headers_sent()) {
            @header_remove('X-Powered-By');
        }

        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'DENY');
            $response->header('X-XSS-Protection', '1; mode=block');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');
            // Content Security Policy with explicit img-src
            $response->header('Content-Security-Policy', "default-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.googleapis.com https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://ajax.googleapis.com https://psgc.gitlab.io data:; img-src 'self' data: blob: http: https:;");
        }

        if (method_exists($response, 'headers')) {
            $response->headers->remove('X-Powered-By');
            $response->headers->remove('Server');
        }

        return $response;
    }
}

