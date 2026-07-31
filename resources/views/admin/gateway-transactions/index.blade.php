@extends('admin.layout')
@section('title', 'Gateway Transactions')
@section('page-title', 'Gateway Transactions')

@push('styles')
<style>
/* ── Tabs ───────────────────────────────────────────────── */
.gtab-bar{display:flex;flex-wrap:wrap;gap:4px;border-bottom:2px solid #e5e7eb;padding-bottom:0;margin-bottom:24px;}
.gtab-btn{padding:9px 18px;border:none;background:none;font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;border-radius:6px 6px 0 0;transition:all .15s;display:flex;align-items:center;gap:6px;}
.gtab-btn:hover{color:#111;background:#f3f4f6;}
.gtab-btn.active{color:var(--theme-color,#f8642a);border-bottom-color:var(--theme-color,#f8642a);background:#fff;}
.gtab-pane{display:none;}.gtab-pane.active{display:block;}

/* ── Filters ────────────────────────────────────────────── */
.fc{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.07);padding:16px 20px;margin-bottom:18px;}
.fg{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end;}
.fl label{font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:4px;}
.fl input,.fl select{width:100%;padding:7px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;outline:none;transition:border .15s;}
.fl input:focus,.fl select:focus{border-color:var(--theme-color,#f8642a);}
.fb{padding:7px 16px;border-radius:6px;border:none;font-size:13px;font-weight:600;cursor:pointer;}
.fb.ap{background:var(--theme-color,#f8642a);color:#fff;}.fb.ap:hover{filter:brightness(1.1);}
.fb.cl{background:#f3f4f6;color:#374151;margin-left:4px;}.fb.cl:hover{background:#e5e7eb;}

/* ── Table ──────────────────────────────────────────────── */
.tc{background:#fff;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden;}
.tr-outer{overflow-x:auto;}
table.gt{width:100%;border-collapse:collapse;font-size:13px;}
.gt th{background:#f9fafb;padding:9px 12px;text-align:left;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;border-bottom:1px solid #e5e7eb;}
.gt th a{color:inherit;text-decoration:none;display:flex;align-items:center;gap:3px;}
.gt th a:hover{color:#374151;}
.gt td{padding:9px 12px;border-bottom:1px solid #f3f4f6;vertical-align:middle;color:#374151;}
.gt tr:last-child td{border-bottom:none;}
.gt tr:hover td{background:#fafafa;}
.mono{font-family:monospace;font-size:12px;}
.tm{font-size:11px;color:#9ca3af;}

/* Status badges */
.bs{display:inline-flex;align-items:center;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.bs.ok{background:#dcfce7;color:#16a34a;}
.bs.fail{background:#fee2e2;color:#dc2626;}
.bs.pend{background:#fef9c3;color:#b45309;}
.bs.info{background:#e0f2fe;color:#0369a1;}
.bs.def{background:#f3f4f6;color:#6b7280;}

/* Pagination */
.tf{display:flex;align-items:center;justify-content:space-between;padding:11px 14px;border-top:1px solid #f3f4f6;font-size:12px;color:#9ca3af;}
.pg{display:flex;gap:3px;}
.pg a,.pg span{padding:4px 9px;border:1px solid #e5e7eb;border-radius:5px;font-size:12px;font-weight:500;color:#374151;text-decoration:none;}
.pg a:hover{background:#f3f4f6;}
.pg span.cur{background:var(--theme-color,#f8642a);color:#fff;border-color:var(--theme-color,#f8642a);}
.no-data{text-align:center;padding:48px 20px;color:#9ca3af;}
.no-data i{font-size:36px;display:block;margin-bottom:8px;}
.loading{text-align:center;padding:48px 20px;color:#9ca3af;}
/* View btn */
.btn-detail{background:none;border:1px solid #e5e7eb;border-radius:5px;padding:3px 10px;font-size:12px;color:#374151;cursor:pointer;transition:all .15s;white-space:nowrap;}
.btn-detail:hover{background:var(--theme-color,#f8642a);color:#fff;border-color:var(--theme-color,#f8642a);}
/* ── Modal ──────────────────────────────────────────────── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9000;display:flex;align-items:center;justify-content:center;padding:16px;}
.modal-box{background:#fff;border-radius:14px;box-shadow:0 24px 64px rgba(0,0,0,.22);width:100%;max-width:720px;max-height:92vh;overflow-y:auto;}
.modal-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:2px solid #f3f4f6;position:sticky;top:0;background:#fff;z-index:1;border-radius:14px 14px 0 0;}
.modal-hd h3{margin:0;font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px;}
.modal-hd .modal-badge{font-size:11px;font-weight:600;padding:2px 10px;border-radius:20px;background:#f3f4f6;color:#6b7280;}
.modal-close{background:none;border:none;font-size:22px;cursor:pointer;color:#9ca3af;padding:2px 6px;border-radius:6px;line-height:1;}
.modal-close:hover{background:#fee2e2;color:#dc2626;}
.modal-body{padding:22px 24px;}
.det-section{margin-bottom:22px;}
.det-section-title{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid #f3f4f6;display:flex;align-items:center;gap:6px;}
.det-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
.det-grid.cols1{grid-template-columns:1fr;}
.det-field{background:#f9fafb;border-radius:8px;padding:10px 13px;border:1px solid #f3f4f6;}
.det-field .lbl{font-size:10px;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.05em;margin-bottom:4px;}
.det-field .val{font-size:13px;color:#111827;font-weight:500;word-break:break-all;line-height:1.4;}
.det-field .val.big{font-size:20px;font-weight:800;color:var(--theme-color,#f8642a);}
.det-field .val.ok{color:#16a34a;font-weight:700;}
.det-field .val.fail{color:#dc2626;font-weight:700;}
.det-field .val.mono{font-family:monospace;font-size:12px;}
.det-field .val.muted{color:#9ca3af;}
@media(max-width:520px){.det-grid{grid-template-columns:1fr;}}
</style>
@endpush

@section('content')
<div class="container-fluid p-3">

    <div class="gtab-bar">
        <button class="gtab-btn active" onclick="switchTab('pesepay',this)"><i class="bi bi-credit-card-2-front"></i> PesePay</button>
        <button class="gtab-btn" onclick="switchTab('payfast',this)"><i class="bi bi-lightning-charge"></i> PayFast</button>
        <button class="gtab-btn" onclick="switchTab('dpo',this)"><i class="bi bi-globe2"></i> DPO Zambia</button>
        <button class="gtab-btn" onclick="switchTab('yoco',this)"><i class="bi bi-phone"></i> Yoco</button>
        <button class="gtab-btn" onclick="switchTab('order-txn',this)"><i class="bi bi-receipt"></i> Order Ref Txns</button>
        <button class="gtab-btn" onclick="switchTab('txn',this)"><i class="bi bi-wallet2"></i> Transactions</button>
        <button class="gtab-btn" onclick="switchTab('vendor-txn',this)"><i class="bi bi-shop"></i> Vendor Transactions</button>
    </div>

    {{-- PesePay --}}
    <div class="gtab-pane active" id="tab-pesepay">
        <div class="fc"><form id="ff-pesepay" onsubmit="applyFilter('pesepay');return false;"><div class="fg">
            <div class="fl"><label>Search</label><input name="search" placeholder="Reference, reason…"></div>
            <div class="fl"><label>Status</label><select name="status"><option value="">All</option><option>SUCCESS</option><option>FAILED</option><option>PENDING</option><option>CANCELLED</option></select></div>
            <div class="fl"><label>Currency</label><select name="currency"><option value="">All</option><option>USD</option><option>ZAR</option><option>ZMW</option></select></div>
            <div class="fl"><label>Date From</label><input type="date" name="date_from"></div>
            <div class="fl"><label>Date To</label><input type="date" name="date_to"></div>
            <div class="fl" style="display:flex;flex-direction:row;align-items:flex-end;gap:4px">
                <button class="fb ap" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                <button class="fb cl" type="button" onclick="clearFilter('pesepay')"><i class="bi bi-x"></i> Clear</button>
            </div>
        </div></form></div>
        <div class="tc"><div id="tc-pesepay"><div class="loading"><i class="bi bi-hourglass-split" style="font-size:28px;display:block;margin-bottom:8px"></i>Loading…</div></div></div>
    </div>

    {{-- PayFast --}}
    <div class="gtab-pane" id="tab-payfast">
        <div class="fc"><form id="ff-payfast" onsubmit="applyFilter('payfast');return false;"><div class="fg">
            <div class="fl"><label>Search</label><input name="search" placeholder="PF ID, email, item…"></div>
            <div class="fl"><label>Status</label><select name="status"><option value="">All</option><option>COMPLETE</option><option>FAILED</option><option>PENDING</option></select></div>
            <div class="fl"><label>Type</label><select name="type"><option value="">All</option><option value="LAYBY_PAYMENT">Layby Payment</option><option value="ORDER">Order</option></select></div>
            <div class="fl"><label>Date From</label><input type="date" name="date_from"></div>
            <div class="fl"><label>Date To</label><input type="date" name="date_to"></div>
            <div class="fl" style="display:flex;flex-direction:row;align-items:flex-end;gap:4px">
                <button class="fb ap" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                <button class="fb cl" type="button" onclick="clearFilter('payfast')"><i class="bi bi-x"></i> Clear</button>
            </div>
        </div></form></div>
        <div class="tc"><div id="tc-payfast"><div class="loading">Loading…</div></div></div>
    </div>

    {{-- DPO Zambia --}}
    <div class="gtab-pane" id="tab-dpo">
        <div class="fc"><form id="ff-dpo" onsubmit="applyFilter('dpo');return false;"><div class="fg">
            <div class="fl"><label>Search</label><input name="search" placeholder="Trans ID, customer, ref…"></div>
            <div class="fl"><label>Status</label><select name="status"><option value="">All</option><option value="000">Approved (000)</option><option value="901">Failed</option></select></div>
            <div class="fl"><label>Currency</label><select name="currency"><option value="">All</option><option>ZMW</option><option>USD</option><option>ZAR</option></select></div>
            <div class="fl"><label>Date From</label><input type="date" name="date_from"></div>
            <div class="fl"><label>Date To</label><input type="date" name="date_to"></div>
            <div class="fl" style="display:flex;flex-direction:row;align-items:flex-end;gap:4px">
                <button class="fb ap" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                <button class="fb cl" type="button" onclick="clearFilter('dpo')"><i class="bi bi-x"></i> Clear</button>
            </div>
        </div></form></div>
        <div class="tc"><div id="tc-dpo"><div class="loading">Loading…</div></div></div>
    </div>

    {{-- Yoco --}}
    <div class="gtab-pane" id="tab-yoco">
        <div class="fc"><form id="ff-yoco" onsubmit="applyFilter('yoco');return false;"><div class="fg">
            <div class="fl"><label>Search</label><input name="search" placeholder="Gateway ID, order #…"></div>
            <div class="fl"><label>Status</label><select name="status"><option value="">All</option><option>successful</option><option>failed</option><option>pending</option></select></div>
            <div class="fl"><label>Currency</label><select name="currency"><option value="">All</option><option>ZAR</option><option>USD</option></select></div>
            <div class="fl"><label>Date From</label><input type="date" name="date_from"></div>
            <div class="fl"><label>Date To</label><input type="date" name="date_to"></div>
            <div class="fl" style="display:flex;flex-direction:row;align-items:flex-end;gap:4px">
                <button class="fb ap" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                <button class="fb cl" type="button" onclick="clearFilter('yoco')"><i class="bi bi-x"></i> Clear</button>
            </div>
        </div></form></div>
        <div class="tc"><div id="tc-yoco"><div class="loading">Loading…</div></div></div>
    </div>

    {{-- Order Transactions --}}
    <div class="gtab-pane" id="tab-order-txn">
        <div class="fc"><form id="ff-order-txn" onsubmit="applyFilter('order-txn');return false;"><div class="fg">
            <div class="fl"><label>Search</label><input name="search" placeholder="Transaction ID, order #…"></div>
            <div class="fl"><label>Date From</label><input type="date" name="date_from"></div>
            <div class="fl"><label>Date To</label><input type="date" name="date_to"></div>
            <div class="fl" style="display:flex;flex-direction:row;align-items:flex-end;gap:4px">
                <button class="fb ap" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                <button class="fb cl" type="button" onclick="clearFilter('order-txn')"><i class="bi bi-x"></i> Clear</button>
            </div>
        </div></form></div>
        <div class="tc"><div id="tc-order-txn"><div class="loading">Loading…</div></div></div>
    </div>

    {{-- Transactions (wallet/points) --}}
    <div class="gtab-pane" id="tab-txn">
        <div class="fc"><form id="ff-txn" onsubmit="applyFilter('txn');return false;"><div class="fg">
            <div class="fl"><label>Search</label><input name="search" placeholder="Detail, type…"></div>
            <div class="fl"><label>Type</label><select name="type"><option value="">All</option><option>credit</option><option>debit</option></select></div>
            <div class="fl"><label>Source</label><select name="source"><option value="">All</option><option value="wallet">Wallet</option><option value="points">Points</option></select></div>
            <div class="fl"><label>Date From</label><input type="date" name="date_from"></div>
            <div class="fl"><label>Date To</label><input type="date" name="date_to"></div>
            <div class="fl" style="display:flex;flex-direction:row;align-items:flex-end;gap:4px">
                <button class="fb ap" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                <button class="fb cl" type="button" onclick="clearFilter('txn')"><i class="bi bi-x"></i> Clear</button>
            </div>
        </div></form></div>
        <div class="tc"><div id="tc-txn"><div class="loading">Loading…</div></div></div>
    </div>

    {{-- Vendor Transactions --}}
    <div class="gtab-pane" id="tab-vendor-txn">
        <div class="fc"><form id="ff-vendor-txn" onsubmit="applyFilter('vendor-txn');return false;"><div class="fg">
            <div class="fl"><label>Search</label><input name="search" placeholder="Detail, type…"></div>
            <div class="fl"><label>Type</label><select name="type"><option value="">All</option><option>credit</option><option>debit</option></select></div>
            <div class="fl"><label>Date From</label><input type="date" name="date_from"></div>
            <div class="fl"><label>Date To</label><input type="date" name="date_to"></div>
            <div class="fl" style="display:flex;flex-direction:row;align-items:flex-end;gap:4px">
                <button class="fb ap" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                <button class="fb cl" type="button" onclick="clearFilter('vendor-txn')"><i class="bi bi-x"></i> Clear</button>
            </div>
        </div></form></div>
        <div class="tc"><div id="tc-vendor-txn"><div class="loading">Loading…</div></div></div>
    </div>

</div>

{{-- Universal Detail Modal --}}
<div class="modal-overlay" id="det-modal" style="display:none" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-hd">
            <h3 id="det-modal-title"><i class="bi bi-info-circle"></i> Transaction Details</h3>
            <button class="modal-close" onclick="closeModal()">&#x2715;</button>
        </div>
        <div class="modal-body" id="det-modal-body"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ROUTES = {
    'pesepay':   '/admin/gateway-transactions/pesepay',
    'payfast':   '/admin/gateway-transactions/payfast',
    'dpo':       '/admin/gateway-transactions/dpo',
    'yoco':      '/admin/gateway-transactions/yoco',
    'order-txn': '/admin/gateway-transactions/order-transactions',
    'txn':       '/admin/gateway-transactions/transactions',
    'vendor-txn':'/admin/gateway-transactions/vendor-transactions',
};

function switchTab(tab, btn) {
    document.querySelectorAll('.gtab-pane').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.gtab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById('tab-'+tab).classList.add('active');
    btn.classList.add('active');
    const el=document.getElementById('tc-'+tab);
    if(!el.dataset.loaded) loadTab(tab);
}

function loadTab(tab) {
    const el=document.getElementById('tc-'+tab);
    el.innerHTML='<div class="loading"><i class="bi bi-hourglass-split" style="font-size:28px;display:block;margin-bottom:8px"></i>Loading&#x2026;</div>';
    const params=new URLSearchParams(getFilters(tab));
    fetch(ROUTES[tab]+'?'+params,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(r=>r.json())
        .then(data=>{el.dataset.loaded='1';render(tab,data,el);})
        .catch(()=>{el.innerHTML='<div class="no-data"><i class="bi bi-exclamation-triangle"></i>Failed to load data.</div>';});
}

function applyFilter(tab){const el=document.getElementById('tc-'+tab);el.dataset.loaded='';loadTab(tab);}
function clearFilter(tab){const f=document.getElementById('ff-'+tab);if(f)f.reset();applyFilter(tab);}
function getFilters(tab){const f=document.getElementById('ff-'+tab);return f?Object.fromEntries(new FormData(f)):{};}

function goPage(tab,url){
    const el=document.getElementById('tc-'+tab);
    el.innerHTML='<div class="loading">Loading&#x2026;</div>';
    const params=new URLSearchParams(getFilters(tab));
    fetch(url+'&'+params,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(r=>r.json()).then(data=>render(tab,data,el)).catch(()=>{});
}

function sortBy(tab,col){
    const f=document.getElementById('ff-'+tab);
    const p=f?new URLSearchParams(new FormData(f)):new URLSearchParams();
    const cur=p.get('sort'),curDir=p.get('dir')||'desc';
    p.set('sort',col);p.set('dir',(cur===col&&curDir==='asc')?'desc':'asc');
    const el=document.getElementById('tc-'+tab);
    el.innerHTML='<div class="loading">Loading&#x2026;</div>';
    fetch(ROUTES[tab]+'?'+p,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
        .then(r=>r.json()).then(data=>{el.dataset.loaded='1';render(tab,data,el);}).catch(()=>{});
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function esc(s){return s?String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;'):''; }
function badge(val){
    if(!val)return'<span class="bs def">&#x2014;</span>';
    const v=String(val).toLowerCase();
    if(['success','complete','completed','paid','approved','000'].some(x=>v.includes(x)))return`<span class="bs ok">${val}</span>`;
    if(['failed','fail','declined','error','cancelled','canceled'].some(x=>v.includes(x)))return`<span class="bs fail">${val}</span>`;
    if(['pending','processing'].includes(v))return`<span class="bs pend">${val}</span>`;
    return`<span class="bs info">${val}</span>`;
}
function fmtDate(d){if(!d)return'&#x2014;';return new Date(d).toLocaleString('en-ZA',{dateStyle:'medium',timeStyle:'short'});}
function fmtAmt(v,c){if(v==null||v==='')return'&#x2014;';return`<strong>${Number(v).toFixed(2)}</strong>${c?' <small class="tm">'+c+'</small>':''}`;}
function thSort(tab,col,label){return`<th><a href="#" onclick="sortBy('${tab}','${col}');return false">${label} <i class="bi bi-arrow-down-up" style="font-size:9px;opacity:.4"></i></a></th>`;}
function viewBtn(data){const s=encodeURIComponent(JSON.stringify(data));return`<button class="btn-detail" onclick="openModal(decodeURIComponent('${s}'))"><i class="bi bi-eye"></i></button>`;}
function pgHtml(data,tab){
    if(!data.links||data.links.length<=3)return'';
    let h='<div class="pg">';
    data.links.forEach(l=>{
        if(l.active)h+=`<span class="cur">${l.label}</span>`;
        else if(l.url)h+=`<a href="#" onclick="goPage('${tab}','${l.url}');return false">${l.label}</a>`;
        else h+=`<span style="color:#d1d5db">${l.label}</span>`;
    });
    return h+'</div>';
}
function footer(data,tab){return`<div class="tf"><span>Showing ${data.from||0}&#x2013;${data.to||0} of <strong>${data.total||0}</strong></span>${pgHtml(data,tab)}</div>`;}
function tableWrap(hd,rows,data,tab){
    if(!rows.length)return'<div class="no-data"><i class="bi bi-inbox"></i><br>No transactions found.</div>';
    return`<div class="tr-outer"><table class="gt"><thead><tr>${hd}</tr></thead><tbody>${rows.join('')}</tbody></table></div>${footer(data,tab)}`;
}

// ── Render dispatcher ─────────────────────────────────────────────────────────
function render(tab,data,el){
    const rows=data.data||[];
    if(tab==='pesepay')         el.innerHTML=renderPesepay(rows,data);
    else if(tab==='payfast')    el.innerHTML=renderPayfast(rows,data);
    else if(tab==='dpo')        el.innerHTML=renderDpo(rows,data);
    else if(tab==='yoco')       el.innerHTML=renderYoco(rows,data);
    else if(tab==='order-txn')  el.innerHTML=renderOrderTxn(rows,data);
    else if(tab==='txn')        el.innerHTML=renderTxn(rows,data);
    else if(tab==='vendor-txn') el.innerHTML=renderVendorTxn(rows,data);
}

// ── PesePay ───────────────────────────────────────────────────────────────────
function renderPesepay(rows,data){
    const hd=['id','reference_number','transaction_status','amount','currency_code','reason_for_payment','application_name','created_at']
        .map((c,i)=>thSort('pesepay',c,['#','Reference','Status','Amount','Currency','Reason','Application','Date'][i])).join('')+'<th></th>';
    const tr=rows.map(r=>`<tr>
        <td class="tm mono">${r.id}</td>
        <td class="mono" style="max-width:190px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.reference_number)||'&#x2014;'}</td>
        <td>${badge(r.transaction_status)}</td>
        <td>${fmtAmt(r.amount,r.currency_code)}</td>
        <td class="mono">${r.currency_code||'&#x2014;'}</td>
        <td style="max-width:190px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(r.reason_for_payment)}">${esc(r.reason_for_payment)||'&#x2014;'}</td>
        <td>${esc(r.application_name)||'&#x2014;'}</td>
        <td class="tm">${fmtDate(r.created_at)}</td>
        <td>${viewBtn(r)}</td></tr>`);
    return tableWrap(hd,tr,data,'pesepay');
}

// ── PayFast ───────────────────────────────────────────────────────────────────
function renderPayfast(rows,data){
    const hd=['id','pf_payment_id','payment_status','amount_gross','amount_net','item_name','email_address','custom_str1','custom_int2','created_at']
        .map((c,i)=>thSort('payfast',c,['#','PF Payment ID','Status','Gross','Net','Item','Email','Type','Order #','Date'][i])).join('')+'<th></th>';
    const tr=rows.map(r=>`<tr>
        <td class="tm mono">${r.id}</td>
        <td class="mono">${esc(r.pf_payment_id||r.m_payment_id)||'&#x2014;'}</td>
        <td>${badge(r.payment_status)}</td>
        <td><strong>${r.amount_gross!=null?Number(r.amount_gross).toFixed(2):'&#x2014;'}</strong></td>
        <td><strong>${r.amount_net!=null?Number(r.amount_net).toFixed(2):'&#x2014;'}</strong></td>
        <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.item_name)||'&#x2014;'}</td>
        <td>${esc(r.email_address)||'&#x2014;'}</td>
        <td>${r.custom_str1?`<span class="bs info">${esc(r.custom_str1)}</span>`:'&#x2014;'}</td>
        <td class="mono">${r.custom_int2||'&#x2014;'}</td>
        <td class="tm">${fmtDate(r.created_at)}</td>
        <td>${viewBtn(r)}</td></tr>`);
    return tableWrap(hd,tr,data,'payfast');
}

// ── DPO Zambia ────────────────────────────────────────────────────────────────
function renderDpo(rows,data){
    const hd=['id','trans_id','_s','payment_amount','transaction_currency','customer_name','company_ref','created_at']
        .map((c,i)=>thSort('dpo',i===2?'transaction_status':c,['#','Trans ID','Status','Amount','Currency','Customer','Company Ref','Date'][i])).join('')+'<th></th>';
    const tr=rows.map(r=>{
        let raw={};
        try{raw=r.raw_response?(typeof r.raw_response==='string'?JSON.parse(r.raw_response):r.raw_response):{};}catch(e){}
        const amt=raw.TransactionAmount||r.payment_amount;
        const cur=raw.TransactionCurrency||r.transaction_currency;
        const isOk=(raw.Result==='000')||String(r.transaction_status||'').toLowerCase().includes('approv');
        const sb=isOk?`<span class="bs ok">${raw.Result||r.transaction_status||'&#x2014;'}</span>`:`<span class="bs fail">${raw.Result||r.transaction_status||'&#x2014;'}</span>`;
        return`<tr>
            <td class="tm mono">${r.id}</td>
            <td class="mono">${esc(r.trans_id)||'&#x2014;'}</td>
            <td>${sb}</td>
            <td>${fmtAmt(amt,cur)}</td>
            <td class="mono">${cur||'&#x2014;'}</td>
            <td>${esc(raw.CustomerName||r.customer_name)||'&#x2014;'}</td>
            <td class="mono">${esc(r.company_ref)||'&#x2014;'}</td>
            <td class="tm">${fmtDate(r.created_at)}</td>
            <td>${viewBtn({...r,_raw:raw})}</td></tr>`;
    });
    return tableWrap(hd,tr,data,'dpo');
}

// ── Yoco ──────────────────────────────────────────────────────────────────────
function renderYoco(rows,data){
    const hd=['id','gateway_transaction_id','status','amount_cents','currency','order_number','created_at']
        .map((c,i)=>thSort('yoco',c,['#','Gateway ID','Status','Amount (cents)','Currency','Order #','Date'][i])).join('')+'<th></th>';
    const tr=rows.map(r=>`<tr>
        <td class="tm mono">${r.id}</td>
        <td class="mono">${esc(r.gateway_transaction_id)||'&#x2014;'}</td>
        <td>${badge(r.status)}</td>
        <td>${r.amount_cents!=null?`<strong>${r.amount_cents}</strong> <small class="tm">cents</small>`:'&#x2014;'}</td>
        <td class="mono">${r.currency||'&#x2014;'}</td>
        <td class="mono">${r.order_number||r.order?.order_number||'&#x2014;'}</td>
        <td class="tm">${fmtDate(r.created_at)}</td>
        <td>${viewBtn(r)}</td></tr>`);
    return tableWrap(hd,tr,data,'yoco');
}

// ── Order Ref Transactions (order_transactions table - gateway ref IDs only) ──
function renderOrderTxn(rows,data){
    const hd=['id','transaction_id','order_id','_on','created_at']
        .map((c,i)=>thSort('order-txn',i===3?'order_id':c,['#','Transaction ID','Order ID','Order #','Date'][i])).join('')+'<th></th>';
    const tr=rows.map(r=>`<tr>
        <td class="tm mono">${r.id}</td>
        <td class="mono" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(r.transaction_id)}">${esc(r.transaction_id)||'&#x2014;'}</td>
        <td class="mono">${r.order_id||'&#x2014;'}</td>
        <td class="mono">${r.order?.order_number||'&#x2014;'}</td>
        <td class="tm">${fmtDate(r.created_at)}</td>
        <td>${viewBtn(r)}</td></tr>`);
    return tableWrap(hd,tr,data,'order-txn');
}

// ── Transactions (transactions table - wallet/points with amount, type, detail) ──
function renderTxn(rows,data){
    const hd=['id','type','amount','order_id','wallet_id','_src','detail','created_at']
        .map((c,i)=>thSort('txn',i===5?'wallet_id':c,['#','Type','Amount','Order ID','Wallet ID','Source','Detail','Date'][i])).join('')+'<th></th>';
    const tr=rows.map(r=>{
        const isC=r.type==='credit';
        const src=r.point_id?'<span class="bs info">Points</span>':'<span class="bs def">Wallet</span>';
        return`<tr>
            <td class="tm mono">${r.id}</td>
            <td>${isC?'<span class="bs ok">&#x2191; Credit</span>':'<span class="bs fail">&#x2193; Debit</span>'}</td>
            <td><strong style="color:${isC?'#16a34a':'#dc2626'}">${r.amount!=null?Number(r.amount).toFixed(2):'&#x2014;'}</strong></td>
            <td class="mono">${r.order_id||'&#x2014;'}</td>
            <td class="mono">${r.wallet_id||'&#x2014;'}</td>
            <td>${src}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(r.detail)}">${esc(r.detail)||'&#x2014;'}</td>
            <td class="tm">${fmtDate(r.created_at)}</td>
            <td>${viewBtn(r)}</td></tr>`;
    });
    return tableWrap(hd,tr,data,'txn');
}

// ── Vendor Transactions ───────────────────────────────────────────────────────
function renderVendorTxn(rows,data){
    const hd=['id','type','amount','vendor_id','vendor_wallet_id','detail','created_at']
        .map((c,i)=>thSort('vendor-txn',c,['#','Type','Amount','Vendor ID','Wallet ID','Detail','Date'][i])).join('')+'<th></th>';
    const tr=rows.map(r=>{
        const isC=r.type==='credit';
        return`<tr>
            <td class="tm mono">${r.id}</td>
            <td>${isC?'<span class="bs ok">&#x2191; Credit</span>':'<span class="bs fail">&#x2193; Debit</span>'}</td>
            <td><strong style="color:${isC?'#16a34a':'#dc2626'}">${r.amount!=null?Number(r.amount).toFixed(2):'&#x2014;'}</strong></td>
            <td class="mono">${r.vendor_id||'&#x2014;'}</td>
            <td class="mono">${r.vendor_wallet_id||'&#x2014;'}</td>
            <td style="max-width:210px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${esc(r.detail)}">${esc(r.detail)||'&#x2014;'}</td>
            <td class="tm">${fmtDate(r.created_at)}</td>
            <td>${viewBtn(r)}</td></tr>`;
    });
    return tableWrap(hd,tr,data,'vendor-txn');
}

// ── Universal Modal ───────────────────────────────────────────────────────────
const LABEL_MAPS = {
    pesepay:{'id':'Record ID','reference_number':'Reference Number','transaction_status':'Status','amount':'Amount','currency_code':'Currency','reason_for_payment':'Reason for Payment','application_name':'Application','application_id':'App ID','user_id':'User ID','api_id':'API ID','internal_reference':'Internal Ref','merchant_reference':'Merchant Ref','transaction_type':'Type','charge_type':'Charge Type','liquidation_status':'Liquidation','settlement_mode':'Settlement Mode','redirect_required':'Redirect Required','poll_url':'Poll URL','redirect_url':'Redirect URL','result_url':'Result URL','return_url':'Return URL','transaction_status_code':'Status Code','transaction_status_description':'Status Desc','date_of_transaction':'Txn Date','transaction_date':'Date','time_of_transaction':'Time','created_at':'Created','updated_at':'Updated'},
    payfast:{'id':'Record ID','pf_payment_id':'PF Payment ID','m_payment_id':'Merchant Payment ID','payment_status':'Status','item_name':'Item Name','item_description':'Description','amount_gross':'Gross','amount_fee':'Fee','amount_net':'Net','custom_str1':'Type','custom_str2':'Custom 2','custom_str3':'Custom 3','custom_int1':'Custom Int 1','custom_int2':'Order #','custom_int3':'Custom Int 3','name_first':'First Name','name_last':'Last Name','email_address':'Email','merchant_id':'Merchant ID','signature':'Signature','created_at':'Created','updated_at':'Updated'},
    dpo:{'id':'Record ID','trans_id':'Trans ID','transaction_token':'Token','result':'Result','result_code':'Result Code','result_explanation':'Result Explanation','transaction_status':'Status','ccd_approval':'CCD Approval','company_ref':'Company Ref','transaction_currency':'Currency','payment_amount':'Amount','customer_name':'Customer','customer_phone':'Phone','customer_email':'Email','customer_country':'Country','fraud_alert':'Fraud Alert','fraud_explanation':'Fraud Explanation','date_created':'Created Date','date_approved':'Approved Date','created_at':'Created','updated_at':'Updated'},
    yoco:{'id':'Record ID','gateway_transaction_id':'Gateway ID','status':'Status','amount_cents':'Amount (cents)','currency':'Currency','order_number':'Order #','order_id':'Order ID','created_at':'Created','updated_at':'Updated'},
    'order-txn':{'id':'Record ID','transaction_id':'Transaction ID','order_id':'Order ID','created_at':'Created','updated_at':'Updated'},
    'txn':{'id':'Record ID','wallet_id':'Wallet ID','point_id':'Point ID','order_id':'Order ID','detail':'Detail','amount':'Amount','type':'Type','from':'From','created_at':'Created'},
    'vendor-txn':{'id':'Record ID','vendor_id':'Vendor ID','vendor_wallet_id':'Wallet ID','detail':'Detail','amount':'Amount','type':'Type','from':'From','created_at':'Created'},
};
const SKIP_KEYS=['raw_response','other_fields','amount_details','transaction_metadata','payment_metadata','payment_method_details','customer','customer_amount_paid','_raw','order','vendor_wallet'];

function openModal(jsonStr){
    let r={};try{r=JSON.parse(jsonStr);}catch(e){}
    let gw='pesepay';
    if(r.pf_payment_id!==undefined||r.m_payment_id!==undefined) gw='payfast';
    else if(r.trans_id!==undefined||r.transaction_token!==undefined) gw='dpo';
    else if(r.gateway_transaction_id!==undefined&&r.amount_cents!==undefined) gw='yoco';
    else if(r.transaction_id!==undefined&&r.wallet_id===undefined&&r.vendor_id===undefined) gw='order-txn';
    else if(r.wallet_id!==undefined||r.point_id!==undefined) gw='txn';
    else if(r.vendor_id!==undefined) gw='vendor-txn';

    const labels=LABEL_MAPS[gw]||{};
    const icons={pesepay:'bi-credit-card-2-front',payfast:'bi-lightning-charge',dpo:'bi-globe2',yoco:'bi-phone','order-txn':'bi-receipt','txn':'bi-wallet2','vendor-txn':'bi-shop'};
    const titles={pesepay:'PesePay Transaction',payfast:'PayFast Transaction',dpo:'DPO Zambia Transaction',yoco:'Yoco Transaction','order-txn':'Order Ref Transaction','txn':'Transaction (Wallet / Points)','vendor-txn':'Vendor Transaction'};

    document.getElementById('det-modal-title').innerHTML=`<i class="bi ${icons[gw]||'bi-info-circle'}"></i> ${titles[gw]||'Transaction'} <span class="modal-badge">#${r.id||'&#x2014;'}</span>`;

    const df=(lbl,val,cls='')=>{
        if(val===null||val===undefined||val==='') val='&#x2014;';
        if(typeof val==='boolean') val=val?'Yes':'No';
        if(typeof val==='object') val=`<code style="font-size:11px;word-break:break-all">${JSON.stringify(val)}</code>`;
        return`<div class="det-field"><div class="lbl">${lbl}</div><div class="val ${cls}">${val}</div></div>`;
    };

    let body='';
    const statusVal=r.transaction_status||r.payment_status||r.status||r.result||'';
    const amtVal=r.amount??r.amount_gross??r.payment_amount??r.amount_cents??null;
    const curVal=r.currency_code||r.transaction_currency||r.currency||'';
    const isOk=['success','complete','completed','paid','approved','000'].some(x=>String(statusVal).toLowerCase().includes(x));

    if(statusVal||amtVal!=null){
        body+=`<div class="det-section"><div class="det-section-title"><i class="bi bi-info-circle"></i> Overview</div><div class="det-grid">`;
        if(statusVal) body+=df('Status',`<span class="bs ${isOk?'ok':'fail'}">${statusVal}</span>`);
        if(amtVal!=null) body+=df('Amount',`${Number(amtVal).toFixed(2)} ${curVal}`,'big');
        body+=`</div></div>`;
    }

    // DPO raw section
    const raw=r._raw||null;
    if(gw==='dpo'&&raw&&typeof raw==='object'){
        body+=`<div class="det-section"><div class="det-section-title"><i class="bi bi-cash-stack"></i> Payment Details (Gateway)</div><div class="det-grid">`;
        const dpoMap={Result:'Result Code',ResultExplanation:'Result Explanation',TransactionApproval:'Approval Code',TransactionCurrency:'Currency',TransactionAmount:'Amount Paid',TransactionNetAmount:'Net Amount',TransactionFinalAmount:'Final Amount',TransactionFinalCurrency:'Final Currency',TransactionSettlementDate:'Settlement Date',TransactionRollingReserveAmount:'Rolling Reserve',TransactionRollingReserveDate:'Reserve Date',CustomerName:'Customer Name',CustomerPhone:'Phone',CustomerCountry:'Country',CustomerAddress:'Address',CustomerCity:'City',CustomerZip:'ZIP',CustomerCreditType:'Credit Type',MobilePaymentRequest:'Mobile Payment',FraudAlert:'Fraud Alert',FraudExplnation:'Fraud Explanation'};
        Object.entries(dpoMap).forEach(([k,lbl])=>{
            if(raw[k]!=null&&raw[k]!==''){
                let v=raw[k],cls='';
                if(k==='TransactionAmount'||k==='TransactionNetAmount'){v=Number(v).toFixed(2)+' '+(raw.TransactionCurrency||'');cls='big';}
                if(k==='Result') cls=v==='000'?'ok':'fail';
                if(k==='FraudExplnation') cls=String(v).toLowerCase().includes('low')?'ok':'fail';
                body+=df(lbl,v,cls);
            }
        });
        body+=`</div></div>`;
    }

    // All other fields
    const SKIP=new Set([...SKIP_KEYS,'id','_raw','amount','currency_code','transaction_status','payment_status','status','result','payment_amount','amount_gross','amount_cents','currency','transaction_currency']);
    const remaining=Object.entries(r).filter(([k])=>!SKIP.has(k));
    if(remaining.length){
        body+=`<div class="det-section"><div class="det-section-title"><i class="bi bi-list-ul"></i> All Fields</div><div class="det-grid">`;
        remaining.forEach(([k,v])=>{
            const lbl=labels[k]||k.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
            let cls='';
            if(k==='created_at'||k==='updated_at'||k.toLowerCase().includes('date')){v=fmtDate(v);cls='muted';}
            else if(k.includes('url')||k.includes('Url')) cls='mono';
            else if(['reference_number','pf_payment_id','transaction_id','gateway_transaction_id'].includes(k)) cls='mono';
            body+=df(lbl,v,cls);
        });
        body+=`</div></div>`;
    }

    // JSON fields
    SKIP_KEYS.filter(k=>!['_raw','order','vendor_wallet'].includes(k)&&r[k]!=null&&typeof r[k]==='object'&&Object.keys(r[k]).length).forEach(k=>{
        body+=`<div class="det-section"><div class="det-section-title"><i class="bi bi-braces"></i> ${k.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase())}</div><div class="det-grid cols1"><div class="det-field"><div class="val mono" style="font-size:11px;white-space:pre-wrap">${JSON.stringify(r[k],null,2)}</div></div></div></div>`;
    });

    document.getElementById('det-modal-body').innerHTML=body||'<p style="color:#9ca3af;text-align:center;padding:20px">No data available.</p>';
    document.getElementById('det-modal').style.display='flex';
    document.body.style.overflow='hidden';
}

function closeModal(){
    document.getElementById('det-modal').style.display='none';
    document.body.style.overflow='';
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});
document.addEventListener('DOMContentLoaded',()=>loadTab('pesepay'));
</script>
@endpush
