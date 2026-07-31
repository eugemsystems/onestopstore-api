<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use App\Repositories\Eloquents\CountryRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Countries", description="Countries")
 */
class CountryController extends Controller
{
    public $repository;

    public function __construct(CountryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/country",
     *   tags={"Countries"},
     *   summary="List countries",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index()
    {
        $countries = getCachedCountries();

        // Load states for each country (from cache)
        $countriesWithStates = $countries->map(function($country) {
            $states = getCachedStatesByCountry($country->id);
            $country->state = $states;
            return $country;
        });

        return response()->json($countriesWithStates);
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/country",
     *   tags={"Countries"},
     *   summary="Create country",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request)
    {
        // not implemented
    }

    /**
     * @OA\Get(
     *   path="/api/country/{country}",
     *   tags={"Countries"},
     *   summary="Get country",
     *   @OA\Parameter(name="country", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Country $country)
    {
        return $this->repository->show($country->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    public function update(Request $request, $id)
    {
        // not implemented
    }

    public function destroy($id)
    {
        // not implemented
    }
}
