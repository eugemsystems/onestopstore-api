@extends('admin.layout')

@section('title', 'Bulk Promotion - Admin Panel')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="bi bi-tags me-2"></i>Bulk Product Promotion
                </h2>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#skuSalePriceModal">
                        <i class="bi bi-upload me-1"></i>Sale Price Upload
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                    </button>
                </div>
            </div>
            <p class="text-muted mt-2">Apply promotional pricing to multiple products at once</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <div class="row">
        <!-- Left Panel: Input Form -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Promotion Settings</h5>
                </div>
                <div class="card-body">
                    <form id="promotionForm">
                        <!-- Mode Toggle -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="is_category_mode"
                                    name="is_category_mode"
                                >
                                <label class="form-check-label fw-bold" for="is_category_mode">
                                    <i class="bi bi-folder me-1"></i>Use Category Slugs Mode
                                </label>
                            </div>
                            <small class="text-muted">
                                Check this to apply promotions to entire categories instead of individual products.
                            </small>
                        </div>

                        <!-- Product/Category Identifiers -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="identifiers" class="form-label fw-bold mb-0" id="identifiersLabel">
                                    <i class="bi bi-upc-scan me-1" id="identifiersIcon"></i><span id="identifiersLabelText">Product SKUs or Slugs</span>
                                    <span class="text-danger">*</span>
                                </label>
                                <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer;">
                                    <i class="bi bi-upload me-1"></i>Upload .txt
                                    <input type="file" id="promoIdentifiersFileInput" accept=".txt" style="display:none;">
                                </label>
                            </div>
                            <div id="promoFileBadge" class="alert alert-info py-1 px-2 mb-1 d-none small">
                                <i class="bi bi-file-earmark-text me-1"></i><span id="promoFileName"></span> — will be processed directly on submit.
                            </div>
                            <textarea
                                class="form-control font-monospace"
                                id="identifiers"
                                name="identifiers"
                                rows="8"
                                placeholder="Enter SKUs or slugs (one per line):&#10;ABC123&#10;product-slug&#10;XYZ789&#10;..."
                            ></textarea>
                            <small class="text-muted" id="identifiersHelp">
                                <i class="bi bi-info-circle me-1"></i><span id="identifiersHelpText">Enter one SKU or slug per line. You can paste from Excel/CSV.</span>
                            </small>
                        </div>

                        <!-- Percentage Increase -->
                        <div class="mb-3">
                            <label for="percentage" class="form-label fw-bold">
                                <i class="bi bi-percent me-1"></i>Price Increase Percentage
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    class="form-control"
                                    id="percentage"
                                    name="percentage"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="20"
                                    required
                                >
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>Original price becomes sale price. New price = original + percentage.
                            </small>
                        </div>

                        <!-- Example Calculation -->
                        <div class="alert alert-info mb-3">
                            <strong>Example:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Original Price: <strong>$100</strong></li>
                                <li>Increase by: <strong><span id="examplePercentage">20</span>%</strong></li>
                                <li>New Price: <strong class="text-success">$<span id="exampleNewPrice">120</span></strong></li>
                                <li>Sale Price: <strong class="text-danger">$<span id="exampleSalePrice">100</span></strong></li>
                                <li>Discount: <strong class="text-primary"><span id="exampleDiscount">16.67</span>%</strong></li>
                            </ul>
                        </div>

                        <!-- Sale Enable/Disable -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-toggle-on me-1"></i>Sale Status
                                <span class="text-danger">*</span>
                            </label>
                            <div class="form-check form-switch">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    id="is_sale_enable"
                                    name="is_sale_enable"
                                    checked
                                >
                                <label class="form-check-label" for="is_sale_enable">
                                    <strong class="text-success">Enable Sale</strong>
                                </label>
                            </div>
                            <small class="text-muted">
                                Turn off to set prices without activating the sale immediately.
                            </small>
                        </div>

                        <!-- Sale Start Date (Optional) -->
                        <div class="mb-3">
                            <label for="sale_starts_at" class="form-label fw-bold">
                                <i class="bi bi-calendar-check me-1"></i>Sale Start Date (Optional)
                            </label>
                            <input
                                type="date"
                                class="form-control"
                                id="sale_starts_at"
                                name="sale_starts_at"
                            >
                            <small class="text-muted">Leave empty for immediate start</small>
                        </div>

                        <!-- Sale End Date (Optional) -->
                        <div class="mb-3">
                            <label for="sale_expired_at" class="form-label fw-bold">
                                <i class="bi bi-calendar-x me-1"></i>Sale End Date (Optional)
                            </label>
                            <input
                                type="date"
                                class="form-control"
                                id="sale_expired_at"
                                name="sale_expired_at"
                            >
                            <small class="text-muted">Leave empty for no expiration</small>
                        </div>

                        <!-- Progress bar -->
                        <div id="applyProgress" class="mb-3" style="display:none;">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-semibold" id="applyProgressText">Processing…</small>
                                <small class="text-muted" id="applyProgressCount"></small>
                            </div>
                            <div class="progress" style="height:22px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                     id="applyProgressBar" role="progressbar" style="width:0%">0%</div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="applyBtn">
                                <i class="bi bi-check-circle me-2"></i>Apply Promotion
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Panel: Results -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Results</h5>
                </div>
                <div class="card-body">
                    <div id="resultsContainer">
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 64px;"></i>
                            <p class="mt-3">No results yet. Apply promotion to see updates.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Reset Promotions ─────────────────────────────────────────── --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header" style="background: linear-gradient(135deg,#7f1d1d,#991b1b);">
                    <i class="bi bi-x-circle-fill me-2"></i>
                    <h5 class="mb-0">Reset Promotions</h5>
                    <small class="ms-2 opacity-75">Clears sale price, sale enable flag, and sale dates by SKU</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Left: SKU input --}}
                        <div class="col-md-5">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="reset-skus" class="form-label fw-semibold mb-0">
                                    <i class="bi bi-upc-scan me-1"></i>Product SKUs <span class="text-danger">*</span>
                                </label>
                                <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer;">
                                    <i class="bi bi-upload me-1"></i>Upload .txt
                                    <input type="file" id="resetSkusFileInput" accept=".txt" style="display:none;">
                                </label>
                            </div>
                            <div id="resetFileBadge" class="alert alert-info py-1 px-2 mb-1 d-none small">
                                <i class="bi bi-file-earmark-text me-1"></i><span id="resetFileName"></span> — will be processed directly on submit.
                            </div>
                            <textarea
                                id="reset-skus"
                                class="form-control font-monospace"
                                rows="9"
                                placeholder="Paste SKUs one per line:&#10;ABC123&#10;DEF456&#10;GHI789&#10;..."
                            ></textarea>
                            <small class="text-muted"><i class="bi bi-info-circle me-1"></i>One SKU per line — can paste from Excel.</small>

                            <div class="alert alert-warning mt-3 mb-0 py-2" style="font-size:0.85rem;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>This will permanently remove:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Sale Price → <code>null</code></li>
                                    <li>Enable Sale → <strong>false</strong></li>
                                    <li>Sale Start &amp; End Date → <code>null</code></li>
                                </ul>
                            </div>

                            <div id="resetProgress" class="mt-3" style="display:none;">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="fw-semibold" id="resetProgressText">Processing…</small>
                                    <small class="text-muted" id="resetProgressCount"></small>
                                </div>
                                <div class="progress" style="height:22px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger"
                                         id="resetProgressBar" role="progressbar" style="width:0%">0%</div>
                                </div>
                            </div>

                            <div class="d-grid mt-3">
                                <button id="reset-btn" type="button" class="btn btn-danger btn-lg" onclick="runReset()">
                                    <i class="bi bi-x-circle me-2"></i>Reset Promotions
                                </button>
                            </div>
                        </div>

                        {{-- Right: Results --}}
                        <div class="col-md-7">
                            <div id="reset-results">
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size:2.5rem;"></i>
                                    <p class="mt-2 mb-0">Paste SKUs and click Reset to see the summary.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Sale Price Upload Modal ────────────────────────────────────────────── --}}
<div class="modal fade" id="skuSalePriceModal" tabindex="-1" aria-labelledby="skuSalePriceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1e3a5f, #2563eb); color: white;">
                <h5 class="modal-title" id="skuSalePriceModalLabel">
                    <i class="bi bi-upload me-2"></i>Sale Price Upload
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div id="sspAlertBox"></div>

                <div class="row g-3">
                    {{-- Left: form inputs --}}
                    <div class="col-md-5">

                        {{-- Textarea --}}
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="ssp-sku-prices" class="form-label fw-bold mb-0">
                                    <i class="bi bi-upc-scan me-1"></i>SKU &amp; Sale Price
                                    <span class="text-danger">*</span>
                                </label>
                                <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer;">
                                    <i class="bi bi-upload me-1"></i>Upload .txt
                                    <input type="file" id="sspFileInput" accept=".txt" style="display:none;">
                                </label>
                            </div>
                            <div id="sspFileBadge" class="alert alert-info py-1 px-2 mb-1 d-none small">
                                <i class="bi bi-file-earmark-text me-1"></i><span id="sspFileName"></span> — will be processed directly on submit.
                            </div>
                            <textarea
                                id="ssp-sku-prices"
                                class="form-control font-monospace"
                                rows="10"
                                placeholder="Paste one per line:&#10;ABC123,99.00&#10;DEF456,149.99&#10;XYZ789,59.50"
                            ></textarea>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>Format: <code>sku,sale_price</code> — one per line.
                            </small>
                        </div>

                        {{-- Percentage --}}
                        <div class="mb-3">
                            <label for="ssp-percentage" class="form-label fw-bold">
                                <i class="bi bi-percent me-1"></i>Price Increase Percentage
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="ssp-percentage"
                                       min="0" max="500" step="0.01" value="20">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">
                                Applied only when the pasted <code>sale_price</code> is <strong>greater than</strong> the current DB price.
                            </small>
                        </div>

                        {{-- Sale Enable toggle --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="bi bi-toggle-on me-1"></i>Sale Status
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ssp-is-sale-enable" checked>
                                <label class="form-check-label" for="ssp-is-sale-enable">
                                    <strong class="text-success">Enable Sale</strong>
                                </label>
                            </div>
                        </div>

                        {{-- Sale Start Date --}}
                        <div class="mb-3">
                            <label for="ssp-sale-starts-at" class="form-label fw-bold">
                                <i class="bi bi-calendar-check me-1"></i>Sale Start Date <small class="text-muted fw-normal">(optional)</small>
                            </label>
                            <input type="date" class="form-control" id="ssp-sale-starts-at">
                        </div>

                        {{-- Sale End Date --}}
                        <div class="mb-3">
                            <label for="ssp-sale-expired-at" class="form-label fw-bold">
                                <i class="bi bi-calendar-x me-1"></i>Sale End Date <small class="text-muted fw-normal">(optional)</small>
                            </label>
                            <input type="date" class="form-control" id="ssp-sale-expired-at">
                        </div>

                        <div id="sspProgress" class="mb-2" style="display:none;">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-semibold" id="sspProgressText">Processing…</small>
                                <small class="text-muted" id="sspProgressCount"></small>
                            </div>
                            <div class="progress" style="height:22px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                     id="sspProgressBar" role="progressbar" style="width:0%">0%</div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button id="ssp-submit-btn" type="button" class="btn btn-primary btn-lg" onclick="runSkuSalePrices()">
                                <i class="bi bi-check-circle me-2"></i>Apply Sale Prices
                            </button>
                        </div>

                    </div>

                    {{-- Right: results --}}
                    <div class="col-md-7">
                        <div id="ssp-results" class="ssp-results-panel">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size:3rem;"></i>
                                <p class="mt-3">Paste your data and click <strong>Apply Sale Prices</strong>.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /modal-body --}}
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .result-item {
        padding: 15px;
        border-left: 4px solid #28a745;
        background: #f8f9fa;
        margin-bottom: 10px;
        border-radius: 4px;
    }
    .result-item.error {
        border-left-color: #dc3545;
        background: #ffe6e6;
    }
    .price-change {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 5px 0;
    }
    .price-old {
        text-decoration: line-through;
        color: #6c757d;
        font-size: 14px;
    }
    .price-new {
        font-weight: bold;
        color: #28a745;
        font-size: 18px;
    }
    .price-sale {
        font-weight: bold;
        color: #dc3545;
        font-size: 18px;
    }
    .discount-badge {
        background: #ffc107;
        color: #000;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    /* Sale Price Upload modal results panel */
    .ssp-results-panel {
        max-height: 520px;
        overflow-y: auto;
        padding-right: 4px;
    }
    .ssp-result-row {
        padding: 10px 14px;
        border-left: 4px solid #22c55e;
        background: #f8fdf9;
        margin-bottom: 8px;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    .ssp-result-row.inflated {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
</style>
@endpush

@push('scripts')
<script>
    const CHUNK_SIZE = 200;

    // Read a .txt file into an array of trimmed non-empty lines
    function readFileLines(file) {
        return new Promise((resolve, reject) => {
            const r = new FileReader();
            r.onload = e => resolve(e.target.result.split(/\r?\n/).map(l => l.trim().split(',')[0].trim()).filter(l => l));
            r.onerror = reject;
            r.readAsText(file);
        });
    }

    // Split array into chunks of size n
    function chunkArray(arr, n) {
        const out = [];
        for (let i = 0; i < arr.length; i += n) out.push(arr.slice(i, i + n));
        return out;
    }

    // Update a progress bar set (barId, textId, countId)
    function setProgress(barId, textId, countId, done, total, chunkIdx, totalChunks) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        const bar = document.getElementById(barId);
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';
        document.getElementById(textId).textContent =
            done >= total ? 'Done!' : `Chunk ${chunkIdx} of ${totalChunks} — processing…`;
        document.getElementById(countId).textContent = `${done} / ${total}`;
    }

    function finishProgress(barId) {
        const bar = document.getElementById(barId);
        bar.classList.remove('progress-bar-animated', 'progress-bar-striped');
        bar.classList.add('bg-success');
    }

    // Show filename badge when a file is selected
    function bindFileBadge(inputId, badgeId, nameId) {
        document.getElementById(inputId).addEventListener('change', function () {
            const badge = document.getElementById(badgeId);
            const nameEl = document.getElementById(nameId);
            if (this.files[0]) {
                nameEl.textContent = this.files[0].name;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        });
    }
    bindFileBadge('promoIdentifiersFileInput', 'promoFileBadge', 'promoFileName');
    bindFileBadge('resetSkusFileInput', 'resetFileBadge', 'resetFileName');
    bindFileBadge('sspFileInput', 'sspFileBadge', 'sspFileName');

    // Toggle mode between products and categories
    document.getElementById('is_category_mode').addEventListener('change', function() {
        const isCategoryMode = this.checked;
        const identifiersLabel = document.getElementById('identifiersLabelText');
        const identifiersIcon = document.getElementById('identifiersIcon');
        const identifiersTextarea = document.getElementById('identifiers');
        const identifiersHelpText = document.getElementById('identifiersHelpText');

        if (isCategoryMode) {
            // Category mode
            identifiersLabel.textContent = 'Category Slugs';
            identifiersIcon.className = 'bi bi-folder me-1';
            identifiersTextarea.placeholder = 'Enter category slugs (one per line):\nelektronics\nfashion\nhome-appliances\n...';
            identifiersHelpText.textContent = 'Enter one category slug per line. All products in these categories will be updated.';
        } else {
            // Product mode
            identifiersLabel.textContent = 'Product SKUs or Slugs';
            identifiersIcon.className = 'bi bi-upc-scan me-1';
            identifiersTextarea.placeholder = 'Enter SKUs or slugs (one per line):\nABC123\nproduct-slug\nXYZ789\n...';
            identifiersHelpText.textContent = 'Enter one SKU or slug per line. You can paste from Excel/CSV.';
        }
    });

    // Update example calculation when percentage changes
    document.getElementById('percentage').addEventListener('input', function() {
        const percentage = parseFloat(this.value) || 0;
        const originalPrice = 100;
        const newPrice = originalPrice * (1 + (percentage / 100));
        const salePrice = originalPrice;
        const discount = ((newPrice - salePrice) / newPrice) * 100;

        document.getElementById('examplePercentage').textContent = percentage.toFixed(2);
        document.getElementById('exampleNewPrice').textContent = newPrice.toFixed(2);
        document.getElementById('exampleSalePrice').textContent = salePrice.toFixed(2);
        document.getElementById('exampleDiscount').textContent = discount.toFixed(2);
    });

    // Handle form submission — chunked
    document.getElementById('promotionForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const form       = e.target;
        const applyBtn   = document.getElementById('applyBtn');
        const alertEl    = document.getElementById('alertContainer');
        const progressEl = document.getElementById('applyProgress');

        alertEl.innerHTML = '';

        // Gather lines from file or textarea
        const fileInput = document.getElementById('promoIdentifiersFileInput');
        let lines;
        if (fileInput.files.length > 0) {
            lines = await readFileLines(fileInput.files[0]);
        } else {
            const txt = document.getElementById('identifiers').value.trim();
            if (!txt) {
                alertEl.innerHTML = `<div class="alert alert-warning">Please paste SKUs/slugs or upload a .txt file.</div>`;
                return;
            }
            lines = txt.split(/\r?\n/).map(l => l.trim().split(',')[0].trim()).filter(l => l);
        }

        if (!lines.length) {
            alertEl.innerHTML = `<div class="alert alert-warning">No valid identifiers found.</div>`;
            return;
        }

        const isCategoryMode = document.getElementById('is_category_mode').checked ? '1' : '0';
        const isSaleEnable   = document.getElementById('is_sale_enable').checked ? '1' : '0';
        const percentage     = document.getElementById('percentage').value;
        const saleStartsAt   = document.getElementById('sale_starts_at').value || null;
        const saleExpiredAt  = document.getElementById('sale_expired_at').value || null;

        applyBtn.disabled = true;
        applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing…';
        progressEl.style.display = '';
        document.getElementById('resultsContainer').innerHTML =
            '<div class="text-center py-4 text-muted"><span class="spinner-border"></span><p class="mt-2">Processing chunks…</p></div>';

        const chunks = chunkArray(lines, CHUNK_SIZE);
        const aggData = { mode: isCategoryMode === '1' ? 'category' : 'product', updated_count: 0, total_found: 0,
                          identifiers_processed: lines.length, updates: [], errors: [] };

        for (let i = 0; i < chunks.length; i++) {
            setProgress('applyProgressBar','applyProgressText','applyProgressCount',
                i * CHUNK_SIZE, lines.length, i + 1, chunks.length);
            try {
                const res = await fetch('{{ route("admin.bulk-promotion.apply") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        identifiers:     chunks[i].join('\n'),
                        is_category_mode: isCategoryMode,
                        percentage,
                        is_sale_enable:  isSaleEnable,
                        sale_starts_at:  saleStartsAt,
                        sale_expired_at: saleExpiredAt,
                    }),
                });
                const json = await res.json();
                if (json.success && json.data) {
                    aggData.updated_count += json.data.updated_count || 0;
                    aggData.total_found   += json.data.total_found   || 0;
                    aggData.updates.push(...(json.data.updates || []));
                    aggData.errors.push(...(json.data.errors  || []));
                } else if (!json.success) {
                    aggData.errors.push(`Chunk ${i+1}: ${json.message}`);
                }
            } catch (err) {
                aggData.errors.push(`Chunk ${i+1} request failed.`);
            }
        }

        setProgress('applyProgressBar','applyProgressText','applyProgressCount',
            lines.length, lines.length, chunks.length, chunks.length);
        finishProgress('applyProgressBar');

        alertEl.innerHTML = `<div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><strong>Done!</strong> ${aggData.updated_count} product(s) updated across ${chunks.length} chunk(s).
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        displayResults(aggData);
        document.querySelector('.col-md-7').scrollIntoView({ behavior: 'smooth', block: 'start' });

        applyBtn.disabled = false;
        applyBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Apply Promotion';
    });

    function displayResults(data) {
        const resultsContainer = document.getElementById('resultsContainer');

        let html = '';

        // Summary
        html += `
            <div class="alert alert-info mb-4">
                <h6 class="mb-2"><i class="bi bi-info-circle me-2"></i>Summary</h6>
                <ul class="mb-0">
                    <li><strong>Mode:</strong> ${data.mode === 'category' ? 'Category Mode' : 'Product Mode'}</li>
                    <li><strong>${data.mode === 'category' ? 'Categories' : 'Identifiers'} Processed:</strong> ${data.identifiers_processed || 0}</li>
                    <li><strong>Products Found:</strong> ${data.total_found}</li>
                    <li><strong>Successfully Updated:</strong> ${data.updated_count}</li>
                    ${data.errors.length > 0 ? `<li class="text-danger"><strong>Errors:</strong> ${data.errors.length}</li>` : ''}
                </ul>
            </div>
        `;

        // Updated products
        if (data.updates && data.updates.length > 0) {
            html += '<h6 class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Updated Products</h6>';

            data.updates.forEach(update => {
                html += `
                    <div class="result-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${update.name}</strong>
                                <br>
                                <small class="text-muted">SKU: ${update.sku}</small>
                            </div>
                            <span class="discount-badge">-${update.discount}% OFF</span>
                        </div>
                        <div class="price-change mt-2">
                            <span class="price-old">Original: $${update.old_price}</span>
                            <i class="bi bi-arrow-right"></i>
                            <span class="price-new">New Price: $${update.new_price}</span>
                            <span class="price-sale">Sale: $${update.sale_price}</span>
                        </div>
                    </div>
                `;
            });
        }

        // Errors
        if (data.errors && data.errors.length > 0) {
            html += '<h6 class="mb-3 mt-4"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Errors</h6>';

            data.errors.forEach(error => {
                html += `
                    <div class="result-item error">
                        <i class="bi bi-x-circle text-danger me-2"></i>${error}
                    </div>
                `;
            });
        }

        resultsContainer.innerHTML = html;
    }

    async function resetForm() {
        const _r = await Swal.fire({ title: 'Are you sure?', text: 'Are you sure you want to reset the form? All entered data will be cleared.', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
        if (_r.isConfirmed) {
            document.getElementById('promotionForm').reset();
            document.getElementById('percentage').value = '20';
            document.getElementById('is_sale_enable').checked = true;
            document.getElementById('is_category_mode').checked = false;

            // Reset labels to product mode
            document.getElementById('identifiersLabelText').textContent = 'Product SKUs or Slugs';
            document.getElementById('identifiersIcon').className = 'bi bi-upc-scan me-1';
            document.getElementById('identifiers').placeholder = 'Enter SKUs or slugs (one per line):\nABC123\nproduct-slug\nXYZ789\n...';
            document.getElementById('identifiersHelpText').textContent = 'Enter one SKU or slug per line. You can paste from Excel/CSV.';

            document.getElementById('resultsContainer').innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 64px;"></i>
                    <p class="mt-3">No results yet. Apply promotion to see updates.</p>
                </div>
            `;
            document.getElementById('alertContainer').innerHTML = '';

            // Reset example calculation
            document.getElementById('examplePercentage').textContent = '20';
            document.getElementById('exampleNewPrice').textContent = '120';
            document.getElementById('exampleSalePrice').textContent = '100';
            document.getElementById('exampleDiscount').textContent = '16.67';
        }
    }

    // ── Reset Promotions — chunked ────────────────────────────────────────
    async function runReset() {
        const fileInput  = document.getElementById('resetSkusFileInput');
        const btn        = document.getElementById('reset-btn');
        const box        = document.getElementById('reset-results');
        const progressEl = document.getElementById('resetProgress');

        let lines;
        if (fileInput.files.length > 0) {
            lines = await readFileLines(fileInput.files[0]);
        } else {
            const txt = document.getElementById('reset-skus').value.trim();
            if (!txt) {
                box.innerHTML = `<div class="alert alert-warning">Please paste at least one SKU or upload a .txt file.</div>`;
                return;
            }
            lines = txt.split(/\r?\n/).map(l => l.trim().split(',')[0].trim()).filter(l => l);
        }

        if (!lines.length) {
            box.innerHTML = `<div class="alert alert-warning">No valid SKUs found.</div>`;
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Resetting…';
        box.innerHTML = '<div class="text-center py-4 text-muted"><span class="spinner-border"></span><p class="mt-2">Processing chunks…</p></div>';
        progressEl.style.display = '';

        const chunks = chunkArray(lines, CHUNK_SIZE);
        const agg = { updated_count: 0, not_found_count: 0, updated: [], not_found: [] };

        for (let i = 0; i < chunks.length; i++) {
            setProgress('resetProgressBar','resetProgressText','resetProgressCount',
                i * CHUNK_SIZE, lines.length, i + 1, chunks.length);
            try {
                const res = await fetch('{{ route("admin.bulk-promotion.reset") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ skus: chunks[i].join('\n') }),
                });
                const json = await res.json();
                if (json.success && json.data) {
                    agg.updated_count     += json.data.updated_count     || 0;
                    agg.not_found_count   += json.data.not_found_count   || 0;
                    agg.updated.push(...(json.data.updated   || []));
                    agg.not_found.push(...(json.data.not_found || []));
                }
            } catch (err) { /* continue */ }
        }

        setProgress('resetProgressBar','resetProgressText','resetProgressCount',
            lines.length, lines.length, chunks.length, chunks.length);
        finishProgress('resetProgressBar');

        let html = `
            <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><strong>${agg.updated_count} product(s) reset across ${chunks.length} chunk(s).</strong></div>
            <div class="d-flex gap-3 mb-3">
                <div class="admin-stat-card flex-fill">
                    <div class="admin-stat-icon" style="background:#dcfce7;"><i class="bi bi-check-circle-fill text-success"></i></div>
                    <div><div class="admin-stat-value">${agg.updated_count}</div><div class="admin-stat-label">Reset</div></div>
                </div>
                <div class="admin-stat-card flex-fill">
                    <div class="admin-stat-icon" style="background:#fee2e2;"><i class="bi bi-question-circle-fill text-danger"></i></div>
                    <div><div class="admin-stat-value">${agg.not_found_count}</div><div class="admin-stat-label">Not Found</div></div>
                </div>
            </div>`;

        if (agg.updated.length > 0) {
            html += `<h6 class="mb-2"><i class="bi bi-check-circle-fill text-success me-1"></i>Reset Successfully</h6>
                     <div class="table-responsive"><table class="table table-sm table-hover mb-3">
                     <thead><tr><th>SKU</th><th>Product</th></tr></thead><tbody>`;
            agg.updated.forEach(r => { html += `<tr><td><code>${r.sku}</code></td><td>${r.name}</td></tr>`; });
            html += `</tbody></table></div>`;
        }
        if (agg.not_found.length > 0) {
            html += `<h6 class="mb-2 text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Not Found</h6>
                     <div class="table-responsive"><table class="table table-sm mb-0">
                     <thead><tr><th>SKU</th></tr></thead><tbody>`;
            agg.not_found.forEach(s => { html += `<tr><td><code class="text-danger">${s}</code></td></tr>`; });
            html += `</tbody></table></div>`;
        }

        box.innerHTML = html;
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle me-2"></i>Reset Promotions';
    }
    // ── Sale Price Upload — chunked ───────────────────────────────────────
    async function runSkuSalePrices() {
        const fileInput    = document.getElementById('sspFileInput');
        const percentage   = document.getElementById('ssp-percentage').value;
        const isSaleEnable = document.getElementById('ssp-is-sale-enable').checked ? '1' : '0';
        const startsAt     = document.getElementById('ssp-sale-starts-at').value || null;
        const expiredAt    = document.getElementById('ssp-sale-expired-at').value || null;
        const btn          = document.getElementById('ssp-submit-btn');
        const alertBox     = document.getElementById('sspAlertBox');
        const resultsBox   = document.getElementById('ssp-results');
        const progressEl   = document.getElementById('sspProgress');

        alertBox.innerHTML = '';

        let lines;
        if (fileInput.files.length > 0) {
            lines = await readFileLines(fileInput.files[0]);
        } else {
            const txt = document.getElementById('ssp-sku-prices').value.trim();
            if (!txt) {
                alertBox.innerHTML = `<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Please paste <code>sku,sale_price</code> lines or upload a .txt file.</div>`;
                return;
            }
            lines = txt.split(/\r?\n/).map(l => l.trim()).filter(l => l);
        }

        if (!lines.length) {
            alertBox.innerHTML = `<div class="alert alert-warning">No valid lines found.</div>`;
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing…';
        resultsBox.innerHTML = '<div class="text-center py-4 text-muted"><span class="spinner-border"></span><p class="mt-2">Processing chunks…</p></div>';
        progressEl.style.display = '';

        const chunks = chunkArray(lines, CHUNK_SIZE);
        const agg = { updated_count: 0, not_found_count: 0, updates: [], not_found: [], errors: [] };

        for (let i = 0; i < chunks.length; i++) {
            setProgress('sspProgressBar','sspProgressText','sspProgressCount',
                i * CHUNK_SIZE, lines.length, i + 1, chunks.length);
            try {
                const res = await fetch('{{ route("admin.bulk-promotion.apply-sku-prices") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sku_prices:      chunks[i].join('\n'),
                        percentage,
                        is_sale_enable:  isSaleEnable,
                        sale_starts_at:  startsAt,
                        sale_expired_at: expiredAt,
                    }),
                });
                const json = await res.json();
                if (json.success && json.data) {
                    agg.updated_count   += json.data.updated_count   || 0;
                    agg.not_found_count += json.data.not_found_count || 0;
                    agg.updates.push(...(json.data.updates   || []));
                    agg.not_found.push(...(json.data.not_found || []));
                    agg.errors.push(...(json.data.errors     || []));
                }
            } catch (err) { agg.errors.push(`Chunk ${i+1} request failed.`); }
        }

        setProgress('sspProgressBar','sspProgressText','sspProgressCount',
            lines.length, lines.length, chunks.length, chunks.length);
        finishProgress('sspProgressBar');

        alertBox.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><strong>${agg.updated_count} product(s) updated across ${chunks.length} chunk(s).</strong></div>`;

        let html = `<div class="d-flex gap-3 mb-3">
            <div class="admin-stat-card flex-fill">
                <div class="admin-stat-icon" style="background:#dcfce7;"><i class="bi bi-check-circle-fill text-success"></i></div>
                <div><div class="admin-stat-value">${agg.updated_count}</div><div class="admin-stat-label">Updated</div></div>
            </div>
            <div class="admin-stat-card flex-fill">
                <div class="admin-stat-icon" style="background:#fee2e2;"><i class="bi bi-question-circle-fill text-danger"></i></div>
                <div><div class="admin-stat-value">${agg.not_found_count}</div><div class="admin-stat-label">Not Found</div></div>
            </div>
        </div>`;

        if (agg.updates.length > 0) {
            html += '<h6 class="mb-2"><i class="bi bi-check-circle-fill text-success me-1"></i>Updated Products</h6>';
            agg.updates.forEach(u => {
                html += `<div class="ssp-result-row ${u.price_changed ? 'inflated' : ''}">
                    <div class="d-flex justify-content-between">
                        <div><strong>${u.name}</strong> <small class="text-muted">SKU: ${u.sku}</small></div>
                        <span class="discount-badge">${u.discount}% OFF</span>
                    </div><div class="mt-1">`;
                if (u.price_changed) html += `<small class="text-warning"><i class="bi bi-arrow-up-circle-fill me-1"></i>Price inflated: ${u.old_price} &rarr; <strong>${u.new_price}</strong></small><br>`;
                html += `<small class="text-danger"><i class="bi bi-tag-fill me-1"></i>Sale price: <strong>${u.sale_price}</strong></small></div></div>`;
            });
        }
        if (agg.not_found.length > 0) {
            html += '<h6 class="mb-2 mt-3 text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Not Found</h6>';
            html += '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>SKU</th></tr></thead><tbody>';
            agg.not_found.forEach(s => { html += `<tr><td><code class="text-danger">${s}</code></td></tr>`; });
            html += '</tbody></table></div>';
        }
        if (agg.errors.length > 0) {
            html += '<h6 class="mb-2 mt-3 text-warning"><i class="bi bi-exclamation-circle-fill me-1"></i>Warnings</h6>';
            agg.errors.forEach(e => { html += `<div class="alert alert-warning py-1 mb-1" style="font-size:.8rem;">${e}</div>`; });
        }

        resultsBox.innerHTML = html;
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Apply Sale Prices';
    }
</script>
@endpush

