<?php

namespace App\Http\Controllers\Admin;

use App\Models\ThemeOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminThemeOptionsController extends BaseAdminController
{
    protected string $permissionPrefix = 'theme-options';

    public function index()
    {
        $this->checkPermission('index');

        $themeOption = ThemeOption::first();

        // If no theme options exist, create default
        if (!$themeOption) {
            $themeOption = ThemeOption::create([
                'options' => []
            ]);
        }

        return view('admin.theme-options.index', compact('themeOption'));
    }

    public function update(Request $request)
    {
        $this->checkPermission('edit');

        $request->validate([
            'options' => 'required|json'
        ]);

        DB::beginTransaction();
        try {
            $themeOption = ThemeOption::first();

            if (!$themeOption) {
                $themeOption = ThemeOption::create([
                    'options' => json_decode($request->options, true)
                ]);
            } else {
                $themeOption->update([
                    'options' => json_decode($request->options, true)
                ]);
            }

            DB::commit();

            // Clear cache
            reGenerateThemeOptions();

            return redirect()->route('admin.theme-options.index')
                ->with('success', 'Theme Options updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to update theme options: ' . $e->getMessage())
                ->withInput();
        }
    }
}

