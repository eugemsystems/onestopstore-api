@extends('admin.layout')
@section('title', 'HTML Catalog Pages')

@section('content')
<div class="mb-3">
    <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-code-fill me-2" style="color:#7c3aed"></i>HTML Catalog Pages</h4>
    <p class="text-muted mb-0 small">
        Upload an HTML file and publish it at <code>{{ $baseUrl }}<em>your-slug</em></code>.
        The link never changes — upload a new file for the same link to replace what it shows.
    </p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-3">
        <ul class="mb-0">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

@can('catalog-pages.create')
<div class="card mb-4">
    <div class="card-header fw-semibold">Create a new link</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.catalog-pages.store') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
            @csrf
            <div class="col-lg-5">
                <label class="form-label fw-semibold" for="slug">Link</label>
                <div class="input-group">
                    <span class="input-group-text small">{{ $baseUrl }}</span>
                    <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug') }}"
                           placeholder="summer-catalog" required maxlength="100" pattern="[A-Za-z0-9]+([_-][A-Za-z0-9]+)*">
                </div>
                <div class="form-text">Lowercase letters, numbers, "-" and "_". This cannot be changed later.</div>
            </div>
            <div class="col-lg-3">
                <label class="form-label fw-semibold" for="title">Label <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" maxlength="150" placeholder="For your reference">
            </div>
            <div class="col-lg-3">
                <label class="form-label fw-semibold" for="html_file">HTML file</label>
                <input type="file" class="form-control" id="html_file" name="html_file" accept=".html,.htm,text/html" required>
                <div class="form-text">.html / .htm, up to {{ $maxMb }} MB</div>
            </div>
            <div class="col-lg-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload"></i></button>
            </div>
        </form>
    </div>
</div>
@endcan

<div class="card">
    <div class="card-header fw-semibold">Published links ({{ $pages->count() }})</div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Link</th>
                    <th>Clicks</th>
                    <th>Current file</th>
                    <th>Updated</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($pages as $page)
                <tr>
                    <td>
                        <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="fw-semibold">/catalog/{{ $page->slug }}</a>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-1 copy-link" data-url="{{ $page->publicUrl() }}" title="Copy full link"><i class="bi bi-clipboard"></i></button>
                        @if($page->title)<div class="text-muted small">{{ $page->title }}</div>@endif
                    </td>
                    @php
                        $rows = $daily[$page->id] ?? collect();
                        $todayHits = (int) optional($rows->firstWhere('date', $today))->hits;
                        $weekHits = (int) $rows->where('date', '>=', $weekStart)->sum('hits');
                    @endphp
                    <td>
                        <div class="fs-5 fw-bold lh-1" title="Times the link was opened by visitors (search engines and link-preview bots are not counted)">{{ number_format($page->hits_total) }}</div>
                        <div class="small text-muted">today {{ number_format($todayHits) }} · 7 days {{ number_format($weekHits) }}</div>
                        <div class="small text-muted">
                            @if($page->last_hit_at)last clicked {{ $page->last_hit_at->diffForHumans() }}@else no clicks yet @endif
                            @if($page->bot_hits_total > 0)<span title="Crawlers / link-preview bots, not counted as clicks"> · {{ number_format($page->bot_hits_total) }} bot</span>@endif
                        </div>
                        <button class="btn btn-link btn-sm p-0" type="button" data-bs-toggle="collapse" data-bs-target="#stats-{{ $page->id }}">daily breakdown</button>
                    </td>
                    <td class="small">
                        @if($page->fileExists())
                            {{ $page->original_name ?: $page->slug.'.html' }}
                            <span class="text-muted">· {{ number_format($page->size / 1024, 1) }} KB · v{{ $page->version }}</span>
                        @else
                            <span class="text-danger">File missing — upload it again</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $page->updated_at->diffForHumans() }}</td>
                    <td>
                        @can('catalog-pages.update')
                        <form method="POST" action="{{ route('admin.catalog-pages.update', $page) }}">
                            @csrf @method('PUT')
                            <input type="hidden" name="_toggle" value="1">
                            <input type="hidden" name="is_active" value="{{ $page->is_active ? 0 : 1 }}">
                            <button type="submit" class="btn btn-sm {{ $page->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                                    title="{{ $page->is_active ? 'Live — click to unpublish (link shows a 404)' : 'Unpublished — click to publish' }}">
                                {{ $page->is_active ? 'Live' : 'Off' }}
                            </button>
                        </form>
                        @else
                            <span class="badge {{ $page->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $page->is_active ? 'Live' : 'Off' }}</span>
                        @endcan
                    </td>
                    <td class="text-end text-nowrap">
                        @can('catalog-pages.update')
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#replace-{{ $page->id }}">
                            <i class="bi bi-arrow-repeat me-1"></i>Replace file
                        </button>
                        @endcan
                        @if($page->fileExists())
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.catalog-pages.download', $page) }}" title="Download the current file"><i class="bi bi-download"></i></a>
                        @endif
                        @can('catalog-pages.destroy')
                        <form method="POST" action="{{ route('admin.catalog-pages.destroy', $page) }}" class="d-inline"
                              onsubmit="return confirm('Delete /catalog/{{ $page->slug }}? The link will stop working.');">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                        @endcan
                    </td>
                </tr>
                <tr class="collapse" id="stats-{{ $page->id }}">
                    <td colspan="6" class="bg-light">
                        <div class="small fw-semibold mb-1">Clicks per day — last 30 days</div>
                        @if($rows->isEmpty())
                            <span class="text-muted small">Nothing recorded yet.</span>
                        @else
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($rows as $r)
                                    <span class="badge text-bg-light border">{{ \Carbon\Carbon::parse($r->date)->format('d M') }}: <strong>{{ number_format($r->hits) }}</strong></span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                </tr>
                @can('catalog-pages.update')
                <tr class="collapse" id="replace-{{ $page->id }}">
                    <td colspan="6" class="bg-light">
                        <form method="POST" action="{{ route('admin.catalog-pages.update', $page) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                            @csrf @method('PUT')
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold mb-1">New HTML file <span class="text-muted fw-normal">(replaces the current one)</span></label>
                                <input type="file" name="html_file" class="form-control form-control-sm" accept=".html,.htm,text/html" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Label</label>
                                <input type="text" name="title" class="form-control form-control-sm" value="{{ $page->title }}" maxlength="150">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-sm btn-primary w-100" type="submit"><i class="bi bi-upload me-1"></i>Upload &amp; replace</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endcan
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No pages yet — create your first link above.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('.copy-link').forEach(function (btn) {
    btn.addEventListener('click', function () {
        navigator.clipboard.writeText(btn.dataset.url).then(function () {
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(function () { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
        });
    });
});
</script>
@endsection
