<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateThemeOptionRequest;
use App\Repositories\Eloquents\ThemeOptionRepository;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Theme Options", description="Theme options")
 */
class ThemeOptionController extends Controller
{
    public $repository;

    public function __construct(ThemeOptionRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/themeOptions",
     *   tags={"Theme Options"},
     *   summary="Get theme options",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index()
    {
        return getCachedThemeOptions();
    }

    /**
     * @OA\Get(
     *   path="/api/themeOptionsFront",
     *   tags={"Theme Options"},
     *   summary="Front theme options",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function indexFront(){
        return getCachedThemeOptions();
    }

    /**
     * @OA\Put(
     *   path="/api/themeOptions",
     *   tags={"Theme Options"},
     *   summary="Update theme options",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateThemeOptionRequest $request, $uuid = null)
    {
        //Log::info($request->all());
        return $this->repository->update($request->all(), $uuid);
    }
}
