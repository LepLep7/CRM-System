<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Pipeline;
use Illuminate\Http\Request;

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

    public function edit(Pipeline $pipeline)
    {
        $this->authorize('update', $pipeline);

        return view('pipelines.edit', compact('pipeline'));
    }

    public function update(Request $request, Pipeline $pipeline)
    {
        $this->authorize('update', $pipeline);

        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
        ]);

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