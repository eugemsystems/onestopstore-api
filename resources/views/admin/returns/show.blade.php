@extends('admin.layout')

@section('title', 'Return Details - Admin Panel')

@section('content')
<div class="container-fluid px-4">
    <!-- Compact Header with Status Badge -->
    <div class="return-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('admin.returns.index', ['page' => session('returns_list_page', 1)]) }}" class="btn btn-icon btn-light">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h3 class="mb-1">Return #{{ $return->id }}</h3>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <span><i class="bi bi-calendar3"></i> {{ $return->created_at->format('M d, Y H:i') }}</span>
                        <span class="text-muted">•</span>
                        <span><i class="bi bi-receipt"></i> Order #{{ $return->order->order_number ?? $return->order_id }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($return->status === 'approved')
                    <span class="status-badge status-approved">
                        <i class="bi bi-check-circle-fill"></i> Approved
                    </span>
                @elseif($return->status === 'rejected')
                    <span class="status-badge status-rejected">
                        <i class="bi bi-x-circle-fill"></i> Rejected
                    </span>
                @else
                    <span class="status-badge status-pending">
                        <i class="bi bi-clock-fill"></i> Pending
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Return Reason Box - RED BACKGROUND -->
            <div class="reason-alert mb-4">
                <div class="reason-header">
                    <i class="bi bi-exclamation-triangle-fill"></i> Return Reason
                </div>
                <div class="reason-content">
                    <div class="reason-main">{{ $return->return_reason }}</div>
                    @if($return->sub_reason)
                        <div class="reason-sub">{{ $return->sub_reason }}</div>
                    @endif
                    @if($return->description)
                        <div class="reason-description">
                            <i class="bi bi-chat-left-text"></i> {{ $return->description }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Compact Info Grid -->
            <div class="info-grid mb-4">
                <div class="info-item">
                    <div class="info-label">Customer</div>
                    <div class="info-value">{{ $return->user->name ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Preferred Outcome</div>
                    <div class="info-value">
                        <span class="badge-sm badge-purple">{{ strtoupper($return->preferred_outcome) }}</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Order ID</div>
                    <div class="info-value">
                        <a href="{{ route('admin.orders.show', $return->order->order_number) }}" class="text-decoration-none fw-bold">
                            #{{ $return->order->order_number }}
                        </a>
                    </div>
                </div>
            </div>

            @if($return->rejection_reason)
            <div class="alert alert-danger d-flex align-items-start gap-2 mb-4">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <div>
                    <strong>Rejection Reason:</strong>
                    <p class="mb-0 mt-1">{{ $return->rejection_reason }}</p>
                </div>
            </div>
            @endif

            <!-- Product & Return Details Combined -->
            @if($return->product)
            <div class="product-return-card mb-4">
                <div class="product-section">
                    <div class="d-flex gap-3 align-items-start">
                        @if($return->product->product_thumbnail)
                            <img src="{{ $return->product->product_thumbnail->image_url }}"
                                 alt="{{ $return->product->name }}"
                                 class="product-image">
                        @else
                            <div class="product-image-placeholder">
                                <i class="bi bi-image"></i>
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <h6 class="mb-2">{{ $return->product->name }}</h6>
                            <div class="product-meta mb-2">
                                <span><strong>SKU:</strong> {{ $return->product->sku }}</span>
                                <span><strong>ID:</strong> {{ $return->product_id }}</span>
                            </div>
                            @php
                                $orderItem = $return->order->products->where('id', $return->product_id)->first();
                                $qty = $orderItem ? $orderItem->pivot->quantity : 1;
                                $price = $orderItem ? $orderItem->pivot->single_price : 0;
                                $subtotal = $orderItem ? $orderItem->pivot->subtotal : 0;
                            @endphp
                            <div class="product-meta">
                                <span><strong>Qty:</strong> {{ $qty }}</span>
                                <span><strong>Price:</strong> {{ $return->order->currency_symbol ?? '$' }}{{ number_format($price, 2) }}</span>
                                <span><strong>Total:</strong> {{ $return->order->currency_symbol ?? '$' }}{{ number_format($subtotal, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="return-section">
                    <div class="return-header">
                        <i class="bi bi-box-arrow-in-left"></i> Return Conditions
                    </div>
                    <div class="return-conditions">
                        @if($return->is_product_not_used)
                            <span class="condition-badge"><i class="bi bi-check-circle-fill"></i> Product Not Used</span>
                        @endif
                        @if($return->in_original_packaging)
                            <span class="condition-badge"><i class="bi bi-check-circle-fill"></i> Original Packaging</span>
                        @endif
                        @if($return->include_all_accessories)
                            <span class="condition-badge"><i class="bi bi-check-circle-fill"></i> All Accessories Included</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif


            <!-- Order Details Accordion -->
            @if($return->order)
            <div class="accordion mb-4" id="orderInfo">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orderDetails">
                            <i class="bi bi-receipt me-2"></i> Order Details
                        </button>
                    </h2>
                    <div id="orderDetails" class="accordion-collapse collapse" data-bs-parent="#orderInfo">
                        <div class="accordion-body">
                            <div class="order-info-grid mb-3">
                                <div>
                                    <span class="order-label">Payment:</span>
                                    <span>{{ strtoupper($return->order->payment_method) }}</span>
                                </div>
                                <div>
                                    <span class="order-label">Status:</span>
                                    <span class="badge bg-{{ $return->order->payment_status === 'COMPLETED' ? 'success' : 'warning' }} badge-sm">
                                        {{ $return->order->payment_status }}
                                    </span>
                                </div>
                                <div>
                                    <span class="order-label">Delivery:</span>
                                    <span>{{ $return->order->delivery_description ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="order-label">Order Status:</span>
                                    <span>{{ $return->order->orderStatus->name ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="order-label">Placed:</span>
                                    <span>{{ $return->order->created_at->format('M d, Y H:i') }}</span>
                                </div>
                            </div>

                            <div class="order-totals">
                                <div class="order-total-row">
                                    <span>Subtotal:</span>
                                    <span>{{ $return->order->currency_symbol ?? '$' }}{{ number_format($return->order->amount, 2) }}</span>
                                </div>
                                <div class="order-total-row">
                                    <span>Shipping:</span>
                                    <span>{{ $return->order->currency_symbol ?? '$' }}{{ number_format($return->order->shipping_total, 2) }}</span>
                                </div>
                                <div class="order-total-row">
                                    <span>Delivery:</span>
                                    <span>{{ $return->order->currency_symbol ?? '$' }}{{ number_format($return->order->delivery_price ?? 0, 2) }}</span>
                                </div>
                                @if($return->order->points_amount)
                                <div class="order-total-row">
                                    <span>Points:</span>
                                    <span class="text-success">-{{ $return->order->currency_symbol ?? '$' }}{{ number_format(abs($return->order->points_amount), 2) }}</span>
                                </div>
                                @endif
                                @if($return->order->wallet_balance)
                                <div class="order-total-row">
                                    <span>Wallet:</span>
                                    <span class="text-success">-{{ $return->order->currency_symbol ?? '$' }}{{ number_format(abs($return->order->wallet_balance), 2) }}</span>
                                </div>
                                @endif
                                <div class="order-total-row order-total-final">
                                    <span>Total:</span>
                                    <span>{{ $return->order->currency_symbol ?? '$' }}{{ number_format($return->order->total, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Sidebar -->
        <div class="col-lg-4">
            <!-- Customer Info Compact -->
            @if($return->user)
            <div class="customer-card mb-3">
                <div class="customer-card-header">
                    <i class="bi bi-person-circle"></i> Customer
                </div>
                <div class="customer-card-body">
                    <div class="customer-name">{{ $return->user->name }}</div>
                    <a href="mailto:{{ $return->user->email }}" class="customer-email">
                        <i class="bi bi-envelope"></i> {{ $return->user->email }}
                    </a>
                    @if($return->user->phone)
                    <div class="customer-phone">
                        <i class="bi bi-telephone"></i>
                        {{ $return->user->country_code ? '+' . $return->user->country_code : '' }} {{ $return->user->phone }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Banking Details (if refund preferred) - Compact Cards -->
            @if(strtolower($return->preferred_outcome) === 'refund' && $return->user && $return->user->paymentAccounts && $return->user->paymentAccounts->count() > 0)
            <div class="mb-3">
                <div class="section-label mb-2">
                    <i class="bi bi-credit-card-2-back"></i> Payment Accounts
                </div>
                @foreach($return->user->paymentAccounts as $index => $account)
                    @php
                        $gradients = [
                            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                        ];
                        $gradient = $gradients[$index % count($gradients)];
                    @endphp

                    <div class="bank-card-compact mb-2" style="background: {{ $gradient }};">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="bank-card-name">{{ $account->bank_name ?? 'Account' }}</div>
                            <div class="d-flex gap-1">
                                @if($account->is_default)
                                    <span class="bank-badge"><i class="bi bi-star-fill"></i></span>
                                @endif
                                @if($account->status)
                                    <span class="bank-badge"><i class="bi bi-check-circle"></i></span>
                                @endif
                            </div>
                        </div>
                        @if($account->bank_account_no)
                        <div class="bank-card-number">{{ chunk_split($account->bank_account_no, 4, ' ') }}</div>
                        @endif
                        @if($account->bank_holder_name)
                        <div class="bank-card-holder">{{ $account->bank_holder_name }}</div>
                        @endif
                        @if($account->swift || $account->ifsc)
                        <div class="bank-card-codes">
                            @if($account->swift)<span>SWIFT: {{ $account->swift }}</span>@endif
                            @if($account->ifsc)<span>IFSC: {{ $account->ifsc }}</span>@endif
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @elseif(strtolower($return->preferred_outcome) === 'refund' && $return->user)
            <div class="alert alert-warning p-3 mb-3">
                <i class="bi bi-exclamation-triangle"></i>
                <small class="d-block mt-1">No payment accounts on file.</small>
            </div>
            @endif

            <!-- Timeline Compact -->
            <div class="timeline-card mb-3">
                <div class="timeline-card-item">
                    <i class="bi bi-plus-circle text-success"></i>
                    <div>
                        <div class="timeline-card-label">Created</div>
                        <div class="timeline-card-time">{{ $return->created_at->format('M d, Y H:i') }}</div>
                    </div>
                </div>
                @if($return->updated_at != $return->created_at)
                <div class="timeline-card-item">
                    <i class="bi bi-arrow-repeat text-primary"></i>
                    <div>
                        <div class="timeline-card-label">Updated</div>
                        <div class="timeline-card-time">{{ $return->updated_at->format('M d, Y H:i') }}</div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Actions -->
            @if($return->status === 'pending')
            <div class="action-buttons">
                @php
                    $outcome = strtolower($return->preferred_outcome);
                    $orderItem = $return->order->products->where('id', $return->product_id)->first();
                    $walletAmount = $orderItem ? $orderItem->pivot->subtotal : 0;
                @endphp

                <div class="mb-3">
                    <label for="statusSelect" class="form-label small fw-bold">Update Status</label>
                    <select id="statusSelect" class="form-select form-select-sm" onchange="handleStatusChange(this.value)">
                        <option value="pending" selected>Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <!-- Approve Actions -->
                <div id="approveActions" style="display: none;">
                    @if($outcome === 'refund')
                        <form method="POST" action="{{ route('admin.returns.update', $return->id) }}" id="refundForm" onsubmit="return handleApproveRefund(event)">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="btn btn-danger w-100 mb-2" id="refundBtn">
                                <i class="bi bi-cash-stack"></i> Create Refund & Approve
                            </button>
                            <small class="text-muted d-block">Creates a refund request for processing</small>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.returns.update', $return->id) }}" id="walletForm" onsubmit="return handleApproveWallet(event)">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="btn btn-success w-100 mb-2" id="walletBtn">
                                <i class="bi bi-wallet2"></i> Credit Wallet & Approve
                            </button>
                            <small class="text-muted d-block">Amount: {{ $return->order->currency_symbol ?? '$' }}{{ number_format($walletAmount, 2) }}</small>
                        </form>
                    @endif
                </div>

                <!-- Reject Form -->
                <div id="rejectSection" style="display: none;">
                    <form method="POST" action="{{ route('admin.returns.update', $return->id) }}" id="rejectForm" onsubmit="return handleReject(event)">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="rejected">

                        <div class="mb-3">
                            <label for="rejection_reason" class="form-label small fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm"
                                      id="rejection_reason"
                                      name="rejection_reason"
                                      rows="3"
                                      required
                                      placeholder="Provide reason for rejection..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-danger w-100" id="rejectBtn">
                            <i class="bi bi-x-circle"></i> Reject Return
                        </button>
                    </form>
                </div>
            </div>
            @elseif($return->status === 'rejected')
            <div class="alert alert-danger">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-x-circle-fill me-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Return Rejected</strong>
                        <div class="small">This return request has been rejected and is now closed.</div>
                    </div>
                </div>
                @if($return->rejection_reason)
                <div class="mt-2 pt-2 border-top border-danger">
                    <strong class="small">Rejection Reason:</strong>
                    <p class="mb-0 mt-1">{{ $return->rejection_reason }}</p>
                </div>
                @endif
                @can('return.edit')
                <div class="mt-3 pt-3 border-top border-danger">
                    <small class="text-muted d-block mb-2">
                        <i class="bi bi-shield-lock"></i> Admin Only: You can reopen this return if needed
                    </small>
                    <form method="POST" action="{{ route('admin.returns.update', $return->id) }}" id="reopenForm" onsubmit="return handleReopenReturn(event)">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="pending">
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-arrow-counterclockwise"></i> Reopen Return
                        </button>
                    </form>
                </div>
                @else
                <div class="mt-2 pt-2 border-top border-danger">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Only administrators can reopen rejected returns.
                    </small>
                </div>
                @endcan
            </div>
            @elseif($return->status === 'approved')
            <div class="alert alert-success">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Return Approved</strong>
                        <div class="small">This return has been approved and processed.</div>
                    </div>
                </div>
                @can('return.edit')
                <div class="mt-3 pt-3 border-top border-success">
                    <small class="text-muted d-block mb-2">
                        <i class="bi bi-shield-lock"></i> Admin Only: Reopen if needed
                    </small>
                    <form method="POST" action="{{ route('admin.returns.update', $return->id) }}" id="reopenForm" onsubmit="return handleReopenReturn(event)">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="pending">
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-arrow-counterclockwise"></i> Reopen Return
                        </button>
                    </form>
                </div>
                @endcan
            </div>
            @endif
        </div>
    </div>
</div>

<style>
/* Header Styles */
.return-header {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.btn-icon {
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.status-approved { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-pending { background: #fff3cd; color: #856404; }

/* Reason Alert - RED BACKGROUND */
.reason-alert {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.reason-header {
    background: rgba(0, 0, 0, 0.2);
    color: white;
    padding: 0.75rem 1.25rem;
    font-weight: 700;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.reason-content {
    padding: 1.25rem;
    color: white;
}

.reason-main {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    line-height: 1.4;
}

.reason-sub {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 0.75rem;
}

.reason-description {
    background: rgba(0, 0, 0, 0.15);
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    margin-top: 0.75rem;
    display: flex;
    align-items: start;
    gap: 0.5rem;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.info-item {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.info-value {
    font-weight: 600;
    color: #212529;
}

.badge-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 4px;
    font-weight: 600;
}

.badge-purple { background: #e0cffc; color: #6f42c1; }

/* Product Return Card */
.product-return-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.product-section {
    padding: 1.5rem;
    border-bottom: 2px solid #f8f9fa;
}

.product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
}

.product-image-placeholder {
    width: 80px;
    height: 80px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
    font-size: 2rem;
}

.product-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.return-section {
    padding: 1.5rem;
    background: #f8f9fa;
}

.return-header {
    font-weight: 700;
    color: #495057;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.return-conditions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.condition-badge {
    padding: 0.25rem 0.75rem;
    background: #d4edda;
    color: #155724;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

/* Section Label */
.section-label {
    font-weight: 700;
    color: #495057;
    font-size: 0.875rem;
}

/* Compact Bank Cards */
.bank-card-compact {
    border-radius: 10px;
    padding: 1rem;
    color: white;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s;
}

.bank-card-compact:hover {
    transform: translateY(-2px);
}

.bank-card-name {
    font-weight: 700;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bank-badge {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
}

.bank-card-number {
    font-family: 'Courier New', monospace;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 2px;
    margin: 0.75rem 0;
}

.bank-card-holder {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
}

.bank-card-codes {
    font-size: 0.7rem;
    opacity: 0.9;
    display: flex;
    gap: 1rem;
}

/* Accordion */
.accordion-item {
    border: none;
    background: #fff;
    border-radius: 12px !important;
    overflow: hidden;
    margin-bottom: 0.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.accordion-button {
    background: #fff;
    color: #212529;
    font-weight: 600;
    padding: 1rem 1.25rem;
}

.accordion-button:not(.collapsed) {
    background: #f8f9fa;
    color: #667eea;
    box-shadow: none;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: transparent;
}

.order-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    font-size: 0.875rem;
}

.order-label {
    font-weight: 600;
    color: #6c757d;
    margin-right: 0.5rem;
}

.order-totals {
    border-top: 2px solid #e9ecef;
    padding-top: 1rem;
}

.order-total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.875rem;
}

.order-total-final {
    border-top: 2px solid #212529;
    font-weight: 700;
    font-size: 1rem;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
}

/* Customer Card */
.customer-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
    overflow: hidden;
}

.customer-card-header {
    padding: 1rem 1.25rem;
    background: rgba(255, 255, 255, 0.1);
    font-weight: 700;
    font-size: 0.875rem;
}

.customer-card-body {
    padding: 1.25rem;
}

.customer-name {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.customer-email,
.customer-phone {
    font-size: 0.875rem;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: white;
    text-decoration: none;
}

.customer-email:hover {
    opacity: 1;
    text-decoration: underline;
}

/* Timeline Card */
.timeline-card {
    background: #fff;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.timeline-card-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
}

.timeline-card-item:not(:last-child) {
    border-bottom: 1px solid #f8f9fa;
}

.timeline-card-item i {
    font-size: 1.25rem;
}

.timeline-card-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 600;
}

.timeline-card-time {
    font-size: 0.875rem;
    color: #212529;
    font-weight: 600;
}

/* Action Buttons */
.action-buttons {
    background: #fff;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

/* Responsive */
@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }

    .product-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

@push('scripts')
<script>
// Handle status dropdown change
function handleStatusChange(status) {
    const approveActions = document.getElementById('approveActions');
    const rejectSection = document.getElementById('rejectSection');

    if (approveActions) approveActions.style.display = 'none';
    if (rejectSection) rejectSection.style.display = 'none';

    if (status === 'approved' && approveActions) {
        approveActions.style.display = 'block';
    } else if (status === 'rejected' && rejectSection) {
        rejectSection.style.display = 'block';
    }
}

// Handle approve refund
function handleApproveRefund(event) {
    event.preventDefault();

    Swal.fire({
        title: 'Approve & Create Refund?',
        text: 'This will approve the return and create a refund request for manual processing',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: () => {
            return fetch(event.target.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(new FormData(event.target))
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Failed to approve return');
                    }).catch(err => {
                        throw new Error('Failed to approve return. Please try again.');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Approved!',
                text: result.value?.message || 'Return approved. Refund will be processed automatically.',
                confirmButtonColor: '#667eea'
            }).then(() => {
                // Reload current page to show the approved status
                window.location.reload();
            });
        }
    });

    return false;
}

// Handle approve wallet credit
function handleApproveWallet(event) {
    event.preventDefault();

    const walletAmount = '{{ number_format($walletAmount ?? 0, 2) }}';

    Swal.fire({
        title: 'Approve & Credit Wallet?',
        text: `This will approve the return and credit {{ $return->order->currency_symbol ?? '$' }}${walletAmount} to customer wallet`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: () => {
            return fetch(event.target.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(new FormData(event.target))
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Failed to approve return');
                    }).catch(err => {
                        throw new Error('Failed to approve return. Please try again.');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Approved!',
                text: result.value?.message || 'Return approved. Wallet will be credited automatically.',
                confirmButtonColor: '#667eea'
            }).then(() => {
                // Reload current page to show the approved status
                window.location.reload();
            });
        }
    });

    return false;
}

// Handle reject
function handleReject(event) {
    event.preventDefault();

    const reason = document.getElementById('rejection_reason').value.trim();
    if (!reason) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please provide a rejection reason',
            confirmButtonColor: '#667eea'
        });
        return false;
    }

    const submitBtn = document.getElementById('rejectBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Rejecting...';

    const formData = new FormData(event.target);

    fetch(event.target.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.message || 'Failed to reject return');
            }).catch(err => {
                throw new Error('Failed to reject return. Please try again.');
            });
        }
        return response.json();
    })
    .then((data) => {
        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('rejectModal'));
        if (modal) modal.hide();

        Swal.fire({
            icon: 'success',
            title: 'Rejected!',
            text: data.message || 'Return has been rejected successfully',
            confirmButtonColor: '#667eea'
        }).then(() => {
            // Reload current page to show the rejected status
            window.location.reload();
        });
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#667eea'
        });
    });

    return false;
}

function handleReopenReturn(event) {
    event.preventDefault();

    Swal.fire({
        title: 'Reopen Return?',
        text: 'This will change the return status back to pending for review.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Reopen',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        preConfirm: () => {
            return fetch(event.target.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(new FormData(event.target))
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Failed to reopen return');
                    }).catch(err => {
                        throw new Error('Failed to reopen return. Please try again.');
                    });
                }
                return response.json();
            })
            .catch(error => {
                Swal.showValidationMessage(`Request failed: ${error.message}`);
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Reopened!',
                text: result.value?.message || 'Return has been reopened for review',
                confirmButtonColor: '#667eea'
            }).then(() => {
                window.location.reload();
            });
        }
    });

    return false;
}
</script>
@endpush
@endsection

