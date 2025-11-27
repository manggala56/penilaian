<x-filament-widgets::widget>
    <div
        @if ($pollingInterval = $this->getPollingInterval())
            wire:poll.{{ $pollingInterval }}
        @endif
        class="fi-wi-stats-overview-stats-ctn grid gap-6 md:grid-cols-3"
    >
        @foreach ($this->getCachedStats() as $stat)
            {{ $stat }}
        @endforeach
    </div>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
