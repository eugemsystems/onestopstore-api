@extends('admin.layout')

@section('title', 'Edit Template - Admin Panel')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('admin.promo-templates.index') }}" class="btn btn-outline-secondary mb-3">
                <i class="bi bi-arrow-left me-2"></i>Back to Templates
            </a>
            <h2><i class="bi bi-pencil me-2"></i>Edit Template: {{ $template->name }}</h2>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.promo-templates.update', $template->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

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
                                   id="name" name="name" value="{{ old('name', $template->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description', $template->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="html_content" class="form-label">HTML Content <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('html_content') is-invalid @enderror font-monospace"
                                      id="html_content" name="html_content" rows="20" required>{{ old('html_content', $template->html_content) }}</textarea>
                            @error('html_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('admin.promo-templates.generate', $template->id) }}" class="btn btn-success w-100 mb-2">
                            <i class="bi bi-magic me-2"></i>Generate Flyer
                        </a>
                        <form action="{{ route('admin.promo-templates.duplicate', $template->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-files me-2"></i>Duplicate Template
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="status" name="status" value="1"
                                       {{ old('status', $template->status) ? 'checked' : '' }}>
                                <label class="form-check-label" for="status">Active</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview Image --}}
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-image me-2"></i>Preview Image</h5>
                        <small class="text-muted">Shown on template picker</small>
                    </div>
                    <div class="card-body">

                        {{-- Current preview --}}
                        @if($template->preview_image)
                        <div id="currentPreviewWrap" class="mb-3">
                            <img src="{{ $template->preview_image }}" alt="Preview"
                                 class="img-fluid rounded border" style="max-height:160px;width:100%;object-fit:cover;">
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">Current preview image</small>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearPreview">
                                    <i class="bi bi-x"></i> Remove
                                </button>
                            </div>
                        </div>
                        @endif

                        {{-- Live preview (hidden until chosen) --}}
                        <div id="newPreviewWrap" class="mb-3" style="display:none;">
                            <img id="newPreviewImg" src="" alt="New preview"
                                 class="img-fluid rounded border" style="max-height:160px;width:100%;object-fit:cover;">
                            <small class="text-muted">New preview</small>
                        </div>

                        {{-- Upload file --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-upload me-1"></i>Upload Image
                            </label>
                            <input type="file" class="form-control form-control-sm"
                                   name="preview_image_file" id="previewFile"
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <div class="form-text">JPG, PNG, GIF or WebP — max 5 MB</div>
                        </div>

                        {{-- Or URL --}}
                        <div class="mb-1">
                            <label class="form-label fw-semibold small">
                                <i class="bi bi-link-45deg me-1"></i>Or paste image URL
                            </label>
                            <input type="url" class="form-control form-control-sm"
                                   name="preview_image_url" id="previewUrl"
                                   placeholder="https://example.com/preview.jpg"
                                   value="{{ old('preview_image_url') }}">
                        </div>

                        {{-- Hidden clear flag --}}
                        <input type="hidden" name="clear_preview_image" id="clearPreviewFlag" value="">
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="bi bi-save me-2"></i>Update Template
                        </button>
                        <button type="button" class="btn btn-outline-danger w-100" onclick="confirmDelete()">
                            <i class="bi bi-trash me-2"></i>Delete Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <form id="delete-form" action="{{ route('admin.promo-templates.destroy', $template->id) }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>

<script>
async function confirmDelete() {
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Are you sure you want to delete this template? This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (_r.isConfirmed) {
        document.getElementById('delete-form').submit();
    }
}

// ── Preview image live preview ──────────────────────────────────────────────
const previewFile    = document.getElementById('previewFile');
const previewUrl     = document.getElementById('previewUrl');
const newPreviewWrap = document.getElementById('newPreviewWrap');
const newPreviewImg  = document.getElementById('newPreviewImg');
const clearFlag      = document.getElementById('clearPreviewFlag');
const clearBtn       = document.getElementById('btnClearPreview');
const currentWrap    = document.getElementById('currentPreviewWrap');

function showNewPreview(src) {
    if (!src) { newPreviewWrap.style.display = 'none'; return; }
    newPreviewImg.src = src;
    newPreviewWrap.style.display = 'block';
}

// File upload → live preview via FileReader
if (previewFile) {
    previewFile.addEventListener('change', function () {
        if (!this.files[0]) { showNewPreview(null); return; }
        previewUrl.value = ''; // clear URL when file chosen
        clearFlag.value  = '';
        const reader = new FileReader();
        reader.onload = e => showNewPreview(e.target.result);
        reader.readAsDataURL(this.files[0]);
    });
}

// URL input → live preview on blur / enter
if (previewUrl) {
    const applyUrl = () => {
        const val = previewUrl.value.trim();
        if (val) {
            previewFile.value = ''; // clear file when URL typed
            clearFlag.value   = '';
            showNewPreview(val);
        } else {
            showNewPreview(null);
        }
    };
    previewUrl.addEventListener('blur',  applyUrl);
    previewUrl.addEventListener('keyup', e => { if (e.key === 'Enter') applyUrl(); });
}

// Remove current image
if (clearBtn) {
    clearBtn.addEventListener('click', function () {
        clearFlag.value = '1';
        if (currentWrap) currentWrap.style.display = 'none';
        previewFile.value = '';
        previewUrl.value  = '';
        showNewPreview(null);
    });
}
</script>
@endsection

