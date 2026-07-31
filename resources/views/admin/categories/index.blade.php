@extends('admin.layout')

@section('title', 'Categories Management')

@push('styles')
<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #e3f2fd !important;
    }
    .sortable-chosen {
        background: #f0f7ff !important;
    }
    .sortable-drag {
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    .drag-handle {
        cursor: grab;
        color: #adb5bd;
        font-size: 1.2rem;
        padding: 4px 8px;
        transition: color 0.2s;
    }
    .drag-handle:hover {
        color: #0d6efd;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
    .position-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f8f9fa;
        border: 2px solid #dee2e6;
        font-weight: 700;
        font-size: 0.85rem;
        color: #495057;
    }
    .position-badge.changed {
        background: #fff3cd;
        border-color: #ffc107;
        color: #856404;
    }
    #saveOrderBtn {
        transition: all 0.3s;
    }
    #saveOrderBtn.has-changes {
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(25, 135, 84, 0); }
        100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
    }
    .category-row {
        transition: background 0.2s;
    }
    .category-row:hover .drag-handle {
        color: #0d6efd;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-tags-fill"></i> Categories Management</h2>
            <p class="text-muted mb-0">Manage parent categories — drag &amp; drop to reorder</p>
        </div>
        <div>
            @can('category.edit')
            <button type="button" class="btn btn-success d-none" id="saveOrderBtn" onclick="saveOrder()">
                <i class="bi bi-save"></i> Save Order
            </button>
            <button type="button" class="btn btn-outline-secondary d-none" id="resetOrderBtn" onclick="resetOrder()">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </button>
            @endcan
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle"></i>
        <strong>Tip:</strong> Drag the <i class="bi bi-grip-vertical"></i> handle to reorder categories. The position numbers update automatically.
        Click <strong>Save Order</strong> to persist changes. Commission rate updates apply to all child categories.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Categories Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Parent Categories</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark d-none" id="changesIndicator">
                        <i class="bi bi-exclamation-circle"></i> Unsaved changes
                    </span>
                    <span class="badge bg-primary">{{ $categories->total() }} Categories</span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 3%"></th>
                            <th style="width: 5%">#</th>
                            <th style="width: 5%">ID</th>
                            <th style="width: 27%">Category Name</th>
                            <th style="width: 17%">Slug</th>
                            <th style="width: 13%">Commission Rate</th>
                            <th style="width: 10%">Children</th>
                            <th style="width: 10%">Status</th>
                            <th style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categorySortable">
                        @forelse($categories as $index => $category)
                        <tr class="category-row" data-id="{{ $category->id }}" data-original-order="{{ $category->sort_order }}">
                            <td class="align-middle text-center">
                                <span class="drag-handle" title="Drag to reorder">
                                    <i class="bi bi-grip-vertical"></i>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <span class="position-badge" data-position>{{ $category->sort_order }}</span>
                            </td>
                            <td class="align-middle">{{ $category->id }}</td>
                            <td class="align-middle">
                                <strong>{{ $category->name }}</strong>
                            </td>
                            <td class="align-middle">
                                <code class="small">{{ $category->slug }}</code>
                            </td>
                            <td class="align-middle">
                                @if($category->commission_rate)
                                    <span class="badge bg-success">{{ $category->commission_rate }}%</span>
                                @else
                                    <span class="badge bg-secondary">Not Set</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-info">{{ $category->children_count }} children</span>
                            </td>
                            <td class="align-middle">
                                @if($category->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="align-middle">
                                @can('category.edit')
                                <button type="button" class="btn btn-sm btn-primary" onclick="editCategory({{ $category->id }})">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted">No parent categories found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($categories->hasPages())
        <div class="card-footer bg-white">
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editCategoryModalLabel">
                    <i class="bi bi-pencil-square"></i> Edit Category
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCategoryForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="categoryId">

                    <!-- Category Name (Read-only) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Name</label>
                        <input type="text" class="form-control bg-light" id="categoryName" readonly disabled>
                        <small class="form-text text-muted">
                            <i class="bi bi-lock"></i> Category name cannot be edited.
                        </small>
                    </div>

                    <!-- Slug (Read-only) -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Slug</label>
                        <input type="text" class="form-control bg-light" id="categorySlug" readonly disabled>
                        <small class="form-text text-muted">
                            <i class="bi bi-lock"></i> Slug is auto-generated and cannot be edited.
                        </small>
                    </div>

                    <!-- Commission Rate -->
                    <div class="mb-3">
                        <label for="commissionRate" class="form-label fw-bold">
                            Commission Rate (%) <span class="text-danger">*</span>
                        </label>
                        <input type="number"
                               class="form-control"
                               id="commissionRate"
                               name="commission_rate"
                               min="0"
                               max="100"
                               step="0.01"
                               required>
                        <small class="form-text text-muted">
                            Enter the commission rate percentage (0-100). This will be applied to all child categories.
                        </small>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label for="categoryStatus" class="form-label fw-bold">
                            Status <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="categoryStatus" name="status" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <small class="form-text text-muted">
                            Status will be applied to all child categories.
                        </small>
                    </div>

                    <!-- Children Count Info -->
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i>
                        <strong id="childrenInfo">Loading...</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- SortableJS from CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
let editModal;
let sortableInstance;
let hasChanges = false;

document.addEventListener('DOMContentLoaded', function() {
    editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));

    // Form submission
    document.getElementById('editCategoryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveCategory();
    });

    // Initialize SortableJS on the table body
    const tbody = document.getElementById('categorySortable');
    if (tbody && tbody.children.length > 0) {
        sortableInstance = Sortable.create(tbody, {
            handle: '.drag-handle',
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function() {
                updatePositionBadges();
                markChanges();
            }
        });
    }

    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

function updatePositionBadges() {
    const rows = document.querySelectorAll('#categorySortable .category-row');
    rows.forEach((row, index) => {
        const badge = row.querySelector('[data-position]');
        if (badge) {
            badge.textContent = index + 1;
            // Highlight changed rows
            const originalOrder = parseInt(row.dataset.originalOrder) || 0;
            if ((index + 1) !== originalOrder) {
                badge.classList.add('changed');
            } else {
                badge.classList.remove('changed');
            }
        }
    });
}

function markChanges() {
    hasChanges = true;
    const saveBtn = document.getElementById('saveOrderBtn');
    const resetBtn = document.getElementById('resetOrderBtn');
    const indicator = document.getElementById('changesIndicator');

    if (saveBtn) {
        saveBtn.classList.remove('d-none');
        saveBtn.classList.add('has-changes');
    }
    if (resetBtn) resetBtn.classList.remove('d-none');
    if (indicator) indicator.classList.remove('d-none');
}

function clearChanges() {
    hasChanges = false;
    const saveBtn = document.getElementById('saveOrderBtn');
    const resetBtn = document.getElementById('resetOrderBtn');
    const indicator = document.getElementById('changesIndicator');

    if (saveBtn) {
        saveBtn.classList.add('d-none');
        saveBtn.classList.remove('has-changes');
    }
    if (resetBtn) resetBtn.classList.add('d-none');
    if (indicator) indicator.classList.add('d-none');
}

function resetOrder() {
    // Reload page to get original order
    window.location.reload();
}

function saveOrder() {
    const rows = document.querySelectorAll('#categorySortable .category-row');
    const order = [];

    rows.forEach((row, index) => {
        order.push({
            id: parseInt(row.dataset.id),
            position: index + 1
        });
    });

    const saveBtn = document.getElementById('saveOrderBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    fetch(`{{ route('admin.categories.reorder') }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ order: order })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            clearChanges();

            // Update original order data attributes so badges reflect the saved state
            rows.forEach((row, index) => {
                row.dataset.originalOrder = index + 1;
            });
            updatePositionBadges();

            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Order';
        } else {
            showToast(data.message || 'Failed to save order', 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Order';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while saving order', 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Order';
    });
}

function editCategory(categoryId) {
    document.getElementById('categoryName').value = 'Loading...';
    document.getElementById('categorySlug').value = 'Loading...';
    document.getElementById('commissionRate').value = '';
    document.getElementById('childrenInfo').textContent = 'Loading category details...';

    editModal.show();

    fetch(`{{ url('admin/categories') }}/${categoryId}/edit`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const category = data.category;

            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categorySlug').value = category.slug;
            document.getElementById('commissionRate').value = category.commission_rate || 0;
            document.getElementById('categoryStatus').value = category.status ? '1' : '0';

            const childrenCount = category.children_count;
            if (childrenCount > 0) {
                document.getElementById('childrenInfo').innerHTML =
                    `This category has <strong>${childrenCount} child categories</strong>. ` +
                    `When you update the commission rate, all ${childrenCount} children will be updated automatically.`;
            } else {
                document.getElementById('childrenInfo').textContent =
                    'This category has no child categories.';
            }
        } else {
            showToast('Failed to load category details', 'error');
            editModal.hide();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while loading category details', 'error');
        editModal.hide();
    });
}

function saveCategory() {
    const categoryId = document.getElementById('categoryId').value;
    const commissionRate = document.getElementById('commissionRate').value;
    const status = document.getElementById('categoryStatus').value;
    const saveBtn = document.getElementById('saveBtn');

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    fetch(`{{ url('admin/categories') }}/${categoryId}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            commission_rate: commissionRate,
            status: status,
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            editModal.hide();

            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Failed to update category', 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Changes';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while saving', 'error');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-save"></i> Save Changes';
    });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '9999';

    const bgClass = type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info';
    const icon = type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle';

    toast.innerHTML = `
        <div class="alert alert-${bgClass} alert-dismissible fade show" role="alert">
            <i class="bi bi-${icon}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);
}
</script>
@endpush

