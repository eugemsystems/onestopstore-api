<?php

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateThemeRequest;
use App\Repositories\Eloquents\ThemeRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Themes", description="Themes")
 */
class ThemeController extends Controller
{
    public $repository;

    public function __construct(ThemeRepository $repository)
    {
        $this->authorizeResource(Theme::class,'theme',[
            'except' => 'index', 'show', 'destroy'
        ]);

        return $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/theme",
     *   tags={"Themes"},
     *   summary="List themes",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        return $this->repository->latest('created_at')->paginate($request->paginate ?? 10);
    }

    public function create()
    {
        // not used in API
    }

    public function store(Request $request)
    {
        // not implemented
    }

    /**
     * @OA\Get(
     *   path="/api/theme/{theme}",
     *   tags={"Themes"},
     *   summary="Get theme",
     *   @OA\Parameter(name="theme", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Theme $theme)
    {
        return $this->repository->show($theme->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/theme/{theme}",
     *   tags={"Themes"},
     *   summary="Update theme",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="theme", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateThemeRequest $request, Theme $theme)
    {
        return $this->repository->update($request->all(), $theme->getId($request));
    }

    public function destroy($id)
    {
        // not implemented
    }
}
