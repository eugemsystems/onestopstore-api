<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Repositories\Eloquents\ReviewRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Reviews", description="Product reviews")
 */
class ReviewController extends Controller
{
    public $repository;

    public function __construct(ReviewRepository $repository)
    {
        $this->authorizeResource(Review::class,'review',[
            'except' => 'edit', 'update', 'destroy'
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/review",
     *   tags={"Reviews"},
     *   summary="List reviews",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $reviews = $this->filter($this->repository->with(['product', 'store']), $request);
        return $reviews->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    /**
     * @OA\Get(
     *   path="/api/front/review",
     *   tags={"Reviews"},
     *   summary="List front reviews",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function frontIndex(Request $request)
    {
        $reviews = $this->filter($this->repository, $request);
        return $reviews->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/review",
     *   tags={"Reviews"},
     *   summary="Create review",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateReviewRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/review/{review}",
     *   tags={"Reviews"},
     *   summary="Get review",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Review $review)
    {
        // not used in current implementation
    }

    public function edit(Review $review)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/review/{review}",
     *   tags={"Reviews"},
     *   summary="Update review",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateReviewRequest $request, Review $review)
    {
        return $this->repository->update($request->all(), $review->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/review/{review}",
     *   tags={"Reviews"},
     *   summary="Delete review",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="review", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Review $review)
    {
        return $this->repository->destroy($review->getId($request));
    }

    /**
     * @OA\Post(
     *   path="/api/review/deleteAll",
     *   tags={"Reviews"},
     *   summary="Bulk delete reviews",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($reviews, $request)
    {
        if (Helpers::isUserLogin()) {
            $roleName = Helpers::getCurrentRoleName();
            if ($roleName == RoleEnum::VENDOR) {
                $reviews = $reviews->where('store_id',Helpers::getCurrentVendorStoreId());
            }
        }

        if ($request->product_id) {
            $reviews = $reviews->where('product_id',$request->product_id);
        }

        if ($request->field && $request->sort) {
            $reviews = $reviews->orderBy($request->field, $request->sort);
        }

        return $reviews;
    }
}
