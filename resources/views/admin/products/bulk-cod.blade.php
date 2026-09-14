@extends('admin.layout')
@section('title', 'Bulk Cash on Delivery')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2" style="color:#15803d"></i>Bulk Cash on Delivery</h4>
        <p class="text-muted mb-0 small">Turn Cash on Delivery on or off for matching products, without touching anything else</p>
    </div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Enable by SKU --}}
<div class="card mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-upc-scan me-1"></i>Enable Cash on Delivery by SKU</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.products.bulk-cod.process') }}" id="bulkEnableCodForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fw-semibold mb-0">SKUs <span class="text-muted fw-normal">(one per line)</span></label>
                    <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer;">
                        <i class="bi bi-upload me-1"></i>Upload .txt
                        <input type="file" name="sku_file" id="enableCodFileInput" accept=".txt" style="display:none;">
                    </label>
                </div>
                <div id="enableCodFileSelectedBanner" class="alert alert-info py-2 mb-2 d-none">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <span id="enableCodFileSelectedName"></span> selected — click <strong>Enable Cash on Delivery</strong> to process it directly.
                </div>
                <textarea name="skus" id="enableCodSkusInput" class="form-control font-monospace @error('skus') is-invalid @enderror"
                          rows="12" placeholder="98578066ZW&#10;98578066ZW2&#10;98578066ZW3"
                          autofocus>{{ old('skus') }}</textarea>
                @error('skus')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text" id="enableCodSkuCount">Lines: 0</div>
            </div>

            <div id="enableCodProgress" class="mb-3" style="display:none;">
                <div class="d-flex justify-content-between mb-1">
                    <small class="fw-semibold" id="enableCodProgressText">Processing…</small>
                    <small class="text-muted" id="enableCodProgressCount"></small>
                </div>
                <div class="progress" style="height:22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                         id="enableCodProgressBar" role="progressbar" style="width:0%">0%</div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-success" id="enableCodSubmitBtn">
                    <i class="bi bi-cash-coin me-1"></i>Enable Cash on Delivery
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('enableCodSkusInput').value = ''; updateEnableCodCount();">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </button>
            </div>
        </form>
    </div>
</div>

<div id="enableCodAjaxResults"></div>

@if(session('bulk_cod_affected') !== null || session('bulk_cod_not_found') !== null)
    @php
        $codAffected = session('bulk_cod_affected', []);
        $codNotFound = session('bulk_cod_not_found', []);
        $codTotal    = count($codAffected) + count($codNotFound);
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-light text-center py-3">
                <div class="fs-1 fw-bold">{{ $codTotal }}</div>
                <div class="text-muted small">SKUs Submitted</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#f0fdf4">
                <div class="fs-1 fw-bold text-success">{{ count($codAffected) }}</div>
                <div class="text-muted small">Cash on Delivery Enabled</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#fafafa">
                <div class="fs-1 fw-bold text-warning">{{ count($codNotFound) }}</div>
                <div class="text-muted small">SKUs Not Found</div>
            </div>
        </div>
    </div>

    @if(count($codAffected) > 0)
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-success"></i>
                <strong>Cash on Delivery Enabled ({{ count($codAffected) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>SKU</th><th>Product Name</th></tr></thead>
                    <tbody>
                        @foreach($codAffected as $i => $row)
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

    @if(count($codNotFound) > 0)
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                <strong>Not Found ({{ count($codNotFound) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>SKU</th></tr></thead>
                    <tbody>
                        @foreach($codNotFound as $i => $sku)
                            <tr><td class="text-muted">{{ $i + 1 }}</td><td><code>{{ $sku }}</code></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif

{{-- Disable by SKU --}}
<div class="card mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-upc-scan me-1"></i>Disable Cash on Delivery by SKU</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.products.bulk-cod.disable-by-sku') }}" id="bulkDisableCodForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label fw-semibold mb-0">SKUs <span class="text-muted fw-normal">(one per line)</span></label>
                    <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer;">
                        <i class="bi bi-upload me-1"></i>Upload .txt
                        <input type="file" name="sku_file" id="disableCodFileInput" accept=".txt" style="display:none;">
                    </label>
                </div>
                <div id="disableCodFileSelectedBanner" class="alert alert-info py-2 mb-2 d-none">
                    <i class="bi bi-file-earmark-text me-1"></i>
                    <span id="disableCodFileSelectedName"></span> selected — click <strong>Disable Cash on Delivery</strong> to process it directly.
                </div>
                <textarea name="skus" id="disableCodSkusInput" class="form-control font-monospace @error('skus') is-invalid @enderror"
                          rows="12" placeholder="98578066ZW&#10;98578066ZW2&#10;98578066ZW3">{{ old('skus') }}</textarea>
                @error('skus')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text" id="disableCodSkuCount">Lines: 0</div>
            </div>

            <div id="disableCodProgress" class="mb-3" style="display:none;">
                <div class="d-flex justify-content-between mb-1">
                    <small class="fw-semibold" id="disableCodProgressText">Processing…</small>
                    <small class="text-muted" id="disableCodProgressCount"></small>
                </div>
                <div class="progress" style="height:22px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger"
                         id="disableCodProgressBar" role="progressbar" style="width:0%">0%</div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-danger" id="disableCodSubmitBtn">
                    <i class="bi bi-cash-coin me-1"></i>Disable Cash on Delivery
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('disableCodSkusInput').value = ''; updateDisableCodCount();">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </button>
            </div>
        </form>
    </div>
</div>

<div id="disableCodAjaxResults"></div>

@if(session('bulk_cod_disable_affected') !== null || session('bulk_cod_disable_not_found') !== null)
    @php
        $codDisableAffected = session('bulk_cod_disable_affected', []);
        $codDisableNotFound = session('bulk_cod_disable_not_found', []);
        $codDisableTotal    = count($codDisableAffected) + count($codDisableNotFound);
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-light text-center py-3">
                <div class="fs-1 fw-bold">{{ $codDisableTotal }}</div>
                <div class="text-muted small">SKUs Submitted</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#fef2f2">
                <div class="fs-1 fw-bold text-danger">{{ count($codDisableAffected) }}</div>
                <div class="text-muted small">Cash on Delivery Disabled</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#fafafa">
                <div class="fs-1 fw-bold text-warning">{{ count($codDisableNotFound) }}</div>
                <div class="text-muted small">SKUs Not Found</div>
            </div>
        </div>
    </div>

    @if(count($codDisableAffected) > 0)
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-danger"></i>
                <strong>Cash on Delivery Disabled ({{ count($codDisableAffected) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>SKU</th><th>Product Name</th></tr></thead>
                    <tbody>
                        @foreach($codDisableAffected as $i => $row)
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

    @if(count($codDisableNotFound) > 0)
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                <strong>Not Found ({{ count($codDisableNotFound) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>#</th><th>SKU</th></tr></thead>
                    <tbody>
                        @foreach($codDisableNotFound as $i => $sku)
                            <tr><td class="text-muted">{{ $i + 1 }}</td><td><code>{{ $sku }}</code></td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif

@endsection

@push('scripts')
<script>
const COD_CHUNK_SIZE = 200;

function updateEnableCodCount() {
    const val = document.getElementById('enableCodSkusInput').value.trim();
    const n = val ? val.split(/\r?\n/).filter(l => l.trim()).length : 0;
    document.getElementById('enableCodSkuCount').textContent = 'Lines: ' + n;
}
document.getElementById('enableCodSkusInput').addEventListener('input', updateEnableCodCount);
updateEnableCodCount();

function updateDisableCodCount() {
    const val = document.getElementById('disableCodSkusInput').value.trim();
    const n = val ? val.split(/\r?\n/).filter(l => l.trim()).length : 0;
    document.getElementById('disableCodSkuCount').textContent = 'Lines: ' + n;
}
document.getElementById('disableCodSkusInput').addEventListener('input', updateDisableCodCount);
updateDisableCodCount();

document.getElementById('enableCodFileInput').addEventListener('change', function () {
    const banner = document.getElementById('enableCodFileSelectedBanner');
    const nameEl = document.getElementById('enableCodFileSelectedName');
    if (this.files[0]) {
        nameEl.textContent = this.files[0].name;
        banner.classList.remove('d-none');
        document.getElementById('enableCodSkusInput').value = '';
        updateEnableCodCount();
    } else {
        banner.classList.add('d-none');
    }
});

document.getElementById('disableCodFileInput').addEventListener('change', function () {
    const banner = document.getElementById('disableCodFileSelectedBanner');
    const nameEl = document.getElementById('disableCodFileSelectedName');
    if (this.files[0]) {
        nameEl.textContent = this.files[0].name;
        banner.classList.remove('d-none');
        document.getElementById('disableCodSkusInput').value = '';
        updateDisableCodCount();
    } else {
        banner.classList.add('d-none');
    }
});

function readCodTextLines(file) {
    return new Promise((resolve, reject) => {
        const r = new FileReader();
        r.onload = e => resolve(e.target.result.split(/\r?\n/).map(l => l.trim().split(',')[0].trim()).filter(l => l));
        r.onerror = reject;
        r.readAsText(file);
    });
}

// Wires up one SKU-list bulk form (enable or disable share this exact flow —
// only the endpoint, labels, and colour differ).
function wireCodBulkForm(cfg) {
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
            lines = await readCodTextLines(fileInput.files[0]);
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
        setProgress(0, lines.length, 0, Math.ceil(lines.length / COD_CHUNK_SIZE));

        const allAffected = [], allNotFound = [];
        const chunks = [];
        for (let i = 0; i < lines.length; i += COD_CHUNK_SIZE) chunks.push(lines.slice(i, i + COD_CHUNK_SIZE));

        for (let i = 0; i < chunks.length; i++) {
            setProgress(i * COD_CHUNK_SIZE, lines.length, i + 1, chunks.length);
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

wireCodBulkForm({
    formId: 'bulkEnableCodForm', fileInputId: 'enableCodFileInput', textareaId: 'enableCodSkusInput',
    submitBtnId: 'enableCodSubmitBtn', progressId: 'enableCodProgress', progressBarId: 'enableCodProgressBar',
    progressTextId: 'enableCodProgressText', progressCountId: 'enableCodProgressCount', resultsId: 'enableCodAjaxResults',
    url: '{{ route("admin.products.bulk-cod.process") }}',
    affectedLabel: 'Cash on Delivery Enabled', affectedBg: '#f0fdf4', affectedTextClass: 'text-success',
    submitBtnHtml: '<i class="bi bi-cash-coin me-1"></i>Enable Cash on Delivery',
});

wireCodBulkForm({
    formId: 'bulkDisableCodForm', fileInputId: 'disableCodFileInput', textareaId: 'disableCodSkusInput',
    submitBtnId: 'disableCodSubmitBtn', progressId: 'disableCodProgress', progressBarId: 'disableCodProgressBar',
    progressTextId: 'disableCodProgressText', progressCountId: 'disableCodProgressCount', resultsId: 'disableCodAjaxResults',
    url: '{{ route("admin.products.bulk-cod.disable-by-sku") }}',
    affectedLabel: 'Cash on Delivery Disabled', affectedBg: '#fef2f2', affectedTextClass: 'text-danger',
    submitBtnHtml: '<i class="bi bi-cash-coin me-1"></i>Disable Cash on Delivery',
});
</script>
@endpush
