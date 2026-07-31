@extends('admin.layout')

@section('title', 'Fast Import [Update] - Admin Panel')

@push('styles')
<style>
    .import-card {
        background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
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

    .empty-state {
        padding: 40px;
        text-align: center;
        color: #6b7280;
    }

    .badge-warning {
        background: #fef3c7;
        color: #92400e;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    .progress-bar {
        width: 100%;
        background: #e5e7eb;
        height: 12px;
        border-radius: 100px;
        overflow: hidden;
        margin: 15px 0;
    }

    .progress-bar > span {
        display: block;
        height: 12px;
        background: linear-gradient(90deg, #f59e0b, #ea580c);
        width: 0;
        transition: width .2s ease;
    }

    .output-console {
        background: #0b1020;
        color: #d1d5db;
        padding: 20px;
        border-radius: 10px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        white-space: pre-wrap;
        max-height: 500px;
        overflow: auto;
        margin-top: 20px;
    }

    .btn-import-update {
        background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
        border: none;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-import-update:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(245, 158, 11, 0.4);
    }

    .btn-import-update:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 0.6s linear infinite;
        margin-left: 8px;
        vertical-align: middle;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* History Table Styles */
    .status-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }

    .status-completed {
        background-color: #d4edda;
        color: #155724;
    }

    .status-failed {
        background-color: #f8d7da;
        color: #721c24;
    }

    .status-processing {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-pending {
        background-color: #e2e3e5;
        color: #383d41;
    }

    .batch-link {
        color: #0066cc;
        text-decoration: none;
        font-family: monospace;
        font-size: 12px;
    }

    .batch-link:hover {
        text-decoration: underline;
    }

    #historyTable {
        font-size: 13px;
    }

    #historyTable td, #historyTable th {
        vertical-align: middle;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }

    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 14px;
        color: #666;
        font-weight: 600;
    }

    .stat-value {
        font-size: 32px;
        font-weight: bold;
        color: #333;
    }

    .stat-card.success {
        border-left: 4px solid #28a745;
    }

    .stat-card.error {
        border-left: 4px solid #dc3545;
    }

    .stat-card.warning {
        border-left: 4px solid #ffc107;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .batch-files-table {
        font-size: 13px;
    }

    .batch-summary {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .form-label-custom {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        display: block;
    }

    .form-control-custom {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .form-control-custom:focus {
        border-color: #f59e0b;
        outline: none;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }

    .alert-warning-custom {
        background: #fef3c7;
        border: 2px solid #f59e0b;
        color: #92400e;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="import-card">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3><i class="bi bi-arrow-repeat me-2"></i>Fast Product Import [Update]</h3>
                <p>Upload JSON files to update product prices and reviews only. Products that don't exist will be skipped.</p>
            </div>
            <a href="{{ route('admin.import-fast-update.history') }}" class="btn btn-light btn-sm">
                <i class="bi bi-clock-history me-2"></i>View Import History
            </a>
        </div>
    </div>

    <!-- Warning -->
    <div class="alert-warning-custom">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Price & Review Update Mode:</strong> This will ONLY update prices and reviews of existing products. New products will NOT be created. Non-existent products will be skipped.
    </div>

    <!-- Existing Files Section -->
    <div class="section">
        <div class="section-title">
            <span><i class="bi bi-folder2-open me-2"></i>Existing JSON Files (Update)</span>
            <span class="badge-warning" id="fileCount">0 files</span>
        </div>
        <div class="file-list" id="fileList">
            <div class="empty-state">
                <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                <p class="mt-3">No JSON files found in storage/app/jsonfiles-update</p>
            </div>
        </div>
        <div style="margin-top: 15px; display: flex; gap: 8px;">
            <button class="btn btn-sm btn-success" id="btnSelectAll">
                <i class="bi bi-check-all me-1"></i> Select All
            </button>
            <button class="btn btn-sm btn-secondary" id="btnDeselectAll">
                <i class="bi bi-x-lg me-1"></i> Deselect All
            </button>
            <button class="btn btn-sm btn-danger" id="btnDeleteSelected">
                <i class="bi bi-trash me-1"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Upload Section -->
        <div class="col-md-6">
            <div class="section">
                <div class="section-title">
                    <span><i class="bi bi-cloud-upload me-2"></i>Upload New File</span>
                </div>
                <input type="file" id="jsonfile" accept=".json,.txt,.zip" class="form-control mb-3" />
                <small class="text-muted d-block mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Supports JSON and ZIP files. ZIP files will be automatically extracted. Chunked upload with 5MB chunks for faster performance.
                </small>
                <div class="progress-bar">
                    <span id="prog"></span>
                </div>
                <button class="btn btn-primary" id="btnUpload">
                    <i class="bi bi-upload me-2"></i>Upload File
                </button>
                <div id="uploadStatus" class="text-muted mt-2"></div>
            </div>
        </div>

        <!-- Import Settings Section -->
        <div class="col-md-6">
            <div class="section">
                <div class="section-title">
                    <span><i class="bi bi-gear me-2"></i>Price & Review Update Settings</span>
                </div>
                <div class="mb-3">
                    <label for="percentage" class="form-label-custom">
                        Percentage <span class="text-danger">*</span>
                    </label>
                    <input required type="number" step="0.01" id="percentage" class="form-control-custom" placeholder="e.g. 1.00" />
                    <small class="text-muted">Price adjustment percentage</small>
                </div>
                <div class="mb-3">
                    <label for="todaysRate" class="form-label-custom">
                        Today's Rate <span class="text-danger">*</span>
                    </label>
                    <input required type="number" step="0.0001" id="todaysRate" class="form-control-custom" placeholder="e.g. 18.0000" />
                    <small class="text-muted">Currency exchange rate</small>
                </div>
                <button class="btn btn-import-update w-100" id="btnRun">
                    <span id="btnRunText">
                        <i class="bi bi-arrow-clockwise me-2"></i>Run Price & Review Update on Selected Files
                    </span>
                    <span id="btnRunSpinner" class="spinner" style="display:none;"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Live Output -->
    <div class="section">
        <div class="section-title">
            <span><i class="bi bi-terminal me-2"></i>Live Output</span>
        </div>
        <div class="output-console" id="out">
            Ready to update prices and reviews. Select files and click "Run Price & Review Update".
        </div>
    </div>

    <!-- Import History Button -->
    <div class="text-center mt-3 mb-4">
        <button class="btn btn-outline-primary btn-lg" id="btnShowHistory">
            <i class="bi bi-clock-history me-2"></i>View Import History
        </button>
    </div>
</div>

<!-- Import History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalLabel">
                    <i class="bi bi-clock-history me-2"></i>Import History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <button class="btn btn-sm btn-primary" id="btnRefreshHistory">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                        <button class="btn btn-sm btn-info" id="btnShowStats">
                            <i class="bi bi-bar-chart me-1"></i>Statistics
                        </button>
                    </div>
                    <div>
                        <select class="form-select form-select-sm" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="processing">Processing</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>

                <div id="historyLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading import history...</p>
                </div>

                <div id="historyContent" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-hover" id="historyTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch ID</th>
                                    <th>Filename</th>
                                    <th>Status</th>
                                    <th>Items</th>
                                    <th>Updated</th>
                                    <th>Skipped</th>
                                    <th>Duration</th>
                                    <th>Started</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                            </tbody>
                        </table>
                    </div>

                    <nav aria-label="History pagination">
                        <ul class="pagination justify-content-center" id="historyPagination">
                        </ul>
                    </nav>
                </div>

                <div id="historyError" style="display: none;" class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span id="historyErrorMessage"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Batch Details Modal -->
<div class="modal fade" id="batchDetailsModal" tabindex="-1" aria-labelledby="batchDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchDetailsModalLabel">
                    <i class="bi bi-file-text me-2"></i>Batch Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="batchDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const existingFiles = @json($existingFiles);

(function(){
  const elFile = document.getElementById('jsonfile');
  const elProg = document.getElementById('prog');
  const elUpload = document.getElementById('btnUpload');
  const elStatus = document.getElementById('uploadStatus');
  const elOut = document.getElementById('out');
  const elRun = document.getElementById('btnRun');
  const elRunText = document.getElementById('btnRunText');
  const elRunSpinner = document.getElementById('btnRunSpinner');
  const elPct = document.getElementById('percentage');
  const elRate = document.getElementById('todaysRate');
  const elFileList = document.getElementById('fileList');
  const elFileCount = document.getElementById('fileCount');
  const elSelectAll = document.getElementById('btnSelectAll');
  const elDeselectAll = document.getElementById('btnDeselectAll');
  const elDeleteSelected = document.getElementById('btnDeleteSelected');

  // Render file list
  function renderFileList() {
    if (existingFiles.length === 0) {
      elFileList.innerHTML = `
        <div class="empty-state">
          <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
          <p class="mt-3">No JSON files found in storage/app/jsonfiles-update</p>
        </div>
      `;
      elFileCount.textContent = '0 files';
      return;
    }

    elFileCount.textContent = existingFiles.length + (existingFiles.length === 1 ? ' file' : ' files');

    let html = '';
    existingFiles.forEach((file, index) => {
      const sizeKB = (file.size / 1024).toFixed(2);
      const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
      const sizeDisplay = file.size > 1024 * 1024 ? sizeMB + ' MB' : sizeKB + ' KB';

      html += `
        <div class="file-item">
          <input type="checkbox" class="file-checkbox" data-filename="${file.name}" id="file-${index}">
          <div class="file-info">
            <div class="file-name">${file.name}</div>
            <div class="file-meta">${sizeDisplay} • ${file.date}</div>
          </div>
          <button class="btn btn-sm btn-primary download-btn" data-filename="${file.name}" title="Download">
            <i class="bi bi-download"></i>
          </button>
        </div>
      `;
    });

    elFileList.innerHTML = html;
  }

  renderFileList();

  // Select/Deselect All
  elSelectAll.addEventListener('click', () => {
    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = true);
  });

  elDeselectAll.addEventListener('click', () => {
    document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);
  });

  // Delete Selected
  elDeleteSelected.addEventListener('click', async function() {
    const selected = Array.from(document.querySelectorAll('.file-checkbox:checked'))
      .map(cb => cb.dataset.filename);

    if (selected.length === 0) {
      showWarning('No Selection', 'No files selected');
      return;
    }

    const _r = await Swal.fire({ title: 'Are you sure?', text: `Delete ${selected.length} file(s)? This cannot be undone.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    try {
      const resp = await fetch('{{ route('admin.import-fast-update.delete-files') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ files: selected })
      });

      const result = await resp.json();
      showSuccess('Deleted', result.message || 'Files deleted');
      location.reload();
    } catch (e) {
      showError('Error', 'Failed to delete files: ' + e.message);
    }
  });

  // Download File
  elFileList.addEventListener('click', function(e) {
    const downloadBtn = e.target.closest('.download-btn');
    if (downloadBtn) {
      const filename = downloadBtn.dataset.filename;
      window.location.href = '{{ route('admin.import-fast-update.download-file') }}?file=' + encodeURIComponent(filename);
    }
  });

  // Upload File with parallel chunks
  elUpload.addEventListener('click', async function(){
    const file = elFile.files[0];
    if(!file){ showWarning('No File', 'Choose a JSON or ZIP file first.'); return; }

    elUpload.disabled = true;
    elProg.style.width = '0%';
    elStatus.textContent = 'Uploading...';

    const chunkSize = 5 * 1024 * 1024; // 5MB chunks for faster uploads
    const totalChunks = Math.ceil(file.size / chunkSize);
    const uploadId = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    const fileName = file.name;
    const maxParallel = 3; // Upload 3 chunks in parallel

    let uploaded = 0;
    let currentChunk = 0;
    const chunks = [];

    // Prepare all chunks
    for (let i = 0; i < totalChunks; i++) {
      const start = i * chunkSize;
      const end = Math.min(start + chunkSize, file.size);
      chunks.push({
        index: i,
        blob: file.slice(start, end)
      });
    }

    const uploadChunk = (chunkData) => new Promise((resolve, reject) => {
      const fd = new FormData();
      fd.append('uploadId', uploadId);
      fd.append('fileName', fileName);
      fd.append('chunkIndex', String(chunkData.index));
      fd.append('totalChunks', String(totalChunks));
      fd.append('chunk', chunkData.blob, `${fileName}.part${chunkData.index}`);

      const xhr = new XMLHttpRequest();
      xhr.open('POST', '{{ route('admin.import-fast-update.upload-chunk') }}', true);
      xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve();
        } else {
          reject(new Error('Chunk upload failed: '+xhr.status));
        }
      };
      xhr.onerror = () => reject(new Error('Network error'));
      xhr.send(fd);
    });

    try {
      // Upload chunks in parallel batches
      while (currentChunk < chunks.length) {
        const batch = chunks.slice(currentChunk, currentChunk + maxParallel);
        await Promise.all(batch.map(chunk => uploadChunk(chunk)));

        uploaded += batch.length;
        currentChunk += batch.length;

        const pct = Math.round((uploaded / totalChunks) * 100);
        elProg.style.width = pct + '%';
        elStatus.textContent = `Uploading... ${pct}% (${uploaded}/${totalChunks} chunks)`;
      }

      // Finalize upload
      elStatus.textContent = 'Finalizing upload...';
      const params = new URLSearchParams();
      params.append('uploadId', uploadId);
      params.append('fileName', fileName);
      params.append('totalChunks', String(totalChunks));

      const resp = await fetch('{{ route('admin.import-fast-update.upload-complete') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: params.toString(),
      });

      if (!resp.ok) throw new Error('Finalize failed '+resp.status);
      const js = await resp.json();

      if (js.extracted) {
        elStatus.textContent = `✅ Extracted ${js.files_extracted} JSON file(s) from ZIP - Refreshing...`;
      } else {
        elStatus.textContent = 'Uploaded: ' + (js.stored_as || fileName) + ' - Refreshing...';
      }

      setTimeout(() => location.reload(), 1500);
    } catch (e) {
      elStatus.textContent = 'Upload failed: ' + (e?.message || 'unknown');
      elProg.style.width = '0%';
    } finally {
      elUpload.disabled = false;
    }
  });

  // Run Price Update
  elRun.addEventListener('click', async function(){
    const pct = elPct.value.trim();
    const rate = elRate.value.trim();

    if(!pct || !rate) {
      showWarning('Required Fields', 'Please enter both Percentage and Today\'s Rate');
      return;
    }

    const selected = Array.from(document.querySelectorAll('.file-checkbox:checked'))
      .map(cb => cb.dataset.filename);

    if (selected.length === 0) {
      showWarning('No Selection', 'Please select at least one JSON file to process');
      return;
    }

    const _r = await Swal.fire({ title: 'Are you sure?', text: `This will update prices and reviews for existing products only. Products that don't exist will be skipped. Continue with ${selected.length} file(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    // Show loading state
    elRun.disabled = true;
    elRunText.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
    elRunSpinner.style.display = 'inline-block';
    elOut.textContent = `Starting price & review update for ${selected.length} file(s)...\n`;

    const params = new URLSearchParams();
    params.append('percentage', pct);
    params.append('todaysRate', rate);
    params.append('selectedFiles', JSON.stringify(selected));
    params.append('_token', '{{ csrf_token() }}');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route('admin.import-fast-update.run') }}', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function(){
      if(xhr.readyState === 3 || xhr.readyState === 4){
        elOut.innerHTML = xhr.responseText;
        elOut.scrollTop = elOut.scrollHeight;

        if(xhr.readyState === 4) {
          elRun.disabled = false;
          elRunText.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Run Price & Review Update on Selected Files';
          elRunSpinner.style.display = 'none';
        }
      }
    };

    xhr.onerror = function() {
      elRun.disabled = false;
      elRunText.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Run Price & Review Update on Selected Files';
      elRunSpinner.style.display = 'none';
      elOut.innerHTML += '<br><span style="color:#ef4444;">Error: Request failed</span>';
    };

    xhr.send(params.toString());
  });

  // ============================================
  // Import History Functionality
  // ============================================
  const btnShowHistory = document.getElementById('btnShowHistory');
  const historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
  const batchDetailsModal = new bootstrap.Modal(document.getElementById('batchDetailsModal'));
  const historyLoading = document.getElementById('historyLoading');
  const historyContent = document.getElementById('historyContent');
  const historyError = document.getElementById('historyError');
  const historyTableBody = document.getElementById('historyTableBody');
  const historyPagination = document.getElementById('historyPagination');
  const btnRefreshHistory = document.getElementById('btnRefreshHistory');
  const btnShowStats = document.getElementById('btnShowStats');
  const statusFilter = document.getElementById('statusFilter');

  let currentPage = 1;
  let currentStatus = '';

  // Format duration
  function formatDuration(seconds) {
    if (!seconds) return 'N/A';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    if (h > 0) return `${h}h ${m}m ${s}s`;
    if (m > 0) return `${m}m ${s}s`;
    return `${s}s`;
  }

  // Format date
  function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleString();
  }

  // Load import history
  async function loadImportHistory(page = 1) {
    historyLoading.style.display = 'block';
    historyContent.style.display = 'none';
    historyError.style.display = 'none';

    try {
      const url = new URL('{{ route('admin.import-fast-update.history') }}', window.location.origin);
      url.searchParams.append('per_page', '20');
      url.searchParams.append('page', page);

      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        throw new Error('Failed to load history');
      }

      const result = await response.json();
      renderHistoryTable(result.data);

      historyLoading.style.display = 'none';
      historyContent.style.display = 'block';
    } catch (error) {
      historyLoading.style.display = 'none';
      historyError.style.display = 'block';
      document.getElementById('historyErrorMessage').textContent = error.message;
    }
  }

  // Render history table
  function renderHistoryTable(data) {
    historyTableBody.innerHTML = '';

    if (!data.data || data.data.length === 0) {
      historyTableBody.innerHTML = `
        <tr>
          <td colspan="9" class="text-center text-muted py-4">
            <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-2">No import history found</p>
          </td>
        </tr>
      `;
      historyPagination.innerHTML = '';
      return;
    }

    // Filter by status if needed
    let filteredData = data.data;
    if (currentStatus) {
      filteredData = data.data.filter(job => job.status === currentStatus);
    }

    filteredData.forEach(job => {
      const row = document.createElement('tr');
      row.className = job.status === 'failed' ? 'table-danger' : '';
      row.innerHTML = `
        <td>
          <a href="#" class="batch-link" data-batch-id="${job.batch_id}">${job.batch_id}</a>
        </td>
        <td>
          <small>${job.filename}</small>
        </td>
        <td>
          <span class="status-badge status-${job.status}">${job.status}</span>
        </td>
        <td>${job.total_items.toLocaleString()}</td>
        <td class="text-success">${job.updated_items.toLocaleString()}</td>
        <td class="text-warning">${job.skipped_items.toLocaleString()}</td>
        <td>${formatDuration(job.duration_seconds)}</td>
        <td><small>${formatDate(job.started_at)}</small></td>
        <td>
          ${job.status === 'failed' ? `
            <button class="btn btn-sm btn-warning retry-btn" data-batch-id="${job.batch_id}">
              <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
          ` : ''}
          <button class="btn btn-sm btn-info details-btn" data-batch-id="${job.batch_id}">
            <i class="bi bi-info-circle"></i>
          </button>
        </td>
      `;
      historyTableBody.appendChild(row);
    });

    // Render pagination
    renderPagination(data);

    // Attach event listeners
    attachHistoryEventListeners();
  }

  // Render pagination
  function renderPagination(data) {
    historyPagination.innerHTML = '';

    if (data.last_page <= 1) return;

    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = 'page-item' + (data.current_page === 1 ? ' disabled' : '');
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${data.current_page - 1}">Previous</a>`;
    historyPagination.appendChild(prevLi);

    // Page numbers
    for (let i = 1; i <= data.last_page; i++) {
      if (i === 1 || i === data.last_page || (i >= data.current_page - 2 && i <= data.current_page + 2)) {
        const li = document.createElement('li');
        li.className = 'page-item' + (i === data.current_page ? ' active' : '');
        li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        historyPagination.appendChild(li);
      } else if (i === data.current_page - 3 || i === data.current_page + 3) {
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        li.innerHTML = `<span class="page-link">...</span>`;
        historyPagination.appendChild(li);
      }
    }

    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = 'page-item' + (data.current_page === data.last_page ? ' disabled' : '');
    nextLi.innerHTML = `<a class="page-link" href="#" data-page="${data.current_page + 1}">Next</a>`;
    historyPagination.appendChild(nextLi);
  }

  // Attach event listeners to history table
  function attachHistoryEventListeners() {
    // Batch links
    document.querySelectorAll('.batch-link').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const batchId = this.dataset.batchId;
        viewBatchDetails(batchId);
      });
    });

    // Details buttons
    document.querySelectorAll('.details-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const batchId = this.dataset.batchId;
        viewBatchDetails(batchId);
      });
    });

    // Retry buttons
    document.querySelectorAll('.retry-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const batchId = this.dataset.batchId;
        retryFailedImports(batchId);
      });
    });

    // Pagination links
    document.querySelectorAll('#historyPagination .page-link').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const page = parseInt(this.dataset.page);
        if (page && page > 0) {
          currentPage = page;
          loadImportHistory(page);
        }
      });
    });
  }

  // View batch details
  async function viewBatchDetails(batchId) {
    const content = document.getElementById('batchDetailsContent');
    content.innerHTML = `
      <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading batch details...</p>
      </div>
    `;

    batchDetailsModal.show();

    try {
      const url = '{{ route('admin.import-fast-update.batch-details', ['batchId' => 'BATCH_ID']) }}'.replace('BATCH_ID', batchId);
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        throw new Error('Failed to load batch details');
      }

      const result = await response.json();
      renderBatchDetails(result);
    } catch (error) {
      content.innerHTML = `
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-triangle me-2"></i>
          ${error.message}
        </div>
      `;
    }
  }

  // Render batch details
  function renderBatchDetails(data) {
    const { batch, jobs } = data;
    const content = document.getElementById('batchDetailsContent');

    let html = `
      <h4 class="mb-3">Batch: ${batch.batch_id}</h4>

      <div class="batch-summary">
        <div class="stats-grid">
          <div class="stat-card">
            <h3>Total Files</h3>
            <div class="stat-value">${batch.total_files}</div>
          </div>
          <div class="stat-card success">
            <h3>Completed</h3>
            <div class="stat-value">${batch.completed}</div>
          </div>
          <div class="stat-card error">
            <h3>Failed</h3>
            <div class="stat-value">${batch.failed}</div>
          </div>
          <div class="stat-card warning">
            <h3>Processing</h3>
            <div class="stat-value">${batch.processing}</div>
          </div>
          <div class="stat-card">
            <h3>Total Items</h3>
            <div class="stat-value">${batch.total_items.toLocaleString()}</div>
          </div>
          <div class="stat-card success">
            <h3>Updated</h3>
            <div class="stat-value">${batch.updated_items.toLocaleString()}</div>
          </div>
          <div class="stat-card warning">
            <h3>Skipped</h3>
            <div class="stat-value">${batch.skipped_items.toLocaleString()}</div>
          </div>
          <div class="stat-card">
            <h3>Duration</h3>
            <div class="stat-value">${formatDuration(batch.total_duration)}</div>
          </div>
        </div>
      </div>

      <h5 class="mt-4 mb-3">Files in Batch</h5>
      <div class="table-responsive">
        <table class="table table-sm table-hover batch-files-table">
          <thead class="table-light">
            <tr>
              <th>Filename</th>
              <th>Status</th>
              <th>Items</th>
              <th>Updated</th>
              <th>Skipped</th>
              <th>Duration</th>
              <th>Error</th>
            </tr>
          </thead>
          <tbody>
    `;

    jobs.forEach(job => {
      html += `
        <tr class="${job.status === 'failed' ? 'table-danger' : ''}">
          <td><small>${job.filename}</small></td>
          <td><span class="status-badge status-${job.status}">${job.status}</span></td>
          <td>${job.total_items.toLocaleString()}</td>
          <td class="text-success">${job.updated_items.toLocaleString()}</td>
          <td class="text-warning">${job.skipped_items.toLocaleString()}</td>
          <td>${formatDuration(job.duration_seconds)}</td>
          <td><small class="text-danger">${job.error_message || '-'}</small></td>
        </tr>
      `;
    });

    html += `
          </tbody>
        </table>
      </div>
    `;

    if (batch.failed > 0) {
      html += `
        <div class="text-center mt-4">
          <button class="btn btn-warning btn-lg" onclick="retryFailedImports('${batch.batch_id}')">
            <i class="bi bi-arrow-clockwise me-2"></i>Resume Failed Imports
          </button>
        </div>
      `;
    }

    content.innerHTML = html;
  }

  // Retry failed imports
  async function retryFailedImports(batchId) {
    const pct = prompt('Enter percentage:', elPct.value || '35');
    const rate = prompt('Enter today\'s rate:', elRate.value || '18.5');

    if (!pct || !rate) {
      showWarning('Required Fields', 'Percentage and rate are required');
      return;
    }

    try {
      const response = await fetch('{{ route('admin.import-fast-update.resume-failed') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
          batch_id: batchId,
          percentage: parseFloat(pct),
          todaysRate: parseFloat(rate)
        })
      });

      const result = await response.json();

      if (result.success) {
        showSuccess('Retry Queued', `Retry batch created: ${result.batch_id}. Retrying ${result.files_to_retry} file(s)`);

        // Close modals
        historyModal.hide();
        batchDetailsModal.hide();

        // Automatically trigger the import with retry parameters
        if (result.redirect_to_run && result.run_params) {
          // Set the form values
          elPct.value = result.run_params.percentage;
          elRate.value = result.run_params.todaysRate;

          // Uncheck all files first
          document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);

          // Check only the files to retry
          result.run_params.selectedFiles.forEach(filename => {
            const checkbox = document.querySelector(`.file-checkbox[data-filename="${filename}"]`);
            if (checkbox) checkbox.checked = true;
          });

          // Scroll to run button
          elRun.scrollIntoView({ behavior: 'smooth' });

          // Optional: Automatically click run after a delay
          const _rc = await Swal.fire({ title: 'Are you sure?', text: 'Files selected. Click OK to start the import now.', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
          if (_rc.isConfirmed) {
            setTimeout(() => elRun.click(), 500);
          }
        }
      } else {
        showError('Error', 'Error: ' + result.message);
      }
    } catch (error) {
      showError('Error', 'Failed to resume imports: ' + error.message);
    }
  }

  // Show history button click
  btnShowHistory.addEventListener('click', function() {
    currentPage = 1;
    currentStatus = '';
    statusFilter.value = '';
    loadImportHistory(1);
    historyModal.show();
  });

  // Refresh history
  btnRefreshHistory.addEventListener('click', function() {
    loadImportHistory(currentPage);
  });

  // Status filter change
  statusFilter.addEventListener('change', function() {
    currentStatus = this.value;
    loadImportHistory(currentPage);
  });

  // Show statistics
  btnShowStats.addEventListener('click', async function() {
    try {
      const response = await fetch('{{ route('admin.import-fast-update.statistics') }}', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await response.json();
      showStatistics(result);
    } catch (error) {
      showError('Error', 'Failed to load statistics: ' + error.message);
    }
  });

  // Show statistics modal
  function showStatistics(data) {
    const stats = data.statistics;
    const content = document.getElementById('batchDetailsContent');

    let html = `
      <h4 class="mb-4">Import Statistics</h4>

      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Batches</h3>
          <div class="stat-value">${stats.total_batches}</div>
        </div>
        <div class="stat-card">
          <h3>Total Imports</h3>
          <div class="stat-value">${stats.total_imports}</div>
        </div>
        <div class="stat-card success">
          <h3>Completed</h3>
          <div class="stat-value">${stats.completed}</div>
          <small>${((stats.completed / stats.total_imports) * 100).toFixed(1)}%</small>
        </div>
        <div class="stat-card error">
          <h3>Failed</h3>
          <div class="stat-value">${stats.failed}</div>
          <small>${((stats.failed / stats.total_imports) * 100).toFixed(1)}%</small>
        </div>
        <div class="stat-card">
          <h3>Items Processed</h3>
          <div class="stat-value">${stats.total_items_processed.toLocaleString()}</div>
        </div>
        <div class="stat-card success">
          <h3>Items Updated</h3>
          <div class="stat-value">${stats.total_items_updated.toLocaleString()}</div>
        </div>
        <div class="stat-card warning">
          <h3>Items Skipped</h3>
          <div class="stat-value">${stats.total_items_skipped.toLocaleString()}</div>
        </div>
        <div class="stat-card">
          <h3>Avg Duration</h3>
          <div class="stat-value">${formatDuration(Math.round(stats.average_duration))}</div>
        </div>
      </div>

      <h5 class="mt-4 mb-3">Recent Batches</h5>
      <ul class="list-group">
    `;

    data.recent_batches.forEach(batch => {
      html += `
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div>
            <a href="#" class="batch-link" onclick="viewBatchDetails('${batch.batch_id}'); return false;">
              ${batch.batch_id}
            </a>
            <br>
            <small class="text-muted">${batch.file_count} file(s) - ${formatDate(batch.started_at)}</small>
          </div>
        </li>
      `;
    });

    html += `
      </ul>
    `;

    content.innerHTML = html;
    batchDetailsModal.show();
  }

  // Make retryFailedImports globally accessible
  window.retryFailedImports = retryFailedImports;
  window.viewBatchDetails = viewBatchDetails;
})();
</script>
@endpush

