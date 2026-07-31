@extends('admin.layout')

@section('title', 'Create ' . ucfirst($type) . ' - Admin Panel')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-plus-circle"></i> Create {{ ucfirst(str_replace('_', ' ', $type)) }}</h2>
        <small id="autosaveStatus" class="text-muted" style="display: none;">
            <i class="bi bi-clock-history"></i> <span id="autosaveText">Not saved</span>
        </small>
    </div>
    <a href="{{ route('admin.invoices-quotations.index') }}" onclick="history.back(); return false;" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<div class="row">
    <!-- Form Section -->
    <div class="col-lg-8">
        <form id="documentForm">
            @csrf
            <input type="hidden" name="document_type" value="{{ $type }}">

            <!-- Customer Information -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person"></i> Customer Information
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Search Existing Customer</label>
                        <input type="text" id="userSearch" class="form-control" placeholder="Type customer name, email, or phone...">
                        <input type="hidden" name="user_id" id="userId">
                        <div id="userResults" class="list-group mt-2" style="display: none;"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" name="customer_name" id="customerName" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" id="customerEmail" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="customer_phone" id="customerPhone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Currency *</label>
                            <select name="currency_code" id="currencyCode" class="form-select" required>
                                <option value="">Select Currency</option>
                                @foreach($currencies as $curr)
                                    <option value="{{ $curr['code'] }}"
                                            data-country="{{ $curr['country'] ?? '' }}"
                                            {{ $curr['code'] === $defaultCurrency ? 'selected' : '' }}>
                                        {{ $curr['code'] }} - {{ $curr['name'] }} ({{ $curr['symbol'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="customer_address" id="customerAddress" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box"></i> Products/Services</span>
                    <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#productSearchModal">
                        <i class="bi bi-search"></i> Search Products
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="itemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Image</th>
                                    <th>Product/Description</th>
                                    <th style="width: 100px;">Qty</th>
                                    <th style="width: 120px;">Unit Price</th>
                                    <th style="width: 120px;">Subtotal</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Items will be added here dynamically -->
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addManualItem()">
                        <i class="bi bi-plus"></i> Add Manual Item
                    </button>
                </div>
            </div>

            <!-- Pricing & Discounts -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-calculator"></i> Pricing & Discounts
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Discount Type</label>
                            <select name="discount_type" id="discountType" class="form-select" onchange="calculateTotals()">
                                <option value="amount">Fixed Amount</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Discount Value</label>
                            <input type="number" name="discount_value" id="discountValue" class="form-control" step="0.01" value="0" oninput="calculateTotals()">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="include_vat" id="includeVat" class="form-check-input" checked onchange="calculateTotals()">
                        <label class="form-check-label" for="includeVat">Include VAT</label>
                    </div>

                    <div class="mb-3" id="vatPercentageDiv">
                        <label class="form-label">VAT Percentage (%)</label>
                        <input type="number" name="vat_percentage" id="vatPercentage" class="form-control" step="0.01" value="{{ config('tax.default_rate', 15) }}" oninput="calculateTotals()">
                    </div>
                </div>
            </div>

            <!-- Shipping Fee (Based on Rules) -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-box-seam"></i> Shipping Fee (Based on Rules)
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> Shipping fee is calculated based on product weight/price rules. You can override or remove it.
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="include_shipping_fee" id="includeShippingFee" class="form-check-input" checked>
                        <label class="form-check-label" for="includeShippingFee">
                            <strong>Include Shipping Fee (Rule-Based)</strong>
                        </label>
                    </div>

                    <div id="shippingFeeDiv">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Rule Type</label>
                                <select name="shipping_rule_type" id="shippingRuleType" class="form-select" onchange="calculateShippingFee()">
                                    <option value="">Select Rule Type</option>
                                    <option value="base_on_weight">Based on Weight</option>
                                    <option value="base_on_price" selected>Based on Price</option>
                                </select>
                                <small class="text-muted">How shipping is calculated</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Shipping Fee Amount</label>
                                <input type="number" name="shipping_total" id="shippingTotal" class="form-control"
                                       step="0.01" value="0" oninput="calculateTotals()">
                                <small class="text-muted">Auto-calculated based on rules (editable)</small>
                            </div>
                        </div>

                        <div id="shippingRuleInfo" class="alert alert-secondary" style="display: none;">
                            <strong>Applied Rule:</strong>
                            <span id="shippingRuleText">No rule applied</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping & Delivery -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-truck"></i> Shipping & Delivery Options
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label"><strong>Select Delivery Method</strong></label>
                        <select name="delivery_method" id="deliveryMethod" class="form-select" onchange="updateShippingFee()">
                            <option value="">-- No Shipping --</option>
                            @foreach($shippingOptions as $option)
                                <option value="{{ $option['title'] }}"
                                        data-title="{{ $option['title'] }}"
                                        data-description="{{ $option['description'] }}"
                                        data-price="{{ $option['price'] }}">
                                    {{ $option['title'] }}
                                    @if($option['price'] > 0)
                                        - ${{ number_format($option['price'], 2) }}
                                    @else
                                        - Free
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Choose how the products will be delivered to the customer</small>
                    </div>

                    <div id="deliveryDetailsDiv" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Delivery Description</label>
                                <input type="text" name="delivery_description" id="deliveryDescription" class="form-control"
                                       placeholder="Auto-filled based on selected method" readonly>
                                <small class="text-muted">Auto-filled from selected shipping option</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Shipping Fee</label>
                                <input type="number" name="delivery_price" id="deliveryPrice" class="form-control"
                                       step="0.01" value="0" oninput="calculateTotals()">
                                <small class="text-muted">Adjust shipping cost if needed</small>
                            </div>
                        </div>

                        <div class="row" id="deliveryIntervalDiv" style="display: none;">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Delivery Time Interval (Optional)</label>
                                <input type="text" name="delivery_interval" id="deliveryInterval" class="form-control"
                                       placeholder="e.g., 9:00 AM - 5:00 PM">
                                <small class="text-muted">Specific delivery time window</small>
                            </div>
                        </div>

                        <!-- Collection Point Selection -->
                        <div id="collectionPointDiv" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Select Collection Point</label>
                                <select name="collection_point" id="collectionPoint" class="form-select">
                                    <option value="">-- Select Collection Point --</option>
                                    @foreach($collectionPoints as $point)
                                        <option value="{{ $point['id'] ?? $point['name'] }}">
                                            {{ $point['name'] }}
                                            @if(isset($point['address']))
                                                - {{ $point['address'] }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Customer will collect from selected location</small>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong>
                            <span id="deliveryNote">Delivery fee will be added to the final total.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-info-circle"></i> Additional Information
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Issue Date *</label>
                            <input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        @if(in_array($type, ['invoice', 'proforma']))
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        @endif
                        @if($type == 'quotation')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valid Until</label>
                            <input type="date" name="valid_until" class="form-control">
                        </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea name="terms_conditions" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Create Document
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="previewDocument()">
                    <i class="bi bi-eye"></i> Preview
                </button>
            </div>
        </form>
    </div>

    <!-- Preview/Summary Section -->
    <div class="col-lg-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-success text-white">
                <i class="bi bi-calculator"></i> Summary
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span id="summarySubtotal" class="fw-bold">0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Discount:</span>
                    <span id="summaryDiscount" class="fw-bold text-danger">0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>VAT:</span>
                    <span id="summaryVat" class="fw-bold">0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-2" id="summaryShippingFeeRow" style="display: none;">
                    <span>
                        <i class="bi bi-box-seam me-1"></i>
                        Shipping Fee:
                    </span>
                    <span id="summaryShippingFee" class="fw-bold text-primary">0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-2" id="summaryDeliveryRow" style="display: none;">
                    <span>
                        <i class="bi bi-truck me-1"></i>
                        <span id="summaryDeliveryLabel">Delivery Fee:</span>
                    </span>
                    <span id="summaryDeliveryFee" class="fw-bold text-info">0.00</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="h5 mb-0">Total:</span>
                    <span id="summaryTotal" class="h5 mb-0 fw-bold text-success">0.00</span>
                </div>

                <div class="mt-4">
                    <small class="text-muted">Items: <span id="itemCount">0</span></small>
                    <div id="summaryDeliveryMethod" style="display: none;" class="mt-2">
                        <small class="text-muted">
                            <i class="bi bi-truck me-1"></i>
                            <span id="deliveryMethodText">No delivery method selected</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Search Modal -->
<div class="modal fade" id="productSearchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Search Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="productSearchInput" class="form-control" placeholder="Type product name or SKU...">
                </div>
                <div id="productSearchResults" class="row g-3">
                    <!-- Product results will appear here -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ============================================================================
// AUTO-SAVE FEATURE
// ============================================================================
// This form automatically saves drafts as users work to prevent data loss.
//
// How it works:
// 1. User fills customer name + currency (required)
// 2. User adds first item → Auto-save triggered
// 3. System waits 3 seconds after last change before saving
// 4. Saves with 'autosave' status (temporary draft)
// 5. On page reload, offers to restore the autosave
// 6. When user clicks "Create Document", converts autosave to 'draft' status
//
// Benefits:
// - No data loss on accidental page close
// - Can resume work from any device
// - Creator automatically tracked
// ============================================================================

const documentType = '{{ $type }}';
let items = [];
let itemCounter = 0;
let autosaveId = null;
let autosaveTimeout = null;
let lastAutosaveTime = null;
let isCustomerInfoFilled = false;
let isRestoring = false; // Flag to prevent auto-save during restore

// User search
let userSearchTimeout;
document.getElementById('userSearch').addEventListener('input', function() {
    clearTimeout(userSearchTimeout);
    const query = this.value;

    if (query.length < 2) {
        document.getElementById('userResults').style.display = 'none';
        return;
    }

    userSearchTimeout = setTimeout(() => {
        fetch(`{{ route('admin.invoices-quotations.search-users') }}?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(users => {
                const resultsDiv = document.getElementById('userResults');
                if (users.length === 0) {
                    resultsDiv.innerHTML = '<div class="list-group-item">No users found</div>';
                } else {
                    resultsDiv.innerHTML = users.map(user => `
                        <button type="button" class="list-group-item list-group-item-action" onclick="selectUser(${JSON.stringify(user).replace(/"/g, '&quot;')})">
                            <div><strong>${user.name}</strong></div>
                            <small class="text-muted">${user.email} ${user.phone ? '| ' + user.phone : ''}</small>
                        </button>
                    `).join('');
                }
                resultsDiv.style.display = 'block';
            });
    }, 300);
});

function selectUser(user) {
    document.getElementById('userId').value = user.id;
    document.getElementById('customerName').value = user.name;
    document.getElementById('customerEmail').value = user.email || '';
    document.getElementById('customerPhone').value = user.phone || '';
    document.getElementById('userSearch').value = user.name;
    document.getElementById('userResults').style.display = 'none';

    // Handle addresses
    if (user.addresses && user.addresses.length > 0) {
        if (user.addresses.length === 1) {
            // Single address - auto-fill
            document.getElementById('customerAddress').value = user.addresses[0].address || '';
        } else {
            // Multiple addresses - show selection
            showAddressSelection(user.addresses);
        }
    } else {
        document.getElementById('customerAddress').value = '';
    }
}

function showAddressSelection(addresses) {
    const addressField = document.getElementById('customerAddress');
    const addressContainer = addressField.parentElement;

    // Create address selection dropdown
    const selectHtml = `
        <div id="addressSelectionDiv">
            <label class="form-label">Select Address</label>
            <select class="form-select mb-2" id="addressSelect" onchange="fillSelectedAddress()">
                <option value="">-- Choose an address --</option>
                ${addresses.map((addr, idx) => `
                    <option value="${idx}">${addr.address}</option>
                `).join('')}
            </select>
        </div>
    `;

    // Remove existing address selection if any
    const existingDiv = document.getElementById('addressSelectionDiv');
    if (existingDiv) {
        existingDiv.remove();
    }

    // Insert before the textarea
    addressField.insertAdjacentHTML('beforebegin', selectHtml);

    // Store addresses globally for selection
    window.selectedUserAddresses = addresses;
}

function fillSelectedAddress() {
    const selectEl = document.getElementById('addressSelect');
    const selectedIdx = selectEl.value;

    if (selectedIdx !== '' && window.selectedUserAddresses) {
        const address = window.selectedUserAddresses[selectedIdx];
        document.getElementById('customerAddress').value = address.address;
    }
}

function addManualItem() {
    addItem({
        product_id: null,
        variation_id: null,
        product_name: '',
        sku: '',
        image_url: null,
        quantity: 1,
        unit_price: 0,
        is_manual: true // Mark as manual entry for editable fields
    });
}

function addItem(itemData) {
    const id = itemCounter++;
    items.push({...itemData, id});
    renderItems();
    calculateTotals();

    // Trigger auto-save when first item is added (if customer info is filled and not restoring)
    if (!isRestoring && checkCustomerInfo()) {
        triggerAutoSave();
    }
}

function removeItem(id) {
    items = items.filter(item => item.id !== id);
    renderItems();
    calculateTotals();

    // Trigger auto-save on item removal (if not restoring)
    if (!isRestoring && checkCustomerInfo() && items.length > 0) {
        triggerAutoSave();
    }
}

function updateItemSubtotalCell(itemId) {
    const item = items.find(i => i.id === itemId);
    if (!item) return;
    const subtotal = (item.quantity * item.unit_price).toFixed(2);
    const input = document.querySelector(`.item-quantity-input[data-item-id="${itemId}"]`);
    if (input) {
        const row = input.closest('tr');
        const cell = row && row.querySelector('.subtotal-cell strong');
        if (cell) cell.textContent = subtotal;
    }
}

function updateItemQuantity(id, newQuantity) {
    const item = items.find(i => i.id === id);
    if (item) {
        item.quantity = parseFloat(newQuantity) || 0;
        updateItemSubtotalCell(id);
        calculateTotals();

        // Trigger auto-save on quantity change (if not restoring)
        if (!isRestoring && checkCustomerInfo() && items.length > 0) {
            triggerAutoSave();
        }
    }
}

function updateItemPrice(id, newPrice) {
    const item = items.find(i => i.id === id);
    if (item) {
        item.unit_price = parseFloat(newPrice) || 0;
        updateItemSubtotalCell(id);
        calculateTotals();

        // Trigger auto-save on price change (if not restoring)
        if (!isRestoring && checkCustomerInfo() && items.length > 0) {
            triggerAutoSave();
        }
    }
}

function renderItems() {
    const tbody = document.getElementById('itemsTableBody');
    tbody.innerHTML = items.map((item, index) => {
        const isManual = item.is_manual === true;

        return `
        <tr>
            <td>
                <img src="${item.image_url || '/assets/images/placeholder.png'}"
                     alt="${item.product_name}"
                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm"
                       value="${item.product_name}"
                       onchange="items[${index}].product_name = this.value"
                       placeholder="Product name" required>
                <input type="text" class="form-control form-control-sm mt-1"
                       value="${item.sku || ''}"
                       ${isManual ? `onchange="items[${index}].sku = this.value"` : ''}
                       placeholder="SKU" ${isManual ? '' : 'disabled'}>
                <input type="hidden" name="items[${index}][product_id]" value="${item.product_id || ''}">
                <input type="hidden" name="items[${index}][variation_id]" value="${item.variation_id || ''}">
                <input type="hidden" name="items[${index}][product_name]" value="${item.product_name}">
                <input type="hidden" name="items[${index}][sku]" value="${item.sku || ''}">
                <input type="hidden" name="items[${index}][image_url]" value="${item.image_url || ''}">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm item-quantity-input"
                       name="items[${index}][quantity]"
                       data-item-id="${item.id}"
                       value="${item.quantity}"
                       step="1" min="1"
                       oninput="updateItemQuantity(${item.id}, this.value)" required>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm item-price-input"
                       name="items[${index}][unit_price]"
                       data-item-id="${item.id}"
                       value="${item.unit_price}"
                       step="0.01" min="0"
                       ${(isManual || documentType === 'receipt') ? `oninput="updateItemPrice(${item.id}, this.value)"` : 'disabled'}>
            </td>
            <td class="subtotal-cell">
                <strong>${(item.quantity * item.unit_price).toFixed(2)}</strong>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${item.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `}).join('');

    document.getElementById('itemCount').textContent = items.length;
}

function calculateTotals(skipShippingRecalc = false) {
    const subtotal = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);

    const discountType = document.getElementById('discountType').value;
    const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;

    let discountAmount = 0;
    if (discountType === 'percentage') {
        discountAmount = (subtotal * discountValue) / 100;
    } else {
        discountAmount = discountValue;
    }

    const amountAfterDiscount = subtotal - discountAmount;

    const includeVat = document.getElementById('includeVat').checked;
    const vatPercentage = parseFloat(document.getElementById('vatPercentage').value) || 0;

    // Auto-recalculate shipping fee if checkbox is checked AND rule type is selected
    if (!skipShippingRecalc) {
        const includeShippingFeeCheckbox = document.getElementById('includeShippingFee');
        const shippingRuleType = document.getElementById('shippingRuleType');

        // Only recalculate if checkbox is CHECKED and rule type is selected
        if (includeShippingFeeCheckbox && includeShippingFeeCheckbox.checked &&
            shippingRuleType && shippingRuleType.value) {
            // Recalculate shipping fee based on current subtotal
            calculateShippingFeeOnly(); // Calculate but don't call calculateTotals
            // Continue to update summary below
        }
    }

    // Calculate shipping fee (rule-based)
    const includeShippingFee = document.getElementById('includeShippingFee')?.checked || false;
    const shippingFee = includeShippingFee ? (parseFloat(document.getElementById('shippingTotal')?.value) || 0) : 0;

    // Calculate delivery fee (collection/delivery method)
    const deliveryFee = parseFloat(document.getElementById('deliveryPrice')?.value) || 0;

    // VAT applies to the full taxable amount: products (after discount) + shipping + delivery
    let vatAmount = 0;
    if (includeVat) {
        const vatBase = amountAfterDiscount + shippingFee + deliveryFee;
        vatAmount = (vatBase * vatPercentage) / 100;
    }

    const total = amountAfterDiscount + shippingFee + deliveryFee + vatAmount;

    const currencyCode = document.getElementById('currencyCode').value || '';

    document.getElementById('summarySubtotal').textContent = `${currencyCode} ${subtotal.toFixed(2)}`;
    document.getElementById('summaryDiscount').textContent = `${currencyCode} ${discountAmount.toFixed(2)}`;
    document.getElementById('summaryVat').textContent = `${currencyCode} ${vatAmount.toFixed(2)}`;
    document.getElementById('itemCount').textContent = items.length;

    // Show/hide and update shipping fee in summary
    const shippingFeeRow = document.getElementById('summaryShippingFeeRow');
    if (includeShippingFee) {
        // Show row if checkbox is checked, even if $0 (shows "Free Shipping")
        shippingFeeRow.style.display = 'flex';
        document.getElementById('summaryShippingFee').textContent = `${currencyCode} ${shippingFee.toFixed(2)}`;
    } else {
        // Checkbox unchecked - update to $0 before hiding
        document.getElementById('summaryShippingFee').textContent = `${currencyCode} 0.00`;
        shippingFeeRow.style.display = 'none';
    }

    // Show/hide and update delivery fee in summary
    const deliveryRow = document.getElementById('summaryDeliveryRow');
    const deliveryMethodSelect = document.getElementById('deliveryMethod');

    if (deliveryMethodSelect && deliveryMethodSelect.value && deliveryFee > 0) {
        deliveryRow.style.display = 'flex';
        document.getElementById('summaryDeliveryFee').textContent = `${currencyCode} ${deliveryFee.toFixed(2)}`;

        // Update delivery method text
        const methodText = deliveryMethodSelect.options[deliveryMethodSelect.selectedIndex].text;
        document.getElementById('summaryDeliveryMethod').style.display = 'block';
        document.getElementById('deliveryMethodText').textContent = methodText;
    } else {
        deliveryRow.style.display = 'none';
        document.getElementById('summaryDeliveryMethod').style.display = 'none';
    }

    document.getElementById('summaryTotal').textContent = `${currencyCode} ${total.toFixed(2)}`;

    document.getElementById('vatPercentageDiv').style.display = includeVat ? 'block' : 'none';
}

// Toggle shipping fee section
function toggleShippingFee() {
    const includeShippingFee = document.getElementById('includeShippingFee').checked;
    const shippingFeeDiv = document.getElementById('shippingFeeDiv');
    const shippingRuleType = document.getElementById('shippingRuleType');
    const shippingTotal = document.getElementById('shippingTotal');
    const shippingRuleInfo = document.getElementById('shippingRuleInfo');

    shippingFeeDiv.style.display = includeShippingFee ? 'block' : 'none';

    if (!includeShippingFee) {
        // Unchecked - clear everything and update summary
        shippingTotal.value = 0;
        shippingRuleInfo.style.display = 'none';
        shippingRuleType.value = '';
        calculateTotals(true); // Update summary to show $0
    } else {
        // Checked - auto-select "base_on_price" if not selected, then calculate
        if (!shippingRuleType.value) {
            shippingRuleType.value = 'base_on_price';
        }
        calculateShippingFee(); // This will calculate and update summary
    }
}

// Calculate shipping fee only (without calling calculateTotals)
function calculateShippingFeeOnly() {
    const ruleType = document.getElementById('shippingRuleType').value;
    const subtotal = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);

    if (!ruleType) {
        document.getElementById('shippingTotal').value = 0;
        document.getElementById('shippingRuleInfo').style.display = 'none';
        return;
    }

    // Convert subtotal to USD for comparison with shipping rules
    // Shipping rules min/max are always in USD in database
    const selectedCurrency = document.getElementById('currencyCode').value || 'USD';
    let subtotalUSD = subtotal;

    if (selectedCurrency !== 'USD' && exchangeRates[selectedCurrency]) {
        const targetRate = exchangeRates[selectedCurrency].rate;
        const baseRate = exchangeRates['USD']?.rate || 1.0;
        // Convert from selected currency back to USD
        subtotalUSD = (subtotal * baseRate) / targetRate;
    }

    // Find matching rule from database
    let matchedRule = null;
    let shippingFee = 0;
    let ruleText = '';

    if (ruleType === 'base_on_price') {
        // Sort rules by min (ascending) to find the best match
        const sortedRules = shippingRules
            .filter(rule => rule.rule_type === 'base_on_price')
            .sort((a, b) => a.min - b.min);

        // Find the matching rule
        // Rules should be checked: if subtotal >= min AND subtotal <= max, apply that rule
        matchedRule = sortedRules.find(rule => {
            return subtotalUSD >= rule.min && subtotalUSD <= rule.max;
        });

        if (matchedRule) {
            shippingFee = matchedRule.amount;
            ruleText = `${matchedRule.name}: $${matchedRule.min.toFixed(2)} - $${matchedRule.max.toFixed(2)} = $${matchedRule.amount.toFixed(2)} shipping`;
        } else {
            // No rule matched - check if subtotal is below the lowest rule or above the highest
            const lowestRule = sortedRules[0];
            const highestRule = sortedRules[sortedRules.length - 1];

            if (lowestRule && subtotalUSD < lowestRule.min) {
                shippingFee = 0;
                ruleText = `Order below $${lowestRule.min.toFixed(2)} minimum: No shipping fee`;
            } else if (highestRule && subtotalUSD > highestRule.max) {
                // Check if there's a rule that applies to amounts above this
                // If not, no shipping (or you could set a default)
                shippingFee = 0;
                ruleText = `Order above $${highestRule.max.toFixed(2)}: No matching rule (no shipping fee)`;
            } else {
                shippingFee = 0;
                ruleText = 'No matching shipping rule found';
            }
        }
    } else if (ruleType === 'base_on_weight') {
        // TODO: Calculate total weight from products
        // For now, look for weight-based rules
        matchedRule = shippingRules.find(rule => rule.rule_type === 'base_on_weight');

        if (matchedRule) {
            shippingFee = matchedRule.amount;
            ruleText = `${matchedRule.name}: Weight-based shipping = $${matchedRule.amount}`;
        } else {
            shippingFee = 0;
            ruleText = 'No weight-based shipping rules configured';
        }
    }

    // Convert shipping fee from USD to selected currency for display
    let shippingFeeConverted = shippingFee;
    if (selectedCurrency !== 'USD' && exchangeRates[selectedCurrency]) {
        const targetRate = exchangeRates[selectedCurrency].rate;
        const baseRate = exchangeRates['USD']?.rate || 1.0;
        shippingFeeConverted = (shippingFee / baseRate) * targetRate;
    }

    document.getElementById('shippingTotal').value = shippingFeeConverted.toFixed(2);
    document.getElementById('shippingRuleInfo').style.display = 'block';
    document.getElementById('shippingRuleText').textContent = ruleText;
    // DON'T call calculateTotals here - let the caller do it
}

// Calculate shipping fee based on rules (price-based or weight-based)
function calculateShippingFee() {
    calculateShippingFeeOnly();
    calculateTotals(true); // Skip shipping recalc to prevent loop
}

// Update shipping fee based on delivery method
function updateShippingFee() {
    const deliveryMethodSelect = document.getElementById('deliveryMethod');
    const selectedIndex = deliveryMethodSelect.value;
    const deliveryDetailsDiv = document.getElementById('deliveryDetailsDiv');
    const deliveryPriceInput = document.getElementById('deliveryPrice');
    const deliveryDescInput = document.getElementById('deliveryDescription');
    const collectionPointDiv = document.getElementById('collectionPointDiv');
    const deliveryIntervalDiv = document.getElementById('deliveryIntervalDiv');
    const deliveryNote = document.getElementById('deliveryNote');

    if (!selectedIndex || selectedIndex === '') {
        deliveryDetailsDiv.style.display = 'none';
        deliveryPriceInput.value = 0;
        calculateTotals(true); // Skip shipping recalc, just update totals
        return;
    }

    // Get data from selected option
    const selectedOption = deliveryMethodSelect.options[deliveryMethodSelect.selectedIndex];
    const title = selectedOption.getAttribute('data-title');
    const description = selectedOption.getAttribute('data-description');
    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;

    deliveryDetailsDiv.style.display = 'block';
    collectionPointDiv.style.display = 'none';
    deliveryIntervalDiv.style.display = 'none';

    // Set values from selected shipping option
    deliveryDescInput.value = `${title} | ${description}`;
    deliveryPriceInput.value = price.toFixed(2);

    // Check if it's a pickup/collection option
    const titleLower = title.toLowerCase();
    if (titleLower.includes('pickup') || titleLower.includes('collect')) {
        collectionPointDiv.style.display = 'block';
        deliveryNote.textContent = 'Customer will collect from selected store/collection point.';
    } else if (titleLower.includes('local') || titleLower.includes('same day')) {
        deliveryIntervalDiv.style.display = 'block';
        deliveryNote.textContent = 'Local delivery - You can specify a delivery time window.';
    } else {
        deliveryNote.textContent = `${title} - ${description}`;
    }

    calculateTotals(true); // Skip shipping recalc, just update totals with new delivery price
}

// Preview document function
function previewDocument() {
    if (items.length === 0) {
        showWarning('Warning', 'Please add at least one item to preview the document');
        return;
    }

    // Get form data
    const customerName = document.getElementById('customerName').value;
    const customerEmail = document.getElementById('customerEmail').value;
    const customerPhone = document.getElementById('customerPhone').value;
    const customerAddress = document.getElementById('customerAddress').value;
    const currencyCode = document.getElementById('currencyCode').value;
    const discountType = document.getElementById('discountType').value;
    const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
    const includeVat = document.getElementById('includeVat').checked;
    const vatPercentage = parseFloat(document.getElementById('vatPercentage').value) || 0;

    if (!customerName || !currencyCode) {
        showWarning('Warning', 'Please fill in customer name and currency before previewing');
        return;
    }

    // Calculate totals
    const subtotal = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);

    let discountAmount = 0;
    if (discountType === 'percentage') {
        discountAmount = (subtotal * discountValue) / 100;
    } else {
        discountAmount = discountValue;
    }

    const amountAfterDiscount = subtotal - discountAmount;

    const previewShippingFee = document.getElementById('includeShippingFee')?.checked
        ? (parseFloat(document.getElementById('shippingTotal')?.value) || 0) : 0;
    const previewDeliveryFee = parseFloat(document.getElementById('deliveryPrice')?.value) || 0;

    let vatAmount = 0;
    if (includeVat) {
        const vatBase = amountAfterDiscount + previewShippingFee + previewDeliveryFee;
        vatAmount = (vatBase * vatPercentage) / 100;
    }

    const total = amountAfterDiscount + previewShippingFee + previewDeliveryFee + vatAmount;

    // Build preview HTML
    const previewHTML = `
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">
            <div style="background: linear-gradient(135deg, #11529c 0%, #0d3e75 100%); color: white; padding: 30px; border-bottom: 5px solid #e70810;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <img src="https://media.raines.africa/storage/uploads/2024/11/21/20/59/5e36d2aa-4258-4b6a-90c6-95e2ee80c3e9.png" alt="Raines Logo" style="height: 60px; margin-bottom: 10px;">
                        <h1 style="margin: 0; font-size: 32px;">RAINES</h1>
                        <p style="margin: 5px 0 0; font-size: 14px; opacity: 0.9;">Raines Technologies (PTY) LTD</p>
                    </div>
                    <div style="text-align: right;">
                        <h2 style="margin: 0; font-size: 36px; letter-spacing: 2px;">${'{{ ucfirst($type) }}'.toUpperCase()}</h2>
                        <p style="margin: 5px 0 0; font-size: 16px; opacity: 0.9;">PREVIEW</p>
                    </div>
                </div>
            </div>

            <div style="padding: 30px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                    <div>
                        <h3 style="color: #11529c; border-bottom: 2px solid #e70810; padding-bottom: 5px;">BILL TO</h3>
                        <p style="margin: 10px 0;"><strong>${customerName}</strong></p>
                        ${customerEmail ? `<p style="margin: 5px 0;">${customerEmail}</p>` : ''}
                        ${customerPhone ? `<p style="margin: 5px 0;">${customerPhone}</p>` : ''}
                        ${customerAddress ? `<p style="margin: 5px 0; color: #666;">${customerAddress}</p>` : ''}
                    </div>
                    <div>
                        <h3 style="color: #11529c; border-bottom: 2px solid #e70810; padding-bottom: 5px;">DETAILS</h3>
                        <p style="margin: 10px 0;"><strong>Currency:</strong> ${currencyCode}</p>
                        <p style="margin: 5px 0;"><strong>Date:</strong> ${new Date().toLocaleDateString()}</p>
                        <p style="margin: 5px 0;"><strong>Items:</strong> ${items.length}</p>
                    </div>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <thead>
                        <tr style="background: #11529c; color: white;">
                            <th style="padding: 12px; text-align: left; border-bottom: 3px solid #e70810;">Product</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 3px solid #e70810;">Qty</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 3px solid #e70810;">Unit Price</th>
                            <th style="padding: 12px; text-align: right; border-bottom: 3px solid #e70810;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map((item, idx) => `
                            <tr style="background: ${idx % 2 === 0 ? '#f9f9f9' : 'white'};">
                                <td style="padding: 10px; border-bottom: 1px solid #ddd;">${item.product_name}</td>
                                <td style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">${item.quantity}</td>
                                <td style="padding: 10px; text-align: right; border-bottom: 1px solid #ddd;">${currencyCode} ${item.unit_price.toFixed(2)}</td>
                                <td style="padding: 10px; text-align: right; border-bottom: 1px solid #ddd;"><strong>${currencyCode} ${(item.quantity * item.unit_price).toFixed(2)}</strong></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>

                <div style="float: right; width: 300px; margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd;">
                        <span>Subtotal:</span>
                        <strong>${currencyCode} ${subtotal.toFixed(2)}</strong>
                    </div>
                    ${discountAmount > 0 ? `
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd;">
                            <span>Discount ${discountType === 'percentage' ? '(' + discountValue + '%)' : ''}:</span>
                            <strong style="color: #e70810;">-${currencyCode} ${discountAmount.toFixed(2)}</strong>
                        </div>
                    ` : ''}
                    ${includeVat && vatAmount > 0 ? `
                        <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ddd;">
                            <span>VAT (${vatPercentage}%):</span>
                            <strong>${currencyCode} ${vatAmount.toFixed(2)}</strong>
                        </div>
                    ` : ''}
                    <div style="background: #11529c; color: white; display: flex; justify-content: space-between; padding: 15px; margin-top: 10px; border-radius: 5px; font-size: 18px;">
                        <span>TOTAL:</span>
                        <strong>${currencyCode} ${total.toFixed(2)}</strong>
                    </div>
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
    `;

    // Create and show modal
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Document Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    ${previewHTML}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" onclick="document.getElementById('documentForm').dispatchEvent(new Event('submit', {bubbles: true, cancelable: true})); bootstrap.Modal.getInstance(this.closest('.modal')).hide();">
                        <i class="bi bi-check-circle"></i> Create Document
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();

    // Remove modal from DOM after it's hidden
    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
}

// Auto-save functions
function checkCustomerInfo() {
    const customerName = document.getElementById('customerName').value.trim();
    const currencyCode = document.getElementById('currencyCode').value;

    isCustomerInfoFilled = customerName && currencyCode;
    return isCustomerInfoFilled;
}

function triggerAutoSave() {
    // Clear existing timeout
    if (autosaveTimeout) {
        clearTimeout(autosaveTimeout);
    }

    // Set new timeout for auto-save after 3 seconds of inactivity
    autosaveTimeout = setTimeout(() => {
        performAutoSave();
    }, 3000);
}

function performAutoSave() {
    // Only auto-save if customer info is filled and there's at least one item
    // Don't auto-save if we're currently restoring data
    if (isRestoring || !checkCustomerInfo() || items.length === 0) {
        return;
    }

    const formData = new FormData(document.getElementById('documentForm'));

    const data = {
        id: autosaveId,
        document_type: formData.get('document_type'),
        currency_code: formData.get('currency_code'),
        customer_name: formData.get('customer_name'),
        customer_email: formData.get('customer_email'),
        customer_phone: formData.get('customer_phone'),
        customer_address: formData.get('customer_address'),
        user_id: formData.get('user_id') || null,
        issue_date: formData.get('issue_date'),
        due_date: formData.get('due_date') || null,
        valid_until: formData.get('valid_until') || null,
        include_vat: formData.get('include_vat') === 'on',
        vat_percentage: formData.get('vat_percentage'),
        shipping_total: formData.get('shipping_total') || 0,
        delivery_method: formData.get('delivery_method') || null,
        delivery_description: formData.get('delivery_description') || null,
        delivery_price: formData.get('delivery_price') || 0,
        delivery_interval: formData.get('delivery_interval') || null,
        collection_point: formData.get('collection_point') || null,
        discount_type: formData.get('discount_type'),
        discount_value: formData.get('discount_value'),
        notes: formData.get('notes'),
        terms_conditions: formData.get('terms_conditions'),
        items: items.map(item => ({
            product_id: item.product_id,
            variation_id: item.variation_id,
            product_name: item.product_name,
            sku: item.sku,
            description: item.description,
            image_url: item.image_url,
            quantity: item.quantity,
            unit_price: item.unit_price
        }))
    };

    // Show saving indicator
    updateAutosaveStatus('Saving...', 'text-primary');

    fetch('{{ route('admin.invoices-quotations.autosave') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            autosaveId = result.document_id;
            lastAutosaveTime = new Date(result.autosave_time);
            updateAutosaveStatus('Auto-saved at ' + formatTime(lastAutosaveTime), 'text-success');
        } else {
            updateAutosaveStatus('Auto-save failed', 'text-danger');
        }
    })
    .catch(err => {
        console.error('Auto-save error:', err);
        updateAutosaveStatus('Auto-save failed', 'text-danger');
    });
}

function updateAutosaveStatus(text, className) {
    const statusEl = document.getElementById('autosaveStatus');
    const textEl = document.getElementById('autosaveText');

    statusEl.style.display = 'block';
    textEl.textContent = text;
    textEl.className = className;
}

function formatTime(date) {
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function loadAutosave() {
    // Check if we have a resume parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const resumeId = urlParams.get('resume');

    if (!resumeId) {
        // No resume parameter, don't load anything
        return;
    }

    const documentType = document.querySelector('input[name="document_type"]').value;

    fetch(`{{ route('admin.invoices-quotations.resume') }}?type=${documentType}`)
        .then(res => res.json())
        .then(result => {
            if (result.success && result.document) {
                const doc = result.document;

                // Verify this is the correct autosave (matching resumeId)
                if (doc.id != resumeId) {
                    console.warn('Autosave ID mismatch');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Draft Not Found',
                        text: 'The auto-saved draft could not be found.',
                    });
                    return;
                }

                // Show loading message
                Swal.fire({
                    title: 'Loading Draft...',
                    text: `Restoring ${doc.items.length} item(s)`,
                    icon: 'info',
                    showConfirmButton: false,
                    timer: 1500
                });

                // Automatically restore without prompting
                setTimeout(() => {
                    restoreAutosave(doc);
                }, 1500);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Draft Not Found',
                    text: 'The auto-saved draft could not be found or has been deleted.',
                });
            }
        })
        .catch(err => {
            console.error('Error loading autosave:', err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not load the auto-saved draft.',
            });
        });
}

function restoreAutosave(doc) {

    // Set flag to prevent auto-save during restoration
    isRestoring = true;

    try {
        // Restore form data
        autosaveId = doc.id;

        // Customer information
        const customerName = document.getElementById('customerName');
        const customerEmail = document.getElementById('customerEmail');
        const customerPhone = document.getElementById('customerPhone');
        const customerAddress = document.getElementById('customerAddress');
        const currencyCode = document.getElementById('currencyCode');
        const userId = document.getElementById('userId');

        if (customerName) customerName.value = doc.customer_name || '';
        if (customerEmail) customerEmail.value = doc.customer_email || '';
        if (customerPhone) customerPhone.value = doc.customer_phone || '';
        if (customerAddress) customerAddress.value = doc.customer_address || '';
        if (currencyCode) currencyCode.value = doc.currency_code || '';
        if (userId) userId.value = doc.user_id || '';

        // Date fields (use querySelector with name attribute)
        const issueDate = document.querySelector('input[name="issue_date"]');
        const dueDate = document.querySelector('input[name="due_date"]');
        const validUntil = document.querySelector('input[name="valid_until"]');

        if (doc.issue_date && issueDate) issueDate.value = doc.issue_date;
        if (doc.due_date && dueDate) dueDate.value = doc.due_date;
        if (doc.valid_until && validUntil) validUntil.value = doc.valid_until;

        // VAT and discount settings
        const includeVat = document.getElementById('includeVat');
        const vatPercentage = document.getElementById('vatPercentage');
        const discountType = document.getElementById('discountType');
        const discountValue = document.getElementById('discountValue');

        if (includeVat) includeVat.checked = doc.include_vat;
        if (vatPercentage) vatPercentage.value = doc.vat_percentage || 15;
        if (discountType) discountType.value = doc.discount_type || 'amount';
        if (discountValue) discountValue.value = doc.discount_value || 0;

        // Shipping fields
        const shippingTotal = document.getElementById('shippingTotal');
        const deliveryMethod = document.getElementById('deliveryMethod');
        const deliveryPrice = document.getElementById('deliveryPrice');

        if (doc.shipping_total && shippingTotal) shippingTotal.value = doc.shipping_total;
        if (doc.delivery_method && deliveryMethod) deliveryMethod.value = doc.delivery_method;
        if (doc.delivery_price && deliveryPrice) deliveryPrice.value = doc.delivery_price;

        // Notes and terms
        const notesField = document.querySelector('textarea[name="notes"]');
        const termsField = document.querySelector('textarea[name="terms_conditions"]');

        if (doc.notes && notesField) notesField.value = doc.notes;
        if (doc.terms_conditions && termsField) termsField.value = doc.terms_conditions;

        // Restore items - directly push without triggering addItem
        items = [];
        itemCounter = 0;

        if (doc.items && Array.isArray(doc.items) && doc.items.length > 0) {
            doc.items.forEach((item, index) => {

                const id = itemCounter++;
                items.push({
                    id: id,
                    product_id: item.product_id || null,
                    variation_id: item.variation_id || null,
                    product_name: item.product_name || '',
                    sku: item.sku || '',
                    description: item.description || '',
                    image_url: item.image_url || null,
                    quantity: parseFloat(item.quantity) || 1,
                    unit_price: parseFloat(item.unit_price) || 0,
                    is_manual: !item.product_id
                });
            });

            renderItems();
            calculateTotals();
        }

        isCustomerInfoFilled = true;
        lastAutosaveTime = new Date(doc.autosave_time);
        updateAutosaveStatus('Restored from ' + formatTime(lastAutosaveTime), 'text-info');

        // Show success message
        Swal.fire({
            icon: 'success',
            title: 'Draft Restored',
            text: `Successfully restored ${items.length} item(s)`,
            timer: 2000,
            showConfirmButton: false
        });

        // Re-enable auto-save after a short delay
        setTimeout(() => {
            isRestoring = false;
        }, 1000);

    } catch (error) {
        isRestoring = false;

        Swal.fire({
            icon: 'error',
            title: 'Restoration Failed',
            text: 'Could not restore the draft: ' + error.message,
        });
    }
}

function discardAutosave(documentId) {
    Swal.fire({
        title: 'Discard Draft?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, discard it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            // Delete the autosave
            fetch(`{{ url('admin/invoices-quotations') }}/${documentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(res => res.json())
            .then(result => {
                Swal.fire({
                    icon: 'success',
                    title: 'Discarded',
                    text: 'Auto-saved draft has been deleted.',
                    timer: 2000,
                    showConfirmButton: false
                });
            })
            .catch(err => {
                console.error('Error discarding autosave:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not discard the draft.',
                });
            });
        }
    });
}

// Form submission
document.getElementById('documentForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (items.length === 0) {
        showWarning('Warning', 'Please add at least one item');
        return;
    }

    const formData = new FormData(this);

    // Convert to JSON
    const data = {
        autosave_id: autosaveId,
        document_type: formData.get('document_type'),
        currency_code: formData.get('currency_code'),
        customer_name: formData.get('customer_name'),
        customer_email: formData.get('customer_email'),
        customer_phone: formData.get('customer_phone'),
        customer_address: formData.get('customer_address'),
        user_id: formData.get('user_id') || null,
        issue_date: formData.get('issue_date'),
        due_date: formData.get('due_date') || null,
        valid_until: formData.get('valid_until') || null,
        include_vat: formData.get('include_vat') === 'on',
        vat_percentage: formData.get('vat_percentage'),
        include_shipping_fee: formData.get('include_shipping_fee') === 'on',
        shipping_total: formData.get('shipping_total') || 0,
        shipping_rule_type: formData.get('shipping_rule_type') || null,
        delivery_method: formData.get('delivery_method') || null,
        delivery_description: formData.get('delivery_description') || null,
        delivery_price: formData.get('delivery_price') || 0,
        delivery_interval: formData.get('delivery_interval') || null,
        collection_point: formData.get('collection_point') || null,
        discount_type: formData.get('discount_type'),
        discount_value: formData.get('discount_value'),
        notes: formData.get('notes'),
        terms_conditions: formData.get('terms_conditions'),
        items: items.map(item => ({
            product_id: item.product_id,
            product_name: item.product_name,
            sku: item.sku,
            image_url: item.image_url,
            quantity: item.quantity,
            unit_price: item.unit_price
        }))
    };

    fetch('{{ route('admin.invoices-quotations.store') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            window.location.href = result.redirect;
        } else {
            showError('Error', result.message);
        }
    })
    .catch(err => {
        console.error(err);
        showError('Error', 'An error occurred while creating the document');
    });
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Load any existing autosave
    loadAutosave();

    // Calculate initial totals
    calculateTotals();

    // Add event listeners for auto-save
    const customerName = document.getElementById('customerName');
    const currencyCode = document.getElementById('currencyCode');
    const customerEmail = document.getElementById('customerEmail');
    const customerPhone = document.getElementById('customerPhone');
    const customerAddress = document.getElementById('customerAddress');

    // Customer info change triggers auto-save check
    [customerName, currencyCode, customerEmail, customerPhone, customerAddress].forEach(el => {
        if (el) {
            el.addEventListener('input', () => {
                if (checkCustomerInfo() && items.length > 0) {
                    triggerAutoSave();
                }
            });
            el.addEventListener('change', () => {
                if (checkCustomerInfo() && items.length > 0) {
                    triggerAutoSave();
                }
            });
        }
    });

    // Other form fields trigger auto-save if customer info is filled
    const autoSaveFields = [
        'issueDate', 'dueDate', 'validUntil', 'includeVat', 'vatPercentage',
        'discountType', 'discountValue', 'shippingTotal', 'deliveryMethod',
        'deliveryPrice', 'notes', 'terms_conditions'
    ];

    autoSaveFields.forEach(fieldId => {
        const field = document.getElementById(fieldId) || document.querySelector(`[name="${fieldId}"]`);
        if (field) {
            field.addEventListener('change', () => {
                if (checkCustomerInfo() && items.length > 0) {
                    triggerAutoSave();
                }
            });
        }
    });

    // If shipping checkbox is checked by default, calculate shipping fee
    const includeShippingFeeCheckbox = document.getElementById('includeShippingFee');
    if (includeShippingFeeCheckbox && includeShippingFeeCheckbox.checked) {
        const shippingRuleType = document.getElementById('shippingRuleType');
        if (shippingRuleType && shippingRuleType.value) {
            // Wait a moment for items to be loaded, then calculate
            setTimeout(() => {
                if (items.length > 0) {
                    calculateShippingFee();
                }
            }, 100);
        }
    }

    // Currency change listener for price conversion
    document.getElementById('currencyCode').addEventListener('change', function() {
        const newCurrency = this.value;
        convertItemPrices(newCurrency);
        convertDeliveryPrice(newCurrency);
    });

    // Shipping fee checkbox - update summary when toggled
    if (includeShippingFeeCheckbox) {
        includeShippingFeeCheckbox.addEventListener('change', function() {
            toggleShippingFee(); // This already calls calculateTotals
        });
    }

    // Shipping rule type - recalculate when changed
    const shippingRuleType = document.getElementById('shippingRuleType');
    if (shippingRuleType) {
        shippingRuleType.addEventListener('change', function() {
            calculateShippingFee();
        });
    }

    // Shipping total input - update summary when manually edited
    const shippingTotalInput = document.getElementById('shippingTotal');
    if (shippingTotalInput) {
        shippingTotalInput.addEventListener('input', function() {
            calculateTotals(true); // Skip recalc to allow manual override
        });
    }

    // Delivery method - update summary when changed
    const deliveryMethodSelect = document.getElementById('deliveryMethod');
    if (deliveryMethodSelect) {
        deliveryMethodSelect.addEventListener('change', function() {
            updateShippingFee();
        });
    }

    // Delivery price input - update summary when manually edited
    const deliveryPriceInput = document.getElementById('deliveryPrice');
    if (deliveryPriceInput) {
        deliveryPriceInput.addEventListener('input', function() {
            calculateTotals(true); // Skip recalc to allow manual override
        });
    }

    // Collection point - just log for now (doesn't affect price)
    const collectionPointSelect = document.getElementById('collectionPoint');
    if (collectionPointSelect) {
        collectionPointSelect.addEventListener('change', function() {
            console.log('Collection point changed:', this.value);
        });
    }
});

// Store exchange rates, shipping options, and shipping rules from backend
const exchangeRates = @json($exchangeRates ?? []);
const shippingOptions = @json($shippingOptions ?? []);
const shippingRules = @json($shippingRules ?? []);

// Convert delivery price when currency changes
function convertDeliveryPrice(targetCurrency) {
    const deliveryPriceInput = document.getElementById('deliveryPrice');
    if (!deliveryPriceInput || !targetCurrency || !exchangeRates[targetCurrency]) {
        return;
    }

    const currentPrice = parseFloat(deliveryPriceInput.value) || 0;
    if (currentPrice === 0) return;

    const targetRate = exchangeRates[targetCurrency].rate;
    const baseRate = exchangeRates['USD']?.rate || 1.0;

    // Store original USD price if not already stored
    if (!deliveryPriceInput.dataset.originalUsdPrice) {
        deliveryPriceInput.dataset.originalUsdPrice = currentPrice;
    }

    // Convert from original USD price
    const originalPrice = parseFloat(deliveryPriceInput.dataset.originalUsdPrice);
    const convertedPrice = (originalPrice / baseRate) * targetRate;
    deliveryPriceInput.value = convertedPrice.toFixed(2);

    calculateTotals();
}

// Convert all item prices when currency changes
function convertItemPrices(targetCurrency) {
    if (!targetCurrency || !exchangeRates[targetCurrency]) {
        return;
    }

    const targetRate = exchangeRates[targetCurrency].rate;
    const baseRate = exchangeRates['USD']?.rate || 1.0;

    // Convert each item's unit price from ORIGINAL USD price
    items.forEach(item => {
        // Convert from original USD price, not from current price
        // This prevents accumulation when switching currencies
        if (item.original_usd_price === undefined) {
            // First time - store the current price as original USD price
            item.original_usd_price = item.unit_price;
        }

        // Always convert from the original USD price
        item.unit_price = (item.original_usd_price / baseRate) * targetRate;
    });

    renderItems();
    calculateTotals();
}

// Update product search to show prices in selected currency
let productSearchTimeout;
document.getElementById('productSearchInput').addEventListener('input', function() {
    clearTimeout(productSearchTimeout);
    const query = this.value;

    if (query.length < 2) {
        document.getElementById('productSearchResults').innerHTML = '';
        return;
    }

    productSearchTimeout = setTimeout(() => {
        const selectedCurrency = document.getElementById('currencyCode').value || '{{ $defaultCurrency }}';

        fetch(`{{ route('admin.invoices-quotations.search-products') }}?q=${encodeURIComponent(query)}&currency=${selectedCurrency}`)
            .then(res => res.json())
            .then(products => {
                const resultsDiv = document.getElementById('productSearchResults');
                if (products.length === 0) {
                    resultsDiv.innerHTML = '<div class="col-12 text-center text-muted">No products found</div>';
                } else {
                    resultsDiv.innerHTML = products.map(product => {
                        // Check if product has variations
                        if (product.has_variations && product.variations && product.variations.length > 0) {
                            // Product with variations - show variation selector
                            const currencySymbol = exchangeRates[selectedCurrency]?.symbol || '$';

                            const variationsHtml = product.variations.map(variation => {
                                let originalPrice = variation.sale_price || variation.price;
                                let price = originalPrice;

                                // Convert price to selected currency
                                if (selectedCurrency !== 'USD' && exchangeRates[selectedCurrency]) {
                                    const targetRate = exchangeRates[selectedCurrency].rate;
                                    const baseRate = exchangeRates['USD']?.rate || 1.0;
                                    price = (originalPrice / baseRate) * targetRate;
                                }

                                const isOutOfStock = variation.quantity === 0 || variation.stock_status === 'out_of_stock';
                                const stockBadge = isOutOfStock
                                    ? '<span class="badge bg-danger ms-1">Out of Stock</span>'
                                    : '<span class="badge bg-success ms-1">In Stock</span>';

                                return `
                                    <button type="button"
                                            class="btn btn-sm ${isOutOfStock ? 'btn-outline-secondary' : 'btn-outline-primary'} w-100 mb-1 text-start"
                                            ${isOutOfStock ? 'disabled' : ''}
                                            onclick='${!isOutOfStock ? `addProductItemWithVariation(${JSON.stringify({...product, variation_id: variation.id, variation_name: variation.name, variation_sku: variation.sku, converted_price: price, original_usd_price: originalPrice, variation_image: variation.image_url}).replace(/'/g, "\\'")})`  : ''}'>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-truncate">${variation.name}</span>
                                            <div>
                                                <strong>${selectedCurrency} ${currencySymbol}${price.toFixed(2)}</strong>
                                                ${stockBadge}
                                            </div>
                                        </div>
                                    </button>
                                `;
                            }).join('');

                            return `
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body p-2">
                                        <div class="d-flex gap-2 mb-2">
                                            <img src="${product.image_url || '/assets/images/placeholder.png'}"
                                                 alt="${product.name}"
                                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <div class="small fw-bold text-truncate">${product.name}</div>
                                                <small class="text-muted">SKU: ${product.sku || 'N/A'}</small><br>
                                                <small class="text-info">${product.variations.length} variation(s) available</small>
                                            </div>
                                        </div>
                                        <div class="variations-list">
                                            <small class="text-muted d-block mb-1"><strong>Select a variation:</strong></small>
                                            ${variationsHtml}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            `;
                        } else {
                            // Simple product without variations
                            let originalPrice = product.sale_price || product.price;
                            let price = originalPrice;

                            // Convert price to selected currency
                            if (selectedCurrency !== 'USD' && exchangeRates[selectedCurrency]) {
                                const targetRate = exchangeRates[selectedCurrency].rate;
                                const baseRate = exchangeRates['USD']?.rate || 1.0;
                                price = (originalPrice / baseRate) * targetRate;
                            }

                            const currencySymbol = exchangeRates[selectedCurrency]?.symbol || '$';

                            return `
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body p-2">
                                        <div class="d-flex gap-2">
                                            <img src="${product.image_url || '/assets/images/placeholder.png'}"
                                                 alt="${product.name}"
                                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <div class="small fw-bold text-truncate">${product.name}</div>
                                                <small class="text-muted">SKU: ${product.sku || 'N/A'}</small><br>
                                                <strong class="text-success">${selectedCurrency} ${currencySymbol}${price.toFixed(2)}</strong>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary w-100 mt-2"
                                                onclick='addProductItem(${JSON.stringify({...product, converted_price: price, original_usd_price: originalPrice}).replace(/'/g, "\\'")})'>
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                            `;
                        }
                    }).join('');
                }
            });
    }, 300);
});

function addProductItem(product) {
    const price = product.converted_price || product.sale_price || product.price;
    const originalUsdPrice = product.original_usd_price || product.sale_price || product.price;

    addItem({
        product_id: product.id,
        variation_id: null,
        product_name: product.name,
        sku: product.sku,
        image_url: product.image_url,
        quantity: 1,
        unit_price: parseFloat(price),
        original_usd_price: parseFloat(originalUsdPrice) // Store original USD price
    });
    bootstrap.Modal.getInstance(document.getElementById('productSearchModal')).hide();
    document.getElementById('productSearchInput').value = '';
    document.getElementById('productSearchResults').innerHTML = '';
}

function addProductItemWithVariation(product) {
    const price = product.converted_price || product.sale_price || product.price;
    const originalUsdPrice = product.original_usd_price || product.sale_price || product.price;

    addItem({
        product_id: product.id,
        variation_id: product.variation_id,
        product_name: `${product.name} - ${product.variation_name}`,
        sku: product.variation_sku || product.sku,
        image_url: product.variation_image || product.image_url,
        quantity: 1,
        unit_price: parseFloat(price),
        original_usd_price: parseFloat(originalUsdPrice) // Store original USD price
    });
    bootstrap.Modal.getInstance(document.getElementById('productSearchModal')).hide();
    document.getElementById('productSearchInput').value = '';
    document.getElementById('productSearchResults').innerHTML = '';
}
</script>
@endpush

