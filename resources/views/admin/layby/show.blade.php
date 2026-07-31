@extends('admin.layout')

@section('title', 'Layby Application #' . $application->application_number)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.layby.index') }}" class="btn btn-link ps-0">
                        ← Back to Applications
                    </a>
                    <h2 class="mb-0">Application #{{ $application->application_number }}</h2>
                    <p class="text-muted">Applied {{ $application->created_at->format('F j, Y \a\t g:i A') }}</p>
                </div>
                <div>
                    @php
                        $statusColors = [
                            'pending' => 'warning',
                            'approved' => 'info',
                            'active' => 'primary',
                            'completed' => 'success',
                            'rejected' => 'danger',
                            'cancelled' => 'secondary',
                        ];
                        $color = $statusColors[$application->status] ?? 'secondary';
                    @endphp
                    <span class="badge bg-{{ $color }} fs-5">{{ ucfirst($application->status) }}</span>
                </div>
            </div>
        </div>
    </div>

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

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Customer Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> {{ $application->user->name }}</p>
                            <p><strong>Email:</strong> <a href="mailto:{{ $application->user->email }}">{{ $application->user->email }}</a></p>
                            <p><strong>Phone:</strong> {{ $application->user->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Customer Since:</strong> {{ $application->user->created_at->format('M d, Y') }}</p>
                            <p><strong>Total Orders:</strong> {{ $userOrderCount }}</p>
                            <p><strong>User ID:</strong> #{{ $application->user->id }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex">
                        @if($application->product && $application->product->product_thumbnail)
                        <img src="{{ $application->product->product_thumbnail->image_url }}"
                             alt="{{ $application->product_name }}"
                             style="width: 200px; height: 200px; object-fit: contain; border-radius: 8px;"
                             class="me-4">
                        @endif
                        <div class="flex-grow-1">
                            <h4>{{ $application->product_name }}</h4>
                            @if($application->variation_display_name)
                            <p class="text-muted mb-2">
                                <strong>Variation:</strong> {{ $application->variation_display_name }}
                            </p>
                            @endif
                            <p><strong>Product Price:</strong> <span class="cv" data-usd="{{ $application->product_price }}">{{ $application->currency_symbol }}{{ number_format($application->product_price, 2) }}</span></p>
                            <p><strong>SKU:</strong> {{ $application->product->sku ?? 'N/A' }}</p>
                            <a href="{{ url('/admin/products/' . $application->product_id . '/edit') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                View Product
                            </a>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Payment Schedule -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment Schedule</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <p><strong>Duration:</strong> {{ $application->duration_months }} months</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Deposit ({{ $depositPercent ?? 30 }}%):</strong> <span class="cv" data-usd="{{ $application->deposit_amount }}">{{ $application->currency_symbol }}{{ number_format($application->deposit_amount, 2) }}</span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Monthly Payment:</strong> <span class="cv" data-usd="{{ $application->monthly_amount }}">{{ $application->currency_symbol }}{{ number_format($application->monthly_amount, 2) }}</span></p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><strong>Payment Progress</strong></span>
                            <span>{{ number_format($application->getProgressPercentage(), 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 30px;">
                            <div class="progress-bar progress-bar-striped" role="progressbar"
                                 style="width: {{ $application->getProgressPercentage() }}%"
                                 aria-valuenow="{{ $application->getProgressPercentage() }}"
                                 aria-valuemin="0" aria-valuemax="100">
                                <span class="cv" data-usd="{{ $application->total_paid }}">{{ $application->currency_symbol }}{{ number_format($application->total_paid, 2) }}</span> / <span class="cv" data-usd="{{ $application->total_amount }}">{{ $application->currency_symbol }}{{ number_format($application->total_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Total Paid</h6>
                                    <h4><span class="cv" data-usd="{{ $application->total_paid }}">{{ $application->currency_symbol }}{{ number_format($application->total_paid, 2) }}</span></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h6>Balance</h6>
                                    <h4><span class="cv" data-usd="{{ $application->balance_remaining }}">{{ $application->currency_symbol }}{{ number_format($application->balance_remaining, 2) }}</span></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6>Remaining</h6>
                                    <h4>{{ $application->getRemainingMonths() }} months</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linked Order -->
            @if($application->order)
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-cart-check"></i> Linked Order
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Order Created Successfully!</strong> This layby has been converted to an order after full payment completion.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Order Number:</strong>
                                <a href="/admin/orders/{{$application->order->order_number}}" class="text-primary">
                                    #{{ $application->order->order_number }}
                                </a>
                            </p>
                            <p><strong>Order Status:</strong>
                                <span class="badge bg-info">{{ $application->order->orderStatus->name ?? 'N/A' }}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Payment Status:</strong>
                                <span class="badge bg-success">{{ $application->order->payment_status }}</span>
                            </p>
                            <p><strong>Created:</strong> {{ $application->order->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="/admin/orders/{{$application->order->order_number}}" class="btn btn-primary">
                            <i class="bi bi-eye"></i> View Full Order Details
                        </a>
                    </div>
                </div>
            </div>
            @elseif($application->status === 'completed')
            <div class="card mb-4 border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="bi bi-exclamation-triangle"></i> Order Creation Pending
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        <i class="bi bi-info-circle me-2"></i>
                        This layby is completed but the order hasn't been created yet. This usually happens when automatic order creation fails due to missing user information.
                    </p>

                    <div id="order-creation-status" class="mb-3">
                        <!-- Status will be loaded here -->
                    </div>

                    <button type="button" class="btn btn-primary" id="btnCreateOrder" onclick="checkAndCreateOrder()">
                        <i class="bi bi-cart-plus me-2"></i> Create Order Manually
                    </button>
                </div>
            </div>

            <script>
                // Check eligibility on page load
                document.addEventListener('DOMContentLoaded', function() {
                    checkOrderEligibility();
                });

                function checkOrderEligibility() {
                    const statusDiv = document.getElementById('order-creation-status');
                    const createBtn = document.getElementById('btnCreateOrder');

                    statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Checking prerequisites...';

                    fetch('{{ route("admin.layby.check-order-eligibility", $application->id) }}')
                        .then(response => response.json())
                        .then(data => {
                            if (data.eligible) {
                                statusDiv.innerHTML = '<div class="alert alert-success mb-0"><i class="bi bi-check-circle me-2"></i>All prerequisites met. Ready to create order.</div>';

                                if (data.warning) {
                                    statusDiv.innerHTML += '<div class="alert alert-danger mt-2 mb-0"><i class="bi bi-info-circle me-2"></i>' + data.warning + '</div>';
                                }

                                createBtn.disabled = false;
                            } else {
                                let errorHtml = '<div class="alert alert-danger mb-0"><strong><i class="bi bi-x-circle me-2"></i>Cannot create order:</strong><ul class="mb-0 mt-2">';
                                data.errors.forEach(error => {
                                    errorHtml += '<li>' + error + '</li>';
                                });
                                errorHtml += '</ul></div>';
                                statusDiv.innerHTML = errorHtml;
                                createBtn.disabled = true;
                            }
                        })
                        .catch(error => {
                            statusDiv.innerHTML = '<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Error checking eligibility: ' + error.message + '</div>';
                            createBtn.disabled = true;
                        });
                }

                function checkAndCreateOrder() {
                    // First check eligibility
                    fetch('{{ route("admin.layby.check-order-eligibility", $application->id) }}')
                        .then(response => response.json())
                        .then(data => {
                            if (!data.eligible) {
                                let errorList = '<ul style="text-align: left;">';
                                data.errors.forEach(error => {
                                    errorList += '<li>' + error + '</li>';
                                });
                                errorList += '</ul>';

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Cannot Create Order',
                                    html: '<p>Please fix the following issues:</p>' + errorList,
                                    confirmButtonText: 'OK'
                                });
                                return;
                            }

                            // Show confirmation
                            let confirmationHtml = '<p>This will create an order for:</p>' +
                                '<p><strong>Layby:</strong> {{ $application->application_number }}</p>' +
                                '<p><strong>Product:</strong> {{ $application->product_name }}</p>' +
                                '<p><strong>Amount:</strong> <span class="cv" data-usd="{{ $application->total_amount }}">{{ $application->currency_symbol }}{{ number_format($application->total_amount, 2) }}</span></p>';

                            if (data.warning) {
                                confirmationHtml += '<div class="alert alert-warning mt-3 mb-0" style="font-size: 0.9em;"><i class="bi bi-info-circle me-2"></i>' + data.warning + '</div>';
                            }

                            Swal.fire({
                                icon: 'question',
                                title: 'Create Order?',
                                html: confirmationHtml,
                                showCancelButton: true,
                                confirmButtonText: 'Yes, Create Order',
                                cancelButtonText: 'Cancel',
                                confirmButtonColor: '#0d6efd',
                                cancelButtonColor: '#6c757d',
                                showLoaderOnConfirm: true,
                                preConfirm: () => {
                                    return fetch('{{ route("admin.layby.manually-create-order", $application->id) }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        }
                                    })
                                    .then(response => {
                                        if (!response.ok) {
                                            return response.json().then(err => {
                                                throw new Error(err.message || 'Failed to create order');
                                            });
                                        }
                                        return response.json();
                                    })
                                    .catch(error => {
                                        Swal.showValidationMessage(`Request failed: ${error.message}`);
                                    });
                                },
                                allowOutsideClick: () => !Swal.isLoading()
                            }).then((result) => {
                                if (result.isConfirmed && result.value.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Order Created!',
                                        html: '<p>' + result.value.message + '</p>' +
                                              '<p><strong>Order Number:</strong> #' + result.value.order.order_number + '</p>',
                                        confirmButtonText: 'View Order',
                                        showCancelButton: true,
                                        cancelButtonText: 'Stay Here'
                                    }).then((viewResult) => {
                                        if (viewResult.isConfirmed) {
                                            window.location.href = '/admin/orders/' + result.value.order.order_number;
                                        } else {
                                            // Reload page to show the linked order
                                            window.location.reload();
                                        }
                                    });
                                }
                            });
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to check eligibility: ' + error.message,
                                confirmButtonText: 'OK'
                            });
                        });
                }
            </script>
            @endif

            <!-- Payment History -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    @if($application->payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Payment #</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Captured By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($application->payments as $payment)
                                <tr>
                                    <td>{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i') : $payment->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $payment->payment_number }}</td>
                                    <td><strong><span class="cv" data-usd="{{ $payment->amount }}">{{ $application->currency_symbol }}{{ number_format($payment->amount, 2) }}</span></strong></td>
                                    <td>{{ strtoupper($payment->payment_method) }}</td>
                                    <td>
                                        @php
                                            $paymentStatusColors = [
                                                'pending' => 'warning',
                                                'completed' => 'success',
                                                'failed' => 'danger',
                                                'refunded' => 'secondary',
                                            ];
                                            $paymentColor = $paymentStatusColors[$payment->payment_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $paymentColor }}">{{ ucfirst($payment->payment_status) }}</span>
                                    </td>
                                    <td>{{ $payment->capturedBy->name ?? 'System' }}</td>
                                    <td>
                                        @if($payment->payment_status === 'completed')
                                        <button type="button" class="btn btn-sm btn-warning me-1"
                                                onclick="editPayment({{ $payment->id }}, {{ $payment->amount }}, '{{ $payment->payment_method }}', '{{ $payment->transaction_id }}', '{{ addslashes($payment->payment_note ?? '') }}')">
                                            Edit
                                        </button>
                                        <form action="{{ route('admin.layby.delete-payment', [$application->id, $payment->id]) }}"
                                              method="POST"
                                              data-swal-confirm="Are you sure you want to delete this payment?"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @if($payment->payment_note)
                                <tr>
                                    <td colspan="7" class="bg-light">
                                        <small><strong>Note:</strong> {{ $payment->payment_note }}</small>
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center py-4">No payments recorded yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Status Management -->
            @if($application->status === 'pending')
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0 text-white">Approve or Reject</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.layby.update-status', $application->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Decision</label>
                            <select name="status" class="form-select" required onchange="toggleRejectionReason(this)">
                                <option value="">Select...</option>
                                <option value="approved">Approve Application</option>
                                <option value="rejected">Reject Application</option>
                            </select>
                        </div>
                        <div class="mb-3" id="rejection-reason-field" style="display: none;">
                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea name="rejection_reason" class="form-control" rows="3"
                                      placeholder="Please provide a reason for rejection"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Submit Decision</button>
                    </form>
                </div>
            </div>

            <script>
            function toggleRejectionReason(select) {
                const reasonField = document.getElementById('rejection-reason-field');
                if (select.value === 'rejected') {
                    reasonField.style.display = 'block';
                    reasonField.querySelector('textarea').required = true;
                } else {
                    reasonField.style.display = 'none';
                    reasonField.querySelector('textarea').required = false;
                }
            }
            </script>
            @elseif($application->status === 'rejected')
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Application Rejected</h5>
                </div>
                <div class="card-body">
                    <p><strong>Rejected on:</strong> {{ $application->rejected_at->format('M d, Y') }}</p>
                    @if($application->rejection_reason)
                    <p><strong>Reason:</strong></p>
                    <p class="text-muted">{{ $application->rejection_reason }}</p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Capture Payment -->
            @if(in_array($application->status, ['approved', 'active']) && $application->balance_remaining > 0)
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Capture Payment</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.layby.capture-payment', $application->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span> <small class="text-muted fw-normal" id="captureAmountCurrLabel">(USD)</small></label>
                            <input type="number" name="amount" id="captureAmountInput" class="form-control"
                                   step="0.01" min="0.01"
                                   max="{{ $application->balance_remaining }}"
                                   placeholder="0.00" required>
                            <small class="text-muted">Max: <span class="cv" data-usd="{{ $application->balance_remaining }}">{{ $application->currency_symbol }}{{ number_format($application->balance_remaining, 2) }}</span></small>
                            <div id="captureUsdConversion" class="text-info small mt-1 fw-bold" style="display:none;"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="payfast">PayFast</option>
                                <option value="yoco">Yoco</option>
                                <option value="card">Card</option>
                                <option value="eft">EFT</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Transaction ID</label>
                            <input type="text" name="transaction_id" class="form-control"
                                   placeholder="Optional reference number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea name="payment_note" class="form-control" rows="2"
                                      placeholder="Optional payment note"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Record Payment</button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Completed Status -->
            @if($application->status === 'completed')
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">✓ Completed</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><strong>Completed on:</strong> {{ $application->completed_at->format('M d, Y') }}</p>
                    <p class="text-success mb-0 mt-2">This layby has been fully paid!</p>
                </div>
            </div>
            @endif

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <p><strong>Last Payment:</strong> {{ $application->last_payment_at ? $application->last_payment_at->format('M d, Y') : 'None' }}</p>
                    <p><strong>Total Payments:</strong> {{ $application->payments->where('payment_status', 'completed')->count() }}</p>
                    @if($application->approved_at)
                    <p><strong>Approved on:</strong> {{ $application->approved_at->format('M d, Y') }}</p>
                    <p><strong>Approved by:</strong> {{ $application->approvedBy->name ?? 'System' }}</p>
                    @endif
                </div>
            </div>

            <!-- ID Document -->
            @if($application->id_document_attachment_id || $application->id_document_path)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-file-earmark-person"></i> ID Document
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>Document Type:</strong> {{ strtoupper($application->id_document_type ?? 'ID') }}</p>
                    <p><strong>Document Number:</strong> {{ $application->id_document_number ?? 'N/A' }}</p>
                    <p><strong>Uploaded:</strong> {{ $application->created_at->format('M d, Y H:i') }}</p>

                    @php
                        // Resolve document info from attachment record
                        $fileName    = null;
                        $storagePath = null;   // relative path inside storage/app/public

                        if ($application->id_document_attachment_id && $application->idDocumentAttachment) {
                            $att         = $application->idDocumentAttachment;
                            $fileName    = $att->file_name ?? 'document';

                            // Build the path: attachment stores files under layby_documents/Y/m/d/{uuid}.ext
                            // The original_url / image_url already contains the full public URL.
                            // Extract the path relative to storage/app/public so we can re-route it.
                            $rawUrl      = $att->image_url ?? $att->original_url ?? '';
                            // Strip anything up to and including /storage/ to get the relative path
                            if (str_contains($rawUrl, '/storage/')) {
                                $storagePath = ltrim(substr($rawUrl, strpos($rawUrl, '/storage/') + strlen('/storage/')), '/');
                            } elseif (str_contains($rawUrl, '/api/layby-files/')) {
                                // Already the new route — extract path after /api/layby-files/
                                $storagePath = ltrim(substr($rawUrl, strpos($rawUrl, '/api/layby-files/') + strlen('/api/layby-files/')), '/');
                            } else {
                                // Fallback: use file_name, assume layby_documents bucket
                                $storagePath = 'layby_documents/' . $fileName;
                            }
                        } elseif ($application->id_document_path) {
                            // Legacy: path stored directly
                            $storagePath = ltrim($application->id_document_path, '/');
                            $fileName    = basename($storagePath);
                        }

                        // Always serve through the /api/layby-files/ route
                        $documentUrl = $storagePath
                            ? rtrim(config('app.url'), '/') . '/api/layby-files/' . $storagePath
                            : null;

                        // Determine file type for preview
                        $extension = $fileName ? strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) : '';
                        $isImage   = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        $isPdf     = ($extension === 'pdf');
                    @endphp

                    @if($documentUrl)
                        {{-- Storage badge --}}
                        @if($application->id_document_attachment_id)
                        <div class="alert alert-info mb-3 py-2" style="font-size:0.82rem;">
                            <i class="bi bi-cloud-check me-1"></i>
                            <strong>Storage:</strong>
                            {{ $application->idDocumentAttachment ? 'Media Server' : 'Local Fallback' }}
                            &nbsp;·&nbsp; <code style="font-size:0.78rem;">{{ $storagePath }}</code>
                        </div>
                        @else
                        <div class="alert alert-secondary mb-3 py-2" style="font-size:0.82rem;">
                            <i class="bi bi-hdd me-1"></i> <strong>Storage:</strong> Legacy local path
                        </div>
                        @endif

                        {{-- Action buttons --}}
                        <div class="d-grid gap-2 mb-3">
                            @if($isPdf)
                            {{-- PDF: open in new tab so browser renders it inline --}}
                            <a href="{{ $documentUrl }}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-eye me-1"></i> View PDF
                            </a>
                            @else
                            <a href="{{ $documentUrl }}" target="_blank" class="btn btn-primary">
                                <i class="bi bi-eye me-1"></i> View Document
                            </a>
                            @endif
                            <a href="{{ $documentUrl }}" download="{{ $fileName }}" class="btn btn-outline-primary">
                                <i class="bi bi-download me-1"></i> Download
                            </a>
                        </div>

                        {{-- Preview --}}
                        <div class="border-top pt-3">
                            <h6 class="mb-3">Preview:</h6>
                            @if($isImage)
                                <img src="{{ $documentUrl }}"
                                     alt="ID Document"
                                     style="max-width:100%; max-height:340px; object-fit:contain; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                            @elseif($isPdf)
                                {{-- Inline PDF embed --}}
                                <iframe src="{{ $documentUrl }}"
                                        width="100%" height="420"
                                        style="border:1px solid #dee2e6; border-radius:8px;"
                                        title="ID Document PDF">
                                    <p class="text-muted small">
                                        Your browser cannot display the PDF inline.
                                        <a href="{{ $documentUrl }}" target="_blank">Open it here</a>.
                                    </p>
                                </iframe>
                            @else
                                <div class="text-center py-4 bg-light rounded">
                                    <i class="bi bi-file-earmark" style="font-size:48px; color:#6c757d;"></i>
                                    <p class="mt-2 mb-0 small">{{ strtoupper($extension) }} File</p>
                                    <small class="text-muted">Click "View Document" to open</small>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Document reference exists but the file URL could not be resolved.
                        </div>
                    @endif
                </div>
            </div>
            @else
            <div class="card mt-4 border-warning">
                <div class="card-body">
                    <p class="text-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>No ID document uploaded.</strong>
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST" id="editPaymentForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="payment_id" id="edit_payment_id">
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span> <small class="text-muted fw-normal" id="editAmountCurrLabel">(USD)</small></label>
                        <input type="number" name="amount" id="edit_amount" class="form-control"
                               step="0.01" min="0.01" required>
                        <div id="editUsdConversion" class="text-info small mt-1 fw-bold" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="edit_payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="payfast">PayFast</option>
                            <option value="yoco">Yoco</option>
                            <option value="card">Card</option>
                            <option value="eft">EFT</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" name="transaction_id" id="edit_transaction_id" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea name="payment_note" id="edit_payment_note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============ CURRENCY CONVERSION SYSTEM ============
// Uses the logged-in user's preferred_currency from the DB (set via sidebar currency selector)
// Exchange rate is looked up from the currencies table for accuracy
(function() {
    @php
        $userPrefCurrency = auth()->user()->preferred_currency ?? 'USD';
        $userCurrSymbol   = auth()->user()->currency_symbol ?? '$';
        $currRecord       = \App\Models\Currency::where('code', $userPrefCurrency)->where('status', 1)->first();
        $userExchangeRate = $currRecord ? $currRecord->exchange_rate : (auth()->user()->currency_exchange_rate ?? 1);
    @endphp
    const userCurrency = {
        code: '{{ $userPrefCurrency }}',
        rate: parseFloat('{{ $userExchangeRate }}') || 1,
        symbol: '{{ $userCurrSymbol }}'
    };

    function formatAmount(usd) {
        const converted = usd * userCurrency.rate;
        const formatted = converted.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return userCurrency.symbol + formatted;
    }

    function convertAllAmounts() {
        document.querySelectorAll('.cv[data-usd]').forEach(el => {
            const usd = parseFloat(el.dataset.usd);
            if (isNaN(usd)) return;
            el.textContent = formatAmount(usd);
        });

        // Update payment input labels
        const capLabel = document.getElementById('captureAmountCurrLabel');
        if (capLabel) capLabel.textContent = '(' + userCurrency.code + ')';
        const editLabel = document.getElementById('editAmountCurrLabel');
        if (editLabel) editLabel.textContent = '(' + userCurrency.code + ')';

        // Update max attribute on capture input (convert max from USD to display currency)
        const capInput = document.getElementById('captureAmountInput');
        if (capInput) {
            const maxUsd = {{ $application->balance_remaining }};
            capInput.max = (maxUsd * userCurrency.rate).toFixed(2);
        }
    }

    function showUsdConversion(inputId, displayId) {
        const input = document.getElementById(inputId);
        const display = document.getElementById(displayId);
        if (!input || !display) return;
        const val = parseFloat(input.value);
        if (isNaN(val) || val <= 0 || userCurrency.code === 'USD') {
            display.style.display = 'none';
            return;
        }
        const usdAmount = val / userCurrency.rate;
        display.textContent = '≈ $' + usdAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' USD (stored amount)';
        display.style.display = 'block';
    }

    function updateCaptureConversion() {
        showUsdConversion('captureAmountInput', 'captureUsdConversion');
    }
    function updateEditConversion() {
        showUsdConversion('edit_amount', 'editUsdConversion');
    }

    const captureInput = document.getElementById('captureAmountInput');
    if (captureInput) captureInput.addEventListener('input', updateCaptureConversion);

    const editInput = document.getElementById('edit_amount');
    if (editInput) editInput.addEventListener('input', updateEditConversion);

    // Intercept capture payment form submit: convert display currency back to USD
    const captureForm = captureInput ? captureInput.closest('form') : null;
    if (captureForm) {
        captureForm.addEventListener('submit', function(e) {
            if (userCurrency.code !== 'USD') {
                const displayVal = parseFloat(captureInput.value);
                if (!isNaN(displayVal)) {
                    captureInput.value = (displayVal / userCurrency.rate).toFixed(2);
                }
            }
        });
    }

    // Intercept edit payment form submit: convert display currency back to USD
    const editForm = document.getElementById('editPaymentForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const editAmountInput = document.getElementById('edit_amount');
            if (userCurrency.code !== 'USD' && editAmountInput) {
                const displayVal = parseFloat(editAmountInput.value);
                if (!isNaN(displayVal)) {
                    editAmountInput.value = (displayVal / userCurrency.rate).toFixed(2);
                }
            }
        });
    }

    // Make formatAmount available globally for editPayment function
    window._laybyUserCurrency = userCurrency;

    // Initial conversion on page load
    convertAllAmounts();
})();

function editPayment(paymentId, amount, method, transactionId, note) {
    const curr = window._laybyUserCurrency || { code: 'USD', rate: 1, symbol: '$' };
    const displayAmount = (amount * curr.rate).toFixed(2);

    document.getElementById('edit_payment_id').value = paymentId;
    document.getElementById('edit_amount').value = displayAmount;
    document.getElementById('edit_payment_method').value = method;
    document.getElementById('edit_transaction_id').value = transactionId || '';
    document.getElementById('edit_payment_note').value = note || '';

    // Update form action
    document.getElementById('editPaymentForm').action = '{{ route("admin.layby.show", $application->id) }}'.replace('/{{ $application->id }}', '/{{ $application->id }}/edit-payment/' + paymentId);

    // Trigger conversion display
    const editConv = document.getElementById('editUsdConversion');
    if (editConv && curr.code !== 'USD') {
        editConv.textContent = '≈ $' + parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' USD (stored amount)';
        editConv.style.display = 'block';
    } else if (editConv) {
        editConv.style.display = 'none';
    }

    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
    modal.show();
}
</script>
@endsection

