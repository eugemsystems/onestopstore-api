<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Product Import", description="Import products from CSV")
 */
class ProductImportController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/products/import",
     *   tags={"Product Import"},
     *   summary="Show import form",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function showImportForm()
    {
        return view('products.import');
    }

    /**
     * @OA\Post(
     *   path="/api/products/import",
     *   tags={"Product Import"},
     *   summary="Import products via CSV",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="multipart/form-data",
     *       @OA\Schema(
     *         type="object",
     *         required={"import_file"},
     *         @OA\Property(property="import_file", type="string", format="binary", description="CSV or TXT file")
     *       )
     *     )
     *   ),
     *   @OA\Response(response=200, description="Import started or completed")
     * )
     */
    public function import_previous(Request $request)
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        $request->validate([
            'import_file' => 'required|mimes:csv,txt'
        ]);

        // --- Helper functions (closures) ---

        // Convert to int or null
        $intOrNull = function ($value) {
            if (is_numeric($value)) return (int)$value;
            return ($value === null || $value === '') ? null : (int)$value;
        };

        // Convert to float or null
        $floatOrNull = function ($value) {
            if (is_numeric($value)) return (float)$value;
            return ($value === null || $value === '') ? null : (float)$value;
        };

        // Normalise stock status inputs
        $normalizeStockStatus = function ($value) {
            $value = strtolower(trim((string)$value));
            if (in_array($value, ['in_stock', 'in stock', 'instock'])) return 'in_stock';
            if (in_array($value, ['out_of_stock', 'out of stock', 'outofstock'])) return 'out_of_stock';
            return 'in_stock'; // fallback
        };

        // Generate a unique slug from a base (optionally excluding an existing product id)
        $uniqueSlug = function (string $base, ?int $exceptId = null): string {
            $base = Str::slug($base) ?: 'product';
            $slug = $base;
            $i = 1;

            $query = Product::where('slug', $slug);
            if ($exceptId) $query->where('id', '!=', $exceptId);

            while ($query->exists()) {
                $slug = $base . '-' . $i++;
                $query = Product::where('slug', $slug);
                if ($exceptId) $query->where('id', '!=', $exceptId);
            }
            return $slug;
        };

        // Generate a SKU only when creating (NEVER from the slug),
        // ensure uniqueness with a suffix if needed.
        $generateSku = function (?string $proposedSku, string $name) {
            $sku = trim((string)$proposedSku);
            if ($sku === '') {
                $base = strtoupper(Str::of($name)->slug('-')->limit(40, ''));
                if ($base === '') $base = 'SKU';
                $sku = $base . '-' . Str::upper(Str::random(6));
            }
            $try = $sku; $i = 1;
            while (Product::where('sku', $try)->exists()) {
                $try = $sku . '-' . $i++;
            }
            return $try;
        };

        // --- Load CSV ---
        $path = $request->file('import_file')->getRealPath();
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'CSV file appears to be empty or unreadable.'], 422);
        }

        $header = array_map('trim', $rows[0] ?? []);
        unset($rows[0]);

        $imported = 0;
        $skipped = 0;
        $skippedRows = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                // Basic row sanity checks
                if (!is_array($row) || array_filter($row) === []) { $skipped++; continue; }
                if (count($row) !== count($header)) {
                    $skipped++;
                    $skippedRows[] = ['index' => $index + 2, 'row' => $row];
                    continue;
                }

                $data = array_combine($header, $row);

                // === Map fields (NEVER trust CSV slug; we will generate it) ===
                $name  = $data['post_title'] ?? $data['Name'] ?? $data['name'] ?? '';
                if (trim($name) === '') { // require a name to proceed
                    $skipped++;
                    $skippedRows[] = ['index' => $index + 2, 'reason' => 'Missing name'];
                    continue;
                }

                // NOTE: Do not use CSV slug at all to avoid SKU leaking into slug.
                // $csvSlug = $data['post_name'] ?? $data['Slug'] ?? $data['slug'] ?? null;

                $short_description = $data['short_description'] ?? $data['ShortDescription'] ?? $data['post_excerpt'] ?? '';
                $description       = $data['post_content'] ?? $data['Description'] ?? $data['description'] ?? '';
                $type              = $data['type'] ?? $data['Type'] ?? 'simple';
                $unit              = $data['unit'] ?? $data['Unit'] ?? '';
                $weight            = $intOrNull($data['weight'] ?? $data['Weight'] ?? null);
                $quantity          = 9999999;

                $price       = $floatOrNull($data['regular_price'] ?? $data['Price'] ?? $data['price'] ?? null);
                $sale_price  = $floatOrNull($data['sale_price'] ?? $data['SalePrice'] ?? null);
                $discount    = $floatOrNull($data['discount'] ?? $data['Discount'] ?? null);

                $incomingSku = $data['sku'] ?? $data['SKU'] ?? null; // may be null/empty; we will generate on create
                $stock_status = $normalizeStockStatus($data['stock_status'] ?? $data['StockStatus'] ?? 'in_stock');

                $meta_title       = $data['meta_title'] ?? $data['MetaTitle'] ?? $name;
                $meta_description = $data['meta_description'] ?? $data['MetaDescription'] ?? '';

                $is_featured     = $intOrNull($data['featured'] ?? $data['IsFeatured'] ?? null);
                $shipping_days   = $intOrNull($data['shipping_days'] ?? $data['ShippingDays'] ?? null);
                $is_cod          = $intOrNull($data['is_cod'] ?? $data['IsCOD'] ?? null);
                $is_free_shipping= $intOrNull($data['is_free_shipping'] ?? $data['IsFreeShipping'] ?? null);
                $is_sale_enable  = $intOrNull($data['is_sale_enable'] ?? $data['IsSaleEnable'] ?? null);
                $is_return       = $intOrNull($data['is_return'] ?? $data['IsReturn'] ?? null);
                $is_trending     = $intOrNull($data['is_trending'] ?? $data['IsTrending'] ?? null);
                $is_approved     = $intOrNull($data['is_approved'] ?? $data['IsApproved'] ?? 1);
                $is_external     = $intOrNull($data['is_external'] ?? $data['IsExternal'] ?? null);

                $external_url         = $data['external_url'] ?? $data['ExternalUrl'] ?? null;
                $external_button_text = $data['external_button_text'] ?? $data['ExternalButtonText'] ?? null;

                $sale_starts_at = $data['sale_starts_at'] ?? $data['SaleStartsAt'] ?? null;
                $sale_expired_at= $data['sale_expired_at'] ?? $data['SaleExpiredAt'] ?? null;

                $is_random_related_products = $intOrNull($data['is_random_related_products'] ?? $data['IsRandomRelatedProducts'] ?? null);
                $estimated_delivery_text = $data['estimated_delivery_text'] ?? $data['EstimatedDeliveryText'] ?? '';
                $return_policy_text     = $data['return_policy_text'] ?? $data['ReturnPolicyText'] ?? '';

                $safe_checkout   = $intOrNull($data['safe_checkout'] ?? $data['SafeCheckout'] ?? 1);
                $secure_checkout = $intOrNull($data['secure_checkout'] ?? $data['SecureCheckout'] ?? 1);
                $social_share    = $intOrNull($data['social_share'] ?? $data['SocialShare'] ?? 1);
                $encourage_order = $intOrNull($data['encourage_order'] ?? $data['EncourageOrder'] ?? 1);
                $encourage_view  = $intOrNull($data['encourage_view'] ?? $data['EncourageView'] ?? 1);
                $status          = $intOrNull($data['status'] ?? $data['Status'] ?? 1);

                // Relationships
                $categoryName      = $data['categories'] ?? $data['Category'] ?? $data['category'] ?? null;
                $product_image_url = $data['images'] ?? $data['ImageUrl'] ?? null;

                // ==== Category import ====
                $category = null;
                if ($categoryName) {
                    $category = Category::firstOrCreate(
                        ['name' => trim($categoryName)],
                        ['slug' => Str::slug($categoryName), 'status' => 1, 'type' => 'product']
                    );
                }

                // ==== Gallery / Thumbnail import ====
                $product_gallery_ids = [];
                $thumbnail_id = null;

                if ($product_image_url) {
                    $gallery_urls = array_filter(array_map('trim', explode(',', $product_image_url)));
                    foreach ($gallery_urls as $i => $imgUrl) {
                        if (!$imgUrl) continue;

                        $img = Attachment::firstOrCreate(
                            ['image_url' => $imgUrl],
                            [
                                'name' => $name . ' Image ' . ($i + 1),
                                'file_name' => $name . '-' . ($i + 1),
                                'disk' => 'public',
                                'conversions_disk' => 'public',
                                'created_by_id' => 1,
                            ]
                        );

                        // Use numeric ID for product_images pivot
                        if (!empty($img->getKey())) {
                            $product_gallery_ids[] = $img->getKey();

                            if ($i === 0) {
                                $thumbnail_id = $img->getKey(); // first is thumbnail
                            }
                        }
                    }
                }
                if (!$thumbnail_id && !empty($product_gallery_ids)) {
                    $thumbnail_id = $product_gallery_ids[0];
                }

                // === Product Creation/Update (SKU is the identity) ===
                $existing = null;
                if (!empty($incomingSku)) {
                    $existing = Product::where('sku', trim($incomingSku))->first();
                }

                if ($existing) {
                    // Keep the SAME SKU; do not let CSV change it.
                    // If the name changed, regenerate a unique slug from the NEW name.
                    $desiredSlug = $existing->slug;
                    if (trim((string)$existing->name) !== trim((string)$name)) {
                        $desiredSlug = $uniqueSlug($name, $existing->id);
                    }

                    $existing->fill([
                        'name' => $name,
                        'slug' => $desiredSlug, // always from name
                        'short_description' => $short_description,
                        'description' => $description,
                        'type' => $type,
                        'unit' => $unit,
                        'weight' => $weight,
                        'quantity' => $quantity,
                        'price' => $price,
                        'sale_price' => $sale_price,
                        'discount' => $discount,
                        // 'sku' => $existing->sku, // intentionally not changing
                        'product_thumbnail_id' => $thumbnail_id,
                        'stock_status' => $stock_status,
                        'meta_title' => $meta_title,
                        'meta_description' => $meta_description,
                        'is_featured' => $is_featured,
                        'shipping_days' => $shipping_days,
                        'is_cod' => $is_cod,
                        'is_free_shipping' => $is_free_shipping,
                        'is_sale_enable' => $is_sale_enable,
                        'is_return' => $is_return,
                        'is_trending' => $is_trending,
                        'is_approved' => $is_approved,
                        'is_external' => $is_external,
                        'external_url' => $external_url,
                        'external_button_text' => $external_button_text,
                        'sale_starts_at' => $sale_starts_at,
                        'sale_expired_at' => $sale_expired_at,
                        'is_random_related_products' => $is_random_related_products,
                        'estimated_delivery_text' => $estimated_delivery_text,
                        'return_policy_text' => $return_policy_text,
                        'safe_checkout' => $safe_checkout,
                        'secure_checkout' => $secure_checkout,
                        'social_share' => $social_share,
                        'encourage_order' => $encourage_order,
                        'encourage_view' => $encourage_view,
                        'status' => $status,
                    ]);

                    $existing->save();
                    $product = $existing;

                } else {
                    // Creating new: ensure SKU exists & is unique. Generate slug from name.
                    $finalSku  = $generateSku($incomingSku, $name);
                    $finalSlug = $uniqueSlug($name, null);

                    $product = Product::create([
                        'name' => $name,
                        'slug' => $finalSlug,
                        'short_description' => $short_description,
                        'description' => $description,
                        'type' => $type,
                        'unit' => $unit,
                        'weight' => $weight,
                        'quantity' => $quantity,
                        'price' => $price,
                        'sale_price' => $sale_price,
                        'discount' => $discount,
                        'sku' => $finalSku,
                        'product_thumbnail_id' => $thumbnail_id,
                        'stock_status' => $stock_status,
                        'meta_title' => $meta_title,
                        'meta_description' => $meta_description,
                        'is_featured' => $is_featured,
                        'shipping_days' => $shipping_days,
                        'is_cod' => $is_cod,
                        'is_free_shipping' => $is_free_shipping,
                        'is_sale_enable' => $is_sale_enable,
                        'is_return' => $is_return,
                        'is_trending' => $is_trending,
                        'is_approved' => $is_approved,
                        'is_external' => $is_external,
                        'external_url' => $external_url,
                        'external_button_text' => $external_button_text,
                        'sale_starts_at' => $sale_starts_at,
                        'sale_expired_at' => $sale_expired_at,
                        'is_random_related_products' => $is_random_related_products,
                        'estimated_delivery_text' => $estimated_delivery_text,
                        'return_policy_text' => $return_policy_text,
                        'safe_checkout' => $safe_checkout,
                        'secure_checkout' => $secure_checkout,
                        'social_share' => $social_share,
                        'encourage_order' => $encourage_order,
                        'encourage_view' => $encourage_view,
                        'status' => $status,
                    ]);
                }

                // === Attach Category ===
                if ($category && !$product->categories->contains($category->id)) {
                    $product->categories()->attach($category->id);
                }

                // === Attach Gallery Images (only this product's images) ===
                if (!empty($product_gallery_ids)) {
                    $product->product_galleries()->sync($product_gallery_ids);
                }

                $imported++;
            }

            DB::commit();

            $msg = "$imported products imported successfully.";
            if ($skipped > 0) {
                $msg .= " ($skipped rows skipped.)";
            }

            return response()->json(['success' => true, 'message' => $msg], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => false, 'message' => 'Import failed: ' . $e->getMessage()], 422);
        }
    }

    public function import(Request $request)
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        $request->validate([
            'import_file' => 'required|mimes:csv,txt'
        ]);

        // ---------- helpers ----------
        $intOrNull = function ($v) { return is_numeric($v) ? (int)$v : (strlen((string)$v) ? (int)$v : null); };
        $floatOrNull = function ($v) { return is_numeric($v) ? (float)$v : (strlen((string)$v) ? (float)$v : null); };
        $normalizeStockStatus = function ($v) {
            $v = strtolower(trim((string)$v));
            if (in_array($v, ['in_stock','in stock','instock'])) return 'in_stock';
            if (in_array($v, ['out_of_stock','out of stock','outofstock'])) return 'out_of_stock';
            return 'in_stock';
        };
        $uniqueSlug = function (string $base, ?int $exceptId = null): string {
            $base = \Illuminate\Support\Str::slug($base) ?: 'product';
            $slug = $base; $i = 1;
            $q = Product::where('slug', $slug);
            if ($exceptId) $q->where('id','!=',$exceptId);
            while ($q->exists()) {
                $slug = $base.'-'.$i++;
                $q = Product::where('slug', $slug);
                if ($exceptId) $q->where('id','!=',$exceptId);
            }
            return $slug;
        };
        $generateSku = function (?string $proposedSku, string $name) {
            $sku = trim((string)$proposedSku);
            if ($sku === '') {
                $base = strtoupper(\Illuminate\Support\Str::of($name)->slug('-')->limit(40, ''));
                if ($base === '') $base = 'SKU';
                $sku = $base.'-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6));
            }
            $try = $sku; $i = 1;
            while (Product::where('sku',$try)->exists()) $try = $sku.'-'.$i++;
            return $try;
        };

        // Text sanitization to ensure valid UTF-8 (handles Windows-1252/ISO-8859-1 CSVs)
        $sanitize = function ($v) {
            if ($v === null) return null;
            if (is_string($v)) {
                $enc = @mb_detect_encoding($v, 'UTF-8, ISO-8859-1, Windows-1252', true);
                if ($enc && $enc !== 'UTF-8') {
                    $v = @mb_convert_encoding($v, 'UTF-8', $enc);
                } elseif (!@mb_check_encoding($v, 'UTF-8')) {
                    $v = @utf8_encode($v);
                }
                // Strip ASCII control chars except tab/newline/carriage-return
                $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);
            }
            return $v;
        };

        // Make headers unique (handles duplicate names like "images")
        $normalizeHeaders = function(array $rawHeader): array {
            $header = [];
            $seen = [];
            foreach ($rawHeader as $col) {
                // strip UTF-8 BOM on first cell if present
                $col = preg_replace('/^\xEF\xBB\xBF/', '', (string)$col);
                $col = trim($col);
                if ($col === '') $col = 'col';
                if (!isset($seen[$col])) {
                    $seen[$col] = 1;
                    $header[] = $col;
                } else {
                    $seen[$col]++;
                    $header[] = $col . '_' . $seen[$col]; // images -> images_2, etc.
                }
            }
            return $header;
        };

        // Combine header+row safely even if counts differ (pads with nulls)
        $safeCombine = function(array $header, array $row): array {
            $hc = count($header); $rc = count($row);
            if ($rc < $hc)      { $row = array_pad($row, $hc, null); }
            elseif ($rc > $hc)  { $row = array_slice($row, 0, $hc); }
            return array_combine($header, $row) ?: [];
        };

        // Split a gallery string into clean URLs
        $parseGallery = function (?string $raw): array {
            if (!$raw) return [];
            // allow comma / semicolon / pipe separated lists
            $parts = preg_split('/\s*[,;|]\s*/', $raw) ?: [];
            $urls = [];
            foreach ($parts as $u) {
                $u = trim($u, " \t\n\r\0\x0B\"'"); // trim quotes/spaces
                if ($u === '') continue;
                // tolerate spaces after commas inside csv
                $u = preg_replace('/\s+/', ' ', $u);
                // accept http(s) only
                if (stripos($u, 'http://') === 0 || stripos($u, 'https://') === 0) {
                    $urls[] = $u;
                }
            }
            // de-dup, keep order
            return array_values(array_unique($urls));
        };

        // ---------- load CSV robustly ----------
        $path = $request->file('import_file')->getRealPath();
        $rows = [];
        if (($h = fopen($path, 'r')) === false) {
            return response()->json(['success' => false, 'message' => 'Unable to open CSV.'], 422, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        }

        // Important: set delimiter = "," and enclosure = '"' so commas/line-breaks inside HTML are preserved correctly
        while (($row = fgetcsv($h, 0, ",", '"', "\\")) !== false) {
            $rows[] = array_map($sanitize, $row);
        }
        fclose($h);

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'CSV file appears to be empty.'], 422, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        }

        // Header (unique) + body
        $header = $normalizeHeaders(array_map(function($c) use ($sanitize) { return trim($sanitize($c)); }, $rows[0] ?? []));
        unset($rows[0]);

        $imported = 0;
        $skipped = 0;
        $skippedRows = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                if (!is_array($row) || array_filter($row, fn($v)=>$v!==null && $v!=='') === []) {
                    $skipped++; continue;
                }

                $data = $safeCombine($header, $row);

                // --- map fields (stable across both files) ---
                $name  = $data['post_title'] ?? $data['name'] ?? '';
                if (trim($name) === '') {
                    $skipped++; $skippedRows[] = ['index' => $i+2, 'reason' => 'Missing name']; continue;
                }

                // Slug: never trust CSV; regenerate from name unless you want to keep incoming
                $incomingSlug = $data['post_name'] ?? null;
                $short_description = $data['post_excerpt'] ?? $data['short_description'] ?? '';
                $description       = $data['post_content'] ?? $data['description'] ?? '';
                $type              = $data['type'] ?? 'simple';
                $unit              = $data['unit'] ?? '';
                $weight            = $intOrNull($data['weight'] ?? null);
                $quantity          = 9999999;

                $price       = $floatOrNull($data['regular_price'] ?? $data['_price'] ?? $data['price'] ?? null);
                $sale_price  = $floatOrNull($data['sale_price'] ?? null);
                $discount    = $floatOrNull($data['discount'] ?? null);

                $incomingSku  = $data['sku'] ?? null;
                $stock_status = $normalizeStockStatus($data['stock_status'] ?? 'in_stock');

                $meta_title       = $data['meta_title'] ?? $name;
                $meta_description = $data['meta_description'] ?? '';

                $is_featured      = $intOrNull($data['featured'] ?? null);
                $shipping_days    = $intOrNull($data['shipping_days'] ?? null);
                $is_cod           = $intOrNull($data['is_cod'] ?? null);
                $is_free_shipping = $intOrNull($data['is_free_shipping'] ?? null);
                $is_sale_enable   = $intOrNull($data['is_sale_enable'] ?? null);
                $is_return        = $intOrNull($data['is_return'] ?? null);
                $is_trending      = $intOrNull($data['is_trending'] ?? null);
                $is_approved      = $intOrNull($data['is_approved'] ?? 1);
                $is_external      = $intOrNull($data['is_external'] ?? null);

                $external_url         = $data['external_url'] ?? $data['product_url'] ?? null;  // file2 uses product_url
                $external_button_text = $data['external_button_text'] ?? $data['button_text'] ?? null;

                $sale_starts_at  = $data['sale_price_dates_from'] ?? $data['sale_starts_at'] ?? null;
                $sale_expired_at = $data['sale_price_dates_to']   ?? $data['sale_expired_at'] ?? null;

                $is_random_related_products = $intOrNull($data['is_random_related_products'] ?? null);
                $estimated_delivery_text = $data['estimated_delivery_text'] ?? '';
                $return_policy_text     = $data['return_policy_text'] ?? '';

                $safe_checkout   = $intOrNull($data['safe_checkout'] ?? 1);
                $secure_checkout = $intOrNull($data['secure_checkout'] ?? 1);
                $social_share    = $intOrNull($data['social_share'] ?? 1);
                $encourage_order = $intOrNull($data['encourage_order'] ?? 1);
                $encourage_view  = $intOrNull($data['encourage_view'] ?? 1);
                $status          = $intOrNull($data['status'] ?? 1);

                // Relationships
                $categoryName = $data['categories'] ?? $data['Category'] ?? $data['category'] ?? null;

                // -------- robust images extraction --------
                // After header dedupe, the images column will be either 'images' or 'images_2' etc.
                // Prefer the first 'images*' column that contains http(s)
                $candidateImageCols = array_filter(array_keys($data), fn($k) => preg_match('/^images(\_\d+)?$/i', $k));
                $product_image_url = null;
                foreach ($candidateImageCols as $colKey) {
                    if (!empty($data[$colKey]) && stripos($data[$colKey], 'http') !== false) {
                        $product_image_url = $data[$colKey];
                        break;
                    }
                }
                // last resort (very rare misalignment) — some exports drop into downloadable_files
                if (!$product_image_url && !empty($data['downloadable_files']) && stripos($data['downloadable_files'], 'http') !== false) {
                    $product_image_url = $data['downloadable_files'];
                }

                $gallery_urls = $parseGallery($product_image_url);

                // ==== Category import ====
                $category = null;
                if ($categoryName) {
                    $category = Category::firstOrCreate(
                        ['name' => trim($categoryName)],
                        ['slug' => \Illuminate\Support\Str::slug($categoryName), 'status' => 1, 'type' => 'product']
                    );
                }

                // ==== Gallery / Thumbnail import ====
                $product_gallery_ids = [];
                $thumbnail_id = null;

                if (!empty($gallery_urls)) {
                    foreach ($gallery_urls as $idx => $imgUrl) {
                        $img = Attachment::firstOrCreate(
                            ['image_url' => $imgUrl],
                            [
                                'name' => $name . ' Image ' . ($idx + 1),
                                'file_name' => $name . '-' . ($idx + 1),
                                'disk' => 'public',
                                'conversions_disk' => 'public',
                                'created_by_id' => 1,
                            ]
                        );
                        if (!empty($img->getKey())) {
                            $product_gallery_ids[] = $img->getKey();
                            if ($idx === 0) $thumbnail_id = $img->getKey();
                        }
                    }
                }

                if (!$thumbnail_id && !empty($product_gallery_ids)) {
                    $thumbnail_id = $product_gallery_ids[0];
                }

                // === Product Creation/Update (SKU is the identity) ===
                $existing = null;
                if (!empty($incomingSku)) $existing = Product::where('sku', trim($incomingSku))->first();

                if ($existing) {
                    $desiredSlug = $existing->slug;
                    if (trim((string)$existing->name) !== trim((string)$name)) {
                        $desiredSlug = $uniqueSlug($name, $existing->id);
                    }
                    $existing->fill([
                        'name' => $name,
                        'slug' => $desiredSlug,
                        'short_description' => $short_description,
                        'description' => $description,
                        'type' => $type,
                        'unit' => $unit,
                        'weight' => $weight,
                        'quantity' => $quantity,
                        'price' => $price,
                        'sale_price' => $sale_price,
                        'discount' => $discount,
                        'product_thumbnail_id' => $thumbnail_id,
                        'stock_status' => $stock_status,
                        'meta_title' => $meta_title,
                        'meta_description' => $meta_description,
                        'is_featured' => $is_featured,
                        'shipping_days' => $shipping_days,
                        'is_cod' => $is_cod,
                        'is_free_shipping' => $is_free_shipping,
                        'is_sale_enable' => $is_sale_enable,
                        'is_return' => $is_return,
                        'is_trending' => $is_trending,
                        'is_approved' => $is_approved,
                        'is_external' => $is_external,
                        'external_url' => $external_url,
                        'external_button_text' => $external_button_text,
                        'sale_starts_at' => $sale_starts_at,
                        'sale_expired_at' => $sale_expired_at,
                        'is_random_related_products' => $is_random_related_products,
                        'estimated_delivery_text' => $estimated_delivery_text,
                        'return_policy_text' => $return_policy_text,
                        'safe_checkout' => $safe_checkout,
                        'secure_checkout' => $secure_checkout,
                        'social_share' => $social_share,
                        'encourage_order' => $encourage_order,
                        'encourage_view' => $encourage_view,
                        'status' => $status,
                    ])->save();
                    $product = $existing;
                } else {
                    $finalSku  = $generateSku($incomingSku, $name);
                    $finalSlug = $uniqueSlug($name, null);
                    $product = Product::create([
                        'name' => $name,
                        'slug' => $finalSlug,
                        'short_description' => $short_description,
                        'description' => $description,
                        'type' => $type,
                        'unit' => $unit,
                        'weight' => $weight,
                        'quantity' => $quantity,
                        'price' => $price,
                        'sale_price' => $sale_price,
                        'discount' => $discount,
                        'sku' => $finalSku,
                        'product_thumbnail_id' => $thumbnail_id,
                        'stock_status' => $stock_status,
                        'meta_title' => $meta_title,
                        'meta_description' => $meta_description,
                        'is_featured' => $is_featured,
                        'shipping_days' => $shipping_days,
                        'is_cod' => $is_cod,
                        'is_free_shipping' => $is_free_shipping,
                        'is_sale_enable' => $is_sale_enable,
                        'is_return' => $is_return,
                        'is_trending' => $is_trending,
                        'is_approved' => $is_approved,
                        'is_external' => $is_external,
                        'external_url' => $external_url,
                        'external_button_text' => $external_button_text,
                        'sale_starts_at' => $sale_starts_at,
                        'sale_expired_at' => $sale_expired_at,
                        'is_random_related_products' => $is_random_related_products,
                        'estimated_delivery_text' => $estimated_delivery_text,
                        'return_policy_text' => $return_policy_text,
                        'safe_checkout' => $safe_checkout,
                        'secure_checkout' => $secure_checkout,
                        'social_share' => $social_share,
                        'encourage_order' => $encourage_order,
                        'encourage_view' => $encourage_view,
                        'status' => $status,
                    ]);
                }

                // Attach category
                if ($category && !$product->categories->contains($category->id)) {
                    $product->categories()->attach($category->id);
                }

                // Attach galleries (pivot: product_images)
                if (!empty($product_gallery_ids)) {
                    $product->product_galleries()->sync($product_gallery_ids);
                }

                $imported++;
            }

            DB::commit();

            $msg = "$imported products imported successfully.";
            if ($skipped > 0) {
                $msg .= " ($skipped rows skipped.)";
            }

            return response()->json(['success' => true, 'message' => $msg], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Import failed', ['e' => $e, 'line' => $e->getLine(), 'file' => $e->getFile()]);
            $msg = $e->getMessage();
            $enc = @mb_detect_encoding($msg, 'UTF-8, ISO-8859-1, Windows-1252', true);
            if ($enc && $enc !== 'UTF-8') { $msg = @mb_convert_encoding($msg, 'UTF-8', $enc); }
            elseif (!@mb_check_encoding($msg, 'UTF-8')) { $msg = @utf8_encode($msg); }
            $msg = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $msg);
            return response()->json(['success' => false, 'message' => 'Import failed: '.$msg], 422, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        }
    }


    public function import_(Request $request)
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        $request->validate([
            'import_file' => 'required|mimes:csv,txt'
        ]);

        // --- Helper functions ---
        $intOrNull = function($value) {
            if (is_numeric($value)) {
                return (int) $value;
            }
            return ($value === null || $value === '') ? null : (int) $value;
        };
        $floatOrNull = function($value) {
            if (is_numeric($value)) {
                return (float) $value;
            }
            return ($value === null || $value === '') ? null : (float) $value;
        };
        $normalizeStockStatus = function($value) {
            $value = strtolower(trim($value));
            if (in_array($value, ['in_stock', 'in stock', 'instock'])) return 'in_stock';
            if (in_array($value, ['out_of_stock', 'out of stock', 'outofstock'])) return 'out_of_stock';
            return 'in_stock'; // fallback
        };

        $path = $request->file('import_file')->getRealPath();

        // Use fgetcsv for robust parsing
        $rows = [];
        if (($handle = fopen($path, "r")) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }
        if (empty($rows)) {
            return back()->with('error', 'CSV file appears to be empty or unreadable.');
        }
        $header = array_map('trim', $rows[0]);
        unset($rows[0]);

        // Detect format
        if (in_array('post_title', $header) && in_array('post_name', $header)) {
            $format = 'wordpress';
        } else {
            $format = 'other';
        }

        $imported = 0;
        $skipped = 0;
        $skippedRows = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if (count($row) != count($header)) {
                    $skipped++;
                    $skippedRows[] = [
                        'index' => $index + 2,
                        'row' => $row
                    ];
                    continue;
                }
                if (array_filter($row) === []) {
                    $skipped++;
                    continue;
                }

                $data = array_combine($header, $row);

                // === All Fields Mapped ===
                $name = $data['post_title'] ?? $data['Name'] ?? $data['name'] ?? '';
                $slug = $data['post_name'] ?? $data['Slug'] ?? $data['slug'] ?? Str::slug($name);
                $short_description = $data['short_description'] ?? $data['ShortDescription'] ?? $data['post_excerpt'] ?? '';
                $description = $data['post_content'] ?? $data['Description'] ?? $data['description'] ?? '';
                $type = $data['type'] ?? $data['Type'] ?? 'simple';
                $unit = $data['unit'] ?? $data['Unit'] ?? '';
                $weight = $intOrNull($data['weight'] ?? $data['Weight'] ?? null);
                $quantity = 9999999;
                $price = $floatOrNull($data['regular_price'] ?? $data['Price'] ?? $data['price'] ?? null);
                $sale_price = $floatOrNull($data['sale_price'] ?? $data['SalePrice'] ?? null);
                $discount = $floatOrNull($data['discount'] ?? $data['Discount'] ?? null);
                $sku = $data['sku'] ?? $data['SKU'] ?? null;
                if (empty($sku)) {
                    $sku = $slug . '-' . ($index + 1); // Ensure unique SKU as fallback
                }
                $stock_status = $normalizeStockStatus($data['stock_status'] ?? $data['StockStatus'] ?? 'in_stock');
                $meta_title = $data['meta_title'] ?? $data['MetaTitle'] ?? $name;
                $meta_description = $data['meta_description'] ?? $data['MetaDescription'] ?? '';
                $is_featured = $intOrNull($data['featured'] ?? $data['IsFeatured'] ?? null);
                $shipping_days = $intOrNull($data['shipping_days'] ?? $data['ShippingDays'] ?? null);
                $is_cod = $intOrNull($data['is_cod'] ?? $data['IsCOD'] ?? null);
                $is_free_shipping = $intOrNull($data['is_free_shipping'] ?? $data['IsFreeShipping'] ?? null);
                $is_sale_enable = $intOrNull($data['is_sale_enable'] ?? $data['IsSaleEnable'] ?? null);
                $is_return = $intOrNull($data['is_return'] ?? $data['IsReturn'] ?? null);
                $is_trending = $intOrNull($data['is_trending'] ?? $data['IsTrending'] ?? null);
                $is_approved = $intOrNull($data['is_approved'] ?? $data['IsApproved'] ?? 1);
                $is_external = $intOrNull($data['is_external'] ?? $data['IsExternal'] ?? null);
                $external_url = $data['external_url'] ?? $data['ExternalUrl'] ?? null;
                $external_button_text = $data['external_button_text'] ?? $data['ExternalButtonText'] ?? null;
                $sale_starts_at = $data['sale_starts_at'] ?? $data['SaleStartsAt'] ?? null;
                $sale_expired_at = $data['sale_expired_at'] ?? $data['SaleExpiredAt'] ?? null;
                $is_random_related_products = $intOrNull($data['is_random_related_products'] ?? $data['IsRandomRelatedProducts'] ?? null);
                $estimated_delivery_text = $data['estimated_delivery_text'] ?? $data['EstimatedDeliveryText'] ?? '';
                $return_policy_text = $data['return_policy_text'] ?? $data['ReturnPolicyText'] ?? '';
                $safe_checkout = $intOrNull($data['safe_checkout'] ?? $data['SafeCheckout'] ?? 1);
                $secure_checkout = $intOrNull($data['secure_checkout'] ?? $data['SecureCheckout'] ?? 1);
                $social_share = $intOrNull($data['social_share'] ?? $data['SocialShare'] ?? 1);
                $encourage_order = $intOrNull($data['encourage_order'] ?? $data['EncourageOrder'] ?? 1);
                $encourage_view = $intOrNull($data['encourage_view'] ?? $data['EncourageView'] ?? 1);
                $status = $intOrNull($data['status'] ?? $data['Status'] ?? 1);

                // -- Relationships/Lookups --
                $categoryName = $data['categories'] ?? $data['Category'] ?? $data['category'] ?? null;
                $product_image_url = $data['images'] ?? $data['ImageUrl'] ?? null;

                // ==== Category import ====
                $category = null;
                if ($categoryName) {
                    $category = Category::firstOrCreate(
                        ['name' => trim($categoryName)],
                        [
                            'slug' => Str::slug($categoryName),
                            'status' => 1,
                            'type' => 'product',
                        ]
                    );
                }

                // ==== Gallery and Thumbnail logic ====
                $product_gallery_ids = [];
                $thumbnail_id = null;
                if ($product_image_url) {
                    $gallery_urls = array_filter(array_map('trim', explode(',', $product_image_url)));
                    foreach ($gallery_urls as $i => $imgUrl) {
                        if (!$imgUrl) continue;
                        $img = Attachment::firstOrCreate(
                            ['image_url' => $imgUrl],
                            [
                                'name' => $name . ' Image',
                                'disk' => 'public',
                            ]
                        );
                        if (!empty($img->id)) {
                            $product_gallery_ids[] = $img->id;
                            if ($i === 0) {
                                $thumbnail_id = $img->id; // First image is thumbnail!
                            }
                        }
                    }
                }
                if (!$thumbnail_id && !empty($product_gallery_ids)) {
                    $thumbnail_id = $product_gallery_ids[0];
                }

                // === Product Creation/Update ===
                $product = Product::updateOrCreate(
                    ['sku' => $sku], // <- UNIQUE BY SLUG
                    [
                        'name' => $name,
                        'slug' => $slug,
                        'short_description' => $short_description,
                        'description' => $description,
                        'type' => $type,
                        'unit' => $unit,
                        'weight' => $weight,
                        'quantity' => $quantity,
                        'price' => $price,
                        'sale_price' => $sale_price,
                        'discount' => $discount,
                        'sku' => $sku,
                        'product_thumbnail_id' => $thumbnail_id,
                        'stock_status' => $stock_status,
                        'meta_title' => $meta_title,
                        'meta_description' => $meta_description,
                        'is_featured' => $is_featured,
                        'shipping_days' => $shipping_days,
                        'is_cod' => $is_cod,
                        'is_free_shipping' => $is_free_shipping,
                        'is_sale_enable' => $is_sale_enable,
                        'is_return' => $is_return,
                        'is_trending' => $is_trending,
                        'is_approved' => $is_approved,
                        'is_external' => $is_external,
                        'external_url' => $external_url,
                        'external_button_text' => $external_button_text,
                        'sale_starts_at' => $sale_starts_at,
                        'sale_expired_at' => $sale_expired_at,
                        'is_random_related_products' => $is_random_related_products,
                        'estimated_delivery_text' => $estimated_delivery_text,
                        'return_policy_text' => $return_policy_text,
                        'safe_checkout' => $safe_checkout,
                        'secure_checkout' => $secure_checkout,
                        'social_share' => $social_share,
                        'encourage_order' => $encourage_order,
                        'encourage_view' => $encourage_view,
                        'status' => $status,
                    ]
                );

                // === Attach Category ===
                if ($category && !$product->categories->contains($category->id)) {
                    $product->categories()->attach($category->id);
                }

                // === Attach Gallery Images (only this product's images) ===
                if (!empty($product_gallery_ids)) {
                    $product->product_galleries()->sync($product_gallery_ids);
                }

                $imported++;
            }
            DB::commit();

            $msg = "$imported products imported successfully.";
            if ($skipped > 0) {
                $msg .= " ($skipped rows skipped due to missing columns or empty rows.)";
            }
            return response()->json(['success' => true, 'message' => $msg], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Import failed: '.$e->getMessage()], 422);
        }

    }
}
