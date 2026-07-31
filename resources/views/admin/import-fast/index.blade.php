@extends('admin.layout')

@section('title', 'Fast Import - Admin Panel')

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

    .badge-info {
        background: #dbeafe;
        color: #1e40af;
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
        background: linear-gradient(90deg, #22c55e, #16a34a);
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

    .btn-import {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-import:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-import:disabled {
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
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="import-card">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3><i class="bi bi-lightning-charge-fill me-2"></i>Fast Product Import</h3>
                <p>Upload JSON product files and import them into the database with automatic indexing and caching.</p>
            </div>
            <a href="{{ route('admin.import-fast.history') }}" class="btn btn-light btn-sm">
                <i class="bi bi-clock-history me-2"></i>View Import History
            </a>
        </div>
    </div>

    <!-- Existing Files Section -->
    <div class="section">
        <div class="section-title">
            <span><i class="bi bi-folder2-open me-2"></i>Existing JSON Files</span>
            <span class="badge-info" id="fileCount">0 files</span>
        </div>
        <div class="file-list" id="fileList">
            <div class="empty-state">
                <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                <p class="mt-3">No JSON files found in storage/app/jsonfiles</p>
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
                    <span><i class="bi bi-gear me-2"></i>Import Settings</span>
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
                <button class="btn btn-import w-100" id="btnRun">
                    <span id="btnRunText">
                        <i class="bi bi-play-fill me-2"></i>Run Import on Selected Files
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
            Ready to import. Select files and click "Run Import".
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
          <p class="mt-3">No JSON files found in storage/app/jsonfiles</p>
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
      const resp = await fetch('{{ route('admin.import-fast.delete-files') }}', {
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
      window.location.href = '{{ route('admin.import-fast.download-file') }}?file=' + encodeURIComponent(filename);
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
      xhr.open('POST', '{{ route('admin.import-fast.upload-chunk') }}', true);
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

      const resp = await fetch('{{ route('admin.import-fast.upload-complete') }}', {
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

  // Run Import
  elRun.addEventListener('click', function(){
    const pct = elPct.value.trim();
    const rate = elRate.value.trim();

    if(!pct || !rate) {
      showWarning('Required Fields', 'Please enter both Percentage and Today\'s Rate');
      return;
    }

    const selected = Array.from(document.querySelectorAll('.file-checkbox:checked'))
      .map(cb => cb.dataset.filename);

    if (selected.length === 0) {
      showWarning('No Selection', 'Please select at least one JSON file to import');
      return;
    }

    // Show loading state
    elRun.disabled = true;
    elRunText.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
    elRunSpinner.style.display = 'inline-block';
    elOut.textContent = `Starting import of ${selected.length} file(s)...\n`;

    const params = new URLSearchParams();
    params.append('percentage', pct);
    params.append('todaysRate', rate);
    params.append('selectedFiles', JSON.stringify(selected));
    params.append('_token', '{{ csrf_token() }}');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route('admin.import-fast.run') }}', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function(){
      if(xhr.readyState === 3 || xhr.readyState === 4){
        elOut.innerHTML = xhr.responseText;
        elOut.scrollTop = elOut.scrollHeight;

        if(xhr.readyState === 4) {
          elRun.disabled = false;
          elRunText.innerHTML = '<i class="bi bi-play-fill me-2"></i>Run Import on Selected Files';
          elRunSpinner.style.display = 'none';
        }
      }
    };

    xhr.onerror = function() {
      elRun.disabled = false;
      elRunText.innerHTML = '<i class="bi bi-play-fill me-2"></i>Run Import on Selected Files';
      elRunSpinner.style.display = 'none';
      elOut.innerHTML += '<br><span style="color:#ef4444;">Error: Request failed</span>';
    };

    xhr.send(params.toString());
  });
})();
</script>
@endpush

