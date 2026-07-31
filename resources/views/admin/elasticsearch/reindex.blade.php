@extends('admin.layout')

@section('title', 'Elasticsearch Reindex - Admin Panel')

@push('styles')
<style>
    .reindex-card {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .reindex-card h3 {
        color: white;
        margin-bottom: 10px;
    }

    .reindex-card p {
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
        color: #1f2937;
    }

    .form-section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        border: 1px solid #e5e7eb;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        display: block;
    }

    .form-control {
        border-radius: 8px;
        border: 1px solid #d1d5db;
        padding: 10px 15px;
    }

    .form-control:focus {
        border-color: #4F46E5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .form-check {
        padding: 12px;
        background: #f9fafb;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        margin-bottom: 10px;
    }

    .form-check-input:checked {
        background-color: #4F46E5;
        border-color: #4F46E5;
    }

    .btn-reindex {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-reindex:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.4);
        color: white;
    }

    .btn-reindex:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .output-container {
        background: #1e293b;
        border-radius: 10px;
        padding: 20px;
        min-height: 400px;
        max-height: 600px;
        overflow-y: auto;
        font-family: 'Courier New', monospace;
        color: #e2e8f0;
        font-size: 13px;
        line-height: 1.6;
        display: none;
    }

    .output-container.active {
        display: block;
    }

    .output-line {
        margin-bottom: 4px;
        word-wrap: break-word;
    }

    .output-line.info {
        color: #60a5fa;
    }

    .output-line.success {
        color: #34d399;
    }

    .output-line.error {
        color: #f87171;
    }

    .output-line.warning {
        color: #fbbf24;
    }

    .progress-container {
        margin-top: 20px;
        display: none;
    }

    .progress-container.active {
        display: block;
    }

    .progress {
        height: 30px;
        border-radius: 8px;
        background: #e5e7eb;
    }

    .progress-bar {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        border-radius: 8px;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .status-badge.idle {
        background: #e5e7eb;
        color: #6b7280;
    }

    .status-badge.running {
        background: #dbeafe;
        color: #2563eb;
    }

    .status-badge.success {
        background: #d1fae5;
        color: #059669;
    }

    .status-badge.failed {
        background: #fee2e2;
        color: #dc2626;
    }

    .info-box {
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .info-box strong {
        color: #1e40af;
    }

    .help-text {
        font-size: 13px;
        color: #6b7280;
        margin-top: 5px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .stat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #4F46E5;
    }

    .stat-label {
        font-size: 13px;
        color: #6b7280;
        margin-top: 5px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header Card -->
    <div class="reindex-card">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h3 class="mb-2">
                    <i class="bi bi-arrow-repeat"></i> Elasticsearch Reindex
                </h3>
                <p>Reindex products into Elasticsearch with customizable options</p>
            </div>
            <div>
                <span id="statusBadge" class="status-badge idle">
                    <i class="bi bi-circle-fill"></i> Idle
                </span>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <i class="bi bi-info-circle"></i>
        <strong>Note:</strong> This command will reindex products into Elasticsearch. You can specify product ID ranges, chunk sizes, and run in dry-run mode for testing.
    </div>

    <div class="row">
        <!-- Configuration Panel -->
        <div class="col-lg-5">
            <div class="form-section">
                <h5 class="section-title mb-4">
                    <i class="bi bi-sliders"></i> Configuration
                </h5>

                <form id="reindexForm">
                    <!-- Product ID Range -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-hash"></i> Product ID Range
                        </label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="number" class="form-control" id="fromId" name="from_id"
                                       placeholder="From ID" min="1">
                                <div class="help-text">Start from product ID</div>
                            </div>
                            <div class="col-6">
                                <input type="number" class="form-control" id="toId" name="to_id"
                                       placeholder="To ID" min="1">
                                <div class="help-text">End at product ID</div>
                            </div>
                        </div>
                    </div>

                    <!-- Chunk Size -->
                    <div class="form-group">
                        <label class="form-label" for="chunk">
                            <i class="bi bi-stack"></i> Chunk Size
                        </label>
                        <input type="number" class="form-control" id="chunk" name="chunk"
                               placeholder="500" min="1" max="5000" value="500">
                        <div class="help-text">Number of products to process per batch (default: 500)</div>
                    </div>

                    <!-- Options -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-toggles"></i> Options
                        </label>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="dryRun" name="dry_run">
                            <label class="form-check-label" for="dryRun">
                                <strong>Dry Run Mode</strong>
                                <div class="help-text mt-1">Test without actually indexing (shows what would be indexed)</div>
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="disableRefresh" name="disable_refresh">
                            <label class="form-check-label" for="disableRefresh">
                                <strong>Disable Refresh</strong>
                                <div class="help-text mt-1">Improve performance for large datasets (refresh happens at the end)</div>
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="recreateIndex" name="recreate_index">
                            <label class="form-check-label" for="recreateIndex">
                                <strong>Recreate Index</strong>
                                <div class="help-text mt-1">Delete and recreate the index with new mapping (destructive operation)</div>
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="testMode" name="test">
                            <label class="form-check-label" for="testMode">
                                <strong>Test Mode</strong>
                                <div class="help-text mt-1">Index only first 100 products for testing</div>
                            </label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="indexAll" name="all">
                            <label class="form-check-label" for="indexAll">
                                <strong>Index All Products</strong>
                                <div class="help-text mt-1">Index ALL products regardless of status/approval (use with caution)</div>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-reindex" id="startBtn">
                            <i class="bi bi-play-fill"></i> Start Reindex
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="clearOutputBtn" style="display: none;">
                            <i class="bi bi-x-circle"></i> Clear Output
                        </button>
                    </div>
                </form>

                <!-- Additional Actions -->
                <div class="mt-4 pt-4 border-top">
                    <h6 class="mb-3">
                        <i class="bi bi-tools"></i> Index Management
                    </h6>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" id="checkIndexBtn">
                            <i class="bi bi-info-circle"></i> Check Index Status
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="deleteIndexBtn">
                            <i class="bi bi-trash"></i> Delete Index
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid" id="statsGrid" style="display: none;">
                    <div class="stat-card">
                        <div class="stat-value" id="statProcessed">0</div>
                        <div class="stat-label">Processed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="statTotal">0</div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="statPercent">0%</div>
                        <div class="stat-label">Progress</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Output Panel -->
        <div class="col-lg-7">
            <div class="section">
                <h5 class="section-title">
                    <i class="bi bi-terminal"></i> Output
                </h5>

                <!-- Artisan Commands Section - Moved to top -->
                <div class="mb-3 p-3 bg-white rounded border">
                    <h6 class="mb-3">
                        <i class="bi bi-terminal-fill"></i> Quick Artisan Commands
                    </h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm artisan-cmd-btn" data-command="cache:clear">
                            <i class="bi bi-arrow-clockwise"></i> Clear Cache
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm artisan-cmd-btn" data-command="view:clear">
                            <i class="bi bi-eye-slash"></i> Clear Views
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm artisan-cmd-btn" data-command="config:clear">
                            <i class="bi bi-gear"></i> Clear Config
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm artisan-cmd-btn" data-command="route:clear">
                            <i class="bi bi-signpost"></i> Clear Routes
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm artisan-cmd-btn" data-command="optimize">
                            <i class="bi bi-lightning-charge"></i> Optimize
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm artisan-cmd-btn" data-command="optimize:clear">
                            <i class="bi bi-lightning-charge-fill"></i> Clear Optimization
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm artisan-cmd-btn" data-command="categories:cache-tree">
                            <i class="bi bi-diagram-3"></i> Rebuild Category Tree
                        </button>
                    </div>
                    <div class="help-text mt-2">
                        <small><i class="bi bi-info-circle"></i> Click any button to run the corresponding artisan command. Output will be displayed below.</small>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="progress-container" id="progressContainer">
                    <div class="progress">
                        <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%">0%</div>
                    </div>
                </div>

                <!-- Terminal Output -->
                <div class="output-container" id="outputContainer">
                    <div class="output-line info">
                        <i class="bi bi-info-circle"></i> Configure options and click "Start Reindex" to begin...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reindexForm');
    const startBtn = document.getElementById('startBtn');
    const clearOutputBtn = document.getElementById('clearOutputBtn');
    const outputContainer = document.getElementById('outputContainer');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const statusBadge = document.getElementById('statusBadge');
    const statsGrid = document.getElementById('statsGrid');
    const recreateIndexCheckbox = document.getElementById('recreateIndex');

    let pollTimer = null;
    let isRunning = false;

    // Warn when recreate index is checked
    recreateIndexCheckbox.addEventListener('change', async function() {
        if (this.checked) {
            const _r = await Swal.fire({ title: 'Are you sure?', text: 'WARNING: Recreate Index will DELETE all existing indexed data and create a fresh index. Continue?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (!_r.isConfirmed) {
                this.checked = false;
            }
        }
    });

    // Handle form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (isRunning) {
            return;
        }

        // Additional confirmation for destructive operations
        if (recreateIndexCheckbox.checked) {
            const _r = await Swal.fire({ title: 'Are you sure?', text: 'You are about to RECREATE the index. This will delete all existing data. Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (!_r.isConfirmed) {
                return;
            }
        }

        // Get form data
        const formData = new FormData(form);
        const params = new URLSearchParams();

        if (formData.get('from_id')) params.append('from_id', formData.get('from_id'));
        if (formData.get('to_id')) params.append('to_id', formData.get('to_id'));
        if (formData.get('chunk')) params.append('chunk', formData.get('chunk'));
        if (document.getElementById('dryRun').checked) params.append('dry_run', '1');
        if (document.getElementById('disableRefresh').checked) params.append('disable_refresh', '1');
        if (document.getElementById('recreateIndex').checked) params.append('recreate_index', '1');
        if (document.getElementById('testMode').checked) params.append('test', '1');
        if (document.getElementById('indexAll').checked) params.append('all', '1');

        // Start reindex
        startReindex(params.toString());
    });

    function startReindex(params) {
        isRunning = true;
        updateStatus('running', 'Running');

        outputContainer.innerHTML = '';
        outputContainer.classList.add('active');
        progressContainer.classList.add('active');
        statsGrid.style.display = 'grid';
        clearOutputBtn.style.display = 'block';

        startBtn.disabled = true;
        form.querySelectorAll('input').forEach(input => input.disabled = true);

        updateProgress(0, 0, 0);
        addOutput('Launching reindex in background...', 'info');

        fetch('{{ route("admin.elasticsearch.reindex-start") }}?' + params)
            .then(r => r.json())
            .then(data => {
                if (data.job_id) {
                    addOutput('Reindex started. Tracking progress...', 'info');
                    pollReindex(data.job_id, 0);
                } else {
                    addOutput('Failed to start: ' + (data.error || 'Unknown error'), 'error');
                    updateStatus('failed', 'Error');
                    finishReindex();
                }
            })
            .catch(err => {
                addOutput('Failed to launch reindex: ' + err.message, 'error');
                updateStatus('failed', 'Error');
                finishReindex();
            });
    }

    function pollReindex(jobId, offset) {
        if (!isRunning) return;

        fetch('{{ route("admin.elasticsearch.reindex-poll") }}?job_id=' + encodeURIComponent(jobId) + '&offset=' + offset)
            .then(r => r.json())
            .then(data => {
                (data.lines || []).forEach(line => {
                    if (line.trim()) processOutputLine(line.trim());
                });

                if (data.status === 'completed') {
                    addOutput('Reindex completed successfully!', 'success');
                    updateStatus('success', 'Completed');
                    finishReindex();
                } else if (data.status === 'failed') {
                    addOutput('Reindex failed. Check server logs for details.', 'error');
                    updateStatus('failed', 'Error');
                    finishReindex();
                } else {
                    pollTimer = setTimeout(() => pollReindex(jobId, data.offset), 2000);
                }
            })
            .catch(() => {
                if (isRunning) {
                    pollTimer = setTimeout(() => pollReindex(jobId, offset), 3000);
                }
            });
    }

    function processOutputLine(line) {
        // Parse progress from output
        const progressMatch = line.match(/PROGRESS:\s*([\d.]+)%\s*\(([,\d]+)\/([,\d]+)\)/);
        if (progressMatch) {
            const percent = parseFloat(progressMatch[1]);
            const processed = progressMatch[2].replace(/,/g, '');
            const total = progressMatch[3].replace(/,/g, '');
            updateProgress(percent, parseInt(processed), parseInt(total));
        }

        // Determine line type
        let type = 'info';
        if (line.includes('ERROR') || line.includes('Failed')) {
            type = 'error';
        } else if (line.includes('SUCCESS') || line.includes('complete')) {
            type = 'success';
        } else if (line.includes('WARNING')) {
            type = 'warning';
        }

        addOutput(line, type);
    }

    function addOutput(text, type = 'info') {
        const line = document.createElement('div');
        line.className = `output-line ${type}`;

        // Add icon based on type
        const icon = type === 'error' ? 'x-circle' :
                    type === 'success' ? 'check-circle' :
                    type === 'warning' ? 'exclamation-triangle' :
                    'info-circle';

        line.innerHTML = `<i class="bi bi-${icon}"></i> ${escapeHtml(text)}`;
        outputContainer.appendChild(line);

        // Auto-scroll to bottom
        outputContainer.scrollTop = outputContainer.scrollHeight;
    }

    function updateProgress(percent, processed, total) {
        progressBar.style.width = percent + '%';
        progressBar.textContent = percent.toFixed(1) + '%';

        document.getElementById('statProcessed').textContent = processed.toLocaleString();
        document.getElementById('statTotal').textContent = total.toLocaleString();
        document.getElementById('statPercent').textContent = percent.toFixed(1) + '%';
    }

    function updateStatus(status, text) {
        statusBadge.className = `status-badge ${status}`;
        statusBadge.innerHTML = `<i class="bi bi-circle-fill"></i> ${text}`;
    }

    function finishReindex() {
        isRunning = false;

        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }

        startBtn.disabled = false;
        form.querySelectorAll('input').forEach(input => input.disabled = false);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Clear output button
    clearOutputBtn.addEventListener('click', function() {
        outputContainer.innerHTML = '<div class="output-line info"><i class="bi bi-info-circle"></i> Output cleared. Ready for next reindex...</div>';
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        updateStatus('idle', 'Idle');
        statsGrid.style.display = 'none';
    });

    // Delete index button
    document.getElementById('deleteIndexBtn').addEventListener('click', async function() {
        const _r = await Swal.fire({ title: 'Are you sure?', text: 'Are you sure you want to DELETE the Elasticsearch index? This will remove all indexed products!', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
        if (!_r.isConfirmed) {
            return;
        }

        const _r2 = await Swal.fire({ title: 'Are you sure?', text: 'This is a destructive operation and cannot be undone. Are you absolutely sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
        if (!_r2.isConfirmed) {
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Deleting...';

        fetch('{{ route("admin.elasticsearch.delete-index") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addOutput('✓ ' + data.message, 'success');
                outputContainer.classList.add('active');
            } else {
                addOutput('✗ ' + data.message, 'error');
                outputContainer.classList.add('active');
            }
        })
        .catch(error => {
            addOutput('✗ Error deleting index: ' + error.message, 'error');
            outputContainer.classList.add('active');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-trash"></i> Delete Index';
        });
    });

    // Check index status button
    document.getElementById('checkIndexBtn').addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Checking...';

        fetch('{{ route("admin.elasticsearch.check-index") }}', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            outputContainer.classList.add('active');
            addOutput('=== Index Status ===', 'info');
            addOutput('Index Name: ' + data.index, 'info');
            addOutput('Exists: ' + (data.exists ? 'Yes' : 'No'), data.exists ? 'success' : 'warning');

            if (data.exists) {
                addOutput('Document Count: ' + data.count.toLocaleString(), 'success');
                addOutput('Size: ' + data.size, 'info');
                addOutput('Health: ' + data.health, data.health === 'green' ? 'success' : (data.health === 'yellow' ? 'warning' : 'error'));
            }
        })
        .catch(error => {
            addOutput('✗ Error checking index: ' + error.message, 'error');
            outputContainer.classList.add('active');
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-info-circle"></i> Check Index Status';
        });
    });

    // Artisan command buttons
    document.querySelectorAll('.artisan-cmd-btn').forEach(button => {
        button.addEventListener('click', async function() {
            const command = this.getAttribute('data-command');
            const originalHtml = this.innerHTML;

            if (isRunning) {
                showWarning('Process Running', 'Another process is already running. Please wait for it to complete.');
                return;
            }

            const _r = await Swal.fire({ title: 'Are you sure?', text: `Run artisan command: ${command}?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (!_r.isConfirmed) {
                return;
            }

            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Running...';

            // Clear previous output for artisan commands
            outputContainer.innerHTML = '';
            outputContainer.classList.add('active');
            progressContainer.style.display = 'none';
            statsGrid.style.display = 'none';

            updateStatus('running', 'Running Command');
            addOutput('=== Running Artisan Command ===', 'info');
            addOutput('Command: php artisan ' + command, 'info');
            addOutput('', 'info');

            isRunning = true;
            clearOutputBtn.style.display = 'block';

            // Create URL with command parameter
            const url = '{{ route("admin.elasticsearch.run-artisan") }}?command=' + encodeURIComponent(command);
            const cmdEventSource = new EventSource(url);

            cmdEventSource.onmessage = function(e) {
                try {
                    const data = JSON.parse(e.data);

                    switch (data.type) {
                        case 'status':
                            addOutput(data.message, 'info');
                            break;

                        case 'output':
                            if (data.message && data.message.trim()) {
                                addOutput(data.message, 'info');
                            }
                            break;

                        case 'complete':
                            addOutput('', 'info');
                            addOutput(data.message, 'success');
                            updateStatus('success', 'Completed');
                            cmdEventSource.close();
                            isRunning = false;
                            button.disabled = false;
                            button.innerHTML = originalHtml;
                            break;

                        case 'error':
                            addOutput(data.message, 'error');
                            updateStatus('failed', 'Error');
                            cmdEventSource.close();
                            isRunning = false;
                            button.disabled = false;
                            button.innerHTML = originalHtml;
                            break;
                    }
                } catch (error) {
                    console.error('Error parsing SSE data:', error, e.data);
                }
            };

            cmdEventSource.onerror = function() {
                if (isRunning) {
                    addOutput('Command execution completed or connection closed', 'warning');
                    updateStatus('success', 'Completed');
                    cmdEventSource.close();
                    isRunning = false;
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                }
            };
        });
    });

    window.addEventListener('beforeunload', function() {
        if (pollTimer) clearTimeout(pollTimer);
    });
});
</script>
@endpush
