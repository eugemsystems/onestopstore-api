<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Attachment;
use App\Models\Variation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Manual permission checks - more reliable than middleware
        $this->middleware(function ($request, $next) {
            $action = $request->route()->getActionMethod();

            // Map actions to required permissions
            $permissionMap = [
                'index' => 'product.index',
                'vendorProducts' => 'product.index', // ✅ ADDED: Same permission as regular products
                'create' => 'product.create',
                'store' => 'product.create',
                'edit' => 'product.edit',
                'update' => 'product.edit',
                'toggleStatus' => 'product.edit',
                'destroy' => 'product.destroy',
                'storeAttribute' => 'product.create', // Allow attribute creation for product creators
                'storeVariation' => 'product.edit',
                'updateVariation' => 'product.edit',
                'deleteVariation' => 'product.edit',
                'searchAttributeValues' => 'product.edit', // For attribute search in variations
            ];

            // Check if this action requires a permission
            if (isset($permissionMap[$action])) {
                $requiredPermission = $permissionMap[$action];

                // Get user info for debugging
                $user = auth()->user();



                if (!$user->can($requiredPermission)) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Unauthorized. You need the '{$requiredPermission}' permission to perform this action.",
                            'debug' => [
                                'user' => $user->email,
                                'roles' => $user->roles->pluck('name'),
                                'permissions' => $user->getAllPermissions()->pluck('name'),
                                'required' => $requiredPermission
                            ]
                        ], 403);
                    }
                    abort(403, "Unauthorized. You need the '{$requiredPermission}' permission to perform this action. Your roles: " . $user->roles->pluck('name')->join(', '));
                }
            }

            return $next($request);
        });
    }

    /**
     * Display a listing of products with search and filter
     */
    public function index(Request $request)
    {
        // Optimized query with only necessary relationships and select specific columns
        $query = Product::query();

        // Search by SKU or Product Name/Title
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('sku', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        // Filter by region
        if ($request->filled('region')) {
            match ($request->region) {
                'zambia_only'   => $query->where('zambia_only', true),
                'zimbabwe_only' => $query->where('zimbabwe_only', true),
                'sa_only'       => $query->where('sa_only', true),
                'global'        => $query->where('zambia_only', false)->where('zimbabwe_only', false)->where('sa_only', false),
                default => null,
            };
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20)->withQueryString();

        // Use cached hierarchical categories from helper function
        $categories = collect();

        $brands = collect(); // Empty collection (brands feature not implemented)

        return view('admin.products.index', compact('products', 'categories', 'brands'));
    }

    /**
     * Product Search & Export page — search by keywords/name, filter, export to Excel
     */
    public function searchExport(Request $request)
    {
        $products = null;
        $totalFound = 0;
        $hasSearch = $request->hasAny(['search', 'status', 'stock_status', 'region', 'category_id', 'price_min', 'price_max']);

        if ($hasSearch) {
            $query = Product::with(['brand:id,name', 'categories:id,name,slug', 'product_thumbnail:id,image_url']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('sku', 'ILIKE', "%{$search}%")
                      ->orWhere('search_keywords', 'ILIKE', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('stock_status')) {
                $query->where('stock_status', $request->stock_status);
            }
            if ($request->filled('region')) {
                match ($request->region) {
                    'zambia_only'   => $query->where('zambia_only', true),
                    'zimbabwe_only' => $query->where('zimbabwe_only', true),
                    'sa_only'       => $query->where('sa_only', true),
                    'global'        => $query->where('zambia_only', false)->where('zimbabwe_only', false)->where('sa_only', false),
                    default => null,
                };
            }
            if ($request->filled('category_id')) {
                $query->whereHas('categories', fn($q) => $q->where('categories.id', $request->category_id));
            }
            if ($request->filled('price_min')) {
                $query->where('price', '>=', (float) $request->price_min);
            }
            if ($request->filled('price_max')) {
                $query->where('price', '<=', (float) $request->price_max);
            }

            $query->orderBy($request->get('sort_by', 'name'), $request->get('sort_order', 'asc'));

            $totalFound = (clone $query)->count();
            $products = $query->paginate(50)->withQueryString();
        }

        return view('admin.products.search-export', compact('products', 'totalFound', 'hasSearch'));
    }

    /**
     * Export searched products to Excel
     */
    public function exportExcel(Request $request)
    {
        $query = Product::with(['brand:id,name', 'categories:id,name']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('sku', 'ILIKE', "%{$search}%")
                  ->orWhere('search_keywords', 'ILIKE', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }
        if ($request->filled('region')) {
            match ($request->region) {
                'zambia_only'   => $query->where('zambia_only', true),
                'zimbabwe_only' => $query->where('zimbabwe_only', true),
                'sa_only'       => $query->where('sa_only', true),
                'global'        => $query->where('zambia_only', false)->where('zimbabwe_only', false)->where('sa_only', false),
                default => null,
            };
        }
        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn($q) => $q->where('categories.id', $request->category_id));
        }
        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float) $request->price_min);
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float) $request->price_max);
        }

        $query->orderBy($request->get('sort_by', 'name'), $request->get('sort_order', 'asc'));

        $products = $query->get();

        // Generate Excel using PhpSpreadsheet or simple CSV
        $filename = 'products_export_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($products) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, ['Name', 'SKU', 'Price']);

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->name,
                    $product->sku ?? '',
                    $product->price ?? 0,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display a listing of vendor products only (products with store_id)
     * Shows products added by vendors through their stores
     */
    public function vendorProducts(Request $request)
    {
        // Only show products that belong to vendors (have store_id)
        $query = Product::query()->whereNotNull('store_id'); // ✅ Key filter: Only vendor products

        // Search by SKU or Product Name/Title
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('sku', 'ILIKE', "%{$search}%")
                  ->orWhere('name', 'ILIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by approval status
        if ($request->filled('is_approved')) {
            $query->where('is_approved', $request->is_approved);
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Filter by store/vendor
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20)->withQueryString();

        // Use cached hierarchical categories from helper function
        $categories = collect();

        // Cache stores for 1 hour
        $stores = Cache::remember('admin_vendor_stores', 3600, function() {
            return \App\Models\Store::select('id', 'store_name')
                ->where('is_approved', 1)
                ->orderBy('store_name')
                ->get();
        });

        $brands = collect(); // Empty collection

        return view('admin.products.vendor-products', compact('products', 'categories', 'brands', 'stores'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        // Use cached data for categories and tags (needed for UI rendering)
        $categories = getCachedCategoriesForProductEdit();
        $tags = getCachedActiveTags();
        $taxes = getCachedActiveTaxes();

        // Don't load all brands - use AJAX search instead (18k+ brands cause performance issues)
        $brands = collect(); // Empty collection, will use Select2 AJAX

        return view('admin.products.create', compact('categories', 'tags', 'brands', 'taxes'));
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        // Generate unique request ID to track duplicate submissions
        $requestId = uniqid('product_create_', true);


        // Prevent duplicate submissions within 5 seconds
        if (session()->has('last_created_product_time')) {
            $lastCreateTime = session()->get('last_created_product_time');
            $timeDiff = time() - $lastCreateTime;

            if ($timeDiff < 5) {
                $productId = session()->get('last_created_product_id');


                return redirect()->route('admin.products.edit', $productId)
                    ->with('info', 'Product was already created.');
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,classified',
            'unit' => 'nullable|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'stock_status' => 'required|in:in_stock,out_of_stock',
            'brand_id' => 'nullable|exists:product_brands,id',
            'status' => 'required|boolean',
            'is_featured' => 'nullable|boolean',
            'is_trending' => 'nullable|boolean',
            'is_sale_enable' => 'nullable|boolean',
            'is_return' => 'nullable|boolean',
            'is_free_shipping' => 'nullable|boolean',
            'is_cod' => 'nullable|boolean',
            'is_external' => 'nullable|boolean',
            'is_approved' => 'nullable|boolean',
            'is_permanently_disabled' => 'nullable|boolean',
            'has_expedited_shipping' => 'nullable|boolean',
            'is_gift_card' => 'nullable|boolean',
            'zambia_only' => 'nullable|boolean',
            'zimbabwe_only' => 'nullable|boolean',
            'sa_only' => 'nullable|boolean',
            'voucher_validity_days' => 'nullable|integer|min:0',
            'safe_checkout' => 'nullable|boolean',
            'secure_checkout' => 'nullable|boolean',
            'social_share' => 'nullable|boolean',
            'encourage_order' => 'nullable|boolean',
            'encourage_view' => 'nullable|boolean',
            'shipping_days' => 'nullable|string',
            'standard_shipping_days' => 'nullable|string',
            'expedited_shipping_days' => 'nullable|string',
            'standard_shipping_price' => 'nullable|numeric|min:0',
            'expedited_shipping_price' => 'nullable|numeric|min:0',
            'estimated_delivery_text' => 'nullable|string',
            'return_policy_text' => 'nullable|string',
            'external_url' => 'nullable|url',
            'external_button_text' => 'nullable|string',
            'sale_starts_at' => 'nullable|date',
            'sale_expired_at' => 'nullable|date',
            'visible_time' => 'nullable|date',
            'categories' => 'nullable|array',
            'tags' => 'nullable|array',
            'tax_id' => 'nullable|exists:taxes,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'product_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'product_galleries.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'product_meta_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'size_chart_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        try {
            DB::beginTransaction();

            // Generate slug
            $validated['slug'] = Str::slug($validated['name']);

            // Ensure unique slug
            $originalSlug = $validated['slug'];
            $count = 1;
            while (Product::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $count;
                $count++;
            }

            // Create product first (without image IDs)
            $product = Product::create($validated);


            // Handle Product Thumbnail Upload
            if ($request->hasFile('product_thumbnail')) {
                try {
                    $file = $request->file('product_thumbnail');
                    $attachment = $this->uploadImage($file);

                    // Update product with thumbnail ID
                    $product->product_thumbnail_id = $attachment->id;
                    $product->save();

                } catch (\Exception $e) {
                    Log::error('Failed to upload product thumbnail', [
                        'request_id' => $requestId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Handle Product Meta Image Upload
            if ($request->hasFile('product_meta_image')) {
                try {
                    $file = $request->file('product_meta_image');
                    $attachment = $this->uploadImage($file);
                    $product->product_meta_image_id = $attachment->id;
                    $product->save();

                } catch (\Exception $e) {
                    Log::error('Failed to upload product meta image', [
                        'request_id' => $requestId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Handle Size Chart Image Upload
            if ($request->hasFile('size_chart_image')) {
                try {
                    $file = $request->file('size_chart_image');
                    $attachment = $this->uploadImage($file);
                    $product->size_chart_image_id = $attachment->id;
                    $product->save();

                } catch (\Exception $e) {
                    Log::error('Failed to upload size chart image', [
                        'request_id' => $requestId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Handle Product Galleries Upload - Use attach() like the API does
            if ($request->hasFile('product_galleries')) {
                $galleryIds = [];
                $files = $request->file('product_galleries');

                // Filter out any null or invalid files
                $validFiles = array_filter($files, function($file) {
                    return $file !== null && $file->isValid();
                });

                foreach ($validFiles as $file) {
                    try {
                        $attachment = $this->uploadImage($file);
                        if ($attachment && $attachment->id) {
                            $galleryIds[] = $attachment->id;
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to upload gallery image during creation', [
                            'request_id' => $requestId,
                            'file_name' => $file->getClientOriginalName(),
                            'error' => $e->getMessage()
                        ]);
                        // Continue with other images
                    }
                }

                // Use attach() instead of sync() for new product (like the API does)
                if (!empty($galleryIds)) {
                    $product->product_galleries()->attach($galleryIds);
                }
            }

            // Attach categories
            if ($request->filled('categories')) {
                $product->categories()->attach($request->categories);
            }

            // Attach tags
            if ($request->filled('tags')) {
                $product->tags()->attach($request->tags);
            }

            // IMPORTANT: Reload product with ALL relationships before commit
            // This ensures the ProductObserver has fresh data with correct image IDs
            $product->refresh();
            $product->load([
                'categories',
                'tags',
                'brand',
                'product_thumbnail',
                'product_meta_image',
                'product_galleries',
                'size_chart_image',
                'variations',
                'store',
                'tax',
            ]);

            DB::commit();


            // Store in session to prevent duplicate submissions (expires in 30 seconds)
            session()->put('last_created_product_id', $product->id);
            session()->put('last_created_product_time', time());

            // Redirect to products index page with success message
            return redirect()->route('admin.products.index')
                ->with('success', 'Product created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create product', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to create product: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show the form for editing a product
     */
    public function edit($id)
    {
        // Optimized loading - only load what's absolutely necessary for the edit form
        $product = Product::with([
            'categories:id,name,slug,status', // Only load needed columns
            'tags:id,name,slug,status', // Only load needed columns
            'product_thumbnail:id,file_name,image_url,disk',
            'product_galleries' => function($query) {
                // Limit galleries to first 30 to prevent slow loading
                $query->take(30);
            },
            'product_meta_image:id,file_name,image_url,disk',
            'size_chart_image:id,file_name,image_url,disk',
            'variations' => function($query) {
                // Limit variations and only load essential data
                $query->select('id', 'product_id', 'name', 'price', 'sale_price', 'quantity', 'status', 'stock_status', 'sku', 'zambia_only', 'zimbabwe_only', 'sa_only')
                      ->with([
                          'attribute_values' => function($q) {
                              $q->select('attribute_values.id', 'attribute_values.value', 'attribute_values.attribute_id')
                                ->with('attribute:id,name');
                          },
                          'variation_image:id,file_name,image_url,disk'
                      ])
                      ->take(30); // Reduced from 50 to 30 for even faster loading
            },
            'tax:id,name,rate',
            'brand:id,name,slug,status'
        ])->findOrFail($id);

        // For categories, DON'T load the entire tree - just load the product's current categories
        // The view will use Select2 with AJAX to search/add more categories
        $categories = $product->categories; // Only the categories this product already has

        // Load all categories with their subcategories for the collapsible tree view
        $allCategories = getCachedCategoriesForProductEdit();

        // Use cached data for tags and taxes (smaller datasets)
        $tags = getCachedActiveTags();
        $taxes = getCachedActiveTaxes();

        // Don't load all brands - use AJAX search instead (18k+ brands cause performance issues)
        $brands = collect(); // Empty collection, will use Select2 AJAX

        // Don't load attributes here - causes memory exhaustion with 1M+ attributes
        // We'll use AJAX search in the variation modal instead
        $attributes = collect(); // Empty collection

        return view('admin.products.edit', compact('product', 'categories', 'allCategories', 'tags', 'brands', 'taxes', 'attributes'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id)
    {

        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $id,
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'type' => 'required|in:simple,classified',
            'unit' => 'nullable|string|max:50',
            'weight' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'stock_status' => 'required|in:in_stock,out_of_stock',
            'brand_id' => 'nullable|exists:product_brands,id',
            'status' => 'required|boolean',
            'is_featured' => 'nullable|boolean',
            'is_trending' => 'nullable|boolean',
            'is_sale_enable' => 'nullable|boolean',
            'is_return' => 'nullable|boolean',
            'is_free_shipping' => 'nullable|boolean',
            'is_cod' => 'nullable|boolean',
            'is_external' => 'nullable|boolean',
            'is_approved' => 'nullable|boolean',
            'is_permanently_disabled' => 'nullable|boolean',
            'has_expedited_shipping' => 'nullable|boolean',
            'is_gift_card' => 'nullable|boolean',
            'zambia_only' => 'nullable|boolean',
            'zimbabwe_only' => 'nullable|boolean',
            'sa_only' => 'nullable|boolean',
            'voucher_validity_days' => 'nullable|integer|min:0',
            'safe_checkout' => 'nullable|boolean',
            'secure_checkout' => 'nullable|boolean',
            'social_share' => 'nullable|boolean',
            'encourage_order' => 'nullable|boolean',
            'encourage_view' => 'nullable|boolean',
            'shipping_days' => 'nullable|string',
            'standard_shipping_days' => 'nullable|string',
            'expedited_shipping_days' => 'nullable|string',
            'standard_shipping_price' => 'nullable|numeric|min:0',
            'expedited_shipping_price' => 'nullable|numeric|min:0',
            'estimated_delivery_text' => 'nullable|string',
            'return_policy_text' => 'nullable|string',
            'external_url' => 'nullable|url',
            'external_button_text' => 'nullable|string',
            'sale_starts_at' => 'nullable|date',
            'sale_expired_at' => 'nullable|date',
            'visible_time' => 'nullable|date',
            'categories' => 'nullable|array',
            'tags' => 'nullable|array',
            'tax_id' => 'nullable|exists:taxes,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'product_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'product_galleries.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'product_meta_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'size_chart_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        try {
            DB::beginTransaction();

            // Update slug if name changed
            if ($product->name !== $validated['name']) {
                $validated['slug'] = Str::slug($validated['name']);

                // Ensure unique slug
                $originalSlug = $validated['slug'];
                $count = 1;
                while (Product::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                        $validated['slug'] = $originalSlug . '-' . $count;
                    $count++;
                }
            }

            // Remove any keys not in $fillable to avoid unexpected column errors on PostgreSQL
            // (categories, tags, visible_time, is_cod etc. are not fillable but pass validation)
            $fillable = (new Product)->getFillable();
            $safeData = array_intersect_key($validated, array_flip($fillable));

            // Preserve computed slug if set above
            if (isset($validated['slug'])) {
                $safeData['slug'] = $validated['slug'];
            }

            // Permanently disabled products are always kept off
            if (!empty($safeData['is_permanently_disabled'])) {
                $safeData['status'] = 0;
                $safeData['is_approved'] = 0;
            }

            // Update product with only fillable-safe fields
            try {
                $product->update($safeData);
            } catch (\Exception $e) {
                Log::error('Failed to update product core fields', [
                    'product_id' => $product->id,
                    'data_keys' => array_keys($safeData),
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }


            // Handle Product Thumbnail Upload
            if ($request->hasFile('product_thumbnail')) {
                try {
                    $file = $request->file('product_thumbnail');
                    $attachment = $this->uploadImage($file);
                    $product->product_thumbnail_id = $attachment->id;
                } catch (\Exception $e) {
                    Log::error('Failed to upload product thumbnail during update', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue — image upload failure should not abort the product update
                }
            }

            // Handle Product Meta Image Upload
            if ($request->hasFile('product_meta_image')) {
                try {
                    $file = $request->file('product_meta_image');
                    $attachment = $this->uploadImage($file);
                    $product->product_meta_image_id = $attachment->id;
                } catch (\Exception $e) {
                    Log::error('Failed to upload product meta image during update', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue — image upload failure should not abort the product update
                }
            }

            // Handle Size Chart Image Upload
            if ($request->hasFile('size_chart_image')) {
                try {
                    $file = $request->file('size_chart_image');
                    $attachment = $this->uploadImage($file);
                    $product->size_chart_image_id = $attachment->id;
                } catch (\Exception $e) {
                    Log::error('Failed to upload size chart image during update', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage()
                    ]);
                    // Continue — image upload failure should not abort the product update
                }
            }

            // Save attachment IDs if any were set
            if ($request->hasFile('product_thumbnail') ||
                $request->hasFile('product_meta_image') ||
                $request->hasFile('size_chart_image')) {

                // Save the attachment IDs to the product
                // Use updateQuietly to avoid triggering observers twice
                try {
                    $product->updateQuietly([
                        'product_thumbnail_id' => $product->product_thumbnail_id,
                        'product_meta_image_id' => $product->product_meta_image_id,
                        'size_chart_image_id' => $product->size_chart_image_id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to save attachment IDs during update', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            // Handle Product Galleries Upload
            if ($request->hasFile('product_galleries')) {
                $galleryIds = [];
                $files = $request->file('product_galleries');

                // Filter out any null or invalid files
                $validFiles = array_filter($files, function($file) {
                    return $file !== null && $file->isValid();
                });

                foreach ($validFiles as $file) {
                    try {
                        $attachment = $this->uploadImage($file);
                        if ($attachment && $attachment->id) {
                            $galleryIds[] = $attachment->id;
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to upload gallery image during update', [
                            'product_id' => $product->id,
                            'file_name' => $file->getClientOriginalName(),
                            'error' => $e->getMessage()
                        ]);
                        // Continue with other images
                    }
                }

                // Only sync if we have valid IDs
                if (!empty($galleryIds)) {
                    try {
                        $product->product_galleries()->syncWithoutDetaching($galleryIds);
                    } catch (\Exception $e) {
                        Log::error('Failed to sync product galleries', [
                            'product_id' => $product->id,
                            'error' => $e->getMessage()
                        ]);
                        throw $e;
                    }
                }
            }

            // Handle removed galleries
            if ($request->filled('removed_galleries')) {
                try {
                    $removedIds = explode(',', $request->removed_galleries);
                    $product->product_galleries()->detach($removedIds);
                } catch (\Exception $e) {
                    Log::error('Failed to detach product galleries', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }

            // Sync categories — run in own try/catch to capture any constraint errors clearly
            if ($request->has('categories')) {
                try {
                    $product->categories()->sync($request->categories ?: []);
                } catch (\Exception $e) {
                    Log::error('Failed to sync product categories', [
                        'product_id' => $product->id,
                        'categories' => $request->categories,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e; // Re-throw so the outer catch rolls back
                }
            }

            // Sync tags
            if ($request->has('tags')) {
                try {
                    $product->tags()->sync($request->tags ?: []);
                } catch (\Exception $e) {
                    Log::error('Failed to sync product tags', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            // IMPORTANT: Reload product with ALL relationships before commit
            // This ensures the ProductObserver has fresh data with correct image IDs
            $product->refresh();
            $product->load([
                'categories',
                'tags',
                'brand',
                'product_thumbnail',
                'product_meta_image',
                'product_galleries',
                'size_chart_image',
                'variations',
                'store',
                'tax',
            ]);

            DB::commit();

            // Clear cache (ProductObserver handles Elasticsearch reindexing automatically)
            try {
                // Clear specific product caches
                $keysToForget = [
                    "product:{$product->id}",
                    "product_details:{$product->id}",
                    "product_with_relations:{$product->id}",
                    "api_product_{$product->id}",
                ];

                foreach ($keysToForget as $key) {
                    if (Cache::has($key)) {
                        Cache::forget($key);
                    }
                }

                // Bump products cache version to invalidate all product caches
                $currentVersion = (int) Cache::get('products_cache_version', 1);
                $newVersion = $currentVersion + 1;
                Cache::put('products_cache_version', $newVersion, now()->addDays(365));


                // Note: ProductObserver will automatically handle Elasticsearch reindexing
                // with all relationships loaded. No need to manually reindex here.

            } catch (\Exception $e) {
                Log::error('Error clearing product cache after edit', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the update if cache clearing fails
            }

            return redirect()->route('admin.products.edit', $product->id)
                ->with('success', 'Product updated successfully!');
        } catch (\Exception $e) {
            // Roll back before doing anything else — PostgreSQL will reject all
            // further queries on the connection once a transaction is aborted (SQLSTATE 25P02)
            DB::rollBack();
            Log::error('Failed to update product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to update product: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Helper method to upload image to laravel-media service
     * The file is uploaded to external laravel-media app, but record is stored locally
     */
    private function uploadImage($file)
    {
        $uuid = (string) \Illuminate\Support\Str::uuid();

        try {
            // Upload to external laravel-media API
            $mediaApiUrl = config('app.image_api_url', env('IMAGE_API_URL', 'http://localhost:8002/api'));

            $response = \Illuminate\Support\Facades\Http::attach(
                'image', // The field name expected by laravel-media API
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post($mediaApiUrl . '/attachments/from-api', [
                'model_id' => $uuid,
                'model_type' => 'Product',
                'collection_name' => 'products',
            ]);

            if (!$response->successful()) {
                Log::error('Failed to upload to media service', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new \Exception('Failed to upload to media service: ' . $response->body());
            }

            $uploadedFile = $response->json('files')[0] ?? null;

            if (!$uploadedFile) {
                Log::error('No file data returned from media service', [
                    'response' => $response->json()
                ]);
                throw new \Exception('No file data returned from media service');
            }

            // Check if attachment already exists (to avoid duplicate UUID error)
            $attachment = Attachment::where('uuid', $uploadedFile['uuid'])->first();

            if ($attachment) {
                // Update existing attachment record
                Log::warning('Attachment UUID already exists in database - updating instead of creating', [
                    'uuid' => $uploadedFile['uuid'],
                    'existing_id' => $attachment->id,
                    'existing_created_at' => $attachment->created_at,
                    'new_file_name' => $uploadedFile['file_name'],
                    'existing_file_name' => $attachment->file_name,
                    'scenario' => 'This could be a retry, double-submission, or race condition'
                ]);

                $attachment->update([
                    'name' => $uploadedFile['name'] ?? $uploadedFile['file_name'],
                    'file_name' => $uploadedFile['file_name'],
                    'mime_type' => $uploadedFile['mime_type'],
                    'disk' => $uploadedFile['disk'] ?? 'public',
                    'collection_name' => $uploadedFile['collection_name'] ?? 'products',
                    'size' => $uploadedFile['size'] ?? 0,
                    'original_url' => $uploadedFile['image_url'] ?? $uploadedFile['url'] ?? null,
                    'image_url' => $uploadedFile['image_url'] ?? $uploadedFile['url'] ?? null,
                    'model_id' => $uuid,
                    'model_type' => 'Product',
                ]);

            } else {
                // Create new attachment record locally in laravel-api database
                // The actual file is on laravel-media server, we just reference it
                $attachment = Attachment::create([
                    'uuid' => $uploadedFile['uuid'], // Use the UUID from laravel-media
                    'name' => $uploadedFile['name'] ?? $uploadedFile['file_name'],
                    'file_name' => $uploadedFile['file_name'],
                    'mime_type' => $uploadedFile['mime_type'],
                    'disk' => $uploadedFile['disk'] ?? 'public',
                    'collection_name' => $uploadedFile['collection_name'] ?? 'products',
                    'size' => $uploadedFile['size'] ?? 0,
                    'original_url' => $uploadedFile['image_url'] ?? $uploadedFile['url'] ?? null,
                    'image_url' => $uploadedFile['image_url'] ?? $uploadedFile['url'] ?? null,
                    'model_id' => $uuid,
                    'model_type' => 'Product',
                ]);
            }

            return $attachment;

        } catch (\Exception $e) {
            Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Store a new product variation
     */
    public function storeVariation(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'stock_status' => 'required|in:in_stock,out_of_stock',
            'status' => 'required|boolean',
            'attribute_values' => 'required|array|min:1',
            'attribute_values.*' => 'required|exists:attribute_values,id',
            'variation_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'zambia_only' => 'nullable|boolean',
            'zimbabwe_only' => 'nullable|boolean',
            'sa_only' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            // Handle variation image upload
            $variationImageId = null;
            if ($request->hasFile('variation_image')) {
                $file = $request->file('variation_image');
                $attachment = $this->uploadImage($file);
                $variationImageId = $attachment->id;

                // Also add the variation image to the product's gallery
                // Check if image is not already in gallery to avoid duplicates
                $existingGalleryIds = $product->product_galleries()->pluck('attachments.id')->toArray();
                if (!in_array($variationImageId, $existingGalleryIds)) {
                    $product->product_galleries()->attach($variationImageId);
                }
            }

            // Create variation
            $variation = Variation::create([
                'product_id' => $product->id,
                'name' => $validated['name'],
                'sku' => $validated['sku'] ?? null,
                'price' => $validated['price'],
                'sale_price' => $validated['sale_price'] ?? null,
                'quantity' => $validated['quantity'],
                'stock_status' => $validated['stock_status'],
                'status' => $validated['status'],
                'variation_image_id' => $variationImageId,
                'zambia_only' => $validated['zambia_only'] ?? false,
                'zimbabwe_only' => $validated['zimbabwe_only'] ?? false,
                'sa_only' => $validated['sa_only'] ?? false,
            ]);

            // Attach attribute values to variation
            $variation->attribute_values()->sync($validated['attribute_values']);

            // CRITICAL: Sync attributes to product (product_attributes table)
            // Frontend needs this to know which attributes the product has
            $attributeIds = \App\Models\AttributeValue::whereIn('id', $validated['attribute_values'])
                ->pluck('attribute_id')
                ->unique()
                ->toArray();

            // Sync without detaching existing attributes
            $product->attributes()->syncWithoutDetaching($attributeIds);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Product variation created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create variation: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Update a product variation
     */
    public function updateVariation(Request $request, $productId, $variationId)
    {
        $product = Product::findOrFail($productId);
        $variation = Variation::where('product_id', $product->id)->findOrFail($variationId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'stock_status' => 'required|in:in_stock,out_of_stock',
            'status' => 'required|boolean',
            'attribute_values' => 'required|array|min:1',
            'attribute_values.*' => 'required|exists:attribute_values,id',
            'variation_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'zambia_only' => 'nullable|boolean',
            'zimbabwe_only' => 'nullable|boolean',
            'sa_only' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            // Handle variation image upload
            if ($request->hasFile('variation_image')) {
                $file = $request->file('variation_image');
                $attachment = $this->uploadImage($file);
                $validated['variation_image_id'] = $attachment->id;

                // Also add the variation image to the product's gallery
                // Check if image is not already in gallery to avoid duplicates
                $existingGalleryIds = $product->product_galleries()->pluck('attachments.id')->toArray();
                if (!in_array($attachment->id, $existingGalleryIds)) {
                    $product->product_galleries()->attach($attachment->id);
                }
            }

            // Update variation
            $variation->update($validated);

            // Sync attribute values to variation
            $variation->attribute_values()->sync($validated['attribute_values']);

            // CRITICAL: Sync attributes to product (product_attributes table)
            // Get all unique attribute IDs from all variations of this product
            $allVariationAttributeIds = DB::table('variation_attribute_values as vav')
                ->join('attribute_values as av', 'vav.attribute_value_id', '=', 'av.id')
                ->join('variations as v', 'vav.variation_id', '=', 'v.id')
                ->where('v.product_id', $product->id)
                ->pluck('av.attribute_id')
                ->unique()
                ->toArray();

            // Sync all attributes used by any variation of this product
            $product->attributes()->sync($allVariationAttributeIds);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Product variation updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update variation', [
                'variation_id' => $variationId,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Failed to update variation: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Delete a product variation
     */
    public function deleteVariation($productId, $variationId)
    {

        $product = Product::findOrFail($productId);
        $variation = Variation::where('product_id', $product->id)->findOrFail($variationId);


        try {
            DB::beginTransaction();

            // Detach attribute values from variation
            $variation->attribute_values()->detach();

            // Delete variation
            $variation->delete();


            // CRITICAL: Update product_attributes to reflect remaining variations
            // Get all attribute IDs still used by remaining variations of this product
            $remainingAttributeIds = DB::table('variation_attribute_values as vav')
                ->join('attribute_values as av', 'vav.attribute_value_id', '=', 'av.id')
                ->join('variations as v', 'vav.variation_id', '=', 'v.id')
                ->where('v.product_id', $product->id)
                ->pluck('av.attribute_id')
                ->unique()
                ->toArray();

            // Sync product attributes (removes unused ones, keeps used ones)
            $product->attributes()->sync($remainingAttributeIds);

            DB::commit();

            return redirect()->back()
                ->with('success', 'Product variation deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to delete variation', [
                'variation_id' => $variationId,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Failed to delete variation: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {


        // Check if user has admin role
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized access.');
        }

        try {
            $product = Product::findOrFail($id);

            DB::beginTransaction();

            // Detach relationships
            $product->categories()->detach();
            $product->tags()->detach();

            // Delete product
            $product->delete();

            DB::commit();

            return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete product: ' . $e->getMessage());
        }
    }

    /**
     * Search attribute values via AJAX for Select2
     */
    public function searchAttributeValues(Request $request)
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 30;

        $query = \App\Models\AttributeValue::select('id', 'value', 'attribute_id')
            ->with('attribute:id,name')
            ->whereHas('attribute'); // Only include attribute values that have a valid attribute

        if (strlen($search) >= 3) {
            $query->where('value', 'ILIKE', '%' . $search . '%');
        }

        $total = $query->count();
        $results = $query->orderBy('value')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'results' => $results->map(function($av) {
                // Check if attribute exists to avoid null errors
                if (!$av->attribute) {
                    return [
                        'id' => $av->id,
                        'text' => $av->value . ' (No Attribute)',
                        'attribute_name' => 'Unknown',
                        'value' => $av->value
                    ];
                }

                return [
                    'id' => $av->id,
                    'text' => $av->attribute->name . ': ' . $av->value,
                    'attribute_name' => $av->attribute->name,
                    'value' => $av->value
                ];
            }),
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Store a new attribute for use in variations
     */
    public function storeAttribute(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'values' => 'required|string', // Comma-separated values
            ]);

            DB::beginTransaction();

            // Create the attribute
            $attribute = \App\Models\Attribute::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'status' => 1,
            ]);
            // Create attribute values from comma-separated list
            $values = array_filter(array_map('trim', explode(',', $validated['values'])));

            foreach ($values as $value) {
                \App\Models\AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                    'slug' => Str::slug($value),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attribute created successfully!',
                'attribute' => $attribute->load('attribute_values')
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create attribute: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle product status
     */
    public function toggleStatus(Request $request, $id)
    {

        try {
            $product = Product::findOrFail($id);
            $product->status = !$product->status;
            $product->save();

            return response()->json([
                'success' => true,
                'status' => $product->status,
                'message' => 'Product status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update status'], 500);
        }
    }

    /**
     * Bulk Disable products by SKU list
     */
    public function bulkDisable()
    {
        return view('admin.products.bulk-disable');
    }

    public function bulkDisableProcess(Request $request)
    {
        if ($request->hasFile('sku_file')) {
            $request->validate(['sku_file' => 'required|file|max:102400']);
            $content = file_get_contents($request->file('sku_file')->getRealPath());
        } else {
            $request->validate(['skus' => 'required|string']);
            $content = $request->input('skus');
        }

        $rawLines = preg_split('/\r\n|\r|\n/', trim($content));
        $skus = array_values(array_filter(
            array_map(function ($line) {
                return trim(explode(',', trim($line), 2)[0]);
            }, $rawLines)
        ));

        if (empty($skus)) {
            return back()->with('error', 'No SKUs provided.');
        }

        // Fetch all matching products in one query
        $found = \Illuminate\Support\Facades\DB::table('products')
            ->whereIn('sku', $skus)
            ->whereNull('deleted_at')
            ->select('id', 'sku', 'name')
            ->get()
            ->keyBy('sku');

        $foundSkus        = $found->keys()->all();
        $notFound         = array_values(array_diff($skus, $foundSkus));
        $affected         = [];
        $permanentlyDisable = $request->input('permanently_disable') === '1';

        $updateFields = [
            'status'      => 0,
            'is_approved' => 0,
            'updated_at'  => now(),
        ];

        if ($permanentlyDisable) {
            $updateFields['is_permanently_disabled'] = true;
        }

        // Bulk update in chunks of 500 — single UPDATE per chunk, no ORM overhead
        foreach (array_chunk($foundSkus, 500) as $chunk) {
            \Illuminate\Support\Facades\DB::table('products')
                ->whereIn('sku', $chunk)
                ->whereNull('deleted_at')
                ->update($updateFields);
        }

        foreach ($foundSkus as $sku) {
            $affected[] = ['sku' => $sku, 'name' => $found[$sku]->name];
        }

        \Illuminate\Support\Facades\Cache::put(
            'products_cache_version',
            ((int) \Illuminate\Support\Facades\Cache::get('products_cache_version', 1)) + 1,
            now()->addDays(365)
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data'    => ['affected' => $affected, 'not_found' => $notFound],
            ]);
        }

        return back()->with([
            'bulk_affected' => $affected,
            'bulk_not_found' => $notFound,
        ]);
    }

    /**
     * Search categories for Select2 AJAX dropdown
     */
    public function searchCategories(Request $request)
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 30;

        $query = Category::where('status', 1)
            ->select('id', 'name', 'parent_id')
            ->orderBy('name');

        if (!empty($search)) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $total = $query->count();
        $categories = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $results = $categories->map(function($category) {
            return [
                'id' => $category->id,
                'text' => $category->name
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }

    /**
     * Search brands for Select2 dropdown (AJAX)
     */
    public function searchBrands(Request $request)
    {
        $search = $request->get('q', '');
        $page = $request->get('page', 1);
        $perPage = 20;

        $query = \App\Models\Brand::where('status', 1);

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $total = $query->count();
        $brands = $query->orderBy('name')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get(['id', 'name']);

        $results = $brands->map(function($brand) {
            return [
                'id' => $brand->id,
                'text' => $brand->name
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ]
        ]);
    }
}
