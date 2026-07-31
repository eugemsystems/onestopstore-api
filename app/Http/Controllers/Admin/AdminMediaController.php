<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminMediaController extends BaseAdminController
{
    protected string $permissionPrefix = 'media';

    public function index(Request $request)
    {
        $this->checkPermission('index');

        $query = Attachment::query();

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhere('mime_type', 'like', "%{$search}%");
            });
        }

        // Filter by folder (model_type)
        if ($request->has('folder') && $request->folder) {
            $query->where('model_type', $request->folder);
        }

        // Filter by mime type
        if ($request->has('type') && $request->type) {
            $mimeTypes = [
                'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
                'video' => ['video/mp4', 'video/mpeg', 'video/quicktime'],
                'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'archive' => ['application/zip', 'application/x-rar-compressed', 'application/x-tar']
            ];

            if (isset($mimeTypes[$request->type])) {
                $query->whereIn('mime_type', $mimeTypes[$request->type]);
            }
        }

        // Order
        $query->orderBy($request->get('sort', 'created_at'), $request->get('order', 'desc'));

        $media = $query->paginate($request->get('per_page', 24));

        // Get folder statistics
        $folders = DB::table('attachments')
            ->select('model_type', DB::raw('count(*) as count'))
            ->whereNotNull('model_type')
            ->groupBy('model_type')
            ->get();

        return view('admin.media.index', compact('media', 'folders'));
    }

    public function create()
    {
        $this->checkPermission('create');

        return view('admin.media.upload');
    }

    public function store(Request $request)
    {
        $this->checkPermission('create');

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:10240', // 10MB max
            'folder' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $uploadedFiles = [];

            foreach ($request->file('files') as $file) {
                // Upload to laravel-media service
                $attachment = $this->uploadToMediaService($file, $request->folder);
                $uploadedFiles[] = $attachment;
            }

            DB::commit();

            return redirect()->route('admin.media.index')
                ->with('success', count($uploadedFiles) . ' file(s) uploaded successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to upload files: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $this->checkPermission('show');

        $media = Attachment::findOrFail($id);

        return view('admin.media.show', compact('media'));
    }

    public function edit($id)
    {
        $this->checkPermission('edit');

        $media = Attachment::findOrFail($id);

        return view('admin.media.edit', compact('media'));
    }

    public function update(Request $request, $id)
    {
        $this->checkPermission('edit');

        $request->validate([
            'name' => 'required|string|max:255',
            'model_type' => 'nullable|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $media = Attachment::findOrFail($id);

            $media->update([
                'name' => $request->name,
                'model_type' => $request->model_type
            ]);

            DB::commit();

            return redirect()->route('admin.media.index')
                ->with('success', 'Media updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to update media: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $this->checkPermission('delete');

        DB::beginTransaction();
        try {
            $media = Attachment::findOrFail($id);

            // Delete from laravel-media service if uuid exists
            if ($media->uuid) {
                try {
                    $mediaServiceUrl = config('app.image_api_url', env('IMAGE_API_URL', 'http://localhost:8002/api'));
                    Http::delete($mediaServiceUrl . '/attachments/' . $media->uuid);
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete from media service: ' . $e->getMessage());
                }
            }

            $media->delete();

            DB::commit();

            return redirect()->route('admin.media.index')
                ->with('success', 'Media deleted successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to delete media: ' . $e->getMessage());
        }
    }

    public function bulkDelete(Request $request)
    {
        $this->checkPermission('delete');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:attachments,id'
        ]);

        DB::beginTransaction();
        try {
            $media = Attachment::whereIn('id', $request->ids)->get();

            foreach ($media as $item) {
                // Delete from laravel-media service
                if ($item->uuid) {
                    try {
                        $mediaServiceUrl = config('app.image_api_url', env('IMAGE_API_URL', 'http://localhost:8002/api'));
                        Http::delete($mediaServiceUrl . '/attachments/' . $item->uuid);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete from media service: ' . $e->getMessage());
                    }
                }

                $item->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($request->ids) . ' file(s) deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file to laravel-media service
     */
    private function uploadToMediaService($file, $folder = null)
    {
        // Use IMAGE_API_URL from .env (same as products and other uploads)
        $mediaServiceUrl = config('app.image_api_url', env('IMAGE_API_URL', 'http://localhost:8002/api'));

        $response = Http::attach(
            'image', // Laravel-media expects 'image' field name
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->post($mediaServiceUrl . '/attachments/from-api', [
            'model_type' => $folder ?? 'Media',
            'model_id' => Str::uuid()
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to upload to media service: ' . $response->body());
        }

        $responseData = $response->json();

        // Laravel-media returns: { message: "...", files: [...] }
        $uploadedFiles = $responseData['files'] ?? [];

        if (empty($uploadedFiles)) {
            throw new \Exception('No files returned from media service');
        }

        $uploadedFile = $uploadedFiles[0]; // Get first file

        // laravel-media shares the same raines_api DB, so it already inserted this record.
        // Use updateOrCreate to avoid duplicate-key on uuid.
        $attachment = Attachment::updateOrCreate(
            ['uuid' => $uploadedFile['uuid']],
            [
                'name' => $uploadedFile['name'] ?? $file->getClientOriginalName(),
                'file_name' => $uploadedFile['file_name'],
                'image_url' => $uploadedFile['image_url'],
                'original_url' => $uploadedFile['image_url'],
                'mime_type' => $uploadedFile['mime_type'],
                'size' => $uploadedFile['size'],
                'model_type' => $folder ?? 'Media',
                'model_id' => $uploadedFile['model_id'],
                'disk' => 'public',
                'created_by_id' => auth()->id(),
            ]
        );

        return $attachment;
    }
}

