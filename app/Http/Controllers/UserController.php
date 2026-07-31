<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\UserRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache; // <-- add
use Illuminate\Support\Facades\DB;     // <-- add
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Users", description="Users")
 */
class UserController extends Controller
{
    protected $repository;

    public function __construct(UserRepository $repository)
    {
        $this->authorizeResource(User::class,'user');
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/user",
     *   tags={"Users"},
     *   summary="List users",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Parameter(name="role", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="field", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="isStoreExists", in="query", required=false, @OA\Schema(type="boolean")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {
            // Build a cache key from the query params
            $perPage = (int) ($request->paginate ?? 15);
            $perPage = $perPage > 0 ? min($perPage, 100) : 15;

            $cacheKey = sprintf(
                'users:list:v1|search=%s|name=%s|email=%s|status=%s|role=%s|field=%s|sort=%s|storeExists=%s|page=%d|per=%d',
                strtolower((string) $request->get('search', '')),
                strtolower((string) $request->get('name', '')),
                strtolower((string) $request->get('email', '')),
                (string) $request->get('status', '*'),
                (string) $request->get('role', '*'),
                (string) $request->get('field', '*'),
                (string) $request->get('sort', '*'),
                (string) $request->get('isStoreExists', 'null'),
                (int) $request->get('page', 1),
                $perPage
            );

            // Cache for 60 seconds; flush on user save/delete in a model observer if needed
            $ttl = 60; // seconds

            $paginator = Cache::tags(['users','search'])->remember($cacheKey, $ttl, function () use ($request, $perPage) {
                $query = $this->filter($this->repository, $request);
                return $query->latest('created_at')->paginate($perPage);
            });

            return $paginator;

        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Optional cached consumer list
     * @OA\Get(
     *   path="/api/cached-users",
     *   tags={"Users"},
     *   summary="Cached user list (consumers)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function cachedUsers(Request $request)
    {
        try {
            $perPage = (int) ($request->paginate ?? 15);
            $perPage = $perPage > 0 ? min($perPage, 100) : 15;

            $users = User::where('status', 1)
                ->whereNull('deleted_at')
                ->whereHas('roles', function($query) {
                    $query->whereName(RoleEnum::CONSUMER);
                });

            return $users->latest('created_at')->paginate($perPage);

        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *   path="/api/user",
     *   tags={"Users"},
     *   summary="Create user",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateUserRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/user/{user}",
     *   tags={"Users"},
     *   summary="Get user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(User $user)
    {
        return $this->repository->show($user->id);
    }

    /**
     * @OA\Put(
     *   path="/api/user/{user}",
     *   tags={"Users"},
     *   summary="Update user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        return $this->repository->update($request->all(), $user->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/user/{user}",
     *   tags={"Users"},
     *   summary="Delete user",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, User $user)
    {
        return $this->repository->destroy($user->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/user/{id}/{status}",
     *   tags={"Users"},
     *   summary="Update user status",
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
     * @OA\Delete(
     *   path="/api/user/address/{id}",
     *   tags={"Users"},
     *   summary="Delete user address",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAddress(Request $request, User $user)
    {
        return $this->repository->deleteAddress($user->getId($request));
    }

    /**
     * @OA\Post(
     *   path="/api/user/deleteAll",
     *   tags={"Users"},
     *   summary="Bulk delete users",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    /**
     * @OA\Post(
     *   path="/api/user/csv/import",
     *   tags={"Users"},
     *   summary="Import users (CSV)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Import started")
     * )
     */
    public function import()
    {
        return $this->repository->import();
    }

    public function getUsersExportUrl(Request $request)
    {
        return $this->repository->getUsersExportUrl($request);
    }

    /**
     * @OA\Post(
     *   path="/api/user/csv/export",
     *   tags={"Users"},
     *   summary="Export users (CSV)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Export URL")
     * )
     */
    public function export()
    {
        return $this->repository->export();
    }

    public function filter($users, $request)
    {
        if (Helpers::isUserLogin()) {
            $roleName = Helpers::getCurrentRoleName();
            // If you want to scope by creator for non-admins, uncomment:
            // if ($roleName != RoleEnum::ADMIN) {
            //     $users = $users->where('created_by_id', Helpers::getCurrentUserId());
            // }
        }

        if ($request->field && $request->sort) {
            $users = $users->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $users = $users->where('status', $request->status);
        }

        // Avoid subqueries with ->get(); use raw subquery
        if (!is_null($request->isStoreExists)) {
            $sub = DB::table('stores')->select('vendor_id');

            $isTrue = filter_var($request->isStoreExists, FILTER_VALIDATE_BOOLEAN);
            if ($isTrue) {
                $users = $users->whereIn('id', $sub);
            } else {
                $users = $users->whereNotIn('id', $sub);
            }
        }

        if ($request->role) {
            $role = $request->role;
            $users = $users->whereHas('roles', function($query) use($role) {
                $query->whereName($role);
            });
        } else {
            $users = $users->whereHas('roles', function($query){
                $query->whereNotIn('name', [RoleEnum::ADMIN, RoleEnum::VENDOR]);
            });
        }

        return $users;
    }

    /**
     * @OA\Delete(
     *   path="/api/deleteaccount",
     *   tags={"Users"},
     *   summary="Delete (deactivate) the authenticated user's own account",
     *   description="Sets the user's status to 0, revokes all Sanctum tokens, and soft-deletes the account. The record remains in the database but is deactivated and inaccessible.",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Account deleted successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Account deleted successfully.")
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 1. Deactivate account
        $user->status = 0;
        $user->save();

        // 2. Revoke all Sanctum tokens so the user is immediately logged out everywhere
        $user->tokens()->delete();

        // 3. Soft-delete the record (sets deleted_at)
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully.',
        ], 200);
    }
}
