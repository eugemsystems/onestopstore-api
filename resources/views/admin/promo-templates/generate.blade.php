<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Flyer - {{ $template->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }
        .full-height-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header-bar {
            background: white;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .main-layout {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
            overflow: hidden;
        }
        .controls-bar {
            background: white;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .template-frame {
            flex: 1;
            background: white;
            overflow: auto;
            padding: 0;
        }
        #template-container {
            min-height: 100%;
        }
        .right-panel {
            width: 400px;
            background: white;
            border-left: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .right-panel-header {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        .product-search {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .product-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }
        .product-item {
            display: flex;
            gap: 10px;
            padding: 10px;
            margin-bottom: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }
        .product-item:hover {
            border-color: #0d6efd;
            box-shadow: 0 2px 8px rgba(13,110,253,0.15);
        }
        .product-item.selected {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        .product-item img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 4px;
            background: #f8f9fa;
        }
        .product-item-info {
            flex: 1;
            min-width: 0;
        }
        .product-item-name {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .product-item-sku {
            font-size: 11px;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .product-item-price {
            font-size: 12px;
            font-weight: 700;
            color: #198754;
        }
        .product-item-price.sale {
            color: #dc3545;
        }
        .product-item-price .old-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 11px;
            margin-right: 5px;
        }
        .selected-count {
            display: inline-block;
            padding: 4px 12px;
            background: #0d6efd;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
        }
        .spinner-border {
            width: 1rem;
            height: 1rem;
        }
        /* Hide template's built-in toolbar */
        #template-container .toolbar {
            display: none !important;
        }
        #template-container .page-wrap {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        .no-products {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        /* Layby Banner */
        .layby-banner {
            position: absolute;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #a21c1c 0%, #e70202 100% 100%);
            color: white;
            padding: 8px 24px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            z-index: 10;
            display: flex !important;
            align-items: center;
            gap: 8px;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .layby-banner i {
            font-size: 16px;
        }
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            }
            50% {
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.6);
            }
        }
        @media print {
            .layby-banner {
                display: flex !important;
                visibility: visible !important;
            }
        }
        /* Ensure template container captures all children including absolute positioned elements */
        #template-container {
            position: relative;
            overflow: visible;
            padding-top: 0; /* Make space for the banner */
            min-height: 100%;
        }
    </style>

    <script>
        // 1) Define your market contacts here
        const MARKET_CONTACTS = {
            ZW: {
                address: "Shop No. 6 Rhodesville Shops.No 32 Rhodesville Avenue Greendale, Harare",
                email: "admin@raines.africa",
                phones: ["+263 77 941 1028", "+263 71 716 8255"]
            },
            ZM: {
                address: "Niyati Plaza, Kalingalinga Area, 35235 Alick Nkhata Rd, Lusaka,Zambia",
                email: "admin@raines.africa",
                phones: ["+260 77 726 5389", "+260 76 591 4363"]
            },
            ZA: {
                address: "7 Nel Street Roodepoort 1724 South Africa",
                email: "admin@raines.africa",
                phones: ["+27 XX XXX XXXX"]
            }
        };

        async function urlToDataUrl(url) {
            const res = await fetch(url, { mode: "cors" }); // requires CORS allowed by server
            if (!res.ok) throw new Error("Failed to fetch: " + url);

            const blob = await res.blob();
            return await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
        }

        async function embedBackgroundImages() {
            const nodes = document.querySelectorAll("[data-bg-url]");
            for (const el of nodes) {
                const url = el.getAttribute("data-bg-url");
                if (!url) continue;

                try {
                    const dataUrl = await urlToDataUrl(url);
                    el.style.backgroundImage = `url('${dataUrl}')`;
                    el.setAttribute("data-bg-embedded", "1");
                } catch (err) {
                    console.warn("BG embed failed:", url, err);
                }
            }
        }

        async function embedImgTags() {
            const imgs = document.querySelectorAll("img[data-embed='1'], img.embed-base64");
            for (const img of imgs) {
                const url = img.getAttribute("src");
                if (!url || url.startsWith("data:")) continue;

                try {
                    const dataUrl = await urlToDataUrl(url);
                    img.src = dataUrl;
                    img.setAttribute("data-embedded", "1");
                } catch (err) {
                    console.warn("IMG embed failed:", url, err);
                }
            }
        }

        // Call this before you do html2canvas / PDF export
        async function embedAllExternalImagesToBase64() {
            await embedBackgroundImages();
            await embedImgTags();
        }

        // Example: run on load (or run only when generating PDF)
        document.addEventListener("DOMContentLoaded", async () => {
            // If you only want base64 when exporting, comment this out
            // and call embedAllExternalImagesToBase64() inside your PDF/image button handler.
            await embedAllExternalImagesToBase64();
        });

        function renderMarketContact(marketCode) {
            const data = MARKET_CONTACTS[marketCode] || MARKET_CONTACTS.ZW;

            const addressEl = document.getElementById("footerAddress");
            const emailEl = document.getElementById("footerEmail");
            const phonesEl = document.getElementById("footerPhones");

            if (addressEl) addressEl.textContent = data.address;
            if (emailEl) emailEl.textContent = data.email;

            if (phonesEl) {
                phonesEl.innerHTML = data.phones
                    .map(p => `<span class="phone">${p}</span>`)
                    .join("");
            }
        }

        // Map currency codes to market codes
        function getMarketFromCurrency(currencyCode) {
            const currencyToMarket = {
                'USD': 'ZW',  // USD = Zimbabwe
                'ZMW': 'ZM',  // ZMW = Zambia
                'ZAR': 'ZA'   // ZAR = South Africa
            };
            return currencyToMarket[currencyCode] || 'ZW';
        }

        // Initialize market based on default currency
        document.addEventListener("DOMContentLoaded", () => {
            // Set initial market based on USD (default)
            renderMarketContact('ZW');
        });


        // Safe shared event buffer (works for most marketers / GTM / custom)
        window.dataLayer = window.dataLayer || [];
        function marketerPush(){ window.dataLayer.push(arguments); }
    </script>
</head>
<body>
<div class="full-height-container">
    <!-- Header Bar -->
    <div class="header-bar">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="{{ route('admin.promo-templates.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
                <a href="{{ route('admin.promo-templates.edit', $template->id) }}" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="bi bi-pencil me-1"></i>Edit Template
                </a>
                <span class="ms-3 fw-bold text-muted">{{ $template->name }}</span>
            </div>
            <div>
                <small id="status" class="text-muted me-3">Ready</small>
                <button id="btnLoad" class="btn btn-sm btn-primary">
                    <i class="bi bi-arrow-clockwise me-1"></i>Load Products
                </button>
                <button id="btnPDF" class="btn btn-sm btn-success">
                    <i class="bi bi-file-pdf me-1"></i>PDF
                </button>
                <button id="btnIMG" class="btn btn-sm btn-info">
                    <i class="bi bi-image me-1"></i>Image
                </button>
            </div>
        </div>
    </div>

    <!-- Main Layout -->
    <div class="main-layout">
        <!-- Left Panel: Preview -->
        <div class="left-panel">
            <div class="controls-bar" style="display: block !important; visibility: visible !important;">
                <div class="row g-2" style="display: flex !important;">
                    <div class="col-5">
                        <label class="form-label fw-bold mb-1" style="font-size: 13px;">
                            <i class="bi bi-upc-scan me-1"></i>Manual SKU Entry (Optional)
                        </label>
                        <textarea class="form-control form-control-sm" id="skus" rows="3"
                                  placeholder="Enter SKUs (one per line):&#10;69199513&#10;46859581&#10;99274552"></textarea>
                    </div>
                    <div class="col-4" style="display: block !important; visibility: visible !important;">
                        <div class="input" style="min-width:220px;margin-top: 3px;">
                            <label> Currency</label>
                            <select id="currency" style="border:none;outline:none;background:transparent;font-size:14px;font-weight:600;width:100%;cursor:pointer;">
                                <option value="USD|$|1">USD ($)</option>
                            </select>
                        </div>

                        <div class="row g-1 mt-2">
                            <div class="col-6">
                                <div class="input" style="min-width:220px;margin-top: 3px;">
                                    <label><i class="bi bi-grid me-1"></i> Rows</label>
                                    <input type="number" style="border:none;outline:none;background:transparent;font-size:14px;font-weight:600;width:100%;cursor:pointer;" id="pdfRows"
                                           value="3" min="1" max="5" >
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="input" style="min-width:220px;margin-top: 3px;">
                                    <label> <i class="bi bi-columns me-1"></i>Cols</label>
                                    <input type="number" style="border:none;outline:none;background:transparent;font-size:14px;font-weight:600;width:100%;cursor:pointer;" id="pdfColumns"
                                           value="3" min="1" max="5" >
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <label class="form-label fw-bold mb-1" style="font-size: 13px; color: #6f42c1;">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Layout
                        </label>
                        <div class="alert alert-info p-2 mb-0" style="font-size: 11px;">
                            <div><strong id="itemsPerPage">9</strong> items/page</div>
                            <div class="text-muted">(<span id="rowsDisplay">3</span>×<span id="colsDisplay">3</span> grid)</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="template-frame">
                <div id="template-container" data-version="{{ time() }}" style="position: relative;">
                    <!-- Layby Acceptance Banner -->
                    <div class="layby-banner">
                        <i class="bi bi-calendar-check"></i>
                        <span>✨ WE ACCEPT LAYBYS ✨</span>
                    </div>
                    {!! $template->html_content !!}
                </div>
            </div>
        </div>

        <!-- Right Panel: Product Selector -->
        <div class="right-panel">
            <div class="right-panel-header">
                <h6 class="mb-0">
                    <i class="bi bi-box-seam me-2"></i>Select Products
                    <span id="selectedCount" class="selected-count" style="display: none;">0</span>
                </h6>
            </div>

            <div class="product-search">
                <div class="input-group input-group-sm mb-2">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search products...">
                    <button class="btn btn-outline-secondary" type="button" id="btnSearch">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="onlySaleProducts">
                    <label class="form-check-label" for="onlySaleProducts" style="font-size: 13px;">
                        <i class="bi bi-tag-fill me-1 text-danger"></i>Show only products on sale
                    </label>
                </div>
            </div>

            <div class="product-list" id="productList">
                <div class="no-products">
                    <i class="bi bi-inbox" style="font-size: 48px;"></i>
                    <p class="mt-3">Search or filter products to select</p>
                </div>
            </div>

            <div class="p-3 border-top">
                <button id="btnLoadSelected" class="btn btn-primary btn-sm w-100" disabled>
                    <i class="bi bi-check-circle me-1"></i>Load Selected Products
                </button>
                <button id="btnClearSelection" class="btn btn-outline-secondary btn-sm w-100 mt-2" disabled>
                    <i class="bi bi-x-circle me-1"></i>Clear Selection
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner-border text-primary mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5 id="loadingText">Processing...</h5>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
    // Wait for everything to load including template's script
    window.addEventListener('load', function() {
        // DISABLE TEMPLATE'S BUTTONS to prevent double downloads
        setTimeout(() => {
            // Find and disable template's buttons inside the iframe content
            const templateButtons = templateContainer.querySelectorAll('button[id^="btn"]');
            templateButtons.forEach(btn => {
                btn.style.display = 'none';
                btn.disabled = true;
                // Remove all event listeners by cloning
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
            });

            // Also hide the toolbar if it exists
            const toolbar = templateContainer.querySelector('.toolbar');
            if (toolbar) toolbar.style.display = 'none';
        }, 100);

        // Elements
        const ourSkusInput = document.getElementById('skus');
        const ourBtnLoad = document.getElementById('btnLoad');
        const ourBtnPDF = document.getElementById('btnPDF');
        const ourBtnIMG = document.getElementById('btnIMG');
        const ourStatusEl = document.getElementById('status');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        const templateContainer = document.getElementById('template-container');
        const currencySelect = document.getElementById('currency');

        // Right panel elements
        const searchInput = document.getElementById('searchInput');
        const btnSearch = document.getElementById('btnSearch');
        const onlySaleProducts = document.getElementById('onlySaleProducts');
        const productList = document.getElementById('productList');
        const btnLoadSelected = document.getElementById('btnLoadSelected');
        const btnClearSelection = document.getElementById('btnClearSelection');
        const selectedCount = document.getElementById('selectedCount');

        // Currency state
        let currentCurrency = { code: 'USD', symbol: '$', rate: 1 };
        let currencies = [];

        // State
        let availableProducts = [];
        let selectedProducts = new Set();

        // Load available currencies
        async function loadCurrencies() {
            try {
                const response = await fetch('/admin/promo-templates/currencies');
                const data = await response.json();

                if (data.success && data.currencies) {
                    currencies = data.currencies;

                    // Populate currency dropdown
                    currencySelect.innerHTML = currencies.map(curr => {
                        const rate = parseFloat(curr.exchange_rate);
                        return `<option value="${curr.code}|${curr.symbol}|${rate}">${curr.code} (${curr.symbol})</option>`;
                    }).join('');

                    // Set default to USD or first currency
                    const usdCurrency = currencies.find(c => c.code === 'USD');
                    if (usdCurrency) {
                        currencySelect.value = `USD|${usdCurrency.symbol}|${usdCurrency.exchange_rate}`;
                    }

                    updateCurrentCurrency();
                }
            } catch (error) {
                currencySelect.innerHTML = '<option value="USD|$|1">USD ($)</option>';
            }
        }

        // Update current currency from selector
        function updateCurrentCurrency() {
            const [code, symbol, rate] = currencySelect.value.split('|');
            currentCurrency = {
                code: code,
                symbol: symbol,
                rate: parseFloat(rate)
            };
        }

        // Convert price to selected currency
        function convertPrice(usdPrice) {
            if (!usdPrice) return 0;
            return usdPrice * currentCurrency.rate;
        }

        // Currency change event - reload products with new currency and update market
        currencySelect.addEventListener('change', () => {
            updateCurrentCurrency();

            // Automatically update market contact based on currency
            const marketCode = getMarketFromCurrency(currentCurrency.code);
            renderMarketContact(marketCode);

            // Trigger reload of displayed products if any are loaded
            const skus = ourSkusInput.value.split('\n').map(s => s.trim()).filter(Boolean);
            if (skus.length > 0) {
                setStatus('Currency changed - reloading products...');
                loadProducts();
            }
        });

        // Load currencies on startup
        loadCurrencies();

        // Helper Functions
        function setStatus(msg) {
            if (ourStatusEl) ourStatusEl.textContent = msg;
        }

        function showLoading(text = 'Processing...') {
            if (loadingText) loadingText.textContent = text;
            if (loadingOverlay) loadingOverlay.classList.add('active');
        }

        function hideLoading() {
            if (loadingOverlay) loadingOverlay.classList.remove('active');
        }

        function disableButtons(disabled = true) {
            if (ourBtnLoad) ourBtnLoad.disabled = disabled;
            if (ourBtnPDF) ourBtnPDF.disabled = disabled;
            if (ourBtnIMG) ourBtnIMG.disabled = disabled;
        }

        function updateSelectedCount() {
            const count = selectedProducts.size;
            selectedCount.textContent = count;
            selectedCount.style.display = count > 0 ? 'inline-block' : 'none';
            btnLoadSelected.disabled = count === 0;
            btnClearSelection.disabled = count === 0;
        }

        function toggleProductSelection(product) {
            const sku = product.sku;
            if (selectedProducts.has(sku)) {
                selectedProducts.delete(sku);
            } else {
                selectedProducts.add(sku);
            }
            updateSelectedCount();
            renderProductList();
        }

        function clearSelection() {
            selectedProducts.clear();
            updateSelectedCount();
            renderProductList();
        }

        function renderProductList() {
            if (availableProducts.length === 0) {
                productList.innerHTML = `
                        <div class="no-products">
                            <i class="bi bi-inbox" style="font-size: 48px;"></i>
                            <p class="mt-3">Search or filter products to select</p>
                        </div>
                    `;
                return;
            }

            productList.innerHTML = availableProducts.map(product => {
                const isSelected = selectedProducts.has(product.sku);
                // API returns product_thumbnail, not thumbnail
                const thumbnail = product.product_thumbnail || product.thumbnail;
                const imageUrl = thumbnail?.image_url || thumbnail?.original_url || '';
                const hasSale = product.sale_price !== null && product.sale_price !== undefined;
                const priceClass = hasSale ? 'sale' : '';

                return `
                        <div class="product-item ${isSelected ? 'selected' : ''}" data-sku="${product.sku}">
                            <img src="${imageUrl}" alt="${product.name}"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22%3E%3Crect fill=%22%23f0f0f0%22 width=%2260%22 height=%2260%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 fill=%22%23999%22 font-size=%2212%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                            <div class="product-item-info">
                                <div class="product-item-name">${product.name}</div>
                                <div class="product-item-sku">SKU: ${product.sku}</div>
                                <div class="product-item-price ${priceClass}">
                                    ${hasSale ? `<span class="old-price">$${product.price}</span>$${product.sale_price}` : `$${product.price}`}
                                </div>
                            </div>
                        </div>
                    `;
            }).join('');

            // Attach click handlers
            productList.querySelectorAll('.product-item').forEach(item => {
                item.addEventListener('click', function() {
                    const sku = this.dataset.sku;
                    const product = availableProducts.find(p => p.sku === sku);
                    if (product) {
                        toggleProductSelection(product);
                    }
                });
            });
        }

        // Search Products
        async function searchProducts() {
            const query = searchInput.value.trim();
            const onlySale = onlySaleProducts.checked;

            if (!query && !onlySale) {
                setStatus('Enter search term or filter by sale products');
                return;
            }

            showLoading('Searching products...');
            setStatus('Searching...');

            try {
                // Use dedicated promo search endpoint (bypasses Elasticsearch, direct DB query)
                let url = '/api/product/search-promo?';
                const params = [];

                if (query) {
                    params.push(`search=${encodeURIComponent(query)}`);
                }
                if (onlySale) {
                    params.push('on_sale=1');
                }

                url += params.join('&');

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`API error: ${response.status}`);
                }

                const data = await response.json();
                availableProducts = data.data || [];

                setStatus(`Found ${availableProducts.length} product(s)`);
                renderProductList();

            } catch (error) {
                setStatus('Error: ' + error.message);
                productList.innerHTML = `
                        <div class="no-products">
                            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 48px;"></i>
                            <p class="mt-3">Error loading products</p>
                        </div>
                    `;
            } finally {
                hideLoading();
            }
        }

        // Load Products Function
        async function loadProducts(skuList = null) {
            let skus;

            if (skuList) {
                skus = skuList;
            } else {
                skus = ourSkusInput.value
                    .split('\n')  // Split by newline
                    .map(s => s.trim())  // Trim whitespace
                    .map(s => s.replace(/["']/g, ''))  // Remove quotes
                    .filter(Boolean)  // Remove empty strings
                    .filter(s => /^[a-zA-Z0-9\-_]+$/.test(s));  // Only keep valid SKU format (alphanumeric, dash, underscore)
            }

            if (!skus.length) {
                Swal.fire({ icon: 'warning', title: 'Warning', text: 'Please enter SKUs or select products from the list.' });
                return;
            }

            showLoading('Loading products...');
            disableButtons(true);
            setStatus('Fetching products from database...');

            try {
                const url = `/api/product/by-skus?skus=${encodeURIComponent(skus.join(','))}`;
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`API error: ${response.status} ${response.statusText}`);
                }

                const data = await response.json();

                if (!data || !data.success) {
                    throw new Error(data.message || 'Failed to load products');
                }

                const products = data.products || [];
                setStatus(`Loaded ${products.length} product(s) successfully!`);

                // Ensure image_url is available and apply currency conversion
                const productsWithFixedImages = products.map(p => {
                    const thumbnail = p.thumbnail || p.product_thumbnail;

                    // Convert prices to selected currency
                    const convertedPrice = p.price ? convertPrice(p.price) : 0;
                    const convertedSalePrice = p.sale_price ? convertPrice(p.sale_price) : null;

                    return {
                        ...p,
                        // Store original USD prices
                        original_price: p.price,
                        original_sale_price: p.sale_price,
                        // Converted prices
                        price: convertedPrice,
                        sale_price: convertedSalePrice,
                        // Currency info
                        currency_symbol: currentCurrency.symbol,
                        currency_code: currentCurrency.code,
                        thumbnail: thumbnail ? {
                            ...thumbnail,
                            image_url: thumbnail.image_url || thumbnail.original_url,
                            original_url: thumbnail.image_url || thumbnail.original_url
                        } : null
                    };
                });

                // Get dynamic layout configuration
                const rows = parseInt(pdfRowsInput?.value) || 2;
                const cols = parseInt(pdfColumnsInput?.value) || 2;
                const itemsPerPage = rows * cols;

                // Call template's renderProducts function if it exists
                if (typeof window.renderProducts === 'function') {
                    // Store products globally for PDF pagination
                    window.loadedProducts = productsWithFixedImages;

                    // Pass layout configuration to template
                    window.renderProducts(productsWithFixedImages, { rows, cols, itemsPerPage });
                } else {
                    setStatus(`Products loaded but template has no renderProducts function.`);
                }

            } catch (error) {
                setStatus('Error: ' + error.message);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load products: ' + error.message });
            } finally {
                hideLoading();
                disableButtons(false);
            }
        }

        // Load selected products
        function loadSelectedProducts() {
            const skus = Array.from(selectedProducts);
            if (skus.length > 0) {
                loadProducts(skus);
            }
        }

        // ...existing code for captureTemplate, downloadImage, downloadPDF...
        // Capture Template as Canvas
        // Capture for Image Download - natural dimensions
        async function captureTemplateForImage() {
            // Always capture the entire template-container to include the layby banner
            const element = templateContainer;

            // Ensure layby banner is visible before capture
            const laybyBanner = element.querySelector('.layby-banner');
            if (laybyBanner) {
                laybyBanner.style.display = 'flex';
                laybyBanner.style.visibility = 'visible';
                laybyBanner.style.opacity = '1';
                laybyBanner.style.position = 'absolute';
            }

            const canvas = await html2canvas(element, {
                scale: 3,
                useCORS: false,
                allowTaint: false,
                backgroundColor: '#ffffff',
                logging: true,
                onclone: (clonedDoc) => {
                    // Ensure layby banner is visible in cloned document
                    const clonedBanner = clonedDoc.querySelector('.layby-banner');
                    if (clonedBanner) {
                        clonedBanner.style.display = 'flex';
                        clonedBanner.style.visibility = 'visible';
                        clonedBanner.style.opacity = '1';
                        clonedBanner.style.position = 'absolute';
                        clonedBanner.style.zIndex = '10';
                    }
                }
            });

            return canvas;
        }

        // Capture for PDF - optimize for A4 with no cropping
        async function captureTemplateForPDF() {
            // Always capture the entire template-container to include the layby banner
            const element = templateContainer;

            // Ensure layby banner is visible before capture
            const laybyBanner = element.querySelector('.layby-banner');
            if (laybyBanner) {
                laybyBanner.style.display = 'flex';
                laybyBanner.style.visibility = 'visible';
                laybyBanner.style.opacity = '1';
                laybyBanner.style.position = 'absolute';
            }

            // Capture at natural size with high quality
            const canvas = await html2canvas(element, {
                scale: 2,
                useCORS: false,
                allowTaint: false,
                backgroundColor: '#ffffff',
                logging: true,
                onclone: (clonedDoc) => {
                    // Ensure layby banner is visible in cloned document
                    const clonedBanner = clonedDoc.querySelector('.layby-banner');
                    if (clonedBanner) {
                        clonedBanner.style.display = 'flex';
                        clonedBanner.style.visibility = 'visible';
                        clonedBanner.style.opacity = '1';
                        clonedBanner.style.position = 'absolute';
                        clonedBanner.style.zIndex = '10';
                    }
                }
            });

            return canvas;
        }

        // Download as Image
        async function downloadImage() {
            if (typeof html2canvas === 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: 'html2canvas library is not loaded. Please refresh the page.' });
                return;
            }

            showLoading('Generating high-quality image...');
            disableButtons(true);
            setStatus('Rendering image...');

            try {
                const canvas = await captureTemplateForImage();

                canvas.toBlob((blob) => {
                    if (!blob) {
                        throw new Error('Failed to create image blob');
                    }
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `{{ Str::slug($template->name) }}-${Date.now()}.png`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                    setStatus('Image downloaded successfully!');
                    hideLoading();
                    disableButtons(false);
                }, 'image/png', 1.0);

            } catch (error) {
                setStatus('Error generating image: ' + error.message);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to generate image: ' + error.message });
                hideLoading();
                disableButtons(false);
            }
        }

        // Download as PDF - Split tall content into multiple A4 pages
        async function downloadPDF_() {
            if (typeof html2canvas === 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: 'html2canvas library is not loaded. Please refresh the page.' });
                return;
            }
            if (typeof window.jspdf === 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: 'jsPDF library is not loaded. Please refresh the page.' });
                return;
            }

            showLoading('Generating PDF...');
            disableButtons(true);
            setStatus('Rendering PDF...');

            try {
                // Ensure layby banner is visible
                const laybyBanner = templateContainer.querySelector('.layby-banner');
                if (laybyBanner) {
                    laybyBanner.style.display = 'flex';
                    laybyBanner.style.visibility = 'visible';
                    laybyBanner.style.opacity = '1';
                }

                // Capture the ENTIRE template once with ALL products
                setStatus('Capturing template...');
                const canvas = await html2canvas(templateContainer, {
                    scale: 2,
                    useCORS: false,
                    allowTaint: false,
                    backgroundColor: '#ffffff',
                    logging: false,
                    onclone: (clonedDoc) => {
                        const clonedBanner = clonedDoc.querySelector('.layby-banner');
                        if (clonedBanner) {
                            clonedBanner.style.display = 'flex';
                            clonedBanner.style.visibility = 'visible';
                            clonedBanner.style.opacity = '1';
                        }
                    }
                });

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4',
                    compress: true
                });

                const pageWidth = pdf.internal.pageSize.getWidth();   // 210mm
                const pageHeight = pdf.internal.pageSize.getHeight(); // 297mm

                // Calculate how the canvas fits
                const imgWidth = pageWidth;
                const imgHeight = pageWidth * (canvas.height / canvas.width);

                // If content fits on one page, add it centered
                if (imgHeight <= pageHeight) {
                    setStatus('Adding single page...');
                    const imgData = canvas.toDataURL('image/png', 1.0);
                    const yOffset = (pageHeight - imgHeight) / 2;
                    pdf.addImage(imgData, 'PNG', 0, yOffset, imgWidth, imgHeight, undefined, 'FAST');
                } else {
                    // Content is taller than one page - split into multiple pages
                    const totalPages = Math.ceil(imgHeight / pageHeight);
                    setStatus(`Splitting into ${totalPages} page(s)...`);

                    for (let page = 0; page < totalPages; page++) {
                        if (page > 0) {
                            pdf.addPage();
                        }

                        setStatus(`Adding page ${page + 1} of ${totalPages}...`);

                        // Calculate which vertical slice of the canvas to show
                        const sourceY = (page * pageHeight) * (canvas.height / imgHeight);
                        const sourceHeight = Math.min(pageHeight * (canvas.height / imgHeight), canvas.height - sourceY);

                        // Create a temporary canvas for this page slice
                        const pageCanvas = document.createElement('canvas');
                        pageCanvas.width = canvas.width;
                        pageCanvas.height = sourceHeight;

                        const ctx = pageCanvas.getContext('2d');
                        ctx.drawImage(canvas, 0, sourceY, canvas.width, sourceHeight, 0, 0, canvas.width, sourceHeight);

                        const pageImgData = pageCanvas.toDataURL('image/png', 1.0);
                        const pageImgHeight = pageWidth * (pageCanvas.height / canvas.width);

                        pdf.addImage(pageImgData, 'PNG', 0, 0, imgWidth, pageImgHeight, undefined, 'FAST');
                    }
                }

                pdf.save(`{{ Str::slug($template->name) }}-${Date.now()}.pdf`);
                setStatus('PDF downloaded successfully!');

            } catch (error) {
                setStatus('Error generating PDF: ' + error.message);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to generate PDF: ' + error.message });
                console.error('PDF Error:', error);
            } finally {
                hideLoading();
                disableButtons(false);
            }
        }

        async function downloadPDF() {
            if (typeof html2canvas === 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: 'html2canvas library is not loaded. Please refresh the page.' });
                return;
            }
            if (typeof window.jspdf === 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: 'jsPDF library is not loaded. Please refresh the page.' });
                return;
            }

            showLoading('Generating PDF...');
            disableButtons(true);
            setStatus('Rendering PDF...');

            try {
                const allProducts = window.loadedProducts || [];
                const cols = parseInt(pdfColumnsInput?.value) || 3;
                const FIRST_PAGE_ROWS = 4;
                const OTHER_PAGE_ROWS = 5;
                const firstPageItems = FIRST_PAGE_ROWS * cols;
                const otherPageItems = OTHER_PAGE_ROWS * cols;

                // Split products into page chunks
                const pageChunks = [];
                if (allProducts.length <= firstPageItems) {
                    pageChunks.push(allProducts.slice(0));
                } else {
                    pageChunks.push(allProducts.slice(0, firstPageItems));
                    let offset = firstPageItems;
                    while (offset < allProducts.length) {
                        pageChunks.push(allProducts.slice(offset, offset + otherPageItems));
                        offset += otherPageItems;
                    }
                }

                const totalPages = pageChunks.length;

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: 'portrait',
                    unit: 'mm',
                    format: 'a4',
                    compress: true
                });

                const pageWidth = pdf.internal.pageSize.getWidth();   // 210mm
                const pageHeight = pdf.internal.pageSize.getHeight(); // 297mm

                // Ensure layby banner is visible
                const laybyBanner = templateContainer.querySelector('.layby-banner');
                if (laybyBanner) {
                    laybyBanner.style.display = 'flex';
                    laybyBanner.style.visibility = 'visible';
                    laybyBanner.style.opacity = '1';
                }

                // Save references to header/footer so we can hide/show them
                const header = templateContainer.querySelector('.header');
                const footer = templateContainer.querySelector('.footer');

                for (let pageIdx = 0; pageIdx < totalPages; pageIdx++) {
                    if (pageIdx > 0) {
                        pdf.addPage();
                    }

                    const chunk = pageChunks[pageIdx];
                    const isFirstPage = pageIdx === 0;
                    const isLastPage = pageIdx === totalPages - 1;
                    const rowsForPage = isFirstPage ? FIRST_PAGE_ROWS : OTHER_PAGE_ROWS;

                    setStatus(`Rendering page ${pageIdx + 1} of ${totalPages}...`);

                    // Render only this chunk of products into the grid
                    if (typeof window.renderProducts === 'function') {
                        window.renderProducts(chunk, { rows: rowsForPage, cols: cols, itemsPerPage: rowsForPage * cols });
                    }

                    // Control visibility of header/footer/banner per page:
                    // - Header: only on first page
                    // - Footer: only on first page (if single page) OR on last page
                    // - Layby banner: only on first page
                    // - Subbar (outside grid): only on first page
                    const showHeader = isFirstPage;
                    const showFooter = isFirstPage || isLastPage;
                    const showBanner = isFirstPage;

                    if (header) header.style.display = showHeader ? '' : 'none';
                    if (footer) footer.style.display = showFooter ? '' : 'none';
                    if (laybyBanner) {
                        if (showBanner) {
                            laybyBanner.style.display = 'flex';
                            laybyBanner.style.visibility = 'visible';
                            laybyBanner.style.opacity = '1';
                        } else {
                            laybyBanner.style.display = 'none';
                        }
                    }
                    // Hide subbar on non-first pages
                    const subbar = templateContainer.querySelector('.subbar');
                    if (subbar && subbar.closest && !subbar.closest('#grid')) {
                        if (!isFirstPage) {
                            subbar.dataset.pdfHidden = subbar.style.display;
                            subbar.style.display = 'none';
                        } else {
                            if (subbar.dataset.pdfHidden !== undefined) {
                                subbar.style.display = subbar.dataset.pdfHidden;
                                delete subbar.dataset.pdfHidden;
                            }
                        }
                    }

                    // Wait a moment for re-render to settle
                    await new Promise(r => setTimeout(r, 150));

                    // Capture the template container
                    const canvas = await html2canvas(templateContainer, {
                        scale: 2,
                        useCORS: false,
                        allowTaint: false,
                        backgroundColor: '#ffffff',
                        logging: false,
                        onclone: (clonedDoc) => {
                            const clonedBanner = clonedDoc.querySelector('.layby-banner');
                            const clonedHeader = clonedDoc.querySelector('.header');
                            const clonedFooter = clonedDoc.querySelector('.footer');
                            // Header: only first page
                            if (clonedHeader) clonedHeader.style.display = showHeader ? '' : 'none';
                            // Footer: first and last page only
                            if (clonedFooter) clonedFooter.style.display = showFooter ? '' : 'none';
                            // Banner: only first page
                            if (clonedBanner) {
                                if (showBanner) {
                                    clonedBanner.style.display = 'flex';
                                    clonedBanner.style.visibility = 'visible';
                                    clonedBanner.style.opacity = '1';
                                } else {
                                    clonedBanner.style.display = 'none';
                                }
                            }
                        }
                    });

                    // ALWAYS fit to full width (no left/right margins) like the original templates
                    const renderWidth = pageWidth;  // Always 210mm (full A4 width)
                    const renderHeight = pageWidth * (canvas.height / canvas.width);
                    const imgData = canvas.toDataURL('image/png', 1.0);

                    // Place at x=0, y=0 - full page width, no side margins
                    // Content always starts at top of page
                    pdf.addImage(imgData, 'PNG', 0, 0, renderWidth, renderHeight, undefined, 'FAST');
                }

                // Restore everything back to normal after PDF generation
                if (header) header.style.display = '';
                if (footer) footer.style.display = '';
                if (laybyBanner) {
                    laybyBanner.style.display = 'flex';
                    laybyBanner.style.visibility = 'visible';
                    laybyBanner.style.opacity = '1';
                }
                const subbar = templateContainer.querySelector('.subbar');
                if (subbar && subbar.dataset.pdfHidden !== undefined) {
                    subbar.style.display = subbar.dataset.pdfHidden;
                    delete subbar.dataset.pdfHidden;
                }

                // Restore all products in the preview
                if (typeof window.renderProducts === 'function' && allProducts.length > 0) {
                    const rows = parseInt(pdfRowsInput?.value) || 3;
                    window.renderProducts(allProducts, { rows, cols, itemsPerPage: rows * cols });
                }

                pdf.save(`{{ Str::slug($template->name) }}-${Date.now()}.pdf`);
                setStatus('PDF downloaded successfully!');

            } catch (error) {
                setStatus('Error generating PDF: ' + error.message);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to generate PDF: ' + error.message });
                console.error('PDF Error:', error);

                // Restore state on error
                const header = templateContainer.querySelector('.header');
                const footer = templateContainer.querySelector('.footer');
                const laybyBanner = templateContainer.querySelector('.layby-banner');
                if (header) header.style.display = '';
                if (footer) footer.style.display = '';
                if (laybyBanner) {
                    laybyBanner.style.display = 'flex';
                    laybyBanner.style.visibility = 'visible';
                }
                // Restore products
                const allProducts = window.loadedProducts || [];
                if (typeof window.renderProducts === 'function' && allProducts.length > 0) {
                    const rows = parseInt(pdfRowsInput?.value) || 3;
                    const cols = parseInt(pdfColumnsInput?.value) || 3;
                    window.renderProducts(allProducts, { rows, cols, itemsPerPage: rows * cols });
                }
            } finally {
                hideLoading();
                disableButtons(false);
            }
        }

        // Event Listeners
        if (ourBtnLoad) ourBtnLoad.addEventListener('click', () => loadProducts());
        if (ourBtnIMG) ourBtnIMG.addEventListener('click', downloadImage);
        if (ourBtnPDF) ourBtnPDF.addEventListener('click', downloadPDF);

        // Right panel event listeners
        if (btnSearch) btnSearch.addEventListener('click', searchProducts);
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    searchProducts();
                }
            });
        }
        if (onlySaleProducts) onlySaleProducts.addEventListener('change', searchProducts);
        if (btnLoadSelected) btnLoadSelected.addEventListener('click', loadSelectedProducts);
        if (btnClearSelection) btnClearSelection.addEventListener('click', clearSelection);

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                if (e.key === 'l') {
                    e.preventDefault();
                    loadProducts();
                } else if (e.key === 'p') {
                    e.preventDefault();
                    downloadPDF();
                } else if (e.key === 'i') {
                    e.preventDefault();
                    downloadImage();
                } else if (e.key === 'f') {
                    e.preventDefault();
                    searchInput.focus();
                }
            }
        });

        // Update layout preview when rows/columns change
        const pdfRowsInput = document.getElementById('pdfRows');
        const pdfColumnsInput = document.getElementById('pdfColumns');
        const itemsPerPageDisplay = document.getElementById('itemsPerPage');
        const rowsDisplay = document.getElementById('rowsDisplay');
        const colsDisplay = document.getElementById('colsDisplay');

        function updateLayoutPreview() {
            const rows = parseInt(pdfRowsInput.value) || 2;
            const cols = parseInt(pdfColumnsInput.value) || 2;
            const itemsPerPage = rows * cols;

            itemsPerPageDisplay.textContent = itemsPerPage;
            rowsDisplay.textContent = rows;
            colsDisplay.textContent = cols;
        }

        if (pdfRowsInput) {
            pdfRowsInput.addEventListener('input', updateLayoutPreview);
            pdfRowsInput.addEventListener('change', updateLayoutPreview);
        }
        if (pdfColumnsInput) {
            pdfColumnsInput.addEventListener('input', updateLayoutPreview);
            pdfColumnsInput.addEventListener('change', updateLayoutPreview);
        }

        // Initialize layout preview
        updateLayoutPreview();

        // Initialize
        setStatus('Ready. Search products or enter SKUs manually.');
        // Auto-search on sale products initially
        setTimeout(() => {
            onlySaleProducts.checked = true;
            searchProducts();
        }, 500);
    });
</script>
</body>
</html>
