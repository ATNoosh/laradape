<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('jobs.http.api_key');
        $provided = (string) $request->header('X-API-KEY');

        if ($expected === '' || $expected === 'null') {
            return $next($request);
        }

        if (! hash_equals($expected, $provided)) {
            return response()->json(ApiResponse::error(
                new ErrorResponse(
                    code: 'unauthorized',
                    message: 'Invalid API key'
                )
            ), 401);
        }

        return $next($request);
    }
}
