<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\CreateOrderStatusRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Repositories\Eloquents\OrderStatusRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Order Statuses", description="Order statuses")
 */
class OrderStatusController extends Controller
{
    protected $repository;

    public function __construct(OrderStatusRepository $repository)
    {
        $this->repository = $repository;
        $this->authorizeResource(OrderStatus::class, 'orderStatus', [
            'except' => ['index', 'show']
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/orderStatus",
     *   tags={"Order Statuses"},
     *   summary="List statuses",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {
            $orderStatus = $this->repository;
            $orderStatus = $this->filter($orderStatus, $request);
            return $orderStatus->oldest('sequence')->paginate($request->paginate ?? $orderStatus->count());

        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/orderStatus",
     *   tags={"Order Statuses"},
     *   summary="Create status",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateOrderStatusRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/orderStatus/{orderStatus}",
     *   tags={"Order Statuses"},
     *   summary="Get status",
     *   @OA\Parameter(name="orderStatus", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(OrderStatus $orderStatus)
    {
        return $this->repository->show($orderStatus->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/orderStatus/{orderStatus}",
     *   tags={"Order Statuses"},
     *   summary="Update order status",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="orderStatus", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateOrderStatusRequest $request, OrderStatus $orderStatus)
    {
        return $this->repository->update($request->all(), $orderStatus->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/orderStatus/{orderStatus}",
     *   tags={"Order Statuses"},
     *   summary="Delete order status",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="orderStatus", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, OrderStatus $orderStatus)
    {
        return $this->repository->destroy($orderStatus->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/orderStatus/{id}/{status}",
     *   tags={"Order Statuses"},
     *   summary="Update status flag",
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

    /**
     * @OA\Post(
     *   path="/api/orderStatus/deleteAll",
     *   tags={"Order Statuses"},
     *   summary="Bulk delete statuses",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($orderStatus, $request)
    {
        if ($request->field && $request->sort) {
            $orderStatus = $orderStatus->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $orderStatus = $orderStatus->where('status', $request->status);
        }

        return $orderStatus;
    }
}

