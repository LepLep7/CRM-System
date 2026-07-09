<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Pipeline;
use Illuminate\Http\Request;
use App\Http\Requests\UpdatePipelineRequest;
use Illuminate\Support\Facades\Storage;
use App\Services\PipelineStageService;

class PipelineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Pipeline::with(['customer', 'salesperson']);

        if ($user->role === 'salesperson') {
            $query->where('salesperson_id', $user->id);
        } elseif ($user->role === 'manager') {
            $query->whereHas('salesperson', fn ($q) => $q->where('team_id', $user->team_id));
        }
        // hod & admin -> no filter, nampak semua

        $pipelines = $query->latest()->paginate(15);

        return view('pipelines.index', compact('pipelines'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('pipelines.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'new_customer_name' => 'required_without:customer_id|string|max:255',
            'new_customer_email' => 'nullable|email',
            'new_customer_phone' => 'nullable|string|max:20',
            'project_name' => 'required|string|max:255',
        ]);

        $customer = $validated['customer_id'] ?? null
            ? Customer::findOrFail($validated['customer_id'])
            : Customer::create([
                'name' => $validated['new_customer_name'],
                'email' => $validated['new_customer_email'] ?? null,
                'phone' => $validated['new_customer_phone'] ?? null,
            ]);

        $pipeline = Pipeline::create([
            'customer_id' => $customer->id,
            'salesperson_id' => $request->user()->id,
            'department_id' => $request->user()->department_id,
            'stage' => 'new',
            'project_name' => $validated['project_name'],
        ]);

        return redirect()->route('pipelines.show', $pipeline)
            ->with('success', 'Pipeline created.');
    }

    public function show(Pipeline $pipeline)
    {
        $this->authorize('view', $pipeline);

        $pipeline->load(['customer', 'salesperson', 'stageHistories.changedBy', 'attachments']);

        return view('pipelines.show', compact('pipeline'));
    }

    public function edit(Request $request, Pipeline $pipeline)
    {
        $this->authorize('update', $pipeline);

        $updateRequest = new \App\Http\Requests\UpdatePipelineRequest();
        $allowedNextStages = $updateRequest->getAllowedTransitions()[$pipeline->stage] ?? [];

        $allStages = [
            'new', 'qualify', 'proposal_submitted', 'shortlisted',
            'verbal', 'contract', 'loss', 'renewal', 'decline',
        ];

        $stageOptions = in_array($request->user()->role, ['manager', 'admin'])
            ? $allStages
            : array_values(array_unique(array_merge([$pipeline->stage], $allowedNextStages)));

        $mainPath = ['new', 'qualify', 'proposal_submitted', 'shortlisted', 'verbal', 'contract', 'renewal'];
        $currentIndex = array_search($pipeline->stage, $mainPath);
        $showProposalGroup = $currentIndex === false || $currentIndex >= array_search('qualify', $mainPath);
        $showContractGroup = $currentIndex === false || $currentIndex >= array_search('verbal', $mainPath);

        $scopeOptions = \App\Models\DropdownOption::category('scope_of_service')->active()->pluck('value');
        $countryOptions = \App\Models\DropdownOption::category('country')->active()->pluck('value');
        $portOptions = \App\Models\DropdownOption::category('port')->active()->pluck('value');

        return view('pipelines.edit', compact(
            'pipeline', 'stageOptions', 'showProposalGroup', 'showContractGroup',
            'scopeOptions', 'countryOptions', 'portOptions'
        ));
    }

    public function update(UpdatePipelineRequest $request, Pipeline $pipeline)
    {
        $validated = $request->validated();

        // Long Term contract period always locks to 12 months (server-side, not user input)
        if (($validated['contract_period'] ?? null) === 'long_term') {
            $validated['number_of_months'] = 12;
        }

        // Stamp date_funnel automatically the first time pipeline enters Qualify
        if ($validated['stage'] === 'qualify' && ! $pipeline->date_funnel) {
            $validated['date_funnel'] = now();
        }

        // Auto-calculate forecast revenue once date_secured is set
        if (! empty($validated['date_secured']) && ! empty($validated['value_per_annum'])) {
            $validated['forecast_revenue'] = $validated['value_per_annum'];
        }

        // Lock the record once it reaches Contract stage
        if ($validated['stage'] === 'contract') {
            $validated['is_locked'] = true;
        }

        // Record stage history if the stage actually changed
        if ($pipeline->stage !== $validated['stage']) {
            $isForward = $request->isForwardTransition($pipeline->stage, $validated['stage']);

            $pipeline->stageHistories()->create([
                'changed_by' => $request->user()->id,
                'from_stage' => $pipeline->stage,
                'to_stage' => $validated['stage'],
                'is_rollback' => ! $isForward,
                'approved_by' => ! $isForward ? $request->user()->id : null,
            ]);
        }

        // Save uploaded quotation attachments
        foreach ($request->file('quotation_attachments', []) as $file) {
            $path = $file->store('attachments/quotation', 'public');
            $pipeline->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'category' => 'quotation',
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        // Save uploaded contract attachments
        foreach ($request->file('contract_attachments', []) as $file) {
            $path = $file->store('attachments/contract', 'public');
            $pipeline->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'category' => 'contract',
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        // Remove file-array keys before mass-updating the pipeline (not real columns)
        unset($validated['quotation_attachments'], $validated['contract_attachments']);

        $pipeline->update($validated);

        return redirect()->route('pipelines.show', $pipeline)
            ->with('success', 'Pipeline updated.');
    }

    public function destroy(Pipeline $pipeline)
    {
        $this->authorize('delete', $pipeline);

        $pipeline->delete();

        return redirect()->route('pipelines.index')
            ->with('success', 'Pipeline deleted.');
    }

    protected array $autosaveFields = [
        'project_name' => 'string', 'chance_level' => 'chance_level',
        'expected_start_date' => 'date', 'scope_of_service' => 'dropdown:scope_of_service',
        'customer_product' => 'string', 'value_per_annum' => 'decimal',
        'contract_period' => 'string', 'number_of_months' => 'integer',
        'origin_country' => 'dropdown:country', 'port_of_loading' => 'dropdown:port',
        'destination_country' => 'dropdown:country', 'port_of_destination' => 'dropdown:port',
        'operating_profit_margin' => 'decimal', 'remarks_proposal' => 'string',
        'date_secured' => 'date', 'date_go_live' => 'date', 'remarks_contract' => 'string',
    ];

    protected array $chanceLevelMap = ['low' => 25, 'medium' => 50, 'high' => 75];

    public function autosaveField(Request $request, Pipeline $pipeline)
    {
        $this->authorize('update', $pipeline);

        if ($pipeline->is_locked && ! in_array($request->user()->role, ['manager', 'admin'])) {
            return response()->json(['error' => 'This pipeline is locked.'], 403);
        }

        $field = $request->input('field');
        $value = $request->input('value');

        if (! array_key_exists($field, $this->autosaveFields)) {
            return response()->json(['error' => 'Invalid field.'], 422);
        }

        $type = $this->autosaveFields[$field];

        if ($type === 'chance_level') {
            if (! array_key_exists($value, $this->chanceLevelMap) && $value !== '') {
                return response()->json(['error' => 'Invalid chance level.'], 422);
            }

            $pipeline->update([
                'chance_level' => $value === '' ? null : $value,
                'chance_percent' => $value === '' ? null : $this->chanceLevelMap[$value],
            ]);

            return response()->json(['success' => true]);
        }

        if (str_starts_with($type, 'dropdown:')) {
            $category = substr($type, 9);

            if ($value !== '' && ! \App\Models\DropdownOption::category($category)->active()->where('value', $value)->exists()) {
                return response()->json(['error' => 'Invalid option selected.'], 422);
            }

            $pipeline->update([$field => $value === '' ? null : $value]);

            return response()->json(['success' => true]);
        }

        $rules = match ($type) {
            'integer' => 'nullable|integer',
            'decimal' => 'nullable|numeric',
            'date' => 'nullable|date|after_or_equal:today',
            default => 'nullable|string|max:2000',
        };

        $validator = validator(['value' => $value], ['value' => $rules]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first('value')], 422);
        }

        $pipeline->update([$field => $value === '' ? null : $value]);

        if ($field === 'contract_period' && $value === 'long_term') {
            $pipeline->update(['number_of_months' => 12]);
        }

        return response()->json(['success' => true]);
    }

    public function uploadAttachment(Request $request, Pipeline $pipeline)
    {
        $this->authorize('update', $pipeline);

        $request->validate([
            'category' => 'required|in:quotation,contract',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        $path = $request->file('file')->store('attachments/' . $request->category, 'public');

        $attachment = $pipeline->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'category' => $request->category,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $request->file('file')->getClientMimeType(),
            'size' => $request->file('file')->getSize(),
        ]);

        return response()->json([
            'success' => true,
            'attachment' => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'url' => Storage::url($attachment->file_path),
            ],
        ]);
    }

    public function advanceStage(Request $request, Pipeline $pipeline, PipelineStageService $stageService)
    {
        $this->authorize('update', $pipeline);

        $request->validate([
            'target_stage' => 'required|in:' . implode(',', array_keys($stageService->allowedTransitions)),
        ]);

        $targetStage = $request->input('target_stage');
        $isForward = $stageService->isForwardTransition($pipeline->stage, $targetStage);

        if (! $isForward && ! in_array($request->user()->role, ['manager', 'admin'])) {
            return response()->json(['errors' => ['Only a Manager or Admin can move a pipeline back to a previous stage.']], 403);
        }

        $errors = $stageService->missingFieldsForStage($pipeline, $targetStage);
        if (! empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $updates = ['stage' => $targetStage];

        if ($targetStage === 'qualify' && ! $pipeline->date_funnel) {
            $updates['date_funnel'] = now();
        }
        if (! empty($pipeline->date_secured) && ! empty($pipeline->value_per_annum)) {
            $updates['forecast_revenue'] = $pipeline->value_per_annum;
        }
        if ($targetStage === 'contract') {
            $updates['is_locked'] = true;
        }

        $pipeline->stageHistories()->create([
            'changed_by' => $request->user()->id,
            'from_stage' => $pipeline->stage,
            'to_stage' => $targetStage,
            'is_rollback' => ! $isForward,
            'approved_by' => ! $isForward ? $request->user()->id : null,
        ]);

        $pipeline->update($updates);

        return response()->json([
            'success' => true,
            'redirect' => route('pipelines.edit', $pipeline),
            'message' => 'Stage advanced to ' . ucwords(str_replace('_', ' ', $targetStage)) . '.',
        ]);
    }
}