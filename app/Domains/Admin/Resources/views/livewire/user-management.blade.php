<div class="ds-sans max-w-7xl mx-auto py-6 space-y-8 pb-12">
    {{-- Header --}}
    <div class="space-y-2">
        <h1 class="text-3xl font-extrabold text-ds-ink tracking-tight">
            {{ __('admin.users.title') }}
        </h1>
        <p class="text-sm text-ds-muted">
            {{ __('admin.users.subtitle') }}
        </p>
    </div>

    {{-- Alerts --}}
    @if ($successMessage)
        <div role="status" class="p-4 rounded-2xl border border-ds-green-soft bg-ds-green-soft/40 text-xs font-semibold text-ds-ink">
            {{ $successMessage }}
        </div>
    @endif
    @if ($errorMessage)
        <div role="alert" class="p-4 rounded-2xl border border-ds-red-soft bg-ds-red-soft/40 text-xs font-semibold text-ds-ink">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="relative">
                <i aria-hidden="true" class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-ds-faint text-sm"></i>
                <input
                    id="search"
                    name="search"
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('admin.users.search_placeholder') }}"
                    aria-label="{{ __('admin.users.search_placeholder') }}"
                    class="w-full h-11 pl-11 pr-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
                />
            </div>
            <div>
                <select
                    id="statusFilter"
                    wire:model.live="statusFilter"
                    aria-label="{{ __('admin.users.status') }}"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
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
                    aria-label="{{ __('admin.users.role') }}"
                    class="w-full h-11 px-4 rounded-xl text-sm bg-white text-ds-ink border border-ds-line focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"
                >
                    <option value="">{{ __('admin.users.all_roles') }}</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    @php
        $chip = function (?string $variant): string {
            return match ($variant) {
                'success' => 'bg-ds-green-soft text-ds-green-ink',
                'warning' => 'bg-ds-amber-soft text-ds-amber-ink',
                'danger' => 'bg-ds-red-soft text-ds-red-ink',
                'primary' => 'bg-ds-blue-soft text-ds-blue-ink',
                default => 'bg-ds-soft text-ds-muted',
            };
        };
    @endphp

    {{-- Data Display --}}
    <div wire:loading.delay.class="opacity-50" class="transition-opacity">
        @if ($users->isEmpty())
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                    <i aria-hidden="true" class="fa-solid fa-user"></i>
                </div>
                <h3 class="text-lg font-extrabold text-ds-ink">{{ __('admin.users.empty_title') }}</h3>
                <p class="text-xs text-ds-muted mt-2">{{ __('admin.users.empty_description') }}</p>
            </div>
        @else
            {{-- Desktop Table --}}
            <div class="hidden md:block">
                <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs overflow-x-auto">
                    <table class="min-w-full divide-y divide-ds-line/60 text-left text-sm">
                        <thead class="bg-ds-canvas/80 text-ds-muted">
                            <tr>
                                <th class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.users.name') }}</th>
                                <th class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.users.email') }}</th>
                                <th class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.users.role') }}</th>
                                <th class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.users.status') }}</th>
                                <th class="px-4 py-3 font-bold text-xs uppercase tracking-wider">{{ __('admin.users.active_items') }}</th>
                                <th class="px-4 py-3 font-bold text-xs uppercase tracking-wider text-right">{{ __('admin.users.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ds-line/60">
                            @foreach ($users as $user)
                                <tr wire:key="user-{{ $user->id }}" class="hover:bg-ds-canvas/60 transition-colors">
                                    <td class="px-4 py-3 font-bold text-ds-ink">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-ds-muted">{{ $user->email }}</td>
                                    <td class="px-4 py-3 text-ds-muted">{{ $user->role->label() }}</td>
                                    <td class="px-4 py-3">
                                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($user->status->color()) }}">
                                            {{ $user->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-ds-muted">{{ $activeCounts[$user->id] ?? 0 }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($user->id === $adminId)
                                            <span class="text-xs text-ds-muted">{{ __('admin.users.self_row') }}</span>
                                        @elseif ($user->status->isBlocked())
                                            <button
                                                type="button"
                                                wire:click="openActivateModal('{{ $user->id }}')"
                                                class="inline-flex items-center justify-center px-4 py-1.5 min-h-[44px] text-xs font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs"
                                            >
                                                {{ __('admin.users.activate') }}
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="openBlockModal('{{ $user->id }}')"
                                                class="inline-flex items-center justify-center px-4 py-1.5 min-h-[44px] text-xs font-bold bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 rounded-xl transition-all shadow-xs"
                                            >
                                                {{ __('admin.users.block') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden space-y-3">
                @foreach ($users as $user)
                    <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-4" wire:key="user-card-{{ $user->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-extrabold text-ds-ink truncate">{{ $user->name }}</p>
                                <p class="text-xs text-ds-muted truncate">{{ $user->email }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-[10px] px-2 py-0.5 rounded-md font-bold {{ $chip($user->status->color()) }}">
                                        {{ $user->status->label() }}
                                    </span>
                                    <span class="text-xs text-ds-muted">{{ $user->role->label() }}</span>
                                </div>
                                <p class="text-xs text-ds-muted mt-1">
                                    {{ __('admin.users.active_items') }}: {{ $activeCounts[$user->id] ?? 0 }}
                                </p>
                            </div>
                            <div class="shrink-0">
                                @if ($user->id === $adminId)
                                    <span class="text-xs text-ds-muted">{{ __('admin.users.self_row') }}</span>
                                @elseif ($user->status->isBlocked())
                                    <button
                                        type="button"
                                        wire:click="openActivateModal('{{ $user->id }}')"
                                        class="inline-flex items-center justify-center px-4 py-1.5 min-h-[44px] text-xs font-bold bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink rounded-xl transition-all shadow-xs"
                                    >
                                        {{ __('admin.users.activate') }}
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="openBlockModal('{{ $user->id }}')"
                                        class="inline-flex items-center justify-center px-4 py-1.5 min-h-[44px] text-xs font-bold bg-white border border-ds-red-soft text-ds-danger hover:bg-ds-red-soft/30 rounded-xl transition-all shadow-xs"
                                    >
                                        {{ __('admin.users.block') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
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
                    <div role="alert" class="p-4 rounded-2xl border border-ds-amber-soft bg-ds-amber-soft/40 text-xs font-semibold text-ds-ink">
                        {{ __('admin.users.has_active_items', ['count' => count($blockingActiveItems)]) }}
                    </div>
                    <ul class="space-y-1 text-sm">
                        @foreach ($blockingActiveItems as $item)
                            <li class="flex items-center gap-2">
                                <i aria-hidden="true" class="fa-solid fa-triangle-exclamation text-ds-amber-ink"></i>
                                <span>{{ __('admin.users.item_'.$item['type']) }}: <span class="font-mono text-xs">{{ $item['id'] }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                    <label class="flex items-start gap-2 cursor-pointer text-sm min-h-[44px]">
                        <input type="checkbox" wire:model.live="confirmCancelItems"
                            class="mt-0.5 w-4 h-4 rounded border-ds-line text-ds-blue focus:ring-ds-blue/30" />
                        <span>{{ __('admin.users.confirm_cancel_items') }}</span>
                    </label>
                    @error('confirmCancelItems')
                        <p class="text-xs text-ds-danger" role="alert">{{ $message }}</p>
                    @enderror
                @endif
                <div class="w-full">
                    <label for="blockReason" class="block text-xs font-bold text-ds-ink mb-1.5">
                        {{ __('admin.users.reason_label') }}
                    </label>
                    <textarea id="blockReason" name="blockReason" wire:model="blockReason" rows="3"
                        placeholder="{{ __('admin.users.reason_placeholder') }}"
                        class="w-full px-4 py-3 rounded-xl text-sm bg-white text-ds-ink border border-ds-line placeholder:text-ds-faint focus:outline-none focus:ring-2 focus:ring-ds-blue/20 focus:border-ds-blue transition-colors"></textarea>
                    @error('blockReason')
                        <p class="text-xs text-ds-danger mt-1" role="alert">{{ $message }}</p>
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
