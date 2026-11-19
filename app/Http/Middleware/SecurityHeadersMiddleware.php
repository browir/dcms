<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and attach standard security headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Cegah Clickjacking — Hanya izinkan frame dari domain yang sama (untuk preview PDF modal internal)
        if (! $response->headers->has('X-Frame-Options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        // 2. Cegah MIME-Type Sniffing
        if (! $response->headers->has('X-Content-Type-Options')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        // 3. Kebijakan Referrer — Jaga privasi URL internal saat mengklik link keluar
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        // 4. Batasi akses perangkat keras/sensor browser yang tidak dibutuhkan
        if (! $response->headers->has('Permissions-Policy')) {
            $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        }

        // 5. HSTS (Strict-Transport-Security) — Hanya dipasang pada koneksi HTTPS agar tidak mengganggu localhost/HTTP lokal
        if ($request->isSecure() && ! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // 6. Content-Security-Policy (CSP) — Dioptimalkan agar ramah Livewire 3, Alpine.js, dan PDF blob
        if (! $response->headers->has('Content-Security-Policy')) {
            $csp = implode('; ', [
                "default-src 'self' 'unsafe-inline' 'unsafe-eval' data: blob:",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: blob:",
                "style-src 'self' 'unsafe-inline' https:",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data: https:",
                "frame-src 'self' blob:",
                "object-src 'self' blob:",
                "connect-src 'self' https: wss:",
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}

