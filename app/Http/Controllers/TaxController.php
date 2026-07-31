<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Tax;
use Illuminate\Http\Request;
use App\Http\Requests\CreateTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\TaxRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Taxes", description="Tax management")
 */
class TaxController extends Controller
{
    protected $repository;

    public function __construct(TaxRepository $repository)
    {
        $this->authorizeResource(Tax::class, 'tax', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/tax",
     *   tags={"Taxes"},
     *   summary="List taxes",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {

            $taxes = $this->filter($this->repository, $request);
            return $taxes->latest('created_at')->paginate($request->paginate ?? $taxes->count());

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
     *   path="/api/tax",
     *   tags={"Taxes"},
     *   summary="Create tax",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateTaxRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/tax/{tax}",
     *   tags={"Taxes"},
     *   summary="Get tax",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="tax", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Tax $tax)
    {
        return $this->repository->show($tax->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/tax/{tax}",
     *   tags={"Taxes"},
     *   summary="Update tax",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="tax", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateTaxRequest $request, Tax $tax)
    {
        return $this->repository->update($request->all(), $tax->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/tax/{tax}",
     *   tags={"Taxes"},
     *   summary="Delete tax",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="tax", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Tax $tax)
    {
        return  $this->repository->destroy($tax->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/tax/{id}/{status}",
     *   tags={"Taxes"},
     *   summary="Update tax status",
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
     *   path="/api/tax/deleteAll",
     *   tags={"Taxes"},
     *   summary="Bulk delete taxes",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($taxes, $request)
    {
        if ($request->field && $request->sort) {
            $taxes = $taxes->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $taxes = $taxes->where('status',$request->status);
        }

        return $taxes;
    }
}
