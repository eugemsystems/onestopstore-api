<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Module;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use App\Http\Requests\CreateRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\RoleRepository;
use Illuminate\Support\Facades\Auth;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Roles", description="User roles & permissions")
 */
class RoleController extends Controller
{
    public $module;
    public $repository;

    public function __construct(RoleRepository $repository, Module $module)
    {
        $this->authorizeResource(Role::class, 'role', [
            'except' => ['index', 'show'],
        ]);

        $this->repository = $repository;
        $this->module = $module;
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *   path="/api/role",
     *   tags={"Roles"},
     *   summary="List roles",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="field", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Response(response=200, description="OK")
     * )
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {

            $roles = $this->filter($this->repository->with('permissions'), $request);
            return $roles->latest('created_at')->paginate($request->paginate ?? $roles->count());

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {
        // Not used in API
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *   path="/api/role",
     *   tags={"Roles"},
     *   summary="Create role",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(CreateRoleRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *   path="/api/role/{role}",
     *   tags={"Roles"},
     *   summary="Get role",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function show(Role $role)
    {
        return $this->repository->show($role->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function edit($id)
    {
        // Not used in API
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *   path="/api/role/{role}",
     *   tags={"Roles"},
     *   summary="Update role",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, Role $role)
    {
        return $this->repository->update($request->all(), isset($role->id) ? $role->id : $request->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *   path="/api/role/{role}",
     *   tags={"Roles"},
     *   summary="Delete role",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="role", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function destroy(Request $request, Role $role)
    {
        return $this->repository->destroy(isset($role->id) ? $role->id : $request->id);
    }

    /**
     * List modules and permissions
     *
     * @OA\Get(
     *   path="/api/module",
     *   tags={"Roles"},
     *   summary="List role modules",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function modules()
    {
        return $this->module->with('modulePermissions')->orderBy('sequence', 'asc')->get();
    }

    /**
     * Bulk delete roles
     *
     * @OA\Post(
     *   path="/api/role/deleteAll",
     *   tags={"Roles"},
     *   summary="Bulk delete roles",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($roles, $request)
    {
        if (Helpers::isUserLogin()) {
            $roleName = Helpers::getCurrentRoleName();
            if ($roleName != RoleEnum::ADMIN) {
                $roles = $this->repository->whereNot('name', $roleName);
            }
        }

        if ($request->field && $request->sort) {
            $roles = $roles->orderBy($request->field, $request->sort);
        }

        return $roles;
    }
}
