<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Repositories\Eloquents\AddressRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Addresses", description="Operations related to addresses")
 */
class AddressController extends Controller
{
    public $repository;

    public function __construct(AddressRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/address",
     *   tags={"Addresses"},
     *   summary="List addresses",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $address = $this->filter($this->repository);
        return $address->latest('created_at')->paginate($request->paginate ?? $address->count());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/address",
     *   tags={"Addresses"},
     *   summary="Create address",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateAddressRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/address/{address}",
     *   tags={"Addresses"},
     *   summary="Get address",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="address", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Address $address)
    {
        return $this->repository->show($address->id);
    }

    /**
     * @OA\Put(
     *   path="/api/address/{address}",
     *   tags={"Addresses"},
     *   summary="Update address",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="address", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateAddressRequest $request, Address $address)
    {
        return $this->repository->update($request->all(), $address->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/address/{address}",
     *   tags={"Addresses"},
     *   summary="Delete address",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="address", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Address $address)
    {
        return $this->repository->destroy($address->getId($request));
    }

    public function filter($address)
    {
        $roleName = Helpers::getCurrentRoleName();
        if ($roleName != RoleEnum::ADMIN) {
            $address->where('user_id', Helpers::getCurrentUserId());
        }

        return $address;
    }
}
