@extends('admin.layout')

@section('title', 'Import History - Fast Import')

@push('styles')
<style>
    .history-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 30px;
    }

    .history-card h3 {
        color: white;
        margin-bottom: 10px;
    }

    .history-card p {
        opacity: 0.9;
        margin-bottom: 0;
    }

    .status-badge {
        padding: 6px 12px;
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
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="history-card">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3><i class="bi bi-clock-history me-2"></i>Import History - Fast Import</h3>
                <p>View all import jobs, track progress, and manage failed imports</p>
            </div>
            <a href="{{ route('admin.import-fast.index') }}" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Import
            </a>
        </div>
    </div>

    <!-- History Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="historyTable">
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Filename</th>
                            <th>Status</th>
                            <th>Items</th>
                            <th>Updated</th>
                            <th>Skipped</th>
                            <th>User</th>
                            <th>Date</th>
                            <th>Duration</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs as $job)
                        <tr>
                            <td>
                                <a href="{{ route('admin.import-fast.batch-details', $job->batch_id) }}" class="batch-link">
                                    {{ $job->batch_id }}
                                </a>
                            </td>
                            <td>{{ $job->filename }}</td>
                            <td>
                                <span class="status-badge status-{{ $job->status }}">
                                    {{ $job->status }}
                                </span>
                            </td>
                            <td>{{ number_format($job->total_items) }}</td>
                            <td>{{ number_format($job->updated_items) }}</td>
                            <td>{{ number_format($job->skipped_items) }}</td>
                            <td>{{ $job->user?->name ?? 'System' }}</td>
                            <td>{{ $job->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                @if($job->duration_seconds)
                                    {{ gmdate('H:i:s', $job->duration_seconds) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($job->status === 'failed')
                                    <button class="btn btn-sm btn-warning" onclick="retryJob('{{ $job->batch_id }}')">
                                        <i class="bi bi-arrow-clockwise"></i> Retry
                                    </button>
                                @endif
                                @if($job->error_message)
                                    <button class="btn btn-sm btn-danger" onclick="showError('{{ addslashes($job->error_message) }}')">
                                        <i class="bi bi-exclamation-circle"></i> Error
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                                <p class="mt-3">No import history found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
</div>

<script>
async function retryJob(batchId) {
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Retry all failed jobs in this batch?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    fetch('{{ route("admin.import-fast.resume-failed") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ batch_id: batchId })
    })
    .then(response => response.json())
    .then(data => {
        showSuccess('Retry Queued', data.message || 'Jobs marked for retry');
        location.reload();
    })
    .catch(error => {
        showError('Error', 'Failed to retry jobs: ' + error.message);
    });
}
</script>
@endsection

