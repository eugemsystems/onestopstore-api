@extends('admin.layout')

@section('title', 'Create Template - Admin Panel')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('admin.promo-templates.index') }}" class="btn btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left me-2"></i>Back to Templates
            </a>
            <h2><i class="bi bi-plus-circle me-2"></i>Create New Template</h2>
        </div>
    </div>

    <form action="{{ route('admin.promo-templates.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Template Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="html_content" class="form-label">HTML Content <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('html_content') is-invalid @enderror font-monospace"
                                      id="html_content" name="html_content" rows="20" required>{{ old('html_content') }}</textarea>
                            @error('html_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Paste your complete HTML template here.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="status" name="status" value="1"
                                       {{ old('status', 1) ? 'checked' : '' }}>
                                <label class="form-check-label" for="status">Active</label>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Tip:</strong> Your HTML template should include placeholders for dynamic content that will be replaced when generating flyers.
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-2"></i>Create Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

