<x-app-layout>
    <x-slot name="header">
        <h2 class="fs-4 fw-semibold">{{ $pipeline->project_name }}</h2>
    </x-slot>

    <div class="container py-4">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <p><strong>Customer:</strong> {{ $pipeline->customer->name }}</p>
        <p><strong>Salesperson:</strong> {{ $pipeline->salesperson->name }}</p>
        <p><strong>Stage:</strong> <span class="badge bg-secondary">{{ $pipeline->stage }}</span></p>

        @can('update', $pipeline)
            <a href="{{ route('pipelines.edit', $pipeline) }}" class="btn btn-sm btn-warning">Edit</a>
        @endcan

        @can('delete', $pipeline)
            <form method="POST" action="{{ route('pipelines.destroy', $pipeline) }}" class="d-inline">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this pipeline?')">Delete</button>
            </form>
        @endcan
    </div>
</x-app-layout>