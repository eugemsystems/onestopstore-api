<?php

namespace App\Http\Controllers\Admin;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminAttributeController extends BaseAdminController
{
    protected string $permissionPrefix = 'attribute';

    /**
     * Display a listing of attributes
     */
    public function index(Request $request)
    {
        $this->checkPermission('index');

        $query = Attribute::with(['attribute_values'])
            ->withCount('attribute_values');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('slug', 'ILIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Style filter
        if ($request->filled('style')) {
            $query->where('style', $request->style);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $attributes = $query->paginate($request->get('per_page', 15));

        return view('admin.attributes.index', compact('attributes'));
    }

    /**
     * Show the form for creating a new attribute
     */
    public function create()
    {
        $this->checkPermission('create');
        return view('admin.attributes.create');
    }

    /**
     * Store a newly created attribute in storage
     */
    public function store(Request $request)
    {
        $this->checkPermission('create');

        $request->validate([
            'name' => 'required|string|max:255|unique:attributes,name',
            'style' => 'nullable|in:dropdown,radio,swatch',
            'status' => 'required|boolean',
            'attribute_values' => 'nullable|array',
            'attribute_values.*.value' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $attribute = Attribute::create([
                'name' => $request->name,
                'style' => $request->style ?? 'dropdown',
                'status' => $request->status,
                'created_by_id' => Auth::id(),
            ]);

            // Create attribute values if provided
            if ($request->has('attribute_values')) {
                foreach ($request->attribute_values as $valueData) {
                    if (!empty($valueData['value'])) {
                        AttributeValue::create([
                            'attribute_id' => $attribute->id,
                            'value' => $valueData['value'],
                            'created_by_id' => Auth::id(),
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.attributes.index')
                ->with('success', 'Attribute created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating attribute: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error creating attribute: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified attribute
     */
    public function show($id)
    {
        $this->checkPermission('index');

        $attribute = Attribute::with(['attribute_values'])
            ->withCount('products')
            ->findOrFail($id);

        return view('admin.attributes.show', compact('attribute'));
    }

    /**
     * Show the form for editing the specified attribute
     */
    public function edit($id)
    {
        $this->checkPermission('edit');

        $attribute = Attribute::with('attribute_values')->findOrFail($id);

        return view('admin.attributes.edit', compact('attribute'));
    }

    /**
     * Update the specified attribute in storage
     */
    public function update(Request $request, $id)
    {
        $this->checkPermission('edit');

        $attribute = Attribute::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:attributes,name,' . $id,
            'style' => 'nullable|in:dropdown,radio,swatch',
            'status' => 'required|boolean',
            'attribute_values' => 'nullable|array',
            'attribute_values.*.id' => 'nullable|exists:attribute_values,id',
            'attribute_values.*.value' => 'nullable|string|max:255',
            'attribute_values.*.delete' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();

            $attribute->update([
                'name' => $request->name,
                'style' => $request->style ?? 'dropdown',
                'status' => $request->status,
            ]);

            // Handle attribute values
            if ($request->has('attribute_values')) {
                $existingValueIds = [];

                foreach ($request->attribute_values as $valueData) {
                    // Skip if marked for deletion
                    if (isset($valueData['delete']) && $valueData['delete']) {
                        if (isset($valueData['id'])) {
                            AttributeValue::find($valueData['id'])?->delete();
                        }
                        continue;
                    }

                    if (empty($valueData['value'])) {
                        continue;
                    }

                    // Update existing or create new
                    if (isset($valueData['id'])) {
                        $attributeValue = AttributeValue::find($valueData['id']);
                        if ($attributeValue) {
                            $attributeValue->update(['value' => $valueData['value']]);
                            $existingValueIds[] = $attributeValue->id;
                        }
                    } else {
                        $newValue = AttributeValue::create([
                            'attribute_id' => $attribute->id,
                            'value' => $valueData['value'],
                            'created_by_id' => Auth::id(),
                        ]);
                        $existingValueIds[] = $newValue->id;
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.attributes.index')
                ->with('success', 'Attribute updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating attribute: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error updating attribute: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified attribute from storage
     */
    public function destroy($id)
    {
        $this->checkPermission('destroy');

        try {
            $attribute = Attribute::findOrFail($id);

            // Check if attribute is being used by products
            if ($attribute->products()->count() > 0) {
                return back()->with('error', 'Cannot delete attribute that is being used by products.');
            }

            // Delete associated attribute values
            $attribute->attribute_values()->delete();

            // Delete the attribute
            $attribute->delete();

            return redirect()->route('admin.attributes.index')
                ->with('success', 'Attribute deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting attribute: ' . $e->getMessage());
            return back()->with('error', 'Error deleting attribute: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete attributes
     */
    public function bulkDelete(Request $request)
    {
        $this->checkPermission('destroy');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|exists:attributes,id',
        ]);

        try {
            $attributes = Attribute::whereIn('id', $request->ids)->get();

            $deleted = 0;
            $skipped = 0;

            foreach ($attributes as $attribute) {
                // Check if attribute is being used
                if ($attribute->products()->count() > 0) {
                    $skipped++;
                    continue;
                }

                $attribute->attribute_values()->delete();
                $attribute->delete();
                $deleted++;
            }

            $message = "Deleted {$deleted} attribute(s) successfully.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} attribute(s) in use by products.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk deleting attributes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting attributes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle attribute status
     */
    public function toggleStatus($id)
    {
        $this->checkPermission('edit');

        try {
            $attribute = Attribute::findOrFail($id);
            $attribute->status = !$attribute->status;
            $attribute->save();

            return response()->json([
                'success' => true,
                'status' => $attribute->status,
                'message' => 'Status updated successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
