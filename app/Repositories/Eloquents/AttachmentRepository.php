<?php

namespace App\Repositories\Eloquents;

use App\Models\Attachment as ApiAttachment;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AttachmentRepository
{
    protected $apiUrl;

    public function __construct()
    {
        // The base URL of your Media app (e.g. 'http://localhost:8002')
        $this->apiUrl = env('IMAGE_API_URL');
    }

    /**
     * Fetch all attachments directly from the Media app (with filters, sorting).
     * Optional: you could also fetch from your local DB if you prefer.
     */
    public function fetchAll($request)
    {
        try {
            $queryParams = [
                'name' => $request->query('name'),
                'file_name' => $request->query('file_name'),
                'collection_name' => $request->query('collection_name'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'paginate' => $request->query('paginate', 20),
                'page' => $request->query('page', 1),
            ];

            $response = Http::get($this->apiUrl . "/attachments", $queryParams);

            if ($response->successful()) {
                return $response->json(); // paginated JSON from Media app
            } else {
                throw new Exception('Failed to fetch attachments: ' . $response->body());
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Show an attachment by local ID, then fetch the real data
     * from the Media App using local->uuid
     */
    public function show($uuid)
    {
        try {
            // 1) Find local record by its own ID in the API database
            $apiAttachment = ApiAttachment::whereUuid($uuid)->first();

            if (!$apiAttachment->uuid) {
                throw new Exception("No linked uuid found for local attachment #$uuid.");
            }

            // 2) Call Media app’s /attachments/{uuid}
            $response = Http::get($this->apiUrl . "/attachments/{$apiAttachment->uuid}");

            if ($response->successful()) {
                $mediaAttachment = $response->json();

                // Combine or just return the media data
                return array_merge($mediaAttachment, [
                    // Local DB’s own ID
                    'api_id'  => $apiAttachment->id,
                    'api_uuid'=> $apiAttachment->uuid,
                ]);
            } else {
                throw new Exception('Failed to fetch attachment from media: ' . $response->body());
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Store new attachments:
     * 1) Upload file(s) to the Media App
     * 2) For each file, create a local attachment row with the same uuid
     */
    public function store_($request)
    {
        if (!$request->hasFile('attachments')) {
            throw new Exception('No files uploaded.');
        }

        $uploadedImages = [];
        $files = is_array($request->file('attachments'))
            ? $request->file('attachments')
            : [$request->file('attachments')];

        foreach ($files as $file) {
            // 1) POST to Media app with the file
            $response = Http::attach(
                'image', // Media app expects "image"
                file_get_contents($file->path()),
                $file->getClientOriginalName()
            )->post($this->apiUrl . '/attachments');

            if ($response->successful()) {
                /**
                 * The Media app response is typically:
                 * {
                 *   "message": "Files uploaded",
                 *   "files": [
                 *       {
                 *         "id": 5,   // the Media's own numeric id
                 *         "uuid": "abc123-...",
                 *         "image_url": "...",
                 *         ...
                 *       }
                 *   ]
                 * }
                 */
                $mediaResponse = $response->json();
                $filesArray = $mediaResponse['files'] ?? [];

                // Possibly a loop, but if you only expect 1 file, just do $filesArray[0]
                foreach ($filesArray as $singleFile) {
                    // 2) Save local record referencing the same uuid
                    $apiAttachment = new ApiAttachment();
                    // store the same uuid that Media app used
                    $apiAttachment->uuid       = $singleFile['uuid'];
                    $apiAttachment->name       = $singleFile['name']       ?? null;
                    $apiAttachment->file_name  = $singleFile['file_name']  ?? null;
                    $apiAttachment->image_url  = $singleFile['image_url']  ?? null;
                    $apiAttachment->mime_type  = $singleFile['mime_type']  ?? null;
                    $apiAttachment->size       = $singleFile['size']       ?? null;
                    $apiAttachment->disk       = $singleFile['disk']       ?? 'public';
                    // If you want to store the Media's numeric ID for reference, you can do so:
                    // $apiAttachment->media_id = $singleFile['id'] ?? null;

                    $apiAttachment->save();

                    $uploadedImages[] = $apiAttachment;
                }
            } else {
                throw new Exception('Image upload failed: ' . $response->body());
            }
        }

        return count($uploadedImages) > 1
            ? $uploadedImages
            : (count($uploadedImages) === 1 ? $uploadedImages[0] : []);
    }

    public function store($request)
    {
        // accept either attachments[] or attachment
        $param = null;
        if ($request->hasFile('attachments')) {
            $param = 'attachments';
        } elseif ($request->hasFile('attachment')) {
            $param = 'attachment';
        }

        if (!$param) {
            throw new Exception('No files uploaded.');
        }

        $raw = $request->file($param);
        $files = is_array($raw) ? $raw : [$raw];

        $uploadedImages = [];

        foreach ($files as $file) {
            $response = Http::attach(
                'image', // Media app expects "image"
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post($this->apiUrl . '/attachments');

            if ($response->successful()) {
                $mediaResponse = $response->json();
                $filesArray = $mediaResponse['files'] ?? [];

                foreach ($filesArray as $singleFile) {
                    try {
                        // Check if attachment already exists to avoid duplicate UUID error
                        $apiAttachment = ApiAttachment::where('uuid', $singleFile['uuid'])->first();

                        if ($apiAttachment) {
                            // Update existing attachment
                            $apiAttachment->name      = $singleFile['name']      ?? $apiAttachment->name;
                            $apiAttachment->file_name = $singleFile['file_name'] ?? $apiAttachment->file_name;
                            $apiAttachment->image_url = $singleFile['image_url'] ?? $apiAttachment->image_url;
                            $apiAttachment->mime_type = $singleFile['mime_type'] ?? $apiAttachment->mime_type;
                            $apiAttachment->size      = $singleFile['size']      ?? $apiAttachment->size;
                            $apiAttachment->disk      = $singleFile['disk']      ?? $apiAttachment->disk;
                            $apiAttachment->save();

                        } else {
                            // Create new attachment
                            $apiAttachment = new ApiAttachment();
                            $apiAttachment->uuid      = $singleFile['uuid'];
                            $apiAttachment->name      = $singleFile['name']      ?? null;
                            $apiAttachment->file_name = $singleFile['file_name'] ?? null;
                            $apiAttachment->image_url = $singleFile['image_url'] ?? null;
                            $apiAttachment->mime_type = $singleFile['mime_type'] ?? null;
                            $apiAttachment->size      = $singleFile['size']      ?? null;
                            $apiAttachment->disk      = $singleFile['disk']      ?? 'public';
                            $apiAttachment->save();
                        }

                        $uploadedImages[] = $apiAttachment;
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Handle race condition where UUID was just created by another request
                        if ($e->getCode() === '23505' || strpos($e->getMessage(), 'duplicate key') !== false) {
                            // Fetch the existing attachment
                            $apiAttachment = ApiAttachment::where('uuid', $singleFile['uuid'])->first();
                            if ($apiAttachment) {
                                Log::warning('Attachment duplicate UUID caught in race condition', [
                                    'uuid' => $singleFile['uuid'],
                                    'id' => $apiAttachment->id
                                ]);
                                $uploadedImages[] = $apiAttachment;
                            } else {
                                Log::error('Failed to create or fetch attachment after duplicate error', [
                                    'uuid' => $singleFile['uuid']
                                ]);
                            }
                        } else {
                            // Re-throw if it's a different error
                            throw $e;
                        }
                    }
                }
            } else {
                throw new Exception('Image upload failed: ' . $response->body());
            }
        }

        return count($uploadedImages) > 1
            ? $uploadedImages
            : (count($uploadedImages) === 1 ? $uploadedImages[0] : []);
    }




    /**
     * Update the record by local ID -> calls Media app with local->uuid
     */
    public function update($data, $uuid)
    {
        try {
            if (!is_string($uuid) || !Str::isUuid($uuid)) {
                throw new \Exception('Invalid attachment UUID provided.');
            }

            $apiAttachment = ApiAttachment::whereUuid($uuid)->first();
            if (!$apiAttachment || !$apiAttachment->uuid) {
                throw new \Exception("No linked uuid found for local attachment #$uuid.");
            }

            // PUT to Media app’s /attachments/{uuid}
            $updateResponse = Http::put(
                $this->apiUrl . "/attachments/{$apiAttachment->uuid}",
                $data
            );

            if ($updateResponse->successful()) {
                $updatedMedia = $updateResponse->json();

                // Sync local data with whatever changed in the Media app
                $apiAttachment->name      = $updatedMedia['name']      ?? $apiAttachment->name;
                $apiAttachment->file_name = $updatedMedia['file_name'] ?? $apiAttachment->file_name;
                $apiAttachment->image_url = $updatedMedia['image_url'] ?? $apiAttachment->image_url;
                $apiAttachment->mime_type = $updatedMedia['mime_type'] ?? $apiAttachment->mime_type;
                $apiAttachment->size      = $updatedMedia['size']      ?? $apiAttachment->size;

                $apiAttachment->save();

                return $apiAttachment;
            } else {
                throw new Exception('Failed to update media attachment: ' . $updateResponse->body());
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Delete an attachment by local ID -> calls Media app with local->uuid
     */
    public function destroy($uuid)
    {
        try {
            $apiAttachment = ApiAttachment::whereUuid($uuid)->first();

            if (!$apiAttachment->uuid) {
                throw new Exception("No linked uuid found for local attachment #$uuid.");
            }

            // DELETE from Media app by uuid
            $response = Http::delete($this->apiUrl . "/attachments/{$apiAttachment->uuid}");

            if ($response->successful()) {
                // Delete local row
                $apiAttachment->delete();
                return ['message' => 'Attachment deleted successfully.'];
            } else {
                throw new Exception('Failed to delete attachment: ' . $response->body());
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Delete multiple attachments by local IDs -> get each uuid -> do bulk delete or loop
     */
    public function deleteAll($ids)
    {
        // 1) Get local attachments
        $apiAttachments = ApiAttachment::whereIn('uuid', $ids)->get();

        // Collect the uuids
        $uuids = $apiAttachments->pluck('uuid')->filter()->all();
        if (empty($uuids)) {
            return ['message' => 'No UUIDs found for the given attachments.'];
        }

        // 2) If your Media app supports "bulk delete by uuid":
        //    e.g. DELETE /attachments?uuids[]=[uuid1]&uuids[]=[uuid2]
        //    If not, you'd loop each $uuid and DELETE individually.
        $response = Http::delete($this->apiUrl . "/attachments", [
            'uuids' => $uuids
        ]);

        if ($response->successful()) {
            // 3) Delete locally
            ApiAttachment::whereIn('id', $ids)->delete();
            return ['message' => 'Attachments deleted successfully.'];
        } else {
            throw new Exception('Failed to delete attachments: ' . $response->body());
        }
    }
}
