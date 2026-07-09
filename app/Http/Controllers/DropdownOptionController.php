<?php

namespace App\Http\Controllers;

use App\Models\DropdownOption;
use Illuminate\Http\Request;

class DropdownOptionController extends Controller
{
    protected function ensureAllowed(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['manager', 'hod', 'admin']), 403);
    }

    public function index(Request $request)
    {
        $this->ensureAllowed($request);

        $options = DropdownOption::orderBy('category')->orderBy('sort_order')->get()->groupBy('category');

        return view('dropdown-options.index', compact('options'));
    }

    public function store(Request $request)
    {
        $this->ensureAllowed($request);

        $validated = $request->validate([
            'category' => 'required|in:scope_of_service,country,port',
            'value' => 'required|string|max:255',
        ]);

        DropdownOption::create($validated);

        return back()->with('success', 'Option added.');
    }

    public function update(Request $request, DropdownOption $dropdownOption)
    {
        $this->ensureAllowed($request);

        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $dropdownOption->update([
            'value' => $validated['value'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Option updated.');
    }

    public function destroy(Request $request, DropdownOption $dropdownOption)
    {
        $this->ensureAllowed($request);

        $dropdownOption->delete();

        return back()->with('success', 'Option deleted.');
    }
}