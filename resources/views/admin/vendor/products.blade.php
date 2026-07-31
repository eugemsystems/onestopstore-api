@extends('admin.layout')

@section('title', 'My Products')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-box-seam"></i> My Products
                    </h2>
                    <p class="text-muted">Manage your product catalog</p>
                </div>
                <div>
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Product
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Total Products</h6>
                    <h2 class="mb-0">{{ number_format($stats['total']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Active & Approved</h6>
                    <h2 class="mb-0">{{ number_format($stats['active']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Pending Approval</h6>
                    <h2 class="mb-0">{{ number_format($stats['pending_approval']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="card-title text-white-50">Inactive</h6>
                    <h2 class="mb-0">{{ number_format($stats['inactive']) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.vendor.products') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Approval Status</label>
                        <select name="is_approved" class="form-select">
                            <option value="">All</option>
                            <option value="1" {{ request('is_approved') === '1' ? 'selected' : '' }}>Approved</option>
                            <option value="0" {{ request('is_approved') === '0' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Product name or SKU..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('admin.vendor.products') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Approval</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        <tr>
                            <td>
                                @if($product->product_thumbnail)
                                    <img src="{{ $product->product_thumbnail->original_url }}" alt="{{ $product->name }}" style="width: 50px; height: 50px; object-fit: cover;" class="rounded">
                                @else
                                    <div class="bg-light rounded" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $product->name }}</strong>
                            </td>
                            <td><code>{{ $product->sku }}</code></td>
                            <td><strong>${{ number_format($product->price, 2) }}</strong></td>
                            <td>
                                @if($product->quantity > 10)
                                    <span class="badge bg-success">{{ $product->quantity }}</span>
                                @elseif($product->quantity > 0)
                                    <span class="badge bg-warning">{{ $product->quantity }}</span>
                                @else
                                    <span class="badge bg-danger">Out of Stock</span>
                                @endif
                            </td>
                            <td>
                                @if($product->status)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if($product->is_approved)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Approved
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-hourglass-split"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="{{ url('/en/products/' . $product->slug) }}" target="_blank" class="btn btn-outline-secondary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-2 mb-3">No products found</p>
                                <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add Your First Product
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

