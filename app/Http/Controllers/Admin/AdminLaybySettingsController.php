<?php

namespace App\Http\Controllers\Admin;

use App\Models\LaybySetting;
use Illuminate\Http\Request;

class AdminLaybySettingsController extends BaseAdminController
{
    protected string $permissionPrefix = 'layby';

    /**
     * Show layby settings page
     */
    public function index()
    {
        $this->checkPermission('settings');

        $settings = LaybySetting::all()->pluck('value', 'key')->toArray();

        return view('admin.layby.settings', compact('settings'));
    }

    /**
     * Update layby settings
     */
    public function update(Request $request)
    {
        $this->checkPermission('settings');

        $validated = $request->validate([
            'sale_products_deposit_percentage' => 'required|integer|min:10|max:100',
            'sale_products_duration_months' => 'required|integer|min:1|max:24',
            'regular_products_deposit_percentage' => 'required|integer|min:10|max:100',
            'regular_products_duration_months' => 'required|string', // Comma-separated
            'require_id_upload' => 'required|boolean',
        ]);

        foreach ($validated as $key => $value) {
            LaybySetting::set($key, $value);
        }

        LaybySetting::clearCache();

        return redirect()->back()->with('success', 'Layby settings updated successfully');
    }
}

