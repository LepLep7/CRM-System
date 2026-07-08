<x-app-layout>
    <x-slot name="header">
        <h2 class="fs-4 fw-semibold">Pipelines</h2>
    </x-slot>

    <div class="container py-4">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <a href="{{ route('pipelines.create') }}" class="btn btn-primary mb-3">+ New Pipeline</a>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Customer</th>
                    <th>Salesperson</th>
                    <th>Stage</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pipelines as $pipeline)
                    <tr>
                        <td>{{ $pipeline->project_name }}</td>
                        <td>{{ $pipeline->customer->name }}</td>
                        <td>{{ $pipeline->salesperson->name }}</td>
                        <td><span class="badge bg-secondary">{{ $pipeline->stage }}</span></td>
                        <td><a href="{{ route('pipelines.show', $pipeline) }}">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No pipelines yet.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{ $pipelines->links() }}
    </div>
</x-app-layout>