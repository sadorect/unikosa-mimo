<?php
namespace App\Filament\Resources\BusinessListingResource\Pages;
use App\Filament\Resources\BusinessListingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListBusinessListings extends ListRecords { protected static string $resource = BusinessListingResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
