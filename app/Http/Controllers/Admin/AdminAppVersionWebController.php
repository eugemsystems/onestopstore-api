<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\Request;

class AdminAppVersionWebController extends Controller
{
    public function index()
    {
        $versions = AppVersion::whereIn('platform', ['android', 'ios'])
            ->get()
            ->keyBy('platform');

        return view('admin.app-version.index', compact('versions'));
    }

    public function update(Request $request, string $platform)
    {
        if (!in_array($platform, ['android', 'ios'], true)) {
            abort(404);
        }

        $data = $request->validate([
            'latest_version'  => 'required|string|max:20|regex:/^\d+\.\d+\.\d+$/',
            'latest_build'    => 'required|integer|min:1',
            'minimum_version' => 'required|string|max:20|regex:/^\d+\.\d+\.\d+$/',
            'force_update'    => 'nullable|in:0,1',
            'release_notes'   => 'nullable|string',
            'store_url'       => 'nullable|url|max:500',
            'released_at'     => 'nullable|date',
        ]);

        $data['force_update'] = $request->boolean('force_update');

        AppVersion::updateOrCreate(['platform' => $platform], array_merge($data, ['platform' => $platform]));

        return redirect()->route('admin.app-version.index')
            ->with('success', ucfirst($platform) . ' version updated successfully.');
    }
}
