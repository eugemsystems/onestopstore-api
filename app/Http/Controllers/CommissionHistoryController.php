<?php

namespace App\Http\Controllers;

use Exception;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Models\CommissionHistory;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\CommissionHistoryRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Commission History", description="Commission history")
 */
class CommissionHistoryController extends Controller
{
    public $repository;

    public function __construct(CommissionHistoryRepository $repository)
    {
        $this->authorizeResource(CommissionHistory::class, 'commissionHistory');
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/commissionHistory",
     *   tags={"Commission History"},
     *   summary="List commission history",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {

            $commissionHistories = $this->filter($this->repository, $request);
            return $commissionHistories->latest('created_at')->paginate($request->paginate ?? $commissionHistories->count());

        }  catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/commissionHistory",
     *   tags={"Commission History"},
     *   summary="Create commission history",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store()
    {
        return $this->repository->store();
    }

    /**
     * @OA\Get(
     *   path="/api/commissionHistory/{commissionHistory}",
     *   tags={"Commission History"},
     *   summary="Get commission history record",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="commissionHistory", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(CommissionHistory $commissionHistory)
    {
        // not implemented
    }

    public function edit(CommissionHistory $commissionHistory)
    {
        // not used in API
    }

    public function update(Request $request, CommissionHistory $commissionHistory)
    {
        // not implemented
    }

    public function destroy(CommissionHistory $commissionHistory)
    {
        // not implemented
    }

    public function filter($commissionHistories, $request)
    {
        $roleName = Helpers::getCurrentRoleName();
        if ($roleName == RoleEnum::VENDOR) {
            $commissionHistories = $commissionHistories->where('store_id', Helpers::getCurrentVendorStoreId());
        }

        if ($request->field && $request->sort) {
            $commissionHistories = $commissionHistories->orderBy($request->field, $request->sort);
        }

        if ($request->start_date && $request->end_date) {
            $commissionHistories = $commissionHistories->whereBetween('created_at',[$request->start_date, $request->end_date]);
        }

        return $commissionHistories;
    }
}
