<?php

declare(strict_types=1);

namespace App\Domains\Admin\Livewire;

use App\Domains\Admin\Actions\RecordAuditLog;
use App\Domains\Identity\Contracts\UserModerationContract;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Exceptions\UserHasActiveItemsException;
use App\Domains\Identity\Models\User;
use App\Support\Exceptions\DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin user management (ADM-4): list, block, and activate users.
 *
 * Every action starts with Gate::authorize('admin.access').
 * Business logic lives in UserModerationContract; this component only
 * orchestrates UI, audit logging, and friendly error messages.
 */
final class UserManagement extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'role')]
    public string $roleFilter = '';

    public ?string $blockingUserId = null;

    public string $blockReason = '';

    public bool $confirmCancelItems = false;

    /** @var list<array{type: string, id: string}> */
    public array $blockingActiveItems = [];

    public ?string $activatingUserId = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        Gate::authorize('admin.access');
    }

    public function updatingSearch(): void
    {
        Gate::authorize('admin.access');

        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        Gate::authorize('admin.access');

        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        Gate::authorize('admin.access');

        $this->resetPage();
    }

    public function openBlockModal(string $userId, UserModerationContract $moderation): void
    {
        Gate::authorize('admin.access');

        $this->resetAlerts();

        /** @var User $admin */
        $admin = Auth::user();

        if ($admin->getKey() === $userId) {
            return;
        }

        $this->blockingUserId = $userId;
        $this->blockReason = '';
        $this->confirmCancelItems = false;
        $this->blockingActiveItems = $moderation->activeItemsForUsers([$userId])[$userId] ?? [];
    }

    public function closeBlockModal(): void
    {
        Gate::authorize('admin.access');

        $this->blockingUserId = null;
        $this->blockReason = '';
        $this->confirmCancelItems = false;
        $this->blockingActiveItems = [];
        $this->resetValidation();
    }

    public function blockUser(UserModerationContract $moderation, RecordAuditLog $recordAuditLog): void
    {
        Gate::authorize('admin.access');

        $this->resetAlerts();

        if ($this->blockingUserId === null) {
            return;
        }

        /** @var User $admin */
        $admin = Auth::user();

        $this->validate([
            'blockReason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $hasActiveItems = $this->blockingActiveItems !== [];

        if ($hasActiveItems && ! $this->confirmCancelItems) {
            $this->addError('confirmCancelItems', __('admin.users.confirm_cancel_required'));

            return;
        }

        try {
            $moderation->blockUser($admin, $this->blockingUserId, $this->blockReason, $hasActiveItems);

            $recordAuditLog->handle(
                actor: $admin,
                action: 'block_user',
                entity: 'user',
                entityId: $this->blockingUserId,
                meta: [
                    'reason' => $this->blockReason,
                    'cancelled_items' => array_map(
                        fn (array $item): string => $item['type'].':'.$item['id'],
                        $this->blockingActiveItems,
                    ),
                ],
            );

            $this->successMessage = __('admin.users.block_success');
            $this->closeBlockModal();
        } catch (UserHasActiveItemsException $e) {
            // Items appeared between modal open and confirm; refresh the list.
            $this->blockingActiveItems = $moderation->activeItemsForUsers([$this->blockingUserId])[$this->blockingUserId] ?? [];
            $this->confirmCancelItems = false;
            $this->errorMessage = $e->getMessage();
        } catch (DomainException $e) {
            $this->closeBlockModal();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function openActivateModal(string $userId): void
    {
        Gate::authorize('admin.access');

        $this->resetAlerts();
        $this->activatingUserId = $userId;
    }

    public function closeActivateModal(): void
    {
        Gate::authorize('admin.access');

        $this->activatingUserId = null;
    }

    public function activateUser(UserModerationContract $moderation, RecordAuditLog $recordAuditLog): void
    {
        Gate::authorize('admin.access');

        $this->resetAlerts();

        if ($this->activatingUserId === null) {
            return;
        }

        /** @var User $admin */
        $admin = Auth::user();

        try {
            $moderation->activateUser($admin, $this->activatingUserId);

            $recordAuditLog->handle(
                actor: $admin,
                action: 'activate_user',
                entity: 'user',
                entityId: $this->activatingUserId,
            );

            $this->successMessage = __('admin.users.activate_success');
            $this->closeActivateModal();
        } catch (DomainException $e) {
            $this->closeActivateModal();
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(UserModerationContract $moderation): View
    {
        Gate::authorize('admin.access');

        $query = User::query();

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->roleFilter !== '') {
            $query->where('role', $this->roleFilter);
        }

        if (trim($this->search) !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        /** @var User $admin */
        $admin = Auth::user();
        $adminId = (string) $admin->getKey();

        $ids = $users->getCollection()->map(fn (User $user): string => (string) $user->getKey())->all();
        $activeItems = $moderation->activeItemsForUsers($ids);
        $activeCounts = [];
        foreach ($activeItems as $userId => $items) {
            $activeCounts[$userId] = count($items);
        }

        return view('admin::livewire.user-management', [
            'users' => $users,
            'adminId' => $adminId,
            'activeCounts' => $activeCounts,
            'statuses' => UserStatus::cases(),
            'roles' => UserRole::cases(),
            'blockingUser' => $this->blockingUserId !== null
                ? User::query()->find($this->blockingUserId)
                : null,
            'activatingUser' => $this->activatingUserId !== null
                ? User::query()->find($this->activatingUserId)
                : null,
        ])->layout('components.layouts.app', ['title' => __('admin.users.title')]);
    }

    private function resetAlerts(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;
    }
}
