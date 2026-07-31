<?php

use App\Http\Controllers\ProductFeedController;
use App\Services\ElasticsearchService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductImportController;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Http\Request;


Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Serve robots.txt for the API domain (block everything)
Route::get('/robots.txt', function () {
    return response("User-agent: *\nDisallow: /\n", 200)
        ->header('Content-Type', 'text/plain');
});

// Image proxy for CORS-free image loading in PDF/Image exports
Route::get('/proxy-image', [\App\Http\Controllers\ImageProxyController::class, 'proxy'])->name('proxy-image');

require __DIR__.'/auth.php';

Route::get('/vendor/log-viewer/{path}', function (string $path) {
    $full = public_path('vendor/log-viewer/' . $path);

    if (! File::exists($full)) {
        abort(404);
    }

    // Basic content-type handling
    $ext = pathinfo($full, PATHINFO_EXTENSION);
    $mime = match (strtolower($ext)) {
        'css' => 'text/css; charset=UTF-8',
        'js'  => 'application/javascript; charset=UTF-8',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        default => File::mimeType($full) ?: 'application/octet-stream',
    };

    // Cache headers for 1 day (tweak as you like)
    return Response::file($full, [
        'Content-Type'  => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*'); // allow nested paths


Route::get('/dev/log-viewer/debug-auth', function (Request $r) {
    $cookie = $r->cookie('lv_token');
    $auth  = $r->header('Authorization');
    $user  = auth()->user() ?: auth('sanctum')->user() ?: auth('web')->user();

    $byCookie = $cookie ? (bool) PersonalAccessToken::findToken($cookie) : null;
    $byBearer = null;
    if (preg_match('/^Bearer\s+(\S+)/i', (string) $auth, $m)) {
        $byBearer = (bool) PersonalAccessToken::findToken($m[1]);
    }

    return response()->json([
        'session_user'  => $user ? $user->only(['id','email']) : null,
        'lv_token_cookie_present' => $cookie !== null,
        'cookie_decoded_token_found' => $byCookie,
        'authorization_header' => (bool) $auth,
        'bearer_token_found' => $byBearer,
    ]);
})->middleware(['web']);  // same as page


Route::get('/dev/log-viewer/login', function (Request $r) {
    $raw = $r->query('token');
    if (!$raw) {
        abort(400, 'Missing token');
    }

    // TIP: your token must be URL-encoded when you call this route.
    // Example: ?token=12%7CeyJ...

    // Validate the plaintext Sanctum token
    if (! PersonalAccessToken::findToken($raw)) {
        abort(403, 'Invalid token');
    }

    // Persist for 6 hours, secure, httpOnly, SameSite=Lax
    Cookie::queue(
        Cookie::make(
            'lv_token', $raw, 60 * 6, // minutes
            null, null,
            secure: true,
            httpOnly: true,
            raw: false,
            sameSite: 'Lax'
        )
    );

    // Now go to the viewer without query string
    return redirect('/dev/log-viewer');
})->middleware(['web']);

// ========================================
// ADMIN PANEL ROUTES (Laravel Blade-based)
// ========================================

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminLaybyController;
use App\Http\Controllers\Admin\AdminOrderItemSearchController;
use App\Http\Controllers\Admin\AdminOrderQRCodeController;
use App\Http\Controllers\Admin\AdminQRScannerController;
use App\Http\Controllers\Admin\AdminQuestionAnswerController;

// Admin Authentication Routes (Guest only)
Route::prefix('admin')->name('admin.')->group(function () {
    // Public routes (login page)
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');

    // Set token cookie and redirect (called after successful API login)
    Route::get('/set-token', function () {
        $token = request()->query('token');
        if (!$token) {
            return redirect()->route('admin.login')->with('error', 'Invalid token');
        }

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if (!$accessToken || !$accessToken->tokenable) {
            return redirect()->route('admin.login')->with('error', 'Invalid or expired token');
        }

        // Persist cookie for 6 hours, secure + httpOnly on HTTPS, SameSite=Lax
        $minutes = 60 * 6;
        $cookie = \Cookie::make(
            'admin_token',
            $token,
            $minutes,
            path: '/',
            domain: null, // default to current host to avoid domain mismatches in prod
            secure: app()->environment('local') ? false : true,
            httpOnly: true,
            raw: false,
            sameSite: config('session.same_site', 'lax')
        );
        \Cookie::queue($cookie);

        return redirect()->route('admin.dashboard');
    })->name('set-token');

    // Protected Admin Routes (Requires Authentication)
    Route::middleware(\App\Http\Middleware\AdminTokenAuth::class)->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminAuthController::class, 'dashboard'])->name('dashboard');

        // Profile
        Route::get('/profile', [AdminAuthController::class, 'profile'])->name('profile');

        // Logout
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Orders
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/get-it-tomorrow', [AdminOrderController::class, 'getItTomorrow'])->name('orders.get-it-tomorrow');
        Route::get('/orders/same-day-delivery', [AdminOrderController::class, 'sameDayDelivery'])->name('orders.same-day-delivery');
        Route::get('/orders/corporate', [AdminOrderController::class, 'corporateOrders'])->name('orders.corporate');
        Route::get('/orders/create', [AdminOrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [AdminOrderController::class, 'store'])->name('orders.store');

        // Order Statistics (MUST be before {orderNumber} route to prevent conflict)
        Route::prefix('orders/stats')->name('orders.stats.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminOrderStatsController::class, 'index'])->name('index');
            Route::get('/overview', [\App\Http\Controllers\Admin\AdminOrderStatsController::class, 'overview'])->name('overview');
            Route::get('/top-products', [\App\Http\Controllers\Admin\AdminOrderStatsController::class, 'topProducts'])->name('top-products');
            Route::get('/status-details', [\App\Http\Controllers\Admin\AdminOrderStatsController::class, 'statusDetails'])->name('status-details');
            Route::get('/product-performance', [\App\Http\Controllers\Admin\AdminOrderStatsController::class, 'productPerformance'])->name('product-performance');
            Route::get('/customer-insights', [\App\Http\Controllers\Admin\AdminOrderStatsController::class, 'customerInsights'])->name('customer-insights');
        });

        // Order Creation Helpers (specific routes before {orderNumber})
        Route::get('/orders/search/users', [AdminOrderController::class, 'searchUsers'])->name('orders.search.users');
        Route::get('/orders/search/products', [AdminOrderController::class, 'searchProducts'])->name('orders.search.products');
        Route::get('/orders/addresses/user', [AdminOrderController::class, 'getUserAddresses'])->name('orders.addresses.user');
        Route::post('/orders/addresses/create', [AdminOrderController::class, 'createAddress'])->name('orders.addresses.create');
        Route::get('/orders/wallet/user', [AdminOrderController::class, 'getUserWallet'])->name('orders.wallet.user');
        Route::get('/orders/points/user', [AdminOrderController::class, 'getUserPoints'])->name('orders.points.user');
        Route::get('/orders/shipping/methods', [AdminOrderController::class, 'getShippingMethods'])->name('orders.shipping.methods');
        Route::get('/orders/countries', [AdminOrderController::class, 'getCountries'])->name('orders.countries');
        Route::get('/orders/states', [AdminOrderController::class, 'getStates'])->name('orders.states');

        // Order Notes
        Route::post('/orders/notes', [AdminOrderController::class, 'storeNote'])->name('orders.notes.store');
        Route::put('/orders/notes/{noteId}', [AdminOrderController::class, 'updateNote'])->name('orders.notes.update');
        Route::delete('/orders/notes/{noteId}', [AdminOrderController::class, 'destroyNote'])->name('orders.notes.destroy');

        // Fast Shipping Toggle
        Route::post('/orders/{orderId}/toggle-fast-shipping', [AdminOrderController::class, 'toggleFastShipping'])->name('orders.toggle-fast-shipping');

        // Order Status and Payment Method Updates
        Route::put('/orders/{orderId}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::put('/orders/{orderId}/payment-method', [AdminOrderController::class, 'updatePaymentMethod'])->name('orders.update-payment-method');

        // Order Item Search (MUST be before {orderNumber} route to prevent conflict)
        Route::prefix('orders/item-search')->name('orders.item-search.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminOrderItemSearchController::class, 'index'])->name('index');
            Route::get('/search', [\App\Http\Controllers\Admin\AdminOrderItemSearchController::class, 'search'])->name('search');
        });

        // Processing Orders Link Builder (MUST be before {orderNumber} route to prevent conflict)
        Route::get('/orders/processing-link-builder', [\App\Http\Controllers\Admin\ProcessingOrdersLinkController::class, 'index'])->name('orders.processing-link-builder');
        Route::post('/orders/processing-link-builder/transfer-to-inventory', [\App\Http\Controllers\Admin\ProcessingOrdersLinkController::class, 'transferToInventory'])->name('orders.processing-link-builder.transfer-to-inventory');

        // Takealot API Proxy (for adding items to cart from processing orders)
        Route::post('/takealot/search-products', [\App\Http\Controllers\Admin\TakealotProxyController::class, 'searchProducts'])->name('takealot.search-products');
        Route::post('/takealot/add-to-cart', [\App\Http\Controllers\Admin\TakealotProxyController::class, 'addToCart'])->name('takealot.add-to-cart');

        // QR Code Management (MUST be before {orderNumber} route to prevent conflict)
        Route::prefix('orders')->name('orders.')->group(function () {
            // QR Code Scanner
            Route::get('/qr-scanner', [AdminQRScannerController::class, 'showScanner'])->name('qr-scanner');
            Route::post('/qr-scanner/scan', [AdminQRScannerController::class, 'scanQRCode'])->name('qr-scanner.scan');
            Route::get('/qr-scanner/history', [AdminQRScannerController::class, 'getScanHistory'])->name('qr-scanner.history');

            // Collection QR Code Scanner - handles QR codes from "Ready for Collection" emails
            Route::get('/collection-scan/{orderNumber}', [AdminQRScannerController::class, 'handleCollectionQRScan'])->name('collection-scan');

            // QR Code Generation and Display (order-specific routes)
            Route::get('/{orderNumber}/qr-codes', [AdminOrderQRCodeController::class, 'showQRCodes'])->name('qr-codes.show');
            Route::post('/{orderNumber}/qr-codes/generate', [AdminOrderQRCodeController::class, 'generateQRCodes'])->name('qr-codes.generate');
            Route::get('/{orderNumber}/qr-codes/{pivotId}/download', [AdminOrderQRCodeController::class, 'downloadQRCode'])->name('qr-codes.download');
            Route::get('/{orderNumber}/qr-codes/json', [AdminOrderQRCodeController::class, 'getQRCodesJson'])->name('qr-codes.json');
        });

        // Generic order show route (MUST be last to avoid conflicts with specific routes)
        Route::get('/orders/{orderNumber}', [AdminOrderController::class, 'show'])->name('orders.show');

        // Membership Card Scanner
        Route::prefix('membership-cards')->name('membership-cards.')->group(function () {
            Route::get('/scanner', [\App\Http\Controllers\Admin\AdminMembershipCardController::class, 'index'])->name('scanner');
            Route::post('/scan', [\App\Http\Controllers\Admin\AdminMembershipCardController::class, 'scanCard'])->name('scan');
            Route::post('/assign', [\App\Http\Controllers\Admin\AdminMembershipCardController::class, 'assignCard'])->name('assign');
            Route::post('/add-points', [\App\Http\Controllers\Admin\AdminMembershipCardController::class, 'addPoints'])->name('add-points');
            Route::post('/add-wallet', [\App\Http\Controllers\Admin\AdminMembershipCardController::class, 'addWallet'])->name('add-wallet');
            Route::get('/search-user', [\App\Http\Controllers\Admin\AdminMembershipCardController::class, 'searchUser'])->name('search-user');
            Route::get('/user-orders', [\App\Http\Controllers\Admin\AdminMembershipCardController::class, 'getUserOrders'])->name('user-orders');
            Route::post('/award-order-points', [\App\Http\Controllers\Admin\AdminMembershipCardController::class, 'awardOrderPoints'])->name('award-order-points');
        });

        // Order Reminders
        Route::prefix('order-reminders')->name('order-reminders.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminOrderReminderController::class, 'index'])->name('index');
            Route::get('/settings', [\App\Http\Controllers\Admin\AdminOrderReminderController::class, 'settings'])->name('settings');
            Route::post('/settings', [\App\Http\Controllers\Admin\AdminOrderReminderController::class, 'updateSettings'])->name('settings.update');
            Route::post('/resend/{id}', [\App\Http\Controllers\Admin\AdminOrderReminderController::class, 'resend'])->name('resend');
            Route::get('/stats', [\App\Http\Controllers\Admin\AdminOrderReminderController::class, 'stats'])->name('stats');
        });

        // Processing Overdue
        Route::prefix('processing-overdue')->name('processing-overdue.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminProcessingOverdueController::class, 'index'])->name('index');
            Route::get('/count', [\App\Http\Controllers\Admin\AdminProcessingOverdueController::class, 'getCount'])->name('count');
        });

        // Late Orders — paid orders with overdue items, apology email management
        Route::prefix('late-orders')->name('late-orders.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminLateOrdersController::class, 'index'])->name('index');
            Route::post('/{orderId}/send-apology', [\App\Http\Controllers\Admin\AdminLateOrdersController::class, 'sendApology'])->name('send-apology');
            Route::get('/settings', [\App\Http\Controllers\Admin\AdminOrderApologySettingsController::class, 'index'])->name('settings');
            Route::post('/settings', [\App\Http\Controllers\Admin\AdminOrderApologySettingsController::class, 'update'])->name('settings.update');
        });

        // Layby Management
        Route::prefix('layby')->name('layby.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminLaybyController::class, 'index'])->name('index');
            Route::get('/settings', [\App\Http\Controllers\Admin\AdminLaybySettingsController::class, 'index'])->name('settings');
            Route::post('/settings', [\App\Http\Controllers\Admin\AdminLaybySettingsController::class, 'update'])->name('settings.update');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminLaybyController::class, 'show'])->name('show');
            Route::post('/{id}/update-status', [\App\Http\Controllers\Admin\AdminLaybyController::class, 'updateStatus'])->name('update-status');
            Route::post('/{id}/capture-payment', [\App\Http\Controllers\Admin\AdminLaybyController::class, 'capturePayment'])->name('capture-payment');
            Route::put('/{applicationId}/edit-payment/{paymentId}', [\App\Http\Controllers\Admin\AdminLaybyController::class, 'editPayment'])->name('edit-payment');
            Route::delete('/{applicationId}/payments/{paymentId}', [\App\Http\Controllers\Admin\AdminLaybyController::class, 'deletePayment'])->name('delete-payment');
            Route::post('/{id}/cancel', [\App\Http\Controllers\Admin\AdminLaybyController::class, 'cancel'])->name('cancel');
            Route::get('/{id}/check-order-eligibility', [\App\Http\Controllers\Admin\AdminLaybyController::class, 'checkOrderCreationEligibility'])->name('check-order-eligibility');
            Route::post('/{id}/manually-create-order', [\App\Http\Controllers\Admin\AdminLaybyController::class, 'manuallyCreateOrder'])->name('manually-create-order');
        });

        // Currency Preference
        Route::post('/update-currency', [AdminOrderController::class, 'updateCurrency'])->name('update-currency');

        // Marketing Feedback
        Route::prefix('marketing-feedback')->name('marketing-feedback.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminMarketingFeedbackController::class, 'index'])->name('index');
            Route::get('/stats', [\App\Http\Controllers\Admin\AdminMarketingFeedbackController::class, 'getStats'])->name('stats');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminMarketingFeedbackController::class, 'show'])->name('show');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminMarketingFeedbackController::class, 'destroy'])->name('destroy');
            Route::get('/export/csv', [\App\Http\Controllers\Admin\AdminMarketingFeedbackController::class, 'export'])->name('export');
        });

        // AI Analytics — natural-language reporting (admin only)
        Route::prefix('ai-analytics')->name('ai-analytics.')->middleware('role:admin')->group(function () {
            Route::get('/',             [\App\Http\Controllers\Admin\AIAnalyticsController::class, 'index'])->name('index');
            Route::post('/query',       [\App\Http\Controllers\Admin\AIAnalyticsController::class, 'query'])->name('query');
            Route::get('/clear-history',[\App\Http\Controllers\Admin\AIAnalyticsController::class, 'clearHistory'])->name('clear-history');
        });

        // Analytics Dashboard (Admin Role Only - checked in controller)
        Route::get('/analytics', [\App\Http\Controllers\Admin\AdminAnalyticsController::class, 'index'])->name('analytics.dashboard');
        Route::get('/analytics/active-users', [\App\Http\Controllers\Admin\AdminAnalyticsController::class, 'getActiveUsers'])->name('analytics.active-users');
        Route::get('/analytics/cart-abandonments', [\App\Http\Controllers\Admin\AdminAnalyticsController::class, 'getCartAbandonments'])->name('analytics.cart-abandonments');
        Route::get('/analytics/session/{sessionId}', [\App\Http\Controllers\Admin\AdminAnalyticsController::class, 'sessionDetails'])->name('analytics.session-details');

        // Audit Trail (permission: activity-log.view)
        Route::get('/activity-log', [\App\Http\Controllers\Admin\AdminActivityLogController::class, 'index'])->name('activity-log.index');

        // Product Management (Admin Role Only)
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminProductController::class, 'index'])->name('index');
            Route::get('/statistics', [\App\Http\Controllers\Admin\AdminProductStatisticsController::class, 'index'])->name('statistics');
            Route::get('/vendor-products', [\App\Http\Controllers\Admin\AdminProductController::class, 'vendorProducts'])->name('vendor-products');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminProductController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminProductController::class, 'store'])->name('store');

            // Attribute Management (for creating new attributes from variation modal)
            Route::post('/attributes/create', [\App\Http\Controllers\Admin\AdminProductController::class, 'storeAttribute'])->name('attributes.store');
            Route::get('/attributes/search', [\App\Http\Controllers\Admin\AdminProductController::class, 'searchAttributeValues'])->name('attributes.search');

            // Brand search for Select2 AJAX
            Route::get('/brands/search', [\App\Http\Controllers\Admin\AdminProductController::class, 'searchBrands'])->name('brands.search');

            // Category search for Select2 AJAX
            Route::get('/categories/search', [\App\Http\Controllers\Admin\AdminProductController::class, 'searchCategories'])->name('categories.search');

            // Variation Management - MUST be before the {id} routes to avoid conflicts
            Route::prefix('{productId}/variations')->name('variations.')->group(function () {
                Route::post('/', [\App\Http\Controllers\Admin\AdminProductController::class, 'storeVariation'])->name('store');
                Route::put('/{variationId}', [\App\Http\Controllers\Admin\AdminProductController::class, 'updateVariation'])->name('update');
                Route::delete('/{variationId}', [\App\Http\Controllers\Admin\AdminProductController::class, 'deleteVariation'])->name('destroy');
            });

            // Search & Export
            Route::get('/search-export', [\App\Http\Controllers\Admin\AdminProductController::class, 'searchExport'])->name('search-export');
            Route::get('/export-excel', [\App\Http\Controllers\Admin\AdminProductController::class, 'exportExcel'])->name('export-excel');

            // Bulk Disable by SKU
            Route::get('/bulk-disable', [\App\Http\Controllers\Admin\AdminProductController::class, 'bulkDisable'])->name('bulk-disable');
            Route::post('/bulk-disable', [\App\Http\Controllers\Admin\AdminProductController::class, 'bulkDisableProcess'])->name('bulk-disable.process');

            // Product-specific routes - these use {id} so must come AFTER more specific routes
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminProductController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminProductController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminProductController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [\App\Http\Controllers\Admin\AdminProductController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Blog Management
        Route::prefix('blogs')->name('blogs.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminBlogController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminBlogController::class, 'create'])->name('create');
            Route::get('/categories-data', [\App\Http\Controllers\Admin\AdminBlogController::class, 'getCategories'])->name('categories-data');
            Route::get('/tags-data', [\App\Http\Controllers\Admin\AdminBlogController::class, 'getTags'])->name('tags-data');
            Route::post('/', [\App\Http\Controllers\Admin\AdminBlogController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminBlogController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminBlogController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminBlogController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [\App\Http\Controllers\Admin\AdminBlogController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Product Feed Export
        Route::prefix('product-feed')->name('product-feed.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'index'])->name('index');
            Route::get('/categories-by-store', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'getCategoriesByStore'])->name('categories-by-store');
            Route::get('/stats', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'stats'])->name('stats');
            Route::get('/export', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'export'])->name('export');
            Route::get('/export-tsv', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'exportTsv'])->name('export-tsv');
            Route::get('/download-xml', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'downloadXml'])->name('download-xml');
            Route::get('/download-tsv', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'downloadTsv'])->name('download-tsv');
            Route::get('/download-csv', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'downloadCsv'])->name('download-csv');
            Route::get('/check-status/{jobId}', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'checkStatus'])->name('check-status');
            Route::get('/export-stream', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'exportStream'])->name('export-stream');
            Route::get('/export-tsv-stream', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'exportTsvStream'])->name('export-tsv-stream');
            // Background job start endpoints (replaces streaming for large exports)
            Route::get('/start-export', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'export'])->name('start-export');
            Route::get('/start-export-tsv', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'exportTsv'])->name('start-export-tsv');
            Route::get('/download-temp', [\App\Http\Controllers\Admin\AdminProductFeedController::class, 'downloadTemp'])->name('download-temp');
        });

        // Promo Templates
        Route::prefix('promo-templates')->name('promo-templates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/generate', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'generate'])->name('generate');
            Route::post('/{id}/duplicate', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'duplicate'])->name('duplicate');
            Route::get('/currencies', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'getCurrencies'])->name('currencies');
        });

        // Bulk Promotion
        Route::prefix('bulk-promotion')->name('bulk-promotion.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminBulkPromotionController::class, 'index'])->name('index');
            Route::post('/apply', [\App\Http\Controllers\Admin\AdminBulkPromotionController::class, 'apply'])->name('apply');
            Route::post('/reset', [\App\Http\Controllers\Admin\AdminBulkPromotionController::class, 'reset'])->name('reset');
            Route::post('/apply-sku-prices', [\App\Http\Controllers\Admin\AdminBulkPromotionController::class, 'applySkuPrices'])->name('apply-sku-prices');
        });

        // Q&A Management
        Route::prefix('questions')->name('questions.')->group(function () {
            Route::get('/', [AdminQuestionAnswerController::class, 'index'])->name('index');
            Route::post('/{id}/answer', [AdminQuestionAnswerController::class, 'answer'])->name('answer');
            Route::delete('/{id}', [AdminQuestionAnswerController::class, 'destroy'])->name('destroy');
        });

        // Fast Import (Admin Only)
        Route::prefix('import-fast')->name('import-fast.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'index'])->name('index');
            Route::post('/upload', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'upload'])->name('upload');
            Route::post('/upload-chunk', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'uploadChunk'])->name('upload-chunk');
            Route::post('/upload-complete', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'uploadComplete'])->name('upload-complete');
            Route::post('/delete-files', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'deleteFiles'])->name('delete-files');
            Route::get('/download-file', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'downloadFile'])->name('download-file');
            Route::post('/run', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'run'])->name('run');
            Route::post('/run-queued', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'runQueued'])->name('run-queued');
            Route::post('/queue-indexing', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'queueIndexing'])->name('queue-indexing');

            // History and batch details
            Route::get('/history', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'history'])->name('history');
            Route::get('/batch/{batchId}', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'batchDetails'])->name('batch-details');
            Route::post('/resume-failed', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'resumeFailed'])->name('resume-failed');
            Route::get('/statistics', [\App\Http\Controllers\Admin\AdminImportFastController::class, 'statistics'])->name('statistics');
        });

        // Fast Import [Update] - Price Update Only (Admin Only)
        Route::prefix('import-fast-update')->name('import-fast-update.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'index'])->name('index');
            Route::post('/upload', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'upload'])->name('upload');
            Route::post('/upload-chunk', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'uploadChunk'])->name('upload-chunk');
            Route::post('/upload-complete', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'uploadComplete'])->name('upload-complete');
            Route::post('/delete-files', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'deleteFiles'])->name('delete-files');
            Route::get('/download-file', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'downloadFile'])->name('download-file');
            Route::post('/run', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'run'])->name('run');

            // Import tracking and history
            Route::get('/history', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'history'])->name('history');
            Route::get('/batch/{batchId}', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'batchDetails'])->name('batch-details');
            Route::post('/resume-failed', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'resumeFailed'])->name('resume-failed');
            Route::get('/statistics', [\App\Http\Controllers\Admin\AdminImportFastUpdateController::class, 'statistics'])->name('statistics');
        });

        // Back Order Management (Admin Only)
        Route::prefix('back-orders')->name('back-orders.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminBackOrderController::class, 'index'])->name('index');
            Route::post('/upload', [\App\Http\Controllers\Admin\AdminBackOrderController::class, 'upload'])->name('upload');
            Route::get('/files', [\App\Http\Controllers\Admin\AdminBackOrderController::class, 'listFiles'])->name('files');
            Route::post('/process', [\App\Http\Controllers\Admin\AdminBackOrderController::class, 'process'])->name('process');
            Route::get('/status', [\App\Http\Controllers\Admin\AdminBackOrderController::class, 'status'])->name('status');
            Route::post('/delete-files', [\App\Http\Controllers\Admin\AdminBackOrderController::class, 'deleteFiles'])->name('delete-files');
            Route::post('/clear-all', [\App\Http\Controllers\Admin\AdminBackOrderController::class, 'clearAll'])->name('clear-all');
            Route::get('/history', [\App\Http\Controllers\Admin\AdminBackOrderController::class, 'history'])->name('history');
        });

        // Settings Management (Admin Only)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'update'])->name('update');
            Route::get('/reindex-stream', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'reindexStream'])->name('reindex-stream');
        });

        // App Version Management (Admin Only)
        Route::prefix('app-version')->name('app-version.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminAppVersionWebController::class, 'index'])->name('index');
            Route::put('/{platform}', [\App\Http\Controllers\Admin\AdminAppVersionWebController::class, 'update'])->name('update');
        });

        // Elasticsearch Reindex Management (Admin Only)
        Route::prefix('elasticsearch')->name('elasticsearch.')->group(function () {
            Route::get('/reindex', [\App\Http\Controllers\Admin\AdminElasticsearchController::class, 'reindex'])->name('reindex');
            Route::get('/reindex-start', [\App\Http\Controllers\Admin\AdminElasticsearchController::class, 'reindexStart'])->name('reindex-start');
            Route::get('/reindex-poll', [\App\Http\Controllers\Admin\AdminElasticsearchController::class, 'reindexPoll'])->name('reindex-poll');
            Route::post('/delete-index', [\App\Http\Controllers\Admin\AdminElasticsearchController::class, 'deleteIndex'])->name('delete-index');
            Route::get('/check-index', [\App\Http\Controllers\Admin\AdminElasticsearchController::class, 'checkIndex'])->name('check-index');
            Route::match(['get', 'post'], '/run-artisan', [\App\Http\Controllers\Admin\AdminElasticsearchController::class, 'runArtisanCommand'])->name('run-artisan');
        });

        // Categories Management (Admin Only)
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCategoryController::class, 'index'])->name('index');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminCategoryController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminCategoryController::class, 'update'])->name('update');
            Route::post('/reorder', [\App\Http\Controllers\Admin\AdminCategoryController::class, 'reorder'])->name('reorder');
        });

        // Attributes Management
        Route::prefix('attributes')->name('attributes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminAttributeController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminAttributeController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminAttributeController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminAttributeController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminAttributeController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminAttributeController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminAttributeController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\Admin\AdminAttributeController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/{id}/toggle-status', [\App\Http\Controllers\Admin\AdminAttributeController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Theme Options Management (Admin Only)
        Route::prefix('theme-options')->name('theme-options.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminThemeOptionsController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminThemeOptionsController::class, 'update'])->name('update');
        });

// Home Pages Management (Admin Only)
Route::prefix('home-pages')->name('home-pages.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminHomePagesController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\AdminHomePagesController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\AdminHomePagesController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminHomePagesController::class, 'edit'])->name('edit');
    Route::get('/{id}/builder', [\App\Http\Controllers\Admin\AdminHomePagesController::class, 'builder'])->name('builder');
    Route::put('/{id}', [\App\Http\Controllers\Admin\AdminHomePagesController::class, 'update'])->name('update');
    Route::post('/{id}/builder', [\App\Http\Controllers\Admin\AdminHomePagesController::class, 'updateBuilder'])->name('update-builder');
    Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminHomePagesController::class, 'destroy'])->name('destroy');
});

// Media Management (Admin Only - Permission Based)
Route::prefix('media')->name('media.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminMediaController::class, 'index'])->name('index');
    Route::get('/upload', [\App\Http\Controllers\Admin\AdminMediaController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\AdminMediaController::class, 'store'])->name('store');
    Route::get('/{id}', [\App\Http\Controllers\Admin\AdminMediaController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminMediaController::class, 'edit'])->name('edit');
    Route::put('/{id}', [\App\Http\Controllers\Admin\AdminMediaController::class, 'update'])->name('update');
    Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminMediaController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-delete', [\App\Http\Controllers\Admin\AdminMediaController::class, 'bulkDelete'])->name('bulk-delete');
});

        // User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminUserController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminUserController::class, 'store'])->name('store');
            Route::post('/bulk-update-branch', [\App\Http\Controllers\Admin\AdminUserController::class, 'bulkUpdateBranch'])->name('bulk-update-branch');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminUserController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminUserController::class, 'destroy'])->name('destroy');

            // Address Management
            Route::get('/{userId}/addresses', [\App\Http\Controllers\Admin\AdminUserController::class, 'getAddresses'])->name('addresses.get');
            Route::post('/{userId}/addresses', [\App\Http\Controllers\Admin\AdminUserController::class, 'storeAddress'])->name('addresses.store');
            Route::put('/{userId}/addresses/{addressId}', [\App\Http\Controllers\Admin\AdminUserController::class, 'updateAddress'])->name('addresses.update');
            Route::delete('/{userId}/addresses/{addressId}', [\App\Http\Controllers\Admin\AdminUserController::class, 'deleteAddress'])->name('addresses.delete');
            Route::post('/{userId}/addresses/{addressId}/set-default', [\App\Http\Controllers\Admin\AdminUserController::class, 'setDefaultAddress'])->name('addresses.set-default');

            // Helper endpoints for dropdowns
            Route::get('/countries', [\App\Http\Controllers\Admin\AdminUserController::class, 'getCountries'])->name('countries');
            Route::get('/countries/{countryId}/states', [\App\Http\Controllers\Admin\AdminUserController::class, 'getStates'])->name('states');
        });

        // Role Management
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminRoleController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminRoleController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminRoleController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminRoleController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminRoleController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminRoleController::class, 'destroy'])->name('destroy');
        });

        // Search Analytics (Admin Only)
        Route::prefix('search-analytics')->name('search-analytics.')
            ->middleware('role:admin')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\SearchAnalyticsController::class, 'index'])->name('index');
                Route::get('/export', [\App\Http\Controllers\Admin\SearchAnalyticsController::class, 'export'])->name('export');
            });

        // Wallet Management (Admin Only)
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminWalletController::class, 'index'])->name('index');
            Route::get('/list', [\App\Http\Controllers\Admin\AdminWalletController::class, 'listWallets'])->name('list');
            Route::get('/manage/{userId}', [\App\Http\Controllers\Admin\AdminWalletController::class, 'manage'])->name('manage');
            Route::get('/user-wallet', [\App\Http\Controllers\Admin\AdminWalletController::class, 'getUserWallet'])->name('user-wallet');
            Route::get('/transactions', [\App\Http\Controllers\Admin\AdminWalletController::class, 'getTransactions'])->name('transactions');
            Route::post('/credit', [\App\Http\Controllers\Admin\AdminWalletController::class, 'credit'])->name('credit');
            Route::post('/debit', [\App\Http\Controllers\Admin\AdminWalletController::class, 'debit'])->name('debit');
        });

        // Points Management (Admin Only)
        Route::prefix('points')->name('points.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminPointsController::class, 'index'])->name('index');
            Route::get('/user-points', [\App\Http\Controllers\Admin\AdminPointsController::class, 'getUserPoints'])->name('user-points');
            Route::get('/transactions', [\App\Http\Controllers\Admin\AdminPointsController::class, 'getTransactions'])->name('transactions');
            Route::post('/credit', [\App\Http\Controllers\Admin\AdminPointsController::class, 'credit'])->name('credit');
            Route::post('/debit', [\App\Http\Controllers\Admin\AdminPointsController::class, 'debit'])->name('debit');
        });

        // Cash Book Management (Permission Based)
        Route::prefix('cash-book')->name('cash-book.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCashBookController::class, 'index'])->name('index');
            Route::get('/entries', [\App\Http\Controllers\Admin\AdminCashBookController::class, 'entries'])->name('entries');
            Route::get('/stats', [\App\Http\Controllers\Admin\AdminCashBookController::class, 'stats'])->name('stats');
            Route::post('/order-details', [\App\Http\Controllers\Admin\AdminCashBookController::class, 'getOrderDetails'])->name('order-details');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminCashBookController::class, 'show'])->name('show');
            Route::post('/', [\App\Http\Controllers\Admin\AdminCashBookController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminCashBookController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminCashBookController::class, 'destroy'])->name('destroy');
            Route::post('/import-csv', [\App\Http\Controllers\Admin\AdminCashBookController::class, 'importCsv'])->name('import-csv');
        });

        // Refunds Management (Admin Only)
        Route::prefix('refunds')->name('refunds.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminRefundsController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminRefundsController::class, 'show'])->name('show');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminRefundsController::class, 'update'])->name('update');
        });

        // Returns Management (Admin Only)
        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminReturnsController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminReturnsController::class, 'show'])->name('show');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminReturnsController::class, 'update'])->name('update');
        });

        // Product Bulk Actions (Web routes for admin panel)
        Route::prefix('products')->name('products.')->group(function () {
            Route::post('/bulk-delete', [\App\Http\Controllers\ProductController::class, 'deleteAll'])->name('bulk-delete');
            Route::post('/bulk-duplicate', [\App\Http\Controllers\ProductController::class, 'replicate'])->name('bulk-duplicate');
        });

        // Vendor Product Import (CSV/Excel)
        Route::prefix('vendor/products')->name('vendor.products.')->group(function () {
            Route::get('/import', [\App\Http\Controllers\Admin\AdminVendorProductImportController::class, 'index'])->name('import');
            Route::get('/import/categories', [\App\Http\Controllers\Admin\AdminVendorProductImportController::class, 'getCategories'])->name('import.categories');
            Route::post('/import/upload', [\App\Http\Controllers\Admin\AdminVendorProductImportController::class, 'upload'])->name('import.upload');
            Route::get('/import/template', [\App\Http\Controllers\Admin\AdminVendorProductImportController::class, 'downloadTemplate'])->name('import.template');
        });

// Vendor Applications Management (Admin Only)
Route::prefix('vendor-applications')->name('vendor-applications.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'index'])->name('index');
    Route::get('/export', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'export'])->name('export');
    Route::get('/{id}', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'show'])->name('show');
    Route::put('/{id}/approve', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'approve'])->name('approve');
    Route::put('/{id}/reject', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'reject'])->name('reject');
    Route::post('/{id}/ban', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'ban'])->name('ban');
    Route::post('/{id}/unban', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'unban'])->name('unban');
    Route::post('/{storeId}/products/{productId}/approve', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'approveProduct'])->name('products.approve');
    Route::post('/{storeId}/products/bulk-approve', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'bulkApproveProducts'])->name('products.bulk-approve');
    Route::post('/{storeId}/products/approve-all', [\App\Http\Controllers\Admin\AdminVendorApplicationController::class, 'approveAllProducts'])->name('products.approve-all');
});

        // Commission Management
        Route::prefix('commissions')->name('commissions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCommissionHistoryController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\AdminCommissionHistoryController::class, 'export'])->name('export');
            Route::get('/monthly-stats', [\App\Http\Controllers\Admin\AdminCommissionHistoryController::class, 'monthlyStats'])->name('monthly-stats');
        });

        // Withdrawal Request Management
        Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminWithdrawRequestController::class, 'index'])->name('index');
            Route::get('/export', [\App\Http\Controllers\Admin\AdminWithdrawRequestController::class, 'export'])->name('export');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminWithdrawRequestController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [\App\Http\Controllers\Admin\AdminWithdrawRequestController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\Admin\AdminWithdrawRequestController::class, 'reject'])->name('reject');
        });

        // Vendor Dashboard & Management (Vendors Only)
        Route::prefix('vendor')->name('vendor.')->middleware('role:vendor')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Admin\VendorDashboardController::class, 'index'])->name('dashboard');
            Route::get('/products', [\App\Http\Controllers\Admin\VendorDashboardController::class, 'products'])->name('products');
            Route::get('/orders', [\App\Http\Controllers\Admin\VendorDashboardController::class, 'orders'])->name('orders');
            Route::get('/commissions', [\App\Http\Controllers\Admin\VendorDashboardController::class, 'commissions'])->name('commissions');
            Route::get('/commissions/export', [\App\Http\Controllers\Admin\VendorDashboardController::class, 'exportCommissions'])->name('commissions.export');

            Route::prefix('withdrawals')->name('withdrawals.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\VendorDashboardController::class, 'withdrawals'])->name('index');
                Route::post('/', [\App\Http\Controllers\Admin\VendorDashboardController::class, 'createWithdrawal'])->name('create');
            });
        });

        // Inventory Shipments Management
        Route::prefix('inventory-shipments')->name('inventory-shipments.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'store'])->name('store');
            Route::post('/bulk-delete', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'bulkDestroy'])->name('bulk-destroy');
            Route::post('/bulk-update', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'bulkUpdate'])->name('bulk-update');
            Route::post('/bulk-generate-stickers', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'bulkGenerateStickers'])->name('bulk-generate-stickers');
            Route::get('/{id}/generate-waybill', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'generateWaybill'])->name('generate-waybill');
            Route::get('/{id}/generate-sticker', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'generateSticker'])->name('generate-sticker');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/data', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'getShipment'])->name('get');
            Route::get('/{id}/history', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'history'])->name('history');
            Route::get('/scan/{compact}', [\App\Http\Controllers\Admin\AdminInventoryShipmentController::class, 'scanLookup'])->where('compact', '[0-9]+')->name('scan-lookup');
        });

        // Inventory Receiving (Scanner with batch processing)
        Route::prefix('inventory-receiving')->name('inventory-receiving.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminInventoryReceivingController::class, 'index'])->name('index');
            Route::post('/scan', [\App\Http\Controllers\Admin\AdminInventoryReceivingController::class, 'scan'])->name('scan');
            Route::delete('/{shipmentId}/remove', [\App\Http\Controllers\Admin\AdminInventoryReceivingController::class, 'removeItem'])->name('remove');
            Route::post('/clear', [\App\Http\Controllers\Admin\AdminInventoryReceivingController::class, 'clearList'])->name('clear');
            Route::post('/bulk-delete', [\App\Http\Controllers\Admin\AdminInventoryReceivingController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/save', [\App\Http\Controllers\Admin\AdminInventoryReceivingController::class, 'saveReceiving'])->name('save');
            Route::get('/count', [\App\Http\Controllers\Admin\AdminInventoryReceivingController::class, 'getScannedItemsCount'])->name('count');
            Route::get('/debug/{shipmentId}', [\App\Http\Controllers\Admin\AdminInventoryReceivingController::class, 'debugOrderItem'])->name('debug');
            Route::get('/history', [\App\Http\Controllers\Admin\AdminInventoryReceivingController::class, 'scanHistory'])->name('history');
        });

        // Support Tickets Management
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TicketController::class, 'index'])->name('index');
            Route::get('/{ticket}', [\App\Http\Controllers\Admin\TicketController::class, 'show'])->name('show');
            Route::post('/{ticket}/reply', [\App\Http\Controllers\Admin\TicketController::class, 'reply'])->name('reply');
            Route::patch('/{ticket}/status', [\App\Http\Controllers\Admin\TicketController::class, 'updateStatus'])->name('status');
            Route::patch('/{ticket}/assign', [\App\Http\Controllers\Admin\TicketController::class, 'assign'])->name('assign');
            Route::post('/{ticket}/close', [\App\Http\Controllers\Admin\TicketController::class, 'close'])->name('close');
            Route::post('/{ticket}/reopen', [\App\Http\Controllers\Admin\TicketController::class, 'reopen'])->name('reopen');
        });

        // Invoices & Quotations Management
        Route::prefix('invoices-quotations')->name('invoices-quotations.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'create'])->name('create');

            // AJAX endpoints (MUST be before {id} routes)
            Route::get('/search/products', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'searchProducts'])->name('search-products');
            Route::get('/search/users', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'searchUsers'])->name('search-users');
            Route::get('/users/{userId}/addresses', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'getUserAddresses'])->name('user-addresses');

            // Auto-save endpoints
            Route::post('/autosave', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'autoSave'])->name('autosave');
            Route::get('/resume', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'resume'])->name('resume');
            Route::delete('/autosave/discard', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'discardAutoSave'])->name('autosave-discard');

            // Stats route (MUST be before {id} routes)
            Route::get('/stats', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'stats'])->name('stats');

            // Resource routes with {id} parameter
            Route::post('/', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'update'])->name('update');
            Route::patch('/{id}/status', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/pdf', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'downloadPdf'])->name('pdf');
            Route::get('/{id}/preview', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'preview'])->name('preview');

            // Conversion routes
            Route::post('/{id}/convert-to-invoice', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'convertToInvoice'])->name('convert-to-invoice');
            Route::post('/{id}/convert-to-order', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'convertToOrder'])->name('convert-to-order');
            Route::patch('/{id}/convert-type', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'convertType'])->name('convert-type');

            // Email route
            Route::post('/{id}/send-email', [\App\Http\Controllers\Admin\AdminInvoiceQuotationController::class, 'sendEmail'])->name('send-email');

        });

        // Order Products ETA Tracking (Overdue Items)
        Route::prefix('order-products-eta')->name('order-products-eta.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminOrderProductEtaController::class, 'index'])
                ->name('index')
                ->middleware('can:eta-overdue.index');
            Route::get('/export', [\App\Http\Controllers\Admin\AdminOrderProductEtaController::class, 'export'])
                ->name('export')
                ->middleware('can:eta-overdue.index');
        });

        // Gift Vouchers Management (Admin Only)
        Route::prefix('vouchers')->name('vouchers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminVoucherController::class, 'index'])->name('index');
            Route::get('/stats', [\App\Http\Controllers\Admin\AdminVoucherController::class, 'stats'])->name('stats');
            Route::get('/{id}', [\App\Http\Controllers\Admin\AdminVoucherController::class, 'show'])->name('show');
        });

        // System Tickets Management
        Route::prefix('system-tickets')->name('system-tickets.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SystemTicketController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\SystemTicketController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\SystemTicketController::class, 'store'])->name('store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\SystemTicketController::class, 'show'])->name('show');
            Route::put('/{id}', [\App\Http\Controllers\Admin\SystemTicketController::class, 'update'])->name('update');
            Route::post('/{id}/reopen', [\App\Http\Controllers\Admin\SystemTicketController::class, 'reopen'])->name('reopen');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\SystemTicketController::class, 'destroy'])->name('destroy');
        });

        // Currency Management
        Route::prefix('currencies')->name('currencies.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminCurrencyController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AdminCurrencyController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\Admin\AdminCurrencyController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\AdminCurrencyController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Admin\AdminCurrencyController::class, 'toggleStatus'])->name('toggle-status');
        });

        // Gateway Transactions (Pesepay, PayFast, DPO Zambia, Yoco, Order, Wallet, Vendor)
        Route::prefix('gateway-transactions')->name('gateway-transactions.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminGatewayTransactionsController::class, 'index'])->name('index');
            Route::get('/pesepay', [\App\Http\Controllers\Admin\AdminGatewayTransactionsController::class, 'pesepay'])->name('pesepay');
            Route::get('/payfast', [\App\Http\Controllers\Admin\AdminGatewayTransactionsController::class, 'payfast'])->name('payfast');
            Route::get('/dpo', [\App\Http\Controllers\Admin\AdminGatewayTransactionsController::class, 'dpo'])->name('dpo');
            Route::get('/yoco', [\App\Http\Controllers\Admin\AdminGatewayTransactionsController::class, 'yoco'])->name('yoco');
            Route::get('/order-transactions', [\App\Http\Controllers\Admin\AdminGatewayTransactionsController::class, 'orderTransactions'])->name('order-transactions');
            Route::get('/transactions', [\App\Http\Controllers\Admin\AdminGatewayTransactionsController::class, 'transactions'])->name('transactions');
            Route::get('/vendor-transactions', [\App\Http\Controllers\Admin\AdminGatewayTransactionsController::class, 'vendorTransactions'])->name('vendor-transactions');
        });

        // WhatsApp Chat Agent Management
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            // Job Titles (AJAX)
            Route::get('/job-titles', [\App\Http\Controllers\Admin\AdminWhatsappJobTitleController::class, 'index'])->name('job-titles.index');
            Route::post('/job-titles', [\App\Http\Controllers\Admin\AdminWhatsappJobTitleController::class, 'store'])->name('job-titles.store');
            Route::put('/job-titles/{id}', [\App\Http\Controllers\Admin\AdminWhatsappJobTitleController::class, 'update'])->name('job-titles.update');
            Route::delete('/job-titles/{id}', [\App\Http\Controllers\Admin\AdminWhatsappJobTitleController::class, 'destroy'])->name('job-titles.destroy');

            // Agents
            Route::get('/agents', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'index'])->name('agents.index');
            Route::post('/agents', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'store'])->name('agents.store');
            Route::put('/agents/{id}', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'update'])->name('agents.update');
            Route::delete('/agents/{id}', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'destroy'])->name('agents.destroy');
            Route::post('/agents/{id}/upload-photo', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'uploadPhoto'])->name('agents.upload-photo');
            Route::get('/agents/by-user/{userId}', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'getByUser'])->name('agents.by-user');

            // Self-service (profile page)
            Route::post('/my-profile', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'updateMyProfile'])->name('my-profile.update');
            Route::post('/my-profile/photo', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'uploadMyPhoto'])->name('my-profile.photo');

            // AJAX helpers for modal dropdowns
            Route::get('/roles', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'getRoles'])->name('roles');
            Route::get('/users-by-role', [\App\Http\Controllers\Admin\AdminWhatsappAgentController::class, 'getUsersByRole'])->name('users-by-role');
        });

        // Promo Templates Management
        Route::prefix('promo-templates')->name('promo-templates.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'store'])->name('store');
            Route::get('/currencies', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'getCurrencies'])->name('currencies');
            // Save images endpoint (before {id} routes)
            Route::post('/save-images', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'saveImages'])->name('save-images');
            // Remove background from product image (rembg)
            Route::post('/remove-bg', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'removeBackground'])->name('remove-bg');
            // Per-template routes
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/duplicate', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'duplicate'])->name('duplicate');
            Route::get('/{id}/generate', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'generate'])->name('generate');
            Route::get('/{id}/generate-sku', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'generateSku'])->name('generate-sku');
            Route::get('/{id}/generate-sku-bg', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'generateSkuBg'])->name('generate-sku-bg');
        });

        // Save BG-convert banner images endpoint (outside {id} prefix to avoid conflicts)
        Route::post('/promo-templates/save-images-bg', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'saveImagesBg'])->name('promo-templates.save-images-bg');

        // Serve cached no-background product images (bypasses symlink issues)
        Route::get('/promo-nobg/{file}', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'servePromoNoBg'])->name('promo-nobg.serve');

        // Serve BG-convert banner images (bypasses symlink issues)
        Route::get('/promo-banners-bg/{file}', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'servePromoBannerBg'])->name('promo-banners-bg.serve');

        // Auction Management
        Route::prefix('auctions')->name('auctions.')->group(function () {
            Route::get('/',              [\App\Http\Controllers\Admin\AdminAuctionController::class, 'index'])->name('index');
            Route::get('/create',        [\App\Http\Controllers\Admin\AdminAuctionController::class, 'create'])->name('create');
            Route::post('/',             [\App\Http\Controllers\Admin\AdminAuctionController::class, 'store'])->name('store');
            Route::get('/search-products', [\App\Http\Controllers\Admin\AdminAuctionController::class, 'searchProducts'])->name('search-products');
            // ── Auction Statistics (must be before /{auction} routes) ─────────
            Route::get('/statistics',    [\App\Http\Controllers\Admin\AdminAuctionController::class, 'statistics'])->name('statistics');
            // ── Auction Settings (must be before /{auction} routes) ──────────
            Route::get('/settings',      [\App\Http\Controllers\Admin\AdminAuctionController::class, 'settings'])->name('settings');
            Route::post('/settings',     [\App\Http\Controllers\Admin\AdminAuctionController::class, 'saveSettings'])->name('settings.save');
            // ── Bulk Actions (must be before /{auction} wildcard) ──────────
            Route::post('/bulk-action',  [\App\Http\Controllers\Admin\AdminAuctionController::class, 'bulkAction'])->name('bulk-action');
            // ── Deposit Refunds (must be before /{auction} wildcard) ──────────
            Route::get('/deposit-refunds',        [\App\Http\Controllers\Admin\AdminAuctionController::class, 'depositRefundsPage'])->name('deposit-refunds');
            Route::post('/deposit-refunds/{refund}/update',       [\App\Http\Controllers\Admin\AdminAuctionController::class, 'processDepositRefund'])->name('deposit-refunds.update');
            Route::post('/deposit-refunds/{refund}/credit-wallet', [\App\Http\Controllers\Admin\AdminAuctionController::class, 'creditRefundToWallet'])->name('deposit-refunds.credit-wallet');
            // ── Won Auctions (must be before /{auction} wildcard) ──────────
            Route::get('/won',                     [\App\Http\Controllers\Admin\AdminAuctionController::class, 'wonAuctions'])->name('won');
            // ── Fulfilled Items (must be before /{auction} wildcard) ──────────
            Route::get('/fulfilled',               [\App\Http\Controllers\Admin\AdminAuctionController::class, 'fulfilledItems'])->name('fulfilled');
            // ── Unpaid Items + Bans (must be before /{auction} wildcard) ─────
            Route::get('/unpaid',                  [\App\Http\Controllers\Admin\AdminAuctionController::class, 'unpaidItems'])->name('unpaid');
            Route::get('/bans',                    [\App\Http\Controllers\Admin\AdminAuctionController::class, 'bansPage'])->name('bans');
            Route::post('/bans/{ban}/lift',        [\App\Http\Controllers\Admin\AdminAuctionController::class, 'liftBan'])->name('bans.lift');
            // ── Deposits page ─────────────────────────────────────────────────
            Route::get('/deposits',                   [\App\Http\Controllers\Admin\AdminAuctionController::class, 'deposits'])->name('deposits');
            // ── Active Bidders (must be before /{auction} wildcard) ───────────
            Route::get('/bidders',                    [\App\Http\Controllers\Admin\AdminAuctionController::class, 'bidders'])->name('bidders');
            Route::post('/bidders/{user}/toggle',     [\App\Http\Controllers\Admin\AdminAuctionController::class, 'toggleBidderApproval'])->name('bidders.toggle');
            Route::post('/bidders/add-deposit',       [\App\Http\Controllers\Admin\AdminAuctionController::class, 'addDeposit'])->name('bidders.add-deposit');
            Route::get('/bidders/search-users',       [\App\Http\Controllers\Admin\AdminAuctionController::class, 'searchUsersForDeposit'])->name('bidders.search-users');
            // ── Drag-and-drop reorder (must be before /{auction} wildcard) ────
            Route::post('/reorder',                   [\App\Http\Controllers\Admin\AdminAuctionController::class, 'reorderAuctions'])->name('reorder');
            Route::post('/{auction}/mark-ready',   [\App\Http\Controllers\Admin\AdminAuctionController::class, 'markReady'])->name('mark-ready');
            Route::post('/{auction}/mark-paid',    [\App\Http\Controllers\Admin\AdminAuctionController::class, 'markPaid'])->name('mark-paid');
            Route::patch('/{auction}/update-status',[\App\Http\Controllers\Admin\AdminAuctionController::class, 'updateStatus'])->name('update-status');
            Route::post('/{auction}/send-reminder',[\App\Http\Controllers\Admin\AdminAuctionController::class, 'sendReminder'])->name('send-reminder');
            Route::get('/{auction}/participants', [\App\Http\Controllers\Admin\AdminAuctionController::class, 'participants'])->name('participants');
            Route::get('/{auction}/stats',        [\App\Http\Controllers\Admin\AdminAuctionController::class, 'auctionStats'])->name('auction-stats');
            Route::get('/{auction}',        [\App\Http\Controllers\Admin\AdminAuctionController::class, 'show'])->name('show');
            Route::get('/{auction}/edit',[\App\Http\Controllers\Admin\AdminAuctionController::class, 'edit'])->name('edit');
            Route::put('/{auction}',     [\App\Http\Controllers\Admin\AdminAuctionController::class, 'update'])->name('update');
            Route::delete('/{auction}',  [\App\Http\Controllers\Admin\AdminAuctionController::class, 'destroy'])->name('destroy');
            Route::post('/{auction}/end',[\App\Http\Controllers\Admin\AdminAuctionController::class, 'end'])->name('end');
            Route::delete('/{auction}/images', [\App\Http\Controllers\Admin\AdminAuctionController::class, 'removeImage'])->name('remove-image');
        });
    });

    // Stream saved promo banner images — bypasses nginx symlink (disable_symlinks)
    Route::get('/promo-banners/{file}', [\App\Http\Controllers\Admin\PromoTemplateController::class, 'servePromoBanner'])
        ->name('admin.promo-banners.serve')
        ->where('file', '[^/]+');

});


