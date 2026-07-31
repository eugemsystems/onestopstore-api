<?php

namespace App\Http\Controllers;

use App\Helpers\Helpers;
use App\Models\HomePage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\UpdateHomePageRequest;
use App\Repositories\Eloquents\HomePageRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Home", description="Home content")
 */
class HomePageController extends Controller
{

    public $repository;

    public function __construct(HomePageRepository $repository)
    {
        $this->authorizeResource(HomePage::class, 'home', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/home",
     *   tags={"Home"},
     *   summary="List home sections",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        return $this->filter($this->repository, $request);
    }

     /**
     * @OA\Get(
     *   path="/api/home/{home}",
     *   tags={"Home"},
     *   summary="Get home section",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="home", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(HomePage $homePage)
    {

    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/home/{home}",
     *   tags={"Home"},
     *   summary="Update home section",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="home", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateHomePageRequest $request, HomePage $homePage)
    {
        return $this->repository->update($request->all(), $homePage->getId($request));
    }

     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, HomePage $homePage)
    {

    }

    public function filter($homePage, $request)
    {
        // When a specific slug is requested (admin), bypass cache
        if (!empty($request->slug)) {
            return $this->repository->where('slug', $request->slug)->first();
        }

        $cacheKey = 'homepage_active';
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($homePage) {
            $slug = Helpers::getActiveTheme()->first();
            return $homePage->where('slug', $slug)->first();
        });
    }
}
