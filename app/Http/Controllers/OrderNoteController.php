<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrderNoteRequest;
use App\Http\Requests\UpdateOrderNoteRequest;
use App\Repositories\Eloquents\OrderNoteRepository;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Order Notes", description="Notes attached to orders")
 */
class OrderNoteController extends Controller
{
    public function __construct(private OrderNoteRepository $repository)
    {
    }

    /**
     * @OA\Get(
     *   path="/api/order-notes",
     *   tags={"Order Notes"},
     *   summary="List order notes",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="order_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="privacy", in="query", required=false, @OA\Schema(type="string", enum={"public","private"})),
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        return $this->repository->index($request);
    }

    /**
     * @OA\Post(
     *   path="/api/order-notes",
     *   tags={"Order Notes"},
     *   summary="Create order note",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(type="object",
     *         required={"order_id","note","privacy"},
     *         @OA\Property(property="order_id", type="integer", example=1001),
     *         @OA\Property(property="note", type="string", example="Customer called to update address."),
     *         @OA\Property(property="privacy", type="string", enum={"public","private"}, example="private")
     *       ),
     *       example={"order_id":1001, "note":"Customer called to update address.", "privacy":"private"}
     *     )
     *   ),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateOrderNoteRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/order-notes/{id}",
     *   tags={"Order Notes"},
     *   summary="Get a single order note",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show($id)
    {
        return $this->repository->show($id);
    }

    /**
     * @OA\Put(
     *   path="/api/order-notes/{id}",
     *   tags={"Order Notes"},
     *   summary="Update an order note",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(type="object",
     *         @OA\Property(property="note", type="string", example="Updated delivery window."),
     *         @OA\Property(property="privacy", type="string", enum={"public","private"}, example="public")
     *       ),
     *       example={"note":"Updated delivery window.", "privacy":"public"}
     *     )
     *   ),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateOrderNoteRequest $request, $id)
    {
        return $this->repository->update($request->all(), $id);
    }

    /**
     * @OA\Delete(
     *   path="/api/order-notes/{id}",
     *   tags={"Order Notes"},
     *   summary="Delete an order note",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        return $this->repository->destroy($id);
    }
}
