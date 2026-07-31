@extends('admin.layout')

@section('title', 'Roles Management')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-shield-fill-check"></i> Roles Management</h2>
            <p class="text-muted mb-0">Manage roles and their permissions</p>
        </div>
        <div>
            @can('role.create')
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New Role
            </a>
            @endcan
        </div>
    </div>

    <!-- Roles Grid -->
    <div class="row">
        @foreach($roles as $role)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-gradient text-black" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check"></i> {{ ucfirst($role->name) }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="mb-0 text-primary">{{ $role->users_count }}</h3>
                                <small class="text-muted">Users</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="mb-0 text-success">{{ $role->permissions_count }}</h3>
                                <small class="text-muted">Permissions</small>
                            </div>
                        </div>
                    </div>

                    @if($role->permissions_count > 0)
                    <div class="mb-3">
                        <h6 class="text-muted mb-2">Top Permissions:</h6>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($role->permissions->take(5) as $permission)
                                <span class="badge bg-secondary">{{ $permission->name }}</span>
                            @endforeach
                            @if($role->permissions_count > 5)
                                <span class="badge bg-info">+{{ $role->permissions_count - 5 }} more</span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between">
                        @can('role.edit')
                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        @endcan

                        @can('role.destroy')
                        @if($role->name !== 'admin')
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="deleteRole({{ $role->id }}, '{{ $role->name }}')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                        @else
                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                            <i class="bi bi-lock-fill"></i> Protected
                        </button>
                        @endif
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        @if($roles->count() === 0)
        <div class="col-12">
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <p class="text-muted mt-3">No roles found.</p>
                @can('role.create')
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create First Role
                </a>
                @endcan
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Delete Form (Hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
async function deleteRole(id, name) {
    const _r = await Swal.fire({ title: 'Are you sure?', text: `Are you sure you want to delete role "${name}"?\n\nUsers with this role will lose its permissions.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (_r.isConfirmed) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/roles/${id}`;
        form.submit();
    }
}
</script>
@endpush

