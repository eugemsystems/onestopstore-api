@extends('admin.layout')

@section('title', 'Add New Product - Admin Panel')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .sidebar-nav {
        position: sticky;
        top: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 1.5rem;
    }

    .sidebar-nav .nav-link {
        color: #495057;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
        display: flex;
        align-items: center;
        font-weight: 500;
    }

    .sidebar-nav .nav-link:hover {
        background: rgba(102, 126, 234, 0.08);
        color: #667eea;
        border-left-color: #667eea;
        transform: translateX(5px);
    }

    .sidebar-nav .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-left-color: #fbbf24;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .sidebar-nav .nav-link i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .section-content {
        scroll-margin-top: 100px;
    }

    .image-preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 15px;
    }

    .image-preview {
        position: relative;
        width: 120px;
        height: 120px;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #e0e0e0;
    }

    .image-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-preview .remove-image {
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
        opacity: 0;
        transition: opacity 0.3s;
    }

    .image-preview:hover .remove-image {
        opacity: 1;
    }

    .upload-area {
        border: 2px dashed #ccc;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-area:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
    }

    .upload-area.dragover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.1);
    }

    .ck-editor__editable {
        min-height: 300px;
    }

    /* Sticky Save Button */
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
        from {
            transform: translateX(100px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .sticky-save-bar .btn {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 12px 24px;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<!-- Sticky Save Buttons (Always Visible) -->
<div class="sticky-save-bar">
    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">
        <i class="bi bi-x-circle me-1"></i> Cancel
    </a>
    <button type="submit" form="productForm" class="btn btn-primary">
        <i class="bi bi-check-circle me-1"></i> Create Product
    </button>
</div>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-plus-circle me-2"></i>Add New Product</h2>
        <div>
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i> Back to Products
            </a>
            <button type="submit" form="productForm" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> Create Product
            </button>
        </div>
    </div>

    <form action="{{ route('admin.products.store') }}" method="POST" id="productForm" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <!-- Left Sidebar Navigation -->
            <div class="col-lg-2">
                <nav class="sidebar-nav">
                    <div class="nav flex-column">
                        <a class="nav-link active" href="#basic-info" data-section="basic-info">
                            <i class="bi bi-info-circle"></i> Basic Info
                        </a>
                        <a class="nav-link" href="#images" data-section="images">
                            <i class="bi bi-images"></i> Images
                        </a>
                        <a class="nav-link" href="#pricing" data-section="pricing">
                            <i class="bi bi-cash-stack"></i> Pricing
                        </a>
                        <a class="nav-link" href="#inventory" data-section="inventory">
                            <i class="bi bi-box-seam"></i> Inventory
                        </a>
                        <a class="nav-link" href="#shipping" data-section="shipping">
                            <i class="bi bi-truck"></i> Shipping
                        </a>
                        <a class="nav-link" href="#taxonomy" data-section="taxonomy">
                            <i class="bi bi-tags"></i> Taxonomy
                        </a>
                        <a class="nav-link" href="#variations" data-section="variations">
                            <i class="bi bi-sliders"></i> Variations
                        </a>
                        <a class="nav-link" href="#seo" data-section="seo">
                            <i class="bi bi-search"></i> SEO
                        </a>
                        <a class="nav-link" href="#settings" data-section="settings">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </div>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10">
                <!-- Basic Information -->
                <div id="basic-info" class="section-content mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="sku" class="form-label">SKU (Stock Keeping Unit)</label>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                           id="sku" name="sku" value="{{ old('sku') }}">
                                    @error('sku')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" class="form-control" readonly placeholder="Auto-generated from product name">
                                    <small class="text-muted">Auto-generated from product name</small>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="short_description" class="form-label">Short Description</label>
                                    <textarea class="form-control @error('short_description') is-invalid @enderror"
                                              id="short_description" name="short_description" rows="3">{{ old('short_description') }}</textarea>
                                    @error('short_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">Full Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                              id="description" name="description">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="type" class="form-label">Product Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('type') is-invalid @enderror"
                                            id="type" name="type" required>
                                        <option value="simple" {{ old('type') === 'simple' ? 'selected' : '' }}>Simple Product</option>
                                        <option value="classified" {{ old('type') === 'classified' ? 'selected' : '' }}>Classified Product</option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="unit" class="form-label">Unit</label>
                                    <input type="text" class="form-control @error('unit') is-invalid @enderror"
                                           id="unit" name="unit" value="{{ old('unit') }}"
                                           placeholder="e.g., kg, pcs, box, liter">
                                    @error('unit')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Images Section -->
                <div id="images" class="section-content mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-images me-2"></i>Product Images</h5>
                        </div>
                        <div class="card-body">
                            <!-- Product Thumbnail -->
                            <div class="mb-4">
                                <label class="form-label">Product Thumbnail <span class="text-danger">*</span></label>
                                <div class="upload-area" id="thumbnail-upload-area">
                                    <i class="bi bi-cloud-upload" style="font-size: 3rem; color: #667eea;"></i>
                                    <p class="mb-2">Click to upload or drag and drop</p>
                                    <small class="text-muted">PNG, JPG, WEBP up to 5MB</small>
                                    <input type="file" id="product_thumbnail" name="product_thumbnail"
                                           accept="image/*" style="display: none;">
                                </div>
                                <div class="image-preview-container mt-3" id="thumbnail-preview"></div>
                            </div>

                            <!-- Product Gallery -->
                            <div class="mb-4">
                                <label class="form-label">Product Gallery (Multiple Images)</label>
                                <div class="upload-area" id="gallery-upload-area">
                                    <i class="bi bi-images" style="font-size: 3rem; color: #667eea;"></i>
                                    <p class="mb-2">Click to upload or drag and drop multiple images</p>
                                    <small class="text-muted">PNG, JPG, WEBP up to 5MB each</small>
                                    <input type="file" id="product_galleries" name="product_galleries[]"
                                           accept="image/*" multiple style="display: none;">
                                </div>
                                <div class="image-preview-container mt-3" id="gallery-preview"></div>
                            </div>

                            <!-- Meta Image (SEO) -->
                            <div class="mb-4">
                                <label class="form-label">Meta Image (SEO/Social Sharing)</label>
                                <div class="upload-area" id="meta-image-upload-area">
                                    <i class="bi bi-share" style="font-size: 3rem; color: #667eea;"></i>
                                    <p class="mb-2">Click to upload or drag and drop</p>
                                    <small class="text-muted">PNG, JPG, WEBP up to 5MB</small>
                                    <input type="file" id="product_meta_image" name="product_meta_image"
                                           accept="image/*" style="display: none;">
                                </div>
                                <div class="image-preview-container mt-3" id="meta-image-preview"></div>
                            </div>

                            <!-- Size Chart Image -->
                            <div class="mb-3">
                                <label class="form-label">Size Chart Image</label>
                                <div class="upload-area" id="size-chart-upload-area">
                                    <i class="bi bi-rulers" style="font-size: 3rem; color: #667eea;"></i>
                                    <p class="mb-2">Click to upload size chart</p>
                                    <small class="text-muted">PNG, JPG, WEBP up to 5MB</small>
                                    <input type="file" id="size_chart_image" name="size_chart_image"
                                           accept="image/*" style="display: none;">
                                </div>
                                <div class="image-preview-container mt-3" id="size-chart-preview"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing Section -->
                <div id="pricing" class="section-content mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Pricing</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="price" class="form-label">Regular Price (USD) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror"
                                           id="price" name="price" value="{{ old('price') }}" required>
                                    @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="sale_price" class="form-label">Sale Price (USD)</label>
                                    <input type="number" step="0.01" class="form-control @error('sale_price') is-invalid @enderror"
                                           id="sale_price" name="sale_price" value="{{ old('sale_price') }}">
                                    @error('sale_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="discount" class="form-label">Discount (%)</label>
                                    <input type="number" step="0.01" class="form-control @error('discount') is-invalid @enderror"
                                           id="discount" name="discount" value="{{ old('discount') }}">
                                    @error('discount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="tax_id" class="form-label">Tax Class</label>
                                    <select class="form-select @error('tax_id') is-invalid @enderror"
                                            id="tax_id" name="tax_id">
                                        <option value="">No Tax</option>
                                        @foreach($taxes as $tax)
                                            <option value="{{ $tax->id }}" {{ old('tax_id') == $tax->id ? 'selected' : '' }}>
                                                {{ $tax->name }} ({{ $tax->rate }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tax_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="form-check mt-4">
                                        <input type="hidden" name="is_sale_enable" value="0">
                                        <input type="checkbox" class="form-check-input" id="is_sale_enable"
                                               name="is_sale_enable" value="1" {{ old('is_sale_enable') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_sale_enable">
                                            <i class="bi bi-tag-fill text-danger"></i> Enable Sale Price
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="sale_starts_at" class="form-label">Sale Start Date</label>
                                    <input type="date" class="form-control @error('sale_starts_at') is-invalid @enderror"
                                           id="sale_starts_at" name="sale_starts_at" value="{{ old('sale_starts_at') }}">
                                    @error('sale_starts_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="sale_expired_at" class="form-label">Sale End Date</label>
                                    <input type="date" class="form-control @error('sale_expired_at') is-invalid @enderror"
                                           id="sale_expired_at" name="sale_expired_at" value="{{ old('sale_expired_at') }}">
                                    @error('sale_expired_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Section -->
                <div id="inventory" class="section-content mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Inventory Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('quantity') is-invalid @enderror"
                                           id="quantity" name="quantity" value="{{ old('quantity', 0) }}" required>
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="stock_status" class="form-label">Stock Status <span class="text-danger">*</span></label>
                                    <select class="form-select @error('stock_status') is-invalid @enderror"
                                            id="stock_status" name="stock_status" required>
                                        <option value="in_stock" {{ old('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                        <option value="out_of_stock" {{ old('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                    </select>
                                    @error('stock_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="weight" class="form-label">Weight (kg)</label>
                                    <input type="number" step="0.01" class="form-control @error('weight') is-invalid @enderror"
                                           id="weight" name="weight" value="{{ old('weight') }}">
                                    @error('weight')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Section -->
                <div id="shipping" class="section-content mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Shipping Options</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-check">
                                        <input type="hidden" name="is_free_shipping" value="0">
                                        <input type="checkbox" class="form-check-input" id="is_free_shipping"
                                               name="is_free_shipping" value="1" {{ old('is_free_shipping') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_free_shipping">
                                            <i class="bi bi-truck text-success"></i> Free Shipping
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="shipping_days" class="form-label">Standard Shipping Days</label>
                                    <input type="text" class="form-control @error('shipping_days') is-invalid @enderror"
                                           id="shipping_days" name="shipping_days" value="{{ old('shipping_days') }}"
                                           placeholder="e.g., 3-5">
                                    @error('shipping_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="estimated_delivery_text" class="form-label">Estimated Delivery Text</label>
                                    <input type="text" class="form-control @error('estimated_delivery_text') is-invalid @enderror"
                                           id="estimated_delivery_text" name="estimated_delivery_text"
                                           value="{{ old('estimated_delivery_text') }}"
                                           placeholder="e.g., Delivery in 3-5 business days">
                                    @error('estimated_delivery_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <div class="form-check">
                                        <input type="hidden" name="has_expedited_shipping" value="0">
                                        <input type="checkbox" class="form-check-input" id="has_expedited_shipping"
                                               name="has_expedited_shipping" value="1" {{ old('has_expedited_shipping') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="has_expedited_shipping">
                                            <i class="bi bi-lightning-fill text-warning"></i> Offer Expedited Shipping
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="standard_shipping_days" class="form-label">Standard Shipping Days</label>
                                    <input type="text" class="form-control @error('standard_shipping_days') is-invalid @enderror"
                                           id="standard_shipping_days" name="standard_shipping_days"
                                           value="{{ old('standard_shipping_days') }}">
                                    @error('standard_shipping_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="expedited_shipping_days" class="form-label">Expedited Shipping Days</label>
                                    <input type="text" class="form-control @error('expedited_shipping_days') is-invalid @enderror"
                                           id="expedited_shipping_days" name="expedited_shipping_days"
                                           value="{{ old('expedited_shipping_days') }}">
                                    @error('expedited_shipping_days')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="standard_shipping_price" class="form-label">Standard Shipping Price (USD)</label>
                                    <input type="number" step="0.01" class="form-control @error('standard_shipping_price') is-invalid @enderror"
                                           id="standard_shipping_price" name="standard_shipping_price"
                                           value="{{ old('standard_shipping_price') }}">
                                    @error('standard_shipping_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="expedited_shipping_price" class="form-label">Expedited Shipping Price (USD)</label>
                                    <input type="number" step="0.01" class="form-control @error('expedited_shipping_price') is-invalid @enderror"
                                           id="expedited_shipping_price" name="expedited_shipping_price"
                                           value="{{ old('expedited_shipping_price') }}">
                                    @error('expedited_shipping_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Taxonomy Section -->
                <div id="taxonomy" class="section-content mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-tags me-2"></i>Categories & Tags</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Collapsible Categories -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Categories</label>
                                    <div class="categories-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px;">
                                        @foreach($categories->whereNull('parent_id') as $category)
                                            @php
                                                $hasChildren = $category->subcategories && $category->subcategories->count() > 0;
                                                $categorySelected = in_array($category->id, old('categories', []));
                                            @endphp
                                            <div class="category-parent mb-2">
                                                <div class="d-flex align-items-center">
                                                    @if($hasChildren)
                                                        <button type="button" class="btn btn-sm btn-link p-0 me-2 category-toggle"
                                                                data-category-id="{{ $category->id }}"
                                                                style="text-decoration: none; color: #667eea;">
                                                            <i class="bi bi-chevron-right"></i>
                                                        </button>
                                                    @else
                                                        <span style="width: 24px; display: inline-block;"></span>
                                                    @endif
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input"
                                                               id="category_{{ $category->id }}"
                                                               name="categories[]"
                                                               value="{{ $category->id }}"
                                                               {{ $categorySelected ? 'checked' : '' }}>
                                                        <label class="form-check-label fw-bold" for="category_{{ $category->id }}">
                                                            {{ $category->name }}
                                                        </label>
                                                    </div>
                                                </div>

                                                @if($hasChildren)
                                                    <div class="category-children ms-4" style="display: none;" id="children-{{ $category->id }}">
                                                        @foreach($category->subcategories as $subcategory)
                                                            @php
                                                                $hasSubChildren = $subcategory->subcategories && $subcategory->subcategories->count() > 0;
                                                                $subcategorySelected = in_array($subcategory->id, old('categories', []));
                                                            @endphp
                                                            <div class="category-child mt-2">
                                                                <div class="d-flex align-items-center">
                                                                    @if($hasSubChildren)
                                                                        <button type="button" class="btn btn-sm btn-link p-0 me-2 category-toggle"
                                                                                data-category-id="{{ $subcategory->id }}"
                                                                                style="text-decoration: none; color: #667eea;">
                                                                            <i class="bi bi-chevron-right"></i>
                                                                        </button>
                                                                    @else
                                                                        <span style="width: 24px; display: inline-block;"></span>
                                                                    @endif
                                                                    <div class="form-check">
                                                                        <input type="checkbox" class="form-check-input"
                                                                               id="category_{{ $subcategory->id }}"
                                                                               name="categories[]"
                                                                               value="{{ $subcategory->id }}"
                                                                               {{ $subcategorySelected ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="category_{{ $subcategory->id }}">
                                                                            {{ $subcategory->name }}
                                                                        </label>
                                                                    </div>
                                                                </div>

                                                                @if($hasSubChildren)
                                                                    <div class="category-children ms-4" style="display: none;" id="children-{{ $subcategory->id }}">
                                                                        @foreach($subcategory->subcategories as $subsubcategory)
                                                                            @php
                                                                                $subsubcategorySelected = in_array($subsubcategory->id, old('categories', []));
                                                                            @endphp
                                                                            <div class="form-check mt-2">
                                                                                <input type="checkbox" class="form-check-input"
                                                                                       id="category_{{ $subsubcategory->id }}"
                                                                                       name="categories[]"
                                                                                       value="{{ $subsubcategory->id }}"
                                                                                       {{ $subsubcategorySelected ? 'checked' : '' }}>
                                                                                <label class="form-check-label text-muted" for="category_{{ $subsubcategory->id }}">
                                                                                    {{ $subsubcategory->name }}
                                                                                </label>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Select2 Tags -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tags</label>
                                    <select class="form-select" id="tags-select" name="tags[]" multiple="multiple" style="width: 100%;">
                                        @foreach($tags as $tag)
                                            <option value="{{ $tag->id }}"
                                                {{ in_array($tag->id, old('tags', [])) ? 'selected' : '' }}>
                                                {{ $tag->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Search and select multiple tags</small>
                                </div>

                                <!-- Brand Selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="brand_id" class="form-label">Brand</label>
                                    <select class="form-select @error('brand_id') is-invalid @enderror"
                                            id="brand_id" name="brand_id">
                                        <option value="">No Brand</option>
                                        @if(old('brand_id'))
                                            {{-- If there's an old value, we need to preserve it --}}
                                            <option value="{{ old('brand_id') }}" selected>Loading...</option>
                                        @endif
                                    </select>
                                    <small class="text-muted">Search and select a brand (type to search)</small>
                                    @error('brand_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Variations Section -->
                <div id="variations" class="section-content mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-sliders me-2"></i>Product Variations</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Note:</strong> Product variations can only be added after creating the product.
                                Please save the product first, then you can add variations from the edit page.
                            </div>
                            <p class="text-muted">
                                Variations allow you to create different versions of your product with specific attributes like size, color, etc.
                                Each variation can have its own price, SKU, and stock quantity.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- SEO Section -->
                <div id="seo" class="section-content mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-search me-2"></i>SEO & Meta Data</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="meta_title" class="form-label">Meta Title</label>
                                    <input type="text" class="form-control @error('meta_title') is-invalid @enderror"
                                           id="meta_title" name="meta_title" value="{{ old('meta_title') }}"
                                           maxlength="60">
                                    <small class="text-muted">Recommended: 50-60 characters</small>
                                    @error('meta_title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="meta_description" class="form-label">Meta Description</label>
                                    <textarea class="form-control @error('meta_description') is-invalid @enderror"
                                              id="meta_description" name="meta_description" rows="3" maxlength="160">{{ old('meta_description') }}</textarea>
                                    <small class="text-muted">Recommended: 150-160 characters</small>
                                    @error('meta_description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Section -->
                <div id="settings" class="section-content mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Product Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @can('product.edit')
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select @error('status') is-invalid @enderror"
                                            id="status" name="status" required>
                                        <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @else
                                <!-- Users without product.edit: Products are inactive by default -->
                                <input type="hidden" name="status" value="0">
                                @endcan

                                <div class="col-md-12 mb-3">
                                    <h6 class="mb-3">Product Features</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="is_featured" value="0">
                                                <input type="checkbox" class="form-check-input" id="is_featured"
                                                       name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_featured">
                                                    <i class="bi bi-star-fill text-warning"></i> Featured Product
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="is_trending" value="0">
                                                <input type="checkbox" class="form-check-input" id="is_trending"
                                                       name="is_trending" value="1" {{ old('is_trending') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_trending">
                                                    <i class="bi bi-graph-up-arrow text-success"></i> Trending Product
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="is_return" value="0">
                                                <input type="checkbox" class="form-check-input" id="is_return"
                                                       name="is_return" value="1" {{ old('is_return') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_return">
                                                    <i class="bi bi-arrow-return-left text-info"></i> Allow Returns
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="is_cod" value="0">
                                                <input type="checkbox" class="form-check-input" id="is_cod"
                                                       name="is_cod" value="1" {{ old('is_cod') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_cod">
                                                    <i class="bi bi-cash-coin text-success"></i> Cash on Delivery
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="safe_checkout" value="0">
                                                <input type="checkbox" class="form-check-input" id="safe_checkout"
                                                       name="safe_checkout" value="1" {{ old('safe_checkout', 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="safe_checkout">
                                                    <i class="bi bi-shield-check text-success"></i> Safe Checkout
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="secure_checkout" value="0">
                                                <input type="checkbox" class="form-check-input" id="secure_checkout"
                                                       name="secure_checkout" value="1" {{ old('secure_checkout', 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="secure_checkout">
                                                    <i class="bi bi-lock-fill text-primary"></i> Secure Checkout
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="social_share" value="0">
                                                <input type="checkbox" class="form-check-input" id="social_share"
                                                       name="social_share" value="1" {{ old('social_share', 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="social_share">
                                                    <i class="bi bi-share-fill text-info"></i> Enable Social Sharing
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="encourage_order" value="0">
                                                <input type="checkbox" class="form-check-input" id="encourage_order"
                                                       name="encourage_order" value="1" {{ old('encourage_order', 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="encourage_order">
                                                    <i class="bi bi-cart-plus text-success"></i> Show "X people bought this"
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="encourage_view" value="0">
                                                <input type="checkbox" class="form-check-input" id="encourage_view"
                                                       name="encourage_view" value="1" {{ old('encourage_view', 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="encourage_view">
                                                    <i class="bi bi-eye-fill text-primary"></i> Show "X people viewing"
                                                </label>
                                            </div>
                                        </div>
                                        @can('product.edit')
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="is_approved" value="0">
                                                <input type="checkbox" class="form-check-input" id="is_approved"
                                                       name="is_approved" value="1" {{ old('is_approved', 1) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="is_approved">
                                                    <i class="bi bi-check-circle-fill text-success"></i> Approved
                                                </label>
                                            </div>
                                        </div>
                                        @else
                                        <!-- Users without product.edit: Products require approval -->
                                        <input type="hidden" name="is_approved" value="0">
                                        @endcan
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="is_gift_card" value="0">
                                                <input type="checkbox" class="form-check-input" id="is_gift_card"
                                                       name="is_gift_card" value="1" {{ old('is_gift_card') ? 'checked' : '' }}
                                                       onchange="toggleVoucherValidity()">
                                                <label class="form-check-label" for="is_gift_card">
                                                    <i class="bi bi-gift-fill text-danger"></i> Gift Card Product
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="zambia_only" value="0">
                                                <input type="checkbox" class="form-check-input" id="zambia_only"
                                                       name="zambia_only" value="1" {{ old('zambia_only') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="zambia_only">
                                                    <i class="bi bi-globe-americas text-success"></i> Zambia Only (ZMW)
                                                </label>
                                                <br><small class="text-muted">Product only visible when currency is Kwacha</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="zimbabwe_only" value="0">
                                                <input type="checkbox" class="form-check-input" id="zimbabwe_only"
                                                       name="zimbabwe_only" value="1" {{ old('zimbabwe_only') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="zimbabwe_only">
                                                    <i class="bi bi-globe-americas text-warning"></i> Zimbabwe Only (USD)
                                                </label>
                                                <br><small class="text-muted">Product only visible when currency is USD</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input type="hidden" name="sa_only" value="0">
                                                <input type="checkbox" class="form-check-input" id="sa_only"
                                                       name="sa_only" value="1" {{ old('sa_only') ? 'checked' : '' }}>
                                                <label class="form-check-label" for="sa_only">
                                                    <i class="bi bi-globe-americas text-primary"></i> South Africa Only (ZAR)
                                                </label>
                                                <br><small class="text-muted">Product only visible when currency is Rand</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gift Card Voucher Validity -->
                                <div class="col-md-12 mb-3" id="voucherValiditySection" style="display: {{ old('is_gift_card') ? 'block' : 'none' }};">
                                    <h6 class="mb-3"><i class="bi bi-calendar-check text-danger"></i> Gift Card Settings</h6>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Gift Card Product:</strong> When customers purchase this product, they will receive a unique voucher code that can be redeemed for wallet credit.
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="voucher_validity_days" class="form-label">Voucher Validity (Days)</label>
                                            <input type="number" class="form-control @error('voucher_validity_days') is-invalid @enderror"
                                                   id="voucher_validity_days" name="voucher_validity_days"
                                                   value="{{ old('voucher_validity_days', 365) }}"
                                                   min="1" placeholder="365">
                                            <small class="text-muted">Number of days the voucher remains valid after purchase. Leave blank for no expiration.</small>
                                            @error('voucher_validity_days')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card bg-light border-0 h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title"><i class="bi bi-lightbulb text-warning"></i> How it works</h6>
                                                    <ul class="small mb-0">
                                                        <li>Customer purchases this gift card product</li>
                                                        <li>Unique voucher code is auto-generated</li>
                                                        <li>Code can be redeemed for wallet credit</li>
                                                        <li>Credit can only be used for purchases (non-cashable)</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <h6 class="mb-3">External Product</h6>
                                    <div class="form-check mb-3">
                                        <input type="hidden" name="is_external" value="0">
                                        <input type="checkbox" class="form-check-input" id="is_external"
                                               name="is_external" value="1" {{ old('is_external') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_external">
                                            <i class="bi bi-box-arrow-up-right text-warning"></i> This is an external/affiliate product
                                        </label>
                                    </div>

                                    <div id="external-fields" style="display: {{ old('is_external') ? 'block' : 'none' }};">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="external_url" class="form-label">External URL</label>
                                                <input type="url" class="form-control @error('external_url') is-invalid @enderror"
                                                       id="external_url" name="external_url"
                                                       value="{{ old('external_url') }}"
                                                       placeholder="https://example.com/product">
                                                @error('external_url')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="external_button_text" class="form-label">Button Text</label>
                                                <input type="text" class="form-control @error('external_button_text') is-invalid @enderror"
                                                       id="external_button_text" name="external_button_text"
                                                       value="{{ old('external_button_text') }}"
                                                       placeholder="Buy on Amazon">
                                                @error('external_button_text')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="return_policy_text" class="form-label">Return Policy Text</label>
                                    <textarea class="form-control @error('return_policy_text') is-invalid @enderror"
                                              id="return_policy_text" name="return_policy_text" rows="3">{{ old('return_policy_text') }}</textarea>
                                    @error('return_policy_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body text-end">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary me-2">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Create Product
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize CKEditor
    let editor;
    ClassicEditor
        .create(document.querySelector('#description'), {
            toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo'],
            height: '400px'
        })
        .then(newEditor => {
            editor = newEditor;
        })
        .catch(error => {
            console.error(error);
        });

    // Sidebar Navigation
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    const sections = document.querySelectorAll('.section-content');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));

            // Add active class to clicked link
            this.classList.add('active');

            // Scroll to section
            const targetId = this.getAttribute('data-section');
            const targetSection = document.getElementById(targetId);
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Highlight active section on scroll
    window.addEventListener('scroll', function() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (pageYOffset >= (sectionTop - 150)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('data-section') === current) {
                link.classList.add('active');
            }
        });
    });

    // Image Upload Handling
    function setupImageUpload(uploadAreaId, inputId, previewId, multiple = false) {
        const uploadArea = document.getElementById(uploadAreaId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);

        uploadArea.addEventListener('click', () => input.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            input.files = files;
            handleFiles(files, preview, multiple);
        });

        input.addEventListener('change', (e) => {
            handleFiles(e.target.files, preview, multiple);
        });
    }

    function handleFiles(files, preview, multiple) {
        if (!multiple) {
            preview.innerHTML = '';
        }

        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'image-preview';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="remove-image" onclick="this.parentElement.remove()">
                            <i class="bi bi-x"></i>
                        </button>
                    `;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Setup all image uploads
    setupImageUpload('thumbnail-upload-area', 'product_thumbnail', 'thumbnail-preview');
    setupImageUpload('gallery-upload-area', 'product_galleries', 'gallery-preview', true);
    setupImageUpload('meta-image-upload-area', 'product_meta_image', 'meta-image-preview');
    setupImageUpload('size-chart-upload-area', 'size_chart_image', 'size-chart-preview');

    // External product toggle
    document.getElementById('is_external').addEventListener('change', function() {
        const externalFields = document.getElementById('external-fields');
        externalFields.style.display = this.checked ? 'block' : 'none';
    });

    // Auto-populate shipping days when expedited shipping is enabled
    document.getElementById('has_expedited_shipping').addEventListener('change', function() {
        const standardDays = document.getElementById('standard_shipping_days');
        const expeditedDays = document.getElementById('expedited_shipping_days');
        const standardPrice = document.getElementById('standard_shipping_price');
        const expeditedPrice = document.getElementById('expedited_shipping_price');

        if (this.checked) {
            // Set values when checked
            standardDays.value = '7-14';
            expeditedDays.value = '3-4';
        } else {
            // Clear values when unchecked
            standardDays.value = '';
            expeditedDays.value = '';
            standardPrice.value = '0';
            expeditedPrice.value = '0';
        }
    });

    // Initialize Select2 for tags
    if (typeof $.fn.select2 !== 'undefined') {
        $('#tags-select').select2({
            theme: 'bootstrap-5',
            placeholder: 'Search and select tags...',
            allowClear: true,
            closeOnSelect: false,
            width: '100%'
        });

        // Initialize Select2 for brands with AJAX search (prevents loading 18k+ brands)
        $('#brand_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Search for a brand...',
            allowClear: true,
            width: '100%',
            ajax: {
                url: '{{ route("admin.products.brands.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results,
                        pagination: {
                            more: data.pagination.more
                        }
                    };
                },
                cache: true
            },
            minimumInputLength: 0 // Allow browsing without typing
        });
    } else {
        console.error('Select2 is not loaded!');
    }

    // Prevent double form submission - MUST handle both form submit and button clicks
    let isSubmitting = false;

    // Get all submit buttons (including those with form="productForm" attribute)
    const submitButtons = document.querySelectorAll('button[type="submit"][form="productForm"], #productForm button[type="submit"]');
    const productForm = document.getElementById('productForm');

    // Validation function
    function validateForm() {
        const requiredFields = ['name', 'price', 'quantity', 'stock_status'];
        let isValid = true;

        requiredFields.forEach(field => {
            const input = document.getElementById(field);
            if (!input.value) {
                isValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            showWarning('Required Fields', 'Please fill in all required fields.');
        }

        return isValid;
    }

    // Disable all submit buttons
    function disableSubmitButtons() {
        submitButtons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Creating...';
        });
    }

    // Re-enable all submit buttons (for validation failures)
    function enableSubmitButtons() {
        submitButtons.forEach(btn => {
            btn.disabled = false;
            // Restore original text
            if (btn.querySelector('.bi-hourglass-split')) {
                btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Create Product';
            }
        });
    }

    // Handle form submission
    productForm.addEventListener('submit', function(e) {
        // If already submitting, prevent
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }

        // Validate form
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }

        // Mark as submitting
        isSubmitting = true;
        disableSubmitButtons();
    });

    // Add click event to all submit buttons to catch early clicks
    submitButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    });

    // Category toggle functionality
    document.querySelectorAll('.category-toggle').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const categoryId = this.getAttribute('data-category-id');
            const childrenContainer = document.getElementById('children-' + categoryId);
            const icon = this.querySelector('i');

            if (!childrenContainer) {
                return;
            }

            // Check computed style instead of inline style
            const isHidden = window.getComputedStyle(childrenContainer).display === 'none';

            if (isHidden) {
                childrenContainer.style.display = 'block';
                icon.classList.remove('bi-chevron-right');
                icon.classList.add('bi-chevron-down');
            } else {
                childrenContainer.style.display = 'none';
                icon.classList.remove('bi-chevron-down');
                icon.classList.add('bi-chevron-right');
            }
        });
    });

    // Gift Card toggle functionality
    function toggleVoucherValidity() {
        const checkbox = document.getElementById('is_gift_card');
        const section = document.getElementById('voucherValiditySection');
        section.style.display = checkbox.checked ? 'block' : 'none';
    }

    // Initialize gift card toggle on page load
    toggleVoucherValidity();

    // Show session flash messages with SweetAlert
    @if(session('success'))
        if (typeof showSuccess === 'function') {
            showSuccess('Success!', '{{ session("success") }}');
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ addslashes(str_replace(["\r\n", "\r", "\n"], " ", session("success"))) }}',
                confirmButtonColor: '#667eea',
                timer: 3000,
                timerProgressBar: true
            });
        }
    @endif

    @if(session('error'))
        if (typeof showError === 'function') {
            showError('Error!', '{{ addslashes(str_replace(["\r\n", "\r", "\n"], " ", session("error"))) }}');
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '{{ addslashes(str_replace(["\r\n", "\r", "\n"], " ", session("error"))) }}',
                confirmButtonColor: '#667eea'
            });
        }
    @endif
});
</script>
@endpush


