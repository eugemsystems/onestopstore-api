<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $platform = $request->query('platform');

        if ($platform !== null && !in_array($platform, ['android', 'ios'], true)) {
            return response()->json(['error' => 'Invalid platform. Must be "android" or "ios".'], 400);
        }

        $rows = AppVersion::when($platform, fn($q) => $q->where('platform', $platform))
            ->get(['platform', 'latest_version', 'latest_build', 'minimum_version', 'force_update', 'release_notes', 'store_url', 'released_at']);

        if ($rows->isEmpty()) {
            return response()->json(['error' => 'No version data available.'], 500);
        }

        $currentVersion = $request->query('current_version');

        $format = fn(AppVersion $v) => [
            'latest_version'  => $v->latest_version,
            'latest_build'    => $v->latest_build,
            'minimum_version' => $v->minimum_version,
            'force_update'    => $v->force_update || ($currentVersion !== null && version_compare($currentVersion, $v->minimum_version, '<')),
            'release_notes'   => $v->release_notes,
            'store_url'       => $v->store_url,
            'released_at'     => $v->released_at?->toIso8601String(),
        ];

        if ($platform) {
            return response()->json([$platform => $format($rows->first())])
                ->header('Cache-Control', 'public, max-age=300');
        }

        $result = [];
        foreach ($rows as $row) {
            $result[$row->platform] = $format($row);
        }

        return response()->json($result)->header('Cache-Control', 'public, max-age=300');
    }
}
