<x-filament-panels::page>
    @if (!$this->importComplete)
        <form wire:submit.prevent="importMembers">
            {{ $this->form }}

            @if ($this->showPreview && $this->totalRows > 0)
                <div class="mt-6">
                    <h3 class="text-lg font-semibold mb-3">Preview</h3>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $this->totalRows }}</div>
                            <div class="text-sm text-gray-600">Total Rows</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $this->validRows }}</div>
                            <div class="text-sm text-gray-600">Valid Rows</div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 text-center">
                            <div class="text-2xl font-bold text-red-600">{{ $this->skippedRows }}</div>
                            <div class="text-sm text-gray-600">Skipped (Errors)</div>
                        </div>
                    </div>

                    @if (!empty($this->errors))
                        <div class="mb-4 max-h-64 overflow-y-auto">
                            <h4 class="font-medium text-red-600 mb-2">Errors:</h4>
                            @foreach ($this->errors as $rowIndex => $rowErrors)
                                @if (!empty($rowErrors))
                                    <div class="text-sm text-red-500 mb-1">
                                        Row {{ $rowIndex + 1 }}: {{ implode(', ', $rowErrors) }}
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <div class="overflow-x-auto max-h-96">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-gray-800">
                                <tr>
                                    <th class="px-3 py-2 text-left">#</th>
                                    @foreach ($this->headers as $header)
                                        <th class="px-3 py-2 text-left">{{ $header }}</th>
                                    @endforeach
                                    <th class="px-3 py-2 text-left">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_slice($this->rows, 0, 50) as $index => $row)
                                    <tr class="{{ !empty($this->errors[$index]) ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                        <td class="px-3 py-2">{{ $index + 1 }}</td>
                                        @foreach ($row as $cell)
                                            <td class="px-3 py-2">{{ $cell }}</td>
                                        @endforeach
                                        <td class="px-3 py-2">
                                            @if (!empty($this->errors[$index]))
                                                <span class="text-red-500">Error</span>
                                            @else
                                                <span class="text-green-500">Valid</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-filament::button type="submit" color="success" wire:loading.attr="disabled">
                        Import {{ $this->validRows }} Members
                    </x-filament::button>
                </div>
            @endif
        </form>
    @else
        <div class="text-center py-12">
            <x-filament::icon icon="heroicon-o-check-circle" class="w-16 h-16 mx-auto text-green-500 mb-4" />
            <h3 class="text-xl font-semibold mb-2">Import Complete!</h3>
            <p class="text-gray-600 mb-4">Successfully imported {{ $this->importedCount }} members.</p>
            <x-filament::button wire:click="$set('importComplete', false)" tag="a" href="{{ route('filament.admin.pages.import-members') }}">
                Import More
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>
