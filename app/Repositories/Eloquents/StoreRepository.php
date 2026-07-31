<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Models\User;
use App\Models\Store;
use App\Models\Attachment;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Events\VendorRegisterEvent;
use Illuminate\Support\Facades\Hash;
use App\GraphQL\Exceptions\ExceptionHandler;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;

class StoreRepository extends BaseRepository
{
    protected $user;
    protected $role;

    protected $fieldSearchable = [
        'store_name' => 'like',
    ];

    public function boot()
    {
        try {

            $this->pushCriteria(app(RequestCriteria::class));

        } catch (ExceptionHandler $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    function model()
    {
        $this->user = new User();
        $this->role = new Role();
        return Store::class;
    }

    public function show($id)
    {
        try {

            return $this->model->with(config('enums.store.with'))->findOrFail($id);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {

            $settings = Helpers::getSettings();
            if ($settings['activation']['multivendor']) {
                $user = $this->user->create([
                    'name'     => $request->name,
                    'email'    => $request->email,
                    'country_code' => $request->country_code,
                    'phone'    => (string) $request->phone,
                    'password' => Hash::make($request->password),
                ]);

                $user->assignRole(RoleEnum::VENDOR);

                // Handle product catalog file upload if present
                $product_catalog_id = null;
                if ($request->hasFile('product_catalog')) {
                    $file = $request->file('product_catalog');
                    $product_catalog_id = $this->uploadCatalog($file);
                }

                $store = $this->model->create([
                    'store_name' => $request->store_name,
                    'description' => $request->description,
                    'country_id' => $request->country_id,
                    'state_id' => $request->state_id,
                    'city' => $request->city,
                    'address' => $request->address,
                    'pincode' => $request->pincode,
                    'facebook' => $request->facebook,
                    'twitter' => $request->twitter,
                    'instagram'=> $request->instagram,
                    'youtube'=> $request->youtube,
                    'pinterest'=> $request->pinterest,
                    'store_logo_id'=> $request->store_logo_id,
                    'store_cover_id'=> $request->store_cover_id,
                    'hide_vendor_email' => $request->hide_vendor_email,
                    'hide_vendor_phone' => $request->hide_vendor_phone,
                    'vendor_id' => $user->id,
                    'status' => $request->status ?? 0,
                    'is_approved' => $settings['activation']['store_auto_approve'],
                    // New vendor registration fields
                    'is_vat_registered' => $request->is_vat_registered,
                    'vat_number' => $request->vat_number,
                    'identification_type' => $request->identification_type,
                    'id_number' => $request->id_number,
                    'legal_name' => $request->legal_name,
                    'trading_name' => $request->trading_name,
                    'monthly_revenue' => $request->monthly_revenue,
                    'has_physical_stores' => $request->has_physical_stores,
                    'number_of_stores' => $request->number_of_stores,
                    'is_supplier_to_retailers' => $request->is_supplier_to_retailers,
                    'has_marketplace_accounts' => $request->has_marketplace_accounts,
                    'number_of_products' => $request->number_of_products,
                    'primary_category' => $request->primary_category,
                    'stock_holding' => $request->stock_holding,
                    'product_source' => $request->product_source,
                    'product_branding' => $request->product_branding,
                    'owned_brands' => $request->owned_brands,
                    'reseller_brands' => $request->reseller_brands,
                    'website' => $request->website,
                    'social_media_page' => $request->social_media_page,
                    'product_catalog_id' => $product_catalog_id,
                    'business_summary' => $request->business_summary,
                    'product_uniqueness' => $request->product_uniqueness,
                    'intended_products' => $request->intended_products,
                    'certifications' => $request->certifications,
                    'referral_source' => $request->referral_source,
                ]);

                $store->vendor->vendor_wallet()->create();
                $store->vendor->makeHidden(['store']);
                $store->vendor->vendor_wallet;

                event(new VendorRegisterEvent($store));

                DB::commit();
                return $store;
            }

            throw new Exception('The multi-vendor feature is currently deactivated.', 403);

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {

            $store = $this->model->findOrFail($id);
            $store->update($request);

            if (isset($request['store_logo_id'])) {
                $store->store_logo()->associate($request['store_logo_id']);
            }

            if (isset($request['store_cover_id'])) {
                $store->store_cover()->associate($request['store_cover_id']);
            }

            $store->vendor->makeHidden(['store']);
            if (isset($request['name'])) {
                $vendor['name'] = $request['name'];
            }

            if (isset($request['email'])) {
                $vendor['email'] = $request['email'];
            }

            if (isset($request['country_code'])) {
                $vendor['country_code'] = $request['country_code'];
            }

            if (isset($request['phone'])) {
                $vendor['phone'] = $request['phone'];
            }

            $store->vendor->update($vendor);

            DB::commit();
            return $store;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

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

            $store = $this->model->findOrFail($id);
            $store->update(['status' => $status]);

            return $store;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function deleteAll($ids)
    {
        try {

            $stores = $this->model->whereIn('id', $ids)->get();
            foreach($stores as $store) {
                $this->model->findOrFail($store->id)->destroy($store->id);
            }

            return true;

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function approve($id, $approve)
    {
        try {

            $store = $this->model->findOrFail($id);
            $store->update(['is_approved' => $approve]);

            $store = $store->fresh();
            $store->total_in_approved_stores = $this->model->where('is_approved', false)->count();

            return $store;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

        public function getStoreBySlug($slug)
        {
            try {

                return $this->model->where('slug', $slug)->firstOrFail();

            } catch (Exception $e) {

                throw new ExceptionHandler($e->getMessage(), $e->getCode());
            }
        }

    /**
     * Upload catalog file to laravel-media service
     */
    private function uploadCatalog($file)
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
                'model_type' => 'Store',
                'collection_name' => 'store_catalogs',
            ]);

            if (!$response->successful()) {
                throw new Exception('Failed to upload to media service: ' . $response->body());
            }

            $uploadedFile = $response->json('files')[0] ?? null;

            if (!$uploadedFile) {
                throw new Exception('No file data returned from media service');
            }

            // Store the attachment record locally
            $attachment = Attachment::create([
                'uuid' => $uploadedFile['uuid'],
                'name' => $uploadedFile['name'],
                'file_name' => $uploadedFile['file_name'],
                'mime_type' => $uploadedFile['mime_type'],
                'disk' => $uploadedFile['disk'],
                'collection_name' => $uploadedFile['collection_name'],
                'size' => $uploadedFile['size'],
                'original_url' => $uploadedFile['image_url'],
                'image_url' => $uploadedFile['image_url'],
                'model_id' => $uuid,
                'model_type' => 'Store',
            ]);

            return $attachment->id;

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Store catalog upload failed: ' . $e->getMessage());
            return null;
        }
    }
}
