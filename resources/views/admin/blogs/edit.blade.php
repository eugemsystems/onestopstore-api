@extends('admin.layout')

@section('title', 'Edit Blog - Admin Panel')
@section('page-title', 'Edit Blog')

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet" />
<style>
    .form-section {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .form-section h5 {
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f0f0f0;
        color: #062a6a;
        font-weight: 700;
    }

    .image-preview-container {
        margin-top: 10px;
    }

    .image-preview-box {
        position: relative;
        width: 150px;
        height: 150px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #e0e0e0;
    }

    .image-preview-box.hidden {
        display: none;
    }

    .image-preview-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-preview-box .remove-preview {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 0.75rem;
    }

    .current-image {
        position: relative;
        display: inline-block;
        margin-bottom: 10px;
    }

    .current-image img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #e0e0e0;
    }

    .current-image-label {
        font-size: 0.75rem;
        color: #6c757d;
        display: block;
        margin-top: 4px;
    }

    .upload-area {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-area:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
    }

    #quill-editor {
        min-height: 300px;
        background: white;
    }

    .ql-toolbar.ql-snow {
        border-radius: 8px 8px 0 0;
        border-color: #dee2e6;
    }

    .ql-container.ql-snow {
        border-radius: 0 0 8px 8px;
        border-color: #dee2e6;
        font-size: 1rem;
    }

    .sticky-save-bar {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1050;
        display: flex;
        gap: 10px;
        animation: slideInRight 0.3s ease;
    }

    @keyframes slideInRight {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    .sticky-save-bar .btn {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 12px 24px;
        font-weight: 600;
    }

    .form-check-input:checked {
        background-color: #667eea;
        border-color: #667eea;
    }

    @media (max-width: 768px) {
        .sticky-save-bar {
            top: auto;
            bottom: 15px;
            right: 15px;
            left: 15px;
            justify-content: stretch;
        }

        .sticky-save-bar .btn {
            flex: 1;
            padding: 10px 15px;
        }
    }
</style>
@endpush

@section('content')
<!-- Sticky Save Buttons -->
<div class="sticky-save-bar">
    <a href="{{ route('admin.blogs.index') }}" class="btn btn-secondary">
        <i class="bi bi-x-circle me-1"></i> Cancel
    </a>
    <button type="submit" form="blogForm" class="btn btn-primary">
        <i class="bi bi-check-circle me-1"></i> Update Blog
    </button>
</div>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square me-2"></i>Edit Blog</h2>
        <div>
            <a href="{{ route('admin.blogs.index') }}" class="btn btn-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i> Back to Blogs
            </a>
            <button type="submit" form="blogForm" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Update Blog
            </button>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.blogs.update', $blog->id) }}" method="POST" id="blogForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <!-- Main Content Column -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="form-section">
                    <h5><i class="bi bi-info-circle me-2"></i>Basic Information</h5>

                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $blog->title) }}" required placeholder="Enter blog title">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3" placeholder="Short excerpt or summary">{{ old('description', $blog->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Content</label>
                        <div style="display:flex; justify-content:flex-end; margin-bottom:6px; gap:6px;">
                            <button type="button" id="btnVisual" onclick="switchToVisual()" style="background:#0c79ba; color:#fff; border:none; border-radius:6px; padding:5px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                &#9998; Visual Editor
                            </button>
                            <button type="button" id="btnSource" onclick="switchToSource()" style="background:#475569; color:#fff; border:none; border-radius:6px; padding:5px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                &lt;/&gt; HTML Source
                            </button>
                            <button type="button" id="btnPreview" onclick="switchToPreview()" style="background:#475569; color:#fff; border:none; border-radius:6px; padding:5px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                &#128065; Preview
                            </button>
                        </div>
                        <div id="quill-editor"></div>
                        <textarea id="html-source" style="display:none; width:100%; min-height:400px; font-family:monospace; font-size:13px; padding:12px; border:1px solid #ccc; border-radius:6px; background:#1e293b; color:#e2e8f0; line-height:1.6; resize:vertical;" placeholder="Paste your HTML here..."></textarea>
                        <div id="html-preview" style="display:none; min-height:300px; padding:20px; border:1px solid #e2e8f0; border-radius:6px; background:#fff; overflow:auto; line-height:1.7; font-size:15px; color:#334155;"></div>
                        <input type="hidden" name="content" id="content-input">
                        @error('content')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Images -->
                <div class="form-section">
                    <h5><i class="bi bi-images me-2"></i>Images</h5>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Blog Thumbnail</label>
                        @if($blog->blog_thumbnail && ($blog->blog_thumbnail->image_url || $blog->blog_thumbnail->original_url))
                            <div class="current-image">
                                <img src="{{ $blog->blog_thumbnail->image_url ?? $blog->blog_thumbnail->original_url }}" alt="Current thumbnail">
                                <span class="current-image-label">Current thumbnail</span>
                            </div>
                        @endif
                        <div class="upload-area" onclick="document.getElementById('blog_thumbnail').click()">
                            <i class="bi bi-cloud-arrow-up" style="font-size: 1.5rem; color: #667eea;"></i>
                            <p class="mb-0 mt-1 text-muted small">Click to upload a new thumbnail image</p>
                            <p class="mb-0 text-muted" style="font-size: 0.75rem;">JPEG, PNG, JPG, WEBP (max 5MB)</p>
                        </div>
                        <input type="file" name="blog_thumbnail" id="blog_thumbnail" class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'thumbnail-preview')">
                        @error('blog_thumbnail')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div class="image-preview-container">
                            <div class="image-preview-box hidden" id="thumbnail-preview">
                                <img src="" alt="Thumbnail preview">
                                <button type="button" class="remove-preview" onclick="removePreview('blog_thumbnail', 'thumbnail-preview')">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta Image <span class="text-muted fw-normal">(optional)</span></label>
                        @if($blog->blog_meta_image && ($blog->blog_meta_image->image_url || $blog->blog_meta_image->original_url))
                            <div class="current-image">
                                <img src="{{ $blog->blog_meta_image->image_url ?? $blog->blog_meta_image->original_url }}" alt="Current meta image">
                                <span class="current-image-label">Current meta image</span>
                            </div>
                        @endif
                        <div class="upload-area" onclick="document.getElementById('blog_meta_image').click()">
                            <i class="bi bi-cloud-arrow-up" style="font-size: 1.5rem; color: #667eea;"></i>
                            <p class="mb-0 mt-1 text-muted small">Click to upload a new meta image for SEO</p>
                            <p class="mb-0 text-muted" style="font-size: 0.75rem;">JPEG, PNG, JPG, WEBP (max 5MB)</p>
                        </div>
                        <input type="file" name="blog_meta_image" id="blog_meta_image" class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'meta-image-preview')">
                        @error('blog_meta_image')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div class="image-preview-container">
                            <div class="image-preview-box hidden" id="meta-image-preview">
                                <img src="" alt="Meta image preview">
                                <button type="button" class="remove-preview" onclick="removePreview('blog_meta_image', 'meta-image-preview')">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO -->
                <div class="form-section">
                    <h5><i class="bi bi-search me-2"></i>SEO</h5>

                    <div class="mb-3">
                        <label for="meta_title" class="form-label fw-semibold">Meta Title</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control" value="{{ old('meta_title', $blog->meta_title) }}" placeholder="SEO title (defaults to blog title if empty)">
                    </div>

                    <div class="mb-3">
                        <label for="meta_description" class="form-label fw-semibold">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" class="form-control" rows="3" placeholder="SEO description for search engines">{{ old('meta_description', $blog->meta_description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Sidebar Column -->
            <div class="col-lg-4">
                <!-- Publish Settings -->
                <div class="form-section">
                    <h5><i class="bi bi-gear me-2"></i>Publish Settings</h5>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ old('status', $blog->status) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="status">Active</label>
                        </div>
                        <small class="text-muted">Uncheck to save as draft</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $blog->is_featured) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_featured">Featured</label>
                        </div>
                        <small class="text-muted">Show in featured section</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_sticky" id="is_sticky" value="1" {{ old('is_sticky', $blog->is_sticky) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_sticky">Sticky</label>
                        </div>
                        <small class="text-muted">Pin to top of blog list</small>
                    </div>

                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <strong>Created:</strong> {{ $blog->created_at->format('M d, Y H:i') }}<br>
                            <strong>Updated:</strong> {{ $blog->updated_at->format('M d, Y H:i') }}
                            @if($blog->created_by)
                                <br><strong>Author:</strong> {{ $blog->created_by->name }}
                            @endif
                        </small>
                    </div>
                </div>

                <!-- Categories -->
                <div class="form-section">
                    <h5><i class="bi bi-tags me-2"></i>Categories</h5>

                    <div class="mb-2">
                        <input type="text" id="categorySearch" class="form-control form-control-sm" placeholder="Search categories...">
                    </div>
                    <div id="categoryListContainer" style="max-height: 350px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 8px;">
                        <div class="text-muted small">Loading categories...</div>
                    </div>
                    <small class="text-muted d-block mt-1">Check categories to assign. Use search to filter.</small>
                </div>

                <!-- Tags -->
                <div class="form-section">
                    <h5><i class="bi bi-bookmark me-2"></i>Tags</h5>

                    <div class="mb-2">
                        <input type="text" id="tagSearch" class="form-control form-control-sm" placeholder="Search tags...">
                    </div>
                    <div id="tagListContainer" style="max-height: 250px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; padding: 8px;">
                        <div class="text-muted small">Loading tags...</div>
                    </div>
                    <small class="text-muted d-block mt-1">Check tags to assign. Use search to filter.</small>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
    // Initialize Quill Editor
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Write your blog content here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'font': [] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                [{ 'align': [] }],
                ['blockquote', 'code-block'],
                ['link', 'image', 'video'],
                ['clean']
            ]
        }
    });

    // Load existing content into Quill
    var existingContent = @json(old('content', $blog->content ?? ''));
    if (existingContent) {
        quill.root.innerHTML = existingContent;
    }

    // Editor mode: 'visual', 'source', 'preview'
    var editorMode = 'visual';
    var rawHtmlContent = '';

    function setActiveBtn(active) {
        document.getElementById('btnVisual').style.background = active === 'visual' ? '#0c79ba' : '#475569';
        document.getElementById('btnSource').style.background = active === 'source' ? '#0c79ba' : '#475569';
        document.getElementById('btnPreview').style.background = active === 'preview' ? '#0c79ba' : '#475569';
    }

    function hideAll() {
        document.getElementById('quill-editor').style.display = 'none';
        document.querySelector('.ql-toolbar').style.display = 'none';
        document.getElementById('html-source').style.display = 'none';
        document.getElementById('html-preview').style.display = 'none';
    }

    async function switchToVisual() {
        if (editorMode === 'source' && rawHtmlContent) {
            var source = document.getElementById('html-source');
            if (source.value.trim() && source.value !== quill.root.innerHTML) {
                const _r = await Swal.fire({ title: 'Are you sure?', text: 'Switching to Visual Editor will lose tables, inline styles, and some HTML. Continue?\n\nTip: Use Preview to see your HTML, or stay in Source mode and save directly.', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
                if (!_r.isConfirmed) return;
                quill.root.innerHTML = source.value;
                rawHtmlContent = '';
            }
        }
        hideAll();
        document.getElementById('quill-editor').style.display = '';
        document.querySelector('.ql-toolbar').style.display = '';
        editorMode = 'visual';
        setActiveBtn('visual');
    }

    function switchToSource() {
        hideAll();
        var source = document.getElementById('html-source');
        if (!rawHtmlContent && editorMode === 'visual') {
            source.value = quill.root.innerHTML;
        } else if (rawHtmlContent) {
            source.value = rawHtmlContent;
        }
        source.style.display = 'block';
        editorMode = 'source';
        setActiveBtn('source');
    }

    function switchToPreview() {
        hideAll();
        var preview = document.getElementById('html-preview');
        var html = '';
        if (rawHtmlContent) {
            html = rawHtmlContent;
        } else if (editorMode === 'source') {
            html = document.getElementById('html-source').value;
        } else {
            html = quill.root.innerHTML;
        }
        preview.innerHTML = html;
        preview.style.display = 'block';
        editorMode = 'preview';
        setActiveBtn('preview');
    }

    document.getElementById('html-source').addEventListener('input', function() {
        rawHtmlContent = this.value;
    });

    // Sync content to hidden input on form submit
    document.getElementById('blogForm').addEventListener('submit', function() {
        if (rawHtmlContent) {
            document.getElementById('content-input').value = rawHtmlContent;
        } else {
            document.getElementById('content-input').value = quill.root.innerHTML;
        }
    });

    // Load categories and tags on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadBlogCategories();
        loadBlogTags();
    });

    // Category loading and rendering
    var oldSelectedCategories = @json(old('categories', $blog->categories->pluck('id')->toArray()));

    async function loadBlogCategories() {
        var container = document.getElementById('categoryListContainer');
        try {
            var response = await fetch('{{ route("admin.blogs.categories-data") }}');
            if (!response.ok) {
                var errData = await response.json().catch(function() { return {}; });
                container.innerHTML = '<div class="text-danger small">' + (errData.error || errData.message || 'Failed to load categories.') + '</div>';
                return;
            }
            var data = await response.json();

            // The API returns a flat array: [{id, name, path, level}, ...]
            if (!Array.isArray(data) || data.length === 0) {
                container.innerHTML = '<div class="text-muted small">No categories found.</div>';
                return;
            }

            var html = '';
            data.forEach(function(cat) {
                var isChecked = oldSelectedCategories.indexOf(cat.id) !== -1 || oldSelectedCategories.indexOf(String(cat.id)) !== -1;
                var indent = (cat.level || 0) * 18;
                var searchText = (cat.path || cat.name || '').toLowerCase();
                html += '<div class="form-check category-check-item" style="padding-left: ' + (indent + 4) + 'px; margin-bottom: 2px;" data-search="' + searchText + '">' +
                    '<input class="form-check-input" type="checkbox" name="categories[]" value="' + cat.id + '" id="blog_cat_' + cat.id + '"' + (isChecked ? ' checked' : '') + '>' +
                    '<label class="form-check-label small" for="blog_cat_' + cat.id + '">' + cat.name + '</label>' +
                    '</div>';
            });
            container.innerHTML = html;
        } catch (error) {
            console.error('Error loading categories:', error);
            container.innerHTML = '<div class="text-danger small">Error loading categories. Please refresh.</div>';
        }
    }

    // Search filter for categories
    document.getElementById('categorySearch').addEventListener('input', function() {
        var term = this.value.toLowerCase().trim();
        var items = document.querySelectorAll('.category-check-item');
        items.forEach(function(item) {
            var searchText = item.getAttribute('data-search');
            item.style.display = searchText.indexOf(term) !== -1 ? '' : 'none';
        });
    });

    // Tag loading and rendering
    var allTags = [];
    var oldSelectedTags = @json(old('tags', $blog->tags->pluck('id')->toArray()));

    async function loadBlogTags() {
        var container = document.getElementById('tagListContainer');
        try {
            var response = await fetch('{{ route("admin.blogs.tags-data") }}');
            if (!response.ok) {
                container.innerHTML = '<div class="text-danger small">Failed to load tags. Please refresh.</div>';
                return;
            }
            allTags = await response.json();
            renderTagCheckboxes(allTags);
        } catch (error) {
            console.error('Error loading tags:', error);
            container.innerHTML = '<div class="text-danger small">Error loading tags. Please refresh.</div>';
        }
    }

    function renderTagCheckboxes(tags) {
        var container = document.getElementById('tagListContainer');
        if (!tags || tags.length === 0) {
            container.innerHTML = '<div class="text-muted small">No tags found.</div>';
            return;
        }
        var html = '';
        tags.forEach(function(tag) {
            var isChecked = oldSelectedTags.indexOf(tag.id) !== -1 || oldSelectedTags.indexOf(String(tag.id)) !== -1;
            html += '<div class="form-check tag-check-item" style="margin-bottom: 2px;" data-search="' + tag.name.toLowerCase() + '">' +
                '<input class="form-check-input" type="checkbox" name="tags[]" value="' + tag.id + '" id="blog_tag_' + tag.id + '"' + (isChecked ? ' checked' : '') + '>' +
                '<label class="form-check-label small" for="blog_tag_' + tag.id + '">' + tag.name + '</label>' +
                '</div>';
        });
        container.innerHTML = html;
    }

    // Search filter for tags
    document.getElementById('tagSearch').addEventListener('input', function() {
        var term = this.value.toLowerCase().trim();
        var items = document.querySelectorAll('.tag-check-item');
        items.forEach(function(item) {
            var searchText = item.getAttribute('data-search');
            item.style.display = searchText.indexOf(term) !== -1 ? '' : 'none';
        });
    });

    // Image preview
    function previewImage(input, previewId) {
        var previewBox = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                previewBox.querySelector('img').src = e.target.result;
                previewBox.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Remove image preview
    function removePreview(inputId, previewId) {
        document.getElementById(inputId).value = '';
        var previewBox = document.getElementById(previewId);
        previewBox.classList.add('hidden');
        previewBox.querySelector('img').src = '';
    }
</script>
@endpush
