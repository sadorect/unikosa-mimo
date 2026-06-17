<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 49;
    protected static ?string $title = 'Site Settings';
    protected static string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    /** Keys managed by this panel and their default values. */
    protected array $managedKeys = [
        'site_name' => 'UNIKOSA',
        'logo_path' => null,
        'favicon_path' => null,
        'theme_mode' => 'light',
        'accent_color' => '#F59E0B',
        'font_family' => 'Inter',
        'default_currency' => 'NGN',
        'exchange_rate_source' => 'manual',
        'exchange_rate_usd' => null,
        'exchange_rate_gbp' => null,
        'social_facebook' => null,
        'social_twitter' => null,
        'social_instagram' => null,
        'social_linkedin' => null,
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
                Forms\Components\Section::make('Branding')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->required()
                            ->helperText('Displayed across the site; change if the association rebrands away from "UNIKOSA".'),
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('r2')
                            ->directory('branding'),
                        Forms\Components\FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->image()
                            ->disk('r2')
                            ->directory('branding'),
                    ])->columns(3),

                Forms\Components\Section::make('Theme')
                    ->schema([
                        Forms\Components\Select::make('theme_mode')
                            ->label('Default mode')
                            ->options(['light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto (system)'])
                            ->required(),
                        Forms\Components\ColorPicker::make('accent_color')
                            ->label('Accent color')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Typography')
                    ->schema([
                        Forms\Components\Select::make('font_family')
                            ->label('Site font')
                            ->options([
                                'Inter' => 'Inter',
                                'Poppins' => 'Poppins',
                                'Roboto' => 'Roboto',
                                'Lora' => 'Lora',
                                'Merriweather' => 'Merriweather',
                                'Nunito' => 'Nunito',
                            ])
                            ->required(),
                    ]),

                Forms\Components\Section::make('Currency')
                    ->schema([
                        Forms\Components\Select::make('default_currency')
                            ->options(['NGN' => 'NGN', 'USD' => 'USD', 'GBP' => 'GBP', 'EUR' => 'EUR'])
                            ->required(),
                        Forms\Components\Select::make('exchange_rate_source')
                            ->label('Exchange rate source')
                            ->options(['manual' => 'Manual', 'api' => 'API-based'])
                            ->live()
                            ->required(),
                        Forms\Components\TextInput::make('exchange_rate_usd')
                            ->label('NGN per 1 USD')
                            ->numeric()
                            ->visible(fn (Forms\Get $get) => $get('exchange_rate_source') === 'manual'),
                        Forms\Components\TextInput::make('exchange_rate_gbp')
                            ->label('NGN per 1 GBP')
                            ->numeric()
                            ->visible(fn (Forms\Get $get) => $get('exchange_rate_source') === 'manual'),
                    ])->columns(2),

                Forms\Components\Section::make('Social media links (footer)')
                    ->schema([
                        Forms\Components\TextInput::make('social_facebook')->label('Facebook')->url(),
                        Forms\Components\TextInput::make('social_twitter')->label('X / Twitter')->url(),
                        Forms\Components\TextInput::make('social_instagram')->label('Instagram')->url(),
                        Forms\Components\TextInput::make('social_linkedin')->label('LinkedIn')->url(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $group = str_starts_with($key, 'social_') ? 'social'
                : (in_array($key, ['theme_mode', 'accent_color', 'font_family']) ? 'theme'
                : (in_array($key, ['default_currency', 'exchange_rate_source', 'exchange_rate_usd', 'exchange_rate_gbp']) ? 'currency'
                : 'branding'));

            Setting::set($key, is_array($value) ? json_encode($value) : $value, $group);
        }

        Notification::make()->title('Settings saved')->success()->send();
    }
}
