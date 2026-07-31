<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Repositories\Eloquents\StoreRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Stores", description="Vendor stores")
 */
class StoreController extends Controller
{
    public $repository;

    public function __construct(StoreRepository $repository)
    {
        $this->authorizeResource(Store::class,'store',[
            'except' => [ 'index', 'show','store' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *   path="/api/store",
     *   tags={"Stores"},
     *   summary="List stores",
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Response(response=200, description="OK")
     * )
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $store = $this->filter($this->repository, $request);
        return $store->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // not used in API
    }

    /**
     * Store a newly created resource in storage - Vendor Registration.
     *
     * @OA\Post(
     *   path="/api/store",
     *   tags={"Stores"},
     *   summary="Register as vendor/seller",
     *   description="Create a new vendor store account. All business and product information required.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"name","email","phone","password","password_confirmation","is_vat_registered","identification_type","legal_name","store_name","description","country_id","state_id","city","address","pincode","monthly_revenue","has_physical_stores","is_supplier_to_retailers","has_marketplace_accounts","number_of_products","primary_category","stock_holding","product_source","product_branding","business_summary","referral_source"},
     *       @OA\Property(property="name", type="string", example="John Doe", description="Full name"),
     *       @OA\Property(property="email", type="string", format="email", example="seller@example.com", description="Email address (must be unique)"),
     *       @OA\Property(property="phone", type="string", example="+263771234567", description="Phone number (6-15 digits)"),
     *       @OA\Property(property="password", type="string", format="password", example="SecurePass123", description="Password (min 8 characters)"),
     *       @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123", description="Password confirmation"),
     *       @OA\Property(property="is_vat_registered", type="string", enum={"yes","no"}, example="yes", description="VAT registration status"),
     *       @OA\Property(property="vat_number", type="string", example="VAT123456", description="VAT number (required if is_vat_registered=yes)"),
     *       @OA\Property(property="identification_type", type="string", enum={"id","passport"}, example="id", description="ID document type"),
     *       @OA\Property(property="id_number", type="string", example="63-123456-A-12", description="ID number (required if identification_type=id)"),
     *       @OA\Property(property="legal_name", type="string", example="Test Trading Company (Pvt) Ltd", description="Business legal name"),
     *       @OA\Property(property="trading_name", type="string", example="Test Store", description="Trading name (optional)"),
     *       @OA\Property(property="store_name", type="string", example="My Online Store", description="Store name (must be unique)"),
     *       @OA\Property(property="description", type="string", example="We sell quality products", description="Store description (min 10 chars)"),
     *       @OA\Property(property="country_id", type="integer", example=1, description="Country ID"),
     *       @OA\Property(property="state_id", type="integer", example=5, description="State/Province ID"),
     *       @OA\Property(property="city", type="string", example="Harare", description="City"),
     *       @OA\Property(property="address", type="string", example="123 Main Street", description="Street address"),
     *       @OA\Property(property="pincode", type="string", example="00263", description="Postal code"),
     *       @OA\Property(property="monthly_revenue", type="string", enum={"less_than_1k","1k_2.5k","2.5k_5k","5k_25k","25k_50k","50k_125k","125k_plus"}, example="5k_25k", description="Monthly revenue bracket"),
     *       @OA\Property(property="has_physical_stores", type="string", enum={"yes","no"}, example="yes", description="Has physical stores"),
     *       @OA\Property(property="number_of_stores", type="integer", example=2, description="Number of physical stores (required if has_physical_stores=yes)"),
     *       @OA\Property(property="is_supplier_to_retailers", type="string", enum={"yes","no"}, example="yes", description="Supplies to retailers"),
     *       @OA\Property(property="has_marketplace_accounts", type="string", enum={"yes","no"}, example="no", description="Has other marketplace accounts"),
     *       @OA\Property(property="number_of_products", type="integer", example=50, description="Number of products (min 1)"),
     *       @OA\Property(property="primary_category", type="string", example="Electronics", description="Primary product category"),
     *       @OA\Property(property="stock_holding", type="string", enum={"whole_range","some_range","on_demand"}, example="whole_range", description="Stock holding status"),
     *       @OA\Property(property="product_source", type="string", enum={"imported","manufactured_locally","mixture"}, example="mixture", description="Product source"),
     *       @OA\Property(property="product_branding", type="string", enum={"branded","unbranded","combination"}, example="branded", description="Product branding"),
     *       @OA\Property(property="owned_brands", type="string", example="MyBrand, TestBrand", description="Owned brand names (optional)"),
     *       @OA\Property(property="reseller_brands", type="string", example="Samsung, Apple", description="Reseller brand names (optional)"),
     *       @OA\Property(property="website", type="string", format="url", example="https://www.mystore.com", description="Website URL (optional)"),
     *       @OA\Property(property="social_media_page", type="string", format="url", example="https://facebook.com/mystore", description="Social media page (optional)"),
     *       @OA\Property(property="business_summary", type="string", example="We provide quality electronics...", description="Business summary (min 10 chars)"),
     *       @OA\Property(property="product_uniqueness", type="string", example="Extended warranty", description="Product uniqueness (optional)"),
     *       @OA\Property(property="intended_products", type="string", example="Smartphones, laptops", description="Intended products (optional)"),
     *       @OA\Property(property="certifications", type="string", example="ISO 9001", description="Business certifications (optional)"),
     *       @OA\Property(property="referral_source", type="string", enum={"google","customer","expo","township_initiative","referral","tiktok","youtube","linkedin","facebook"}, example="google", description="How did you find us"),
     *       @OA\Property(property="facebook", type="string", format="url", example="https://facebook.com/store", description="Facebook URL (optional)"),
     *       @OA\Property(property="twitter", type="string", format="url", example="https://twitter.com/store", description="Twitter URL (optional)"),
     *       @OA\Property(property="instagram", type="string", format="url", example="https://instagram.com/store", description="Instagram URL (optional)"),
     *       @OA\Property(property="youtube", type="string", format="url", example="https://youtube.com/store", description="YouTube URL (optional)"),
     *       @OA\Property(property="pinterest", type="string", format="url", example="https://pinterest.com/store", description="Pinterest URL (optional)"),
     *       @OA\Property(property="status", type="integer", enum={0,1}, example=1, description="Status (1=active)")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Store registered successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="integer", example=201),
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Store registered successfully"),
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="store", type="object",
     *           @OA\Property(property="id", type="integer", example=123),
     *           @OA\Property(property="store_name", type="string", example="My Online Store"),
     *           @OA\Property(property="is_approved", type="integer", example=0),
     *           @OA\Property(property="status", type="integer", example=1)
     *         ),
     *         @OA\Property(property="vendor", type="object",
     *           @OA\Property(property="id", type="integer", example=456),
     *           @OA\Property(property="name", type="string", example="John Doe"),
     *           @OA\Property(property="email", type="string", example="seller@example.com")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="The given data was invalid."),
     *       @OA\Property(property="errors", type="object",
     *         @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken.")),
     *         @OA\Property(property="store_name", type="array", @OA\Items(type="string", example="The store name has already been taken."))
     *       )
     *     )
     *   )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateStoreRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *   path="/api/store/{store}",
     *   tags={"Stores"},
     *   summary="Get store",
     *   @OA\Parameter(name="store", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Store $store)
    {
        return $this->repository->show($store->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // not used in API
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *   path="/api/store/{store}",
     *   tags={"Stores"},
     *   summary="Update store",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="store", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        return $this->repository->update($request->all(), $store->getId($request));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *   path="/api/store/{store}",
     *   tags={"Stores"},
     *   summary="Delete store",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="store", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Store $store)
    {
        return $this->repository->destroy($store->getId($request));
    }

    /**
     * Update Status the specified resource from storage.
     *
     * @OA\Put(
     *   path="/api/store/{id}/{status}",
     *   tags={"Stores"},
     *   summary="Update store status",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Response(response=200, description="Updated")
     * )
     *
     * @param  int  $id
     * @param int $status
     * @return \Illuminate\Http\Response
     */
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    /**
     * Bulk delete stores
     *
     * @OA\Post(
     *   path="/api/store/deleteAll",
     *   tags={"Stores"},
     *   summary="Bulk delete stores",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    /**
     * Approve store
     *
     * @OA\Put(
     *   path="/api/store/approve/{id}/{status}",
     *   tags={"Stores"},
     *   summary="Approve store",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function approve($id, $status)
    {
        return $this->repository->approve($id, $status);
    }

    /**
     * Get store by slug
     *
     * @OA\Get(
     *   path="/api/store/slug/{slug}",
     *   tags={"Stores"},
     *   summary="Get store by slug",
     *   @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function getStoreBySlug($slug)
    {
        return $this->repository->getStoreBySlug($slug);
    }


    public function filter($store, $request)
    {
        isset($store->first()->vendor)?
            $store->first()->vendor->makeHidden(['store']) : $store;

        if ($request->field && $request->sort) {
            $store = $store->orderBy($request->field, $request->sort);
        }

        if ($request->top_vendor && $request->filter_by) {
            $store = Helpers::getTopVendors($store);
        }

        if (isset($request->status)) {
            $store = $store->where('status',$request->status);
        }

        return $store->with(config('enums.store.with'));
    }
}
