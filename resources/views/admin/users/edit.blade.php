@extends('admin.layout')

@section('title', 'Edit User - ' . $user->name)

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
            <h2 class="mb-1"><i class="bi bi-pencil-square"></i> Edit User</h2>
            <p class="text-muted mb-0">Update user information, roles, and permissions</p>
        </div>
        <div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>

    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

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
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Branch</label>
                            <select name="branch" class="form-select @error('branch') is-invalid @enderror">
                                <option value="None" {{ old('branch', $user->branch ?? 'None') == 'None' ? 'selected' : '' }}>None</option>
                                <option value="Harare" {{ old('branch', $user->branch) == 'Harare' ? 'selected' : '' }}>Harare</option>
                                <option value="Bulawayo" {{ old('branch', $user->branch) == 'Bulawayo' ? 'selected' : '' }}>Bulawayo</option>
                                <option value="Mutare" {{ old('branch', $user->branch) == 'Mutare' ? 'selected' : '' }}>Mutare</option>
                                <option value="Zambia" {{ old('branch', $user->branch) == 'Zambia' ? 'selected' : '' }}>Zambia</option>
                            </select>
                            @error('branch')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">New Password</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            <small class="text-muted">Leave blank to keep current password</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>

                        <div class="alert alert-info mb-0">
                            <small>
                                <i class="bi bi-info-circle"></i>
                                <strong>User ID:</strong> {{ $user->id }}<br>
                                <strong>Created:</strong> {{ $user->created_at->format('M d, Y') }}
                            </small>
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
                                           {{ in_array($role->name, $userRoles) ? 'checked' : '' }}>
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
                        <h5 class="mb-0"><i class="bi bi-shield-fill-check"></i> Current Roles</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-lock-fill"></i> You don't have permission to modify user roles.
                        </div>
                        <div class="mt-3">
                            <strong>Current Roles:</strong>
                            @if(count($userRoles) > 0)
                                <div class="mt-2">
                                    @foreach($userRoles as $role)
                                        <span class="badge bg-primary me-2">{{ ucfirst($role) }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">No roles assigned</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endcan

                <!-- Direct Permissions Section -->
                @can('user.assign-permissions')
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0"><i class="bi bi-key-fill"></i> Direct Permissions</h5>
                            <button type="button" class="btn btn-sm btn-link text-decoration-none ms-2"
                                    data-bs-toggle="collapse" data-bs-target="#permissionsCollapse"
                                    aria-expanded="false" aria-controls="permissionsCollapse"
                                    id="permissionsToggleBtn">
                                <i class="bi bi-chevron-down" id="permissionsToggleIcon"></i>
                                <span class="small">Expand</span>
                            </button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllPermissions()">
                                <i class="bi bi-check-all"></i> Select All
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllPermissions()">
                                <i class="bi bi-x-circle"></i> Deselect All
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-save"></i> Update User
                            </button>
                        </div>
                    </div>
                    <div class="collapse" id="permissionsCollapse">
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
                                                   {{ in_array($permission->name, $userPermissions) ? 'checked' : '' }}>
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
                </div>
                @else
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="bi bi-key-fill"></i> Current Direct Permissions</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-lock-fill"></i> You don't have permission to modify user permissions.
                        </div>
                        <div>
                            <strong>Current Direct Permissions:</strong>
                            @if(count($userPermissions) > 0)
                                <div class="mt-2">
                                    @foreach($userPermissions as $permission)
                                        <span class="badge bg-info text-dark me-2 mb-2">{{ $permission }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">No direct permissions assigned</p>
                            @endif
                        </div>
                    </div>
                </div>
                @endcan

                <!-- Submit Button -->
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Update User
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>

    <!-- User Addresses Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-geo-alt-fill"></i> User Addresses</h5>
                    <button type="button" class="btn btn-primary btn-sm" onclick="showAddAddressModal()">
                        <i class="bi bi-plus-circle"></i> Add New Address
                    </button>
                </div>
                <div class="card-body">
                    <div id="addressesContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2">Loading addresses...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- WhatsApp Chat Agent Section --}}
    @php
        $waJobTitles = \App\Models\WhatsappJobTitle::orderBy('sort_order')->orderBy('name')->get();
        $waAgent     = \App\Models\WhatsappAgent::with('jobTitle')->where('user_id', $user->id)->first();
    @endphp
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-left: 4px solid #25D366 !important;">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center" style="background:#f0fdf4;">
                    <h5 class="mb-0" style="color:#16a34a;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
                        </svg>
                        WhatsApp Chat Agent
                    </h5>
                    @if($waAgent)
                        <span class="badge {{ $waAgent->chat_enabled ? 'bg-success' : 'bg-secondary' }}">
                            {{ $waAgent->chat_enabled ? 'Chat On' : 'Chat Off' }}
                        </span>
                    @else
                        <span class="text-muted small">Not set up as chat agent</span>
                    @endif
                </div>
                <div class="card-body">
                    <div id="waAgentAlert" class="d-none mb-3"></div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Display Name</label>
                            <input type="text" class="form-control" id="waName" value="{{ $waAgent?->name ?? $user->name }}" maxlength="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">WhatsApp Number</label>
                            <input type="text" class="form-control" id="waNumber" value="{{ $waAgent?->whatsapp_number ?? '' }}" placeholder="+263771234567">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Job Title</label>
                            <select class="form-select" id="waJobTitle">
                                <option value="">— None —</option>
                                @foreach($waJobTitles as $jt)
                                    <option value="{{ $jt->id }}" {{ $waAgent?->job_title_id == $jt->id ? 'selected' : '' }}>{{ $jt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Branch</label>
                            <input type="text" class="form-control" id="waBranch" value="{{ $waAgent?->branch ?? $user->branch }}" maxlength="100">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">From</label>
                            <input type="time" class="form-control" id="waFrom" value="{{ $waAgent?->available_from ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">To</label>
                            <input type="time" class="form-control" id="waTo" value="{{ $waAgent?->available_to ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" class="form-control" id="waSort" value="{{ $waAgent?->sort_order ?? 0 }}" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Available Days</label>
                            <div class="d-flex gap-2 flex-wrap">
                                @php $waDays = $waAgent?->available_days ?? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; @endphp
                                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input wa-day-cb" type="checkbox" value="{{ $day }}" id="wad_{{ $day }}" {{ in_array($day,$waDays)?'checked':'' }}>
                                        <label class="form-check-label" for="wad_{{ $day }}">{{ $day }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="waChatEnabled" role="switch" {{ $waAgent?->chat_enabled ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="waChatEnabled">Chat Enabled</label>
                            </div>
                        </div>
                        @if($waAgent?->profile_picture_url)
                        <div class="col-12">
                            <label class="form-label fw-semibold">Profile Photo</label>
                            <div class="d-flex align-items-center gap-3">
                                <img src="{{ $waAgent->profile_picture_url }}" class="rounded-circle" width="60" height="60" style="object-fit:cover" id="waPhotoPreview">
                                <label class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-camera"></i> Change Photo
                                    <input type="file" id="waPhotoInput" accept="image/*" class="d-none">
                                </label>
                            </div>
                        </div>
                        @else
                        <div class="col-12" id="waPhotoSection" style="{{ $waAgent ? '' : 'display:none' }}">
                            <label class="form-label fw-semibold">Profile Photo</label>
                            <label class="btn btn-outline-secondary btn-sm d-block" style="width:fit-content;">
                                <i class="bi bi-camera"></i> Upload Photo
                                <input type="file" id="waPhotoInput" accept="image/*" class="d-none">
                            </label>
                        </div>
                        @endif
                        <div class="col-12">
                            <button type="button" class="btn btn-success" id="btnSaveWaAgent">
                                <span class="spinner-border spinner-border-sm d-none" id="waSpinner"></span>
                                <i class="bi bi-check-lg"></i> {{ $waAgent ? 'Update Chat Agent' : 'Add as Chat Agent' }}
                            </button>
                            @if($waAgent)
                            <button type="button" class="btn btn-outline-danger ms-2" id="btnRemoveWaAgent">
                                <i class="bi bi-person-x"></i> Remove from Chat
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Address Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addressModalLabel">
                    <i class="bi bi-geo-alt"></i> <span id="modalTitle">Add New Address</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    <input type="hidden" id="addressId" value="">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Address Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" placeholder="e.g., Home, Office, Billing Address">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Street Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="street" placeholder="Street address, apartment, suite">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="city" placeholder="City">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Postal/Zip Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pincode" placeholder="Postal code">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Country <span class="text-danger">*</span></label>
                            <select class="form-select" id="country_id" onchange="loadStates()">
                                <option value="">Select Country</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">State/Province</label>
                            <select class="form-select" id="state_id">
                                <option value="">Select State (Optional)</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" placeholder="Phone number">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_default">
                                <label class="form-check-label fw-bold" for="is_default">
                                    Use as default address (for both billing & shipping)
                                </label>
                                <small class="d-block text-muted">This address will be used as default for both billing and shipping</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="saveAddress()">
                    <i class="bi bi-save"></i> Save Address
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let countries = [];
let currentAddresses = [];

// Load addresses on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCountries();
    loadAddresses();
});

function loadCountries() {
    fetch('{{ route("admin.users.countries") }}')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load countries: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                countries = data.countries;
                const countrySelect = document.getElementById('country_id');
                countrySelect.innerHTML = '<option value="">Select Country</option>';

                if (data.countries && data.countries.length > 0) {
                    data.countries.forEach(country => {
                        countrySelect.innerHTML += `<option value="${country.id}">${country.name}</option>`;
                    });
                } else {
                    console.warn('No countries found in database');
                    countrySelect.innerHTML = '<option value="">No countries available</option>';
                }
            } else {
                console.error('Failed to load countries:', data.message);
                const countrySelect = document.getElementById('country_id');
                countrySelect.innerHTML = '<option value="">Error loading countries</option>';
            }
        })
        .catch(error => {
            console.error('Error loading countries:', error);
            const countrySelect = document.getElementById('country_id');
            countrySelect.innerHTML = '<option value="">Error loading countries</option>';
        });
}

function loadStates() {
    const countryId = document.getElementById('country_id').value;
    const stateSelect = document.getElementById('state_id');

    if (!countryId) {
        stateSelect.innerHTML = '<option value="">Select State (Optional)</option>';
        return;
    }

    fetch(`{{ url('admin/users/countries') }}/${countryId}/states`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                stateSelect.innerHTML = '<option value="">Select State (Optional)</option>';
                data.states.forEach(state => {
                    stateSelect.innerHTML += `<option value="${state.id}">${state.name}</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading states:', error));
}

function loadAddresses() {
    const container = document.getElementById('addressesContainer');

    fetch('{{ route("admin.users.addresses.get", $user->id) }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentAddresses = data.addresses;

                if (data.addresses.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-5">
                            <i class="bi bi-geo-alt" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-3">No addresses added yet</p>
                            <button type="button" class="btn btn-primary" onclick="showAddAddressModal()">
                                <i class="bi bi-plus-circle"></i> Add First Address
                            </button>
                        </div>
                    `;
                } else {
                    let html = '<div class="row">';
                    data.addresses.forEach(address => {
                        html += renderAddressCard(address);
                    });
                    html += '</div>';
                    container.innerHTML = html;
                }
            }
        })
        .catch(error => {
            console.error('Error loading addresses:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Failed to load addresses: ${error.message}
                </div>
            `;
        });
}

function renderAddressCard(address) {
    const defaultBadge = address.is_default
        ? '<span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Default</span>'
        : '';

    return `
        <div class="col-md-6 mb-3">
            <div class="card h-100 ${address.is_default ? 'border-success' : ''}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0">
                            <i class="bi bi-geo-alt-fill"></i> ${address.title}${defaultBadge}
                        </h6>
                        <div class="btn-group btn-group-sm">
                            ${!address.is_default ? `
                                <button type="button" class="btn btn-outline-success btn-sm"
                                        onclick="setDefaultAddress(${address.id})"
                                        title="Set as default">
                                    <i class="bi bi-star"></i>
                                </button>
                            ` : ''}
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="editAddress(${address.id})"
                                    title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                    onclick="deleteAddress(${address.id})"
                                    title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <p class="mb-1 text-muted small">${address.street}</p>
                    <p class="mb-1 text-muted small">${address.city}, ${address.pincode}</p>
                    <p class="mb-1 text-muted small">${address.state ? address.state.name + ', ' : ''}${address.country ? address.country.name : ''}</p>
                    <p class="mb-0 text-muted small"><i class="bi bi-telephone"></i> ${address.phone}</p>
                </div>
            </div>
        </div>
    `;
}

function showAddAddressModal() {
    document.getElementById('modalTitle').textContent = 'Add New Address';
    document.getElementById('addressId').value = '';
    document.getElementById('addressForm').reset();
    clearValidationErrors();

    const modal = new bootstrap.Modal(document.getElementById('addressModal'));
    modal.show();
}

function editAddress(addressId) {
    const address = currentAddresses.find(a => a.id === addressId);
    if (!address) return;

    document.getElementById('modalTitle').textContent = 'Edit Address';
    document.getElementById('addressId').value = address.id;
    document.getElementById('title').value = address.title;
    document.getElementById('street').value = address.street;
    document.getElementById('city').value = address.city;
    document.getElementById('pincode').value = address.pincode;
    document.getElementById('country_id').value = address.country_id;
    document.getElementById('phone').value = address.phone;
    document.getElementById('is_default').checked = address.is_default == 1;

    // Load states for selected country
    if (address.country_id) {
        loadStates();
        setTimeout(() => {
            if (address.state_id) {
                document.getElementById('state_id').value = address.state_id;
            }
        }, 500);
    }

    clearValidationErrors();
    const modal = new bootstrap.Modal(document.getElementById('addressModal'));
    modal.show();
}

function saveAddress() {
    clearValidationErrors();

    const addressId = document.getElementById('addressId').value;
    const formData = {
        title: document.getElementById('title').value,
        street: document.getElementById('street').value,
        city: document.getElementById('city').value,
        pincode: document.getElementById('pincode').value,
        country_id: document.getElementById('country_id').value,
        state_id: document.getElementById('state_id').value || null,
        phone: document.getElementById('phone').value,
        is_default: document.getElementById('is_default').checked ? 1 : 0,
    };

    const url = addressId
        ? `{{ url('admin/users/' . $user->id . '/addresses') }}/${addressId}`
        : '{{ route("admin.users.addresses.store", $user->id) }}';

    const method = addressId ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            return response.text().then(text => {
                // Try to parse as JSON, fallback to text
                try {
                    const data = JSON.parse(text);
                    throw data;
                } catch(e) {
                    throw new Error(text || 'Server error');
                }
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });

            bootstrap.Modal.getInstance(document.getElementById('addressModal')).hide();
            loadAddresses();
        } else {
            if (data.errors) {
                showValidationErrors(data.errors);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to save address'
                });
            }
        }
    })
    .catch(error => {
        console.error('Save address error:', error);

        // Handle validation errors
        if (error.errors) {
            showValidationErrors(error.errors);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to save address'
            });
        }
    });
}

function deleteAddress(addressId) {
    Swal.fire({
        icon: 'warning',
        title: 'Delete Address?',
        text: 'This action cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`{{ url('admin/users/' . $user->id . '/addresses') }}/${addressId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadAddresses();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete address: ' + error.message
                });
            });
        }
    });
}

function setDefaultAddress(addressId) {
    fetch(`{{ url('admin/users/' . $user->id . '/addresses') }}/${addressId}/set-default`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });
            loadAddresses();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to set default address: ' + error.message
        });
    });
}

function showValidationErrors(errors) {
    Object.keys(errors).forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = errors[field][0];
            }
        }
    });
}

function clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.textContent = '';
    });
}

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

// Handle permissions collapse toggle
document.addEventListener('DOMContentLoaded', function() {
    const permissionsCollapse = document.getElementById('permissionsCollapse');
    const toggleIcon = document.getElementById('permissionsToggleIcon');
    const toggleBtn = document.getElementById('permissionsToggleBtn');

    if (permissionsCollapse && toggleIcon && toggleBtn) {
        permissionsCollapse.addEventListener('show.bs.collapse', function () {
            toggleIcon.classList.remove('bi-chevron-down');
            toggleIcon.classList.add('bi-chevron-up');
            toggleBtn.querySelector('span').textContent = 'Collapse';
        });

        permissionsCollapse.addEventListener('hide.bs.collapse', function () {
            toggleIcon.classList.remove('bi-chevron-up');
            toggleIcon.classList.add('bi-chevron-down');
            toggleBtn.querySelector('span').textContent = 'Expand';
        });
    }
});
</script>


<script>
// ==============================
// WhatsApp Agent Section
// ==============================
const WA_CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const WA_AGENT_URL  = '{{ url("admin/whatsapp/agents") }}';
const WA_USER_ID    = {{ $user->id }};
@if($waAgent)
let waAgentId = {{ $waAgent->id }};
@else
let waAgentId = null;
@endif

async function waShowAlert(msg, type='success') {
    const el = document.getElementById('waAgentAlert');
    el.className = `alert alert-${type} mb-3`;
    el.textContent = msg;
    el.classList.remove('d-none');
    setTimeout(() => el.classList.add('d-none'), 4000);
}

document.getElementById('btnSaveWaAgent')?.addEventListener('click', async function() {
    const days = [...document.querySelectorAll('.wa-day-cb:checked')].map(cb => cb.value);
    const body = new FormData();
    body.append('user_id', WA_USER_ID);
    body.append('name', document.getElementById('waName').value);
    body.append('whatsapp_number', document.getElementById('waNumber').value);
    body.append('job_title_id', document.getElementById('waJobTitle').value);
    body.append('branch', document.getElementById('waBranch').value);
    body.append('available_from', document.getElementById('waFrom').value);
    body.append('available_to', document.getElementById('waTo').value);
    body.append('sort_order', document.getElementById('waSort').value);
    body.append('chat_enabled', document.getElementById('waChatEnabled').checked ? '1' : '0');
    days.forEach(d => body.append('available_days[]', d));
    if (waAgentId) body.append('_method', 'PUT');

    const spinner = document.getElementById('waSpinner');
    spinner.classList.remove('d-none');
    this.disabled = true;

    try {
        const url = waAgentId ? `${WA_AGENT_URL}/${waAgentId}` : WA_AGENT_URL;
        const resp = await fetch(url, {
            method: 'POST', headers: {'X-CSRF-TOKEN': WA_CSRF, 'Accept': 'application/json'}, body,
        });
        const data = await resp.json();
        if (!resp.ok || !data.success) throw new Error(data.message ?? 'Save failed');
        waAgentId = data.data.id;
        waShowAlert(data.message, 'success');
        // Show photo section after first save
        document.getElementById('waPhotoSection')?.style.setProperty('display','block');
    } catch(err) {
        waShowAlert(err.message, 'danger');
    } finally {
        spinner.classList.add('d-none');
        this.disabled = false;
    }
});

document.getElementById('btnRemoveWaAgent')?.addEventListener('click', async function() {
    if (!waAgentId) return;
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Remove this user from WhatsApp chat agents?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;
    const body = new FormData();
    body.append('_method', 'DELETE');
    const resp = await fetch(`${WA_AGENT_URL}/${waAgentId}`, {
        method:'POST', headers:{'X-CSRF-TOKEN':WA_CSRF,'Accept':'application/json'}, body,
    });
    const data = await resp.json();
    if (data.success) {
        waAgentId = null;
        waShowAlert('Agent removed from WhatsApp chat.', 'warning');
        this.style.display = 'none';
    }
});

document.getElementById('waPhotoInput')?.addEventListener('change', async function() {
    if (!waAgentId) { waShowAlert('Save agent details first before uploading a photo.','warning'); return; }
    const file = this.files[0];
    if (!file) return;
    const body = new FormData();
    body.append('photo', file);
    const resp = await fetch(`${WA_AGENT_URL}/${waAgentId}/upload-photo`, {
        method:'POST', headers:{'X-CSRF-TOKEN':WA_CSRF,'Accept':'application/json'}, body,
    });
    const data = await resp.json();
    if (data.success) {
        const preview = document.getElementById('waPhotoPreview');
        if (preview) preview.src = data.profile_picture_url;
        waShowAlert('Photo updated!', 'success');
    } else waShowAlert(data.message ?? 'Upload failed','danger');
});
</script>
@endpush
