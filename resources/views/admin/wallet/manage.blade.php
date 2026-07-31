@extends('admin.layout')

@section('title', 'Manage Wallet - ' . $user->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="bi bi-wallet2"></i> Manage Wallet - {{ $user->name }}</h2>
                    <small class="text-muted">{{ $user->email }}</small>
                </div>
                <a href="{{ route('admin.wallet.list') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to All Wallets
                </a>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Wallet Statistics Row -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-primary">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-1">Current Balance</h6>
                                    <h3 class="mb-0">{{ getSettings()?->general?->default_currency?->symbol ?? '$' }}{{ number_format($wallet->balance, 2) }}</h3>
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
                                    <i class="bi bi-arrow-up-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-1">Total Credits</h6>
                                    <h3 class="mb-0">{{ getSettings()?->general?->default_currency?->symbol ?? '$' }}{{ number_format($stats['total_credits'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-danger">
                                    <i class="bi bi-arrow-down-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-1">Total Debits</h6>
                                    <h3 class="mb-0">{{ getSettings()?->general?->default_currency?->symbol ?? '$' }}{{ number_format($stats['total_debits'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stat-icon bg-info">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-1">Transactions</h6>
                                    <h3 class="mb-0">{{ number_format($stats['transaction_count']) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wallet Actions Card -->
            @can('wallet.edit')
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Wallet Actions</h5>
                </div>
                <div class="card-body">
                    <form id="walletActionForm">
                        @csrf
                        <input type="hidden" id="consumerId" value="{{ $user->id }}">

                        <div class="row">
                            <div class="col-md-5">
                                <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number"
                                       id="amount"
                                       class="form-control"
                                       placeholder="Enter amount"
                                       step="0.01"
                                       min="0.01"
                                       required>
                            </div>
                            <div class="col-md-5">
                                <label for="remark" class="form-label">Remark (Optional)</label>
                                <input type="text"
                                       id="remark"
                                       class="form-control"
                                       placeholder="Enter remark"
                                       maxlength="500">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                            class="btn btn-success flex-fill"
                                            onclick="creditWallet()">
                                        <i class="bi bi-plus-circle"></i> Add
                                    </button>
                                    <button type="button"
                                            class="btn btn-danger flex-fill"
                                            onclick="debitWallet()">
                                        <i class="bi bi-dash-circle"></i> Deduct
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endcan

            <!-- Transactions Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Transaction History ({{ $transactions->total() }} total)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Balance After</th>
                                    <th>Detail</th>
                                    <th>Order</th>
                                    <th>Added By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td>
                                        <div>{{ $transaction->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $transaction->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        @if($transaction->type === 'credit')
                                            <span class="badge bg-success">
                                                <i class="bi bi-arrow-up"></i> Credit
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="bi bi-arrow-down"></i> Debit
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="{{ $transaction->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->type === 'credit' ? '+' : '-' }}{{ getSettings()?->general?->default_currency?->symbol ?? '$' }}{{ number_format($transaction->amount, 2) }}
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            {{ getSettings()?->general?->default_currency?->symbol ?? '$' }}{{ number_format($wallet->balance, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $transaction->detail ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @if($transaction->order_id && $transaction->order)
                                            <a href="{{ route('admin.orders.show', $transaction->order_id) }}"
                                               class="badge bg-primary text-decoration-none"
                                               target="_blank">
                                                {{ $transaction->order->order_number }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($transaction->createdBy)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-2">
                                                    {{ substr($transaction->createdBy->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <div class="small">{{ $transaction->createdBy->name }}</div>
                                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $transaction->createdBy->email }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted small">System</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                        <p class="text-muted mt-2">No transactions yet</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($transactions->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $transactions->links() }}
                    </div>
                    @endif
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

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

.avatar-xs {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.875rem;
    flex-shrink: 0;
}
</style>

@push('scripts')
<script>
async function creditWallet() {
    const consumerId = document.getElementById('consumerId').value;
    const amount = document.getElementById('amount').value;
    const remark = document.getElementById('remark').value;

    if (!amount || amount <= 0) {
        showWarning('Warning', 'Please enter a valid amount');
        return;
    }

    const _r = await Swal.fire({ title: 'Are you sure?', text: `Add $${amount} to this wallet?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (_r.isConfirmed) {
        fetch('{{ route('admin.wallet.credit') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                consumer_id: consumerId,
                balance: parseFloat(amount),
                remark: remark
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showSuccess('Success', data.message);
                window.location.reload();
            } else {
                showError('Error', data.message || 'Failed to credit wallet');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error', 'An error occurred');
        });
    }
}

async function debitWallet() {
    const consumerId = document.getElementById('consumerId').value;
    const amount = document.getElementById('amount').value;
    const remark = document.getElementById('remark').value;

    if (!amount || amount <= 0) {
        showWarning('Warning', 'Please enter a valid amount');
        return;
    }

    const currentBalance = {{ $wallet->balance }};
    if (parseFloat(amount) > currentBalance) {
        showWarning('Warning', 'Insufficient wallet balance');
        return;
    }

    const _r = await Swal.fire({ title: 'Are you sure?', text: `Deduct $${amount} from this wallet?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (_r.isConfirmed) {
        fetch('{{ route('admin.wallet.debit') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                consumer_id: consumerId,
                balance: parseFloat(amount),
                remark: remark
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showSuccess('Success', data.message);
                window.location.reload();
            } else {
                showError('Error', data.message || 'Failed to debit wallet');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error', 'An error occurred');
        });
    }
}
</script>
@endpush
@endsection

