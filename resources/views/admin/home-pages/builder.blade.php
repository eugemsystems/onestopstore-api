`@extends('admin.layout')

@section('title', 'Edit Home Page - ' . $homePage->slug)

@push('styles')
<style>
    .nav-pills .nav-link {
        color: #6c757d;
        border-radius: 8px;
        padding: 10px 15px;
        margin-bottom: 5px;
    }
    .nav-pills .nav-link.active {
        background-color: #0d6efd;
        color: white;
    }
    .section-card {
        border-left: 4px solid #0d6efd;
    }
    .sticky-sidebar {
        position: sticky;
        top: 20px;
    }
    .product-search-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: none;
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    .product-search-modal.active {
        display: flex;
    }
    .product-search-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 800px;
        max-height: 80vh;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .product-search-item {
        cursor: pointer;
        padding: 10px;
        border: 2px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    .product-search-item:hover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }
    .product-search-item.selected {
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }
    .product-search-item img {
        width: 60px;
        height: 60px;
        object-fit: contain;
    }
    .selected-products-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    .selected-product-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 10px;
        background: #e7f1ff;
        border: 1px solid #0d6efd;
        border-radius: 4px;
    }
    .selected-product-badge img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        border-radius: 4px;
    }
    .category-checkbox {
        padding: 8px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        margin-bottom: 5px;
    }
    .category-checkbox:hover {
        background-color: #f8f9fa;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-grid-3x3"></i> Edit Home Page</h2>
            <p class="text-muted mb-0">Editing: <span class="badge bg-primary">{{ $homePage->slug }}</span></p>
        </div>
        <div>
            <a href="{{ route('admin.home-pages.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.home-pages.update-builder', $homePage->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <!-- Left Sidebar -->
            <div class="col-lg-3">
                <div class="card sticky-sidebar">
                    <div class="card-body">
                        <h6 class="mb-3">Sections</h6>
                        <ul class="nav nav-pills flex-column" id="sectionsTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="home-banner-tab" data-bs-toggle="pill" data-bs-target="#home-banner" type="button">
                                    <i class="bi bi-image"></i> Home Banner
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="featured-banners-tab" data-bs-toggle="pill" data-bs-target="#featured-banners" type="button">
                                    <i class="bi bi-images"></i> Featured Banners
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="main-content-tab" data-bs-toggle="pill" data-bs-target="#main-content" type="button">
                                    <i class="bi bi-layout-text-window"></i> Main Content
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="newsletter-tab" data-bs-toggle="pill" data-bs-target="#newsletter" type="button">
                                    <i class="bi bi-envelope"></i> Newsletter
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="popups-tab" data-bs-toggle="pill" data-bs-target="#popups" type="button">
                                    <i class="bi bi-window-fullscreen"></i> PopUps
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Content -->
            <div class="col-lg-9">
                <div class="tab-content">
                    <!-- Home Banner Tab -->
                    <div class="tab-pane fade show active" id="home-banner">
                        <div class="card section-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5><i class="bi bi-image"></i> Home Banner</h5>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="home_banner[status]" value="1"
                                               {{ data_get($homePage->content, 'home_banner.status') ? 'checked' : '' }}>
                                        <label class="form-check-label">Enable</label>
                                    </div>
                                </div>

                                <h6 class="mt-4">Main Banner</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Image URL</label>
                                        <input type="text" name="home_banner[main_banner][image_url]" class="form-control"
                                               value="{{ data_get($homePage->content, 'home_banner.main_banner.image_url') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Link Type</label>
                                        <select name="home_banner[main_banner][redirect_link][link_type]" class="form-select">
                                            <option value="collection" {{ data_get($homePage->content, 'home_banner.main_banner.redirect_link.link_type') == 'collection' ? 'selected' : '' }}>Collection</option>
                                            <option value="product" {{ data_get($homePage->content, 'home_banner.main_banner.redirect_link.link_type') == 'product' ? 'selected' : '' }}>Product</option>
                                            <option value="external_url" {{ data_get($homePage->content, 'home_banner.main_banner.redirect_link.link_type') == 'external_url' ? 'selected' : '' }}>External URL</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Link</label>
                                        <input type="text" name="home_banner[main_banner][redirect_link][link]" class="form-control"
                                               value="{{ data_get($homePage->content, 'home_banner.main_banner.redirect_link.link') }}">
                                    </div>
                                </div>

                                <h6 class="mt-4">Sub Banner 1</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Image URL</label>
                                        <input type="text" name="home_banner[sub_banner_1][image_url]" class="form-control"
                                               value="{{ data_get($homePage->content, 'home_banner.sub_banner_1.image_url') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Link Type</label>
                                        <select name="home_banner[sub_banner_1][redirect_link][link_type]" class="form-select">
                                            <option value="collection" {{ data_get($homePage->content, 'home_banner.sub_banner_1.redirect_link.link_type') == 'collection' ? 'selected' : '' }}>Collection</option>
                                            <option value="product" {{ data_get($homePage->content, 'home_banner.sub_banner_1.redirect_link.link_type') == 'product' ? 'selected' : '' }}>Product</option>
                                            <option value="external_url" {{ data_get($homePage->content, 'home_banner.sub_banner_1.redirect_link.link_type') == 'external_url' ? 'selected' : '' }}>External URL</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Link</label>
                                        <input type="text" name="home_banner[sub_banner_1][redirect_link][link]" class="form-control"
                                               value="{{ data_get($homePage->content, 'home_banner.sub_banner_1.redirect_link.link') }}">
                                    </div>
                                </div>

                                <h6 class="mt-4">Sub Banner 2</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Image URL</label>
                                        <input type="text" name="home_banner[sub_banner_2][image_url]" class="form-control"
                                               value="{{ data_get($homePage->content, 'home_banner.sub_banner_2.image_url') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Link Type</label>
                                        <select name="home_banner[sub_banner_2][redirect_link][link_type]" class="form-select">
                                            <option value="collection" {{ data_get($homePage->content, 'home_banner.sub_banner_2.redirect_link.link_type') == 'collection' ? 'selected' : '' }}>Collection</option>
                                            <option value="product" {{ data_get($homePage->content, 'home_banner.sub_banner_2.redirect_link.link_type') == 'product' ? 'selected' : '' }}>Product</option>
                                            <option value="external_url" {{ data_get($homePage->content, 'home_banner.sub_banner_2.redirect_link.link_type') == 'external_url' ? 'selected' : '' }}>External URL</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Link</label>
                                        <input type="text" name="home_banner[sub_banner_2][redirect_link][link]" class="form-control"
                                               value="{{ data_get($homePage->content, 'home_banner.sub_banner_2.redirect_link.link') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Banners Tab -->
                    <div class="tab-pane fade" id="featured-banners">
                        <div class="card section-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5><i class="bi bi-images"></i> Featured Banners</h5>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="featured_banners[status]" value="1"
                                               {{ data_get($homePage->content, 'featured_banners.status') ? 'checked' : '' }}>
                                        <label class="form-check-label">Enable</label>
                                    </div>
                                </div>

                                @php
                                    $banners = data_get($homePage->content, 'featured_banners.banners', []);
                                    if (count($banners) < 4) {
                                        $banners = array_pad($banners, 4, ['status' => true, 'image_url' => '', 'redirect_link' => ['link' => '', 'link_type' => 'collection']]);
                                    }
                                @endphp

                                @foreach($banners as $index => $banner)
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6>Banner {{ $index + 1 }}</h6>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox"
                                                       name="featured_banners[banners][{{ $index }}][status]" value="1"
                                                       {{ data_get($banner, 'status') ? 'checked' : '' }}>
                                                <label class="form-check-label">Enable</label>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <label class="form-label">Image URL</label>
                                                <input type="text" name="featured_banners[banners][{{ $index }}][image_url]" class="form-control"
                                                       value="{{ data_get($banner, 'image_url') }}">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label">Link Type</label>
                                                <select name="featured_banners[banners][{{ $index }}][redirect_link][link_type]" class="form-select">
                                                    <option value="collection" {{ data_get($banner, 'redirect_link.link_type') == 'collection' ? 'selected' : '' }}>Collection</option>
                                                    <option value="product" {{ data_get($banner, 'redirect_link.link_type') == 'product' ? 'selected' : '' }}>Product</option>
                                                    <option value="external_url" {{ data_get($banner, 'redirect_link.link_type') == 'external_url' ? 'selected' : '' }}>External URL</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label">Link</label>
                                                <input type="text" name="featured_banners[banners][{{ $index }}][redirect_link][link]" class="form-control"
                                                       value="{{ data_get($banner, 'redirect_link.link') }}">
                                            </div>
                                            {{-- Region Restrictions --}}
                                            <div class="col-md-3 mb-2">
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="featured_banners[banners][{{ $index }}][zambia_only]" value="1"
                                                           {{ data_get($banner, 'zambia_only') ? 'checked' : '' }}>
                                                    <label class="form-check-label text-warning fw-semibold">🇿🇲 Zambia Only</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <div class="form-check form-switch mt-2">
                                                    <input class="form-check-input" type="checkbox"
                                                           name="featured_banners[banners][{{ $index }}][zimbabwe_only]" value="1"
                                                           {{ data_get($banner, 'zimbabwe_only') ? 'checked' : '' }}>
                                                    <label class="form-check-label text-success fw-semibold">🇿🇼 Zimbabwe Only</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Tab -->
                    <div class="tab-pane fade" id="main-content">
                        <div class="card section-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5><i class="bi bi-layout-text-window"></i> Main Content</h5>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="main_content[status]" value="1"
                                               {{ data_get($homePage->content, 'main_content.status') ? 'checked' : '' }}>
                                        <label class="form-check-label">Enable</label>
                                    </div>
                                </div>

                                <!-- Section 1 Products -->
                                <h6 class="mt-4">Section 1: Trending Products</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="main_content[section1_products][title]" class="form-control"
                                               value="{{ data_get($homePage->content, 'main_content.section1_products.title') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" name="main_content[section1_products][status]" value="1"
                                                   {{ data_get($homePage->content, 'main_content.section1_products.status') ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Products</label>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" class="btn btn-primary" onclick="openProductSearch('section1')">
                                                <i class="bi bi-search"></i> Search & Select Products
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" onclick="openBulkSku('section1')">
                                                <i class="bi bi-upc-scan"></i> Bulk Add by SKU
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllProducts('section1')">
                                                <i class="bi bi-trash"></i> Clear All
                                            </button>
                                        </div>
                                        <input type="hidden" name="main_content[section1_products][product_ids]" id="section1_product_ids"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.section1_products.product_ids', [])) }}">
                                        <div id="section1_products_preview" class="selected-products-preview"></div>
                                    </div>
                                </div>

                                <!-- Section 4 Products -->
                                <h6 class="mt-4">Section 4: Christmas Collections</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="main_content[section4_products][title]" class="form-control"
                                               value="{{ data_get($homePage->content, 'main_content.section4_products.title') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" name="main_content[section4_products][status]" value="1"
                                                   {{ data_get($homePage->content, 'main_content.section4_products.status') ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Products</label>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" class="btn btn-primary" onclick="openProductSearch('section4')">
                                                <i class="bi bi-search"></i> Search & Select Products
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" onclick="openBulkSku('section4')">
                                                <i class="bi bi-upc-scan"></i> Bulk Add by SKU
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllProducts('section4')">
                                                <i class="bi bi-trash"></i> Clear All
                                            </button>
                                        </div>
                                        <input type="hidden" name="main_content[section4_products][product_ids]" id="section4_product_ids"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.section4_products.product_ids', [])) }}">
                                        <div id="section4_products_preview" class="selected-products-preview"></div>
                                    </div>
                                </div>

                                <!-- Section 7 Products -->
                                <h6 class="mt-4">Section 7: Best Sellers</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="main_content[section7_products][title]" class="form-control"
                                               value="{{ data_get($homePage->content, 'main_content.section7_products.title') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" name="main_content[section7_products][status]" value="1"
                                                   {{ data_get($homePage->content, 'main_content.section7_products.status') ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Products</label>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" class="btn btn-primary" onclick="openProductSearch('section7')">
                                                <i class="bi bi-search"></i> Search & Select Products
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" onclick="openBulkSku('section7')">
                                                <i class="bi bi-upc-scan"></i> Bulk Add by SKU
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllProducts('section7')">
                                                <i class="bi bi-trash"></i> Clear All
                                            </button>
                                        </div>
                                        <input type="hidden" name="main_content[section7_products][product_ids]" id="section7_product_ids"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.section7_products.product_ids', [])) }}">
                                        <div id="section7_products_preview" class="selected-products-preview"></div>
                                    </div>
                                </div>

                                <!-- Home Appliances Section -->
                                <h6 class="mt-4">Home Appliances Section</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="main_content[home_appliances][title]" class="form-control"
                                               value="{{ data_get($homePage->content, 'main_content.home_appliances.title') }}"
                                               placeholder="Shop Home Appliances Today">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" name="main_content[home_appliances][status]" value="1"
                                                   {{ data_get($homePage->content, 'main_content.home_appliances.status') ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Products</label>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" class="btn btn-primary" onclick="openProductSearch('home_appliances')">
                                                <i class="bi bi-search"></i> Search & Select Products
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" onclick="openBulkSku('home_appliances')">
                                                <i class="bi bi-upc-scan"></i> Bulk Add by SKU
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllProducts('home_appliances')">
                                                <i class="bi bi-trash"></i> Clear All
                                            </button>
                                        </div>
                                        <input type="hidden" name="main_content[home_appliances][product_ids]" id="home_appliances_product_ids"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.home_appliances.product_ids', [])) }}">
                                        <div id="home_appliances_products_preview" class="selected-products-preview"></div>
                                    </div>
                                </div>

                                <!-- Section 2 Categories -->
                                <h6 class="mt-4">Section 2: Browse By Categories</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="main_content[section2_categories_list][title]" class="form-control"
                                               value="{{ data_get($homePage->content, 'main_content.section2_categories_list.title') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" name="main_content[section2_categories_list][status]" value="1"
                                                   {{ data_get($homePage->content, 'main_content.section2_categories_list.status') ? 'checked' : '' }}>
                                            <label class="form-check-label">Enable</label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Categories</label>
                                        <button type="button" class="btn btn-primary" onclick="openCategorySelector('section2')">
                                            <i class="bi bi-grid"></i> Select Categories
                                        </button>
                                        <input type="hidden" name="main_content[section2_categories_list][category_ids]" id="section2_category_ids"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.section2_categories_list.category_ids', [])) }}">
                                        <div id="section2_categories_preview" class="selected-products-preview"></div>
                                    </div>
                                </div>

                                <!-- Sidebar -->
                                <h6 class="mt-4">Sidebar</h6>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="main_content[sidebar][status]" value="1"
                                           {{ data_get($homePage->content, 'main_content.sidebar.status') ? 'checked' : '' }}>
                                    <label class="form-check-label">Enable Sidebar</label>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sidebar Products Title</label>
                                        <input type="text" name="main_content[sidebar][sidebar_products][title]" class="form-control"
                                               value="{{ data_get($homePage->content, 'main_content.sidebar.sidebar_products.title') }}">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Sidebar Products</label>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button type="button" class="btn btn-primary" onclick="openProductSearch('sidebar')">
                                                <i class="bi bi-search"></i> Search & Select Products
                                            </button>
                                            <button type="button" class="btn btn-outline-primary" onclick="openBulkSku('sidebar')">
                                                <i class="bi bi-upc-scan"></i> Bulk Add by SKU
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllProducts('sidebar')">
                                                <i class="bi bi-trash"></i> Clear All
                                            </button>
                                        </div>
                                        <input type="hidden" name="main_content[sidebar][sidebar_products][product_ids]" id="sidebar_product_ids"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.sidebar.sidebar_products.product_ids', [])) }}">
                                        <div id="sidebar_products_preview" class="selected-products-preview"></div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Sidebar Categories</label>
                                        <button type="button" class="btn btn-primary" onclick="openCategorySelector('sidebar')">
                                            <i class="bi bi-grid"></i> Select Categories
                                        </button>
                                        <input type="hidden" name="main_content[sidebar][categories_icon_list][category_ids]" id="sidebar_category_ids"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.sidebar.categories_icon_list.category_ids', [])) }}">
                                        <div id="sidebar_categories_preview" class="selected-products-preview"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Newsletter Tab -->
                    <div class="tab-pane fade" id="newsletter">
                        <div class="card section-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5><i class="bi bi-envelope"></i> Newsletter</h5>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="news_letter[status]" value="1"
                                               {{ data_get($homePage->content, 'news_letter.status') ? 'checked' : '' }}>
                                        <label class="form-check-label">Enable</label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" name="news_letter[title]" class="form-control"
                                               value="{{ data_get($homePage->content, 'news_letter.title') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Sub Title</label>
                                        <input type="text" name="news_letter[sub_title]" class="form-control"
                                               value="{{ data_get($homePage->content, 'news_letter.sub_title') }}">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Image URL</label>
                                        <input type="text" name="news_letter[image_url]" class="form-control"
                                               value="{{ data_get($homePage->content, 'news_letter.image_url') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PopUps Tab -->
                    <div class="tab-pane fade" id="popups">
                        <div class="card section-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5><i class="bi bi-window-fullscreen"></i> PopUps</h5>
                                    <span class="badge bg-info">Shown once per session after 15 seconds</span>
                                </div>

                                <div class="alert alert-light border mb-4">
                                    <i class="bi bi-info-circle"></i>
                                    Add one row per image. Each image can have an optional redirect link that opens when clicked.
                                    Portrait images (e.g. 1200&times;1420px) work best.
                                </div>

                                @php
                                    // Normalise to [{image_url, link}] regardless of old/new format
                                    $normalise = function(array $items) {
                                        return array_map(function($item) {
                                            if (is_string($item)) return ['image_url' => $item, 'link' => ''];
                                            return ['image_url' => $item['image_url'] ?? '', 'link' => $item['link'] ?? ''];
                                        }, $items);
                                    };
                                    $zambiaItems = $normalise(data_get($homePage->content, 'popup_images.zambia', []));
                                    $otherItems  = $normalise(data_get($homePage->content, 'popup_images.other',  []));
                                @endphp

                                <!-- Zambia Popup -->
                                <div class="card mb-4 border-warning">
                                    <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">🇿🇲 Zambia Popup <span class="text-muted fw-normal">(ZMW currency)</span></h6>
                                        <button type="button" class="btn btn-sm btn-warning" onclick="addPopupRow('zambia')">
                                            <i class="bi bi-plus-circle"></i> Add Image
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="popup-zambia-rows">
                                            @foreach($zambiaItems as $i => $item)
                                            <div class="popup-row border rounded p-2 mb-2 d-flex gap-2 align-items-start">
                                                <div class="popup-thumb-wrap" style="width:60px;flex-shrink:0;">
                                                    <img src="{{ $item['image_url'] }}" style="width:60px;height:60px;object-fit:cover;border-radius:4px;display:{{ $item['image_url'] ? 'block' : 'none' }};" onerror="this.style.display='none'" alt="">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="mb-1">
                                                        <label class="form-label mb-0 small fw-semibold">Image URL</label>
                                                        <input type="text" name="popup_images[zambia][{{ $i }}][image_url]"
                                                               class="form-control form-control-sm popup-img-url"
                                                               value="{{ $item['image_url'] }}"
                                                               placeholder="https://media.raines.africa/storage/..."
                                                               oninput="updateThumb(this)">
                                                    </div>
                                                    <div>
                                                        <label class="form-label mb-0 small fw-semibold">Redirect Link <span class="text-muted fw-normal">(optional)</span></label>
                                                        <input type="text" name="popup_images[zambia][{{ $i }}][link]"
                                                               class="form-control form-control-sm"
                                                               value="{{ $item['link'] }}"
                                                               placeholder="https://raines.africa/collection/...">
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="removeRow(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            @endforeach
                                            @if(empty($zambiaItems))
                                            <p class="text-muted small" id="popup-zambia-empty">No images yet. Click "Add Image" to start.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- South Africa Popup -->
                                @php $saItems = $normalise(data_get($homePage->content, 'popup_images.south_africa', [])); @endphp
                                <div class="card mb-4 border-primary">
                                    <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">🇿🇦 South Africa PopUp <span class="text-muted fw-normal">(shown when currency is ZAR)</span></h6>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addPopupRow('south_africa')">
                                            <i class="bi bi-plus-circle"></i> Add Image
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="popup-south_africa-rows">
                                            @foreach($saItems as $i => $item)
                                            <div class="popup-row border rounded p-2 mb-2 d-flex gap-2 align-items-start">
                                                <div class="popup-thumb-wrap" style="width:60px;flex-shrink:0;">
                                                    <img src="{{ $item['image_url'] }}" style="width:60px;height:60px;object-fit:cover;border-radius:4px;display:{{ $item['image_url'] ? 'block' : 'none' }};" onerror="this.style.display='none'" alt="">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="mb-1">
                                                        <label class="form-label mb-0 small fw-semibold">Image URL</label>
                                                        <input type="text" name="popup_images[south_africa][{{ $i }}][image_url]"
                                                               class="form-control form-control-sm popup-img-url"
                                                               value="{{ $item['image_url'] }}"
                                                               placeholder="https://media.raines.africa/storage/..."
                                                               oninput="updateThumb(this)">
                                                    </div>
                                                    <div>
                                                        <label class="form-label mb-0 small fw-semibold">Redirect Link <span class="text-muted fw-normal">(optional)</span></label>
                                                        <input type="text" name="popup_images[south_africa][{{ $i }}][link]"
                                                               class="form-control form-control-sm"
                                                               value="{{ $item['link'] }}"
                                                               placeholder="https://raines.africa/collection/...">
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="removeRow(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            @endforeach
                                            @if(empty($saItems))
                                            <p class="text-muted small" id="popup-south_africa-empty">No images yet. Click "Add Image" to start.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Other Countries Popup -->
                                <div class="card mb-4 border-success">
                                    <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">🌍 Other Countries Popup <span class="text-muted fw-normal">(USD / all others)</span></h6>
                                        <button type="button" class="btn btn-sm btn-success" onclick="addPopupRow('other')">
                                            <i class="bi bi-plus-circle"></i> Add Image
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="popup-other-rows">
                                            @foreach($otherItems as $i => $item)
                                            <div class="popup-row border rounded p-2 mb-2 d-flex gap-2 align-items-start">
                                                <div class="popup-thumb-wrap" style="width:60px;flex-shrink:0;">
                                                    <img src="{{ $item['image_url'] }}" style="width:60px;height:60px;object-fit:cover;border-radius:4px;display:{{ $item['image_url'] ? 'block' : 'none' }};" onerror="this.style.display='none'" alt="">
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="mb-1">
                                                        <label class="form-label mb-0 small fw-semibold">Image URL</label>
                                                        <input type="text" name="popup_images[other][{{ $i }}][image_url]"
                                                               class="form-control form-control-sm popup-img-url"
                                                               value="{{ $item['image_url'] }}"
                                                               placeholder="https://media.raines.africa/storage/..."
                                                               oninput="updateThumb(this)">
                                                    </div>
                                                    <div>
                                                        <label class="form-label mb-0 small fw-semibold">Redirect Link <span class="text-muted fw-normal">(optional)</span></label>
                                                        <input type="text" name="popup_images[other][{{ $i }}][link]"
                                                               class="form-control form-control-sm"
                                                               value="{{ $item['link'] }}"
                                                               placeholder="https://raines.africa/collection/...">
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="removeRow(this)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            @endforeach
                                            @if(empty($otherItems))
                                            <p class="text-muted small" id="popup-other-empty">No images yet. Click "Add Image" to start.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label fw-bold">Slug</label>
                                <input type="text" name="slug" class="form-control" value="{{ $homePage->slug }}" required>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                                <a href="{{ route('admin.home-pages.index') }}" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-x"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Product Search Modal -->
    <div id="productSearchModal" class="product-search-modal">
        <div class="product-search-content">
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Search Products</h5>
                    <button type="button" class="btn-close" onclick="closeProductSearch()"></button>
                </div>
                <div class="input-group">
                    <input type="text" id="productSearchInput" class="form-control" placeholder="Search by name or SKU...">
                    <button class="btn btn-primary" onclick="searchProducts()">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="p-3" style="overflow-y: auto; flex: 1;">
                <div id="productSearchResults"></div>
            </div>
            <div class="p-3 border-top">
                <button class="btn btn-success" onclick="closeProductSearch()">
                    <i class="bi bi-check"></i> Done
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk SKU Modal -->
    <div id="bulkSkuModal" class="product-search-modal">
        <div class="product-search-content" style="max-width: 600px;">
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-upc-scan"></i> Bulk Add Products by SKU</h5>
                    <button type="button" class="btn-close" onclick="closeBulkSku()"></button>
                </div>
            </div>
            <div class="p-3">
                <label class="form-label">Paste SKUs below <span class="text-muted">(one per line)</span></label>
                <textarea id="bulkSkuInput" class="form-control" rows="10"
                          placeholder="e.g.&#10;SKU-001&#10;SKU-002&#10;SKU-003"></textarea>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-info-circle"></i> Enter one SKU per line. Empty lines will be ignored.
                </small>
                <div id="bulkSkuStatus" class="mt-3" style="display:none;"></div>
            </div>
            <div class="p-3 border-top d-flex justify-content-between">
                <button class="btn btn-outline-secondary" onclick="closeBulkSku()">Cancel</button>
                <button class="btn btn-success" id="bulkSkuSubmitBtn" onclick="submitBulkSkus()">
                    <i class="bi bi-plus-circle"></i> Fetch & Add Products
                </button>
            </div>
        </div>
    </div>

    <!-- Category Selector Modal -->
    <div id="categorySelectorModal" class="product-search-modal">
        <div class="product-search-content">
            <div class="p-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Select Categories</h5>
                    <button type="button" class="btn-close" onclick="closeCategorySelector()"></button>
                </div>
            </div>
            <div class="p-3" style="overflow-y: auto; flex: 1;">
                <div id="categorySelectorList"></div>
            </div>
            <div class="p-3 border-top">
                <button class="btn btn-success" onclick="closeCategorySelector()">
                    <i class="bi bi-check"></i> Done
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── Popup image row helpers ────────────────────────────────────────────────
function addPopupRow(group) {
    const container = document.getElementById('popup-' + group + '-rows');
    // Remove "no images" placeholder if present
    const placeholder = container.querySelector('p.text-muted');
    if (placeholder) placeholder.remove();

    const idx = container.querySelectorAll('.popup-row').length;
    const html = `
        <div class="popup-row border rounded p-2 mb-2 d-flex gap-2 align-items-start">
            <div class="popup-thumb-wrap" style="width:60px;flex-shrink:0;">
                <img src="" style="width:60px;height:60px;object-fit:cover;border-radius:4px;display:none;" alt="">
            </div>
            <div class="flex-grow-1">
                <div class="mb-1">
                    <label class="form-label mb-0 small fw-semibold">Image URL</label>
                    <input type="text" name="popup_images[${group}][${idx}][image_url]"
                           class="form-control form-control-sm popup-img-url"
                           placeholder="https://media.raines.africa/storage/..."
                           oninput="updateThumb(this)">
                </div>
                <div>
                    <label class="form-label mb-0 small fw-semibold">Redirect Link <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="popup_images[${group}][${idx}][link]"
                           class="form-control form-control-sm"
                           placeholder="https://raines.africa/collection/...">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="removeRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    // Focus the new image URL input
    container.querySelectorAll('.popup-img-url').forEach((el, i, arr) => {
        if (i === arr.length - 1) el.focus();
    });
}

function removeRow(btn) {
    const row = btn.closest('.popup-row');
    const container = row.parentElement;
    row.remove();
    // Re-index remaining rows so form names stay sequential
    const group = container.id.replace('popup-', '').replace('-rows', '');
    container.querySelectorAll('.popup-row').forEach((r, i) => {
        r.querySelector('[name*="image_url"]').name = `popup_images[${group}][${i}][image_url]`;
        r.querySelector('[name*="link"]').name        = `popup_images[${group}][${i}][link]`;
    });
}

function updateThumb(input) {
    const wrap = input.closest('.popup-row').querySelector('.popup-thumb-wrap img');
    if (!wrap) return;
    const url = input.value.trim();
    if (url) { wrap.src = url; wrap.style.display = 'block'; }
    else      { wrap.src = '';  wrap.style.display = 'none';  }
}
// ── End popup helpers ──────────────────────────────────────────────────────

let currentProductSection = '';
let currentCategorySection = '';
let selectedProductsData = {};
let selectedCategoriesData = {};
let categoriesCache = null;


// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load initial product previews
    ['section1', 'section4', 'section7', 'home_appliances', 'sidebar'].forEach(section => {
        const ids = document.getElementById(section + '_product_ids').value;
        if (ids) {
            loadInitialProducts(section, ids.split(',').filter(id => id));
        }
    });

    // Load initial category previews
    ['section2', 'sidebar'].forEach(section => {
        const ids = document.getElementById(section + '_category_ids').value;
        if (ids) {
            loadInitialCategories(section, ids.split(',').filter(id => id));
        }
    });

    // Search on Enter key
    document.getElementById('productSearchInput')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchProducts();
        }
    });
});

// Product Search Functions
function openProductSearch(section) {
    currentProductSection = section;
    document.getElementById('productSearchModal').classList.add('active');
    document.getElementById('productSearchInput').value = '';
    document.getElementById('productSearchResults').innerHTML = '';
}

function closeProductSearch() {
    document.getElementById('productSearchModal').classList.remove('active');
}

function searchProducts() {
    const query = document.getElementById('productSearchInput').value;
    const resultsContainer = document.getElementById('productSearchResults');

    if (!query) {
        resultsContainer.innerHTML = '<div class="alert alert-info">Please enter a search term</div>';
        return;
    }

    resultsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';

    fetch(`/api/product/search-promo?search=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const products = data.data || [];

            if (products.length === 0) {
                resultsContainer.innerHTML = '<div class="alert alert-info">No products found</div>';
                return;
            }

            const currentIds = getSelectedProductIds(currentProductSection);

            resultsContainer.innerHTML = products.map(product => {
                const thumbnail = product.product_thumbnail || product.thumbnail;
                const imageUrl = thumbnail?.image_url || thumbnail?.original_url || '';
                const isSelected = currentIds.includes(product.id.toString());

                // Store product data
                selectedProductsData[product.id] = product;

                return `
                    <div class="product-search-item ${isSelected ? 'selected' : ''}" onclick="toggleProduct(${product.id})">
                        <div class="d-flex align-items-center gap-3">
                            <img src="${imageUrl}" alt="${product.name}" class="rounded">
                            <div class="flex-grow-1">
                                <div class="fw-bold">${product.name}</div>
                                <div class="text-muted small">SKU: ${product.sku}</div>
                                <div class="text-primary small">ID: ${product.id}</div>
                            </div>
                            ${isSelected ? '<i class="bi bi-check-circle-fill text-success fs-4"></i>' : ''}
                        </div>
                    </div>
                `;
            }).join('');
        })
        .catch(error => {
            console.error('Error:', error);
            resultsContainer.innerHTML = '<div class="alert alert-danger">Error searching products</div>';
        });
}

function toggleProduct(productId) {
    const currentIds = getSelectedProductIds(currentProductSection);
    const index = currentIds.indexOf(productId.toString());

    if (index > -1) {
        currentIds.splice(index, 1);
    } else {
        currentIds.push(productId.toString());
    }

    setSelectedProductIds(currentProductSection, currentIds);
    updateProductPreview(currentProductSection);

    // Update UI
    const items = document.querySelectorAll('.product-search-item');
    items.forEach(item => {
        const itemId = item.getAttribute('onclick').match(/\d+/)[0];
        if (parseInt(itemId) === parseInt(productId)) {
            item.classList.toggle('selected');
            const icon = item.querySelector('.bi-check-circle-fill');
            if (icon) {
                icon.remove();
            } else {
                item.querySelector('.d-flex').innerHTML += '<i class="bi bi-check-circle-fill text-success fs-4"></i>';
            }
        }
    });
}

function getSelectedProductIds(section) {
    const value = document.getElementById(section + '_product_ids').value;
    return value ? value.split(',').filter(id => id) : [];
}

function setSelectedProductIds(section, ids) {
    document.getElementById(section + '_product_ids').value = ids.join(',');
}

function updateProductPreview(section) {
    const ids = getSelectedProductIds(section);
    const previewContainer = document.getElementById(section + '_products_preview');

    if (ids.length === 0) {
        previewContainer.innerHTML = '<small class="text-muted">No products selected</small>';
        return;
    }

    previewContainer.innerHTML = ids.map(id => {
        const product = selectedProductsData[id];
        if (!product) return '';

        const thumbnail = product.product_thumbnail || product.thumbnail;
        const imageUrl = thumbnail?.image_url || thumbnail?.original_url || '';

        return `
            <div class="selected-product-badge">
                ${imageUrl ? `<img src="${imageUrl}" alt="${product.name}">` : ''}
                <span class="small">${product.name}</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeProduct('${section}', ${id})">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        `;
    }).join('');
}

function removeProduct(section, productId) {
    const currentIds = getSelectedProductIds(section);
    const filtered = currentIds.filter(id => parseInt(id) !== parseInt(productId));
    setSelectedProductIds(section, filtered);
    updateProductPreview(section);
}

async function clearAllProducts(section) {
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Are you sure you want to remove all products from this section?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;
    setSelectedProductIds(section, []);
    updateProductPreview(section);
}

function loadInitialProducts(section, ids) {
    if (ids.length === 0) return;

    fetch(`/api/product?ids=${ids.join(',')}`)
        .then(response => response.json())
        .then(data => {
            const products = data.data || [];
            products.forEach(product => {
                selectedProductsData[product.id] = product;
            });
            updateProductPreview(section);
        })
        .catch(error => console.error('Error loading products:', error));
}

// Category Selector Functions
function openCategorySelector(section) {
    currentCategorySection = section;
    document.getElementById('categorySelectorModal').classList.add('active');
    loadCategories();
}

function closeCategorySelector() {
    document.getElementById('categorySelectorModal').classList.remove('active');
}

function loadCategories() {
    const container = document.getElementById('categorySelectorList');

    if (categoriesCache) {
        renderCategories(categoriesCache);
        return;
    }

    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';

    // Fetch only parent categories (no parent_id)
    fetch('/api/category?status=1&paginate=100')
        .then(response => response.json())
        .then(data => {
            // Filter only categories without parent_id (main categories)
            const categories = (data.data || []).filter(cat => !cat.parent_id);
            categoriesCache = categories;
            categories.forEach(cat => {
                selectedCategoriesData[cat.id] = cat;
            });
            renderCategories(categories);
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = '<div class="alert alert-danger">Error loading categories</div>';
        });
}

function renderCategories(categories) {
    const container = document.getElementById('categorySelectorList');
    const currentIds = getSelectedCategoryIds(currentCategorySection);

    container.innerHTML = categories.map(category => {
        const isSelected = currentIds.includes(category.id.toString());

        return `
            <div class="category-checkbox">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${category.id}"
                           id="cat_${category.id}" ${isSelected ? 'checked' : ''}
                           onchange="toggleCategory(${category.id})">
                    <label class="form-check-label" for="cat_${category.id}">
                        ${category.name}
                    </label>
                </div>
            </div>
        `;
    }).join('');
}

function toggleCategory(categoryId) {
    const currentIds = getSelectedCategoryIds(currentCategorySection);
    const index = currentIds.indexOf(categoryId.toString());

    if (index > -1) {
        currentIds.splice(index, 1);
    } else {
        currentIds.push(categoryId.toString());
    }

    setSelectedCategoryIds(currentCategorySection, currentIds);
    updateCategoryPreview(currentCategorySection);
}

function getSelectedCategoryIds(section) {
    const value = document.getElementById(section + '_category_ids').value;
    return value ? value.split(',').filter(id => id) : [];
}

function setSelectedCategoryIds(section, ids) {
    document.getElementById(section + '_category_ids').value = ids.join(',');
}

function updateCategoryPreview(section) {
    const ids = getSelectedCategoryIds(section);
    const previewContainer = document.getElementById(section + '_categories_preview');

    if (ids.length === 0) {
        previewContainer.innerHTML = '<small class="text-muted">No categories selected</small>';
        return;
    }

    previewContainer.innerHTML = ids.map(id => {
        const category = selectedCategoriesData[id];
        if (!category) return '';

        return `
            <div class="selected-product-badge">
                <span class="small">${category.name}</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeCategory('${section}', ${id})">
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        `;
    }).join('');
}

function removeCategory(section, categoryId) {
    const currentIds = getSelectedCategoryIds(section);
    const filtered = currentIds.filter(id => parseInt(id) !== parseInt(categoryId));
    setSelectedCategoryIds(section, filtered);
    updateCategoryPreview(section);

    // Update checkbox in modal
    const checkbox = document.getElementById('cat_' + categoryId);
    if (checkbox) checkbox.checked = false;
}

function loadInitialCategories(section, ids) {
    if (ids.length === 0) return;

    fetch(`/api/category?status=1&paginate=100`)
        .then(response => response.json())
        .then(data => {
            const categories = (data.data || []).filter(cat => !cat.parent_id);
            categories.forEach(cat => {
                selectedCategoriesData[cat.id] = cat;
            });
            updateCategoryPreview(section);
        })
        .catch(error => console.error('Error loading categories:', error));
}

// ── Bulk Add by SKU Functions ──────────────────────────────────────────────
let currentBulkSkuSection = '';

function openBulkSku(section) {
    currentBulkSkuSection = section;
    document.getElementById('bulkSkuInput').value = '';
    document.getElementById('bulkSkuStatus').style.display = 'none';
    document.getElementById('bulkSkuSubmitBtn').disabled = false;
    document.getElementById('bulkSkuSubmitBtn').innerHTML = '<i class="bi bi-plus-circle"></i> Fetch & Add Products';
    document.getElementById('bulkSkuModal').classList.add('active');
    setTimeout(() => document.getElementById('bulkSkuInput').focus(), 100);
}

function closeBulkSku() {
    document.getElementById('bulkSkuModal').classList.remove('active');
}

function submitBulkSkus() {
    const raw = document.getElementById('bulkSkuInput').value.trim();
    if (!raw) {
        showBulkSkuStatus('warning', 'Please paste at least one SKU.');
        return;
    }

    // Parse SKUs: split by newlines, trim, remove empties and duplicates
    const skus = [...new Set(
        raw.split(/[\r\n]+/)
           .map(s => s.trim())
           .filter(s => s.length > 0)
    )];

    if (skus.length === 0) {
        showBulkSkuStatus('warning', 'No valid SKUs found.');
        return;
    }

    const btn = document.getElementById('bulkSkuSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Fetching...';
    showBulkSkuStatus('info', `Looking up ${skus.length} SKU(s)...`);

    const skuParam = encodeURIComponent(skus.join(','));
    let allProducts = [];

    // Fetch all pages recursively
    function fetchPage(page) {
        return fetch(`/api/product?skus=${skuParam}&status=1&paginate=50&page=${page}`)
            .then(r => r.json())
            .then(data => {
                const products = data.data || [];
                allProducts = allProducts.concat(products);
                const currentPage = data.current_page || 1;
                const lastPage = data.last_page || 1;
                if (currentPage < lastPage) {
                    showBulkSkuStatus('info', `Fetching page ${currentPage + 1} of ${lastPage}...`);
                    return fetchPage(page + 1);
                }
            });
    }

    fetchPage(1)
        .then(() => {
            if (allProducts.length === 0) {
                showBulkSkuStatus('danger', 'No products found matching the entered SKUs.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-plus-circle"></i> Fetch & Add Products';
                return;
            }

            // Get current IDs for this section
            const currentIds = getSelectedProductIds(currentBulkSkuSection);
            let addedCount = 0;
            let duplicateCount = 0;

            allProducts.forEach(product => {
                selectedProductsData[product.id] = product;
                const idStr = product.id.toString();
                if (!currentIds.includes(idStr)) {
                    currentIds.push(idStr);
                    addedCount++;
                } else {
                    duplicateCount++;
                }
            });

            setSelectedProductIds(currentBulkSkuSection, currentIds);
            updateProductPreview(currentBulkSkuSection);

            const foundSkus = allProducts.map(p => p.sku);
            const notFound = skus.filter(sku => !foundSkus.some(fs => fs && fs.toLowerCase() === sku.toLowerCase()));
            let msg = `<strong>${addedCount}</strong> product(s) added.`;
            if (duplicateCount > 0) msg += ` <strong>${duplicateCount}</strong> already in the list.`;
            if (notFound.length > 0) msg += `<br><span class="text-danger"><strong>${notFound.length}</strong> SKU(s) not found: <code>${notFound.join(', ')}</code></span>`;

            showBulkSkuStatus(notFound.length > 0 ? 'warning' : 'success', msg);

            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus-circle"></i> Fetch & Add Products';
        })
        .catch(error => {
            console.error('Error:', error);
            showBulkSkuStatus('danger', 'Error fetching products. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-plus-circle"></i> Fetch & Add Products';
        });
}

function showBulkSkuStatus(type, message) {
    const el = document.getElementById('bulkSkuStatus');
    el.style.display = 'block';
    el.className = `mt-3 alert alert-${type}`;
    el.innerHTML = message;
}
</script>
@endpush

