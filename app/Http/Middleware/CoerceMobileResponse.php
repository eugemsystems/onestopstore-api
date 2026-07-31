<?php

namespace App\Http\Middleware;

use App\Helpers\Helpers;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rewrites a few product fields for the mobile app only, without touching the
 * database or the web/React responses.
 *
 * The mobile app parses `standard_shipping_days` / `expedited_shipping_days`
 * with an int parser, but the catalog stores them as range strings like
 * "7-14" / "3-5" — which the app can't parse, so it renders "null Days".
 * Here we collapse the range to its first integer ("7-14" -> 7) for mobile
 * clients, so it shows "7 Days" instead of "null Days".
 */
class CoerceMobileResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! Helpers::isMobileClient($request->userAgent())) {
            return $response;
        }

        $content = $response->getContent();
        // Cheap guard: only pay the decode cost when a target key is present.
        if ($content === false || strpos($content, 'shipping_days') === false) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            if (is_array($data)) {
                $this->coerce($data);
                $response->setData($data);
            }
            return $response;
        }

        $ct = (string) $response->headers->get('Content-Type', '');
        if (strpos($ct, 'application/json') === false) {
            return $response;
        }
        $data = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $this->coerce($data);
            $response->setContent(json_encode($data));
        }
        return $response;
    }

    private function coerce(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->coerce($value);
            } elseif (
                ($key === 'standard_shipping_days' || $key === 'expedited_shipping_days')
                && is_string($value)
                && preg_match('/\d+/', $value, $m)
            ) {
                $value = (int) $m[0];
            }
        }
        unset($value);
    }
}
