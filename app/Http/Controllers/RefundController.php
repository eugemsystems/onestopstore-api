<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Refund;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateRefundRequest;
use App\Http\Requests\UpdateRefundRequest;
use App\Repositories\Eloquents\RefundRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Refunds", description="Refund requests management")
 */
class RefundController extends Controller
{
    public $repository;

    public function __construct(RefundRepository $repository)
    {
        $this->authorizeResource(Refund::class, 'refund',[
            'except' => 'destroy'
        ]);

        $this->repository = $repository;
    }

    /**
     * Display a listing of refund requests.
     *
     * @OA\Get(
     *   path="/api/refund",
     *   tags={"Refunds"},
     *   summary="List refund requests",
     *   description="Get all refund requests (role-based filtering: consumers see own, vendors see their store's, admins see all)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="paginate",
     *     in="query",
     *     description="Items per page",
     *     required=false,
     *     @OA\Schema(type="integer", default=15)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="List of refunds",
     *     @OA\JsonContent(
     *       @OA\Property(property="data", type="array",
     *         @OA\Items(
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="consumer_id", type="integer", example=5),
     *           @OA\Property(property="order_id", type="integer", example=123),
     *           @OA\Property(property="product_id", type="integer", example=45),
     *           @OA\Property(property="amount", type="number", format="float", example=150.00),
     *           @OA\Property(property="status", type="string", example="pending"),
     *           @OA\Property(property="payment_type", type="string", example="bank"),
     *           @OA\Property(property="reason", type="string", example="Product return"),
     *           @OA\Property(property="created_at", type="string", format="date-time")
     *         )
     *       ),
     *       @OA\Property(property="current_page", type="integer", example=1),
     *       @OA\Property(property="last_page", type="integer", example=5)
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        $refunds = $this->filter($this->repository, $request);
        return $refunds->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created refund request.
     *
     * @OA\Post(
     *   path="/api/refund",
     *   tags={"Refunds"},
     *   summary="Create refund request",
     *   description="Create a new refund request (Admin/Vendor)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"consumer_id", "amount", "reason"},
     *       @OA\Property(property="consumer_id", type="integer", example=5),
     *       @OA\Property(property="order_id", type="integer", example=123),
     *       @OA\Property(property="product_id", type="integer", example=45),
     *       @OA\Property(property="variation_id", type="integer", example=10),
     *       @OA\Property(property="amount", type="number", format="float", example=150.00),
     *       @OA\Property(property="quantity", type="integer", example=1),
     *       @OA\Property(property="reason", type="string", example="Defective product"),
     *       @OA\Property(property="payment_type", type="string", enum={"bank", "wallet", "paypal"}, example="bank")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Refund request created",
     *     @OA\JsonContent(
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="consumer_id", type="integer", example=5),
     *       @OA\Property(property="amount", type="number", format="float", example=150.00),
     *       @OA\Property(property="status", type="string", example="pending")
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateRefundRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Refund $refund)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Refund $refund)
    {
        //
    }

    /**
     * Update a refund request.
     *
     * @OA\Put(
     *   path="/api/refund/{id}",
     *   tags={"Refunds"},
     *   summary="Update refund status",
     *   description="Update refund request status (Admin only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="Refund ID",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"}, example="approved")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Refund updated",
     *     @OA\JsonContent(
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="status", type="string", example="approved")
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Refund not found")
     * )
     */
    public function update(UpdateRefundRequest $request, Refund $refund)
    {
        return $this->repository->update($request->all(), $refund->getId($request));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Refund $refund)
    {
        //
    }

    public function filter($refunds)
    {
        $roleName = Helpers::getCurrentRoleName();
        if ($roleName == RoleEnum::VENDOR) {
            $refunds = $refunds->where('store_id',Helpers::getCurrentVendorStoreId());
        }

        if ($roleName == RoleEnum::CONSUMER) {
            $refunds = $refunds->where('consumer_id',Helpers::getCurrentUserId());
        }

        return $refunds;
    }
}
