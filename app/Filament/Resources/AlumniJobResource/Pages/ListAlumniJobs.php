<?php
namespace App\Filament\Resources\AlumniJobResource\Pages;
use App\Filament\Resources\AlumniJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListAlumniJobs extends ListRecords
{
    protected static string $resource = AlumniJobResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
