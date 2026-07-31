@extends('admin.layout')

@section('title', 'Voucher Details - ' . $voucher->code)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.vouchers.index') }}" class="btn btn-link text-decoration-none ps-0">
                        <i class="bi bi-arrow-left"></i> Back to Vouchers
                    </a>
                    <h2 class="mb-0"><i class="bi bi-gift"></i> Voucher Details</h2>
                    <p class="text-muted mb-0">Complete voucher information and payment history</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Voucher Information -->
        <div class="col-md-8">
            <!-- Voucher Code Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Voucher Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-4 text-center">
                            <h3 class="voucher-code-display">{{ $voucher->code }}</h3>
                            <p class="text-muted">Voucher Code</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label>Amount</label>
                                <div class="info-value">
                                    <strong class="text-primary" style="font-size: 1.5rem;">
                                        {{ $voucher->currency_code }} {{ number_format($voucher->amount, 2) }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label>Status</label>
                                <div class="info-value">
                                    @if($voucher->status === 'active')
                                        <span class="badge bg-success" style="font-size: 1rem;">
                                            <i class="bi bi-check-circle"></i> Active
                                        </span>
                                    @elseif($voucher->status === 'redeemed')
                                        <span class="badge bg-info" style="font-size: 1rem;">
                                            <i class="bi bi-check2-square"></i> Redeemed
                                        </span>
                                    @else
                                        <span class="badge bg-secondary" style="font-size: 1rem;">
                                            {{ ucfirst($voucher->status) }}
                                        </span>
                                    @endif

                                    @if($voucher->expires_at && $voucher->expires_at < now() && $voucher->status !== 'redeemed')
                                        <span class="badge bg-danger" style="font-size: 1rem;">
                                            <i class="bi bi-clock-history"></i> Expired
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label><i class="bi bi-calendar"></i> Created</label>
                                <div class="info-value">{{ $voucher->created_at->format('F d, Y h:i A') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label><i class="bi bi-calendar-event"></i> Expires</label>
                                <div class="info-value">
                                    @if($voucher->expires_at)
                                        {{ $voucher->expires_at->format('F d, Y') }}
                                    @else
                                        <span class="text-muted">Never expires</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Information -->
            @if($voucher->order)
            <div class="card mb-4">
                <div class="card-header bg-success">
                    <h5 class="mb-0"><i class="bi bi-cart-check"></i> Purchase Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-group">
                                <label>Order Number</label>
                                <div class="info-value">
                                    <a href="{{ route('admin.orders.show', $voucher->order->order_number) }}"
                                       class="text-decoration-none">
                                        #{{ $voucher->order->order_number }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-group">
                                <label>Order Total</label>
                                <div class="info-value">
                                    {{ $voucher->order->currency_symbol ?? '$' }}
                                    {{ number_format($voucher->order->total, 2) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-group">
                                <label>Order Date</label>
                                <div class="info-value">
                                    {{ $voucher->order->created_at->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($voucher->order->consumer)
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label>Customer Name</label>
                                <div class="info-value">{{ $voucher->order->consumer->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label>Customer Email</label>
                                <div class="info-value">{{ $voucher->order->consumer->email }}</div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Payment Details -->
            @if($paymentDetails)
            <div class="card mb-4">
                <div class="card-header bg-success">
                    <h5 class="mb-0"><i class="bi bi-credit-card-2-front"></i> Payment Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-group">
                                <label>Payment Method</label>
                                <div class="info-value">
                                    <span class="badge bg-secondary">
                                        {{ strtoupper($paymentDetails['method']) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-group">
                                <label>Payment Status</label>
                                <div class="info-value">
                                    @if(strtolower($paymentDetails['status']) === 'completed' || strtolower($paymentDetails['status']) === 'paid')
                                        <span class="badge bg-success">{{ $paymentDetails['status'] }}</span>
                                    @elseif(strtolower($paymentDetails['status']) === 'pending')
                                        <span class="badge bg-warning">{{ $paymentDetails['status'] }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ $paymentDetails['status'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-group">
                                <label>Order Status</label>
                                <div class="info-value">
                                    <span class="badge bg-info">{{ $paymentDetails['order_status'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group">
                                <label>Currency</label>
                                <div class="info-value">
                                    {{ $paymentDetails['currency'] }} ({{ $paymentDetails['currency_symbol'] }})
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <label>Exchange Rate</label>
                                <div class="info-value">{{ number_format($paymentDetails['exchange_rate'], 4) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Transaction History -->
            @if($transactions->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-success" >
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Transaction History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                <tr>
                                    <td>
                                        <small>{{ $transaction->created_at->format('M d, Y H:i') }}</small>
                                    </td>
                                    <td>
                                        @if($transaction->type === 'credit')
                                            <span class="badge bg-success">Credit</span>
                                        @else
                                            <span class="badge bg-danger">Debit</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format(abs($transaction->amount), 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : 'warning' }}">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $transaction->detail }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Redemption Info -->
            @if($voucher->status === 'redeemed')
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-check-circle"></i> Redemption Info</h5>
                </div>
                <div class="card-body">
                    @if($voucher->redeemedBy)
                    <div class="info-group">
                        <label>Redeemed By</label>
                        <div class="info-value">{{ $voucher->redeemedBy->name }}</div>
                        <small class="text-muted">{{ $voucher->redeemedBy->email }}</small>
                    </div>
                    @endif
                    <div class="info-group mt-3">
                        <label>Redeemed Date</label>
                        <div class="info-value">{{ $voucher->redeemed_at->format('F d, Y h:i A') }}</div>
                    </div>
                    @if($redemptionTransaction)
                    <div class="info-group mt-3">
                        <label>Wallet Credit</label>
                        <div class="info-value">
                            <span class="badge bg-success">
                                +{{ number_format($redemptionTransaction->amount, 2) }}
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Product Info -->
            @if($voucher->product)
            <div class="card mb-4">
                <div class="card-header bg-success">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> Product</h5>
                </div>
                <div class="card-body">
                    <div class="info-group">
                        <label>Product Name</label>
                        <div class="info-value">{{ $voucher->product->name }}</div>
                    </div>
                    @if($voucher->product->is_gift_card)
                    <div class="mt-2">
                        <span class="badge bg-primary">Gift Card Product</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Notes -->
            @if($voucher->notes)
            <div class="card">
                <div class="card-header bg-success">
                    <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Notes</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $voucher->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.voucher-code-display {
    font-family: 'Courier New', monospace;
    font-size: 2rem;
    font-weight: 700;
    letter-spacing: 4px;
    background: linear-gradient(135deg, #062a6a 0%, #0d47a1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    padding: 1rem;
    border: 3px dashed #e9ecef;
    border-radius: 8px;
}

.info-group {
    margin-bottom: 1rem;
}

.info-group label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
    display: block;
}

.info-group .info-value {
    font-size: 1rem;
    color: #2c3e50;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    background: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
}
</style>
@endsection

