@extends('admin.layout')
@section('title', 'Currencies')
@section('page-title', 'Currency Management')

@push('styles')
<style>
.cur-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.cur-header h2{margin:0;font-size:20px;font-weight:700;}
.btn-add{background:var(--theme-color,#f8642a);color:#fff;border:none;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:filter .15s;}
.btn-add:hover{filter:brightness(1.1);}

/* Table card */
.tcard{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;}
.tcard table{width:100%;border-collapse:collapse;font-size:13px;}
.tcard th{background:#f9fafb;padding:11px 14px;text-align:left;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;border-bottom:1px solid #e5e7eb;}
.tcard td{padding:11px 14px;border-bottom:1px solid #f3f4f6;vertical-align:middle;color:#374151;}
.tcard tr:last-child td{border-bottom:none;}
.tcard tr:hover td{background:#fafafa;}

/* Status toggle */
.status-pill{display:inline-flex;align-items:center;gap:6px;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;border:none;transition:all .15s;}
.status-pill.on{background:#dcfce7;color:#16a34a;}
.status-pill.off{background:#fee2e2;color:#dc2626;}

/* Action buttons */
.act-btn{background:none;border:1px solid #e5e7eb;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;transition:all .15s;color:#374151;}
.act-btn:hover{background:#f3f4f6;}
.act-btn.del:hover{background:#fee2e2;border-color:#fca5a5;color:#dc2626;}

/* Mono */
.mono{font-family:monospace;font-size:12px;}
.sym{font-size:15px;font-weight:700;color:#374151;}

/* Modal */
.m-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;display:flex;align-items:center;justify-content:center;padding:16px;}
.m-box{background:#fff;border-radius:14px;box-shadow:0 24px 64px rgba(0,0,0,.22);width:100%;max-width:560px;max-height:92vh;overflow-y:auto;}
.m-hd{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:2px solid #f3f4f6;position:sticky;top:0;background:#fff;z-index:1;border-radius:14px 14px 0 0;}
.m-hd h3{margin:0;font-size:16px;font-weight:700;}
.m-close{background:none;border:none;font-size:22px;cursor:pointer;color:#9ca3af;padding:2px 6px;border-radius:6px;}
.m-close:hover{background:#fee2e2;color:#dc2626;}
.m-body{padding:22px 24px;}
.m-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.m-grid.full{grid-template-columns:1fr;}
.fg{display:flex;flex-direction:column;gap:4px;}
.fg label{font-size:11px;font-weight:700;text-transform:uppercase;color:#9ca3af;letter-spacing:.05em;}
.fg input,.fg select{padding:8px 11px;border:1px solid #e5e7eb;border-radius:7px;font-size:13px;outline:none;transition:border .15s;width:100%;}
.fg input:focus,.fg select:focus{border-color:var(--theme-color,#f8642a);box-shadow:0 0 0 3px rgba(248,100,42,.12);}
.fg .hint{font-size:11px;color:#9ca3af;}
.error-msg{font-size:11px;color:#dc2626;font-weight:600;display:none;margin-top:2px;}
.error-msg.show{display:block;}
.fg input.error,.fg select.error{border-color:#dc2626;background:#fef2f2;}

.m-footer{display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid #f3f4f6;}
.btn-save{background:var(--theme-color,#f8642a);color:#fff;border:none;padding:9px 22px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;}
.btn-save:hover{filter:brightness(1.1);}
.btn-cancel{background:#f3f4f6;color:#374151;border:none;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;}
.btn-cancel:hover{background:#e5e7eb;}

.empty-state{text-align:center;padding:60px 20px;color:#9ca3af;}
.empty-state i{font-size:44px;display:block;margin-bottom:10px;}
</style>
@endpush

@section('content')
<div class="container-fluid p-3">

    <div class="cur-header">
        <h2><i class="bi bi-currency-exchange" style="color:var(--theme-color,#f8642a)"></i> &nbsp;Currencies</h2>
        @can('currency.create')
        <button class="btn-add" onclick="openModal()">
            <i class="bi bi-plus-circle"></i> Add Currency
        </button>
        @endcan
    </div>

    <div class="tcard">
        @if($currencies->isEmpty())
            <div class="empty-state">
                <i class="bi bi-currency-exchange"></i>
                No currencies found. Add one to get started.
            </div>
        @else
        <div style="overflow-x:auto">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Symbol</th>
                    <th>Exchange Rate</th>
                    <th>Decimals</th>
                    <th>Symbol Position</th>
                    <th>Separators</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="currency-tbody">
            @foreach($currencies as $c)
            <tr id="cur-row-{{ $c->id }}">
                <td class="mono" style="color:#9ca3af">{{ $c->id }}</td>
                <td><strong class="mono" style="font-size:14px">{{ $c->code }}</strong></td>
                <td><span class="sym">{{ $c->symbol }}</span></td>
                <td><strong>{{ number_format($c->exchange_rate, 4) }}</strong></td>
                <td class="mono">{{ $c->no_of_decimal }}</td>
                <td>
                    @if($c->symbol_position === 'before_price')
                        <span style="font-size:12px;color:#6b7280"><i class="bi bi-arrow-left"></i> Before</span>
                    @else
                        <span style="font-size:12px;color:#6b7280">After <i class="bi bi-arrow-right"></i></span>
                    @endif
                </td>
                <td class="mono" style="font-size:11px;color:#9ca3af">
                    Dec: <strong>{{ $c->decimal_separator === 'period' ? '.' : ($c->decimal_separator === 'comma' ? ',' : ' ') }}</strong>
                    &nbsp;·&nbsp;
                    Thou: <strong>{{ $c->thousands_separator ? ($c->thousands_separator === 'comma' ? ',' : ($c->thousands_separator === 'period' ? '.' : ' ')) : 'none' }}</strong>
                </td>
                <td>
                    @can('currency.edit')
                    <button class="status-pill {{ $c->status ? 'on' : 'off' }}"
                            onclick="toggleStatus({{ $c->id }}, this)"
                            id="status-btn-{{ $c->id }}">
                        <i class="bi bi-{{ $c->status ? 'check-circle-fill' : 'x-circle-fill' }}"></i>
                        {{ $c->status ? 'Active' : 'Inactive' }}
                    </button>
                    @else
                        <span class="status-pill {{ $c->status ? 'on' : 'off' }}" style="cursor:default">
                            {{ $c->status ? 'Active' : 'Inactive' }}
                        </span>
                    @endcan
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        @can('currency.edit')
                        <button class="act-btn" onclick='openModal(@json($c))' title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        @endcan
                        @can('currency.delete')
                        <button class="act-btn del" onclick="deleteCurrency({{ $c->id }}, '{{ $c->code }}')" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endcan
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
</div>

{{-- Modal --}}
<div class="m-overlay" id="cur-modal" style="display:none" onclick="if(event.target===this)closeModal()">
    <div class="m-box">
        <div class="m-hd">
            <h3 id="modal-title"><i class="bi bi-plus-circle"></i> Add Currency</h3>
            <button class="m-close" onclick="closeModal()">×</button>
        </div>
        <div class="m-body">
            <input type="hidden" id="cur-id">
            <div class="m-grid">
                <div class="fg">
                    <label>Currency Code <span style="color:#dc2626">*</span></label>
                    <input id="cur-code" placeholder="e.g. USD, ZMW, ZAR" maxlength="10">
                    <span class="hint">ISO 4217 code (e.g. USD)</span>
                    <span class="error-msg" id="err-code"></span>
                </div>
                <div class="fg">
                    <label>Symbol <span style="color:#dc2626">*</span></label>
                    <input id="cur-symbol" placeholder="e.g. $, K, R" maxlength="10">
                    <span class="error-msg" id="err-symbol"></span>
                </div>
                <div class="fg">
                    <label>Exchange Rate <span style="color:#dc2626">*</span></label>
                    <input id="cur-rate" type="number" step="0.0001" min="0" placeholder="1.0000">
                    <span class="hint">Rate relative to base currency</span>
                    <span class="error-msg" id="err-rate"></span>
                </div>
                <div class="fg">
                    <label>Decimal Places <span style="color:#dc2626">*</span></label>
                    <input id="cur-decimals" type="number" min="0" max="8" value="2">
                    <span class="error-msg" id="err-decimals"></span>
                </div>
                <div class="fg">
                    <label>Symbol Position <span style="color:#dc2626">*</span></label>
                    <select id="cur-position">
                        <option value="before_price">Before Price (e.g. $100)</option>
                        <option value="after_price">After Price (e.g. 100 K)</option>
                    </select>
                    <span class="error-msg" id="err-position"></span>
                </div>
                <div class="fg">
                    <label>Status <span style="color:#dc2626">*</span></label>
                    <select id="cur-status">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <span class="error-msg" id="err-status"></span>
                </div>
                <div class="fg">
                    <label>Decimal Separator <span style="color:#dc2626">*</span></label>
                    <select id="cur-decimal-sep">
                        <option value="period">Period (.)</option>
                        <option value="comma">Comma (,)</option>
                        <option value="space">Space ( )</option>
                    </select>
                    <span class="hint">Character used as decimal point</span>
                    <span class="error-msg" id="err-decimal-sep"></span>
                </div>
                <div class="fg">
                    <label>Thousands Separator</label>
                    <select id="cur-thou-sep">
                        <option value="">None</option>
                        <option value="comma">Comma (,)</option>
                        <option value="period">Period (.)</option>
                        <option value="space">Space ( )</option>
                    </select>
                    <span class="hint">Separator for thousands</span>
                    <span class="error-msg" id="err-thou-sep"></span>
                </div>
            </div>
        </div>
        <div class="m-footer">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-save" onclick="saveCurrency()">
                <i class="bi bi-check-circle"></i> <span id="save-btn-text">Save Currency</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

function openModal(c) {
    const isEdit = c && c.id;
    document.getElementById('modal-title').innerHTML = isEdit
        ? `<i class="bi bi-pencil"></i> Edit Currency — <span class="mono">${c.code}</span>`
        : `<i class="bi bi-plus-circle"></i> Add Currency`;
    document.getElementById('save-btn-text').textContent = isEdit ? 'Update Currency' : 'Save Currency';

    document.getElementById('cur-id').value        = c?.id || '';
    document.getElementById('cur-code').value       = c?.code || '';
    document.getElementById('cur-symbol').value     = c?.symbol || '';
    document.getElementById('cur-rate').value       = c?.exchange_rate || '';
    document.getElementById('cur-decimals').value   = c?.no_of_decimal ?? 2;
    document.getElementById('cur-position').value   = c?.symbol_position || 'before_price';
    document.getElementById('cur-status').value     = c?.status ?? 1;
    document.getElementById('cur-decimal-sep').value  = c?.decimal_separator || 'period';
    document.getElementById('cur-thou-sep').value     = c?.thousands_separator || '';

    // Clear all errors
    clearErrors();

    document.getElementById('cur-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(()=>document.getElementById('cur-code').focus(), 80);
}

function closeModal() {
    document.getElementById('cur-modal').style.display = 'none';
    document.body.style.overflow = '';
    clearErrors();
}

function clearErrors() {
    document.querySelectorAll('.error-msg').forEach(e => {
        e.textContent = '';
        e.classList.remove('show');
    });
    document.querySelectorAll('.error').forEach(e => e.classList.remove('error'));
}

function showErrors(errors) {
    clearErrors();
    const fieldMap = {
        'code': 'cur-code',
        'symbol': 'cur-symbol',
        'exchange_rate': 'cur-rate',
        'no_of_decimal': 'cur-decimals',
        'symbol_position': 'cur-position',
        'status': 'cur-status',
        'decimal_separator': 'cur-decimal-sep',
        'thousands_separator': 'cur-thou-sep',
    };

    Object.entries(errors).forEach(([field, messages]) => {
        const inputId = fieldMap[field];
        const errorId = 'err-' + inputId.replace('cur-', '');
        const inputEl = document.getElementById(inputId);
        const errorEl = document.getElementById(errorId);

        if (inputEl) inputEl.classList.add('error');
        if (errorEl) {
            errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
            errorEl.classList.add('show');
        }
    });
}

async function saveCurrency() {
    const id   = document.getElementById('cur-id').value;
    const isEdit = !!id;

    clearErrors();

    const payload = {
        code:                document.getElementById('cur-code').value.trim().toUpperCase(),
        symbol:              document.getElementById('cur-symbol').value.trim(),
        exchange_rate:       document.getElementById('cur-rate').value,
        no_of_decimal:       document.getElementById('cur-decimals').value,
        symbol_position:     document.getElementById('cur-position').value,
        status:              document.getElementById('cur-status').value,
        decimal_separator:   document.getElementById('cur-decimal-sep').value,
        thousands_separator: document.getElementById('cur-thou-sep').value,
    };

    if (!payload.code || !payload.symbol || !payload.exchange_rate) {
        showError('Validation', 'Code, Symbol and Exchange Rate are required.');
        return;
    }

    const url    = isEdit ? `/admin/currencies/${id}` : '/admin/currencies';
    const method = isEdit ? 'PUT' : 'POST';

    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (!res.ok) {
            // Show validation errors below inputs
            if (data.errors) {
                showErrors(data.errors);
                return;
            }
            // Generic error message
            const msgs = data.message || 'An error occurred';
            showError('Failed', msgs);
            return;
        }

        closeModal();
        showSuccess('Done!', data.message);
        setTimeout(()=>window.location.reload(), 1200);
    } catch(e) {
        showError('Network Error', e.message);
    }
}

async function deleteCurrency(id, code) {
    const result = await confirmDelete(`Delete ${code}?`, 'This will permanently remove this currency.');
    if (!result.isConfirmed) return;

    try {
        const res  = await fetch(`/admin/currencies/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
        });
        const data = await res.json();

        if (!res.ok) { showError('Failed', data.message||'Error'); return; }

        showSuccess('Deleted!', data.message);
        const row = document.getElementById('cur-row-' + id);
        if (row) row.remove();
    } catch(e) {
        showError('Network Error', e.message);
    }
}

async function toggleStatus(id, btn) {
    try {
        const res  = await fetch(`/admin/currencies/${id}/toggle-status`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
        });
        const data = await res.json();
        if (!res.ok) { showError('Failed', data.message); return; }

        const isOn = data.status == 1;
        btn.className = `status-pill ${isOn?'on':'off'}`;
        btn.innerHTML = `<i class="bi bi-${isOn?'check-circle-fill':'x-circle-fill'}"></i> ${isOn?'Active':'Inactive'}`;
        showToast('success', data.message);
    } catch(e) {
        showError('Network Error', e.message);
    }
}

document.addEventListener('keydown', e => { if(e.key==='Escape') closeModal(); });
</script>
@endpush

