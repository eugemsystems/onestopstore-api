<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\Eloquents\NotificationRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Notifications", description="Notifications")
 */
class NotificationController extends Controller
{
    protected $repository;

    public function __construct(NotificationRepository $repository){
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/notifications",
     *   tags={"Notifications"},
     *   summary="List notifications",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $user = $this->repository->findOrFail(Helpers::getCurrentUserId());
        return $user->notifications()->latest('created_at')->paginate($request->paginate ?? 10);
    }

    /**
     * @OA\Put(
     *   path="/api/notifications/markAsRead",
     *   tags={"Notifications"},
     *   summary="Mark all as read",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function markAsRead(Request $request)
    {
        return $this->repository->markAsRead($request);
    }

    /**
     * @OA\Delete(
     *   path="/api/notifications/{id}",
     *   tags={"Notifications"},
     *   summary="Delete notification",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy(Request $request)
    {
        return $this->repository->destroy($request->id);
    }
}
