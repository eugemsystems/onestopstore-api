@extends('admin.layout')

@section('title', 'View Attribute')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-sliders"></i> {{ $attribute->name }}</h2>
            <p class="text-muted mb-0">Attribute Details</p>
        </div>
        <div class="btn-group">
            @can('attribute.edit')
            <a href="{{ route('admin.attributes.edit', $attribute->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            <a href="{{ route('admin.attributes.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Attribute Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Name:</strong></div>
                        <div class="col-md-9">{{ $attribute->name }}</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Slug:</strong></div>
                        <div class="col-md-9"><code>{{ $attribute->slug }}</code></div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Display Style:</strong></div>
                        <div class="col-md-9">
                            @if($attribute->style == 'dropdown')
                                <span class="badge bg-primary">Dropdown</span>
                            @elseif($attribute->style == 'radio')
                                <span class="badge bg-info">Radio Buttons</span>
                            @elseif($attribute->style == 'swatch')
                                <span class="badge bg-warning">Color/Image Swatches</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($attribute->style) }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Status:</strong></div>
                        <div class="col-md-9">
                            @if($attribute->status)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Created:</strong></div>
                        <div class="col-md-9">{{ $attribute->created_at->format('M d, Y H:i A') }}</div>
                    </div>

                    <div class="row">
                        <div class="col-md-3"><strong>Last Updated:</strong></div>
                        <div class="col-md-9">{{ $attribute->updated_at->format('M d, Y H:i A') }}</div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Attribute Values</h5>
                    <span class="badge bg-secondary">{{ $attribute->attribute_values->count() }} values</span>
                </div>
                <div class="card-body">
                    @if($attribute->attribute_values->count() > 0)
                        <div class="row">
                            @foreach($attribute->attribute_values as $value)
                                <div class="col-md-4 mb-2">
                                    <div class="border rounded p-2">
                                        <i class="bi bi-check-circle text-success"></i> {{ $value->value }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No values added yet</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <p class="text-muted mb-0 small">Total Values</p>
                            <h3 class="mb-0">{{ $attribute->attribute_values->count() }}</h3>
                        </div>
                        <div>
                            <i class="bi bi-tag-fill text-primary" style="font-size: 2rem;"></i>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-0 small">Products Using</p>
                            <h3 class="mb-0">{{ $attribute->products_count ?? 0 }}</h3>
                        </div>
                        <div>
                            <i class="bi bi-box-seam text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            @can('attribute.destroy')
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Danger Zone</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Deleting this attribute will remove it from all products. This action cannot be undone.
                    </p>
                    @if($attribute->products_count > 0)
                        <div class="alert alert-warning small">
                            <i class="bi bi-info-circle"></i> This attribute is currently used by {{ $attribute->products_count }} product(s). Cannot be deleted.
                        </div>
                    @else
                        <form action="{{ route('admin.attributes.destroy', $attribute->id) }}" method="POST"
                              data-swal-confirm="Are you sure you want to delete this attribute? This action cannot be undone.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-trash"></i> Delete Attribute
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            @endcan
        </div>
    </div>
</div>
@endsection
