<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Back Order Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .content {
            padding: 30px;
        }

        .upload-section {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            background: #f8f9ff;
            transition: all 0.3s;
        }

        .upload-section:hover {
            border-color: #764ba2;
            background: #f0f2ff;
        }

        .upload-section.dragover {
            background: #e8ebff;
            border-color: #764ba2;
            transform: scale(1.02);
        }

        .file-input-label {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1.1em;
            transition: all 0.3s;
            margin: 10px;
        }

        .file-input-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        input[type="file"] {
            display: none;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            transition: all 0.3s;
            margin: 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 87, 108, 0.4);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .files-list {
            margin-top: 30px;
        }

        .file-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            background: #f8f9ff;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .file-item:hover {
            background: #f0f2ff;
            transform: translateX(5px);
        }

        .file-item input[type="checkbox"] {
            margin-right: 15px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .file-meta {
            color: #666;
            font-size: 0.9em;
        }

        .status-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 10px;
            display: none;
        }

        .status-section.active {
            display: block;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
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

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 15px 0;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        .history-section {
            margin-top: 30px;
        }

        .history-item {
            padding: 15px;
            background: #f8f9ff;
            border-radius: 10px;
            margin: 10px 0;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .history-stats {
            display: flex;
            gap: 20px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Back Order Management</h1>
            <p>Upload TXT or ZIP files containing SKUs to mark as Back Order</p>
        </div>

        <div class="content">
            <!-- Upload Section -->
            <div class="upload-section" id="uploadZone">
                <h2>📤 Upload Files</h2>
                <p style="margin: 15px 0; color: #666;">
                    Drag & drop files here or click to browse<br>
                    <small>Supports .txt and .zip files (ZIP files will be automatically extracted)</small>
                </p>
                <label for="fileInput" class="file-input-label">
                    Choose Files
                </label>
                <input type="file" id="fileInput" accept=".txt,.zip" multiple>
                <div id="uploadStatus"></div>
            </div>

            <!-- Files List -->
            <div class="files-list" id="filesList" style="display: none;">
                <h3>📁 Uploaded Files</h3>
                <div class="actions">
                    <button class="btn btn-primary" onclick="selectAll()">Select All</button>
                    <button class="btn btn-primary" onclick="deselectAll()">Deselect All</button>
                    <button class="btn btn-primary" onclick="processSelected()" id="processBtn">
                        Process Selected
                    </button>
                    <button class="btn btn-danger" onclick="deleteSelected()">Delete Selected</button>
                    <button class="btn btn-danger" onclick="clearAll()">Clear All</button>
                </div>
                <div id="filesContainer"></div>
            </div>

            <!-- Status Section -->
            <div class="status-section" id="statusSection">
                <h3>⚙️ Processing Status</h3>
                <div class="spinner" id="spinner"></div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar">0%</div>
                </div>
                <div class="stats" id="stats"></div>
                <div id="statusMessages"></div>
            </div>

            <!-- History Section -->
            <div class="history-section" id="historySection" style="display: none;">
                <h3>📊 Processing History</h3>
                <div id="historyContainer"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
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
                const response = await fetch('/api/back-orders/upload', {
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
                const response = await fetch('/api/back-orders/files');
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
                Swal.fire({ icon: 'warning', title: 'Warning', text: 'Please select at least one file to process' });
                return;
            }

            const _r = await Swal.fire({ title: 'Are you sure?', text: `Process ${selectedFiles.length} file(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#667eea', cancelButtonColor: '#6c757d' });
            if (!_r.isConfirmed) return;

            document.getElementById('processBtn').disabled = true;
            document.getElementById('statusSection').classList.add('active');

            try {
                const response = await fetch('/api/back-orders/process', {
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

            statusCheckInterval = setInterval(checkStatus, 2000);
            checkStatus();
        }

        async function checkStatus() {
            if (!currentBatchId) return;

            try {
                const response = await fetch(`/api/back-orders/status?batch_id=${currentBatchId}`);
                const data = await response.json();

                if (data.success) {
                    updateStatusDisplay(data.status);

                    if (data.status.status === 'completed' || data.status.status === 'failed') {
                        clearInterval(statusCheckInterval);
                        document.getElementById('spinner').style.display = 'none';
                        document.getElementById('processBtn').disabled = false;
                        loadHistory();
                    }
                }
            } catch (error) {
                console.error('Status check failed:', error);
            }
        }

        function updateStatusDisplay(status) {
            const total = status.processed || 0;
            const updated = status.updated || 0;
            const failed = status.failed || 0;
            const progress = total > 0 ? Math.round((updated / total) * 100) : 0;

            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('progressBar').textContent = progress + '%';

            document.getElementById('stats').innerHTML = `
                <div class="stat-card">
                    <div class="stat-value">${total}</div>
                    <div class="stat-label">Processed</div>
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
        }

        async function deleteSelected() {
            const selectedFiles = Array.from(
                document.querySelectorAll('#filesContainer input[type="checkbox"]:checked')
            ).map(cb => cb.value);

            if (selectedFiles.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Warning', text: 'Please select at least one file to delete' });
                return;
            }

            const _r = await Swal.fire({ title: 'Are you sure?', text: `Delete ${selectedFiles.length} file(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#667eea', cancelButtonColor: '#6c757d' });
            if (!_r.isConfirmed) return;

            try {
                const response = await fetch('/api/back-orders/delete-files', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ files: selectedFiles })
                });

                const data = await response.json();
                Swal.fire({ icon: 'success', title: 'Success', text: data.message });
                loadUploadedFiles();
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete files: ' + error.message });
            }
        }

        async function clearAll() {
            const _r = await Swal.fire({ title: 'Are you sure?', text: 'Delete ALL uploaded files?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#667eea', cancelButtonColor: '#6c757d' });
            if (!_r.isConfirmed) return;

            try {
                const response = await fetch('/api/back-orders/clear-all', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                Swal.fire({ icon: 'success', title: 'Success', text: data.message });
                loadUploadedFiles();
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to clear files: ' + error.message });
            }
        }

        async function loadHistory() {
            try {
                const response = await fetch('/api/back-orders/history');
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
</body>
</html>
