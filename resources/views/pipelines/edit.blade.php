<x-app-layout>
    <x-slot name="header">
        <h2 class="fs-4 fw-semibold">Edit pipeline</h2>
    </x-slot>

    <div class="container py-4">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <form method="POST" action="{{ route('pipelines.update', $pipeline) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label">Project name</label>
                <input type="text" name="project_name" class="form-control" value="{{ old('project_name', $pipeline->project_name) }}" required>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</x-app-layout>