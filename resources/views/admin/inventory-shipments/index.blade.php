@extends('admin.layout')

@section('title', 'Inventory Shipments')

@section('content')
<style>
    /* ── Filter card ── */
    .filter-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    /* ── Stat cards ── */
    .stat-card          { background: linear-gradient(135deg,#f093fb,#f5576c); color:#fff; border:none; }
    .stat-card.success  { background: linear-gradient(135deg,#4facfe,#00f2fe); }
    .stat-card.warning  { background: linear-gradient(135deg,#fa709a,#fee140); }
    .stat-card.info     { background: linear-gradient(135deg,#a8edea,#fed6e3); }

    /* ── Destination badge colours ── */
    [class*="badge-destination-"]       { background-color:#6c757d !important; color:#fff !important; }
    .badge-destination-harare           { background:linear-gradient(135deg,#667eea,#764ba2) !important; color:#fff !important; }
    .badge-destination-bulawayo         { background:linear-gradient(135deg,#f093fb,#f5576c) !important; color:#fff !important; }
    .badge-destination-mutare           { background:linear-gradient(135deg,#4facfe,#00f2fe) !important; color:#fff !important; }
    .badge-destination-zambia           { background:linear-gradient(135deg,#43e97b,#38f9d7) !important; color:#fff !important; }

    /* ── Card-table wrapper ── */
    .is-table-wrap { padding: 0 4px; }
    .is-table {
        width:100%;
        border-collapse:separate;
        border-spacing:0 8px;
        table-layout:auto;
    }

    /* ── Header row ── */
    .is-table thead tr {
        background:linear-gradient(135deg,#1e293b 0%,#334155 100%);
    }
    .is-table thead th {
        background:transparent; color:#cbd5e1;
        font-size:.6rem; font-weight:800; letter-spacing:.12em;
        text-transform:uppercase; padding:.7rem 1rem;
        border:none; white-space:nowrap;
    }
    .is-table thead tr th:first-child { border-radius:10px 0 0 10px; }
    .is-table thead tr th:last-child  { border-radius:0 10px 10px 0; }

    /* ── Data rows ── */
    .is-table tbody tr td {
        background:#f7f9fd;
        padding:.8rem 1rem;
        border-top:1px solid #eef2f7;
        border-bottom:1px solid #eef2f7;
        vertical-align:middle;
    }
    .is-table tbody tr td:first-child {
        border-left:1px solid #eef2f7;
        border-radius:10px 0 0 10px;
    }
    .is-table tbody tr td:last-child {
        border-right:1px solid #eef2f7;
        border-radius:0 10px 10px 0;
    }
    .is-table tbody tr:hover td {
        background:#edf2ff;
        border-color:#c7d3f7;
        box-shadow:0 4px 16px rgba(59,130,246,.09);
    }

    /* ── Action icon pills ── */
    .is-act { display:inline-flex; align-items:center; gap:4px;
              border:none; border-radius:8px; padding:.28rem .55rem;
              font-size:.73rem; font-weight:600; cursor:pointer;
              transition:all .12s; text-decoration:none; white-space:nowrap; }
    .is-act-edit    { background:#eef2ff; color:#4f46e5; }
    .is-act-edit:hover   { background:#e0e7ff; }
    .is-act-hist    { background:#eff6ff; color:#0284c7; }
    .is-act-hist:hover   { background:#dbeafe; }
    .is-act-waybill { background:#f0fdf4; color:#15803d; }
    .is-act-waybill:hover{ background:#dcfce7; }
    .is-act-sticker { background:#fafafa; color:#0f172a; border:1px solid #e2e8f0; }
    .is-act-sticker:hover{ background:#f1f5f9; }
    .is-act-copy    { background:#fafafa; color:#64748b; border:1px solid #e2e8f0; }
    .is-act-copy:hover   { background:#f1f5f9; }
    .is-act-del     { background:#fef2f2; color:#dc2626; }
    .is-act-del:hover    { background:#fee2e2; }

    /* ── Bulk actions bar ── */
    .is-bulk-bar {
        background:linear-gradient(135deg,#eef2ff,#f0f9ff);
        border:1.5px solid #c7d2fe;
        border-radius:14px;
        padding:.75rem 1.1rem;
        margin-bottom:1rem;
        display:flex;
        flex-wrap:wrap;
        align-items:center;
        justify-content:space-between;
        gap:.75rem;
    }
    .is-bulk-count {
        background:#6366f1; color:#fff;
        border-radius:50px; font-size:.72rem;
        font-weight:800; padding:3px 12px;
    }

    /* Mobile */
    @media(max-width:768px)  { .filter-section { margin-bottom:1rem; } }
    @media(max-width:1024px) { .container-fluid { padding:0 15px; max-width:100%; overflow-x:hidden; } }

    /* ── Mobile filter fixes ── */
    @media(max-width:768px) {
        .filter-card .card-body { padding: 12px; }
        .filter-card .row.g-3 > [class*="col-"] {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
        .filter-card .form-select,
        .filter-card .form-control {
            font-size: 14px !important;
            min-height: 40px !important;
            padding: 8px 10px !important;
            background-color: rgba(255,255,255,0.95) !important;
            color: #333 !important;
            border: 1px solid rgba(255,255,255,0.3) !important;
            border-radius: 8px !important;
            -webkit-appearance: none !important;
        }
        .filter-card .form-select option {
            color: #333 !important;
            background: #fff !important;
        }
        .filter-card .form-label {
            font-size: 12px !important;
            margin-bottom: 3px !important;
            opacity: 0.9;
        }
        .filter-card .btn {
            width: 48% !important;
            min-height: 40px !important;
            font-size: 13px !important;
        }
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="mb-2 mb-md-0">
                    <h2 class="mb-0">
                        <i class="bi bi-truck"></i> Inventory Shipments
                    </h2>
                    <p class="text-muted">Track and manage all inventory shipments</p>
                </div>
                <div>
                    @can('inventory-shipment.create')
                    <a href="{{ route('admin.inventory-shipments.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add New Shipment
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.inventory-shipments.index') }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small">Destination</label>
                        <select name="destination" class="form-select form-select-sm">
                            <option value="">All Destinations</option>
                            <option value="Harare" {{ request('destination') == 'Harare' ? 'selected' : '' }}>Harare</option>
                            <option value="Bulawayo" {{ request('destination') == 'Bulawayo' ? 'selected' : '' }}>Bulawayo</option>
                            <option value="Mutare" {{ request('destination') == 'Mutare' ? 'selected' : '' }}>Mutare</option>
                            <option value="Zambia" {{ request('destination') == 'Zambia' ? 'selected' : '' }}>Zambia</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small">WH Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All WH Statuses</option>
                            <option value="Received" {{ request('status') == 'Received' ? 'selected' : '' }}>Received</option>
                            <option value="Not yet" {{ request('status') == 'Not yet' ? 'selected' : '' }}>Not yet</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small">Transporter</label>
                        <select name="transporter" class="form-select form-select-sm">
                            <option value="">All Transporters</option>
                            @foreach($transporters as $transporter)
                                <option value="{{ $transporter }}" {{ request('transporter') == $transporter ? 'selected' : '' }}>
                                    {{ $transporter }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small">Destination Status</label>
                        <select name="f_status" class="form-select form-select-sm">
                            <option value="">All Destination Statuses</option>
                            @foreach($fStatuses as $fStatus)
                                <option value="{{ $fStatus }}" {{ request('f_status') == $fStatus ? 'selected' : '' }}>
                                    {{ $fStatus }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small">Signed By</label>
                        <select name="signed_by" class="form-select form-select-sm">
                            <option value="">All Signers</option>
                            @foreach($signedByUsers as $staff)
                                <option value="{{ $staff->id }}" {{ request('signed_by') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small">Received By</label>
                        <select name="received_by" class="form-select form-select-sm">
                            <option value="">All Staff</option>
                            @foreach($staffUsers as $staff)
                                <option value="{{ $staff->id }}" {{ request('received_by') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small">Start Date</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small">End Date</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label small">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Search order ID, title, transporter, destination, status, notes..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-light btn-sm">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.inventory-shipments.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Shipments List -->
    <div class="card">
        <div class="card-body">
            @if($shipments->count() > 0)
                <!-- Desktop Table View -->
                <div class="d-none d-lg-block">

                    <!-- Bulk Actions Bar -->
                    <div id="bulkActionsBar" class="is-bulk-bar" style="display:none;">
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <span class="is-bulk-count" id="selectedCount">0</span>
                            <span style="font-size:.83rem;font-weight:600;color:#3730a3;">items selected</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @can('inventory-shipment.edit')
                            <button type="button"
                                    style="background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;border:none;border-radius:50px;font-size:.72rem;font-weight:700;padding:.32rem 1rem;cursor:pointer;"
                                    onclick="openBulkEditModal()">
                                <i class="bi bi-pencil-square me-1"></i>Bulk Edit
                            </button>
                            <button type="button"
                                    style="background:linear-gradient(135deg,#0f172a,#334155);color:#fff;border:none;border-radius:50px;font-size:.72rem;font-weight:700;padding:.32rem 1rem;cursor:pointer;"
                                    onclick="bulkGenerateStickers()">
                                <i class="bi bi-upc me-1"></i>QR Stickers
                            </button>
                            @endcan
                            @can('inventory-shipment.destroy')
                            <button type="button"
                                    style="background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;border-radius:50px;font-size:.72rem;font-weight:700;padding:.32rem 1rem;cursor:pointer;"
                                    onclick="bulkDelete()">
                                <i class="bi bi-trash me-1"></i>Delete
                            </button>
                            @endcan
                            <button type="button"
                                    style="border:1.5px solid #334155;background:transparent;color:#334155;border-radius:50px;font-size:.72rem;font-weight:700;padding:.3rem .9rem;cursor:pointer;"
                                    onclick="clearSelection()">
                                <i class="bi bi-x-circle me-1"></i>Clear
                            </button>
                        </div>
                    </div>

                    {{-- Hidden form for bulk sticker POST --}}
                    <form id="bulkStickerForm" method="POST" action="{{ route('admin.inventory-shipments.bulk-generate-stickers') }}" target="_blank" style="display:none;">
                        @csrf
                        <div id="bulkStickerIds"></div>
                    </form>

                    <div class="is-table-wrap">
                      <table class="is-table">
                        <thead>
                          <tr>
                            <th style="width:32px;">
                              <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll()">
                            </th>
                            <th style="width:120px;">
                              <a href="?sort_by=order&sort_order={{ request('sort_by') == 'order' && request('sort_order') == 'asc' ? 'desc' : 'asc' }}&{{ http_build_query(request()->except(['sort_by', 'sort_order'])) }}"
                                 style="color:#cbd5e1;text-decoration:none;font:inherit;">
                                Order <i class="bi bi-arrow-down-up ms-1" style="opacity:.6;"></i>
                              </a>
                            </th>
                            <th style="width:220px;">Product &amp; Info</th>
                            <th style="width:130px;">Destination</th>
                            <th style="width:190px;">Status &amp; Dates</th>
                            <th style="width:200px;">Personnel</th>
                            <th style="width:130px;">Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($shipments as $shipment)
                          @php
                              $whColor  = $shipment->status === 'Received' ? '#16a34a' : '#d97706';
                              $fstColor = match(true) {
                                  $shipment->f_status === 'Received' => '#16a34a',
                                  $shipment->f_status === 'Not yet'  => '#d97706',
                                  default                            => '#64748b',
                              };
                          @endphp
                          <tr data-id="{{ $shipment->id }}">
                            {{-- Checkbox — left stripe = WH status colour --}}
                            <td style="box-shadow:inset 5px 0 0 {{ $whColor }};" onclick="event.stopPropagation()">
                              <input type="checkbox" class="form-check-input shipment-checkbox" value="{{ $shipment->id }}" onchange="updateBulkActions()">
                            </td>

                            {{-- Order ID + SRS --}}
                            <td onclick="openEditModal({{ $shipment->id }})" style="cursor:pointer;">
                              <span style="display:inline-block;border:2px solid {{ $whColor }};color:#1e293b;border-radius:7px;font-size:.76rem;font-weight:800;padding:2px 9px;">
                                #{{ $shipment->order ?? '—' }}
                              </span>
                              @if($shipment->srs)
                              <div style="margin-top:5px;font-size:.68rem;color:#64748b;">
                                <i class="bi bi-person-badge" style="font-size:.65rem;"></i>&nbsp;<strong>SRS:</strong> {{ $shipment->srs }}
                              </div>
                              @endif
                              <div style="margin-top:3px;font-size:.65rem;color:#94a3b8;">
                                <i class="bi bi-calendar3" style="font-size:.6rem;"></i>&nbsp;{{ $shipment->created_at->format('d M y') }}
                              </div>
                            </td>

                            {{-- Title + qty + transporter + SRS + notes --}}
                            <td onclick="openEditModal({{ $shipment->id }})" style="cursor:pointer;">
                              <div style="font-size:.9rem;font-weight:700;color:#1e293b;line-height:1.3;">{{ Str::limit($shipment->title, 60) }}</div>
                              @if($shipment->notes)
                              <div style="font-size:.7rem;color:#64748b;margin-top:3px;font-style:italic;" title="{{ $shipment->notes }}">
                                <i class="bi bi-chat-text" style="font-size:.65rem;"></i>&nbsp;{{ Str::limit($shipment->notes, 55) }}
                              </div>
                              @endif
                              <div style="margin-top:5px;display:flex;flex-wrap:wrap;gap:5px;">
                                <span style="border:1.5px solid #0284c7;color:#0284c7;background:transparent;font-size:.6rem;font-weight:700;padding:2px 8px;border-radius:50px;">
                                  <i class="bi bi-box"></i>&nbsp;Qty: {{ number_format($shipment->quantity) }}
                                </span>
                                @if($shipment->srs)
                                <span style="border:1.5px solid #0d9488;color:#0d9488;background:transparent;font-size:.6rem;font-weight:700;padding:2px 8px;border-radius:50px;">
                                  <i class="bi bi-person-badge"></i>&nbsp;SRS: {{ $shipment->srs }}
                                </span>
                                @endif
                                @if($shipment->transporter)
                                <span style="border:1.5px solid #7c3aed;color:#7c3aed;background:transparent;font-size:.6rem;font-weight:700;padding:2px 8px;border-radius:50px;">
                                  <i class="bi bi-truck"></i>&nbsp;{{ $shipment->transporter }}
                                </span>
                                @endif
                              </div>
                            </td>

                            {{-- Destination --}}
                            <td onclick="openEditModal({{ $shipment->id }})" style="cursor:pointer;">
                              @if($shipment->destination)
                              @php
                                  $destLow = strtolower($shipment->destination);
                                  [$dColor, $dIcon] = match(true) {
                                      str_contains($destLow,'harare')   => ['#7c3aed','bi-building'],
                                      str_contains($destLow,'bulawayo') => ['#db2777','bi-city'],
                                      str_contains($destLow,'mutare')   => ['#0284c7','bi-geo-alt-fill'],
                                      str_contains($destLow,'zambia') || str_contains($destLow,'lusaka') => ['#15803d','bi-airplane-fill'],
                                      default                           => ['#475569','bi-geo-alt'],
                                  };
                              @endphp
                              <div style="font-size:.84rem;font-weight:700;color:{{ $dColor }};">
                                <i class="bi {{ $dIcon }} me-1"></i>{{ $shipment->destination }}
                              </div>
                              @endif
                            </td>

                            {{-- WH Status + Dates + Dest Status --}}
                            <td onclick="openEditModal({{ $shipment->id }})" style="cursor:pointer;">

                              {{-- WH Status --}}
                              <div style="font-size:.6rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">WH Status</div>
                              <div style="margin-top:3px;">
                                <span style="display:inline-flex;align-items:center;background:{{ $whColor }}20;color:{{ $whColor }};border:1.5px solid {{ $whColor }};border-radius:50px;font-size:.65rem;font-weight:800;padding:2px 10px;">
                                  @if($shipment->status === 'Received')<i class="bi bi-check-circle-fill me-1"></i>@else<i class="bi bi-hourglass-split me-1"></i>@endif
                                  {{ $shipment->status }}
                                </span>
                              </div>

                              {{-- Ship Date --}}
                              <div style="margin-top:6px;font-size:.68rem;color:#64748b;">
                                <i class="bi bi-calendar3" style="font-size:.63rem;"></i>&nbsp;<strong>Ship Date:</strong> {{ $shipment->date?->format('d M Y') ?? '—' }}
                              </div>

                              {{-- ETA --}}
                              <div style="margin-top:3px;font-size:.68rem;color:#64748b;">
                                <i class="bi bi-calendar-check" style="font-size:.63rem;color:#7c3aed;"></i>&nbsp;<strong>ETA:</strong> {{ $shipment->eta?->format('d M Y') ?? '—' }}
                              </div>

                              {{-- Destination Status --}}
                              @if($shipment->f_status)
                              <div style="margin-top:6px;">
                                <div style="font-size:.6rem;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Dest. Status</div>
                                <div style="margin-top:3px;">
                                  <span style="display:inline-flex;align-items:center;background:{{ $fstColor }}20;color:{{ $fstColor }};border:1.5px solid {{ $fstColor }};border-radius:50px;font-size:.62rem;font-weight:700;padding:2px 9px;">
                                    @if($shipment->f_status === 'Received')<i class="bi bi-check-circle-fill me-1"></i>@elseif($shipment->f_status === 'Not yet')<i class="bi bi-hourglass-split me-1"></i>@endif
                                    {{ $shipment->f_status }}
                                  </span>
                                </div>
                              </div>
                              @endif

                            </td>

                            {{-- Personnel --}}
                            <td onclick="openEditModal({{ $shipment->id }})" style="cursor:pointer;">
                              @if($shipment->signedBy)
                              <div style="font-size:.73rem;color:#334155;">
                                <i class="bi bi-pen-fill" style="font-size:.65rem;color:#6366f1;"></i>&nbsp;<strong>Signed:</strong> {{ $shipment->signedBy->name }}
                              </div>
                              @endif
                              @if($shipment->receivedBy)
                              <div style="font-size:.73rem;color:#334155;margin-top:4px;">
                                <i class="bi bi-box-arrow-in-down" style="font-size:.65rem;color:#16a34a;"></i>&nbsp;<strong>Recv:</strong> {{ $shipment->receivedBy->name }}
                              </div>
                              @endif
                              @if($shipment->createdBy)
                              <div style="font-size:.65rem;color:#94a3b8;margin-top:5px;border-top:1px solid #eef2f7;padding-top:4px;">
                                <i class="bi bi-person" style="font-size:.6rem;"></i>&nbsp;<strong>Signed:</strong> {{ $shipment->createdBy->name }}
                                <span style="margin-left:4px;">&bull; {{ $shipment->created_at->format('d M y') }}</span>
                              </div>
                              @endif
                            </td>

                            {{-- Actions --}}
                            <td onclick="event.stopPropagation()">
                              <div style="display:flex;flex-wrap:wrap;gap:5px;align-items:center;">
                                @can('inventory-shipment.edit')
                                <button class="is-act is-act-edit" onclick="openEditModal({{ $shipment->id }})" title="Edit shipment">
                                  <i class="bi bi-pencil"></i>
                                </button>
                                @endcan
                                @can('inventory-shipment.show')
                                <button class="is-act is-act-hist" onclick="viewHistory({{ $shipment->id }})" title="View history">
                                  <i class="bi bi-clock-history"></i>
                                </button>
                                @endcan
                                @can('inventory-shipment.edit')
                                @php $isAdminUser = auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->hasRole('super_admin'); @endphp
                                @if($isAdminUser || !$shipment->waybill_downloaded_at)
                                <a class="is-act is-act-waybill" href="{{ route('admin.inventory-shipments.generate-waybill', $shipment->id) }}" target="_blank" title="Generate Waybill PDF{{ $shipment->waybill_downloaded_at ? ' (Downloaded: '.$shipment->waybill_downloaded_at->format('d M Y H:i').')' : '' }}">
                                  <i class="bi bi-qr-code"></i>
                                </a>
                                @else
                                <span class="is-act" style="opacity:.4;cursor:not-allowed;color:#6c757d" title="Waybill already downloaded on {{ $shipment->waybill_downloaded_at->format('d M Y H:i') }}. Contact admin for another copy.">
                                  <i class="bi bi-qr-code"></i>
                                </span>
                                @endif
                                @if($isAdminUser || !$shipment->sticker_downloaded_at)
                                <a class="is-act is-act-sticker" href="{{ route('admin.inventory-shipments.generate-sticker', $shipment->id) }}" target="_blank" title="Print QR Sticker (50×33mm){{ $shipment->sticker_downloaded_at ? ' (Downloaded: '.$shipment->sticker_downloaded_at->format('d M Y H:i').')' : '' }}">
                                  <i class="bi bi-upc"></i>
                                </a>
                                @else
                                <span class="is-act" style="opacity:.4;cursor:not-allowed;color:#6c757d" title="Sticker already downloaded on {{ $shipment->sticker_downloaded_at->format('d M Y H:i') }}. Contact admin for another copy.">
                                  <i class="bi bi-upc"></i>
                                </span>
                                @endif
                                <button class="is-act is-act-copy" onclick="copyQRData({{ $shipment->id }},'{{ addslashes($shipment->order ?? 'N/A') }}','{{ addslashes($shipment->title) }}',{{ $shipment->quantity }},'{{ addslashes($shipment->destination) }}')" title="Copy QR data to clipboard">
                                  <i class="bi bi-clipboard"></i>
                                </button>
                                @endcan
                                @can('inventory-shipment.destroy')
                                <button class="is-act is-act-del" onclick="deleteShipment({{ $shipment->id }})" title="Delete shipment">
                                  <i class="bi bi-trash"></i>
                                </button>
                                @endcan
                              </div>
                            </td>
                          </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                </div>

                <!-- Mobile Card View -->
                <div class="d-lg-none">
                    <!-- Mobile Bulk Bar -->
                    <div id="mobileBulkBar" style="display:none; background:linear-gradient(135deg,#eef2ff,#f0f9ff); border:1.5px solid #c7d2fe; border-radius:12px; padding:10px 14px; margin-bottom:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span class="is-bulk-count" id="mobileSelectedCount">0</span>
                            <span style="font-size:.8rem; font-weight:600; color:#3730a3;">selected</span>
                        </div>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            @can('inventory-shipment.edit')
                            <button type="button" class="btn btn-sm btn-primary" onclick="openBulkEditModal()" style="font-size:.75rem;">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            @endcan
                            <button type="button" class="btn btn-sm btn-info" onclick="bulkPrintStickers()" style="font-size:.75rem;">
                                <i class="bi bi-upc"></i> QR Stickers
                            </button>
                            @can('inventory-shipment.destroy')
                            <button type="button" class="btn btn-sm btn-danger" onclick="bulkDelete()" style="font-size:.75rem;">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                            @endcan
                        </div>
                    </div>
                    <div style="margin-bottom:8px;">
                        <label style="display:flex; align-items:center; gap:6px; font-size:.8rem; color:#666; cursor:pointer;">
                            <input type="checkbox" onclick="toggleAllMobile(this)"> Select All
                        </label>
                    </div>
                    @foreach($shipments as $shipment)
                    <div class="card shipment-card status-{{ strtolower(str_replace(' ', '-', $shipment->status)) }} mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div style="display:flex; align-items:flex-start; gap:8px;">
                                    <input type="checkbox" class="shipment-checkbox" value="{{ $shipment->id }}" onchange="updateMobileBulk()" onclick="event.stopPropagation()" style="width:18px; height:18px; margin-top:3px; flex-shrink:0; cursor:pointer;">
                                    <div onclick="openEditModal({{ $shipment->id }})" style="cursor:pointer;">
                                        <span class="badge bg-dark mb-1" style="font-size: 0.85rem;">
                                            <i class="bi bi-hash"></i> Order ID: {{ $shipment->order }}
                                        </span>
                                        <h6 class="mb-0">{{ $shipment->title }}</h6>
                                    </div>
                                </div>
                                <span class="badge bg-{{ $shipment->status_badge_color }}">{{ $shipment->status }}</span>
                            </div>
                            <div class="mb-2">
                                <span class="badge badge-destination-{{ strtolower($shipment->destination) }} me-1">
                                    <i class="bi bi-geo-alt-fill"></i> {{ $shipment->destination }}
                                </span>
                                <span class="badge bg-secondary">
                                    <i class="bi bi-box"></i> Qty: {{ number_format($shipment->quantity) }}
                                </span>
                            </div>
                            <small class="text-muted d-block">
                                <i class="bi bi-calendar"></i> Date: {{ $shipment->date?->format('M d, Y') ?? 'N/A' }}<br>
                                <i class="bi bi-calendar-check"></i> ETA: {{ $shipment->eta?->format('M d, Y') ?? 'N/A' }}
                            </small>
                            @if($shipment->transporter)
                            <small class="text-muted d-block">
                                <i class="bi bi-truck"></i> {{ $shipment->transporter }}
                            </small>
                            @endif
                            <div class="mt-2" onclick="event.stopPropagation()">
                                @can('inventory-shipment.edit')
                                <button class="btn btn-sm btn-outline-primary" onclick="openEditModal({{ $shipment->id }})">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                @php $isAdminUserMobile = auth()->user()->hasRole('admin') || auth()->user()->hasRole('administrator') || auth()->user()->hasRole('super_admin'); @endphp
                                @if($isAdminUserMobile || !$shipment->waybill_downloaded_at)
                                <a href="{{ route('admin.inventory-shipments.generate-waybill', $shipment->id) }}"
                                   class="btn btn-sm btn-outline-success"
                                   target="_blank"
                                   title="Generate Waybill">
                                    <i class="bi bi-qr-code"></i> QR
                                </a>
                                @else
                                <button class="btn btn-sm btn-outline-secondary" disabled title="Waybill already downloaded on {{ $shipment->waybill_downloaded_at->format('d M Y H:i') }}">
                                    <i class="bi bi-qr-code"></i> QR
                                </button>
                                @endif
                                @if($isAdminUserMobile || !$shipment->sticker_downloaded_at)
                                <a href="{{ route('admin.inventory-shipments.generate-sticker', $shipment->id) }}"
                                   class="btn btn-sm btn-outline-info"
                                   target="_blank"
                                   title="Print QR Sticker (50x33mm)">
                                    <i class="bi bi-upc"></i> Sticker
                                </a>
                                @else
                                <button class="btn btn-sm btn-outline-secondary" disabled title="Sticker already downloaded on {{ $shipment->sticker_downloaded_at->format('d M Y H:i') }}">
                                    <i class="bi bi-upc"></i> Sticker
                                </button>
                                @endif
                                <button class="btn btn-sm btn-outline-secondary"
                                        onclick="copyQRData({{ $shipment->id }}, '{{ addslashes($shipment->order ?? 'N/A') }}', '{{ addslashes($shipment->title) }}', {{ $shipment->quantity }}, '{{ addslashes($shipment->destination) }}')"
                                        title="Copy QR Data">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                @endcan
                                @can('inventory-shipment.destroy')
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteShipment({{ $shipment->id }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $shipments->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <p class="text-muted mt-3">No shipments found</p>
                    @can('inventory-shipment.create')
                    <a href="{{ route('admin.inventory-shipments.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add First Shipment
                    </a>
                    @endcan
                </div>
            @endif
        </div>
    </div>


    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="card stat-card">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                    <small>Total Shipments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card stat-card success">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['received'] }}</h3>
                    <small>Received</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card stat-card warning">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ $stats['not_yet'] }}</h3>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="card stat-card info">
                <div class="card-body text-center">
                    <h3 class="mb-0">{{ number_format($stats['total_quantity']) }}</h3>
                    <small>Total Quantity</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="bi bi-pencil"></i> Edit Shipment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editModalContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-labelledby="historyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="historyModalLabel">
                    <i class="bi bi-clock-history"></i> Shipment History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="historyModalContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Edit Modal -->
<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="bulkEditModalLabel">
                    <i class="bi bi-pencil-square"></i> Bulk Edit Shipments
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulkEditForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Only fill in the fields you want to update for all selected shipments. Leave fields blank to keep existing values.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="bulk_destination" class="form-label">Destination</label>
                            <select name="destination" id="bulk_destination" class="form-select">
                                <option value="">Don't change</option>
                                <option value="Harare">Harare</option>
                                <option value="Bulawayo">Bulawayo</option>
                                <option value="Mutare">Mutare</option>
                                <option value="Zambia">Zambia</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bulk_status" class="form-label">WH Status</label>
                            <select name="status" id="bulk_status" class="form-select">
                                <option value="">Don't change</option>
                                <option value="Received">Received</option>
                                <option value="Not yet">Not yet</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bulk_transporter" class="form-label">Transporter</label>
                            <input type="text" name="transporter" id="bulk_transporter" class="form-control" placeholder="Don't change">
                        </div>
                        <div class="col-md-6">
                            <label for="bulk_f_status" class="form-label">Destination Status</label>
                            <select name="f_status" id="bulk_f_status" class="form-select">
                                <option value="">Don't change</option>
                                <option value="Received">Received</option>
                                <option value="Not yet">Not yet</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bulk_signed_by" class="form-label">Signed By</label>
                            <select name="signed_by" id="bulk_signed_by" class="form-select">
                                <option value="">Don't change</option>
                                @foreach($signedByUsers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bulk_received_by" class="form-label">Received By</label>
                            <select name="received_by" id="bulk_received_by" class="form-select">
                                <option value="">Don't change</option>
                                @foreach($staffUsers as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bulk_assigned_to" class="form-label">Assigned To</label>
                            <select name="assigned_to" id="bulk_assigned_to" class="form-select">
                                <option value="">Don't change</option>
                                <option value="Mike">Mike</option>
                                <option value="Tinashe">Tinashe</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="bulk_srs" class="form-label">SRS</label>
                            <select name="srs" id="bulk_srs" class="form-select">
                                <option value="">Don't change</option>
                                <option value="Mike">Mike</option>
                                <option value="Tinashe">Tinashe</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Update Selected Shipments
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let editModal;
let historyModal;
let bulkEditModal;

document.addEventListener('DOMContentLoaded', function() {
    editModal = new bootstrap.Modal(document.getElementById('editModal'));
    historyModal = new bootstrap.Modal(document.getElementById('historyModal'));
    bulkEditModal = new bootstrap.Modal(document.getElementById('bulkEditModal'));

    // Bulk edit form submission
    document.getElementById('bulkEditForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitBulkEdit();
    });
});

function openEditModal(id) {
    const modalContent = document.getElementById('editModalContent');
    modalContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

    editModal.show();

    fetch(`/admin/inventory-shipments/${id}/data`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderEditForm(data.shipment);
            }
        })
        .catch(error => {
            modalContent.innerHTML = '<div class="alert alert-danger">Failed to load shipment data</div>';
        });
}

function renderEditForm(shipment) {
    const modalContent = document.getElementById('editModalContent');

    // Build staff options for Signed By (only 4 specific users)
    const signedByUsersData = @json($signedByUsers);
    // Build staff options for Received By (all staff)
    const staffUsersData = @json($staffUsers);

    // Get the actual ID value - handle both object and direct ID
    const signedById = shipment.signed_by?.id || shipment.signed_by;
    const receivedById = shipment.received_by?.id || shipment.received_by;

    // Build Signed By options (only 4 specific users)
    let signedByOptions = '<option value="">-- Select Staff --</option>';
    signedByUsersData.forEach(staff => {
        const isSelected = signedById && (String(signedById) === String(staff.id));
        signedByOptions += `<option value="${staff.id}" ${isSelected ? 'selected' : ''}>${staff.name}</option>`;
    });

    // Build Received By options (all staff users)
    let receivedByOptions = '<option value="">-- Select Staff --</option>';
    staffUsersData.forEach(staff => {
        const isSelected = receivedById && (String(receivedById) === String(staff.id));
        receivedByOptions += `<option value="${staff.id}" ${isSelected ? 'selected' : ''}>${staff.name}</option>`;
    });

    // Escape values for HTML
    const escapeHtml = (str) => {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    };

    modalContent.innerHTML = `
        <form id="editShipmentForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Title *</label>
                    <input type="text" class="form-control" name="title" value="${escapeHtml(shipment.title)}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Quantity *</label>
                    <input type="number" class="form-control" name="quantity" value="${shipment.quantity || 0}" required min="1">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Destination *</label>
                    <select class="form-select" name="destination" required>
                        <option value="Harare" ${String(shipment.destination) === 'Harare' ? 'selected' : ''}>Harare</option>
                        <option value="Bulawayo" ${String(shipment.destination) === 'Bulawayo' ? 'selected' : ''}>Bulawayo</option>
                        <option value="Mutare" ${String(shipment.destination) === 'Mutare' ? 'selected' : ''}>Mutare</option>
                        <option value="Zambia" ${String(shipment.destination) === 'Zambia' ? 'selected' : ''}>Zambia</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">WH Status *</label>
                    <select class="form-select" name="status" required>
                        <option value="Received" ${String(shipment.status) === 'Received' ? 'selected' : ''}>Received</option>
                        <option value="Not yet" ${String(shipment.status) === 'Not yet' ? 'selected' : ''}>Not yet</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Order Number</label>
                    <input type="number" class="form-control" name="order" value="${shipment.order || 0}" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Transporter</label>
                    <input type="text" class="form-control" name="transporter" value="${escapeHtml(shipment.transporter)}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" value="${shipment.date ? (shipment.date.split('T')[0] || shipment.date.split(' ')[0] || shipment.date) : ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">ETA</label>
                    <input type="date" class="form-control" name="eta" value="${shipment.eta ? (shipment.eta.split('T')[0] || shipment.eta.split(' ')[0] || shipment.eta) : ''}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Destination Status</label>
                    <select class="form-select" name="f_status">
                        <option value="">-- Select Destination Status --</option>
                        <option value="Received" ${String(shipment.f_status) === 'Received' ? 'selected' : ''}>Received</option>
                        <option value="Not yet" ${String(shipment.f_status) === 'Not yet' ? 'selected' : ''}>Not yet</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Signed By</label>
                    <select class="form-select" name="signed_by">
                        ${signedByOptions}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Received By</label>
                    <select class="form-select" name="received_by">
                        ${receivedByOptions}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">SRS</label>
                    <select class="form-select" name="assigned_to">
                        <option value="">-- Select Person --</option>
                        <option value="Mike" ${String(shipment.assigned_to) === 'Mike' ? 'selected' : ''}>Mike</option>
                        <option value="Tinashe" ${String(shipment.assigned_to) === 'Tinashe' ? 'selected' : ''}>Tinashe</option>
                        <option value="Other" ${String(shipment.assigned_to) === 'Other' ? 'selected' : ''}>Other</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="3">${escapeHtml(shipment.notes)}</textarea>
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </form>
    `;

    document.getElementById('editShipmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveShipment(shipment.id);
    });
}

function saveShipment(id) {
    const form = document.getElementById('editShipmentForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

    fetch(`/admin/inventory-shipments/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            editModal.hide();

            // Create and display success alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Success!</strong> ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);

            // Reload page after short delay to show the message
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            showError('Error', data.message || 'Failed to update shipment');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        showError('Error', 'Failed to update shipment');
    });
}

function viewHistory(id) {
    const modalContent = document.getElementById('historyModalContent');
    modalContent.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading history...</p></div>';

    historyModal.show();

    fetch(`/admin/inventory-shipments/${id}/history`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (data.count === 0) {
                    modalContent.innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No history available for this shipment yet.</div>';
                } else {
                    renderHistory(data.history);
                }
            } else {
                modalContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${data.message || 'Failed to load history'}</div>`;
            }
        })
        .catch(error => {
            console.error('History load error:', error);
            modalContent.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load history: ${error.message}</div>`;
        });
}

function renderHistory(history) {
    const modalContent = document.getElementById('historyModalContent');

    if (!history || history.length === 0) {
        modalContent.innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No history available</div>';
        return;
    }

    let html = '<div class="timeline">';

    history.forEach(item => {
        const badgeColor = item.action_badge_color || 'secondary';
        const userName = item.user ? item.user.name : 'System';
        const date = new Date(item.created_at).toLocaleString();

        html += `
            <div class="timeline-item mb-3 border-start border-3 border-${badgeColor} ps-3">
                <div class="d-flex justify-content-between">
                    <span class="badge bg-${badgeColor}">${item.action_text || item.action}</span>
                    <small class="text-muted">${date}</small>
                </div>
                <p class="mb-1"><strong>By:</strong> ${userName}</p>
        `;

        // Defensive check for changes - ensure it's an array
        if (item.changes && Array.isArray(item.changes) && item.changes.length > 0) {
            html += '<small class="text-muted"><strong>Changes:</strong></small><ul class="small">';
            item.changes.forEach(change => {
                const fromValue = change.from !== null && change.from !== undefined ? change.from : 'null';
                const toValue = change.to !== null && change.to !== undefined ? change.to : 'null';
                html += `<li><strong>${change.field}:</strong> ${fromValue} → ${toValue}</li>`;
            });
            html += '</ul>';
        }

        html += '</div>';
    });

    html += '</div>';
    modalContent.innerHTML = html;
}

async function deleteShipment(id) {
    const _r = await Swal.fire({ title: 'Are you sure?', text: 'Are you sure you want to delete this shipment?', icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    fetch(`/admin/inventory-shipments/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            showError('Error', data.message || 'Failed to delete shipment');
        }
    })
    .catch(error => {
        showError('Error', 'Failed to delete shipment');
    });
}

// Bulk Actions Functions
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.shipment-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.shipment-checkbox:checked');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    const selectAll = document.getElementById('selectAll');

    selectedCount.textContent = checkboxes.length;
    bulkActionsBar.style.display = checkboxes.length > 0 ? 'block' : 'none';

    const allCheckboxes = document.querySelectorAll('.shipment-checkbox');
    selectAll.checked = checkboxes.length === allCheckboxes.length && allCheckboxes.length > 0;
    selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
}

function clearSelection() {
    document.querySelectorAll('.shipment-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkActions();
}

async function bulkDelete() {
    const checkboxes = document.querySelectorAll('.shipment-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length === 0) {
        showWarning('No Selection', 'Please select at least one shipment to delete');
        return;
    }

    const _r = await Swal.fire({ title: 'Are you sure?', text: `Are you sure you want to delete ${ids.length} shipment(s)?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;

    fetch('/admin/inventory-shipments/bulk-delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Success!</strong> ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);

            setTimeout(() => {
                alertDiv.remove();
                window.location.reload();
            }, 1500);
        } else {
            showError('Error', data.message || 'Failed to delete shipments');
        }
    })
    .catch(error => {
        showError('Error', 'Failed to delete shipments');
        console.error(error);
    });
}

// Bulk Edit Functions
function openBulkEditModal() {
    const checkboxes = document.querySelectorAll('.shipment-checkbox:checked');

    if (checkboxes.length === 0) {
        showWarning('No Selection', 'Please select at least one shipment to edit');
        return;
    }

    // Reset form
    document.getElementById('bulkEditForm').reset();

    // Show modal
    bulkEditModal.show();
}

function submitBulkEdit() {
    const checkboxes = document.querySelectorAll('.shipment-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length === 0) {
        showWarning('No Selection', 'Please select at least one shipment to edit');
        return;
    }

    const form = document.getElementById('bulkEditForm');
    const formData = new FormData(form);
    const updates = {};

    // Only include non-empty values
    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            updates[key] = value;
        }
    }

    if (Object.keys(updates).length === 0) {
        showWarning('No Changes', 'Please fill in at least one field to update');
        return;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

    fetch('/admin/inventory-shipments/bulk-update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ ids: ids, updates: updates })
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;

        if (data.success) {
            // Hide modal
            bulkEditModal.hide();

            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong>Success!</strong> ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);

            // Clear selection and reload
            setTimeout(() => {
                alertDiv.remove();
                window.location.reload();
            }, 1500);
        } else {
            showError('Error', data.message || 'Failed to update shipments');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        showError('Error', 'Failed to update shipments');
        console.error(error);
    });
}

// Copy QR Data to clipboard
function copyQRData(shipmentId, orderNumber, productName, quantity, destination) {
    // Create QR data object matching the format used in generateWaybill
    const qrData = {
        shipment_id: shipmentId,
        order_number: orderNumber,
        product_name: productName,
        quantity: quantity,
        destination: destination,
        type: 'inventory_shipment',
        timestamp: Math.floor(Date.now() / 1000)
    };

    // Convert to JSON string
    const qrDataString = JSON.stringify(qrData);

    // Copy to clipboard
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(qrDataString)
            .then(() => {
                // Show success message
                showToast('QR Data Copied!', 'The QR code data has been copied to your clipboard.', 'success');
            })
            .catch(err => {
                console.error('Failed to copy:', err);
                // Fallback method
                fallbackCopyToClipboard(qrDataString);
            });
    } else {
        // Fallback for older browsers
        fallbackCopyToClipboard(qrDataString);
    }
}

// Fallback copy method for older browsers
function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();

    try {
        document.execCommand('copy');
        showToast('QR Data Copied!', 'The QR code data has been copied to your clipboard.', 'success');
    } catch (err) {
        console.error('Failed to copy:', err);
        showToast('Copy Failed', 'Could not copy to clipboard. Data: ' + text, 'danger');
    }

    document.body.removeChild(textArea);
}

// Show toast notification
function showToast(title, message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 350px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';

    let icon = 'info-circle-fill';
    if (type === 'success') icon = 'check-circle-fill';
    else if (type === 'danger') icon = 'exclamation-triangle-fill';
    else if (type === 'warning') icon = 'exclamation-circle-fill';

    alertDiv.innerHTML = `
        <i class="bi bi-${icon} me-2"></i>
        <strong>${title}</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto-dismiss after 4 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 4000);
}
// Bulk generate QR stickers — submits hidden form (POST → opens PDF in new tab)
function bulkGenerateStickers() {
    const checkboxes = document.querySelectorAll('.shipment-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    if (ids.length === 0) {
        showWarning('No Selection', 'Please select at least one shipment.');
        return;
    }
    const container = document.getElementById('bulkStickerIds');
    container.innerHTML = '';
    ids.forEach(id => {
        const inp = document.createElement('input');
        inp.type  = 'hidden';
        inp.name  = 'ids[]';
        inp.value = id;
        container.appendChild(inp);
    });
    document.getElementById('bulkStickerForm').submit();
}

// ── Mobile bulk select ──
function toggleAllMobile(master) {
    document.querySelectorAll('.d-lg-none .shipment-checkbox').forEach(cb => cb.checked = master.checked);
    updateMobileBulk();
}

function updateMobileBulk() {
    const checked = document.querySelectorAll('.d-lg-none .shipment-checkbox:checked');
    const bar = document.getElementById('mobileBulkBar');
    const count = document.getElementById('mobileSelectedCount');

    if (checked.length > 0) {
        bar.style.display = 'flex';
        count.textContent = checked.length;
    } else {
        bar.style.display = 'none';
    }

    // Also sync with the desktop bulk system so bulk actions work
    updateBulkActions();
}
</script>
@endpush

