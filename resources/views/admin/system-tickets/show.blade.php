@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">{{ $ticket->ticket_number }}</h1>
                <div>
                    @if($ticket->status === 'closed')
                    @can('system-ticket.edit')
                    <form action="{{ route('admin.system-tickets.reopen', $ticket->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning" data-swal-confirm="Are you sure you want to reopen this ticket?">
                            <i class="bi bi-arrow-clockwise"></i> Reopen Ticket
                        </button>
                    </form>
                    @endcan
                    @endif
                    <a href="{{ route('admin.system-tickets.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Tickets
                    </a>
                </div>
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

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <!-- Ticket Details -->
            <div class="card mb-4 bg-dark text-white">
                <div class="card-header bg-dark border-secondary">
                    <h5 class="mb-0 text-white">{{ $ticket->title }}</h5>
                    @if($ticket->category)
                    <small class="text-light"><i class="bi bi-tag"></i> {{ $ticket->category }}</small>
                    @endif
                </div>
                <div class="card-body bg-dark">
                    <div class="mb-3">
                        <strong class="text-white">Description:</strong>
                        <div class="ql-content mt-2 text-light">{!! $ticket->description !!}</div>
                    </div>

                    @if(!empty($ticket->attachments) && count($ticket->attachments) > 0)
                    <div class="mb-3">
                        <strong class="text-white">Attachments:</strong>
                        <div class="row g-2 mt-2">
                            @foreach($ticket->attachments as $attachment)
                            <div class="col-md-3">
                                <a href="{{ $attachment->image_url }}" target="_blank" class="text-decoration-none">
                                    @if(str_starts_with($attachment->mime_type ?? '', 'image/'))
                                        <img src="{{ $attachment->image_url }}" class="img-thumbnail" style="height: 100px; width: 100%; object-fit: cover;" alt="{{ $attachment->name }}">
                                    @else
                                        <div class="card text-center p-3 bg-secondary">
                                            <i class="bi bi-file-earmark fs-1 text-white"></i>
                                            <small class="text-white mt-2">{{ $attachment->name }}</small>
                                        </div>
                                    @endif
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-md-4">
                            <small class="text-light">Created By:</small>
                            <div><strong class="text-white">{{ $ticket->createdBy->name ?? 'Unknown' }}</strong></div>
                            <small class="text-light">{{ $ticket->created_at->format('M d, Y h:i A') }}</small>
                        </div>
                        @if($ticket->closed_at)
                        <div class="col-md-4">
                            <small class="text-light">Closed By:</small>
                            <div><strong class="text-white">{{ $ticket->closedBy->name ?? 'Unknown' }}</strong></div>
                            <small class="text-light">{{ $ticket->closed_at->format('M d, Y h:i A') }}</small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Activity Log</h5>
                </div>
                <div class="card-body">
                    @forelse($ticket->activities as $activity)
                    <div class="d-flex mb-4 {{ !$loop->last ? 'border-bottom pb-4' : '' }}">
                        <div class="me-3">
                            <div class="avatar-md">
                                <div class="avatar-title rounded-circle bg-primary text-white">
                                    {{ substr($activity->user->name ?? 'U', 0, 1) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $activity->user->name ?? 'Unknown' }}</strong>
                                    @php
                                        $badgeColor = match($activity->action) {
                                            'created' => 'success',
                                            'commented' => 'info',
                                            'status_changed' => 'warning',
                                            'assigned' => 'primary',
                                            'closed' => 'danger',
                                            'reopened' => 'warning',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeColor }} ms-2">{{ $activity->action_label }}</span>
                                </div>
                                <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                            </div>

                            @if($activity->action === 'status_changed' && $activity->old_value && $activity->new_value)
                            <div class="mt-2">
                                Changed status from
                                <span class="badge bg-danger">{{ ucwords(str_replace('_', ' ', $activity->old_value)) }}</span>
                                to
                                <span class="badge bg-success">{{ ucwords(str_replace('_', ' ', $activity->new_value)) }}</span>
                            </div>
                            @endif

                            @if($activity->comment)
                            <div class="mt-2 p-3 bg-light rounded ql-content">{!! $activity->comment !!}</div>
                            @endif

                            @if(!empty($activity->attachments) && count($activity->attachments) > 0)
                            <div class="mt-2">
                                <div class="row g-2">
                                    @foreach($activity->attachments as $attachment)
                                    <div class="col-md-3">
                                        <a href="{{ $attachment->image_url }}" target="_blank" class="text-decoration-none">
                                            @if(str_starts_with($attachment->mime_type ?? '', 'image/'))
                                                <img src="{{ $attachment->image_url }}" class="img-thumbnail" style="height: 80px; width: 100%; object-fit: cover;" alt="{{ $attachment->name }}">
                                            @else
                                                <div class="card text-center p-2">
                                                    <i class="bi bi-file-earmark fs-3"></i>
                                                    <small class="text-muted" style="font-size: 0.7rem;">{{ Str::limit($attachment->name, 15) }}</small>
                                                </div>
                                            @endif
                                        </a>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-3">No activity yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Add Comment Section -->
            @can('system-ticket.edit')
            @if($ticket->status !== 'closed')
            <div class="card mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Add Comment</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.system-tickets.update', $ticket->id) }}" method="POST" enctype="multipart/form-data" id="comment-form">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Comment</label>
                            <input type="hidden" name="comment" id="comment-input">
                            <div id="comment-editor" style="min-height: 140px;"></div>
                        </div>

                        <div class="mb-3">
                            <label for="comment_attachments" class="form-label">Attachments (Images Only)</label>
                            <input type="file" class="form-control" id="comment_attachments" name="attachments[]" multiple accept="image/*">
                            <small class="text-muted">Max size: 5MB per image.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Add Comment
                        </button>
                    </form>
                </div>
            </div>
            @endif
            @endcan
        </div>

        <div class="col-md-4">
            <!-- Ticket Status -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">Ticket Status</h6>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <div>
                            <span class="badge bg-{{ $ticket->status_color }} fs-6">{{ $ticket->status_label }}</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <div>
                            <span class="badge bg-{{ $ticket->priority_color }} fs-6">{{ $ticket->priority_label }}</span>
                        </div>
                    </div>

                    @can('system-ticket.edit')
                    @if($ticket->status !== 'closed')
                    <form action="{{ route('admin.system-tickets.update', $ticket->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="status" class="form-label">Update Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">-- Select Status --</option>
                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="testing" {{ $ticket->status === 'testing' ? 'selected' : '' }}>Testing</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="priority_update" class="form-label">Update Priority</label>
                            <select class="form-select" id="priority_update" name="priority">
                                <option value="">-- Select Priority --</option>
                                <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ $ticket->priority === 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-check-lg"></i> Update
                        </button>
                    </form>
                    @endif
                    @endcan
                </div>
            </div>

            <!-- Assignment -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">Assignment</h6>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Assigned To</label>
                        <div>
                            @if($ticket->assignedTo)
                            <div class="d-flex align-items-center">
                                <div class="avatar-md me-2">
                                    <div class="avatar-title rounded-circle bg-info text-white">
                                        {{ substr($ticket->assignedTo->name, 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <div><strong>{{ $ticket->assignedTo->name }}</strong></div>
                                    <small class="text-muted">{{ $ticket->assignedTo->email }}</small>
                                </div>
                            </div>
                            @else
                            <span class="text-muted">Not assigned</span>
                            @endif
                        </div>
                    </div>

                    @can('system-ticket.edit')
                    @if($ticket->status !== 'closed')
                    <form action="{{ route('admin.system-tickets.update', $ticket->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">-- Select User --</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $ticket->assigned_to == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-person-check"></i> Assign
                        </button>
                    </form>
                    @endif
                    @endcan
                </div>
            </div>

            <!-- Metadata -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Metadata</h6>
                    <hr>
                    <div class="mb-2">
                        <small class="text-muted">Created:</small>
                        <div>{{ $ticket->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Last Updated:</small>
                        <div>{{ $ticket->updated_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @if($ticket->closed_at)
                    <div class="mb-2">
                        <small class="text-muted">Closed:</small>
                        <div>{{ $ticket->closed_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.snow.css" rel="stylesheet">
<style>
.avatar-md {
    width: 40px;
    height: 40px;
}
.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    font-weight: 600;
}
/* Quill comment editor */
#comment-editor {
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 4px 4px;
    background: #fff;
}
.ql-toolbar.ql-snow {
    border: 1px solid #dee2e6;
    border-radius: 4px 4px 0 0;
}
/* Rich content rendering */
.ql-content ul, .ql-content ol { padding-left: 1.5rem; }
.ql-content h1, .ql-content h2, .ql-content h3 { margin-top: 0.5rem; margin-bottom: 0.5rem; }
.ql-content blockquote { border-left: 4px solid #ccc; padding-left: 1rem; color: #6c757d; }
.ql-content pre { background: #f4f4f4; padding: 0.75rem; border-radius: 4px; }
.ql-content a { color: #0d6efd; }
/* Dark card description overrides */
.bg-dark .ql-content a { color: #7eb8ff; }
</style>

<script src="https://cdn.jsdelivr.net/npm/quill@2/dist/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    @can('system-ticket.edit')
    @if($ticket->status !== 'closed')
    var commentQuill = new Quill('#comment-editor', {
        theme: 'snow',
        placeholder: 'Add a comment...',
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

    document.getElementById('comment-form').addEventListener('submit', function (e) {
        var text = commentQuill.getText().trim();
        // Comment is optional — only populate if something was written
        document.getElementById('comment-input').value = text ? commentQuill.getSemanticHTML() : '';
    });
    @endif
    @endcan
});
</script>
@endsection
