<x-layouts.app :title="__('home.how.title')" :navItems="$navItems">
    <div class="space-y-8">
        <div>
            <h1 class="text-xl font-bold text-text sm:text-2xl">{{ __('home.how.heading') }}</h1>
            <p class="mt-1 text-sm text-muted sm:text-base">{{ __('home.how.intro') }}</p>
        </div>

        <section aria-labelledby="how-roles-heading">
            <h2 id="how-roles-heading" class="text-lg font-bold text-text">
                {{ __('home.how.roles_title') }}
            </h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-ui.card>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                        <x-ui.icon name="user" class="h-6 w-6" />
                    </span>
                    <h3 class="mt-3 text-base font-semibold text-text">{{ __('home.how.role_buyer_title') }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ __('home.how.role_buyer_description') }}</p>
                </x-ui.card>
                <x-ui.card>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                        <x-ui.icon name="tag" class="h-6 w-6" />
                    </span>
                    <h3 class="mt-3 text-base font-semibold text-text">{{ __('home.how.role_seller_title') }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ __('home.how.role_seller_description') }}</p>
                </x-ui.card>
                <x-ui.card>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                        <x-ui.icon name="shield-check" class="h-6 w-6" />
                    </span>
                    <h3 class="mt-3 text-base font-semibold text-text">{{ __('home.how.role_inspector_title') }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ __('home.how.role_inspector_description') }}</p>
                </x-ui.card>
            </div>
        </section>

        <section aria-labelledby="how-steps-heading">
            <h2 id="how-steps-heading" class="text-lg font-bold text-text">
                {{ __('home.how.steps_title') }}
            </h2>
            <ol class="mt-4 space-y-4">
                @foreach ([
                    ['icon' => 'search', 'title' => __('home.how.step_1_title'), 'description' => __('home.how.step_1_description')],
                    ['icon' => 'document-text', 'title' => __('home.how.step_2_title'), 'description' => __('home.how.step_2_description')],
                    ['icon' => 'camera', 'title' => __('home.how.step_3_title'), 'description' => __('home.how.step_3_description')],
                    ['icon' => 'check-circle', 'title' => __('home.how.step_4_title'), 'description' => __('home.how.step_4_description')],
                ] as $index => $step)
                    <li>
                        <x-ui.card>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                                    <x-ui.icon :name="$step['icon']" class="h-6 w-6" />
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-text">
                                        {{ ($index + 1) . '. ' . $step['title'] }}
                                    </h3>
                                    <p class="mt-1 text-sm text-muted">
                                        {{ $step['description'] }}
                                    </p>
                                </div>
                            </div>
                        </x-ui.card>
                    </li>
                @endforeach
            </ol>
        </section>

        <x-ui.alert variant="info" :title="__('home.how.simulation_title')" :message="__('home.how.simulation_message')" />

        <section aria-labelledby="how-privacy-heading">
            <x-ui.card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary" aria-hidden="true">
                        <x-ui.icon name="shield-check" class="h-6 w-6" />
                    </span>
                    <div>
                        <h2 id="how-privacy-heading" class="text-lg font-bold text-text">
                            {{ __('home.how.privacy_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-muted">
                            {{ __('home.how.privacy_description') }}
                        </p>
                    </div>
                </div>
            </x-ui.card>
        </section>

        <div class="flex flex-col gap-2 sm:flex-row">
            <x-ui.button :href="route('catalog.index')" variant="primary" icon="search">
                {{ __('home.how.cta_browse') }}
            </x-ui.button>
            <x-ui.button :href="route('catalog.help')" variant="secondary" icon="information-circle">
                {{ __('home.how.cta_help') }}
            </x-ui.button>
        </div>
    </div>
</x-layouts.app>
