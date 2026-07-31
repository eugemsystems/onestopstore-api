@extends('admin.layout')

@section('title', 'Edit Attribute')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-pencil"></i> Edit Attribute</h2>
            <p class="text-muted mb-0">Update attribute: <strong>{{ $attribute->name }}</strong></p>
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

    <form action="{{ route('admin.attributes.update', $attribute->id) }}" method="POST">
        @csrf
        @method('PUT')

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
                                   id="name" name="name" value="{{ old('name', $attribute->name) }}"
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
                                <option value="dropdown" {{ old('style', $attribute->style) == 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                                <option value="radio" {{ old('style', $attribute->style) == 'radio' ? 'selected' : '' }}>Radio Buttons</option>
                                <option value="swatch" {{ old('style', $attribute->style) == 'swatch' ? 'selected' : '' }}>Color/Image Swatches</option>
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
                            @foreach($attribute->attribute_values as $index => $value)
                            <div class="mb-2 d-flex gap-2 value-row" data-id="{{ $value->id }}">
                                <input type="hidden" name="attribute_values[{{ $index }}][id]" value="{{ $value->id }}">
                                <input type="text" class="form-control"
                                       name="attribute_values[{{ $index }}][value]"
                                       value="{{ old("attribute_values.{$index}.value", $value->value) }}"
                                       placeholder="Enter value">
                                <input type="hidden" name="attribute_values[{{ $index }}][delete]" value="0" class="delete-flag">
                                <button type="button" class="btn btn-outline-danger" onclick="removeValue(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Add or edit values for this attribute</small>
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
                                       name="status" value="1" {{ old('status', $attribute->status) ? 'checked' : '' }}>
                                <label class="form-check-label" for="status">Active</label>
                            </div>
                            <small class="text-muted">Inactive attributes won't be shown on products</small>
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-check-lg"></i> Update Attribute
                        </button>

                        @can('attribute.destroy')
                        <button type="button" class="btn btn-outline-danger w-100" onclick="deleteAttribute()">
                            <i class="bi bi-trash"></i> Delete Attribute
                        </button>
                        @endcan
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="small mb-1"><strong>Slug:</strong></p>
                        <p class="small text-muted mb-3"><code>{{ $attribute->slug }}</code></p>

                        <p class="small mb-1"><strong>Created:</strong></p>
                        <p class="small text-muted mb-3">{{ $attribute->created_at->format('M d, Y H:i') }}</p>

                        <p class="small mb-1"><strong>Last Updated:</strong></p>
                        <p class="small text-muted">{{ $attribute->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Delete Form -->
    <form id="delete-form" action="{{ route('admin.attributes.destroy', $attribute->id) }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>

@push('scripts')
<script>
let valueIndex = {{ count($attribute->attribute_values) }};

function addValue() {
    const container = document.getElementById('values-container');
    const div = document.createElement('div');
    div.className = 'mb-2 d-flex gap-2 value-row';
    div.innerHTML = `
        <input type="text" class="form-control" name="attribute_values[${valueIndex}][value]"
               placeholder="Enter value">
        <button type="button" class="btn btn-outline-danger" onclick="removeValue(this)">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(div);
    valueIndex++;
}

function removeValue(button) {
    const row = button.closest('.value-row');

    // If it's an existing value (has data-id), mark it for deletion
    if (row.hasAttribute('data-id')) {
        const deleteFlag = row.querySelector('.delete-flag');
        if (deleteFlag) {
            deleteFlag.value = '1';
        }
        row.style.opacity = '0.5';
        row.querySelector('input[type="text"]').disabled = true;
        button.disabled = true;
        button.innerHTML = '<i class="bi bi-check"></i> Marked for deletion';
        button.classList.remove('btn-outline-danger');
        button.classList.add('btn-outline-secondary');
    } else {
        // If it's a new value, just remove it
        if (document.querySelectorAll('.value-row').length > 1) {
            row.remove();
        } else {
            showWarning('At least one value is recommended');
        }
    }
}

async function deleteAttribute() {
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Are you sure you want to delete this attribute? This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;
    document.getElementById('delete-form').submit();
}
</script>
@endpush
@endsection
