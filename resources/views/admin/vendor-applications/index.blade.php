@extends('admin.layout')

@section('title', 'Vendor Applications')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Vendor Applications</h4>
                    <div>
                        <a href="{{ route('admin.vendor-applications.export') }}" class="btn btn-success btn-sm" onclick="event.stopPropagation();" download>
                            <i class="fas fa-file-excel"></i> Export CSV
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="is_approved" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Applications</option>
                                    <option value="0" {{ request('is_approved') === '0' ? 'selected' : '' }}>Pending</option>
                                    <option value="1" {{ request('is_approved') === '1' ? 'selected' : '' }}>Approved</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="is_banned" class="form-control" onchange="this.form.submit()">
                                    <option value="">All Vendors</option>
                                    <option value="0" {{ request('is_banned') === '0' ? 'selected' : '' }}>Not Banned</option>
                                    <option value="1" {{ request('is_banned') === '1' ? 'selected' : '' }}>Banned</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Search by store name, vendor name, email..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Applications Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60">ID</th>
                                    <th>Store / Vendor Details</th>
                                    <th width="150">Products</th>
                                    <th width="120">Status</th>
                                    <th width="120">Approval</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($applications as $app)
                                <tr>
                                    <td><strong>{{ $app->id }}</strong></td>
                                    <td>
                                        <div class="mb-1">
                                            <strong class="d-block" style="font-size: 1.05rem;">{{ $app->store_name }}</strong>
                                            @if($app->legal_name && $app->legal_name !== $app->store_name)
                                            <small class="text-muted d-block">Legal: {{ $app->legal_name }}</small>
                                            @endif
                                        </div>
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-user"></i> {{ $app->vendor->name ?? 'N/A' }}
                                            </span>
                                            @if($app->city)
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-map-marker-alt"></i> {{ $app->city }}{{ $app->state ? ', ' . $app->state->name : '' }}
                                            </span>
                                            @endif
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-calendar"></i> {{ $app->created_at->format('M d, Y') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($app->is_approved)
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge bg-primary" style="font-size: 0.9rem;" title="Total Products">
                                                    <i class="fas fa-boxes"></i> {{ $app->total_products_count ?? 0 }} Total
                                                </span>
                                                @if($app->active_products_count > 0)
                                                <span class="badge bg-success" style="font-size: 0.85rem;" title="Active/Approved">
                                                    <i class="fas fa-check-circle"></i> {{ $app->active_products_count }} Active
                                                </span>
                                                @endif
                                                @if($app->pending_products_count > 0)
                                                <span class="badge bg-warning" style="font-size: 0.85rem;" title="Pending Approval">
                                                    <i class="fas fa-clock"></i> {{ $app->pending_products_count }} Pending
                                                </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($app->is_banned)
                                        <span class="badge bg-danger w-100">
                                            <i class="fas fa-ban"></i> Banned
                                        </span>
                                        @elseif($app->status)
                                        <span class="badge bg-success w-100">
                                            <i class="fas fa-check"></i> Active
                                        </span>
                                        @else
                                        <span class="badge bg-secondary w-100">
                                            <i class="fas fa-times"></i> Inactive
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($app->is_approved)
                                        <span class="badge bg-success w-100">
                                            <i class="fas fa-check-circle"></i> Approved
                                        </span>
                                        @else
                                        <span class="badge bg-warning w-100">
                                            <i class="fas fa-hourglass-half"></i> Pending
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.vendor-applications.show', $app->id) }}" class="btn btn-sm btn-info w-100">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No applications found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    {{ $applications->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

