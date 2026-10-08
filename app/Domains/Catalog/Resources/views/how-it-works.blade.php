<x-layouts.app :title="__('home.how.title')" :navItems="$navItems">
    <div class="ds-sans space-y-12 pb-12">
        <div class="text-center max-w-2xl mx-auto space-y-3">
            <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">Panduan</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-ds-ink tracking-tight">{{ __('home.how.heading') }}</h1>
            <p class="text-sm sm:text-base text-ds-muted leading-relaxed">{{ __('home.how.intro') }}</p>
        </div>

        <section aria-labelledby="how-roles-heading">
            <div class="text-center max-w-2xl mx-auto mb-8 space-y-2">
                <h2 id="how-roles-heading" class="text-2xl font-extrabold text-ds-ink tracking-tight">
                    {{ __('home.how.roles_title') }}
                </h2>
            </div>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'fa-user', 'title' => __('home.how.role_buyer_title'), 'description' => __('home.how.role_buyer_description')],
                    ['icon' => 'fa-tag', 'title' => __('home.how.role_seller_title'), 'description' => __('home.how.role_seller_description')],
                    ['icon' => 'fa-shield-halved', 'title' => __('home.how.role_inspector_title'), 'description' => __('home.how.role_inspector_description')],
                ] as $role)
                    <div class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 p-6">
                        <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-4 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                            <i aria-hidden="true" class="fa-solid {{ $role['icon'] }}"></i>
                        </div>
                        <h3 class="text-lg font-bold text-ds-ink">{{ $role['title'] }}</h3>
                        <p class="mt-2 text-xs text-ds-muted leading-relaxed">{{ $role['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="how-steps-heading">
            <div class="text-center max-w-2xl mx-auto mb-8 space-y-2">
                <h2 id="how-steps-heading" class="text-2xl font-extrabold text-ds-ink tracking-tight">
                    {{ __('home.how.steps_title') }}
                </h2>
            </div>
            <ol class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['icon' => 'fa-magnifying-glass', 'title' => __('home.how.step_1_title'), 'description' => __('home.how.step_1_description')],
                    ['icon' => 'fa-file-lines', 'title' => __('home.how.step_2_title'), 'description' => __('home.how.step_2_description')],
                    ['icon' => 'fa-camera', 'title' => __('home.how.step_3_title'), 'description' => __('home.how.step_3_description')],
                    ['icon' => 'fa-circle-check', 'title' => __('home.how.step_4_title'), 'description' => __('home.how.step_4_description')],
                ] as $index => $step)
                    <li class="bg-ds-canvas rounded-2xl p-6 border border-ds-line/60 relative group hover:bg-white hover:shadow-xl transition-all duration-300">
                        <div class="w-14 h-14 rounded-2xl bg-ds-blue text-white flex items-center justify-center text-xl mb-5 shadow-lg shadow-ds-blue/20 group-hover:scale-110 transition-transform">
                            <i aria-hidden="true" class="fa-solid {{ $step['icon'] }}"></i>
                        </div>
                        <span class="text-4xl font-extrabold text-ds-line absolute top-6 right-6">0{{ $index + 1 }}</span>
                        <h3 class="text-lg font-bold text-ds-ink mb-2">{{ $step['title'] }}</h3>
                        <p class="text-xs text-ds-muted leading-relaxed">{{ $step['description'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="bg-ds-blue-soft/60 border border-ds-line/60 rounded-2xl p-6 flex gap-4">
            <div class="w-11 h-11 shrink-0 rounded-2xl bg-ds-blue text-white flex items-center justify-center">
                <i aria-hidden="true" class="fa-solid fa-circle-info"></i>
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-ds-ink">{{ __('home.how.simulation_title') }}</h3>
                <p class="mt-1 text-xs text-ds-muted leading-relaxed">{{ __('home.how.simulation_message') }}</p>
            </div>
        </div>

        <section aria-labelledby="how-privacy-heading">
            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-6 flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="w-14 h-14 shrink-0 rounded-2xl bg-ds-green text-white flex items-center justify-center text-xl shadow-lg shadow-ds-green/20">
                    <i aria-hidden="true" class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h2 id="how-privacy-heading" class="text-lg font-extrabold text-ds-ink">
                        {{ __('home.how.privacy_title') }}
                    </h2>
                    <p class="mt-2 text-xs text-ds-muted leading-relaxed">
                        {{ __('home.how.privacy_description') }}
                    </p>
                </div>
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                <i aria-hidden="true" class="fa-solid fa-magnifying-glass text-xs"></i>
                {{ __('home.how.cta_browse') }}
            </a>
            <a href="{{ route('catalog.help') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-white border border-ds-line hover:border-ds-blue hover:text-ds-blue text-ds-ink font-bold text-sm rounded-xl transition-all shadow-xs">
                <i aria-hidden="true" class="fa-solid fa-circle-question text-xs"></i>
                {{ __('home.how.cta_help') }}
            </a>
        </div>
    </div>
</x-layouts.app>
