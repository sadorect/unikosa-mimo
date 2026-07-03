<x-filament-panels::page>
    <div class="space-y-6">
        @if ($record->status !== 'results_published')
            <div class="rounded-lg bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/30 px-4 py-3 text-sm text-warning-700 dark:text-warning-400">
                This is a private preview for admins/moderators only — results are not yet published to members.
            </div>
        @endif

        @foreach ($this->getResults() as $result)
            <x-filament::section>
                <x-slot name="heading">{{ $result['position']->title }}</x-slot>
                <x-slot name="description">
                    {{ $result['total'] }} ballots decided this position ({{ $result['abstentions'] }} abstained)
                </x-slot>

                <div class="space-y-3">
                    @forelse ($result['tallies'] as $row)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium flex items-center gap-2">
                                    {{ $row['candidate']?->user?->name ?? 'Unknown candidate' }}
                                    @if ($result['winner'] && $result['winner']['candidate']?->id === $row['candidate']?->id)
                                        <x-filament::badge color="success">Winner</x-filament::badge>
                                    @endif
                                </span>
                                <span class="text-gray-500">{{ $row['votes'] }} votes ({{ $row['percent'] }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-2 rounded-full bg-primary-500" style="width: {{ $row['percent'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No votes cast for this position yet.</p>
                    @endforelse

                    @if (! $result['winner'] && $result['tallies']->isNotEmpty())
                        <p class="text-sm text-danger-600 dark:text-danger-400 font-medium">
                            No winner — threshold not met.
                        </p>
                    @endif
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
