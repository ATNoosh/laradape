<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class IdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header('Idempotency-Key');

        if ($key === '') {
            return $next($request);
        }

        $cacheKey = $this->cacheKey($key);
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && isset($cached['status'], $cached['body'])) {
            return response($cached['body'], $cached['status'], $cached['headers'] ?? []);
        }

        /** @var Response $response */
        $response = $next($request);

        Cache::put($cacheKey, [
            'status' => $response->getStatusCode(),
            'body' => $response->getContent(),
            'headers' => [
                'Content-Type' => $response->headers->get('Content-Type'),
            ],
        ], $this->ttlSeconds());

        return $response;
    }

    private function cacheKey(string $key): string
    {
        return 'http:idempotency:' . sha1($key);
    }

    private function ttlSeconds(): int
    {
        return (int) config('jobs.http.idempotency_ttl_seconds', 300);
    }
}
