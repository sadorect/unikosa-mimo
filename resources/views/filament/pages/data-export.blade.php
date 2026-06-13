<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-3">Export All Users</h3>
            <p class="text-sm text-gray-500 mb-4">Download a complete export of all member data in Excel format. Includes profile information, set/chapter membership, and registration details.</p>
            <x-filament::button wire:click="exportUsers" icon="heroicon-o-arrow-down-tray">
                Download Users Export (XLSX)
            </x-filament::button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-3">Scheduled Exports</h3>
            <p class="text-sm text-gray-500 mb-2">Email digest jobs are scheduled to run automatically:</p>
            <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                <li>Blog Digest — Every Monday at 8:00 AM</li>
                <li>Event Digest — Daily at 7:00 AM</li>
                <li>Dues Reminders — 1st of every month at 9:00 AM</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
