@extends('admin.layout')

@section('title', 'Vendor Products - Admin Panel')

@push('styles')
<style>
    .bulk-actions-bar {
        display: none;
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 30px;
        border-radius: 50px;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        z-index: 1000;
        animation: slideUp 0.3s ease;
    }

    .bulk-actions-bar.show {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    @keyframes slideUp {
        from {
            transform: translateX(-50%) translateY(100px);
            opacity: 0;
        }
        to {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    }

    .select-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .category-tree {
        max-height: 400px;
        overflow-y: auto;
    }

    .category-option {
        padding: 5px 0;
    }

    .category-option.parent {
        font-weight: 600;
        color: #062a6a;
    }

    .category-option.child {
        padding-left: 25px;
        font-size: 0.95em;
    }

    .category-option.grandchild {
        padding-left: 50px;
        font-size: 0.9em;
        color: #666;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-shop me-2"></i>Vendor Products</h2>
            <p class="text-muted mb-0">Products added by vendors through their stores</p>
        </div>
    </div>

    <!-- Bulk Actions Bar (Fixed at bottom) -->
    <div class="bulk-actions-bar" id="bulkActionsBar">
        <span id="selectedCount">0</span> selected
        <button type="button" class="btn btn-sm btn-light" onclick="bulkAction('approve')">
            <i class="bi bi-check-circle"></i> Approve
        </button>
        <button type="button" class="btn btn-sm btn-light" onclick="bulkAction('disapprove')">
            <i class="bi bi-x-circle"></i> Disapprove
        </button>
        <button type="button" class="btn btn-sm btn-light" onclick="bulkAction('activate')">
            <i class="bi bi-toggle-on"></i> Activate
        </button>
        <button type="button" class="btn btn-sm btn-light" onclick="bulkAction('deactivate')">
            <i class="bi bi-toggle-off"></i> Deactivate
        </button>
        @can('product.destroy')
            <button type="button" class="btn btn-sm btn-danger" onclick="bulkAction('delete')">
                <i class="bi bi-trash"></i> Delete
            </button>
        @endcan
        <button type="button" class="btn btn-sm btn-outline-light" onclick="clearSelection()">
            <i class="bi bi-x"></i> Clear
        </button>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.products.vendor-products') }}" id="filterForm">
                <div class="row g-3">
                    <!-- Search -->
                    <div class="col-md-3">
                        <label class="form-label">Search by SKU or Product Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Enter SKU or product name..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <!-- Approval Status Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Approval Status</label>
                        <select name="is_approved" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('is_approved') === '1' ? 'selected' : '' }}>Approved</option>
                            <option value="0" {{ request('is_approved') === '0' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>

                    <!-- Store/Vendor Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Vendor Store</label>
                        <select name="store_id" class="form-select">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Category Filter (Using Cached Hierarchical Tree) -->
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category['id'] }}" {{ request('category_id') == $category['id'] ? 'selected' : '' }}>
                                    {{ $category['path'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.products.vendor-products') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-body">
            @if($products->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="productsTable">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" class="select-checkbox" id="selectAll">
                                </th>
                                <th>Image</th>
                                <th>SKU</th>
                                <th>Product Name</th>
                                <th>Vendor Store</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Approval</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="select-checkbox product-checkbox"
                                               data-id="{{ $product->id }}" value="{{ $product->id }}">
                                    </td>
                                    <td>
                                        @if($product->product_thumbnail)
                                            <img src="{{ $product->product_thumbnail->image_url }}"
                                                 alt="{{ $product->name }}"
                                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                        @else
                                            <div style="width: 50px; height: 50px; background: #e9ecef; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td><code>{{ $product->sku ?: 'N/A' }}</code></td>
                                    <td>
                                        <strong>{{ $product->name }}</strong>
                                        @if($product->is_featured)
                                            <span class="badge bg-warning text-dark ms-1">Featured</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->store)
                                            <span class="badge bg-info">{{ $product->store->store_name }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->sale_price && $product->sale_price < $product->price)
                                            <span class="text-muted text-decoration-line-through">${{ number_format($product->price, 2) }}</span><br>
                                            <strong class="text-success">${{ number_format($product->sale_price, 2) }}</strong>
                                        @else
                                            <strong>${{ number_format($product->price, 2) }}</strong>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->stock_status === 'in_stock')
                                            <span class="badge bg-success">{{ $product->quantity }} In Stock</span>
                                        @else
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($product->is_approved)
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @can('product.edit')
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                       data-id="{{ $product->id }}"
                                                       {{ $product->status ? 'checked' : '' }}>
                                            </div>
                                        @else
                                            <span class="badge bg-{{ $product->status ? 'success' : 'secondary' }}">
                                                {{ $product->status ? 'Active' : 'Inactive' }}
                                            </span>
                                        @endcan
                                    </td>
                                    <td>{{ $product->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('product.edit')
                                                <a href="{{ route('admin.products.edit', $product->id) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endcan

                                            @can('product.destroy')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger delete-product"
                                                        data-id="{{ $product->id }}"
                                                        data-name="{{ $product->name }}"
                                                        title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                    <h5 class="mt-3">No vendor products found</h5>
                    <p class="text-muted">Try adjusting your filters or search terms.</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Bulk selection
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    updateBulkActionsBar();
});

document.querySelectorAll('.product-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActionsBar);
});

function updateBulkActionsBar() {
    const selected = document.querySelectorAll('.product-checkbox:checked');
    const bulkBar = document.getElementById('bulkActionsBar');
    const countSpan = document.getElementById('selectedCount');

    if (selected.length > 0) {
        bulkBar.classList.add('show');
        countSpan.textContent = selected.length;
    } else {
        bulkBar.classList.remove('show');
    }
}

function clearSelection() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkActionsBar();
}

function bulkAction(action) {
    const selected = Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);

    if (selected.length === 0) {
        showWarning('No Selection', 'Please select at least one product');
        return;
    }

    let confirmMessage = `Are you sure you want to ${action} ${selected.length} product(s)?`;

    Swal.fire({ title: 'Are you sure?', text: confirmMessage, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor }).then(_r => {
        if (!_r.isConfirmed) return;

        // TODO: Implement bulk actions via AJAX
        showWarning('Coming Soon', `Bulk ${action} functionality will be implemented soon`);
    });
}

// Status toggle
document.querySelectorAll('.status-toggle').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const productId = this.dataset.id;
        const newStatus = this.checked ? 1 : 0;

        fetch(`/admin/products/${productId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                this.checked = !newStatus;
                showError('Error', 'Failed to update status');
            }
        })
        .catch(() => {
            this.checked = !newStatus;
            showError('Error', 'Error updating status');
        });
    });
});

// Delete product
document.querySelectorAll('.delete-product').forEach(button => {
    button.addEventListener('click', function() {
        const productId = this.dataset.id;
        const productName = this.dataset.name;

        Swal.fire({ title: 'Are you sure?', text: `Are you sure you want to delete "${productName}"?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor }).then(_r => {
            if (_r.isConfirmed) {
                fetch(`/admin/products/${productId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        showError('Error', 'Failed to delete product');
                    }
                })
                .catch(() => showError('Error', 'Error deleting product'));
            }
        });
    });
});
</script>
@endpush
@endsection

