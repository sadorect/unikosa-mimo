<?php
namespace App\Filament\Resources\LoanProgramResource\Pages;
use App\Filament\Resources\LoanProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditLoanProgram extends EditRecord { protected static string $resource = LoanProgramResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
