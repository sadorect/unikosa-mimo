<?php

namespace App\Filament\Resources\SetResource\Pages;

use App\Filament\Resources\SetResource;
use App\Support\SetCoordinatorRole;
use Filament\Resources\Pages\CreateRecord;

class CreateSet extends CreateRecord
{
    protected static string $resource = SetResource::class;

    protected function afterCreate(): void
    {
        SetCoordinatorRole::sync($this->record);
    }
}
