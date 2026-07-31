<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAppVersionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(AppVersion::all());
    }

    public function upsert(Request $request, string $platform): JsonResponse
    {
        if (!in_array($platform, ['android', 'ios'], true)) {
            return response()->json(['error' => 'Platform must be "android" or "ios".'], 400);
        }

        $data = $request->validate([
            'latest_version'  => 'required|string|max:20|regex:/^\d+\.\d+\.\d+$/',
            'latest_build'    => 'required|integer|min:1',
            'minimum_version' => 'required|string|max:20|regex:/^\d+\.\d+\.\d+$/',
            'force_update'    => 'boolean',
            'release_notes'   => 'nullable|string',
            'store_url'       => 'nullable|url|max:500',
            'released_at'     => 'nullable|date',
        ]);

        $version = AppVersion::updateOrCreate(
            ['platform' => $platform],
            array_merge($data, ['platform' => $platform])
        );

        return response()->json($version);
    }
}
