<?php

namespace App\Http\Controllers;

use App\Models\Shipping;
use Illuminate\Http\Request;
use App\Http\Requests\CreateShippingRequest;
use App\Http\Requests\UpdateShippingRequest;
use App\Repositories\Eloquents\ShippingRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Shipping", description="Shipping methods")
 */
class ShippingController extends Controller
{
    public $repository;

    public function __construct(ShippingRepository $repository)
    {
        $this->authorizeResource(Shipping::class, 'shipping');
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/shipping",
     *   tags={"Shipping"},
     *   summary="List shipping methods",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index()
    {
        return $this->repository->latest('created_at')->with(['country','shipping_rules'])->get();
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/shipping",
     *   tags={"Shipping"},
     *   summary="Create shipping method",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateShippingRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/shipping/{shipping}",
     *   tags={"Shipping"},
     *   summary="Get shipping method",
     *   @OA\Parameter(name="shipping", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Shipping $shipping)
    {
        return $this->repository->show($shipping->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/shipping/{shipping}",
     *   tags={"Shipping"},
     *   summary="Update shipping method",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="shipping", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateShippingRequest $request, Shipping $shipping)
    {
        return $this->repository->update($request->all(), $shipping->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/shipping/{shipping}",
     *   tags={"Shipping"},
     *   summary="Delete shipping method",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="shipping", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Shipping $shipping)
    {
        return $this->repository->destroy($shipping->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/shipping/{id}/{status}",
     *   tags={"Shipping"},
     *   summary="Update shipping status",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }
}
