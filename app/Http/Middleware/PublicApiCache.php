<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PublicApiCache
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only cache successful GET/HEAD
        if (in_array($request->method(), ['GET','HEAD']) && $response->isSuccessful()) {
            // 10 minutes public cache, adjust per route as needed
            $response->headers->set('Cache-Control', 'public, max-age=600, s-maxage=600, stale-while-revalidate=60');
            // Optional: add a weak ETag from the body
            if (!$response->headers->has('ETag') && method_exists($response, 'getContent')) {
                $etag = 'W/"'.substr(sha1($response->getContent()), 0, 16).'"';
                $response->headers->set('ETag', $etag);
            }
        }

        return $response;
    }
}
