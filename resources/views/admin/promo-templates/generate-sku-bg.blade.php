<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BG Convert Banners — {{ $template->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background:#f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .top-bar { background:#fff; border-bottom:1px solid #dee2e6; padding:12px 20px; position:sticky; top:0; z-index:100; box-shadow:0 2px 6px rgba(0,0,0,.06); }
        .controls-card { background:#fff; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,.06); margin-bottom:20px; }

        /* BG-Convert mode banner */
        .mode-banner {
            background: linear-gradient(135deg, #f59e0b15, #ef444415);
            border: 1px solid #f59e0b40;
            border-radius: 10px;
            padding: 10px 16px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #92400e;
        }
        .mode-banner i { font-size: 20px; color: #f59e0b; flex-shrink: 0; }

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
        .banner-card .bg-fail-badge {
            position:absolute; top:8px; left:8px;
            font-size:10px; padding:2px 7px; border-radius:20px;
            background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;
        }

        /* ── Template CSS from DB ─────────────────────────────────── */
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
        .progress-bar-fill { height:6px; background:#f59e0b; border-radius:3px; transition:width .3s; }

        /* ── Edit overlay controls ─────────────────────────────────── */
        .edit-toolbar {
            position:absolute; bottom:36px; left:0; right:0;
            display:flex; gap:6px; justify-content:center;
            padding:6px; opacity:0; transition:opacity .2s; z-index:10;
            pointer-events:none;
        }
        .banner-card:hover .edit-toolbar { opacity:1; pointer-events:auto; }
        .edit-toolbar .btn { font-size:11px; padding:3px 10px; border-radius:16px; box-shadow:0 2px 6px rgba(0,0,0,.25); }
        .banner-card.editing { outline:2px solid #f59e0b; }
        .banner-card .resize-handle {
            position:absolute; width:10px; height:10px; background:#f59e0b;
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
            <span class="text-muted ms-2" style="font-size:13px;">— BG Convert</span>
        </div>
    </div>
    <div class="d-flex gap-2" id="actionButtons">
        <button class="btn btn-warning text-dark" id="btnGenerate" style="font-weight:600;">
            <i class="bi bi-stars me-1"></i>Generate BG-Converted Previews
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

    <!-- Mode Info Banner -->
    <div class="mode-banner">
        <i class="bi bi-magic"></i>
        <div>
            <strong>Canvas Mask Mode</strong> — White &amp; near-white background pixels are made transparent directly in the image using canvas pixel processing. No AI, no server calls — instant results that work at any position or scale.
        </div>
    </div>

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
                    <i class="bi bi-info-circle me-1"></i>Enter SKUs and click Generate BG-Converted Previews
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Area -->
    <div id="previewSection" class="d-none">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-grid me-2"></i>Preview — <span id="previewCount">0</span> banner(s)</h5>
            <small class="text-muted">White BG hidden via CSS mask. Click Save Images to upload to media library.</small>
        </div>
        <div class="preview-grid" id="previewGrid"></div>
    </div>

</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-box">
        <div class="spinner-border text-warning mb-3" style="width:2rem;height:2rem;"></div>
        <div class="fw-bold" id="loadingText">Processing...</div>
        <div class="text-muted mt-1" id="loadingSubText"></div>
        <div class="progress-bar-wrap"><div class="progress-bar-fill" id="loadingProgress" style="width:0%"></div></div>
    </div>
</div>

<!-- Hidden container for full-res banner rendering -->
<div id="renderPool" style="position:absolute;left:-9999px;top:0;pointer-events:none;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

<script>
const CSRF           = document.querySelector('meta[name="csrf-token"]').content;
const TEMPLATE_BG_B64 = '{!! $bgBase64 ?? "" !!}';
const btnGenerate    = document.getElementById('btnGenerate');
const btnSave        = document.getElementById('btnSave');
const btnDownloadCsv = document.getElementById('btnDownloadCsv');
const skuInput       = document.getElementById('skuInput');
const currencySelect = document.getElementById('currencySelect');
const previewSection = document.getElementById('previewSection');
const previewGrid    = document.getElementById('previewGrid');
const previewCount   = document.getElementById('previewCount');
const saveCount      = document.getElementById('saveCount');
const statusMsg      = document.getElementById('statusMsg');
const renderPool     = document.getElementById('renderPool');
const loadingOverlay = document.getElementById('loadingOverlay');
const loadingText    = document.getElementById('loadingText');
const loadingSubText = document.getElementById('loadingSubText');
const loadingProgress= document.getElementById('loadingProgress');

let loadedProducts = [];
let savedItems     = [];
let bgDataUrl      = null;

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
async function toDataUrl(url) {
    if (!url) return null;
    if (url.startsWith('data:')) return url;

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

    return new Promise(resolve => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            const c = document.createElement('canvas');
            c.width  = img.naturalWidth  || 800;
            c.height = img.naturalHeight || 850;
            c.getContext('2d').drawImage(img, 0, 0);
            try { resolve(c.toDataURL('image/png')); }
            catch (_) { resolve(null); }
        };
        img.onerror = () => resolve(null);
        img.src = url;
    });
}

function getBgUrl() { return TEMPLATE_BG_B64 || null; }

// ── Canvas pixel mask: make white/near-white pixels transparent ────────────
// threshold: pixels where R, G, B are ALL above this value become transparent.
// tolerance: softens the edge — pixels just below the threshold get partial alpha.
async function removeWhiteBg(dataUrl, threshold = 235, tolerance = 20) {
    if (!dataUrl) return dataUrl;
    return new Promise(resolve => {
        const img = new Image();
        img.onload = () => {
            const canvas = document.createElement('canvas');
            canvas.width  = img.naturalWidth  || img.width;
            canvas.height = img.naturalHeight || img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);

            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const d = imageData.data;

            for (let i = 0; i < d.length; i += 4) {
                const r = d[i], g = d[i+1], b = d[i+2];
                // Brightness of this pixel (0–255)
                const brightness = Math.min(r, g, b);  // darkest channel drives transparency
                if (brightness >= threshold) {
                    // Fully white-ish pixel → fully transparent
                    d[i+3] = 0;
                } else if (brightness >= threshold - tolerance) {
                    // Soft edge: gradually reduce alpha approaching the threshold
                    d[i+3] = Math.round(((threshold - brightness) / tolerance) * 255);
                }
                // Darker pixels: leave alpha as-is
            }

            ctx.putImageData(imageData, 0, 0);
            resolve(canvas.toDataURL('image/png'));
        };
        img.onerror = () => resolve(dataUrl); // fallback: return original
        img.crossOrigin = 'anonymous';
        img.src = dataUrl;
    });
}

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
function buildBannerNode(product, curr, bgData, imgDataUrl) {
    const wasPrice   = product.price      ? formatPrice(product.price,      curr) : '';
    const nowPrice   = product.sale_price ? formatPrice(product.sale_price, curr) : wasPrice;
    const displayWas = product.sale_price ? wasPrice : '';
    const name = (product.name || '').length > 50
        ? product.name.substring(0, 50).trimEnd() + '…'
        : (product.name || '');

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

// ── Render previews — CSS mix-blend-mode handles BG removal (no AI) ──────────
async function renderPreviews(products) {
    const curr = getCurrency();
    bgDataUrl = getBgUrl();
    if (!bgDataUrl) {
        setStatus('<i class="bi bi-exclamation-triangle me-1"></i>No background image found in this template CSS', 'warning');
    }

    previewGrid.innerHTML = '';

    for (let i = 0; i < products.length; i++) {
        const product = products[i];
        setProgress(5 + Math.round((i / products.length) * 90), `Loading product ${i+1}/${products.length}…`);

        const thumbnail = product.product_thumbnail || product.thumbnail;
        const rawImgUrl = thumbnail?.image_url || thumbnail?.original_url || '';

        // Load image, then strip white/near-white pixels via canvas mask
        let imgDataUrl = rawImgUrl ? await toDataUrl(rawImgUrl) : null;
        if (imgDataUrl) {
            imgDataUrl = await removeWhiteBg(imgDataUrl);
        }
        product._imgDataUrl = imgDataUrl;

        const bannerNode = buildBannerNode(product, curr, bgDataUrl, imgDataUrl);

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
                <span class="badge bg-success ms-1" style="font-size:10px;">✦ BG Removed</span>
            </div>
        `;
        card.querySelector('.banner-scale-outer').replaceWith(outer);
        previewGrid.appendChild(card);
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
const PREVIEW_SCALE = 0.35;

function makeEditable(card, productIndex) {
    const product = loadedProducts[productIndex];
    if (!product) return;
    if (!product._imgTransform) product._imgTransform = { x: 0, y: 0, scale: 1 };

    const scaleWrap    = card.querySelector('.banner-scale-wrap');
    const imgContainer = scaleWrap?.querySelector('.product-image-container');
    if (!imgContainer) return;

    function applyTransform() {
        const t = product._imgTransform;
        imgContainer.style.transform = `translate(${t.x}px, ${t.y}px) scale(${t.scale})`;
        imgContainer.style.transformOrigin = 'center center';
    }

    function refreshPreview() {
        const img = imgContainer.querySelector('img');
        if (img && product._imgDataUrl) img.src = product._imgDataUrl;
        applyTransform();
    }

    // ── MOVE ──
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
        product._imgTransform.x = origX + (e.clientX - dragStartX) / PREVIEW_SCALE;
        product._imgTransform.y = origY + (e.clientY - dragStartY) / PREVIEW_SCALE;
        applyTransform();
    });

    document.addEventListener('mouseup', () => {
        if (dragging) {
            dragging = false;
            scaleWrap.style.cursor = card.classList.contains('editing') ? 'grab' : '';
        }
    });

    // ── RESIZE ──
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

    // ── REPLACE ──
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.style.display = 'none';
    card.appendChild(fileInput);

    card.querySelector('.btn-edit-replace').addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        if (!file) return;
        const dataUrl = await new Promise(resolve => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.readAsDataURL(file);
        });
        product._imgDataUrl = dataUrl;
        refreshPreview();
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

    showLoading('Loading products from database…', '', 5);
    try {
        const url  = `/api/product/by-skus?skus=${encodeURIComponent(skus.join(','))}`;
        const res  = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await res.json();

        if (!data.success || !data.products?.length) {
            throw new Error(data.message || 'No products found for those SKUs');
        }

        loadedProducts = data.products;
        showLoading('Loading product images…', 'Applying CSS mask…', 10);
        await renderPreviews(loadedProducts);
        setStatus(
            `<i class="bi bi-check-circle me-1"></i>Generated ${loadedProducts.length} preview(s) — CSS mask applied`,
            'success'
        );
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

            await new Promise(r => setTimeout(r, 80));

            const canvas = await html2canvas(node, {
                scale:           1,
                useCORS:         true,
                allowTaint:      false,
                backgroundColor: null,
                width:           800,
                height:          850,
                logging:         false,
            });
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
        const res  = await fetch('/admin/promo-templates/save-images-bg', {
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
        setStatus(`<i class="bi bi-check-circle me-1"></i>Saved ${data.count} BG-converted image(s) to media storage`, 'success');
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
    a.download = `promo-feed-bg-${new Date().toISOString().slice(0,10)}.csv`;
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
