@extends('admin.layout')

@section('title', 'View Media')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-eye"></i> Media Details</h2>
        <div>
            <a href="{{ route('admin.media.edit', $media->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('admin.media.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body text-center">
                    @if(str_starts_with($media->mime_type, 'image/'))
                        <img src="{{ $media->image_url }}" alt="{{ $media->name }}" class="img-fluid rounded" style="max-height: 600px;">
                    @elseif(str_starts_with($media->mime_type, 'video/'))
                        <video controls class="w-100 rounded" style="max-height: 600px;">
                            <source src="{{ $media->image_url }}" type="{{ $media->mime_type }}">
                        </video>
                    @else
                        <div class="py-5">
                            <i class="bi bi-file-earmark display-1 text-muted"></i>
                            <p class="mt-3">{{ $media->file_name }}</p>
                            <a href="{{ $media->image_url }}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-download"></i> Download File
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">{{ $media->name }}</h5>

                    <table class="table">
                        <tr>
                            <th>ID:</th>
                            <td>{{ $media->id }}</td>
                        </tr>
                        <tr>
                            <th>UUID:</th>
                            <td><small>{{ $media->uuid }}</small></td>
                        </tr>
                        <tr>
                            <th>File Name:</th>
                            <td>{{ $media->file_name }}</td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td>{{ $media->mime_type }}</td>
                        </tr>
                        <tr>
                            <th>Size:</th>
                            <td>{{ number_format($media->size / 1024, 2) }} KB</td>
                        </tr>
                        <tr>
                            <th>Folder:</th>
                            <td>{{ $media->model_type ?? 'General' }}</td>
                        </tr>
                        <tr>
                            <th>Uploaded:</th>
                            <td>{{ $media->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Modified:</th>
                            <td>{{ $media->updated_at->format('M d, Y H:i') }}</td>
                        </tr>
                    </table>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Image URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="imageUrl" value="{{ $media->image_url }}" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard('imageUrl')">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>

                    @if($media->original_url && $media->original_url != $media->image_url)
                        <div class="mb-3">
                            <label class="form-label fw-bold">Original URL</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="originalUrl" value="{{ $media->original_url }}" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyToClipboard('originalUrl')">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('admin.media.destroy', $media->id) }}" method="POST" data-swal-confirm="Are you sure you want to delete this file?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-trash"></i> Delete File
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyToClipboard(elementId) {
    const input = document.getElementById(elementId);
    input.select();
    document.execCommand('copy');

    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i>';
    setTimeout(() => {
        btn.innerHTML = originalContent;
    }, 2000);
}
</script>
@endpush

