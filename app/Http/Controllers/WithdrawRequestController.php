<?php

namespace App\Http\Controllers;

use Exception;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Models\WithdrawRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\CreateWithdrawRequest;
use App\Http\Requests\UpdateWithdrawRequest;
use App\Repositories\Eloquents\WithdrawRequestRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Withdraw Requests", description="Vendor withdrawal requests")
 */
class WithdrawRequestController extends Controller
{
    public $repository;

    public function __construct(WithdrawRequestRepository $repository)
    {
        $this->authorizeResource(WithdrawRequest::class, 'withdraw_request',[
            'except' => 'destroy'
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/withdrawRequest",
     *   tags={"Withdraw Requests"},
     *   summary="List withdraw requests",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {

            $withdrawRequest = $this->filter($this->repository, $request);
            return $withdrawRequest->latest('created_at')->paginate($request->paginate);

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
     *   path="/api/withdrawRequest",
     *   tags={"Withdraw Requests"},
     *   summary="Create withdraw request",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateWithdrawRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/withdrawRequest/{withdrawRequest}",
     *   tags={"Withdraw Requests"},
     *   summary="Get withdraw request",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="withdrawRequest", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(WithdrawRequest $withdrawRequest)
    {
        return $this->repository->show($withdrawRequest->id);
    }

    public function edit(WithdrawRequest $withdrawRequest)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/withdrawRequest/{withdrawRequest}",
     *   tags={"Withdraw Requests"},
     *   summary="Update withdraw request",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="withdrawRequest", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateWithdrawRequest $request, WithdrawRequest $withdrawRequest)
    {
        return $this->repository->update($request->all(), $withdrawRequest->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/withdrawRequest/{withdrawRequest}",
     *   tags={"Withdraw Requests"},
     *   summary="Delete withdraw request",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="withdrawRequest", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(WithdrawRequest $withdrawRequest)
    {
        // not implemented
    }

    public function filter($withdrawRequest, $request)
    {
        $roleName = Helpers::getCurrentRoleName();
        if ($roleName == RoleEnum::VENDOR) {
            $withdrawRequest = $this->repository->where('vendor_id',Helpers::getCurrentUserId());
        }

        if ($request->field && $request->sort) {
            $withdrawRequest = $withdrawRequest->orderBy($request->field, $request->sort);
        }

        if ($request->start_date && $request->end_date) {
            $withdrawRequest = $withdrawRequest->whereBetween('created_at',[$request->start_date, $request->end_date]);
        }

        return $withdrawRequest;
    }
}
