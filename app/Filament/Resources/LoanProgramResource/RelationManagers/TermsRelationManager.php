<?php

namespace App\Filament\Resources\LoanProgramResource\RelationManagers;

use App\Support\HtmlSanitizer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Versioned terms of operation. Applications snapshot the version they agreed
 * to, so correcting published terms means publishing a new version rather
 * than editing the old one — a loan disputed months later must still render
 * the text its applicant actually saw.
 */
class TermsRelationManager extends RelationManager
{
    protected static string $relationship = 'terms';

    protected static ?string $title = 'Terms of operation';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('version')
                ->required()
                ->maxLength(32)
                ->helperText('e.g. v1, v2. Unique within this fund.'),
            Forms\Components\RichEditor::make('body')
                ->required()
                ->columnSpanFull()
                ->helperText('Must state the publication penalty explicitly — it is the consent applicants and guarantors give.'),
            Forms\Components\DateTimePicker::make('published_at')
                ->label('Published at')
                ->helperText('Leave blank while drafting. Applications always bind to the most recently published version.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('version'),
                Tables\Columns\IconColumn::make('published_at')->label('Published')->boolean(),
                Tables\Columns\TextColumn::make('published_at')->label('Published at')->dateTime()->placeholder('Draft'),
                Tables\Columns\TextColumn::make('loans_count')->counts('loans')->label('Loans bound'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data) => $this->sanitize($data)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(fn (array $data) => $this->sanitize($data)),
                // A version with loans bound to it must never be deleted: the
                // loan record would lose the text it was agreed under.
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->loans()->doesntExist()),
            ]);
    }

    protected function sanitize(array $data): array
    {
        $data['body'] = HtmlSanitizer::clean($data['body'] ?? '');
        $data['created_by'] ??= Auth::id();

        return $data;
    }
}
