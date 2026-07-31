@extends('admin.layout')

@section('title', 'Media Library')

@push('styles')
<style>
    .media-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }
    .media-item {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .media-item:hover {
        border-color: #0d6efd;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .media-item.selected {
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }
    .media-item img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    .media-item-info {
        padding: 10px;
    }
    .media-item-checkbox {
        position: absolute;
        top: 10px;
        left: 10px;
        width: 24px;
        height: 24px;
    }
    .file-icon {
        width: 100%;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        font-size: 48px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-folder2-open"></i> Media Library</h2>
            <p class="text-muted mb-0">Manage your media files and folders</p>
        </div>
        <div>
            <a href="{{ route('admin.media.create') }}" class="btn btn-primary">
                <i class="bi bi-cloud-upload"></i> Upload Files
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Folders</h6>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.media.index') }}" class="list-group-item list-group-item-action {{ !request('folder') ? 'active' : '' }}">
                            <i class="bi bi-folder"></i> All Media
                            <span class="badge bg-secondary float-end">{{ $media->total() }}</span>
                        </a>
                        @foreach($folders as $folder)
                            <a href="{{ route('admin.media.index', ['folder' => $folder->model_type]) }}"
                               class="list-group-item list-group-item-action {{ request('folder') == $folder->model_type ? 'active' : '' }}">
                                <i class="bi bi-folder"></i> {{ $folder->model_type }}
                                <span class="badge bg-secondary float-end">{{ $folder->count }}</span>
                            </a>
                        @endforeach
                    </div>

                    <h6 class="mt-4 mb-3">Filter by Type</h6>
                    <div class="list-group list-group-flush">
                        <a href="{{ route('admin.media.index', array_merge(request()->except('type'), [])) }}"
                           class="list-group-item list-group-item-action {{ !request('type') ? 'active' : '' }}">
                            <i class="bi bi-files"></i> All Types
                        </a>
                        <a href="{{ route('admin.media.index', array_merge(request()->except('type'), ['type' => 'image'])) }}"
                           class="list-group-item list-group-item-action {{ request('type') == 'image' ? 'active' : '' }}">
                            <i class="bi bi-image"></i> Images
                        </a>
                        <a href="{{ route('admin.media.index', array_merge(request()->except('type'), ['type' => 'video'])) }}"
                           class="list-group-item list-group-item-action {{ request('type') == 'video' ? 'active' : '' }}">
                            <i class="bi bi-camera-video"></i> Videos
                        </a>
                        <a href="{{ route('admin.media.index', array_merge(request()->except('type'), ['type' => 'document'])) }}"
                           class="list-group-item list-group-item-action {{ request('type') == 'document' ? 'active' : '' }}">
                            <i class="bi bi-file-text"></i> Documents
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Search and Actions -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <form action="{{ route('admin.media.index') }}" method="GET" class="d-flex">
                                @foreach(request()->except('search') as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach
                                <input type="text" name="search" class="form-control" placeholder="Search files..." value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary ms-2">
                                    <i class="bi bi-search"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="display: none;">
                                <i class="bi bi-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Media Grid -->
            <div class="card">
                <div class="card-body">
                    @if($media->count() > 0)
                        <div class="media-grid">
                            @foreach($media as $item)
                                <div class="media-item" data-id="{{ $item->id }}">
                                    <input type="checkbox" class="form-check-input media-item-checkbox" value="{{ $item->id }}">

                                    @if(str_starts_with($item->mime_type, 'image/'))
                                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}">
                                    @else
                                        <div class="file-icon">
                                            @if(str_starts_with($item->mime_type, 'video/'))
                                                <i class="bi bi-camera-video text-primary"></i>
                                            @elseif(str_starts_with($item->mime_type, 'application/pdf'))
                                                <i class="bi bi-file-pdf text-danger"></i>
                                            @else
                                                <i class="bi bi-file-earmark text-secondary"></i>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="media-item-info">
                                        <div class="fw-bold small text-truncate">{{ $item->name }}</div>
                                        <div class="text-muted small">{{ number_format($item->size / 1024, 2) }} KB</div>
                                        <div class="mt-2">
                                            <a href="{{ route('admin.media.show', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.media.edit', $item->id) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.media.destroy', $item->id) }}" method="POST" class="d-inline" data-swal-confirm="Delete this file?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $media->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-folder2-open display-1 text-muted"></i>
                            <p class="text-muted mt-3">No media files found</p>
                            <a href="{{ route('admin.media.create') }}" class="btn btn-primary">
                                <i class="bi bi-cloud-upload"></i> Upload Files
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedIds = [];
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');
    const checkboxes = document.querySelectorAll('.media-item-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const id = this.value;
            const item = this.closest('.media-item');

            if (this.checked) {
                selectedIds.push(id);
                item.classList.add('selected');
            } else {
                selectedIds = selectedIds.filter(i => i !== id);
                item.classList.remove('selected');
            }

            updateBulkActions();
        });
    });

    function updateBulkActions() {
        if (selectedIds.length > 0) {
            bulkDeleteBtn.style.display = 'inline-block';
            selectedCount.textContent = selectedIds.length;
        } else {
            bulkDeleteBtn.style.display = 'none';
        }
    }

    bulkDeleteBtn.addEventListener('click', async function() {
        const _r = await Swal.fire({ title: 'Are you sure?', text: `Delete ${selectedIds.length} selected file(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
        if (!_r.isConfirmed) return;

        fetch('{{ route('admin.media.bulk-delete') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ ids: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                showError('Error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error', 'Failed to delete files');
        });
    });
});
</script>
@endpush

