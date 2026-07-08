<?php

namespace App\Filament\Resources\ElectionResource\Pages;

use App\Filament\Resources\ElectionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateElection extends CreateRecord
{
    protected static string $resource = ElectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
