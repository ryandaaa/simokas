<?php

declare(strict_types=1);

namespace App\Domains\Identity\Livewire;

use App\Domains\Identity\Actions\ChangePassword;
use App\Domains\Identity\Actions\UpdateProfile;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Rules\IndonesianPhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class AccountSettings extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
    }

    public function updateProfile(UpdateProfile $action): void
    {
        $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:60'],
            'phone' => ['required', 'string', new IndonesianPhoneNumber],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $action->handle($user, [
            'name' => $this->name,
            'phone' => $this->phone,
        ]);

        session()->flash('status_profile', __('identity.account.profile_updated'));
    }

    public function changePassword(ChangePassword $action): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $throttleKey = $this->passwordThrottleKey($user);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            abort(429, __('auth.throttle', ['seconds' => $seconds]));
        }

        try {
            $action->handle($user, $this->current_password, $this->password);
        } catch (ValidationException $e) {
            RateLimiter::hit($throttleKey, 60);

            throw $e;
        }

        RateLimiter::clear($throttleKey);

        if (session()->isStarted()) {
            session()->regenerate();
        }

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('status_password', __('identity.account.password_changed'));
    }

    protected function passwordThrottleKey(User $user): string
    {
        return 'change-password:'.Str::transliterate($user->id.'|'.request()->ip());
    }

    public function render(): View
    {
        return view('identity::livewire.account-settings')
            ->layout('components.layouts.app', ['title' => __('identity.account.title')]);
    }
}
