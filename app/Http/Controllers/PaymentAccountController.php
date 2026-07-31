<?php

namespace App\Http\Controllers;

use Exception;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Models\PaymentAccount;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\UpdatePaymentAccountRequest;
use App\Repositories\Eloquents\PaymentAccountRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Payment Accounts", description="Payment account configs")
 */
class PaymentAccountController extends Controller
{
    public $repository;

    public function __construct(PaymentAccountRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *   path="/api/paymentAccount",
     *   tags={"Payment Accounts"},
     *   summary="List accounts",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index()
    {
        try {

            return $this->repository->where('user_id', Helpers::getCurrentUserId())->first();

        }  catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // not used in API
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *   path="/api/paymentAccount",
     *   tags={"Payment Accounts"},
     *   summary="Create account",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(UpdatePaymentAccountRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *   path="/api/paymentAccount/{paymentAccount}",
     *   tags={"Payment Accounts"},
     *   summary="Get account",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="paymentAccount", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(PaymentAccount $paymentAccount)
    {
        return $this->repository->show($paymentAccount->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentAccount $paymentAccount)
    {
        // not used in API
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *   path="/api/paymentAccount/{paymentAccount}",
     *   tags={"Payment Accounts"},
     *   summary="Update account",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="paymentAccount", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdatePaymentAccountRequest $request, PaymentAccount $paymentAccount)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *   path="/api/paymentAccount/{paymentAccount}",
     *   tags={"Payment Accounts"},
     *   summary="Delete account",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="paymentAccount", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, PaymentAccount $paymentAccount)
    {
       // implement if needed
    }
}
