<x-filament-panels::page>
    <form wire:submit.prevent="mergeRecords">
        {{ $this->form }}

        @if ($primaryData || $duplicateData)
            <div class="mt-6 grid grid-cols-2 gap-4">
                <div class="rounded-lg border border-success-500 bg-success-50 p-4 dark:bg-success-950">
                    <h3 class="font-semibold text-success-700 dark:text-success-300">Primary (kept)</h3>
                    @if ($primaryData)
                        <p class="mt-1 text-sm">{{ $primaryData['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ $primaryData['email'] }}</p>
                        <p class="text-xs text-gray-500">Status: {{ $primaryData['status'] }}</p>
                    @else
                        <p class="text-sm text-gray-400">Not selected</p>
                    @endif
                </div>
                <div class="rounded-lg border border-danger-500 bg-danger-50 p-4 dark:bg-danger-950">
                    <h3 class="font-semibold text-danger-700 dark:text-danger-300">Duplicate (will be deleted)</h3>
                    @if ($duplicateData)
                        <p class="mt-1 text-sm">{{ $duplicateData['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ $duplicateData['email'] }}</p>
                        <p class="text-xs text-gray-500">Status: {{ $duplicateData['status'] }}</p>
                    @else
                        <p class="text-sm text-gray-400">Not selected</p>
                    @endif
                </div>
            </div>
        @endif

        @if ($primaryId && $duplicateId && $primaryId !== $duplicateId)
            <div class="mt-4 rounded-lg border border-warning-400 bg-warning-50 p-3 text-sm text-warning-800 dark:bg-warning-950 dark:text-warning-200">
                All posts, payments, jobs, and other records from the duplicate will be re-assigned to the primary. The duplicate account will be permanently deleted.
            </div>
            <div class="mt-4">
                <x-filament::button type="submit" color="danger" icon="heroicon-o-arrows-right-left">
                    Merge Records
                </x-filament::button>
            </div>
        @endif
    </form>
</x-filament-panels::page>
