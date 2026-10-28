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
        /* Rating row sits inside .product-name so it always flows below the name.
           Templates can restyle it by defining .product-rating in their own CSS (below). */
        .product-rating { font-size:20px; font-weight:normal; color:#ffc107; margin-top:6px; letter-spacing:2px; text-shadow:1px 1px 3px rgba(0,0,0,.6); white-space:nowrap; }
        .product-rating .rating-count { font-size:15px; color:#fff; letter-spacing:0; margin-left:4px; }
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
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="removeBgToggle">
                    <label class="form-check-label" for="removeBgToggle" style="font-size:12px;">
                        Remove image backgrounds <span class="text-danger">(slow &amp; heavy on the server — leave off unless needed)</span>
                    </label>
                </div>
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
@if(!empty($usesTemplateCards))
<!-- The saved template, loaded as-is. Its own renderProducts() draws the card we capture. -->
<iframe id="tplFrame" src="{{ route('admin.promo-templates.raw', $template->id) }}"
        style="position:absolute;left:-12000px;top:0;width:1500px;height:2600px;border:0;pointer-events:none;" tabindex="-1" aria-hidden="true"></iframe>
@endif

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
const removeBgToggle= document.getElementById('removeBgToggle');
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
const USES_TEMPLATE_CARDS = {{ !empty($usesTemplateCards) ? 'true' : 'false' }};
let TEMPLATE_CARD_MODE = false;
// Buttons / action icons that exist on the live card but must NOT appear in the saved image.
// Add data-no-capture (or class no-capture) to anything else in your template to hide it too.
const CARD_HIDE_SELECTOR = [
    'button', '.btn', '[role="button"]', '[data-no-capture]', '.no-capture',
    '[class*="add-to-cart" i]', '[class*="addtocart" i]', '[class*="wishlist" i]', '[class*="compare" i]',
    '[class*="quick-view" i]', '[class*="quickview" i]', '[class*="card-action" i]'
].join(',');

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
// bgData and imgDataUrl must already be base64 data URLs or null.
// The card is the TEMPLATE'S OWN markup (cloned from its saved HTML and filled with the
// product), so the saved image is exactly the card designed in the template.
const TEMPLATE_BODY = {!! json_encode($templateBodyHtml ?? '') !!};

function ratingMarkup(product) {
    const rating = parseFloat(product.rating);
    if (isNaN(rating) || rating <= 0 || !(product.reviews_count > 0)) return '';
    const full = Math.max(0, Math.min(5, Math.round(rating)));
    return '★'.repeat(full) + '☆'.repeat(5 - full)
        + `<span class="rating-count">${rating.toFixed(1)} (${product.reviews_count})</span>`;
}

function formatMoney(rawPrice, curr) {
    const v = parseFloat(rawPrice) * curr.rate;
    if (!isFinite(v)) return '';
    return curr.symbol + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function buildBannerNode(product, curr, bgData, imgDataUrl) {
    const price    = parseFloat(product.price) || 0;
    const sale     = parseFloat(product.sale_price) || 0;
    const onSale   = sale > 0 && price > 0 && sale < price;
    const name     = product.name || '';
    const nameLong = name.length > 50 ? name.substring(0, 50).trimEnd() + '…' : name;
    const nameShort= name.length > 64 ? name.substring(0, 64).trimEnd() + '…' : name;
    const rating   = parseFloat(product.rating);
    const hasRating= !isNaN(rating) && rating > 0 && product.reviews_count > 0;

    const tokens = {
        name,
        name_short:       nameShort,
        sku:              product.sku || '',
        now_price:        formatMoney(onSale ? sale : price, curr),
        was_price:        onSale ? formatMoney(price, curr) : '',
        discount_percent: onSale ? Math.round((1 - sale / price) * 100) : 0,
        save_amount:      onSale ? formatMoney(price - sale, curr) : '',
        rating:           hasRating ? rating.toFixed(1) : '',
        reviews_count:    hasRating ? product.reviews_count : '',
        currency:         curr.code,
        symbol:           curr.symbol,
    };

    const tokenMode = /\{\{\s*\w+\s*\}\}/.test(TEMPLATE_BODY);
    const html = tokenMode
        ? TEMPLATE_BODY.replace(/\{\{\s*(\w+)\s*\}\}/g, (m, k) =>
              k === 'image' ? (imgDataUrl || '') : escHtml(tokens[k] ?? ''))
        : TEMPLATE_BODY;

    const holder = document.createElement('div');
    holder.innerHTML = html;
    // The card is whatever root element the saved template defines
    const root = holder.querySelector('.promo-banner, .promo-card') || holder.firstElementChild;
    if (!root) return buildFallbackBannerNode(product, curr, bgData, imgDataUrl);

    if (tokenMode) {
        // data-show="sale" / "rating" / "nosale": drop elements that don't apply to this product
        holder.querySelectorAll('[data-show]').forEach(el => {
            const k = el.getAttribute('data-show');
            if ((k === 'sale' && !onSale) || (k === 'nosale' && onSale) || (k === 'rating' && !hasRating)) el.remove();
        });
        root.querySelectorAll('img').forEach(img => {
            img.removeAttribute('onerror');
            if (!imgDataUrl) img.style.display = 'none';
        });
    } else {
        // Legacy class-based templates (.product-name / .now-price / …)
        const wasPrice = price ? formatPrice(product.price, curr) : '';
        const nowPrice = sale ? formatPrice(product.sale_price, curr) : wasPrice;
        const setText = (sel, text) => root.querySelectorAll(sel).forEach(el => { el.textContent = text; });
        setText('.product-id', product.sku ? '#' + product.sku : '');
        setText('.product-name', nameLong);
        setText('.was-price', onSale ? wasPrice : '');
        setText('.now-price', nowPrice);
        root.querySelectorAll('.sale-end-date').forEach(el => { el.textContent = ''; el.classList.remove('visible'); });

        const c = root.querySelector('.product-image-container');
        if (c) {
            let img = c.querySelector('img');
            if (!img) { img = document.createElement('img'); c.appendChild(img); }
            if (imgDataUrl) { img.removeAttribute('onerror'); img.src = imgDataUrl; img.style.display = ''; }
            else { img.removeAttribute('src'); img.style.display = 'none'; }
        }
        if (hasRating) {
            let ratingEl = root.querySelector('.product-rating, .rating, #dynamicRating');
            if (!ratingEl) {
                ratingEl = document.createElement('div');
                ratingEl.className = 'product-rating';
                (root.querySelector('.product-name') || root).appendChild(ratingEl);
            }
            ratingEl.innerHTML = ratingMarkup(product);
        }
    }

    // Image move/resize edits made in the preview
    const container = root.querySelector('.product-image-container');
    const tx = product._imgTransform || { x: 0, y: 0, scale: 1 };
    if (container && (tx.x || tx.y || tx.scale !== 1)) {
        container.style.transform = `translate(${tx.x||0}px,${tx.y||0}px) scale(${tx.scale||1})`;
        container.style.transformOrigin = 'center center';
    }

    // Strip duplicate ids so many cards can live on one page
    root.removeAttribute('id');
    root.querySelectorAll('[id]').forEach(el => el.removeAttribute('id'));

    if (bgData) root.style.backgroundImage = `url('${bgData}')`;
    return root;
}

// Real size of the template's card, so previews/captures match it exactly
let BANNER_W = 800, BANNER_H = 850;
function measureBanner() {
    const probe = buildBannerNode({ name: 'Sample', sku: 'x' }, getCurrency(), bgDataUrl, null);
    probe.style.visibility = 'hidden';
    renderPool.appendChild(probe);
    const w = probe.offsetWidth, h = probe.offsetHeight;
    renderPool.removeChild(probe);
    if (w > 50 && h > 50) { BANNER_W = w; BANNER_H = h; }
}

// Used only when a template has no .promo-banner markup in its saved HTML
function buildFallbackBannerNode(product, curr, bgData, imgDataUrl) {
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

    const stars = ratingMarkup(product);
    const ratingHtml = stars ? `<div class="product-rating">${stars}</div>` : '';

    div.innerHTML = `
        <div class="product-name">${escHtml(name)}${ratingHtml}</div>
        <div class="product-image-container" style="${imgStyle}">
            ${imgDataUrl ? `<img src="${imgDataUrl}" alt="">` : ''}
        </div>
        ${displayWas ? `<span class="was-price">${escHtml(displayWas)}</span>` : ''}
        <span class="now-price">${escHtml(nowPrice)}</span>
    `;
    return div;
}

// ── Template-card mode: run the template's own renderProducts() and capture its card ──────
function tplFrameReady() {
    return new Promise(resolve => {
        const frame = document.getElementById('tplFrame');
        if (!frame) return resolve(false);
        const started = Date.now();
        (function poll() {
            const win = frame.contentWindow;
            if (win && typeof win.renderProducts === 'function' && win.document.getElementById('grid')) return resolve(true);
            if (Date.now() - started > 25000) return resolve(false);
            setTimeout(poll, 150);
        })();
    });
}

async function renderTemplateCard(product, curr, imgDataUrl) {
    const frame = document.getElementById('tplFrame');
    const win = frame.contentWindow, doc = frame.contentDocument;

    const thumb = imgDataUrl ? { id: 0, image_url: imgDataUrl, original_url: imgDataUrl, takealot_url: imgDataUrl } : null;
    // Same shape generate.blade.php hands to the template: prices already converted to the chosen currency
    const p = Object.assign({}, product, {
        price:           product.price      ? product.price * curr.rate : 0,
        sale_price:      product.sale_price ? product.sale_price * curr.rate : null,
        original_price:      product.price,
        original_sale_price: product.sale_price,
        currency_symbol: curr.symbol,
        currency_code:   curr.code,
        thumbnail:         thumb,
        product_thumbnail: thumb,
    });

    win.renderProducts([p], {});               // template's own default layout → real card size
    const card = doc.querySelector('#grid .card') || doc.querySelector('.card');
    if (!card) throw new Error('The template did not draw a product card');

    await Promise.all(Array.from(card.querySelectorAll('img')).map(img =>
        img.complete ? null : new Promise(r => { img.onload = img.onerror = r; })));
    await new Promise(r => setTimeout(r, 150));

    const canvas = await html2canvas(card, {
        scale:           2,
        useCORS:         true,
        allowTaint:      false,
        backgroundColor: null,
        logging:         false,
        ignoreElements:  el => el.matches && el.matches(CARD_HIDE_SELECTOR),
        onclone: clonedDoc => clonedDoc.querySelectorAll(CARD_HIDE_SELECTOR).forEach(e => e.remove()),
    });
    return canvas.toDataURL('image/webp', 0.9);
}

function addTemplateCardPreview(product, i) {
    const card = document.createElement('div');
    card.className = 'banner-card';
    card.dataset.index = i;
    card.innerHTML = `
        <img src="${product._cardDataUrl}" alt="" style="display:block;width:320px;max-width:100%;">
        <div class="edit-toolbar">
            <button class="btn btn-sm btn-info btn-edit-replace" title="Replace image"><i class="bi bi-upload"></i> Replace image</button>
        </div>
        <div class="banner-label"><strong>${escHtml(product.sku)}</strong> — ${escHtml((product.name || '').substring(0, 50))}</div>
    `;
    const fileInput = document.createElement('input');
    fileInput.type = 'file'; fileInput.accept = 'image/*'; fileInput.style.display = 'none';
    card.appendChild(fileInput);
    card.querySelector('.btn-edit-replace').addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        if (!file) return;
        const dataUrl = await new Promise(resolve => { const r = new FileReader(); r.onload = () => resolve(r.result); r.readAsDataURL(file); });
        product._imgDataUrl = dataUrl;
        product._cardDataUrl = await renderTemplateCard(product, getCurrency(), dataUrl);
        card.querySelector('img').src = product._cardDataUrl;
        fileInput.value = '';
    });
    previewGrid.appendChild(card);
}

// ── Render previews ────────────────────────────────────────────────────────
async function renderPreviews(products) {
    const curr = getCurrency();
    const bg   = getBgUrl();

    // Flyer templates: wait for the template's own renderProducts() to be available
    TEMPLATE_CARD_MODE = USES_TEMPLATE_CARDS && await tplFrameReady();
    if (USES_TEMPLATE_CARDS && !TEMPLATE_CARD_MODE) {
        hideLoading();
        throw new Error('The template did not finish loading (no renderProducts found). Open the template and check its script.');
    }

    // Background is already base64 from PHP — use directly, skip toDataUrl()
    bgDataUrl = getBgUrl();
    if (!TEMPLATE_CARD_MODE) {
        measureBanner();
        PREVIEW_SCALE = Math.min(0.5, 300 / BANNER_W);
    }

    previewGrid.innerHTML = '';

    for (let i = 0; i < products.length; i++) {
        const product = products[i];
        setProgress(5 + Math.round((i / products.length) * 30), `Loading product ${i+1}/${products.length}…`);

        const thumbnail  = product.product_thumbnail || product.thumbnail;
        const rawImgUrl  = thumbnail?.image_url || thumbnail?.original_url || '';

        // Try server-side background removal (cached by SKU)
        let imgDataUrl = null;
        if (removeBgToggle.checked && rawImgUrl && product.sku) {
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

        if (TEMPLATE_CARD_MODE) {
            setProgress(35 + Math.round((i / products.length) * 60), `Drawing card ${i+1}/${products.length}…`);
            product._cardDataUrl = await renderTemplateCard(product, curr, imgDataUrl);
            addTemplateCardPreview(product, i);
            continue;
        }

        const bannerNode = buildBannerNode(product, curr, bgDataUrl, imgDataUrl);

        // Scaled preview card
        const outer = document.createElement('div');
        outer.className = 'banner-scale-outer';
        outer.style.width  = (BANNER_W * PREVIEW_SCALE) + 'px';
        outer.style.height = (BANNER_H * PREVIEW_SCALE) + 'px';
        const wrap = document.createElement('div');
        wrap.className = 'banner-scale-wrap';
        wrap.style.width  = BANNER_W + 'px';
        wrap.style.height = BANNER_H + 'px';
        wrap.style.transform = `scale(${PREVIEW_SCALE})`;
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
let PREVIEW_SCALE = 0.35; // set from the template's real size in renderPreviews()

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
// Captures the WHOLE banner card (background, product image, name, rating, prices…)
// and uploads in small batches so a big SKU list never builds one giant request or
// holds hundreds of images in memory at once.
const SAVE_BATCH_SIZE = 5;

async function captureCard(product, curr) {
    if (TEMPLATE_CARD_MODE) return product._cardDataUrl;   // exactly what the preview shows
    const node = buildBannerNode(product, curr, bgDataUrl, product._imgDataUrl || null);
    renderPool.appendChild(node);
    try {
        // Let the DOM settle before capture
        await new Promise(r => setTimeout(r, 80));
        const canvas = await html2canvas(node, {
            scale:           BANNER_W <= 600 ? 2 : 1,
            useCORS:         true,
            allowTaint:      false,  // data URLs don't taint the canvas
            backgroundColor: null,
            width:           BANNER_W,
            height:          BANNER_H,
            logging:         false,
        });
        // WebP at 85% quality: ~3-5× smaller than PNG, fully supported by Facebook
        return canvas.toDataURL('image/webp', 0.85);
    } finally {
        if (node.parentNode === renderPool) renderPool.removeChild(node);
    }
}

async function uploadBatch(items) {
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
    return data.saved;
}

btnSave.addEventListener('click', async () => {
    if (!loadedProducts.length) return;

    const curr  = getCurrency();
    const total = loadedProducts.length;
    let batch   = [];
    let failed  = 0;
    savedItems  = [];

    showLoading('Saving banners…', `0 / ${total}`, 0);

    try {
        for (let i = 0; i < total; i++) {
            const product = loadedProducts[i];
            setProgress(Math.round((i / total) * 100), `Capturing & saving ${i + 1} / ${total}`);

            try {
                const imageDataUrl = await captureCard(product, curr);
                batch.push({
                    sku:          product.sku,
                    imageDataUrl,
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
            } catch (e) {
                failed++;
                console.error('Capture failed for', product.sku, e);
            }

            if (batch.length >= SAVE_BATCH_SIZE || (i === total - 1 && batch.length)) {
                const saved = await uploadBatch(batch);
                savedItems.push(...saved);
                batch = [];
            }
        }

        if (!savedItems.length) throw new Error('No banners could be captured');

        setProgress(100, `Saved ${savedItems.length} image(s)`);
        hideLoading();

        savedItems.forEach(item => {
            const idx = loadedProducts.findIndex(p => String(p.sku).replace(/[^a-zA-Z0-9\-_]/g, '') === item.sku);
            const card = previewGrid.children[idx];
            if (card && !card.querySelector('.save-status')) {
                const badge = document.createElement('span');
                badge.className = 'save-status badge bg-success';
                badge.textContent = '✓ Saved';
                card.appendChild(badge);
            }
        });

        btnSave.classList.add('d-none');
        btnDownloadCsv.classList.remove('d-none');
        setStatus(`<i class="bi bi-check-circle me-1"></i>Saved ${savedItems.length} full banner image(s) to media storage` + (failed ? ` — ${failed} failed to capture` : ''), failed ? 'warning' : 'success');
    } catch (e) {
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
        const link  = `${frontendUrl}/product/${product.slug || sku}`;
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
