<?php

namespace App\Filament\Resources\SetResource\Pages;

use App\Filament\Resources\SetResource;
use App\Support\SetCoordinatorRole;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSet extends EditRecord
{
    protected static string $resource = SetResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }

    protected function afterSave(): void
    {
        SetCoordinatorRole::sync($this->record);
    }
}
