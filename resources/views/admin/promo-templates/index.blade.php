@extends('admin.layout')

@section('title', 'Promo Templates - Admin Panel')

@push('styles')
<style>
/* ── Hero ──────────────────────────────────────────────────────────────── */
.pt-hero {
    background: linear-gradient(135deg, #0f0c29 0%, #302b63 55%, #24243e 100%);
    border-radius: 16px;
    padding: 28px 32px;
    color: #fff;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
}
.pt-hero h1 { font-size: 1.6rem; font-weight: 700; margin: 0; letter-spacing: -.4px; }
.pt-hero p  { margin: 3px 0 0; opacity: .65; font-size: 13.5px; }

/* ── Grid ──────────────────────────────────────────────────────────────── */
.tpl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}

/* ── Card ──────────────────────────────────────────────────────────────── */
.tpl-card {
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform .18s ease, box-shadow .18s ease;
    border: 1px solid rgba(0,0,0,.05);
}
.tpl-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(0,0,0,.14);
}

/* Thumb — fixed height, FULL image visible (no crop) */
.tpl-thumb {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: #1e1b2e;   /* dark base for letterboxing */
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
/* Blurred background fill (decorative, matches image colour) */
.tpl-thumb::before {
    content: '';
    position: absolute;
    inset: 0;
    background: inherit;
    z-index: 0;
}
.tpl-thumb img {
    position: relative;
    z-index: 1;
    max-width: 100%;
    max-height: 100%;
    width: 100%;
    height: 100%;
    object-fit: contain;   /* shows the FULL image — no cropping */
    display: block;
    transition: transform .3s ease;
}
.tpl-card:hover .tpl-thumb img { transform: scale(1.04); }

.tpl-thumb .no-img {
    width: 100%; height: 100%;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    color: #c0c8d6; gap: 8px;
}
.tpl-thumb .no-img i { font-size: 44px; }

.tpl-thumb .status-badge {
    position: absolute; top: 10px; right: 10px;
    z-index: 2;
    font-size: 10.5px; padding: 3px 9px;
    border-radius: 20px; font-weight: 600;
    backdrop-filter: blur(4px);
    background: rgba(0,0,0,.45); color: #fff;
    border: 1px solid rgba(255,255,255,.25);
}
.tpl-thumb .status-badge.active-badge { background: rgba(34,197,94,.85); }

/* Body */
.tpl-body {
    padding: 13px 15px 6px;
    flex: 1;
}
.tpl-body h5 {
    font-size: 14.5px; font-weight: 700; margin: 0 0 3px;
    color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.tpl-body .tpl-desc {
    font-size: 12px; color: #64748b; line-height: 1.45; min-height: 32px;
}
.tpl-body .tpl-meta {
    font-size: 11px; color: #94a3b8; margin-top: 6px;
    display: flex; align-items: center; gap: 5px;
}

/* Actions */
.tpl-actions {
    padding: 10px 12px 13px;
    display: flex;
    flex-direction: column;
    gap: 7px;
}
.tpl-actions-main {
    display: flex;
    gap: 6px;
}
.tpl-actions-icons {
    display: flex;
    gap: 6px;
    justify-content: center;
}
.btn-gen {
    flex: 1;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none; color: #fff;
    font-size: 12px; font-weight: 600;
    border-radius: 9px; padding: 7px 8px;
    transition: opacity .15s, transform .12s;
    text-decoration: none; text-align: center;
}
.btn-gen:hover { opacity: .86; transform: translateY(-1px); color: #fff; }
.btn-sku {
    flex: 1;
    background: linear-gradient(135deg, #10b981, #34d399);
    border: none; color: #fff;
    font-size: 12px; font-weight: 600;
    border-radius: 9px; padding: 7px 8px;
    transition: opacity .15s, transform .12s;
    text-decoration: none; text-align: center;
}
.btn-sku:hover { opacity: .86; transform: translateY(-1px); color: #fff; }
.btn-bgconv {
    flex: 1;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    border: none; color: #fff;
    font-size: 12px; font-weight: 600;
    border-radius: 9px; padding: 7px 8px;
    transition: opacity .15s, transform .12s;
    text-decoration: none; text-align: center;
}
.btn-bgconv:hover { opacity: .86; transform: translateY(-1px); color: #fff; }
.btn-ico {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    display: flex; align-items: center; justify-content: center;
    color: #64748b; font-size: 13px;
    cursor: pointer;
    transition: background .12s, border-color .12s, color .12s;
    text-decoration: none;
    flex-shrink: 0;
    padding: 0;
}
.btn-ico:hover { background: #f1f5f9; border-color: #94a3b8; color: #1e293b; }
.btn-ico.danger:hover { background: #fef2f2; border-color: #fca5a5; color: #dc2626; }

/* Empty state */
.empty-state {
    text-align: center; padding: 80px 20px; color: #94a3b8;
}
.empty-state i { font-size: 60px; display: block; margin-bottom: 14px; }
.empty-state h4 { color: #334155; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- Hero --}}
    <div class="pt-hero">
        <div>
            <h1><i class="bi bi-layout-text-sidebar-reverse me-2"></i>Promo Templates</h1>
            <p>Design and manage your promotional banner templates</p>
        </div>
        <a href="{{ route('admin.promo-templates.create') }}" class="btn btn-light fw-semibold px-4">
            <i class="bi bi-plus-circle me-2"></i>New Template
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Grid --}}
    @if($templates->isNotEmpty())
    <div class="tpl-grid">
        @foreach($templates as $template)
        <div class="tpl-card">

            {{-- Thumbnail --}}
            <div class="tpl-thumb">
                @if($template->preview_image)
                    <img src="{{ $template->preview_image }}" alt="{{ $template->name }}"
                         onerror="this.parentElement.innerHTML='<div class=\'no-img\'><i class=\'bi bi-image\'></i><span style=\'font-size:12px\'>No preview</span></div>'">
                @else
                    <div class="no-img">
                        <i class="bi bi-file-earmark-image"></i>
                        <span style="font-size:12px">No preview set</span>
                    </div>
                @endif
                <span class="status-badge {{ $template->status ? 'active-badge' : '' }}">
                    {{ $template->status ? 'Active' : 'Inactive' }}
                </span>
            </div>

            {{-- Info --}}
            <div class="tpl-body">
                <h5 title="{{ $template->name }}">{{ $template->name }}</h5>
                <div class="tpl-desc">{{ Str::limit($template->description, 80) ?: '—' }}</div>
                <div class="tpl-meta">
                    <i class="bi bi-calendar2"></i>
                    {{ $template->created_at->format('d M Y') }}
                </div>
            </div>

            {{-- Actions --}}
            <div class="tpl-actions">
                <div class="tpl-actions-main">
                    <a href="{{ route('admin.promo-templates.generate', $template->id) }}" class="btn-gen">
                        <i class="bi bi-magic me-1"></i>Generate
                    </a>
                    <a href="{{ route('admin.promo-templates.generate-sku', $template->id) }}" class="btn-sku">
                        <i class="bi bi-upc-scan me-1"></i>SKUs Python
                    </a>
                </div>
                <div class="tpl-actions-main">
                    <a href="{{ route('admin.promo-templates.generate-sku-bg', $template->id) }}" class="btn-bgconv">
                        <i class="bi bi-stars me-1"></i>SKUs CSS
                    </a>
                </div>
                <div class="tpl-actions-icons">
                    <a href="{{ route('admin.promo-templates.edit', $template->id) }}" class="btn-ico" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form action="{{ route('admin.promo-templates.duplicate', $template->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn-ico" title="Duplicate"><i class="bi bi-files"></i></button>
                    </form>
                    <form action="{{ route('admin.promo-templates.destroy', $template->id) }}" method="POST"
                          class="d-inline delete-form" data-template-name="{{ $template->name }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-ico danger" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h4>No Templates Yet</h4>
        <p class="mt-1 mb-3">Create your first promo template to get started.</p>
        <a href="{{ route('admin.promo-templates.create') }}" class="btn btn-primary px-5">
            <i class="bi bi-plus-circle me-2"></i>Create Template
        </a>
    </div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const _r = await Swal.fire({ title: 'Are you sure?', text: `Delete template "${this.dataset.templateName}"?\n\nThis action cannot be undone.`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
            if (_r.isConfirmed) {
                this.submit();
            }
        });
    });
});
</script>
@endsection
