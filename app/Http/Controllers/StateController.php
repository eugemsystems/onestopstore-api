<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;
use App\Repositories\Eloquents\StateRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="States", description="States/Provinces")
 */
class StateController extends Controller
{
    public $repository;

    public function __construct(StateRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/state",
     *   tags={"States"},
     *   summary="List states",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        // If country_id filter is provided, use cached states by country
        if ($request->has('filter') && isset($request->filter['country_id'])) {
            $states = getCachedStatesByCountry($request->filter['country_id']);
        } else {
            // Return all cached states
            $states = getCachedStates();
        }

        return response()->json($states);
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/state",
     *   tags={"States"},
     *   summary="Create state",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request)
    {
        // not implemented
    }

    /**
     * @OA\Get(
     *   path="/api/state/{state}",
     *   tags={"States"},
     *   summary="Get state",
     *   @OA\Parameter(name="state", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(State $state)
    {
        return $this->repository->show($state->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/state/{state}",
     *   tags={"States"},
     *   summary="Update state",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="state", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        // not implemented
    }

    /**
     * @OA\Delete(
     *   path="/api/state/{state}",
     *   tags={"States"},
     *   summary="Delete state",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="state", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        // not implemented
    }

    public function filter($states, $request)
    {
        if ($request->country_id) {
            $states = $states->where('country_id',$request->country_id);
        }

        return $states;
    }
}
