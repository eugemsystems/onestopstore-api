@extends('admin.layout')

@section('title', 'Upload Files')

@push('styles')
<style>
    .upload-zone {
        border: 3px dashed #dee2e6;
        border-radius: 8px;
        padding: 60px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    .upload-zone:hover, .upload-zone.dragover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    .preview-item {
        position: relative;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        overflow: hidden;
    }
    .preview-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    .preview-item .remove-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 24px;
        height: 24px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-cloud-upload"></i> Upload Files</h2>
            <p class="text-muted mb-0">Upload images, videos, and documents to your media library</p>
        </div>
        <div>
            <a href="{{ route('admin.media.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Library
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Folder (Optional)</label>
                    <select name="folder" class="form-select">
                        <option value="">General / Uncategorized</option>
                        <option value="Product">Products</option>
                        <option value="Category">Categories</option>
                        <option value="Banner">Banners</option>
                        <option value="Blog">Blog</option>
                        <option value="User">Users</option>
                        <option value="Other">Other</option>
                    </select>
                    <small class="text-muted">Select a folder to organize your files</small>
                </div>

                <div class="upload-zone" id="uploadZone">
                    <i class="bi bi-cloud-arrow-up display-1 text-muted"></i>
                    <p class="mt-3 mb-2 fw-bold">Drag & Drop files here</p>
                    <p class="text-muted">or click to browse</p>
                    <input type="file" name="files[]" id="fileInput" multiple class="d-none" accept="image/*,video/*,.pdf,.doc,.docx">
                </div>

                <div id="previewContainer" class="preview-grid"></div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                        <i class="bi bi-cloud-upload"></i> Upload Files
                    </button>
                    <a href="{{ route('admin.media.index') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('fileInput');
    const previewContainer = document.getElementById('previewContainer');
    const submitBtn = document.getElementById('submitBtn');
    let selectedFiles = [];

    // Click to browse
    uploadZone.addEventListener('click', function() {
        fileInput.click();
    });

    // Drag and drop
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });

    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
    });

    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    // File input change
    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        for (let file of files) {
            if (file.size > 10 * 1024 * 1024) { // 10MB
                showWarning('File Too Large', file.name + ' is too large. Maximum size is 10MB.');
                continue;
            }

            selectedFiles.push(file);
            createPreview(file);
        }

        updateSubmitButton();
        updateFileInput();
    }

    function createPreview(file) {
        const div = document.createElement('div');
        div.className = 'preview-item';

        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            div.appendChild(img);
        } else {
            const icon = document.createElement('div');
            icon.className = 'text-center py-5';
            icon.innerHTML = '<i class="bi bi-file-earmark fs-1"></i><br><small>' + file.name + '</small>';
            div.appendChild(icon);
        }

        const removeBtn = document.createElement('button');
        removeBtn.className = 'remove-btn';
        removeBtn.type = 'button';
        removeBtn.innerHTML = '×';
        removeBtn.onclick = function() {
            const index = selectedFiles.indexOf(file);
            if (index > -1) {
                selectedFiles.splice(index, 1);
                div.remove();
                updateSubmitButton();
                updateFileInput();
            }
        };

        div.appendChild(removeBtn);
        previewContainer.appendChild(div);
    }

    function updateSubmitButton() {
        submitBtn.disabled = selectedFiles.length === 0;
    }

    function updateFileInput() {
        // Create a new FileList from selectedFiles
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
    }
});
</script>
@endpush

