<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Currency;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Requests\CreateCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Repositories\Eloquents\CurrencyRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Currencies", description="Currencies")
 */
class CurrencyController extends Controller
{
    use AuthorizesRequests;

    protected $repository;

    public function __construct(CurrencyRepository $repository)
    {
        $this->authorizeResource(Currency::class, 'currency', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/currency",
     *   tags={"Currencies"},
     *   summary="List currencies",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {
            $currencies = $this->filter($this->repository, $request);
            return $currencies->paginate($request->paginate ?? 10);

        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Get(
     *   path="/api/currencyFront",
     *   tags={"Currencies"},
     *   summary="Currencies (frontend)",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function indexFront(){
        return getCachedCurrencies();
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/currency",
     *   tags={"Currencies"},
     *   summary="Create currency",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateCurrencyRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/currency/{currency}",
     *   tags={"Currencies"},
     *   summary="Get currency",
     *   @OA\Parameter(name="currency", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Currency $currency)
    {
        return $this->repository->show($currency->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/currency/{currency}",
     *   tags={"Currencies"},
     *   summary="Update currency",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="currency", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency)
    {
        return $this->repository->update($request->all(), $currency->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/currency/{currency}",
     *   tags={"Currencies"},
     *   summary="Delete currency",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="currency", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Currency $currency)
    {
        return  $this->repository->destroy($currency->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/currency/{id}/{status}",
     *   tags={"Currencies"},
     *   summary="Update currency status",
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
     *   path="/api/currency/deleteAll",
     *   tags={"Currencies"},
     *   summary="Bulk delete currencies",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($currencies, $request)
    {
        if ($request->field && $request->sort) {
            $currencies = $currencies->orderBy($request->field, $request->sort);
        }

        return $currencies;
    }
}
