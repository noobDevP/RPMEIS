<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (function_exists('header_remove') && !headers_sent()) {
            @header_remove('X-Powered-By');
        }

        // Generate a cryptographically secure 16-byte random nonce per HTTP response
        $nonce = base64_encode(random_bytes(16));
        $request->attributes->set('cspNonce', $nonce);
        app()->instance('cspNonce', $nonce);
        View::share('cspNonce', $nonce);

        $response = $next($request);

        // Clear redirect response body to prevent scanner alerts regarding big redirect bodies (OWASP CWE-201)
        if (method_exists($response, 'isRedirection') && $response->isRedirection()) {
            $response->setContent('');
        }

        // For HTML responses, inject the CSP nonce onto any <script> and <style> tags
        if (isset($response->headers) && method_exists($response, 'getContent') && method_exists($response, 'setContent')) {
            $contentType = $response->headers->get('Content-Type', '');
            if ($contentType === '' || stripos($contentType, 'text/html') !== false) {
                $content = $response->getContent();
                if ($content && is_string($content) && !empty($content)) {
                    $content = preg_replace_callback(
                        '/<(script|style)\b([^>]*)>/i',
                        function ($matches) use ($nonce) {
                            $tag = $matches[1];
                            $attrs = $matches[2];
                            if (preg_match('/\bnonce\s*=/i', $attrs)) {
                                return $matches[0];
                            }
                            return "<{$tag} nonce=\"{$nonce}\"{$attrs}>";
                        },
                        $content
                    );
                    $response->setContent($content);
                }
            }
        }

        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'DENY');
            $response->header('X-XSS-Protection', '1; mode=block');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

            if (!$request->is('images/*', 'css/*', 'js/*', '*.png', '*.jpg', '*.ico')) {
                $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->header('Pragma', 'no-cache');
            }

            // Strict CSP Baseline (No unsafe-inline in script-src/style-src, No wildcards, Full fallback directives)
            $cspDirectives = [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "script-src-attr 'unsafe-inline'",
                "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
                "style-src-attr 'unsafe-inline'",
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

        if (isset($response->headers)) {
            $response->headers->remove('X-Powered-By');
            $response->headers->remove('Server');

            if ($response->headers instanceof \Symfony\Component\HttpFoundation\ResponseHeaderBag) {
                foreach ($response->headers->getCookies() as $cookie) {
                    if (!$cookie->isHttpOnly()) {
                        $response->headers->setCookie(
                            new \Symfony\Component\HttpFoundation\Cookie(
                                $cookie->getName(),
                                $cookie->getValue(),
                                $cookie->getExpiresTime(),
                                $cookie->getPath(),
                                $cookie->getDomain(),
                                $cookie->isSecure(),
                                true,
                                $cookie->isRaw(),
                                $cookie->getSameSite()
                            )
                        );
                    }
                }
            }
        }

        return $response;
    }
}
