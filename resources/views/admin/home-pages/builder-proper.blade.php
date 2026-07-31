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
                                        <label class="form-label">Product IDs (comma-separated)</label>
                                        <input type="text" name="main_content[section1_products][product_ids]" class="form-control"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.section1_products.product_ids', [])) }}">
                                        <small class="text-muted">Enter product IDs separated by commas</small>
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
                                        <label class="form-label">Product IDs (comma-separated)</label>
                                        <input type="text" name="main_content[section4_products][product_ids]" class="form-control"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.section4_products.product_ids', [])) }}">
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
                                        <label class="form-label">Product IDs (comma-separated)</label>
                                        <input type="text" name="main_content[section7_products][product_ids]" class="form-control"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.section7_products.product_ids', [])) }}">
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
                                        <label class="form-label">Category IDs (comma-separated)</label>
                                        <input type="text" name="main_content[section2_categories_list][category_ids]" class="form-control"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.section2_categories_list.category_ids', [])) }}">
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
                                        <label class="form-label">Sidebar Product IDs</label>
                                        <input type="text" name="main_content[sidebar][sidebar_products][product_ids]" class="form-control"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.sidebar.sidebar_products.product_ids', [])) }}">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Sidebar Category IDs</label>
                                        <input type="text" name="main_content[sidebar][categories_icon_list][category_ids]" class="form-control"
                                               value="{{ implode(',', data_get($homePage->content, 'main_content.sidebar.categories_icon_list.category_ids', [])) }}">
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
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle checkbox states
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        if (!checkbox.checked) {
            checkbox.value = '0';
        }
    });
});
</script>
@endpush

