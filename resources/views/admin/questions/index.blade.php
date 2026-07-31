@extends('admin.layout')

@section('title', 'Q&A Management - Admin Panel')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-chat-square-quote-fill" style="color:#667eea"></i> Questions &amp; Answers</h2>
        <p class="text-muted mb-0">Manage customer questions and provide answers that appear on product pages</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if($unansweredCount > 0)
            <span class="badge fs-6" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <i class="bi bi-clock-fill"></i> {{ $unansweredCount }} Unanswered
            </span>
        @else
            <span class="badge bg-success fs-6"><i class="bi bi-check-circle-fill"></i> All Answered</span>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" style="border-bottom:2px solid #e5e7eb">
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'unanswered' ? 'active fw-bold' : '' }}" href="{{ route('admin.questions.index', array_merge(request()->query(), ['tab'=>'unanswered'])) }}" style="{{ $tab === 'unanswered' ? 'border-bottom:3px solid #667eea;color:#667eea' : '' }}">
            <i class="bi bi-clock"></i> Unanswered
            @if($unansweredCount > 0)
                <span class="badge ms-1" style="background:#ef4444">{{ $unansweredCount }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $tab === 'all' ? 'active fw-bold' : '' }}" href="{{ route('admin.questions.index', array_merge(request()->query(), ['tab'=>'all'])) }}" style="{{ $tab === 'all' ? 'border-bottom:3px solid #667eea;color:#667eea' : '' }}">
            <i class="bi bi-list-ul"></i> All Questions
        </a>
    </li>
</ul>

<!-- Search -->
<div class="card mb-3" style="border:1px solid #e5e7eb">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.questions.index') }}" class="row g-2 align-items-center">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="col-md-9">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search questions, answers, customer name or product..." value="{{ $search }}">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn w-100" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff">Search</button>
            </div>
            @if($search)
            <div class="col-md-1">
                <a href="{{ route('admin.questions.index', ['tab'=>$tab]) }}" class="btn btn-outline-secondary w-100"><i class="bi bi-x"></i></a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Questions List -->
@forelse($questions as $qna)
<div class="card mb-3" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.05)">
    <!-- Product Info Bar -->
    <div style="background:#f8fafc;border-bottom:1px solid #e5e7eb;padding:8px 16px;display:flex;align-items:center;gap:10px">
        @if($qna->product?->product_thumbnail?->image_url)
            <img src="{{ $qna->product->product_thumbnail->image_url }}" width="32" height="32" style="border-radius:6px;object-fit:cover" alt="">
        @endif
        <div>
            <span style="font-size:13px;font-weight:600;color:#374151">
                {{ $qna->product?->name ?? 'Unknown Product' }}
            </span>
            @if($qna->product?->slug)
                <a href="https://raines.africa/{{ app()->getLocale() }}/product/{{ $qna->product->slug }}" target="_blank" class="ms-2" style="font-size:11px;color:#667eea">
                    <i class="bi bi-box-arrow-up-right"></i> View
                </a>
            @endif
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <small class="text-muted">{{ $qna->created_at->diffForHumans() }}</small>
            <form method="POST" action="{{ route('admin.questions.destroy', $qna->id) }}" onsubmit="return confirm('Delete this question?')" class="d-inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" style="padding:2px 8px;font-size:12px">
                    <i class="bi bi-trash3"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Question -->
    <div style="padding:14px 16px;background:linear-gradient(135deg,#667eea08,#764ba208);border-bottom:1px solid #ede9fe">
        <div style="display:flex;gap:10px;align-items:flex-start">
            <div style="min-width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0">Q</div>
            <div>
                <div style="font-size:12px;color:#6b7280;margin-bottom:4px">
                    <i class="bi bi-person-fill"></i> {{ $qna->consumer?->name ?? 'Anonymous' }}
                    &nbsp;·&nbsp;
                    <i class="bi bi-envelope"></i> {{ $qna->consumer?->email ?? '' }}
                </div>
                <p style="margin:0;font-size:15px;font-weight:500;color:#1f2937;line-height:1.5">{{ $qna->question }}</p>
            </div>
        </div>
    </div>

    <!-- Answer / Answer Form -->
    @if($qna->answer)
        <div style="background:#f0fdf4;border-bottom:1px solid #bbf7d0">
            <div style="background:linear-gradient(135deg,#22c55e,#16a34a);padding:6px 16px;display:flex;align-items:center;gap:8px">
                <div style="min-width:24px;height:24px;border-radius:50%;background:rgba(255,255,255,0.25);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:12px">A</div>
                <span style="color:rgba(255,255,255,0.9);font-size:12px"><i class="bi bi-shop-window"></i> Raines Africa (Admin)</span>
                <button class="btn btn-sm ms-auto" style="background:rgba(255,255,255,0.2);color:#fff;font-size:11px;padding:2px 10px;border:none" onclick="toggleEditAnswer({{ $qna->id }})">
                    <i class="bi bi-pencil-fill"></i> Edit
                </button>
            </div>
            <div id="answer-display-{{ $qna->id }}" style="padding:12px 16px">
                <p style="margin:0;color:#166534;font-size:14px;line-height:1.6">{{ $qna->answer }}</p>
            </div>
            <!-- Inline edit form (hidden by default) -->
            <div id="answer-edit-{{ $qna->id }}" style="display:none;padding:12px 16px">
                <form method="POST" action="{{ route('admin.questions.answer', $qna->id) }}">
                    @csrf
                    <textarea name="answer" rows="4" class="form-control mb-2" style="border-color:#bbf7d0;font-size:14px">{{ $qna->answer }}</textarea>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Save</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleEditAnswer({{ $qna->id }})">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Stats -->
        <div style="padding:8px 16px;background:#f9fafb;display:flex;gap:16px">
            <small class="text-muted"><i class="bi bi-hand-thumbs-up-fill text-success"></i> {{ $qna->total_likes }} helpful</small>
            <small class="text-muted"><i class="bi bi-hand-thumbs-down-fill text-danger"></i> {{ $qna->total_dislikes }} not helpful</small>
        </div>
    @else
        <!-- Unanswered — show answer form -->
        <div style="background:#fffbeb;padding:0">
            <div style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:6px 16px;display:flex;align-items:center;gap:8px">
                <i class="bi bi-clock-fill" style="color:#fff;font-size:12px"></i>
                <span style="color:#fff;font-size:12px;font-weight:600">Awaiting your answer</span>
            </div>
            <div style="padding:14px 16px" id="answer-form-container-{{ $qna->id }}">
                <button class="btn btn-sm" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;font-size:13px" onclick="toggleAnswerForm({{ $qna->id }})">
                    <i class="bi bi-reply-fill"></i> Write Answer
                </button>
                <div id="answer-form-{{ $qna->id }}" style="display:none;margin-top:12px">
                    <form method="POST" action="{{ route('admin.questions.answer', $qna->id) }}">
                        @csrf
                        <textarea name="answer" rows="4" class="form-control mb-2" placeholder="Type your answer here... It will be visible to all customers on the product page." style="font-size:14px;border-color:#d1d5db" required></textarea>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm" style="background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff">
                                <i class="bi bi-check-lg"></i> Publish Answer
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAnswerForm({{ $qna->id }})">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@empty
<div class="text-center py-5">
    <i class="bi bi-chat-square-quote" style="font-size:48px;color:#d1d5db"></i>
    <p class="text-muted mt-3">
        @if($tab === 'unanswered')
            No unanswered questions — great work!
        @else
            No questions found.
        @endif
    </p>
</div>
@endforelse

<!-- Pagination -->
@if($questions->hasPages())
<div class="d-flex justify-content-center mt-4">
    {{ $questions->links() }}
</div>
@endif

<script>
function toggleAnswerForm(id) {
    const form = document.getElementById('answer-form-' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
function toggleEditAnswer(id) {
    const display = document.getElementById('answer-display-' + id);
    const edit = document.getElementById('answer-edit-' + id);
    const isEditing = edit.style.display !== 'none';
    display.style.display = isEditing ? 'block' : 'none';
    edit.style.display = isEditing ? 'none' : 'block';
}
</script>
@endsection
