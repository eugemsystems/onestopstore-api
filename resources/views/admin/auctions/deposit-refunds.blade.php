@extends('admin.layout')
@section('title', 'Auction Deposit Refunds')

@push('styles')
<style>
    .refund-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: .75rem; }
    .refund-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
    .badge-pending  { background:#fffbeb; color:#d97706; border:1px solid #fde68a; }
    .badge-approved { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
    .badge-rejected { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
    .badge-wallet   { background:#f5f3ff; color:#7e22ce; border:1px solid #ddd6fe; }
    .badge-bank     { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
    .banking-box    { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:.6rem .9rem; font-size:.8rem; }
    .wallet-panel   { background:#f5f3ff; border:1px solid #ddd6fe; border-radius:10px; padding:.75rem 1rem; font-size:.82rem; }
    .tx-row         { display:flex; justify-content:space-between; align-items:center; padding:.3rem 0; border-bottom:1px solid #ede9fe; }
    .tx-row:last-child { border-bottom:none; }
    .tx-credit      { color:#059669; font-weight:700; }
    .tx-debit       { color:#dc2626; font-weight:700; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-arrow-counterclockwise me-2" style="color:#7e22ce"></i>Auction Deposit Refunds</h4>
        <p class="text-muted mb-0 small">Review and process customer deposit refund requests</p>
    </div>
    <a href="{{ route('admin.auctions.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Auctions
    </a>
</div>

{{-- Filter bar --}}
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="status" class="form-select form-select-sm" style="width:140px" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach(['pending','approved','rejected'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="refund_method" class="form-select form-select-sm" style="width:130px" onchange="this.form.submit()">
                <option value="">All Methods</option>
                <option value="wallet" {{ request('refund_method') == 'wallet' ? 'selected' : '' }}>💳 Wallet</option>
                <option value="bank"   {{ request('refund_method') == 'bank'   ? 'selected' : '' }}>🏦 Bank</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="width:200px" placeholder="Customer or auction…" value="{{ request('search') }}">
            <button class="btn btn-sm btn-primary">Filter</button>
            @if(request()->hasAny(['status','search','refund_method']))
                <a href="{{ route('admin.auctions.deposit-refunds') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            @endif
            <span class="ms-auto text-muted small">{{ $refunds->total() }} request(s)</span>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show py-2"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show py-2">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Wallet verification panel (flashed after credit-wallet) --}}
@if(session('wallet_verification'))
    @php $wv = session('wallet_verification'); @endphp
    <div class="card mb-3 border-0" style="background:#f0fdf4;border:1px solid #a7f3d0!important;border-radius:12px;">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-wallet2 text-success fs-5"></i>
                <strong class="text-success">Wallet Credited — {{ $wv['user_name'] }}</strong>
                <span class="ms-auto fw-bold text-success fs-5">${{ number_format($wv['new_balance'], 2) }} balance</span>
            </div>
            <div class="small text-muted mb-2">Last transactions (most recent first):</div>
            @foreach($wv['transactions'] as $tx)
                <div class="tx-row">
                    <span>{{ $tx['details'] }}</span>
                    <span class="{{ $tx['type'] === 'credit' ? 'tx-credit' : 'tx-debit' }}">
                        {{ $tx['type'] === 'credit' ? '+' : '−' }}${{ number_format($tx['amount'], 2) }}
                    </span>
                    <span class="text-muted" style="font-size:.75rem">{{ $tx['created_at'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

@forelse($refunds as $refund)
    <div class="refund-card" id="refund-{{ $refund->id }}">
        <div class="row g-3 align-items-start">

            {{-- Left: customer + auction + method badge --}}
            <div class="col-md-4">
                <div class="fw-bold">{{ $refund->user->name ?? '—' }}</div>
                <div class="text-muted small">{{ $refund->user->email ?? '' }}</div>
                <div class="mt-1 small text-secondary"><i class="bi bi-hammer me-1"></i>{{ $refund->auction->title ?? "Auction #{$refund->auction_item_id}" }}</div>
                <div class="mt-1 small text-muted">Requested: {{ $refund->created_at->format('d M Y H:i') }}</div>
                @if($refund->processed_at)
                    <div class="small text-muted">Processed: {{ $refund->processed_at->format('d M Y H:i') }}</div>
                @endif
                <div class="mt-2 d-flex flex-wrap gap-1">
                    @if(($refund->refund_method ?? 'bank') === 'wallet')
                        <span class="badge badge-wallet px-2 py-1" style="font-size:.72rem;border-radius:20px;border-style:solid;border-width:1px">💳 Wallet Refund</span>
                    @else
                        <span class="badge badge-bank px-2 py-1" style="font-size:.72rem;border-radius:20px;border-style:solid;border-width:1px">🏦 Bank Transfer</span>
                    @endif
                    @if($refund->wallet_credited_at)
                        <span class="badge bg-success px-2 py-1" style="font-size:.68rem;border-radius:20px">
                            ✅ Credited {{ $refund->wallet_credited_at->format('d M H:i') }}
                        </span>
                    @endif
                </div>
                {{-- Wallet balance --}}
                @php $userWallet = $refund->user?->wallet; @endphp
                <div class="mt-2 d-inline-flex align-items-center gap-1 px-2 py-1 rounded"
                     style="background:#f5f3ff;border:1px solid #ddd6fe;font-size:.78rem;">
                    <i class="bi bi-wallet2" style="color:#7e22ce"></i>
                    <span style="color:#7e22ce;font-weight:700;">
                        Wallet: ${{ $userWallet ? number_format($userWallet->balance, 2) : '0.00' }}
                    </span>
                </div>
            </div>

            {{-- Middle: amounts + banking/wallet info --}}
            <div class="col-md-4">
                <div class="small mb-1"><span class="text-muted">Deposit Paid:</span> <strong>${{ number_format($refund->deposit_amount, 2) }}</strong></div>
                @if($refund->winner_bid_amount > 0)
                    <div class="small mb-1"><span class="text-muted">Winning Bid:</span> <strong>${{ number_format($refund->winner_bid_amount, 2) }}</strong></div>
                @endif
                <div class="small mb-2">
                    <span class="text-muted">Refund Amount:</span>
                    <strong style="color:#7e22ce;font-size:1rem">${{ number_format($refund->refund_amount, 2) }}</strong>
                </div>

                @if(($refund->refund_method ?? 'bank') === 'bank' && !$refund->wallet_credited_at)
                    @php $account = $refund->user?->payment_account; @endphp
                    @if($account)
                        <div class="banking-box">
                            <div class="fw-bold mb-1 text-success" style="font-size:.75rem"><i class="bi bi-bank me-1"></i>Banking Details</div>
                            @if($account->bank_name)    <div>Bank: {{ $account->bank_name }}</div>    @endif
                            @if($account->account_name) <div>Name: {{ $account->account_name }}</div> @endif
                            @if($account->account_number) <div>Account: {{ $account->account_number }}</div> @endif
                            @if($account->branch_code)  <div>Branch: {{ $account->branch_code }}</div>  @endif
                        </div>
                    @else
                        <div class="banking-box text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>No banking details on file</div>
                    @endif
                @else
                    <div class="wallet-panel">
                        <div class="fw-bold mb-1" style="color:#7e22ce;font-size:.75rem"><i class="bi bi-wallet me-1"></i>Wallet Refund</div>
                        @if($refund->wallet_credited_at)
                            <div class="text-success small">✅ Credited to wallet on {{ $refund->wallet_credited_at->format('d M Y H:i') }}</div>
                        @else
                            <div class="text-muted small">Approve to credit the customer's in-app wallet instantly.</div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Right: status + actions --}}
            <div class="col-md-4">
                <div class="mb-2">
                    <span class="badge badge-{{ $refund->status }} px-3 py-1" style="font-size:.8rem;border-radius:20px;border-style:solid;border-width:1px">
                        {{ ucfirst($refund->status) }}
                    </span>
                </div>
                @if($refund->reason)
                    <div class="small text-secondary mb-2"><em>"{{ Str::limit($refund->reason, 80) }}"</em></div>
                @endif
                @if($refund->admin_notes)
                    <div class="small text-muted bg-light rounded p-1 mb-2"><i class="bi bi-chat-square-text me-1"></i>{{ $refund->admin_notes }}</div>
                @endif

                {{-- Approve / Reject form (pending only) --}}
                @if($refund->status === 'pending')
                    <form method="POST" action="{{ route('admin.auctions.deposit-refunds.update', $refund) }}" class="mb-2">
                        @csrf
                        <div class="mb-2">
                            <textarea name="admin_notes" class="form-control form-control-sm" rows="2" placeholder="Admin notes (optional)…"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            @if(($refund->refund_method ?? 'bank') === 'wallet')
                                <button type="submit" name="status" value="approved" class="btn btn-sm btn-success flex-fill"
                                    data-swal-confirm="Approve & auto-credit ${{ number_format($refund->refund_amount, 2) }} to {{ addslashes($refund->user->name ?? 'the customer') }}'s wallet?">
                                    <i class="bi bi-wallet me-1"></i>Approve & Credit Wallet
                                </button>
                            @else
                                <button type="submit" name="status" value="approved" class="btn btn-sm btn-success flex-fill"
                                    data-swal-confirm="Approve this refund of ${{ number_format($refund->refund_amount, 2) }}? Remember to process the bank transfer manually.">
                                    <i class="bi bi-check-circle me-1"></i>Approve
                                </button>
                            @endif
                            <button type="submit" name="status" value="rejected" class="btn btn-sm btn-danger flex-fill"
                                data-swal-confirm="Reject this refund request?">
                                <i class="bi bi-x-circle me-1"></i>Reject
                            </button>
                        </div>
                    </form>
                @endif

                {{-- Transfer to Wallet button — visible for ALL non-credited refunds (pending or approved bank) --}}
                @if(!$refund->wallet_credited_at)
                    <form method="POST" action="{{ route('admin.auctions.deposit-refunds.credit-wallet', $refund) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-purple w-100"
                            style="border-color:#7e22ce;color:#7e22ce;font-weight:700;"
                            data-swal-confirm="Transfer ${{ number_format($refund->refund_amount, 2) }} directly to {{ addslashes($refund->user->name ?? 'the customer') }}'s wallet? This will auto-approve the refund and cannot be undone.">
                            <i class="bi bi-wallet2 me-1"></i>Transfer to Wallet
                        </button>
                    </form>
                @else
                    <div class="text-center small text-success fw-bold mt-1">
                        <i class="bi bi-check-circle-fill me-1"></i>Wallet already credited
                    </div>
                @endif
            </div>

        </div>
    </div>
@empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3"></i>
        <p class="mt-2">No deposit refund requests found.</p>
    </div>
@endforelse

{{ $refunds->withQueryString()->links() }}
@endsection
