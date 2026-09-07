<?php
namespace App\Filament\Resources\LoanProgramResource\Pages;
use App\Filament\Resources\LoanProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListLoanPrograms extends ListRecords { protected static string $resource = LoanProgramResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
