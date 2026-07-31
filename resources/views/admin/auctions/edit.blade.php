@extends('admin.layout')
@section('title', 'Edit Auction - Admin Panel')
@section('content')

<div class="orders-page-header d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="orders-icon-wrap" style="background:linear-gradient(135deg,#7e22ce,#a855f7)">
            <i class="bi bi-hammer"></i>
        </div>
        <div>
            <h2 class="mb-0 fw-bold">Edit Auction</h2>
            <p class="text-muted mb-0 small">{{ $auction->title }}</p>
        </div>
    </div>
    <a href="{{ route('admin.auctions.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<form action="{{ route('admin.auctions.update', $auction) }}" method="POST" enctype="multipart/form-data">
@csrf @method('PUT')
<div class="row g-4">
    {{-- Left column --}}
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-info-circle me-2"></i>Item Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Link to Existing Product <span class="text-muted fw-normal">(optional)</span></label>
                    <select name="product_id" id="productPicker" class="form-select">
                        @if($auction->product)
                            <option value="{{ $auction->product->id }}" selected>{{ $auction->product->name }}</option>
                        @else
                            <option value="">— Search for a product —</option>
                        @endif
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Auction Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title')is-invalid @enderror"
                           value="{{ old('title', $auction->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                    <select name="branch" class="form-select @error('branch')is-invalid @enderror" required>
                        <option value="">— Select Branch —</option>
                        <option value="All" {{ old('branch', $auction->branch) === 'All' ? 'selected' : '' }}>All Branches</option>
                        @foreach(['Harare','Mutare','Bulawayo','Zambia'] as $br)
                            <option value="{{ $br }}" {{ old('branch', $auction->branch) === $br ? 'selected' : '' }}>{{ $br }}</option>
                        @endforeach
                    </select>
                    @error('branch')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    {{-- Quill.js rich text editor --}}
                    <div id="descriptionEditor" style="min-height:160px;background:#fff;border-radius:6px;border:1px solid #ced4da;"></div>
                    <input type="hidden" name="description" id="descriptionInput" value="{{ old('description', $auction->description) }}">
                    <div class="form-text">Use the editor to format the description. HTML will be saved and rendered on the auction page.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
                    <select name="condition" class="form-select" required>
                        <option value="damaged"             {{ old('condition',$auction->condition) === 'damaged'             ? 'selected':'' }}>Damaged (transit damage)</option>
                        <option value="boxed-damaged"       {{ old('condition',$auction->condition) === 'boxed-damaged'       ? 'selected':'' }}>Damaged Box</option>
                        <option value="no-box"              {{ old('condition',$auction->condition) === 'no-box'              ? 'selected':'' }}>No Box</option>
                        <option value="returned"            {{ old('condition',$auction->condition) === 'returned'            ? 'selected':'' }}>Returned</option>
                        <option value="dented"              {{ old('condition',$auction->condition) === 'dented'              ? 'selected':'' }}>Dented</option>
                        <option value="missing-accessories" {{ old('condition',$auction->condition) === 'missing-accessories' ? 'selected':'' }}>Missing Accessories</option>
                        <option value="refurbished"         {{ old('condition',$auction->condition) === 'refurbished'         ? 'selected':'' }}>Refurbished</option>
                        <option value="as-is"               {{ old('condition',$auction->condition) === 'as-is'               ? 'selected':'' }}>As-Is</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Existing images --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-images me-2"></i>Photos</div>
            <div class="card-body">
                @php $images = $auction->images ?? []; @endphp
                @if(count($images) > 0)
                    <p class="text-muted small mb-2">Existing photos — click ✕ to remove.</p>
                    <div class="image-preview-grid mb-3" id="existingGrid">
                        @foreach($images as $imgUrl)
                            <div class="preview-thumb-wrap" data-url="{{ $imgUrl }}">
                                <img src="{{ $imgUrl }}" class="preview-thumb">
                                <button type="button" class="preview-thumb-remove remove-existing" title="Remove">✕</button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <p class="text-muted small mb-2">Add more photos:</p>
                <div class="auction-image-drop" id="dropZone">
                    <i class="bi bi-cloud-upload auction-drop-icon"></i>
                    <p class="mb-1">Drag & drop or click to browse</p>
                    <input type="file" name="images[]" id="imageInput" accept="image/*" multiple class="visually-hidden">
                </div>
                <div class="image-preview-grid mt-3" id="newImageGrid"></div>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-currency-dollar me-2"></i>Pricing</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Starting Price (USD) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="starting_price" class="form-control"
                               value="{{ old('starting_price',$auction->starting_price) }}" step="0.01" min="0.01" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Reserve Price <span class="text-muted fw-normal">(optional)</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="reserve_price" class="form-control"
                               value="{{ old('reserve_price',$auction->reserve_price) }}" step="0.01" min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Minimum Bid Increment</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="min_bid_increment" class="form-control"
                               value="{{ old('min_bid_increment',$auction->min_bid_increment) }}" step="0.01" min="0.01" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header fw-semibold"><i class="bi bi-calendar-event me-2"></i>Schedule</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="draft"     {{ old('status',$auction->status) === 'draft'     ? 'selected':'' }}>Draft</option>
                        <option value="active"    {{ old('status',$auction->status) === 'active'    ? 'selected':'' }}>Active</option>
                        <option value="cancelled" {{ old('status',$auction->status) === 'cancelled' ? 'selected':'' }}>Cancelled</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Start Date &amp; Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="starts_at" class="form-control"
                           value="{{ old('starts_at', $auction->starts_at?->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">End Date &amp; Time <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="ends_at" class="form-control"
                           value="{{ old('ends_at', $auction->ends_at?->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Auto-Extend (minutes)</label>
                    <input type="number" name="auto_extend_minutes" class="form-control"
                           value="{{ old('auto_extend_minutes',$auction->auto_extend_minutes) }}" min="0" max="60">
                </div>
            </div>
        </div>

        {{-- Bid stats (read-only) --}}
        @if($auction->bid_count > 0)
        <div class="card mb-4 border-success">
            <div class="card-header fw-semibold bg-success text-white"><i class="bi bi-graph-up me-2"></i>Bid Activity</div>
            <div class="card-body">
                <p class="mb-1"><strong>Current Bid:</strong> ${{ number_format($auction->current_bid,2) }}</p>
                <p class="mb-1"><strong>Total Bids:</strong> {{ $auction->bid_count }}</p>
                @if($auction->winner)
                    <p class="mb-0"><strong>Winner:</strong> {{ $auction->winner->name }}</p>
                @endif
            </div>
        </div>
        @endif

        <button type="submit" class="btn btn-primary w-100 btn-lg">
            <i class="bi bi-save me-2"></i>Save Changes
        </button>
    </div>
</div>
</form>

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet"/>
<style>
.auction-image-drop { border:2px dashed #cbd5e1;border-radius:12px;padding:30px 20px;text-align:center;cursor:pointer;transition:border-color .2s,background .2s; }
.auction-image-drop:hover,.auction-image-drop.dragover{border-color:#062a6a;background:#f0f5ff;}
.auction-drop-icon{font-size:2rem;color:#94a3b8;display:block;margin-bottom:8px;}
.image-preview-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:10px;}
.preview-thumb-wrap{position:relative;}
.preview-thumb{width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;}
.preview-thumb-remove{position:absolute;top:3px;right:3px;background:rgba(239,68,68,.85);color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:.65rem;cursor:pointer;display:flex;align-items:center;justify-content:center;}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('#productPicker').select2({
        theme:'bootstrap-5', placeholder:'Search for a product…', allowClear:true,
        ajax:{
            url:'{{ route("admin.auctions.search-products") }}',
            dataType:'json', delay:300,
            data:params=>({q:params.term}),
            processResults:data=>({results:data.map(p=>({id:p.id,text:p.name+' — $'+parseFloat(p.price||0).toFixed(2)}))}),
            cache:true
        }
    });

    // Remove existing image via AJAX
    document.querySelectorAll('.remove-existing').forEach(btn => {
        btn.addEventListener('click', () => {
            const wrap = btn.closest('.preview-thumb-wrap');
            const url  = wrap.dataset.url;
            fetch('{{ route("admin.auctions.remove-image", $auction) }}', {
                method:'DELETE',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body:JSON.stringify({url})
            }).then(r=>r.json()).then(()=>wrap.remove());
        });
    });

    // Add new images
    const dropZone  = document.getElementById('dropZone');
    const fileInput = document.getElementById('imageInput');
    const grid      = document.getElementById('newImageGrid');
    const dt = new DataTransfer();
    dropZone.addEventListener('click',()=>fileInput.click());
    dropZone.addEventListener('dragover',e=>{e.preventDefault();dropZone.classList.add('dragover');});
    dropZone.addEventListener('dragleave',()=>dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop',e=>{e.preventDefault();dropZone.classList.remove('dragover');addFiles([...e.dataTransfer.files]);});
    fileInput.addEventListener('change',()=>addFiles([...fileInput.files]));
    function addFiles(files){
        files.forEach(file=>{
            if(!file.type.startsWith('image/'))return;
            dt.items.add(file);
            const url=URL.createObjectURL(file);
            const wrap=document.createElement('div');
            wrap.className='preview-thumb-wrap';
            wrap.innerHTML=`<img src="${url}" class="preview-thumb"><button type="button" class="preview-thumb-remove" title="Remove">✕</button>`;
            wrap.querySelector('.preview-thumb-remove').addEventListener('click',()=>{
                const idx=[...grid.children].indexOf(wrap);
                dt.items.remove(idx);
                fileInput.files=dt.files;
                wrap.remove();
            });
            grid.appendChild(wrap);
        });
        fileInput.files=dt.files;
    }
});
</script>

{{-- Quill rich text editor --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
(function () {
    var hiddenInput = document.getElementById('descriptionInput');
    var form        = document.querySelector('form[action*="auctions"]');

    var quill = new Quill('#descriptionEditor', {
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

    // ── 1. Pre-populate editor with saved content ────────────────────────
    var existing = hiddenInput.value;
    if (existing) {
        quill.root.innerHTML = existing;
    }

    // ── 2. Keep hidden input in sync on every keystroke / format change ──
    //  This is the RELIABLE approach — the hidden input always has the
    //  latest HTML before the browser ever serialises the form.
    function syncToInput() {
        var html = quill.root.innerHTML;
        hiddenInput.value = (html === '<p><br></p>' || html === '<p></p>') ? '' : html;
    }

    quill.on('text-change', syncToInput);

    // ── 3. Belt-and-suspenders: also sync on submit ─────────────────────
    if (form) {
        form.addEventListener('submit', syncToInput);
    }
})();
</script>
@endpush

