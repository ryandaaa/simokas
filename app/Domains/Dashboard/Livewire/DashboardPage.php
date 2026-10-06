<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Livewire;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Symfony\Component\HttpFoundation\Response;

final class DashboardPage extends Component
{
    public function mount(): void
    {
        $user = Auth::user();

        // Check if user is blocked
        if ($user->status === UserStatus::Blocked) {
            if (request()->wantsJson()) {
                response()->json(
                    ['message' => __('identity.login.blocked_message')],
                    Response::HTTP_FORBIDDEN,
                )->send();
                exit;
            }
            redirect(route('identity.blocked'))->send();
        }

        // If unverified inspector, redirect to verification page
        if ($user->role === UserRole::Inspector) {
            $profile = InspectorProfile::where('user_id', $user->id)->first();
            if (! $profile || $profile->verification_status !== VerificationStatus::Approved) {
                redirect(route('inspector.verification-status'))->send();
            }
        }
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('dashboard::dashboard', [
            'user' => $user,
            'role' => $user->role,
        ]);
    }
}
