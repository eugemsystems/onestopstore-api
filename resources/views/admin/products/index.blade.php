@extends('admin.layout')

@section('title', 'Products - Admin Panel')

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
        <h2><i class="bi bi-box-seam me-2"></i>Products</h2>
        <div class="d-flex gap-2">
            @can('product.create')
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add New Product
                </a>
            @endcan
            <a href="{{ route('admin.product-feed.index') }}" class="btn btn-success">
                <i class="bi bi-rss-fill me-1"></i> Export Feed
            </a>
        </div>
    </div>

    <!-- Bulk Actions Bar (Fixed at bottom) -->
    <div class="bulk-actions-bar" id="bulkActionsBar">
        <span id="selectedCount">0</span> selected
        <button type="button" class="btn btn-sm btn-light" onclick="bulkAction('activate')">
            <i class="bi bi-check-circle"></i> Activate
        </button>
        <button type="button" class="btn btn-sm btn-light" onclick="bulkAction('deactivate')">
            <i class="bi bi-x-circle"></i> Deactivate
        </button>
        <button type="button" class="btn btn-sm btn-light" onclick="bulkAction('duplicate')">
            <i class="bi bi-files"></i> Duplicate
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
            <form method="GET" action="{{ route('admin.products.index') }}" id="filterForm">
                <div class="row g-3">
                    <!-- Search -->
                    <div class="col-md-4">
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

                    <!-- Stock Status Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Stock Status</label>
                        <select name="stock_status" class="form-select">
                            <option value="">All Stock</option>
                            <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                            <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                        </select>
                    </div>

                    <!-- Category Filter (Using Cached Hierarchical Tree) -->
                    <div class="col-md-2">
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

                    <!-- Region Filter -->
                    <div class="col-md-2">
                        <label class="form-label">Region</label>
                        <select name="region" class="form-select">
                            <option value="">All Regions</option>
                            <option value="zambia_only" {{ request('region') === 'zambia_only' ? 'selected' : '' }}>🇿🇲 Zambia Only</option>
                            <option value="zimbabwe_only" {{ request('region') === 'zimbabwe_only' ? 'selected' : '' }}>🇿🇼 Zimbabwe Only</option>
                            <option value="sa_only" {{ request('region') === 'sa_only' ? 'selected' : '' }}>🇿🇦 South Africa Only</option>
                            <option value="global" {{ request('region') === 'global' ? 'selected' : '' }}>🌍 Global (No Restriction)</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-1"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
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
                                <th width="40">
                                    <input type="checkbox" class="select-checkbox" id="selectAll">
                                </th>
                                <th>Product Info</th>
                                <th>Price & Stock</th>
                                <th>Status & Date</th>
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
                                        <div class="d-flex align-items-center">
                                            @if($product->product_thumbnail)
                                                <img src="{{ $product->product_thumbnail->image_url }}"
                                                     alt="{{ $product->name }}"
                                                     style="width: 45px; height: 45px; object-fit: cover; border-radius: 4px;" class="me-2">
                                            @else
                                                <div style="width: 45px; height: 45px; background: #e9ecef; border-radius: 4px; display: flex; align-items: center; justify-content: center;" class="me-2">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <strong>{{ $product->name }}</strong>
                                                @if($product->is_featured)
                                                    <span class="badge bg-warning text-dark">Featured</span>
                                                @endif
                                                <br><small class="text-muted"><code>{{ $product->sku ?: 'N/A' }}</code></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($product->sale_price && $product->sale_price < $product->price)
                                            <span class="text-muted text-decoration-line-through small">${{ number_format($product->price, 2) }}</span><br>
                                            <strong class="text-success">${{ number_format($product->sale_price, 2) }}</strong>
                                        @else
                                            <strong>${{ number_format($product->price, 2) }}</strong>
                                        @endif
                                        <br>
                                        @if($product->stock_status === 'in_stock')
                                            <span class="badge bg-success small">{{ $product->quantity }} In Stock</span>
                                        @else
                                            <span class="badge bg-danger small">Out of Stock</span>
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
                                        <br><small class="text-muted">{{ $product->created_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @can('product.edit')
                                                <a href="{{ route('admin.products.edit', $product->id) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endcan

                                            @can('product.edit')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-info duplicate-product"
                                                        data-id="{{ $product->id }}"
                                                        data-name="{{ $product->name }}"
                                                        title="Duplicate">
                                                    <i class="bi bi-files"></i>
                                                </button>
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
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
                    </div>
                    {{ $products->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #6c757d;"></i>
                    <h5 class="mt-3 text-muted">No products found</h5>
                    <p class="text-muted">Try adjusting your filters or search criteria</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="productName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Confirmation Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Bulk Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="bulkCount"></strong> selected products? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmBulkDelete()">Yes, Delete All</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');

    // Select All functionality
    selectAll?.addEventListener('change', function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionsBar();
    });

    // Individual checkbox change
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllState();
            updateBulkActionsBar();
        });
    });

    // Update Select All state
    function updateSelectAllState() {
        const checkedCount = document.querySelectorAll('.product-checkbox:checked').length;
        const totalCount = productCheckboxes.length;

        if (selectAll) {
            selectAll.checked = checkedCount === totalCount && totalCount > 0;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCount;
        }
    }

    // Update bulk actions bar visibility
    function updateBulkActionsBar() {
        const checkedCount = document.querySelectorAll('.product-checkbox:checked').length;
        selectedCount.textContent = checkedCount;

        if (checkedCount > 0) {
            bulkActionsBar.classList.add('show');
        } else {
            bulkActionsBar.classList.remove('show');
        }
    }

    // Status toggle
    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const productId = this.dataset.id;
            const isChecked = this.checked;

            fetch(`{{ route('admin.products.index') }}/${productId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Status updated successfully');
                } else {
                    this.checked = !isChecked;
                    showError('Error', 'Failed to update status');
                }
            })
            .catch(error => {
                this.checked = !isChecked;
                showError('Error', 'Failed to update status');
            });
        });
    });

    // Delete confirmation
    document.querySelectorAll('.delete-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const productName = this.dataset.name;

            document.getElementById('productName').textContent = productName;
            document.getElementById('deleteForm').action = `{{ route('admin.products.index') }}/${productId}`;

            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    // Duplicate product
    document.querySelectorAll('.duplicate-product').forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.id;
            const productName = this.dataset.name;

            Swal.fire({ title: 'Are you sure?', text: `Duplicate "${productName}"?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor }).then(_r => {
                if (_r.isConfirmed) { duplicateProducts([productId]); }
            });
        });
    });
});

// Clear selection
function clearSelection() {
    document.querySelectorAll('.product-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.getElementById('selectAll').checked = false;
    document.getElementById('bulkActionsBar').classList.remove('show');
}

// Bulk action handler
function bulkAction(action) {
    const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked'))
        .map(cb => cb.value);

    if (selectedIds.length === 0) {
        showWarning('No Selection', 'Please select at least one product');
        return;
    }

    switch(action) {
        case 'delete':
            document.getElementById('bulkCount').textContent = selectedIds.length;
            new bootstrap.Modal(document.getElementById('bulkDeleteModal')).show();
            break;
        case 'activate':
            bulkUpdateStatus(selectedIds, 1);
            break;
        case 'deactivate':
            bulkUpdateStatus(selectedIds, 0);
            break;
        case 'duplicate':
            duplicateProducts(selectedIds);
            break;
    }
}

// Confirm bulk delete
function confirmBulkDelete() {
    const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked'))
        .map(cb => cb.value);

    fetch('{{ route("admin.products.bulk-delete") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ ids: selectedIds })
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal')).hide();
        showSuccess('Deleted', `Successfully deleted ${selectedIds.length} products`);
        location.reload();
    })
    .catch(error => {
        showError('Error', 'Failed to delete products: ' + error.message);
        console.error(error);
    });
}

// Bulk update status
async function bulkUpdateStatus(ids, status) {
    const statusText = status ? 'activate' : 'deactivate';

    const _r = await Swal.fire({ title: 'Are you sure?', text: `${statusText} ${ids.length} selected products?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    // Update each product status
    Promise.all(ids.map(id =>
        fetch(`{{ route('admin.products.index') }}/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: status })
        })
    ))
    .then(() => {
        showSuccess('Success', `Successfully ${statusText}d ${ids.length} products`);
        location.reload();
    })
    .catch(error => {
        showError('Error', `Failed to ${statusText} products`);
        console.error(error);
    });
}

// Duplicate products
async function duplicateProducts(ids) {
    const _r = await Swal.fire({ title: 'Are you sure?', text: `Duplicate ${ids.length} selected product(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    fetch('{{ route("admin.products.bulk-duplicate") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        credentials: 'same-origin',
        body: JSON.stringify({ ids: ids })
    })
    .then(response => {
        // Parse response regardless of status to get error message
        return response.json().then(data => {
            if (!response.ok) {
                throw new Error(data.message || `HTTP error! status: ${response.status}`);
            }
            return data;
        });
    })
    .then(data => {
        showSuccess('Duplicated', data.message || `Successfully duplicated ${ids.length} product(s)`);
        location.reload();
    })
    .catch(error => {
        showError('Error', 'Failed to duplicate products: ' + error.message);
        console.error('Duplication error:', error);
    });
}
</script>
@endpush
@endsection

