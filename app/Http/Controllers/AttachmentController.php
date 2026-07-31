<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CreateAttachmentRequest;
use App\Repositories\Eloquents\AttachmentRepository;

class AttachmentController extends Controller
{
    public $repository;

    public function __construct(AttachmentRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of attachments (from the Media app).
     */
    public function index(Request $request)
    {
        return response()->json($this->repository->fetchAll($request));
    }

    /**
     * Store a new attachment.
     */
    public function store(CreateAttachmentRequest $request)
    {
        // After normalization, we’ll have either:
        //  - attachment (single) OR attachments (array)
        $files = [];

        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
        } elseif ($request->hasFile('attachment')) {
            $files = [$request->file('attachment')];
        }

        $attachment = $this->repository->store($request);
        return response()->json($attachment, 201);
    }


    /**
     * Show a single attachment by local ID -> fetch from media by uuid.
     */
    public function show($uuid)
    {
        $attachment = $this->repository->show($uuid);
        return response()->json($attachment);
    }

    /**
     * Update a single attachment by local ID -> update media by uuid.
     */
    public function update(Request $request, $uuid)
    {
        $updated = $this->repository->update($request->all(), $uuid);
        return response()->json($updated);
    }

    /**
     * Destroy a single attachment by local ID -> delete media by uuid.
     */
    public function destroy($uuid)
    {
        $result = $this->repository->destroy($uuid);
        return response()->json($result);
    }

    /**
     * Bulk delete by local IDs.
     */
    public function deleteAll(Request $request)
    {
        $result = $this->repository->deleteAll($request->ids);
        return response()->json($result);
    }
}
