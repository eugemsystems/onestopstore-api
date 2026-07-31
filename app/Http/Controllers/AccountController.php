<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateStoreProfileRequest;
use App\Repositories\Eloquents\AccountRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Account", description="Account profile & password")
 */
class AccountController extends Controller
{
    protected $repository;

    public function __construct(AccountRepository $repository){
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/self",
     *   tags={"Account"},
     *   summary="Get authenticated user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function self()
    {
        return $this->repository->self();
    }

    /**
     * @OA\Put(
     *   path="/api/updatePassword",
     *   tags={"Account"},
     *   summary="Update password",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        return $this->repository->updatePassword($request);
    }

    /**
     * @OA\Put(
     *   path="/api/updateProfile",
     *   tags={"Account"},
     *   summary="Update profile",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        return $this->repository->updateProfile($request);
    }

    /**
     * @OA\Put(
     *   path="/api/updateStoreProfile",
     *   tags={"Account"},
     *   summary="Update store profile",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function updateStoreProfile(UpdateStoreProfileRequest $request)
    {
        return $this->repository->updateStoreProfile($request);
    }
}
