<?php

declare(strict_types=1);

namespace App\Domains\Identity\Livewire;

use App\Domains\Identity\Actions\AuthenticateUser;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function login(AuthenticateUser $action): mixed
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            abort(429, __('auth.throttle', ['seconds' => $seconds]));
        }

        try {
            $action->handle($this->email, $this->password, $this->remember);
        } catch (ValidationException $e) {
            RateLimiter::hit($throttleKey, 60);

            throw $e;
        }

        RateLimiter::clear($throttleKey);

        /** @var User|null $user */
        $user = Auth::user();

        $targetDashboard = match ($user?->role) {
            UserRole::Admin => route('dashboard.admin'),
            UserRole::Inspector => route('dashboard.inspector'),
            default => route('dashboard.user'),
        };

        return redirect()->intended($targetDashboard);
    }

    protected function throttleKey(): string
    {
        return 'login:'.Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render(): View
    {
        return view('identity::livewire.login')
            ->layout('components.layouts.app', ['title' => __('identity.login.title')]);
    }
}
