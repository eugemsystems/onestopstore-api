@extends('admin.layout')

@section('title', 'Theme Options Management')

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
    .json-preview {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        max-height: 400px;
        overflow-y: auto;
    }
    .json-key {
        color: #0066cc;
        font-weight: bold;
    }
    .json-value {
        color: #008800;
    }
    .json-string {
        color: #dd1144;
    }
    .json-number {
        color: #009999;
    }
    .json-boolean {
        color: #990073;
    }
    .json-null {
        color: #999;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-palette-fill"></i> Theme Options Management</h2>
            <p class="text-muted mb-0">Manage frontend theme options (JSON format)</p>
        </div>
        <div>
            <button type="button" class="btn btn-outline-primary" id="formatJsonBtn">
                <i class="bi bi-code-square"></i> Format JSON
            </button>
            <button type="button" class="btn btn-outline-secondary" id="togglePreviewBtn">
                <i class="bi bi-eye"></i> Preview
            </button>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle"></i>
        <strong>Important:</strong> This is the raw JSON data from the <code>theme_options</code> table.
        Edit carefully to maintain valid JSON structure. Invalid JSON will prevent saving.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="row">
        <!-- Editor Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> JSON Editor</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.theme-options.update') }}" method="POST" id="themeOptionsForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Theme Options JSON Data</label>
                            <textarea name="options" id="jsonEditor" class="form-control">{{ json_encode($themeOption->options ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</textarea>
                            <div class="invalid-feedback" id="jsonError"></div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="saveBtn">
                                <i class="bi bi-save"></i> Save Theme Options
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Column -->
        <div class="col-lg-4">
            <!-- Quick Info Card -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Theme Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Record ID:</small>
                        <div class="fw-bold">{{ $themeOption->id ?? 'N/A' }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Last Updated:</small>
                        <div class="fw-bold">{{ $themeOption->updated_at ? $themeOption->updated_at->format('M d, Y H:i:s') : 'N/A' }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Created:</small>
                        <div class="fw-bold">{{ $themeOption->created_at ? $themeOption->created_at->format('M d, Y H:i:s') : 'N/A' }}</div>
                    </div>
                </div>
            </div>

            <!-- JSON Preview Card -->
            <div class="card border-0 shadow-sm" id="previewCard" style="display: none;">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-eye"></i> JSON Preview</h6>
                </div>
                <div class="card-body">
                    <div class="json-preview" id="jsonPreview">
                        <!-- Preview content will be inserted here -->
                    </div>
                </div>
            </div>

            <!-- Common Theme Structure -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-list-ul"></i> Common Structure</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">Typical theme options structure includes:</small>
                    <ul class="list-unstyled mt-2 small">
                        <li><i class="bi bi-dot"></i> <code>logo</code> - Site logos (header, footer, favicon)</li>
                        <li><i class="bi bi-dot"></i> <code>header</code> - Header settings</li>
                        <li><i class="bi bi-dot"></i> <code>footer</code> - Footer configuration</li>
                        <li><i class="bi bi-dot"></i> <code>colors</code> - Color scheme</li>
                        <li><i class="bi bi-dot"></i> <code>typography</code> - Font settings</li>
                        <li><i class="bi bi-dot"></i> <code>layout</code> - Layout options</li>
                        <li><i class="bi bi-dot"></i> <code>homepage</code> - Homepage sections</li>
                        <li><i class="bi bi-dot"></i> <code>product</code> - Product page settings</li>
                        <li><i class="bi bi-dot"></i> <code>seo</code> - SEO metadata</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
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
        if (previewCard.style.display === 'none') {
            previewCard.style.display = 'block';
            updatePreview();
            this.innerHTML = '<i class="bi bi-eye-slash"></i> Hide Preview';
        } else {
            previewCard.style.display = 'none';
            this.innerHTML = '<i class="bi bi-eye"></i> Preview';
        }
    });

    // Update preview
    function updatePreview() {
        try {
            const json = JSON.parse(editor.getValue());
            const preview = document.getElementById('jsonPreview');
            preview.innerHTML = formatJsonPreview(json);
        } catch (e) {
            document.getElementById('jsonPreview').innerHTML =
                '<div class="alert alert-danger mb-0">Invalid JSON</div>';
        }
    }

    // Format JSON for preview
    function formatJsonPreview(obj, indent = 0) {
        let html = '';
        const spacing = '&nbsp;&nbsp;&nbsp;&nbsp;'.repeat(indent);

        if (Array.isArray(obj)) {
            html += '[<br>';
            obj.forEach((item, index) => {
                html += spacing + '&nbsp;&nbsp;' + formatJsonPreview(item, indent + 1);
                if (index < obj.length - 1) html += ',';
                html += '<br>';
            });
            html += spacing + ']';
        } else if (typeof obj === 'object' && obj !== null) {
            html += '{<br>';
            const keys = Object.keys(obj);
            keys.forEach((key, index) => {
                html += spacing + '&nbsp;&nbsp;<span class="json-key">"' + key + '"</span>: ';
                html += formatJsonPreview(obj[key], indent + 1);
                if (index < keys.length - 1) html += ',';
                html += '<br>';
            });
            html += spacing + '}';
        } else if (typeof obj === 'string') {
            html += '<span class="json-string">"' + obj + '"</span>';
        } else if (typeof obj === 'number') {
            html += '<span class="json-number">' + obj + '</span>';
        } else if (typeof obj === 'boolean') {
            html += '<span class="json-boolean">' + obj + '</span>';
        } else if (obj === null) {
            html += '<span class="json-null">null</span>';
        }

        return html;
    }

    // Reset button
    document.getElementById('resetBtn').addEventListener('click', async function() {
        const _r = await Swal.fire({ title: 'Are you sure?', text: 'Are you sure you want to reset to the original value?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
        if (_r.isConfirmed) {
            editor.setValue(originalValue);
            showToast('Reset to original value', 'info');
        }
    });

    // Form validation
    document.getElementById('themeOptionsForm').addEventListener('submit', function(e) {
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
            showToast('Please fix JSON errors before saving', 'error');
        }
    });

    // Toast notification
    function showToast(message, type = 'info') {
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            info: '#17a2b8'
        };

        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 5000);
    }
});
</script>
@endpush

