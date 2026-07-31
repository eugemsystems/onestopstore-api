<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageProxyController extends Controller
{
    /**
     * Proxy external images to avoid CORS issues
     */
    public function proxy(Request $request)
    {
        $url = $request->query('url');

        if (!$url) {
            return response('No URL provided', 400);
        }

        // Prevent recursive proxy calls (proxy calling itself)
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';
        $path = $parsedUrl['path'] ?? '';

        if (str_contains($path, 'proxy-image') || str_contains($url, 'proxy-image')) {
            Log::warning('Image proxy: blocked recursive proxy call', ['url' => $url]);
            return response('Recursive proxy not allowed', 400);
        }

        // Validate URL is from allowed domains
        $allowedDomains = [
            'media.takealot.com',
            'media.raines.africa',
            'localhost',
        ];

        $allowed = false;
        foreach ($allowedDomains as $domain) {
            if (str_contains($host, $domain)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            return response('Domain not allowed', 403);
        }

        // Use a cache key based on the URL to avoid re-fetching frequently
        $cacheKey = 'proxy_img_' . md5($url);

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response($cached['body'])
                ->header('Content-Type', $cached['content_type'])
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET')
                ->header('Cache-Control', 'public, max-age=86400');
        }

        try {
            // Fetch the image with a longer timeout and connection timeout
            $response = Http::withOptions([
                'connect_timeout' => 10,
                'timeout'         => 30,
            ])->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; RainesProxy/1.0)',
            ])->get($url);

            if (!$response->successful()) {
                Log::warning('Image proxy: upstream returned ' . $response->status(), ['url' => $url]);
                return response('Failed to fetch image', $response->status());
            }

            $body = $response->body();
            $contentType = $response->header('Content-Type') ?: 'image/jpeg';

            // Cache the image for 1 hour to reduce repeated external calls
            Cache::put($cacheKey, [
                'body'         => $body,
                'content_type' => $contentType,
            ], now()->addHour());

            // Return image with CORS headers
            return response($body)
                ->header('Content-Type', $contentType)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET')
                ->header('Cache-Control', 'public, max-age=86400');
        } catch (\Exception $e) {
            Log::error('Image proxy error: ' . $e->getMessage());

            // Return a 1x1 transparent pixel as fallback so the browser doesn't keep retrying
            $transparentPixel = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
            );

            return response($transparentPixel)
                ->header('Content-Type', 'image/png')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Cache-Control', 'public, max-age=300'); // Cache error fallback for 5 min
        }
    }
}

