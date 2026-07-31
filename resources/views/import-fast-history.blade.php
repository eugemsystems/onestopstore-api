<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Import History - Fast Import</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 20px; background: #f7f7fb; color: #222; }
    .container { max-width: 1400px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 18px rgba(0,0,0,.06); overflow: hidden; }
    .header { padding: 16px 20px; background: #0f172a; color: #fff; display: flex; justify-content: space-between; align-items: center; }
    .header h1 { margin: 0; font-size: 18px; }
    .content { padding: 20px; }
    .btn { display: inline-block; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; border: 0; cursor: pointer; transition: all .2s ease; margin-right: 8px; }
    .btn-primary { background: #2563eb; color: #fff; }
    .btn-secondary { background: #64748b; color: #fff; }
    .btn-danger { background: #dc2626; color: #fff; }
    .btn-warning { background: #f59e0b; color: #fff; }
    .btn-sm { padding: 6px 12px; font-size: 13px; }
    .status-badge { padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; display: inline-block; }
    .status-completed { background-color: #d4edda; color: #155724; }
    .status-failed { background-color: #f8d7da; color: #721c24; }
    .status-processing { background-color: #fff3cd; color: #856404; }
    .status-pending { background-color: #e2e3e5; color: #383d41; }
    .batch-link { color: #0066cc; text-decoration: none; font-family: monospace; font-size: 12px; }
    .batch-link:hover { text-decoration: underline; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { background: #f3f4f6; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
    td { padding: 12px; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
    tr:hover { background: #f9fafb; }
    .empty-state { padding: 60px; text-align: center; color: #6b7280; }
    .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
    .pagination a, .pagination span { padding: 8px 12px; border-radius: 6px; border: 1px solid #e5e7eb; text-decoration: none; color: #374151; }
    .pagination .active { background: #2563eb; color: white; border-color: #2563eb; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Import History - Fast Import</h1>
      <a href="/api/import-fast" class="btn btn-sm btn-secondary">← Back to Import</a>
    </div>
    <div class="content">
      <p style="color: #6b7280; margin-bottom: 20px;">View all import jobs, track progress, and manage failed imports</p>

      <div style="overflow-x: auto;">
        <table>
          <thead>
            <tr>
              <th>Batch ID</th>
              <th>Filename</th>
              <th>Status</th>
              <th>Items</th>
              <th>Created</th>
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
                <a href="/api/import-fast/batch/{{ $job->batch_id }}" class="batch-link">
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
                    🔄 Retry
                  </button>
                @endif
                @if($job->error_message)
                  <button class="btn btn-sm btn-danger" onclick="showError(`{{ addslashes($job->error_message) }}`)">
                    ❌ Error
                  </button>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="10" class="empty-state">
                <div style="font-size: 48px; opacity: 0.3;">📋</div>
                <p style="margin-top: 10px;">No import history found</p>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      @if($jobs->hasPages())
      <div class="pagination">
        @if($jobs->onFirstPage())
          <span>« Previous</span>
        @else
          <a href="{{ $jobs->previousPageUrl() }}">« Previous</a>
        @endif

        @foreach(range(1, $jobs->lastPage()) as $page)
          @if($page == $jobs->currentPage())
            <span class="active">{{ $page }}</span>
          @else
            <a href="{{ $jobs->url($page) }}">{{ $page }}</a>
          @endif
        @endforeach

        @if($jobs->hasMorePages())
          <a href="{{ $jobs->nextPageUrl() }}">Next »</a>
        @else
          <span>Next »</span>
        @endif
      </div>
      @endif
    </div>
  </div>

<script>
async function retryJob(batchId) {
  const _r = await Swal.fire({ title: 'Are you sure?', text: 'Retry all failed jobs in this batch?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
  if (!_r.isConfirmed) return;

  fetch('/api/import-fast/resume-failed', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
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
</body>
</html>

