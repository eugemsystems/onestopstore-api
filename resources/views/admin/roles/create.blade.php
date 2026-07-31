@extends('admin.layout')

@section('title', 'Create New Role')

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
            <h2 class="mb-1"><i class="bi bi-plus-circle-fill"></i> Create New Role</h2>
            <p class="text-muted mb-0">Create a new role with specific permissions</p>
        </div>
        <div>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Roles
            </a>
        </div>
    </div>

    <form action="{{ route('admin.roles.store') }}" method="POST">
        @csrf

        <div class="row">
            <!-- Role Info -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="bi bi-shield-fill-check"></i> Role Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Role Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required autofocus placeholder="e.g., manager">
                            <small class="text-muted">Use lowercase letters, numbers, and hyphens</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info mb-0">
                            <small>
                                <i class="bi bi-lightbulb"></i> <strong>Examples:</strong><br>
                                • manager<br>
                                • editor<br>
                                • viewer<br>
                                • support-staff
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-key-fill"></i> Assign Permissions</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPermissions()">
                                <i class="bi bi-check-all"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllPermissions()">
                                <i class="bi bi-x-circle"></i> Deselect All
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Select which permissions this role should have. Users assigned to this role will inherit these permissions.</p>

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

                <!-- Submit Button -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Create Role
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary btn-lg">
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

