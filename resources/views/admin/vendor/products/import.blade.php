@extends('admin.layout')

@section('title', 'Import Products - Admin Panel')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-file-upload"></i> Import Products
                    </h4>
                    <a href="{{ route('admin.vendor.products.import.template') }}" class="btn btn-success">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- IMPORTANT: Existing Categories Browser -->
                    <div class="card border-warning mb-3">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>IMPORTANT: Use Existing Categories First!</strong>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning mb-3">
                                <strong>⚠️ Before creating new categories, please check if the category already exists below!</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Copy the exact category path from the list below</li>
                                    <li>Paste it into your CSV under the "categories" column</li>
                                    <li>Creating duplicate categories requires admin approval and causes delays</li>
                                </ul>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <h6><i class="fas fa-search"></i> Search Categories:</h6>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text"
                                               id="categorySearch"
                                               class="form-control"
                                               placeholder="Type to search from {{ count($categories) }} categories... (e.g., 'furniture', 'electronics', 'sport')"
                                               autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                    </div>
                                    <div id="searchStats" class="mb-2" style="display: none;">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i>
                                            Showing <strong id="visibleCount">0</strong> of <strong>{{ count($categories) }}</strong> categories
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <strong id="categoriesHeader">Loading Categories...</strong>
                                    <small class="text-muted">Scroll to browse all categories</small>
                                </div>
                                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                                    <div id="categoriesList">
                                        <!-- Loading indicator -->
                                        <div id="categoriesLoading" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-3 text-muted">Loading 5000+ categories...</p>
                                        </div>

                                        <!-- Categories table (populated via AJAX) -->
                                        <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem; display: none;" id="categoriesTable">
                                            <thead class="sticky-top bg-white shadow-sm">
                                                <tr>
                                                    <th width="65%" class="small">Category Path (Copy This to CSV)</th>
                                                    <th width="15%" class="small">ID</th>
                                                    <th width="20%" class="small">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="categoryTableBody">
                                                <!-- Will be populated via AJAX -->
                                            </tbody>
                                        </table>

                                        <!-- Error message -->
                                        <div id="categoriesError" class="alert alert-danger m-3" style="display: none;">
                                            <strong>Error loading categories:</strong>
                                            <p id="categoriesErrorMessage"></p>
                                            <button class="btn btn-sm btn-primary" onclick="loadCategories()">
                                                <i class="fas fa-redo"></i> Retry
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mt-3 mb-0" style="font-size: 0.9rem;">
                                <strong><i class="fas fa-lightbulb"></i> Pro Tips:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Use the search box above to quickly find categories</li>
                                    <li>You can use category IDs instead of paths. For example: <code>5,12,24</code></li>
                                    <li>Copy exact paths to avoid creating duplicates</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Comprehensive Import Instructions</h5>
                        <ul class="mb-0">
                            <li><strong>Download Template:</strong> Click "Download Template" for a complete CSV with all fields</li>
                            <li><strong>Required Fields:</strong> name, price (all other fields are optional)</li>
                            <li><strong>Images:</strong> Use full URLs from your image server (comma-separated for multiple)</li>
                            <li><strong>⚠️ CATEGORIES - IMPORTANT:</strong>
                                <ul>
                                    <li><strong class="text-danger">Step 1: CHECK THE YELLOW BOX ABOVE FIRST!</strong> Copy existing category paths</li>
                                    <li><strong class="text-danger">Step 2: Use the exact path or category ID from the list</strong></li>
                                    <li>Format: "Parent > Child > Grandchild" (e.g., "Furniture > Living Room > Sofas")</li>
                                    <li>Or use category IDs: "5,12,24"</li>
                                    <li>Multiple categories: Separate with commas</li>
                                    <li class="text-warning"><strong>Creating new categories requires admin approval and causes delays!</strong></li>
                                </ul>
                            </li>
                            <li><strong>Gallery Images:</strong> Separate URLs with commas (e.g., "url1.jpg,url2.jpg,url3.jpg")</li>
                            <li><strong>Tags:</strong> Comma-separated, auto-created if not exists</li>
                            <li><strong>Pricing:</strong> Use decimal format (e.g., 99.99)</li>
                            <li><strong>Boolean Fields:</strong> Use 1 for Yes, 0 for No (status, is_featured, etc.)</li>
                            @if($isVendor)
                            <li class="text-danger"><strong>Vendor Note:</strong> All products will be inactive and require admin approval.</li>
                            @endif
                        </ul>
                    </div>

                    <!-- Field Reference -->
                    <div class="accordion mb-3" id="fieldReference">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fieldsCollapse">
                                    <i class="fas fa-book"></i> &nbsp; View Complete Field Reference
                                </button>
                            </h2>
                            <div id="fieldsCollapse" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Field</th>
                                                    <th>Type</th>
                                                    <th>Description</th>
                                                    <th>Example</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td colspan="4" class="table-primary"><strong>BASIC INFORMATION</strong></td></tr>
                                                <tr><td>name *</td><td>Text</td><td>Product name</td><td>Modern Leather Sofa</td></tr>
                                                <tr><td>sku</td><td>Text</td><td>Stock keeping unit (unique)</td><td>SOFA-001</td></tr>
                                                <tr><td>short_description</td><td>Text</td><td>Brief description</td><td>Luxurious 3-seater...</td></tr>
                                                <tr><td>description</td><td>Text</td><td>Full description (can include HTML)</td><td>This premium leather...</td></tr>
                                                <tr><td>type</td><td>simple/classified</td><td>Product type</td><td>simple</td></tr>
                                                <tr><td>unit</td><td>Text</td><td>Unit of measurement</td><td>piece, kg, meter</td></tr>
                                                <tr><td>weight</td><td>Number</td><td>Weight in kg</td><td>85</td></tr>

                                                <tr><td colspan="4" class="table-primary"><strong>PRICING</strong></td></tr>
                                                <tr><td>price *</td><td>Decimal</td><td>Regular price</td><td>1299.99</td></tr>
                                                <tr><td>sale_price</td><td>Decimal</td><td>Sale price</td><td>999.99</td></tr>
                                                <tr><td>discount</td><td>Number</td><td>Discount percentage</td><td>23</td></tr>

                                                <tr><td colspan="4" class="table-primary"><strong>INVENTORY</strong></td></tr>
                                                <tr><td>quantity</td><td>Number</td><td>Stock quantity</td><td>15</td></tr>
                                                <tr><td>stock_status</td><td>Text</td><td>in_stock or out_of_stock</td><td>in_stock</td></tr>

                                                <tr><td colspan="4" class="table-primary"><strong>CATEGORIES & TAGS</strong></td></tr>
                                                <tr><td>categories</td><td>Text</td><td>Hierarchical: "Parent > Child" or IDs/names</td><td>Furniture > Living Room > Sofas</td></tr>
                                                <tr><td>tags</td><td>Text</td><td>Comma-separated tags</td><td>luxury,modern,leather</td></tr>

                                                <tr><td colspan="4" class="table-primary"><strong>IMAGES (URLs)</strong></td></tr>
                                                <tr><td>thumbnail_url</td><td>URL</td><td>Main product image URL</td><td>https://cdn.example.com/img.jpg</td></tr>
                                                <tr><td>gallery_urls</td><td>URLs</td><td>Comma-separated gallery URLs</td><td>url1.jpg,url2.jpg,url3.jpg</td></tr>

                                                <tr><td colspan="4" class="table-primary"><strong>SHIPPING</strong></td></tr>
                                                <tr><td>shipping_days</td><td>Number</td><td>Default shipping days</td><td>7</td></tr>
                                                <tr><td>is_free_shipping</td><td>0/1</td><td>Free shipping</td><td>1</td></tr>
                                                <tr><td>has_expedited_shipping</td><td>0/1</td><td>Expedited option</td><td>1</td></tr>
                                                <tr><td>standard_shipping_days</td><td>Text</td><td>Standard delivery time</td><td>7-10</td></tr>
                                                <tr><td>expedited_shipping_days</td><td>Text</td><td>Expedited delivery time</td><td>3-5</td></tr>
                                                <tr><td>standard_shipping_price</td><td>Decimal</td><td>Standard shipping cost</td><td>49.99</td></tr>
                                                <tr><td>expedited_shipping_price</td><td>Decimal</td><td>Expedited shipping cost</td><td>89.99</td></tr>

                                                <tr><td colspan="4" class="table-primary"><strong>FEATURES</strong></td></tr>
                                                <tr><td>is_featured</td><td>0/1</td><td>Featured product</td><td>1</td></tr>
                                                <tr><td>is_trending</td><td>0/1</td><td>Trending product</td><td>1</td></tr>
                                                <tr><td>is_return</td><td>0/1</td><td>Returnable</td><td>1</td></tr>
                                                <tr><td>is_cod</td><td>0/1</td><td>Cash on delivery</td><td>0</td></tr>

                                                <tr><td colspan="4" class="table-primary"><strong>SEO</strong></td></tr>
                                                <tr><td>meta_title</td><td>Text</td><td>SEO title</td><td>Modern Leather Sofa - Buy Now</td></tr>
                                                <tr><td>meta_description</td><td>Text</td><td>SEO description</td><td>Buy premium modern leather sofa...</td></tr>

                                                <tr><td colspan="4" class="table-primary"><strong>STATUS</strong></td></tr>
                                                <tr><td>status</td><td>0/1</td><td>Active/Inactive (vendors: auto-set to 0)</td><td>1</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-muted small">* Required fields | All other fields are optional</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <form id="importForm" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency_id" class="form-label">
                                        <i class="fas fa-dollar-sign"></i> Select Currency of Your Prices *
                                    </label>
                                    <select class="form-select"
                                            id="currency_id"
                                            name="currency_id"
                                            required>
                                        <option value="">-- Select Currency --</option>
                                        @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                                data-code="{{ $currency->code }}"
                                                data-symbol="{{ $currency->symbol }}"
                                                data-rate="{{ $currency->exchange_rate }}">
                                            {{ $currency->code }} ({{ $currency->symbol }}) - Rate: {{ $currency->exchange_rate }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> Select the currency used in your CSV file.
                                        Prices will be automatically converted to USD for storage.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="file" class="form-label">Select CSV or Excel File *</label>
                                    <input type="file"
                                           class="form-control"
                                           id="file"
                                           name="file"
                                           accept=".csv,.xlsx,.xls"
                                           required>
                                    <small class="text-muted">Supported formats: CSV, Excel (.xlsx, .xls). Max size: 10MB</small>
                                </div>
                            </div>
                        </div>

                        <!-- Currency Conversion Preview -->
                        <div id="conversionPreview" class="alert alert-info" style="display: none;">
                            <h6><i class="fas fa-exchange-alt"></i> Currency Conversion Preview</h6>
                            <p class="mb-0">
                                Your prices in <strong id="previewCurrency">---</strong> will be converted to USD.<br>
                                Example: <span id="previewExample"></span>
                            </p>
                        </div>

                        @if($isAdmin && !$isVendor)
                        <div class="mb-3">
                            <label for="store_id" class="form-label">Select Store</label>
                            <select class="form-select" id="store_id" name="store_id">
                                <option value="1">Default Store</option>
                                <!-- You can add more stores here -->
                            </select>
                        </div>
                        @endif

                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <span id="btnText">
                                <i class="fas fa-upload"></i> Upload and Import
                            </span>
                            <span id="btnSpinner" style="display: none;">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Importing...
                            </span>
                        </button>
                    </form>

                    <!-- Success Message -->
                    <div id="successMessage" class="alert alert-success mt-3" style="display: none;">
                        <h5 class="alert-heading">
                            <i class="fas fa-check-circle"></i> Import Completed Successfully!
                        </h5>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>✅ Imported:</strong> <span id="importedCount" class="badge bg-success">0</span> products</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>❌ Failed:</strong> <span id="failedCount" class="badge bg-danger">0</span> products</p>
                            </div>
                        </div>
                    </div>

                    <!-- Live Import Terminal -->
                    <div class="card mt-4" id="importTerminal" style="display: none;">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-terminal"></i> Live Import Progress
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <iframe id="importIframe"
                                    name="importIframe"
                                    style="width: 100%; height: 400px; border: none; background: #1e1e1e; color: #00ff00; font-family: 'Courier New', monospace; padding: 15px; overflow-y: auto;">
                            </iframe>
                        </div>
                    </div>

                    <!-- Simple Results Display (hidden, replaced by terminal) -->
                    <div id="importResults" class="mt-4" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .progress {
        height: 30px;
    }
    .progress-bar {
        font-size: 16px;
        line-height: 30px;
    }

    /* Terminal styling */
    #importIframe {
        background: #1e1e1e !important;
        color: #00ff00;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.6;
    }
</style>

<script>
// Currency conversion preview
document.getElementById('currency_id')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const previewDiv = document.getElementById('conversionPreview');

    if (this.value) {
        const code = selectedOption.dataset.code;
        const symbol = selectedOption.dataset.symbol;
        const rate = parseFloat(selectedOption.dataset.rate);

        // Show preview
        document.getElementById('previewCurrency').textContent = code;

        // Calculate example conversion
        const examplePrice = 100;
        const convertedPrice = (examplePrice / rate).toFixed(2);

        document.getElementById('previewExample').innerHTML =
            `<strong>${symbol}${examplePrice.toFixed(2)}</strong> ${code} = <strong>$${convertedPrice}</strong> USD`;

        previewDiv.style.display = 'block';
    } else {
        previewDiv.style.display = 'none';
    }
});

// Load categories via AJAX
let totalCategories = 0;

function loadCategories() {
    const loadingDiv = document.getElementById('categoriesLoading');
    const errorDiv = document.getElementById('categoriesError');
    const table = document.getElementById('categoriesTable');
    const tbody = document.getElementById('categoryTableBody');
    const header = document.getElementById('categoriesHeader');

    // Show loading
    loadingDiv.style.display = 'block';
    errorDiv.style.display = 'none';
    table.style.display = 'none';

    fetch('{{ route("admin.vendor.products.import.categories") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                totalCategories = data.count;

                // Update header
                header.textContent = `Available Categories (${totalCategories} total)`;

                // Build table rows
                let html = '';
                data.categories.forEach(category => {
                    const level = category.level || 0;
                    const padding = level * 15;

                    let icon = '<i class="fas fa-file text-secondary" style="font-size: 0.65rem;"></i>';
                    if (level === 0) {
                        icon = '<i class="fas fa-folder text-primary" style="font-size: 0.7rem;"></i>';
                    } else if (level === 1) {
                        icon = '<i class="fas fa-folder-open text-info" style="font-size: 0.7rem;"></i>';
                    }

                    html += `
                        <tr class="category-row" data-path="${category.path.toLowerCase()}" data-id="${category.id}">
                            <td class="py-1">
                                <span style="padding-left: ${padding}px; font-size: 0.8rem;">
                                    ${icon}
                                    <span class="category-path">${category.path}</span>
                                </span>
                            </td>
                            <td class="py-1">
                                <code style="font-size: 0.75rem;">${category.id}</code>
                            </td>
                            <td class="py-1">
                                <button class="btn btn-sm btn-success copy-btn"
                                        data-path="${category.path}"
                                        title="Copy to clipboard"
                                        style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                    <i class="fas fa-copy" style="font-size: 0.7rem;"></i> Copy
                                </button>
                            </td>
                        </tr>
                    `;
                });

                tbody.innerHTML = html;

                // Show table, hide loading
                table.style.display = 'table';
                loadingDiv.style.display = 'none';

                // Attach copy button listeners
                attachCopyButtons();

            } else {
                // Show error
                document.getElementById('categoriesErrorMessage').textContent = data.message || 'Unknown error';
                errorDiv.style.display = 'block';
                loadingDiv.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Failed to load categories:', error);
            document.getElementById('categoriesErrorMessage').textContent = error.message;
            errorDiv.style.display = 'block';
            loadingDiv.style.display = 'none';
        });
}

// Attach copy button listeners
function attachCopyButtons() {
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const path = this.dataset.path;

            navigator.clipboard.writeText(path).then(() => {
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copied!';
                this.classList.remove('btn-success');
                this.classList.add('btn-primary');

                setTimeout(() => {
                    this.innerHTML = originalHtml;
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-success');
                }, 2000);
            }).catch(err => {
                console.error('Copy failed:', err);
                showError('Error', 'Failed to copy to clipboard');
            });
        });
    });
}

// Load categories on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
});

// Category search functionality with performance optimization
let searchTimeout;
const categorySearch = document.getElementById('categorySearch');
const clearSearchBtn = document.getElementById('clearSearch');
const searchStats = document.getElementById('searchStats');
const visibleCountElement = document.getElementById('visibleCount');
// totalCategories is set by loadCategories() function

categorySearch?.addEventListener('input', function(e) {
    // Clear previous timeout for debouncing
    clearTimeout(searchTimeout);

    // Debounce search for better performance with 5000+ categories
    searchTimeout = setTimeout(() => {
        const searchTerm = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.category-row');
        let visibleCount = 0;

        // Show all if search is empty
        if (searchTerm === '') {
            rows.forEach(row => {
                row.style.display = '';
            });
            searchStats.style.display = 'none';
            clearSearchBtn.style.display = 'none';

            // Remove no results message
            const noResultsMsg = document.getElementById('noResultsMsg');
            if (noResultsMsg) {
                noResultsMsg.remove();
            }
            return;
        }

        // Show clear button when searching
        clearSearchBtn.style.display = 'block';

        // Perform search
        rows.forEach(row => {
            const path = row.dataset.path;
            const id = row.dataset.id;

            // Search in both path and ID
            if (path.includes(searchTerm) || id.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update stats
        visibleCountElement.textContent = visibleCount;
        searchStats.style.display = 'block';

        // Show "no results" message
        const tbody = document.getElementById('categoryTableBody');
        let noResultsMsg = document.getElementById('noResultsMsg');

        if (visibleCount === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('tr');
                noResultsMsg.id = 'noResultsMsg';
                noResultsMsg.innerHTML = '<td colspan="3" class="text-center text-muted py-4"><i class="fas fa-search"></i> No categories found matching "<strong>' + e.target.value + '</strong>"<br><small>Try different keywords or clear the search</small></td>';
                tbody.appendChild(noResultsMsg);
            } else {
                noResultsMsg.innerHTML = '<td colspan="3" class="text-center text-muted py-4"><i class="fas fa-search"></i> No categories found matching "<strong>' + e.target.value + '</strong>"<br><small>Try different keywords or clear the search</small></td>';
            }
        } else {
            if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }
    }, 300); // 300ms debounce delay
});

// Clear search functionality
clearSearchBtn?.addEventListener('click', function() {
    categorySearch.value = '';
    const event = new Event('input');
    categorySearch.dispatchEvent(event);
    categorySearch.focus();
});

// Focus search on Ctrl+F or Cmd+F
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        categorySearch?.focus();
    }
});

// Copy to clipboard functionality
document.querySelectorAll('.copy-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const path = this.dataset.path;

        // Copy to clipboard
        navigator.clipboard.writeText(path).then(() => {
            // Visual feedback
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Copied!';
            this.classList.remove('btn-success');
            this.classList.add('btn-primary');

            setTimeout(() => {
                this.innerHTML = originalHtml;
                this.classList.remove('btn-primary');
                this.classList.add('btn-success');
            }, 2000);
        }).catch(err => {
            showWarning('Warning', 'Failed to copy. Please copy manually: ' + path);
        });
    });
});

// Product import form handling - IFRAME STREAMING APPROACH (like fast import)
document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const uploadBtn = document.getElementById('uploadBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    const importTerminal = document.getElementById('importTerminal');
    const importResults = document.getElementById('importResults');
    const importIframe = document.getElementById('importIframe');
    const successMessage = document.getElementById('successMessage');

    // Validate required fields
    const fileInput = document.getElementById('file');
    const currencySelect = document.getElementById('currency_id');

    if (!fileInput.files.length) {
        showWarning('Warning', 'Please select a file to upload');
        return;
    }

    if (!currencySelect.value) {
        showWarning('Warning', 'Please select a currency');
        return;
    }

    // Hide old results
    importResults.style.display = 'none';
    importResults.innerHTML = '';
    successMessage.style.display = 'none';

    // Show terminal
    importTerminal.style.display = 'block';

    // Clear previous iframe content
    importIframe.srcdoc = '<html><body style="background: #1e1e1e; color: #00ff00; font-family: monospace; padding: 15px; margin: 0;">Starting import...</body></html>';

    // Show loading spinner on button
    btnText.style.display = 'none';
    btnSpinner.style.display = 'inline-block';
    uploadBtn.disabled = true;

    // Set form target to iframe and submit
    this.target = 'importIframe';
    this.action = '{{ route('admin.vendor.products.import.upload') }}';
    this.method = 'POST';

    // Submit form (will load streaming response in iframe)
    this.submit();

    // Monitor iframe for completion
    let checkInterval = setInterval(function() {
        try {
            const iframeDoc = importIframe.contentDocument || importIframe.contentWindow.document;
            const iframeBody = iframeDoc.body.innerText || iframeDoc.body.textContent;

            // Check if import is complete
            if (iframeBody.includes('Import finished successfully') || iframeBody.includes('Import completed')) {
                clearInterval(checkInterval);

                // Extract statistics from the output
                let imported = 0;
                let failed = 0;

                const importedMatch = iframeBody.match(/Imported:\s*(\d+)/i);
                const failedMatch = iframeBody.match(/Failed:\s*(\d+)/i);

                if (importedMatch) imported = parseInt(importedMatch[1]);
                if (failedMatch) failed = parseInt(failedMatch[1]);

                // Show success message
                document.getElementById('importedCount').textContent = imported;
                document.getElementById('failedCount').textContent = failed;
                successMessage.style.display = 'block';

                // Restore button
                btnText.style.display = 'inline-block';
                btnSpinner.style.display = 'none';
                uploadBtn.disabled = false;

                // Scroll to success message
                successMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Check for errors
            if (iframeBody.includes('Import failed') || iframeBody.includes('Error')) {
                clearInterval(checkInterval);

                // Restore button
                btnText.style.display = 'inline-block';
                btnSpinner.style.display = 'none';
                uploadBtn.disabled = false;
            }
        } catch (e) {
            // Can't access iframe yet (different origin or loading)
            // This is normal during loading
        }
    }, 1000); // Check every second

    // Scroll to terminal
    importTerminal.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>
@endsection

