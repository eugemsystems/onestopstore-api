<?php

namespace App\Http\Controllers;

use App\Models\ShippingRule;
use Illuminate\Http\Request;
use App\Http\Requests\CreateShippingRuleRequest;
use App\Http\Requests\UpdateShippingRuleRequest;
use App\Repositories\Eloquents\ShippingRuleRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Shipping Rules", description="Rule-based shipping")
 */
class ShippingRuleController extends Controller
{
    public $repository;

    public function __construct(ShippingRuleRepository $repository)
    {
        $this->authorizeResource(ShippingRule::class, 'shippingRule');
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/shippingRule",
     *   tags={"Shipping Rules"},
     *   summary="List shipping rules",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index()
    {
        return $this->repository->latest('created_at')->get();
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/shippingRule",
     *   tags={"Shipping Rules"},
     *   summary="Create shipping rule",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateShippingRuleRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/shippingRule/{shippingRule}",
     *   tags={"Shipping Rules"},
     *   summary="Get shipping rule",
     *   @OA\Parameter(name="shippingRule", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(ShippingRule $shippingRule)
    {
        return $this->repository->show($shippingRule->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/shippingRule/{shippingRule}",
     *   tags={"Shipping Rules"},
     *   summary="Update shipping rule",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="shippingRule", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateShippingRuleRequest $request, ShippingRule $shippingRule)
    {
        return $this->repository->update($request->all(), $shippingRule->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/shippingRule/{shippingRule}",
     *   tags={"Shipping Rules"},
     *   summary="Delete shipping rule",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="shippingRule", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, ShippingRule $shippingRule)
    {
        return $this->repository->destroy($shippingRule->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/shippingRule/{id}/{status}",
     *   tags={"Shipping Rules"},
     *   summary="Update shipping rule status",
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
