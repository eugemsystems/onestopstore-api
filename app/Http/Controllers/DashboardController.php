<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Eloquents\DashboardRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Dashboard", description="Dashboards & stats")
 */
class DashboardController extends Controller
{
    protected $repository;

    public function __construct(DashboardRepository $repository){
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/statistics/count",
     *   tags={"Dashboard"},
     *   summary="Statistics count",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        return $this->repository->index($request);
    }

    /**
     * @OA\Get(
     *   path="/api/dashboard/chart",
     *   tags={"Dashboard"},
     *   summary="Chart data",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function chart(Request $request)
    {
        return $this->repository->chart($request);
    }
}
