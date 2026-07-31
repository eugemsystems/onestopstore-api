<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Generate Banners by SKU — {{ $template->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background:#f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .top-bar { background:#fff; border-bottom:1px solid #dee2e6; padding:12px 20px; position:sticky; top:0; z-index:100; box-shadow:0 2px 6px rgba(0,0,0,.06); }
        .controls-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.06); margin-bottom:20px; }

        /* Preview grid */
        .preview-grid { display:flex; flex-wrap:wrap; gap:20px; justify-content:center; }
        .banner-card {
            background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.08);
            overflow:hidden; position:relative; transition:transform .2s;
        }
        .banner-card:hover { transform:translateY(-2px); }
        .banner-card .banner-label {
            background:#f8f9fa; border-top:1px solid #dee2e6;
            padding:8px 12px; font-size:12px; color:#6c757d;
        }
        .banner-card .save-status {
            position:absolute; top:8px; right:8px;
            font-size:11px; padding:2px 8px; border-radius:20px;
        }


        /* ── Template CSS from DB (each template has its own styles) ─────── */
        /* background-image is handled inline via base64 — stripped from CSS  */
        .promo-banner { background-size:cover; background-position:center; }
        {!! $templateCss !!}     


        /* Scale banner for preview */
        .banner-scale-wrap {
            transform:scale(0.35);
            transform-origin:top left;
            width:800px; height:850px;
        }
        .banner-scale-outer {
            width: calc(800px * 0.35);
            height: calc(850px * 0.35);
            overflow:hidden;
        }


        .loading-overlay {
            position:fixed; inset:0; background:rgba(0,0,0,.6);
            display:none; align-items:center; justify-content:center; z-index:9999;
        }
        .loading-overlay.active { display:flex; }
        .loading-box { background:#fff; padding:30px 40px; border-radius:14px; text-align:center; min-width:280px; }
        .progress-bar-wrap { height:6px; background:#e9ecef; border-radius:3px; margin-top:12px; }
        .progress-bar-fill { height:6px; background:#0d6efd; border-radius:3px; transition:width .3s; }

        /* ── Edit overlay controls ─────────────────────────────────── */
        .edit-toolbar {
            position:absolute; bottom:36px; left:0; right:0;
            display:flex; gap:6px; justify-content:center;
            padding:6px; opacity:0; transition:opacity .2s; z-index:10;
            pointer-events:none;
        }
        .banner-card:hover .edit-toolbar { opacity:1; pointer-events:auto; }
        .edit-toolbar .btn { font-size:11px; padding:3px 10px; border-radius:16px; box-shadow:0 2px 6px rgba(0,0,0,.25); }
        .banner-card.editing { outline:2px solid #0d6efd; }
        .banner-card .resize-handle {
            position:absolute; width:10px; height:10px; background:#0d6efd;
            border:2px solid #fff; border-radius:50%; z-index:20;
            cursor:nwse-resize; display:none; box-shadow:0 1px 3px rgba(0,0,0,.3);
        }
        .banner-card.editing .resize-handle { display:block; }
        .resize-handle.tl { top:4px;  left:4px;  cursor:nwse-resize; }
        .resize-handle.tr { top:4px;  right:4px; cursor:nesw-resize; }
        .resize-handle.bl { bottom:36px; left:4px;  cursor:nesw-resize; }
        .resize-handle.br { bottom:36px; right:4px; cursor:nwse-resize; }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('admin.promo-templates.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <span class="fw-bold">{{ $template->name }}</span>
            <span class="text-muted ms-2" style="font-size:13px;">— Single SKU</span>
        </div>
    </div>
    <div class="d-flex gap-2" id="actionButtons">
        <button class="btn btn-primary" id="btnGenerate">
            <i class="bi bi-play-circle me-1"></i>Generate Previews
        </button>
        <button class="btn btn-success d-none" id="btnSave">
            <i class="bi bi-cloud-upload me-1"></i>Save Images (<span id="saveCount">0</span>)
        </button>
        <button class="btn btn-outline-secondary d-none" id="btnDownloadCsv">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Download CSV
        </button>
    </div>
</div>

<div class="container-fluid py-4 px-4">

    <!-- Controls -->
    <div class="controls-card">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold"><i class="bi bi-upc-scan me-1"></i>SKUs <span class="text-muted fw-normal">(one per line)</span></label>
                <textarea class="form-control font-monospace" id="skuInput" rows="5"
                    placeholder="69199513&#10;46859581&#10;99274552"></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold"><i class="bi bi-currency-exchange me-1"></i>Currency</label>
                <select class="form-select" id="currencySelect">
                    <option value="USD|$|1">USD ($)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex flex-column justify-content-end">
                <div id="statusMsg" class="alert alert-info py-2 mb-0" style="font-size:13px;">
                    <i class="bi bi-info-circle me-1"></i>Enter SKUs and click Generate Previews
                </div>
            </div>
        </div>
    </div>



    <!-- Preview Area -->
    <div id="previewSection" class="d-none">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-grid me-2"></i>Preview — <span id="previewCount">0</span> banner(s)</h5>
            <small class="text-muted">Scroll to see all. Click Save Images to upload to media library.</small>
        </div>
        <div class="preview-grid" id="previewGrid"></div>
    </div>

</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-box">
        <div class="spinner-border text-primary mb-3" style="width:2rem;height:2rem;"></div>
        <div class="fw-bold" id="loadingText">Processing...</div>
        <div class="text-muted mt-1" id="loadingSubText"></div>
        <div class="progress-bar-wrap"><div class="progress-bar-fill" id="loadingProgress" style="width:0%"></div></div>
    </div>
</div>

<!-- Hidden container for full-res banner rendering (captured by html2canvas) -->
<div id="renderPool" style="position:absolute;left:-9999px;top:0;pointer-events:none;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
const CSRF          = document.querySelector('meta[name="csrf-token"]').content;
// Background already converted to base64 data URL on the server (avoids CORS)
const TEMPLATE_BG_B64 = '{!! $bgBase64 ?? "" !!}';
const btnGenerate   = document.getElementById('btnGenerate');
const btnSave       = document.getElementById('btnSave');
const btnDownloadCsv= document.getElementById('btnDownloadCsv');
const skuInput      = document.getElementById('skuInput');
const currencySelect= document.getElementById('currencySelect');
const bgImageUrl    = document.getElementById('bgImageUrl');
const previewSection= document.getElementById('previewSection');
const previewGrid   = document.getElementById('previewGrid');
const previewCount  = document.getElementById('previewCount');
const saveCount     = document.getElementById('saveCount');
const statusMsg     = document.getElementById('statusMsg');
const renderPool    = document.getElementById('renderPool');
const loadingOverlay= document.getElementById('loadingOverlay');
const loadingText   = document.getElementById('loadingText');
const loadingSubText= document.getElementById('loadingSubText');
const loadingProgress=document.getElementById('loadingProgress');

let loadedProducts = [];
let savedItems     = [];
let bgDataUrl      = null;   // cached base64 background

// ── Utilities ──────────────────────────────────────────────────────────────
function setStatus(msg, type = 'info') {
    statusMsg.className = `alert alert-${type} py-2 mb-0`;
    statusMsg.innerHTML = msg;
}
function showLoading(text, sub = '', pct = 0) {
    loadingText.textContent    = text;
    loadingSubText.textContent = sub;
    loadingProgress.style.width = pct + '%';
    loadingOverlay.classList.add('active');
}
function setProgress(pct, sub = '') {
    loadingProgress.style.width = pct + '%';
    if (sub) loadingSubText.textContent = sub;
}
function hideLoading() { loadingOverlay.classList.remove('active'); }

// ── Convert any URL → base64 data URL ─────────────────────────────────────
// Critical: html2canvas cannot load external/relative background-images unless
// they are embedded as base64 data URLs on the element's inline style.
async function toDataUrl(url) {
    if (!url) return null;
    if (url.startsWith('data:')) return url;

    // Primary: fetch (works for same-origin & CORS-enabled URLs)
    try {
        const res = await fetch(url, { mode: 'cors', cache: 'force-cache' });
        if (res.ok) {
            const blob = await res.blob();
            return await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload  = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(blob);
            });
        }
    } catch (_) {}

    // Fallback: <img> → canvas (same-origin or CORS-allowed)
    return new Promise(resolve => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            const c = document.createElement('canvas');
            c.width  = img.naturalWidth  || 800;
            c.height = img.naturalHeight || 850;
            c.getContext('2d').drawImage(img, 0, 0);
            try { resolve(c.toDataURL('image/png')); }
            catch (_) { resolve(null); }   // tainted canvas
        };
        img.onerror = () => resolve(null);
        img.src = url;
    });
}

// ── Background is already base64 from PHP — use directly, skip toDataUrl() ──
function getBgUrl() { return TEMPLATE_BG_B64 || null; }

// ── Currency ───────────────────────────────────────────────────────────────
async function loadCurrencies() {
    try {
        const res  = await fetch('/admin/promo-templates/currencies');
        const data = await res.json();
        if (data.success && data.currencies) {
            currencySelect.innerHTML = data.currencies.map(c =>
                `<option value="${c.code}|${c.symbol}|${c.exchange_rate}">${c.code} (${c.symbol})</option>`
            ).join('');
            const usd = data.currencies.find(c => c.code === 'USD');
            if (usd) currencySelect.value = `USD|${usd.symbol}|${usd.exchange_rate}`;
        }
    } catch(e) {}
}
function getCurrency() {
    const [code, symbol, rate] = currencySelect.value.split('|');
    return { code, symbol, rate: parseFloat(rate) };
}
function formatPrice(rawPrice, curr) {
    if (!rawPrice) return '';
    return curr.symbol + Math.round(parseFloat(rawPrice) * curr.rate).toLocaleString();
}
function escHtml(s) {
    return String(s ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Build full-resolution banner DOM node ──────────────────────────────────
// bgData and imgDataUrl must already be base64 data URLs or null
function buildBannerNode(product, curr, bgData, imgDataUrl) {
    const wasPrice   = product.price      ? formatPrice(product.price,      curr) : '';
    const nowPrice   = product.sale_price ? formatPrice(product.sale_price, curr) : wasPrice;
    const displayWas = product.sale_price ? wasPrice : '';
    // Truncate name at 50 chars to prevent text overlapping price/image areas
    const name = (product.name || '').length > 50
        ? product.name.substring(0, 50).trimEnd() + '…'
        : (product.name || '');

    // Apply stored transform if product was edited
    const tx = product._imgTransform || { x: 0, y: 0, scale: 1 };
    const imgStyle = (tx.x || tx.y || tx.scale !== 1)
        ? `transform:translate(${tx.x||0}px,${tx.y||0}px) scale(${tx.scale||1});transform-origin:center center;`
        : '';

    const div = document.createElement('div');
    div.className = 'promo-banner';
    if (bgData) div.style.backgroundImage = `url('${bgData}')`;

    div.innerHTML = `
        <div class="product-name">${escHtml(name)}</div>
        <div class="product-image-container" style="${imgStyle}">
            ${imgDataUrl ? `<img src="${imgDataUrl}" alt="">` : ''}
        </div>
        ${displayWas ? `<span class="was-price">${escHtml(displayWas)}</span>` : ''}
        <span class="now-price">${escHtml(nowPrice)}</span>
    `;
    return div;
}

// ── Render previews ────────────────────────────────────────────────────────
async function renderPreviews(products) {
    const curr = getCurrency();
    const bg   = getBgUrl();

    // Background is already base64 from PHP — use directly, skip toDataUrl()
    bgDataUrl = getBgUrl();
    if (!bgDataUrl) {
        setStatus('<i class="bi bi-exclamation-triangle me-1"></i>No background image found in this template CSS', 'warning');
    }

    previewGrid.innerHTML = '';

    for (let i = 0; i < products.length; i++) {
        const product = products[i];
        setProgress(5 + Math.round((i / products.length) * 30), `Loading product ${i+1}/${products.length}…`);

        const thumbnail  = product.product_thumbnail || product.thumbnail;
        const rawImgUrl  = thumbnail?.image_url || thumbnail?.original_url || '';

        // Try server-side background removal (cached by SKU)
        let imgDataUrl = null;
        if (rawImgUrl && product.sku) {
            try {
                const bgRes = await fetch('/admin/promo-templates/remove-bg', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ image_url: rawImgUrl, sku: product.sku }),
                });
                const bgData = await bgRes.json();
                if (bgData.success && bgData.url) {
                    // Convert the transparent PNG URL to data URL for html2canvas
                    imgDataUrl = await toDataUrl(bgData.url);
                }
            } catch (e) {
                console.warn('Background removal failed for', product.sku, e);
            }
        }
        // Fallback: use original image if bg removal failed
        if (!imgDataUrl && rawImgUrl) {
            imgDataUrl = await toDataUrl(rawImgUrl);
        }

        // Store on product for reuse in save
        product._imgDataUrl = imgDataUrl;

        const bannerNode = buildBannerNode(product, curr, bgDataUrl, imgDataUrl);

        // Scaled preview card
        const outer = document.createElement('div');
        outer.className = 'banner-scale-outer';
        const wrap = document.createElement('div');
        wrap.className = 'banner-scale-wrap';
        wrap.appendChild(bannerNode.cloneNode(true));
        outer.appendChild(wrap);

        const card = document.createElement('div');
        card.className = 'banner-card';
        card.dataset.index = i;
        card.innerHTML = `
            <div class="banner-scale-outer"></div>
            <div class="edit-toolbar">
                <button class="btn btn-sm btn-primary btn-edit-move" title="Drag to move image"><i class="bi bi-arrows-move"></i> Move</button>
                <button class="btn btn-sm btn-warning btn-edit-resize" title="Resize image"><i class="bi bi-aspect-ratio"></i> Resize</button>
                <button class="btn btn-sm btn-info btn-edit-replace" title="Replace image"><i class="bi bi-upload"></i> Replace</button>
            </div>
            <div class="resize-handle tl"></div>
            <div class="resize-handle tr"></div>
            <div class="resize-handle bl"></div>
            <div class="resize-handle br"></div>
            <div class="banner-label">
                <strong>${escHtml(product.sku)}</strong> — ${escHtml(product.name.substring(0,50))}
            </div>
        `;
        card.querySelector('.banner-scale-outer').replaceWith(outer);
        previewGrid.appendChild(card);

        // Attach interactive editing
        makeEditable(card, i);
    }

    hideLoading();
    previewSection.classList.remove('d-none');
    previewCount.textContent = products.length;
    btnSave.classList.remove('d-none');
    saveCount.textContent = products.length;
    btnDownloadCsv.classList.add('d-none');
    savedItems = [];
}

// ── Interactive Image Editing ──────────────────────────────────────────────
const PREVIEW_SCALE = 0.35; // must match .banner-scale-wrap transform:scale()

function makeEditable(card, productIndex) {
    const product = loadedProducts[productIndex];
    if (!product) return;
    if (!product._imgTransform) product._imgTransform = { x: 0, y: 0, scale: 1 };

    const scaleWrap = card.querySelector('.banner-scale-wrap');
    const imgContainer = scaleWrap?.querySelector('.product-image-container');
    if (!imgContainer) return;

    // ── Helper: update the preview image transform ──
    function applyTransform() {
        const t = product._imgTransform;
        imgContainer.style.transform = `translate(${t.x}px, ${t.y}px) scale(${t.scale})`;
        imgContainer.style.transformOrigin = 'center center';
    }

    // ── Helper: rebuild the preview after image replacement ──
    function refreshPreview() {
        const img = imgContainer.querySelector('img');
        if (img && product._imgDataUrl) {
            img.src = product._imgDataUrl;
        }
        applyTransform();
    }

    // ── MOVE (drag) ──
    card.querySelector('.btn-edit-move').addEventListener('click', () => {
        card.classList.toggle('editing');
        scaleWrap.style.cursor = card.classList.contains('editing') ? 'grab' : '';
    });

    let dragging = false, dragStartX, dragStartY, origX, origY;

    scaleWrap.addEventListener('mousedown', (e) => {
        if (!card.classList.contains('editing')) return;
        if (e.target.classList.contains('resize-handle')) return;
        e.preventDefault();
        dragging = true;
        scaleWrap.style.cursor = 'grabbing';
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        origX = product._imgTransform.x;
        origY = product._imgTransform.y;
    });

    document.addEventListener('mousemove', (e) => {
        if (!dragging) return;
        // Divide by PREVIEW_SCALE because the banner is scaled down in preview
        const dx = (e.clientX - dragStartX) / PREVIEW_SCALE;
        const dy = (e.clientY - dragStartY) / PREVIEW_SCALE;
        product._imgTransform.x = origX + dx;
        product._imgTransform.y = origY + dy;
        applyTransform();
    });

    document.addEventListener('mouseup', () => {
        if (dragging) {
            dragging = false;
            scaleWrap.style.cursor = card.classList.contains('editing') ? 'grab' : '';
        }
    });

    // ── RESIZE (corner handles) ──
    card.querySelector('.btn-edit-resize').addEventListener('click', () => {
        card.classList.toggle('editing');
    });

    card.querySelectorAll('.resize-handle').forEach(handle => {
        handle.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            let resizeStartY = e.clientY;
            let origScale = product._imgTransform.scale;

            const onMove = (ev) => {
                // Drag up = bigger, drag down = smaller
                const dy = (resizeStartY - ev.clientY) / 200;
                product._imgTransform.scale = Math.max(0.2, Math.min(3, origScale + dy));
                applyTransform();
            };

            const onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
            };

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    });

    // ── REPLACE (upload new image) ──
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.style.display = 'none';
    card.appendChild(fileInput);

    card.querySelector('.btn-edit-replace').addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        if (!file) return;

        // Read file as data URL
        const dataUrl = await new Promise(resolve => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.readAsDataURL(file);
        });

        // Update product data with the uploaded image (no bg removal for uploaded files)
        product._imgDataUrl = dataUrl;
        refreshPreview();

        // Reset file input for re-uploads
        fileInput.value = '';
    });
}

// ── Generate ───────────────────────────────────────────────────────────────
btnGenerate.addEventListener('click', async () => {
    const skus = skuInput.value.split('\n').map(s => s.trim()).filter(Boolean);
    if (!skus.length) {
        setStatus('<i class="bi bi-exclamation-triangle me-1"></i>Enter at least one SKU', 'warning');
        return;
    }

    showLoading('Loading products from database…', '', 10);
    try {
        const url  = `/api/product/by-skus?skus=${encodeURIComponent(skus.join(','))}`;
        const res  = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await res.json();

        if (!data.success || !data.products?.length) {
            throw new Error(data.message || 'No products found for those SKUs');
        }

        loadedProducts = data.products;
        await renderPreviews(loadedProducts);
        setStatus(`<i class="bi bi-check-circle me-1"></i>Generated ${loadedProducts.length} preview(s)`, 'success');
    } catch(e) {
        hideLoading();
        setStatus(`<i class="bi bi-x-circle me-1"></i>${e.message}`, 'danger');
    }
});

// ── Save Images ────────────────────────────────────────────────────────────
btnSave.addEventListener('click', async () => {
    if (!loadedProducts.length) return;

    const curr  = getCurrency();
    const items = [];

    showLoading('Capturing banners…', `0 / ${loadedProducts.length}`, 0);

    for (let i = 0; i < loadedProducts.length; i++) {
        const product    = loadedProducts[i];
        const imgDataUrl = product._imgDataUrl || null;

        setProgress(Math.round((i / loadedProducts.length) * 60), `Capturing ${i+1} / ${loadedProducts.length}`);

        try {
            const node = buildBannerNode(product, curr, bgDataUrl, imgDataUrl);
            renderPool.appendChild(node);

            // Let the DOM settle before capture
            await new Promise(r => setTimeout(r, 80));

            const canvas = await html2canvas(node, {
                scale:           1,      // 800×850 is sufficient for Facebook ads (1200×628 min)
                useCORS:         true,
                allowTaint:      false,  // data URLs don't taint the canvas
                backgroundColor: null,
                width:           800,
                height:          850,
                logging:         false,
            });
            // WebP at 85% quality: ~3-5× smaller than PNG, fully supported by Facebook
            const dataUrl = canvas.toDataURL('image/webp', 0.85);
            renderPool.removeChild(node);

            items.push({
                sku:          product.sku,
                imageDataUrl: dataUrl,
                product: {
                    sku:          product.sku,
                    name:         product.name,
                    description:  product.description ?? '',
                    slug:         product.slug ?? '',
                    price:        product.price,
                    sale_price:   product.sale_price,
                    stock_status: product.stock_status ?? 'in_stock',
                    brand:        product.brand?.name ?? '{{ config("app.name") }}',
                    currency:     curr.code,
                    rate:         curr.rate,
                    symbol:       curr.symbol,
                },
            });
        } catch(e) {
            console.error('Capture failed for', product.sku, e);
        }
    }

    setProgress(65, 'Uploading to media storage…');
    try {
        const res  = await fetch('/admin/promo-templates/save-images', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF,
                Accept:         'application/json',
            },
            body: JSON.stringify({ items }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Save failed');

        savedItems = data.saved;
        setProgress(100, `Saved ${data.count} image(s)`);
        hideLoading();

        savedItems.forEach((_, i) => {
            const card = previewGrid.children[i];
            if (card) {
                const badge = document.createElement('span');
                badge.className = 'save-status badge bg-success';
                badge.textContent = '✓ Saved';
                card.appendChild(badge);
            }
        });

        btnSave.classList.add('d-none');
        btnDownloadCsv.classList.remove('d-none');
        setStatus(`<i class="bi bi-check-circle me-1"></i>Saved ${data.count} image(s) to media storage`, 'success');
    } catch(e) {
        hideLoading();
        setStatus(`<i class="bi bi-x-circle me-1"></i>Save failed: ${e.message}`, 'danger');
    }
});

// ── Download CSV ───────────────────────────────────────────────────────────
btnDownloadCsv.addEventListener('click', () => {
    if (!savedItems.length) return;

    const frontendUrl = '{{ rtrim(config("app.frontend_url") ?? config("app.url"), "/") }}';
    const header = ['id','title','description','link','image_link','availability','price','sale_price','brand','condition'];

    const rows = savedItems.map(({ sku, url, product }) => {
        const rate  = parseFloat(product.rate ?? 1);
        const code  = product.currency ?? 'USD';
        const price = product.price      ? ((parseFloat(product.price)      * rate).toFixed(2) + ' ' + code) : '';
        const saleP = product.sale_price ? ((parseFloat(product.sale_price) * rate).toFixed(2) + ' ' + code) : '';
        const link  = `${frontendUrl}/en/product/${product.slug || sku}`;
        return [
            csv(product.sku), csv(product.name), csv(product.description),
            csv(link), csv(url), csv(product.stock_status ?? 'in_stock'),
            csv(price), csv(saleP), csv(product.brand), 'new',
        ].join(',');
    });

    const content = [header.join(','), ...rows].join('\n');
    const blob = new Blob([content], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `promo-feed-${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
});

function csv(val) {
    if (val === null || val === undefined) return '""';
    return '"' + String(val).replace(/"/g, '""').replace(/\n/g, ' ') + '"';
}

loadCurrencies();
</script>
</body>
</html>
