<?php

namespace App\Filament\Pages\Auth;

use App\Services\CaptchaService;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public ?string $captchaId = null;
    public ?string $captchaImage = null;

    public function mount(): void
    {
        parent::mount();

        if ($this->captchaEnabled()) {
            $this->refreshCaptcha();
        }
    }

    public function refreshCaptcha(): void
    {
        $challenge = app(CaptchaService::class)->generate();
        $this->captchaId = $challenge['id'];
        $this->captchaImage = $challenge['image'];
    }

    protected function captchaEnabled(): bool
    {
        return app(CaptchaService::class)->appliesTo('login');
    }

    protected function getForms(): array
    {
        $schema = [
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getRememberFormComponent(),
        ];

        if ($this->captchaEnabled()) {
            $schema[] = Placeholder::make('captcha_image')
                ->label('Security check')
                ->content(fn () => new HtmlString(
                    '<img src="' . e($this->captchaImage) . '" alt="Captcha" style="border-radius:0.5rem;border:1px solid #e5e7eb;">'
                ));
            $schema[] = TextInput::make('captcha_answer')
                ->label('Enter the code above')
                ->autocomplete('off')
                ->required();
            $schema[] = Actions::make([
                FormAction::make('refresh_captcha')
                    ->label('Get a new code')
                    ->link()
                    ->action('refreshCaptcha'),
            ]);
        }

        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema($schema)
                    ->statePath('data'),
            ),
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        if ($this->captchaEnabled()) {
            $data = $this->form->getState();

            if (! app(CaptchaService::class)->verify($this->captchaId, $data['captcha_answer'] ?? null)) {
                $this->refreshCaptcha();

                throw ValidationException::withMessages([
                    'data.captcha_answer' => 'The captcha answer is incorrect. Please try again.',
                ]);
            }
        }

        return parent::authenticate();
    }
}
