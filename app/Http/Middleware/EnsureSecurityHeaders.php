<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $viteDevOrigin = $this->viteDevOrigin();
        $styleSources = ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', 'https://unpkg.com'];
        $scriptSources = ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://unpkg.com'];
        $fontSources = ["'self'", 'data:', 'https://fonts.gstatic.com'];
        $connectSources = ["'self'"];

        if ($viteDevOrigin !== null) {
            $styleSources[] = $viteDevOrigin;
            $scriptSources[] = $viteDevOrigin;
            $connectSources[] = $viteDevOrigin;

            $viteWebsocketOrigin = preg_replace('/^http/i', 'ws', $viteDevOrigin);

            if (is_string($viteWebsocketOrigin)) {
                $connectSources[] = $viteWebsocketOrigin;
            }
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self)');
        $response->headers->set(
            'Content-Security-Policy',
            sprintf(
                "default-src 'self'; img-src 'self' data: https:; style-src %s; script-src %s; font-src %s; connect-src %s; frame-ancestors 'self'; base-uri 'self'; form-action 'self';",
                implode(' ', array_unique($styleSources)),
                implode(' ', array_unique($scriptSources)),
                implode(' ', array_unique($fontSources)),
                implode(' ', array_unique($connectSources)),
            )
        );

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function viteDevOrigin(): ?string
    {
        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return null;
        }

        $hotUrl = trim((string) file_get_contents($hotFile));

        if ($hotUrl === '') {
            return null;
        }

        $scheme = parse_url($hotUrl, PHP_URL_SCHEME);
        $host = parse_url($hotUrl, PHP_URL_HOST);
        $port = parse_url($hotUrl, PHP_URL_PORT);

        if (! is_string($scheme) || ! is_string($host)) {
            return null;
        }

        return $port !== null
            ? sprintf('%s://%s:%d', $scheme, $host, $port)
            : sprintf('%s://%s', $scheme, $host);
    }
}
