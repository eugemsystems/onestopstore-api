@extends('admin.layout')

@section('title', 'Cash Book Management')
@section('page-title', 'Cash Book')

@push('styles')
<style>
    .cash-book-container {
        padding: 20px;
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .stat-card.positive {
        border-left: 4px solid #10B981;
    }

    .stat-card.negative {
        border-left: 4px solid #EF4444;
    }

    .stat-card.neutral {
        border-left: 4px solid #3B82F6;
    }

    .stat-label {
        font-size: 14px;
        color: #6B7280;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #1F2937;
    }

    .filters-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 5px;
        color: #374151;
    }

    .form-control {
        padding: 8px 12px;
        border: 1px solid #D1D5DB;
        border-radius: 6px;
        font-size: 14px;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary {
        background: #3B82F6;
        color: white;
    }

    .btn-primary:hover {
        background: #2563EB;
    }

    .btn-success {
        background: #10B981;
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }

    .table-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow-x: auto;
    }

    .entries-table {
        width: 100%;
        border-collapse: collapse;
    }

    .entries-table th {
        background: #F9FAFB;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        color: #374151;
        border-bottom: 2px solid #E5E7EB;
    }

    .entries-table td {
        padding: 12px;
        border-bottom: 1px solid #E5E7EB;
        font-size: 14px;
    }

    .entries-table tr:hover {
        background: #F9FAFB;
    }

    .badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-success {
        background: #D1FAE5;
        color: #065F46;
    }

    .badge-danger {
        background: #FEE2E2;
        color: #991B1B;
    }

    .text-success {
        color: #10B981;
        font-weight: 600;
    }

    .text-danger {
        color: #EF4444;
        font-weight: 600;
    }

    .loading {
        text-align: center;
        padding: 40px;
        color: #6B7280;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .branch-tabs {
        display: flex;
        gap: 0;
        margin-bottom: 30px;
        border-bottom: 2px solid #E5E7EB;
    }

    .branch-tab {
        padding: 12px 24px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 15px;
        font-weight: 500;
        color: #6B7280;
        transition: all 0.2s;
    }

    .branch-tab:hover {
        color: #374151;
        background: #F9FAFB;
    }

    .branch-tab.active {
        color: #3B82F6;
        border-bottom-color: #3B82F6;
        background: none;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }

    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        max-width: 900px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }

    .form-row-full {
        margin-bottom: 15px;
    }

    .transaction-type-selector {
        background: #F9FAFB;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        border: 2px solid #E5E7EB;
    }

    .transaction-type-selector h4 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: #374151;
    }

    .type-options {
        display: flex;
        gap: 20px;
    }

    .type-option {
        flex: 1;
        display: flex;
        align-items: center;
        padding: 15px;
        background: white;
        border: 2px solid #D1D5DB;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .type-option:hover {
        border-color: #3B82F6;
        background: #F0F9FF;
    }

    .type-option.selected {
        border-color: #10B981;
        background: #ECFDF5;
    }

    .type-option input[type="radio"] {
        width: 20px;
        height: 20px;
        margin-right: 10px;
        cursor: pointer;
    }

    .type-option label {
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .type-option.selected label {
        color: #047857;
    }

    .transaction-fields {
        display: none;
    }

    .transaction-fields.active {
        display: block;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-header h3 {
        margin: 0;
    }

    .close-modal {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6B7280;
    }

    .close-modal:hover {
        color: #1F2937;
    }

    .file-upload-area {
        border: 2px dashed #D1D5DB;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        margin: 20px 0;
        cursor: pointer;
        transition: all 0.2s;
    }

    .file-upload-area:hover {
        border-color: #3B82F6;
        background: #F9FAFB;
    }

    .file-upload-area.dragover {
        border-color: #10B981;
        background: #F0FDF4;
    }

    .file-info {
        margin-top: 15px;
        padding: 10px;
        background: #F3F4F6;
        border-radius: 6px;
        font-size: 14px;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 15px;
    }

    .alert-success {
        background: #D1FAE5;
        color: #065F46;
        border: 1px solid #10B981;
    }

    .alert-danger {
        background: #FEE2E2;
        color: #991B1B;
        border: 1px solid #EF4444;
    }

    .btn-warning {
        background: #F59E0B;
        color: white;
    }

    .btn-warning:hover {
        background: #D97706;
    }

    .btn-danger {
        background: #EF4444;
        color: white;
    }

    .btn-danger:hover {
        background: #DC2626;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }

    .action-cell {
        white-space: nowrap;
        display: flex;
        gap: 6px;
    }

    .delete-confirm-modal .modal-content {
        max-width: 450px;
        text-align: center;
    }

    .delete-confirm-modal .modal-icon {
        font-size: 48px;
        margin-bottom: 15px;
        color: #EF4444;
    }
</style>
@endpush

@section('content')
<div class="cash-book-container">
    <!-- Branch Tabs -->
    <div class="branch-tabs">
    @foreach($availableBranches as $branchKey => $branchLabel)
    <button class="branch-tab {{ $loop->first ? 'active' : '' }}"
            data-branch="{{ $branchKey }}"
            onclick="switchBranch('{{ $branchKey }}')">
        {{ $branchLabel }}
    </button>
    @endforeach
</div>

@if($userBranch && !$isAdmin)
<div class="alert alert-info" style="margin-bottom: 20px; background: #E0F2FE; color: #0369A1; border: 1px solid #7DD3FC;">
    <i class="fas fa-info-circle"></i> You are viewing cashbook for: <strong>{{ $userBranch }}</strong> branch only.
</div>
@endif

    @can('cashbook.view_stats_card')
        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card positive">
                <div class="stat-label">Total Income</div>
                <div class="stat-value" id="totalIncome">$0.00</div>
            </div>
            <div class="stat-card negative">
                <div class="stat-label">Total Expenses</div>
                <div class="stat-value" id="totalExpenses">$0.00</div>
            </div>
            <div class="stat-card neutral">
                <div class="stat-label">Net Cash Flow</div>
                <div class="stat-value" id="netCashFlow">$0.00</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Current Balance</div>
                <div class="stat-value" id="currentBalance">$0.00</div>
            </div>
        </div>
    @endcan

   @can('cashbook.create')
        <!-- Action Buttons -->
        <div class="action-buttons">
            <button type="button" class="btn btn-success" onclick="showAddEntryModal()">
                <i class="fas fa-plus"></i> Add Entry
            </button>
            <button type="button" class="btn btn-primary" onclick="showImportModal()">
                <i class="fas fa-upload"></i> Import CSV
            </button>
        </div>

    @endcan
    <!-- Filters -->
    <div class="filters-section">
        <h3 style="margin-bottom: 15px;">Filters</h3>
        <form id="filtersForm">
            <div class="filters-grid">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" class="form-control" name="start_date" id="startDate">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" class="form-control" name="end_date" id="endDate">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select class="form-control" name="category_id" id="categoryFilter">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Mode</label>
                    <select class="form-control" name="mode" id="modeFilter">
                        <option value="">All Modes</option>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="card">Card</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select class="form-control" name="type" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" class="form-control" name="search" id="searchFilter"
                           placeholder="Search remarks, party, ref...">
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <button type="button" class="btn" onclick="resetFilters()"
                        style="background: #6B7280; color: white;">Reset</button>
            </div>
        </form>
    </div>

    <!-- Entries Table -->
    <div class="table-container">
        <h3 style="margin-bottom: 15px;">Cash Book Entries</h3>
        <div id="entriesContainer">
            <div class="loading">Loading entries...</div>
        </div>
    </div>
</div>

<!-- Add Entry Modal -->
<div id="addEntryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Cash Book Entry</h3>
            <button class="close-modal" onclick="closeAddEntryModal()">&times;</button>
        </div>
        <div id="addEntryAlert"></div>

        <!-- Order Lookup Section -->
        <div style="background: #F3F4F6; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; font-size: 16px;">🔍 Lookup Order Details</h4>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="orderNumberLookup" class="form-control" placeholder="Enter Order Number (e.g., 10005)" style="flex: 1;">
                <button type="button" class="btn btn-primary" onclick="lookupOrder()" style="white-space: nowrap;">
                    <i class="fas fa-search"></i> Lookup
                </button>
            </div>
            <div id="orderDetailsDisplay" style="margin-top: 10px; display: none;"></div>
        </div>

        <form id="addEntryForm">
            @csrf

            <!-- Transaction Type Selector -->
            <div class="transaction-type-selector">
                <h4>📝 Select Transaction Type *</h4>
                <div class="type-options">
                    <div class="type-option" id="cashInOption" onclick="selectTransactionType('cash_in')">
                        <input type="radio" name="transaction_type" id="transactionTypeCashIn" value="cash_in">
                        <label for="transactionTypeCashIn">
                            <i class="fas fa-arrow-down" style="color: #10B981;"></i>
                            <span>Cash In (Money Received)</span>
                        </label>
                    </div>
                    <div class="type-option" id="cashOutOption" onclick="selectTransactionType('cash_out')">
                        <input type="radio" name="transaction_type" id="transactionTypeCashOut" value="cash_out">
                        <label for="transactionTypeCashOut">
                            <i class="fas fa-arrow-up" style="color: #EF4444;"></i>
                            <span>Cash Out (Money Paid)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Transaction Fields (Hidden until type is selected) -->
            <div id="transactionFields" class="transaction-fields">
                <div class="form-row">
                    <div class="form-group">
                        <label>Branch *</label>
                        <select class="form-control" name="branch" id="entryBranch" required>
                            @foreach($availableBranches as $branchKey => $branchLabel)
                            <option value="{{ $branchKey }}" {{ $loop->first ? 'selected' : '' }}>{{ $branchKey }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Entry Date *</label>
                        <input type="date" class="form-control" name="entry_date" required value="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Entry Time</label>
                        <input type="time" class="form-control" name="entry_time" value="{{ now()->format('H:i') }}">
                    </div>
                    <div class="form-group">
                        <label>Payment Mode *</label>
                        <select class="form-control" name="mode" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="card">Card</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Party (Customer/Vendor)</label>
                        <input type="text" class="form-control" name="party" placeholder="Customer or vendor name">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" name="category_id">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Amount Field (Dynamic based on transaction type) -->
                <div class="form-row-full">
                    <div class="form-group">
                        <label id="amountLabel">Amount *</label>
                        <input type="number" class="form-control" id="transactionAmount" step="0.01" min="0" placeholder="0.00" required>
                        <input type="hidden" name="cash_in" id="cashInField" value="0">
                        <input type="hidden" name="cash_out" id="cashOutField" value="0">
                    </div>
                </div>

                <div class="form-row-full">
                    <div class="form-group">
                        <label>Remark</label>
                        <textarea class="form-control" name="remark" rows="2" placeholder="Transaction description"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Reference Number (Optional)</label>
                        <input type="text" class="form-control" name="reference_number" placeholder="Order #, Invoice #, etc.">
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Additional notes"></textarea>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Entry
                    </button>
                    <button type="button" class="btn" onclick="closeAddEntryModal()" style="background: #6B7280; color: white;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Import CSV Modal -->
<div id="importModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Cash Book from CSV</h3>
            <button class="close-modal" onclick="closeImportModal()">&times;</button>
        </div>
        <div id="importAlert"></div>
        <form id="importForm">
            @csrf
            <div class="form-group" style="margin-bottom: 20px;">
                <label><strong>Select Branch for Import *</strong></label>
                <select class="form-control" name="branch" id="importBranch" required>
                    <option value="Harare" selected>Harare</option>
                    <option value="Bulawayo">Bulawayo</option>
                    <option value="Mutare">Mutare</option>
                    <option value="Zambia">Zambia</option>
                </select>
                <p style="margin: 5px 0 0 0; color: #6B7280; font-size: 13px;">All imported entries will be assigned to this branch</p>
            </div>
            <div class="file-upload-area" id="fileUploadArea">
                <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #6B7280; margin-bottom: 10px;"></i>
                <p style="margin: 10px 0; color: #374151; font-weight: 500;">Click to upload or drag and drop</p>
                <p style="margin: 0; color: #6B7280; font-size: 13px;">Tab-separated CSV file (Max 10MB)</p>
                <input type="file" id="csvFile" name="csv_file" accept=".csv,.txt" style="display: none;" required>
            </div>
            <div id="fileInfo" class="file-info" style="display: none;">
                <strong>Selected file:</strong> <span id="fileName"></span> (<span id="fileSize"></span>)
            </div>

            <div style="margin: 20px 0; padding: 15px; background: #F3F4F6; border-radius: 6px; font-size: 13px;">
                <strong>CSV Format Required:</strong>
                <pre style="margin: 10px 0; overflow-x: auto;">Date,Time,Remark,Party,Category,Mode,Entry By,Cash In,Cash Out,Balance</pre>
                <p style="margin: 5px 0 0 0; color: #6B7280;">
                    Supports both comma-separated (CSV) and tab-separated (TSV) formats<br>
                    Date format: 1-Dec-25 or 2025-12-01<br>
                    Time format: 9:10 am or 09:10
                </p>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" id="importBtn">
                    <i class="fas fa-upload"></i> Import CSV
                </button>
                <button type="button" class="btn" onclick="closeImportModal()" style="background: #6B7280; color: white;">Cancel</button>
            </div>
        </form>

        <div id="importProgress" style="display: none; margin-top: 20px;">
            <div style="background: #E5E7EB; height: 8px; border-radius: 4px; overflow: hidden;">
                <div id="progressBar" style="background: #10B981; height: 100%; width: 0%; transition: width 0.3s;"></div>
            </div>
            <p id="progressText" style="margin-top: 10px; text-align: center; color: #6B7280; font-size: 14px;">Uploading...</p>
        </div>
    </div>
</div>

@can('cashbook.edit')
<!-- Edit Entry Modal -->
<div id="editEntryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Cash Book Entry</h3>
            <button class="close-modal" onclick="closeEditModal()">&times;</button>
        </div>
        <div id="editEntryAlert"></div>
        <form id="editEntryForm">
            @csrf
            @method('PUT')
            <input type="hidden" id="editEntryId" name="entry_id">

            <div class="form-row">
                <div class="form-group">
                    <label>Entry Date *</label>
                    <input type="date" class="form-control" name="entry_date" id="editEntryDate" required>
                </div>
                <div class="form-group">
                    <label>Entry Time</label>
                    <input type="time" class="form-control" name="entry_time" id="editEntryTime">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Party (Customer/Vendor)</label>
                    <input type="text" class="form-control" name="party" id="editParty" placeholder="Customer or vendor name">
                </div>
                <div class="form-group">
                    <label>Payment Mode *</label>
                    <select class="form-control" name="mode" id="editMode" required>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="card">Card</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select class="form-control" name="category_id" id="editCategory">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Reference Number</label>
                    <input type="text" class="form-control" name="reference_number" id="editReferenceNumber" placeholder="Order #, Invoice #, etc.">
                </div>
            </div>

            <div class="form-row-full">
                <div class="form-group">
                    <label>Remark</label>
                    <textarea class="form-control" name="remark" id="editRemark" rows="2" placeholder="Transaction description"></textarea>
                </div>
            </div>

            <!-- Transaction type + single amount -->
            <div class="form-row-full" style="margin-bottom:15px;">
                <label style="font-size:14px;font-weight:600;color:#374151;margin-bottom:8px;display:block;">Transaction Type *</label>
                <div style="display:flex;gap:12px;">
                    <label style="flex:1;display:flex;align-items:center;gap:8px;padding:12px;border:2px solid #D1D5DB;border-radius:8px;cursor:pointer;background:white;" id="editCashInLabel">
                        <input type="radio" name="edit_transaction_type" id="editTypeCashIn" value="cash_in" style="width:18px;height:18px;">
                        <span style="font-size:14px;font-weight:500;"><i class="fas fa-arrow-down" style="color:#10B981;"></i> Cash In</span>
                    </label>
                    <label style="flex:1;display:flex;align-items:center;gap:8px;padding:12px;border:2px solid #D1D5DB;border-radius:8px;cursor:pointer;background:white;" id="editCashOutLabel">
                        <input type="radio" name="edit_transaction_type" id="editTypeCashOut" value="cash_out" style="width:18px;height:18px;">
                        <span style="font-size:14px;font-weight:500;"><i class="fas fa-arrow-up" style="color:#EF4444;"></i> Cash Out</span>
                    </label>
                </div>
            </div>

            <div class="form-row-full" style="margin-bottom:15px;">
                <div class="form-group">
                    <label id="editAmountLabel">Amount *</label>
                    <input type="number" class="form-control" id="editAmount" step="0.01" min="0.01" placeholder="0.00" required>
                </div>
            </div>

            <div class="form-row-full">
                <div class="form-group">
                    <label>Notes</label>
                    <textarea class="form-control" name="notes" id="editNotes" rows="2" placeholder="Additional notes"></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Entry
                </button>
                <button type="button" class="btn" onclick="closeEditModal()" style="background: #6B7280; color: white;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('cashbook.delete')
<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal delete-confirm-modal">
    <div class="modal-content">
        <div class="modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3 style="margin-bottom: 10px;">Delete Entry?</h3>
        <p style="color: #6B7280; margin-bottom: 20px;">This action cannot be undone. The entry will be permanently deleted and balances will be recalculated.</p>
        <input type="hidden" id="deleteEntryId">
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="btn btn-danger" onclick="executeDelete()">
                <i class="fas fa-trash"></i> Yes, Delete
            </button>
            <button class="btn" onclick="closeDeleteModal()" style="background: #6B7280; color: white;">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
        <div id="deleteAlert" style="margin-top: 15px;"></div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
let currentBranch = '{{ $userBranch ?? array_key_first($availableBranches) }}';
let userAssignedBranch = '{{ $userBranch ?? "" }}';
let isAdmin = {{ $isAdmin ? 'true' : 'false' }};
let currentCurrency = { code: 'USD', symbol: '$', name: 'US Dollar' };

// Map to store entry objects by id so onclick can pass just the id (avoids JSON-in-attribute quoting issues)
const entriesMap = {};

document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadEntries();

    document.getElementById('filtersForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadEntries();
        loadStats();
    });

    // Setup file upload area
    setupFileUpload();

    // Setup add entry form
    document.getElementById('addEntryForm').addEventListener('submit', handleAddEntry);

    // Setup edit entry form (admin only)
    const editForm = document.getElementById('editEntryForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditEntry);
    }

    // Setup import form
    document.getElementById('importForm').addEventListener('submit', handleImportCSV);

    // Setup order lookup on Enter key
    document.getElementById('orderNumberLookup').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            lookupOrder();
        }
    });
});

function switchBranch(branch) {
    currentBranch = branch;

    // Update active tab
    document.querySelectorAll('.branch-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`.branch-tab[data-branch="${branch}"]`).classList.add('active');

    // Reload data for this branch
    loadStats();
    loadEntries();
}

async function loadStats() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    let url = `/admin/cash-book/stats?branch=${currentBranch}`;
    if (startDate && endDate) {
        url += `&start_date=${startDate}&end_date=${endDate}`;
    }

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            // Update currency from response
            if (data.currency) {
                currentCurrency = data.currency;
            }

            document.getElementById('totalIncome').textContent = formatCurrency(data.stats.total_income);
            document.getElementById('totalExpenses').textContent = formatCurrency(data.stats.total_expenses);
            document.getElementById('netCashFlow').textContent = formatCurrency(data.stats.net_cash_flow);
            document.getElementById('currentBalance').textContent = formatCurrency(data.stats.current_balance);
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

async function loadEntries() {
    const formData = new FormData(document.getElementById('filtersForm'));
    const params = new URLSearchParams(formData);
    params.append('branch', currentBranch);

    const container = document.getElementById('entriesContainer');
    container.innerHTML = '<div class="loading">Loading entries...</div>';

    try {
        const response = await fetch(`/admin/cash-book/entries?${params}`);
        const data = await response.json();

        if (data.success) {
            // Update currency from response
            if (data.currency) {
                currentCurrency = data.currency;
            }

            if (data.entries.data.length > 0) {
                let html = `
                    <table class="entries-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Remark</th>
                                <th>Party</th>
                                <th>Category</th>
                                <th>Mode</th>
                                <th>Entered By</th>
                                <th>Cash In</th>
                                <th>Cash Out</th>
                                <th>Balance</th>
                                ${isAdmin ? '<th>Actions</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>
                `;

                data.entries.data.forEach(entry => {
                    // Store entry in map so openEditModal can look it up by id safely
                    entriesMap[entry.id] = entry;

                    // Laravel serialises the enteredBy() relation as snake_case "entered_by" in JSON
                    const userObj = entry.entered_by || entry.enteredBy || entry.entered_by_user || null;
                    const enteredBy = userObj
                        ? (userObj.name || userObj.email || 'Unknown')
                        : '-';

                    const actionsHtml = isAdmin ? `
                        <td>
                            <div class="action-cell">
                                <button class="btn btn-warning btn-sm" onclick="openEditModal(${entry.id})" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="confirmDelete(${entry.id})" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    ` : '';

                    html += `
                        <tr>
                            <td>${entry.entry_date ? entry.entry_date.split('T')[0] : '-'}</td>
                            <td>${entry.entry_time || '-'}</td>
                            <td>${entry.remark || '-'}</td>
                            <td>${entry.party || '-'}</td>
                            <td>${entry.category ? entry.category.name : '-'}</td>
                            <td><span class="badge">${entry.mode}</span></td>
                            <td>${enteredBy}</td>
                            <td class="text-success">${entry.cash_in > 0 ? formatCurrency(entry.cash_in) : '-'}</td>
                            <td class="text-danger">${entry.cash_out > 0 ? formatCurrency(entry.cash_out) : '-'}</td>
                            <td><strong>${formatCurrency(entry.balance)}</strong></td>
                            ${actionsHtml}
                        </tr>
                    `;
                });

                html += `
                        </tbody>
                    </table>
                `;

                // Add pagination if there are more pages
                if (data.entries.last_page > 1) {
                    html += '<div style="margin-top: 20px; text-align: center;">';
                    html += `<p>Showing page ${data.entries.current_page} of ${data.entries.last_page} (${data.entries.total} total entries)</p>`;
                    html += '</div>';
                }

                container.innerHTML = html;
            } else {
                container.innerHTML = `<div class="loading">No entries found for ${currentBranch}. Add your first entry or import from CSV.</div>`;
            }
        }
    } catch (error) {
        console.error('Error loading entries:', error);
        container.innerHTML = '<div class="loading" style="color: red;">Error loading entries</div>';
    }
}

function formatCurrency(amount) {
    const symbol = currentCurrency.symbol || '$';
    const formatted = parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    return symbol + formatted;
}

function resetFilters() {
    document.getElementById('filtersForm').reset();
    loadEntries();
    loadStats();
}

// Add Entry Modal Functions
// Add Entry Modal Functions
function showAddEntryModal() {
    document.getElementById('addEntryModal').classList.add('show');
    document.getElementById('addEntryForm').reset();
    document.getElementById('addEntryAlert').innerHTML = '';
    document.getElementById('orderNumberLookup').value = '';
    document.getElementById('orderDetailsDisplay').style.display = 'none';

    // Reset transaction type selection
    document.getElementById('transactionFields').classList.remove('active');
    document.getElementById('cashInOption').classList.remove('selected');
    document.getElementById('cashOutOption').classList.remove('selected');
    document.getElementById('transactionTypeCashIn').checked = false;
    document.getElementById('transactionTypeCashOut').checked = false;
    document.getElementById('transactionAmount').value = '';

    // Set current branch - IMPORTANT: Do this AFTER reset
    const branchSelect = document.getElementById('entryBranch');
    const branchToSet = userAssignedBranch || currentBranch;

    // Set the value
    branchSelect.value = branchToSet;

    // If user has assigned branch, make it visually locked (but still submits)
    if (userAssignedBranch) {
        branchSelect.style.backgroundColor = '#F3F4F6';
        branchSelect.style.pointerEvents = 'none'; // Prevents clicking/changing
        branchSelect.style.cursor = 'not-allowed';
        branchSelect.title = 'Your branch is restricted to: ' + userAssignedBranch;
    } else {
        branchSelect.style.backgroundColor = '';
        branchSelect.style.pointerEvents = '';
        branchSelect.style.cursor = '';
        branchSelect.title = '';
    }
}

// Transaction Type Selection
function selectTransactionType(type) {
    // Update radio button
    const cashInRadio = document.getElementById('transactionTypeCashIn');
    const cashOutRadio = document.getElementById('transactionTypeCashOut');
    const cashInOption = document.getElementById('cashInOption');
    const cashOutOption = document.getElementById('cashOutOption');

    if (type === 'cash_in') {
        cashInRadio.checked = true;
        cashInOption.classList.add('selected');
        cashOutOption.classList.remove('selected');
        document.getElementById('amountLabel').innerHTML = 'Cash In Amount (Money Received) *';
    } else {
        cashOutRadio.checked = true;
        cashOutOption.classList.add('selected');
        cashInOption.classList.remove('selected');
        document.getElementById('amountLabel').innerHTML = 'Cash Out Amount (Money Paid) *';
    }

    // Show the transaction fields
    document.getElementById('transactionFields').classList.add('active');
}

function closeAddEntryModal() {
    document.getElementById('addEntryModal').classList.remove('show');
    // Reset transaction type selection
    document.getElementById('transactionFields').classList.remove('active');
    document.getElementById('cashInOption').classList.remove('selected');
    document.getElementById('cashOutOption').classList.remove('selected');
    document.getElementById('transactionTypeCashIn').checked = false;
    document.getElementById('transactionTypeCashOut').checked = false;
}

// Order Lookup Function
async function lookupOrder() {
    const orderNumber = document.getElementById('orderNumberLookup').value.trim();
    const displayDiv = document.getElementById('orderDetailsDisplay');
    const alertDiv = document.getElementById('addEntryAlert');

    if (!orderNumber) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please enter an order number</div>';
        return;
    }

    // Show loading
    displayDiv.innerHTML = '<div style="text-align: center; padding: 10px; color: #6B7280;">Looking up order...</div>';
    displayDiv.style.display = 'block';
    alertDiv.innerHTML = '';

    try {
        const response = await fetch('/admin/cash-book/order-details', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ order_number: orderNumber })
        });

        const data = await response.json();

        if (data.success && data.order) {
            const order = data.order;

            // Format the amount with proper currency symbol
            const formattedAmount = `${order.currency.symbol}${parseFloat(order.total_amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;


            // Check if we should allow form filling
            const canFillForm = !order.branch_mismatch && !order.has_cashbook_entry;

            // Display order details with warnings
            let warningsHtml = '';

            // Branch mismatch warning (RED - blocking)
            if (order.branch_mismatch) {
                warningsHtml += `
                    <div style="margin-top: 8px; padding: 12px; background: #FEE2E2; color: #991B1B; border: 2px solid #EF4444; border-radius: 4px; font-size: 13px; font-weight: 600;">
                        🚫 BRANCH MISMATCH: ${order.mismatch_message}
                    </div>
                `;
            }

            // Existing entry warning (RED - blocking)
            if (order.has_cashbook_entry) {
                warningsHtml += `
                    <div style="margin-top: 8px; padding: 12px; background: #FEE2E2; color: #991B1B; border: 2px solid #EF4444; border-radius: 4px; font-size: 13px; font-weight: 600;">
                        🚫 DUPLICATE: This order already has a cashbook entry
                    </div>
                `;
            }

            // Exchange rate warning (YELLOW - informational)
            if (!order.exchange_rate_used && order.branch === 'Zambia' && !order.branch_mismatch) {
                warningsHtml += `
                    <div style="margin-top: 8px; padding: 8px; background: #FEF3C7; color: #92400E; border-radius: 4px; font-size: 12px;">
                        ⚠️ No exchange rate set - showing USD amount. Update order with exchange rate!
                    </div>
                `;
            }

            // Fill button or blocked message
            let actionButton = '';
            if (canFillForm) {
                actionButton = `
                    <button type="button" onclick='fillOrderDetails(${JSON.stringify(order)})' class="btn btn-primary" style="margin-top: 10px; width: 100%; font-size: 13px;">
                        <i class="fas fa-arrow-down"></i> Fill Form with Order Details
                    </button>
                `;
            } else {
                actionButton = `
                    <button type="button" class="btn" disabled style="margin-top: 10px; width: 100%; font-size: 13px; background: #9CA3AF; color: white; cursor: not-allowed;">
                        <i class="fas fa-ban"></i> Cannot Fill Form - See Warning Above
                    </button>
                `;
            }

            displayDiv.innerHTML = `
                <div style="background: white; padding: 12px; border-radius: 6px; border: 1px solid #E5E7EB;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; font-size: 13px;">
                        <div><strong>Order #:</strong> ${order.order_number}</div>
                        <div><strong>Status:</strong> ${order.order_status}</div>
                        <div><strong>Customer:</strong> ${order.customer_name}</div>
                        <div><strong>Amount:</strong> <span style="color: #10B981; font-weight: 600;">${formattedAmount}</span></div>
                        <div><strong>Payment:</strong> ${order.payment_method}</div>
                        <div><strong>Branch:</strong> ${order.branch} (${order.currency.code})</div>
                    </div>
                    ${warningsHtml}
                    ${actionButton}
                </div>
            `;
        } else {
            displayDiv.innerHTML = `<div style="padding: 10px; color: #EF4444; background: #FEE2E2; border-radius: 4px;">${data.message || 'Order not found'}</div>`;
        }
    } catch (error) {
        console.error('Error looking up order:', error);
        displayDiv.innerHTML = '<div style="padding: 10px; color: #EF4444; background: #FEE2E2; border-radius: 4px;">Failed to lookup order. Please try again.</div>';
    }
}

// Fill form with order details
function fillOrderDetails(order) {
    const form = document.getElementById('addEntryForm');
    const alertDiv = document.getElementById('addEntryAlert');

    // VALIDATION: Refuse to fill if branch mismatch
    if (order.branch_mismatch) {
        console.error('🚫 BLOCKED: Branch mismatch detected');
        alertDiv.innerHTML = `
            <div class="alert alert-danger" style="background: #FEE2E2; border: 2px solid #EF4444;">
                <strong>🚫 CANNOT FILL FORM</strong><br>
                ${order.mismatch_message}<br>
                <small>This order belongs to <strong>${order.branch}</strong> branch but you are viewing <strong>${order.expected_branch}</strong> branch.</small>
            </div>
        `;
        return; // Exit - do not fill form
    }

    // VALIDATION: Refuse to fill if order already has cashbook entry
    if (order.has_cashbook_entry) {
        console.error('🚫 BLOCKED: Order already has cashbook entry');
        alertDiv.innerHTML = `
            <div class="alert alert-danger" style="background: #FEE2E2; border: 2px solid #EF4444;">
                <strong>🚫 CANNOT FILL FORM</strong><br>
                This order already has a cashbook entry. Creating a duplicate entry is not allowed.<br>
                <small>Order #${order.order_number} has been processed previously.</small>
            </div>
        `;
        return; // Exit - do not fill form
    }


    const amountToFill = parseFloat(order.total_amount).toFixed(2);


    // Fill form fields with the CONVERTED amount
    const branchField = form.querySelector('[name="branch"]');

    branchField.value = order.branch;


    if (!branchField.value || branchField.value.trim() === '') {
        Array.from(branchField.options).forEach(opt => {
            console.log(`  - value: "${opt.value}", text: "${opt.text}"`);
        });
    }

    form.querySelector('[name="party"]').value = order.customer_name;
    form.querySelector('[name="remark"]').value = `Order #${order.order_number} - ${order.order_status} - Payment via ${order.payment_method}`;

    // Set Sale category automatically
    const categoryField = form.querySelector('[name="category_id"]');
    if (order.sale_category_id) {
        categoryField.value = order.sale_category_id;
    }

    // Select Cash In transaction type automatically for orders
    selectTransactionType('cash_in');

    // Use the total_amount which should already be converted for Zambia orders
    document.getElementById('transactionAmount').value = amountToFill;

    form.querySelector('[name="reference_number"]').value = order.order_number;
    form.querySelector('[name="mode"]').value = order.payment_method === 'cod' ? 'cash' : (order.payment_method === 'eft' || order.payment_method === 'bank_transfer' ? 'bank' : 'other');

    // Store order ID for linking (add hidden fields if they don't exist)
    let referenceTypeInput = form.querySelector('[name="reference_type"]');
    let referenceIdInput = form.querySelector('[name="reference_id"]');

    if (!referenceTypeInput) {
        referenceTypeInput = document.createElement('input');
        referenceTypeInput.type = 'hidden';
        referenceTypeInput.name = 'reference_type';
        form.appendChild(referenceTypeInput);
    }

    if (!referenceIdInput) {
        referenceIdInput = document.createElement('input');
        referenceIdInput.type = 'hidden';
        referenceIdInput.name = 'reference_id';
        form.appendChild(referenceIdInput);
    }

    referenceTypeInput.value = 'order';
    referenceIdInput.value = order.id;

    // Add note about order with currency info
    const notesField = form.querySelector('[name="notes"]');
    const formattedAmount = `${order.currency.symbol}${parseFloat(order.total_amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;

    notesField.value = `Order ID: ${order.id}\nCustomer: ${order.customer_name}\nEmail: ${order.customer_email}\nPhone: ${order.customer_phone}\nOrder Status: ${order.order_status}\nCurrency: ${order.currency.code} (${order.currency.name})\nOriginal USD: $${order.original_usd_amount}\nExchange Rate: ${order.exchange_rate_used || 'N/A'}\nAmount: ${formattedAmount}`;

    // Show success message with converted amount
    let alertMessage = `✅ Order details filled in ${order.currency.code}! Amount: <strong>${formattedAmount}</strong>. Category set to <strong>Sale</strong>.`;

    if (order.branch === 'Zambia' && (!order.exchange_rate_used || order.exchange_rate_used === 0)) {
        alertMessage += `<br><span style="color: #EF4444;">⚠️ WARNING: This order has NO exchange rate! Amount is in USD, not ZMW.</span>`;
    }

    alertDiv.innerHTML = `<div class="alert alert-success">${alertMessage} Review and submit the form.</div>`;
}

async function handleAddEntry(e) {
    e.preventDefault();

    const form = e.target;
    const alertDiv = document.getElementById('addEntryAlert');

    // Check if transaction type is selected
    const transactionType = form.querySelector('input[name="transaction_type"]:checked');
    if (!transactionType) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please select transaction type (Cash In or Cash Out)</div>';
        return;
    }

    // Get the amount and set it to the correct field
    const amount = document.getElementById('transactionAmount').value;
    if (!amount || parseFloat(amount) <= 0) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please enter a valid amount greater than 0</div>';
        return;
    }

    // Set cash_in or cash_out based on transaction type
    if (transactionType.value === 'cash_in') {
        document.getElementById('cashInField').value = amount;
        document.getElementById('cashOutField').value = 0;
    } else {
        document.getElementById('cashInField').value = 0;
        document.getElementById('cashOutField').value = amount;
    }

    const formData = new FormData(form);

    // Debug: Check branch value before submission
    const branchValue = formData.get('branch');

    // Validation: Ensure branch is set
    if (!branchValue || branchValue.trim() === '') {
        alertDiv.innerHTML = '<div class="alert alert-danger">Error: Branch is required. Please refresh and try again.</div>';
        return;
    }

    try {
        const response = await fetch('/admin/cash-book', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">Entry added successfully!</div>';
            form.reset();
            // Reset transaction type selection
            document.getElementById('transactionFields').classList.remove('active');
            document.getElementById('cashInOption').classList.remove('selected');
            document.getElementById('cashOutOption').classList.remove('selected');
            loadEntries();
            loadStats();
            setTimeout(() => {
                closeAddEntryModal();
            }, 1500);
        } else {
            alertDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
        }
    } catch (error) {
        console.error('Error adding entry:', error);
        alertDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
    }
}

// Import CSV Modal Functions
function showImportModal() {
    document.getElementById('importModal').classList.add('show');
    document.getElementById('importForm').reset();
    document.getElementById('importAlert').innerHTML = '';
    document.getElementById('fileInfo').style.display = 'none';
    document.getElementById('importProgress').style.display = 'none';
    // Set current branch
    document.getElementById('importBranch').value = currentBranch;
}

function closeImportModal() {
    document.getElementById('importModal').classList.remove('show');
}

function setupFileUpload() {
    const uploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('csvFile');

    uploadArea.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            displayFileInfo(this.files[0]);
        }
    });

    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');

        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            displayFileInfo(e.dataTransfer.files[0]);
        }
    });
}

function displayFileInfo(file) {
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('fileSize').textContent = formatFileSize(file.size);
    document.getElementById('fileInfo').style.display = 'block';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

async function handleImportCSV(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const alertDiv = document.getElementById('importAlert');
    const progressDiv = document.getElementById('importProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const importBtn = document.getElementById('importBtn');

    // Show progress
    progressDiv.style.display = 'block';
    importBtn.disabled = true;
    progressBar.style.width = '0%';
    progressText.textContent = 'Uploading...';
    alertDiv.innerHTML = '';

    try {
        // Simulate progress
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += 10;
            if (progress <= 90) {
                progressBar.style.width = progress + '%';
            }
        }, 200);

        const response = await fetch('/admin/cash-book/import-csv', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        });

        clearInterval(progressInterval);
        progressBar.style.width = '100%';

        const data = await response.json();

        if (data.success) {
            progressText.textContent = 'Import complete!';

            let errorHtml = '';
            if (data.stats.errors && data.stats.errors.length > 0) {
                errorHtml = '<div style="margin-top: 10px; max-height: 200px; overflow-y: auto; background: #FEF2F2; padding: 10px; border-radius: 4px;">';
                errorHtml += '<strong style="color: #991B1B;">Errors/Warnings:</strong><ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">';
                data.stats.errors.forEach(err => {
                    errorHtml += `<li style="color: #DC2626;">${err}</li>`;
                });
                errorHtml += '</ul></div>';
            }

            alertDiv.innerHTML = `
                <div class="alert alert-success">
                    <strong>Import Complete!</strong><br>
                    ✅ Successfully imported: <strong>${data.stats.imported}</strong> entries<br>
                    ${data.stats.skipped > 0 ? `⚠️ Skipped: <strong>${data.stats.skipped}</strong> entries<br>` : ''}
                    📊 Total rows processed: ${data.stats.total_rows || data.stats.imported + data.stats.skipped}
                    ${errorHtml}
                </div>
            `;
            form.reset();
            document.getElementById('fileInfo').style.display = 'none';
            loadEntries();
            loadStats();

            // Keep modal open longer if there were errors
            const closeDelay = data.stats.errors && data.stats.errors.length > 0 ? 8000 : 3000;
            setTimeout(() => {
                if (data.stats.imported > 0) {
                    progressDiv.style.display = 'none';
                    closeImportModal();
                }
            }, closeDelay);
        } else {
            progressText.textContent = 'Import failed';
            alertDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
            importBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error importing CSV:', error);
        progressText.textContent = 'Import failed';
        alertDiv.innerHTML = '<div class="alert alert-danger">An error occurred during import. Please try again.</div>';
        importBtn.disabled = false;
    }
}

// ─── Edit Entry ────────────────────────────────────────────────
function openEditModal(entryId) {
    if (!isAdmin) return;

    const entry = entriesMap[entryId];
    if (!entry) {
        console.error('Entry not found in map:', entryId);
        return;
    }

    // Show modal first so elements are visible/interactable
    document.getElementById('editEntryModal').classList.add('show');
    document.getElementById('editEntryAlert').innerHTML = '';

    document.getElementById('editEntryId').value = entry.id;

    // Date — strip any time component
    document.getElementById('editEntryDate').value = entry.entry_date
        ? entry.entry_date.split('T')[0]
        : '';

    // Time — strip seconds (HH:MM:SS → HH:MM) so <input type="time"> accepts it
    const rawTime = entry.entry_time || '';
    document.getElementById('editEntryTime').value = rawTime ? rawTime.substring(0, 5) : '';

    document.getElementById('editParty').value           = entry.party || '';
    document.getElementById('editReferenceNumber').value = entry.reference_number || '';
    document.getElementById('editRemark').value          = entry.remark || '';
    document.getElementById('editNotes').value           = entry.notes || '';

    // Determine type and amount from entry
    const cashIn  = parseFloat(entry.cash_in)  || 0;
    const cashOut = parseFloat(entry.cash_out) || 0;
    const isCashIn = cashIn > 0;

    const typeIn  = document.getElementById('editTypeCashIn');
    const typeOut = document.getElementById('editTypeCashOut');
    const labelIn  = document.getElementById('editCashInLabel');
    const labelOut = document.getElementById('editCashOutLabel');

    if (isCashIn) {
        typeIn.checked  = true;
        typeOut.checked = false;
        document.getElementById('editAmount').value = cashIn.toFixed(2);
        document.getElementById('editAmountLabel').textContent = 'Cash In Amount *';
        labelIn.style.borderColor  = '#10B981';
        labelIn.style.background   = '#ECFDF5';
        labelOut.style.borderColor = '#D1D5DB';
        labelOut.style.background  = 'white';
    } else {
        typeOut.checked = true;
        typeIn.checked  = false;
        document.getElementById('editAmount').value = cashOut.toFixed(2);
        document.getElementById('editAmountLabel').textContent = 'Cash Out Amount *';
        labelOut.style.borderColor = '#EF4444';
        labelOut.style.background  = '#FEF2F2';
        labelIn.style.borderColor  = '#D1D5DB';
        labelIn.style.background   = 'white';
    }

    // Wire up radio change to update label styling
    typeIn.onchange = () => {
        document.getElementById('editAmountLabel').textContent = 'Cash In Amount *';
        labelIn.style.borderColor  = '#10B981';
        labelIn.style.background   = '#ECFDF5';
        labelOut.style.borderColor = '#D1D5DB';
        labelOut.style.background  = 'white';
    };
    typeOut.onchange = () => {
        document.getElementById('editAmountLabel').textContent = 'Cash Out Amount *';
        labelOut.style.borderColor = '#EF4444';
        labelOut.style.background  = '#FEF2F2';
        labelIn.style.borderColor  = '#D1D5DB';
        labelIn.style.background   = 'white';
    };

    // Set selects after modal is shown
    setTimeout(() => {
        // Mode select
        const modeSelect = document.getElementById('editMode');
        modeSelect.value = entry.mode || 'cash';
        if (modeSelect.value !== entry.mode) {
            Array.from(modeSelect.options).forEach(opt => { opt.selected = (opt.value === entry.mode); });
        }

        // Category select
        const catSelect = document.getElementById('editCategory');
        const catId = entry.category_id ? String(entry.category_id) : '';
        catSelect.value = catId;
        if (catId && catSelect.value !== catId) {
            Array.from(catSelect.options).forEach(opt => { opt.selected = (opt.value === catId); });
        }
    }, 50);
}

function closeEditModal() {
    document.getElementById('editEntryModal').classList.remove('show');
    document.getElementById('editEntryAlert').innerHTML = '';
}

async function handleEditEntry(e) {
    e.preventDefault();

    const form     = e.target;
    const alertDiv = document.getElementById('editEntryAlert');
    const entryId  = document.getElementById('editEntryId').value;

    // Read transaction type
    const typeRadio = form.querySelector('input[name="edit_transaction_type"]:checked');
    if (!typeRadio) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please select Cash In or Cash Out.</div>';
        return;
    }

    const amount = parseFloat(document.getElementById('editAmount').value || 0);
    if (!amount || amount <= 0) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please enter a valid amount greater than 0.</div>';
        return;
    }

    const cashIn  = typeRadio.value === 'cash_in'  ? amount : 0;
    const cashOut = typeRadio.value === 'cash_out' ? amount : 0;

    // Get raw time value and strip seconds if present
    const rawTime = document.getElementById('editEntryTime').value || '';
    const entryTime = rawTime ? rawTime.substring(0, 5) : null;

    const formData = new FormData(form);
    const payload = {
        entry_date:       formData.get('entry_date'),
        entry_time:       entryTime,
        party:            formData.get('party') || null,
        mode:             formData.get('mode'),
        category_id:      formData.get('category_id') || null,
        reference_number: formData.get('reference_number') || null,
        remark:           formData.get('remark') || null,
        cash_in:          cashIn,
        cash_out:         cashOut,
        notes:            formData.get('notes') || null,
        _method:          'PUT',
    };

    alertDiv.innerHTML = '<div class="alert" style="background:#E0F2FE;color:#0369A1;border:1px solid #7DD3FC;">Saving changes...</div>';

    try {
        const response = await fetch(`/admin/cash-book/${entryId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">Entry updated successfully!</div>';
            loadEntries();
            loadStats();
            setTimeout(() => closeEditModal(), 1200);
        } else {
            // Show field-level errors if present
            let msg = data.message;
            if (data.errors) {
                const errs = Object.values(data.errors).flat();
                msg += '<ul style="margin:5px 0 0 15px;">' + errs.map(e => `<li>${e}</li>`).join('') + '</ul>';
            }
            alertDiv.innerHTML = `<div class="alert alert-danger">${msg}</div>`;
        }
    } catch (error) {
        console.error('Error updating entry:', error);
        alertDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
    }
}

// ─── Delete Entry ──────────────────────────────────────────────
function confirmDelete(entryId) {
    if (!isAdmin) return;
    document.getElementById('deleteEntryId').value = entryId;
    document.getElementById('deleteAlert').innerHTML = '';
    document.getElementById('deleteConfirmModal').classList.add('show');
}

function closeDeleteModal() {
    document.getElementById('deleteConfirmModal').classList.remove('show');
    document.getElementById('deleteAlert').innerHTML = '';
}

async function executeDelete() {
    const entryId  = document.getElementById('deleteEntryId').value;
    const alertDiv = document.getElementById('deleteAlert');

    alertDiv.innerHTML = '<div class="alert" style="background:#E0F2FE;color:#0369A1;border:1px solid #7DD3FC;">Deleting...</div>';

    try {
        const response = await fetch(`/admin/cash-book/${entryId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ _method: 'DELETE' }),
        });

        const data = await response.json();

        if (data.success) {
            alertDiv.innerHTML = '<div class="alert alert-success">Entry deleted successfully!</div>';
            loadEntries();
            loadStats();
            setTimeout(() => closeDeleteModal(), 1000);
        } else {
            alertDiv.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
        }
    } catch (error) {
        console.error('Error deleting entry:', error);
        alertDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
    }
}
</script>
@endpush
