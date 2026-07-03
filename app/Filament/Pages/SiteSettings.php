<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionGuardedPage;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPermissionGuardedPage;

    protected static string|array $permission = 'manage settings';

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
        'contact_email' => null,
        'contact_whatsapp_number' => null,
        'captcha_enabled' => 'true',
        'captcha_forms' => 'login,register,forgot_password,reset_password,contact',
        'captcha_length' => '5',
        'captcha_case_sensitive' => 'false',
        'captcha_characters' => 'ABCDEFGHJKMNPQRSTUVWXYZ23456789',
        'captcha_noise_lines' => '5',
        'captcha_width' => '160',
        'captcha_height' => '50',
        'captcha_expiry_seconds' => '300',
    ];

    /** Keys whose stored value is the literal string "true"/"false", edited as a Toggle. */
    protected array $booleanKeys = ['captcha_enabled', 'captcha_case_sensitive'];

    /** Keys stored as a comma-separated string but edited as a checkbox list. */
    protected array $listKeys = ['captcha_forms'];

    public function mount(): void
    {
        $values = [];
        foreach ($this->managedKeys as $key => $default) {
            $value = Setting::get($key, $default);

            if (in_array($key, $this->booleanKeys, true)) {
                $value = $value === 'true';
            } elseif (in_array($key, $this->listKeys, true)) {
                $value = array_filter(array_map('trim', explode(',', (string) $value)));
            }

            $values[$key] = $value;
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
                            ->disk(config('filesystems.media_disk'))
                            ->directory('branding'),
                        Forms\Components\FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->image()
                            ->disk(config('filesystems.media_disk'))
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

                Forms\Components\Section::make('Contact')
                    ->description('Where messages submitted through the public contact form are delivered.')
                    ->schema([
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Notification email')
                            ->email()
                            ->helperText('Every contact form submission is emailed here. Leave blank to only store submissions for review in Contact Messages.'),
                        Forms\Components\TextInput::make('contact_whatsapp_number')
                            ->label('WhatsApp number')
                            ->tel()
                            ->helperText('Country code + number, digits only (e.g. 2348012345678). Shown as a "Chat on WhatsApp" link on the contact page.'),
                    ])->columns(2),

                Forms\Components\Section::make('Captcha')
                    ->description('A simple generated image captcha shown on public forms to deter spam and brute-force attempts.')
                    ->schema([
                        Forms\Components\Toggle::make('captcha_enabled')
                            ->label('Enable captcha')
                            ->live(),
                        Forms\Components\CheckboxList::make('captcha_forms')
                            ->label('Apply captcha to')
                            ->options([
                                'login' => 'Login',
                                'register' => 'Registration',
                                'forgot_password' => 'Forgot password',
                                'reset_password' => 'Reset password',
                                'contact' => 'Contact form',
                            ])
                            ->columns(3)
                            ->visible(fn (Forms\Get $get) => $get('captcha_enabled')),
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('captcha_length')
                                    ->label('Characters')
                                    ->numeric()->minValue(3)->maxValue(10),
                                Forms\Components\TextInput::make('captcha_noise_lines')
                                    ->label('Noise lines')
                                    ->numeric()->minValue(0)->maxValue(15),
                                Forms\Components\TextInput::make('captcha_width')
                                    ->label('Width (px)')
                                    ->numeric()->minValue(80)->maxValue(400),
                                Forms\Components\TextInput::make('captcha_height')
                                    ->label('Height (px)')
                                    ->numeric()->minValue(30)->maxValue(150),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('captcha_enabled')),
                        Forms\Components\TextInput::make('captcha_characters')
                            ->label('Allowed characters')
                            ->helperText('Ambiguous characters (0/O, 1/I/l) are excluded by default.')
                            ->visible(fn (Forms\Get $get) => $get('captcha_enabled')),
                        Forms\Components\TextInput::make('captcha_expiry_seconds')
                            ->label('Expires after (seconds)')
                            ->numeric()->minValue(30)->maxValue(3600)
                            ->visible(fn (Forms\Get $get) => $get('captcha_enabled')),
                        Forms\Components\Toggle::make('captcha_case_sensitive')
                            ->label('Case sensitive')
                            ->visible(fn (Forms\Get $get) => $get('captcha_enabled')),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $group = str_starts_with($key, 'social_') ? 'social'
                : (in_array($key, ['theme_mode', 'accent_color', 'font_family']) ? 'theme'
                : (in_array($key, ['default_currency', 'exchange_rate_source', 'exchange_rate_usd', 'exchange_rate_gbp']) ? 'currency'
                : (str_starts_with($key, 'captcha_') ? 'captcha'
                : (str_starts_with($key, 'contact_') ? 'contact'
                : 'branding'))));

            if (in_array($key, $this->booleanKeys, true)) {
                $value = $value ? 'true' : 'false';
            } elseif (in_array($key, $this->listKeys, true)) {
                $value = implode(',', $value ?: []);
            } elseif (is_array($value)) {
                $value = json_encode($value);
            }

            Setting::set($key, $value, $group);
        }

        Notification::make()->title('Settings saved')->success()->send();
    }
}
