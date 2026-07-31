<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Requests\CreatePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\PageRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Pages", description="CMS pages")
 */
class PageController extends Controller
{
    public $repository;

    public function __construct(PageRepository $repository)
    {
        $this->authorizeResource(Page::class,'page', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/page",
     *   tags={"Pages"},
     *   summary="List pages",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        try {

            $pages = $this->filter($this->repository->with('created_by'), $request);
            return $pages->latest('created_at')->paginate($request->paginate ?? $pages->count());

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
     *   path="/api/page",
     *   tags={"Pages"},
     *   summary="Create page",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreatePageRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/page/{page}",
     *   tags={"Pages"},
     *   summary="Get page",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="page", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Page $page)
    {
        return $this->repository->show($page->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/page/{page}",
     *   tags={"Pages"},
     *   summary="Update page",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="page", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdatePageRequest $request, Page $page)
    {
        return $this->repository->update($request->all(), $page->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/page/{page}",
     *   tags={"Pages"},
     *   summary="Delete page",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="page", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Page $page)
    {
        return $this->repository->destroy($page->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/page/{id}/{status}",
     *   tags={"Pages"},
     *   summary="Update page status",
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
     *   path="/api/page/deleteAll",
     *   tags={"Pages"},
     *   summary="Bulk delete pages",
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
     *   path="/api/page/slug/{slug}",
     *   tags={"Pages"},
     *   summary="Get pages by slug",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function getPagesBySlug($slug)
    {
        try {
            $page = $this->repository->getPagesBySlug($slug);
            return response()->json($page, 200, [
                'Content-Type' => 'application/json'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Page not found',
                'error' => $e->getMessage()
            ], 404, [
                'Content-Type' => 'application/json'
            ]);
        }
    }

    public function filter($pages, $request)
    {
        if ($request->field && $request->sort) {
            $pages = $pages->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $pages = $pages->where('status',$request->status);
        }

        return $pages;
    }
}
