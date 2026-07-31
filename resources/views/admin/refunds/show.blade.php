@extends('admin.layout')

@section('title', 'Refund Details - Admin Panel')

@section('content')
<div class="container-fluid px-4">
    <!-- Compact Header with Status Badge -->
    <div class="refund-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('admin.refunds.index', ['page' => session('refunds_list_page', 1)]) }}" class="btn btn-icon btn-light">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div>
                    <h3 class="mb-1">Refund #{{ $refund->id }}</h3>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <span><i class="bi bi-calendar3"></i> {{ $refund->created_at->format('M d, Y H:i') }}</span>
                        <span class="text-muted">•</span>
                        <span><i class="bi bi-receipt"></i> Order #{{ $refund->order->order_number ?? $refund->order_id }}</span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($refund->status === 'approved')
                    <span class="status-badge status-approved">
                        <i class="bi bi-check-circle-fill"></i> Approved
                    </span>
                @elseif($refund->status === 'rejected')
                    <span class="status-badge status-rejected">
                        <i class="bi bi-x-circle-fill"></i> Rejected
                    </span>
                @else
                    <span class="status-badge status-pending">
                        <i class="bi bi-clock-fill"></i> Pending
                    </span>
                @endif
                <span class="amount-badge">{{ $refund->order->currency_symbol ?? '$' }}{{ number_format($refund->amount, 2) }}</span>
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
        <!-- Main Content - Full Width -->
        <div class="col-lg-8">
            <!-- Refund Reason Box - RED BACKGROUND -->
            <div class="reason-alert mb-4">
                <div class="reason-header">
                    <i class="bi bi-exclamation-triangle-fill"></i> Refund Reason
                </div>
                <div class="reason-content">
                    <div class="reason-main">{{ $refund->reason }}</div>
                </div>
            </div>

            <!-- Compact Info Grid -->
            <div class="info-grid mb-4">
                <div class="info-item">
                    <div class="info-label">Customer</div>
                    <div class="info-value">{{ $refund->user->name ?? 'N/A' }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Type</div>
                    <div class="info-value">
                        <span class="badge-sm badge-info">{{ strtoupper($refund->payment_type) }}</span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Amount</div>
                    <div class="info-value">{{ $refund->order->currency_symbol ?? '$' }}{{ number_format($refund->amount, 2) }}</div>
                </div>
            </div>

            @if($refund->rejection_reason)
            <div class="alert alert-danger d-flex align-items-start gap-2 mb-4">
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                <div>
                    <strong>Rejection Reason:</strong>
                    <p class="mb-0 mt-1">{{ $refund->rejection_reason }}</p>
                </div>
            </div>
            @endif

            <!-- Product & Return Details Combined -->
            @php
                $returnRequest = \App\Models\ReturnRequest::where('order_id', $refund->order_id)
                    ->where('product_id', $refund->product_id)
                    ->with(['product.product_thumbnail'])
                    ->first();
            @endphp

            @if($refund->product)
            <div class="product-return-card mb-4">
                <div class="product-section">
                    <div class="d-flex gap-3 align-items-start">
                        @if($refund->product->product_thumbnail)
                            <img src="{{ $refund->product->product_thumbnail->image_url }}"
                                 alt="{{ $refund->product->name }}"
                                 class="product-image">
                        @else
                            <div class="product-image-placeholder">
                                <i class="bi bi-image"></i>
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <h6 class="mb-2">{{ $refund->product->name }}</h6>
                            @php
                                $orderItem = $refund->order->products->where('id', $refund->product_id)->first();
                                $qty = $orderItem ? $orderItem->pivot->quantity : 1;
                                $price = $orderItem ? $orderItem->pivot->single_price : 0;
                                $subtotal = $orderItem ? $orderItem->pivot->subtotal : 0;
                            @endphp
                            <div class="product-meta">
                                <span><strong>Qty:</strong> {{ $qty }}</span>
                                <span><strong>Price:</strong> {{ $refund->order->currency_symbol ?? '$' }}{{ number_format($price, 2) }}</span>
                                <span><strong>Total:</strong> {{ $refund->order->currency_symbol ?? '$' }}{{ number_format($subtotal, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($returnRequest)
                <div class="return-section">
                    <div class="return-header">
                        <i class="bi bi-box-arrow-in-left"></i> Return Details
                    </div>
                    <div class="return-info-grid">
                        <div>
                            <span class="return-label">Outcome:</span>
                            <span class="badge-sm badge-purple">{{ ucfirst($returnRequest->preferred_outcome) }}</span>
                        </div>
                        <div>
                            <span class="return-label">Reason:</span>
                            <span>{{ $returnRequest->return_reason }}</span>
                        </div>
                        @if($returnRequest->sub_reason)
                        <div>
                            <span class="return-label">Sub-reason:</span>
                            <span>{{ $returnRequest->sub_reason }}</span>
                        </div>
                        @endif
                    </div>

                    @if($returnRequest->description)
                    <div class="return-description">
                        {{ $returnRequest->description }}
                    </div>
                    @endif

                    <div class="return-conditions">
                        @if($returnRequest->is_product_not_used)
                            <span class="condition-badge"><i class="bi bi-check-circle-fill"></i> Not Used</span>
                        @endif
                        @if($returnRequest->in_original_packaging)
                            <span class="condition-badge"><i class="bi bi-check-circle-fill"></i> Original Packaging</span>
                        @endif
                        @if($returnRequest->include_all_accessories)
                            <span class="condition-badge"><i class="bi bi-check-circle-fill"></i> All Accessories</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endif
            <!-- Additional Information Accordion -->
            <div class="accordion mb-4" id="additionalInfo">
                @if($refund->order && $refund->order->statusHistories && count($refund->order->statusHistories) > 0)
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orderHistory">
                            <i class="bi bi-clock-history me-2"></i> Order Status History
                            <span class="badge bg-secondary ms-2">{{ count($refund->order->statusHistories) }}</span>
                        </button>
                    </h2>
                    <div id="orderHistory" class="accordion-collapse collapse" data-bs-parent="#additionalInfo">
                        <div class="accordion-body">
                            <div class="timeline-compact">
                                @foreach($refund->order->statusHistories as $history)
                                <div class="timeline-compact-item">
                                    <div class="timeline-compact-marker"></div>
                                    <div class="timeline-compact-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <span class="fw-medium">
                                                {{ $history->oldStatus->name ?? 'N/A' }}
                                                <i class="bi bi-arrow-right small"></i>
                                                {{ $history->newStatus->name ?? 'N/A' }}
                                            </span>
                                            <small class="text-muted">{{ $history->created_at->format('M d, H:i') }}</small>
                                        </div>
                                        @if($history->updatedBy)
                                            <small class="text-muted d-block">{{ $history->updatedBy->name }}</small>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#rawData">
                            <i class="bi bi-code-square me-2"></i> Technical Data (JSON)
                        </button>
                    </h2>
                    <div id="rawData" class="accordion-collapse collapse" data-bs-parent="#additionalInfo">
                        <div class="accordion-body p-0">
                            <div class="raw-data-container">
                                <pre class="mb-0"><code>{{ json_encode($refund->toArray(), JSON_PRETTY_PRINT) }}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - Customer & Actions -->
        <div class="col-lg-4">
            <!-- Customer Info Compact -->
            @if($refund->user)
            <div class="customer-card mb-3">
                <div class="customer-card-header">
                    <i class="bi bi-person-circle"></i> Customer
                </div>
                <div class="customer-card-body">
                    <div class="customer-name">{{ $refund->user->name }}</div>
                    <a href="mailto:{{ $refund->user->email }}" class="customer-email">
                        <i class="bi bi-envelope"></i> {{ $refund->user->email }}
                    </a>
                    @if($refund->user->phone)
                    <div class="customer-phone">
                        <i class="bi bi-telephone"></i>
                        {{ $refund->user->country_code ? '+' . $refund->user->country_code : '' }} {{ $refund->user->phone }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Banking Details - Compact Cards -->
            @if($refund->user && $refund->user->paymentAccounts && $refund->user->paymentAccounts->count() > 0)
            <div class="mb-3">
                <div class="section-label mb-2">
                    <i class="bi bi-credit-card-2-back"></i> Payment Accounts
                </div>
                @foreach($refund->user->paymentAccounts as $index => $account)
                    @php
                        $gradients = [
                            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                            'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
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
            @elseif($refund->user)
            <div class="alert alert-warning p-3 mb-3">
                <i class="bi bi-exclamation-triangle"></i>
                <small class="d-block mt-1">No banking details available.</small>
            </div>
            @endif

            <!-- Timeline Compact -->
            <div class="timeline-card mb-3">
                <div class="timeline-card-item">
                    <i class="bi bi-plus-circle text-success"></i>
                    <div>
                        <div class="timeline-card-label">Created</div>
                        <div class="timeline-card-time">{{ $refund->created_at->format('M d, Y H:i') }}</div>
                    </div>
                </div>
                @if($refund->updated_at != $refund->created_at)
                <div class="timeline-card-item">
                    <i class="bi bi-arrow-repeat text-primary"></i>
                    <div>
                        <div class="timeline-card-label">Updated</div>
                        <div class="timeline-card-time">{{ $refund->updated_at->format('M d, Y H:i') }}</div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Actions -->
            @if($refund->status === 'pending')
            <div class="action-buttons">
                <form method="POST" action="{{ route('admin.refunds.update', $refund->id) }}" id="approveForm" onsubmit="return handleApprove(event)">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-check-circle"></i> Approve Refund
                    </button>
                </form>

                <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="bi bi-x-circle"></i> Reject Refund
                </button>
            </div>
            @elseif($refund->status === 'rejected')
            <div class="alert alert-danger">
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-x-circle-fill me-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Refund Rejected</strong>
                        <div class="small">This refund request has been rejected and is now closed.</div>
                    </div>
                </div>
                @if($refund->reason && $refund->status === 'rejected')
                <div class="mt-2 pt-2 border-top border-danger">
                    <strong class="small">Rejection Reason:</strong>
                    <p class="mb-0 mt-1">{{ $refund->reason }}</p>
                </div>
                @endif
                @can('refund.edit')
                <div class="mt-3 pt-3 border-top border-danger">
                    <small class="text-muted d-block mb-2">
                        <i class="bi bi-shield-lock"></i> Admin Only: You can reopen this refund if needed
                    </small>
                    <form method="POST" action="{{ route('admin.refunds.update', $refund->id) }}" id="reopenForm" onsubmit="return handleReopen(event)">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="pending">
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-arrow-counterclockwise"></i> Reopen Refund
                        </button>
                    </form>
                </div>
                @else
                <div class="mt-2 pt-2 border-top border-danger">
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Only administrators can reopen rejected refunds.
                    </small>
                </div>
                @endcan
            </div>
            @elseif($refund->status === 'approved')
            <div class="alert alert-success">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Refund Approved</strong>
                        <div class="small">This refund has been approved and processed.</div>
                    </div>
                </div>
                @can('refund.edit')
                <div class="mt-3 pt-3 border-top border-success">
                    <small class="text-muted d-block mb-2">
                        <i class="bi bi-shield-lock"></i> Admin Only: Reopen if needed
                    </small>
                    <form method="POST" action="{{ route('admin.refunds.update', $refund->id) }}" id="reopenForm" onsubmit="return handleReopen(event)">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="pending">
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-arrow-counterclockwise"></i> Reopen Refund
                        </button>
                    </form>
                </div>
                @endcan
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.refunds.update', $refund->id) }}" id="rejectForm" onsubmit="return handleReject(event)">
                @csrf
                @method('PUT')
                <input type="hidden" name="status" value="rejected">

                <div class="modal-header">
                    <h5 class="modal-title">Reject Refund Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control"
                                  id="rejection_reason"
                                  name="rejection_reason"
                                  rows="4"
                                  required
                                  placeholder="Please provide a reason for rejecting this refund request..."></textarea>
                        <small class="text-muted">This will be sent to the customer.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="rejectBtn">Reject Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Header Styles */
.refund-header {
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

.amount-badge {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 700;
    font-size: 1.125rem;
}

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
    margin-bottom: 0;
    line-height: 1.4;
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

.badge-info { background: #cfe2ff; color: #084298; }
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
    margin-top: 0.5rem;
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

.return-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.return-label {
    font-weight: 600;
    color: #6c757d;
    margin-right: 0.5rem;
}

.return-description {
    padding: 1rem;
    background: white;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: #495057;
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

/* Accordion Styles */
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

.timeline-compact-item {
    position: relative;
    padding-left: 2rem;
    padding-bottom: 1rem;
}

.timeline-compact-marker {
    position: absolute;
    left: 0;
    top: 0.25rem;
    width: 10px;
    height: 10px;
    background: #667eea;
    border-radius: 50%;
}

.timeline-compact-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 4px;
    top: 1rem;
    width: 2px;
    height: 100%;
    background: #e9ecef;
}

.timeline-compact-content {
    font-size: 0.875rem;
}

.raw-data-container {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 1rem;
    max-height: 400px;
    overflow-y: auto;
}

.raw-data-container pre {
    margin: 0;
    font-size: 0.75rem;
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
function handleApprove(event) {
    event.preventDefault();

    Swal.fire({
        title: 'Approve Refund?',
        text: 'Are you sure you want to approve this refund request?',
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
                        throw new Error(data.message || 'Failed to approve refund');
                    }).catch(err => {
                        throw new Error('Failed to approve refund. Please try again.');
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
                text: result.value?.message || 'Refund has been approved successfully',
                confirmButtonColor: '#667eea'
            }).then(() => {
                // Reload current page to show the approved status
                window.location.reload();
            });
        }
    });

    return false;
}

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
                throw new Error(data.message || 'Failed to reject refund');
            }).catch(err => {
                // If JSON parsing fails, throw generic error
                throw new Error('Failed to reject refund. Please try again.');
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
            text: data.message || 'Refund has been rejected successfully',
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

function handleReopen(event) {
    event.preventDefault();

    Swal.fire({
        title: 'Reopen Refund?',
        text: 'This will change the refund status back to pending for review.',
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
                        throw new Error(data.message || 'Failed to reopen refund');
                    }).catch(err => {
                        throw new Error('Failed to reopen refund. Please try again.');
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
                text: result.value?.message || 'Refund has been reopened for review',
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

