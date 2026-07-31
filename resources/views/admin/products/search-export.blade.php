@extends('admin.layout')
@section('title', 'Product Search & Export')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-search me-2" style="color:#0c79ba"></i>Product Search & Export</h4>
        <p class="text-muted mb-0 small">Search products by name, keywords or SKU and export results to Excel</p>
    </div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
</div>

{{-- Search & Filters --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.products.search-export') }}">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Search (Name, SKU, Keywords)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control"
                               placeholder="Type product name, SKU, or keyword..."
                               value="{{ request('search') }}" autofocus>
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Stock</label>
                    <select name="stock_status" class="form-select">
                        <option value="">All</option>
                        <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                        <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Region</label>
                    <select name="region" class="form-select">
                        <option value="">All Regions</option>
                        <option value="zambia_only" {{ request('region') === 'zambia_only' ? 'selected' : '' }}>Zambia Only</option>
                        <option value="zimbabwe_only" {{ request('region') === 'zimbabwe_only' ? 'selected' : '' }}>Zimbabwe Only</option>
                        <option value="sa_only" {{ request('region') === 'sa_only' ? 'selected' : '' }}>SA Only</option>
                        <option value="global" {{ request('region') === 'global' ? 'selected' : '' }}>Global</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label fw-semibold">Min $</label>
                    <input type="number" name="price_min" class="form-control" placeholder="0" step="0.01" value="{{ request('price_min') }}">
                </div>

                <div class="col-md-1">
                    <label class="form-label fw-semibold">Max $</label>
                    <input type="number" name="price_max" class="form-control" placeholder="999" step="0.01" value="{{ request('price_max') }}">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                    <a href="{{ route('admin.products.search-export') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> Clear
                    </a>

                    @if($hasSearch && $totalFound > 0)
                        <a href="{{ route('admin.products.export-excel', request()->query()) }}"
                           class="btn btn-success ms-2">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export {{ number_format($totalFound) }} Products to Excel
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Results --}}
@if($hasSearch)
    @if($products && $products->total() > 0)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">
                    <i class="bi bi-list-ul me-1"></i>
                    {{ number_format($totalFound) }} products found
                </span>
                <a href="{{ route('admin.products.export-excel', request()->query()) }}"
                   class="btn btn-sm btn-success">
                    <i class="bi bi-download me-1"></i> Export All to CSV
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th style="width:50px">#</th>
                                <th style="width:60px">Image</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Sale Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Region</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $product)
                            <tr>
                                <td class="text-muted">{{ $product->id }}</td>
                                <td>
                                    @if($product->product_thumbnail?->image_url)
                                        <img src="{{ $product->product_thumbnail->image_url }}" alt="" style="width:48px; height:48px; object-fit:contain; border-radius:6px; border:1px solid #eee;">
                                    @else
                                        <div style="width:48px; height:48px; border-radius:6px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#ccc; font-size:18px;">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div style="max-width:300px;">
                                        <a href="{{ route('admin.products.edit', $product->id) }}" class="text-decoration-none fw-semibold text-dark" style="font-size:.85rem;">
                                            {{ Str::limit($product->name, 60) }}
                                        </a>
                                        @if($product->brand)
                                            <div class="text-muted" style="font-size:.72rem;">{{ $product->brand->name }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td><code style="font-size:.75rem;">{{ $product->sku ?? '—' }}</code></td>
                                <td>${{ number_format($product->price ?? 0, 2) }}</td>
                                <td>
                                    @if($product->sale_price)
                                        <span class="text-success fw-bold">${{ number_format($product->sale_price, 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $product->stock_status === 'in_stock' ? 'bg-success' : 'bg-danger' }}" style="font-size:.7rem;">
                                        {{ $product->stock_status === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $product->status ? 'bg-primary' : 'bg-secondary' }}" style="font-size:.7rem;">
                                        {{ $product->status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    @if($product->zambia_only) <span class="badge bg-warning text-dark" style="font-size:.65rem;">ZMW</span> @endif
                                    @if($product->zimbabwe_only) <span class="badge bg-info" style="font-size:.65rem;">USD</span> @endif
                                    @if($product->sa_only) <span class="badge bg-primary" style="font-size:.65rem;">ZAR</span> @endif
                                    @if(!$product->zambia_only && !$product->zimbabwe_only && !$product->sa_only) <span class="text-muted" style="font-size:.7rem;">Global</span> @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-sm btn-outline-primary" style="font-size:.72rem; padding:2px 8px;">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ number_format($products->total()) }}
                </span>
                {{ $products->appends(request()->query())->links() }}
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-search" style="font-size:3rem; color:#ccc;"></i>
                <h5 class="mt-3 text-muted">No products found</h5>
                <p class="text-muted">Try a different search term or adjust your filters.</p>
            </div>
        </div>
    @endif
@else
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-box-seam" style="font-size:3rem; color:#ccc;"></i>
            <h5 class="mt-3 text-muted">Search for products</h5>
            <p class="text-muted">Enter a product name, SKU, or keyword above to find products and export them.</p>
        </div>
    </div>
@endif
@endsection
