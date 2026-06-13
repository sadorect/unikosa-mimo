<x-filament-panels::page>
    <div class="mb-6 flex gap-4 items-end">
        <x-filament::input type="date" wire:model="dateFrom" label="From" />
        <x-filament::input type="date" wire:model="dateTo" label="To" />
        <x-filament::button wire:click="loadSummary">Filter</x-filament::button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
            <div class="text-sm text-gray-500">Total Raised</div>
            <div class="text-2xl font-bold text-amber-500">{{ number_format($this->summary['total_raised'] / 100, 2) }}</div>
            <div class="text-xs text-gray-400">{{ $this->summary['total_transactions'] }} transactions</div>
        </div>
        @foreach($this->summary['by_method'] ?? [] as $method)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="text-sm text-gray-500 capitalize">{{ $method['payment_method'] }}</div>
                <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($method['total'] / 100, 2) }}</div>
                <div class="text-xs text-gray-400">{{ $method['count'] }} transactions</div>
            </div>
        @endforeach
    </div>

    <h3 class="text-lg font-semibold mb-4">Campaign Progress</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($this->summary['campaigns'] ?? [] as $campaign)
            @php
                $pct = $campaign['target_amount'] > 0 ? min(100, round(($campaign['raised_amount'] / $campaign['target_amount']) * 100)) : 0;
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
                <div class="font-medium">{{ $campaign['title'] }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ number_format($campaign['raised_amount'] / 100) }} / {{ number_format($campaign['target_amount'] / 100) }}</div>
                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 mt-2">
                    <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
