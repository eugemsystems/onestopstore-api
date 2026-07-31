@extends('admin.layout')
@section('title', 'Bulk Disable Products')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-slash-circle me-2" style="color:#dc2626"></i>Bulk Disable Products</h4>
        <p class="text-muted mb-0 small">Paste SKUs (one per line) to disable matching products — sets status and approval to inactive</p>
    </div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Input Form --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.products.bulk-disable.process') }}" id="bulkDisableForm" enctype="multipart/form-data">
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
                    <span id="fileSelectedName"></span> selected — click <strong>Disable Products</strong> to process it directly.
                </div>
                <textarea name="skus" id="skusInput" class="form-control font-monospace @error('skus') is-invalid @enderror"
                          rows="14" placeholder="98578066ZW&#10;98578066ZW2&#10;98578066ZW3"
                          autofocus>{{ old('skus') }}</textarea>
                @error('skus')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text" id="skuCount">Lines: 0</div>
            </div>
            {{-- Progress bar (hidden until processing starts) --}}
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

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="permanently_disable" name="permanently_disable" value="1">
                    <label class="form-check-label fw-semibold text-danger" for="permanently_disable">
                        <i class="bi bi-slash-circle me-1"></i>Permanently disable these products
                    </label>
                </div>
                <div class="form-text text-danger" id="permDisableHint" style="display:none;">
                    These products will be permanently disabled — no import script will ever re-enable them.
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-danger" id="submitBtn">
                    <i class="bi bi-slash-circle me-1"></i>Disable Products
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('skusInput').value = ''; updateCount();">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </button>
            </div>
        </form>
    </div>
</div>

{{-- AJAX results (populated by JS chunked processing) --}}
<div id="ajaxResults"></div>

{{-- Results --}}
@if(session('bulk_affected') !== null || session('bulk_not_found') !== null)
    @php
        $affected  = session('bulk_affected', []);
        $notFound  = session('bulk_not_found', []);
        $total     = count($affected) + count($notFound);
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 bg-light text-center py-3">
                <div class="fs-1 fw-bold">{{ $total }}</div>
                <div class="text-muted small">SKUs Submitted</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#fef2f2">
                <div class="fs-1 fw-bold text-danger">{{ count($affected) }}</div>
                <div class="text-muted small">Products Disabled</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 text-center py-3" style="background:#fafafa">
                <div class="fs-1 fw-bold text-warning">{{ count($notFound) }}</div>
                <div class="text-muted small">SKUs Not Found</div>
            </div>
        </div>
    </div>

    @if(count($affected) > 0)
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill text-danger"></i>
                <strong>Disabled ({{ count($affected) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>SKU</th>
                            <th>Product Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($affected as $i => $row)
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

    @if(count($notFound) > 0)
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                <strong>Not Found ({{ count($notFound) }})</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>SKU</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notFound as $i => $sku)
                            <tr>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td><code>{{ $sku }}</code></td>
                            </tr>
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
const CHUNK_SIZE = 200;

function updateCount() {
    const val = document.getElementById('skusInput').value.trim();
    const n = val ? val.split(/\r?\n/).filter(l => l.trim()).length : 0;
    document.getElementById('skuCount').textContent = 'Lines: ' + n;
}
document.getElementById('skusInput').addEventListener('input', updateCount);
updateCount();

document.getElementById('permanently_disable').addEventListener('change', function () {
    document.getElementById('permDisableHint').style.display = this.checked ? '' : 'none';
});

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

function setProgress(done, total, chunkIdx, totalChunks) {
    const pct = total > 0 ? Math.round((done / total) * 100) : 0;
    const bar = document.getElementById('disableProgressBar');
    bar.style.width = pct + '%';
    bar.textContent = pct + '%';
    document.getElementById('disableProgressText').textContent =
        done >= total ? 'Done!' : `Chunk ${chunkIdx} of ${totalChunks} — processing…`;
    document.getElementById('disableProgressCount').textContent = `${done} / ${total} SKUs`;
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
                <div class="card border-0 text-center py-3" style="background:#fef2f2">
                    <div class="fs-1 fw-bold text-danger">${affected.length}</div>
                    <div class="text-muted small">Products Disabled</div>
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
                <i class="bi bi-check-circle-fill text-danger"></i>
                <strong>Disabled (${affected.length})</strong>
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

    document.getElementById('ajaxResults').innerHTML = html;
}

document.getElementById('bulkDisableForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const fileInput = document.getElementById('skuFileInput');
    let lines;

    if (fileInput.files.length > 0) {
        lines = await readTextLines(fileInput.files[0]);
    } else {
        const val = document.getElementById('skusInput').value.trim();
        if (!val) { alert('No SKUs provided.'); return; }
        lines = val.split(/\r?\n/).map(l => l.trim().split(',')[0].trim()).filter(l => l);
    }

    if (!lines.length) { alert('No valid SKUs found.'); return; }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing…';
    document.getElementById('ajaxResults').innerHTML = '';
    document.getElementById('disableProgress').style.display = '';
    setProgress(0, lines.length, 0, Math.ceil(lines.length / CHUNK_SIZE));

    const permanentlyDisable = document.getElementById('permanently_disable').checked ? '1' : '0';
    const allAffected = [], allNotFound = [];
    const chunks = [];
    for (let i = 0; i < lines.length; i += CHUNK_SIZE) chunks.push(lines.slice(i, i + CHUNK_SIZE));

    for (let i = 0; i < chunks.length; i++) {
        setProgress(i * CHUNK_SIZE, lines.length, i + 1, chunks.length);
        try {
            const fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            fd.append('skus', chunks[i].join('\n'));
            fd.append('permanently_disable', permanentlyDisable);
            const res = await fetch('{{ route("admin.products.bulk-disable.process") }}', {
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
    btn.innerHTML = '<i class="bi bi-slash-circle me-1"></i>Disable Products';

    document.getElementById('disableProgress').querySelector('.progress-bar')
        .classList.replace('progress-bar-animated', 'bg-success');
    document.getElementById('disableProgress').querySelector('.progress-bar')
        .classList.remove('progress-bar-striped');
});
</script>
@endpush
