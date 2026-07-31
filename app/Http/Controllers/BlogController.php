<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Blog;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Enums\SortByEnum;
use Illuminate\Http\Request;
use App\Http\Requests\CreateBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use Illuminate\Database\Eloquent\Builder;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\BlogRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Blogs", description="Blog posts")
 */
class BlogController extends Controller
{
    public $repository;

    public function __construct(BlogRepository $repository)
    {
        $this->authorizeResource(Blog::class, 'blog', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/blog",
     *   tags={"Blogs"},
     *   summary="List blogs",
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {

            $blog = $this->filter($this->repository->scopeQuery(function ($q) {
                return $q->with(['blog_thumbnail', 'blog_meta_image', 'categories', 'tags', 'created_by'])
                    ->where('status', 1);
            }), $request);

            return $blog->latest('created_at')->paginate($request->paginate ?? 12);

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/blog",
     *   tags={"Blogs"},
     *   summary="Create blog",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateBlogRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/blog/{blog}",
     *   tags={"Blogs"},
     *   summary="Get blog",
     *   @OA\Parameter(name="blog", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Blog $blog)
    {
        return $this->repository->show($blog->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/blog/{blog}",
     *   tags={"Blogs"},
     *   summary="Update blog",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="blog", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateBlogRequest $request, Blog $blog)
    {
        return $this->repository->update($request->all(), $blog->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/blog/{blog}",
     *   tags={"Blogs"},
     *   summary="Delete blog",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="blog", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Blog $blog)
    {
        return $this->repository->destroy($blog->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/blog/{id}/{status}",
     *   tags={"Blogs"},
     *   summary="Update blog status",
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
     *   path="/api/blog/deleteAll",
     *   tags={"Blogs"},
     *   summary="Bulk delete blogs",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    /**
     * @OA\Get(
     *   path="/api/blog/slug/{slug}",
     *   tags={"Blogs"},
     *   summary="Get blogs by slug",
     *   @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function getBlogsBySlug($slug)
    {
        return $this->repository->getBlogsBySlug($slug);
    }

    public function filter($blog, $request)
    {
        if (!Helpers::isUserLogin() || (Helpers::getCurrentRoleName() == RoleEnum::CONSUMER)) {
            $blog = $blog->orderBy('is_sticky', SortByEnum::DESC);
        }

        if ($request->ids) {
            $ids = explode(',',$request->ids);
            $blog->whereIn('id',$ids);
        }

        if ($request->field && $request->sort) {
            $blog =  $blog->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $blog = $blog->where('status',$request->status);
        }

        if ($request->category) {
            $slugs = explode(',', $request->category);
            $blog->whereHas('categories', function (Builder $query) use ($slugs) {
                $query->WhereIn('slug', $slugs);
            });
        }

        if ($request->tag) {
            $slugs = explode(',', $request->tag);
            $blog->whereHas('tags', function (Builder $query) use ($slugs) {
                $query->WhereIn('slug', $slugs);
            });
        }

        return $blog;
    }
}
