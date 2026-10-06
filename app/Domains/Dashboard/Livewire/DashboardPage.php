<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Livewire;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class DashboardPage extends Component
{
    public function mount(): void
    {
        $user = Auth::user();

        // If unverified inspector, redirect to verification page
        if ($user !== null && $user->role === UserRole::Inspector) {
            $profile = InspectorProfile::where('user_id', $user->id)->first();
            if (! $profile || $profile->verification_status !== VerificationStatus::Approved) {
                $this->redirectRoute('inspector.onboarding');

                return;
            }
        }
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('dashboard::dashboard', [
            'user' => $user,
            'role' => $user?->role,
        ])->layout('components.layouts.app', [
            'title' => __('dashboard.title'),
        ]);
    }
}
