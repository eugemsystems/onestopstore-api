@extends('admin.layout')
@section('title', 'Create Auction - Admin Panel')
@section('content')

<div class="orders-page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="orders-icon-wrap" style="background:linear-gradient(135deg,#7e22ce,#a855f7)">
            <i class="bi bi-hammer"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold">Create Auction</h2>
            <p class="text-muted mb-0 small">List a transit-damaged item for auction</p>
        </div>
    </div>
    <a href="{{ route('admin.auctions.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('admin.auctions.store') }}" method="POST" enctype="multipart/form-data" id="auctionForm">
@csrf

<div class="row g-4">
    {{-- Left column --}}
    <div class="col-lg-8">

        {{-- Basic Info --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2"></i>Item Details</div>
            <div class="card-body">
                {{-- Product picker --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Link to Existing Product <span class="text-muted fw-normal">(optional)</span></label>
                    <select name="product_id" id="productPicker" class="form-select">
                        <option value="">— Search for a product —</option>
                    </select>
                    <div class="form-text">If selected, the React frontend will show a link to the original product listing.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Auction Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title')is-invalid @enderror"
                           value="{{ old('title') }}" placeholder="e.g. Sony 65&#34; TV - Cracked Corner" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                    <select name="branch" class="form-select @error('branch')is-invalid @enderror" required>
                        <option value="">— Select Branch —</option>
                        <option value="All" {{ old('branch') === 'All' ? 'selected' : '' }}>All Branches</option>
                        @foreach(['Harare','Mutare','Bulawayo','Zambia'] as $br)
                            <option value="{{ $br }}" {{ old('branch') === $br ? 'selected' : '' }}>{{ $br }}</option>
                        @endforeach
                    </select>
                    @error('branch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">The branch where this auction lot is held.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    {{-- Quill.js rich text editor --}}
                    <div id="descriptionEditor" style="min-height:160px;background:#fff;border-radius:6px;border:1px solid #ced4da;"></div>
                    <input type="hidden" name="description" id="descriptionInput" value="{{ old('description') }}">
                    <div class="form-text">Use the editor to format the description. HTML will be saved and rendered on the auction page.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
                    <select name="condition" class="form-select" required>
                        <option value="damaged"              {{ old('condition','damaged') === 'damaged'              ? 'selected':'' }}>Damaged (transit damage)</option>
                        <option value="boxed-damaged"        {{ old('condition') === 'boxed-damaged'        ? 'selected':'' }}>Damaged Box</option>
                        <option value="no-box"               {{ old('condition') === 'no-box'               ? 'selected':'' }}>No Box</option>
                        <option value="returned"             {{ old('condition') === 'returned'             ? 'selected':'' }}>Returned</option>
                        <option value="dented"               {{ old('condition') === 'dented'               ? 'selected':'' }}>Dented</option>
                        <option value="missing-accessories"  {{ old('condition') === 'missing-accessories'  ? 'selected':'' }}>Missing Accessories</option>
                        <option value="refurbished"          {{ old('condition') === 'refurbished'          ? 'selected':'' }}>Refurbished</option>
                        <option value="as-is"                {{ old('condition') === 'as-is'                ? 'selected':'' }}>As-Is</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Images --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-images me-2"></i>Photos</div>
            <div class="card-body">
                <div class="auction-image-drop" id="dropZone">
                    <i class="bi bi-cloud-upload auction-drop-icon"></i>
                    <p class="mb-1">Drag & drop photos here, or click to browse</p>
                    <p class="text-muted small mb-0">Max 5 MB per image • JPG, PNG, WEBP</p>
                    <input type="file" name="images[]" id="imageInput" accept="image/*" multiple class="visually-hidden">
                </div>
                <div class="image-preview-grid mt-3" id="imagePreviewGrid"></div>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-4">

        {{-- Pricing --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-currency-dollar me-2"></i>Pricing</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Starting Price (USD) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="starting_price" class="form-control @error('starting_price')is-invalid @enderror"
                               value="{{ old('starting_price','1.00') }}" step="0.01" min="0.01" required>
                        @error('starting_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Reserve Price <span class="text-muted fw-normal">(optional)</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="reserve_price" class="form-control" value="{{ old('reserve_price') }}" step="0.01" min="0">
                    </div>
                    <div class="form-text">Auction will only count if bids reach this amount.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Minimum Bid Increment (USD) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="min_bid_increment" class="form-control" value="{{ old('min_bid_increment','1.00') }}" step="0.01" min="0.01" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-calendar-event me-2"></i>Schedule</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="draft"  {{ old('status','draft') === 'draft'  ? 'selected':'' }}>Draft (not visible)</option>
                        <option value="active" {{ old('status') === 'active' ? 'selected':'' }}>Active (live)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Start Date &amp; Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="starts_at" class="form-control @error('starts_at')is-invalid @enderror"
                           value="{{ old('starts_at') }}" required>
                    @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">End Date &amp; Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="ends_at" class="form-control @error('ends_at')is-invalid @enderror"
                           value="{{ old('ends_at') }}" required>
                    @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Auto-Extend (minutes)</label>
                    <input type="number" name="auto_extend_minutes" class="form-control" value="{{ old('auto_extend_minutes','5') }}" min="0" max="60">
                    <div class="form-text">Extend by this many minutes when a bid is placed in the last N minutes. Set 0 to disable.</div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="bi bi-hammer me-2"></i>Create Auction
        </button>
    </div>
</div>
</form>

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>
<style>
.auction-image-drop {
    border: 2px dashed #cbd5e1; border-radius: 12px;
    padding: 40px 20px; text-align: center;
    cursor: pointer; transition: border-color .2s, background .2s;
}
.auction-image-drop:hover, .auction-image-drop.dragover {
    border-color: #062a6a; background: #f0f5ff;
}
.auction-drop-icon { font-size: 2.5rem; color: #94a3b8; display: block; margin-bottom: 10px; }
.image-preview-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
}
.preview-thumb-wrap { position: relative; }
.preview-thumb { width: 100%; aspect-ratio: 1; object-fit: cover; border-radius: 8px; }
.preview-thumb-remove {
    position: absolute; top: 4px; right: 4px;
    background: rgba(239,68,68,.85); color: #fff; border: none;
    border-radius: 50%; width: 22px; height: 22px;
    font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    // Product Select2 — with Elasticsearch + image thumbnails
    $('#productPicker').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search for a product…',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
            url: '{{ route("admin.auctions.search-products") }}',
            dataType: 'json',
            delay: 280,
            data: params => ({ q: params.term }),
            processResults: data => ({
                results: data.map(p => ({
                    id:        p.id,
                    text:      p.name,
                    price:     p.price,
                    image_url: p.image_url,
                }))
            }),
            cache: true,
        },
        templateResult: function(p) {
            if (p.loading) return $('<span>Searching…</span>');
            const img = p.image_url
                ? `<img src="${p.image_url}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;margin-right:10px;border:1px solid #e2e8f0;" onerror="this.style.display='none'">`
                : `<span style="width:40px;height:40px;border-radius:6px;margin-right:10px;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-image" style="color:#94a3b8;font-size:1rem;"></i></span>`;
            return $(`<div style="display:flex;align-items:center;padding:4px 0;">
                ${img}
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:.875rem;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${p.text}</div>
                    <div style="font-size:.75rem;color:#64748b;">$${parseFloat(p.price||0).toFixed(2)}</div>
                </div>
            </div>`);
        },
        templateSelection: function(p) {
            return p.text || p.id;
        },
    });


    // Image drop zone
    const dropZone  = document.getElementById('dropZone');
    const fileInput = document.getElementById('imageInput');
    const grid      = document.getElementById('imagePreviewGrid');
    const dt = new DataTransfer();

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('dragover');
        addFiles([...e.dataTransfer.files]);
    });
    fileInput.addEventListener('change', () => addFiles([...fileInput.files]));

    function addFiles(files) {
        files.forEach(file => {
            if (!file.type.startsWith('image/')) return;
            dt.items.add(file);
            const url = URL.createObjectURL(file);
            const wrap = document.createElement('div');
            wrap.className = 'preview-thumb-wrap';
            wrap.innerHTML = `<img src="${url}" class="preview-thumb"><button type="button" class="preview-thumb-remove" title="Remove">✕</button>`;
            wrap.querySelector('.preview-thumb-remove').addEventListener('click', () => {
                const idx = [...grid.children].indexOf(wrap);
                dt.items.remove(idx);
                fileInput.files = dt.files;
                wrap.remove();
            });
            grid.appendChild(wrap);
        });
        fileInput.files = dt.files;
    }
});
</script>

{{-- Quill rich text editor --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
(function () {
    const quill = new Quill('#descriptionEditor', {
        theme: 'snow',
        placeholder: 'Describe the damage, what works, included accessories …',
        modules: {
            toolbar: [
                ['bold','italic','underline','strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote'],
                [{ header: [1, 2, 3, false] }],
                ['clean'],
            ],
        },
    });

    // Pre-fill from old() / server value
    const existing = document.getElementById('descriptionInput').value;
    if (existing) quill.root.innerHTML = existing;

    // Sync to hidden input before submit
    document.querySelector('form').addEventListener('formdata', () => {
        document.getElementById('descriptionInput').value = quill.root.innerHTML;
    });
    document.querySelector('form').addEventListener('submit', () => {
        document.getElementById('descriptionInput').value = quill.root.innerHTML;
    });
})();
</script>
@endpush
