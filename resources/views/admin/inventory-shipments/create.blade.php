@extends('admin.layout')

@section('title', 'Add New Shipment')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-plus-circle"></i> Add New Shipment
                    </h2>
                    <p class="text-muted">Create a new inventory shipment record</p>
                </div>
                <div>
                    <a href="{{ route('admin.inventory-shipments.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.inventory-shipments.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">
                            <!-- Title -->
                            <div class="col-md-8">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror"
                                       name="title" value="{{ old('title') }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Order ID -->
                            <div class="col-md-4">
                                <label class="form-label">Order Number (Optional)</label>
                                <input type="text" class="form-control @error('order') is-invalid @enderror"
                                       name="order" value="{{ old('order') }}">
                                <small class="text-muted">Numeric identifier for sorting</small>
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Quantity -->
                            <div class="col-md-6">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('quantity') is-invalid @enderror"
                                       name="quantity" value="{{ old('quantity') }}" required min="1">
                                @error('quantity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Destination -->
                            <div class="col-md-6">
                                <label class="form-label">Destination <span class="text-danger">*</span></label>
                                <select class="form-select @error('destination') is-invalid @enderror"
                                        name="destination" required>
                                    <option value="">-- Select Destination --</option>
                                    <option value="Harare" {{ old('destination') == 'Harare' ? 'selected' : '' }}>Harare</option>
                                    <option value="Bulawayo" {{ old('destination') == 'Bulawayo' ? 'selected' : '' }}>Bulawayo</option>
                                    <option value="Mutare" {{ old('destination') == 'Mutare' ? 'selected' : '' }}>Mutare</option>
                                    <option value="Zambia" {{ old('destination') == 'Zambia' ? 'selected' : '' }}>Zambia</option>
                                </select>
                                @error('destination')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- WH Status -->
                            <div class="col-md-6">
                                <label class="form-label">WH Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror"
                                        name="status" required>
                                    <option value="Not yet">Not yet</option>
                                    <option value="Received" {{ old('status') == 'Received' ? 'selected' : '' }}>Received</option>
                                    <option value="Not yet" {{ old('status') == 'Not yet' ? 'selected' : '' }}>Not yet</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Transporter -->
                            <div class="col-md-6">
                                <label class="form-label">Transporter</label>
                                <input type="text" class="form-control @error('transporter') is-invalid @enderror"
                                       name="transporter" value="{{ old('transporter') }}">
                                @error('transporter')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Date -->
                            <div class="col-md-6">
                                <label class="form-label">Shipment Date</label>
                                <input type="date" class="form-control @error('date') is-invalid @enderror"
                                       name="date" value="{{ old('date') }}">
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- ETA -->
                            <div class="col-md-6">
                                <label class="form-label">Estimated Time of Arrival (ETA)</label>
                                <input type="date" class="form-control @error('eta') is-invalid @enderror"
                                       name="eta" value="{{ old('eta') }}">
                                @error('eta')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Destination Status -->
                            <div class="col-md-6">
                                <label class="form-label">Destination Status</label>
                                <select class="form-select @error('f_status') is-invalid @enderror"
                                        name="f_status">
                                    <option value="Not yet">Not yet</option>
                                    <option value="Received" {{ old('f_status') == 'Received' ? 'selected' : '' }}>Received</option>
                                    <option value="Not yet" {{ old('f_status') == 'Not yet' ? 'selected' : '' }}>Not yet</option>
                                </select>
                                @error('f_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Signed By -->
                            <div class="col-md-6">
                                <label class="form-label">Signed By (Optional)</label>
                                <select class="form-select @error('signed_by') is-invalid @enderror"
                                        name="signed_by">
                                    <option value="">-- Select Staff --</option>
                                    @foreach($signedByUsers as $staff)
                                        <option value="{{ $staff->id }}" {{ old('signed_by') == $staff->id ? 'selected' : '' }}>
                                            {{ $staff->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('signed_by')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Received By -->
                            <div class="col-md-6">
                                <label class="form-label">Received By (Staff)</label>
                                <select class="form-select @error('received_by') is-invalid @enderror"
                                        name="received_by">
                                    <option value="">-- Select Staff --</option>
                                    @foreach($staffUsers as $staff)
                                        <option value="{{ $staff->id }}" {{ old('received_by') == $staff->id ? 'selected' : '' }}>
                                            {{ $staff->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('received_by')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Assigned To -->
                            <div class="col-md-6">
                                <label class="form-label">SRS</label>
                                <select class="form-select @error('srs') is-invalid @enderror"
                                        name="srs">
                                    <option value="">-- Select Person --</option>
                                    <option value="Mike" {{ old('srs') == 'Mike' ? 'selected' : '' }}>Mike</option>
                                    <option value="Tinashe" {{ old('srs') == 'Tinashe' ? 'selected' : '' }}>Tinashe</option>
                                    <option value="Other" {{ old('srs') == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('srs')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                          name="notes" rows="4">{{ old('notes') }}</textarea>
                                <small class="text-muted">Any additional information about this shipment</small>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Submit Buttons -->
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('admin.inventory-shipments.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Create Shipment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

