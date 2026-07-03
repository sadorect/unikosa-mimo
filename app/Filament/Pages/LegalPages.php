<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\HtmlSanitizer;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class LegalPages extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 50;
    protected static ?string $title = 'Legal Pages';
    protected static string $view = 'filament.pages.legal-pages';

    public ?array $data = [];

    protected array $managedKeys = [
        'legal_privacy_policy' => '',
        'legal_terms_of_service' => '',
        'contact_page_content' => '',
    ];

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $values = [];
        foreach ($this->managedKeys as $key => $default) {
            $values[$key] = Setting::get($key, $default);
        }
        $this->form->fill($values);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Privacy Policy')
                    ->schema([
                        Forms\Components\RichEditor::make('legal_privacy_policy')
                            ->hiddenLabel()
                            ->helperText('Shown at /privacy-policy. The pre-filled draft is generic boilerplate — have it reviewed before relying on it.'),
                    ]),

                Forms\Components\Section::make('Terms of Service')
                    ->schema([
                        Forms\Components\RichEditor::make('legal_terms_of_service')
                            ->hiddenLabel()
                            ->helperText('Shown at /terms-of-service. The pre-filled draft is generic boilerplate — have it reviewed before relying on it.'),
                    ]),

                Forms\Components\Section::make('Contact Page')
                    ->schema([
                        Forms\Components\RichEditor::make('contact_page_content')
                            ->hiddenLabel()
                            ->helperText('Shown at /contact.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Setting::set($key, HtmlSanitizer::clean($value), 'legal');
        }

        Notification::make()->title('Legal pages saved')->success()->send();
    }
}
