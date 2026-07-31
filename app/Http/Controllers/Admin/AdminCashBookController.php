<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashBookEntry;
use App\Models\CashBookCategory;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminCashBookController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cashbook.index')->only(['index', 'entries']);
        $this->middleware('permission:cashbook.show')->only(['show', 'stats']);
        $this->middleware('permission:cashbook.create')->only(['store', 'importCsv']);
        $this->middleware('permission:cashbook.edit')->only(['update']);
        $this->middleware('permission:cashbook.delete')->only(['destroy']);
    }

    /**
     * Display cash book page
     */
    public function index()
    {
        $categories = CashBookCategory::active()->orderBy('name')->get();
        $users = User::orderBy('name')->get();

        // Get user's branch for permission filtering
        $userBranch = auth()->user()->branch ?? null;
        $isAdmin = auth()->user()->hasRole('admin');
        $availableBranches = $this->getAvailableBranches($userBranch, $isAdmin);

        return view('admin.cash-book.index', compact('categories', 'users', 'userBranch', 'availableBranches', 'isAdmin'));
    }

    /**
     * Get available branches based on user's assigned branch
     */
    private function getAvailableBranches(?string $userBranch, bool $isAdmin = false): array
    {
        $allBranches = [
            'Harare' => '🏢 Harare',
            'Bulawayo' => '🏢 Bulawayo',
            'Mutare' => '🏢 Mutare',
            'Zambia' => '🏢 Zambia',
        ];

        // Admin role can see all branches
        if ($isAdmin) {
            return $allBranches;
        }

        // If user has a specific branch assigned, only show that branch
        if ($userBranch && isset($allBranches[$userBranch])) {
            return [$userBranch => $allBranches[$userBranch]];
        }

        // If no branch or admin, show all branches
        return $allBranches;
    }

    /**
     * Get currency info for a branch
     */
    private function getBranchCurrency(string $branch): array
    {
        return match($branch) {
            'Zambia' => [
                'code' => 'ZMW',
                'symbol' => 'K',
                'name' => 'Zambian Kwacha'
            ],
            default => [
                'code' => 'USD',
                'symbol' => '$',
                'name' => 'US Dollar'
            ]
        };
    }

    /**
     * Get entries with filters (AJAX)
     */
    public function entries(Request $request)
    {
        // Get user's assigned branch
        $userBranch = auth()->user()->branch ?? null;
        $isAdmin = auth()->user()->hasRole('admin');

        // Default branch is Harare or user's assigned branch
        $requestedBranch = $request->input('branch', 'Harare');

        // Admin can view any branch, otherwise restrict to user's branch
        if ($isAdmin) {
            $branch = $requestedBranch;
        } elseif ($userBranch) {
            $branch = $userBranch;
        } else {
            $branch = $requestedBranch;
        }

        $query = CashBookEntry::with(['category', 'enteredBy'])
            ->where('branch', $branch)
            ->orderBy('entry_date', 'desc')
            ->orderBy('entry_time', 'desc')
            ->orderBy('id', 'desc');

        // ...existing code for filters...

        if ($request->filled('start_date')) {
            $query->where('entry_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('entry_date', '<=', $request->end_date);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }

        if ($request->filled('type')) {
            if ($request->type === 'income') {
                $query->where('cash_in', '>', 0);
            } elseif ($request->type === 'expense') {
                $query->where('cash_out', '>', 0);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('remark', 'like', "%{$search}%")
                  ->orWhere('party', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 50);
        $entries = $query->paginate($perPage);

        // Get currency info for the branch
        $currency = $this->getBranchCurrency($branch);

        return response()->json([
            'success' => true,
            'entries' => $entries,
            'branch' => $branch,
            'currency' => $currency
        ]);
    }

    /**
     * Get statistics (AJAX)
     */
    public function stats(Request $request)
    {
        // Get user's assigned branch
        $userBranch = auth()->user()->branch ?? null;
        $isAdmin = auth()->user()->hasRole('admin');

        // Default branch is Harare or user's assigned branch
        $requestedBranch = $request->input('branch', 'Harare');

        // Admin can view any branch, otherwise restrict to user's branch
        if ($isAdmin) {
            $branch = $requestedBranch;
        } elseif ($userBranch) {
            $branch = $userBranch;
        } else {
            $branch = $requestedBranch;
        }

        // Only use date filters if provided, otherwise get ALL data for the branch
        $query = CashBookEntry::where('branch', $branch);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $query->dateRange($startDate, $endDate);
        }

        // Overall stats for the branch (with date filter if provided)
        $totalIncome = (clone $query)->sum('cash_in');
        $totalExpenses = (clone $query)->sum('cash_out');
        $netCashFlow = $totalIncome - $totalExpenses;

        // Current balance for the branch (latest entry)
        $currentBalance = CashBookEntry::where('branch', $branch)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->value('balance') ?? 0;

        // By category
        $expensesByCategory = (clone $query)->where('cash_out', '>', 0)
            ->select('category_id', DB::raw('SUM(cash_out) as total'))
            ->with('category')
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function($entry) {
                return [
                    'category' => $entry->category->name ?? 'Uncategorized',
                    'total' => $entry->total,
                    'color' => $entry->category->color ?? '#6B7280',
                ];
            });

        $incomeByCategory = (clone $query)->where('cash_in', '>', 0)
            ->select('category_id', DB::raw('SUM(cash_in) as total'))
            ->with('category')
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function($entry) {
                return [
                    'category' => $entry->category->name ?? 'Uncategorized',
                    'total' => $entry->total,
                    'color' => $entry->category->color ?? '#6B7280',
                ];
            });

        // By mode
        $byMode = (clone $query)
            ->select('mode',
                DB::raw('SUM(cash_in) as total_in'),
                DB::raw('SUM(cash_out) as total_out')
            )
            ->groupBy('mode')
            ->get();

        // Daily trend (only if date range provided)
        $dailyTrend = [];
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $dailyTrend = (clone $query)
                ->select('entry_date',
                    DB::raw('SUM(cash_in) as total_in'),
                    DB::raw('SUM(cash_out) as total_out')
                )
                ->groupBy('entry_date')
                ->orderBy('entry_date')
                ->get();
        }

        // Get currency info for the branch
        $currency = $this->getBranchCurrency($branch);

        return response()->json([
            'success' => true,
            'branch' => $branch,
            'currency' => $currency,
            'stats' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_cash_flow' => $netCashFlow,
                'current_balance' => $currentBalance,
                'expenses_by_category' => $expensesByCategory,
                'income_by_category' => $incomeByCategory,
                'by_mode' => $byMode,
                'daily_trend' => $dailyTrend,
            ]
        ]);
    }

    /**
     * Store new entry
     */
    public function store(Request $request)
    {
        // Get user's assigned branch
        $userBranch = auth()->user()->branch ?? null;

        // If user has a branch but no branch in request, use their assigned branch
        if ($userBranch && !$request->has('branch')) {
            $request->merge(['branch' => $userBranch]);
        }

        $validator = Validator::make($request->all(), [
            'branch' => 'required|in:Harare,Bulawayo,Mutare,Zambia',
            'entry_date' => 'required|date',
            'entry_time' => 'nullable|date_format:H:i',
            'remark' => 'nullable|string|max:500',
            'party' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:cash_book_categories,id',
            'mode' => 'required|in:cash,bank,card,mobile_money,other',
            'cash_in' => 'nullable|numeric|min:0',
            'cash_out' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'reference_type' => 'nullable|string|in:order',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Cashbook entry validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->except(['_token'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $requestedBranch = $request->branch;
        $user = auth()->user();
        $userBranch = $user->branch;

        // Admin can always proceed
        if (!$user->hasRole('admin')) {

            // If user has a branch and it's different from requested branch → block
            if ($userBranch && $userBranch !== $requestedBranch) {
                return response()->json([
                    'success' => false,
                    'message' => "You are not authorized to create entries for branch: {$requestedBranch}.
                          You can only create entries for: {$userBranch}"
                ], 403);
            }
        }


        DB::beginTransaction();
        try {
            $branch = $request->branch;
            $cashIn = $request->input('cash_in', 0);
            $cashOut = $request->input('cash_out', 0);

            // Get the last balance for THIS BRANCH
            $lastEntry = CashBookEntry::where('branch', $branch)
                ->orderBy('entry_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $lastBalance = $lastEntry ? $lastEntry->balance : 0;
            $newBalance = $lastBalance + $cashIn - $cashOut;

            $entry = CashBookEntry::create([
                'branch' => $branch,
                'entry_date' => $request->entry_date,
                'entry_time' => $request->entry_time ?? now()->format('H:i'),
                'remark' => $request->remark,
                'party' => $request->party,
                'category_id' => $request->category_id,
                'mode' => $request->mode,
                'entered_by' => auth()->id(),
                'cash_in' => $cashIn,
                'cash_out' => $cashOut ?? 0,
                'balance' => $newBalance,
                'reference_number' => $request->reference_number,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cash book entry created successfully',
                'entry' => $entry->load(['category', 'enteredBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create cash book entry', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update entry
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can edit cash book entries.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'entry_date' => 'required|date',
            'entry_time' => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'remark' => 'nullable|string|max:500',
            'party' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:cash_book_categories,id',
            'mode' => 'required|in:cash,bank,card,mobile_money,other',
            'cash_in' => 'nullable|numeric|min:0',
            'cash_out' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $entry = CashBookEntry::findOrFail($id);

            $cashIn = $request->input('cash_in', 0);
            $cashOut = $request->input('cash_out', 0);

            // Recalculate balance
            $previousEntry = CashBookEntry::where('entry_date', '<', $entry->entry_date)
                ->orWhere(function($q) use ($entry) {
                    $q->where('entry_date', '=', $entry->entry_date)
                      ->where('id', '<', $entry->id);
                })
                ->orderBy('entry_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $previousBalance = $previousEntry ? $previousEntry->balance : 0;
            $newBalance = $previousBalance + $cashIn - $cashOut;

            $entry->update([
                'entry_date' => $request->entry_date,
                'entry_time' => $request->entry_time,
                'remark' => $request->remark,
                'party' => $request->party,
                'category_id' => $request->category_id,
                'mode' => $request->mode,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'balance' => $newBalance,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
            ]);

            // Recalculate subsequent balances
            $this->recalculateBalances($entry->entry_date, $entry->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cash book entry updated successfully',
                'entry' => $entry->load(['category', 'enteredBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update cash book entry', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete entry
     */
    public function destroy($id)
    {
        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can delete cash book entries.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $entry = CashBookEntry::findOrFail($id);
            $entryDate = $entry->entry_date;

            $entry->delete();

            // Recalculate balances for entries after this one
            $this->recalculateBalances($entryDate);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cash book entry deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete cash book entry', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete entry: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import CSV
     */
    public function importCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
            'branch' => 'required|in:Harare,Bulawayo,Mutare,Zambia',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $branch = $request->branch;
            $file = $request->file('csv_file');
            $content = file_get_contents($file->getRealPath());

            // Auto-detect delimiter (comma or tab)
            $firstLine = strtok($content, "\n");
            $commaCount = substr_count($firstLine, ',');
            $tabCount = substr_count($firstLine, "\t");
            $delimiter = $tabCount > $commaCount ? "\t" : ",";


            // Pre-load existing categories to avoid transaction conflicts
            $categories = CashBookCategory::all()->keyBy('slug');

            $handle = fopen($file->getRealPath(), 'r');

            // Skip header row
            $header = fgetcsv($handle, 10000, $delimiter);

            $imported = 0;
            $skipped = 0;
            $errors = [];
            $rowNumber = 1; // Start at 1 (header is row 0)
            $batchSize = 50; // Commit every 50 rows
            $currentBatch = 0;

            DB::beginTransaction();

            while (($row = fgetcsv($handle, 10000, $delimiter)) !== false) {
                $rowNumber++;

                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        $skipped++;
                        continue;
                    }

                    // Must have at least 7 columns (up to Entry By)
                    if (count($row) < 7) {
                        $skipped++;
                        $errors[] = "Row {$rowNumber}: Not enough columns (found " . count($row) . ", need at least 7)";
                        Log::warning("Row {$rowNumber} skipped", ['columns' => $row, 'count' => count($row)]);
                        continue;
                    }

                    // Parse CSV row - Your format:
                    // Date, Time, Remark, Party, Category, Mode, Entry By, Cash In, Cash Out, Balance

                    // Parse Date (1-Dec-25 format)
                    $dateStr = trim($row[0] ?? '');
                    if (empty($dateStr)) {
                        $skipped++;
                        $errors[] = "Row {$rowNumber}: Missing date";
                        continue;
                    }

                    try {
                        // Handle formats like "1-Dec-25"
                        $entryDate = Carbon::parse($dateStr)->format('Y-m-d');
                    } catch (\Exception $e) {
                        $skipped++;
                        $errors[] = "Row {$rowNumber}: Invalid date format '{$dateStr}'";
                        continue;
                    }

                    // Parse Time (9:10 am format)
                    $timeStr = trim($row[1] ?? '');
                    if (!empty($timeStr)) {
                        try {
                            $entryTime = Carbon::parse($timeStr)->format('H:i');
                        } catch (\Exception $e) {
                            $entryTime = now()->format('H:i');
                        }
                    } else {
                        $entryTime = now()->format('H:i');
                    }

                    $remark = !empty($row[2]) ? trim($row[2]) : null;
                    $party = !empty($row[3]) ? trim($row[3]) : null;
                    $categoryName = !empty($row[4]) ? trim($row[4]) : null;
                    $mode = !empty($row[5]) ? strtolower(trim($row[5])) : 'cash';
                    $enteredByName = !empty($row[6]) ? trim($row[6]) : null;

                    // Parse amounts - handle empty strings and spaces
                    $cashInStr = isset($row[7]) ? trim($row[7]) : '';
                    $cashOutStr = isset($row[8]) ? trim($row[8]) : '';
                    $balanceStr = isset($row[9]) ? trim($row[9]) : '';

                    $cashIn = !empty($cashInStr) ? floatval(str_replace(',', '', $cashInStr)) : 0;
                    $cashOut = !empty($cashOutStr) ? floatval(str_replace(',', '', $cashOutStr)) : 0;
                    $balance = !empty($balanceStr) ? floatval(str_replace(',', '', $balanceStr)) : 0;

                    // Find category from pre-loaded cache
                    $categoryId = null;
                    if (!empty($categoryName)) {
                        $slug = \Str::slug($categoryName);
                        if (isset($categories[$slug])) {
                            $categoryId = $categories[$slug]->id;
                        } else {
                            // Create new category outside transaction
                            try {
                                // Temporarily commit current transaction to create category
                                if (DB::transactionLevel() > 0) {
                                    DB::commit();
                                }

                                $category = CashBookCategory::firstOrCreate(
                                    ['slug' => $slug],
                                    [
                                        'name' => $categoryName,
                                        'type' => 'both',
                                        'is_active' => true
                                    ]
                                );
                                $categoryId = $category->id;
                                $categories[$slug] = $category; // Add to cache

                                // Restart transaction
                                DB::beginTransaction();
                                $currentBatch = 0;
                            } catch (\Exception $e) {
                                Log::warning("Failed to create category", [
                                    'name' => $categoryName,
                                    'error' => $e->getMessage()
                                ]);
                                // Restart transaction
                                DB::beginTransaction();
                                $currentBatch = 0;
                            }
                        }
                    }

                    // Always use user ID 1 for imports
                    $enteredBy = 1;

                    CashBookEntry::create([
                        'branch' => $branch,
                        'entry_date' => $entryDate,
                        'entry_time' => $entryTime,
                        'remark' => $remark,
                        'party' => $party,
                        'category_id' => $categoryId,
                        'mode' => $mode,
                        'entered_by' => $enteredBy,
                        'cash_in' => $cashIn,
                        'cash_out' => $cashOut,
                        'balance' => $balance, // Use balance from CSV
                    ]);

                    $imported++;
                    $currentBatch++;

                    // Commit in batches to prevent long-running transactions
                    if ($currentBatch >= $batchSize) {
                        DB::commit();
                        DB::beginTransaction();
                        $currentBatch = 0;
                    }

                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: " . $e->getMessage();
                    Log::warning("CSV import row {$rowNumber} failed", [
                        'error' => $e->getMessage(),
                        'row_data' => $row
                    ]);

                    // Always rollback and restart on error to prevent stuck transaction
                    try {
                        if (DB::transactionLevel() > 0) {
                            DB::rollBack();
                        }
                    } catch (\Exception $rollbackError) {
                        // Ignore rollback errors
                    }

                    // Start fresh transaction
                    DB::beginTransaction();
                    $currentBatch = 0;
                }
            }

            // Commit any remaining rows in the last batch
            if (DB::transactionLevel() > 0) {
                DB::commit();
            }

            fclose($handle);


            return response()->json([
                'success' => true,
                'message' => "Import completed. {$imported} entries imported, {$skipped} skipped.",
                'stats' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'total_rows' => $rowNumber - 1,
                    'errors' => array_slice($errors, 0, 20) // Limit to first 20 errors
                ]
            ]);

        } catch (\Exception $e) {
            // Try to rollback if there's an active transaction
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Failed to import CSV', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to import CSV: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate balances for entries after a certain date
     */
    private function recalculateBalances($fromDate, $excludeId = null)
    {
        $entries = CashBookEntry::where('entry_date', '>=', $fromDate)
            ->when($excludeId, function($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            })
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $currentBalance = 0;

        // Get the balance before the first entry
        $lastBeforeEntry = CashBookEntry::where('entry_date', '<', $fromDate)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastBeforeEntry) {
            $currentBalance = $lastBeforeEntry->balance;
        }

        foreach ($entries as $entry) {
            $currentBalance = $currentBalance + $entry->cash_in - $entry->cash_out;
            $entry->update(['balance' => $currentBalance]);
        }
    }

    /**
     * Get show entry details
     */
    public function show($id)
    {
        $entry = CashBookEntry::with(['category', 'enteredBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'entry' => $entry
        ]);
    }

    /**
     * Fetch order details by order number
     */
    public function getOrderDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $orderNumber = $request->order_number;

            $order = Order::where('order_number', $orderNumber)
                ->with(['consumer', 'order_status', 'products'])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => "Order #{$orderNumber} not found"
                ], 404);
            }

            // Check if order already has a cashbook entry
            $existingEntry = CashBookEntry::where('reference_type', 'order')
                ->where('reference_id', $order->id)
                ->first();

            // Extract branch to determine currency
            $branch = $this->extractBranchFromDelivery($order->delivery_description);
            $currency = $this->getBranchCurrency($branch);

            // Get Sale category ID for auto-filling
            $saleCategory = CashBookCategory::where('slug', 'sale')->first();

            // Get the order amount in the correct currency
            // For Zambia orders, use the Zambian amount from order
            $totalAmount = $order->total ?? $order->amount;
            $originalAmount = $totalAmount;

            // If Zambia branch, check if order has ZMW total stored
            if ($branch === 'Zambia') {
                // Check if order has exchange_rate (the actual field name in orders table)
                if (isset($order->exchange_rate) && $order->exchange_rate > 0) {
                    $totalAmount = ($order->total ?? $order->amount) * $order->exchange_rate;
                } else {
                    Log::warning('⚠️ Zambia order has no exchange_rate field', [
                        'order_number' => $order->order_number,
                        'order_id' => $order->id,
                        'total_usd' => $originalAmount,
                        'exchange_rate' => $order->exchange_rate ?? 'NULL',
                        'using_usd_amount' => $totalAmount
                    ]);
                }
            }

            // Check branch mismatch - get user's current viewing branch or assigned branch
            $userBranch = auth()->user()->branch ?? null;
            $isAdmin = auth()->user()->hasRole('admin');

            // Determine the expected branch for validation
            // If user has assigned branch (not admin), they should only see orders from their branch
            $expectedBranch = null;
            if ($userBranch && !$isAdmin) {
                $expectedBranch = $userBranch;
            }

            // Check for branch mismatch
            $branchMismatch = false;
            $mismatchMessage = '';

            if ($expectedBranch && $branch !== $expectedBranch) {
                $branchMismatch = true;

                if ($expectedBranch === 'Zambia') {
                    // Zambia user looking at USD order
                    $mismatchMessage = "This order belongs to {$branch} branch (USD). You can only process Zambian branch orders (ZMW).";
                }
                else {
                    // USD branch user looking at Zambia order
                    $mismatchMessage = "This order belongs to {$branch} branch. You can only process {$expectedBranch} branch orders.";
                }

                Log::warning('Order branch mismatch detected', [
                    'order_number' => $order->order_number,
                    'order_branch' => $branch,
                    'expected_branch' => $expectedBranch,
                    'user_id' => auth()->id()
                ]);
            }

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->consumer->name ?? 'N/A',
                    'customer_email' => $order->consumer->email ?? 'N/A',
                    'customer_phone' => $order->consumer->phone ?? 'N/A',
                    'total_amount' => $totalAmount,
                    'original_usd_amount' => $originalAmount,
                    'exchange_rate_used' => $order->exchange_rate ?? null,
                    'currency' => $currency,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'order_status' => $order->order_status->name ?? 'N/A',
                    'delivery_description' => $order->delivery_description,
                    'branch' => $branch,
                    'sale_category_id' => $saleCategory ? $saleCategory->id : null,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'has_cashbook_entry' => !is_null($existingEntry),
                    'branch_mismatch' => $branchMismatch,
                    'mismatch_message' => $mismatchMessage,
                    'expected_branch' => $expectedBranch,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch order details for cashbook', [
                'order_number' => $request->order_number,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract branch from delivery description
     */
    private function extractBranchFromDelivery(?string $deliveryDescription): string
    {
        if (!$deliveryDescription) {
            return 'Harare'; // Default
        }

        $delivery = strtolower($deliveryDescription);

        if (str_contains($delivery, 'harare')) {
            return 'Harare';
        } elseif (str_contains($delivery, 'bulawayo')) {
            return 'Bulawayo';
        } elseif (str_contains($delivery, 'mutare')) {
            return 'Mutare';
        } elseif (str_contains($delivery, 'zambia') || str_contains($delivery, 'lusaka')) {
            return 'Zambia';
        }

        return 'Harare'; // Default
    }
}
