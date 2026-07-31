<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderApologySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrderApologySettingsController extends Controller
{
    public function index(): View
    {
        $settings = OrderApologySetting::current();
        return view('admin.late-orders.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cooldown_days'     => 'required|integer|min:1|max:365',
            'auto_send_enabled' => 'required|boolean',
            'auto_send_time'    => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ]);

        $settings = OrderApologySetting::current();
        $settings->update($validated);

        return back()->with('success', 'Late order apology settings saved.');
    }
}
