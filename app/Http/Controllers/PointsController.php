<?php

namespace App\Http\Controllers;

use Exception;
use App\Helpers\Helpers;
use App\Http\Traits\WalletPointsTrait;
use App\Http\Requests\WalletPointsRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\CreditDebitPointsRequest;
use App\Repositories\Eloquents\PointsRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Points", description="Consumer points/rewards management and transactions")
 */
class PointsController extends Controller
{
    use WalletPointsTrait;

    public $repository;

    public function __construct(PointsRepository $repository)
    {
        return $this->repository = $repository;
    }

    /**
     * Display a Consumer Points Transactions.
     *
     * @OA\Get(
     *   path="/api/points/consumer",
     *   tags={"Points"},
     *   summary="Get consumer points transactions",
     *   description="Retrieve points balance and transaction history for authenticated consumer",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="consumer_id",
     *     in="query",
     *     description="Consumer ID (optional, defaults to authenticated user)",
     *     required=false,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="paginate",
     *     in="query",
     *     description="Number of transactions per page",
     *     required=false,
     *     @OA\Schema(type="integer", default=15)
     *   ),
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Search transaction type",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="start_date",
     *     in="query",
     *     description="Filter transactions from date (Y-m-d)",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Parameter(
     *     name="end_date",
     *     in="query",
     *     description="Filter transactions to date (Y-m-d)",
     *     required=false,
     *     @OA\Schema(type="string", format="date")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Points data with transactions",
     *     @OA\JsonContent(
     *       @OA\Property(property="id", type="integer", example=1),
     *       @OA\Property(property="consumer_id", type="integer", example=5),
     *       @OA\Property(property="balance", type="integer", example=500),
     *       @OA\Property(property="transactions", type="object",
     *         @OA\Property(property="data", type="array",
     *           @OA\Items(
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="amount", type="integer", example=100),
     *             @OA\Property(property="type", type="string", example="credit"),
     *             @OA\Property(property="detail", type="string", example="Purchase reward"),
     *             @OA\Property(property="created_at", type="string", format="date-time")
     *           )
     *         ),
     *         @OA\Property(property="current_page", type="integer", example=1),
     *         @OA\Property(property="last_page", type="integer", example=3)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(WalletPointsRequest $request)
    {
        try {

            return $this->filter($this->repository, $request);

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Credit Amount to Consumer Points.
     *
     * @OA\Post(
     *   path="/api/credit/points",
     *   tags={"Points"},
     *   summary="Credit points",
     *   description="Add points to consumer account (Admin only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"consumer_id", "balance"},
     *       @OA\Property(property="consumer_id", type="integer", example=5),
     *       @OA\Property(property="balance", type="integer", example=200),
     *       @OA\Property(property="type", type="string", example="credit"),
     *       @OA\Property(property="detail", type="string", example="Bonus points")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Points credited successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Points credited successfully"),
     *       @OA\Property(property="balance", type="integer", example=700)
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function credit(CreditDebitPointsRequest $request)
    {
        return $this->repository->credit($request);
    }

    /**
     * Debit Amount from Consumer Points.
     *
     * @OA\Post(
     *   path="/api/debit/points",
     *   tags={"Points"},
     *   summary="Debit points",
     *   description="Deduct points from consumer account (Admin only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"consumer_id", "balance"},
     *       @OA\Property(property="consumer_id", type="integer", example=5),
     *       @OA\Property(property="balance", type="integer", example=100),
     *       @OA\Property(property="type", type="string", example="debit"),
     *       @OA\Property(property="detail", type="string", example="Points redemption")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Points debited successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Points debited successfully"),
     *       @OA\Property(property="balance", type="integer", example=400)
     *     )
     *   ),
     *   @OA\Response(response=400, description="Insufficient points"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     */
    public function debit(CreditDebitPointsRequest $request)
    {
        return $this->repository->debit($request);
    }

    public function filter($points, $request)
    {
        $consumer_id = $request->consumer_id ?? Helpers::getCurrentUserId();
        $points = $this->repository->where('consumer_id',$consumer_id)->first();

        if (!$points) {
            $points = $this->getPoints($request->consumer_id);
            $points = $points->fresh();
        }

        $transactions = $points->transactions()->where('type', 'LIKE', "%{$request->search}%");
        if ($request->start_date && $request->end_date) {
            $transactions->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $paginate = $request->paginate ?? $points->transactions()->count();
        $points->setRelation('transactions', $transactions->paginate($paginate));

        return $points;
    }
}
