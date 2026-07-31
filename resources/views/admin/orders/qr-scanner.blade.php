@extends('admin.layout')

@section('title', 'QR Code Scanner')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-upc-scan"></i> QR Code Scanner</h2>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Orders
    </a>
</div>

<div class="row">
    <!-- Hardware Scanner Section (Primary for Order Collection) -->
    <div class="col-lg-12 mb-4">
        <div class="card border-success" style="border-width: 3px;">
            <div class="card-header text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                <h4 class="mb-0">
                    <i class="bi bi-upc-scan"></i> Hardware Barcode Scanner (Primary)
                    <span class="badge bg-light text-success ms-2">Ready!</span>
                </h4>
                <small>For scanning order collection QR codes from customer emails</small>
            </div>
            <div class="card-body bg-light">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <label for="barcode-input" class="form-label fw-bold">
                            <i class="bi bi-qr-code-scan"></i> Click here and scan QR code with your hardware scanner:
                        </label>
                        <input
                            type="text"
                            id="barcode-input"
                            class="form-control form-control-lg"
                            placeholder="Click here, then scan QR code..."
                            autocomplete="off"
                            style="border: 3px solid #28a745; font-size: 1.1rem; padding: 15px;">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            The scanner will automatically detect and process order collection QR codes
                        </small>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="p-3 bg-white rounded border border-success">
                            <i class="bi bi-phone" style="font-size: 3rem; color: #28a745;"></i>
                            <p class="mb-0 mt-2"><strong>Customer shows email QR code</strong></p>
                            <p class="text-muted small mb-0">Staff scans with hardware scanner</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Scan Results and History -->
    <div class="col-lg-6">
        <!-- Current Scan Result -->
        <div class="card mb-3">
            <div class="card-header" style="background-color: #062a6a; color: white;">
                <h5 class="mb-0"><i class="bi bi-file-check"></i> Scan Result</h5>
            </div>
            <div class="card-body">
                <div id="scan-result" class="alert alert-secondary">
                    <i class="bi bi-info-circle"></i> No scan yet. Scan a QR code to see results here.
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
let scanHistory = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCameras();
    loadScanHistory();
    setupBarcodeScanner();

    // Auto-focus barcode input for immediate scanning
    const barcodeInput = document.getElementById('barcode-input');
    if (barcodeInput) {
        barcodeInput.focus();
    }
});

// Setup hardware barcode scanner
let barcodeTimeout = null;

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

        // Keep focus on input (re-focus if lost)
        barcodeInput.addEventListener('blur', function() {
            setTimeout(() => barcodeInput.focus(), 100);
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
            cameraSelect.innerHTML = '<option value="">No cameras found</option>';
        }
    } catch (err) {
        console.error('Error loading cameras:', err);
        document.getElementById('camera-select').innerHTML = '<option value="">Error loading cameras</option>';
    }
}

// Start QR scanner
async function startScanner() {
    const cameraSelect = document.getElementById('camera-select');
    const cameraId = cameraSelect.value;

    if (!cameraId) {
        showWarning('No Camera', 'Please select a camera first.');
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
async function onScanSuccess(decodedText, decodedResult) {


    // Stop scanner immediately to prevent multiple scans
    if (html5QrcodeScanner && scanning) {
        try {
            await html5QrcodeScanner.stop();
            scanning = false;
        } catch (err) {
            console.error('Error stopping scanner:', err);
        }
    }

    // Process the QR code
    await processQRCode(decodedText);
}

// Handle scan error (can be ignored for continuous scanning)
function onScanError(error) {
    // Silently ignore errors during continuous scanning
}

// Process scanned QR code
async function processQRCode(qrData) {
    const resultDiv = document.getElementById('scan-result');
    resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Processing...</span></div> Processing...';

    // Trim whitespace
    qrData = qrData.trim();

    try {
        // FIRST: Check if it's a URL with collection-scan (most common for new QR codes)
        const isCollectionScanUrl = qrData.includes('/collection-scan/') ||
                                    qrData.includes('collection-scan');


        if (isCollectionScanUrl) {

            let redirectUrl = qrData;

            // If it's a full URL starting with http/https, use it directly
            if (qrData.startsWith('http://') || qrData.startsWith('https://')) {
                redirectUrl = qrData;
            }
            // If it's a relative path, construct full URL
            else if (qrData.startsWith('/')) {
                redirectUrl = window.location.origin + qrData;
            }
            // If it's just a partial path, add the base
            else {
                redirectUrl = window.location.origin + '/' + qrData;
            }

            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <h5><i class="bi bi-box-arrow-up-right"></i> Collection URL Detected!</h5>
                    <p class="mb-1"><strong>Redirecting now...</strong></p>
                    <p class="mb-0"><small class="text-muted">${redirectUrl}</small></p>
                </div>
            `;

            // Play success sound
            playSound('success');


            // Use immediate redirect without timeout
            window.location.href = redirectUrl;

            return;
        }

        // SECOND: Try to parse as JSON (old format)
        let jsonData = null;
        try {
            jsonData = JSON.parse(qrData);
        } catch (e) {
            console.log('Not JSON format');
        }

        // Check if it's JSON with order collection type
        if (jsonData && (jsonData.type === 'order_collection' || jsonData.type === 'order_delivery')) {

            // Build the collection scan URL
            const collectionUrl = window.location.origin + '/admin/orders/collection-scan/' +
                                  jsonData.order_number +
                                  '?auto_collect=' + (jsonData.auto_collect ? 'true' : 'false');

            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <h5><i class="bi bi-box-arrow-up-right"></i> Order QR Code Scanned!</h5>
                    <hr>
                    <p class="mb-1"><strong>Order Number:</strong> ${jsonData.order_number}</p>
                    <p class="mb-1"><strong>Customer:</strong> ${jsonData.customer_name || 'N/A'}</p>
                    <p class="mb-0"><strong>Action:</strong> ${jsonData.auto_collect ? 'Opening and marking as collected...' : 'Opening order...'}</p>
                </div>
            `;

            // Play success sound
            playSound('success');

            // Redirect IMMEDIATELY
            window.location.href = collectionUrl;

            return;
        }


        resultDiv.innerHTML = `
            <div class="alert alert-warning">
                <h5><i class="bi bi-exclamation-triangle"></i> Invalid QR Code</h5>
                <p class="mb-2">This QR code is not a valid order collection code.</p>
                <details class="mt-2">
                    <summary class="text-muted" style="cursor: pointer;">Show scanned data</summary>
                    <pre class="mt-2 mb-0 p-2 bg-light rounded" style="font-size: 0.8rem; max-height: 150px; overflow: auto;">${qrData}</pre>
                </details>
                <small class="text-muted d-block mt-2">Expected: URL containing '/collection-scan/' or JSON with type 'order_collection'</small>
            </div>
        `;
        playSound('warning');

    } catch (error) {


        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <h5><i class="bi bi-x-circle"></i> Processing Error</h5>
                <p class="mb-2">Failed to process QR code.</p>
                <details class="mt-2">
                    <summary class="text-muted" style="cursor: pointer;">Show error details</summary>
                    <div class="mt-2">
                        <p class="mb-1"><strong>Error:</strong> ${error.message}</p>
                        <pre class="mb-0 p-2 bg-light rounded" style="font-size: 0.8rem; max-height: 150px; overflow: auto;">${qrData}</pre>
                    </div>
                </details>
                <small class="text-muted d-block mt-2">Check browser console (F12) for more details</small>
            </div>
        `;
        playSound('error');
    }
}

// Process manual entry
function processManualEntry() {
    const qrData = document.getElementById('manual-qr-data').value.trim();

    if (!qrData) {
        showWarning('No Data', 'Please enter QR code data.');
        return;
    }

    processQRCode(qrData);
}

// Load scan history
async function loadScanHistory() {
    try {
        const response = await fetch('{{ route("admin.orders.qr-scanner.history") }}');
        const data = await response.json();

        if (data.success && data.history.length > 0) {
            const historyDiv = document.getElementById('scan-history');
            historyDiv.innerHTML = '';

            data.history.forEach(item => {
                const timeAgo = formatTimeAgo(item.scanned_at);
                const historyItem = `
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${item.product_name}</h6>
                            <small class="text-muted">${timeAgo}</small>
                        </div>
                        <p class="mb-1"><small>Order #${item.order_number} • SKU: ${item.product_sku}</small></p>
                        <small class="text-success"><i class="bi bi-check-circle"></i> Arrived at local branch</small>
                    </div>
                `;
                historyDiv.innerHTML += historyItem;
            });
        } else {
            document.getElementById('scan-history').innerHTML = '<div class="text-center text-muted"><i class="bi bi-inbox"></i> No scan history yet</div>';
        }
    } catch (error) {
        console.error('Error loading scan history:', error);
    }
}

// Format time ago
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' min ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hr ago';
    return Math.floor(seconds / 86400) + ' days ago';
}

// Play sound feedback
function playSound(type) {
    // Create audio context for beep sounds
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

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (scanning) {
        stopScanner();
    }
});
</script>

<style>
    #video-container {
        background: #000;
        border-radius: 8px;
        padding: 10px;
    }

    .list-group-item {
        transition: background-color 0.2s;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection
