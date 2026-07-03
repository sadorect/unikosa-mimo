<?php

namespace App\Filament\Resources\ElectionResource\Pages;

use App\Filament\Resources\ElectionResource;
use App\Services\ElectionResultService;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class ViewElectionResults extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ElectionResource::class;
    protected static string $view = 'filament.resources.election-resource.pages.view-election-results';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return "Results — {$this->record->title}";
    }

    public function getResults(): \Illuminate\Support\Collection
    {
        $service = app(ElectionResultService::class);

        return $this->record->positions()->orderBy('display_order')->get()->map(fn ($position) => [
            'position' => $position,
            ...$service->resultsFor($position),
        ]);
    }
}
