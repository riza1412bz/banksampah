<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Basis URL aset (Vite) mengikuti host & skema permintaan saat ini.
 *
 * Tanpa ini, Laravel membangun URL aset dari APP_URL (.env = http://localhost),
 * jadi saat aplikasi dibuka lewat tunnel HTTPS, CSS/JS dirender sebagai
 * http://localhost/... dan browser memblokir mixed content — halaman tampil
 * tanpa styling.
 */
class SinkronkanAppUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Cegah Host header palsu (host header injection) — hanya izinkan
        // host lokal atau subdomain tunnel trycloudflare.com.
        if ($host === 'localhost' || $host === '127.0.0.1' || str_ends_with($host, '.trycloudflare.com')) {
            config(['app.url' => $request->getScheme().'://'.$host]);
        }

        return $next($request);
    }
}
