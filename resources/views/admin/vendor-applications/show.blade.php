@extends('admin.layout')

@section('title', 'Vendor Application Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4 class="mb-0">
                            Vendor Application #{{ $application->id }}
                            @if($application->is_banned)
                                <span class="badge bg-danger ms-2">BANNED</span>
                            @elseif($application->is_approved)
                                <span class="badge bg-success ms-2">APPROVED</span>
                            @else
                                <span class="badge bg-warning ms-2">PENDING</span>
                            @endif
                        </h4>
                        <div>
                            <a href="{{ route('admin.vendor-applications.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            @if(!$application->is_approved && !$application->is_banned)
                            <form method="POST" action="{{ route('admin.vendor-applications.approve', $application->id) }}" class="d-inline" onsubmit="return handleApprove(event)">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_approved" value="1">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger btn-sm" onclick="handleReject()">
                                <i class="fas fa-times"></i> Reject
                            </button>
                            @endif

                            @if($application->is_approved)
                                @if(!$application->is_banned)
                                <button type="button" class="btn btn-warning btn-sm" onclick="handleBan()">
                                    <i class="fas fa-ban"></i> Ban Vendor
                                </button>
                                @else
                                <form method="POST" action="{{ route('admin.vendor-applications.unban', $application->id) }}" class="d-inline" onsubmit="return handleUnban(event)">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-undo"></i> Unban Vendor
                                    </button>
                                </form>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Product Stats -->
                    @if($application->is_approved)
                    <div class="d-flex gap-3 mt-2">
                        <small class="text-white">
                            <i class="fas fa-box"></i> Total Products: <strong>{{ $application->total_products_count ?? 0 }}</strong>
                        </small>
                        <small class="text-success">
                            <i class="fas fa-check-circle"></i> Active: <strong>{{ $application->active_products_count ?? 0 }}</strong>
                        </small>
                        <small class="text-warning">
                            <i class="fas fa-clock"></i> Pending: <strong>{{ $application->pending_products_count ?? 0 }}</strong>
                        </small>
                    </div>
                    @endif

                    <!-- Toggle Button for Vendor Info -->
                    <button type="button" class="btn btn-sm btn-light mt-2" onclick="toggleVendorInfo()" id="toggleVendorInfoBtn">
                        <i class="fas fa-eye" id="toggleIcon"></i> <span id="toggleText">Show Vendor Details</span>
                    </button>
                </div>

                <div class="card-body" id="vendorInfoSection" style="display: none;">
                    <!-- Ban Information Alert -->
                    @if($application->is_banned)
                    <div class="alert alert-danger" role="alert">
                        <h5 class="alert-heading">
                            <i class="fas fa-ban"></i> This Vendor is Currently Banned
                        </h5>
                        <hr>
                        <p><strong>Reason:</strong> {{ $application->ban_reason }}</p>
                        <p class="mb-0">
                            <strong>Banned On:</strong>
                            @if($application->banned_at)
                                {{ is_string($application->banned_at) ? \Carbon\Carbon::parse($application->banned_at)->format('M d, Y H:i') : $application->banned_at->format('M d, Y H:i') }}
                            @else
                                N/A
                            @endif
                            @if($application->bannedBy)
                            <br><strong>Banned By:</strong> {{ $application->bannedBy->name }}
                            @endif
                        </p>
                    </div>
                    @endif

                    <div class="row">
                        <!-- Personal Information -->
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2 mb-3">Personal Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Vendor Name:</th>
                                    <td>{{ $application->vendor->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $application->vendor->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>{{ $application->vendor->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Registration Date:</th>
                                    <td>{{ $application->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- VAT & Identification -->
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2 mb-3">VAT & Identification</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">VAT Registered:</th>
                                    <td>
                                        @if($application->is_vat_registered === 'yes')
                                        <span class="badge bg-success">Yes</span>
                                        @else
                                        <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($application->is_vat_registered === 'yes')
                                <tr>
                                    <th>VAT Number:</th>
                                    <td>{{ $application->vat_number }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>Identification Type:</th>
                                    <td>{{ ucfirst($application->identification_type ?? 'N/A') }}</td>
                                </tr>
                                @if($application->identification_type === 'id')
                                <tr>
                                    <th>ID Number:</th>
                                    <td>{{ $application->id_number }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <!-- Business Information -->
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2 mb-3">Business Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Legal Name:</th>
                                    <td>{{ $application->legal_name }}</td>
                                </tr>
                                <tr>
                                    <th>Trading Name:</th>
                                    <td>{{ $application->trading_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Monthly Revenue:</th>
                                    <td>{{ ucwords(str_replace('_', ' ', $application->monthly_revenue ?? 'N/A')) }}</td>
                                </tr>
                                <tr>
                                    <th>Physical Stores:</th>
                                    <td>{{ ucfirst($application->has_physical_stores ?? 'N/A') }}</td>
                                </tr>
                                @if($application->has_physical_stores === 'yes')
                                <tr>
                                    <th>Number of Stores:</th>
                                    <td>{{ $application->number_of_stores }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th>Supplier to Retailers:</th>
                                    <td>{{ ucfirst($application->is_supplier_to_retailers ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <th>Marketplace Accounts:</th>
                                    <td>{{ ucfirst($application->has_marketplace_accounts ?? 'N/A') }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Store Information -->
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2 mb-3">Store Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Store Name:</th>
                                    <td>{{ $application->store_name }}</td>
                                </tr>
                                <tr>
                                    <th>Description:</th>
                                    <td>{{ Str::limit($application->description, 100) }}</td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td>{{ $application->address }}</td>
                                </tr>
                                <tr>
                                    <th>City:</th>
                                    <td>{{ $application->city }}</td>
                                </tr>
                                <tr>
                                    <th>State/Province:</th>
                                    <td>{{ $application->state->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Country:</th>
                                    <td>{{ $application->country->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Postal Code:</th>
                                    <td>{{ $application->pincode }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <!-- Product Range -->
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2 mb-3">Product Range & Brands</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Number of Products:</th>
                                    <td>{{ $application->number_of_products }}</td>
                                </tr>
                                <tr>
                                    <th>Primary Category:</th>
                                    <td>{{ $application->primary_category }}</td>
                                </tr>
                                <tr>
                                    <th>Stock Holding:</th>
                                    <td>{{ ucwords(str_replace('_', ' ', $application->stock_holding ?? 'N/A')) }}</td>
                                </tr>
                                <tr>
                                    <th>Product Source:</th>
                                    <td>{{ ucwords(str_replace('_', ' ', $application->product_source ?? 'N/A')) }}</td>
                                </tr>
                                <tr>
                                    <th>Product Branding:</th>
                                    <td>{{ ucfirst($application->product_branding ?? 'N/A') }}</td>
                                </tr>
                                <tr>
                                    <th>Owned Brands:</th>
                                    <td>{{ $application->owned_brands ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Reseller Brands:</th>
                                    <td>{{ $application->reseller_brands ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>

                        <!-- Online Presence -->
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2 mb-3">Online Presence</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Website:</th>
                                    <td>
                                        @if($application->website)
                                        <a href="{{ $application->website }}" target="_blank">{{ $application->website }}</a>
                                        @else
                                        N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Social Media:</th>
                                    <td>
                                        @if($application->social_media_page)
                                        <a href="{{ $application->social_media_page }}" target="_blank">{{ $application->social_media_page }}</a>
                                        @else
                                        N/A
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Product Catalog:</th>
                                    <td>
                                        @if($application->product_catalog)
                                            <div class="d-flex align-items-center gap-2">
                                                <a href="{{ $application->product_catalog->image_url }}" target="_blank" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-download"></i> Download Catalog
                                                </a>
                                                <small class="text-muted">
                                                    ({{ $application->product_catalog->file_name ?? 'File' }})
                                                </small>
                                            </div>
                                            @if($application->product_catalog->mime_type)
                                                <small class="text-muted d-block mt-1">
                                                    Type: {{ $application->product_catalog->mime_type }}
                                                    @if($application->product_catalog->size)
                                                        | Size: {{ number_format($application->product_catalog->size / 1024, 2) }} KB
                                                    @endif
                                                </small>
                                            @endif
                                        @elseif($application->product_catalog_id)
                                            <div class="alert alert-warning small">
                                                <strong>Catalog ID exists ({{ $application->product_catalog_id }}) but file not found.</strong>
                                                <br>The attachment may have been deleted.
                                            </div>
                                        @else
                                            <span class="text-muted">No catalog uploaded</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Referral Source:</th>
                                    <td>{{ ucwords(str_replace('_', ' ', $application->referral_source ?? 'N/A')) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- Business Summary -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3">Business Summary</h5>
                            <div class="mb-3">
                                <strong>Business Summary:</strong>
                                <p>{{ $application->business_summary ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <strong>What makes products unique:</strong>
                                <p>{{ $application->product_uniqueness ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <strong>Intended products for platform:</strong>
                                <p>{{ $application->intended_products ?? 'N/A' }}</p>
                            </div>
                            <div class="mb-3">
                                <strong>Certifications:</strong>
                                <p>{{ $application->certifications ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commission Statistics Card -->
            @if($application->is_approved)
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave"></i> Commission Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Wallet Balance -->
                        <div class="col-md-4 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-wallet fa-3x mb-2"></i>
                                    <h6>Current Wallet Balance</h6>
                                    <h3 class="mb-0">${{ number_format($commissionData['wallet_balance'], 2) }}</h3>
                                </div>
                            </div>
                        </div>

                        <!-- Total Commission Earned -->
                        <div class="col-md-4 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-line fa-3x mb-2"></i>
                                    <h6>Total Commission Earned</h6>
                                    <h3 class="mb-0">${{ number_format($commissionData['total_commission'], 2) }}</h3>
                                </div>
                            </div>
                        </div>

                        <!-- This Month Commission -->
                        <div class="col-md-4 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-alt fa-3x mb-2"></i>
                                    <h6>This Month Commission</h6>
                                    <h3 class="mb-0">${{ number_format($commissionData['this_month_commission'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Commission History -->
                    <div class="mt-4">
                        <h6 class="border-bottom pb-2 mb-3">Recent Commission History</h6>

                        @if($commissionData['commission_count'] > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Order Number</th>
                                        <th>Order Status</th>
                                        <th>Admin Commission</th>
                                        <th>Vendor Commission</th>
                                        <th>Total Amount</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commissionData['recent_commissions'] as $commission)
                                    <tr>
                                        <td>{{ $commission->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            @if($commission->order)
                                                <a href="{{ route('admin.orders.show', $commission->order->order_number) }}" target="_blank">
                                                    #{{ $commission->order->order_number }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($commission->order)
                                                <span class="badge bg-{{ $commission->order->payment_status == 'COMPLETED' ? 'success' : 'warning' }}">
                                                    {{ $commission->order->payment_status }}
                                                </span>
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="text-danger">-${{ number_format($commission->admin_commission, 2) }}</td>
                                        <td class="text-success"><strong>+${{ number_format($commission->vendor_commission, 2) }}</strong></td>
                                        <td>${{ number_format($commission->admin_commission + $commission->vendor_commission, 2) }}</td>
                                        <td>
                                            @if($commission->items && $commission->items->count() > 0)
                                                <button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#commission-items-{{ $commission->id }}" aria-expanded="false">
                                                    <i class="fas fa-eye"></i> View Breakdown
                                                </button>
                                            @else
                                                <span class="text-muted">No details</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($commission->items && $commission->items->count() > 0)
                                    <tr class="collapse" id="commission-items-{{ $commission->id }}">
                                        <td colspan="7">
                                            <div class="card mb-0">
                                                <div class="card-body">
                                                    <h6 class="card-title">Commission Breakdown by Product</h6>
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Product</th>
                                                                <th>Category</th>
                                                                <th>Rate Applied</th>
                                                                <th>Source</th>
                                                                <th>Subtotal</th>
                                                                <th>Vendor Commission</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($commission->items as $item)
                                                            <tr>
                                                                <td>
                                                                    @if($item->product)
                                                                        <a href="{{ route('admin.products.edit', $item->product_id) }}" target="_blank">
                                                                            {{ $item->product_name }}
                                                                        </a>
                                                                    @else
                                                                        {{ $item->product_name }}
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($item->category_name)
                                                                        <span class="badge bg-secondary">{{ $item->category_name }}</span>
                                                                    @else
                                                                        <span class="text-muted">N/A</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <strong>{{ number_format($item->commission_rate, 2) }}%</strong>
                                                                </td>
                                                                <td>
                                                                    @if($item->commission_source == 'category')
                                                                        <span class="badge bg-primary">Category</span>
                                                                    @else
                                                                        <span class="badge bg-warning">Default</span>
                                                                    @endif
                                                                </td>
                                                                <td>${{ number_format($item->subtotal, 2) }}</td>
                                                                <td class="text-success"><strong>+${{ number_format($item->vendor_commission, 2) }}</strong></td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot class="table-light">
                                                            <tr>
                                                                <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                                                <td class="text-success"><strong>+${{ number_format($commission->vendor_commission, 2) }}</strong></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No commission history yet. Commission will be credited when orders are marked as <strong>Delivered</strong> or <strong>Collected</strong> with payment status <strong>COMPLETED</strong>.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Vendor Products Section -->
            @if($application->is_approved)
            <div class="card mt-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-boxes"></i> Vendor Products
                                <span class="badge bg-primary ms-2">{{ $products->total() }}</span>
                            </h5>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <!-- Approve All Button -->
                            @if($products->total() > 0)
                            <button type="button" class="btn btn-success btn-sm" onclick="approveAllProducts()">
                                <i class="fas fa-check-double"></i> Approve All Products
                            </button>
                            @endif

                            <!-- Bulk Actions Bar -->
                            <div id="bulkActionsBar" style="display: none;">
                                <span class="me-2" id="selectedCount">0 selected</span>
                                <button type="button" class="btn btn-success btn-sm" onclick="bulkApprove()">
                                    <i class="fas fa-check"></i> Approve Selected
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="bulkDisapprove()">
                                    <i class="fas fa-times"></i> Disapprove Selected
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                                    <i class="fas fa-times-circle"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('admin.vendor-applications.show', $application->id) }}" class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select name="product_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="1" {{ request('product_status') === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ request('product_status') === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="product_approval" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Approval</option>
                                    <option value="1" {{ request('product_approval') === '1' ? 'selected' : '' }}>Approved</option>
                                    <option value="0" {{ request('product_approval') === '0' ? 'selected' : '' }}>Pending</option>
                                </select>
                            </div>
                            @if(request('product_status') !== null || request('product_approval') !== null)
                            <div class="col-md-3">
                                <a href="{{ route('admin.vendor-applications.show', $application->id) }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            </div>
                            @endif
                        </div>
                    </form>

                    <!-- Filter Status Display -->
                    @if(request('product_status') !== null || request('product_approval') !== null)
                    <div class="alert alert-light border mb-3">
                        <small>
                            <strong>Active Filters:</strong>
                            @if(request('product_status') !== null)
                                <span class="badge bg-primary">Status: {{ request('product_status') == '1' ? 'Active' : 'Inactive' }}</span>
                            @endif
                            @if(request('product_approval') !== null)
                                <span class="badge bg-info">Approval: {{ request('product_approval') == '1' ? 'Approved' : 'Pending' }}</span>
                            @endif
                            | <strong>Results:</strong> {{ $products->total() }} product(s)
                        </small>
                    </div>
                    @endif

                    @if($products->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()" class="form-check-input">
                                    </th>
                                    <th width="80">Image</th>
                                    <th>Product Name</th>
                                    <th width="120">SKU</th>
                                    <th width="100">Price</th>
                                    <th width="100">Stock</th>
                                    <th width="120">Status</th>
                                    <th width="130">Approval</th>
                                    <th width="200">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input product-checkbox"
                                               value="{{ $product->id }}"
                                               onchange="updateBulkActions()">
                                    </td>
                                    <td>
                                        @if($product->product_thumbnail)
                                        <img src="{{ $product->product_thumbnail->image_url }}"
                                             alt="{{ $product->name }}"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        @else
                                        <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $product->name }}</strong>
                                        <br>
                                        <small class="text-muted">ID: {{ $product->id }}</small>
                                    </td>
                                    <td>{{ $product->sku ?? 'N/A' }}</td>
                                    <td>${{ number_format($product->price, 2) }}</td>
                                    <td>
                                        @if($product->quantity > 0)
                                            <span class="badge bg-success">{{ $product->quantity }}</span>
                                        @else
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->status)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->is_approved)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle"></i> Approved
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            @if(!$product->is_approved)
                                            <form method="POST" action="{{ route('admin.vendor-applications.products.approve', [$application->id, $product->id]) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="approve" value="1">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                            @else
                                            <form method="POST" action="{{ route('admin.vendor-applications.products.approve', [$application->id, $product->id]) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="approve" value="0">
                                                <button type="submit" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-times"></i> Unapprove
                                                </button>
                                            </form>
                                            @endif
                                            <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-3">
                        {{ $products->links() }}
                    </div>
                    @else
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i>
                        @if(request('product_status') !== null || request('product_approval') !== null)
                            No products found matching the selected filters.
                        @else
                            This vendor has not added any products yet.
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Ban Modal -->
<div class="modal fade" id="banModal" tabindex="-1" aria-labelledby="banModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.vendor-applications.ban', $application->id) }}">
                @csrf

                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="banModalLabel">
                        <i class="fas fa-ban"></i> Ban Vendor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning!</strong> Banning this vendor will:
                        <ul class="mb-0 mt-2">
                            <li>Deactivate their store</li>
                            <li>Disable ALL their products immediately</li>
                            <li>Prevent them from accessing the seller dashboard</li>
                            <li>Send them an email notification</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="ban_reason" class="form-label">Reason for Ban <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="ban_reason" name="ban_reason" rows="4"
                                  required placeholder="Explain why this vendor is being banned..."></textarea>
                        <small class="text-muted">This reason will be included in the email sent to the vendor.</small>
                    </div>

                    <p class="text-muted mb-0">
                        <strong>Store:</strong> {{ $application->store_name }}<br>
                        <strong>Total Products:</strong> {{ $application->total_products_count ?? 0 }}
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban"></i> Confirm Ban
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.vendor-applications.approve', $application->id) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="is_approved" value="0">

                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Reject Vendor Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> You are about to reject this vendor application. An email will be sent to the applicant.
                    </div>

                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Reason for Rejection (Optional)</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="4"
                                  placeholder="Explain why the application is being rejected..."></textarea>
                        <small class="text-muted">This reason will be included in the email sent to the applicant.</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
#bulkActionsBar {
    background: #f8f9fa;
    padding: 8px 15px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-checkbox, #selectAll {
    cursor: pointer;
    width: 18px;
    height: 18px;
}

.product-checkbox:checked, #selectAll:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

tbody tr:has(.product-checkbox:checked) {
    background-color: #e7f3ff !important;
}
</style>
@endpush

@push('scripts')
<script>
function toggleVendorInfo() {
    const section = document.getElementById('vendorInfoSection');
    const icon = document.getElementById('toggleIcon');
    const text = document.getElementById('toggleText');

    if (section.style.display === 'none') {
        section.style.display = 'block';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        text.textContent = 'Hide Vendor Details';
    } else {
        section.style.display = 'none';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        text.textContent = 'Show Vendor Details';
    }
}

// Bulk product actions
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.product-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });

    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    const selectAll = document.getElementById('selectAll');
    const allCheckboxes = document.querySelectorAll('.product-checkbox');

    // Update selected count
    selectedCount.textContent = checkboxes.length + ' selected';

    // Show/hide bulk actions bar
    if (checkboxes.length > 0) {
        bulkActionsBar.style.display = 'block';
    } else {
        bulkActionsBar.style.display = 'none';
    }

    // Update select all checkbox state
    if (checkboxes.length === allCheckboxes.length) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else if (checkboxes.length > 0) {
        selectAll.checked = false;
        selectAll.indeterminate = true;
    } else {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
}

function getSelectedProductIds() {
    const checkboxes = document.querySelectorAll('.product-checkbox:checked');
    return Array.from(checkboxes).map(cb => cb.value);
}

function clearSelection() {
    document.getElementById('selectAll').checked = false;
    document.querySelectorAll('.product-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateBulkActions();
}

function bulkApprove() {
    const productIds = getSelectedProductIds();

    if (productIds.length === 0) {
        showWarning('No Selection', 'Please select at least one product');
        return;
    }

    confirmAction(
        'Approve Products?',
        `Approve ${productIds.length} selected product(s)?`,
        'Yes, approve'
    ).then((result) => {
        if (result.isConfirmed) {
            bulkUpdateProducts(productIds, true);
        }
    });
}

function bulkDisapprove() {
    const productIds = getSelectedProductIds();

    if (productIds.length === 0) {
        showWarning('No Selection', 'Please select at least one product');
        return;
    }

    confirmAction(
        'Disapprove Products?',
        `Disapprove ${productIds.length} product(s)?`,
        'Yes, disapprove',
        'warning'
    ).then((result) => {
        if (result.isConfirmed) {
            bulkUpdateProducts(productIds, false);
        }
    });
}

function bulkUpdateProducts(productIds, approve) {
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("admin.vendor-applications.products.bulk-approve", $application->id) }}';

    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    // Add product IDs
    productIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'product_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    // Add approve flag
    const approveInput = document.createElement('input');
    approveInput.type = 'hidden';
    approveInput.name = 'approve';
    approveInput.value = approve ? '1' : '0';
    form.appendChild(approveInput);

    // Submit form
    document.body.appendChild(form);
    form.submit();
}

function approveAllProducts() {
    const totalProducts = {{ $products->total() }};

    if (totalProducts === 0) {
        showWarning('No Products', 'No products to approve');
        return;
    }

    confirmAction(
        'Approve All Products?',
        `Are you sure you want to approve ALL ${totalProducts} product(s) for this vendor?\n\nThis will approve all products regardless of filters or pagination.`,
        'Yes, approve all'
    ).then((result) => {
        if (result.isConfirmed) {
            const loading = showLoadingToast('Approving all products...');

            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.vendor-applications.products.approve-all", $application->id) }}';

            // Add CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            // Submit form
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Vendor Application Action Handlers
function handleApprove(event) {
    event.preventDefault();

    confirmAction(
        'Approve Application?',
        'Approve this vendor application? This will activate the vendor account.',
        'Yes, approve'
    ).then((result) => {
        if (result.isConfirmed) {
            submitFormWithLoading(event.target, {
                successTitle: 'Approved!',
                successText: 'Vendor application has been approved'
            });
        }
    });

    return false;
}

function handleReject() {
    Swal.fire({
        title: 'Reject Application?',
        html: `
            <div class="text-start">
                <p>Please provide a reason for rejecting this application:</p>
                <div class="mb-3">
                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea id="rejection_reason" class="form-control" rows="4" placeholder="e.g., Incomplete documentation, Invalid business registration..." required></textarea>
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-times"></i> Reject Application',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: () => {
            const rejection_reason = document.getElementById('rejection_reason').value.trim();

            if (!rejection_reason) {
                Swal.showValidationMessage('Rejection reason is required');
                return false;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.vendor-applications.reject", $application->id) }}';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PUT';
            form.appendChild(methodInput);

            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'rejection_reason';
            reasonInput.value = rejection_reason;
            form.appendChild(reasonInput);

            document.body.appendChild(form);
            form.submit();

            return new Promise(() => {}); // Keep loading state
        }
    });
}

function handleBan() {
    Swal.fire({
        title: 'Ban Vendor?',
        html: `
            <div class="text-start">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning!</strong> Banning this vendor will:
                    <ul class="mb-0 mt-2">
                        <li>Deactivate their store</li>
                        <li>Disable ALL their products immediately</li>
                        <li>Prevent them from accessing the seller dashboard</li>
                        <li>Send them an email notification</li>
                    </ul>
                </div>
            </div>
        `,
        input: 'textarea',
        inputLabel: 'Ban Reason (Required)',
        inputPlaceholder: 'Enter the reason for banning this vendor (e.g., Terms violation, Fraudulent activity, etc.)',
        inputAttributes: {
            'aria-label': 'Ban reason',
            'rows': 4
        },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-ban"></i> Ban Vendor',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        inputValidator: (value) => {
            if (!value) {
                return 'Ban reason is required!';
            }
            if (value.trim().length < 10) {
                return 'Ban reason must be at least 10 characters!';
            }
            return null;
        },
        preConfirm: (ban_reason) => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.vendor-applications.ban", $application->id) }}';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'ban_reason';
            reasonInput.value = ban_reason;
            form.appendChild(reasonInput);

            document.body.appendChild(form);
            form.submit();

            return new Promise(() => {}); // Keep loading state
        }
    });
}

function handleUnban(event) {
    event.preventDefault();

    confirmAction(
        'Unban Vendor?',
        'Remove the ban from this vendor? They will be able to access their account again.',
        'Yes, unban',
        'question'
    ).then((result) => {
        if (result.isConfirmed) {
            submitFormWithLoading(event.target, {
                successTitle: 'Unbanned!',
                successText: 'Vendor has been unbanned successfully'
            });
        }
    });

    return false;
}
</script>
@endpush

