@extends('admin.layout')

@section('title', 'Profile - Admin Panel')

@section('content')
<h2 class="mb-4"><i class="bi bi-person"></i> My Profile</h2>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Account Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label fw-bold">Name:</label>
                    <div class="col-sm-9">
                        <p class="form-control-plaintext">{{ auth()->user()->name }}</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label fw-bold">Email:</label>
                    <div class="col-sm-9">
                        <p class="form-control-plaintext">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label fw-bold">Role:</label>
                    <div class="col-sm-9">
                        <p class="form-control-plaintext">
                            @foreach(auth()->user()->roles as $role)
                                <span class="badge bg-primary">{{ ucfirst($role->name) }}</span>
                            @endforeach
                        </p>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label fw-bold">Account Created:</label>
                    <div class="col-sm-9">
                        <p class="form-control-plaintext">{{ auth()->user()->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-shield-check"></i> Security</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">For security reasons, password changes should be done through the main application.</p>
                <a href="{{ config('app.frontend_url') }}/account/profile" class="btn btn-outline-primary btn-sm" target="_blank">
                    <i class="bi bi-box-arrow-up-right"></i> Go to Main Profile
                </a>
            </div>
        </div>
    </div>
</div>

{{-- WhatsApp Chat Availability (only shown if user is a chat agent) --}}
@php
    $myAgent = \App\Models\WhatsappAgent::where('user_id', auth()->id())->with('jobTitle')->first();
@endphp

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card border-success">
            <div class="card-header" style="background:#25D366;color:#fff;">
                <h5 class="mb-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                        <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/>
                    </svg>
                    WhatsApp Chat Availability
                </h5>
            </div>
            <div class="card-body">
                @if(!$myAgent)
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> You are not currently set up as a WhatsApp chat agent.
                        Contact an administrator to be added to the chat team.
                    </div>
                @else
                    <div id="profileWaAlert" class="d-none mb-3"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Profile Photo</label>
                            <div class="d-flex align-items-center gap-3">
                                @if($myAgent->profile_picture_url)
                                    <img id="profileWaPhoto" src="{{ $myAgent->profile_picture_url }}" class="rounded-circle" width="60" height="60" style="object-fit:cover">
                                @else
                                    <div id="profileWaPhoto" class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white fw-bold" style="width:60px;height:60px;font-size:1.4rem">
                                        {{ strtoupper(substr(auth()->user()->name,0,1)) }}
                                    </div>
                                @endif
                                <label class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-camera"></i> Change Photo
                                    <input type="file" id="myPhotoInput" accept="image/*" class="d-none">
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Job Title</label>
                            <p class="form-control-plaintext text-muted">{{ $myAgent->jobTitle?->name ?? '—' }}</p>
                            <label class="form-label fw-semibold mt-1">Branch</label>
                            <p class="form-control-plaintext text-muted">{{ $myAgent->branch }}</p>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="myChatEnabled" role="switch" {{ $myAgent->chat_enabled ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="myChatEnabled">Chat Available</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">From</label>
                            <input type="time" class="form-control" id="myAvailFrom" value="{{ $myAgent->available_from ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">To</label>
                            <input type="time" class="form-control" id="myAvailTo" value="{{ $myAgent->available_to ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Available Days</label>
                            <div class="d-flex gap-2 flex-wrap">
                                @php $myDays = $myAgent->available_days ?? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']; @endphp
                                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input my-day-cb" type="checkbox" value="{{ $day }}" id="md_{{ $day }}" {{ in_array($day,$myDays)?'checked':'' }}>
                                        <label class="form-check-label" for="md_{{ $day }}">{{ $day }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-success" id="btnSaveMyProfile">
                                <i class="bi bi-check-lg"></i> Save Availability
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(isset($myAgent) && $myAgent)
<script>
const WA_CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// Save availability
document.getElementById('btnSaveMyProfile')?.addEventListener('click', async function() {
    const days = [...document.querySelectorAll('.my-day-cb:checked')].map(cb => cb.value);
    const body = new FormData();
    body.append('chat_enabled', document.getElementById('myChatEnabled').checked ? '1' : '0');
    body.append('available_from', document.getElementById('myAvailFrom').value);
    body.append('available_to', document.getElementById('myAvailTo').value);
    days.forEach(d => body.append('available_days[]', d));

    this.disabled = true;
    const resp = await fetch('{{ route("admin.whatsapp.my-profile.update") }}', {
        method: 'POST', headers: {'X-CSRF-TOKEN': WA_CSRF, 'Accept': 'application/json'}, body,
    });
    const data = await resp.json();
    const alertEl = document.getElementById('profileWaAlert');
    alertEl.className = data.success ? 'alert alert-success mb-3' : 'alert alert-danger mb-3';
    alertEl.textContent = data.message ?? (data.success ? 'Saved!' : 'Error saving');
    alertEl.classList.remove('d-none');
    this.disabled = false;
    setTimeout(() => alertEl.classList.add('d-none'), 3000);
});

// Photo upload
document.getElementById('myPhotoInput')?.addEventListener('change', async function() {
    const file = this.files[0];
    if (!file) return;
    const body = new FormData();
    body.append('photo', file);
    const resp = await fetch('{{ route("admin.whatsapp.my-profile.photo") }}', {
        method:'POST', headers:{'X-CSRF-TOKEN': WA_CSRF, 'Accept':'application/json'}, body,
    });
    const data = await resp.json();
    if (data.success) {
        const el = document.getElementById('profileWaPhoto');
        if (el.tagName === 'IMG') el.src = data.profile_picture_url;
        else {
            const img = document.createElement('img');
            img.src = data.profile_picture_url;
            img.className = 'rounded-circle';
            img.style.cssText = 'width:60px;height:60px;object-fit:cover';
            img.id = 'profileWaPhoto';
            el.replaceWith(img);
        }
    }
});
</script>
@endif
@endpush
