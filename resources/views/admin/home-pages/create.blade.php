@extends('admin.layout')

@section('title', 'Create New Home Page')

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
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-plus-circle"></i> Create New Home Page</h2>
            <p class="text-muted mb-0">Add a new homepage configuration for a theme</p>
        </div>
        <div>
            <a href="{{ route('admin.home-pages.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <button type="button" class="btn btn-outline-primary" id="formatJsonBtn">
                <i class="bi bi-code-square"></i> Format JSON
            </button>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle"></i>
        <strong>Creating a Home Page:</strong> The slug should match your theme identifier (e.g., "paris", "tokyo", "berlin").
        The content should be valid JSON containing your homepage sections.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="row">
        <!-- Editor Column -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Home Page Details</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.home-pages.store') }}" method="POST" id="homePageForm">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">Slug (Theme Identifier) <span class="text-danger">*</span></label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" required placeholder="e.g., paris, tokyo, berlin">
                            <small class="text-muted">Must be unique. Use lowercase letters and hyphens only.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Content JSON Data <span class="text-danger">*</span></label>
                            <textarea name="content" id="jsonEditor" class="form-control">{
  "hero": {
    "title": "Welcome to Our Store",
    "subtitle": "Discover amazing products",
    "button_text": "Shop Now",
    "button_link": "/products"
  },
  "featured_products": {
    "enabled": true,
    "title": "Featured Products",
    "limit": 8
  },
  "categories": {
    "enabled": true,
    "title": "Shop by Category"
  },
  "banners": []
}</textarea>
                            <div class="invalid-feedback" id="jsonError"></div>
                            <small class="text-muted">Edit the JSON above to match your homepage structure</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="saveBtn">
                                <i class="bi bi-save"></i> Create Home Page
                            </button>
                            <a href="{{ route('admin.home-pages.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Column -->
        <div class="col-lg-4">
            <!-- Example Structure -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Example Themes</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">Common theme slugs:</small>
                    <ul class="list-unstyled mt-2 small">
                        <li><i class="bi bi-dot"></i> <code>paris</code></li>
                        <li><i class="bi bi-dot"></i> <code>tokyo</code></li>
                        <li><i class="bi bi-dot"></i> <code>berlin</code></li>
                        <li><i class="bi bi-dot"></i> <code>madrid</code></li>
                        <li><i class="bi bi-dot"></i> <code>rome</code></li>
                        <li><i class="bi bi-dot"></i> <code>osaka</code></li>
                        <li><i class="bi bi-dot"></i> <code>denver</code></li>
                    </ul>
                </div>
            </div>

            <!-- Common Sections -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0"><i class="bi bi-list-ul"></i> Common Sections</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">Typical home page sections:</small>
                    <ul class="list-unstyled mt-2 small">
                        <li><i class="bi bi-dot"></i> <code>hero</code> - Hero/banner section</li>
                        <li><i class="bi bi-dot"></i> <code>featured_products</code> - Featured products</li>
                        <li><i class="bi bi-dot"></i> <code>categories</code> - Category sections</li>
                        <li><i class="bi bi-dot"></i> <code>banners</code> - Promotional banners</li>
                        <li><i class="bi bi-dot"></i> <code>testimonials</code> - Customer reviews</li>
                        <li><i class="bi bi-dot"></i> <code>services</code> - Service highlights</li>
                        <li><i class="bi bi-dot"></i> <code>blog</code> - Blog/news section</li>
                        <li><i class="bi bi-dot"></i> <code>newsletter</code> - Newsletter signup</li>
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

    // Form validation
    document.getElementById('homePageForm').addEventListener('submit', function(e) {
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
            saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Creating...';

        } catch (error) {
            e.preventDefault();
            textarea.classList.add('is-invalid');
            jsonError.textContent = 'Invalid JSON: ' + error.message;
            showToast('Please fix JSON errors before saving', 'error');
        }
    });

    // Toast notification
    function showToast(message, type = 'info') {
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

