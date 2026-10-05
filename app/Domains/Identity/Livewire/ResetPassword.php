<?php

declare(strict_types=1);

namespace App\Domains\Identity\Livewire;

use App\Domains\Identity\Actions\ResetPassword as ResetPasswordAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;

final class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $errorMessage = null;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ];
    }

    public function resetPassword(ResetPasswordAction $action): mixed
    {
        $this->validate();
        $this->errorMessage = null;

        $status = $action->handle($this->email, $this->password, $this->token);

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', __('identity.reset_password.success'));

            return redirect()->route('login');
        }

        $this->errorMessage = __('identity.reset_password.invalid_token');
        $this->addError('email', __('identity.reset_password.invalid_token'));

        return null;
    }

    public function render(): View
    {
        return view('identity::livewire.reset-password')
            ->layout('components.layouts.app', ['title' => __('identity.reset_password.title')]);
    }
}
