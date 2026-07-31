@extends('admin.layout')

@php
    // Helper function to shorten delivery descriptions
    function shortenDeliveryDescription($description) {
        if (empty($description)) {
            return 'Other';
        }

        // Check for partial matches (case-insensitive)
        $lowerDesc = strtolower($description);

        if (strpos($lowerDesc, 'harare') !== false) {
            return 'Harare Branch';
        }
        if (strpos($lowerDesc, 'bulawayo') !== false) {
            return 'Bulawayo Branch';
        }
        if (strpos($lowerDesc, 'lusaka') !== false) {
            return 'Lusaka Branch';
        }
        if (strpos($lowerDesc, 'mutare') !== false) {
            return 'Mutare Branch';
        }
        if (strpos($lowerDesc, 'home delivery') !== false || strpos($lowerDesc, 'standard home') !== false) {
            return 'Home Delivery';
        }

        // If no match found, return 'Other'
        return 'Other';
    }

    // Helper function to get badge color for each branch
    function getBranchBadgeColor($branchName) {
        $colors = [
            'Harare Branch' => 'bg-primary',
            'Bulawayo Branch' => 'bg-success',
            'Lusaka Branch' => 'bg-warning text-dark',
            'Mutare Branch' => 'bg-danger',
            'Home Delivery' => 'bg-info',
        ];

        return $colors[$branchName] ?? 'bg-secondary';
    }

    // Helper function to get badge color for item status
    function getItemStatusBadgeColor($status) {
        $status = strtolower(trim($status ?? ''));
        $colors = [
            'pending' => 'bg-secondary',
            'processing' => 'bg-primary',
            'shipped' => 'bg-info',
            'out for delivery' => 'bg-warning text-dark',
            'ready for collection' => 'bg-warning text-dark',
            'delivered' => 'bg-success',
            'collected' => 'bg-success',
            'cancelled' => 'bg-danger',
            'canceled' => 'bg-danger',
        ];

        return $colors[$status] ?? 'bg-secondary';
    }

    // Helper function to format status display name
    function formatItemStatus($status) {
        if (empty($status)) {
            return 'Pending';
        }
        return ucwords(str_replace('_', ' ', $status));
    }

    // Helper function to get badge color for order status
    function getOrderStatusBadgeColor($status) {
        $status = strtolower(trim($status ?? ''));
        $colors = [
            'pending' => 'bg-secondary',
            'processing' => 'bg-primary',
            'warehouse packing' => 'bg-primary',
            'from supplier' => 'bg-primary',
            'shipped' => 'bg-info',
            'in transit to zim' => 'bg-info',
            'dropped at the deport' => 'bg-info',
            'out for delivery' => 'bg-warning text-dark',
            'ready for collection' => 'bg-warning text-dark',
            'delivered' => 'bg-success',
            'collected' => 'bg-success',
            'cancelled' => 'bg-danger',
            'canceled' => 'bg-danger',
            'stuck' => 'bg-dark',
        ];

        return $colors[$status] ?? 'bg-secondary';
    }

    // Helper function to format order status display name
    function formatOrderStatus($status) {
        if (empty($status)) {
            return 'Pending';
        }
        return ucwords(strtolower($status));
    }
@endphp

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Processing Order Items - Takealot Link Builder</h2>
                    <p class="text-muted mb-0">Select order items to build a Takealot product link with all SKUs</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Link Builder Card -->
    <div class="card mb-4" id="linkBuilderCard" style="display: none;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-link-45deg"></i> Generated Takealot Links
            </h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center mb-3">
                <div class="col-md-10">
                    <div class="form-group mb-0">
                        <label class="fw-bold mb-2">
                            <span id="selectedCount">0</span> item(s) selected |
                            <span id="skuCount">0</span> unique SKU(s)
                        </label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control form-control-lg"
                                   id="generatedLink"
                                   readonly
                                   value="https://www.takealot.com/all?filter=Id:"
                                   style="font-family: monospace; font-size: 14px;">
                            <button class="btn btn-primary"
                                    type="button"
                                    id="copyLinkBtn"
                                    onclick="copyToClipboard('skuLink')">
                                <i class="bi bi-clipboard"></i> Copy Link
                            </button>
                            <button class="btn btn-success"
                                    type="button"
                                    id="openProductsBtn"
                                    onclick="openProductsOnTakealot('skuLink')">
                                <i class="bi bi-box-arrow-up-right"></i> Open Products on Takealot
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-outline-secondary" onclick="clearSelection()">
                        <i class="bi bi-x-circle"></i> Clear All
                    </button>
                </div>
            </div>

            <!-- Product ID Link (Fetched from Takealot) -->
            <div class="row mt-3" id="productIdLinkSection" style="display: none;">
                <div class="col-12">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i>
                        <strong>Fetched Product IDs:</strong> <span id="productIdCount">0</span> product(s) found on Takealot
                    </div>
                </div>
                <div class="col-md-10">
                    <div class="form-group mb-0">
                        <label class="fw-bold mb-2">Product ID Link (Using Takealot Product IDs)</label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control form-control-lg"
                                   id="productIdLink"
                                   readonly
                                   value="https://www.takealot.com/all?filter=Id:"
                                   style="font-family: monospace; font-size: 14px; background-color: #e8f5e9;">
                            <button class="btn btn-primary"
                                    type="button"
                                    onclick="copyToClipboard('productIdLink')">
                                <i class="bi bi-clipboard"></i> Copy Link
                            </button>
                            <button class="btn btn-success"
                                    type="button"
                                    onclick="openProductsOnTakealot('productIdLink')">
                                <i class="bi bi-box-arrow-up-right"></i> Open Products on Takealot
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add to Cart Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i>
                        <strong>Fetch Takealot Product IDs:</strong> Click the button below to fetch actual Takealot Product IDs and generate an accurate link.
                    </div>
                    <button class="btn btn-primary btn-lg w-100"
                            type="button"
                            id="addToCartBtn"
                            onclick="fetchProductIds()"
                            disabled>
                        <i class="bi bi-search"></i> Fetch Takealot Product IDs
                    </button>
                    <div id="cartProgress" class="mt-3" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                 id="cartProgressBar"
                                 role="progressbar"
                                 style="width: 0%">
                                <span id="cartProgressText">0%</span>
                            </div>
                        </div>
                        <div id="cartStatus" class="mt-2 text-center">
                            <small class="text-muted">Initializing...</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.orders.processing-link-builder') }}" class="row g-3">
                <!-- Preserve existing filters -->
                @if(request('branch'))
                    <input type="hidden" name="branch" value="{{ request('branch') }}">
                @endif
                @if(request('item_status'))
                    <input type="hidden" name="item_status" value="{{ request('item_status') }}">
                @endif
                @if(request('inventory_status'))
                    <input type="hidden" name="inventory_status" value="{{ request('inventory_status') }}">
                @endif
                @if(request('sort_by'))
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                @endif

                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Search by product name or order number..."
                               value="{{ request('search') }}"
                               autocomplete="off">
                    </div>
                </div>
                <div class="col-md-2 d-grid">
                    @if(request('search'))
                        <a href="{{ route('admin.orders.processing-link-builder', request()->except('search')) }}"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear Search
                        </a>
                    @else
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                    @endif
                </div>
            </form>

            @if(request('search'))
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i>
                        Searching for: <strong>"{{ request('search') }}"</strong> -
                        Found {{ $orderItems->total() }} result(s)
                    </small>
                </div>
            @endif
        </div>
    </div>

    <!-- Branch Filter Buttons -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-subtitle mb-3 text-muted">Filter by Destination / Collection Point:</h6>
            <div class="d-flex flex-wrap gap-2">
                <!-- All Items Button -->
                <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->except('branch'), request()->query())) }}"
                   class="btn {{ !request('branch') ? 'btn-dark' : 'btn-outline-dark' }}">
                    <i class="bi bi-grid-3x3-gap"></i> All Items
                    <span class="badge bg-white text-dark ms-1">{{ $orderItems->total() }}</span>
                </a>

                <!-- Harare Branch -->
                @if(isset($branchCounts['Harare Branch']) && $branchCounts['Harare Branch'] > 0)
                <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['branch' => 'Harare Branch'])) }}"
                   class="btn {{ request('branch') === 'Harare Branch' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="bi bi-geo-alt"></i> Harare Branch
                    <span class="badge bg-white text-primary ms-1">{{ $branchCounts['Harare Branch'] }}</span>
                </a>
                @endif

                <!-- Bulawayo Branch -->
                @if(isset($branchCounts['Bulawayo Branch']) && $branchCounts['Bulawayo Branch'] > 0)
                <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['branch' => 'Bulawayo Branch'])) }}"
                   class="btn {{ request('branch') === 'Bulawayo Branch' ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="bi bi-geo-alt"></i> Bulawayo Branch
                    <span class="badge bg-white text-success ms-1">{{ $branchCounts['Bulawayo Branch'] }}</span>
                </a>
                @endif

                <!-- Lusaka Branch -->
                @if(isset($branchCounts['Lusaka Branch']) && $branchCounts['Lusaka Branch'] > 0)
                <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['branch' => 'Lusaka Branch'])) }}"
                   class="btn {{ request('branch') === 'Lusaka Branch' ? 'btn-warning' : 'btn-outline-warning' }}">
                    <i class="bi bi-geo-alt"></i> Lusaka Branch
                    <span class="badge bg-white text-warning ms-1">{{ $branchCounts['Lusaka Branch'] }}</span>
                </a>
                @endif

                <!-- Mutare Branch -->
                @if(isset($branchCounts['Mutare Branch']) && $branchCounts['Mutare Branch'] > 0)
                <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['branch' => 'Mutare Branch'])) }}"
                   class="btn {{ request('branch') === 'Mutare Branch' ? 'btn-danger' : 'btn-outline-danger' }}">
                    <i class="bi bi-geo-alt"></i> Mutare Branch
                    <span class="badge bg-white text-danger ms-1">{{ $branchCounts['Mutare Branch'] }}</span>
                </a>
                @endif

                <!-- Home Delivery -->
                @if(isset($branchCounts['Home Delivery']) && $branchCounts['Home Delivery'] > 0)
                <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['branch' => 'Home Delivery'])) }}"
                   class="btn {{ request('branch') === 'Home Delivery' ? 'btn-info' : 'btn-outline-info' }}">
                    <i class="bi bi-truck"></i> Home Delivery
                    <span class="badge bg-white text-info ms-1">{{ $branchCounts['Home Delivery'] }}</span>
                </a>
                @endif
            </div>
            @if(request('branch'))
            <div class="mt-2">
                <small class="text-muted">
                    <i class="bi bi-filter-circle"></i> Filtered by: <strong>{{ request('branch') }}</strong>
                </small>
            </div>
            @endif
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Processing Order Items ({{ $orderItems->total() }})</h5>
            <div class="d-flex gap-2 align-items-center">
                <!-- Price Sort Filter -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="priceSortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sort-numeric-down"></i> Sort by Price
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="priceSortDropdown">
                        <li>
                            <a class="dropdown-item {{ !request('sort_by') || request('sort_by') === 'date' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['sort_by' => 'date'])) }}">
                                <i class="bi bi-calendar"></i> Order Date (Default)
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item {{ request('sort_by') === 'price_asc' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['sort_by' => 'price_asc'])) }}">
                                <i class="bi bi-sort-numeric-down"></i> Price: Low to High
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('sort_by') === 'price_desc' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['sort_by' => 'price_desc'])) }}">
                                <i class="bi bi-sort-numeric-up"></i> Price: High to Low
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Item Status Filter -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="itemStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-funnel"></i> Filter by Status
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="itemStatusDropdown">
                        <li>
                            <a class="dropdown-item {{ !request('item_status') ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->except('item_status'), request()->query())) }}">
                                <i class="bi bi-grid-3x3-gap"></i> All Statuses
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item {{ request('item_status') === 'pending' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['item_status' => 'pending'])) }}">
                                <span class="badge bg-secondary me-2">•</span> Pending
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('item_status') === 'processing' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['item_status' => 'processing'])) }}">
                                <span class="badge bg-primary me-2">•</span> Processing
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('item_status') === 'shipped' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['item_status' => 'shipped'])) }}">
                                <span class="badge bg-info me-2">•</span> Shipped
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('item_status') === 'out for delivery' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['item_status' => 'out for delivery'])) }}">
                                <span class="badge bg-warning text-dark me-2">•</span> Out for Delivery
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('item_status') === 'ready for collection' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['item_status' => 'ready for collection'])) }}">
                                <span class="badge bg-warning text-dark me-2">•</span> Ready for Collection
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('item_status') === 'delivered' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['item_status' => 'delivered'])) }}">
                                <span class="badge bg-success me-2">•</span> Delivered
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('item_status') === 'cancelled' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['item_status' => 'cancelled'])) }}">
                                <span class="badge bg-danger me-2">•</span> Cancelled
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Inventory Status Filter -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="inventoryStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-box-seam"></i> Inventory Status
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="inventoryStatusDropdown">
                        <li>
                            <a class="dropdown-item {{ !request('inventory_status') ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->except('inventory_status'), request()->query())) }}">
                                <i class="bi bi-grid-3x3-gap"></i> All Items
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item {{ request('inventory_status') === 'in_inventory' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['inventory_status' => 'in_inventory'])) }}">
                                <span class="badge bg-success me-2">✓</span> In Inventory
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ request('inventory_status') === 'not_in_inventory' ? 'active' : '' }}"
                               href="{{ route('admin.orders.processing-link-builder', array_merge(request()->query(), ['inventory_status' => 'not_in_inventory'])) }}">
                                <span class="badge bg-warning text-dark me-2">⚠</span> Not in Inventory
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Clear Filters Button (shows when filters are active) -->
                @if(request('search') || request('branch') || request('item_status') || request('inventory_status') || (request('sort_by') && request('sort_by') !== 'date'))
                <a href="{{ route('admin.orders.processing-link-builder') }}"
                   class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
                @endif

                @if($orderItems->count() > 0)
                <button class="btn btn-sm btn-primary" onclick="selectAll()">
                    <i class="bi bi-check-all"></i> Select All
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                    <i class="bi bi-x"></i> Deselect All
                </button>
                <button class="btn btn-sm btn-success ms-2" id="transferToInventoryBtn" onclick="transferToInventory()" style="display: none;">
                    <i class="bi bi-truck"></i> Transfer to Inventory
                </button>
                @endif
            </div>
        </div>

        <!-- Active Filters Display -->
        @if(request('search') || request('branch') || request('item_status') || request('inventory_status') || (request('sort_by') && request('sort_by') !== 'date'))
        <div class="card-body border-bottom bg-light py-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <small class="text-muted fw-bold">
                    <i class="bi bi-funnel-fill"></i> Active Filters:
                </small>

                @if(request('search'))
                <span class="badge bg-dark">
                    Search: "{{ request('search') }}"
                    <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->except('search'), request()->query())) }}"
                       class="text-white text-decoration-none ms-1">×</a>
                </span>
                @endif

                @if(request('branch'))
                <span class="badge bg-primary">
                    Branch: {{ request('branch') }}
                    <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->except('branch'), request()->query())) }}"
                       class="text-white text-decoration-none ms-1">×</a>
                </span>
                @endif

                @if(request('item_status'))
                <span class="badge bg-info">
                    Status: {{ ucwords(str_replace('_', ' ', request('item_status'))) }}
                    <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->except('item_status'), request()->query())) }}"
                       class="text-white text-decoration-none ms-1">×</a>
                </span>
                @endif

                @if(request('inventory_status'))
                <span class="badge {{ request('inventory_status') === 'in_inventory' ? 'bg-success' : 'bg-warning text-dark' }}">
                    Inventory: {{ request('inventory_status') === 'in_inventory' ? 'In Inventory' : 'Not in Inventory' }}
                    <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->except('inventory_status'), request()->query())) }}"
                       class="text-white text-decoration-none ms-1">×</a>
                </span>
                @endif

                @if(request('sort_by') && request('sort_by') !== 'date')
                <span class="badge bg-secondary">
                    Sort: {{ request('sort_by') === 'price_asc' ? 'Price Low-High' : 'Price High-Low' }}
                    <a href="{{ route('admin.orders.processing-link-builder', array_merge(request()->except('sort_by'), request()->query())) }}"
                       class="text-white text-decoration-none ms-1">×</a>
                </span>
                @endif

                <a href="{{ route('admin.orders.processing-link-builder') }}"
                   class="badge bg-danger text-white text-decoration-none">
                    <i class="bi bi-x-circle"></i> Clear All
                </a>
            </div>
        </div>
        @endif

        <div class="card-body p-0">
            @if($orderItems->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="selectAllCheckbox"
                                       onchange="toggleAll(this)">
                            </th>
                            <th>Order & Product</th>
                            <th>SKU & Variation</th>
                            <th>Qty, Price & Destination</th>
                            <th>Status & Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orderItems as $item)
                        <tr class="order-item-row">
                            <td>
                                <input type="checkbox"
                                       class="form-check-input item-checkbox"
                                       data-order-product-id="{{ $item->order_product_id }}"
                                       data-order-number="{{ $item->order_number }}"
                                       data-sku="{{ $item->sku }}"
                                       onchange="updateLink()">
                            </td>
                            <td>
                                <a href="{{ route('admin.orders.show', $item->order_number) }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-decoration-none fw-bold"
                                   onclick="event.stopPropagation();">
                                    #{{ $item->order_number }}
                                    <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                </a>
                                <br>
                                <small class="d-block text-truncate" style="max-width: 250px;" title="{{ $item->product_name }}">
                                    {{ $item->product_name }}
                                </small>
                            </td>
                            <td>
                                <code class="d-block">{{ $item->sku }}</code>
                                @if($item->variation)
                                <small class="text-muted d-block">{{ $item->variation }}</small>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <strong>Qty:</strong> {{ $item->quantity }}
                                    <span class="mx-1">|</span>
                                    <strong>${{ number_format($item->single_price, 2) }}</strong>
                                </div>
                                @php
                                    $branchName = shortenDeliveryDescription($item->destination);
                                    $branchColor = getBranchBadgeColor($branchName);
                                @endphp
                                <span class="badge {{ $branchColor }} small mt-1">
                                    <i class="bi bi-geo-alt"></i> {{ $branchName }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $itemStatusColor = getItemStatusBadgeColor($item->item_status);
                                    $orderStatusColor = getOrderStatusBadgeColor($item->status);
                                @endphp
                                <small class="text-mutedX"><strong>Item Status:</strong></small>
                                <span class="badge {{ $itemStatusColor }} small">
                                    {{ formatItemStatus($item->item_status) }}
                                </span>
                                <br>
                                <small class="text-mutedX"><strong>Order Status:</strong></small>
                                <span class="badge {{ $orderStatusColor }} small">
                                    {{ formatOrderStatus($item->status) }}
                                </span>
                                <br>
                                <small class="text-mutedX"><strong>Inventory:</strong></small>
                                @if($item->added_to_inventory)
                                    <span class="badge bg-success" title="Added to Inventory">
                                        <i class="bi bi-check-circle"></i> In Inventory
                                    </span>
                                    @if($item->inventory_shipment_id)
                                        <a href="{{ route('admin.inventory-shipments.index') }}"
                                           class="badge bg-info text-decoration-none ms-1"
                                           title="View Shipment #{{ $item->inventory_shipment_id }}"
                                           target="_blank">
                                            <i class="bi bi-box-arrow-up-right"></i> #{{ $item->inventory_shipment_id }}
                                        </a>
                                    @endif
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle"></i> Not in Inventory
                                    </span>
                                @endif
                                <br>
                                <small class="text-mutedX d-block mt-1">
                                    <i class="bi bi-calendar"></i> {{ \Carbon\Carbon::parse($item->order_date)->format('M d, Y') }}
                                </small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ddd;"></i>
                <h5 class="text-muted mt-3">No Processing Order Items Found</h5>
                <p class="text-muted">There are no order items with "processing" status at the moment.</p>

                <div class="alert alert-info mt-4 mx-auto" style="max-width: 600px;">
                    <strong><i class="bi bi-info-circle"></i> Possible Reasons:</strong>
                    <ul class="text-start mt-2 mb-0">
                        <li>No orders currently have "processing" status</li>
                        <li>Orders may have been moved to a different status</li>
                        <li>Products may be missing SKUs</li>
                        <li>Order items may have been deleted</li>
                    </ul>
                </div>

                <div class="mt-3">
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-primary">
                        <i class="bi bi-list"></i> View All Orders
                    </a>
                </div>
            </div>
            @endif
        </div>
        @if($orderItems->count() > 0)
        <div class="card-footer bg-white">
            {{ $orderItems->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<style>
.order-item-row:hover {
    background-color: #f8f9fa;
}
.item-checkbox {
    cursor: pointer;
    width: 20px;
    height: 20px;
}
#generatedLink {
    background-color: #f8f9fa;
}
#productIdLink {
    background-color: #e8f5e9;
}
code {
    font-size: 0.9rem;
}
#openProductsBtn {
    white-space: nowrap;
}
#cartProgress {
    animation: fadeIn 0.3s ease-in;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.progress {
    height: 30px;
    font-size: 14px;
}
.progress-bar {
    transition: width 0.5s ease;
}
#addToCartBtn:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}
.alert-info {
    border-left: 4px solid #0dcaf0;
}
.alert-success {
    border-left: 4px solid #28a745;
}

/* Branch filter buttons styling */
.gap-2 {
    gap: 0.5rem !important;
}

.btn .badge {
    font-size: 0.75rem;
    padding: 0.25em 0.5em;
    font-weight: 600;
}

/* Make active buttons stand out */
.btn-primary:not(.btn-outline-primary),
.btn-success:not(.btn-outline-success),
.btn-warning:not(.btn-outline-warning),
.btn-danger:not(.btn-outline-danger),
.btn-info:not(.btn-outline-info),
.btn-dark:not(.btn-outline-dark) {
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    font-weight: 600;
}

/* Branch button icons */
.btn i.bi {
    margin-right: 0.25rem;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Global variables
let currentSkus = [];
let currentProductIds = [];

// Update the generated link based on selected order items
function updateLink() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const linkCard = document.getElementById('linkBuilderCard');
    const linkInput = document.getElementById('generatedLink');
    const selectedCountEl = document.getElementById('selectedCount');
    const skuCountEl = document.getElementById('skuCount');
    const cartBtn = document.getElementById('addToCartBtn');
    const transferBtn = document.getElementById('transferToInventoryBtn');

    // Collect all SKUs from selected items
    let allSkus = [];
    checkboxes.forEach(checkbox => {
        const sku = checkbox.dataset.sku;
        if (sku) {
            allSkus.push(sku);
        }
    });

    // Remove duplicates and sort
    allSkus = [...new Set(allSkus)].sort();
    currentSkus = allSkus;

    // Update counts
    selectedCountEl.textContent = checkboxes.length;
    skuCountEl.textContent = allSkus.length;

    // Show/hide transfer button based on selection
    if (transferBtn) {
        transferBtn.style.display = checkboxes.length > 0 ? 'inline-block' : 'none';
    }

    // Show/hide card based on selection
    if (checkboxes.length > 0 && allSkus.length > 0) {
        linkCard.style.display = 'block';
        const link = 'https://www.takealot.com/all?filter=Id:' + allSkus.join('|');
        linkInput.value = link;

        // Enable cart button
        if (cartBtn) {
            cartBtn.disabled = false;
        }
    } else {
        linkCard.style.display = 'none';
        linkInput.value = 'https://www.takealot.com/all?filter=Id:';

        // Disable cart button
        if (cartBtn) {
            cartBtn.disabled = true;
        }

        // Hide product ID section when no items selected
        const productIdSection = document.getElementById('productIdLinkSection');
        if (productIdSection) {
            productIdSection.style.display = 'none';
        }
    }

    // Update select all checkbox state
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const totalCheckboxes = document.querySelectorAll('.item-checkbox').length;
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = checkboxes.length === totalCheckboxes;
        selectAllCheckbox.indeterminate = checkboxes.length > 0 && checkboxes.length < totalCheckboxes;
    }
}

// Fetch Takealot Product IDs without adding to cart
async function fetchProductIds() {
    if (currentSkus.length === 0) {
        showToast('No items selected', 'error');
        return;
    }

    const progressDiv = document.getElementById('cartProgress');
    const progressBar = document.getElementById('cartProgressBar');
    const progressText = document.getElementById('cartProgressText');
    const statusDiv = document.getElementById('cartStatus');
    const fetchBtn = document.getElementById('addToCartBtn');
    const productIdSection = document.getElementById('productIdLinkSection');
    const productIdLinkInput = document.getElementById('productIdLink');
    const productIdCountEl = document.getElementById('productIdCount');

    // Disable button and show progress
    fetchBtn.disabled = true;
    progressDiv.style.display = 'block';
    progressBar.style.width = '0%';
    progressText.textContent = '0%';
    progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
    statusDiv.innerHTML = '<small class="text-muted">Fetching product IDs from Takealot...</small>';

    try {
        // Fetch product details from Takealot API via our backend proxy
        const skuString = currentSkus.join('|');
        const searchUrl = "{{ route('admin.takealot.search-products') }}";

        progressBar.style.width = '20%';
        progressText.textContent = '20%';


        const searchResponse = await fetch(searchUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                skus: skuString
            })
        });

        if (!searchResponse.ok) {
            const errorData = await searchResponse.json().catch(() => ({}));
            throw new Error(errorData.message || `Failed to fetch products: ${searchResponse.status}`);
        }

        const searchData = await searchResponse.json();

        if (!searchData.success) {
            throw new Error(searchData.message || 'Failed to fetch products from Takealot');
        }

        progressBar.style.width = '60%';
        progressText.textContent = '60%';

        // Extract product IDs from response
        const productIds = searchData.product_ids || [];
        currentProductIds = productIds;

        if (productIds.length === 0) {
            throw new Error('No products found with the provided SKUs');
        }

        progressBar.style.width = '80%';
        progressText.textContent = '80%';

        // Show product ID link section
        productIdSection.style.display = 'block';
        productIdCountEl.textContent = productIds.length;
        const productIdLink = 'https://www.takealot.com/all?filter=Id:' + productIds.join('|');
        productIdLinkInput.value = productIdLink;

        statusDiv.innerHTML = `<small class="text-success">Found ${productIds.length} product(s) on Takealot!</small>`;

        // Show final status
        progressBar.style.width = '100%';
        progressText.textContent = '100%';
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-success');

        showToast(`Successfully fetched ${productIds.length} product IDs from Takealot!`, 'success');

    } catch (error) {
        console.error('Error in fetchProductIds:', error);
        progressBar.classList.add('bg-danger');
        progressBar.classList.remove('progress-bar-animated');

        const errorMessage = error.message || 'Unknown error occurred';
        statusDiv.innerHTML = `<small class="text-danger"><strong>Error:</strong> ${errorMessage}</small>`;
        showToast(errorMessage, 'error');
    } finally {
        // Re-enable button after 2 seconds
        setTimeout(() => {
            fetchBtn.disabled = false;
        }, 2000);
    }
}

// Open the generated Takealot link in a new tab
function openProductsOnTakealot(linkType) {
    // Convert linkType to actual element ID
    const elementId = linkType === 'skuLink' ? 'generatedLink' : linkType;
    const linkInput = document.getElementById(elementId);

    if (!linkInput) {
        console.error('Link input element not found:', elementId);
        showToast('Error: Link element not found', 'error');
        return;
    }

    const link = linkInput.value;

    if (link && link !== 'https://www.takealot.com/all?filter=Id:') {
        window.open(link, '_blank', 'noopener,noreferrer');
        showToast('Opening products on Takealot...', 'success');
    } else {
        showToast('No products selected', 'error');
    }
}

// Copy link to clipboard
function copyToClipboard(linkType) {
    const linkInput = document.getElementById(linkType === 'skuLink' ? 'generatedLink' : linkType);
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices

    try {
        document.execCommand('copy');

        // Visual feedback - find the copy button that was clicked
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Copied!';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');

        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2000);

        // Show toast notification
        showToast('Link copied to clipboard!', 'success');
    } catch (err) {
        console.error('Failed to copy:', err);
        showToast('Failed to copy link', 'error');
    }
}

// ...existing code...

// Select all items
function selectAll() {
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateLink();
}

// Deselect all items
function deselectAll() {
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateLink();
}

// Clear selection
function clearSelection() {
    deselectAll();
}

// Toggle all checkboxes
function toggleAll(selectAllCheckbox) {
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateLink();
}

// Transfer selected items to inventory shipment
async function transferToInventory() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');

    if (checkboxes.length === 0) {
        showToast('Please select at least one item to transfer', 'error');
        return;
    }

    // Collect order product IDs
    const orderProductIds = [];
    checkboxes.forEach(checkbox => {
        const orderProductId = checkbox.dataset.orderProductId;
        if (orderProductId) {
            orderProductIds.push(parseInt(orderProductId));
        }
    });

    if (orderProductIds.length === 0) {
        showToast('No valid items selected', 'error');
        return;
    }

    // Confirm action via SweetAlert2
    const confirmResult = await Swal.fire({
        title: 'Transfer to Inventory?',
        text: `Transfer ${orderProductIds.length} item(s) to inventory shipments?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Transfer',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
    });

    if (!confirmResult.isConfirmed) {
        return;
    }

    const transferBtn = document.getElementById('transferToInventoryBtn');
    const originalBtnHtml = transferBtn.innerHTML;

    try {
        // Disable button and show loading state
        transferBtn.disabled = true;
        transferBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Transferring...';

        const response = await fetch("{{ route('admin.orders.processing-link-builder.transfer-to-inventory') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                order_product_ids: orderProductIds
            })
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');

            // Reload page after a short delay to show updated status
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Failed to transfer items to inventory', 'error');
            transferBtn.disabled = false;
            transferBtn.innerHTML = originalBtnHtml;
        }
    } catch (error) {
        console.error('Error transferring to inventory:', error);
        showToast('An error occurred while transferring items', 'error');
        transferBtn.disabled = false;
        transferBtn.innerHTML = originalBtnHtml;
    }
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateLink();
});
</script>
@endsection
