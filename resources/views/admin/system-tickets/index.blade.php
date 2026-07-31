@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">System Tickets</h1>
                @can('system-ticket.create')
                <a href="{{ route('admin.system-tickets.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Create Ticket
                </a>
                @endcan
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(!Auth::user()->hasRole(\App\Enums\RoleEnum::ADMIN))
    <div class="alert alert-info" role="alert">
        <i class="bi bi-info-circle"></i> You are viewing tickets assigned to you. Only administrators can see all tickets.
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search tickets..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="testing" {{ request('status') == 'testing' ? 'selected' : '' }}>Testing</option>
                            <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                            <option value="reopened" {{ request('status') == 'reopened' ? 'selected' : '' }}>Reopened</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="priority" class="form-select">
                            <option value="">All Priority</option>
                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ request('priority') == 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                    @if(Auth::user()->hasRole(\App\Enums\RoleEnum::ADMIN) && $users->isNotEmpty())
                    <div class="col-md-2">
                        <select name="assigned_to" class="form-select">
                            <option value="">All Assigned Users</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-{{ Auth::user()->hasRole(\App\Enums\RoleEnum::ADMIN) && $users->isNotEmpty() ? '1' : '3' }}">
                        <a href="{{ route('admin.system-tickets.index') }}" class="btn btn-secondary w-100">
                            <i class="bi bi-x-lg"></i> Clear
                        </a>
                    </div>
                </div>
            </form>

            <!-- Tickets Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Title</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Assigned To</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        <tr>
                            <td>
                                <a href="{{ route('admin.system-tickets.show', $ticket->id) }}" class="text-decoration-none">
                                    <strong>{{ $ticket->ticket_number }}</strong>
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('admin.system-tickets.show', $ticket->id) }}" class="text-decoration-none">
                                    {{ Str::limit($ticket->title, 50) }}
                                </a>
                                @if($ticket->category)
                                <br><small class="text-muted"><i class="bi bi-tag"></i> {{ $ticket->category }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $ticket->priority_color }}">
                                    {{ $ticket->priority_label }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $ticket->status_color }}">
                                    {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <div class="avatar-title rounded-circle bg-primary text-white">
                                            {{ substr($ticket->createdBy->name ?? 'U', 0, 1) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small">{{ $ticket->createdBy->name ?? 'Unknown' }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $ticket->createdBy->email ?? '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($ticket->assignedTo)
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        <div class="avatar-title rounded-circle bg-info text-white">
                                            {{ substr($ticket->assignedTo->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small">{{ $ticket->assignedTo->name }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $ticket->assignedTo->email }}</div>
                                    </div>
                                </div>
                                @else
                                <span class="text-muted">Unassigned</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $ticket->created_at->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $ticket->created_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.system-tickets.show', $ticket->id) }}" class="btn btn-sm btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('system-ticket.delete')
                                <form action="{{ route('admin.system-tickets.destroy', $ticket->id) }}" method="POST" class="d-inline" data-swal-confirm="Are you sure you want to delete this ticket?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="text-muted mt-3">No tickets found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($tickets->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $tickets->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 32px;
    height: 32px;
}
.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}
</style>
@endsection
