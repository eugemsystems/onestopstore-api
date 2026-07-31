@extends('admin.layout')

@section('title', 'Support Tickets')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-ticket-perforated"></i> Support Tickets</h2>
</div>

<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['total'] }}</h5>
                    <p class="card-text">Total</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['open'] }}</h5>
                    <p class="card-text">Open</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['in_progress'] }}</h5>
                    <p class="card-text">In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center {{ $stats['waiting_admin'] > 0 ? 'bg-danger' : 'bg-warning' }} text-white">
                <div class="card-body">
                    <h5 class="card-title">
                        {{ $stats['waiting_admin'] }}
                        @if($stats['waiting_admin'] > 0)
                            <i class="bi bi-exclamation-circle-fill ms-1" style="font-size:1rem;"></i>
                        @endif
                    </h5>
                    <p class="card-text">Needs Reply</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['resolved'] }}</h5>
                    <p class="card-text">Resolved</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center bg-secondary text-white">
                <div class="card-body">
                    <h5 class="card-title">{{ $stats['unassigned'] }}</h5>
                    <p class="card-text">Unassigned</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.tickets.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all">All</option>
                        <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="waiting_customer" {{ request('status') == 'waiting_customer' ? 'selected' : '' }}>Waiting Customer</option>
                        <option value="waiting_admin" {{ request('status') == 'waiting_admin' ? 'selected' : '' }}>Waiting Admin</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" onchange="this.form.submit()">
                        <option value="all">All</option>
                        <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="all">All</option>
                        <option value="general" {{ request('category') == 'general' ? 'selected' : '' }}>General</option>
                        <option value="technical" {{ request('category') == 'technical' ? 'selected' : '' }}>Technical</option>
                        <option value="billing" {{ request('category') == 'billing' ? 'selected' : '' }}>Billing</option>
                        <option value="account" {{ request('category') == 'account' ? 'selected' : '' }}>Account</option>
                        <option value="order" {{ request('category') == 'order' ? 'selected' : '' }}>Order</option>
                        <option value="other" {{ request('category') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Ticket #, Subject..." value="{{ request('search') }}">
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Subject</th>
                            <th>User</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Reply</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tickets as $ticket)
                        @php
                            $isClosed       = in_array($ticket->status, ['resolved', 'closed']);
                            $customerReplied = $ticket->latestMessage
                                              && $ticket->user
                                              && $ticket->latestMessage->user_id === $ticket->user_id
                                              && !$isClosed;
                        @endphp
                        <tr class="{{ $customerReplied ? 'needs-attention' : '' }}">
                            <td>
                                <strong>{{ $ticket->ticket_number }}</strong>
                                @if($ticket->messages_count > 0)
                                    <div class="msg-count"><i class="bi bi-chat-left-text"></i> {{ $ticket->messages_count }}</div>
                                @endif
                            </td>
                            <td>
                                {{ Str::limit($ticket->subject, 50) }}
                                @if($ticket->latestMessage)
                                    <div class="last-activity">
                                        <i class="bi bi-clock"></i> {{ $ticket->latestMessage->created_at->diffForHumans() }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($ticket->user)
                                    {{ $ticket->user->name }}
                                @else
                                    <span class="text-muted">Deleted</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $ticket->priority == 'urgent' ? 'danger' : ($ticket->priority == 'high' ? 'warning' : ($ticket->priority == 'medium' ? 'info' : 'success')) }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ in_array($ticket->status, ['open', 'in_progress']) ? 'primary' : (in_array($ticket->status, ['waiting_customer', 'waiting_admin']) ? 'warning' : ($ticket->status == 'resolved' ? 'success' : 'secondary')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>
                            <td>
                                @if($ticket->assignedTo)
                                    {{ $ticket->assignedTo->name }}
                                @else
                                    <span class="text-muted">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($customerReplied)
                                    <span class="badge-reply">
                                        <i class="bi bi-chat-dots-fill"></i> NEW REPLY
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.tickets.show', $ticket) }}"
                                   class="btn btn-sm {{ $customerReplied ? 'btn-danger' : 'btn-primary' }}">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No tickets found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $tickets->links() }}
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }
    .badge {
        padding: 0.5em 0.75em;
        font-size: 0.875rem;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    @@keyframes reply-glow {
        0%, 100% { box-shadow: 0 0 5px 1px rgba(220,53,69,0.55); transform: scale(1); }
        50%       { box-shadow: 0 0 14px 5px rgba(220,53,69,0.9); transform: scale(1.07); }
    }
    .badge-reply {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #dc3545;
        color: #fff;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        padding: 0.45em 0.85em;
        border-radius: 6px;
        animation: reply-glow 1.3s ease-in-out infinite;
        white-space: nowrap;
        cursor: default;
    }
    tr.needs-attention {
        border-left: 4px solid #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.05) !important;
    }
    tr.needs-attention td:first-child { padding-left: 10px; }
    .msg-count {
        font-size: 0.7rem;
        color: #6c757d;
    }
    .last-activity {
        font-size: 0.7rem;
        color: #6c757d;
    }
</style>
@endpush
@endsection

