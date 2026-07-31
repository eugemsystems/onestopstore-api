@extends('admin.layout')

@section('title', 'Home Pages Management')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-house-fill"></i> Home Pages Management</h2>
            <p class="text-muted mb-0">Manage homepage content sections for different themes</p>
        </div>
        @can('home-pages.create')
        <div>
            <a href="{{ route('admin.home-pages.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create New Home Page
            </a>
        </div>
        @endcan
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle"></i>
        <strong>About Home Pages:</strong> Each home page represents a different theme layout.
        The <code>slug</code> field identifies which theme the content belongs to.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- Home Pages List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Home Pages</h5>
        </div>
        <div class="card-body">
            @if($homePages->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Slug (Theme)</th>
                                <th>Content Size</th>
                                <th>Last Updated</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($homePages as $homePage)
                            <tr>
                                <td><strong>#{{ $homePage->id }}</strong></td>
                                <td>
                                    <span class="badge bg-primary">{{ $homePage->slug }}</span>
                                </td>
                                <td>
                                    @php
                                        $contentSize = strlen(json_encode($homePage->content));
                                        $sizeKB = round($contentSize / 1024, 2);
                                    @endphp
                                    <small class="text-muted">{{ $sizeKB }} KB</small>
                                </td>
                                <td>
                                    <small>{{ $homePage->updated_at->format('M d, Y H:i') }}</small>
                                </td>
                                <td>
                                    <small>{{ $homePage->created_at->format('M d, Y') }}</small>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        @can('home-pages.edit')
                                        <a href="{{ route('admin.home-pages.builder', $homePage->id) }}"
                                           class="btn btn-sm btn-success"
                                           title="Visual Builder">
                                            <i class="bi bi-grid-3x3"></i> Builder
                                        </a>
                                        <a href="{{ route('admin.home-pages.edit', $homePage->id) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="JSON Editor">
                                            <i class="bi bi-code-square"></i> JSON
                                        </a>
                                        @endcan
                                        @can('home-pages.delete')
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="deleteHomePage({{ $homePage->id }}, '{{ $homePage->slug }}')"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-3">No home pages found. Create your first one!</p>
                    <a href="{{ route('admin.home-pages.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Home Page
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Form (Hidden) -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
async function deleteHomePage(id, slug) {
    const _r = await Swal.fire({ title: 'Are you sure?', text: `Are you sure you want to delete the home page "${slug}"?\n\nThis action cannot be undone.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (_r.isConfirmed) {
        const form = document.getElementById('deleteForm');
        form.action = `/admin/home-pages/${id}`;
        form.submit();
    }
}
</script>
@endpush
r
