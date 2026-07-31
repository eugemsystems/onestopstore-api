@extends('admin.layout')

@section('title', 'All Wallets - Admin Panel')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-wallet2"></i> All Wallets</h2>
                <a href="{{ route('admin.wallet.index') }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Manage Individual Wallet
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-1">Total Wallets</h6>
                                    <h3 class="mb-0">{{ number_format($statistics['total_wallets']) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-success">
                                    <i class="bi bi-cash-stack"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-1">Wallet Balance</h6>
                                    <h3 class="mb-0">${{ number_format($statistics['total_balance'], 2) }}</h3>
                                    <small class="text-muted">Regular funds only</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-warning">
                                    <i class="bi bi-gift"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-1">Gift Card Balance</h6>
                                    <h3 class="mb-0">${{ number_format($statistics['total_gift_card_balance'], 2) }}</h3>
                                    <small class="text-muted">Non-cashable funds</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-0 shadow-sm border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon" style="background: linear-gradient(135deg,#062a6a,#667eea);">
                                    <i class="bi bi-piggy-bank"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-1">Combined Total</h6>
                                    <h3 class="mb-0 text-primary">${{ number_format($statistics['total_combined_balance'], 2) }}</h3>
                                    <small class="text-muted">Wallet + Gift Cards</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Statistics -->
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-muted">Balance Range</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Highest:</span>
                                <strong class="text-success">{{ getSettings()?->general?->default_currency?->symbol ?? '$' }}{{ number_format($statistics['highest_balance'], 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Lowest:</span>
                                <strong class="text-danger">{{ getSettings()?->general?->default_currency?->symbol ?? '$' }}{{ number_format($statistics['lowest_balance'], 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Zero Balance:</span>
                                <strong>{{ number_format($statistics['zero_balance_wallets']) }} wallets</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8 col-md-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-muted">Last 30 Days Activity</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center p-3 rounded bg-light">
                                        <h5 class="mb-1">{{ number_format($statistics['recent_transactions']) }}</h5>
                                        <small class="text-muted">Transactions</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 rounded bg-light">
                                        <h5 class="mb-1 text-success">+{{ getSettings()?->general?->default_currency?->symbol ?? '$' }}{{ number_format($statistics['recent_credits'], 2) }}</h5>
                                        <small class="text-muted">Credits</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 rounded bg-light">
                                        <h5 class="mb-1 text-danger">-{{ getSettings()?->general?->default_currency?->symbol ?? '$' }}{{ number_format($statistics['recent_debits'], 2) }}</h5>
                                        <small class="text-muted">Debits</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filter Wallets</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.wallet.list') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search Customer</label>
                                <input type="text"
                                       class="form-control"
                                       id="search"
                                       name="search"
                                       value="{{ request('search') }}"
                                       placeholder="Name or email...">
                            </div>
                            <div class="col-md-2">
                                <label for="min_balance" class="form-label">Min Balance</label>
                                <input type="number"
                                       class="form-control"
                                       id="min_balance"
                                       name="min_balance"
                                       value="{{ request('min_balance') }}"
                                       step="0.01"
                                       placeholder="0.00">
                            </div>
                            <div class="col-md-2">
                                <label for="max_balance" class="form-label">Max Balance</label>
                                <input type="number"
                                       class="form-control"
                                       id="max_balance"
                                       name="max_balance"
                                       value="{{ request('max_balance') }}"
                                       step="0.01"
                                       placeholder="1000.00">
                            </div>
                            <div class="col-md-2">
                                <label for="sort_by" class="form-label">Sort By</label>
                                <select class="form-select" id="sort_by" name="sort_by">
                                    <option value="balance" {{ request('sort_by') == 'balance' ? 'selected' : '' }}>Balance</option>
                                    <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Date Created</option>
                                    <option value="updated_at" {{ request('sort_by') == 'updated_at' ? 'selected' : '' }}>Last Updated</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label for="sort_order" class="form-label">Order</label>
                                <select class="form-select" id="sort_order" name="sort_order">
                                    <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Desc</option>
                                    <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Asc</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Wallets Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Active Wallets ({{ $wallets->total() }} with balance)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Wallet Balance</th>
                                    <th>Gift Cards</th>
                                    <th>Combined Total</th>
                                    <th>Customer Since</th>
                                    <th>Last Activity</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($wallets as $wallet)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2">
                                                {{ substr($wallet->consumer?->name ?? 'Unknown', 0, 1) }}
                                            </div>
                                            <strong>{{ $wallet->consumer?->name ?? 'Unknown User' }}</strong>
                                        </div>
                                    </td>
                                    <td>{{ $wallet->consumer?->email ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge {{ $wallet->balance > 0 ? 'bg-success' : 'bg-secondary' }}">
                                            ${{ number_format($wallet->balance, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($wallet->non_cashable_balance > 0)
                                            <span class="badge bg-warning text-dark">
                                                <i class="bi bi-gift"></i> ${{ number_format($wallet->non_cashable_balance, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php $combined = $wallet->balance + ($wallet->non_cashable_balance ?? 0); @endphp
                                        <span class="badge bg-primary fs-6">
                                            ${{ number_format($combined, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $wallet->consumer?->created_at?->format('M d, Y') ?? 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $wallet->updated_at->diffForHumans() }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($wallet->consumer_id)
                                        <a href="{{ route('admin.wallet.manage', $wallet->consumer_id) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Manage Wallet">
                                            <i class="bi bi-pencil-square"></i> Manage
                                        </a>
                                        @else
                                        <span class="text-muted small">No user</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2">No wallets found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $wallets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1rem;
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}
</style>
@endsection

