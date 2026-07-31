@extends('admin.layout')

@section('title', 'Export Product Feed - Admin Panel')

@push('styles')
<style>
    /* Compact, Modern Design */
    .feed-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 18px 24px;
        border-radius: 10px;
        margin-bottom: 18px;
        box-shadow: 0 3px 12px rgba(102, 126, 234, 0.2);
    }

    .feed-header h2 {
        margin: 0 0 4px 0;
        font-size: 21px;
        font-weight: 600;
    }

    .feed-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 13px;
    }

    .feed-card {
        background: white;
        border-radius: 9px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        padding: 16px;
        margin-bottom: 16px;
    }

    .feed-card-title {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 11px;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .form-group-compact {
        margin-bottom: 11px;
    }

    .form-group-compact label {
        font-size: 12px;
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 4px;
        display: block;
    }

    .category-list-wrapper {
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        background: #fafafa;
        overflow: hidden;
    }

    .category-breadcrumb {
        background: #f7fafc;
        padding: 6px 9px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 11px;
    }

    .category-list-container {
        max-height: 250px;
        overflow-y: auto;
        padding: 7px;
        background: white;
    }

    .info-box-compact {
        background: #f7fafc;
        border-left: 2px solid #667eea;
        border-radius: 6px;
        padding: 11px;
        font-size: 11px;
        line-height: 1.4;
    }

    .info-box-compact h5 {
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 5px;
        color: #2d3748;
    }

    .info-box-compact ul {
        margin: 0;
        padding-left: 15px;
    }

    .info-box-compact li {
        margin-bottom: 2px;
        color: #4a5568;
    }

    .stats-box {
        background: #f0fdf4;
        border: 1px solid #86efac;
        border-radius: 6px;
        padding: 9px 11px;
        margin-bottom: 9px;
        font-size: 12px;
    }

    .split-warning-box {
        background: #fef3c7;
        border: 1px solid #fcd34d;
        border-radius: 6px;
        padding: 9px 11px;
        margin-bottom: 9px;
        font-size: 11px;
    }

    .delivery-filter-box {
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        padding: 8px;
        max-height: 350px;
        overflow-y: auto;
        background: white;
    }

    .feed-export-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .feed-export-card h3 {
        color: white;
        margin-bottom: 10px;
    }

    .feed-export-card p {
        opacity: 0.9;
        margin-bottom: 0;
    }

    .export-form-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }

    .export-form-card .card-body {
        padding: 30px;
    }

    .form-label {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
    }

    .form-select, .form-control {
        border-radius: 10px;
        border: 2px solid #e2e8f0;
        padding: 12px 16px;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .form-select:focus, .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-export {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 10px;
        padding: 12px 30px;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
    }

    .info-box {
        background: #f7fafc;
        border-left: 4px solid #667eea;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .info-box h5 {
        color: #2d3748;
        margin-bottom: 10px;
        font-weight: 600;
    }

    .info-box ul {
        margin-bottom: 0;
        padding-left: 20px;
    }

    .info-box li {
        color: #4a5568;
        margin-bottom: 5px;
    }

    /* Category List Styles */
    .category-item {
        padding: 5px 8px;
        margin: 2px 0;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: background 0.15s;
        font-size: 13px;
    }

    .category-item:hover {
        background: #f0f4f8;
    }

    .category-item.selected {
        background: #EBF5FF;
        border-left: 2px solid #667eea;
        font-weight: 500;
    }

    .category-item input[type="radio"] {
        cursor: pointer;
        width: 15px;
        height: 15px;
        margin: 0;
        accent-color: #667eea;
    }

    .category-item label {
        cursor: pointer;
        margin: 0;
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 13px;
    }

    .expand-icon {
        cursor: pointer;
        padding: 2px 5px;
        font-weight: bold;
        color: #667eea;
        user-select: none;
        border-radius: 3px;
        font-size: 11px;
    }

    .expand-icon:hover {
        background: #e2e8f0;
    }

    .delivery-checkbox-item {
        padding: 4px 6px;
        margin: 2px 0;
        border-radius: 4px;
        transition: all 0.15s;
        font-size: 12px;
    }

    .delivery-checkbox-item:hover {
        background: #f7fafc;
    }

    .delivery-checkbox-item label {
        cursor: pointer;
        display: flex;
        align-items: center;
        margin: 0;
        width: 100%;
    }

    .delivery-checkbox-item input[type="checkbox"] {
        margin-right: 7px;
        width: 15px;
        height: 15px;
        cursor: pointer;
        accent-color: #667eea;
    }

    /* Compact spacing */
    .row-compact {
        margin-left: -10px;
        margin-right: -10px;
    }

    .row-compact > [class*="col-"] {
        padding-left: 10px;
        padding-right: 10px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid feed-page-container">
    <!-- Header -->
    <div class="feed-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-rss-fill me-2"></i>Product Feed Export</h2>
                <p>Generate XML/TSV feeds for Google Shopping and advertising platforms</p>
            </div>
            <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row row-compact">
        <!-- Main Form (Left Column - 8) -->
        <div class="col-lg-8">
            <div class="feed-card">
                <h5 class="feed-card-title"><i class="bi bi-sliders me-1"></i>Export Configuration</h5>

                <!-- Export Mode Toggle -->
                <div class="form-group-compact mb-3">
                    <label><i class="bi bi-funnel me-1"></i>Export Mode</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="export_mode" id="mode_category" value="category" checked>
                        <label class="btn btn-outline-primary" for="mode_category">
                            <i class="bi bi-folder me-1"></i>By Category
                        </label>

                        <input type="radio" class="btn-check" name="export_mode" id="mode_sku" value="sku">
                        <label class="btn btn-outline-primary" for="mode_sku">
                            <i class="bi bi-list-ul me-1"></i>By SKU List
                        </label>
                    </div>
                </div>

                <!-- SKU List Section (Hidden by default) -->
                <div id="skuListSection" style="display: none;">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group-compact mb-3">
                                <label><i class="bi bi-upc-scan me-1"></i>Enter SKUs (One per line)</label>
                                <textarea
                                    class="form-control"
                                    id="sku_list"
                                    name="sku_list"
                                    rows="10"
                                    placeholder="Enter SKUs, one per line. Example:&#10;SKU12345&#10;SKU67890&#10;ABC-123&#10;XYZ-789"
                                    style="font-family: monospace; font-size: 12px;"
                                ></textarea>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Paste your SKUs here, one per line. Empty lines will be ignored.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group-compact">
                                <label><i class="bi bi-currency-exchange me-1"></i>Currency *</label>
                                <select class="form-select" id="currency_sku" name="currency_sku" required>
                                    <option value="">Select Currency</option>
                                    @foreach($currencies as $curr)
                                        <option value="{{ $curr->code }}" {{ $curr->code === 'USD' ? 'selected' : '' }}>
                                            {{ $curr->code }} - {{ $curr->name }} ({{ $curr->symbol }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Section (Shown by default) -->
                <div id="categorySection">
                <div class="row">
                    <!-- Store Filter -->
                    <div class="col-md-6">
                        <div class="form-group-compact">
                            <label><i class="bi bi-shop me-1"></i>Filter by Store (Optional)</label>
                            <select class="form-select" id="store_id" name="store_id">
                                <option value="">All Stores</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store['id'] }}">{{ $store['store_name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Currency Selection -->
                    <div class="col-md-6">
                        <div class="form-group-compact">
                            <label><i class="bi bi-currency-exchange me-1"></i>Currency *</label>
                            <select class="form-select" id="currency" name="currency" required>
                                <option value="">Select Currency</option>
                                @foreach($currencies as $curr)
                                    <option value="{{ $curr->code }}" {{ $curr->code === 'USD' ? 'selected' : '' }}>
                                        {{ $curr->code }} - {{ $curr->name }} ({{ $curr->symbol }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Region Filter -->
                <div class="row mt-1 mb-1">
                    <div class="col-12">
                        <div class="form-group-compact">
                            <label><i class="bi bi-globe me-1"></i>Region Filter (Optional)</label>
                            <select class="form-select" id="region_filter" name="region_filter">
                                <option value="">All Regions</option>
                                <option value="zambia_only">Zambia Only</option>
                                <option value="zimbabwe_only">Zimbabwe Only</option>
                                <option value="sa_only">SA Only</option>
                                <option value="global">Global</option>
                            </select>
                            <small class="text-muted">Filter products by their region availability.</small>
                        </div>
                    </div>
                </div>

                <!-- Promotion Filter -->
                <div class="row mt-1 mb-1">
                    <div class="col-12">
                        <div class="form-group-compact">
                            <label><i class="bi bi-tag me-1"></i>Promotion / Sale Filter (Optional)</label>
                            <select class="form-select" id="promotion_filter" name="promotion_filter">
                                <option value="">All Products (no filter)</option>
                                <option value="sale_price">Has Sale Price (sale_price &gt; 0)</option>
                                <option value="sale_enabled">Sale Enabled (is_sale_enabled = 1)</option>
                                <option value="both">Both — Sale Price &gt; 0 AND Sale Enabled</option>
                                <option value="either">Either — Sale Price &gt; 0 OR Sale Enabled</option>
                            </select>
                            <small class="text-muted">Use this to export only products currently on promotion.</small>
                        </div>
                    </div>
                </div>

                <!-- Price Range Filter -->
                <div class="row mt-1 mb-1">
                    <div class="col-12">
                        <div class="form-group-compact">
                            <label><i class="bi bi-currency-dollar me-1"></i>Price Range Filter (Optional — base currency)</label>
                            <div class="d-flex gap-2 align-items-center">
                                <div class="flex-fill">
                                    <input type="number" class="form-control" id="price_min" name="price_min" min="0" step="0.01" placeholder="Min price (e.g. 100)">
                                    <small class="text-muted">From</small>
                                </div>
                                <span class="text-muted fw-bold">–</span>
                                <div class="flex-fill">
                                    <input type="number" class="form-control" id="price_max" name="price_max" min="0" step="0.01" placeholder="Max price (e.g. 5000)">
                                    <small class="text-muted">To</small>
                                </div>
                            </div>
                            <small class="text-muted">Filters by effective price (sale price if set, otherwise regular price) in the product's base currency.</small>
                        </div>
                    </div>
                </div>

                <h5 class="feed-card-title"><i class="bi bi-sliders me-1"></i>Export Configuration</h5>

                <!-- Category Selection -->
                <div class="form-group-compact">
                    <label><i class="bi bi-folder me-1"></i>Select Category *</label>

                    <!-- Category List -->
                    <div class="category-list-wrapper">
                        <!-- Breadcrumb -->
                        <div id="categoryBreadcrumb" class="category-breadcrumb" style="display: none;">
                            <div id="breadcrumbLinks"></div>
                        </div>

                        <!-- List Container -->
                        <div class="category-list-container">
                            <ul id="categoryList" class="list-unstyled mb-0">
                                <li class="text-muted">Loading categories...</li>
                            </ul>
                        </div>
                    </div>

                    <input type="hidden" id="selectedCategorySlug" name="category" required>
                    <small class="text-muted d-block mt-1">Click radio to select. Click ▶ to expand subcategories.</small>
                </div>

                </div>
                <!-- End Category Section -->


            </div>

            <div class="feed-card">

                <!-- Stats & Download -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div id="statsContainer" style="display: none;">
                            <div class="stats-box">
                                <strong id="statsText"></strong>
                            </div>
                        </div>
                        <div id="splitWarning" style="display: none;">
                            <div class="split-warning-box">
                                <i class="bi bi-file-earmark-zip me-1"></i>
                                <strong id="splitText"></strong>
                            </div>
                        </div>
                        <div id="loadingStats" style="display: none;">
                            <div class="alert alert-warning mb-2 py-2 px-3">
                                <i class="bi bi-hourglass-split me-1"></i>
                                <small>Loading product count...</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Download Buttons -->
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <button type="button" id="downloadXml" class="btn btn-primary btn-export w-100" disabled>
                            <i class="bi bi-file-earmark-code me-1"></i>Download XML
                        </button>
                        <small class="text-muted d-block text-center mt-1">Google Shopping</small>
                    </div>
                    <div class="col-md-4 mb-2">
                        <button type="button" id="downloadTsv" class="btn btn-success btn-export w-100" disabled>
                            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Download TSV
                        </button>
                        <small class="text-muted d-block text-center mt-1">Google Ads</small>
                    </div>
                    <div class="col-md-4 mb-2">
                        <button type="button" id="downloadCsv" class="btn btn-info btn-export w-100" disabled>
                            <i class="bi bi-file-earmark-excel me-1"></i>Download CSV
                        </button>
                        <small class="text-muted d-block text-center mt-1">Excel / Sheets</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Sidebar (Right Column - 4) -->
        <div class="col-lg-4">
            <!-- Delivery Filter -->
            <div class="form-group-compact">
                <label><i class="bi bi-truck me-1"></i>Exclude Delivery Types (Optional)</label>
                <div class="delivery-filter-box">
                    @foreach($deliveryTexts as $text)
                        <div class="delivery-checkbox-item">
                            <label>
                                <input type="checkbox" class="delivery-exclude-checkbox" value="{{ $text }}">
                                <span>{{ $text }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="info-box-compact mb-3">
                <h5><i class="bi bi-lightbulb me-1"></i>Feed Information</h5>
                <ul class="mb-0">
                    <li>Only <strong>active & approved</strong> products included</li>
                    <li>Products from selected <strong>category & subcategories</strong></li>
                    <li>Prices <strong>auto-converted</strong> to selected currency</li>
                    <li>Available in <strong>XML & TSV</strong> formats</li>
                    <li>Product count shown before download</li>
                </ul>
            </div>

            <div class="info-box-compact">
                <h5><i class="bi bi-question-circle me-1"></i>Quick Guide</h5>
                <ol class="mb-0" style="padding-left: 16px;">
                    <li class="mb-1">Select category (click ▶ to expand)</li>
                    <li class="mb-1">Choose currency for pricing</li>
                    <li class="mb-1">Wait for product count</li>
                    <li class="mb-1">Click Download XML or TSV</li>
                    <li>Upload to Google Merchant Center</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
    const categoryList = document.getElementById('categoryList');
    const selectedCategorySlug = document.getElementById('selectedCategorySlug');
    const categoryBreadcrumb = document.getElementById('categoryBreadcrumb');
    const breadcrumbLinks = document.getElementById('breadcrumbLinks');
    const storeSelect = document.getElementById('store_id');
    const statsContainer = document.getElementById('statsContainer');
    const splitWarning = document.getElementById('splitWarning');
    const loadingStats = document.getElementById('loadingStats');
    const statsText = document.getElementById('statsText');
    const splitText = document.getElementById('splitText');
    const downloadXmlBtn = document.getElementById('downloadXml');
    const downloadTsvBtn = document.getElementById('downloadTsv');
    const downloadCsvBtn = document.getElementById('downloadCsv');
    const deliveryCheckboxes = document.querySelectorAll('.delivery-exclude-checkbox');
    const promotionFilterSelect = document.getElementById('promotion_filter');
    const regionFilterSelect = document.getElementById('region_filter');
    const priceMinInput = document.getElementById('price_min');
    const priceMaxInput = document.getElementById('price_max');

    // Export mode elements
    const modeCategoryRadio = document.getElementById('mode_category');
    const modeSkuRadio = document.getElementById('mode_sku');
    const categorySection = document.getElementById('categorySection');
    const skuListSection = document.getElementById('skuListSection');
    const skuListTextarea = document.getElementById('sku_list');
    const currencySelect = document.getElementById('currency'); // Category mode currency
    const currencySkuSelect = document.getElementById('currency_sku'); // SKU mode currency

    let currentStats = null;
    let allCategories = [];
    let categoryPath = []; // Track breadcrumb path
    let categoryMap = {}; // Map of all categories by ID
    let exportMode = 'category'; // 'category' or 'sku'

    // Helper function to check if download buttons should be enabled in SKU mode
    function checkSkuModeButtons() {
        if (exportMode === 'sku') {
            const hasSkus = skuListTextarea.value.trim().length > 0;
            const hasCurrency = currencySkuSelect.value !== '';
            const canDownload = hasSkus && hasCurrency;

            downloadXmlBtn.disabled = !canDownload;
            downloadTsvBtn.disabled = !canDownload;
            downloadCsvBtn.disabled = !canDownload;
        }
    }

    // Toggle between Category and SKU export modes
    function toggleExportMode() {
        exportMode = modeSkuRadio.checked ? 'sku' : 'category';

        if (exportMode === 'sku') {
            categorySection.style.display = 'none';
            skuListSection.style.display = 'block';
            selectedCategorySlug.removeAttribute('required');
            skuListTextarea.setAttribute('required', 'required');
        } else {
            categorySection.style.display = 'block';
            skuListSection.style.display = 'none';
            selectedCategorySlug.setAttribute('required', 'required');
            skuListTextarea.removeAttribute('required');
        }

        // Reset stats when mode changes
        resetStats();
    }

    modeCategoryRadio.addEventListener('change', toggleExportMode);
    modeSkuRadio.addEventListener('change', toggleExportMode);

    // Enable download buttons when SKU list has content and currency is selected
    skuListTextarea.addEventListener('input', checkSkuModeButtons);

    // Also check when SKU currency changes
    currencySkuSelect.addEventListener('change', checkSkuModeButtons);

    // Category currency change - check stats
    currencySelect.addEventListener('change', () => {
        if (exportMode === 'category' && selectedCategorySlug.value) {
            checkAndLoadStats();
        }
    });

    // Load categories from the hierarchical tree cache
    async function loadCategories() {
        try {
            const response = await fetch('{{ route("admin.product-feed.categories-by-store") }}');
            const data = await response.json();

            if (!data.success) {
                categoryList.innerHTML = `<li class="text-danger">${data.message || 'Failed to load categories'}</li>`;
                return;
            }

            // The response has categories as a flat array with parent_id
            const flatCategories = data.categories || [];

            if (flatCategories.length === 0) {
                categoryList.innerHTML = '<li class="text-muted">No categories with products found</li>';
                return;
            }

            // Build hierarchical tree from flat array
            allCategories = buildHierarchy(flatCategories);

            // Build category map for quick lookup
            buildCategoryMap(allCategories);

            // Show parent categories
            renderCategories();

        } catch (error) {
            console.error('Error loading categories:', error);
            categoryList.innerHTML = '<li class="text-danger">Error loading categories. Please refresh the page.</li>';
        }
    }

    // Build hierarchical tree from flat array
    function buildHierarchy(flatList) {
        const map = {};
        const roots = [];

        // First pass: create map and add subcategories array
        flatList.forEach(cat => {
            map[cat.id] = {...cat, subcategories: []};
        });

        // Second pass: build tree
        flatList.forEach(cat => {
            if (cat.parent_id && map[cat.parent_id]) {
                map[cat.parent_id].subcategories.push(map[cat.id]);
            } else {
                roots.push(map[cat.id]);
            }
        });

        return roots;
    }

    // Build category map recursively
    function buildCategoryMap(categories) {
        categories.forEach(cat => {
            categoryMap[cat.id] = cat;
            if (cat.subcategories && cat.subcategories.length > 0) {
                buildCategoryMap(cat.subcategories);
            }
        });
    }

    // Render categories (main or subcategories)
    function renderCategories() {
        const currentCategory = categoryPath.length > 0 ? categoryPath[categoryPath.length - 1] : null;
        const categoriesToShow = currentCategory ? (currentCategory.subcategories || []) : allCategories;

        // Update breadcrumb
        if (categoryPath.length > 0) {
            categoryBreadcrumb.style.display = 'block';
            let breadcrumbHTML = '<a style="margin-right:3px;padding:5px;background-color: #0b8a6f;color:white;border-radius:5px;" role="button" onclick="goBackToAll()"><i class="bi bi-arrow-left me-1"></i> All Categories</a>';
            categoryPath.forEach((cat, index) => {
                breadcrumbHTML += `<a style="margin-right:3px;padding:5px;background-color: #000000;color:white;border-radius:5px;" role="button" onclick="goBackToLevel(${index})">${cat.name}</a>`;
            });
            breadcrumbLinks.innerHTML = breadcrumbHTML;
        } else {
            categoryBreadcrumb.style.display = 'none';
        }

        // Render category list
        let html = '';
        categoriesToShow.forEach(cat => {
            const hasChildren = cat.subcategories && cat.subcategories.length > 0;
            const isSelected = selectedCategorySlug.value === cat.slug;
            const productCount = cat.product_count || 0;

            html += `
                <li class="category-item ${isSelected ? 'selected' : ''}" data-category-id="${cat.id}">
                    <input type="radio"
                           name="category_radio"
                           id="cat_${cat.id}"
                           value="${cat.slug}"
                           ${isSelected ? 'checked' : ''}
                           onchange="selectCategory('${cat.slug}')">
                    <label for="cat_${cat.id}">
                        <span>${cat.name} <small class="text-muted">(${productCount.toLocaleString()})</small></span>
                        ${hasChildren ? `<span class="expand-icon" onclick="expandCategory(event, ${cat.id})">▶</span>` : ''}
                    </label>
                </li>
            `;
        });

        categoryList.innerHTML = html || '<li class="text-muted">No categories found</li>';
    }

    // Select category
    window.selectCategory = function(slug) {
        selectedCategorySlug.value = slug;

        // Update selected styling
        document.querySelectorAll('.category-item').forEach(item => {
            item.classList.remove('selected');
        });
        const selectedItem = document.querySelector(`input[value="${slug}"]`)?.closest('.category-item');
        if (selectedItem) {
            selectedItem.classList.add('selected');
        }

        checkAndLoadStats();
    };

    // Expand category to show children
    window.expandCategory = function(event, categoryId) {
        event.stopPropagation();
        const category = categoryMap[categoryId];
        if (category && category.subcategories && category.subcategories.length > 0) {
            categoryPath.push(category);
            renderCategories();
        }
    };

    // Go back to all categories
    window.goBackToAll = function() {
        categoryPath = [];
        renderCategories();
    };

    // Go back to specific level
    window.goBackToLevel = function(index) {
        categoryPath = categoryPath.slice(0, index + 1);
        renderCategories();
    };

    // Function to get excluded delivery texts
    function getExcludedDeliveryTexts() {
        const excluded = [];
        deliveryCheckboxes.forEach(cb => {
            if (cb.checked) {
                excluded.push(cb.value);
            }
        });
        return excluded;
    }

    // Store selection change
    storeSelect.addEventListener('change', () => {
        if (selectedCategorySlug.value || regionFilterSelect.value) {
            checkAndLoadStats();
        }
    });

    // Delivery checkbox change
    deliveryCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            if (selectedCategorySlug.value || regionFilterSelect.value) {
                checkAndLoadStats();
            }
        });
    });

    // Promotion filter change
    promotionFilterSelect.addEventListener('change', () => {
        if (selectedCategorySlug.value || regionFilterSelect.value) {
            checkAndLoadStats();
        }
    });

    // Region-to-currency mapping
    const regionCurrencyMap = {
        'zambia_only':   'ZMW',
        'zimbabwe_only': 'USD',
        'sa_only':       'ZAR',
        'global':        'USD',
    };

    function setCurrencyForRegion(region) {
        const code = regionCurrencyMap[region];
        if (!code) return;
        // Set both currency selects
        [currencySelect, currencySkuSelect].forEach(sel => {
            const opt = Array.from(sel.options).find(o => o.value === code);
            if (opt) sel.value = code;
        });
    }

    // Region filter change
    regionFilterSelect.addEventListener('change', () => {
        setCurrencyForRegion(regionFilterSelect.value);
        checkAndLoadStats();
    });

    // Price range change
    let priceRangeTimer = null;
    function onPriceRangeChange() {
        clearTimeout(priceRangeTimer);
        priceRangeTimer = setTimeout(() => {
            if (selectedCategorySlug.value || regionFilterSelect.value) {
                checkAndLoadStats();
            }
        }, 600);
    }
    priceMinInput.addEventListener('input', onPriceRangeChange);
    priceMaxInput.addEventListener('input', onPriceRangeChange);

    // Currency change
    currencySelect.addEventListener('change', checkAndLoadStats);

    // Check and load stats
    function checkAndLoadStats() {
        const storeId = storeSelect.value;
        const excludedDelivery = getExcludedDeliveryTexts();
        const category = selectedCategorySlug.value;
        const currency = currencySelect.value;
        const promotionFilter = promotionFilterSelect.value;
        const region = regionFilterSelect.value;
        const priceMin = priceMinInput.value;
        const priceMax = priceMaxInput.value;

        resetStats();

        if (!currency || (!category && !region)) {
            return;
        }

        loadingStats.style.display = 'block';

        const urlParts = [];
        if (category) urlParts.push(`category=${encodeURIComponent(category)}`);
        if (storeId)  urlParts.push(`store_id=${encodeURIComponent(storeId)}`);
        if (region)   urlParts.push(`region=${encodeURIComponent(region)}`);
        if (excludedDelivery.length > 0) urlParts.push(`exclude_delivery=${encodeURIComponent(JSON.stringify(excludedDelivery))}`);
        if (promotionFilter) urlParts.push(`promotion_filter=${encodeURIComponent(promotionFilter)}`);
        if (priceMin !== '') urlParts.push(`price_min=${encodeURIComponent(priceMin)}`);
        if (priceMax !== '') urlParts.push(`price_max=${encodeURIComponent(priceMax)}`);
        const url = `{{ route('admin.product-feed.stats') }}?${urlParts.join('&')}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                loadingStats.style.display = 'none';

                if (data.success) {
                    currentStats = data;
                    let statsMessage = data.category && data.category !== 'All Products'
                        ? `Found ${data.product_count.toLocaleString()} product(s) in "${data.category}" category`
                        : `Found ${data.product_count.toLocaleString()} product(s)`;
                    if (excludedDelivery.length > 0) {
                        statsMessage += ` (excluding ${excludedDelivery.length} delivery type(s))`;
                    }
                    if (promotionFilter) {
                        const filterLabels = {
                            'sale_price': 'has sale price',
                            'sale_enabled': 'sale enabled',
                            'both': 'sale price AND sale enabled',
                            'either': 'sale price OR sale enabled'
                        };
                        statsMessage += ` — filter: ${filterLabels[promotionFilter] || promotionFilter}`;
                    }
                    if (priceMin !== '' || priceMax !== '') {
                        statsMessage += ` — price: ${priceMin || '0'} – ${priceMax || '∞'}`;
                    }
                    statsText.textContent = statsMessage;
                    statsContainer.style.display = 'block';

                    if (data.will_split) {
                        splitText.textContent = `Large export detected! Files will be split into ${data.estimated_parts} parts (~${data.estimated_size_mb} MB total). You'll download a ZIP archive containing multiple files.`;
                        splitWarning.style.display = 'block';
                    }

                    if (data.product_count > 0) {
                        downloadXmlBtn.disabled = false;
                        downloadTsvBtn.disabled = false;
                        downloadCsvBtn.disabled = false;
                    } else {
                        statsText.textContent = `No products found in "${data.category}" category with current filters`;
                    }
                } else {
                    showError('Error', data.message);
                }
            })
            .catch(error => {
                loadingStats.style.display = 'none';
                console.error('Error:', error);
                showError('Error', 'Failed to load product statistics. Please try again.');
            });
    }

    function resetStats() {
        downloadXmlBtn.disabled = true;
        downloadTsvBtn.disabled = true;
        downloadCsvBtn.disabled = true;
        statsContainer.style.display = 'none';
        splitWarning.style.display = 'none';
        loadingStats.style.display = 'none';
        currentStats = null;
    }

    // Helper: build export URL params
    function buildExportParams() {
        const storeId = storeSelect.value;
        const excludedDelivery = getExcludedDeliveryTexts();
        const promotionFilter = promotionFilterSelect.value;
        const region = regionFilterSelect.value;
        const priceMin = priceMinInput.value;
        const priceMax = priceMaxInput.value;

        // Get currency based on mode
        const currency = exportMode === 'sku' ? currencySkuSelect.value : currencySelect.value;

        let params = `?currency=${encodeURIComponent(currency)}`;

        if (exportMode === 'sku') {
            // SKU mode - send SKU list
            const skuList = skuListTextarea.value
                .split('\n')
                .map(s => s.trim())
                .filter(s => s.length > 0);
            params += `&sku_list=${encodeURIComponent(JSON.stringify(skuList))}`;
        } else {
            // Category mode — only include category if one is selected
            const category = selectedCategorySlug.value;
            if (category) params += `&category=${encodeURIComponent(category)}`;
        }

        if (storeId) params += `&store_id=${encodeURIComponent(storeId)}`;
        if (region) params += `&region=${encodeURIComponent(region)}`;
        if (excludedDelivery.length > 0) {
            params += `&exclude_delivery=${encodeURIComponent(JSON.stringify(excludedDelivery))}`;
        }
        if (promotionFilter) {
            params += `&promotion_filter=${encodeURIComponent(promotionFilter)}`;
        }
        if (priceMin !== '') {
            params += `&price_min=${encodeURIComponent(priceMin)}`;
        }
        if (priceMax !== '') {
            params += `&price_max=${encodeURIComponent(priceMax)}`;
        }
        return params;
    }

    // Download handlers — check size first, warn if too large
    downloadXmlBtn.addEventListener('click', async () => {
        if (exportMode === 'category' && !currentStats && !regionFilterSelect.value) return;

        // Get currency from correct select based on mode
        const currency = exportMode === 'sku' ? currencySkuSelect.value : currencySelect.value;

        // Validate currency
        if (!currency) {
            showWarning('Please select a currency');
            return;
        }

        // Validate SKU list if in SKU mode
        if (exportMode === 'sku') {
            const skuList = skuListTextarea.value.trim();
            if (!skuList) {
                showWarning('Please enter at least one SKU');
                return;
            }
            const skuCount = skuList.split('\n').filter(s => s.trim().length > 0).length;
            if (skuCount === 0) {
                showWarning('Please enter at least one valid SKU');
                return;
            }
        }

        // Warn for large category exports
        if (exportMode === 'category' && currentStats && currentStats.product_count > 10000) {
            const _r = await Swal.fire({ title: 'Are you sure?', text: `Large Export Detected! This category has ${currentStats.product_count.toLocaleString()} products. For categories with more than 10,000 products, the export may timeout. Recommendation: Select a SUBCATEGORY instead.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (!_r.isConfirmed) {
                return;
            }
        }

        const params = buildExportParams();


        // Show loading indicator
        downloadXmlBtn.disabled = true;
        downloadXmlBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Generating... (please wait)';

        // Direct download - server writes file then returns it
        window.location.href = `{{ route('admin.product-feed.download-xml') }}${params}`;

        // Re-enable after timeout
        const timeout = (exportMode === 'category' && currentStats && currentStats.product_count > 10000) ? 10000 : 3000;
        setTimeout(() => {
            downloadXmlBtn.disabled = false;
            downloadXmlBtn.innerHTML = '<i class="bi bi-file-earmark-code me-1"></i>Download XML';
        }, timeout);
    });

    downloadTsvBtn.addEventListener('click', async () => {
        if (exportMode === 'category' && !currentStats && !regionFilterSelect.value) return;

        // Get currency from correct select based on mode
        const currency = exportMode === 'sku' ? currencySkuSelect.value : currencySelect.value;

        // Validate currency
        if (!currency) {
            showWarning('Please select a currency');
            return;
        }

        // Validate SKU list if in SKU mode
        if (exportMode === 'sku') {
            const skuList = skuListTextarea.value.trim();
            if (!skuList) {
                showWarning('Please enter at least one SKU');
                return;
            }
            const skuCount = skuList.split('\n').filter(s => s.trim().length > 0).length;
            if (skuCount === 0) {
                showWarning('Please enter at least one valid SKU');
                return;
            }
        }

        // Warn for large category exports
        if (exportMode === 'category' && currentStats && currentStats.product_count > 10000) {
            const _r = await Swal.fire({ title: 'Are you sure?', text: `Large Export Detected! This category has ${currentStats.product_count.toLocaleString()} products. For categories with more than 10,000 products, the export may timeout. Recommendation: Select a SUBCATEGORY instead.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (!_r.isConfirmed) {
                return;
            }
        }

        const params = buildExportParams();

        // Show loading indicator
        downloadTsvBtn.disabled = true;
        downloadTsvBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Generating... (please wait)';

        // Direct download - server writes file then returns it
        window.location.href = `{{ route('admin.product-feed.download-tsv') }}${params}`;

        // Re-enable after timeout
        const timeout = (exportMode === 'category' && currentStats && currentStats.product_count > 10000) ? 10000 : 3000;
        setTimeout(() => {
            downloadTsvBtn.disabled = false;
            downloadTsvBtn.innerHTML = '<i class="bi bi-file-earmark-spreadsheet me-1"></i>Download TSV';
        }, timeout);
    });

    downloadCsvBtn.addEventListener('click', async () => {
        if (exportMode === 'category' && !currentStats && !regionFilterSelect.value) return;

        // Get currency from correct select based on mode
        const currency = exportMode === 'sku' ? currencySkuSelect.value : currencySelect.value;

        // Validate currency
        if (!currency) {
            showWarning('Please select a currency');
            return;
        }

        // Validate SKU list if in SKU mode
        if (exportMode === 'sku') {
            const skuList = skuListTextarea.value.trim();
            if (!skuList) {
                showWarning('Please enter at least one SKU');
                return;
            }
            const skuCount = skuList.split('\n').filter(s => s.trim().length > 0).length;
            if (skuCount === 0) {
                showWarning('Please enter at least one valid SKU');
                return;
            }
        }

        // Warn for large category exports
        if (exportMode === 'category' && currentStats && currentStats.product_count > 10000) {
            const _r = await Swal.fire({ title: 'Are you sure?', text: `Large Export Detected! This category has ${currentStats.product_count.toLocaleString()} products. For categories with more than 10,000 products, the export may timeout. Recommendation: Select a SUBCATEGORY instead.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (!_r.isConfirmed) {
                return;
            }
        }

        const params = buildExportParams();


        // Show loading indicator
        downloadCsvBtn.disabled = true;
        downloadCsvBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Generating... (please wait)';

        // Direct download - server writes file then returns it
        window.location.href = `{{ route('admin.product-feed.download-csv') }}${params}`;

        // Re-enable after timeout
        const timeout = (exportMode === 'category' && currentStats && currentStats.product_count > 10000) ? 10000 : 3000;
        setTimeout(() => {
            downloadCsvBtn.disabled = false;
            downloadCsvBtn.innerHTML = '<i class="bi bi-file-earmark-excel me-1"></i>Download CSV';
        }, timeout);
    });

    // Load categories on page load
    document.addEventListener('DOMContentLoaded', loadCategories);
</script>
@endpush
