@extends('admin.layout')

@section('title', 'Create Attribute')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-plus-lg"></i> Create Attribute</h2>
            <p class="text-muted mb-0">Add a new product attribute</p>
        </div>
        <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Attributes
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong><i class="bi bi-exclamation-triangle-fill"></i> Validation Errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.attributes.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Attribute Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Attribute Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}"
                                   placeholder="e.g., Color, Size, Material" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">This will be displayed on product pages</small>
                        </div>

                        <div class="mb-3">
                            <label for="style" class="form-label">Display Style</label>
                            <select class="form-select @error('style') is-invalid @enderror"
                                    id="style" name="style">
                                <option value="dropdown" {{ old('style') == 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                                <option value="radio" {{ old('style') == 'radio' ? 'selected' : '' }}>Radio Buttons</option>
                                <option value="swatch" {{ old('style') == 'swatch' ? 'selected' : '' }}>Color/Image Swatches</option>
                            </select>
                            @error('style')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">How this attribute will be displayed to customers</small>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Attribute Values</h5>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addValue()">
                            <i class="bi bi-plus-lg"></i> Add Value
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="values-container">
                            <!-- Values will be added here dynamically -->
                        </div>
                        <small class="text-muted">Add values for this attribute (e.g., Red, Blue, Green for Color)</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Publish</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="status"
                                       name="status" value="1" {{ old('status', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="status">Active</label>
                            </div>
                            <small class="text-muted">Inactive attributes won't be shown on products</small>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg"></i> Create Attribute
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Help</h5>
                    </div>
                    <div class="card-body">
                        <p class="small mb-2"><strong>What are attributes?</strong></p>
                        <p class="small text-muted">Attributes are product characteristics like Color, Size, or Material that customers can choose from.</p>

                        <p class="small mb-2 mt-3"><strong>Display Styles:</strong></p>
                        <ul class="small text-muted">
                            <li><strong>Dropdown:</strong> Best for many options</li>
                            <li><strong>Radio:</strong> Best for few options</li>
                            <li><strong>Swatch:</strong> Best for colors/patterns</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
let valueIndex = 0;

// Add initial empty value field
document.addEventListener('DOMContentLoaded', function() {
    addValue();
});

function addValue() {
    const container = document.getElementById('values-container');
    const div = document.createElement('div');
    div.className = 'mb-2 d-flex gap-2 value-row';
    div.innerHTML = `
        <input type="text" class="form-control" name="attribute_values[${valueIndex}][value]"
               placeholder="Enter value (e.g., Red, Large, Cotton)">
        <button type="button" class="btn btn-outline-danger" onclick="removeValue(this)">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(div);
    valueIndex++;
}

function removeValue(button) {
    const row = button.closest('.value-row');
    if (document.querySelectorAll('.value-row').length > 1) {
        row.remove();
    } else {
        showWarning('At least one value is recommended');
    }
}
</script>
@endpush
@endsection
