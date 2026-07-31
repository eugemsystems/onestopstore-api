<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Matchish\ScoutElasticSearch\Engines\ElasticSearchEngine;
use Opcodes\LogViewer\Facades\LogViewer;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Cookie;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Debugbar', \Barryvdh\Debugbar\Facades\Debugbar::class);


        $this->app->make(EngineManager::class)->extend('elasticsearch', function () {
            $clientBuilder = ClientBuilder::create()
                ->setHosts([env('ELASTICSEARCH_HOST', 'localhost:9200')]);

            // Add authentication if credentials are provided
            // Check both ELASTICSEARCH_USERNAME and ELASTICSEARCH_USER (for compatibility)
            $username = env('ELASTICSEARCH_USERNAME') ?? env('ELASTICSEARCH_USER');
            $password = env('ELASTICSEARCH_PASSWORD');
            $apiKey = env('ELASTICSEARCH_API_KEY');
            $cloudId = env('ELASTICSEARCH_CLOUD_ID');

            if ($cloudId) {
                // Elastic Cloud configuration
                $clientBuilder->setElasticCloudId($cloudId);
            }

            if ($apiKey) {
                // API Key authentication (preferred for Elastic Cloud)
                $clientBuilder->setApiKey($apiKey);
            } elseif ($username && $password) {
                // Basic authentication
                $clientBuilder->setBasicAuthentication($username, $password);
            } else {
                \Log::warning("Elasticsearch credentials not found! Jobs will fail with 401 error.");
            }

            return new ElasticsearchEngine($clientBuilder->build());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Use Bootstrap 5 for pagination views
        \Illuminate\Pagination\Paginator::useBootstrapFive();

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Override the verification URL so it points at the React frontend instead
        // of the Laravel backend (which has no web session and would 405 on /login).
        // The frontend /verify-email page reads these params and calls GET /api/email/verify/{id}/{hash}
        VerifyEmail::createUrlUsing(function (object $notifiable) {
            $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
            $id          = $notifiable->getKey();
            $hash        = sha1($notifiable->getEmailForVerification());

            // Generate a signed API URL so the backend can validate it
            $signedRoute  = URL::temporarySignedRoute(
                'api.verification.verify',
                now()->addMinutes(60),
                compact('id', 'hash')
            );

            // Parse just expires + signature from the signed route query string
            parse_str(parse_url($signedRoute, PHP_URL_QUERY), $params);

            return "{$frontendUrl}/en/verify-email?" . http_build_query([
                'id'        => $id,
                'hash'      => $hash,
                'expires'   => $params['expires']   ?? '',
                'signature' => $params['signature'] ?? '',
            ]);
        });


        require_once app_path('Helpers/CachedData.php');

        // Register custom Blade directives for cached permission checks
        $this->registerCachedPermissionBladeDirectives();

        RateLimiter::for('images', function () {
            return [
                Limit::perMinute(60)->by('images'),    // steady rate
                Limit::perMinute(100)->by('images-burst') // small burst capacity
            ];
        });

        // Rate limiter for analytics endpoints
        RateLimiter::for('analytics', function ($request) {
            // Use session_id if available, otherwise fall back to IP
            $key = $request->input('session_id') ?? $request->ip();
            return Limit::perMinute(120)->by('analytics:' . $key);
        });

        Order::observe(OrderObserver::class);
        Order::observe(\App\Observers\OrderVoucherObserver::class);

        // Register Product observer for automatic Elasticsearch reindexing
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);

        // Register Category observer for cache invalidation
        \App\Models\Category::observe(\App\Observers\CategoryObserver::class);

        // Register Tag observer for cache invalidation
        \App\Models\Tag::observe(\App\Observers\TagObserver::class);

        // Register Brand observer for cache invalidation
        \App\Models\Brand::observe(\App\Observers\BrandObserver::class);

        // Register Tax observer for cache invalidation
        \App\Models\Tax::observe(\App\Observers\TaxObserver::class);

        // Register Role and Permission observers for cache invalidation
        \Spatie\Permission\Models\Role::observe(\App\Observers\RoleObserver::class);
        \Spatie\Permission\Models\Permission::observe(\App\Observers\PermissionObserver::class);

        // Register Country and State observers for cache invalidation
        \App\Models\Country::observe(\App\Observers\CountryObserver::class);
        \App\Models\State::observe(\App\Observers\StateObserver::class);

        // ── Universal Admin Audit Trail ─────────────────────────────────
        // Logs create / update / delete for every key admin model.
        // Order and Product have their own dedicated observers already.
        $this->bootAuditTrail();

        // Explicitly map policies for vendor models (Spatie Role)
        Gate::policy(\Spatie\Permission\Models\Role::class, \App\Policies\RolePolicy::class);

        // Register Voucher policy
        Gate::policy(\App\Models\Voucher::class, \App\Policies\VoucherPolicy::class);

        // Global admin bypass: allow admins to pass all gates and policies
        Gate::before(function ($user, ?string $ability = null) {
            try {
                $isAdmin = method_exists($user, 'hasRole') && $user->hasRole(\App\Enums\RoleEnum::ADMIN);
                if ($isAdmin) {
                    return true;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        });

        /** DB query logging
        if (config('app.debug')) {
            DB::listen(function ($query) {
                // You can log the SQL query, bindings, and the execution time
                Log::debug("SQL: {$query->sql} [".implode(', ', $query->bindings)."] (Time: {$query->time}ms)");
            });
        }
        */

        // Force HTTPS only in development environments
        if ($this->app->environment('local')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Register universal create/update/delete audit trail for all key admin models.
     * Skips unauthenticated requests and consumer-only users.
     */
    protected function bootAuditTrail(): void
    {
        $auditModels = [
            \App\Models\User::class,
            \App\Models\Category::class,
            \App\Models\Brand::class,
            \App\Models\Tag::class,
            \App\Models\Tax::class,
            \App\Models\Coupon::class,
            \App\Models\Voucher::class,
            \App\Models\Currency::class,
            \App\Models\OrderStatus::class,
            \App\Models\InvoiceQuotation::class,
            \App\Models\InvoiceQuotationItem::class,
            \App\Models\LaybyApplication::class,
            \App\Models\LaybyPayment::class,
            \App\Models\LaybySetting::class,
            \App\Models\InventoryShipment::class,
            \App\Models\AuctionItem::class,
            \App\Models\AuctionBidDeposit::class,
            \App\Models\AuctionSetting::class,
            \App\Models\AuctionBan::class,
            \App\Models\CashBookEntry::class,
            \App\Models\CashBookCategory::class,
            \App\Models\Blog::class,
            \App\Models\Faq::class,
            \App\Models\Country::class,
            \App\Models\State::class,
            \App\Models\Attribute::class,
            \App\Models\AttributeValue::class,
        ];

        $ignore = ['updated_at', 'last_seen_at', 'remember_token', 'email_verified_at', 'two_factor_secret'];

        $shouldAudit = function (): bool {
            // Check web guard first (admin/staff browser sessions)
            $user = auth('web')->user();
            // Fall back to API/Sanctum guard (customer API requests)
            if (!$user) {
                $user = auth('sanctum')->user();
            }
            return $user !== null;
        };

        $label = function ($model): string {
            $name = $model->name ?? $model->title ?? $model->subject ?? $model->order_number ?? null;
            $id   = $model->getKey();
            return $name
                ? class_basename($model) . " '{$name}' (#{$id})"
                : class_basename($model) . " #{$id}";
        };

        foreach ($auditModels as $modelClass) {
            $modelClass::created(function ($model) use ($shouldAudit, $label) {
                if (!$shouldAudit()) return;
                try {
                    \App\Services\ActivityLogger::make()
                        ->useLog(strtolower(class_basename($model)))
                        ->event('created')->on($model)
                        ->log($label($model) . ' created');
                } catch (\Throwable) {}
            });

            $modelClass::updated(function ($model) use ($shouldAudit, $label, $ignore) {
                if (!$shouldAudit()) return;
                $dirty = array_diff_key($model->getDirty(), array_flip($ignore));
                if (empty($dirty)) return;
                try {
                    \App\Services\ActivityLogger::make()
                        ->useLog(strtolower(class_basename($model)))
                        ->event('updated')->on($model)
                        ->withChanges(array_intersect_key($model->getOriginal(), $dirty), $dirty)
                        ->log($label($model) . ' updated: ' . implode(', ', array_keys($dirty)));
                } catch (\Throwable) {}
            });

            $modelClass::deleted(function ($model) use ($shouldAudit, $label) {
                if (!$shouldAudit()) return;
                try {
                    \App\Services\ActivityLogger::make()
                        ->useLog(strtolower(class_basename($model)))
                        ->event('deleted')->on($model)
                        ->log($label($model) . ' deleted');
                } catch (\Throwable) {}
            });
        }
    }

    /**
     * Register custom Blade directives for cached permission checks
     * These directives use cached permissions instead of hitting the database
     */
    protected function registerCachedPermissionBladeDirectives(): void
    {
        // @can('permission.name') - Check if user has permission (from cache)
        \Blade::directive('userCan', function ($permission) {
            return "<?php if(userCan($permission)): ?>";
        });
        \Blade::directive('enduserCan', function () {
            return "<?php endif; ?>";
        });

        // @userHasRole('role') - Check if user has role (from cache)
        \Blade::directive('userHasRole', function ($role) {
            return "<?php if(userHasRole($role)): ?>";
        });
        \Blade::directive('enduserHasRole', function () {
            return "<?php endif; ?>";
        });

        // @userHasAnyRole(['admin', 'vendor']) - Check if user has any of the roles (from cache)
        \Blade::directive('userHasAnyRole', function ($roles) {
            return "<?php if(userHasAnyRole($roles)): ?>";
        });
        \Blade::directive('enduserHasAnyRole', function () {
            return "<?php endif; ?>";
        });

        // @userIsAdmin - Check if user is admin (from cache)
        \Blade::directive('userIsAdmin', function () {
            return "<?php if(userIsAdmin()): ?>";
        });
        \Blade::directive('enduserIsAdmin', function () {
            return "<?php endif; ?>";
        });

        // @userIsVendor - Check if user is vendor (from cache)
        \Blade::directive('userIsVendor', function () {
            return "<?php if(userIsVendor()): ?>";
        });
        \Blade::directive('enduserIsVendor', function () {
            return "<?php endif; ?>";
        });
    }
}
