<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceQuotation;
use App\Models\InvoiceQuotationItem;
use App\Models\User;
use App\Models\Product;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\ElasticsearchService;
use App\Services\CurrencyDetectionService;
use App\Mail\InvoiceQuotationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminInvoiceQuotationController extends Controller
{
    protected $elasticsearchService;

    public function __construct(ElasticsearchService $elasticsearchService)
    {
        $this->elasticsearchService = $elasticsearchService;

        // Apply permissions
        $this->middleware('permission:invoice-quotation.index')->only(['index']);
        $this->middleware('permission:invoice-quotation.create')->only(['create', 'store', 'searchUsers', 'searchProducts', 'getUserAddresses', 'autoSave', 'resume', 'discardAutoSave']);
        $this->middleware('permission:invoice-quotation.show')->only(['show', 'preview']);
        $this->middleware('permission:invoice-quotation.edit')->only(['edit', 'update', 'updateStatus', 'convertToInvoice', 'convertToOrder', 'convertType', 'sendEmail']);
        $this->middleware('permission:invoice-quotation.delete')->only(['destroy']);
        $this->middleware('permission:invoice-quotation.download-pdf')->only(['downloadPdf']);
        $this->middleware('permission:invoice-quotation.stats')->only(['stats']);
    }

    /**
     * Advanced statistics for invoices & quotations
     * All monetary values are grouped per currency_code to avoid meaningless cross-currency sums.
     */
    public function stats(Request $request)
    {
        $dateFrom = $request->filled('date_from') ? $request->date_from : now()->subYear()->toDateString();
        $dateTo   = $request->filled('date_to')   ? $request->date_to   : now()->toDateString();

        $base = InvoiceQuotation::whereBetween('issue_date', [$dateFrom, $dateTo])
            ->where('status', '!=', 'autosave');

        // ── Count-only KPIs (currency-agnostic) ──────────────────────────
        $totalDocs    = (clone $base)->count();
        $overdueCount = InvoiceQuotation::where('status', 'sent')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->count();
        $avgItemsPerDoc = $totalDocs > 0
            ? round(InvoiceQuotationItem::whereHas('invoiceQuotation', fn($q) =>
                $q->whereBetween('issue_date', [$dateFrom, $dateTo])
                  ->where('status', '!=', 'autosave')
              )->count() / $totalDocs, 1)
            : 0;

        // ── By document type (count only) ────────────────────────────────
        $byType = (clone $base)->select('document_type', DB::raw('COUNT(*) as count'))
            ->groupBy('document_type')
            ->orderByDesc('count')
            ->get();

        // ── By status (count only) ───────────────────────────────────────
        $byStatus = (clone $base)->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        // ── Per-currency monetary aggregates ─────────────────────────────
        // Each row = one currency with all its financial totals
        $byCurrency = (clone $base)
            ->select(
                'currency_code',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue"),
                DB::raw('SUM(vat_amount) as total_vat'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('AVG(total_amount) as avg_value')
            )
            ->groupBy('currency_code')
            ->orderByDesc('total_revenue')
            ->get();

        // ── By type + currency (for type chart with revenue context) ─────
        $byTypeCurrency = (clone $base)
            ->select('document_type', 'currency_code',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('document_type', 'currency_code')
            ->orderByDesc('count')
            ->get()
            ->groupBy('currency_code');

        // ── By status + currency ─────────────────────────────────────────
        $byStatusCurrency = (clone $base)
            ->select('status', 'currency_code',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('status', 'currency_code')
            ->orderByDesc('count')
            ->get()
            ->groupBy('currency_code');

        // ── Monthly trend per currency (last 12 months) ──────────────────
        $monthlyTrend = InvoiceQuotation::where('status', '!=', 'autosave')
            ->where('issue_date', '>=', now()->subMonths(12)->startOfMonth()->toDateString())
            ->select(
                'currency_code',
                DB::raw("TO_CHAR(issue_date, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('currency_code', 'month')
            ->orderBy('currency_code')
            ->orderBy('month')
            ->get()
            ->groupBy('currency_code');

        // ── Quotation conversion rate (count only) ───────────────────────
        $totalQuotations = InvoiceQuotation::where('document_type', 'quotation')
            ->where('status', '!=', 'autosave')->count();
        $convertedQuotations = InvoiceQuotation::where('document_type', 'invoice')
            ->where('status', '!=', 'autosave')
            ->whereNotNull('notes')->count();
        $conversionRate = $totalQuotations > 0
            ? round(($convertedQuotations / $totalQuotations) * 100, 1) : 0;

        // ── Per-user stats grouped by user + currency ─────────────────────
        // Revenue is only meaningful within the same currency
        $perUser = InvoiceQuotation::where('status', '!=', 'autosave')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->select(
                'created_by',
                'currency_code',
                DB::raw('COUNT(*) as doc_count'),
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue"),
                DB::raw('MAX(created_at) as last_activity'))
            ->with('creator:id,name,email')
            ->groupBy('created_by', 'currency_code')
            ->orderByDesc('doc_count')
            ->get()
            ->groupBy('created_by');

        // Per-user type breakdown (count only)
        $perUserByType = InvoiceQuotation::where('status', '!=', 'autosave')
            ->whereBetween('issue_date', [$dateFrom, $dateTo])
            ->select('created_by', 'document_type', DB::raw('COUNT(*) as count'))
            ->groupBy('created_by', 'document_type')
            ->get()
            ->groupBy('created_by');

        // ── Top products per currency ─────────────────────────────────────
        // Join back to the parent doc to get currency_code with each item
        $topProducts = InvoiceQuotationItem::select(
                'invoice_quotation_items.product_name',
                'iq.currency_code',
                DB::raw('SUM(invoice_quotation_items.quantity) as total_qty'),
                DB::raw('SUM(invoice_quotation_items.subtotal) as total_revenue'),
                DB::raw('COUNT(DISTINCT invoice_quotation_items.invoice_quotation_id) as doc_count'))
            ->join('invoices_quotations as iq', 'invoice_quotation_items.invoice_quotation_id', '=', 'iq.id')
            ->whereNull('iq.deleted_at')
            ->whereBetween('iq.issue_date', [$dateFrom, $dateTo])
            ->where('iq.status', '!=', 'autosave')
            ->groupBy('invoice_quotation_items.product_name', 'iq.currency_code')
            ->orderByDesc('total_qty')
            ->limit(20)
            ->get()
            ->groupBy('currency_code');

        return view('admin.invoices-quotations.stats', compact(
            'dateFrom', 'dateTo',
            'totalDocs', 'overdueCount', 'avgItemsPerDoc',
            'byType', 'byStatus',
            'byCurrency', 'byTypeCurrency', 'byStatusCurrency',
            'monthlyTrend',
            'conversionRate', 'totalQuotations',
            'perUser', 'perUserByType',
            'topProducts'
        ));
    }

    /**
     * Display a listing of invoices/quotations
     */
    public function index(Request $request)
    {
        $query = InvoiceQuotation::with(['user', 'creator', 'items']);

        // Filter by document type
        if ($request->filled('type')) {
            $query->where('document_type', $request->type);
        }

        // Filter by status (including autosave)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by currency
        if ($request->filled('currency')) {
            $query->where('currency_code', $request->currency);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('document_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%")
                  ->orWhere('customer_email', 'LIKE', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('issue_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('issue_date', '<=', $request->date_to);
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.invoices-quotations.index', compact('documents'));
    }

    /**
     * Show the form for creating a new document
     */
    public function create(Request $request)
    {
        $type = $request->query('type', 'invoice');
        $currencies = $this->getCurrencies();

        // Get exchange rates for currency conversion
        $exchangeRates = $this->getExchangeRates();

        // Get default currency from logged-in user's preferred_currency
        $defaultCurrency = Auth::user()->preferred_currency ?? 'USD';

        // Get shipping options from settings (same as checkout)
        $shippingOptions = $this->getShippingOptions();

        // Get collection points from settings
        $collectionPoints = $this->getCollectionPoints();

        // Get shipping rules from database (same as checkout)
        $shippingRules = $this->getShippingRules();

        return view('admin.invoices-quotations.create', compact('type', 'currencies', 'exchangeRates', 'defaultCurrency', 'shippingOptions', 'collectionPoints', 'shippingRules'));
    }

    /**
     * Store a newly created document
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'autosave_id' => 'nullable|exists:invoices_quotations,id',
            'document_type' => 'required|in:invoice,quotation,receipt,proforma,delivery_note',
            'currency_code' => 'required|string|max:3',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'valid_until' => 'nullable|date|after_or_equal:issue_date',
            'include_vat' => 'boolean',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_total' => 'nullable|numeric|min:0',
            'delivery_method' => 'nullable|string',
            'delivery_description' => 'nullable|string',
            'delivery_price' => 'nullable|numeric|min:0',
            'delivery_interval' => 'nullable|string',
            'collection_point' => 'nullable|string',
            'discount_type' => 'required|in:percentage,amount',
            'discount_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:variations,id',
            'items.*.product_name' => 'required|string',
            'items.*.sku' => 'nullable|string',
            'items.*.description' => 'nullable|string',
            'items.*.image_url' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $userId = Auth::id();

            // Check if converting from autosave
            if (!empty($validated['autosave_id'])) {
                $document = InvoiceQuotation::where('id', $validated['autosave_id'])
                    ->where('created_by', $userId)
                    ->where('status', 'autosave')
                    ->firstOrFail();

                // Generate proper document number (remove -AUTOSAVE suffix)
                $documentNumber = InvoiceQuotation::generateDocumentNumber($validated['document_type']);

                // Double check uniqueness against ALL rows (including other autosaves)
                // so we never reuse a number already held by any existing document.
                $attempts = 0;
                while (InvoiceQuotation::where('document_number', $documentNumber)
                    ->where('id', '!=', $document->id)
                    ->exists() && $attempts < 10) {

                    // Regenerate a fresh number (guaranteed highest) rather than just bumping inline
                    $documentNumber = InvoiceQuotation::generateDocumentNumber($validated['document_type']);
                    $attempts++;
                }

                if ($attempts >= 10) {
                    throw new \Exception('Unable to generate unique document number after 10 attempts');
                }

                Log::info('Converting autosave to document', [
                    'autosave_id' => $document->id,
                    'old_number' => $document->document_number,
                    'new_number' => $documentNumber,
                    'user_id' => $userId
                ]);

                $document->document_number = $documentNumber;
                $document->status = 'draft';

                // Delete existing items
                $document->items()->delete();
            } else {
                // Generate document number
                $documentNumber = InvoiceQuotation::generateDocumentNumber($validated['document_type']);

                // Double check uniqueness against ALL rows (including autosaves)
                $attempts = 0;
                while (InvoiceQuotation::where('document_number', $documentNumber)
                    ->exists() && $attempts < 10) {

                    // Regenerate a fresh number (guaranteed highest) rather than just bumping inline
                    $documentNumber = InvoiceQuotation::generateDocumentNumber($validated['document_type']);
                    $attempts++;
                }

                if ($attempts >= 10) {
                    throw new \Exception('Unable to generate unique document number after 10 attempts');
                }

                // Create new document
                $document = new InvoiceQuotation([
                    'document_number' => $documentNumber,
                    'document_type' => $validated['document_type'],
                    'created_by' => $userId,
                    'status' => 'draft',
                ]);
            }

            // Use discount value as-is (already in selected currency from frontend)
            $discountValue = $validated['discount_value'] ?? 0;

            // Update/set document fields
            $document->fill([
                'currency_code' => $validated['currency_code'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_address' => $validated['customer_address'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'valid_until' => $validated['valid_until'] ?? null,
                'include_vat' => $validated['include_vat'] ?? true,
                'vat_percentage' => $validated['vat_percentage'] ?? 15,
                'shipping_total' => $validated['shipping_total'] ?? 0,
                'delivery_method' => $validated['delivery_method'] ?? null,
                'delivery_description' => $validated['delivery_description'] ?? null,
                'delivery_price' => $validated['delivery_price'] ?? 0,
                'delivery_interval' => $validated['delivery_interval'] ?? null,
                'collection_point' => $validated['collection_point'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $discountValue,
                'notes' => $validated['notes'] ?? null,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
            ]);

            $document->save();

            // Create items - use prices as-is (already in selected currency from frontend)
            foreach ($validated['items'] as $itemData) {
                InvoiceQuotationItem::create([
                    'invoice_quotation_id' => $document->id,
                    'product_id' => $itemData['product_id'] ?? null,
                    'variation_id' => $itemData['variation_id'] ?? null,
                    'product_name' => $itemData['product_name'],
                    'sku' => $itemData['sku'] ?? null,
                    'description' => $itemData['description'] ?? null,
                    'image_url' => $itemData['image_url'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'], // Use as-is, already in correct currency
                ]);
            }

            // Calculate totals
            $document->calculateTotals()->save();

            // Delete any other autosaves for this user and document type
            InvoiceQuotation::where('created_by', $userId)
                ->where('document_type', $validated['document_type'])
                ->where('status', 'autosave')
                ->where('id', '!=', $document->id)
                ->delete();

            // Log creation history
            $document->logHistory(
                'created',
                "Created {$document->getDocumentTypeLabel()} #{$document->document_number} for {$document->customer_name}",
                null,
                null,
                null,
                [
                    'document_type' => $document->document_type,
                    'currency' => $document->currency_code,
                    'total_amount' => $document->total_amount,
                    'items_count' => $document->items->count(),
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst($validated['document_type']) . ' created successfully',
                'document' => $document->load('items'),
                'redirect' => route('admin.invoices-quotations.show', $document->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            // Log detailed error information
            Log::error('Failed to create invoice/quotation', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'document_type' => $validated['document_type'] ?? null,
                'autosave_id' => $validated['autosave_id'] ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            // Check if it's a duplicate key error
            if (str_contains($e->getMessage(), 'duplicate key') || str_contains($e->getMessage(), 'Unique violation')) {
                return response()->json([
                    'success' => false,
                    'message' => 'A document with this number already exists. Please try again.'
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error creating document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified document
     */
    public function show($id)
    {
        $document = InvoiceQuotation::with(['items', 'user', 'creator', 'histories.user'])->findOrFail($id);
        return view('admin.invoices-quotations.show', compact('document'));
    }

    /**
     * Show the form for editing the specified document
     */
    public function edit($id)
    {
        $document = InvoiceQuotation::with('items')->findOrFail($id);
        $currencies = $this->getCurrencies();
        $exchangeRates = $this->getExchangeRates();

        return view('admin.invoices-quotations.edit', compact('document', 'currencies', 'exchangeRates'));
    }

    /**
     * Update the specified document
     */
    public function update(Request $request, $id)
    {
        $document = InvoiceQuotation::findOrFail($id);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'valid_until' => 'nullable|date|after_or_equal:issue_date',
            'include_vat' => 'sometimes|boolean',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'required|in:percentage,amount',
            'discount_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'status' => 'required|in:draft,sent,paid,cancelled,expired',
            // Items validation
            'items' => 'sometimes|array',
            'items.*.id' => 'nullable|integer',
            'items.*.product_id' => 'nullable|integer',
            'items.*.variation_id' => 'nullable|integer',
            'items.*.product_name' => 'required|string',
            'items.*.sku' => 'nullable|string',
            'items.*.image_url' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Handle checkbox - if not checked, it won't be in the request
        $validated['include_vat'] = $request->has('include_vat') ? true : false;

        DB::beginTransaction();
        try {
            // Track changes before update
            $changes = [];
            $trackableFields = [
                'customer_name', 'customer_email', 'customer_phone', 'customer_address',
                'issue_date', 'due_date', 'valid_until', 'include_vat', 'vat_percentage',
                'discount_type', 'discount_value', 'notes', 'terms_conditions', 'status'
            ];

            foreach ($trackableFields as $field) {
                if (isset($validated[$field]) && $document->$field != $validated[$field]) {
                    $oldValue = $document->$field;
                    $newValue = $validated[$field];

                    // Format dates for readability
                    if (in_array($field, ['issue_date', 'due_date', 'valid_until']) && $oldValue) {
                        $oldValue = $oldValue instanceof \Carbon\Carbon ? $oldValue->format('M d, Y') : $oldValue;
                        $newValue = \Carbon\Carbon::parse($newValue)->format('M d, Y');
                    }

                    // Format booleans
                    if ($field === 'include_vat') {
                        $oldValue = $oldValue ? 'Yes' : 'No';
                        $newValue = $newValue ? 'Yes' : 'No';
                    }

                    $changes[$field] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                        'label' => ucwords(str_replace('_', ' ', $field))
                    ];
                }
            }

            // Update document (without items)
            $documentData = collect($validated)->except(['items'])->toArray();
            $document->update($documentData);

            // Handle items if provided
            if ($request->has('items') && is_array($request->items)) {
                $oldItemCount = $document->items()->count();

                // Get existing item IDs
                $existingItemIds = $document->items()->pluck('id')->toArray();
                $submittedItemIds = [];

                foreach ($request->items as $itemData) {
                    $itemId = $itemData['id'] ?? null;

                    $qty       = (float) $itemData['quantity'];
                    $unitPrice = (float) $itemData['unit_price'];
                    $subtotal  = $qty * $unitPrice;

                    $itemRecord = [
                        'invoice_quotation_id' => $document->id,
                        'product_id' => !empty($itemData['product_id']) ? $itemData['product_id'] : null,
                        'variation_id' => !empty($itemData['variation_id']) ? $itemData['variation_id'] : null,
                        'product_name' => $itemData['product_name'],
                        'sku' => $itemData['sku'] ?? null,
                        'image_url' => $itemData['image_url'] ?? null,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ];

                    if ($itemId && in_array($itemId, $existingItemIds)) {
                        // Update existing item — use query builder but include subtotal explicitly
                        // (mass updates bypass Eloquent boot hooks so subtotal must be set here)
                        InvoiceQuotationItem::where('id', $itemId)->update($itemRecord);
                        $submittedItemIds[] = $itemId;
                    } else {
                        // Create new item
                        $newItem = InvoiceQuotationItem::create($itemRecord);
                        $submittedItemIds[] = $newItem->id;
                    }
                }

                // Delete items that were removed
                $itemsToDelete = array_diff($existingItemIds, $submittedItemIds);
                if (!empty($itemsToDelete)) {
                    InvoiceQuotationItem::whereIn('id', $itemsToDelete)->delete();
                }

                $newItemCount = count($submittedItemIds);
                if ($oldItemCount !== $newItemCount) {
                    $changes['items'] = [
                        'old' => $oldItemCount . ' items',
                        'new' => $newItemCount . ' items',
                        'label' => 'Items'
                    ];
                }
            }

            // Recalculate totals
            $document->calculateTotals()->save();

            // Log each change
            foreach ($changes as $field => $change) {
                $document->logHistory(
                    'updated',
                    "Updated {$change['label']} from '{$change['old']}' to '{$change['new']}'",
                    $field,
                    $change['old'],
                    $change['new']
                );
            }

            // If no specific field changes but totals changed, log general update
            if (empty($changes)) {
                $document->logHistory(
                    'updated',
                    "Updated {$document->getDocumentTypeLabel()} #{$document->document_number}",
                    null,
                    null,
                    null,
                    ['recalculated_totals' => true]
                );
            }

            DB::commit();

            return redirect()->route('admin.invoices-quotations.show', $document->id)
                ->with('success', 'Document updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error updating document: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified document
     */
    public function destroy($id)
    {
        $document = InvoiceQuotation::findOrFail($id);
        $document->delete();

        return redirect()->route('admin.invoices-quotations.index')
            ->with('success', 'Document deleted successfully');
    }

    /**
     * If the given URL points to a WebP image, fetch it and return a base64 PNG
     * data URI so dompdf can render it without needing imagecreatefromwebp().
     * For all other image formats the original URL is returned unchanged so
     * dompdf continues to handle them exactly as it did before.
     */
    private function convertWebpImageForPdf(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Only intervene for WebP URLs – everything else stays as-is
        $path = strtolower(parse_url($url, PHP_URL_PATH) ?? '');
        if (!str_ends_with($path, '.webp')) {
            return $url;
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'method' => 'GET',
                    'user_agent' => 'Mozilla/5.0',
                    'follow_location' => 1,
                    'max_redirects' => 5,
                    'ignore_errors' => false,
                ],
                'https' => [
                    'timeout' => 15,
                    'method' => 'GET',
                    'user_agent' => 'Mozilla/5.0',
                    'follow_location' => 1,
                    'max_redirects' => 5,
                    'ignore_errors' => false,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ]);

            $imageData = @file_get_contents($url, false, $context);

            if ($imageData === false || $imageData === '') {
                // Could not fetch – return null so the placeholder shows instead
                return null;
            }

            if (!function_exists('imagecreatefromstring') || !function_exists('imagepng')) {
                // GD not available, skip the image rather than crash
                return null;
            }

            $gdImage = @imagecreatefromstring($imageData);
            if ($gdImage === false) {
                return null;
            }

            ob_start();
            imagepng($gdImage);
            $pngData = ob_get_clean();
            imagedestroy($gdImage);

            return 'data:image/png;base64,' . base64_encode($pngData);

        } catch (\Throwable $e) {
            Log::warning('WebP→PNG conversion failed for PDF', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Preprocess document items: convert any WebP image_url to a base64 PNG
     * data URI. Non-WebP URLs are left untouched.
     */
    private function prepareDocumentForPdf(InvoiceQuotation $document): void
    {
        foreach ($document->items as $item) {
            if (!empty($item->image_url)) {
                $safe = $this->convertWebpImageForPdf($item->image_url);
                $item->setAttribute('image_url', $safe);
            }
        }
    }

    /**
     * Build a configured PDF instance for a document.
     */
    private function buildPdf(InvoiceQuotation $document, string $template): \Barryvdh\DomPDF\PDF
    {
        $pdf = Pdf::loadView("admin.invoices-quotations.templates.{$template}", compact('document'));
        $pdf->setPaper('a4', 'portrait');

        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isFontSubsettingEnabled', true);
        $pdf->setOption('defaultFont', 'Arial');
        $pdf->setOption('chroot', '/');
        $pdf->setOption('enable_remote', true);
        $pdf->setOption('debugCss', false);
        $pdf->setOption('debugLayout', false);
        $pdf->setOption('debugLayoutLines', false);
        $pdf->setOption('debugLayoutBlocks', false);

        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'method' => 'GET',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => false,
            ],
            'https' => [
                'timeout' => 60,
                'method' => 'GET',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => false,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'SNI_enabled' => true,
            ],
        ]);

        $pdf->setHttpContext($context);

        return $pdf;
    }

    /**
     * Strip all item image_urls so the PDF renders without any product images.
     * Used as a fallback when an unsupported image format (e.g. WebP) causes a crash.
     */
    private function stripDocumentImages(InvoiceQuotation $document): void
    {
        foreach ($document->items as $item) {
            $item->setAttribute('image_url', null);
        }
    }

    /**
     * Returns true when the exception message is a known image-format error
     * that dompdf throws (WebP, unsupported format, etc.).
     */
    private function isDompdfImageError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        return str_contains($msg, 'imagecreatefromwebp')
            || str_contains($msg, 'cannot convert webp')
            || str_contains($msg, 'image php extension')
            || str_contains($msg, 'imagecreatefrom');
    }

    /**
     * Download document as PDF
     */
    public function downloadPdf(Request $request, $id)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $document = InvoiceQuotation::with(['items'])->findOrFail($id);
        $template = $this->getTemplateForDocument($document);
        $filename = strtolower($document->document_number) . '.pdf';

        if ($request->has('downloadToken')) {
            cookie()->queue('downloadStarted_' . $request->downloadToken, '1', 1, '/');
        }

        // ── First attempt: render with images ────────────────────────────
        try {
            return $this->buildPdf($document, $template)->download($filename);

        } catch (\Throwable $e) {

            // ── If a WebP / unsupported-image error occurs, retry without images ──
            if ($this->isDompdfImageError($e)) {

                Log::warning('PDF image error — retrying without product images', [
                    'document_id' => $document->id,
                    'error'       => $e->getMessage(),
                ]);

                try {
                    // Null out every item's image_url in memory (no DB write)
                    $this->stripDocumentImages($document);

                    return $this->buildPdf($document, $template)->download($filename);

                } catch (\Throwable $e2) {
                    Log::error('PDF generation failed even without images', [
                        'document_id' => $document->id,
                        'error'       => $e2->getMessage(),
                        'trace'       => $e2->getTraceAsString(),
                    ]);

                    return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e2->getMessage());
                }
            }

            // ── Any other error ───────────────────────────────────────────
            Log::error('PDF generation failed', [
                'document_id' => $document->id,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Update document status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,paid,cancelled,expired'
        ]);

        $document = InvoiceQuotation::findOrFail($id);
        $oldStatus = $document->status;
        $newStatus = $request->status;

        $document->update(['status' => $newStatus]);

        // Log status change
        $document->logHistory(
            'status_changed',
            "Status changed from '{$oldStatus}' to '{$newStatus}'",
            'status',
            $oldStatus,
            $newStatus
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        }

        return redirect()->back()->with('success', 'Status updated successfully');
    }

    /**
     * Search products via Elasticsearch
     */
    public function searchProducts(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([]);
        }

        try {
            $client = $this->elasticsearchService->client();
            $indexName = $this->elasticsearchService->indexName();

            $params = [
                'index' => $indexName,
                'body' => [
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['name^3', 'sku^2', 'description'],
                            'fuzziness' => 'AUTO'
                        ]
                    ],
                    'size' => 20
                ]
            ];

            $response = $client->search($params);
            $results = $response->asArray();

            $products = collect($results['hits']['hits'] ?? [])->map(function($hit) {
                $source = $hit['_source'];

                // Extract image URL from product_thumbnail object
                $imageUrl = null;
                if (isset($source['product_thumbnail']) && is_array($source['product_thumbnail'])) {
                    $imageUrl = $source['product_thumbnail']['original_url']
                        ?? $source['product_thumbnail']['image_url']
                        ?? null;
                }

                // Fallback to direct image_url field if exists
                if (!$imageUrl && isset($source['image_url'])) {
                    $imageUrl = $source['image_url'];
                }

                // Return USD prices (frontend will convert based on currency)
                $price = $source['price'] ?? 0;
                $salePrice = $source['sale_price'] ?? null;

                // Get full variation details from database if product has variations
                $variations = [];
                if (isset($source['id']) && !empty($source['variations'])) {
                    $product = Product::with(['variations' => function($query) {
                        $query->with('variation_image');
                    }])->find($source['id']);

                    if ($product && $product->variations->isNotEmpty()) {
                        $variations = $product->variations->map(function($variation) {
                            // Return USD prices (frontend will convert)
                            return [
                                'id' => $variation->id,
                                'name' => $variation->name,
                                'price' => $variation->price ?? 0,
                                'sale_price' => $variation->sale_price,
                                'sku' => $variation->sku,
                                'quantity' => $variation->quantity ?? 0,
                                'stock_status' => $variation->stock_status ?? 'in_stock',
                                'image_url' => $variation->variation_image->original_url ?? null,
                            ];
                        })->toArray();
                    }
                }

                return [
                    'id' => $source['id'] ?? null,
                    'name' => $source['name'] ?? '',
                    'sku' => $source['sku'] ?? '',
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'image_url' => $imageUrl,
                    'variations' => $variations,
                    'has_variations' => !empty($variations),
                ];
            });

            return response()->json($products);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Search users
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q', '');

        if (empty($query)) {
            return response()->json([]);
        }

        $users = User::with(['address' => function($query) {
                $query->select('id', 'user_id', 'street', 'city', 'pincode', 'country_id', 'state_id');
            }])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('email', 'LIKE', "%{$query}%")
                  ->orWhere('phone', 'LIKE', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone', 'country_id'])
            ->map(function($user) {
                // Get all addresses for the user and build full address string
                $addresses = $user->address->map(function($addr) {
                    // Build full address from parts
                    $parts = array_filter([
                        $addr->street,
                        $addr->city,
                        $addr->pincode,
                    ]);
                    $fullAddress = implode(', ', $parts);

                    return [
                        'id' => $addr->id,
                        'address' => $fullAddress,
                    ];
                })->filter(function($addr) {
                    return !empty($addr['address']); // Only include addresses with content
                })->values()->toArray();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'addresses' => $addresses,
                    'address' => $addresses[0]['address'] ?? '', // Default to first address
                ];
            });

        return response()->json($users);
    }

    /**
     * Get available currencies from database
     */
    protected function getCurrencies()
    {
        try {
            $currencies = \App\Models\Currency::where('status', 1)
                ->orderBy('code', 'asc')
                ->get()
                ->map(function($currency) {
                    return [
                        'code' => $currency->code,
                        'name' => $currency->name ?? $currency->code,
                        'symbol' => $currency->symbol,
                        'country' => $currency->country ?? '',
                    ];
                })
                ->toArray();

            // If no currencies in database, return fallback
            if (empty($currencies)) {
                return [
                    ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'country' => 'United States'],
                    ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'country' => 'South Africa'],
                    ['code' => 'ZMW', 'name' => 'Zambian Kwacha', 'symbol' => 'K', 'country' => 'Zambia'],
                ];
            }

            return $currencies;
        } catch (\Exception $e) {
            Log::error('Failed to get currencies from database', ['error' => $e->getMessage()]);

            // Fallback currencies
            return [
                ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'country' => 'United States'],
                ['code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R', 'country' => 'South Africa'],
                ['code' => 'ZMW', 'name' => 'Zambian Kwacha', 'symbol' => 'K', 'country' => 'Zambia'],
            ];
        }
    }

    /**
     * Get the appropriate template for the document
     */
    protected function getTemplateForDocument($document)
    {
        return match($document->document_type) {
            'invoice' => 'invoice',
            'quotation' => 'quotation',
            'receipt' => 'receipt',
            'proforma' => 'proforma',
            'delivery_note' => 'delivery_note',
            default => 'invoice',
        };
    }

    /**
     * Preview the document in an iframe
     */
    public function preview($id)
    {
        $document = InvoiceQuotation::with('items')->findOrFail($id);

        // Render a simple HTML view for the document preview
        return view('admin.invoices-quotations.preview', [
            'id' => $id,
            'document' => $document
        ]);
    }

    /**
     * Convert a quotation to an invoice
     */
    public function convertToInvoice($id)
    {
        $quotation = InvoiceQuotation::with('items')->findOrFail($id);

        // Validate that it's a quotation
        if ($quotation->document_type !== 'quotation') {
            return back()->with('error', 'Only quotations can be converted to invoices');
        }

        DB::beginTransaction();
        try {
            // Generate new invoice number
            $invoiceNumber = InvoiceQuotation::generateDocumentNumber('invoice');

            // Create new invoice based on quotation
            $invoice = InvoiceQuotation::create([
                'document_number' => $invoiceNumber,
                'document_type' => 'invoice',
                'currency_code' => $quotation->currency_code,
                'customer_name' => $quotation->customer_name,
                'customer_email' => $quotation->customer_email,
                'customer_phone' => $quotation->customer_phone,
                'customer_address' => $quotation->customer_address,
                'user_id' => $quotation->user_id,
                'subtotal' => $quotation->subtotal,
                'discount_amount' => $quotation->discount_amount,
                'discount_type' => $quotation->discount_type,
                'discount_value' => $quotation->discount_value,
                'vat_amount' => $quotation->vat_amount,
                'vat_percentage' => $quotation->vat_percentage,
                'include_vat' => $quotation->include_vat,
                'total_amount' => $quotation->total_amount,
                'notes' => $quotation->notes,
                'terms_conditions' => $quotation->terms_conditions,
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'created_by' => Auth::id(),
                'status' => 'sent',
            ]);

            // Copy items
            foreach ($quotation->items as $item) {
                InvoiceQuotationItem::create([
                    'invoice_quotation_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'variation_id' => $item->variation_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'description' => $item->description,
                    'image_url' => $item->image_url,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]);
            }

            // Update quotation status to converted
            $quotation->update(['status' => 'sent']);

            // Log conversion in quotation history
            $quotation->logHistory(
                'converted_to_invoice',
                "Converted to Invoice #{$invoice->document_number}",
                'document_type',
                'quotation',
                'invoice',
                [
                    'new_invoice_id' => $invoice->id,
                    'new_invoice_number' => $invoice->document_number,
                ]
            );

            // Log creation in invoice history
            $invoice->logHistory(
                'created',
                "Created from Quotation #{$quotation->document_number}",
                null,
                null,
                null,
                [
                    'source_quotation_id' => $quotation->id,
                    'source_quotation_number' => $quotation->document_number,
                    'document_type' => 'invoice',
                    'currency' => $invoice->currency_code,
                    'total_amount' => $invoice->total_amount,
                    'items_count' => $invoice->items->count(),
                ]
            );

            DB::commit();

            return redirect()->route('admin.invoices-quotations.show', $invoice->id)
                ->with('success', 'Quotation converted to invoice successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error converting quotation: ' . $e->getMessage());
        }
    }

    /**
     * Convert an invoice or quotation to an order
     */
    public function convertToOrder($id)
    {
        $document = InvoiceQuotation::with('items')->findOrFail($id);

        // Validate that it's paid
        if ($document->status !== 'paid') {
            return back()->with('error', 'Only paid documents can be converted to orders');
        }

        DB::beginTransaction();
        try {
            // Find or create user
            $user = null;
            if ($document->user_id) {
                $user = User::find($document->user_id);
            }

            // If no user or user_id not set, try to find by email
            if (!$user && $document->customer_email) {
                $user = User::where('email', $document->customer_email)->first();
            }

            // Create new user if doesn't exist
            if (!$user) {
                // Extract phone number (digits only)
                $phoneNumber = null;
                if ($document->customer_phone) {
                    $phoneNumber = preg_replace('/[^0-9]/', '', $document->customer_phone);
                    // Convert to integer if not empty
                    $phoneNumber = $phoneNumber ? (int)$phoneNumber : null;
                }

                $user = User::create([
                    'name' => $document->customer_name,
                    'email' => $document->customer_email ?? 'noemail_' . time() . '@raines.africa',
                    'phone' => $phoneNumber,
                    'country_code' => $this->getCountryCodeFromCurrency($document->currency_code),
                    'password' => bcrypt(Str::random(16)),
                    'status' => 1,
                    'is_approved' => 1,
                    'preferred_currency' => $document->currency_code,
                ]);

                // Assign consumer role
                $user->assignRole('consumer');

                // Create address if provided
                if ($document->customer_address) {
                    Address::create([
                        'user_id' => $user->id,
                        'street' => $document->customer_address,
                        'country_id' => $this->getCountryIdFromCurrency($document->currency_code),
                        'is_default' => 1,
                    ]);
                }
            }

            // Get the processing order status
            $processingStatus = OrderStatus::where('slug', 'processing')->first();
            if (!$processingStatus) {
                throw new \Exception('Processing order status not found');
            }

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Calculate order totals
            $subtotal = $document->subtotal;
            $taxTotal = $document->include_vat ? $document->vat_amount : 0;
            $couponDiscount = $document->discount_amount;
            $shippingTotal = $document->shipping_total ?? 0;
            $deliveryPrice = $document->delivery_price ?? 0;
            $total = $document->total_amount;

            // Get address ID
            $addressId = $user->address()->first()?->id;

            // Create order with pending status first
            $order = Order::create([
                'order_number' => $orderNumber,
                'consumer_id' => $user->id,
                'amount' => $subtotal,
                'tax_total' => $taxTotal,
                'coupon_total_discount' => $couponDiscount,
                'total' => $total,
                'status' => 1,
                'payment_status' => 'completed',
                'payment_method' => 'manual',
                'order_status_id' => 1, // Start with pending
                'currency' => $document->currency_code,
                'currency_symbol' => $this->getCurrencySymbol($document->currency_code),
                'exchange_rate' => 1.0,
                'shipping_total' => $shippingTotal,
                'fast_shipping_total' => 0,
                'delivery_price' => $deliveryPrice,
                'delivery_description' => $document->delivery_description,
                'delivery_interval' => $document->delivery_interval,
                'shipping_address_id' => $addressId,
                'billing_address_id' => $addressId,
                'created_by_id' => Auth::id(),
                'note' => "Created from {$document->getDocumentTypeLabel()} #{$document->document_number}" .
                          ($document->collection_point ? " | Collection Point: {$document->collection_point}" : ""),
            ]);

            // Add products to order
            foreach ($document->items as $item) {
                // Skip items without product_id (manual items)
                if (!$item->product_id) {
                    continue;
                }

                // Calculate per-item VAT if VAT is enabled
                $itemVat = 0;
                if ($document->include_vat && $document->vat_percentage > 0) {
                    $itemVat = ($item->subtotal * $document->vat_percentage) / 100;
                }

                // Build pivot data
                $pivotData = [
                    'variation_id' => $item->variation_id ?: null,
                    'quantity' => (int)$item->quantity,
                    'single_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                    'tax' => $itemVat,
                    'shipping_cost' => 0,
                    'fast_shipping_cost' => 0,
                    'item_status' => 'processing',
                    'item_shipping_method' => 'standard',
                    'has_fast_shipping' => false,
                ];

                // Only set variation-related fields if there's an actual variation
                if ($item->variation_id) {
                    // Get variation details from database
                    $variation = \App\Models\Variation::find($item->variation_id);

                    $pivotData['variation_display_name'] = $variation ? $variation->name : $item->product_name;
                    $pivotData['selected_attribute_ids'] = json_encode([]); // Store as JSON string

                } else {
                    $pivotData['variation_display_name'] = null;
                    $pivotData['selected_attribute_ids'] = null;

                }

                $order->products()->attach($item->product_id, $pivotData);
            }

            // Update document to reflect conversion
            $document->update([
                'user_id' => $user->id,
            ]);

            // Log conversion to order
            $document->logHistory(
                'converted_to_order',
                "Converted to Order #{$order->order_number} for {$user->name}",
                'document_type',
                $document->document_type,
                'order',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_was_created' => $user->wasRecentlyCreated ?? false,
                    'order_total' => $order->total,
                    'order_status' => 'processing',
                    'items_count' => $document->items->count(),
                ]
            );

            DB::commit();

            // NOW update order status to processing AFTER commit
            // This will trigger the observer and sync to CRM
            try {
                $order->update(['order_status_id' => $processingStatus->id]);
            } catch (\Exception $e) {
                Log::error('Failed to update order status to processing after invoice conversion', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }

            return redirect()->route('admin.orders.show', $order->order_number)
                ->with('success', 'Order created successfully from ' . $document->getDocumentTypeLabel());

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error converting to order: ' . $e->getMessage());
        }
    }

    /**
     * Generate a unique order number using a pessimistic lock to prevent race conditions.
     * Without the lock two simultaneous conversions read the same max order_number,
     * both increment it by 1, and both try to INSERT the same value → UNIQUE violation.
     */
    protected function generateOrderNumber(): int
    {
        return DB::transaction(function () {
            // Lock the latest order row so no other request can read it until we commit
            $lastOrder = Order::withoutGlobalScope(\App\Models\Concerns\ExcludeTempLaybyScope::class)
                ->select('order_number')
                ->orderByDesc('order_number')
                ->lockForUpdate()
                ->first();

            return $lastOrder ? ((int) $lastOrder->order_number + 1) : 100000;
        });
    }

    /**
     * Get country code from currency
     */
    protected function getCountryCodeFromCurrency($currency)
    {
        return match($currency) {
            'USD', 'ZWL' => '263', // Zimbabwe
            'ZMW' => '260', // Zambia
            'ZAR' => '27', // South Africa
            default => '263',
        };
    }

    /**
     * Get country ID from currency
     */
    protected function getCountryIdFromCurrency($currency)
    {
        return match($currency) {
            'USD', 'ZWL' => 247, // Zimbabwe
            'ZMW' => 246, // Zambia
            'ZAR' => 204, // South Africa
            default => 247,
        };
    }

    /**
     * Get currency symbol
     */
    protected function getCurrencySymbol($currency)
    {
        return match($currency) {
            'USD' => '$',
            'ZWL' => 'ZWL',
            'ZMW' => 'K',
            'ZAR' => 'R',
            default => '$',
        };
    }

    /**
     * Send document via email
     */
    public function sendEmail(Request $request, $id)
    {
        $document = InvoiceQuotation::with('items')->findOrFail($id);

        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'required|string|max:2000',
            'sender_name' => 'nullable|string|max:255',
        ]);

        try {
            // Send email with PDF attachment
            Mail::to($validated['email'])->send(
                new InvoiceQuotationMail(
                    $document,
                    $validated['message'],
                    $validated['sender_name'] ?? 'Raines Africa'
                )
            );

            // Log email sent in history
            $document->logHistory(
                'email_sent',
                "Email sent to {$validated['email']}",
                'email',
                null,
                $validated['email'],
                [
                    'recipient_email' => $validated['email'],
                    'sender_name' => $validated['sender_name'] ?? 'Raines Africa',
                    'has_custom_message' => !empty($validated['message']),
                    'message_preview' => substr($validated['message'], 0, 100),
                ]
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Email sent successfully to ' . $validated['email']
                ]);
            }

            return redirect()->back()->with('success', 'Email sent successfully to ' . $validated['email']);

        } catch (\Exception $e) {

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    /**
     * Get exchange rates for all currencies
     */
    protected function getExchangeRates()
    {
        $rates = [];

        try {
            $currencies = \App\Models\Currency::where('status', 1)->get();

            foreach ($currencies as $currency) {
                $rates[$currency->code] = [
                    'rate' => floatval($currency->exchange_rate),
                    'symbol' => $currency->symbol,
                ];
            }

            // Ensure USD is always available as base
            if (!isset($rates['USD'])) {
                $rates['USD'] = ['rate' => 1.0, 'symbol' => '$'];
            }
        } catch (\Exception $e) {
            $rates = [
                'USD' => ['rate' => 1.0, 'symbol' => '$'],
                'ZMW' => ['rate' => 27.5, 'symbol' => 'K'],
                'ZAR' => ['rate' => 18.0, 'symbol' => 'R'],
                'ZWL' => ['rate' => 1.0, 'symbol' => 'ZWL'],
            ];
        }

        return $rates;
    }

    /**
     * Get shipping options from settings (same as checkout)
     */
    protected function getShippingOptions()
    {
        try {
            // Try to get from settings
            $setting = \App\Models\Setting::first();

            if ($setting && isset($setting->values['delivery']['shipping_options'])) {
                return $setting->values['delivery']['shipping_options'];
            }

            // Fallback shipping options if not in settings
            return [
                [
                    'title' => 'Standard Shipping',
                    'description' => 'Delivery within 7-14 business days',
                    'price' => 10.00
                ],
                [
                    'title' => 'Express Shipping',
                    'description' => 'Delivery within 3-4 business days',
                    'price' => 25.00
                ],
                [
                    'title' => 'Local Pickup - Free over $100',
                    'description' => 'Collect from store',
                    'price' => 0.00
                ]
            ];
        } catch (\Exception $e) {
            // Return fallback options
            return [
                [
                    'title' => 'Standard Shipping',
                    'description' => 'Delivery within 7-14 business days',
                    'price' => 10.00
                ],
                [
                    'title' => 'Express Shipping',
                    'description' => 'Delivery within 3-4 business days',
                    'price' => 25.00
                ]
            ];
        }
    }

    /**
     * Get collection points from settings
     */
    protected function getCollectionPoints()
    {
        try {
            // Try to get from settings
            $setting = \App\Models\Setting::first();

            if ($setting && isset($setting->values['delivery']['collection_points'])) {
                return $setting->values['delivery']['collection_points'];
            }

            // Extract collection points from shipping options if they have "collect" or "pickup" in title
            if ($setting && isset($setting->values['delivery']['shipping_options'])) {
                $collectionPoints = [];
                foreach ($setting->values['delivery']['shipping_options'] as $option) {
                    $titleLower = strtolower($option['title']);
                    if (strpos($titleLower, 'collection') !== false || strpos($titleLower, 'pickup') !== false) {
                        $collectionPoints[] = [
                            'id' => \Str::slug($option['title']),
                            'name' => $option['title'],
                            'address' => $option['description'],
                            'phone' => ''
                        ];
                    }
                }

                if (!empty($collectionPoints)) {
                    return $collectionPoints;
                }
            }

            Log::warning('Collection points not found in settings, using fallback');

            // Fallback collection points
            return [
                [
                    'id' => 'harare_main',
                    'name' => 'Harare Main Branch',
                    'address' => '123 Main Street, Harare, Zimbabwe',
                    'phone' => '+263 4 123 4567'
                ],
                [
                    'id' => 'bulawayo',
                    'name' => 'Bulawayo Branch',
                    'address' => '456 Second Avenue, Bulawayo, Zimbabwe',
                    'phone' => '+263 9 987 6543'
                ],
                [
                    'id' => 'lusaka',
                    'name' => 'Lusaka Branch',
                    'address' => '789 Independence Avenue, Lusaka, Zambia',
                    'phone' => '+260 211 123456'
                ]
            ];
        } catch (\Exception $e) {
            // Return fallback collection points
            return [
                [
                    'id' => 'harare_main',
                    'name' => 'Harare Main Branch',
                    'address' => '123 Main Street, Harare'
                ]
            ];
        }
    }

    /**
     * Get shipping rules from database (same as checkout)
     */
    protected function getShippingRules()
    {
        try {
            // Fetch active shipping rules from database
            $rules = \App\Models\ShippingRule::where('status', 1)
                ->orderBy('min', 'asc')
                ->get()
                ->map(function($rule) {
                    return [
                        'id' => $rule->id,
                        'name' => $rule->name,
                        'rule_type' => $rule->rule_type,
                        'min' => floatval($rule->min),
                        'max' => floatval($rule->max),
                        'amount' => floatval($rule->amount),
                        'shipping_type' => $rule->shipping_type,
                    ];
                })
                ->toArray();

            return $rules;
        } catch (\Exception $e) {
            Log::error('Failed to get shipping rules from database', ['error' => $e->getMessage()]);

            // Return empty array if failed
            return [];
        }
    }

    /**
     * Auto-save invoice/quotation draft
     */
    public function autoSave(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:invoices_quotations,id',
            'document_type' => 'required|in:invoice,quotation,receipt,proforma,delivery_note',
            'currency_code' => 'required|string|max:3',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string',
            'customer_address' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'include_vat' => 'boolean',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_total' => 'nullable|numeric|min:0',
            'delivery_method' => 'nullable|string',
            'delivery_description' => 'nullable|string',
            'delivery_price' => 'nullable|numeric|min:0',
            'delivery_interval' => 'nullable|string',
            'collection_point' => 'nullable|string',
            'discount_type' => 'required|in:percentage,amount',
            'discount_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:variations,id',
            'items.*.product_name' => 'required|string',
            'items.*.sku' => 'nullable|string',
            'items.*.description' => 'nullable|string',
            'items.*.image_url' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $userId = Auth::id();
            $documentType = $validated['document_type'];

            // Check if updating existing autosave or creating new one
            if (!empty($validated['id'])) {
                $document = InvoiceQuotation::where('id', $validated['id'])
                    ->where('created_by', $userId)
                    ->where('status', 'autosave')
                    ->firstOrFail();
            } else {
                // Check if user has an existing autosave for this document type
                $document = InvoiceQuotation::where('created_by', $userId)
                    ->where('document_type', $documentType)
                    ->where('status', 'autosave')
                    ->first();

                // Create new if none exists
                if (!$document) {
                    // Generate unique document number with user ID to prevent collisions
                    $prefix = match($documentType) {
                        'invoice' => 'INV',
                        'quotation' => 'QUO',
                        'receipt' => 'REC',
                        'proforma' => 'PRO',
                        'delivery_note' => 'DEL',
                        default => 'DOC',
                    };

                    $documentNumber = sprintf('%s-AUTOSAVE-USER%d-%s', $prefix, $userId, time());

                    $document = new InvoiceQuotation([
                        'document_number' => $documentNumber,
                        'document_type' => $documentType,
                        'status' => 'autosave',
                        'created_by' => $userId,
                    ]);
                }
            }

            // Update document fields
            $document->fill([
                'currency_code' => $validated['currency_code'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_address' => $validated['customer_address'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
                'issue_date' => $validated['issue_date'] ?? now(),
                'due_date' => $validated['due_date'] ?? null,
                'valid_until' => $validated['valid_until'] ?? null,
                'include_vat' => $validated['include_vat'] ?? true,
                'vat_percentage' => $validated['vat_percentage'] ?? 15,
                'shipping_total' => $validated['shipping_total'] ?? 0,
                'delivery_method' => $validated['delivery_method'] ?? null,
                'delivery_description' => $validated['delivery_description'] ?? null,
                'delivery_price' => $validated['delivery_price'] ?? 0,
                'delivery_interval' => $validated['delivery_interval'] ?? null,
                'collection_point' => $validated['collection_point'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
            ]);

            $document->save();

            // Update items if provided
            if (!empty($validated['items'])) {
                // Delete existing items
                $document->items()->delete();

                // Create new items
                foreach ($validated['items'] as $itemData) {
                    InvoiceQuotationItem::create([
                        'invoice_quotation_id' => $document->id,
                        'product_id' => $itemData['product_id'] ?? null,
                        'variation_id' => $itemData['variation_id'] ?? null,
                        'product_name' => $itemData['product_name'],
                        'sku' => $itemData['sku'] ?? null,
                        'description' => $itemData['description'] ?? null,
                        'image_url' => $itemData['image_url'] ?? null,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'subtotal' => $itemData['quantity'] * $itemData['unit_price'],
                    ]);
                }

                // Recalculate totals
                $document->calculateTotals()->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Auto-saved successfully',
                'document_id' => $document->id,
                'autosave_time' => $document->updated_at->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto-save failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'document_type' => $validated['document_type'] ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Auto-save failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resume from auto-saved document
     */
    public function resume(Request $request)
    {
        $userId = Auth::id();
        $documentType = $request->query('type', 'invoice');

        // Find the latest autosave for this user and document type
        $document = InvoiceQuotation::with('items')
            ->where('created_by', $userId)
            ->where('document_type', $documentType)
            ->where('status', 'autosave')
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$document) {
            Log::info('No autosave found', [
                'user_id' => $userId,
                'document_type' => $documentType
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No auto-saved document found'
            ], 404);
        }

        // Log for debugging
        Log::info('Resume autosave', [
            'document_id' => $document->id,
            'document_number' => $document->document_number,
            'user_id' => $userId,
            'items_count' => $document->items->count(),
            'last_updated' => $document->updated_at->format('Y-m-d H:i:s')
        ]);

        return response()->json([
            'success' => true,
            'document' => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'currency_code' => $document->currency_code,
                'customer_name' => $document->customer_name,
                'customer_email' => $document->customer_email,
                'customer_phone' => $document->customer_phone,
                'customer_address' => $document->customer_address,
                'user_id' => $document->user_id,
                'issue_date' => $document->issue_date?->format('Y-m-d'),
                'due_date' => $document->due_date?->format('Y-m-d'),
                'valid_until' => $document->valid_until?->format('Y-m-d'),
                'include_vat' => $document->include_vat,
                'vat_percentage' => $document->vat_percentage,
                'shipping_total' => $document->shipping_total,
                'delivery_method' => $document->delivery_method,
                'delivery_description' => $document->delivery_description,
                'delivery_price' => $document->delivery_price,
                'delivery_interval' => $document->delivery_interval,
                'collection_point' => $document->collection_point,
                'discount_type' => $document->discount_type,
                'discount_value' => $document->discount_value,
                'notes' => $document->notes,
                'terms_conditions' => $document->terms_conditions,
                'items' => $document->items->map(function($item) {
                    return [
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                        'product_name' => $item->product_name,
                        'sku' => $item->sku,
                        'description' => $item->description,
                        'image_url' => $item->image_url,
                        'quantity' => floatval($item->quantity),
                        'unit_price' => floatval($item->unit_price),
                    ];
                })->toArray(), // Convert to array to ensure it's not a Laravel collection object
                'autosave_time' => $document->updated_at->format('Y-m-d H:i:s'),
                'created_by' => $document->created_by
            ]
        ]);
    }

    /**
     * Discard auto-saved document
     */
    public function discardAutoSave(Request $request)
    {
        $userId = Auth::id();
        $documentType = $request->input('document_type', 'invoice');

        try {
            // Delete all autosaves for this user and document type
            $deleted = InvoiceQuotation::where('created_by', $userId)
                ->where('document_type', $documentType)
                ->where('status', 'autosave')
                ->delete();

            Log::info('Autosave discarded', [
                'user_id' => $userId,
                'document_type' => $documentType,
                'deleted_count' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Auto-saved document discarded'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to discard autosave', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to discard autosave: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert a document to any other document type.
     * Generates a new document number for the target type; all other content is preserved.
     */
    public function convertType(Request $request, $id)
    {
        $document = InvoiceQuotation::findOrFail($id);

        $request->validate([
            'target_type' => 'required|in:invoice,quotation,proforma,receipt,delivery_note',
        ]);

        $targetType = $request->target_type;

        if ($targetType === $document->document_type) {
            return back()->with('error', 'The document is already of type ' . ucfirst($targetType) . '.');
        }

        DB::beginTransaction();
        try {
            $oldType   = $document->document_type;
            $oldNumber = $document->document_number;

            // Generate a unique document number for the target type
            $newNumber = InvoiceQuotation::generateDocumentNumber($targetType);
            $attempts  = 0;
            while (
                InvoiceQuotation::where('document_number', $newNumber)
                    ->where('id', '!=', $document->id)
                    ->exists()
                && $attempts < 10
            ) {
                $newNumber = InvoiceQuotation::generateDocumentNumber($targetType);
                $attempts++;
            }

            if ($attempts >= 10) {
                throw new \Exception('Unable to generate a unique document number after 10 attempts.');
            }

            $document->document_type   = $targetType;
            $document->document_number = $newNumber;
            $document->save();

            $document->logHistory(
                'updated',
                "Converted document type from {$oldType} ({$oldNumber}) to {$targetType} ({$newNumber})",
                'document_type',
                $oldType,
                $targetType
            );

            DB::commit();

            $label = match($targetType) {
                'invoice'       => 'Invoice',
                'quotation'     => 'Quotation',
                'proforma'      => 'Proforma Invoice',
                'receipt'       => 'Receipt',
                'delivery_note' => 'Delivery Note',
                default         => ucfirst($targetType),
            };

            return redirect()->route('admin.invoices-quotations.show', $document->id)
                ->with('success', "Document converted to {$label} ({$newNumber}) successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Conversion failed: ' . $e->getMessage());
        }
    }
}

