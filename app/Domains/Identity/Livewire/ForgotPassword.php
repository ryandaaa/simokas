<?php

declare(strict_types=1);

namespace App\Domains\Identity\Livewire;

use App\Domains\Identity\Actions\SendPasswordResetLink;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

final class ForgotPassword extends Component
{
    public string $email = '';

    public ?string $status = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    public function sendResetLink(SendPasswordResetLink $action): void
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            abort(429, __('passwords.throttled').' '.__('auth.throttle', ['seconds' => $seconds]));
        }

        $action->handle($this->email);

        RateLimiter::hit($throttleKey, 60);

        $this->status = __('identity.forgot_password.success_notice');
    }

    protected function throttleKey(): string
    {
        return 'forgot-password:'.Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render(): View
    {
        return view('identity::livewire.forgot-password')
            ->layout('components.layouts.app', ['title' => __('identity.forgot_password.title')]);
    }
}
