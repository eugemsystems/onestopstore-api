<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryShipment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InventoryShipmentController extends Controller
{
    /**
     * Create a new inventory shipment from CRM order item transfer
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'order'       => 'required|max:255',
                'title'       => 'required|string|max:500',
                'quantity'    => 'nullable|integer|min:1',
                'sku'         => 'nullable|string|max:100',
                'destination' => 'nullable|string|max:255',
                'eta'         => 'nullable|date',
                'notes'       => 'nullable|string|max:1000',
                'signed_by'   => 'nullable|integer|exists:users,id',
            ]);

            $shipment = InventoryShipment::create([
                'order'       => (string) $validated['order'],
                'title'       => $validated['title'],
                'quantity'    => $validated['quantity'] ?? 1,
                'destination' => $validated['destination'] ?? null,
                'status'      => 'Not yet',
                'eta'         => $validated['eta'] ?? null,
                'signed_by'   => $validated['signed_by'] ?? null,
                'notes'       => isset($validated['sku'])
                    ? ('SKU: ' . $validated['sku'] . (($validated['notes'] ?? null) ? "\n" . $validated['notes'] : ''))
                    : ($validated['notes'] ?? null),
                'date'        => now()->toDateString(),
                // created_by is set automatically by the model boot via Auth::id()
            ]);

            Log::info('Inventory shipment created from CRM', [
                'shipment_id'  => $shipment->id,
                'order_number' => $validated['order'],
            ]);

            return response()->json([
                'success'     => true,
                'message'     => 'Inventory shipment created successfully',
                'shipment_id' => $shipment->id,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create inventory shipment from CRM', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inventory shipment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return users with the 'Staff Raines' role for CRM assignment dropdown.
     * Authenticated endpoint used by CRM when opening the ETA/Status popover.
     */
    public function staffRaines()
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $staff = \App\Models\User::role('Staff Raines')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $staff,
        ]);
    }

    /**
     * Update signed_by on an existing shipment (called by CRM when assignment changes after transfer).
     * PATCH /api/crm-inventory-shipments/{id}/signed-by
     */
    public function updateSignedBy(Request $request, int $id)
    {
        $shipment = InventoryShipment::find($id);
        if (!$shipment) {
            return response()->json(['success' => false, 'message' => 'Shipment not found'], 404);
        }

        $validated = $request->validate([
            'signed_by' => 'nullable|integer|exists:users,id',
        ]);

        $shipment->signed_by = $validated['signed_by'] ?? null;
        $shipment->save();

        Log::info('Shipment signed_by updated from CRM', [
            'shipment_id' => $shipment->id,
            'signed_by'   => $shipment->signed_by,
        ]);

        return response()->json(['success' => true, 'message' => 'signed_by updated']);
    }

    /**
     * Get inventory shipments with search functionality for mobile app
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)

    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $search = $request->input('search', '');
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);
            $status = $request->input('status'); // Optional status filter
            $destination = $request->input('destination'); // Optional destination filter

            $query = InventoryShipment::query()
                ->with(['createdBy', 'updatedBy', 'receivedBy', 'signedBy'])
                ->orderBy('created_at', 'desc');

            // Apply search filters
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    // Search by product name/title
                    $q->where('title', 'ILIKE', "%{$search}%")
                        // Search by order number
                        ->orWhere('order', 'ILIKE', "%{$search}%")
                        // Search by destination
                        ->orWhere('destination', 'ILIKE', "%{$search}%")
                        // Search by transporter
                        ->orWhere('transporter', 'ILIKE', "%{$search}%");
                });
            }

            // Apply status filter
            if (!empty($status)) {
                $query->where('status', $status);
            }

            // Apply destination filter
            if (!empty($destination)) {
                $query->where('destination', 'ILIKE', "%{$destination}%");
            }

            $shipments = $query->paginate($perPage, ['*'], 'page', $page);

            $items = $shipments->getCollection()->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'order_number' => $shipment->order,
                    'product_name' => $shipment->title,
                    'quantity' => $shipment->quantity ?? 1,
                    'destination' => $shipment->destination,
                    'status' => $shipment->status,
                    'f_status' => $shipment->f_status,
                    'transporter' => $shipment->transporter,
                    'date' => $shipment->date ? $shipment->date->format('Y-m-d') : null,
                    'eta' => $shipment->eta ? $shipment->eta->format('Y-m-d') : null,
                    'created_at' => $shipment->created_at ? $shipment->created_at->toISOString() : null,
                    'created_by' => $shipment->createdBy ? [
                        'id' => $shipment->createdBy->id,
                        'name' => $shipment->createdBy->name,
                    ] : null,
                    'received_by' => $shipment->receivedBy ? [
                        'id' => $shipment->receivedBy->id,
                        'name' => $shipment->receivedBy->name,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'current_page' => $shipments->currentPage(),
                    'per_page' => $shipments->perPage(),
                    'total' => $shipments->total(),
                    'last_page' => $shipments->lastPage(),
                    'from' => $shipments->firstItem(),
                    'to' => $shipments->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inventory shipments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get QR code data and image for a specific shipment
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQRCode($id)
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $shipment = InventoryShipment::with(['createdBy', 'updatedBy', 'receivedBy', 'signedBy'])
                ->find($id);

            if (!$shipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipment not found',
                ], 404);
            }

            // Generate QR code data (EXACT SAME FORMAT AS STICKER/WAYBILL)
            $qrData = json_encode([
                'shipment_id' => $shipment->id,
                'order_number' => $shipment->order ?? 'N/A',
                'product_name' => $shipment->title,
                'quantity' => $shipment->quantity,
                'destination' => $shipment->destination,
                'type' => 'inventory_shipment',
                'timestamp' => now()->timestamp,
            ], JSON_UNESCAPED_SLASHES);

            // Generate QR code as PNG and convert to base64
            $qrCodePng = QrCode::format('png')
                ->size(500) // Larger size for mobile display
                ->margin(1)
                ->errorCorrection('H') // High error correction
                ->generate($qrData);

            $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodePng);

            return response()->json([
                'success' => true,
                'data' => [
                    'shipment' => [
                        'id' => $shipment->id,
                        'order_number' => $shipment->order,
                        'product_name' => $shipment->title,
                        'quantity' => $shipment->quantity ?? 1,
                        'destination' => $shipment->destination,
                        'status' => $shipment->status,
                        'f_status' => $shipment->f_status,
                        'transporter' => $shipment->transporter,
                        'date' => $shipment->date ? $shipment->date->format('Y-m-d') : null,
                        'eta' => $shipment->eta ? $shipment->eta->format('Y-m-d') : null,
                        'created_at' => $shipment->created_at ? $shipment->created_at->toISOString() : null,
                        'created_by' => $shipment->createdBy ? [
                            'id' => $shipment->createdBy->id,
                            'name' => $shipment->createdBy->name,
                        ] : null,
                        'received_by' => $shipment->receivedBy ? [
                            'id' => $shipment->receivedBy->id,
                            'name' => $shipment->receivedBy->name,
                        ] : null,
                    ],
                    'qr_code' => [
                        'data' => $qrData,
                        'image_base64' => $qrCodeBase64,
                        'format' => 'png',
                        'size' => 500,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating QR code for shipment', [
                'shipment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single shipment details
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $shipment = InventoryShipment::with(['createdBy', 'updatedBy', 'receivedBy', 'signedBy', 'history.user'])
                ->find($id);

            if (!$shipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shipment not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $shipment->id,
                    'order_number' => $shipment->order,
                    'product_name' => $shipment->title,
                    'quantity' => $shipment->quantity ?? 1,
                    'destination' => $shipment->destination,
                    'status' => $shipment->status,
                    'f_status' => $shipment->f_status,
                    'transporter' => $shipment->transporter,
                    'date' => $shipment->date ? $shipment->date->format('Y-m-d') : null,
                    'eta' => $shipment->eta ? $shipment->eta->format('Y-m-d') : null,
                    'srs' => $shipment->srs,
                    'notes' => $shipment->notes,
                    'created_at' => $shipment->created_at ? $shipment->created_at->toISOString() : null,
                    'updated_at' => $shipment->updated_at ? $shipment->updated_at->toISOString() : null,
                    'created_by' => $shipment->createdBy ? [
                        'id' => $shipment->createdBy->id,
                        'name' => $shipment->createdBy->name,
                        'email' => $shipment->createdBy->email,
                    ] : null,
                    'updated_by' => $shipment->updatedBy ? [
                        'id' => $shipment->updatedBy->id,
                        'name' => $shipment->updatedBy->name,
                    ] : null,
                    'received_by' => $shipment->receivedBy ? [
                        'id' => $shipment->receivedBy->id,
                        'name' => $shipment->receivedBy->name,
                    ] : null,
                    'signed_by' => $shipment->signedBy ? [
                        'id' => $shipment->signedBy->id,
                        'name' => $shipment->signedBy->name,
                    ] : null,
                    'history' => $shipment->history ? $shipment->history->map(function ($h) {
                        return [
                            'id' => $h->id,
                            'action' => $h->action,
                            'action_text' => $h->action_text ?? $h->action,
                            'user' => $h->user ? [
                                'id' => $h->user->id,
                                'name' => $h->user->name,
                            ] : null,
                            'created_at' => $h->created_at ? $h->created_at->toISOString() : null,
                        ];
                    }) : [],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching shipment details', [
                'shipment_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch shipment details: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available filters/options for mobile app
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function filters()
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Get unique values for filters
            $statuses = InventoryShipment::distinct()
                ->pluck('status')
                ->filter()
                ->values();

            $destinations = InventoryShipment::distinct()
                ->pluck('destination')
                ->filter()
                ->values();

            $transporters = InventoryShipment::distinct()
                ->pluck('transporter')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'statuses' => $statuses,
                    'destinations' => $destinations,
                    'transporters' => $transporters,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching inventory shipment filters', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch filters: ' . $e->getMessage(),
            ], 500);
        }
    }
}
