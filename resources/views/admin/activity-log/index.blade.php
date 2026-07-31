@extends('admin.layout')

@section('title', 'Audit Trail — Admin')

@push('styles')
<style>
.al-hero {
    background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
    border-radius: 16px;
    padding: 24px 28px;
    margin-bottom: 24px;
    color: #fff;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 20px;
}
.al-hero::before {
    content:'';
    position:absolute;top:-40px;right:-40px;
    width:200px;height:200px;border-radius:50%;
    background:radial-gradient(circle,rgba(168,85,247,.25) 0%,transparent 70%);
    pointer-events:none;
}
.al-stat {
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.12);
    border-radius:12px;
    padding:14px 20px;
    text-align:center;
    min-width:110px;
}
.al-stat-val { font-size:1.6rem;font-weight:800;line-height:1; }
.al-stat-lbl { font-size:.65rem;text-transform:uppercase;letter-spacing:.6px;opacity:.65;margin-top:4px; }

.al-filter-bar {
    background:#fff;
    border-radius:12px;
    border:1px solid #e2e8f0;
    padding:14px 18px;
    margin-bottom:20px;
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    align-items:flex-end;
}
.al-filter-bar label { font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#64748b;display:block;margin-bottom:3px; }
.al-filter-bar input,
.al-filter-bar select { font-size:.8rem;border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;background:#f8fafc; }

.al-timeline { position:relative;padding-left:0; }

.event-badge {
    display:inline-flex;align-items:center;gap:4px;
    padding:3px 10px;border-radius:20px;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;
}
.ev-created  { background:#dcfce7;color:#15803d; }
.ev-updated  { background:#dbeafe;color:#1d4ed8; }
.ev-deleted  { background:#fee2e2;color:#dc2626; }
.ev-custom   { background:#f3e8ff;color:#7e22ce; }
.ev-auth     { background:#fef9c3;color:#854d0e; }

.al-table thead th { background:linear-gradient(135deg,#0f0c29,#302b63);color:#94a3b8;font-size:.68rem;text-transform:uppercase;letter-spacing:.6px;padding:12px 14px;border:none;white-space:nowrap; }
.al-table thead th:first-child { color:#fff; }
.al-table tbody td { padding:11px 14px;vertical-align:middle;border-bottom:1px solid #f0f4f8;font-size:.82rem; }
.al-table tbody tr:nth-child(odd) { background:#fff; }
.al-table tbody tr:nth-child(even) { background:#f8f6ff; }
.al-table tbody tr:hover td { background:#ede9fe !important; }

.diff-pill {
    display:inline-block;padding:1px 6px;border-radius:4px;font-size:.7rem;font-family:monospace;
}
.diff-old { background:#fee2e2;color:#991b1b; }
.diff-new { background:#dcfce7;color:#166534; }

.al-avatar {
    width:30px;height:30px;border-radius:50%;
    display:inline-flex;align-items:center;justify-content:center;
    font-weight:700;font-size:.75rem;color:#fff;flex-shrink:0;
}
</style>
@endpush

@section('content')
<div class="container-fluid">

{{-- Hero Header --}}
<div class="al-hero">
    <div style="width:52px;height:52px;border-radius:14px;background:rgba(168,85,247,.25);border:1px solid rgba(168,85,247,.4);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-shield-lock-fill" style="font-size:1.5rem;color:#c084fc;"></i>
    </div>
    <div>
        <h2 style="margin:0;font-size:1.3rem;font-weight:800;">Audit Trail</h2>
        <p style="margin:0;opacity:.65;font-size:.78rem;">Full activity log — who did what, when, and on which entity</p>
    </div>
    <div style="margin-left:auto;display:flex;gap:12px;flex-wrap:wrap;">
        <div class="al-stat">
            <div class="al-stat-val">{{ number_format($stats['total']) }}</div>
            <div class="al-stat-lbl">Total Events</div>
        </div>
        <div class="al-stat">
            <div class="al-stat-val">{{ number_format($stats['today']) }}</div>
            <div class="al-stat-lbl">Today</div>
        </div>
        <div class="al-stat">
            <div class="al-stat-val">{{ number_format($stats['week']) }}</div>
            <div class="al-stat-lbl">Last 7 Days</div>
        </div>
        <div class="al-stat">
            <div class="al-stat-val">{{ number_format($stats['users']) }}</div>
            <div class="al-stat-lbl">Actors</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<form method="GET" action="{{ route('admin.activity-log.index') }}" class="al-filter-bar">
    <div>
        <label>User / Actor</label>
        <select name="user_id" onchange="this.form.submit()">
            <option value="">All Users</option>
            @foreach($allUsers as $u)
                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Module</label>
        <select name="log_name" onchange="this.form.submit()">
            <option value="">All Modules</option>
            @foreach($logNames as $ln)
                <option value="{{ $ln }}" {{ $logName === $ln ? 'selected' : '' }}>{{ ucfirst($ln) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Event</label>
        <select name="event" onchange="this.form.submit()">
            <option value="">All Events</option>
            @foreach($events as $ev)
                <option value="{{ $ev }}" {{ $event === $ev ? 'selected' : '' }}>{{ ucfirst($ev) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>From</label>
        <input type="date" name="date_from" value="{{ $dateFrom ? $dateFrom->format('Y-m-d') : '' }}">
    </div>
    <div>
        <label>To</label>
        <input type="date" name="date_to" value="{{ $dateTo ? $dateTo->format('Y-m-d') : '' }}">
    </div>
    <div style="flex:1;min-width:180px;">
        <label>Search Description</label>
        <input type="text" name="search" value="{{ $search }}" placeholder="e.g. Order #1234..." style="width:100%;">
    </div>
    <div style="display:flex;gap:8px;align-items:flex-end;">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-search me-1"></i>Filter
        </button>
        @if($userId || $event || $logName || $search || $dateFrom || $dateTo)
            <a href="{{ route('admin.activity-log.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg me-1"></i>Reset
            </a>
        @endif
    </div>
</form>

{{-- Results count --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
    <span style="font-size:.78rem;color:#64748b;">
        Showing <strong>{{ $query->firstItem() }}–{{ $query->lastItem() }}</strong> of <strong>{{ number_format($query->total()) }}</strong> events
    </span>
    <span style="font-size:.75rem;color:#94a3b8;">Page {{ $query->currentPage() }} of {{ $query->lastPage() }}</span>
</div>

{{-- Activity Table --}}
<div class="table-responsive">
    <table class="table al-table mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Actor</th>
                <th>Event</th>
                <th>Module</th>
                <th>Description</th>
                <th>Entity</th>
                <th>Changes (Old → New)</th>
                <th>IP</th>
                <th>Date &amp; Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($query as $log)
                @php
                    $causerName   = $log->causer?->name ?? 'System';
                    $causerInitial = strtoupper(substr($causerName, 0, 1));
                    $colors = ['#6366f1','#8b5cf6','#ec4899','#14b8a6','#f59e0b','#10b981','#3b82f6'];
                    $color = $colors[crc32($causerName) % count($colors)];
                    $evClass = match($log->event) {
                        'created' => 'ev-created',
                        'updated' => 'ev-updated',
                        'deleted' => 'ev-deleted',
                        'login','logout' => 'ev-auth',
                        default => 'ev-custom',
                    };
                    $entityLabel = class_basename($log->subject_type ?? '');
                @endphp
                <tr>
                    <td style="color:#94a3b8;font-size:.72rem;">{{ $log->id }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="al-avatar" style="background:{{ $color }};">{{ $causerInitial }}</div>
                            <div>
                                <div style="font-weight:600;font-size:.8rem;">{{ $causerName }}</div>
                                @if($log->causer?->email)
                                    <div style="font-size:.68rem;color:#94a3b8;">{{ $log->causer->email }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td><span class="event-badge {{ $evClass }}">{{ $log->event }}</span></td>
                    <td>
                        <span style="background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:6px;font-size:.72rem;font-weight:600;">
                            {{ ucfirst($log->log_name) }}
                        </span>
                    </td>
                    <td style="max-width:260px;">{{ $log->description }}</td>
                    <td>
                        @if($log->subject_id && $entityLabel)
                            <span style="font-size:.72rem;color:#475569;">
                                {{ $entityLabel }} <strong>#{{ $log->subject_id }}</strong>
                            </span>
                        @else
                            <span style="color:#cbd5e1;">—</span>
                        @endif
                    </td>
                    <td style="max-width:300px;">
                        @if(!empty($log->old) || !empty($log->new))
                            <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                @foreach($log->new as $field => $newVal)
                                    @php $oldVal = $log->old[$field] ?? null; @endphp
                                    <div style="display:flex;align-items:center;gap:3px;flex-wrap:wrap;margin-bottom:2px;">
                                        <span style="font-size:.65rem;color:#64748b;margin-right:2px;">{{ $field }}:</span>
                                        @if($oldVal !== null)
                                            <span class="diff-pill diff-old">{{ mb_strimwidth((string)$oldVal, 0, 25, '…') }}</span>
                                            <span style="color:#94a3b8;font-size:.7rem;">→</span>
                                        @endif
                                        <span class="diff-pill diff-new">{{ mb_strimwidth((string)$newVal, 0, 25, '…') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span style="color:#cbd5e1;font-size:.72rem;">—</span>
                        @endif
                    </td>
                    <td style="font-size:.72rem;color:#64748b;font-family:monospace;">{{ $log->ip_address }}</td>
                    <td style="font-size:.72rem;white-space:nowrap;">
                        <div style="font-weight:600;">{{ $log->created_at->format('d M Y') }}</div>
                        <div style="color:#94a3b8;">{{ $log->created_at->format('H:i:s') }}</div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:50px;color:#94a3b8;">
                        <i class="bi bi-shield-check" style="font-size:2.5rem;display:block;margin-bottom:10px;color:#cbd5e1;"></i>
                        No activity logged yet{{ ($userId || $event || $logName || $search) ? ' for these filters' : '' }}.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="d-flex justify-content-center mt-3">
    {{ $query->links() }}
</div>

</div>
@endsection
