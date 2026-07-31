@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Create System Ticket</h1>
                <a href="{{ route('admin.system-tickets.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Tickets
                </a>
            </div>
        </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form id="create-ticket-form" action="{{ route('admin.system-tickets.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            {{-- Hidden input receives Quill HTML on submit --}}
                            <input type="hidden" name="description" id="description-input">
                            @error('description')
                            <div class="text-danger small mb-1">{{ $message }}</div>
                            @enderror
                            <div id="description-editor" style="min-height: 200px;"></div>
                            <small class="text-muted">Describe the issue in detail. Include steps to reproduce if applicable.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                                    <option value="">Select Priority</option>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                                    <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                                </select>
                                @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category') }}" placeholder="e.g., Orders, Products, Users">
                                @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="attachments" class="form-label">Attachments (Images Only)</label>
                            <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" id="attachments" name="attachments[]" multiple accept="image/*">
                            @error('attachments.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">You can upload multiple images (JPEG, PNG, GIF). Max size: 5MB per image.</small>
                        </div>

                        <div id="preview-container" class="mb-3"></div>

                        <div class="mb-3">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-select @error('assigned_to') is-invalid @enderror" id="assigned_to" name="assigned_to">
                                <option value="">-- Unassigned --</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                                @endforeach
                            </select>
                            @error('assigned_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Optional — you can assign later.</small>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.system-tickets.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Create Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Assignment card --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-person-check"></i> Assignment</h5>
                    <hr>
                    <p class="small text-muted">Use the <strong>Assign To</strong> field in the form to assign this ticket to a team member immediately on creation.</p>
                    <p class="small text-muted mb-0">If left unassigned the ticket can be assigned later from the ticket detail page.</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Priority Guidelines</h5>
                    <hr>
                    <div class="mb-3">
                        <span class="badge bg-danger mb-1">Critical</span>
                        <p class="small mb-0">System is down or major functionality is broken. Requires immediate attention.</p>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-warning mb-1">High</span>
                        <p class="small mb-0">Important feature not working. Affects multiple users or core functionality.</p>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-info mb-1">Medium</span>
                        <p class="small mb-0">Issue affects some users or non-critical functionality.</p>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-success mb-1">Low</span>
                        <p class="small mb-0">Minor issue, cosmetic problem, or feature request.</p>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Tips</h5>
                    <hr>
                    <ul class="small">
                        <li>Be specific and clear in your title</li>
                        <li>Include steps to reproduce the issue</li>
                        <li>Attach screenshots if possible</li>
                        <li>Mention which page/section has the issue</li>
                        <li>Note any error messages you see</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<style>
    #description-editor {
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 4px 4px;
        background: #fff;
    }
    #description-editor .ql-toolbar { display: none; }
    .ql-toolbar.ql-snow { border: 1px solid #dee2e6; border-radius: 4px 4px 0 0; }
</style>

<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Quill description editor
    var descQuill = new Quill('#description-editor', {
        theme: 'snow',
        placeholder: 'Describe the issue in detail. Include steps to reproduce if applicable...',
        modules: {
            toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'code-block'],
                ['link'],
                ['clean']
            ]
        }
    });

    @if(old('description'))
    descQuill.clipboard.dangerouslyPasteHTML({!! json_encode(old('description')) !!});
    @endif

    // Keep hidden input always in sync with editor content
    descQuill.on('text-change', function () {
        var text = descQuill.getText().trim();
        document.getElementById('description-input').value = text ? descQuill.getSemanticHTML() : '';
    });

    // Guard: block submit only if editor is blank
    document.getElementById('create-ticket-form').addEventListener('submit', function (e) {
        if (!descQuill.getText().trim()) {
            e.preventDefault();
            showWarning('Required', 'Please enter a description.');
        }
    });

    // Image attachment preview
    document.getElementById('attachments').addEventListener('change', function(e) {
        const previewContainer = document.getElementById('preview-container');
        previewContainer.innerHTML = '';

        if (this.files.length > 0) {
            previewContainer.innerHTML = '<label class="form-label">Preview:</label><div class="row g-2" id="preview-grid"></div>';
            const previewGrid = document.getElementById('preview-grid');

            Array.from(this.files).forEach((file) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const col = document.createElement('div');
                        col.className = 'col-md-3';
                        col.innerHTML = `
                            <div class="card">
                                <img src="${e.target.result}" class="card-img-top" style="height: 100px; object-fit: cover;">
                                <div class="card-body p-2">
                                    <small class="text-truncate d-block">${file.name}</small>
                                </div>
                            </div>
                        `;
                        previewGrid.appendChild(col);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
});
</script>
@endsection
