<?php
namespace App\Filament\Resources\AlumniJobResource\Pages;
use App\Filament\Resources\AlumniJobResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditAlumniJob extends EditRecord
{
    protected static string $resource = AlumniJobResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
