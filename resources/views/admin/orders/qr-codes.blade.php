@extends('admin.layout')

@section('title', 'QR Codes - Order #' . $order->order_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-qr-code"></i> QR Codes - Order #{{ $order->order_number }}</h2>
    <div>
        <button onclick="window.print()" class="btn btn-primary me-2">
            <i class="bi bi-printer"></i> Print All
        </button>
        <a href="{{ route('admin.orders.show', $order->order_number) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Order
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card">
    <div class="card-header" style="background-color: #062a6a; color: white;">
        <h5 class="mb-0"><i class="bi bi-box-seam"></i> Order Items with QR Codes</h5>
    </div>
    <div class="card-body">
        @if(count($orderItems) === 0)
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No items found in this order.
            </div>
        @else
            <div class="row">
                @foreach($orderItems as $item)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card qr-code-card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-truncate" title="{{ $item['product_name'] }}">
                                    {{ $item['product_name'] }}
                                </h6>
                                <small class="text-muted">SKU: {{ $item['product_sku'] }}</small>
                            </div>
                            <div class="card-body text-center">
                                @if($item['has_qr_code'])
                                    <div class="qr-code-container mb-3">
                                        <img src="data:image/png;base64,{{ $item['qr_code'] }}" 
                                             alt="QR Code" 
                                             class="qr-code-image"
                                             style="max-width: 250px; width: 100%; height: auto;">
                                    </div>
                                    
                                    <div class="item-details mb-3">
                                        <p class="mb-1">
                                            <strong>Order #:</strong> {{ $order->order_number }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>Quantity:</strong> {{ $item['quantity'] }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>Status:</strong> 
                                            <span class="badge bg-{{ $item['item_status'] === 'arrived at local branch' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($item['item_status']) }}
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <div class="btn-group w-100" role="group">
                                        <a href="{{ route('admin.orders.qr-codes.download', [$order->order_number, $item['pivot_id']]) }}" 
                                           class="btn btn-sm btn-outline-primary"
                                           download>
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                        <button onclick="printQRCode('qr-{{ $item['pivot_id'] }}')" 
                                                class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-printer"></i> Print
                                        </button>
                                    </div>
                                    
                                    <!-- Hidden printable version -->
                                    <div id="qr-{{ $item['pivot_id'] }}" class="print-only" style="display: none;">
                                        <div class="text-center" style="padding: 20px;">
                                            <h4>{{ $item['product_name'] }}</h4>
                                            <p><strong>SKU:</strong> {{ $item['product_sku'] }}</p>
                                            <p><strong>Order #:</strong> {{ $order->order_number }}</p>
                                            <p><strong>Quantity:</strong> {{ $item['quantity'] }}</p>
                                            <img src="data:image/png;base64,{{ $item['qr_code'] }}" 
                                                 alt="QR Code" 
                                                 style="max-width: 300px; width: 100%;">
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i> QR Code not generated
                                    </div>
                                    <button onclick="generateQRCode()" class="btn btn-primary btn-sm">
                                        <i class="bi bi-qr-code"></i> Generate QR Codes
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<style>
    .qr-code-card {
        height: 100%;
        transition: transform 0.2s;
    }
    
    .qr-code-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .qr-code-container {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
    }
    
    .qr-code-image {
        border: 2px solid #dee2e6;
        border-radius: 4px;
    }
    
    @media print {
        body * {
            visibility: hidden;
        }
        .print-only, .print-only * {
            visibility: visible !important;
            display: block !important;
        }
        .print-only {
            position: absolute;
            left: 0;
            top: 0;
        }
    }
</style>

<script>
    function printQRCode(elementId) {
        const element = document.getElementById(elementId);
        const originalDisplay = element.style.display;
        element.style.display = 'block';
        window.print();
        element.style.display = originalDisplay;
    }
    
    async function generateQRCode() {
        const _r = await Swal.fire({ title: 'Are you sure?', text: 'Generate QR codes for all items in this order?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
        if (_r.isConfirmed) {
            fetch('{{ route("admin.orders.qr-codes.generate", $order->order_number) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Success', data.message);
                    window.location.reload();
                } else {
                    showError('Error', 'Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error', 'Failed to generate QR codes');
            });
        }
    }
</script>
@endsection
