<?php
namespace App\Filament\Resources\DueResource\Pages;
use App\Filament\Resources\DueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListDues extends ListRecords { protected static string $resource = DueResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
