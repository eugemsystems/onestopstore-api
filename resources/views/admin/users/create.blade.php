@extends('admin.layout')

@section('title', 'Create New User')

@push('styles')
<style>
    .permission-group {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: #f8f9fa;
    }
    .permission-group h6 {
        color: #667eea;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: capitalize;
    }
    .form-check {
        margin-bottom: 8px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-person-plus-fill"></i> Create New User</h2>
            <p class="text-muted mb-0">Add a new user with roles and permissions</p>
        </div>
        <div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf

        <div class="row">
            <!-- Basic Information -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="bi bi-person-fill"></i> Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            <small class="text-muted">Minimum 8 characters</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Roles and Permissions -->
            <div class="col-lg-8">
                <!-- Roles Section -->
                @can('user.assign-roles')
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="bi bi-shield-fill-check"></i> Assign Roles</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Select one or more roles for this user. Role permissions are inherited.</p>
                        <div class="row">
                            @foreach($roles as $role)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]"
                                           value="{{ $role->name }}" id="role_{{ $role->id }}"
                                           {{ old('roles') && in_array($role->name, old('roles')) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        <strong>{{ ucfirst($role->name) }}</strong>
                                        <br><small class="text-muted">{{ $role->permissions->count() }} permissions</small>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @else
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="bi bi-shield-fill-check"></i> Roles</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-lock-fill"></i> You don't have permission to assign roles to users. The default 'consumer' role will be assigned.
                        </div>
                    </div>
                </div>
                @endcan

                <!-- Direct Permissions Section -->
                @can('user.assign-permissions')
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-key-fill"></i> Direct Permissions</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPermissions()">
                                <i class="bi bi-check-all"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllPermissions()">
                                <i class="bi bi-x-circle"></i> Deselect All
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-save"></i> Create User
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Assign specific permissions directly to this user (in addition to role permissions).</p>

                        <div class="row">
                            @foreach($permissions as $group => $groupPermissions)
                            <div class="col-md-6">
                                <div class="permission-group">
                                    <h6><i class="bi bi-folder"></i> {{ ucfirst($group) }}</h6>
                                    @foreach($groupPermissions as $permission)
                                    <div class="form-check">
                                        <input class="form-check-input permission-checkbox" type="checkbox"
                                               name="permissions[]" value="{{ $permission->name }}"
                                               id="perm_{{ $permission->id }}"
                                               {{ old('permissions') && in_array($permission->name, old('permissions')) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="perm_{{ $permission->id }}">
                                            <small>{{ str_replace($group . '.', '', $permission->name) }}</small>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @else
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="bi bi-key-fill"></i> Direct Permissions</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-lock-fill"></i> You don't have permission to assign direct permissions to users.
                        </div>
                    </div>
                </div>
                @endcan

                <!-- Submit Button -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Create User
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function selectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAllPermissions() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}
</script>
@endpush

