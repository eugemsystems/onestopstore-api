@extends('admin.layout')
@section('title', 'Bulk Disable Layby')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-2" style="color:#dc2626"></i>Bulk Disable Layby</h4>
        <p class="text-muted mb-0 small">Turn off the layby option for matching products, without disabling the products themselves</p>
    </div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- By SKU --}}
<div class="card mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-upc-scan me-1"></i>Disable Layby by SKU</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.products.bulk-disable-layby.process') }}" id="bulkDisableLaybyForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fw-semibold mb-0">SKUs <span class="text-muted fw-normal">(one per line)</span></label>
                    <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer;">
                        <i class="bi bi-upload me-1"></i>Upload .txt
                        <input type="file" name="sku_file" id="skuFileInput" accept=".txt" style="display:none;">
                    </label>
                </div>
                <div id="fileSelectedBanner" class="alert alert-info py-2 mb-2 d-none">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <span id="fileSelectedName"></span> selected — click <strong>Disable Layby</strong> to process it directly.
                </div>
                <textarea name="skus" id="skusInput" class="form-control font-monospace @error('skus') is-invalid @enderror"
                          rows="12" placeholder="98578066ZW&#10;98578066ZW2&#10;98578066ZW3"
                          autofocus>{{ old('skus') }}</textarea>
                @error('skus')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text" id="skuCount">Lines: 0</div>
            </div>

            <div id="disableProgress" class="mb-3" style="display:none;">
                <div class="d-flex justify-content-between mb-1">
                    <small class="fw-semibold" id="disableProgressText">Processing…</small>
                    <small class="text-muted" id="disableProgressCount"></small>
                </div>
                <div class="progress" style="height:22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger"
                         id="disableProgressBar" role="progressbar" style="width:0%">0%</div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-danger" id="submitBtn">
                    <i class="bi bi-wallet2 me-1"></i>Disable Layby
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('skusInput').value = ''; updateCount();">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </button>
            </div>
        </form>
    </div>
</div>

{{-- AJAX results for the SKU form --}}
<div id="ajaxResults"></div>

@if(session('bulk_layby_affected') !== null || session('bulk_layby_not_found') !== null)
    @php
        $laybyAffected  = session('bulk_layby_affected', []);
        $laybyNotFound  = session('bulk_layby_not_found', []);
        $laybyTotal     = count($laybyAffected) + count($laybyNotFound);
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-light text-center py-3">
                <div class="fs-1 fw-bold">{{ $laybyTotal }}</div>
                <div class="text-muted small">SKUs Submitted</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#fef2f2">
                <div class="fs-1 fw-bold text-danger">{{ count($laybyAffected) }}</div>
                <div class="text-muted small">Layby Disabled</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#fafafa">
                <div class="fs-1 fw-bold text-warning">{{ count($laybyNotFound) }}</div>
                <div class="text-muted small">SKUs Not Found</div>
            </div>
        </div>
    </div>

    @if(count($laybyAffected) > 0)
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-danger"></i>
                <strong>Layby Disabled ({{ count($laybyAffected) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>SKU</th><th>Product Name</th></tr></thead>
                    <tbody>
                        @foreach($laybyAffected as $i => $row)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td><code>{{ $row['sku'] }}</code></td>
                                <td>{{ $row['name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(count($laybyNotFound) > 0)
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                <strong>Not Found ({{ count($laybyNotFound) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>SKU</th></tr></thead>
                    <tbody>
                        @foreach($laybyNotFound as $i => $sku)
                            <tr><td class="text-muted">{{ $i + 1 }}</td><td><code>{{ $sku }}</code></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif

{{-- Re-enable by SKU --}}
<div class="card mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-upc-scan me-1"></i>Remove Layby Restriction by SKU</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.products.bulk-disable-layby.enable-by-sku') }}" id="bulkEnableLaybyForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fw-semibold mb-0">SKUs <span class="text-muted fw-normal">(one per line)</span></label>
                    <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer;">
                        <i class="bi bi-upload me-1"></i>Upload .txt
                        <input type="file" name="sku_file" id="enableSkuFileInput" accept=".txt" style="display:none;">
                    </label>
                </div>
                <div id="enableFileSelectedBanner" class="alert alert-info py-2 mb-2 d-none">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <span id="enableFileSelectedName"></span> selected — click <strong>Re-enable Layby</strong> to process it directly.
                </div>
                <textarea name="skus" id="enableSkusInput" class="form-control font-monospace @error('skus') is-invalid @enderror"
                          rows="12" placeholder="98578066ZW&#10;98578066ZW2&#10;98578066ZW3">{{ old('skus') }}</textarea>
                @error('skus')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text" id="enableSkuCount">Lines: 0</div>
            </div>

            <div id="enableProgress" class="mb-3" style="display:none;">
                <div class="d-flex justify-content-between mb-1">
                    <small class="fw-semibold" id="enableProgressText">Processing…</small>
                    <small class="text-muted" id="enableProgressCount"></small>
                </div>
                <div class="progress" style="height:22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                         id="enableProgressBar" role="progressbar" style="width:0%">0%</div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-success" id="enableSubmitBtn">
                    <i class="bi bi-wallet2 me-1"></i>Re-enable Layby
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('enableSkusInput').value = ''; updateEnableCount();">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </button>
            </div>
        </form>
    </div>
</div>

<div id="enableAjaxResults"></div>

@if(session('bulk_layby_enable_affected') !== null || session('bulk_layby_enable_not_found') !== null)
    @php
        $enableAffected = session('bulk_layby_enable_affected', []);
        $enableNotFound = session('bulk_layby_enable_not_found', []);
        $enableTotal    = count($enableAffected) + count($enableNotFound);
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-light text-center py-3">
                <div class="fs-1 fw-bold">{{ $enableTotal }}</div>
                <div class="text-muted small">SKUs Submitted</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#f0fdf4">
                <div class="fs-1 fw-bold text-success">{{ count($enableAffected) }}</div>
                <div class="text-muted small">Layby Re-enabled</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#fafafa">
                <div class="fs-1 fw-bold text-warning">{{ count($enableNotFound) }}</div>
                <div class="text-muted small">SKUs Not Found</div>
            </div>
        </div>
    </div>

    @if(count($enableAffected) > 0)
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success"></i>
                <strong>Layby Re-enabled ({{ count($enableAffected) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>SKU</th><th>Product Name</th></tr></thead>
                    <tbody>
                        @foreach($enableAffected as $i => $row)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td><code>{{ $row['sku'] }}</code></td>
                                <td>{{ $row['name'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(count($enableNotFound) > 0)
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                <strong>Not Found ({{ count($enableNotFound) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>SKU</th></tr></thead>
                    <tbody>
                        @foreach($enableNotFound as $i => $sku)
                            <tr><td class="text-muted">{{ $i + 1 }}</td><td><code>{{ $sku }}</code></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif

{{-- By Delivery Text --}}
<div class="card mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-truck me-1"></i>Disable Layby by Delivery Text</div>
    <div class="card-body">
        <p class="text-muted small">
            Matches products whose <strong>Estimated Delivery Text</strong> (shown on the Shipping tab) is exactly one of
            the values below (case-insensitive) — e.g. <code>Same Day Delivery</code>. This is free text with no fixed
            list, so type it exactly as it appears on the product.
        </p>
        <form method="POST" action="{{ route('admin.products.bulk-disable-layby.by-delivery-text') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Delivery Text Values <span class="text-muted fw-normal">(one per line)</span></label>
                <textarea name="delivery_texts" class="form-control @error('delivery_texts') is-invalid @enderror"
                          rows="4" placeholder="Same Day Delivery&#10;Next Day Delivery">{{ old('delivery_texts') }}</textarea>
                @error('delivery_texts')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-wallet2 me-1"></i>Disable Layby for These Delivery Texts
            </button>
        </form>
    </div>
</div>

@if(session('bulk_layby_delivery_affected') !== null)
    @php
        $deliveryAffected = session('bulk_layby_delivery_affected', []);
        $deliveryTexts = session('bulk_layby_delivery_texts', []);
    @endphp

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill text-danger"></i>
            <strong>Layby Disabled for "{{ implode('", "', $deliveryTexts) }}" ({{ count($deliveryAffected) }})</strong>
        </div>
        @if(count($deliveryAffected) > 0)
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>SKU</th><th>Product Name</th><th>Delivery Text</th></tr></thead>
                    <tbody>
                        @foreach($deliveryAffected as $i => $row)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td><code>{{ $row['sku'] }}</code></td>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['estimated_delivery_text'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body text-muted">No products matched those delivery text values.</div>
        @endif
    </div>
@endif

@endsection

@push('scripts')
<script>
const CHUNK_SIZE = 200;

function updateCount() {
    const val = document.getElementById('skusInput').value.trim();
    const n = val ? val.split(/\r?\n/).filter(l => l.trim()).length : 0;
    document.getElementById('skuCount').textContent = 'Lines: ' + n;
}
document.getElementById('skusInput').addEventListener('input', updateCount);
updateCount();

document.getElementById('skuFileInput').addEventListener('change', function () {
    const banner = document.getElementById('fileSelectedBanner');
    const nameEl = document.getElementById('fileSelectedName');
    if (this.files[0]) {
        nameEl.textContent = this.files[0].name;
        banner.classList.remove('d-none');
        document.getElementById('skusInput').value = '';
        updateCount();
    } else {
        banner.classList.add('d-none');
    }
});

function readTextLines(file) {
    return new Promise((resolve, reject) => {
        const r = new FileReader();
        r.onload = e => resolve(e.target.result.split(/\r?\n/).map(l => l.trim().split(',')[0].trim()).filter(l => l));
        r.onerror = reject;
        r.readAsText(file);
    });
}

// Wires up one SKU-list bulk form (disable or re-enable share this exact flow —
// only the endpoint, labels, and colour differ).
function wireSkuBulkForm(cfg) {
    function setProgress(done, total, chunkIdx, totalChunks) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        const bar = document.getElementById(cfg.progressBarId);
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';
        document.getElementById(cfg.progressTextId).textContent =
            done >= total ? 'Done!' : `Chunk ${chunkIdx} of ${totalChunks} — processing…`;
        document.getElementById(cfg.progressCountId).textContent = `${done} / ${total} SKUs`;
    }

    function renderResults(affected, notFound) {
        const total = affected.length + notFound.length;
        let html = `
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="fs-1 fw-bold">${total}</div>
                        <div class="text-muted small">SKUs Submitted</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 text-center py-3" style="background:${cfg.affectedBg}">
                        <div class="fs-1 fw-bold ${cfg.affectedTextClass}">${affected.length}</div>
                        <div class="text-muted small">${cfg.affectedLabel}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 text-center py-3" style="background:#fafafa">
                        <div class="fs-1 fw-bold text-warning">${notFound.length}</div>
                        <div class="text-muted small">SKUs Not Found</div>
                    </div>
                </div>
            </div>`;

        if (affected.length > 0) {
            html += `<div class="card mb-3">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill ${cfg.affectedTextClass}"></i>
                    <strong>${cfg.affectedLabel} (${affected.length})</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>#</th><th>SKU</th><th>Product Name</th></tr></thead>
                        <tbody>${affected.map((r, i) => `<tr><td class="text-muted">${i+1}</td><td><code>${r.sku}</code></td><td>${r.name}</td></tr>`).join('')}</tbody>
                    </table>
                </div>
            </div>`;
        }

        if (notFound.length > 0) {
            html += `<div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                    <strong>Not Found (${notFound.length})</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>#</th><th>SKU</th></tr></thead>
                        <tbody>${notFound.map((s, i) => `<tr><td class="text-muted">${i+1}</td><td><code>${s}</code></td></tr>`).join('')}</tbody>
                    </table>
                </div>
            </div>`;
        }

        document.getElementById(cfg.resultsId).innerHTML = html;
    }

    document.getElementById(cfg.formId).addEventListener('submit', async function (e) {
        e.preventDefault();

        const fileInput = document.getElementById(cfg.fileInputId);
        let lines;

        if (fileInput.files.length > 0) {
            lines = await readTextLines(fileInput.files[0]);
        } else {
            const val = document.getElementById(cfg.textareaId).value.trim();
            if (!val) { alert('No SKUs provided.'); return; }
            lines = val.split(/\r?\n/).map(l => l.trim().split(',')[0].trim()).filter(l => l);
        }

        if (!lines.length) { alert('No valid SKUs found.'); return; }

        const btn = document.getElementById(cfg.submitBtnId);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing…';
        document.getElementById(cfg.resultsId).innerHTML = '';
        document.getElementById(cfg.progressId).style.display = '';
        setProgress(0, lines.length, 0, Math.ceil(lines.length / CHUNK_SIZE));

        const allAffected = [], allNotFound = [];
        const chunks = [];
        for (let i = 0; i < lines.length; i += CHUNK_SIZE) chunks.push(lines.slice(i, i + CHUNK_SIZE));

        for (let i = 0; i < chunks.length; i++) {
            setProgress(i * CHUNK_SIZE, lines.length, i + 1, chunks.length);
            try {
                const fd = new FormData();
                fd.append('_token', '{{ csrf_token() }}');
                fd.append('skus', chunks[i].join('\n'));
                const res = await fetch(cfg.url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd,
                });
                const json = await res.json();
                if (json.success) {
                    allAffected.push(...json.data.affected);
                    allNotFound.push(...json.data.not_found);
                }
            } catch (err) {
                console.error('Chunk ' + (i+1) + ' failed:', err);
            }
        }

        setProgress(lines.length, lines.length, chunks.length, chunks.length);
        renderResults(allAffected, allNotFound);

        btn.disabled = false;
        btn.innerHTML = cfg.submitBtnHtml;

        document.getElementById(cfg.progressId).querySelector('.progress-bar')
            .classList.replace('progress-bar-animated', 'bg-success');
        document.getElementById(cfg.progressId).querySelector('.progress-bar')
            .classList.remove('progress-bar-striped');
    });
}

wireSkuBulkForm({
    formId: 'bulkDisableLaybyForm', fileInputId: 'skuFileInput', textareaId: 'skusInput',
    submitBtnId: 'submitBtn', progressId: 'disableProgress', progressBarId: 'disableProgressBar',
    progressTextId: 'disableProgressText', progressCountId: 'disableProgressCount', resultsId: 'ajaxResults',
    url: '{{ route("admin.products.bulk-disable-layby.process") }}',
    affectedLabel: 'Layby Disabled', affectedBg: '#fef2f2', affectedTextClass: 'text-danger',
    submitBtnHtml: '<i class="bi bi-wallet2 me-1"></i>Disable Layby',
});

wireSkuBulkForm({
    formId: 'bulkEnableLaybyForm', fileInputId: 'enableSkuFileInput', textareaId: 'enableSkusInput',
    submitBtnId: 'enableSubmitBtn', progressId: 'enableProgress', progressBarId: 'enableProgressBar',
    progressTextId: 'enableProgressText', progressCountId: 'enableProgressCount', resultsId: 'enableAjaxResults',
    url: '{{ route("admin.products.bulk-disable-layby.enable-by-sku") }}',
    affectedLabel: 'Layby Re-enabled', affectedBg: '#f0fdf4', affectedTextClass: 'text-success',
    submitBtnHtml: '<i class="bi bi-wallet2 me-1"></i>Re-enable Layby',
});

document.getElementById('enableSkuFileInput').addEventListener('change', function () {
    const banner = document.getElementById('enableFileSelectedBanner');
    const nameEl = document.getElementById('enableFileSelectedName');
    if (this.files[0]) {
        nameEl.textContent = this.files[0].name;
        banner.classList.remove('d-none');
        document.getElementById('enableSkusInput').value = '';
        updateEnableCount();
    } else {
        banner.classList.add('d-none');
    }
});

function updateEnableCount() {
    const val = document.getElementById('enableSkusInput').value.trim();
    const n = val ? val.split(/\r?\n/).filter(l => l.trim()).length : 0;
    document.getElementById('enableSkuCount').textContent = 'Lines: ' + n;
}
document.getElementById('enableSkusInput').addEventListener('input', updateEnableCount);
updateEnableCount();
</script>
@endpush
