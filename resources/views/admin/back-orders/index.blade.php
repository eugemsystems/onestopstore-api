@extends('admin.layout')

@section('title', 'Back Order Management - Admin Panel')

@push('styles')
<style>
    .import-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .import-card h3 {
        color: white;
        margin-bottom: 10px;
    }

    .import-card p {
        opacity: 0.9;
        margin-bottom: 0;
    }

    .section {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .section-title {
        font-weight: 600;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #1f2937;
    }

    .upload-section {
        border: 3px dashed #667eea;
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        background: white;
        transition: all 0.3s;
    }

    .upload-section:hover {
        border-color: #764ba2;
        background: #f8f9ff;
    }

    .upload-section.dragover {
        background: #e8ebff;
        border-color: #764ba2;
        transform: scale(1.02);
    }

    .file-input-label {
        display: inline-block;
        padding: 12px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }

    .file-input-label:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    input[type="file"] {
        display: none;
    }

    .file-list {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: white;
    }

    .file-item {
        padding: 15px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: background .15s;
    }

    .file-item:hover {
        background: #f3f4f6;
    }

    .file-item:last-child {
        border-bottom: 0;
    }

    .file-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .file-info {
        flex: 1;
    }

    .file-name {
        font-weight: 600;
        font-size: 14px;
        color: #111827;
        margin-bottom: 4px;
    }

    .file-meta {
        font-size: 12px;
        color: #6b7280;
    }

    .progress-bar {
        width: 100%;
        background: #e5e7eb;
        height: 12px;
        border-radius: 100px;
        overflow: hidden;
        margin: 15px 0;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #22c55e, #16a34a);
        transition: width 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 10px;
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .stat-card {
        padding: 20px;
        background: white;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .stat-value {
        font-size: 2em;
        font-weight: bold;
        color: #667eea;
    }

    .stat-label {
        color: #666;
        margin-top: 5px;
    }

    .spinner {
        display: inline-block;
        width: 40px;
        height: 40px;
        border: 4px solid rgba(102, 126, 234, 0.3);
        border-radius: 50%;
        border-top-color: #667eea;
        animation: spin 0.6s linear infinite;
        margin: 20px auto;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .history-item {
        padding: 15px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin-bottom: 10px;
        background: white;
    }

    .history-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .history-stats {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        font-size: 13px;
        color: #6b7280;
    }

    .history-stats span {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="import-card">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3><i class="bi bi-arrow-repeat me-2"></i>Back Order Management</h3>
                <p>Upload TXT or ZIP files containing SKUs to mark as Back Order. ZIP files will be automatically extracted.</p>
            </div>
        </div>
    </div>

    <!-- Upload Section -->
    <div class="section">
        <div class="section-title">
            <span><i class="bi bi-cloud-upload me-2"></i>Upload Files</span>
        </div>
        <div class="upload-section" id="uploadZone">
            <h5><i class="bi bi-cloud-arrow-up me-2"></i>Drop Files Here or Click to Browse</h5>
            <p style="margin: 15px 0; color: #666;">
                Supports .txt and .zip files (ZIP files will be automatically extracted)
            </p>
            <label for="fileInput" class="file-input-label">
                <i class="bi bi-folder2-open me-2"></i>Choose Files
            </label>
            <input type="file" id="fileInput" accept=".txt,.zip" multiple>
            <div id="uploadStatus" class="mt-3"></div>
        </div>
    </div>

    <!-- Files List Section -->
    <div class="section" id="filesList" style="display: none;">
        <div class="section-title">
            <span><i class="bi bi-files me-2"></i>Uploaded Files</span>
        </div>
        <div class="file-list">
            <div id="filesContainer"></div>
        </div>
        <div style="margin-top: 15px; display: flex; gap: 8px; flex-wrap: wrap;">
            <button class="btn btn-sm btn-success" onclick="selectAll()">
                <i class="bi bi-check-all me-1"></i>Select All
            </button>
            <button class="btn btn-sm btn-secondary" onclick="deselectAll()">
                <i class="bi bi-x-lg me-1"></i>Deselect All
            </button>
            <button class="btn btn-sm btn-primary" onclick="processSelected()" id="processBtn">
                <i class="bi bi-play-fill me-1"></i>Process Selected
            </button>
            <button class="btn btn-sm btn-danger" onclick="deleteSelected()">
                <i class="bi bi-trash me-1"></i>Delete Selected
            </button>
            <button class="btn btn-sm btn-danger" onclick="clearAll()">
                <i class="bi bi-trash3 me-1"></i>Clear All
            </button>
        </div>
    </div>

    <!-- Status Section -->
    <div class="section" id="statusSection" style="display: none;">
        <div class="section-title">
            <span><i class="bi bi-gear me-2"></i>Processing Status</span>
        </div>
        <div class="spinner" id="spinner"></div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressBar" style="width: 0%;">0%</div>
        </div>
        <div class="stats" id="stats"></div>
        <div id="statusMessages"></div>
    </div>

    <!-- History Section -->
    <div class="section" id="historySection" style="display: none;">
        <div class="section-title">
            <span><i class="bi bi-clock-history me-2"></i>Processing History</span>
        </div>
        <div id="historyContainer"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
        let uploadedFiles = [];
        let currentBatchId = null;
        let statusCheckInterval = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadUploadedFiles();
            loadHistory();
            setupDragAndDrop();
        });

        // Setup drag and drop
        function setupDragAndDrop() {
            const uploadZone = document.getElementById('uploadZone');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                uploadZone.addEventListener(eventName, () => {
                    uploadZone.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                uploadZone.addEventListener(eventName, () => {
                    uploadZone.classList.remove('dragover');
                }, false);
            });

            uploadZone.addEventListener('drop', handleDrop, false);
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        // File input change
        document.getElementById('fileInput').addEventListener('change', function(e) {
            handleFiles(e.target.files);
        });

        async function handleFiles(files) {
            for (let file of files) {
                await uploadFile(file);
            }
            loadUploadedFiles();
        }

        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);

            const statusDiv = document.getElementById('uploadStatus');
            statusDiv.innerHTML = `<div class="alert alert-info">Uploading ${file.name}...</div>`;

            try {
                const response = await fetch('/admin/back-orders/upload', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    statusDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    setTimeout(() => statusDiv.innerHTML = '', 3000);
                } else {
                    statusDiv.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                }
            } catch (error) {
                statusDiv.innerHTML = `<div class="alert alert-error">Upload failed: ${error.message}</div>`;
            }
        }

        async function loadUploadedFiles() {
            try {
                const response = await fetch('/admin/back-orders/files');
                const data = await response.json();

                if (data.success && data.files.length > 0) {
                    uploadedFiles = data.files;
                    displayFiles(data.files);
                    document.getElementById('filesList').style.display = 'block';
                } else {
                    document.getElementById('filesList').style.display = 'none';
                }
            } catch (error) {
                console.error('Failed to load files:', error);
            }
        }

        function displayFiles(files) {
            const container = document.getElementById('filesContainer');
            container.innerHTML = files.map((file, index) => `
                <div class="file-item">
                    <input type="checkbox" id="file-${index}" value="${file.path}">
                    <div class="file-info">
                        <div class="file-name">${file.name}</div>
                        <div class="file-meta">
                            Size: ${formatBytes(file.size)} |
                            Uploaded: ${new Date(file.uploaded_at).toLocaleString()}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function selectAll() {
            document.querySelectorAll('#filesContainer input[type="checkbox"]').forEach(cb => {
                cb.checked = true;
            });
        }

        function deselectAll() {
            document.querySelectorAll('#filesContainer input[type="checkbox"]').forEach(cb => {
                cb.checked = false;
            });
        }

        async function processSelected() {
            const selectedFiles = Array.from(
                document.querySelectorAll('#filesContainer input[type="checkbox"]:checked')
            ).map(cb => cb.value);

            if (selectedFiles.length === 0) {
                showWarning('Warning', 'Please select at least one file to process');
                return;
            }

            const _r = await Swal.fire({ title: 'Are you sure?', text: `Process ${selectedFiles.length} file(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (!_r.isConfirmed) return;

            document.getElementById('processBtn').disabled = true;
            document.getElementById('statusSection').style.display = 'block';
            document.getElementById('spinner').style.display = 'inline-block';

            try {
                const response = await fetch('/admin/back-orders/process', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ files: selectedFiles })
                });

                const data = await response.json();

                if (data.success) {
                    currentBatchId = data.batch_id;
                    document.getElementById('statusMessages').innerHTML =
                        `<div class="alert alert-success">${data.message}</div>`;
                    startStatusCheck();
                } else {
                    document.getElementById('statusMessages').innerHTML =
                        `<div class="alert alert-error">${data.message}</div>`;
                    document.getElementById('processBtn').disabled = false;
                }
            } catch (error) {
                document.getElementById('statusMessages').innerHTML =
                    `<div class="alert alert-error">Failed to start processing: ${error.message}</div>`;
                document.getElementById('processBtn').disabled = false;
            }
        }

        function startStatusCheck() {
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
            }

            // Poll every 1 second for faster visual feedback
            statusCheckInterval = setInterval(checkStatus, 1000);
            checkStatus();
        }

        async function checkStatus() {
            if (!currentBatchId) return;

            try {
                const response = await fetch(`/admin/back-orders/status?batch_id=${currentBatchId}`);
                const data = await response.json();

                if (data.success) {
                    updateStatusDisplay(data.status);

                    if (data.status.status === 'completed' || data.status.status === 'failed') {
                        clearInterval(statusCheckInterval);
                        document.getElementById('spinner').style.display = 'none';
                        document.getElementById('processBtn').disabled = false;
                        loadHistory();

                        // Show completion message
                        const message = data.status.status === 'completed'
                            ? '<div class="alert alert-success"><strong>✓ Processing Complete!</strong></div>'
                            : '<div class="alert alert-danger"><strong>✗ Processing Failed</strong></div>';
                        document.getElementById('statusMessages').innerHTML = message;
                    }
                }
            } catch (error) {
                console.error('Status check failed:', error);
            }
        }

        function updateStatusDisplay(status) {
            const processed = status.processed || 0;
            const total = status.total || 0;
            const updated = status.updated || 0;
            const failed = status.failed || 0;

            // Use percentage from backend if available, otherwise calculate
            let progress = status.percentage || 0;
            if (!status.percentage && total > 0) {
                progress = Math.round((processed / total) * 100);
            }

            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('progressBar').textContent = progress + '%';

            const statsHtml = `
                <div class="stat-card">
                    <div class="stat-value">${processed}</div>
                    <div class="stat-label">Processed / ${total}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${updated}</div>
                    <div class="stat-label">Updated</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${failed}</div>
                    <div class="stat-label">Failed</div>
                </div>
            `;

            // Add duration if completed
            if (status.duration_seconds && status.status === 'completed') {
                const minutes = Math.floor(status.duration_seconds / 60);
                const seconds = status.duration_seconds % 60;
                const durationText =
                    minutes > 0
                        ? `${minutes.toFixed(2)}m ${seconds.toFixed(2)}s`
                        : `${seconds.toFixed(2)}s`;


                document.getElementById('stats').innerHTML = statsHtml + `
                    <div class="stat-card">
                        <div class="stat-value">${durationText}</div>
                        <div class="stat-label">Duration</div>
                    </div>
                `;
            } else {
                document.getElementById('stats').innerHTML = statsHtml;
            }
        }

        async function deleteSelected() {
            const selectedFiles = Array.from(
                document.querySelectorAll('#filesContainer input[type="checkbox"]:checked')
            ).map(cb => cb.value);

            if (selectedFiles.length === 0) {
                showWarning('Warning', 'Please select at least one file to delete');
                return;
            }

            const _r = await Swal.fire({ title: 'Are you sure?', text: `Delete ${selectedFiles.length} file(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (!_r.isConfirmed) return;

            try {
                const response = await fetch('/admin/back-orders/delete-files', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ files: selectedFiles })
                });

                const data = await response.json();
                showSuccess('Success', data.message);
                loadUploadedFiles();
            } catch (error) {
                showError('Error', 'Failed to delete files: ' + error.message);
            }
        }

        async function clearAll() {
            const _r = await Swal.fire({ title: 'Are you sure?', text: 'Delete ALL uploaded files?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (!_r.isConfirmed) return;

            try {
                const response = await fetch('/admin/back-orders/clear-all', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                showSuccess('Success', data.message);
                loadUploadedFiles();
            } catch (error) {
                showError('Error', 'Failed to clear files: ' + error.message);
            }
        }

        async function loadHistory() {
            try {
                const response = await fetch('/admin/back-orders/history');
                const data = await response.json();

                if (data.success && data.history.length > 0) {
                    displayHistory(data.history);
                    document.getElementById('historySection').style.display = 'block';
                }
            } catch (error) {
                console.error('Failed to load history:', error);
            }
        }

        function displayHistory(history) {
            const container = document.getElementById('historyContainer');
            container.innerHTML = history.slice(0, 10).map(item => `
                <div class="history-item">
                    <div class="history-header">
                        <strong>${item.batch_id}</strong>
                        <span>${new Date(item.started_at).toLocaleString()}</span>
                    </div>
                    <div class="history-stats">
                        <span>✅ Updated: ${item.updated}</span>
                        <span>❌ Failed: ${item.failed}</span>
                        <span>📁 File: ${item.file_name}</span>
                    </div>
                </div>
            `).join('');
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
</script>
@endpush
