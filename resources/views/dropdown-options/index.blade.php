<x-app-layout>
    <x-slot name="header">
        <h2 class="fs-4 fw-semibold">Manage dropdown options</h2>
    </x-slot>

    <div class="container py-4">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @foreach (['scope_of_service' => 'Scope of Service', 'country' => 'Country', 'port' => 'Port'] as $category => $label)
            <div class="card mb-4">
                <div class="card-header fw-semibold">{{ $label }}</div>
                <div class="card-body">
                    <ul class="list-group mb-3">
                        @foreach ($options[$category] ?? [] as $option)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <form method="POST" action="{{ route('dropdown-options.update', $option) }}" class="d-flex align-items-center gap-2 flex-grow-1">
                                    @csrf @method('PUT')
                                    <input type="text" name="value" value="{{ $option->value }}" class="form-control form-control-sm" style="max-width: 300px;">
                                    <div class="form-check">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked($option->is_active) id="active-{{ $option->id }}">
                                        <label class="form-check-label small" for="active-{{ $option->id }}">Active</label>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                </form>
                                <form method="POST" action="{{ route('dropdown-options.destroy', $option) }}" onsubmit="return confirm('Delete this option?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger ms-2">Delete</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>

                    <form method="POST" action="{{ route('dropdown-options.store') }}" class="d-flex gap-2">
                        @csrf
                        <input type="hidden" name="category" value="{{ $category }}">
                        <input type="text" name="value" class="form-control" placeholder="New {{ $label }} option" required>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>