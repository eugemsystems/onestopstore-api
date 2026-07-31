<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Requests\CreateTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Repositories\Eloquents\TagRepository;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Tags", description="Product tags")
 */
class TagController extends Controller
{
    public $repository;

    public function __construct(TagRepository $repository)
    {
        $this->authorizeResource(Tag::class,'tag', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *   path="/api/tag",
     *   tags={"Tags"},
     *   summary="List tags",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="paginate", in="query", required=false, @OA\Schema(type="integer", minimum=1)),
     *   @OA\Parameter(name="ids", in="query", required=false, description="Comma-separated IDs", @OA\Schema(type="string")),
     *   @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"post","product"})),
     *   @OA\Parameter(name="field", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"asc","desc"})),
     *   @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="integer", enum={0,1})),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function index(Request $request)
    {
        $tags = $this->filter($this->repository, $request);
        return $tags->latest('created_at')->paginate($request->paginate ?? $tags->count());
    }

    public function create()
    {
        // not used in API
    }

    /**
     * @OA\Post(
     *   path="/api/tag",
     *   tags={"Tags"},
     *   summary="Create tag",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(type="object",
     *         required={"name","type","status"},
     *         @OA\Property(property="name", type="string", example="New Tag"),
     *         @OA\Property(property="type", type="string", enum={"post","product"}, example="product"),
     *         @OA\Property(property="status", type="integer", enum={0,1}, example=1)
     *       ),
     *       example={"name":"New Tag","type":"product","status":1}
     *     )
     *   ),
     *   @OA\Response(response=201, description="Created")
     * )
     */
    public function store(CreateTagRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *   path="/api/tag/{tag}",
     *   tags={"Tags"},
     *   summary="Get tag",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="tag", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="OK")
     * )
     */
    public function show(Tag $tag)
    {
        return $this->repository->show($tag->id);
    }

    public function edit($id)
    {
        // not used in API
    }

    /**
     * @OA\Put(
     *   path="/api/tag/{tag}",
     *   tags={"Tags"},
     *   summary="Update tag",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="tag", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object"))),
     *   @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        return $this->repository->update($request->all(), $tag->getId($request));
    }

    /**
     * @OA\Delete(
     *   path="/api/tag/{tag}",
     *   tags={"Tags"},
     *   summary="Delete tag",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="tag", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=204, description="Deleted")
     * )
     */
    public function destroy(Request $request, Tag $tag)
    {
        return $this->repository->destroy($tag->getId($request));
    }

    /**
     * @OA\Put(
     *   path="/api/tag/{id}/{status}",
     *   tags={"Tags"},
     *   summary="Update tag status",
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
     *   path="/api/tag/deleteAll",
     *   tags={"Tags"},
     *   summary="Bulk delete tags",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/json", @OA\Schema(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer"))))),
     *   @OA\Response(response=200, description="Deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function getTagsExportUrl(Request $request)
    {
        return $this->repository->getTagsExportUrl($request);
    }

    /**
     * @OA\Post(
     *   path="/api/tag/csv/import",
     *   tags={"Tags"},
     *   summary="Import tags (CSV)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Import started")
     * )
     */
    public function import()
    {
        return $this->repository->import();
    }

    /**
     * @OA\Post(
     *   path="/api/tag/csv/export",
     *   tags={"Tags"},
     *   summary="Export tags (CSV)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Export URL")
     * )
     */
    public function export()
    {
        return $this->repository->export();
    }

    public function filter($tags, $request)
    {
        if ($request->ids) {
            $ids = explode(',',$request->ids);
            $tags = $tags->findWhereIn('id',$ids);
        }

        if ($request->type) {
            $tags = $this->repository->whereType($request->type);
        }

        if ($request->field && $request->sort) {
            $tags = $tags->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $tags = $tags->where('status',$request->status);
        }

        return $tags;
    }
}
