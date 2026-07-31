<?php

namespace App\Repositories\Eloquents;

use App\GraphQL\Exceptions\ExceptionHandler;
use Exception;
use Carbon\Carbon;
use App\Models\Product;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Models\Variation;
use App\Enums\StockStatus;
use App\Imports\ProductImport;
use App\Exports\ProductsExport;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Prettus\Repository\Eloquent\BaseRepository;

class ProductRepository extends BaseRepository
{
    protected $variations;

    protected $fieldSearchable = [
        'name' => 'like',
        'sku' => 'like',
        'variations.sku' => 'like',
        'stock_status' => 'like',
        'store.store_name' => 'like'
    ];

    public function boot()
    {
        // (criteria disabled by original code)
    }

    public function model()
    {
        $this->variations = new Variation();
        return Product::class;
    }

    public function show($id)
    {
        try {
            $data = $this->model->with(config('enums.product.with'))
                ->where('status', 1)
                ->where('is_approved', 1)
                ->findOrFail($id);

            // Normalize variation.sku for API consumers: keep DB intact, only ensure JSON has string
            if ($data->relationLoaded('variations')) {
                foreach ($data->variations as $v) {
                    if ($v->sku === null) { $v->sku = ''; }
                }
            }

            $data->setAppends(config('enums.product.appends'))
                ->makeVisible(config('enums.product.visible'));

            return $data;
        } catch (Exception $e){
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /* ---------------------------- helpers ---------------------------- */

    /** Accepts numeric id or UUID and returns numeric id (or null) */
    private function resolveAttachmentId($value): ?int
    {
        if ($value === null || $value === '') return null;

        // If already numeric (or numeric string)
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return (int) $value;
        }

        // If UUID-like, resolve once
        if (is_string($value) && Str::isUuid($value)) {
            return (int) DB::table('attachments')
                ->where('uuid', $value)
                ->whereNull('deleted_at')
                ->value('id');
        }

        return null;
    }

    /** Accepts array of ids or uuids and returns array of numeric ids */
    private function resolveAttachmentIds($values): array
    {
        $values = is_array($values) ? $values : [];
        if (empty($values)) return [];

        // Split into uuids vs numeric to keep queries simple
        $uuids = [];
        $ids   = [];
        foreach ($values as $v) {
            if ($v === null || $v === '') continue;
            if (is_int($v) || (is_string($v) && ctype_digit($v))) {
                $ids[] = (int) $v;
            } elseif (is_string($v) && Str::isUuid($v)) {
                $uuids[] = $v;
            }
        }

        if (!empty($uuids)) {
            $uuidIds = DB::table('attachments')
                ->whereIn('uuid', $uuids)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->map(fn($i) => (int)$i)
                ->all();
            $ids = array_merge($ids, $uuidIds);
        }

        // de-duplicate
        return array_values(array_unique($ids));
    }

    /** Normalizes all attachment-related inputs (UUID or ID) → numeric ids */
    private function normalizeAttachmentInputs(array $data): array
    {
        // Single image fields: accept *_id, *_uuid or nested objects with id/uuid
        $thumbSrc = $data['product_thumbnail_id'] ?? $data['product_thumbnail_uuid'] ?? null;
        if ($thumbSrc === null && isset($data['product_thumbnail']) && is_array($data['product_thumbnail'])) {
            $thumbSrc = $data['product_thumbnail']['id'] ?? $data['product_thumbnail']['uuid'] ?? null;
        }
        $data['product_thumbnail_id'] = $this->resolveAttachmentId($thumbSrc);

        $metaSrc = $data['product_meta_image_id'] ?? $data['product_meta_image_uuid'] ?? null;
        if ($metaSrc === null && isset($data['product_meta_image']) && is_array($data['product_meta_image'])) {
            $metaSrc = $data['product_meta_image']['id'] ?? $data['product_meta_image']['uuid'] ?? null;
        }
        $data['product_meta_image_id'] = $this->resolveAttachmentId($metaSrc);

        $sizeSrc = $data['size_chart_image_id'] ?? $data['size_chart_image_uuid'] ?? null;
        if ($sizeSrc === null && isset($data['size_chart_image']) && is_array($data['size_chart_image'])) {
            $sizeSrc = $data['size_chart_image']['id'] ?? $data['size_chart_image']['uuid'] ?? null;
        }
        $data['size_chart_image_id'] = $this->resolveAttachmentId($sizeSrc);

        // Galleries: accept product_galleries_id[], product_galleries_uuid[] or product_galleries objects
        $galleriesInput = $data['product_galleries_id'] ?? $data['product_galleries_uuid'] ?? null;
        if ($galleriesInput === null && isset($data['product_galleries']) && is_array($data['product_galleries'])) {
            $galleriesInput = array_values(array_filter(array_map(function ($g) {
                if (is_array($g)) return $g['id'] ?? $g['uuid'] ?? null;
                return null;
            }, $data['product_galleries'])));
        }
        if ($galleriesInput === null) $galleriesInput = [];
        $data['product_galleries_id'] = $this->resolveAttachmentIds($galleriesInput);

        // Variations: accept variation_image_id or variation_image_uuid inside each variation
        if (!empty($data['variations']) && is_array($data['variations'])) {
            foreach ($data['variations'] as $i => $variation) {
                if (is_array($variation)) {
                    $vid = $variation['variation_image_id'] ?? $variation['variation_image_uuid'] ?? null;
                    $data['variations'][$i]['variation_image_id'] = $this->resolveAttachmentId($vid);
                }
            }
        }

        return $data;
    }

    private function getMinPriceVariation($payload, $price)
    {
        return head(array_filter($payload['variations'], function ($variation) use ($price) {
            return $variation['price'] == $price;
        }));
    }

    /* ----------------------------- create ---------------------------- */

    public function store($payload)
    {
        DB::beginTransaction();
        try {
            $data = is_array($payload) ? $payload : (array) $payload;

            // normalize UUIDs → IDs
            $data = $this->normalizeAttachmentInputs($data);

            // Coerce shipping fields to proper types in case frontend sends strings/arrays
            // Prefer validated payload, but fallback to raw request to avoid silent drops
            $rawReq = request();
            $boolish = $data['has_expedited_shipping'] ?? $rawReq->input('has_expedited_shipping', 0);
            if (is_array($boolish)) { $boolish = reset($boolish); }
            if (is_string($boolish)) {
                $v = strtolower(trim($boolish));
                $boolish = in_array($v, ['on','true','1','yes'], true) ? 1 : 0;
            }
            $data['has_expedited_shipping'] = (int) (!!$boolish);

            foreach (['standard_shipping_days','expedited_shipping_days'] as $k) {
                $val = $data[$k] ?? $rawReq->input($k);
                $data[$k] = ($val === '' || $val === null) ? null : (int) $val;
            }
            foreach (['standard_shipping_price','expedited_shipping_price'] as $k) {
                $val = $data[$k] ?? $rawReq->input($k);
                $data[$k] = ($val === '' || $val === null) ? null : (float) $val;
            }

            $quantity     = 0;
            $sale_price   = $data['sale_price'] ?? null;
            $discount     = $data['discount'] ?? null;
            $price        = $data['price'] ?? null;
            $stock_status = $data['stock_status'] ?? null;

            $roleName = Helpers::getCurrentRoleName();
            $isAutoApprove = true; // Default for admin
            $productStatus = $data['status'] ?? 1; // Default for admin

            if ($roleName != RoleEnum::ADMIN) {
                $settings = Helpers::getSettings();
                if ($roleName == RoleEnum::VENDOR && !Helpers::isMultiVendorEnable()) {
                    throw new Exception('The multi-vendor feature is currently deactivated.', 403);
                }
                // Vendors: Force products to be inactive and require approval
                $isAutoApprove = false;
                $productStatus = 0;
            }

            if (!empty($data['variations']) && $data['type'] === 'classified') {
                $price = min(array_column($data['variations'], 'price'));
                $minPriceVariation = $this->getMinPriceVariation($data, $price);
                $discount   = $minPriceVariation['discount'] ?? null;
                $sale_price = round($price - (($price * ($discount ?? 0)) / 100), 2);
                $quantity   = max(array_column($data['variations'], 'quantity'));
                $stock_status = $quantity > 0 ? StockStatus::IN_STOCK : StockStatus::OUT_OF_STOCK;
            }

            if (isset($data['quantity'])) {
                $stock_status = ($data['quantity'] > 0) ? StockStatus::IN_STOCK : StockStatus::OUT_OF_STOCK;
            }

            if (isset($data['discount'])) {
                $mrpPrice   = $price ?? $data['price'] ?? null;
                if ($mrpPrice !== null) {
                    $sale_price = round($mrpPrice - (($mrpPrice * $data['discount']) / 100), 2);
                }
            }

            if (
                isset($data['sale_price']) && $data['sale_price'] !== '' && $data['sale_price'] !== null
                && ($price ?? $data['price'] ?? null) !== null
                && ($discount === null || $discount === '')
            ) {
                $mrp  = (float) ($price ?? $data['price']);
                $sale = (float) $data['sale_price'];
                if ($mrp > 0) {
                    if ($sale < 0) { $sale = 0; }
                    if ($sale > $mrp) { $sale = $mrp; }
                    $discount   = round((($mrp - $sale) / $mrp) * 100, 2);
                    $sale_price = round($sale, 2);
                }
            }

            $product = $this->model->create([
                'name'                        => $data['name'],
                'short_description'           => $data['short_description'] ?? null,
                'description'                 => $data['description'] ?? null,
                'type'                        => $data['type'],
                'unit'                        => $data['unit'] ?? null,
                'quantity'                    => $data['quantity'] ?? $quantity,
                'weight'                      => $data['weight'] ?? null,
                'price'                       => $price ?? $data['price'] ?? null,
                'sale_price'                  => $sale_price,
                'discount'                    => $discount,
                'sku'                         => $data['sku'] ?? null,
                'is_external'                 => $data['is_external'] ?? 0,
                'external_url'                => $data['external_url'] ?? null,
                'external_button_text'        => $data['external_button_text'] ?? null,
                'is_featured'                 => $data['is_featured'] ?? 0,
                'shipping_days'               => $data['shipping_days'] ?? null,
                'is_free_shipping'            => $data['is_free_shipping'] ?? 0,
                'has_expedited_shipping'      => $data['has_expedited_shipping'] ?? 0,
                'standard_shipping_days'      => $data['standard_shipping_days'] ?? null,
                'expedited_shipping_days'     => $data['expedited_shipping_days'] ?? null,
                'standard_shipping_price'     => $data['standard_shipping_price'] ?? null,
                'expedited_shipping_price'    => $data['expedited_shipping_price'] ?? null,
                'is_sale_enable'              => $data['is_sale_enable'] ?? 0,
                'sale_starts_at'              => $data['sale_starts_at'] ?? null,
                'sale_expired_at'             => $data['sale_expired_at'] ?? null,
                'is_trending'                 => $data['is_trending'] ?? 0,
                'stock_status'                => $stock_status,
                'meta_title'                  => $data['meta_title'] ?? null,
                'is_return'                   => $data['is_return'] ?? 0,
                'meta_description'            => $data['meta_description'] ?? null,
                'is_random_related_products'  => $data['is_random_related_products'] ?? 0,
                'product_meta_image_id'       => $data['product_meta_image_id'] ?? null,
                'product_thumbnail_id'        => $data['product_thumbnail_id'] ?? null,
                'size_chart_image_id'         => $data['size_chart_image_id'] ?? null,
                'estimated_delivery_text'     => $data['estimated_delivery_text'] ?? null,
                'return_policy_text'          => $data['return_policy_text'] ?? null,
                'safe_checkout'               => $data['safe_checkout'] ?? 0,
                'secure_checkout'             => $data['secure_checkout'] ?? 0,
                'social_share'                => $data['social_share'] ?? 0,
                'encourage_order'             => $data['encourage_order'] ?? 0,
                'encourage_view'              => $data['encourage_view'] ?? 0,
                'tax_id'                      => $data['tax_id'] ?? 1,
                'status'                      => $productStatus,
                'is_approved'                 => $isAutoApprove,
                'store_id'                    => $data['store_id'] ?? null,
            ]);

            // relations
            $this->relationProductModels((object) $data, $product); // method expects request-like

            // variations
            if (!empty($data['variations']) && $data['type'] === 'classified') {
                foreach ($data['variations'] as $variation) {
                    $this->createProductVariation($product, $variation);
                }
                $product->variations;
            }

            DB::commit();
            $created = $product->fresh()->load(config('enums.product.with'));
            return $created
                ->setAppends(config('enums.product.appends'))
                ->makeVisible(config('enums.product.visible'));
        } catch (Exception $e) {
            DB::rollBack();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /* ------------------------------ update --------------------------- */

    public function update($payload, $id)
    {
        //Log::info('Product update payload', ['payload' => $payload]);

        DB::beginTransaction();
        try {
            $data = is_array($payload) ? $payload : (array) $payload;
            //Log::info('Product update data', ['data' => $data]);

            // Check user role - vendors cannot change status or approval
            $roleName = Helpers::getCurrentRoleName();
            if ($roleName == RoleEnum::VENDOR) {
                // Vendors cannot change these fields
                unset($data['status']);
                unset($data['is_approved']);
            }

            // Normalize/alias shipping fields for updates (fallback to raw request if needed)
            $rawReq = request();
            if (array_key_exists('has_expedited_shipping', $data) || $rawReq->has('has_expedited_shipping') || $rawReq->has('has_expedited')) {
                $boolish = $data['has_expedited_shipping'] ?? $rawReq->input('has_expedited_shipping', $rawReq->input('has_expedited'));
                if (is_array($boolish)) { $boolish = reset($boolish); }
                if (is_string($boolish)) {
                    $v = strtolower(trim($boolish));
                    $boolish = in_array($v, ['on','true','1','yes'], true) ? 1 : 0;
                }
                $data['has_expedited_shipping'] = (int) (!!$boolish);
            }
            foreach (['standard_shipping_days','expedited_shipping_days'] as $k) {
                if (array_key_exists($k, $data) || $rawReq->has($k)) {
                    $val = $data[$k] ?? $rawReq->input($k);
                    $data[$k] = ($val === '' || $val === null) ? null : (int) $val;
                }
            }
            foreach (['standard_shipping_price','expedited_shipping_price'] as $k) {
                if (array_key_exists($k, $data) || $rawReq->has($k)) {
                    $val = $data[$k] ?? $rawReq->input($k);
                    $data[$k] = ($val === '' || $val === null) ? null : (float) $val;
                }
            }

            // For classified: derive price/discount/quantity from variations (kept as-is)
            if (!empty($data['variations']) && ($data['type'] ?? null) === 'classified') {
                $data['price']     = min(array_column($data['variations'], 'price'));
                $minPriceVariation = $this->getMinPriceVariation($data, $data['price']);
                $data['discount']  = $minPriceVariation['discount'] ?? null;
                $data['quantity']  = max(array_column($data['variations'], 'quantity'));
            }

            // Stock status from quantity (kept as-is)
            if (isset($data['quantity'])) {
                $data['stock_status'] = ($data['quantity'] > 0) ? StockStatus::IN_STOCK : StockStatus::OUT_OF_STOCK;
            }

            // Existing one-way: discount -> sale_price (kept as-is)
            if (isset($data['discount']) && isset($data['price'])) {
                $data['sale_price'] = round($data['price'] - (($data['price'] * $data['discount']) / 100), 2);
            }

            // NEW: two-way support — if sale_price is provided and discount is empty, derive discount
            if (
                isset($data['sale_price']) && $data['sale_price'] !== '' && $data['sale_price'] !== null
                && isset($data['price']) && $data['price'] !== null && $data['price'] != 0
                && (!isset($data['discount']) || $data['discount'] === null || $data['discount'] === '')
            ) {
                $base = (float) $data['price'];
                $sale = (float) $data['sale_price'];

                // Clamp to sane bounds
                if ($sale < 0) { $sale = 0; }
                if ($sale > $base) { $sale = $base; }

                $computed = (($base - $sale) / $base) * 100;
                $data['discount']   = round($computed, 2);
                $data['sale_price'] = round($sale, 2);
            }

            // Normalize attachment inputs (uuid -> id) before update (kept as-is)
            $data = $this->normalizeAttachmentInputs($data);

            // Default tax_id to 1 when not provided or empty
            if (!array_key_exists('tax_id', $data) || $data['tax_id'] === null || $data['tax_id'] === '') {
                $data['tax_id'] = 1;
            }

            // Permanently disabled products are always kept off
            if (!empty($data['is_permanently_disabled'])) {
                $data['status'] = 0;
                $data['is_approved'] = 0;
            }

            // Find product & update attributes (kept as-is)
            $product = $this->model->findOrFail($id);
            $product->update($data);

            // Associate single images if present (kept as-is)
            if (array_key_exists('product_thumbnail_id', $data)) {
                $product->product_thumbnail()->associate($data['product_thumbnail_id']);
                $product->product_thumbnail;
            }

            if (array_key_exists('product_meta_image_id', $data)) {
                $product->product_meta_image()->associate($data['product_meta_image_id']);
                $product->product_meta_image;
            }

            if (array_key_exists('size_chart_image_id', $data)) {
                $product->size_chart_image_id = $data['size_chart_image_id'];
                $product->save();
            }

            // Sync galleries if provided (method exists on model)
            if (array_key_exists('product_galleries_id', $data)) {
                $product->product_galleries()->sync($data['product_galleries_id'] ?? []);
            }

            // Sync categories/tags if provided (methods exist on model)
            if (array_key_exists('categories', $data)) {
                $product->categories()->sync($data['categories'] ?? []);
            }
            if (array_key_exists('tags', $data)) {
                $product->tags()->sync($data['tags'] ?? []);
            }

            // ✅ Use correct relation names from Product model:
            // - similar_products()  = "related products"
            // - cross_products()    = "cross-sell products"
            if (array_key_exists('related_products', $data)) {
                $product->similar_products()->sync($data['related_products'] ?? []);
            }
            if (array_key_exists('cross_sell_products', $data)) {
                $product->cross_products()->sync($data['cross_sell_products'] ?? []);
            }

            // Variations sync for classified (kept as-is)
            if (!empty($data['variations']) && ($data['type'] ?? null) === 'classified') {
                $variationsIds = [];

                foreach ($data['variations'] as $elem) {
                    $payloadV = [
                        'name'               => $elem['name'] ?? null,
                        'price'              => $elem['price'] ?? null,
                        'sale_price'         => $elem['sale_price'] ?? null,
                        'discount'           => $elem['discount'] ?? null,
                        'quantity'           => $elem['quantity'] ?? 0,
                        'sku'                => $elem['sku'] ?? null,
                        'stock_status'       => $elem['stock_status'] ?? null,
                        'status'             => isset($elem['status']) ? ($elem['status'] ? 1 : 0) : 0,
                        'variation_image_id' => $elem['variation_image_id'] ?? null,
                    ];

                    if (!empty($elem['attribute_values']) && is_array($elem['attribute_values'])) {
                        $payloadV['attribute_values'] = $elem['attribute_values'];
                    }

                    if (!empty($elem['id'])) {
                        $product->variations()->where('id', $elem['id'])->update($payloadV);
                        $vid = (int) $elem['id'];
                    } else {
                        $created = $product->variations()->create($payloadV);
                        $vid = $created->id;
                    }

                    $variationsIds[] = $vid;
                }

                // delete removed variations
                $product->variations()->whereNotIn('id', $variationsIds)->delete();
                $product->variations;
            }

            // Touch tax relation (kept as-is)
            $product->tax;

            DB::commit();
            $updated = $product->fresh()->load(config('enums.product.with'));
            return $updated
                ->setAppends(config('enums.product.appends'))
                ->makeVisible(config('enums.product.visible'));
        } catch (Exception $e) {
            DB::rollBack();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }



    /* ---------------------------- rest (unchanged) ------------------- */

    public function destroy($id)
    {
        try {
            return $this->model->findOrFail($id)->destroy($id);
        } catch (Exception $e){
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function status($id, $status)
    {
        try {
            $product = $this->model->with(config('enums.product.with'))
                ->findOrFail($id)
                ->makeVisible(config('enums.product.visible'))
                ->setAppends(config('enums.product.appends'));

            // Permanently disabled products can never be turned on
            if ($product->is_permanently_disabled && $status) {
                return $product;
            }

            $product->update(['status' => $status]);
            return $product;
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function permanentlyDisable($id, $disable)
    {
        try {
            $product = $this->model->with(config('enums.product.with'))
                ->findOrFail($id)
                ->makeVisible(config('enums.product.visible'))
                ->setAppends(config('enums.product.appends'));

            $updates = ['is_permanently_disabled' => (bool) $disable];
            // When permanently disabling, also turn off status and approval
            if ($disable) {
                $updates['status'] = 0;
                $updates['is_approved'] = 0;
            }

            $product->update($updates);
            return $product;
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function approve($id, $approve)
    {
        try {
            $product = $this->model->with(config('enums.product.with'))
                ->findOrFail($id)
                ->makeVisible(config('enums.product.visible'))
                ->setAppends(config('enums.product.appends'));

            $product->update(['is_approved' => $approve]);
            $product->total_in_approved_products = $this->model->where('is_approved', false)->count();

            return $product;
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function deleteAll($ids)
    {
        try {
            return $this->model->whereIn('id', $ids)->delete();
        } catch (Exception $e){
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function import()
    {
        DB::beginTransaction();
        try {
            $productImport = new ProductImport();
            Excel::import($productImport, request()->file('products'));
            DB::commit();

            return $productImport->getImportedProducts();
        } catch (Exception $e){
            DB::rollBack();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getProductsExportUrl()
    {
        try {
            return route('products.export');
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function export()
    {
        try {
            return Excel::download(new ProductsExport, 'products.csv');
        } catch (Exception $e){
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getRelicateProductName($name)
    {
        $i = 1;
        do {
            $name = $name.str_repeat(' (COPY)', $i++);
        } while ($this->model->where('name', $name)->exists());

        return $name;
    }

    public function getVariationSKU($sku)
    {
        $i = 1;
        do {
            $sku = $sku.str_repeat(' (COPY)', $i++);
        } while ($this->model->variations()->where("sku", $sku)->exists());

        return $sku;
    }

    public function relationProductModels($request, $product)
    {
        $related_product_ids = null;

        if (!is_null($request->related_products ?? null) && ($request->is_random_related_products ?? false)) {
            if (isset($request->categories) && is_array($request->categories)) {
                $rand_category_id = $request->categories[array_rand($request->categories)];
                $related_product_ids = Helpers::getRelatedProductId($this->model, $rand_category_id);
                $product->similar_products()->attach($related_product_ids);
                $product->related_products;
            }
        }

        if (isset($request->product_galleries_id)) {
            $product->product_galleries()->attach($request->product_galleries_id);
            $product->product_galleries;
        }

        if (isset($request->categories)) {
            $product->categories()->attach($request->categories);
            $product->categories;
        }

        if (isset($request->tags)) {
            $product->tags()->attach($request->tags);
            $product->tags;
        }

        if (isset($request->attributes_ids)) {
            $product->attributes()->attach($request->attributes_ids);
            $product->attributes;
        }

        if (!is_null($request->related_products ?? null) && !($request->is_random_related_products ?? false)) {
            $product->similar_products()->attach($request->related_products ?? $related_product_ids);
            $product->related_products;
        }

        if (isset($request->cross_sell_products)) {
            $product->cross_products()->attach($request->cross_sell_products);
            $product->cross_products;
        }
    }

    public function createProductVariation($product, $variation)
    {
        if (isset($variation['attribute_values'])) {
            $variation['sale_price'] = $variation['price'];
            if (isset($variation['discount'])) {
                $variation['sale_price'] = round($variation['price'] - (($variation['price'] * $variation['discount'])/100),2);
            }

            if (isset($variation['quantity'])) {
                $variation['stock_status'] = StockStatus::OUT_OF_STOCK;
                if ($variation['quantity'] > 0) {
                    $variation['stock_status'] = StockStatus::IN_STOCK;
                }
            }

            $variationData = $product->variations()->create([
                'name'               => $variation['name'],
                'price'              => $variation['price'],
                'quantity'           => $variation['quantity'],
                'sku'                => $this->getVariationSKU($variation['sku']),
                'sale_price'         => $variation['sale_price'],
                'discount'           => $variation['discount'] ?? null,
                'stock_status'       => $variation['stock_status'],
                'variation_image_id' => $variation['variation_image_id'] ?? null,
                'status'             => $variation['status'],
                'product_id'         => $product['id']
            ]);

            $variationData->attribute_values()->attach($variation['attribute_values']);
        }
    }

    public function replicate($ids)
    {
        DB::beginTransaction();
        try {
            $clones = [];
            $usedSkus = []; // Track SKUs used in this batch to avoid duplicates

            // Disable Scout (Elasticsearch) sync for Product model during replication
            Product::withoutSyncingToSearch(function () use ($ids, &$clones, &$usedSkus) {
                foreach ($ids as $id) {
                    // Pull relations you need for cloning, but no need to select search_tsv explicitly
                    $product = $this->model->with(['variations','product_galleries','categories','tags','attributes','reviews'])->findOrFail($id);

                // Exclude: counters, timestamps, soft-delete, unique + generated columns
                $clone = $product->replicate([
                    'id',
                    'sku',
                    'slug',
                    'search_tsv',        // GENERATED column -> must not be inserted
                    'orders_count',
                    'reviews_count',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                ]);

                // Give the clone a new identity
                $originalName = $product->name;
                $clone->name  = $this->getRelicateProductName($originalName); // your helper
                $clone->slug  = $this->uniqueSlug(Str::slug($clone->name));
                $clone->sku   = $this->uniqueSkuWithBatch($product->sku, $usedSkus); // Track used SKUs in batch

                // Optional: reset approval or other flags if you want
                // $clone->is_approved = 0;

                $clone->setCreatedAt(Carbon::now());
                $clone->setUpdatedAt(Carbon::now());
                $clone->deleted_at = null;

                $clone->save();

                // Clone media and relations from original
                // Single images: reuse same attachment ids
                $clone->product_thumbnail_id  = $product->product_thumbnail_id;
                $clone->product_meta_image_id = $product->product_meta_image_id;
                $clone->size_chart_image_id   = $product->size_chart_image_id;
                $clone->save();

                // Galleries
                $galleryIds = $product->relationLoaded('product_galleries') ? $product->product_galleries->pluck('id')->all() : [];
                if (!empty($galleryIds)) {
                    $clone->product_galleries()->sync($galleryIds);
                }

                // Categories
                $categoryIds = $product->relationLoaded('categories') ? $product->categories->pluck('id')->all() : [];
                if (!empty($categoryIds)) {
                    $clone->categories()->sync($categoryIds);
                }

                // Tags
                $tagIds = $product->relationLoaded('tags') ? $product->tags->pluck('id')->all() : [];
                if (!empty($tagIds)) {
                    $clone->tags()->sync($tagIds);
                }

                // Attributes
                if ($product->relationLoaded('attributes')) {
                    $attrIds = $product->attributes->pluck('id')->all();
                    if (!empty($attrIds)) {
                        $clone->attributes()->sync($attrIds);
                    }
                }

                // Clone variations (ensure unique SKUs + exclude generated/timestamps)
                if ($product->type === 'classified' && isset($product->variations)) {
                    foreach ($product->variations as $variation) {
                        $payload = Arr::except($variation->toArray(), [
                            'id', 'created_at', 'updated_at', 'deleted_at', 'search_tsv'
                        ]);
                        $payload['sku'] = $this->uniqueSkuWithBatch($variation->sku, $usedSkus);
                        $payload['attribute_values'] = $variation->attribute_values->pluck('id')->all();
                        $this->createProductVariation($clone, $payload);
                    }
                    $clone->load('variations');
                }

                // Clone reviews (use DB::table to bypass the Review boot() hook
                // which would overwrite consumer_id with the current admin user)
                if ($product->relationLoaded('reviews') && $product->reviews->isNotEmpty()) {
                    $now = Carbon::now();
                    foreach ($product->reviews as $review) {
                        DB::table('reviews')->insert([
                            'product_id'      => $clone->id,
                            'consumer_id'     => $review->consumer_id,
                            'store_id'        => $review->store_id,
                            'rating'          => $review->rating,
                            'description'     => $review->description,
                            'review_image_id' => $review->review_image_id,
                            'created_at'      => $review->created_at ?? $now,
                            'updated_at'      => $now,
                        ]);
                    }
                }

                    // Don't use fresh() as it might trigger Scout - just reload relations
                    $clone->load('variations', 'product_galleries', 'categories', 'tags', 'attributes');
                    $clones[] = $clone;
                }
            }); // End Product::withoutSyncingToSearch

            DB::commit();
            return $clones;

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new ExceptionHandler($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Ensure slug is globally unique by suffixing -copy, -copy-2, ...
     */
    protected function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;
        while ($this->model->where('slug', $slug)->exists()) {
            $slug = Str::limit($base, 80, '') . '-copy' . ($i > 1 ? "-$i" : '');
            $i++;
        }
        return $slug;
    }

    /**
     * Ensure SKU is unique. If original is null/empty, derive from slug.
     */
    protected function uniqueSku(?string $sku): string
    {
        $sku = trim((string)$sku);
        if ($sku === '') {
            $sku = strtoupper(Str::slug(Str::random(6), '-'));
        }

        $base = $sku;
        $i = 1;
        while ($this->model->where('sku', $sku)->exists()) {
            $sku = $base . '-COPY' . ($i > 1 ? "-$i" : '');
            $i++;
        }
        return $sku;
    }

    /**
     * Ensure SKU is unique, including tracking SKUs used within the same batch.
     * This prevents duplicate SKU errors when cloning multiple products with the same original SKU.
     *
     * @param string|null $sku The original SKU
     * @param array &$usedSkus Reference to array tracking SKUs used in current batch
     * @return string Unique SKU
     */
    protected function uniqueSkuWithBatch(?string $sku, array &$usedSkus): string
    {
        $sku = trim((string)$sku);
        if ($sku === '') {
            $sku = strtoupper(Str::slug(Str::random(6), '-'));
        }

        $base = $sku;
        $candidate = $base . '-COPY';

        // Check both database AND batch usage, incrementing until we find a unique one
        $counter = 1;
        while ($this->model->where('sku', $candidate)->exists() || in_array($candidate, $usedSkus)) {
            $counter++;
            $candidate = $base . '-COPY-' . $counter;
        }

        // Track this SKU as used in this batch
        $usedSkus[] = $candidate;

        return $candidate;
    }

    public function getProductBySlug_($slug)
    {
        try {
            $product = $this->model->where('slug',$slug)
                ->with(config('enums.product.with'))
                ->firstOrFail();

            $usedValueIds = collect($product->variations ?? [])
                ->flatMap(function ($variation) {
                    return $variation->attribute_values->pluck('id');
                })
                ->unique()
                ->values();

            $product->load(['attributes.attribute_values' => function ($q) use ($usedValueIds) {
                if ($usedValueIds->isNotEmpty()) {
                    $q->whereIn('id', $usedValueIds->all());
                } else {
                    $q->whereRaw('1=0');
                }
            }]);

            return $product
                ->setAppends(config('enums.product.appends'))
                ->makeVisible(config('enums.product.visible'));
        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getProductBySlug($slug): ?\App\Models\Product
    {
        try {
            $product = $this->model->where('slug', $slug)
                ->where('status', 1)
                ->where('is_approved', 1)
                ->with(config('enums.product.with'))
                ->first();
            if (!$product) return null;

            $usedValueIds = collect($product->variations ?? [])
                ->flatMap(function ($variation) {
                    return $variation->attribute_values->pluck('id');
                })
                ->unique()
                ->values();

            $product->load(['attributes.attribute_values' => function ($q) use ($usedValueIds) {
                if ($usedValueIds->isNotEmpty()) {
                    $q->whereIn('id', $usedValueIds->all());
                } else {
                    $q->whereRaw('1=0');
                }
            }]);


            return $product
                ->setAppends(config('enums.product.appends'))
                ->makeVisible(config('enums.product.visible'));
        } catch (\Throwable $e) {
                report($e);
                return null;
        }
    }
}
