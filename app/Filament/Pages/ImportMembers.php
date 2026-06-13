<?php

namespace App\Filament\Pages;

use App\Models\Chapter;
use App\Models\Set;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportMembers extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Import Members';
    protected static string $view = 'filament.pages.import-members';

    public ?string $csvData = null;
    public array $headers = [];
    public array $rows = [];
    public int $totalRows = 0;
    public int $validRows = 0;
    public int $skippedRows = 0;
    public array $errors = [];
    public array $mapping = [];
    public bool $hasHeader = true;
    public bool $isProcessing = false;
    public bool $showPreview = false;
    public bool $importComplete = false;
    public int $importedCount = 0;

    protected function getFormSchema(): array
    {
        return [
            Section::make('Upload CSV/XLSX')->schema([
                FileUpload::make('file')
                    ->label('Upload File')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(10240)
                    ->directory('imports/members')
                    ->disk('local')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $this->processUploadedFile($state);
                        }
                    }),
                Toggle::make('hasHeader')
                    ->label('First row is header')
                    ->default(true)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, $set) => $this->hasHeader = $state),
            ]),

            Section::make('Column Mapping')->schema([
                Grid::make(3)->schema([
                    Select::make('mapping.name')
                        ->label('Full Name *')
                        ->options(fn () => $this->headers)
                        ->required(),
                    Select::make('mapping.email')
                        ->label('Email *')
                        ->options(fn () => $this->headers)
                        ->required(),
                    Select::make('mapping.phone')
                        ->label('Phone')
                        ->options(fn () => $this->headers),
                    Select::make('mapping.graduating_set')
                        ->label('Graduating Set')
                        ->options(fn () => $this->headers),
                    Select::make('mapping.chapter')
                        ->label('Chapter/Country')
                        ->options(fn () => $this->headers),
                    Select::make('mapping.profession')
                        ->label('Profession')
                        ->options(fn () => $this->headers),
                    Select::make('mapping.country')
                        ->label('Country')
                        ->options(fn () => $this->headers),
                    Select::make('mapping.city')
                        ->label('City')
                        ->options(fn () => $this->headers),
                    Select::make('mapping.house')
                        ->label('House')
                        ->options(fn () => $this->headers),
                    Select::make('mapping.bio')
                        ->label('Bio')
                        ->options(fn () => $this->headers),
                    Select::make('mapping.gender')
                        ->label('Gender')
                        ->options(fn () => $this->headers),
                    Select::make('mapping.date_of_birth')
                        ->label('Date of Birth')
                        ->options(fn () => $this->headers),
                ]),
            ])->visible(fn () => !empty($this->headers)),
        ];
    }

    public function processUploadedFile($state): void
    {
        $path = storage_path('app/' . $state);
        if (!file_exists($path)) return;

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls'])) {
            $this->processExcel($path);
        } else {
            $this->processCsv($path);
        }

        $this->showPreview = true;
    }

    protected function processCsv(string $path): void
    {
        $handle = fopen($path, 'r');
        if ($handle === false) return;

        $this->headers = [];
        $this->rows = [];

        if ($this->hasHeader) {
            $this->headers = fgetcsv($handle);
            $this->headers = array_map(fn ($h) => trim($h), $this->headers);
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (!empty(array_filter($row))) {
                $this->rows[] = $row;
            }
        }

        fclose($handle);

        $this->totalRows = count($this->rows);
        $this->validateRows();
    }

    protected function processExcel(string $path): void
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            $this->headers = [];
            $this->rows = [];

            if ($this->hasHeader && !empty($data)) {
                $this->headers = array_map(fn ($h) => trim((string) $h), array_shift($data));
            }

            foreach ($data as $row) {
                if (!empty(array_filter($row))) {
                    $this->rows[] = $row;
                }
            }

            $this->totalRows = count($this->rows);
            $this->validateRows();
        } catch (\Exception $e) {
            Notification::make()->title('Error reading file')->body($e->getMessage())->danger()->send();
        }
    }

    protected function validateRows(): void
    {
        $this->errors = [];
        $this->validRows = 0;
        $this->skippedRows = 0;

        foreach ($this->rows as $index => $row) {
            $rowErrors = [];
            $emailIndex = array_search('email', $this->mapping);
            $nameIndex = array_search('name', $this->mapping);
            $setIndex = array_search('graduating_set', $this->mapping);

            $email = $emailIndex !== false ? ($row[$emailIndex] ?? '') : '';
            $name = $nameIndex !== false ? ($row[$nameIndex] ?? '') : '';
            $set = $setIndex !== false ? ($row[$setIndex] ?? '') : '';

            if (empty($email)) {
                $rowErrors[] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = 'Invalid email format';
            } else {
                $exists = User::where('email', $email)->exists();
                if ($exists) {
                    $rowErrors[] = 'Email already exists';
                }
            }

            if (empty($name)) {
                $rowErrors[] = 'Name is required';
            }

            $this->errors[$index] = $rowErrors;

            if (empty($rowErrors)) {
                $this->validRows++;
            } else {
                $this->skippedRows++;
            }
        }
    }

    public function importMembers(): void
    {
        $this->isProcessing = true;
        $this->importedCount = 0;

        foreach ($this->rows as $index => $row) {
            if (!empty($this->errors[$index])) continue;

            $data = [];
            foreach ($this->mapping as $field => $headerIndex) {
                if ($headerIndex !== null && isset($row[$headerIndex])) {
                    $data[$field] = trim($row[$headerIndex]);
                }
            }

            if (empty($data['name']) || empty($data['email'])) continue;

            $set = null;
            if (!empty($data['graduating_set'])) {
                $set = Set::where('name', 'ilike', '%' . $data['graduating_set'] . '%')
                    ->orWhere('year', $data['graduating_set'])
                    ->first();
            }

            $chapter = null;
            if (!empty($data['chapter'])) {
                $chapter = Chapter::where('name', 'ilike', '%' . $data['chapter'] . '%')
                    ->orWhere('country', 'ilike', '%' . $data['chapter'] . '%')
                    ->first();
            }

            User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'graduating_set_id' => $set?->id,
                'chapter_id' => $chapter?->id,
                'profession' => $data['profession'] ?? null,
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'house' => $data['house'] ?? null,
                'bio' => $data['bio'] ?? null,
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                'status' => 'approved',
                'imported' => true,
                'account_claimed' => false,
                'imported_at' => now(),
                'password' => null,
            ]);

            $this->importedCount++;
        }

        $this->importComplete = true;
        $this->isProcessing = false;

        Notification::make()
            ->title('Import Complete')
            ->body("Successfully imported {$this->importedCount} members.")
            ->success()
            ->send();
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = ['Full Name', 'Email', 'Phone', 'Graduating Set', 'Chapter/Country', 'Profession', 'Country', 'City', 'House', 'Bio', 'Gender', 'Date of Birth'];

        return response()->streamDownload(function () use ($headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, ['John Doe', 'john@example.com', '+2348012345678', 'Set of 2005', 'Nigeria', 'Engineer', 'Nigeria', 'Lagos', 'House A', 'Bio text', 'Male', '1987-05-15']);
            fclose($handle);
        }, 'member_import_template.csv', ['Content-Type' => 'text/csv']);
    }
}
