<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Pipeline;
use Illuminate\Http\Request;
use App\Http\Requests\UpdatePipelineRequest;
use Illuminate\Support\Facades\Storage;

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

        // Manager/Admin can pick any stage (including rollback). Others only see current + valid forward moves.
        $stageOptions = in_array($request->user()->role, ['manager', 'admin'])
            ? $allStages
            : array_values(array_unique(array_merge([$pipeline->stage], $allowedNextStages)));

        return view('pipelines.edit', compact('pipeline', 'stageOptions'));
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
}