<x-layouts.app :title="__('home.help.title')" :navItems="$navItems">
    <div class="space-y-8">
        <div>
            <h1 class="text-xl font-bold text-text sm:text-2xl">{{ __('home.help.heading') }}</h1>
            <p class="mt-1 text-sm text-muted sm:text-base">{{ __('home.help.intro') }}</p>
        </div>

        <section aria-labelledby="help-faq-heading">
            <h2 id="help-faq-heading" class="text-lg font-bold text-text">
                {{ __('home.help.faq_title') }}
            </h2>
            <div class="mt-4 space-y-3">
                @foreach ([
                    ['q' => __('home.help.faq_q1'), 'a' => __('home.help.faq_a1')],
                    ['q' => __('home.help.faq_q2'), 'a' => __('home.help.faq_a2')],
                    ['q' => __('home.help.faq_q3'), 'a' => __('home.help.faq_a3')],
                    ['q' => __('home.help.faq_q4'), 'a' => __('home.help.faq_a4')],
                    ['q' => __('home.help.faq_q5'), 'a' => __('home.help.faq_a5')],
                    ['q' => __('home.help.faq_q6'), 'a' => __('home.help.faq_a6')],
                ] as $faq)
                    <details class="rounded-xl border border-muted/20 bg-surface px-4 py-3">
                        <summary class="flex min-h-[44px] cursor-pointer list-none items-center justify-between gap-2 text-sm font-semibold text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                            <span>{{ $faq['q'] }}</span>
                            <x-ui.icon name="chevron-down" class="h-4 w-4 shrink-0" />
                        </summary>
                        <p class="mt-2 border-t border-muted/15 pt-3 text-sm text-muted">
                            {{ $faq['a'] }}
                        </p>
                    </details>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="help-cta-heading">
            <x-ui.card>
                <h2 id="help-cta-heading" class="text-lg font-bold text-text">
                    {{ __('home.help.cta_title') }}
                </h2>
                <p class="mt-1 text-sm text-muted">
                    {{ __('home.help.cta_message') }}
                </p>
                <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <x-ui.button :href="route('catalog.how-it-works')" variant="primary" icon="document-text">
                        {{ __('home.help.cta_how') }}
                    </x-ui.button>
                    <x-ui.button :href="route('catalog.index')" variant="secondary" icon="search">
                        {{ __('home.help.cta_browse') }}
                    </x-ui.button>
                </div>
            </x-ui.card>
        </section>
    </div>
</x-layouts.app>
