@extends('admin.layout')

@section('title', 'Users Management')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-people-fill"></i> Users Management</h2>
            <p class="text-muted mb-0">Manage users, roles, and permissions</p>
        </div>
        <div>
            @can('user.create')
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New User
            </a>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Users ({{ $users->total() }})</h5>
                @can('user.edit')
                <div id="bulk-actions" style="display: none;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted"><span id="selected-count">0</span> selected</span>
                        <select id="bulk-branch" class="form-select form-select-sm" style="width: 150px;">
                            <option value="">Select Branch</option>
                            <option value="None">None</option>
                            <option value="Harare">Harare</option>
                            <option value="Bulawayo">Bulawayo</option>
                            <option value="Mutare">Mutare</option>
                            <option value="Zambia">Zambia</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-primary" onclick="bulkUpdateBranch()">
                            <i class="bi bi-check-circle"></i> Update Branch
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                            <i class="bi bi-x"></i> Clear
                        </button>
                    </div>
                </div>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                @can('user.edit')
                                <th style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="select-all" onchange="toggleSelectAll(this)">
                                </th>
                                @endcan
                                <th>User Info</th>
                                <th>Branch & Roles</th>
                                <th>Permissions & Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                @can('user.edit')
                                <td>
                                    <input type="checkbox" class="form-check-input user-checkbox" value="{{ $user->id }}" onchange="updateSelectionCount()">
                                </td>
                                @endcan
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <strong>{{ $user->name }}</strong>
                                            @if($user->id === auth()->id())
                                                <span class="badge bg-info">You</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">{{ $user->email }}</small>
                                            <br>
                                            <small class="text-muted">#{{ $user->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $branchColors = [
                                            'None' => 'bg-secondary',
                                            'Harare' => 'bg-primary',
                                            'Bulawayo' => 'bg-success',
                                            'Mutare' => 'bg-warning text-dark',
                                            'Zambia' => 'bg-danger',
                                        ];
                                        $branch = $user->branch ?? 'None';
                                        $badgeClass = $branchColors[$branch] ?? 'bg-secondary';
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $branch }}</span>
                                    <br>
                                    @forelse($user->roles as $role)
                                        <span class="badge bg-primary small">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-muted small">No roles</span>
                                    @endforelse
                                </td>
                                <td>
                                    @php
                                        $directPermissions = $user->permissions->count();
                                    @endphp
                                    @if($directPermissions > 0)
                                        <span class="badge bg-secondary">{{ $directPermissions }} permissions</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">{{ $user->created_at->format('M d, Y') }}</small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        @can('user.edit')
                                        <a href="{{ route('admin.users.edit', $user->id) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Edit">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        @endcan
                                        @can('user.destroy')
                                        @if($user->id !== auth()->id())
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $users->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-3">No users found.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Form (Hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<style>
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
    }
</style>
@endsection

@push('scripts')
<script>
async function deleteUser(id, name) {
    const _r = await Swal.fire({ title: 'Are you sure?', text: `Are you sure you want to delete user "${name}"?\n\nThis action cannot be undone.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (_r.isConfirmed) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/users/${id}`;
        form.submit();
    }
}

// Bulk operations
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectionCount();
}

function updateSelectionCount() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const count = checkboxes.length;
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    if (count > 0) {
        bulkActions.style.display = 'block';
        selectedCount.textContent = count;
    } else {
        bulkActions.style.display = 'none';
    }
}

function clearSelection() {
    document.getElementById('select-all').checked = false;
    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateSelectionCount();
}

async function bulkUpdateBranch() {
    const selectedBranch = document.getElementById('bulk-branch').value;

    if (!selectedBranch) {
        showWarning('Warning', 'Please select a branch first.');
        return;
    }

    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const userIds = Array.from(checkboxes).map(cb => cb.value);

    if (userIds.length === 0) {
        showWarning('Warning', 'Please select at least one user.');
        return;
    }

    const _r = await Swal.fire({ title: 'Are you sure?', text: `Update branch to "${selectedBranch}" for ${userIds.length} user(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) {
        return;
    }

    try {
        const response = await fetch('{{ route("admin.users.bulk-update-branch") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                user_ids: userIds,
                branch: selectedBranch
            })
        });

        const data = await response.json();

        if (data.success) {
            showSuccess('Success', data.message);
            window.location.reload();
        } else {
            showError('Error', data.message || 'Failed to update branches');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Error', 'Failed to update branches. Please try again.');
    }
}
</script>
@endpush

