@extends('admin.layout')

@section('title', 'Inventory Receiving Scanner')

@section('content')
<div class="container-fluid">
    <!-- Version Indicator -->
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="bi bi-check-circle-fill"></i> <strong>Hardware Scanner Ready!</strong>
        Updated version with barcode scanner support loaded successfully.
        <small class="text-muted">(v2.0 - {{ now()->format('Y-m-d H:i:s') }})</small>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-box-seam"></i> Inventory Receiving</h2>
            <p class="text-muted mb-0">Scan items to receive at local branch</p>
        </div>
        <a href="{{ route('admin.inventory-shipments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Shipments
        </a>
    </div>

    <div class="row">
        <!-- Scanner Section -->
        <div class="col-lg-5 mb-4">
            <!-- Hardware Barcode Scanner Input - PRIMARY METHOD -->
            <div class="card border-success mb-3" style="border-width: 3px;">
                <div class="card-header" style="background-color: #28a745; color: white;">
                    <h5 class="mb-0"><i class="bi bi-upc-scan"></i> Hardware Barcode Scanner (Primary)</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-lightning-fill"></i> <strong>READY TO SCAN!</strong>
                        <br>Your barcode scanner is ready. Just click below and start scanning.
                    </div>
                    <div class="mb-3">
                        <label for="barcode-input" class="form-label fw-bold">👉 Scan Here:</label>
                        <input type="text" id="barcode-input" class="form-control form-control-lg"
                               placeholder="Click here, then scan barcode..."
                               autocomplete="off"
                               style="font-size: 1.3rem; border: 4px solid #28a745; padding: 20px; background-color: #f0fff4;">
                        <small class="form-text text-success fw-bold">
                            ✓ Click this field → Scan with your device → Auto-processes
                        </small>
                    </div>
                </div>
            </div>

            <!-- Camera QR Scanner - OPTIONAL -->
            <div class="card mb-3">
                <div class="card-header" style="background-color: #6c757d; color: white; cursor: pointer;"
                     data-bs-toggle="collapse" data-bs-target="#camera-scanner-section">
                    <h5 class="mb-0">
                        <i class="bi bi-camera-video"></i> Camera QR Scanner (Optional)
                        <i class="bi bi-chevron-down float-end"></i>
                    </h5>
                </div>
                <div id="camera-scanner-section" class="collapse">
                    <div class="card-body">
                        <div id="scanner-container" class="text-center mb-3">
                            <div id="video-container" style="position: relative; display: none;">
                                <video id="qr-video" style="width: 100%; max-width: 400px; border: 2px solid #062a6a; border-radius: 8px;"></video>
                            </div>
                            <div id="scanner-placeholder" class="alert alert-info">
                                <i class="bi bi-camera"></i>
                                <p class="mb-0">Click "Start Scanner" to begin</p>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button id="start-scanner" class="btn btn-success btn-lg" onclick="startScanner()">
                                <i class="bi bi-play-circle"></i> Start Scanner
                            </button>
                            <button id="stop-scanner" class="btn btn-danger" onclick="stopScanner()" style="display: none;">
                                <i class="bi bi-stop-circle"></i> Stop Scanner
                            </button>
                        </div>

                        <div class="mt-3">
                            <label for="camera-select" class="form-label">Select Camera:</label>
                            <select id="camera-select" class="form-select" onchange="changeCamera()">
                                <option value="">Loading cameras...</option>
                            </select>
                        </div>

                        <!-- Scan Result -->
                        <div id="scan-result" class="mt-3 alert alert-secondary">
                            <i class="bi bi-info-circle"></i> No scan yet
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Entry - ALTERNATIVE -->
            <div class="card">
                <div class="card-header" style="background-color: #6c757d; color: white; cursor: pointer;"
                     data-bs-toggle="collapse" data-bs-target="#manual-entry-section">
                    <h5 class="mb-0">
                        <i class="bi bi-keyboard"></i> Manual Entry (Alternative)
                        <i class="bi bi-chevron-down float-end"></i>
                    </h5>
                </div>
                <div id="manual-entry-section" class="collapse">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="manual-qr-data" class="form-label">Paste QR Code Data:</label>
                            <textarea id="manual-qr-data" class="form-control" rows="3" placeholder="Paste the QR code data here..."></textarea>
                        </div>
                        <button onclick="processManualEntry()" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Process QR Code
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scanned Items List -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header" style="background-color: #062a6a; color: white;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="mb-0"><i class="bi bi-list-check"></i> Scanned Items (<span id="items-count">{{ count($scannedItems) }}</span>)</h5>
                            @if(count($scannedItems) > 0)
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" id="select-all" onchange="toggleSelectAll()">
                                    <label class="form-check-label" for="select-all">Select All</label>
                                </div>
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            @if(count($scannedItems) > 0)
                                <button class="btn btn-sm btn-outline-light" id="bulk-delete-btn" onclick="bulkDeleteItems()" style="display: none;">
                                    <i class="bi bi-trash"></i> Delete Selected (<span id="selected-count">0</span>)
                                </button>
                            @endif
                            @if($isAdmin ?? false)
                                <button class="btn btn-sm btn-outline-light" onclick="clearList()">
                                    <i class="bi bi-trash"></i> Clear All (Admin)
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Branch Filter Tabs -->
                <div class="card-header bg-white border-bottom">
                    <ul class="nav nav-pills" id="branch-tabs" role="tablist">
                        @php
                            $userBranch = auth()->user()->branch ?? 'None';
                            $branchColors = [
                                'None' => 'bg-secondary',
                                'Harare' => 'bg-primary',
                                'Bulawayo' => 'bg-success',
                                'Mutare' => 'bg-warning text-dark',
                                'Zambia' => 'bg-danger',
                            ];
                        @endphp
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="branch-{{ $userBranch }}-tab" data-bs-toggle="pill"
                                    data-bs-target="#branch-{{ $userBranch }}" type="button" role="tab"
                                    onclick="filterByBranch('{{ $userBranch }}')">
                                <span class="badge {{ $branchColors[$userBranch] ?? 'bg-secondary' }}">
                                    {{ $userBranch }}
                                </span>
                                (<span class="branch-count-{{ $userBranch }}">0</span>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="branch-all-tab" data-bs-toggle="pill"
                                    data-bs-target="#branch-all" type="button" role="tab"
                                    onclick="filterByBranch('all')">
                                All Branches (<span class="branch-count-all">0</span>)
                            </button>
                        </li>
                        @foreach(['None', 'Harare', 'Bulawayo', 'Mutare', 'Zambia'] as $branch)
                            @if($branch !== $userBranch)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="branch-{{ $branch }}-tab" data-bs-toggle="pill"
                                        data-bs-target="#branch-{{ $branch }}" type="button" role="tab"
                                        onclick="filterByBranch('{{ $branch }}')">
                                    <span class="badge {{ $branchColors[$branch] ?? 'bg-secondary' }}">{{ $branch }}</span>
                                    (<span class="branch-count-{{ $branch }}">0</span>)
                                </button>
                            </li>
                            @endif
                        @endforeach
                    </ul>
                </div>

                <div class="card-body">
                    <div id="scanned-items-list">
                        @if(count($scannedItems) > 0)
                            @foreach($scannedItems as $shipmentId => $item)
                            <div class="scanned-item mb-3 p-3 border rounded" data-shipment-id="{{ $shipmentId }}" data-branch="{{ $item['user_branch'] ?? 'None' }}">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-start gap-2 flex-grow-1">
                                        <div class="form-check mt-1">
                                            <input class="form-check-input item-checkbox" type="checkbox" value="{{ $shipmentId }}" onchange="updateSelectionCount()">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <h6 class="mb-0">{{ $item['product_name'] }}</h6>
                                                @php
                                                    $branchColors = [
                                                        'None' => 'bg-secondary',
                                                        'Harare' => 'bg-primary',
                                                        'Bulawayo' => 'bg-success',
                                                        'Mutare' => 'bg-warning text-dark',
                                                        'Zambia' => 'bg-danger',
                                                    ];
                                                    $branch = $item['user_branch'] ?? 'None';
                                                    $badgeClass = $branchColors[$branch] ?? 'bg-secondary';
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ $branch }}</span>
                                            </div>
                                            <small class="text-muted">
                                                <strong>Order:</strong> {{ $item['order_number'] ?? 'N/A' }} |
                                                <strong>Qty:</strong> {{ number_format($item['quantity']) }} |
                                                <strong>Destination:</strong> {{ $item['destination'] }}
                                            </small>
                                            <div>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <strong>Scanned by:</strong> {{ $item['user_name'] ?? 'Unknown' }} |
                                                    <i class="bi bi-clock"></i> {{ $item['scanned_at'] }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-danger" onclick="removeItem({{ $shipmentId }})">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div id="empty-message" class="text-center text-muted py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-3">No items scanned yet.<br>Start scanning to add items to receive.</p>
                            </div>
                        @endif
                    </div>

                    @if(count($scannedItems) > 0)
                    <div class="mt-4">
                        <button class="btn btn-primary btn-lg w-100" onclick="saveReceiving()">
                            <i class="bi bi-check-circle"></i> Save & Receive All Items
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include QR Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>

let html5QrcodeScanner = null;
let scanning = false;
let barcodeBuffer = '';
let barcodeTimeout = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCameras();
    setupBarcodeScanner();

    // Auto-focus barcode input for immediate scanning
    const barcodeInput = document.getElementById('barcode-input');
    if (barcodeInput) {
        barcodeInput.focus();
    } else {
        console.error('ERROR: Barcode input element not found!');
    }
});

// Setup hardware barcode scanner
function setupBarcodeScanner() {
    const barcodeInput = document.getElementById('barcode-input');

    if (barcodeInput) {

        // Handle Enter key (most barcode scanners send Enter after scanning)
        barcodeInput.addEventListener('keypress', function(e) {

            if (e.key === 'Enter') {
                e.preventDefault();
                const scannedData = barcodeInput.value.trim();

                if (scannedData) {
                    processQRCode(scannedData);
                    barcodeInput.value = ''; // Clear for next scan
                }
            }
        });

        // Auto-process after brief pause (in case scanner doesn't send Enter)
        barcodeInput.addEventListener('input', function(e) {
            clearTimeout(barcodeTimeout);

            barcodeTimeout = setTimeout(() => {
                const scannedData = barcodeInput.value.trim();

                if (scannedData.length > 10) { // Minimum QR code length
                    processQRCode(scannedData);
                    barcodeInput.value = ''; // Clear for next scan
                }
            }, 300); // Wait 300ms after last character
        });

        // Keep input focused for continuous scanning
        barcodeInput.addEventListener('blur', function() {
            setTimeout(() => {
                const active = document.activeElement;
                const exempt = ['manual-qr-data', 'history-branch', 'history-date'];
                const isExempt = exempt.some(id => active === document.getElementById(id))
                              || (active && active.closest('#history-section'));
                if (!isExempt) {
                    barcodeInput.focus();
                }
            }, 100);
        });
    }
}

// Load available cameras
async function loadCameras() {
    try {
        const devices = await Html5Qrcode.getCameras();
        const cameraSelect = document.getElementById('camera-select');
        cameraSelect.innerHTML = '';

        if (devices && devices.length > 0) {
            devices.forEach((device, index) => {
                const option = document.createElement('option');
                option.value = device.id;
                option.text = device.label || `Camera ${index + 1}`;
                cameraSelect.appendChild(option);
            });
        } else {
            cameraSelect.innerHTML = '<option value="">No cameras found - Use barcode scanner below instead</option>';
        }
    } catch (err) {
        console.error('Error loading cameras:', err);
        document.getElementById('camera-select').innerHTML = '<option value="">No cameras available - Use barcode scanner below</option>';
    }
}

// Start QR scanner
async function startScanner() {
    const cameraSelect = document.getElementById('camera-select');
    const cameraId = cameraSelect.value;

    if (!cameraId) {
        showWarning('No Camera', 'Please select a camera first');
        return;
    }

    try {
        html5QrcodeScanner = new Html5Qrcode("video-container");

        await html5QrcodeScanner.start(
            cameraId,
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            onScanSuccess,
            onScanError
        );

        scanning = true;
        document.getElementById('scanner-placeholder').style.display = 'none';
        document.getElementById('video-container').style.display = 'block';
        document.getElementById('start-scanner').style.display = 'none';
        document.getElementById('stop-scanner').style.display = 'block';

    } catch (err) {
        console.error('Error starting scanner:', err);
        showError('Scanner Error', 'Failed to start scanner: ' + err.message);
    }
}

// Stop QR scanner
async function stopScanner() {
    if (html5QrcodeScanner && scanning) {
        try {
            await html5QrcodeScanner.stop();
            html5QrcodeScanner.clear();

            scanning = false;
            document.getElementById('scanner-placeholder').style.display = 'block';
            document.getElementById('video-container').style.display = 'none';
            document.getElementById('start-scanner').style.display = 'block';
            document.getElementById('stop-scanner').style.display = 'none';
        } catch (err) {
            console.error('Error stopping scanner:', err);
        }
    }
}

// Change camera
async function changeCamera() {
    if (scanning) {
        await stopScanner();
        setTimeout(startScanner, 500);
    }
}

// Handle successful scan
function onScanSuccess(decodedText, decodedResult) {
    processQRCode(decodedText);
}

// Handle scan error (can be ignored for continuous scanning)
function onScanError(error) {
    // Silently ignore errors during continuous scanning
}

// Process manual entry
function processManualEntry() {
    const qrData = document.getElementById('manual-qr-data').value.trim();

    if (!qrData) {
        showWarning('Empty Input', 'Please enter QR code data');
        return;
    }

    // Clear the textarea
    document.getElementById('manual-qr-data').value = '';

    // Process the QR code
    processQRCode(qrData);
}

// Process scanned QR code
async function processQRCode(qrData) {
    const resultDiv = document.getElementById('scan-result');
    resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Processing...';

    try {

        // Trim whitespace
        qrData = qrData.trim();

        // Note: Order collection QR codes should be scanned at /admin/orders/qr-scanner
        // This page is only for inventory shipment receiving

        // Process as inventory receiving QR code
        const response = await fetch('{{ route("admin.inventory-receiving.scan") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ qr_data: qrData })
        });

        const data = await response.json();

        if (data.success) {
            // Success - add to list
            resultDiv.innerHTML = `
                <div class="alert alert-success mb-0">
                    <h6><i class="bi bi-check-circle"></i> Item Added!</h6>
                    <p class="mb-0">${data.item.product_name}</p>
                    <small>Total items: ${data.total_items}</small>
                </div>
            `;

            // Reload page to show updated list
            window.location.reload();

            playSound('success');
        } else if (data.already_scanned || data.already_in_list) {
            // Already scanned/in list
            resultDiv.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <h6><i class="bi bi-exclamation-triangle"></i> ${data.message}</h6>
                </div>
            `;
            playSound('warning');
        } else {
            // Error
            resultDiv.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <h6><i class="bi bi-x-circle"></i> Error</h6>
                    <p class="mb-0">${data.message}</p>
                </div>
            `;
            playSound('error');
        }
    } catch (error) {
        console.error('Error processing QR code:', error);
        resultDiv.innerHTML = `
            <div class="alert alert-danger mb-0">
                <h6><i class="bi bi-x-circle"></i> Error</h6>
                <p class="mb-0">Failed to process QR code</p>
            </div>
        `;
        playSound('error');
    }
}

// Remove item from list
async function removeItem(shipmentId) {
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Remove this item from the list?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    try {
        const response = await fetch(`{{ route("admin.inventory-receiving.index") }}/${shipmentId}/remove`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success) {
            // Reload page
            window.location.reload();
        }
    } catch (error) {
        console.error('Error removing item:', error);
        showError('Error', 'Failed to remove item');
    }
}

// Clear all items
async function clearList() {
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Clear all scanned items?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    try {
        const response = await fetch('{{ route("admin.inventory-receiving.clear") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success) {
            window.location.reload();
        }
    } catch (error) {
        console.error('Error clearing list:', error);
        showError('Error', 'Failed to clear list');
    }
}

// Save all received items
async function saveReceiving() {
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Save and receive all scanned items? This will update inventory and order statuses.', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

    try {
        const response = await fetch('{{ route("admin.inventory-receiving.save") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success) {
            showSuccess('Success', data.message);
            window.location.reload();
        } else {
            showError('Error', data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Save & Receive All Items';
        }
    } catch (error) {
        console.error('Error saving receiving:', error);
        showError('Error', 'Failed to save receiving');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Save & Receive All Items';
    }
}

// Play sound feedback
function playSound(type) {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);

    if (type === 'success') {
        oscillator.frequency.value = 800;
        gainNode.gain.value = 0.3;
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.2);
    } else if (type === 'warning') {
        oscillator.frequency.value = 600;
        gainNode.gain.value = 0.2;
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.3);
    } else if (type === 'error') {
        oscillator.frequency.value = 400;
        gainNode.gain.value = 0.3;
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.4);
    }
}

// Filter items by branch
function filterByBranch(branch) {
    const items = document.querySelectorAll('.scanned-item');

    items.forEach(item => {
        const itemBranch = item.getAttribute('data-branch');

        if (branch === 'all') {
            item.style.display = 'block';
        } else {
            if (itemBranch === branch) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        }
    });

    // Update selection count after filtering
    updateSelectionCount();
}

// Update branch counts
function updateBranchCounts() {
    const branches = ['None', 'Harare', 'Bulawayo', 'Mutare', 'Zambia'];
    const items = document.querySelectorAll('.scanned-item');
    const counts = {};
    let totalCount = 0;

    // Initialize counts
    branches.forEach(branch => {
        counts[branch] = 0;
    });

    // Count items per branch
    items.forEach(item => {
        const branch = item.getAttribute('data-branch');
        if (counts[branch] !== undefined) {
            counts[branch]++;
        }
        totalCount++;
    });

    // Update display
    branches.forEach(branch => {
        const countElement = document.querySelector(`.branch-count-${branch}`);
        if (countElement) {
            countElement.textContent = counts[branch];
        }
    });

    // Update all branches count
    const allCountElement = document.querySelector('.branch-count-all');
    if (allCountElement) {
        allCountElement.textContent = totalCount;
    }

    // Update main items count
    document.getElementById('items-count').textContent = totalCount;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateBranchCounts();
    // Filter to user's branch by default
    filterByBranch('{{ auth()->user()->branch ?? "None" }}');
});

// Toggle select all checkboxes
function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const visibleCheckboxes = Array.from(checkboxes).filter(cb => {
        const item = cb.closest('.scanned-item');
        return item && item.style.display !== 'none';
    });

    visibleCheckboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });

    updateSelectionCount();
}

// Update selection count and show/hide bulk delete button
function updateSelectionCount() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const count = checkboxes.length;

    const selectedCountSpan = document.getElementById('selected-count');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');

    if (selectedCountSpan) {
        selectedCountSpan.textContent = count;
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.style.display = count > 0 ? 'block' : 'none';
    }

    // Update select-all checkbox state
    const allCheckboxes = document.querySelectorAll('.item-checkbox');
    const visibleCheckboxes = Array.from(allCheckboxes).filter(cb => {
        const item = cb.closest('.scanned-item');
        return item && item.style.display !== 'none';
    });
    const selectAll = document.getElementById('select-all');
    if (selectAll && visibleCheckboxes.length > 0) {
        const allChecked = visibleCheckboxes.every(cb => cb.checked);
        selectAll.checked = allChecked;
    }
}

// Bulk delete selected items
async function bulkDeleteItems() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const shipmentIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    if (shipmentIds.length === 0) {
        showWarning('No Selection', 'Please select items to delete');
        return;
    }

    const _r = await Swal.fire({ title: 'Are you sure?', text: `Delete ${shipmentIds.length} selected item(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    try {
        const response = await fetch('{{ route("admin.inventory-receiving.bulk-delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ shipment_ids: shipmentIds })
        });

        const data = await response.json();

        if (data.success) {
            // Reload page to show updated list
            window.location.reload();
        } else {
            showError('Error', 'Failed to delete items: ' + data.message);
        }
    } catch (error) {
        console.error('Error deleting items:', error);
        showError('Error', 'Failed to delete items');
    }
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (scanning) {
        stopScanner();
    }
});

// ==========================================
// SCAN HISTORY
// ==========================================

const BRANCH_COLORS = {
    'None':     'bg-secondary',
    'Harare':   'bg-primary',
    'Bulawayo': 'bg-success',
    'Mutare':   'bg-warning text-dark',
    'Zambia':   'bg-danger',
};

let historyCurrentPage = 1;

async function loadScanHistory(page) {
    page = page || 1;
    historyCurrentPage = page;

    const branch  = document.getElementById('history-branch').value;
    const date    = document.getElementById('history-date').value;
    const tbody   = document.getElementById('history-tbody');
    const summary = document.getElementById('history-summary');
    const emptyMsg = document.getElementById('history-empty');
    const paginationEl = document.getElementById('history-pagination');

    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';
    emptyMsg.style.display = 'none';
    paginationEl.innerHTML = '';

    const params = new URLSearchParams();
    if (branch) params.set('branch', branch);
    if (date)   params.set('date', date);
    params.set('page', page);

    try {
        const resp = await fetch('{{ route("admin.inventory-receiving.history") }}?' + params.toString(), {
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const data = await resp.json();

        if (!data.success || data.data.length === 0) {
            tbody.innerHTML = '';
            emptyMsg.style.display = 'block';
            summary.textContent = '0 records found';
            return;
        }

        summary.textContent = `${data.total} record(s) — page ${data.current_page} of ${data.last_page}`;

        tbody.innerHTML = data.data.map(row => {
            const badgeClass = BRANCH_COLORS[row.branch] || 'bg-secondary';

            // Inventory status badge (from inventory_shipments via shipment_id)
            const invStatus = row.inventory_status;
            const invColor  = invStatus === 'Received' ? '#198754'
                            : invStatus === 'Not yet'  ? '#b45309'
                            : '#6c757d';
            const badges = invStatus
                ? `<div class="mt-1"><small class="text-muted" style="font-size:0.7rem;">Inventory Status:</small> <span class="badge" style="background-color:${invColor};font-size:0.7rem;">${invStatus}</span></div>`
                : '';

            return `<tr>
                <td>
                    ${row.product_name}
                    <div class="mt-1">
                        <small class="text-muted">Qty: ${row.quantity} &bull; ${row.destination}</small><br>
                        <span class="badge ${badgeClass}" style="font-size:0.7rem;">${row.branch}</span>
                    </div>
                </td>
                <td>
                    <a href="{{ url('/admin/orders') }}/${row.order_number}" target="_blank" class="fw-semibold text-decoration-none">
                        <small>${row.order_number}</small>
                    </a>
                    ${badges}
                </td>
                <td><small>${row.scanned_by}</small></td>
                <td>
                    <small class="d-block text-muted">Scanned: ${row.scanned_at}</small>
                    <small class="d-block text-muted">Saved: ${row.saved_at}</small>
                </td>
            </tr>`;
        }).join('');

        // Render pagination
        if (data.last_page > 1) {
            renderPagination(data.current_page, data.last_page);
        }
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Failed to load history</td></tr>';
        console.error('Scan history load error:', err);
    }
}

function renderPagination(current, last) {
    const el = document.getElementById('history-pagination');
    let html = '<ul class="pagination pagination-sm mb-0 justify-content-end">';

    // Prev
    html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadScanHistory(${current - 1});return false;">&laquo;</a>
    </li>`;

    // Page numbers (show window of 5 around current)
    const start = Math.max(1, current - 2);
    const end   = Math.min(last, current + 2);
    if (start > 1) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
    for (let p = start; p <= end; p++) {
        html += `<li class="page-item ${p === current ? 'active' : ''}">
            <a class="page-link" href="#" onclick="loadScanHistory(${p});return false;">${p}</a>
        </li>`;
    }
    if (end < last) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;

    // Next
    html += `<li class="page-item ${current === last ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadScanHistory(${current + 1});return false;">&raquo;</a>
    </li>`;

    html += '</ul>';
    el.innerHTML = html;
}

// Reset to page 1 on new search
function searchHistory() {
    historyCurrentPage = 1;
    loadScanHistory(1);
}

// Load all records on page ready (no filters pre-applied)
document.addEventListener('DOMContentLoaded', function() {
    loadScanHistory(1);
});
</script>

<style>
#video-container {
    background: #000;
    border-radius: 8px;
    padding: 10px;
}

.scanned-item {
    background: #f8f9fa;
    transition: all 0.2s;
}

.scanned-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

#barcode-input {
    transition: all 0.2s;
}

#barcode-input:focus {
    border-color: #28a745 !important;
    box-shadow: 0 0 10px rgba(40, 167, 69, 0.5) !important;
    background-color: #f0fff4 !important;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Scan history table */
#history-table th {
    font-size: 0.8rem;
    white-space: nowrap;
}
#history-table td {
    vertical-align: middle;
}
</style>

{{-- ===================== SCAN HISTORY ===================== --}}
<div id="history-section" class="container-fluid mt-4">
    <div class="card shadow-sm">
        <div class="card-header" style="background-color: #062a6a; color: white;">
            <i class="bi bi-clock-history"></i>
            <h5 class="mb-0">Scan History</h5>
            <small class="ms-2 opacity-75">View previously saved scan sessions by branch &amp; date</small>
        </div>

        {{-- Filter bar --}}
        <div class="card-body border-bottom bg-light py-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4 col-md-3">
                    <label class="form-label mb-1 fw-semibold small">Branch</label>
                    <select id="history-branch" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        <option value="Harare">Harare</option>
                        <option value="Bulawayo">Bulawayo</option>
                        <option value="Mutare">Mutare</option>
                        <option value="Zambia">Zambia</option>
                        <option value="None">None</option>
                    </select>
                </div>
                <div class="col-sm-4 col-md-3">
                    <label class="form-label mb-1 fw-semibold small">Date</label>
                    <input type="date" id="history-date" class="form-control form-control-sm">
                </div>
                <div class="col-sm-4 col-md-2">
                    <button class="btn btn-primary btn-sm w-100" onclick="searchHistory()">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
                <div class="col-sm-12 col-md-4 text-md-end">
                    <span id="history-summary" class="text-muted small"></span>
                </div>
            </div>
        </div>

        {{-- Results table --}}
        <div class="card-body p-0">
            <div id="history-empty" class="text-center text-muted py-5" style="display:none;">
                <i class="bi bi-inbox" style="font-size:2rem;"></i>
                <p class="mt-2 mb-0">No scan history found for the selected filters.</p>
            </div>
            <div class="table-responsive">
                <table id="history-table" class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Order #</th>
                            <th>Scanned By</th>
                            <th>Timestamps</th>
                        </tr>
                    </thead>
                    <tbody id="history-tbody">
                        <tr><td colspan="4" class="text-center text-muted py-3">
                            <span class="spinner-border spinner-border-sm"></span> Loading...
                        </td></tr>
                    </tbody>
                </table>
            </div>
            {{-- Pagination --}}
            <div class="card-footer d-flex justify-content-end py-2">
                <div id="history-pagination"></div>
            </div>
        </div>
    </div>
</div>

@endsection
