<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlumniJobResource\Pages;
use App\Models\AlumniJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AlumniJobResource extends Resource
{
    protected static ?string $model = AlumniJob::class;
    protected static ?string $modelLabel = 'Job Listing';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\TextInput::make('company'),
            Forms\Components\Textarea::make('description')->required(),
            Forms\Components\Select::make('type')->options([
                'full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract',
                'internship' => 'Internship', 'remote' => 'Remote',
            ])->required(),
            Forms\Components\TextInput::make('location'),
            Forms\Components\Toggle::make('is_remote'),
            Forms\Components\TextInput::make('salary_min')->numeric(),
            Forms\Components\TextInput::make('salary_max')->numeric(),
            Forms\Components\TextInput::make('salary_currency'),
            Forms\Components\TextInput::make('application_url'),
            Forms\Components\TextInput::make('contact_email')->email(),
            Forms\Components\Select::make('status')->options([
                'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected',
            ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('company'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('author.name')->label('Posted By'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlumniJobs::route('/'),
            'create' => Pages\CreateAlumniJob::route('/create'),
            'edit' => Pages\EditAlumniJob::route('/{record}/edit'),
        ];
    }
}
