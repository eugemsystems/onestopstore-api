<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasHeader("Accept-Language")) {
            $locale = $this->parseLocale($request->header("Accept-Language"));
            app()->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Parse the Accept-Language header to get the primary locale.
     *
     * @param  string  $acceptLanguage
     * @return string
     */
    protected function parseLocale($acceptLanguage)
    {
        $locales = explode(',', $acceptLanguage);
        return strtolower(substr($locales[0], 0, 2));
    }
}
