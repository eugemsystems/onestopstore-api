@extends('admin.layout')

@section('title', 'Attributes')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-sliders"></i> Attributes</h2>
            <p class="text-muted mb-0">Manage product attributes (Color, Size, Material, etc.)</p>
        </div>
        @can('attribute.create')
        <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Attribute
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.attributes.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search attributes..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Style</label>
                    <select name="style" class="form-select">
                        <option value="">All</option>
                        <option value="dropdown" {{ request('style') == 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                        <option value="radio" {{ request('style') == 'radio' ? 'selected' : '' }}>Radio</option>
                        <option value="swatch" {{ request('style') == 'swatch' ? 'selected' : '' }}>Swatch</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15</option>
                        <option value="30" {{ request('per_page') == 30 ? 'selected' : '' }}>30</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    @can('attribute.destroy')
    <div class="card mb-3" id="bulk-actions" style="display: none;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <span><strong><span id="selected-count">0</span></strong> item(s) selected</span>
                <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()">
                    <i class="bi bi-trash"></i> Delete Selected
                </button>
            </div>
        </div>
    </div>
    @endcan

    <!-- Attributes Table -->
    <div class="card">
        <div class="card-body">
            @if($attributes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                @can('attribute.destroy')
                                <th width="50">
                                    <input type="checkbox" id="select-all" class="form-check-input">
                                </th>
                                @endcan
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Style</th>
                                <th>Values Count</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attributes as $attribute)
                            <tr>
                                @can('attribute.destroy')
                                <td>
                                    <input type="checkbox" class="form-check-input attribute-checkbox" value="{{ $attribute->id }}">
                                </td>
                                @endcan
                                <td>
                                    <strong>{{ $attribute->name }}</strong>
                                </td>
                                <td>
                                    <code>{{ $attribute->slug }}</code>
                                </td>
                                <td>
                                    @if($attribute->style == 'dropdown')
                                        <span class="badge bg-primary">Dropdown</span>
                                    @elseif($attribute->style == 'radio')
                                        <span class="badge bg-info">Radio</span>
                                    @elseif($attribute->style == 'swatch')
                                        <span class="badge bg-warning">Swatch</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($attribute->style) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $attribute->attribute_values_count }}</span>
                                </td>
                                <td>
                                    @can('attribute.edit')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox"
                                                   data-id="{{ $attribute->id }}"
                                                   {{ $attribute->status ? 'checked' : '' }}
                                                   onchange="toggleStatus({{ $attribute->id }})">
                                        </div>
                                    @else
                                        @if($attribute->status)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    @endcan
                                </td>
                                <td>
                                    <small class="text-muted">{{ $attribute->created_at->format('M d, Y') }}</small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.attributes.show', $attribute->id) }}"
                                           class="btn btn-outline-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @can('attribute.edit')
                                        <a href="{{ route('admin.attributes.edit', $attribute->id) }}"
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @endcan
                                        @can('attribute.destroy')
                                        <button type="button" class="btn btn-outline-danger"
                                                onclick="deleteAttribute({{ $attribute->id }})" title="Delete">
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
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        Showing {{ $attributes->firstItem() }} to {{ $attributes->lastItem() }} of {{ $attributes->total() }} entries
                    </div>
                    <div>
                        {{ $attributes->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No attributes found</p>
                    @can('attribute.create')
                    <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Create First Attribute
                    </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Select All functionality
document.getElementById('select-all')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.attribute-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});

// Individual checkbox change
document.querySelectorAll('.attribute-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkActions);
});

function updateBulkActions() {
    const checked = document.querySelectorAll('.attribute-checkbox:checked').length;
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    if (bulkActions && selectedCount) {
        bulkActions.style.display = checked > 0 ? 'block' : 'none';
        selectedCount.textContent = checked;
    }
}

// Toggle Status
async function toggleStatus(id) {
    try {
        const response = await fetch(`/admin/attributes/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Status toggle handled by checkbox
        } else {
            showError('Error: ' + data.message);
            // Revert checkbox
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to update status');
        location.reload();
    }
}

// Delete Single Attribute
async function deleteAttribute(id) {
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Are you sure you want to delete this attribute? This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/admin/attributes/${id}`;
    form.innerHTML = `
        @csrf
        @method('DELETE')
    `;
    document.body.appendChild(form);
    form.submit();
}

// Bulk Delete
async function bulkDelete() {
    const checked = Array.from(document.querySelectorAll('.attribute-checkbox:checked'))
        .map(cb => parseInt(cb.value));

    if (checked.length === 0) {
        showWarning('Please select at least one attribute');
        return;
    }

    const _r = await Swal.fire({ title: 'Are you sure?', text: `Are you sure you want to delete ${checked.length} attribute(s)? This action cannot be undone.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    try {
        const response = await fetch('{{ route("admin.attributes.bulk-delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ ids: checked })
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            showError('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to delete attributes');
    }
}
</script>
@endpush
@endsection
