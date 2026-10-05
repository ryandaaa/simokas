<?php

declare(strict_types=1);

namespace App\Domains\Identity\Livewire;

use App\Domains\Identity\Actions\RegisterUser;
use App\Domains\Identity\Rules\IndonesianPhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

final class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $account_type = 'user';

    public bool $accepted_terms = false;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:60'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', new IndonesianPhoneNumber],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'account_type' => ['required', 'string', 'in:user,inspector,admin'],
            'accepted_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'email.unique' => __('identity.register.duplicate_email'),
        ];
    }

    public function register(RegisterUser $action): mixed
    {
        $this->validate();

        $user = $action->handle([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
            'role' => $this->account_type,
            'accepted_terms' => $this->accepted_terms,
        ]);

        Auth::login($user);

        return redirect()->intended(route('dashboard'));
    }

    public function render(): View
    {
        return view('identity::livewire.register')
            ->layout('components.layouts.app', ['title' => __('identity.register.title')]);
    }
}
