<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Store;

class CheckBannedVendor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only check for authenticated users
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user has a vendor role
            if ($user->hasRole('vendor')) {
                // Cache ban status for 5 minutes — avoids a Store query on every authenticated request
                $isBanned = Cache::remember('vendor_banned:' . $user->id, now()->addMinutes(5), function () use ($user) {
                    $store = Store::where('vendor_id', $user->id)->first();
                    return $store && $store->is_banned;
                });

                if ($isBanned) {
                    Auth::logout();
                    Cache::forget('vendor_banned:' . $user->id);
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect('/admin/login')
                        ->withErrors([
                            'email' => 'Your vendor account has been banned. Please contact support.'
                        ]);
                }
            }
        }

        return $next($request);
    }
}

