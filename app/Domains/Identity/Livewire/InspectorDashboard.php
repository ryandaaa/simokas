<?php

declare(strict_types=1);

namespace App\Domains\Identity\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class InspectorDashboard extends Component
{
    public function logout(): mixed
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render(): View
    {
        return view('identity::livewire.inspector-dashboard', [
            'user' => Auth::user(),
        ])->layout('components.layouts.app', ['title' => __('identity.dashboard.inspector_title')]);
    }
}
