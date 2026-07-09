@php
    $mainPath = ['new', 'qualify', 'proposal_submitted', 'shortlisted', 'verbal', 'contract', 'renewal'];
    $currentIndex = array_search($pipeline->stage, $mainPath);
    $isExited = in_array($pipeline->stage, ['loss', 'decline']);
@endphp

<div class="d-flex align-items-center mb-4 flex-wrap">
    @foreach ($mainPath as $index => $stage)
        @php
            $isDone = ! $isExited && $currentIndex !== false && $index < $currentIndex;
            $isCurrent = ! $isExited && $index === $currentIndex;
        @endphp
        <div class="d-flex flex-column align-items-center" style="min-width: 90px;">
            <div class="rounded-circle d-flex align-items-center justify-content-center
                {{ $isCurrent ? 'bg-primary text-white' : ($isDone ? 'bg-success text-white' : 'bg-light text-muted border') }}"
                 style="width: 32px; height: 32px; font-size: 14px;">
                {{ $isDone ? '✓' : $index + 1 }}
            </div>
            <small class="mt-1 text-center {{ $isCurrent ? 'fw-semibold' : '' }}">
                {{ ucwords(str_replace('_', ' ', $stage)) }}
            </small>
        </div>
        @if (! $loop->last)
            <div class="flex-grow-1" style="height: 2px; background-color: {{ $isDone ? '#198754' : '#dee2e6' }}; margin: 0 4px 20px;"></div>
        @endif
    @endforeach

    @if ($isExited)
        <div class="ms-3 d-flex flex-column align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger text-white" style="width: 32px; height: 32px; font-size: 14px;">✕</div>
            <small class="mt-1 fw-semibold text-danger">{{ ucwords($pipeline->stage) }}</small>
        </div>
    @endif
</div>