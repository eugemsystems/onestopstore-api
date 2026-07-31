@extends('admin.layout')

@section('title', 'WhatsApp Chat Agents')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0"><i class="bi bi-whatsapp text-success"></i> WhatsApp Chat Agents</h2>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" id="btnManageJobTitles" data-bs-toggle="modal" data-bs-target="#jobTitlesModal">
            <i class="bi bi-briefcase"></i> Manage Job Titles
        </button>
        <button class="btn btn-success" id="btnAddAgent">
            <i class="bi bi-person-plus"></i> Add Agent
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle" id="agentsTable">
            <thead class="table-dark">
                <tr>
                    <th style="width:60px">Photo</th>
                    <th>Name</th>
                    <th>Job Title</th>
                    <th>Branch</th>
                    <th>WhatsApp</th>
                    <th>Chat</th>
                    <th>Available Hours</th>
                    <th>Days</th>
                    <th style="width:110px">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr id="agent-row-{{ $agent->id }}">
                    <td>
                        @if($agent->profile_picture_url)
                            <img src="{{ $agent->profile_picture_url }}" class="rounded-circle" width="44" height="44" style="object-fit:cover" alt="{{ $agent->name }}">
                        @else
                            <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white fw-bold" style="width:44px;height:44px;font-size:1.1rem">
                                {{ strtoupper(substr($agent->name,0,1)) }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $agent->name }}</div>
                        @if($agent->user)
                            <small class="text-muted">{{ $agent->user->email }}</small>
                        @endif
                    </td>
                    <td>{{ $agent->jobTitle?->name ?? '—' }}</td>
                    <td>{{ $agent->branch }}</td>
                    <td><a href="https://wa.me/{{ preg_replace('/\D/','',$agent->whatsapp_number) }}" target="_blank" class="text-success text-decoration-none"><i class="bi bi-whatsapp"></i> {{ $agent->whatsapp_number }}</a></td>
                    <td>
                        @if($agent->chat_enabled)
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> On</span>
                        @else
                            <span class="badge bg-secondary">Off</span>
                        @endif
                    </td>
                    <td>
                        @if($agent->available_from && $agent->available_to)
                            {{ \Carbon\Carbon::parse($agent->available_from)->format('H:i') }} – {{ \Carbon\Carbon::parse($agent->available_to)->format('H:i') }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><small>{{ $agent->available_days ? implode(', ', $agent->available_days) : 'All' }}</small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary btn-edit-agent"
                                data-agent='@json($agent)'
                                title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <label class="btn btn-sm btn-outline-secondary" title="Upload Photo">
                                <i class="bi bi-camera"></i>
                                <input type="file" class="d-none photo-upload-input" data-agent-id="{{ $agent->id }}" accept="image/*">
                            </label>
                            <button class="btn btn-sm btn-outline-danger btn-delete-agent" data-agent-id="{{ $agent->id }}" data-agent-name="{{ $agent->name }}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-whatsapp display-6 d-block mb-2"></i>No agents yet. Click <strong>Add Agent</strong> to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Agent Modal --}}
<div class="modal fade" id="agentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-whatsapp"></i> <span id="agentModalTitle">Add Chat Agent</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="agentForm">
                    @csrf
                    <input type="hidden" id="agentId" value="">
                    <div class="row g-3">

                        {{-- Step 1: Role filter --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Filter by Role <small class="text-muted fw-normal">(to search users)</small></label>
                            <select class="form-select" id="agentRoleFilter">
                                <option value="">— Select a role —</option>
                            </select>
                        </div>

                        {{-- Step 2: Users in that role --}}
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Link to User Account <small class="text-muted fw-normal">(optional)</small></label>
                            <select class="form-select" id="agentUserId" name="user_id" disabled>
                                <option value="">— Select a role first —</option>
                            </select>
                            <div id="agentUserSpinner" class="d-none mt-1">
                                <span class="spinner-border spinner-border-sm text-success"></span>
                                <small class="text-muted ms-1">Loading users…</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Display Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="agentName" name="name" required maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">WhatsApp Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="agentWhatsapp" name="whatsapp_number" placeholder="+263771234567" required maxlength="30">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Job Title</label>
                            <select class="form-select" id="agentJobTitle" name="job_title_id">
                                <option value="">— Select —</option>
                                @foreach($jobTitles as $jt)
                                    <option value="{{ $jt->id }}">{{ $jt->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch</label>
                            <input type="text" class="form-control" id="agentBranch" name="branch" maxlength="100" placeholder="e.g. Harare">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Available From</label>
                            <input type="time" class="form-control" id="agentFrom" name="available_from">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Available To</label>
                            <input type="time" class="form-control" id="agentTo" name="available_to">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" class="form-control" id="agentSort" name="sort_order" value="0" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Available Days</label>
                            <div class="d-flex gap-2 flex-wrap">
                                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input day-checkbox" type="checkbox" name="available_days[]" value="{{ $day }}" id="day_{{ $day }}" checked>
                                        <label class="form-check-label" for="day_{{ $day }}">{{ $day }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="agentEnabled" name="chat_enabled" role="switch">
                                <label class="form-check-label fw-semibold" for="agentEnabled">Chat Enabled</label>
                            </div>
                        </div>
                    </div>
                    <div id="agentFormError" class="alert alert-danger mt-3 d-none"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="btnSaveAgent">
                    <span class="spinner-border spinner-border-sm d-none" id="agentSpinner"></span>
                    <i class="bi bi-check-lg"></i> Save Agent
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Job Titles Modal --}}
<div class="modal fade" id="jobTitlesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="bi bi-briefcase"></i> Manage Job Titles</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Add new --}}
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="newJobTitle" placeholder="New job title e.g. Sales Consultant" maxlength="100">
                    <button class="btn btn-success" id="btnAddJobTitle"><i class="bi bi-plus"></i> Add</button>
                </div>

                <ul class="list-group" id="jobTitlesList">
                    @foreach($jobTitles as $jt)
                        <li class="list-group-item" id="jt-{{ $jt->id }}">
                            {{-- View row --}}
                            <div class="d-flex justify-content-between align-items-center jt-view-row">
                                <span class="jt-label">{{ $jt->name }}</span>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-primary btn-edit-jt" data-id="{{ $jt->id }}" data-name="{{ $jt->name }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete-jt" data-id="{{ $jt->id }}" data-name="{{ $jt->name }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            {{-- Edit row (hidden by default) --}}
                            <div class="d-none jt-edit-row mt-2">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control jt-edit-input" value="{{ $jt->name }}" maxlength="100">
                                    <button class="btn btn-success btn-save-jt" data-id="{{ $jt->id }}"><i class="bi bi-check-lg"></i> Save</button>
                                    <button class="btn btn-secondary btn-cancel-jt"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div id="jtError" class="alert alert-danger mt-2 d-none"></div>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirm Modal --}}
<div class="modal fade" id="deleteAgentModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Remove Agent</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Remove <strong id="deleteAgentName"></strong> from chat agents?</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger" id="btnConfirmDeleteAgent">Remove</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF            = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const AGENTS_URL      = '{{ route("admin.whatsapp.agents.store") }}';
const JT_BASE_URL     = '{{ url("admin/whatsapp/job-titles") }}';
const AGENT_BASE_URL  = '{{ url("admin/whatsapp/agents") }}';
const ROLES_URL       = '{{ route("admin.whatsapp.roles") }}';
const USERS_BY_ROLE_URL = '{{ route("admin.whatsapp.users-by-role") }}';

// ─── Role → User cascade ──────────────────────────────────────────────────────

let rolesLoaded = false;
async function loadRoles() {
    if (rolesLoaded) return;
    const resp = await fetch(ROLES_URL, { headers: { 'Accept': 'application/json' } });
    const data = await resp.json();
    if (!data.success) return;
    const sel = document.getElementById('agentRoleFilter');
    data.roles.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.name;
        opt.textContent = r.name.charAt(0).toUpperCase() + r.name.slice(1);
        sel.appendChild(opt);
    });
    rolesLoaded = true;
}

document.getElementById('agentRoleFilter').addEventListener('change', async function () {
    const role    = this.value;
    const userSel = document.getElementById('agentUserId');
    const spinner = document.getElementById('agentUserSpinner');

    userSel.innerHTML = '<option value="">— None (standalone) —</option>';
    userSel.disabled  = true;
    if (!role) return;

    spinner.classList.remove('d-none');
    try {
        const resp = await fetch(`${USERS_BY_ROLE_URL}?role=${encodeURIComponent(role)}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await resp.json();
        if (data.success && data.users.length) {
            data.users.forEach(u => {
                const opt = document.createElement('option');
                opt.value          = u.id;
                opt.dataset.name   = u.name;
                opt.dataset.branch = u.branch ?? '';
                opt.textContent    = `${u.name} (${u.email})`;
                userSel.appendChild(opt);
            });
            userSel.disabled = false;
        } else {
            userSel.innerHTML = '<option value="">— No users found for this role —</option>';
        }
    } catch {
        userSel.innerHTML = '<option value="">— Error loading users —</option>';
    } finally {
        spinner.classList.add('d-none');
    }
});

document.getElementById('agentUserId').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (opt.value) {
        if (!document.getElementById('agentName').value)
            document.getElementById('agentName').value = opt.dataset.name ?? '';
        if (!document.getElementById('agentBranch').value)
            document.getElementById('agentBranch').value = opt.dataset.branch ?? '';
    }
});

// ─── Modal helpers ────────────────────────────────────────────────────────────

function resetModal() {
    document.getElementById('agentId').value = '';
    document.getElementById('agentForm').reset();
    document.querySelectorAll('.day-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('agentFormError').classList.add('d-none');
    document.getElementById('agentRoleFilter').value = '';
    const userSel = document.getElementById('agentUserId');
    userSel.innerHTML = '<option value="">— Select a role first —</option>';
    userSel.disabled  = true;
}

// Open Add
document.getElementById('btnAddAgent').addEventListener('click', async function () {
    resetModal();
    document.getElementById('agentModalTitle').textContent = 'Add Chat Agent';
    await loadRoles();
    new bootstrap.Modal(document.getElementById('agentModal')).show();
});

// Open Edit
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-edit-agent');
    if (!btn) return;
    const agent = JSON.parse(btn.dataset.agent);

    resetModal();
    await loadRoles();

    document.getElementById('agentId').value = agent.id;
    document.getElementById('agentModalTitle').textContent = 'Edit Agent';

    // If linked to a user, load all non-customer users so the current one shows
    if (agent.user_id) {
        const userSel = document.getElementById('agentUserId');
        userSel.innerHTML = `<option value="">— None (standalone) —</option><option value="${agent.user_id}" selected>Loading… (#${agent.user_id})</option>`;
        userSel.disabled = false;
        const resp = await fetch(USERS_BY_ROLE_URL, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();
        if (data.success) {
            userSel.innerHTML = '<option value="">— None (standalone) —</option>';
            data.users.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.dataset.name = u.name;
                opt.dataset.branch = u.branch ?? '';
                opt.textContent = `${u.name} (${u.email})`;
                if (u.id == agent.user_id) opt.selected = true;
                userSel.appendChild(opt);
            });
        }
    }

    document.getElementById('agentName').value      = agent.name;
    document.getElementById('agentWhatsapp').value  = agent.whatsapp_number;
    document.getElementById('agentJobTitle').value  = agent.job_title_id ?? '';
    document.getElementById('agentBranch').value    = agent.branch ?? '';
    document.getElementById('agentFrom').value      = agent.available_from ?? '';
    document.getElementById('agentTo').value        = agent.available_to ?? '';
    document.getElementById('agentSort').value      = agent.sort_order ?? 0;
    document.getElementById('agentEnabled').checked = !!agent.chat_enabled;
    const days = agent.available_days ?? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    document.querySelectorAll('.day-checkbox').forEach(cb => cb.checked = days.includes(cb.value));
    new bootstrap.Modal(document.getElementById('agentModal')).show();
});

// ─── Save agent ───────────────────────────────────────────────────────────────

document.getElementById('btnSaveAgent').addEventListener('click', async function () {
    const id   = document.getElementById('agentId').value;
    const days = [...document.querySelectorAll('.day-checkbox:checked')].map(cb => cb.value);
    const body = new FormData(document.getElementById('agentForm'));
    body.set('chat_enabled', document.getElementById('agentEnabled').checked ? '1' : '0');
    body.delete('available_days[]');
    days.forEach(d => body.append('available_days[]', d));
    if (id) body.append('_method', 'PUT');

    const spinner = document.getElementById('agentSpinner');
    spinner.classList.remove('d-none');
    this.disabled = true;
    try {
        const url  = id ? `${AGENT_BASE_URL}/${id}` : AGENTS_URL;
        const resp = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
        const data = await resp.json();
        if (!resp.ok || !data.success) throw new Error(data.message ?? 'Error saving agent');
        bootstrap.Modal.getInstance(document.getElementById('agentModal')).hide();
        location.reload();
    } catch (err) {
        document.getElementById('agentFormError').textContent = err.message;
        document.getElementById('agentFormError').classList.remove('d-none');
    } finally {
        spinner.classList.add('d-none');
        this.disabled = false;
    }
});

// ─── Delete agent ─────────────────────────────────────────────────────────────

let deleteAgentId = null;
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-delete-agent');
    if (!btn) return;
    deleteAgentId = btn.dataset.agentId;
    document.getElementById('deleteAgentName').textContent = btn.dataset.agentName;
    new bootstrap.Modal(document.getElementById('deleteAgentModal')).show();
});
document.getElementById('btnConfirmDeleteAgent').addEventListener('click', async function () {
    if (!deleteAgentId) return;
    this.disabled = true;
    const resp = await fetch(`${AGENT_BASE_URL}/${deleteAgentId}`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: new URLSearchParams({ '_method': 'DELETE' }),
    });
    const data = await resp.json();
    if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('deleteAgentModal')).hide();
        document.getElementById(`agent-row-${deleteAgentId}`)?.remove();
    }
    this.disabled = false;
});

// ─── Photo upload ─────────────────────────────────────────────────────────────

document.addEventListener('change', async function (e) {
    if (!e.target.classList.contains('photo-upload-input')) return;
    const agentId = e.target.dataset.agentId;
    const file    = e.target.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('photo', file);
    const resp = await fetch(`${AGENT_BASE_URL}/${agentId}/upload-photo`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd,
    });
    const data = await resp.json();
    if (data.success) location.reload();
    else showError('Error', 'Photo upload failed: ' + (data.message ?? 'Unknown error'));
});

// ─── Job Titles: Add ─────────────────────────────────────────────────────────

document.getElementById('btnAddJobTitle').addEventListener('click', async function () {
    const input = document.getElementById('newJobTitle');
    const name  = input.value.trim();
    if (!name) return;
    const resp = await fetch(JT_BASE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ name }),
    });
    const data = await resp.json();
    if (data.success) {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        li.id = `jt-${data.data.id}`;
        li.innerHTML = `
            <div class="d-flex justify-content-between align-items-center jt-view-row">
                <span class="jt-label">${data.data.name}</span>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary btn-edit-jt" data-id="${data.data.id}" data-name="${data.data.name}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger btn-delete-jt" data-id="${data.data.id}" data-name="${data.data.name}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="d-none jt-edit-row mt-2">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control jt-edit-input" value="${data.data.name}" maxlength="100">
                    <button class="btn btn-success btn-save-jt" data-id="${data.data.id}"><i class="bi bi-check-lg"></i> Save</button>
                    <button class="btn btn-secondary btn-cancel-jt"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>`;
        document.getElementById('jobTitlesList').appendChild(li);
        // Also add to agent modal select
        const opt = document.createElement('option');
        opt.value = data.data.id;
        opt.textContent = data.data.name;
        document.getElementById('agentJobTitle').appendChild(opt);
        input.value = '';
        document.getElementById('jtError').classList.add('d-none');
    } else {
        document.getElementById('jtError').textContent = data.message ?? 'Error adding job title';
        document.getElementById('jtError').classList.remove('d-none');
    }
});

// ─── Job Titles: Edit (show inline edit row) ──────────────────────────────────

document.addEventListener('click', function (e) {
    // Show edit row
    const editBtn = e.target.closest('.btn-edit-jt');
    if (editBtn) {
        const li = document.getElementById(`jt-${editBtn.dataset.id}`);
        li.querySelector('.jt-view-row').classList.add('d-none');
        const editRow = li.querySelector('.jt-edit-row');
        editRow.classList.remove('d-none');
        editRow.querySelector('.jt-edit-input').focus();
        return;
    }

    // Cancel edit
    const cancelBtn = e.target.closest('.btn-cancel-jt');
    if (cancelBtn) {
        const li = cancelBtn.closest('li');
        li.querySelector('.jt-view-row').classList.remove('d-none');
        li.querySelector('.jt-edit-row').classList.add('d-none');
        return;
    }
});

// ─── Job Titles: Save edit ────────────────────────────────────────────────────

document.addEventListener('click', async function (e) {
    const saveBtn = e.target.closest('.btn-save-jt');
    if (!saveBtn) return;
    const id   = saveBtn.dataset.id;
    const li   = document.getElementById(`jt-${id}`);
    const name = li.querySelector('.jt-edit-input').value.trim();
    if (!name) return;

    saveBtn.disabled = true;
    const resp = await fetch(`${JT_BASE_URL}/${id}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, _method: 'PUT' }),
    });
    const data = await resp.json();
    saveBtn.disabled = false;

    if (data.success) {
        // Update view label
        li.querySelector('.jt-label').textContent = data.data.name;
        // Update edit btn data-name
        li.querySelector('.btn-edit-jt').dataset.name   = data.data.name;
        li.querySelector('.btn-delete-jt').dataset.name = data.data.name;
        // Update agent modal option
        const agentOpt = document.querySelector(`#agentJobTitle option[value="${id}"]`);
        if (agentOpt) agentOpt.textContent = data.data.name;
        // Hide edit row
        li.querySelector('.jt-view-row').classList.remove('d-none');
        li.querySelector('.jt-edit-row').classList.add('d-none');
    } else {
        document.getElementById('jtError').textContent = data.message ?? 'Error updating job title';
        document.getElementById('jtError').classList.remove('d-none');
    }
});

// ─── Job Titles: Delete ───────────────────────────────────────────────────────

document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.btn-delete-jt');
    if (!btn) return;
    const _r = await Swal.fire({ title: 'Are you sure?', text: `Delete job title "${btn.dataset.name}"?`, icon: 'warning', showCancelButton: true, confirmButtonColor: SwalConfig.confirmColor, cancelButtonColor: SwalConfig.cancelColor });
    if (!_r.isConfirmed) return;
    const resp = await fetch(`${JT_BASE_URL}/${btn.dataset.id}`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: new URLSearchParams({ '_method': 'DELETE' }),
    });
    const data = await resp.json();
    if (data.success) document.getElementById(`jt-${btn.dataset.id}`)?.remove();
});
</script>
@endpush
