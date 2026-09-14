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
            if ($locale) {
                app()->setLocale($locale);
            }
        }

        return $next($request);
    }

    /**
     * Parse the Accept-Language header to get the primary locale.
     *
     * Returns null (caller keeps the app's configured default) for anything
     * that isn't a plain two-letter language code — most notably "*", a
     * spec-valid Accept-Language value some HTTP clients send meaning "any
     * language is fine", which crashed here: substr('*', 0, 2) is just '*',
     * and passing that straight to app()->setLocale() blew up Carbon's
     * translator setup ("Invalid \"*\" locale") on every single request
     * that sent it — including every checkout submission, regardless of
     * which payment method was selected.
     *
     * @param  string  $acceptLanguage
     * @return string|null
     */
    protected function parseLocale($acceptLanguage)
    {
        $locales = explode(',', $acceptLanguage);
        $locale = strtolower(substr(trim($locales[0]), 0, 2));

        return preg_match('/^[a-z]{2}$/', $locale) ? $locale : null;
    }
}
