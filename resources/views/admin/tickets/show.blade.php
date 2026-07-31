@extends('admin.layout')

@section('title', 'Ticket #' . $ticket->ticket_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-ticket-perforated"></i> Ticket Details</h2>
    <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Tickets
    </a>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Ticket Header -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">{{ $ticket->subject }}</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Ticket #:</strong> {{ $ticket->ticket_number }}<br>
                            <strong>User:</strong> {{ $ticket->user->name }} ({{ $ticket->user->email }})<br>
                            <strong>Category:</strong> <span class="text-capitalize">{{ $ticket->category }}</span><br>
                            <strong>Created:</strong> {{ $ticket->created_at->format('M d, Y H:i') }}
                        </div>
                        <div class="col-md-6">
                            <strong>Priority:</strong>
                            <span class="badge bg-{{ $ticket->priority == 'urgent' ? 'danger' : ($ticket->priority == 'high' ? 'warning' : ($ticket->priority == 'medium' ? 'info' : 'success')) }}">
                                {{ ucfirst($ticket->priority) }}
                            </span><br>
                            <strong>Status:</strong>
                            <span class="badge bg-{{ in_array($ticket->status, ['open', 'in_progress']) ? 'primary' : (in_array($ticket->status, ['waiting_customer', 'waiting_admin']) ? 'warning' : ($ticket->status == 'resolved' ? 'success' : 'secondary')) }}">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </span><br>
                            <strong>Assigned To:</strong>
                            @if($ticket->assignedTo)
                                {{ $ticket->assignedTo->name }}
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </div>
                    </div>

                    <div class="alert alert-light">
                        <strong>Description:</strong>
                        <div class="ql-content mt-1">{!! $ticket->description !!}</div>
                    </div>
                </div>
            </div>

            <!-- Messages/Conversation -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-comments"></i> Conversation</h5>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    @foreach($ticket->messages as $message)
                    <div class="message-item mb-3 p-3 rounded {{ $message->user_id == $ticket->user_id ? 'bg-light' : 'bg-primary bg-opacity-10' }}">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>
                                {{ $message->user->name }}
                                @if($message->user_id != $ticket->user_id)
                                    <span class="badge bg-primary">Admin</span>
                                @endif
                                @if($message->is_internal)
                                    <span class="badge bg-danger">Internal Note</span>
                                @endif
                            </strong>
                            <small class="text-muted">{{ $message->created_at->format('M d, Y H:i') }}</small>
                        </div>
                        <div class="ql-content mb-0">{!! $message->message !!}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Reply Form -->
            @can('ticket.reply')
            @if(!in_array($ticket->status, ['closed']))
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-reply"></i> Add Reply</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.tickets.reply', $ticket) }}" method="POST" id="reply-form">
                        @csrf
                        {{-- Hidden input that receives the Quill HTML before submit --}}
                        <input type="hidden" name="message" id="message-input">
                        <div class="mb-3">
                            <div id="reply-editor" style="min-height: 160px;"></div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal" value="1">
                            <label class="form-check-label" for="is_internal">
                                Internal Note (Not visible to customer)
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Reply
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> This ticket is closed. Reopen it to add more messages.
            </div>
            @endif
            @else
            <div class="alert alert-warning">
                <i class="fas fa-lock"></i> You don't have permission to reply to tickets.
            </div>
            @endcan
        </div>

        <!-- Sidebar Actions -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Ticket Actions</h5>
                </div>
                <div class="card-body">
                    <!-- Update Status -->
                    @can('ticket.update-status')
                    <form action="{{ route('admin.tickets.status', $ticket) }}" method="POST" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <label class="form-label"><strong>Update Status:</strong></label>
                        <select name="status" class="form-select mb-2" onchange="this.form.submit()">
                            <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="waiting_customer" {{ $ticket->status == 'waiting_customer' ? 'selected' : '' }}>Waiting Customer</option>
                            <option value="waiting_admin" {{ $ticket->status == 'waiting_admin' ? 'selected' : '' }}>Waiting Admin</option>
                            <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </form>
                    @endcan

                    <!-- Assign To -->
                    @can('ticket.assign')
                    <form action="{{ route('admin.tickets.assign', $ticket) }}" method="POST" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <label class="form-label"><strong>Assign To:</strong></label>
                        <select name="assigned_to" class="form-select mb-2">
                            <option value="">Unassigned</option>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" {{ $ticket->assigned_to == $admin->id ? 'selected' : '' }}>
                                    {{ $admin->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-user-check"></i> Assign
                        </button>
                    </form>
                    @endcan

                    <hr>

                    <!-- Close/Reopen -->
                    @if($ticket->status != 'closed')
                        @can('ticket.close')
                        <form action="{{ route('admin.tickets.close', $ticket) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100 mb-2">
                                <i class="fas fa-times-circle"></i> Close Ticket
                            </button>
                        </form>
                        @endcan
                    @else
                        @can('ticket.reopen')
                        <form action="{{ route('admin.tickets.reopen', $ticket) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-redo"></i> Reopen Ticket
                            </button>
                        </form>
                        @endcan
                    @endif

                    <!-- Back to List -->
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary w-100">
                        <i class="fas fa-arrow-left"></i> Back to Tickets
                    </a>
                </div>
            </div>

            <!-- Ticket Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Ticket Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Messages:</th>
                            <td>{{ $ticket->messages->count() }}</td>
                        </tr>
                        <tr>
                            <th>Last Update:</th>
                            <td>{{ $ticket->updated_at->diffForHumans() }}</td>
                        </tr>
                        @if($ticket->resolved_at)
                        <tr>
                            <th>Resolved:</th>
                            <td>{{ $ticket->resolved_at->format('M d, Y H:i') }}</td>
                        </tr>
                        @endif
                        @if($ticket->closed_at)
                        <tr>
                            <th>Closed:</th>
                            <td>{{ $ticket->closed_at->format('M d, Y H:i') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<style>
    .card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .message-item {
        border-left: 3px solid #0d6efd;
    }
    .bg-primary.bg-opacity-10 {
        background-color: rgba(13, 110, 253, 0.1) !important;
        border-left-color: #0d6efd;
    }
    .bg-light {
        border-left-color: #6c757d;
    }
    .badge {
        padding: 0.4em 0.6em;
        font-size: 0.875rem;
    }
    /* Quill editor border to match Bootstrap */
    #reply-editor {
        border: 1px solid #dee2e6;
        border-top: none;
        border-radius: 0 0 4px 4px;
        background: #fff;
    }
    .ql-toolbar {
        border: 1px solid #dee2e6;
        border-radius: 4px 4px 0 0;
    }
    /* Rendered rich content styles */
    .ql-content ul, .ql-content ol {
        padding-left: 1.5rem;
    }
    .ql-content h1, .ql-content h2, .ql-content h3 {
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .ql-content blockquote {
        border-left: 4px solid #ccc;
        padding-left: 1rem;
        color: #6c757d;
    }
    .ql-content pre {
        background: #f4f4f4;
        padding: 0.75rem;
        border-radius: 4px;
    }
    .ql-content a {
        color: #0d6efd;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var quill = new Quill('#reply-editor', {
            theme: 'snow',
            placeholder: 'Type your reply...',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'code-block'],
                    ['link'],
                    ['clean']
                ]
            }
        });

        // Before form submits, copy Quill HTML into the hidden input
        document.getElementById('reply-form').addEventListener('submit', function (e) {
            var html = quill.getSemanticHTML();
            var text = quill.getText().trim();
            if (!text) {
                e.preventDefault();
                showWarning('Warning', 'Please enter a reply message.');
                return;
            }
            document.getElementById('message-input').value = html;
        });
    });
</script>
@endpush
@endsection

