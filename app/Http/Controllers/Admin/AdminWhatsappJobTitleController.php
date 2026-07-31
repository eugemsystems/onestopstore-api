<?php

namespace App\Http\Controllers\Admin;

use App\Models\WhatsappJobTitle;
use Illuminate\Http\Request;

class AdminWhatsappJobTitleController extends BaseAdminController
{
    protected string $permissionPrefix = 'user'; // reuse user permission

    public function index(Request $request)
    {
        $jobTitles = WhatsappJobTitle::orderBy('sort_order')->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $jobTitles]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:whatsapp_job_titles,name',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $jobTitle = WhatsappJobTitle::create([
            'name'       => trim($request->name),
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return response()->json(['success' => true, 'message' => 'Job title created.', 'data' => $jobTitle], 201);
    }

    public function update(Request $request, $id)
    {
        $jobTitle = WhatsappJobTitle::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:100|unique:whatsapp_job_titles,name,' . $id,
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $jobTitle->update([
            'name'       => trim($request->name),
            'sort_order' => $request->sort_order ?? $jobTitle->sort_order,
        ]);

        return response()->json(['success' => true, 'message' => 'Job title updated.', 'data' => $jobTitle]);
    }

    public function destroy($id)
    {
        $jobTitle = WhatsappJobTitle::findOrFail($id);
        $jobTitle->delete();
        return response()->json(['success' => true, 'message' => 'Job title deleted.']);
    }
}
