<x-layouts.app :title="__('home.title')" :navItems="$navItems">
    <div class="ds-sans -mx-4 sm:-mx-6 lg:-mx-8 -mt-8">
        {{-- Hero + Kategori: 1 Layar Penuh di Desktop agar Section Bawah Tidak Ngintip --}}
        <div class="lg:min-h-[calc(100vh-4rem)] flex flex-col justify-between">
            {{-- Hero Section --}}
            <section aria-labelledby="home-hero-heading" class="relative overflow-hidden bg-gradient-to-b from-ds-canvas via-ds-blue-soft/40 to-ds-canvas flex-1 flex flex-col justify-center py-10 sm:py-12 lg:py-14">
                <div class="absolute top-10 left-10 w-32 h-32 bg-ds-blue/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute bottom-10 right-20 w-48 h-48 bg-ds-yellow/15 rounded-full blur-3xl pointer-events-none"></div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative w-full">
                <div class="max-w-2xl mx-auto text-center space-y-6">
                    <h1 id="home-hero-heading" class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-ds-ink leading-[1.12] tracking-tight text-balance">
                        {{ __('home.hero_title') }}
                    </h1>
                    <p class="text-base sm:text-lg text-ds-muted max-w-xl mx-auto font-normal leading-relaxed">
                        {{ __('home.hero_subtitle') }}
                    </p>

                    {{-- Hero Search Bar --}}
                    <div class="pt-2 max-w-lg mx-auto">
                        <form method="GET" action="{{ route('catalog.index') }}" class="flex flex-col sm:flex-row items-center gap-2 p-2 bg-white rounded-2xl shadow-xl shadow-ds-ink/10 border border-ds-line/60">
                            <div class="flex items-center gap-3 px-3 w-full sm:w-auto flex-1">
                                <i aria-hidden="true" class="fa-solid fa-magnifying-glass text-ds-faint"></i>
                                <input type="text" name="q" maxlength="100" placeholder="{{ __('catalog.search_placeholder') }}" class="w-full py-2 bg-transparent text-sm font-medium text-ds-ink focus:outline-none placeholder:text-ds-faint">
                            </div>
                            <button type="submit" class="w-full sm:w-auto px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                                <span>{{ __('home.hero_browse_all') }}</span>
                                <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                            </button>
                        </form>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2 text-xs">
                        <a wire:navigate.hover href="{{ route('catalog.index', ['type' => 'car']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 font-semibold text-ds-muted hover:text-ds-blue transition-colors">
                            <i aria-hidden="true" class="fa-solid fa-car text-ds-blue"></i>
                            {{ __('home.hero_search_car') }}
                        </a>
                        <span class="text-ds-line">|</span>
                        <a wire:navigate.hover href="{{ route('catalog.index', ['type' => 'motorcycle']) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 font-semibold text-ds-muted hover:text-ds-blue transition-colors">
                            <i aria-hidden="true" class="fa-solid fa-motorcycle text-ds-blue"></i>
                            {{ __('home.hero_search_motorcycle') }}
                        </a>
                    </div>

                    {{-- Trust Badges --}}
                    <div class="pt-2 flex flex-wrap items-center justify-center gap-8 text-ds-muted text-xs font-semibold">
                        <div class="flex items-center gap-2">
                            <i aria-hidden="true" class="fa-solid fa-shield-halved text-ds-blue text-sm"></i>
                            <span>{{ __('home.trust_verified') }}</span>
                        </div>
                                <div class="flex items-center gap-2">
                                    <i aria-hidden="true" class="fa-solid fa-bolt text-ds-blue text-sm"></i>
                            <span>{{ __('home.trust_from_price') }}</span>
                        </div>
                                <div class="flex items-center gap-2">
                                    <i aria-hidden="true" class="fa-solid fa-location-dot text-ds-blue text-sm"></i>
                            <span>{{ __('home.trust_area') }}</span>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- Category Icon Navigation Bar --}}
        <section aria-label="Kategori Kendaraan" class="py-5 sm:py-6 bg-white border-y border-ds-line/60 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-4 sm:flex sm:items-center sm:justify-center gap-2 sm:gap-6 lg:gap-8">
                    <a wire:navigate.hover href="{{ route('catalog.index') }}" class="flex flex-col items-center gap-2 sm:gap-2.5 sm:min-w-[85px] group">
                        <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-ds-soft group-hover:bg-ds-blue group-hover:text-white text-ds-ink flex items-center justify-center text-lg sm:text-xl transition-all shadow-xs group-hover:-translate-y-1">
                            <i aria-hidden="true" class="fa-solid fa-border-all"></i>
                        </span>
                        <span class="text-xs font-bold text-ds-ink group-hover:text-ds-blue transition-colors text-center">{{ __('home.cat_all') }}</span>
                    </a>
                    <a wire:navigate.hover href="{{ route('catalog.index', ['type' => 'car']) }}" class="flex flex-col items-center gap-2 sm:gap-2.5 sm:min-w-[85px] group">
                        <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-ds-soft group-hover:bg-ds-blue group-hover:text-white text-ds-ink flex items-center justify-center text-lg sm:text-xl transition-all shadow-xs group-hover:-translate-y-1">
                            <i aria-hidden="true" class="fa-solid fa-car"></i>
                        </span>
                        <span class="text-xs font-bold text-ds-ink group-hover:text-ds-blue transition-colors text-center">{{ __('home.cat_car') }}</span>
                    </a>
                    <a wire:navigate.hover href="{{ route('catalog.index', ['type' => 'motorcycle']) }}" class="flex flex-col items-center gap-2 sm:gap-2.5 sm:min-w-[85px] group">
                        <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-ds-soft group-hover:bg-ds-blue group-hover:text-white text-ds-ink flex items-center justify-center text-lg sm:text-xl transition-all shadow-xs group-hover:-translate-y-1">
                            <i aria-hidden="true" class="fa-solid fa-motorcycle"></i>
                        </span>
                        <span class="text-xs font-bold text-ds-ink group-hover:text-ds-blue transition-colors text-center">{{ __('home.cat_motorcycle') }}</span>
                    </a>
                    <a wire:navigate.hover href="{{ route('catalog.index', ['inspected' => 'inspected']) }}" class="flex flex-col items-center gap-2 sm:gap-2.5 sm:min-w-[85px] group">
                        <span class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-ds-soft group-hover:bg-ds-blue group-hover:text-white text-ds-ink flex items-center justify-center text-lg sm:text-xl transition-all shadow-xs group-hover:-translate-y-1">
                            <i aria-hidden="true" class="fa-solid fa-shield-halved"></i>
                        </span>
                        <span class="text-xs font-bold text-ds-ink group-hover:text-ds-blue transition-colors text-center">{{ __('home.cat_inspected') }}</span>
                    </a>
                </div>
            </div>
        </section>
    </div>

        {{-- Featured: Sidebar + Grid --}}
        <section aria-labelledby="home-featured-heading" class="py-12 bg-ds-canvas">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-10 space-y-3">
                <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('home.featured_eyebrow') }}</span>
                <h2 id="home-featured-heading" class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('home.featured_title') }}</h2>
                <p class="text-sm text-ds-muted">{{ __('home.featured_subtitle') }}</p>
            </div>

            <div class="grid lg:grid-cols-12 gap-8 items-start">
                {{-- Sidebar (Floating Sticky di Desktop) --}}
                <aside class="lg:col-span-3 lg:sticky lg:top-20 z-10">
                    <div class="bg-white rounded-2xl p-5 border border-ds-line/80 shadow-xs space-y-4">
                        <h3 class="text-xs font-extrabold text-ds-faint uppercase tracking-wider">{{ __('home.browse_title') }}</h3>
                        <ul class="space-y-1.5 text-sm font-semibold text-ds-muted">
                            <li>
                                <a wire:navigate.hover href="{{ route('catalog.index', ['type' => 'car', 'price_max' => 100000000]) }}" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-ds-canvas hover:text-ds-ink transition-colors">
                                    <span>{{ __('home.browse_car_budget') }}</span>
                                    <i aria-hidden="true" class="fa-solid fa-chevron-right text-xs"></i>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate.hover href="{{ route('catalog.index', ['type' => 'motorcycle', 'price_max' => 20000000]) }}" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-ds-canvas hover:text-ds-ink transition-colors">
                                    <span>{{ __('home.browse_motor_budget') }}</span>
                                    <i aria-hidden="true" class="fa-solid fa-chevron-right text-xs"></i>
                                </a>
                            </li>
                            <li>
                                <a wire:navigate.hover href="{{ route('catalog.index', ['inspected' => 'inspected']) }}" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-ds-canvas hover:text-ds-ink transition-colors">
                                    <span>{{ __('home.browse_inspected') }}</span>
                                    <span class="bg-ds-green-soft text-ds-green-ink text-[10px] font-bold px-2 py-0.5 rounded-full">OK</span>
                                </a>
                            </li>
                        </ul>

                        <hr class="border-ds-line/60">

                        <div class="bg-ds-ink text-white rounded-2xl p-4 text-center space-y-3">
                            <h4 class="text-xs font-bold">{{ __('home.inspector_title') }}</h4>
                            <p class="text-[11px] text-ds-soft">{{ __('home.inspector_description') }}</p>
                            <a wire:navigate.hover href="{{ route('identity.register') }}" class="block w-full py-2 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-extrabold text-xs rounded-xl transition-all shadow-sm">
                                {{ __('home.inspector_action') }}
                            </a>
                        </div>
                    </div>
                </aside>

                    {{-- Cards Grid --}}
                    <div class="lg:col-span-9">
                        @if (count($cards) === 0)
                            <div class="bg-white rounded-2xl border border-ds-line/80 shadow-xs p-8 text-center max-w-2xl mx-auto">
                                <div class="w-14 h-14 mx-auto rounded-2xl bg-ds-soft text-ds-muted flex items-center justify-center text-xl mb-4">
                                    <i aria-hidden="true" class="fa-solid fa-magnifying-glass"></i>
                                </div>
                                <h3 class="text-lg font-extrabold text-ds-ink">{{ __('home.empty_title') }}</h3>
                                <p class="text-xs text-ds-muted mt-2 leading-relaxed">{{ __('home.empty_description') }}</p>
                                <div class="flex justify-center mt-6">
                                    <a wire:navigate.hover href="{{ route('catalog.index') }}" class="inline-flex items-center justify-center gap-2 px-7 py-3 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-bold text-sm rounded-xl transition-all shadow-md">
                                        <span>{{ __('home.empty_action') }}</span>
                                        <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                                @foreach ($cards as $card)
                                    <article class="group bg-white rounded-2xl border border-ds-line/80 shadow-xs hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between relative">
                                        <a wire:navigate.hover href="{{ route('catalog.show', ['listing' => $card->id]) }}" class="absolute inset-0 z-10" aria-label="{{ $card->title }}">
                                            <span class="sr-only">{{ $card->title }}</span>
                                        </a>
                                        <div>
                                            <div class="relative aspect-4/3 overflow-hidden bg-ds-soft">
                                                @if ($card->imageUrl)
                                                    <img src="{{ $card->imageUrl }}" alt="{{ $card->imageAlt }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-ds-faint text-3xl">
                                                        <i aria-hidden="true" class="fa-solid fa-car"></i>
                                                    </div>
                                                @endif
                                                @if ($card->inspected)
                                                    <span class="absolute top-3 left-3 bg-ds-green text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">Sudah Diinspeksi</span>
                                                @else
                                                    <span class="absolute top-3 left-3 bg-ds-soft text-ds-muted text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider">Tersedia</span>
                                                @endif
                                            </div>
                                            <div class="p-4 space-y-2">
                                                <h3 class="font-bold text-ds-ink text-sm group-hover:text-ds-blue transition-colors line-clamp-1">{{ $card->title }}</h3>
                                                <p class="text-xs text-ds-muted line-clamp-1">{{ $card->year }} · {{ $card->mileage ? number_format($card->mileage, 0, ',', '.').' km' : '-' }}</p>
                                                <div class="pt-2 flex items-center justify-between">
                                                    <span class="text-base font-extrabold text-ds-ink">{{ \App\Support\Money::format($card->price) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="px-4 py-3 bg-ds-canvas/80 border-t border-ds-line/60 flex items-center justify-between text-xs text-ds-muted font-medium">
                                            <span class="flex items-center gap-1">
                                                <i aria-hidden="true" class="fa-solid fa-location-dot text-ds-blue text-[11px]"></i> {{ $card->area }}
                                            </span>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                            <div class="mt-10 text-center">
                                <a href="{{ route('catalog.index') }}" class="px-8 py-3 bg-white border border-ds-line hover:border-ds-blue text-ds-ink hover:text-ds-blue font-bold text-xs rounded-xl transition-all shadow-xs hover:shadow-md inline-flex items-center gap-2">
                                    <span>{{ __('home.featured_more') }}</span>
                                    <i aria-hidden="true" class="fa-solid fa-arrows-rotate text-xs"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- How It Works --}}
        <section aria-labelledby="home-steps-heading" class="pt-12 sm:pt-16 pb-6 sm:pb-10 bg-white border-t border-ds-line/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12 space-y-3">
                    <span class="text-ds-blue text-xs font-extrabold uppercase tracking-wider">{{ __('home.steps_eyebrow') }}</span>
                    <h2 id="home-steps-heading" class="text-3xl font-extrabold text-ds-ink tracking-tight">{{ __('home.steps_title') }}</h2>
                    <p class="text-sm text-ds-muted">{{ __('home.steps_subtitle') }}</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ([
                        [
                            'number' => '01',
                            'title' => __('home.step_1_title'),
                            'description' => __('home.step_1_description'),
                            'svg' => '<svg viewBox="0 0 100 100" fill="none" class="w-full h-full" aria-hidden="true"><circle cx="48" cy="48" r="26" stroke="currentColor" stroke-width="2" /><path d="M68 68L88 88" stroke="currentColor" stroke-width="3" stroke-linecap="round" /><circle cx="48" cy="48" r="14" stroke="currentColor" stroke-width="1.5" stroke-dasharray="3 3" /></svg>',
                        ],
                        [
                            'number' => '02',
                            'title' => __('home.step_2_title'),
                            'description' => __('home.step_2_description'),
                            'svg' => '<svg viewBox="0 0 100 100" fill="none" class="w-full h-full" aria-hidden="true"><rect x="24" y="24" width="52" height="64" rx="8" stroke="currentColor" stroke-width="2" /><rect x="38" y="16" width="24" height="12" rx="3" stroke="currentColor" stroke-width="2" /><path d="M38 52L46 60L64 42" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" /><line x1="36" y1="72" x2="64" y2="72" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-dasharray="2 3" /></svg>',
                        ],
                        [
                            'number' => '03',
                            'title' => __('home.step_3_title'),
                            'description' => __('home.step_3_description'),
                            'svg' => '<svg viewBox="0 0 100 100" fill="none" class="w-full h-full" aria-hidden="true"><path d="M26 20H62L78 36V84C78 87.3 75.3 90 72 90H26C22.7 90 20 87.3 20 84V26C20 22.7 22.7 20 26 20Z" stroke="currentColor" stroke-width="2" /><path d="M62 20V36H78" stroke="currentColor" stroke-width="2" /><line x1="32" y1="50" x2="66" y2="50" stroke="currentColor" stroke-width="2" stroke-linecap="round" /><line x1="32" y1="62" x2="66" y2="62" stroke="currentColor" stroke-width="2" stroke-linecap="round" /><line x1="32" y1="74" x2="52" y2="74" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>',
                        ],
                        [
                            'number' => '04',
                            'title' => __('home.step_4_title'),
                            'description' => __('home.step_4_description'),
                            'svg' => '<svg viewBox="0 0 100 100" fill="none" class="w-full h-full" aria-hidden="true"><path d="M50 18L76 28V50C76 68 50 84 50 84C50 84 24 68 24 50V28L50 18Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" /><path d="M40 50L47 57L62 42" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" /></svg>',
                        ],
                    ] as $step)
                        <div class="bg-ds-canvas rounded-2xl p-6 border border-ds-line/60 relative overflow-hidden group hover:bg-white hover:shadow-xl hover:border-ds-blue/40 transition-all duration-300">
                            {{-- Plain subtle SVG watermark in background --}}
                            <div class="absolute -right-3 -bottom-3 w-28 h-28 text-ds-line/50 group-hover:text-ds-blue/15 transition-colors pointer-events-none select-none">
                                {!! $step['svg'] !!}
                            </div>

                            <div class="relative z-10">
                                <span class="text-3xl font-extrabold text-ds-blue tracking-tight block mb-4">{{ $step['number'] }}</span>
                                <h3 class="text-lg font-bold text-ds-ink mb-2">{{ $step['title'] }}</h3>
                                <p class="text-xs text-ds-muted leading-relaxed">{{ $step['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Inspector Banner --}}
        <section aria-labelledby="home-inspector-heading" class="pt-2 sm:pt-4 pb-12 sm:pb-16 bg-ds-canvas">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-ds-ink rounded-3xl p-8 lg:p-12 text-white relative overflow-hidden shadow-xl">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8 relative z-10">
                        <div class="max-w-2xl space-y-4">
                            <h2 id="home-inspector-heading" class="text-3xl sm:text-4xl font-extrabold leading-tight">
                                {{ __('home.inspector_title') }}
                            </h2>
                            <p class="text-ds-soft text-sm sm:text-base font-normal leading-relaxed">
                                {{ __('home.inspector_description') }}
                            </p>
                        </div>
                        <div class="shrink-0">
                            <a href="{{ route('identity.register') }}" class="inline-flex items-center gap-2 px-7 py-3.5 bg-ds-yellow hover:bg-ds-yellow-deep text-ds-ink font-extrabold text-sm rounded-xl transition-all shadow-md">
                                <span>{{ __('home.inspector_action') }}</span>
                                <i aria-hidden="true" class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>
</x-layouts.app>
