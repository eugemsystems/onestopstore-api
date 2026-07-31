<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use App\Http\Requests\CreateUpdateFaqRequest;
use App\Repositories\Eloquents\FaqRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="FAQs", description="Frequently Asked Questions")
 */
class FaqController extends Controller
{
    public $repository;

    public function __construct(FaqRepository $repository)
    {
        $this->authorizeResource(Faq::class,'faq', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/faq",
     *   tags={"FAQs"},
     *   summary="List FAQs",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        return $this->repository->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/faq",
     *   tags={"FAQs"},
     *   summary="Create FAQ",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateUpdateFaqRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/faq/{faq}",
     *   tags={"FAQs"},
     *   summary="Get FAQ",
     *   @OA\Parameter(name="faq", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Faq $faq)
    {
        return $this->repository->show($faq->id);
    }

    public function edit(Faq $faq)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/faq/{faq}",
     *   tags={"FAQs"},
     *   summary="Update FAQ",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="faq", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(CreateUpdateFaqRequest $request, Faq $faq)
    {
        return $this->repository->update($request->all(), $faq->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/faq/{faq}",
     *   tags={"FAQs"},
     *   summary="Delete FAQ",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="faq", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Faq $faq)
    {
        return $this->repository->destroy($faq->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/faq/{id}/{status}",
     *   tags={"FAQs"},
     *   summary="Update FAQ status",
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
     * @OA\Post(
     *   path="/api/faq/deleteAll",
     *   tags={"FAQs"},
     *   summary="Bulk delete FAQs",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }
}
