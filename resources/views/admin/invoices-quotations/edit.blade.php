@extends('admin.layout')

@section('title', 'Edit ' . ucfirst($document->document_type) . ' - Admin Panel')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> Edit {{ $document->getDocumentTypeLabel() }} - {{ $document->document_number }}</h2>
    <div class="btn-group">
        <a href="{{ route('admin.invoices-quotations.show', $document->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-eye"></i> View
        </a>
        <a href="{{ route('admin.invoices-quotations.index') }}" onclick="history.back(); return false;" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<form action="{{ route('admin.invoices-quotations.update', $document->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-lg-8">
            <!-- Customer Information -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person"></i> Customer Information
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer Name *</label>
                            <input type="text" name="customer_name" class="form-control" value="{{ $document->customer_name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-control" value="{{ $document->customer_email }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="customer_phone" class="form-control" value="{{ $document->customer_phone }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Currency</label>
                            <input type="text" class="form-control" value="{{ $document->currency_code }}" disabled>
                            <small class="text-muted">Currency cannot be changed after creation</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="customer_address" class="form-control" rows="2">{{ $document->customer_address }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Items Section (Editable) -->
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
                                <!-- Items will be rendered by JavaScript -->
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
                            <select name="discount_type" id="discountType" class="form-select" onchange="updateTotalsPreview()">
                                <option value="amount" {{ $document->discount_type == 'amount' ? 'selected' : '' }}>Fixed Amount</option>
                                <option value="percentage" {{ $document->discount_type == 'percentage' ? 'selected' : '' }}>Percentage</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Discount Value</label>
                            <input type="number" name="discount_value" id="discountValue" class="form-control" step="0.01" value="{{ $document->discount_value }}" oninput="updateTotalsPreview()">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="hidden" name="include_vat" value="0">
                        <input type="checkbox" name="include_vat" id="includeVat" class="form-check-input" value="1" {{ $document->include_vat ? 'checked' : '' }} onchange="updateTotalsPreview()">
                        <label class="form-check-label" for="includeVat">Include VAT</label>
                    </div>

                    <div class="mb-3" id="vatPercentageDiv" style="display: {{ $document->include_vat ? 'block' : 'none' }};">
                        <label class="form-label">VAT Percentage (%)</label>
                        <input type="number" name="vat_percentage" id="vatPercentage" class="form-control" step="0.01" value="{{ $document->vat_percentage }}" oninput="updateTotalsPreview()">
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
                            <input type="date" name="issue_date" class="form-control" value="{{ $document->issue_date->format('Y-m-d') }}" required>
                        </div>
                        @if(in_array($document->document_type, ['invoice', 'proforma']))
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="{{ $document->due_date?->format('Y-m-d') }}">
                        </div>
                        @endif
                        @if($document->document_type == 'quotation')
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Valid Until</label>
                            <input type="date" name="valid_until" class="form-control" value="{{ $document->valid_until?->format('Y-m-d') }}">
                        </div>
                        @endif
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" {{ $document->status == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="sent" {{ $document->status == 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="paid" {{ $document->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="cancelled" {{ $document->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                <option value="expired" {{ $document->status == 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ $document->notes }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Terms & Conditions</label>
                        <textarea name="terms_conditions" class="form-control" rows="3">{{ $document->terms_conditions }}</textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Update Document
                </button>
                <a href="{{ route('admin.invoices-quotations.show', $document->id) }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-calculator"></i> Summary Preview
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="previewSubtotal" class="fw-bold">{{ $document->currency_code }} {{ number_format($document->subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Discount:</span>
                        <span id="previewDiscount" class="fw-bold text-danger">{{ $document->currency_code }} {{ number_format($document->discount_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>VAT:</span>
                        <span id="previewVat" class="fw-bold">{{ $document->currency_code }} {{ number_format($document->vat_amount, 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="h5 mb-0">Total:</span>
                        <span id="previewTotal" class="h5 mb-0 fw-bold text-success">{{ $document->currency_code }} {{ number_format($document->total_amount, 2) }}</span>
                    </div>

                    <div class="mt-4">
                        <small class="text-muted">Items: {{ $document->items->count() }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

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

@push('scripts')
<script>
let items = [];
let itemCounter = 0;
const subtotal = {{ $document->subtotal }};
const currencyCode = "{{ $document->currency_code }}";
const exchangeRates = @json($exchangeRates ?? []);
const currencySymbol = exchangeRates[currencyCode]?.symbol || '$';

// Initialize items from existing document
document.addEventListener('DOMContentLoaded', function() {

    // Load existing items
    @foreach($document->items as $index => $item)
    items.push({
        id: {{ $index }},
        item_id: {{ $item->id }},
        product_id: {{ $item->product_id ?: 'null' }},
        variation_id: {{ $item->variation_id ?: 'null' }},
        product_name: @json($item->product_name),
        sku: @json($item->sku ?? ''),
        description: @json($item->description ?? ''),
        image_url: @json($item->image_url ?? ''),
        quantity: {{ $item->quantity }},
        unit_price: {{ $item->unit_price }},
        is_manual: {{ $item->product_id ? 'false' : 'true' }}
    });
    @endforeach

    itemCounter = items.length;
    renderItems();
    updateTotalsPreview();
});

// Product search
let productSearchTimeout;
document.getElementById('productSearchInput')?.addEventListener('input', function() {
    clearTimeout(productSearchTimeout);
    const query = this.value;

    if (query.length < 2) {
        document.getElementById('productSearchResults').innerHTML = '';
        return;
    }

    productSearchTimeout = setTimeout(() => {
        fetch(`{{ route('admin.invoices-quotations.search-products') }}?q=${encodeURIComponent(query)}&currency=${currencyCode}`)
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
                            const variationsHtml = product.variations.map(variation => {
                                let originalPrice = variation.sale_price || variation.price;
                                let price = originalPrice;

                                // Convert price to selected currency (same logic as create page)
                                if (currencyCode !== 'USD' && exchangeRates[currencyCode]) {
                                    const targetRate = exchangeRates[currencyCode].rate;
                                    const baseRate = exchangeRates['USD']?.rate || 1.0;
                                    price = (originalPrice / baseRate) * targetRate;
                                }

                                const isOutOfStock = variation.quantity === 0 || variation.stock_status === 'out_of_stock';
                                const stockBadge = isOutOfStock
                                    ? '<span class="badge bg-danger ms-1">Out of Stock</span>'
                                    : '<span class="badge bg-success ms-1">In Stock</span>';

                                const varData = JSON.stringify({
                                    ...product,
                                    variation_id: variation.id,
                                    variation_name: variation.name,
                                    variation_sku: variation.sku,
                                    price: price,
                                    original_usd_price: originalPrice,
                                    variation_image: variation.image_url
                                }).replace(/'/g, "\\'");

                                return `
                                    <button type="button"
                                            class="btn btn-sm ${isOutOfStock ? 'btn-outline-secondary' : 'btn-outline-primary'} w-100 mb-1 text-start"
                                            ${isOutOfStock ? 'disabled' : ''}
                                            onclick='${!isOutOfStock ? `selectProductWithVariation(${varData})` : ''}'>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-truncate">${variation.name}</span>
                                            <div>
                                                <strong>${currencySymbol}${price.toFixed(2)}</strong>
                                                ${stockBadge}
                                            </div>
                                        </div>
                                    </button>
                                `;
                            }).join('');

                            return `
                            <div class="col-md-12">
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <div class="d-flex gap-2 mb-2">
                                            <img src="${product.image_url || '/assets/images/placeholder.png'}"
                                                 alt="${product.name}"
                                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1" style="font-size: 14px;">${product.name}</h6>
                                                <small class="text-muted">SKU: ${product.sku || 'N/A'}</small>
                                                <div class="badge bg-info text-white mt-1">${product.variations.length} variations available</div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <strong class="d-block mb-1" style="font-size: 12px;">Select Variation:</strong>
                                            ${variationsHtml}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            `;
                        } else {
                            // Product without variations - show regular card
                            let originalPrice = product.sale_price || product.price;
                            let price = originalPrice;

                            // Convert price to selected currency (same logic as create page)
                            if (currencyCode !== 'USD' && exchangeRates[currencyCode]) {
                                const targetRate = exchangeRates[currencyCode].rate;
                                const baseRate = exchangeRates['USD']?.rate || 1.0;
                                price = (originalPrice / baseRate) * targetRate;
                            }
                            return `
                                <div class="col-md-4 col-sm-6">
                                    <div class="card h-100 product-card" onclick='selectProduct(${JSON.stringify({...product, converted_price: price, original_usd_price: originalPrice}).replace(/'/g, "\\'")})'  style="cursor: pointer;">
                                        <img src="${product.image_url || '/assets/images/placeholder.png'}" class="card-img-top" style="height: 120px; object-fit: cover;">
                                        <div class="card-body p-2">
                                            <h6 class="card-title mb-1" style="font-size: 12px;">${product.name}</h6>
                                            <p class="card-text mb-0">
                                                <small class="text-muted">SKU: ${product.sku || 'N/A'}</small><br>
                                                <strong>${currencySymbol}${price.toFixed(2)}</strong>
                                            </p>
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

function selectProduct(product) {
    addItem({
        product_id: product.id,
        variation_id: null,
        product_name: product.name,
        sku: product.sku,
        image_url: product.image_url,
        quantity: 1,
        unit_price: product.converted_price || parseFloat(product.sale_price || product.price),
        is_manual: false
    });
    bootstrap.Modal.getInstance(document.getElementById('productSearchModal')).hide();
    document.getElementById('productSearchInput').value = '';
    document.getElementById('productSearchResults').innerHTML = '';
}

function selectProductWithVariation(product) {
    addItem({
        product_id: product.id,
        variation_id: product.variation_id,
        product_name: product.name + ' - ' + product.variation_name,
        sku: product.variation_sku || product.sku,
        image_url: product.variation_image || product.image_url,
        quantity: 1,
        unit_price: parseFloat(product.price),
        is_manual: false
    });
    bootstrap.Modal.getInstance(document.getElementById('productSearchModal')).hide();
    document.getElementById('productSearchInput').value = '';
    document.getElementById('productSearchResults').innerHTML = '';
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
        is_manual: true
    });
}

function addItem(itemData) {
    const id = itemCounter++;
    items.push({...itemData, id});
    renderItems();
    updateTotalsPreview();
}

function removeItem(id) {
    items = items.filter(item => item.id !== id);
    renderItems();
    updateTotalsPreview();
}

function updateItemQuantity(id, newQuantity) {
    const item = items.find(i => i.id === id);
    if (item) {
        item.quantity = parseFloat(newQuantity) || 1;
        updateItemSubtotal(id);
        updateTotalsPreview();
    }
}

function updateItemPrice(id, newPrice) {
    const item = items.find(i => i.id === id);
    if (item) {
        item.unit_price = parseFloat(newPrice) || 0;
        updateItemSubtotal(id);
        updateTotalsPreview();
    }
}

function updateItemSubtotal(itemId) {
    const item = items.find(i => i.id === itemId);
    if (!item) return;

    const subtotal = (item.quantity * item.unit_price).toFixed(2);

    // Find the row and update subtotal cell
    const quantityInput = document.querySelector(`.item-quantity-input[data-item-id="${itemId}"]`);
    if (quantityInput) {
        const row = quantityInput.closest('tr');
        const subtotalCell = row.querySelector('.subtotal-cell strong');
        if (subtotalCell) {
            subtotalCell.textContent = `${currencyCode} ${subtotal}`;
        }
    }
}

function updateItemName(id, newName) {
    const item = items.find(i => i.id === id);
    if (item) {
        item.product_name = newName;
    }
}

function renderItems() {
    const tbody = document.getElementById('itemsTableBody');
    tbody.innerHTML = items.map((item, index) => {
        const isManual = item.is_manual === true;
        const itemSubtotal = (item.quantity * item.unit_price).toFixed(2);

        return `
        <tr>
            <td>
                <img src="${item.image_url || '/assets/images/placeholder.png'}"
                     alt="${item.product_name}"
                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm item-name-input"
                       data-item-id="${item.id}"
                       value="${escapeHtml(item.product_name)}"
                       placeholder="Product name" required>
                <input type="text" class="form-control form-control-sm mt-1 item-sku-input"
                       data-item-id="${item.id}"
                       value="${escapeHtml(item.sku || '')}"
                       placeholder="SKU" ${isManual ? '' : 'readonly'}>
                <input type="hidden" name="items[${index}][id]" value="${item.item_id || ''}">
                <input type="hidden" name="items[${index}][product_id]" value="${item.product_id || ''}">
                <input type="hidden" name="items[${index}][variation_id]" value="${item.variation_id || ''}">
                <input type="hidden" name="items[${index}][product_name]" class="hidden-product-name" value="${escapeHtml(item.product_name)}">
                <input type="hidden" name="items[${index}][sku]" class="hidden-sku" value="${escapeHtml(item.sku || '')}">
                <input type="hidden" name="items[${index}][image_url]" value="${item.image_url || ''}">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm item-quantity-input"
                       name="items[${index}][quantity]"
                       data-item-id="${item.id}"
                       value="${item.quantity}"
                       step="1" min="1" required>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm item-price-input"
                       name="items[${index}][unit_price]"
                       data-item-id="${item.id}"
                       value="${item.unit_price}"
                       step="0.01" min="0">
            </td>
            <td class="subtotal-cell">
                <strong>${currencyCode} ${itemSubtotal}</strong>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${item.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `}).join('');

    // Add event listeners after rendering
    attachItemEventListeners();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function attachItemEventListeners() {
    // Quantity inputs
    document.querySelectorAll('.item-quantity-input').forEach(input => {
        input.addEventListener('input', function() {
            const itemId = parseInt(this.dataset.itemId);
            updateItemQuantity(itemId, this.value);
        });
    });

    // Price inputs
    document.querySelectorAll('.item-price-input').forEach(input => {
        input.addEventListener('input', function() {
            const itemId = parseInt(this.dataset.itemId);
            updateItemPrice(itemId, this.value);
        });
    });

    // Name inputs
    document.querySelectorAll('.item-name-input').forEach(input => {
        input.addEventListener('input', function() {
            const itemId = parseInt(this.dataset.itemId);
            const item = items.find(i => i.id === itemId);
            if (item) {
                item.product_name = this.value;
                // Update hidden field
                const row = this.closest('tr');
                const hiddenName = row.querySelector('.hidden-product-name');
                if (hiddenName) hiddenName.value = this.value;
            }
        });
    });

    // SKU inputs
    document.querySelectorAll('.item-sku-input').forEach(input => {
        if (!input.readOnly) {
            input.addEventListener('input', function() {
                const itemId = parseInt(this.dataset.itemId);
                const item = items.find(i => i.id === itemId);
                if (item) {
                    item.sku = this.value;
                    // Update hidden field
                    const row = this.closest('tr');
                    const hiddenSku = row.querySelector('.hidden-sku');
                    if (hiddenSku) hiddenSku.value = this.value;
                }
            });
        }
    });
}

function updateTotalsPreview() {
    // Calculate subtotal from items
    const calculatedSubtotal = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);

    const discountType = document.getElementById('discountType').value;
    const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
    const includeVat = document.getElementById('includeVat').checked;
    const vatPercentage = parseFloat(document.getElementById('vatPercentage').value) || 0;

    // Calculate discount
    let discountAmount = 0;
    if (discountType === 'percentage') {
        discountAmount = (calculatedSubtotal * discountValue) / 100;
    } else {
        discountAmount = discountValue;
    }

    const amountAfterDiscount = calculatedSubtotal - discountAmount;

    // Shipping and delivery are fixed at creation time — use server-side values
    const shippingFee = {{ (float) ($document->shipping_total ?? 0) }};
    const deliveryFee = {{ (float) ($document->delivery_price ?? 0) }};

    // VAT applies to the full taxable amount: products (after discount) + shipping + delivery
    let vatAmount = 0;
    if (includeVat) {
        const vatBase = amountAfterDiscount + shippingFee + deliveryFee;
        vatAmount = (vatBase * vatPercentage) / 100;
    }

    const total = amountAfterDiscount + shippingFee + deliveryFee + vatAmount;

    // Update preview
    document.getElementById('previewSubtotal').textContent = `${currencyCode} ${calculatedSubtotal.toFixed(2)}`;
    document.getElementById('previewDiscount').textContent = `${currencyCode} ${discountAmount.toFixed(2)}`;
    document.getElementById('previewVat').textContent = `${currencyCode} ${vatAmount.toFixed(2)}`;
    document.getElementById('previewTotal').textContent = `${currencyCode} ${total.toFixed(2)}`;

    // Toggle VAT percentage visibility
    document.getElementById('vatPercentageDiv').style.display = includeVat ? 'block' : 'none';
}
</script>
<style>
.product-card {
    cursor: pointer;
    transition: all 0.2s;
}
.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>
@endpush

