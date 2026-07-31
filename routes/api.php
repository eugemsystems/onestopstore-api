<?php

use App\Http\Controllers\ProductImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SitemapApiController;
use App\Http\Controllers\Api\ProductSkuExportController;

// ── Auction (public - no auth required) ─────────────────────────────────────
Route::prefix('auctions')->group(function () {
    Route::get('/', [\App\Http\Controllers\AuctionController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\AuctionController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/{id}/bids', [\App\Http\Controllers\AuctionController::class, 'bids'])->where('id', '[0-9]+');
    Route::get('/{id}/stream', [\App\Http\Controllers\AuctionController::class, 'stream'])->where('id', '[0-9]+');
});

// ── Auction event tracking (public, rate-limited) ────────────────────────────
Route::post('/auction-events', [\App\Http\Controllers\Api\AuctionEventController::class, 'store'])
    ->middleware('throttle:120,1');


// ── Auction bid placement (auth required) ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auctions/{id}/bid',             [\App\Http\Controllers\AuctionController::class, 'bid'])->where('id', '[0-9]+');
    Route::get('/auctions/my-bids',               [\App\Http\Controllers\AuctionController::class, 'myBids']);
    Route::get('/auctions/won',                   [\App\Http\Controllers\AuctionController::class, 'myWins']);
    Route::post('/auctions/{id}/pay',             [\App\Http\Controllers\AuctionController::class, 'pay'])->where('id', '[0-9]+');

    // ── Bid requirement check & deposit payment ──────────────────────────────
    Route::get('/auctions/{id}/bid-requirements', [\App\Http\Controllers\AuctionController::class, 'bidRequirements'])->where('id', '[0-9]+');
    Route::post('/auctions/{id}/pay-deposit',     [\App\Http\Controllers\AuctionController::class, 'payDeposit'])->where('id', '[0-9]+');
    Route::post('/auctions/{id}/confirm-deposit', [\App\Http\Controllers\AuctionController::class, 'confirmDeposit'])->where('id', '[0-9]+');

    // ── Auto-bid (set / get / cancel) ────────────────────────────────────────
    Route::get('/auctions/{id}/auto-bid',    [\App\Http\Controllers\Api\AuctionAutoBidController::class, 'show'])->where('id', '[0-9]+');
    Route::post('/auctions/{id}/auto-bid',   [\App\Http\Controllers\Api\AuctionAutoBidController::class, 'store'])->where('id', '[0-9]+');
    Route::delete('/auctions/{id}/auto-bid', [\App\Http\Controllers\Api\AuctionAutoBidController::class, 'destroy'])->where('id', '[0-9]+');

    // ── Auction fulfillment (delivery / collection) ───────────────────────────
    Route::get('/auctions/{id}/delivery-options',  [\App\Http\Controllers\AuctionController::class, 'deliveryOptions'])->where('id', '[0-9]+');

    // ── Auction deposit refunds ───────────────────────────────────────────────
    Route::get('/auctions/{id}/refund-info',      [\App\Http\Controllers\AuctionController::class, 'depositRefundInfo'])->where('id', '[0-9]+');
    Route::post('/auctions/{id}/request-refund',  [\App\Http\Controllers\AuctionController::class, 'requestDepositRefund'])->where('id', '[0-9]+');
    Route::get('/auctions/my-deposit-refunds',    [\App\Http\Controllers\AuctionController::class, 'myDepositRefunds']);

    // ── Admin: auction deposit refunds ───────────────────────────────────────
    Route::get('/admin/auctions/deposit-refunds',        [\App\Http\Controllers\Admin\AdminAuctionController::class, 'depositRefundsList']);
    Route::put('/admin/auctions/deposit-refunds/{id}',   [\App\Http\Controllers\Admin\AdminAuctionController::class, 'updateDepositRefund']);

    // ── Email verification resend (used by auction BidForm gate) ─────────────
    Route::post('/email/resend-verification',     [\App\Http\Controllers\AuthController::class, 'resendVerification']);
});

// ── Email verification (public — user clicks from email client, no session) ──
Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\AuthController::class, 'verifyEmail'])
    ->middleware('signed')
    ->name('api.verification.verify');


Route::prefix('sitemap')->middleware('throttle:120,1')->group(function () {
    Route::get('/products/count', [SitemapApiController::class, 'productCount']);
    Route::get('/products',       [SitemapApiController::class, 'productSlice']);   // ?offset=0&limit=50000
    Route::get('/categories',     [SitemapApiController::class, 'categories']);
    Route::get('/blogs',          [SitemapApiController::class, 'blogs']);
});

// WhatsApp Chat Agents — public, no auth required
Route::get('/whatsapp-agents', [\App\Http\Controllers\WhatsappAgentController::class, 'index']);

// App Version — public, CDN-cacheable
Route::get('/app-version', [\App\Http\Controllers\Api\AppVersionController::class, 'show'])
    ->middleware('throttle:120,1');

// Product SKU Export API
Route::prefix('products/skus')->middleware('throttle:60,1')->group(function () {
    Route::get('/list', [ProductSkuExportController::class, 'getSkusList']); // Optimized for millions
    Route::get('/filter', [ProductSkuExportController::class, 'getSkusFiltered']); // Filter by category with query params
    Route::get('/by-category', [ProductSkuExportController::class, 'getSkusByCategory']);
    Route::get('/by-category/{categoryId}', [ProductSkuExportController::class, 'getSkusByCategoryId']);
    Route::get('/summary', [ProductSkuExportController::class, 'getSkuSummary']);
    Route::get('/export-all', [ProductSkuExportController::class, 'exportAllSkus']);
});


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// ── Delete / deactivate own account ──────────────────────────────────────────
Route::middleware(['auth:sanctum'])->delete('/deleteaccount', 'App\Http\Controllers\UserController@deleteAccount');


// Countries & States
Route::apiResource('state', 'App\Http\Controllers\StateController');
Route::apiResource('country', 'App\Http\Controllers\CountryController');

// Settings & Options
Route::get('settings', 'App\Http\Controllers\SettingController@frontSettings');
Route::get('themeOptions', 'App\Http\Controllers\ThemeOptionController@index');
Route::get('settingsFront', 'App\Http\Controllers\SettingController@indexFront');
Route::get('themeOptionsFront', 'App\Http\Controllers\ThemeOptionController@indexFront');

// Webhooks
Route::post('/paypal/webhook', 'App\Http\Controllers\WebhookController@paypal')->name('paypal.webhook');
Route::match(['GET','POST'], '/pesepay/webhook', 'App\\Http\\Controllers\\WebhookController@pesepay')->name('pesepay.webhook');
Route::post('/payfast/webhook', 'App\Http\Controllers\WebhookController@payfast')->name('payfast.webhook');
Route::match(['GET','POST'], '/yoco/webhook', 'App\\Http\\Controllers\\WebhookController@yoco')->name('yoco.webhook');
Route::match(['GET','POST'], '/dpo/webhook', 'App\Http\Controllers\WebhookController@dpo')->name('dpo.webhook');
Route::get('/dpo/redirect', 'App\Http\Controllers\DpoController@redirect')->name('dpo.redirect');
Route::match(['GET','POST'], '/dpo/return', 'App\Http\Controllers\DpoController@return')->name('dpo.return');


// Authentication
Route::post('/login', 'App\Http\Controllers\AuthController@login');
Route::post('/register', 'App\Http\Controllers\AuthController@register');
Route::post('/forgot-password', 'App\Http\Controllers\AuthController@forgotPassword');
Route::post('/verify-token', 'App\Http\Controllers\AuthController@verifyToken');
Route::post('/update-password', 'App\Http\Controllers\AuthController@updatePassword');

// Analytics - Public tracking endpoints (no auth required)
Route::prefix('analytics')->middleware(['throttle:analytics', 'filter.bots'])->group(function () {
    Route::post('/track/page-view', 'App\Http\Controllers\Analytics\AnalyticsController@trackPageView');
    Route::post('/track/session', 'App\Http\Controllers\Analytics\AnalyticsController@trackSession');
    Route::post('/track/event', 'App\Http\Controllers\Analytics\AnalyticsController@trackEvent');
    Route::post('/track/cart-abandonment', 'App\Http\Controllers\Analytics\AnalyticsController@trackCartAbandonment');
    Route::get('/active-users', 'App\Http\Controllers\Analytics\AnalyticsController@getActiveUsers');

    // Protected analytics endpoints (admin only)
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/dashboard/stats', 'App\Http\Controllers\Analytics\AnalyticsController@getDashboardStats');
        Route::get('/dashboard/timeseries', 'App\Http\Controllers\Analytics\AnalyticsController@getTimeSeriesData');
        Route::get('/cart-abandonments', 'App\Http\Controllers\Analytics\AnalyticsController@getCartAbandonments');
        Route::get('/sessions/{sessionId}', 'App\Http\Controllers\Analytics\AnalyticsController@getSessionDetails');
    });
});

// Products
// Register specific routes BEFORE pattern restriction
Route::middleware('throttle:60,1')->group(function () {
    Route::get('product/by-tag',      'App\Http\Controllers\ProductController@getProductsByTag');
    Route::get('product/by-skus',     'App\Http\Controllers\ProductController@getProductsBySKUs');
    Route::get('product/recommendations/{identifier}', [\App\Http\Controllers\ProductRecommendationsController::class, 'show']);
    Route::get('productPagination',   'App\Http\Controllers\ProductController@indexPagination');
});
// search-promo runs iLIKE full-table scans — lower limit
Route::middleware('throttle:30,1')->get('product/search-promo', 'App\Http\Controllers\ProductController@searchForPromo');

// Apply numeric pattern only to dynamic {product} parameter (doesn't affect literal paths above)
Route::pattern('product', '[0-9]+');
//Route::get('product/slug/{slug}', 'App\Http\Controllers\ProductController@getProductBySlug');
Route::middleware('throttle:120,1')->get('product/slug/{slug}', 'App\Http\Controllers\ProductController@getProductBySlug')->where('slug', '[A-Za-z0-9\-\_]+');
Route::middleware('throttle:60,1')->apiResource('product', 'App\Http\Controllers\ProductController',[
  'only' => ['index', 'show'],
]);
Route::get('product/index-no-elastic-search', 'App\Http\Controllers\ProductController@indexNoElasticSearch');
Route::get('product/index-no-elastic-search/{id}', 'App\Http\Controllers\ProductController@indexNoElasticSearchShow');
// Search (Elasticsearch-backed, non-invasive)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('search/products',  [\App\Http\Controllers\SearchController::class, 'products']);
    Route::get('search/categories',[\App\Http\Controllers\SearchController::class, 'categories']);
});

// AI-powered natural language + multilingual search
Route::middleware('throttle:30,1')->group(function () {
    Route::get('natural-search',        [\App\Http\Controllers\NaturalSearchController::class, 'search']);
    Route::get('natural-search/expand', [\App\Http\Controllers\NaturalSearchController::class, 'expand']);
});

// Attributes
Route::apiResource('attribute', 'App\Http\Controllers\AttributeController',[
  'only' => ['index', 'show'],
]);

// Attribute Values
Route::apiResource('attribute-value', 'App\Http\Controllers\AttributeValueController',[
  'only' => ['index', 'show'],
]);

// Categories
Route::apiResource('category', 'App\Http\Controllers\CategoryController',[
  'only' => ['index', 'show', 'indexFront'],
]);
Route::get('categoryFront', 'App\Http\Controllers\CategoryController@indexFront');

// Brands
Route::apiResource('brand', 'App\Http\Controllers\BrandController',[
  'only' => ['index', 'show'],
]);

// Reviews
Route::apiResource('review', 'App\Http\Controllers\ReviewController', [
  'only' => ['index', 'show'],
]);

// Store/Vendor Registration (Public)
Route::post('store/register', 'App\Http\Controllers\StoreController@store');
Route::post('store', 'App\Http\Controllers\StoreController@store');

// Public Store Lookup (by slug for frontend store pages)
Route::get('store/slug/{slug}', 'App\Http\Controllers\StoreController@getStoreBySlug');

// Pages
Route::apiResource('page', 'App\Http\Controllers\PageController',[
  'only' => ['index', 'show'],
]);

// Order Status
Route::apiResource('orderStatus', 'App\Http\Controllers\OrderStatusController',[
  'only' => ['index', 'show'],
]);

// Blogs
Route::apiResource('blog', 'App\Http\Controllers\BlogController', [
  'only' => ['index', 'show'],
]);
Route::get('blog/slug/{slug}', 'App\Http\Controllers\BlogController@getBlogsBySlug');

// Pages
Route::apiResource('page', 'App\Http\Controllers\PageController', [
  'only' => ['index', 'show'],
]);
Route::get('page/slug/{slug}', 'App\Http\Controllers\PageController@getPagesBySlug');

// Taxes
Route::apiResource('tax', 'App\Http\Controllers\TaxController', [
  'only' => ['index', 'show'],
]);

// Coupons
Route::apiResource('coupon', 'App\Http\Controllers\CouponController', [
  'only' => ['index', 'show'],
]);

// Currencies
Route::apiResource('currency', 'App\Http\Controllers\CurrencyController', [
  'only' => ['index', 'show'],
]);
Route::get('currencyFront', 'App\Http\Controllers\CurrencyController@indexFront');

// Faqs
Route::apiResource('faq', 'App\Http\Controllers\FaqController', [
  'only' => ['index', 'show'],
]);

// Home
Route::apiResource('home', 'App\Http\Controllers\HomePageController', [
  'only' => ['index', 'show'],
]);

// Theme
Route::apiResource('theme', 'App\Http\Controllers\ThemeController',[
  'only' => ['index', 'show'],
]);

// Products
Route::apiResource('question-and-answer', 'App\Http\Controllers\QuestionAndAnswerController',[
  'only' => ['index', 'show'],
]);

// Reviews
Route::get('front/review', 'App\Http\Controllers\ReviewController@frontIndex');

// Import Fast (web UI)
Route::get('import-fast', [App\Http\Controllers\ImportFastController::class, 'index']);
Route::post('import-fast/upload', [App\Http\Controllers\ImportFastController::class, 'upload']);
Route::post('import-fast/upload-chunk', [App\Http\Controllers\ImportFastController::class, 'uploadChunk']);
Route::post('import-fast/upload-complete', [App\Http\Controllers\ImportFastController::class, 'uploadComplete']);
Route::post('import-fast/delete-files', [App\Http\Controllers\ImportFastController::class, 'deleteFiles']);
Route::post('import-fast/run', [App\Http\Controllers\ImportFastController::class, 'run']);
Route::get('import-fast/history', [App\Http\Controllers\ImportFastController::class, 'history']);
Route::get('import-fast/batch/{batchId}', [App\Http\Controllers\ImportFastController::class, 'batchDetails']);
Route::post('import-fast/resume-failed', [App\Http\Controllers\ImportFastController::class, 'resumeFailed']);

// Back Order Management (web UI)
Route::get('back-orders', [App\Http\Controllers\BackOrderController::class, 'index']);
Route::post('back-orders/upload', [App\Http\Controllers\BackOrderController::class, 'upload']);
Route::get('back-orders/files', [App\Http\Controllers\BackOrderController::class, 'getUploadedFiles']);
Route::post('back-orders/process', [App\Http\Controllers\BackOrderController::class, 'processFiles']);
Route::get('back-orders/status', [App\Http\Controllers\BackOrderController::class, 'getStatus']);
Route::post('back-orders/delete-files', [App\Http\Controllers\BackOrderController::class, 'deleteFiles']);
Route::post('back-orders/clear-all', [App\Http\Controllers\BackOrderController::class, 'clearAll']);
Route::get('back-orders/history', [App\Http\Controllers\BackOrderController::class, 'history']);

// ContactUs
Route::post('/contact-us', 'App\Http\Controllers\ContactUsController@contactUs');

// Marketing Feedback (Public - can be submitted with token or auth)
Route::post('marketing-feedback/submit', [App\Http\Controllers\MarketingFeedbackController::class, 'submit']);
Route::get('marketing-feedback/check-submitted', [App\Http\Controllers\MarketingFeedbackController::class, 'checkSubmitted']);

// Layby - Public routes
Route::post('/layby/check-eligibility', [App\Http\Controllers\LaybyController::class, 'checkEligibility']);

Route::group(['middleware' => ['localization','auth:sanctum']], function () {

  // Authentication
  Route::post('logout', 'App\Http\Controllers\AuthController@logout');

  // Layby - Protected routes
  Route::prefix('layby')->group(function() {
    // Document upload (chunked for large files)
    Route::post('/documents/upload-chunk', [App\Http\Controllers\LaybyController::class, 'uploadDocumentChunk']);
    Route::post('/documents/upload-complete', [App\Http\Controllers\LaybyController::class, 'uploadDocumentComplete']);
    Route::get('/uploaded-documents', [App\Http\Controllers\LaybyController::class, 'getUploadedDocuments']);

    Route::post('/apply', [App\Http\Controllers\LaybyController::class, 'apply']);
    Route::get('/my-applications', [App\Http\Controllers\LaybyController::class, 'myApplications']);
    Route::get('/check-existing', [App\Http\Controllers\LaybyController::class, 'checkExisting']);
    Route::get('/applications/{id}', [App\Http\Controllers\LaybyController::class, 'show']);
    Route::put('/applications/{id}/document', [App\Http\Controllers\LaybyController::class, 'updateDocument']);
    Route::post('/applications/{id}/payment', [App\Http\Controllers\LaybyController::class, 'makePayment']);
  });


  // Account
  Route::get('self', 'App\Http\Controllers\AccountController@self');
  Route::put('updateProfile', 'App\Http\Controllers\AccountController@updateProfile');
  Route::put('updatePassword', 'App\Http\Controllers\AccountController@updatePassword');
  Route::put('updateProfile', 'App\Http\Controllers\AccountController@updateProfile');
  Route::put('updatePassword', 'App\Http\Controllers\AccountController@updatePassword');
  Route::put('updateStoreProfile', 'App\Http\Controllers\AccountController@updateStoreProfile');

  // Address
  Route::apiResource('address', 'App\Http\Controllers\AddressController');

  // Payment Account
  Route::apiResource('paymentAccount', 'App\Http\Controllers\PaymentAccountController');

  Route::get('badge','App\Http\Controllers\BadgeController@index');

  // Notifications
  Route::get('notifications', 'App\Http\Controllers\NotificationController@index');
  Route::put('notifications/markAsRead', 'App\Http\Controllers\NotificationController@markAsRead');
  Route::delete('notifications/{id}', 'App\Http\Controllers\NotificationController@destroy');


  // ***********  Frontend   ***********
  Route::apiResource('cart', 'App\Http\Controllers\CartController');
  Route::apiResource('refund', 'App\Http\Controllers\RefundController');
  Route::apiResource('returns', 'App\Http\Controllers\ReturnController');
  Route::apiResource('compare', 'App\Http\Controllers\CompareController');
  Route::apiResource('wishlist', 'App\Http\Controllers\WishlistController');

  Route::post('rePayment', 'App\Http\Controllers\OrderController@rePayment');
  Route::get('trackOrder/{order_number}', 'App\Http\Controllers\OrderController@trackOrder');
  Route::get('verifyPayment/{order_number}', 'App\Http\Controllers\OrderController@verifyPayment');

  Route::put('cart', 'App\Http\Controllers\CartController@update');
  Route::post('sync/cart', 'App\Http\Controllers\CartController@sync');
  Route::post('clear/cart', 'App\Http\Controllers\CartController@clearCart');
  Route::put('replace/cart', 'App\Http\Controllers\CartController@replace');

  // **********************  Backend   *******************************

  // Dashboard
  Route::get('statistics/count', 'App\Http\Controllers\DashboardController@index');
  Route::get('dashboard/chart', 'App\Http\Controllers\DashboardController@chart');

  // Users
  Route::apiResource('user', 'App\Http\Controllers\UserController');
  Route::get('cached-users', 'App\Http\Controllers\UserController@cachedUsers')->middleware('can:user.index');
  Route::put('user/{id}/{status}', 'App\Http\Controllers\UserController@status')->middleware('can:user.edit');
  Route::post('user/csv/import', 'App\Http\Controllers\UserController@import')->middleware('can:user.create');
  Route::post('user/csv/export', 'App\Http\Controllers\UserController@export')->name('users.export')->middleware('can:user.index');
  Route::post('user/deleteAll', 'App\Http\Controllers\UserController@deleteAll')->middleware('can:user.destroy');
  Route::delete('user/address/{id}', 'App\Http\Controllers\UserController@deleteAddress')->middleware('can:user.edit');

  //Roles
  Route::apiResource('role', 'App\Http\Controllers\RoleController');
  Route::get('module', 'App\Http\Controllers\RoleController@modules');
  Route::post('role/deleteAll', 'App\Http\Controllers\RoleController@deleteAll')->middleware('can:role.destroy');

  // Products
  Route::apiResource('product', 'App\Http\Controllers\ProductController', [
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::post('product/replicate', 'App\Http\Controllers\ProductController@replicate')->middleware('can:product.edit');
  Route::post('productPagination/replicate', 'App\Http\Controllers\ProductController@replicate')->middleware('can:product.edit');
  Route::post('productPagination/deleteAll', 'App\Http\Controllers\ProductController@deleteAll')->middleware('can:product.destroy');
  Route::put('product/{id}/{status}', 'App\Http\Controllers\ProductController@status')->middleware('can:product.edit');
  Route::post('product/csv/export', 'App\Http\Controllers\ProductController@export')->name('products.export')->middleware('can:product.index');
  Route::post('product/csv/import', 'App\Http\Controllers\ProductController@import')->middleware('can:product.create');
  Route::put('product/approve/{id}/{status}', 'App\Http\Controllers\ProductController@approve')->middleware('can:product.edit');
  Route::put('product/permanently-disable/{id}/{disable}', 'App\Http\Controllers\ProductController@permanentlyDisable')->middleware('can:product.edit');
  Route::post('product/deleteAll', 'App\Http\Controllers\ProductController@deleteAll')->middleware('can:product.destroy');

  //Import
  Route::get('products/import', 'App\Http\Controllers\ProductImportController@showImportForm')->name('products.import.form');
  Route::post('products/import', 'App\Http\Controllers\ProductImportController@import')->name('products.import');

  // Attributes & Attribute Values
  Route::apiResource('attribute', 'App\Http\Controllers\AttributeController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::apiResource('attribute-value', 'App\Http\Controllers\AttributeValueController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::put('attribute/{id}/{status}', 'App\Http\Controllers\AttributeController@status')->middleware('can:attribute.edit');
  Route::post('attribute/csv/import', 'App\Http\Controllers\AttributeController@import')->middleware('can:attribute.create');
  Route::post('attribute/csv/export', 'App\Http\Controllers\AttributeController@export')->name('attributes.export')->middleware('can:attribute.index');
  Route::post('attribute/deleteAll', 'App\Http\Controllers\AttributeController@deleteAll')->middleware('can:attribute.destroy');

  // Categories
  Route::apiResource('category', 'App\Http\Controllers\CategoryController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::post('category/csv/import', 'App\Http\Controllers\CategoryController@import')->middleware('can:category.create');
  Route::post('category/csv/export', 'App\Http\Controllers\CategoryController@export')->name('categories.export')->middleware('can:category.index');
  Route::put('category/{id}/{status}', 'App\Http\Controllers\CategoryController@status')->middleware('can:category.edit');

  // Tags
  Route::apiResource('tag', 'App\Http\Controllers\TagController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::post('tag/csv/import', 'App\Http\Controllers\TagController@import')->middleware('can:tag.create');
  Route::post('tag/csv/export', 'App\Http\Controllers\TagController@export')->name('tags.export')->middleware('can:tag.index');
  Route::post('tag/deleteAll', 'App\Http\Controllers\TagController@deleteAll')->middleware('can:tag.destroy');
  Route::put('tag/{id}/{status}', 'App\Http\Controllers\TagController@status')->middleware('can:tag.edit');

  // Stores
  Route::apiResource('store', 'App\Http\Controllers\StoreController',[
    'only' => ['index', 'show', 'update', 'destroy'],
  ]);
  Route::post('store/deleteAll', 'App\Http\Controllers\StoreController@deleteAll')->middleware('can:store.destroy');
  Route::put('store/approve/{id}/{status}', 'App\Http\Controllers\StoreController@approve')->middleware('can:store.edit');
  Route::put('store/{id}/{status}', 'App\Http\Controllers\StoreController@status')->middleware('can:store.edit');

  // Vendor Wallets
  Route::get('wallet/vendor', 'App\Http\Controllers\VendorWalletController@index')->middleware('can:vendor_wallet.index');
  Route::post('debit/vendorWallet','App\Http\Controllers\VendorWalletController@debit')->middleware('can:vendor_wallet.debit');
  Route::post('credit/vendorWallet','App\Http\Controllers\VendorWalletController@credit')->middleware('can:vendor_wallet.credit');

  // Commission Histories
  Route::apiResource('commissionHistory', 'App\Http\Controllers\CommissionHistoryController',[
    'only' => ['index', 'show'],
  ]);

  // Withdraw Request
  Route::apiResource('withdrawRequest', 'App\Http\Controllers\WithdrawRequestController');

  // Transactions (payments)
  Route::get('transactions/dpo-zambia', 'App\Http\Controllers\TransactionController@dpoZambia');
  Route::get('transactions/payfast', 'App\Http\Controllers\TransactionController@payfast');
  Route::get('transactions/pesepay', 'App\Http\Controllers\TransactionController@pesepay');
  Route::get('transactions/yoco', 'App\Http\Controllers\TransactionController@yoco');

  // Orders
  Route::post('order/{order}/cancel-item', 'App\Http\Controllers\OrderController@adminCancelItem')->middleware('can:order.edit');
  Route::apiResource('order', 'App\Http\Controllers\OrderController');
  Route::post('checkout','App\Http\Controllers\CheckoutController@verifyCheckout');
  Route::apiResource('order-notes', 'App\Http\Controllers\OrderNoteController')->except(['create','edit']);
  Route::post('rePayment', 'App\Http\Controllers\OrderController@rePayment');
  Route::get('trackOrder/{order_number}', 'App\Http\Controllers\OrderController@trackOrder');
  Route::get('verifyPayment/{order_number}', 'App\Http\Controllers\OrderController@verifyPayment');

  // Order Status
  Route::apiResource('orderStatus', 'App\Http\Controllers\OrderStatusController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::post('orderStatus/deleteAll', 'App\Http\Controllers\OrderStatusController@deleteAll')->middleware('can:order_status.destroy');
  Route::put('orderStatus/{id}/{status}', 'App\Http\Controllers\OrderStatusController@status')->middleware('can:order_status.edit');

  // Attachments
  Route::apiResource('attachment', 'App\Http\Controllers\AttachmentController');
  Route::post('attachment/deleteAll', 'App\Http\Controllers\AttachmentController@deleteAll')->middleware('can:attachment.destroy');

  // Blogs
  Route::apiResource('blog', 'App\Http\Controllers\BlogController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::post('blog/deleteAll', 'App\Http\Controllers\BlogController@deleteAll')->middleware('can:blog.destroy');
  Route::put('blog/{id}/{status}', 'App\Http\Controllers\BlogController@status')->middleware('can:blog.edit');

  // Pages
  Route::apiResource('page', 'App\Http\Controllers\PageController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::post('page/deleteAll', 'App\Http\Controllers\PageController@deleteAll')->middleware('can:page.destroy');
  Route::put('page/{id}/{status}', 'App\Http\Controllers\PageController@status')->middleware('can:page.edit');

  // Tax
  Route::apiResource('tax', 'App\Http\Controllers\TaxController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::post('tax/deleteAll', 'App\Http\Controllers\TaxController@deleteAll')->middleware('can:tax.destroy');
  Route::put('tax/{id}/{status}', 'App\Http\Controllers\TaxController@status')->middleware('can:tax.edit');

  // Shipping
  Route::apiResource('shipping', 'App\Http\Controllers\ShippingController');
  Route::put('shipping/{id}/{status}', 'App\Http\Controllers\ShippingController@status')->middleware('can:shipping.edit');

  // Shipping Rule
  Route::apiResource('shippingRule', 'App\Http\Controllers\ShippingRuleController');
  Route::put('shippingRule/{id}/{status}', 'App\Http\Controllers\ShippingRuleController@status')->middleware('can:shipping.edit');

  // Coupon
  Route::apiResource('coupon', 'App\Http\Controllers\CouponController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::put('coupon/{id}/{status}', 'App\Http\Controllers\CouponController@status')->middleware('can:coupon.edit');
  Route::post('coupon/deleteAll', 'App\Http\Controllers\CouponController@deleteAll')->middleware('can:coupon.destroy');

  // Currencies
  Route::apiResource('currency', 'App\Http\Controllers\CurrencyController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::put('currency/{id}/{status}', 'App\Http\Controllers\CurrencyController@status')->middleware('can:currency.edit');
  Route::post('currency/deleteAll', 'App\Http\Controllers\CurrencyController@deleteAll')->middleware('can:currency.destroy');

  // Points
  Route::get('points/consumer', 'App\Http\Controllers\PointsController@index')->middleware('can:point.index');
  Route::post('credit/points','App\Http\Controllers\PointsController@credit')->middleware('can:point.credit');
  Route::post('debit/points','App\Http\Controllers\PointsController@debit')->middleware('can:point.debit');

  // Wallets
  Route::get('wallet/consumer', 'App\Http\Controllers\WalletController@index')->middleware('can:wallet.index');
  Route::get('wallet/consumer/self', 'App\Http\Controllers\WalletController@indexSelf');
  Route::post('credit/wallet','App\Http\Controllers\WalletController@credit')->middleware('can:wallet.credit');
  Route::post('debit/wallet','App\Http\Controllers\WalletController@debit')->middleware('can:wallet.debit');
  Route::post('wallet/refund-all', 'App\Http\Controllers\WalletController@refundAll');

  // Reviews
  Route::apiResource('review', 'App\Http\Controllers\ReviewController');
  Route::post('review/deleteAll', 'App\Http\Controllers\ReviewController@deleteAll')->middleware('can:review.destroy');

  // faqs
  Route::apiResource('faq', 'App\Http\Controllers\FaqController',[
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::put('faq/{id}/{status}', 'App\Http\Controllers\FaqController@status')->middleware('can:faq.edit');
  Route::post('faq/deleteAll', 'App\Http\Controllers\FaqController@deleteAll')->middleware('can:faq.destroy');

  // Quention And Answer
  Route::apiResource('question-and-answer', 'App\Http\Controllers\QuestionAndAnswerController', [
    'only' => ['store', 'update', 'destroy'],
  ]);
  Route::post('question-and-answer/feedback', 'App\Http\Controllers\QuestionAndAnswerController@feedback')->middleware('can:question_and_answer.create');

  // Themes
  Route::apiResource('theme', 'App\Http\Controllers\ThemeController',[
      'only' => ['update']
  ]);

  // Home
  Route::apiResource('home', 'App\Http\Controllers\HomePageController', [
    'only' => ['update'],
  ]);

  // Theme Options
  Route::put('themeOptions', 'App\Http\Controllers\ThemeOptionController@update')->middleware('can:theme_option.edit');

  // Settings
  Route::put('settings', 'App\Http\Controllers\SettingController@update')->middleware('can:setting.edit');
});

Route::prefix('backend')->group(function (){
    Route::post('/login', 'App\Http\Controllers\AuthController@backendLogin');
    Route::group(['middleware' => ['localization','auth:sanctum']], function () {
        Route::get('settings', 'App\Http\Controllers\SettingController@index')->middleware('can:setting.index');
    });
});

// Admin Vendor Applications
Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
    // App Version management
    Route::get('/app-versions', [\App\Http\Controllers\Admin\AdminAppVersionController::class, 'index']);
    Route::put('/app-versions/{platform}', [\App\Http\Controllers\Admin\AdminAppVersionController::class, 'upsert']);

    Route::prefix('vendor-applications')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\VendorApplicationController::class, 'index']);
        Route::get('/export', [App\Http\Controllers\Admin\VendorApplicationController::class, 'export']);
        Route::get('/{id}', [App\Http\Controllers\Admin\VendorApplicationController::class, 'show']);
        Route::put('/{id}/approval', [App\Http\Controllers\Admin\VendorApplicationController::class, 'updateApprovalStatus']);
    });
});

// CRM API endpoints - require authentication only (no specific abilities needed for machine-to-machine integration)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/crm-orders', [App\Http\Controllers\OrderController::class, 'indexCrm']);
    Route::get('/crm-orders/{order}', [App\Http\Controllers\OrderController::class, 'show']);
    Route::put('/crm-orders/{order}', [App\Http\Controllers\OrderController::class, 'update']);
    Route::put('/order/{order_number}/item-status', [App\Http\Controllers\OrderController::class, 'updateItemStatus'])->where('order_number', '.*');
    Route::put('/crm-inventory-shipments', [App\Http\Controllers\Api\InventoryShipmentController::class, 'store']);
    Route::patch('/crm-inventory-shipments/{id}/signed-by', [App\Http\Controllers\Api\InventoryShipmentController::class, 'updateSignedBy']);

    // Ticket System Routes
    Route::prefix('tickets')->group(function () {
        Route::get('/', [App\Http\Controllers\TicketController::class, 'index']);
        Route::post('/', [App\Http\Controllers\TicketController::class, 'store']);
        Route::get('/statistics', [App\Http\Controllers\TicketController::class, 'statistics']);
        Route::get('/{id}', [App\Http\Controllers\TicketController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\TicketController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\TicketController::class, 'destroy']);
        Route::post('/{id}/messages', [App\Http\Controllers\TicketController::class, 'addMessage']);
        Route::post('/{id}/close', [App\Http\Controllers\TicketController::class, 'close']);
        Route::post('/{id}/reopen', [App\Http\Controllers\TicketController::class, 'reopen']);
    });

    // Voucher/Gift Card Routes
    Route::prefix('vouchers')->group(function () {
        Route::post('/check', [App\Http\Controllers\Api\VoucherController::class, 'checkVoucher']);
        Route::post('/redeem', [App\Http\Controllers\Api\VoucherController::class, 'redeemVoucher']);
        Route::get('/my-vouchers', [App\Http\Controllers\Api\VoucherController::class, 'myVouchers']);
        Route::get('/redeemed', [App\Http\Controllers\Api\VoucherController::class, 'redeemedVouchers']);
        Route::post('/{voucherId}/resend-email', [App\Http\Controllers\Api\VoucherController::class, 'resendEmail']);
        Route::get('/wallet-balance', [App\Http\Controllers\Api\VoucherController::class, 'walletBalance']);
        Route::get('/{id}', [App\Http\Controllers\Api\VoucherController::class, 'show']);
    });

    // Inventory Receiving Routes (Mobile App)
    Route::prefix('inventory-receiving')->group(function () {
        Route::post('/scan', [App\Http\Controllers\Api\InventoryReceivingController::class, 'scan']);
        Route::get('/list', [App\Http\Controllers\Api\InventoryReceivingController::class, 'getList']);
        Route::delete('/{id}', [App\Http\Controllers\Api\InventoryReceivingController::class, 'removeItem']);
        Route::post('/clear', [App\Http\Controllers\Api\InventoryReceivingController::class, 'clearList']);
        Route::post('/save', [App\Http\Controllers\Api\InventoryReceivingController::class, 'save']);
    });

    // Inventory Shipments Routes (Mobile App)
    Route::prefix('inventory-shipments')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\InventoryShipmentController::class, 'index']);
        Route::get('/filters', [App\Http\Controllers\Api\InventoryShipmentController::class, 'filters']);
        Route::get('/staff-raines', [App\Http\Controllers\Api\InventoryShipmentController::class, 'staffRaines']);
        Route::get('/{id}', [App\Http\Controllers\Api\InventoryShipmentController::class, 'show']);
        Route::get('/{id}/qr-code', [App\Http\Controllers\Api\InventoryShipmentController::class, 'getQRCode']);
    });

});


Route::get('/product-feed-currencies-all', function(){ return productFeedCurrenciesAll(); })->name('product-feed.product-feed-currencies-all');
//Route::get('/product-feed-categories-tree-root', function(){ return productFeedCategoriesTreeRoot(); })->name('product-feed.product-feed-categories-tree-root');

Route::get('/product-feed-categories-tree-root', [App\Http\Controllers\ProductController::class, 'getCategoryTree'])->name('product-feed.product-feed-categories-tree-root');

// Stream layby documents via Laravel (bypasses nginx symlink issues)
// URL format: /api/layby-files/layby_documents/2026/03/06/{uuid}.pdf
// Files are UUID-named so unguessable — no auth guard needed.
Route::get('/layby-files/{path}', function ($path) {
    // $path already contains the full relative path (e.g. layby_documents/2026/03/06/file.pdf)
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return Storage::disk('public')->response($path);
})->where('path', '.*');

// Stream auction images via Laravel (bypasses nginx symlink issues on production)
// URL format: /api/auction-files/auction-images/{filename}
// Files are UUID-named so unguessable — no auth guard needed.
Route::get('/auction-files/{path}', function ($path) {
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return Storage::disk('public')->response($path);
})->where('path', '.*');

// Stream sitemap XML files via Laravel (same pattern as auction-files)
// URL format: /api/sitemaps/{filename}.xml
Route::get('/sitemaps/{path}', function ($path) {
    $filePath = "sitemaps/{$path}";
    if (!Storage::disk('public')->exists($filePath)) {
        abort(404);
    }

    return Storage::disk('public')->response($filePath, 200, [
        'Content-Type' => 'application/xml',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');
