<?php

namespace App\Http\Controllers\Admin;

use App\Models\InventoryShipment;
use App\Models\Order;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminInventoryShipmentController extends BaseAdminController
{
    protected string $permissionPrefix = 'inventory-shipment';

    /**
     * Display a listing of inventory shipments
     */
    public function index(Request $request)
    {
        $this->checkPermission('index');

        $query = InventoryShipment::with(['createdBy', 'updatedBy', 'receivedBy', 'signedBy']);

        // Search functionality - case-insensitive, partial word matching
        if ($request->filled('search')) {
            $search = $request->search;
            // Split search into individual words and search for each
            $searchWords = explode(' ', $search);

            $query->where(function($q) use ($searchWords) {
                foreach ($searchWords as $word) {
                    if (trim($word) !== '') {
                        $q->where(function($subQuery) use ($word) {
                            $subQuery->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($word) . '%'])
                                   ->orWhereRaw('LOWER(transporter) LIKE ?', ['%' . strtolower($word) . '%'])
                                   ->orWhereRaw('LOWER(destination) LIKE ?', ['%' . strtolower($word) . '%'])
                                   ->orWhereRaw('LOWER(f_status) LIKE ?', ['%' . strtolower($word) . '%'])
                                   ->orWhereRaw('LOWER(notes) LIKE ?', ['%' . strtolower($word) . '%'])
                                   ->orWhereRaw('CAST("order" AS TEXT) LIKE ?', ['%' . $word . '%']);
                        });
                    }
                }
            });
        }

        // Apply filters
        if ($request->filled('destination')) {
            $query->byDestination($request->destination);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('transporter')) {
            $query->byTransporter($request->transporter);
        }

        if ($request->filled('f_status')) {
            $query->byFStatus($request->f_status);
        }

        if ($request->filled('signed_by')) {
            $query->where('signed_by', $request->signed_by);
        }

        if ($request->filled('received_by')) {
            $query->byReceivedBy($request->received_by);
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Order by sort parameter or default to latest data first
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'order') {
            $query->orderBy('order', $sortOrder)->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', $sortOrder)->orderBy('order', 'asc');
        }

        $shipments = $query->paginate(20)->withQueryString();

        // OPTIMIZED: Get all staff and admin users for filters - eager load roles to prevent N+1
        $staffUsers = User::with('roles')
            ->role(['admin', 'Staff Raines'])
            ->orderBy('name')
            ->get();

        // OPTIMIZED: Get limited users for Signed By dropdown - eager load roles to prevent N+1
        $signedByUsers = User::with('roles')
            ->role(['admin', 'Staff Raines'])
            ->whereIn('id', [6979, 6977, 6976, 6975])
            ->orderBy('name')
            ->get();

        // Get unique transporters for filter
        $transporters = InventoryShipment::whereNotNull('transporter')
            ->distinct()
            ->pluck('transporter')
            ->sort();

        // Fixed Destination Status values (matching dropdown options)
        $fStatuses = collect(['Received', 'Not yet']);

        // Statistics
        $stats = [
            'total' => InventoryShipment::count(),
            'received' => InventoryShipment::where('status', 'Received')->count(),
            'not_yet' => InventoryShipment::where('status', 'Not yet')->count(),
            'total_quantity' => InventoryShipment::sum('quantity'),
        ];

        return view('admin.inventory-shipments.index', compact(
            'shipments',
            'staffUsers',
            'signedByUsers',
            'transporters',
            'fStatuses',
            'stats'
        ));
    }

    /**
     * Show the form for creating a new shipment
     */
    public function create()
    {
        $this->checkPermission('create');

        // Get all staff for Received By
        $staffUsers = User::role(['admin', 'Staff Raines'])
            ->orderBy('name')
            ->get();

        // Only show specific users for Signed By dropdown (new record, no existing users to include)
        $signedByUsers = User::role(['admin', 'Staff Raines'])
            ->whereIn('id', [6979, 6977, 6976, 6975])
            ->orderBy('name')
            ->get();

        return view('admin.inventory-shipments.create', compact('staffUsers', 'signedByUsers'));
    }

    /**
     * Store a newly created shipment
     */
    public function store(Request $request)
    {
        $this->checkPermission('create');

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'destination' => 'required|in:Harare,Bulawayo,Mutare,Zambia',
            'status' => 'required|in:Received,Not yet',
            'order' => 'nullable|string|max:100',  // Changed to string for order number
            'transporter' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'eta' => 'nullable|date',
            'f_status' => 'nullable|string|max:255',
            'signed_by' => 'nullable|exists:users,id',
            'received_by' => 'nullable|exists:users,id',
            'srs' => 'nullable|in:Mike,Tinashe,Other',  // New field
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create shipment - created_by is automatically set by the model's boot method
        $shipment = InventoryShipment::create($validator->validated());

        // Audit log
        try {
            ActivityLogger::make()->useLog('inventory')->event('created')->on($shipment)
                ->log("Shipment #{$shipment->id} '{$shipment->title}' created (Dest: {$shipment->destination}, Qty: {$shipment->quantity})");
        } catch (\Throwable) {}

        return redirect()->route('admin.inventory-shipments.index')
            ->with('success', 'Shipment created successfully!');
    }

    /**
     * Display the specified shipment
     */
    public function show($id)
    {
        $this->checkPermission('show');

        $shipment = InventoryShipment::with([
            'createdBy',
            'updatedBy',
            'receivedBy',
            'signedBy',
            'history.user'
        ])->findOrFail($id);

        return view('admin.inventory-shipments.show', compact('shipment'));
    }

    /**
     * Show the form for editing the specified shipment
     */
    public function edit($id)
    {
        $this->checkPermission('edit');

        $shipment = InventoryShipment::findOrFail($id);

        // OPTIMIZED: Get all staff for Received By - eager load roles to prevent N+1
        $staffUsers = User::with('roles')
            ->role(['admin', 'Staff Raines'])
            ->orderBy('name')
            ->get();

        // OPTIMIZED: Only show 4 specific users for Signed By - eager load roles
        $signedByUsers = User::with('roles')
            ->role(['admin', 'Staff Raines'])
            ->whereIn('id', [6979, 6977, 6976, 6975])
            ->orderBy('name')
            ->get();

        return view('admin.inventory-shipments.edit', compact('shipment', 'staffUsers', 'signedByUsers'));
    }

    /**
     * Update the specified shipment
     */
    public function update(Request $request, $id)
    {
        $this->checkPermission('edit');

        $shipment = InventoryShipment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'destination' => 'required|in:Harare,Bulawayo,Mutare,Zambia',
            'status' => 'required|in:Received,Not yet',
            'order' => 'nullable|string|max:100',  // Changed to string for order number
            'transporter' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'eta' => 'nullable|date',
            'f_status' => 'nullable|string|max:255',
            'signed_by' => 'nullable|exists:users,id',
            'received_by' => 'nullable|exists:users,id',
            'srs' => 'nullable|in:Mike,Tinashe,Other',  // New field
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $old = $shipment->only(array_keys($validator->validated()));
        $shipment->update($validator->validated());

        // Audit log
        try {
            $dirty = array_diff_assoc($validator->validated(), $old);
            if (!empty($dirty)) {
                ActivityLogger::make()->useLog('inventory')->event('updated')->on($shipment)
                    ->withChanges(array_intersect_key($old, $dirty), $dirty)
                    ->log("Shipment #{$shipment->id} '{$shipment->title}' updated: " . implode(', ', array_keys($dirty)));
            }
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'message' => 'Shipment updated successfully!',
            'shipment' => $shipment->load(['createdBy', 'updatedBy', 'receivedBy'])
        ]);
    }

    /**
     * Remove the specified shipment
     */
    public function destroy($id)
    {
        $this->checkPermission('destroy');

        $shipment = InventoryShipment::findOrFail($id);
        $label = "#{$shipment->id} '{$shipment->title}'";
        $shipment->delete();

        // Audit log
        try {
            ActivityLogger::make()->useLog('inventory')->event('deleted')
                ->log("Shipment {$label} deleted");
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'message' => 'Shipment deleted successfully!'
        ]);
    }

    /**
     * Bulk delete shipments
     */
    public function bulkDestroy(Request $request)
    {
        $this->checkPermission('destroy');

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:inventory_shipments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid shipment IDs provided'
            ], 422);
        }

        $count = InventoryShipment::whereIn('id', $request->ids)->delete();

        // Audit log
        try {
            ActivityLogger::make()->useLog('inventory')->event('deleted')
                ->log("{$count} shipment(s) bulk deleted (IDs: " . implode(', ', $request->ids) . ")");
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'message' => "{$count} shipment(s) deleted successfully!"
        ]);
    }

    /**
     * Bulk update shipments
     */
    public function bulkUpdate(Request $request)
    {
        $this->checkPermission('edit');

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:inventory_shipments,id',
            'updates' => 'required|array',
            'updates.destination' => 'nullable|in:Harare,Bulawayo,Mutare,Zambia',
            'updates.status' => 'nullable|in:Received,Not yet',
            'updates.transporter' => 'nullable|string|max:255',
            'updates.f_status' => 'nullable|string|max:255',
            'updates.signed_by' => 'nullable|exists:users,id',
            'updates.received_by' => 'nullable|exists:users,id',
            'updates.assigned_to' => 'nullable|in:Mike,Tinashe,Other',
            'updates.srs' => 'nullable|in:Mike,Tinashe,Other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Filter out empty values
        $updates = array_filter($request->updates, function($value) {
            return $value !== null && $value !== '';
        });

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'No updates provided'
            ], 422);
        }

        // Add updated_by to updates
        $updates['updated_by'] = Auth::id();

        // Perform bulk update
        $count = InventoryShipment::whereIn('id', $request->ids)->update($updates);

        // Audit log
        try {
            $fields = implode(', ', array_keys(array_diff_key($updates, ['updated_by' => true])));
            ActivityLogger::make()->useLog('inventory')->event('updated')
                ->log("{$count} shipment(s) bulk updated — fields: {$fields} (IDs: " . implode(', ', $request->ids) . ")");
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'message' => "{$count} shipment(s) updated successfully!"
        ]);
    }

    /**
     * Get shipment data for modal edit (AJAX)
     */
    public function getShipment($id)
    {
        $this->checkPermission('show');

        $shipment = InventoryShipment::with(['createdBy', 'updatedBy', 'receivedBy', 'signedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'shipment' => $shipment
        ]);
    }

    /**
     * Get shipment history
     */
    public function history($id)
    {
        $this->checkPermission('show');

        try {
            $shipment = InventoryShipment::with([
                'history.user'
            ])->findOrFail($id);

            // Get history with all necessary data
            $history = $shipment->history->map(function ($item) {
                // Ensure changes is always an array
                $changes = $item->changes;
                if (is_string($changes)) {
                    $changes = json_decode($changes, true);
                }
                if (!is_array($changes)) {
                    $changes = [];
                }

                // Ensure old_values is always an array or null
                $oldValues = $item->old_values;
                if (is_string($oldValues)) {
                    $oldValues = json_decode($oldValues, true);
                }

                // Ensure new_values is always an array or null
                $newValues = $item->new_values;
                if (is_string($newValues)) {
                    $newValues = json_decode($newValues, true);
                }

                return [
                    'id' => $item->id,
                    'action' => $item->action,
                    'action_text' => $item->action_text,
                    'action_badge_color' => $item->action_badge_color,
                    'changes' => $changes,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                    'user' => $item->user ? [
                        'id' => $item->user->id,
                        'name' => $item->user->name,
                        'email' => $item->user->email,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'history' => $history,
                'count' => $history->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load shipment history', [
                'shipment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate waybill with QR code for inventory shipment
     */
    public function generateWaybill($id, QRCodeService $qrCodeService)
    {
        $this->checkPermission('edit');

        $shipment = InventoryShipment::findOrFail($id);

        // Non-admins can only download once
        $isAdmin = auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->hasRole('super_admin');
        if (!$isAdmin && $shipment->waybill_downloaded_at) {
            abort(403, 'This waybill has already been downloaded. Contact an administrator for another copy.');
        }

        // QR stores only the shipment ID — Flutter app fetches full data via API
        $qrData = (string) $shipment->id;

        // Generate QR code as PNG and save to temporary file
        // DomPDF works better with file paths than base64
        $qrCodePng = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(300)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($qrData);

        // Save to temporary file
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $qrFileName = 'qr_' . $shipment->id . '_' . time() . '.png';
        $qrFilePath = $tempPath . '/' . $qrFileName;
        file_put_contents($qrFilePath, $qrCodePng);

        // Prepare data for PDF
        $data = [
            'shipment' => $shipment,
            'qrCodePath' => $qrFilePath,
            'generatedDate' => now()->format('d M Y H:i'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('admin.inventory-shipments.waybill', $data);
        $pdf->setPaper([0, 0, 141.73, 93.54], 'portrait');

        // Stream the PDF
        $pdfOutput = $pdf->stream("waybill_{$shipment->order}_{$shipment->id}.pdf");

        // Mark as downloaded for non-admins (silent DB update to avoid history log noise)
        if (!$isAdmin) {
            \DB::table('inventory_shipments')->where('id', $shipment->id)->update(['waybill_downloaded_at' => now()]);
        }

        // Clean up temporary QR code file
        register_shutdown_function(function() use ($qrFilePath) {
            if (file_exists($qrFilePath)) {
                @unlink($qrFilePath);
            }
        });

        return $pdfOutput;
    }

    /**
     * Generate QR code sticker (50mm x 33mm) for inventory shipment
     */
    public function generateSticker($id, QRCodeService $qrCodeService)
    {
        $this->checkPermission('edit');

        $shipment = InventoryShipment::findOrFail($id);

        // Non-admins can only download once
        $isAdmin = auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->hasRole('super_admin');
        if (!$isAdmin && $shipment->sticker_downloaded_at) {
            abort(403, 'This sticker has already been downloaded. Contact an administrator for another copy.');
        }

        // QR stores only the shipment ID — Flutter app fetches full data via API
        $qrData = (string) $shipment->id;

        // Generate QR code as PNG - SAME SETTINGS AS WAYBILL but smaller size
        $qrCodePng = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(250) // Slightly smaller for sticker but still scannable
            ->margin(1) // SAME margin as waybill
            ->errorCorrection('H') // High error correction
            ->generate($qrData);

        // Save to temporary file
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $qrFileName = 'qr_sticker_' . $shipment->id . '_' . time() . '.png';
        $qrFilePath = $tempPath . '/' . $qrFileName;
        file_put_contents($qrFilePath, $qrCodePng);

        // Build stickers collection — one entry per unit (quantity copies)
        $stickers = collect();
        $qty = max(1, (int) $shipment->quantity);
        for ($i = 0; $i < $qty; $i++) {
            $stickers->push([
                'shipment' => $shipment,
                'qrPath'   => $qrFilePath,
            ]);
        }

        // Generate PDF — one sticker page per unit
        $pdf = Pdf::loadView('admin.inventory-shipments.bulk-stickers', [
            'stickers' => $stickers,
        ]);
        $pdf->setPaper([0, 0, 141.73, 93.54], 'portrait');

        // Stream the PDF
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $shipment->title);
        $pdfOutput = $pdf->stream($safeName . '_sticker.pdf');

        // Mark as downloaded for non-admins (silent DB update to avoid history log noise)
        if (!$isAdmin) {
            \DB::table('inventory_shipments')->where('id', $shipment->id)->update(['sticker_downloaded_at' => now()]);
        }

        // Clean up temporary QR code file
        register_shutdown_function(function() use ($qrFilePath) {
            if (file_exists($qrFilePath)) {
                @unlink($qrFilePath);
            }
        });

        return $pdfOutput;
    }

    /**
     * Bulk generate QR code stickers for multiple shipments (A4 sheet, 3 per row)
     */
    public function bulkGenerateStickers(Request $request, QRCodeService $qrCodeService)
    {
        $this->checkPermission('edit');

        $validator = Validator::make($request->all(), [
            'ids'   => 'required|array|min:1|max:100',
            'ids.*' => 'required|integer|exists:inventory_shipments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid shipment IDs provided',
            ], 422);
        }

        $shipments = InventoryShipment::whereIn('id', $request->ids)->get();

        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $tempFiles = [];
        $stickers  = collect();

        foreach ($shipments as $shipment) {
            // QR stores only the shipment ID — Flutter app fetches full data via API
            $qrData = (string) $shipment->id;

            $qrCodePng = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(250)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($qrData);

            $qrFileName = 'qr_bulk_' . $shipment->id . '_' . time() . '.png';
            $qrFilePath = $tempPath . '/' . $qrFileName;
            file_put_contents($qrFilePath, $qrCodePng);

            $tempFiles[] = $qrFilePath;

            // Push one sticker entry per unit (quantity copies, same QR image reused)
            $qty = max(1, (int) $shipment->quantity);
            for ($i = 0; $i < $qty; $i++) {
                $stickers->push([
                    'shipment' => $shipment,
                    'qrPath'   => $qrFilePath,
                ]);
            }
        }

        $pdf = Pdf::loadView('admin.inventory-shipments.bulk-stickers', [
            'stickers' => $stickers,
        ]);
        $pdf->setPaper([0, 0, 141.73, 93.54], 'portrait');

        $pdfOutput = $pdf->stream('stickers_bulk_' . now()->format('Ymd_His') . '.pdf');

        // Clean up all temp QR files after streaming
        register_shutdown_function(function () use ($tempFiles) {
            foreach ($tempFiles as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        });

        return $pdfOutput;
    }

    /**
     * QR Code scan lookup — called when a sticker is scanned.
     * Compact QR format: "{shipment_id}-{order_number}"
     * Returns the full shipment JSON payload.
     */
    public function scanLookup($compact)
    {
        // Parse the compact ID: "1955-43045"
        $parts = explode(':', $compact, 2);
        $shipmentId = $parts[0] ?? null;

        if (!$shipmentId || !is_numeric($shipmentId)) {
            return response()->json(['error' => 'Invalid QR code format'], 400);
        }

        $shipment = InventoryShipment::find((int) $shipmentId);

        if (!$shipment) {
            return response()->json(['error' => 'Shipment not found'], 404);
        }

        return response()->json([
            'shipment_id'  => $shipment->id,
            'order_number' => $shipment->order,
            'product_name' => $shipment->title,
            'quantity'     => $shipment->quantity,
            'destination'  => $shipment->destination,
            'status'       => $shipment->status,
            'srs'          => $shipment->srs,
            'date'         => $shipment->date?->format('d M Y'),
            'eta'          => $shipment->eta?->format('d M Y'),
            'type'         => 'inventory_shipment',
        ]);
    }
}
