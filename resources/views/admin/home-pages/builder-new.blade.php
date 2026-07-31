@extends('admin.layout')

@section('title', 'Edit Home Page - ' . $homePage->slug)

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
<style>
    .CodeMirror {
        height: 600px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }
    .section-preview {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .section-header {
        font-weight: bold;
        color: #0d6efd;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 2px solid #0d6efd;
        cursor: pointer;
        user-select: none;
    }
    .section-content {
        padding-left: 20px;
        font-size: 13px;
    }
    .json-key {
        color: #0066cc;
        font-weight: 600;
    }
    .json-value {
        color: #666;
    }
    .section-collapsed .section-body {
        display: none;
    }
    .sticky-save {
        position: sticky;
        top: 20px;
        z-index: 100;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-pencil-square"></i> Edit Home Page</h2>
            <p class="text-muted mb-0">Editing: <span class="badge bg-primary">{{ $homePage->slug }}</span></p>
        </div>
        <div>
            <a href="{{ route('admin.home-pages.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <button type="button" class="btn btn-outline-primary" id="formatJsonBtn">
                <i class="bi bi-code-square"></i> Format JSON
            </button>
            <button type="button" class="btn btn-outline-info" id="togglePreviewBtn">
                <i class="bi bi-eye"></i> Toggle Preview
            </button>
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

    <div class="alert alert-info alert-dismissible fade show">
        <i class="bi bi-lightbulb"></i>
        <strong>Tip:</strong> This editor preserves your exact JSON structure. Edit the JSON directly, or use the preview panel to understand the structure.
        All sections (home_banner, featured_banners, main_content, etc.) are preserved exactly as they are.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <form action="{{ route('admin.home-pages.update-builder', $homePage->id) }}" method="POST" id="builderForm">
        @csrf

        <div class="row">
            <!-- Editor Column -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="bi bi-code-slash"></i> JSON Editor</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Slug (Theme Identifier)</label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug', $homePage->slug) }}" required>
                            <small class="text-muted">Identifies which theme this home page belongs to</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Content JSON</label>
                            <textarea name="content_json" id="jsonEditor" class="form-control" style="display: none;">{{ json_encode($homePage->content ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</textarea>
                            <div class="invalid-feedback" id="jsonError"></div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                            <a href="{{ route('admin.home-pages.index') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="button" class="btn btn-outline-warning btn-lg" id="resetBtn">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Column -->
            <div class="col-lg-4">
                <div class="sticky-save">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0"><i class="bi bi-info-circle"></i> Page Info</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <small class="text-muted">ID:</small>
                                <div class="fw-bold">#{{ $homePage->id }}</div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Slug:</small>
                                <div class="fw-bold">{{ $homePage->slug }}</div>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Last Updated:</small>
                                <div class="fw-bold">{{ $homePage->updated_at->format('M d, Y H:i') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm" id="previewCard">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0"><i class="bi bi-tree"></i> Structure Preview</h6>
                        </div>
                        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                            <div id="structurePreview"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('jsonEditor');
    const originalValue = textarea.value;

    // Initialize CodeMirror
    const editor = CodeMirror.fromTextArea(textarea, {
        mode: 'application/json',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 2,
        tabSize: 2,
        matchBrackets: true,
        autoCloseBrackets: true,
        foldGutter: true,
        gutters: ['CodeMirror-linenumbers', 'CodeMirror-foldgutter']
    });

    // Update preview on change
    editor.on('change', function() {
        updateStructurePreview();
    });

    // Format JSON button
    document.getElementById('formatJsonBtn').addEventListener('click', function() {
        try {
            const json = JSON.parse(editor.getValue());
            editor.setValue(JSON.stringify(json, null, 2));
            showToast('JSON formatted successfully!', 'success');
        } catch (e) {
            showToast('Invalid JSON: ' + e.message, 'error');
        }
    });

    // Toggle preview
    document.getElementById('togglePreviewBtn').addEventListener('click', function() {
        const previewCard = document.getElementById('previewCard');
        previewCard.style.display = previewCard.style.display === 'none' ? 'block' : 'none';
    });

    // Reset button
    document.getElementById('resetBtn').addEventListener('click', async function() {
        const _r = await Swal.fire({ title: 'Are you sure?', text: 'Reset to original value? This will discard all unsaved changes.', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
        if (_r.isConfirmed) {
            editor.setValue(originalValue);
            showToast('Reset to original value', 'info');
        }
    });

    // Update structure preview
    function updateStructurePreview() {
        try {
            const json = JSON.parse(editor.getValue());
            const preview = document.getElementById('structurePreview');
            preview.innerHTML = renderStructure(json);
        } catch (e) {
            document.getElementById('structurePreview').innerHTML =
                '<div class="alert alert-warning mb-0"><small>Invalid JSON - fix errors to see preview</small></div>';
        }
    }

    // Render JSON structure as expandable tree
    function renderStructure(obj, depth = 0) {
        let html = '';
        const indent = depth * 15;

        if (Array.isArray(obj)) {
            html += `<div style="margin-left: ${indent}px;" class="text-muted"><small>Array (${obj.length} items)</small></div>`;
            obj.forEach((item, index) => {
                html += `<div style="margin-left: ${indent + 10}px;">`;
                html += `<span class="text-secondary">[${index}]</span> `;
                html += renderStructure(item, depth + 1);
                html += '</div>';
            });
        } else if (typeof obj === 'object' && obj !== null) {
            Object.keys(obj).forEach(key => {
                const value = obj[key];
                html += `<div class="section-preview" style="margin-left: ${indent}px;">`;

                if (typeof value === 'object' && value !== null) {
                    html += `<div class="section-header json-expandable" onclick="this.parentElement.classList.toggle('section-collapsed')">`;
                    html += `<i class="bi bi-chevron-down"></i> <span class="json-key">${key}</span>`;
                    html += `</div>`;
                    html += `<div class="section-body">`;
                    html += renderStructure(value, depth + 1);
                    html += `</div>`;
                } else {
                    html += `<span class="json-key">${key}:</span> `;
                    html += renderValue(value);
                }

                html += '</div>';
            });
        } else {
            html += renderValue(obj);
        }

        return html;
    }

    // Render individual value
    function renderValue(value) {
        if (typeof value === 'string') {
            // Truncate long strings
            const displayValue = value.length > 50 ? value.substring(0, 50) + '...' : value;
            return `<span class="json-string">"${displayValue}"</span>`;
        } else if (typeof value === 'number') {
            return `<span class="json-number">${value}</span>`;
        } else if (typeof value === 'boolean') {
            return `<span class="json-boolean">${value}</span>`;
        } else if (value === null) {
            return `<span class="json-null">null</span>`;
        }
        return String(value);
    }

    // Form validation
    document.getElementById('builderForm').addEventListener('submit', function(e) {
        const jsonError = document.getElementById('jsonError');
        jsonError.textContent = '';
        textarea.classList.remove('is-invalid');

        try {
            // Validate JSON
            JSON.parse(editor.getValue());

            // Update textarea value before submit
            textarea.value = editor.getValue();

            // Show loading
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

        } catch (error) {
            e.preventDefault();
            textarea.classList.add('is-invalid');
            jsonError.textContent = 'Invalid JSON: ' + error.message;
            jsonError.style.display = 'block';
            showToast('Please fix JSON errors before saving', 'error');
        }
    });

    // Toast notification
    function showToast(message, type = 'info') {
        const alertClass = type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info';
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                <i class="bi bi-${type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    // Initial preview
    updateStructurePreview();
});
</script>
@endpush

