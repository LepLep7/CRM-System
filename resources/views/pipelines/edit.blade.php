@php
use Illuminate\Support\Facades\Storage;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="fs-4 fw-semibold">Edit pipeline — {{ $pipeline->project_name }}</h2>
    </x-slot>

    <div class="container py-4">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('pipelines.update', $pipeline) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Stage --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">Stage</div>
                <div class="card-body">
                    <select name="stage" class="form-select" required>
                        @foreach ($stageOptions as $stage)
                            <option value="{{ $stage }}" @selected(old('stage', $pipeline->stage) === $stage)>
                                {{ ucwords(str_replace('_', ' ', $stage)) }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Current stage: {{ ucwords(str_replace('_', ' ', $pipeline->stage)) }}</small>
                </div>
            </div>

            {{-- Qualify fields --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">Opportunity information (Qualify)</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Project name</label>
                        <input type="text" name="project_name" class="form-control"
                               value="{{ old('project_name', $pipeline->project_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Chance (%)</label>
                        <input type="number" name="chance_percent" min="0" max="100" class="form-control"
                               value="{{ old('chance_percent', $pipeline->chance_percent) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expected start date</label>
                        <input type="date" name="expected_start_date" class="form-control"
                               min="{{ now()->toDateString() }}"
                               value="{{ old('expected_start_date', $pipeline->expected_start_date?->toDateString()) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Customer product</label>
                        <input type="text" name="customer_product" class="form-control"
                               value="{{ old('customer_product', $pipeline->customer_product) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Scope of service</label>
                        <textarea name="scope_of_service" class="form-control" rows="2">{{ old('scope_of_service', $pipeline->scope_of_service) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Proposal / Shortlisted / Verbal fields --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">Proposal / Shortlisted / Verbal</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Value per annum (RM)</label>
                        <input type="number" step="0.01" name="value_per_annum" class="form-control"
                               value="{{ old('value_per_annum', $pipeline->value_per_annum) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contract period</label>
                        <select name="contract_period" id="contract_period" class="form-select">
                            <option value="">-- Select --</option>
                            <option value="long_term" @selected(old('contract_period', $pipeline->contract_period) === 'long_term')>Long Term</option>
                            <option value="adhoc" @selected(old('contract_period', $pipeline->contract_period) === 'adhoc')>AdHoc</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Number of months</label>
                        <input type="number" name="number_of_months" id="number_of_months" min="1" max="12" class="form-control"
                               value="{{ old('number_of_months', $pipeline->number_of_months) }}">
                        <small class="text-muted">Auto-set to 12 if Long Term. 1–11 if AdHoc.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Operating profit margin (%)</label>
                        <input type="number" step="0.01" name="operating_profit_margin" class="form-control"
                               value="{{ old('operating_profit_margin', $pipeline->operating_profit_margin) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Origin country</label>
                        <input type="text" name="origin_country" class="form-control"
                               value="{{ old('origin_country', $pipeline->origin_country) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Port of loading</label>
                        <input type="text" name="port_of_loading" class="form-control"
                               value="{{ old('port_of_loading', $pipeline->port_of_loading) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Destination country</label>
                        <input type="text" name="destination_country" class="form-control"
                               value="{{ old('destination_country', $pipeline->destination_country) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Port of destination</label>
                        <input type="text" name="port_of_destination" class="form-control"
                               value="{{ old('port_of_destination', $pipeline->port_of_destination) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks_proposal" class="form-control" rows="2">{{ old('remarks_proposal', $pipeline->remarks_proposal) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Quotation attachments</label>
                        <input type="file" name="quotation_attachments[]" class="form-control" multiple>

                        @php $quotationFiles = $pipeline->attachments->where('category', 'quotation'); @endphp
                        @if ($quotationFiles->count())
                            <ul class="list-unstyled mt-2 mb-0">
                                @foreach ($quotationFiles as $file)
                                    <li>
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($file->file_path) }}" target="_blank">
                                            📎 {{ $file->original_name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Contract / Renewal / Decline fields --}}
            <div class="card mb-3">
                <div class="card-header fw-semibold">Contract / Renewal / Decline</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Date secured</label>
                        <input type="date" name="date_secured" class="form-control"
                               min="{{ now()->toDateString() }}"
                               value="{{ old('date_secured', $pipeline->date_secured?->toDateString()) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date go live</label>
                        <input type="date" name="date_go_live" class="form-control"
                               min="{{ now()->toDateString() }}"
                               value="{{ old('date_go_live', $pipeline->date_go_live?->toDateString()) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks_contract" class="form-control" rows="2">{{ old('remarks_contract', $pipeline->remarks_contract) }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Contract attachments</label>
                        <input type="file" name="contract_attachments[]" class="form-control" multiple>

                        @php $contractFiles = $pipeline->attachments->where('category', 'contract'); @endphp
                        @if ($contractFiles->count())
                            <ul class="list-unstyled mt-2 mb-0">
                                @foreach ($contractFiles as $file)
                                    <li>
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($file->file_path) }}" target="_blank">
                                            📎 {{ $file->original_name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    @if ($pipeline->forecast_revenue)
                        <div class="col-12">
                            <small class="text-muted">Forecast revenue: RM {{ number_format($pipeline->forecast_revenue, 2) }} (auto-calculated)</small>
                        </div>
                    @endif
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="{{ route('pipelines.show', $pipeline) }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script>
        const periodSelect = document.getElementById('contract_period');
        const monthsInput = document.getElementById('number_of_months');

        function syncMonths() {
            if (periodSelect.value === 'long_term') {
                monthsInput.value = 12;
                monthsInput.readOnly = true;
            } else {
                monthsInput.readOnly = false;
            }
        }

        periodSelect.addEventListener('change', syncMonths);
        syncMonths();
    </script>
</x-app-layout>