@extends('admin.layout')

@section('title', 'Blogs - Admin Panel')
@section('page-title', 'Blogs')

@push('styles')
<style>
    .blog-thumbnail {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e0e0e0;
    }

    .blog-thumbnail-placeholder {
        width: 50px;
        height: 50px;
        border-radius: 6px;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #aaa;
        font-size: 1.2rem;
    }

    .status-badge {
        padding: 0.25em 0.65em;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-badge.active {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.draft {
        background: #fff3cd;
        color: #856404;
    }

    .featured-star {
        color: #ffc107;
        font-size: 1.1rem;
    }

    .featured-star.inactive {
        color: #ddd;
    }

    .filter-bar {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .blog-table {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .blog-table table {
        margin-bottom: 0;
    }

    .blog-table th {
        background: #f8f9fa;
        border-bottom: 2px solid #e9ecef;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #495057;
        padding: 0.85rem 1rem;
    }

    .blog-table td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }

    .blog-table tr:hover {
        background: #f8f9ff;
    }

    .action-btns .btn {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
    }

    .blog-title-cell {
        max-width: 300px;
    }

    .blog-title-cell a {
        color: #062a6a;
        text-decoration: none;
        font-weight: 600;
    }

    .blog-title-cell a:hover {
        color: #667eea;
    }

    .category-badges .badge {
        margin-right: 4px;
        margin-bottom: 2px;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .filter-bar .row > div {
            margin-bottom: 0.5rem;
        }

        .blog-table {
            overflow-x: auto;
        }

        .blog-table table {
            min-width: 700px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-journal-richtext me-2"></i>Blogs</h2>
        <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Create Blog
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <form method="GET" action="{{ route('admin.blogs.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by title..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('admin.blogs.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Blog Table -->
    <div class="blog-table">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60px;">Image</th>
                    <th>Title</th>
                    <th>Categories</th>
                    <th style="width: 90px;">Status</th>
                    <th style="width: 70px;">Featured</th>
                    <th style="width: 120px;">Created</th>
                    <th style="width: 160px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($blogs as $blog)
                    <tr>
                        <td>
                            @if($blog->blog_thumbnail && ($blog->blog_thumbnail->image_url || $blog->blog_thumbnail->original_url))
                                <img src="{{ $blog->blog_thumbnail->image_url ?? $blog->blog_thumbnail->original_url }}" alt="{{ $blog->title }}" class="blog-thumbnail">
                            @else
                                <div class="blog-thumbnail-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            @endif
                        </td>
                        <td class="blog-title-cell">
                            <a href="{{ route('admin.blogs.edit', $blog->id) }}">{{ Str::limit($blog->title, 60) }}</a>
                        </td>
                        <td>
                            <div class="category-badges">
                                @foreach($blog->categories as $cat)
                                    <span class="badge bg-secondary">{{ $cat->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <span class="status-badge {{ $blog->status ? 'active' : 'draft' }}">
                                {{ $blog->status ? 'Active' : 'Draft' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <i class="bi bi-star-fill featured-star {{ $blog->is_featured ? '' : 'inactive' }}"></i>
                        </td>
                        <td>
                            <small class="text-muted">{{ $blog->created_at->format('M d, Y') }}</small>
                        </td>
                        <td>
                            <div class="action-btns d-flex gap-1">
                                <a href="{{ route('admin.blogs.edit', $blog->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.blogs.toggle-status', $blog->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $blog->status ? 'warning' : 'success' }}" title="{{ $blog->status ? 'Set Draft' : 'Set Active' }}">
                                        <i class="bi bi-{{ $blog->status ? 'pause-circle' : 'check-circle' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.blogs.destroy', $blog->id) }}" method="POST" class="d-inline" data-swal-confirm="Are you sure you want to delete this blog?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-journal-x" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">No blogs found.</p>
                                <a href="{{ route('admin.blogs.create') }}" class="btn btn-primary btn-sm mt-3">
                                    <i class="bi bi-plus-circle me-1"></i> Create your first blog
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($blogs->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $blogs->links() }}
        </div>
    @endif
</div>
@endsection
