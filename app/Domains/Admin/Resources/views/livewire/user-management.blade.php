<div class="max-w-7xl mx-auto py-6 space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-text">
            {{ __('admin.users.title') }}
        </h1>
        <p class="text-sm text-muted mt-1">
            {{ __('admin.users.subtitle') }}
        </p>
    </div>

    {{-- Alerts --}}
    @if ($successMessage)
        <x-ui.alert variant="success">{{ $successMessage }}</x-ui.alert>
    @endif
    @if ($errorMessage)
        <x-ui.alert variant="danger">{{ $errorMessage }}</x-ui.alert>
    @endif

    {{-- Filter Bar --}}
    <x-ui.card class="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-ui.input
                    id="search"
                    name="search"
                    wire:model.live.debounce.300ms="search"
                    :placeholder="__('admin.users.search_placeholder')"
                />
            </div>
            <div>
                <select
                    id="statusFilter"
                    wire:model.live="statusFilter"
                    class="w-full rounded-lg border border-muted/30 bg-surface px-3 py-2 text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
                >
                    <option value="">{{ __('admin.users.all_statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select
                    id="roleFilter"
                    wire:model.live="roleFilter"
                    class="w-full rounded-lg border border-muted/30 bg-surface px-3 py-2 text-sm text-text focus:outline-none focus:ring-2 focus:ring-accent"
                >
                    <option value="">{{ __('admin.users.all_roles') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-ui.card>

    {{-- Data Display --}}
    <div wire:loading.delay.class="opacity-50" class="transition-opacity">
        @if ($users->isEmpty())
            <x-ui.card class="p-8">
                <x-ui.empty-state
                    icon="user"
                    :title="__('admin.users.empty_title')"
                    :description="__('admin.users.empty_description')"
                />
            </x-ui.card>
        @else
            {{-- Desktop Table --}}
            <div class="hidden md:block">
                <x-ui.card class="overflow-hidden">
                    <table class="min-w-full divide-y divide-muted/20 text-left text-sm">
                        <thead class="bg-surface">
                            <tr>
                                <th class="px-4 py-3 font-medium text-muted">{{ __('admin.users.name') }}</th>
                                <th class="px-4 py-3 font-medium text-muted">{{ __('admin.users.email') }}</th>
                                <th class="px-4 py-3 font-medium text-muted">{{ __('admin.users.role') }}</th>
                                <th class="px-4 py-3 font-medium text-muted">{{ __('admin.users.status') }}</th>
                                <th class="px-4 py-3 font-medium text-muted">{{ __('admin.users.active_items') }}</th>
                                <th class="px-4 py-3 font-medium text-muted text-right">{{ __('admin.users.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-muted/20">
                            @foreach ($users as $user)
                                <tr wire:key="user-{{ $user->id }}">
                                    <td class="px-4 py-3 text-text">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $user->email }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $user->role->label() }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.status-chip
                                            :label="$user->status->label()"
                                            :variant="$user->status->color()"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-muted">{{ $activeCounts[$user->id] ?? 0 }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($user->id === $adminId)
                                            <span class="text-xs text-muted">{{ __('admin.users.self_row') }}</span>
                                        @elseif ($user->status->isBlocked())
                                            <x-ui.button
                                                type="button"
                                                variant="secondary"
                                                wire:click="openActivateModal('{{ $user->id }}')"
                                            >
                                                {{ __('admin.users.activate') }}
                                            </x-ui.button>
                                        @else
                                            <x-ui.button
                                                type="button"
                                                variant="danger"
                                                wire:click="openBlockModal('{{ $user->id }}')"
                                            >
                                                {{ __('admin.users.block') }}
                                            </x-ui.button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-ui.card>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden space-y-3">
                @foreach ($users as $user)
                    <x-ui.card class="p-4" wire:key="user-card-{{ $user->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-text truncate">{{ $user->name }}</p>
                                <p class="text-xs text-muted truncate">{{ $user->email }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <x-ui.status-chip
                                        :label="$user->status->label()"
                                        :variant="$user->status->color()"
                                    />
                                    <span class="text-xs text-muted">{{ $user->role->label() }}</span>
                                </div>
                                <p class="text-xs text-muted mt-1">
                                    {{ __('admin.users.active_items') }}: {{ $activeCounts[$user->id] ?? 0 }}
                                </p>
                            </div>
                            <div class="shrink-0">
                                @if ($user->id === $adminId)
                                    <span class="text-xs text-muted">{{ __('admin.users.self_row') }}</span>
                                @elseif ($user->status->isBlocked())
                                    <x-ui.button
                                        type="button"
                                        variant="secondary"
                                        wire:click="openActivateModal('{{ $user->id }}')"
                                    >
                                        {{ __('admin.users.activate') }}
                                    </x-ui.button>
                                @else
                                    <x-ui.button
                                        type="button"
                                        variant="danger"
                                        wire:click="openBlockModal('{{ $user->id }}')"
                                    >
                                        {{ __('admin.users.block') }}
                                    </x-ui.button>
                                @endif
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $users->links('components.ui.pagination') }}
            </div>
        @endif
    </div>

    {{-- Modal Blokir --}}
    @if ($blockingUserId && $blockingUser)
        <x-ui.modal
            name="modal-block-user"
            :show="true"
            :title="__('admin.users.block_modal_title', ['name' => $blockingUser->name])"
            :description="__('admin.users.block_modal_description')"
        >
            <div class="p-2 space-y-4 text-sm text-text">
                @if ($blockingActiveItems !== [])
                    <x-ui.alert variant="warning">
                        {{ __('admin.users.has_active_items', ['count' => count($blockingActiveItems)]) }}
                    </x-ui.alert>
                    <ul class="space-y-1 text-sm">
                        @foreach ($blockingActiveItems as $item)
                            <li class="flex items-center gap-2">
                                <x-ui.icon name="warning" class="w-4 h-4 text-warning" />
                                <span>{{ __('admin.users.item_'.$item['type']) }}: <span class="font-mono text-xs">{{ $item['id'] }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                    <label class="flex items-start gap-2 cursor-pointer">
                        <x-ui.checkbox wire:model.live="confirmCancelItems" />
                        <span>{{ __('admin.users.confirm_cancel_items') }}</span>
                    </label>
                    @error('confirmCancelItems')
                        <p class="text-xs text-danger">{{ $message }}</p>
                    @enderror
                @endif
                <div>
                    <x-ui.textarea
                        id="blockReason"
                        name="blockReason"
                        wire:model="blockReason"
                        :label="__('admin.users.reason_label')"
                        :placeholder="__('admin.users.reason_placeholder')"
                        rows="3"
                    />
                    @error('blockReason')
                        <p class="text-xs text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="closeBlockModal"
                >
                    {{ __('admin.users.cancel') }}
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="danger"
                    wire:click="blockUser"
                    wire:loading.attr="disabled"
                    wire:target="blockUser"
                    :disabled="$blockingActiveItems !== [] && !$confirmCancelItems"
                >
                    <span wire:loading.remove wire:target="blockUser">{{ __('admin.users.confirm_block') }}</span>
                    <span wire:loading wire:target="blockUser">{{ __('admin.users.processing') }}</span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

    {{-- Modal Aktifkan --}}
    @if ($activatingUserId && $activatingUser)
        <x-ui.modal
            name="modal-activate-user"
            :show="true"
            :title="__('admin.users.activate_modal_title', ['name' => $activatingUser->name])"
            :description="__('admin.users.activate_modal_description')"
        >
            <x-slot:footer>
                <x-ui.button
                    type="button"
                    variant="secondary"
                    wire:click="closeActivateModal"
                >
                    {{ __('admin.users.cancel') }}
                </x-ui.button>
                <x-ui.button
                    type="button"
                    variant="primary"
                    wire:click="activateUser"
                    wire:loading.attr="disabled"
                    wire:target="activateUser"
                >
                    <span wire:loading.remove wire:target="activateUser">{{ __('admin.users.confirm_activate') }}</span>
                    <span wire:loading wire:target="activateUser">{{ __('admin.users.processing') }}</span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
