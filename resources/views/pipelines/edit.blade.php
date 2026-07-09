@php
use Illuminate\Support\Facades\Storage;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="fs-4 fw-semibold">Edit pipeline — {{ $pipeline->project_name }}</h2>
    </x-slot>

    <div class="container py-4">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        @include('pipelines.partials.stage-bar')

        {{-- Stage action buttons --}}
        @php
            $labels = [
                'qualify' => 'Qualify', 'proposal_submitted' => 'Proposal Submitted',
                'shortlisted' => 'Shortlisted', 'verbal' => 'Verbal', 'contract' => 'Contract',
                'renewal' => 'Renewal', 'decline' => 'Decline', 'loss' => 'Loss',
                'new' => 'New',
            ];
            $transitions = [
                'new' => ['qualify'], 'qualify' => ['proposal_submitted', 'loss'],
                'proposal_submitted' => ['shortlisted', 'loss'], 'shortlisted' => ['verbal', 'loss'],
                'verbal' => ['contract', 'loss'], 'contract' => ['renewal', 'decline'],
            ];
            $nextOptions = $transitions[$pipeline->stage] ?? [];
            $primary = $nextOptions[0] ?? null;
            $branch = $nextOptions[1] ?? null;
        @endphp

        <div class="d-flex gap-2 flex-wrap mb-4" id="stage-actions">
            @if ($primary)
                <button type="button" class="btn btn-primary" data-advance-stage="{{ $primary }}">
                    Next Stage: {{ $labels[$primary] }}
                </button>
            @endif

            @if ($branch)
                <button type="button" class="btn btn-outline-danger" data-advance-stage="{{ $branch }}"
                        data-confirm="Are you sure you want to mark this pipeline as {{ $labels[$branch] }}? This cannot be undone.">
                    Mark as {{ $labels[$branch] }}
                </button>
            @endif

            @if (in_array(auth()->user()->role, ['manager', 'admin']))
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Rollback stage
                    </button>
                    <ul class="dropdown-menu">
                        @foreach (['new','qualify','proposal_submitted','shortlisted','verbal','contract'] as $stage)
                            @if ($stage !== $pipeline->stage)
                                <li><a class="dropdown-item" href="#" data-advance-stage="{{ $stage }}"
                                       data-confirm="Rollback pipeline to {{ $labels[$stage] }}?">
                                    {{ $labels[$stage] }}
                                </a></li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div id="advance-errors" class="alert alert-danger d-none"></div>

        {{-- Qualify fields --}}
        <div class="card mb-3">
            <div class="card-header fw-semibold">Opportunity information (Qualify)</div>
            <div class="card-body row g-3">

                {{-- Read-only customer info --}}
                <div class="col-md-4">
                    <label class="form-label text-muted">Customer name</label>
                    <input type="text" class="form-control" value="{{ $pipeline->customer->name }}" readonly disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted">Customer email</label>
                    <input type="text" class="form-control" value="{{ $pipeline->customer->email }}" readonly disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted">Customer phone</label>
                    <input type="text" class="form-control" value="{{ $pipeline->customer->phone }}" readonly disabled>
                </div>

                {{-- Read-only department --}}
                <div class="col-md-6">
                    <label class="form-label text-muted">Department / Region</label>
                    <input type="text" class="form-control" value="{{ $pipeline->department->name ?? '—' }}" readonly disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted">Salesperson</label>
                    <input type="text" class="form-control" value="{{ $pipeline->salesperson->name }}" readonly disabled>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Project name</label>
                    <input type="text" class="form-control" data-autosave="project_name" value="{{ $pipeline->project_name }}">
                    <small class="autosave-indicator text-muted"></small>
                </div>

                {{-- Chance widget --}}
                <div class="col-md-6">
                    <label class="form-label d-block">Chance</label>
                    <div class="btn-group" role="group" data-chance-widget>
                        @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $val => $label)
                            <input type="radio" class="btn-check" name="chance_level" id="chance_{{ $val }}" value="{{ $val }}" autocomplete="off" @checked($pipeline->chance_level === $val)>
                            <label class="btn btn-outline-secondary" for="chance_{{ $val }}">{{ $label }}</label>
                        @endforeach
                    </div>
                    <small class="autosave-indicator text-muted d-block mt-1"></small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Expected start date</label>
                    <input type="date" class="form-control" data-autosave="expected_start_date" min="{{ now()->toDateString() }}" value="{{ $pipeline->expected_start_date?->toDateString() }}">
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Customer product</label>
                    <input type="text" class="form-control" data-autosave="customer_product" value="{{ $pipeline->customer_product }}">
                    <small class="autosave-indicator text-muted"></small>
                </div>

                {{-- Scope of service dropdown --}}
                <div class="col-12">
                    <label class="form-label">Scope of service</label>
                    <select class="form-select" data-autosave="scope_of_service">
                        <option value="">-- Select --</option>
                        @foreach ($scopeOptions as $option)
                            <option value="{{ $option }}" @selected($pipeline->scope_of_service === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <small class="autosave-indicator text-muted"></small>
                    @if (in_array(auth()->user()->role, ['manager', 'hod', 'admin']))
                        <small class="d-block mt-1"><a href="{{ route('dropdown-options.index') }}">Manage options</a></small>
                    @endif
                </div>
            </div>
        </div>

        {{-- Proposal / Shortlisted / Verbal fields --}}
        @if ($showProposalGroup)
        <div class="card mb-3">
            <div class="card-header fw-semibold">Proposal / Shortlisted / Verbal</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Value per annum (RM)</label>
                    <input type="number" step="0.01" class="form-control" data-autosave="value_per_annum" value="{{ $pipeline->value_per_annum }}">
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contract period</label>
                    <select class="form-select" id="contract_period" data-autosave="contract_period">
                        <option value="">-- Select --</option>
                        <option value="long_term" @selected($pipeline->contract_period === 'long_term')>Long Term</option>
                        <option value="adhoc" @selected($pipeline->contract_period === 'adhoc')>AdHoc</option>
                    </select>
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Number of months</label>
                    <input type="number" min="1" max="12" class="form-control" id="number_of_months" data-autosave="number_of_months" value="{{ $pipeline->number_of_months }}">
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Operating profit margin (%)</label>
                    <input type="number" step="0.01" class="form-control" data-autosave="operating_profit_margin" value="{{ $pipeline->operating_profit_margin }}">
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Origin country</label>
                    <select class="form-select" data-autosave="origin_country">
                        <option value="">-- Select --</option>
                        @foreach ($countryOptions as $option)
                            <option value="{{ $option }}" @selected($pipeline->origin_country === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Port of loading</label>
                    <select class="form-select" data-autosave="port_of_loading">
                        <option value="">-- Select --</option>
                        @foreach ($portOptions as $option)
                            <option value="{{ $option }}" @selected($pipeline->port_of_loading === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Destination country</label>
                    <select class="form-select" data-autosave="destination_country">
                        <option value="">-- Select --</option>
                        @foreach ($countryOptions as $option)
                            <option value="{{ $option }}" @selected($pipeline->destination_country === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Port of destination</label>
                    <select class="form-select" data-autosave="port_of_destination">
                        <option value="">-- Select --</option>
                        @foreach ($portOptions as $option)
                            <option value="{{ $option }}" @selected($pipeline->port_of_destination === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" rows="2" data-autosave="remarks_proposal">{{ $pipeline->remarks_proposal }}</textarea>
                    <small class="autosave-indicator text-muted"></small>
                </div>

                <div class="col-12">
                    <label class="form-label">Quotation attachments</label>
                    <input type="file" class="form-control" data-attachment-category="quotation" multiple>
                    <ul id="quotation-attachment-list" class="list-unstyled mt-2 mb-0">
                        @foreach ($pipeline->attachments->where('category', 'quotation') as $file)
                            <li><a href="{{ Storage::url($file->file_path) }}" target="_blank">📎 {{ $file->original_name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        {{-- Contract / Renewal / Decline fields --}}
        @if ($showContractGroup)
        <div class="card mb-3">
            <div class="card-header fw-semibold">Contract / Renewal / Decline</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Date secured</label>
                    <input type="date" class="form-control" data-autosave="date_secured" min="{{ now()->toDateString() }}" value="{{ $pipeline->date_secured?->toDateString() }}">
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date go live</label>
                    <input type="date" class="form-control" data-autosave="date_go_live" min="{{ now()->toDateString() }}" value="{{ $pipeline->date_go_live?->toDateString() }}">
                    <small class="autosave-indicator text-muted"></small>
                </div>
                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" rows="2" data-autosave="remarks_contract">{{ $pipeline->remarks_contract }}</textarea>
                    <small class="autosave-indicator text-muted"></small>
                </div>

                <div class="col-12">
                    <label class="form-label">Contract attachments</label>
                    <input type="file" class="form-control" data-attachment-category="contract" multiple>
                    <ul id="contract-attachment-list" class="list-unstyled mt-2 mb-0">
                        @foreach ($pipeline->attachments->where('category', 'contract') as $file)
                            <li><a href="{{ Storage::url($file->file_path) }}" target="_blank">📎 {{ $file->original_name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                @if ($pipeline->forecast_revenue)
                    <div class="col-md-6">
                        <label class="form-label text-muted">Forecast Revenue for Current Year (RM)</label>
                        <input type="text" class="form-control" value="{{ $pipeline->forecast_revenue ? number_format($pipeline->forecast_revenue, 2) : '—' }}" readonly disabled>
                        <small class="text-muted">Auto-calculated from Value per Annum once Date Secured is set.</small>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <a href="{{ route('pipelines.show', $pipeline) }}" class="btn btn-secondary">Back</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const pipelineId = {{ $pipeline->id }};
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // --- Autosave individual fields ---
        function autosaveField(el) {
            const field = el.dataset.autosave;
            const value = el.value;
            const indicator = el.parentElement.querySelector('.autosave-indicator');

            fetch(`/pipelines/${pipelineId}/autosave`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ field, value }),
            })
            .then(res => res.json())
            .then(data => {
                if (!indicator) return;
                if (data.success) {
                    indicator.textContent = 'Saved ✓';
                    indicator.className = 'autosave-indicator text-success';
                } else {
                    indicator.textContent = data.error;
                    indicator.className = 'autosave-indicator text-danger';
                }
            });
        }

        document.querySelectorAll('[data-autosave]').forEach(el => {
            el.addEventListener('change', () => autosaveField(el));
            el.addEventListener('blur', () => autosaveField(el));
        });

        // Long Term auto-locks number_of_months to 12 (instant UI feedback)
        const periodSelect = document.getElementById('contract_period');
        const monthsInput = document.getElementById('number_of_months');
        function syncMonths() {
            if (periodSelect.value === 'long_term') {
                monthsInput.value = 12;
                monthsInput.readOnly = true;
                autosaveField(monthsInput);
            } else {
                monthsInput.readOnly = false;
            }
        }
        if (periodSelect) { periodSelect.addEventListener('change', syncMonths); syncMonths(); }

        // --- Attachment upload (immediate, per file) ---
        document.querySelectorAll('[data-attachment-category]').forEach(input => {
            input.addEventListener('change', function () {
                const category = this.dataset.attachmentCategory;
                Array.from(this.files).forEach(file => {
                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('category', category);

                    fetch(`/pipelines/${pipelineId}/upload-attachment`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: formData,
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const list = document.getElementById(`${category}-attachment-list`);
                            const li = document.createElement('li');
                            li.innerHTML = `<a href="${data.attachment.url}" target="_blank">📎 ${data.attachment.name}</a>`;
                            list.appendChild(li);
                        }
                    });
                });
                this.value = '';
            });
        });

        // --- Stage advance / rollback buttons ---
        document.querySelectorAll('[data-advance-stage]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const targetStage = this.dataset.advanceStage;
                if (this.dataset.confirm && !confirm(this.dataset.confirm)) return;

                const errorBox = document.getElementById('advance-errors');
                errorBox.classList.add('d-none');

                fetch(`/pipelines/${pipelineId}/advance`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ target_stage: targetStage }),
                })
                .then(res => res.json().then(data => ({ status: res.status, data })))
                .then(({ status, data }) => {
                    if (status === 200 && data.success) {
                        sessionStorage.setItem('stage_toast', data.message);
                        window.location.href = data.redirect;
                    } else {
                        errorBox.innerHTML = (data.errors || [data.error]).map(m => `<div>${m}</div>`).join('');
                        errorBox.classList.remove('d-none');
                        errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            });
        });

        // Show toast if we just arrived from a stage advance
        document.addEventListener('DOMContentLoaded', () => {
            const msg = sessionStorage.getItem('stage_toast');
            if (msg) {
                sessionStorage.removeItem('stage_toast');
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = 1080;
                toast.innerHTML = `
                    <div class="toast show align-items-center text-white bg-success border-0" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">${msg}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.position-fixed').remove()"></button>
                        </div>
                    </div>`;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            }
        });

        document.querySelectorAll('[data-chance-widget] input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const indicator = this.closest('[data-chance-widget]').nextElementSibling;

                fetch(`/pipelines/${pipelineId}/autosave`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ field: 'chance_level', value: this.value }),
                })
                .then(res => res.json())
                .then(data => {
                    indicator.textContent = data.success ? 'Saved ✓' : data.error;
                    indicator.className = data.success ? 'autosave-indicator text-success d-block mt-1' : 'autosave-indicator text-danger d-block mt-1';
                });
            });
        });
    </script>
</x-app-layout>