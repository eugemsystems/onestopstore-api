@include('emails.partials.layout', [
    'preheader'     => 'Your product import has finished processing successfully.',
    'emailTitle'    => 'Import Completed Successfully',
    'isInteractive' => false,
])
<style>
  .import-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:16px 0; }
  .import-stat { background:#f9fafb; border-left:3px solid #059669; border-radius:8px; padding:14px; }
  .import-stat .sl { font-size:11px; color:#6b7280; font-weight:600; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
  .import-stat .sv { font-size:1.2rem; font-weight:700; color:#111827; }
  .file-item { background:#f9fafb; border-left:4px solid #3b82f6; border-radius:8px; padding:14px; margin-bottom:10px; }
  .file-item:last-child { margin-bottom:0; }
  .file-name { font-size:13px; font-weight:700; color:#111827; margin-bottom:8px; word-break:break-word; }
  .file-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; }
  .fs { text-align:center; }
  .fs-label { font-size:11px; color:#6b7280; }
  .fs-value { font-size:15px; font-weight:700; color:#111827; }
  .es-table { width:100%; border-collapse:collapse; font-size:13px; margin:10px 0; }
  .es-table td { padding:8px 4px; border-bottom:1px dashed #e5e7eb; }
  .es-table td:last-child { text-align:right; font-weight:600; }
  .es-table tr:last-child td { border-bottom:none; }
  .success-highlight { background:#ecfdf5; border:1px solid #059669; border-radius:8px; padding:16px; text-align:center; margin:16px 0; }
  .success-highlight p { font-size:15px; font-weight:600; color:#047857; margin:0; }
  @media (max-width:500px) { .import-grid { grid-template-columns:1fr; } .file-stats { grid-template-columns:repeat(2,1fr); } }
</style>

<div class="email-heading-strip" style="background:linear-gradient(135deg,#064e3b,#059669)">
    <h1>✅ Import Completed Successfully!</h1>
    <p>Your product import has finished processing</p>
</div>

@if(isset($batchStats) && count($batchStats['jobs']) > 1)
<h2>📦 Batch Summary</h2>
<div class="import-grid">
    <div class="import-stat"><div class="sl">Total Files</div><div class="sv">{{ $batchStats['total_files'] ?? 1 }}</div></div>
    <div class="import-stat"><div class="sl">Total Products</div><div class="sv">{{ number_format($batchStats['total_items'] ?? 0) }}</div></div>
    <div class="import-stat"><div class="sl">Batch ID</div><div class="sv" style="font-size:.75rem">{{ $importJob->batch_id }}</div></div>
    <div class="import-stat"><div class="sl">Duration</div><div class="sv" style="font-size:.9rem">
        @if($importJob->completed_at && $importJob->created_at)
            {{ $importJob->created_at->diffForHumans($importJob->completed_at, true) }}
        @else N/A @endif
    </div></div>
</div>
@endif

<h2>📄 @if(isset($batchStats) && count($batchStats['jobs']) > 1) Imported Files ({{ count($batchStats['jobs']) }}) @else Import Details @endif</h2>

<div>
    @if(isset($batchStats) && !empty($batchStats['jobs']))
        @foreach($batchStats['jobs'] as $job)
        <div class="file-item">
            <div class="file-name">📁 {{ $job['filename'] }}</div>
            <div class="file-stats">
                <div class="fs"><div class="fs-label">Total</div><div class="fs-value">{{ number_format($job['total_items'] ?? 0) }}</div></div>
                <div class="fs"><div class="fs-label">Processed</div><div class="fs-value" style="color:#059669">{{ number_format($job['processed_items'] ?? 0) }}</div></div>
                <div class="fs"><div class="fs-label">Updated</div><div class="fs-value" style="color:#3b82f6">{{ number_format($job['updated_items'] ?? 0) }}</div></div>
                <div class="fs"><div class="fs-label">Skipped</div><div class="fs-value" style="color:#f59e0b">{{ number_format($job['skipped_items'] ?? 0) }}</div></div>
            </div>
        </div>
        @endforeach
    @else
        <div class="file-item">
            <div class="file-name">📁 {{ $importJob->filename }}</div>
            <div class="file-stats">
                <div class="fs"><div class="fs-label">Total</div><div class="fs-value">{{ number_format($importJob->total_items ?? 0) }}</div></div>
                <div class="fs"><div class="fs-label">Processed</div><div class="fs-value" style="color:#059669">{{ number_format($importJob->processed_items ?? 0) }}</div></div>
                <div class="fs"><div class="fs-label">Updated</div><div class="fs-value" style="color:#3b82f6">{{ number_format($importJob->updated_items ?? 0) }}</div></div>
                <div class="fs"><div class="fs-label">Skipped</div><div class="fs-value" style="color:#f59e0b">{{ number_format($importJob->skipped_items ?? 0) }}</div></div>
            </div>
        </div>
    @endif
</div>

@if(isset($batchStats['elasticsearch']) && $batchStats['elasticsearch']['status'] !== 'skipped')
<h2>🔍 Elasticsearch Indexing</h2>
@if($batchStats['elasticsearch']['status'] === 'completed')
    @php
        $esStats = $batchStats['elasticsearch'];
        $successRate = $esStats['attempted'] > 0 ? round(($esStats['indexed'] / $esStats['attempted']) * 100, 1) : 0;
    @endphp
    <table class="es-table">
        <tr><td>Status</td><td style="color:#059669">✅ Completed</td></tr>
        <tr><td>Products Indexed</td><td>{{ number_format($esStats['indexed']) }} / {{ number_format($esStats['attempted']) }}</td></tr>
        <tr><td>Success Rate</td><td>{{ $successRate }}%</td></tr>
        <tr><td>Failed</td><td>{{ number_format($esStats['failed']) }}</td></tr>
        <tr><td>Duration</td><td>{{ $esStats['duration'] }} seconds</td></tr>
    </table>
    <div class="highlight-box" style="background:#dbeafe;border-left-color:#3b82f6;color:#1e40af">
        🎯 <strong>All products are now searchable!</strong> Your imported products have been successfully indexed in Elasticsearch.
    </div>
@elseif($batchStats['elasticsearch']['status'] === 'failed')
    <div class="highlight-box">
        ⚠️ <strong>Indexing Failed</strong><br>
        Products were imported successfully but Elasticsearch indexing encountered an error. You can manually reindex them later.
    </div>
@endif
@endif

<div class="success-highlight">
    <p>✨ {{ number_format($batchStats['processed_items'] ?? $importJob->processed_items ?? 0) }} products successfully imported and ready for sale!</p>
</div>

<div class="btn-wrap">
    <a href="{{ url('/admin/import-fast/history') }}" class="btn btn-primary">📋 View Import History</a>
</div>

@include('emails.partials.layout-close', ['isInteractive' => false])
