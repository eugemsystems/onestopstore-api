@extends('admin.layout')

@section('title', 'Edit Media')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil"></i> Edit Media</h2>
        <a href="{{ route('admin.media.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.media.update', $media->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $media->name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Folder</label>
                            <select name="model_type" class="form-select">
                                <option value="">General / Uncategorized</option>
                                <option value="Product" {{ $media->model_type == 'Product' ? 'selected' : '' }}>Products</option>
                                <option value="Category" {{ $media->model_type == 'Category' ? 'selected' : '' }}>Categories</option>
                                <option value="Banner" {{ $media->model_type == 'Banner' ? 'selected' : '' }}>Banners</option>
                                <option value="Blog" {{ $media->model_type == 'Blog' ? 'selected' : '' }}>Blog</option>
                                <option value="User" {{ $media->model_type == 'User' ? 'selected' : '' }}>Users</option>
                                <option value="Other" {{ $media->model_type == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                        <a href="{{ route('admin.media.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    @if(str_starts_with($media->mime_type, 'image/'))
                        <img src="{{ $media->image_url }}" alt="{{ $media->name }}" class="img-fluid rounded mb-3">
                    @else
                        <div class="text-center py-5 bg-light rounded mb-3">
                            <i class="bi bi-file-earmark display-1 text-muted"></i>
                        </div>
                    @endif

                    <h6>File Information</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>File Name:</td>
                            <td><small>{{ $media->file_name }}</small></td>
                        </tr>
                        <tr>
                            <td>Type:</td>
                            <td><small>{{ $media->mime_type }}</small></td>
                        </tr>
                        <tr>
                            <td>Size:</td>
                            <td><small>{{ number_format($media->size / 1024, 2) }} KB</small></td>
                        </tr>
                        <tr>
                            <td>Uploaded:</td>
                            <td><small>{{ $media->created_at->format('M d, Y') }}</small></td>
                        </tr>
                    </table>

                    <div class="mb-2">
                        <label class="form-label small">URL:</label>
                        <input type="text" class="form-control form-control-sm" value="{{ $media->image_url }}" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

